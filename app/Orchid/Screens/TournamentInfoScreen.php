<?php

namespace App\Orchid\Screens;

use App\Models\Round;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Button;
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
            $points = Round::select(DB::raw("
                SUM(CASE
                WHEN games.home_user_id = {$player->id} AND games.goals_home > games.goals_away THEN 2
                WHEN games.away_user_id = {$player->id} AND games.goals_away > games.goals_home THEN 2
                ELSE 0
                END) AS points
            "))->leftJoin('games', 'rounds.game_id', '=', 'games.id')
                ->where('rounds.tournament_id', $this->tournament->id)
                ->where('rounds.home_user_id', $player->id)
                ->orWhere('rounds.away_user_id', $player->id)
                ->pluck('points')->first();
            $leaderboard[] = new Repository(['name' => $player->name, 'points' => $points]);
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
        foreach($currentRound as $roundItem) {
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
                    TD::make('name')->cantHide(),
                TD::make('points')->cantHide()
            ])->title('Leaderboard'),
        ];

        if ($this->currentRound && $this->currentRound < $this->tournament->id) {
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
                        return '<a href="/crud/view/game-resources/' . $game['id'] . '">' . $game['goals_home'] . " - " . $game['goals_away'] . '</a>';
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
                Button::make(__('tournaments.show_previous_rounds'))
            ]);
        }

        return $content;
    }
}
