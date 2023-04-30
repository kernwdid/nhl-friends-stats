<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Orchid\Platform\Models\Role;
use Orchid\Screen\Action;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class DashboardScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $query = [];
        $playerRole = Role::where('slug', 'player')->firstOrFail();
        $isPlayer = auth()->user()->inRole($playerRole);

        if ($isPlayer) {
            $userId = auth()->user()->id;
            $totalGames = Game::where('home_user_id', $userId)->orWhere('away_user_id', $userId)->count();

            $metrics = [
                'total_games' => ['value' => number_format($totalGames)],
            ];

            $usersPlayed = Game::select('home_user_id AS id')
                ->where('away_user_id', $userId)
                ->union(Game::select('away_user_id AS id')
                    ->where('home_user_id', $userId))
                ->get();

            foreach ($usersPlayed as $player) {
                $wins = Game::where('home_user_id', $userId)
                    ->where('away_user_id', $player->id)
                    ->whereRaw('goals_home > goals_away')
                    ->orWhere('away_user_id', $userId)
                    ->where('home_user_id', $player->id)
                    ->whereRaw('goals_home < goals_away')
                    ->count();

                $highestWin = Game::where('home_user_id', $userId)
                    ->where('away_user_id', $player->id)
                    ->whereRaw('goals_home > goals_away')
                    ->select(DB::raw('(games.goals_home AS first, games.goals_away AS second, games.goals_home - games.goals_away) AS highest_win'))
                    ->orWhere('away_user_id', $userId)
                    ->where('home_user_id', $player->id)
                    ->whereRaw('goals_home < goals_away')
                    ->select(DB::raw('games.goals_home AS second, games.goals_away AS first, games.goals_away - games.goals_home AS highest_win'))
                    ->orderBy('highest_win', 'desc')
                    ->first();

                $losses = Game::where('home_user_id', $userId)
                    ->where('away_user_id', $player->id)
                    ->whereRaw('goals_home < goals_away')
                    ->orWhere('away_user_id', $userId)
                    ->where('home_user_id', $player->id)
                    ->whereRaw('goals_home > goals_away')
                    ->count();

                $highestLoss = Game::where('home_user_id', $userId)
                    ->where('away_user_id', $player->id)
                    ->whereRaw('goals_home < goals_away')
                    ->select(DB::raw('games.goals_home AS first, games.goals_away AS second, (games.goals_away - games.goals_home) AS highest_loss'))
                    ->orWhere('away_user_id', $userId)
                    ->where('home_user_id', $player->id)
                    ->whereRaw('goals_home > goals_away')
                    ->select(DB::raw('games.goals_home AS second, games.goals_away AS first, games.goals_home - games.goals_away AS highest_loss'))
                    ->orderBy('highest_loss', 'asc')
                    ->first();

                $metrics['wins_' . $player['id']] = ['value' => number_format($wins)];
                $metrics['losses_' . $player['id']] = ['value' => number_format($losses)];
                $metrics['highest_win_' . $player['id']] = ['value' => $highestWin['first'] . " - " . $highestWin['second']];
                $metrics['highest_loss_' . $player['id']] = ['value' => $highestLoss['first'] . " - " . $highestLoss['second']];

            }

            $query['metrics'] = $metrics;
        }
        return $query;
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Dashboard';
    }

    /**
     * Display header description.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return __('dashboard.description');
    }

    /**
     * The screen's action buttons.
     *
     * @return Action[]
     */
    public function commandBar(): iterable
    {
        return [
//            Link::make('Kaffee kaufen')
            //              ->href('https://www.buymeacoffee.com/didiweinh')
            //            ->icon('cup'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        $userId = auth()->user()->id;
        $content = [
            Layout::view('platform::partials.update-assets'),
            Layout::view('dashboard')
        ];
        $isPlayer = auth()->user()->inRole(Role::where('slug', 'player')->firstOrFail());
        if ($isPlayer) {
            $metrics = [
                __('games.total') => 'metrics.total_games',
            ];

            $usersPlayed = Game::select('users.name', 'games.home_user_id AS id')
                ->leftJoin('users', 'users.id', '=', 'games.home_user_id')
                ->where('away_user_id', $userId)
                ->union(Game::select('users.name AS name', 'games.away_user_id AS id')
                    ->leftJoin('users', 'users.id', '=', 'games.away_user_id')
                    ->where('home_user_id', $userId))
                ->get();

            foreach ($usersPlayed as $player) {
                $name = $player['name'];
                $metrics[__('dashboard.wins_against') . " " . $name] = 'metrics.wins_' . $player['id'];
                $metrics[__('dashboard.losses_against') . " " . $name] = 'metrics.losses_' . $player['id'];
                $metrics[__('dashboard.highest_win_against') . " " . $name] = 'metrics.highest_win_' . $player['id'];
                $metrics[__('dashboard.highest_loss_against') . " " . $name] = 'metrics.highest_loss_' . $player['id'];
            }

            $content[] = Layout::metrics($metrics);

        }
        return $content;
    }
}
