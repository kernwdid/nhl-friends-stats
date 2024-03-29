<?php

namespace App\Orchid\Resources;

use App\Helpers\DateHelper;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Orchid\Crud\Filters\DefaultSorted;
use Orchid\Crud\Resource;
use Orchid\Crud\ResourceRequest;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
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
        $users = User::whereNot(function (Builder $query) {
            $query->where('name', 'admin');
        })->get();

        $parseSeconds = function () {
            $value = $this->get('value');
            if (is_numeric($value)) {
                $this->set('value', DateHelper::minuteAndSecondFormatFromSeconds($value));
            }
        };

        return [
            Select::make('away_user_id')
                ->required()
                ->empty(__('general.select'), '')
                ->fromModel($users, 'name', 'id')
                ->title(__('games.away')),
            Select::make('home_user_id')
                ->required()
                ->empty(__('general.select'), '')
                ->fromModel($users, 'name', 'id')
                ->title(__('games.home')),
            Select::make('away_team_id')
                ->required()
                ->empty(__('general.select'), '')
                ->fromModel(Team::class, 'name', 'id')
                ->title(__('games.away_team')),
            Select::make('home_team_id')
                ->empty(__('general.select'), '')
                ->required()
                ->fromModel(Team::class, 'name', 'id')
                ->title(__('games.home_team')),
            Input::make('goals_away')
                ->min(0)
                ->max(50)
                ->title(__('games.goals_away'))
                ->type('number')->required(),
            Input::make('goals_home')
                ->title(__('games.goals_home'))
                ->type('number')
                ->max(50)
                ->min(0)
                ->required(),
            Select::make('win_type')
                ->options(['regular' => __('games.win_type_regular'), 'ot' => __('games.win_type_ot'), 'so' => __('games.win_type_so')])
                ->required()
                ->title(__('games.win_type')),
            Input::make('shots_away')
                ->min(0)
                ->title(__('games.shots_away'))
                ->type('number')
                ->required(),
            Input::make('shots_home')
                ->min(0)
                ->title(__('games.shots_home'))->type('number')->required(),
            Input::make('time_in_offense_away_in_seconds')
                ->title(__('games.time_in_offense_away_in_seconds'))
                ->addBeforeRender($parseSeconds)
                ->mask('99:99')
                ->required(),
            Input::make('time_in_offense_home_in_seconds')
                ->title(__('games.time_in_offense_home_in_seconds'))
                ->required()
                ->addBeforeRender($parseSeconds)
                ->mask('99:99')
                ->runBeforeRender(),
            Input::make('hits_away')
                ->type('number')
                ->title(__('games.hits_away'))
                ->required(),
            Input::make('hits_home')
                ->type('number')
                ->title(__('games.hits_home'))
                ->required(),
            Input::make('pass_percentage_away')
                ->step(0.1)
                ->min(0)
                ->max(100)
                ->type('number')
                ->title(__('games.pass_percentage_away'))
                ->required(),
            Input::make('pass_percentage_home')
                ->step(0.1)
                ->min(0)
                ->max(100)
                ->type('number')
                ->title(__('games.pass_percentage_home'))
                ->required(),
            Input::make('faceoffs_won_away')
                ->type('number')
                ->title(__('games.faceoffs_won_away'))
                ->required(),
            Input::make('faceoffs_won_home')
                ->type('number')
                ->title(__('games.faceoffs_won_home'))
                ->required(),
            Input::make('penalty_minutes_away_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->required()
                ->mask('99:99')
                ->title(__('games.penalty_minutes_away_in_seconds')),
            Input::make('penalty_minutes_home_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->mask('99:99')
                ->required()
                ->title(__('games.penalty_minutes_home_in_seconds')),
            Input::make('powerplays_used_away')
                ->required()
                ->type('number')->title(__('games.powerplays_used_away')),
            Input::make('powerplays_used_home')
                ->required()
                ->type('number')->title(__('games.powerplays_used_home')),
            Input::make('powerplays_received_away')
                ->required()
                ->type('number')->title(__('games.powerplays_received_away')),
            Input::make('powerplays_received_home')
                ->required()
                ->type('number')->title(__('games.powerplays_received_home')),
            Input::make('powerplay_time_away_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->required()
                ->mask('99:99')
                ->title(__('games.powerplay_time_away_in_seconds')),
            Input::make('powerplay_time_home_in_seconds')
                ->required()
                ->addBeforeRender($parseSeconds)->mask('99:99')->title(__('games.powerplay_time_home_in_seconds')),
            Input::make('shorthanded_goals_away')
                ->required()
                ->type('number')
                ->title(__('games.shorthanded_goals_away')),
            Input::make('shorthanded_goals_home')
                ->required()
                ->type('number')
                ->title(__('games.shorthanded_goals_home')),
        ];
    }

    public function onSave(ResourceRequest $request, Model $model)
    {
        $data = $request->all();

        $data['time_in_offense_home_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['time_in_offense_home_in_seconds']);
        $data['time_in_offense_away_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['time_in_offense_away_in_seconds']);

        $data['pass_percentage_home'] = floatval($data['pass_percentage_home']);
        $data['pass_percentage_away'] = floatval($data['pass_percentage_away']);

        $data['penalty_minutes_home_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['penalty_minutes_home_in_seconds']);
        $data['penalty_minutes_away_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['penalty_minutes_away_in_seconds']);
        $data['powerplay_time_home_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['powerplay_time_home_in_seconds']);
        $data['powerplay_time_away_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['powerplay_time_away_in_seconds']);

        $model->forceFill($data)->save();
    }

    public static function permission(): ?string
    {
        return 'resource.games';
    }

    /**
     * Get the columns displayed by the resource.
     *
     * @return array<TD>
     */
    public function columns(): array
    {
        return [
            TD::make('home_user_id', __('games.home'))->render(function ($game) {
                return $game->home_user->name;
            })->sort(),
            TD::make('away_user_id', __('games.away'))->render(function ($game) {
                return $game->away_user->name;
            })->sort(),
            TD::make('home_team_id', __('games.home_team'))->render(function ($game) {
                $asset = asset('logos/' . $game->home_team->name . '.svg');
                return '<img height="25" src="' . (str_contains($game->home_team->name, 'All-Stars') ? asset('logos/nhl.svg') : $asset) . '" />';
            })->sort(),
            TD::make('away_team_id', __('games.away_team'))->render(function ($game) {
                $asset = asset('logos/' . $game->away_team->name . '.svg');
                return '<img height="25" src="' . (str_contains($game->away_team->name, 'All-Stars') ? asset('logos/nhl.svg') : $asset) . '" />';
            })->sort(),
            TD::make(__('games.goals_home'))->render(function ($game) {
                return $game->goals_home;
            })->sort()->alignRight(),
            TD::make('goals_away', __('games.goals_away'))->render(function ($game) {
                return $game->goals_away;
            })->sort()->alignRight(),
            TD::make('win_type', __('games.win_type'))->render(function ($game) {
                return __('games.win_type_short_' . $game['win_type']);
            })->sort()->alignRight(),
            TD::make(__('general.created_at'))
                ->render(function ($game) {
                    return DateHelper::formatDateTime($game->created_at) . ' ' . __('general.oclock');
                })->sort(),
        ];
    }

    /**
     * Get the sights displayed by the resource.
     *
     * @return array<Sight>
     */
    public function legend(): array
    {
        return [
            Sight::make('created_at', __('general.created_at'))->render(function($game) {
                return DateHelper::formatDateTime($game->created_at) . ' ' . __('general.oclock');
            }),
            Sight::make('updated_at', __('general.updated_at'))->render(function($game) {
                return DateHelper::formatDateTime($game->updated_at) . ' ' . __('general.oclock');
            }),
            Sight::make('home_user', __('games.home'))->render(function ($game) {
                return $game->home_user->name;
            }),
            Sight::make('away_user', __('games.away'))->render(function ($game) {
                return $game->away_user->name;
            }),
            Sight::make('home_team', __('games.home_team'))->render(function ($game) {
                $asset = asset('logos/' . $game->home_team->name . '.svg');
                return '<img height="25" src="' . (str_contains($game->home_team->name, 'All-Stars') ? asset('logos/nhl.svg') : $asset) . '" />' . $game->home_team->name;
            }),
            Sight::make('away_team', __('games.away_team'))->render(function ($game) {
                $asset = asset('logos/' . $game->away_team->name . '.svg');
                return '<img height="25" src="' . (str_contains($game->away_team->name, 'All-Stars') ? asset('logos/nhl.svg') : $asset) . '" />' . $game->away_team->name;
            }),
            Sight::make('goals_home', __('games.goals_home')),
            Sight::make('goals_away', __('games.goals_away')),
            Sight::make('win_type', __('games.win_type'))->render(function($game) {
                return __('games.win_type_' . $game['win_type']);
            }),
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
                return $game->pass_percentage_home . ' %';
            }),
            Sight::make('pass_percentage_away', __('games.pass_percentage_away'))->render(function ($game) {
                return $game->pass_percentage_away . ' %';
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
            new DefaultSorted('created_at', 'desc'),
        ];
    }

    public static function displayInNavigation(): bool
    {
        return false;
    }
}
