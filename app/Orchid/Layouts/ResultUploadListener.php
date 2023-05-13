<?php

namespace App\Orchid\Layouts;

use App\Helpers\DateHelper;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Listener;
use Orchid\Support\Facades\Layout;

class ResultUploadListener extends Listener
{
    /**
     * List of field names for which values will be joined with targets' upon trigger.
     *
     * @var string[]
     */
    protected $extraVars = [];

    /**
     * List of field names for which values will be listened.
     *
     * @var string[]
     */
    protected $targets = [
        'game_result'
    ];

    /**
     * What screen method should be called
     * as a source for an asynchronous request.
     *
     * The name of the method must
     * begin with the prefix "async"
     *
     * @var string
     */
    protected $asyncMethod = 'processResult';

    /**
     * @return Layout[]
     */
    protected function layouts(): iterable
    {
        $fields = [];
        $totalAttachments = Attachment::where('created_at', Carbon::now()->subMonth()->toDateTimeString())->count();

        if ($totalAttachments < 750) {
            $fields = [Cropper::make('game_result')
                ->help(__('games.upload_limit'))
                ->title(__('games.upload_result'))
                ->targetId(),
                TextArea::make('view_result')
                    ->disabled()
                    ->rows(5)
                    ->readonly(),
                Input::make('detection_percentage')
                    ->title(__('games.detection_percentage'))
                    ->disabled()
                    ->readonly(),
            ];
        }

        $users = User::whereNot(function (Builder $query) {
            $query->where('name', 'admin');
        })->get();

        $parseSeconds = function () {
            $value = $this->get('value');
            if (is_numeric($value)) {
                $this->set('value', DateHelper::minuteAndSecondFormatFromSeconds($value));
            }
        };

        $fields = array_merge($fields, [
            Select::make('home_user_id')
                ->required()
                ->empty(__('general.select'), '')
                ->fromModel($users, 'name', 'id')
                ->title(__('games.home')),
            Select::make('away_user_id')
                ->required()
                ->empty(__('general.select'), '')
                ->fromModel($users, 'name', 'id')
                ->title(__('games.away')),
            Select::make('home_team_id')
                ->empty(__('general.select'), '')
                ->required()
                ->fromModel(Team::class, 'name', 'id')
                ->title(__('games.home_team')),
            Select::make('away_team_id')
                ->required()
                ->empty(__('general.select'), '')
                ->fromModel(Team::class, 'name', 'id')
                ->title(__('games.away_team')),
            Input::make('goals_home')
                ->title(__('games.goals_home'))
                ->type('number')
                ->max(50)
                ->min(0)
                ->required()
            ,
            Input::make('goals_away')
                ->min(0)
                ->max(50)
                ->title(__('games.goals_away'))
                ->type('number')
                ->required()
            ,
            Input::make('shots_home')
                ->min(0)
                ->required()
                ->title(__('games.shots_home'))->type('number'),
            Input::make('shots_away')
                ->min(0)
                ->required()
                ->title(__('games.shots_away'))
                ->type('number')
            ,
            Input::make('hits_home')
                ->required()
                ->type('number')
                ->title(__('games.hits_home')),
            Input::make('hits_away')
                ->required()
                ->type('number')
                ->title(__('games.hits_away')),
            Input::make('time_in_offense_home_in_seconds')
                ->required()
                ->title(__('games.time_in_offense_home_in_seconds'))
                ->addBeforeRender($parseSeconds)
                ->mask('99:99')
                ->runBeforeRender(),
            Input::make('time_in_offense_away_in_seconds')
                ->required()
                ->title(__('games.time_in_offense_away_in_seconds'))
                ->addBeforeRender($parseSeconds)
                ->mask('99:99'),
            Input::make('pass_percentage_home')->step(0.1)
                ->min(0)
                ->required()
                ->max(100)
                ->type('number')
                ->title(__('games.pass_percentage_home')),
            Input::make('pass_percentage_away')
                ->required()
                ->step(0.1)
                ->min(0)
                ->max(100)
                ->type('number')
                ->title(__('games.pass_percentage_away')),
            Input::make('faceoffs_won_home')
                ->required()
                ->type('number')
                ->title(__('games.faceoffs_won_home')),
            Input::make('faceoffs_won_away')
                ->required()
                ->type('number')
                ->title(__('games.faceoffs_won_away')),
            Input::make('penalty_minutes_home_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->required()
                ->mask('99:99')
                ->title(__('games.penalty_minutes_home_in_seconds')),
            Input::make('penalty_minutes_away_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->required()
                ->mask('99:99')
                ->title(__('games.penalty_minutes_away_in_seconds')),
            Input::make('powerplays_used_home')
                ->type('number')
                ->required()
                ->title(__('games.powerplays_used_home')),
            Input::make('powerplays_used_away')
                ->type('number')
                ->required()
                ->title(__('games.powerplays_used_away')),
            Input::make('powerplays_received_home')
                ->type('number')
                ->required()
                ->title(__('games.powerplays_received_home')),
            Input::make('powerplays_received_away')
                ->type('number')
                ->required()
                ->title(__('games.powerplays_received_away')),
            Input::make('powerplay_time_home_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->mask('99:99')
                ->required()
                ->title(__('games.powerplay_time_home_in_seconds')),
            Input::make('powerplay_time_away_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->required()
                ->mask('99:99')
                ->title(__('games.powerplay_time_away_in_seconds')),
            Input::make('shorthanded_goals_home')
                ->type('number')
                ->required()
                ->title(__('games.shorthanded_goals_home')),
            Input::make('shorthanded_goals_away')
                ->type('number')
                ->required()
                ->title(__('games.shorthanded_goals_away'))
        ]);
        return [
            Layout::rows($fields),
        ];
    }
}
