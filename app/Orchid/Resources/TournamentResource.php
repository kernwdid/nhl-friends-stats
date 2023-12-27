<?php

namespace App\Orchid\Resources;

use App\Helpers\DateHelper;
use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Orchid\Crud\Filters\DefaultSorted;
use Orchid\Crud\Resource;
use Orchid\Crud\ResourceRequest;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use function DeepCopy\deep_copy;

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
        $tournament = $request->all();
        $tournamentCopy = deep_copy($tournament);
        unset($tournamentCopy['players']);
        $saved = $model->forceFill($tournamentCopy)->save();

        if (!$saved) {
            Log::error("Insert of tournament failed");
            exit(1);
        }
        $model->players()->attach($tournament['players']);

        $teamGraph = $this->createTournamentGraph();
        $calculatedTournamentOptions = $this->calculatePossibleMatches($teamGraph, $tournament['max_team_overall_rating_difference']);

        shuffle($calculatedTournamentOptions['matches']);

        $roundEntries = [];
        $gamesScheduled = 0;
        $gamesToSchedule = $tournament['total_games_per_player'] * count($tournament['players']) / 2;
        $round = 1;
        $players = $tournament['players'];
        foreach ($calculatedTournamentOptions['matches'] as $shuffledMatch) {
            $team1 = $shuffledMatch[0];
            $team2 = $shuffledMatch[1];

            $player1 = array_shift($players);
            $player2 = array_shift($players);
            $roundEntries[] = [
                'round' => $round,
                'home_team_id' => $team1,
                'away_team_id' => $team2,
                'home_user_id' => intval($player1),
                'away_user_id' => intval($player2),
                'tournament_id' => $model->id
            ];
            $gamesScheduled += 1;

            if ($gamesScheduled >= $gamesToSchedule) {
                break;
            }

            if ($gamesScheduled % ($gamesToSchedule / $tournament['rounds']) == 0) {
                $round += 1;
            }

            $players[] = $player1;
            $players[] = $player2;
        }
        Round::insert($roundEntries);
    }

    public static function displayInNavigation(): bool
    {
        return false;
    }

    public static function permission(): ?string
    {
        return 'resource.teams';
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

    private function createTournamentGraph(): array
    {
        $graph = [];

        // Assuming Team::all() returns a collection of Team objects
        $teams = Team::whereNot('division', 'NONE')->get();

        $teamsArray = $teams->map(function ($team) {
            return $team->toArray();
        })->all();

        $teamIds = array_column($teamsArray, 'id');


        foreach ($teamsArray as $team) {
            // Create an array of team IDs excluding the current team
            $opponents = array_diff($teamIds, [$team['id']]);

            // Add the team and its opponents to the graph
            $graph[$team['id']] = $opponents;
        }

        return $graph;
    }

    private function calculatePossibleMatches(&$graph, $maxDifference): array
    {
        $tournament = ['matches' => [], 'team_occurrences' => []];
        $preventTeamSwaps = [];

        $teams = Team::whereNot('division', 'NONE')->get()->map(function ($team) {
            return $team->toArray();
        })->all();

        usort($teams, function ($a, $b) {
            return $b['overall_rating'] - $a['overall_rating'];
        });

        foreach ($teams as $team) {
            $possibleOpponents = $graph[$team['id']];

            usort($possibleOpponents, function ($a, $b) use ($teams, $team) {
                $keyA = array_search($a, array_column($teams, 'id'));
                $keyB = array_search($b, array_column($teams, 'id'));

                return abs($teams[$keyA]['overall_rating'] - $team['overall_rating']) - abs($teams[$keyB]['overall_rating'] - $team['overall_rating']);
            });

            foreach ($possibleOpponents as $opponent) {
                $opponentTeam = $teams[array_search($opponent, array_column($teams, 'id'))];
                if (abs($team['overall_rating'] - $opponentTeam['overall_rating']) <= $maxDifference) {
                    if (!in_array($team['id'] . $opponentTeam['id'], $preventTeamSwaps)) {
                        $preventTeamSwaps[] = $team['id'] . $opponentTeam['id'];
                        $preventTeamSwaps[] = $opponentTeam['id'] . $team['id'];

                        if (array_key_exists($team['id'], $tournament['team_occurrences'])) {
                            $tournament['team_occurrences'][$team['id']] += 1;
                        } else {
                            $tournament['team_occurrences'][$team['id']] = 1;
                        }

                        if (array_key_exists($opponentTeam['id'], $tournament['team_occurrences'])) {
                            $tournament['team_occurrences'][$opponentTeam['id']] += 1;
                        } else {
                            $tournament['team_occurrences'][$opponentTeam['id']] = 1;
                        }

                        $tournament['matches'][] = [$team['id'], $opponentTeam['id']];
                    }
                }
            }
        }
        return $tournament;
    }
}
