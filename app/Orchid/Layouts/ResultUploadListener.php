<?php

namespace App\Orchid\Layouts;

use App\Helpers\DateHelper;
use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Orchid\Screens\VisionScreen;
use App\Services\NhlResultParser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class ResultUploadListener extends Listener
{
    protected $targets = [
        'game_result',
    ];

    protected function layouts(): iterable
    {
        $queryParams = $this->query->get('query', []);
        $fields = [];
        $totalAttachments = Attachment::where('created_at', Carbon::now()->subMonth()->toDateTimeString())->count();

        if ($totalAttachments < config('app.gc_ocr_analyzing_limit')) {
            $fields = [
                Cropper::make('game_result')
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

        $disableHomeTeam = false;
        if (array_key_exists('home_team_id', $queryParams)) {
            if (is_numeric($queryParams['home_team_id'])) {
                $disableHomeTeam = true;
                $this->query->set('home_team_id', $queryParams['home_team_id']);
            }
        }

        $disableAwayTeam = false;
        if (array_key_exists('away_team_id', $queryParams)) {
            if (is_numeric($queryParams['away_team_id'])) {
                $disableAwayTeam = true;
                $this->query->set('away_team_id', $queryParams['away_team_id']);
            }
        }

        $disableHomeUser = false;
        if (array_key_exists('home_user_id', $queryParams)) {
            if (is_numeric($queryParams['home_user_id'])) {
                $this->query->set('home_user_id', $queryParams['home_user_id']);
                $disableHomeUser = true;
            }
        }

        $disableAwayUser = false;
        if (array_key_exists('away_user_id', $queryParams)) {
            if (is_numeric($queryParams['away_user_id'])) {
                $this->query->set('away_user_id', $queryParams['away_user_id']);
                $disableAwayUser = true;
            }
        }

        $fields = array_merge($fields, [
            Select::make('away_user_id')
                ->required()
                ->disabled($disableAwayUser)
                ->empty(__('general.select'), '')
                ->fromModel($users, 'name', 'id')
                ->title(__('games.away')),
            Select::make('home_user_id')
                ->required()
                ->disabled($disableHomeUser)
                ->empty(__('general.select'), '')
                ->fromModel($users, 'name', 'id')
                ->title(__('games.home')),
            Select::make('away_team_id')
                ->required()
                ->disabled($disableAwayTeam)
                ->empty(__('general.select'), '')
                ->fromModel(Team::class, 'name', 'id')
                ->title(__('games.away_team')),
            Select::make('home_team_id')
                ->empty(__('general.select'), '')
                ->required()
                ->disabled($disableHomeTeam)
                ->fromModel(Team::class, 'name', 'id')
                ->title(__('games.home_team')),
            Input::make('goals_away')
                ->min(0)
                ->max(50)
                ->title(__('games.goals_away'))
                ->type('number')
                ->required(),
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
                ->required()
                ->title(__('games.shots_away'))
                ->type('number'),
            Input::make('shots_home')
                ->min(0)
                ->required()
                ->title(__('games.shots_home'))
                ->type('number'),
            Input::make('hits_away')
                ->required()
                ->type('number')
                ->title(__('games.hits_away')),
            Input::make('hits_home')
                ->required()
                ->type('number')
                ->title(__('games.hits_home')),
            Input::make('time_in_offense_away_in_seconds')
                ->required()
                ->title(__('games.time_in_offense_away_in_seconds'))
                ->addBeforeRender($parseSeconds)
                ->mask('99:99'),
            Input::make('time_in_offense_home_in_seconds')
                ->required()
                ->title(__('games.time_in_offense_home_in_seconds'))
                ->addBeforeRender($parseSeconds)
                ->mask('99:99'),
            Input::make('pass_percentage_away')
                ->required()
                ->step(0.1)
                ->min(0)
                ->max(100)
                ->type('number')
                ->title(__('games.pass_percentage_away')),
            Input::make('pass_percentage_home')
                ->step(0.1)
                ->min(0)
                ->required()
                ->max(100)
                ->type('number')
                ->title(__('games.pass_percentage_home')),
            Input::make('faceoffs_won_away')
                ->required()
                ->type('number')
                ->title(__('games.faceoffs_won_away')),
            Input::make('faceoffs_won_home')
                ->required()
                ->type('number')
                ->title(__('games.faceoffs_won_home')),
            Input::make('penalty_minutes_away_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->required()
                ->mask('99:99')
                ->title(__('games.penalty_minutes_away_in_seconds')),
            Input::make('penalty_minutes_home_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->required()
                ->mask('99:99')
                ->title(__('games.penalty_minutes_home_in_seconds')),
            Input::make('powerplays_used_away')
                ->type('number')
                ->required()
                ->title(__('games.powerplays_used_away')),
            Input::make('powerplays_used_home')
                ->type('number')
                ->required()
                ->title(__('games.powerplays_used_home')),
            Input::make('powerplay_time_away_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->required()
                ->mask('99:99')
                ->title(__('games.powerplay_time_away_in_seconds')),
            Input::make('powerplay_time_home_in_seconds')
                ->addBeforeRender($parseSeconds)
                ->mask('99:99')
                ->required()
                ->title(__('games.powerplay_time_home_in_seconds')),
            Input::make('powerplays_received_away')
                ->type('number')
                ->required()
                ->title(__('games.powerplays_received_away')),
            Input::make('powerplays_received_home')
                ->type('number')
                ->required()
                ->title(__('games.powerplays_received_home')),
            Input::make('shorthanded_goals_away')
                ->type('number')
                ->required()
                ->title(__('games.shorthanded_goals_away')),
            Input::make('shorthanded_goals_home')
                ->type('number')
                ->required()
                ->title(__('games.shorthanded_goals_home')),
        ]);

        if ($disableHomeTeam) {
            $fields[] = Input::make('home_team_id')->hidden()->value($queryParams['home_team_id']);
        }

        if ($disableAwayTeam) {
            $fields[] = Input::make('away_team_id')->hidden()->value($queryParams['away_team_id']);
        }

        if ($disableHomeUser) {
            $fields[] = Input::make('home_user_id')->hidden()->value($queryParams['home_user_id']);
        }

        if ($disableAwayUser) {
            $fields[] = Input::make('away_user_id')->hidden()->value($queryParams['away_user_id']);
        }

        if (array_key_exists('round_id', $queryParams)) {
            if (is_numeric($queryParams['round_id'])) {
                $round = Round::find($queryParams['round_id']);
                if ($round) {
                    $fields[] = Input::make('round_id')->hidden()->value($queryParams['round_id']);
                }
            }
        }

        if (array_key_exists('tournament_id', $queryParams)) {
            if (is_numeric($queryParams['tournament_id'])) {
                $tournament = Tournament::find($queryParams['tournament_id']);
                if ($tournament) {
                    $fields[] = Input::make('tournament_id')->hidden()->value($queryParams['tournament_id']);
                }
            }
        }

        return [
            Layout::rows($fields),
        ];
    }

    public function handle(Repository $repository, Request $request): Repository
    {
        foreach (['home_user_id', 'away_user_id', 'win_type'] as $field) {
            if ($request->has($field)) {
                $repository->set($field, $request->input($field));
            }
        }
        $attachmentId = $request->input('game_result');
        if (! is_scalar($attachmentId) || ! ctype_digit((string) $attachmentId) || (int) $attachmentId < 1) {
            return $repository;
        }

        $result = app(VisionScreen::class)->processResult((int) $attachmentId);
        $locked = $repository->get('query', []);
        // Clear the previous image's statistics, including values not detected in
        // the replacement, so two screenshots cannot silently become one result.
        foreach (NhlResultParser::fields() as $field) {
            $repository->set($field, is_array($result) ? ($result[$field] ?? null) : null);
        }
        foreach (['home_team_id', 'away_team_id'] as $field) {
            if (isset($locked[$field]) && is_numeric($locked[$field])) {
                $repository->set($field, $locked[$field]);
            }
        }
        $repository->set('view_result', is_array($result) ? ($result['view_result'] ?? '') : $result);
        $repository->set('detection_percentage', is_array($result) ? ($result['detection_percentage'] ?? 0) : 0);

        return $repository->set('game_result', $attachmentId);
    }
}
