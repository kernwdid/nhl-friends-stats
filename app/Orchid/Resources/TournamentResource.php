<?php

namespace App\Orchid\Resources;

use App\Helpers\DateHelper;
use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Orchid\Crud\Filters\DefaultSorted;
use Orchid\Crud\Resource;
use Orchid\Crud\ResourceRequest;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use App\Services\TournamentSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TournamentResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Tournament::class;

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
                ->required(),
            Input::make('total_games_per_player')
                ->min(1)
                ->max(100)
                ->required()
                ->title(__('tournaments.total_games_per_player'))->type('number'),
            Input::make('rounds')
                ->title(__('tournaments.rounds'))
                ->required()
                ->type('number'),
            Input::make('max_team_overall_rating_difference')
                ->min(0)
                ->max(Team::whereNot('division', 'NONE')->max('overall_rating') - Team::whereNot('division', 'NONE')->min('overall_rating'))
                ->required()
                ->title(__('tournaments.max_team_overall_rating_difference'))->type('number'),
            Select::make('players')
                ->title(__('tournaments.players'))
                ->empty(__('general.select'), '')
                ->required()
                ->multiple()
                ->fromModel(User::class, 'name', 'id'),
        ];
    }

    /**
     * Get the columns displayed by the resource.
     *
     * @return array<TD>
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID'),
            TD::make('name')->render(function ($model) {
                return '<a href="/tournament-info/' . $model->id . '?sort=-points">' . $model->name . '</a>';
            })->sort(),
            TD::make('created_at', __('general.created_at'))
                ->render(function ($model) {
                    return DateHelper::formatDateTime($model->created_at->toDateTimeString()) . ' ' . __('general.oclock');
                }),
            TD::make('updated_at', __('general.updated_at'))
                ->render(function ($model) {
                    return DateHelper::formatDateTime($model->updated_at->toDateTimeString()) . ' ' . __('general.oclock');
                }),
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
            Sight::make('name'),
            Sight::make('total_games_per_player', __('tournaments.total_games_per_player')),
            Sight::make('rounds', __('tournaments.rounds')),
            Sight::make('max_team_overall_rating_difference', __('tournaments.max_team_overall_rating_difference')),
            Sight::make('players', __('tournaments.players'))->render(function ($tournament) {

                return count($tournament->players) > 0 ? implode(', ', array_column($tournament->players->toArray(), 'name')) : '';
            })
        ];
    }

    public function onSave(ResourceRequest $request, Model $model): void
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'total_games_per_player' => 'required|integer|min:1|max:100',
            'rounds' => 'required|integer|min:1',
            'max_team_overall_rating_difference' => 'required|integer|min:0|max:99',
            'players' => 'required|array|min:2',
            'players.*' => 'required|integer|distinct|exists:users,id',
        ]);
        if ($model->exists) {
            // Editing a name must never regenerate published fixtures.
            if ((int) $data['total_games_per_player'] !== (int) $model->total_games_per_player ||
                (int) $data['rounds'] !== (int) $model->rounds ||
                (int) $data['max_team_overall_rating_difference'] !== (int) $model->max_team_overall_rating_difference ||
                array_diff($data['players'], $model->players()->pluck('users.id')->all()) ||
                count($data['players']) !== $model->players()->count()) {
                throw ValidationException::withMessages(['players' => 'Tournament settings cannot change after fixtures have been created.']);
            }
            $model->name = $data['name'];
            $model->save();
            return;
        }
        try {
            $fixtures = app(TournamentSchedule::class)->generate(
                $data['players'], (int) $data['total_games_per_player'], (int) $data['rounds']
            );
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['total_games_per_player' => $exception->getMessage()]);
        }
        DB::transaction(function () use ($model, $data, $fixtures) {
            $players = $data['players'];
            unset($data['players']);
            $model->forceFill($data)->saveOrFail();
            $model->players()->attach($players);
            foreach ($fixtures as &$fixture) {
                $fixture['tournament_id'] = $model->id;
            }
            unset($fixture);
            Round::insert($fixtures);
        });
    }

    public static function displayInNavigation(): bool
    {
        return false;
    }

    public static function permission(): ?string
    {
        return 'resource.tournaments';
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            new DefaultSorted('name', 'asc'),
        ];
    }

}
