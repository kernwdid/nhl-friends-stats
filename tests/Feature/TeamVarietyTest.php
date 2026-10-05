<?php

namespace Tests\Feature;

use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Orchid\Resources\TournamentResource;
use App\Services\RoundTeamAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Orchid\Crud\ResourceRequest;
use Tests\TestCase;

class TeamVarietyTest extends TestCase
{
    use RefreshDatabase;

    private function season(int $teamCount, int $games, int $rounds): Tournament
    {
        Team::query()->delete();
        for ($i = 0; $i < $teamCount; $i++) {
            Team::create(['name' => 'Team '.$i, 'division' => 'CENTRAL',
                'overall_rating' => 85, 'offense_rating' => 85, 'defense_rating' => 85, 'goaltender_rating' => 85]);
        }
        $players = User::factory()->count(2)->create();
        $tournament = new Tournament;
        (new TournamentResource)->onSave(ResourceRequest::create('/', 'POST', [
            'name' => 'Variety', 'players' => $players->modelKeys(),
            'total_games_per_player' => $games, 'rounds' => $rounds,
            'max_team_overall_rating_difference' => 0,
        ]), $tournament);

        return $tournament;
    }

    public function test_unused_teams_are_preferred_across_rounds_and_draws_stay_fixed(): void
    {
        $tournament = $this->season(6, 12, 3);
        $used = [];
        $teamIds = Team::pluck('id')->all();
        foreach ([1, 2, 3] as $number) {
            app(RoundTeamAssignment::class)->startCurrentRound($tournament->id);
            $fixtures = Round::where('tournament_id', $tournament->id)->where('round', $number)->orderBy('id')->get();
            foreach ($fixtures as $fixture) {
                $this->assertNotSame($fixture->home_team_id, $fixture->away_team_id);
                $leastUsed = [];
                foreach (['home', 'away'] as $side) {
                    $player = $fixture->{$side.'_user_id'};
                    $counts = array_replace(array_fill_keys($teamIds, 0), array_count_values($used[$player] ?? []));
                    $leastUsed[$side] = array_keys($counts, min($counts), true);
                }
                // If both players need the same sole remaining team, one must
                // repeat a team. Otherwise both can receive a least-used team.
                $conflict = count($leastUsed['home']) === 1 && $leastUsed['home'] === $leastUsed['away'];
                $excess = 0;
                foreach (['home', 'away'] as $side) {
                    $player = $fixture->{$side.'_user_id'};
                    $team = $fixture->{$side.'_team_id'};
                    $previous = $used[$player] ?? [];
                    $counts = array_replace(array_fill_keys($teamIds, 0), array_count_values($previous));
                    $excess += $counts[$team] - min($counts);
                    $used[$player][] = $team;
                }
                $this->assertSame($conflict ? 1 : 0, $excess);
            }
            $before = $fixtures->toArray();
            app(RoundTeamAssignment::class)->startCurrentRound($tournament->id);
            $this->assertSame($before, Round::where('tournament_id', $tournament->id)->where('round', $number)->orderBy('id')->get()->toArray());
            DB::table('rounds')->where('tournament_id', $tournament->id)->where('round', $number)->update(['game_id' => 999]);
        }
        foreach ($used as $teams) {
            $this->assertCount(6, array_count_values($teams));
            $this->assertCount(12, $teams);
        }
    }

    public function test_two_eligible_teams_alternate_without_breaking_the_rating_limit(): void
    {
        $tournament = $this->season(2, 6, 1);
        Team::create(['name' => 'Ineligible', 'division' => 'CENTRAL',
            'overall_rating' => 99, 'offense_rating' => 99, 'defense_rating' => 99, 'goaltender_rating' => 99]);
        app(RoundTeamAssignment::class)->startCurrentRound($tournament->id);
        $last = [];
        foreach (Round::where('tournament_id', $tournament->id)->orderBy('id')->get() as $fixture) {
            $this->assertSame(85, $fixture->home_overall_rating);
            $this->assertSame(85, $fixture->away_overall_rating);
            foreach (['home', 'away'] as $side) {
                $player = $fixture->{$side.'_user_id'};
                $team = $fixture->{$side.'_team_id'};
                $this->assertNotSame($last[$player] ?? null, $team);
                $last[$player] = $team;
            }
        }
    }
}
