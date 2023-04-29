<?php

namespace App\Orchid\Resources;

use App\Helpers\DateHelper;
use App\Models\Team;
use Orchid\Crud\Filters\DefaultSorted;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class TeamResource extends Resource
{

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Team::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            Input::make('name')
                ->title('Name')
                ->required()
                ->placeholder(__('teams.name_placeholder')),
            Select::make('division')
                ->required()
                ->fromModel(Team::class, 'division', 'division')
                ->title('Division'),
            Input::make('overall_rating')
                ->min(1)
                ->max(100)
                ->required()
                ->title(__('teams.overall_rating'))->type('number'),
            Input::make('offense_rating')
                ->title(__('teams.offense_rating'))
                ->min(1)
                ->required()
                ->max(100)
                ->type('number'),
            Input::make('defense_rating')
                ->title(__('teams.defense_rating'))
                ->min(1)
                ->required()
                ->max(100)
                ->type('number'),
            Input::make('goaltender_rating')
                ->title(__('teams.goaltender_rating'))
                ->min(1)
                ->max(100)
                ->required()
                ->type('number'),
        ];
    }

    /**
     * Get the columns displayed by the resource.
     *
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID'),
            TD::make('name')->render(function ($model) {
                return $model->name;
            })->filter()->sort(),
            TD::make('division', 'Division')->render(function ($model) {
                return $model->division;
            })->filter(Select::make()->fromModel(Team::class, 'division', 'division'))->sort(),
            TD::make('overall_rating', __('teams.overall_rating'))->render(function ($model) {
                return $model->overall_rating;
            })->alignRight()->sort(),

            TD::make('offense_rating', __('teams.offense_rating'))->render(function ($model) {
                return $model->offense_rating;
            })->alignRight()->sort(),

            TD::make('defense_rating', __('teams.defense_rating'))->render(function ($model) {
                return $model->defense_rating;
            })->alignRight()->sort(),

            TD::make('goaltender_rating', __('teams.goaltender_rating'))->render(function ($model) {
                return $model->goaltender_rating;
            })->alignRight()->sort(),

            TD::make('created_at', __('general.created_at'))
                ->render(function ($model) {
                    return DateHelper::formatDateTime($model->created_at->toDateTimeString()) . " " . __('general.oclock');
                }),

            TD::make('updated_at', __('general.updated_at'))
                ->render(function ($model) {
                    return DateHelper::formatDateTime($model->updated_at->toDateTimeString()) . " " . __('general.oclock');
                }),
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
            Sight::make('name'),
            Sight::make('division'),
            Sight::make('overall_rating', __('teams.overall_rating')),
            Sight::make('offense_rating', __('teams.offense_rating')),
            Sight::make('defense_rating', __('teams.defense_rating')),
            Sight::make('goaltender_rating', __('teams.goaltender_rating'))
        ];
    }

    public static function displayInNavigation(): bool
    {
        return false;
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            new DefaultSorted('name', 'asc')
        ];
    }
}
