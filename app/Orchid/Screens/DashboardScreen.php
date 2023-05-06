<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Helpers\DateHelper;
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
            $totalStats = Game::select(DB::raw("
            COUNT(*) as games,
            COALESCE(SUM(CASE
                WHEN home_user_id = {$userId} THEN goals_home
                WHEN away_user_id = {$userId} THEN goals_away
                ELSE 0
            END), 0) AS goals,
            COALESCE(SUM(CASE
                WHEN home_user_id = {$userId} THEN shots_home
                WHEN away_user_id = {$userId} THEN shots_away
                ELSE 0
            END),0) AS shots,
            COALESCE(SUM(CASE
                WHEN home_user_id = {$userId} THEN hits_home
                WHEN away_user_id = {$userId} THEN hits_away
                ELSE 0
            END), 0) AS hits"))
                ->where('home_user_id', $userId)
                ->orWhere('away_user_id', $userId)
                ->first();

            $totalMetrics = [
                'total_games' => ['value' => number_format($totalStats['games'])],
                'total_goals' => ['value' => number_format($totalStats['goals'])],
                'goals_per_game' => ['value' => $totalStats['goals'] ? round($totalStats['goals'] / $totalStats['games'], 2) : $totalStats['goals']],
                'total_shots' => ['value' => number_format($totalStats['shots'])],
                'total_hits' => ['value' => number_format($totalStats['hits'])],
                'shots_for_goal' => ['value' => $totalStats['shots'] ? round($totalStats['shots'] / $totalStats['goals'], 2) : 0],
            ];
            $playerMetrics = [];

            $usersPlayed = Game::select('home_user_id AS id')
                ->where('away_user_id', $userId)
                ->union(Game::select('away_user_id AS id')
                    ->where('home_user_id', $userId))
                ->get();

            foreach ($usersPlayed as $player) {
                if ($player->id == $userId) {
                    continue;
                }
                $highestWin = Game::where('home_user_id', $userId)
                    ->where('away_user_id', $player->id)
                    ->whereRaw('goals_home > goals_away')
                    ->select(DB::raw('games.goals_home AS first, games.goals_away AS second, ABS(games.goals_home - games.goals_away) AS highest_win'))
                    ->orWhere('away_user_id', $userId)
                    ->where('home_user_id', $player->id)
                    ->whereRaw('goals_home < goals_away')
                    ->select(DB::raw('games.goals_home AS second, games.goals_away AS first, ABS(games.goals_away - games.goals_home) AS highest_win'))
                    ->orderBy('highest_win', 'desc')
                    ->first();

                $playerStats = Game::select(DB::raw("
                    SUM(CASE
                    WHEN home_user_id = {$userId} AND goals_home > goals_away THEN 1
                    WHEN away_user_id = {$userId} AND goals_away > goals_home THEN 1
                    ELSE 0
                    END) as wins,
                    SUM(CASE
                    WHEN home_user_id = {$userId} AND goals_home < goals_away THEN 1
                    WHEN away_user_id = {$userId} AND goals_away < goals_home THEN 1
                    ELSE 0
                    END) as losses,
                    COALESCE(AVG(CASE
                        WHEN home_user_id = {$userId} THEN time_in_offense_home_in_seconds
                        WHEN away_user_id = {$userId} THEN time_in_offense_away_in_seconds
                    END), 0) AS avg_offense_time,
                    COALESCE(ROUND(AVG(CASE
                        WHEN home_user_id = {$userId} THEN hits_home
                        WHEN away_user_id = {$userId} THEN hits_away
                    END)::numeric, 2), 0) AS avg_hits,
                    ROUND(AVG(CASE
                        WHEN home_user_id = {$userId} THEN goals_home
                        WHEN away_user_id = {$userId} THEN goals_away
                    END)::numeric, 2) as avg_goals,
                    ROUND(AVG(CASE
                        WHEN home_user_id = {$userId} THEN shots_home
                        WHEN away_user_id = {$userId} THEN shots_away
                    END)::numeric, 2) as avg_shots,
                    ROUND(AVG(CASE
                        WHEN home_user_id = {$userId} THEN pass_percentage_home
                        WHEN away_user_id = {$userId} THEN pass_percentage_away
                    END)::numeric, 2) as avg_pass_percentage
                "))->where('home_user_id', $userId)->where('away_user_id', $player->id)
                    ->orWhere('away_user_id', $userId)->where('home_user_id', $player->id)->first();

                $highestLoss = Game::where('home_user_id', $userId)
                    ->where('away_user_id', $player->id)
                    ->whereRaw('goals_home < goals_away')
                    ->select(DB::raw('games.goals_home AS first, games.goals_away AS second, ABS(games.goals_home - games.goals_away) AS highest_loss'))
                    ->orWhere('away_user_id', $userId)
                    ->where('home_user_id', $player->id)
                    ->whereRaw('goals_home > goals_away')
                    ->select(DB::raw('games.goals_home AS second, games.goals_away AS first, ABS(games.goals_away - games.goals_home) AS highest_loss'))
                    ->orderBy('highest_loss', 'desc')
                    ->first();

                $highestWinResult = "-";
                if ($highestWin) {
                    if ($highestWin['first'] > $highestWin['second']) {
                        $highestWinResult = $highestWin['first'] . " - " . $highestWin['second'];
                    } else {
                        $highestWinResult = $highestWin['second'] . " - " . $highestWin['first'];
                    }
                }
                $highestLossResult = "-";
                if ($highestLoss) {
                    if ($highestLoss['first'] < $highestLoss['second']) {
                        $highestLossResult = $highestLoss['first'] . " - " . $highestLoss['second'];
                    } else {
                        $highestLossResult = $highestLoss['second'] . " - " . $highestLoss['first'];
                    }
                }

                $playerMetrics['wins_' . $player['id']] = ['value' => intval($playerStats['wins'])];
                $playerMetrics['losses_' . $player['id']] = ['value' => $playerStats['losses']];
                $playerMetrics['highest_win_' . $player['id']] = ['value' => $highestWinResult];
                $playerMetrics['highest_loss_' . $player['id']] = ['value' => $highestLossResult];
                $playerMetrics['avg_goals_' . $player['id']] = ['value' => $playerStats['avg_goals']];
                $playerMetrics['avg_shots_' . $player['id']] = ['value' => $playerStats['avg_shots']];
                $playerMetrics['avg_hits_' . $player['id']] = ['value' => $playerStats['avg_hits']];
                $playerMetrics['avg_pass_percentage_' . $player['id']] = ['value' => $playerStats['avg_pass_percentage']];
                $playerMetrics['avg_time_in_offense_against_' . $player['id']] = ['value' =>
                    DateHelper::minuteAndSecondFormatFromSeconds($playerStats['avg_offense_time'])];
            }

            $query['total_metrics'] = $totalMetrics;
            $query['player_metrics'] = $playerMetrics;
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
            $totalMetrics = [
                __('games.total') => 'total_metrics.total_games',
                __('dashboard.total_goals') => 'total_metrics.total_goals',
                __('dashboard.goals_per_game') => 'total_metrics.goals_per_game',
                __('dashboard.total_shots') => 'total_metrics.total_shots',
                __('dashboard.total_shots_for_goal') => 'total_metrics.shots_for_goal',
                __('dashboard.total_hits') => 'total_metrics.total_hits',
            ];
            $playerMetrics = [];

            $usersPlayed = Game::select('users.name', 'games.home_user_id AS id')
                ->leftJoin('users', 'users.id', '=', 'games.home_user_id')
                ->where('away_user_id', $userId)
                ->union(Game::select('users.name AS name', 'games.away_user_id AS id')
                    ->leftJoin('users', 'users.id', '=', 'games.away_user_id')
                    ->where('home_user_id', $userId))
                ->get();

            foreach ($usersPlayed as $player) {
                if ($player->id == $userId) {
                    continue;
                }
                $name = $player['name'];
                $playerMetrics[__('dashboard.wins_against') . " " . $name] = 'player_metrics.wins_' . $player['id'];
                $playerMetrics[__('dashboard.losses_against') . " " . $name] = 'player_metrics.losses_' . $player['id'];
                $playerMetrics[__('dashboard.highest_win_against') . " " . $name] = 'player_metrics.highest_win_' . $player['id'];
                $playerMetrics[__('dashboard.highest_loss_against') . " " . $name] = 'player_metrics.highest_loss_' . $player['id'];
                $playerMetrics[__('dashboard.avg_goals_against') . " " . $name] = 'player_metrics.avg_goals_' . $player['id'];
                $playerMetrics[__('dashboard.avg_shots_against') . " " . $name] = 'player_metrics.avg_shots_' . $player['id'];
                $playerMetrics[__('dashboard.avg_hits_against') . " " . $name] = 'player_metrics.avg_hits_' . $player['id'];
                $playerMetrics[__('dashboard.avg_pass_percentage') . " " . $name] = 'player_metrics.avg_pass_percentage_' . $player['id'];
                $playerMetrics[__('dashboard.avg_time_in_offense_against') . " " . $name] = 'player_metrics.avg_time_in_offense_against_' . $player['id'];
            }

            $content[] = Layout::metrics($totalMetrics);
            $content[] = Layout::metrics($playerMetrics);


        }
        return $content;
    }
}
