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
            shuffle($pairs);
            foreach ($pending->values() as $index => $fixture) {
                if ($fixture->game_id !== null) {
                    throw ValidationException::withMessages(['teams' => 'A completed fixture is missing its teams.']);
                }
                [$home, $away] = $pairs[$index % count($pairs)];
                if (random_int(0, 1)) {
                    [$home, $away] = [$away, $home];
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
