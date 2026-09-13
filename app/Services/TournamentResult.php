<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Round;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TournamentResult
{
    public function save(int $tournamentId, int $roundId, int $userId, array $data): Game
    {
        return DB::transaction(function () use ($tournamentId, $roundId, $userId, $data) {
            Tournament::whereKey($tournamentId)->lockForUpdate()->firstOrFail();
            $fixture = Round::where('tournament_id', $tournamentId)->whereKey($roundId)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($userId, [(int) $fixture->home_user_id, (int) $fixture->away_user_id], true), 403);
            $current = Round::where('tournament_id', $tournamentId)->whereNull('game_id')->min('round');
            if ($fixture->game_id !== null || (int) $fixture->round !== (int) $current ||
                $fixture->home_team_id === null || $fixture->away_team_id === null) {
                throw ValidationException::withMessages(['round_id' => 'This fixture is completed or its round has not started.']);
            }
            foreach (['home_user_id', 'away_user_id', 'home_team_id', 'away_team_id'] as $field) {
                if ((int) ($data[$field] ?? 0) !== (int) $fixture->$field) {
                    throw ValidationException::withMessages([$field => 'Use the players and teams assigned to this fixture.']);
                }
            }
            $game = Game::create($data);
            $fixture->game_id = $game->id;
            $fixture->save();

            return $game;
        }, 3);
    }
}
