<?php

namespace App\Orchid\Resources;

use App\Helpers\DateHelper;
use App\Models\Game;
use Orchid\Crud\Filters\DefaultSorted;
use Orchid\Crud\Resource;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class GameResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Game::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Get the columns displayed by the resource.
     *
     * @return TD[]
     */
    public function columns(): array
    {
        return [

            TD::make('home_user', __('games.home'))->render(function ($game) {
                return $game->home_user->name;
            })->sort(),
            TD::make('away_user', __('games.away'))->render(function ($game) {
                return $game->away_user->name;
            })->sort(),
            TD::make('home_team', __('games.home_team'))->render(function ($game) {
                return $game->home_team->name;
            })->filter()->sort(),
            TD::make(__('games.away_team'))->render(function ($game) {
                return $game->away_team->name;
            })->filter()->sort(),
            TD::make(__('games.goals_home'))->render(function ($game) {
                return $game->goals_home;
            })->sort()->alignRight(),
            TD::make('goals_away', __('games.goals_away'))->render(function ($game) {
                return $game->goals_away;
            })->sort()->alignRight(),
            TD::make(__('general.created_at'))
                ->render(function ($game) {
                    return DateHelper::formatDateTime($game->created_at) . ' ' . __('general.oclock');
                })->sort()
        ];
    }

    /**
     * Get the sights displayed by the resource.
     *
     * @return Sight[]
     */
    public function legend(): array
    {
        return [
            Sight::make('home_user', __('games.home'))->render(function ($game) {
                return $game->home_user->name;
            }),
            Sight::make('away_user', __('games.away'))->render(function ($game) {
                return $game->away_user->name;
            }),
            Sight::make('home_team', __('games.home_team'))->render(function ($game) {
                return $game->home_team->name;
            }),
            Sight::make('away_team', __('games.away_team'))->render(function ($game) {
                return $game->away_team->name;
            }),
            Sight::make('goals_home', __('games.goals_home')),
            Sight::make('goals_away', __('games.goals_away')),
            Sight::make('shots_home', __('games.shots_home')),
            Sight::make('shots_away', __('games.shots_away')),
            Sight::make('time_in_offense_home_in_seconds', __('games.time_in_offense_home_in_seconds'))->render(function ($game) {
                return DateHelper::minuteAndSecondFormatFromSeconds($game->time_in_offense_home_in_seconds);
            }),
            Sight::make('time_in_offense_away_in_seconds', __('games.time_in_offense_away_in_seconds'))->render(function ($game) {
                return DateHelper::minuteAndSecondFormatFromSeconds($game->time_in_offense_away_in_seconds);
            }),
            Sight::make('hits_home', __('games.hits_home')),
            Sight::make('hits_away', __('games.hits_away')),
            Sight::make('pass_percentage_home', __('games.pass_percentage_home'))->render(function ($game) {
                return $game->pass_percentage_home . " %";
            }),
            Sight::make('pass_percentage_away', __('games.pass_percentage_away'))->render(function ($game) {
                return $game->pass_percentage_away . " %";
            }),
            Sight::make('faceoffs_won_home', __('games.faceoffs_won_home')),
            Sight::make('faceoffs_won_away', __('games.faceoffs_won_away')),
            Sight::make('penalty_minutes_home_in_seconds', __('games.penalty_minutes_home_in_seconds'))->render(function ($game) {
                return DateHelper::minuteAndSecondFormatFromSeconds($game->penalty_minutes_home_in_seconds);
            }),
            Sight::make('penalty_minutes_away_in_seconds', __('games.penalty_minutes_away_in_seconds'))->render(function ($game) {
                return DateHelper::minuteAndSecondFormatFromSeconds($game->penalty_minutes_away_in_seconds);
            }),
            Sight::make('powerplays_used_home', __('games.powerplays_used_home')),
            Sight::make('powerplays_used_away', __('games.powerplays_used_away')),
            Sight::make('powerplays_received_home', __('games.powerplays_received_home')),
            Sight::make('powerplays_received_away', __('games.powerplays_received_away')),
            Sight::make('powerplay_time_home_in_seconds', __('games.powerplay_time_home_in_seconds'))->render(function ($game) {
                return DateHelper::minuteAndSecondFormatFromSeconds($game->powerplay_time_home_in_seconds);
            }),
            Sight::make('powerplay_time_away_in_seconds', __('games.powerplay_time_away_in_seconds'))->render(function ($game) {
                return DateHelper::minuteAndSecondFormatFromSeconds($game->powerplay_time_away_in_seconds);
            }),
            Sight::make('shorthanded_goals_home', __('games.shorthanded_goals_home')),
            Sight::make('shorthanded_goals_away', __('games.shorthanded_goals_away')),
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            new DefaultSorted('created_at', 'desc')
        ];
    }


    public static function displayInNavigation(): bool
    {
        return false;
    }
}
