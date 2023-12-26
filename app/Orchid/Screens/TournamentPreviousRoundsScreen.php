<?php

namespace App\Orchid\Screens;

use App\Models\Round;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Orchid\Screen\Action;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;

class TournamentPreviousRoundsScreen extends Screen
{
    public function __construct(Request $request)
    {
        $this->tournament = Tournament::findOrFail($request->route('id'));
        $this->rounds = Round::where('tournament_id', $request->route('id'))
            ->whereNotNull('game_id')
            ->orderBy('round', 'asc')
            ->with('home_user')
            ->with('home_team')
            ->with('away_team')
            ->with('away_user')
            ->with('game')
            ->get();
    }


    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $query = [];
        foreach($this->rounds as $round) {
            $query['round' . $round->round][] = new Repository($round->toArray());
        }
        $this->query = $query;
        return $query;
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('tournaments.all_rounds_results') . " (" . $this->tournament->name . ")";
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
        for($i = 0; $i < count($this->query); $i++) {
            $content[] = Layout::table('round' . ($i + 1), [
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
                    $result = $game['goals_home'] . " - " . $game['goals_away'];

                    if ($game['win_type'] != 'regular') {
                        $result .= " " . __('games.win_type_short_' . $game['win_type']);
                    }

                    return '<a href="/crud/view/game-resources/' . $game['id'] . '">' . $result . '</a>';
                })->cantHide(),
                TD::make('away_team', __('games.away_team'))->render(function (Repository $repository) {
                    $teamName = $repository->get('away_team')['name'];
                    $asset = asset('logos/' . $teamName . '.svg');
                    return '<img height="25" src="' . (str_contains($teamName, 'All-Stars') ? asset('logos/nhl.svg') : $asset) . '" />';
                })->cantHide(),
                TD::make('away_user', __('games.away'))->render(function (Repository $repository) {
                    return $repository->get('away_user')['name'];
                })->cantHide(),
            ])->title(__('tournaments.round') . " " . ($i+1));
        }

        return $content;
    }
}
