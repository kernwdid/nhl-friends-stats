<?php

namespace App\Orchid\Screens;

use App\Models\Round;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class TournamentInfoScreen extends Screen
{
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->tournament = Tournament::findOrFail($this->request->route('id'));
        $this->currentRound = Round::select(DB::raw(
            "MIN(round)",
        ))
            ->whereNull('game_id')
            ->where('tournament_id', $this->tournament->id)
            ->groupBy('round')
            ->orderBy('round', 'ASC')
            ->pluck('min')
            ->first();
    }

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $leaderboard = [];
        foreach ($this->tournament->players as $player) {
            $stats = Round::select(DB::raw("
                SUM(CASE
                WHEN games.home_user_id = {$player->id} AND games.goals_home > games.goals_away THEN 2
                WHEN games.home_user_id = {$player->id} AND games.goals_home < games.goals_away AND win_type != 'regular' THEN 1
                WHEN games.away_user_id = {$player->id} AND games.goals_away > games.goals_home THEN 2
                WHEN games.away_user_id = {$player->id} AND games.goals_away < games.goals_home AND win_type != 'regular' THEN 1
                ELSE 0
                END) AS points,
                SUM(CASE
                WHEN games.home_user_id = {$player->id} THEN games.goals_home
                WHEN games.away_user_id = {$player->id} THEN games.goals_away
                ELSE 0
                END) AS goals_scored,
                SUM(CASE
                WHEN games.home_user_id = {$player->id} THEN games.goals_away
                WHEN games.away_user_id = {$player->id} THEN games.goals_home
                ELSE 0
                END) AS goals_received
            "))->leftJoin('games', 'rounds.game_id', '=', 'games.id')
                ->where('rounds.tournament_id', $this->tournament->id)
                ->where(function ($query) use ($player) {
                    $query->where('games.home_user_id', $player->id)
                        ->orWhere('games.away_user_id', $player->id);
                })->first();

            $gamesPlayed = Round::where('tournament_id', $this->tournament->id)
                ->whereNotNull('game_id')
                ->where(function ($query) use ($player) {
                    $query->where('rounds.home_user_id', $player->id)
                        ->orWhere('rounds.away_user_id', $player->id);
                })
                ->count();

            $leaderboard[] = new Repository([
                'name' => $player->name,
                'games_played' => $gamesPlayed,
                'points' => $stats['points'] ?? 0,
                'goals' => ($stats['goals_scored'] ?? 0) . ":" . ($stats['goals_received'] ?? 0)
            ]);
        }

        usort($leaderboard, function ($a, $b) {
            return $b['points'] - $a['points'];
        });

        $index = 1;
        foreach ($leaderboard as $entry) {
            $entry['index'] = $index++;
        }

        $sortBy = $this->request->query('sort');
        if ($sortBy) {
            $sort = $sortBy;
            $desc = $sortBy[0] == '-';
            if ($desc) {
                $sort = substr($sort, 1);
            }
            usort($leaderboard, function ($a, $b) use($sort, $desc) {
                if ($desc) {
                    if (is_numeric($b[$sort]) && is_numeric($a[$sort])) {
                        return $b[$sort] - $a[$sort];
                    }
                    return strcmp($b[$sort], $a[$sort]);
                }
                if (is_numeric($b[$sort]) && is_numeric($a[$sort])) {
                    return $a[$sort] - $b[$sort];
                }
                return strcmp($a[$sort], $b[$sort]);
            });
        }

        $currentRound = Round::where('tournament_id', $this->tournament->id)
            ->where('round', $this->currentRound)
            ->with('home_user')
            ->with('home_team')
            ->with('away_team')
            ->with('away_user')
            ->with('game')
            ->get();

        $roundItems = [];
        foreach ($currentRound as $roundItem) {
            $roundItems[] = new Repository($roundItem->toArray());
        }

        return [
            'leaderboard' => $leaderboard,
            'current-round' => $roundItems
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->tournament->name;
    }

    /**
     * The screen's action buttons.
     *
     * @return Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return Layout[]|string[]
     */
    public function layout(): iterable
    {
        $content = [
            Layout::table('leaderboard', [
                TD::make('index', 'POS')->sort()->cantHide(),
                TD::make('name')->cantHide()->sort()->cantHide(),
                TD::make('games_played', 'GP')->cantHide()->sort()->alignRight(),
                TD::make('goals', 'G')->cantHide()->sort()->alignRight(),
                TD::make('points', 'P')->cantHide()->sort()->alignRight(),
            ])->title('Leaderboard'),
        ];

        if ($this->currentRound) {
            $content[] = Layout::table('current-round', [
                TD::make('home_user', __('games.home'))->render(function (Repository $repository) {
                    return $repository->get('home_user')['name'];
                })->cantHide(),
                TD::make('home_team', __('games.home_team'))->render(function (Repository $repository) {
                    $teamName = $repository->get('home_team')['name'];
                    $asset = asset('logos/' . $teamName . '.svg');
                    return '<img height="25" src="' . (str_contains($teamName, 'All-Stars') ? asset('logos/nhl.svg') : $asset) . '" />';
                })->cantHide(),
                TD::make('game', __('games.result'))->render(function (Repository $repository) {
                    $game = $repository->get('game');
                    if ($game) {
                        $result = $game['goals_home'] . " - " . $game['goals_away'];

                        if ($game['win_type'] != 'regular') {
                            $result .= " " . __('games.win_type_short_' . $game['win_type']);
                        }

                        return '<a href="/crud/view/game-resources/' . $game['id'] . '">' . $result . '</a>';
                    }
                    $currentUser = $this->request->user()->id;
                    if ($currentUser != $repository->get('home_user_id') && $currentUser != $repository->get('away_user_id')) {
                        return 'n.A';
                    }

                    $params = '?home_team_id=' . $repository->get('home_team_id');
                    $params .= '&away_team_id=' . $repository->get('away_team_id');
                    $params .= '&home_user_id=' . $repository->get('home_user_id');
                    $params .= '&away_user_id=' . $repository->get('away_user_id');
                    $params .= '&round_id=' . $repository->get('id');
                    $params .= '&tournament_id=' . $this->tournament->id;

                    return '<a href="/upload_result' . $params . '"><b>n.A</b></a>';
                })->cantHide(),
                TD::make('away_team', __('games.away_team'))->render(function (Repository $repository) {
                    $teamName = $repository->get('away_team')['name'];
                    $asset = asset('logos/' . $teamName . '.svg');
                    return '<img height="25" src="' . (str_contains($teamName, 'All-Stars') ? asset('logos/nhl.svg') : $asset) . '" />';
                })->cantHide(),
                TD::make('away_user', __('games.away'))->render(function (Repository $repository) {
                    return $repository->get('away_user')['name'];
                })->cantHide(),
            ])->title(__('tournaments.current_round') . " " . $this->currentRound . " / " . $this->tournament->rounds);
        } else {
            $content[] = Layout::block([])
                ->title(__('tournaments.finished'));
        }

        if (!$this->currentRound || $this->currentRound > 1) {
            $content[] = Layout::rows([
                Link::make(__('tournaments.all_rounds_results'))->href('/previous-rounds/' . $this->tournament->id)
            ]);
        }

        return $content;
    }
}
