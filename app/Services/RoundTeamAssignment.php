<?php

namespace App\Services;

use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoundTeamAssignment
{
    public function startCurrentRound(int $tournamentId): ?int
    {
        return DB::transaction(function () use ($tournamentId) {
            // All draws and result submissions lock this same row first.
            $tournament = Tournament::whereKey($tournamentId)->lockForUpdate()->firstOrFail();
            $number = Round::where('tournament_id', $tournamentId)->whereNull('game_id')->min('round');
            if ($number === null) {
                return null;
            }
            $fixtures = Round::where('tournament_id', $tournamentId)->where('round', $number)
                ->orderBy('id')->lockForUpdate()->get();
            $pending = $fixtures->filter(fn ($fixture) => $fixture->home_team_id === null || $fixture->away_team_id === null);
            if ($pending->isEmpty()) {
                return (int) $number;
            }

            // Read one committed rating snapshot; updates publish all teams atomically.
            $teams = Team::where('division', '!=', 'NONE')->orderBy('id')->get();
            $pairs = [];
            foreach ($teams as $i => $home) {
                foreach ($teams->slice($i + 1) as $away) {
                    if (abs($home->overall_rating - $away->overall_rating) <= $tournament->max_team_overall_rating_difference) {
                        $pairs[] = [$home, $away];
                    }
                }
            }
            if ($pairs === []) {
                throw ValidationException::withMessages([
                    'teams' => 'No team pairing satisfies this tournament rating difference. Update the ratings and retry.',
                ]);
            }
            // Count assignments (including unplayed games already drawn), not
            // only results. Each tournament has its own usage history.
            $usage = [];
            $lastTeam = [];
            $history = Round::where('tournament_id', $tournamentId)
                ->where('round', '<=', $number)
                ->whereNotIn('id', $pending->modelKeys())
                ->orderBy('round')->orderBy('id')->get();
            foreach ($history as $previous) {
                foreach (['home', 'away'] as $side) {
                    $player = $previous->{$side.'_user_id'};
                    $team = $previous->{$side.'_team_id'};
                    if ($team !== null) {
                        $usage[$player][$team] = ($usage[$player][$team] ?? 0) + 1;
                        $lastTeam[$player] = $team;
                    }
                }
            }

            foreach ($pending->values() as $fixture) {
                if ($fixture->game_id !== null) {
                    throw ValidationException::withMessages(['teams' => 'A completed fixture is missing its teams.']);
                }
                $bestScore = null;
                $choices = [];
                foreach ($pairs as [$first, $second]) {
                    foreach ([[$first, $second], [$second, $first]] as [$home, $away]) {
                        $homeCount = $usage[$fixture->home_user_id][$home->id] ?? 0;
                        $awayCount = $usage[$fixture->away_user_id][$away->id] ?? 0;
                        // Prefer unused teams for both players, then balance
                        // frequency. Avoid consecutive repeats among equal choices.
                        $score = [
                            (int) ($homeCount > 0) + (int) ($awayCount > 0),
                            max($homeCount, $awayCount),
                            $homeCount + $awayCount,
                            (int) (($lastTeam[$fixture->home_user_id] ?? null) === $home->id)
                                + (int) (($lastTeam[$fixture->away_user_id] ?? null) === $away->id),
                        ];
                        if ($bestScore === null || $score < $bestScore) {
                            $bestScore = $score;
                            $choices = [[$home, $away]];
                        } elseif ($score === $bestScore) {
                            $choices[] = [$home, $away];
                        }
                    }
                }
                [$home, $away] = $choices[random_int(0, count($choices) - 1)];
                foreach (['home' => $home, 'away' => $away] as $side => $team) {
                    $player = $fixture->{$side.'_user_id'};
                    $usage[$player][$team->id] = ($usage[$player][$team->id] ?? 0) + 1;
                    $lastTeam[$player] = $team->id;
                }
                $fixture->home_team_id = $home->id;
                $fixture->away_team_id = $away->id;
                $fixture->home_overall_rating = $home->overall_rating;
                $fixture->away_overall_rating = $away->overall_rating;
                $fixture->teams_assigned_at = now();
                $fixture->save();
            }

            return (int) $number;
        }, 3);
    }
}
