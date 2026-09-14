<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Orchid\Resources\TournamentResource;
use App\Services\RoundTeamAssignment;
use App\Services\TournamentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Orchid\Crud\ResourceRequest;
use Tests\TestCase;

class TournamentRoundsTest extends TestCase
{
    use RefreshDatabase;

    private function tournament(): Tournament
    {
        $players = User::factory()->count(4)->create();
        $request = ResourceRequest::create('/', 'POST', [
            'name' => 'Friends',
            'players' => $players->modelKeys(),
            'total_games_per_player' => 3,
            'rounds' => 3,
            'max_team_overall_rating_difference' => 5,
        ]);
        $tournament = new Tournament;
        (new TournamentResource)->onSave($request, $tournament);

        return $tournament;
    }

    private function ratings(): void
    {
        Team::query()->delete();
        foreach ([80, 85, 98] as $i => $rating) {
            Team::create([
                'name' => 'Test '.$i, 'division' => 'ATLANTIC', 'overall_rating' => $rating,
                'offense_rating' => $rating, 'defense_rating' => $rating, 'goaltender_rating' => $rating,
            ]);
        }
    }

    public function test_only_current_round_is_drawn_once_using_current_ratings(): void
    {
        $this->ratings();
        $tournament = $this->tournament();
        $this->assertSame(6, Round::whereNull('home_team_id')->count());
        $draw = app(RoundTeamAssignment::class);
        $this->assertSame(1, $draw->startCurrentRound($tournament->id));
        $first = Round::where('round', 1)->get()->toArray();
        $this->assertSame(4, Round::whereNull('home_team_id')->count());
        foreach ($first as $fixture) {
            $this->assertSame(5, abs($fixture['home_overall_rating'] - $fixture['away_overall_rating']));
        }
        $teams = Team::orderBy('id')->get();
        $teams[0]->update(['overall_rating' => 40]);
        $teams[1]->update(['overall_rating' => 95]);
        $this->assertSame(1, $draw->startCurrentRound($tournament->id));
        $this->assertSame($first, Round::where('round', 1)->get()->toArray());
        // Mark the first round finished; the next draw must now select 95 vs 98.
        DB::table('rounds')->where('round', 1)->update(['game_id' => 999]);
        $this->assertSame(2, $draw->startCurrentRound($tournament->id));
        foreach (Round::where('round', 2)->get() as $fixture) {
            $this->assertSame(3, abs($fixture->home_overall_rating - $fixture->away_overall_rating));
            $this->assertContains($fixture->home_team_id, [$teams[1]->id, $teams[2]->id]);
        }
        $this->assertSame(2, Round::whereNull('home_team_id')->count());
    }

    public function test_no_eligible_pair_leaves_round_unassigned(): void
    {
        $this->ratings();
        $tournament = $this->tournament();
        $tournament->max_team_overall_rating_difference = 0;
        $tournament->save();
        try {
            app(RoundTeamAssignment::class)->startCurrentRound($tournament->id);
            $this->fail('Expected validation error.');
        } catch (ValidationException) {
            $this->assertSame(6, Round::whereNull('home_team_id')->count());
        }
    }

    public function test_existing_published_draw_is_preserved(): void
    {
        $this->ratings();
        $tournament = $this->tournament();
        $ids = Team::pluck('id');
        DB::table('rounds')->update(['home_team_id' => $ids[0], 'away_team_id' => $ids[2]]);
        $before = Round::get()->toArray();
        app(RoundTeamAssignment::class)->startCurrentRound($tournament->id);
        $this->assertSame($before, Round::get()->toArray());
    }

    public function test_result_cannot_change_assigned_teams_or_skip_rounds(): void
    {
        $this->ratings();
        $tournament = $this->tournament();
        app(RoundTeamAssignment::class)->startCurrentRound($tournament->id);
        $fixture = Round::where('round', 1)->first();
        $data = $fixture->only(['home_user_id', 'away_user_id', 'home_team_id', 'away_team_id']);
        $data['away_team_id'] = $data['home_team_id'];
        try {
            app(TournamentResult::class)->save($tournament->id, $fixture->id, $fixture->home_user_id, $data);
            $this->fail('Tampered teams accepted.');
        } catch (ValidationException) {
            $this->assertSame(0, Game::count());
        }
        $fixture = Round::where('round', 2)->first();
        $this->expectException(ValidationException::class);
        app(TournamentResult::class)->save($tournament->id, $fixture->id, $fixture->home_user_id, []);
    }

    public function test_authenticated_tournament_page_starts_round(): void
    {
        $this->ratings();
        $tournament = $this->tournament();
        $user = $tournament->players()->first();
        $user->permissions = ['platform.index' => true, 'resource.teams' => true, 'resource.tournaments' => true];
        $user->save();
        $this->actingAs($user)->get('/tournament-info/'.$tournament->id)->assertOk();
        $this->assertSame(2, Round::whereNotNull('home_team_id')->count());
    }

    public function test_result_is_saved_once_and_linked_to_its_fixture(): void
    {
        $this->ratings();
        $tournament = $this->tournament();
        app(RoundTeamAssignment::class)->startCurrentRound($tournament->id);
        $fixture = Round::where('round', 1)->first();
        $data = $fixture->only(['home_user_id', 'away_user_id', 'home_team_id', 'away_team_id']);
        foreach (['goals', 'shots', 'time_in_offense', 'hits', 'pass_percentage', 'faceoffs_won'] as $stat) {
            foreach (['home', 'away'] as $side) {
                $key = $stat.'_'.$side.($stat === 'time_in_offense' ? '_in_seconds' : '');
                $data[$key] = 1;
            }
        }
        $data['goals_home'] = 2;
        $data['win_type'] = 'regular';
        $service = app(TournamentResult::class);
        $game = $service->save($tournament->id, $fixture->id, $fixture->home_user_id, $data);
        $this->assertSame($game->id, $fixture->fresh()->game_id);
        try {
            $service->save($tournament->id, $fixture->id, $fixture->home_user_id, $data);
            $this->fail('Duplicate submission accepted.');
        } catch (ValidationException) {
            $this->assertSame(1, Game::count());
        }
        $user = $tournament->players()->first();
        $user->permissions = ['platform.index' => true];
        $user->save();
        $this->actingAs($user)->get('/previous-rounds/'.$tournament->id)->assertOk();
    }

    public function test_renaming_tournament_does_not_duplicate_fixtures(): void
    {
        $tournament = $this->tournament();
        $before = Round::get()->toArray();
        $data = $tournament->only(['name', 'total_games_per_player', 'rounds', 'max_team_overall_rating_difference']);
        $data['name'] = 'Renamed';
        $data['players'] = $tournament->players->modelKeys();
        (new TournamentResource)->onSave(ResourceRequest::create('/', 'POST', $data), $tournament);
        $this->assertSame($before, Round::get()->toArray());
        $this->assertSame('Renamed', $tournament->fresh()->name);
    }

    public function test_leaderboard_breaks_points_ties_by_goal_difference(): void
    {
        $this->ratings();
        $tournament = $this->tournament();
        $players = $tournament->players()->orderBy('users.id')->get();
        $fixtures = Round::where('tournament_id', $tournament->id)->orderBy('id')->get();
        foreach ([[0, 1, 3, 2], [2, 3, 5, 0]] as $index => [$home, $away, $goalsHome, $goalsAway]) {
            $game = Game::factory()->create([
                'home_user_id' => $players[$home]->id, 'away_user_id' => $players[$away]->id,
                'goals_home' => $goalsHome, 'goals_away' => $goalsAway, 'win_type' => 'regular',
            ]);
            $fixtures[$index]->forceFill([
                'game_id' => $game->id, 'home_user_id' => $players[$home]->id, 'away_user_id' => $players[$away]->id,
            ])->save();
        }
        $this->mock(RoundTeamAssignment::class)->shouldReceive('startCurrentRound')->andReturn(null);
        foreach (['', '?sort=-points'] as $suffix) {
            $request = \Illuminate\Http\Request::create('/tournament-info/'.$tournament->id.$suffix);
            $route = new \Illuminate\Routing\Route('GET', 'tournament-info/{id}', fn () => null);
            $route->bind($request);
            $request->setRouteResolver(fn () => $route);
            $data = (new \App\Orchid\Screens\TournamentInfoScreen)->query($request);
            $this->assertSame(
                [$players[2]->name, $players[0]->name, $players[1]->name, $players[3]->name],
                array_map(fn ($entry) => $entry['name'], $data['leaderboard'])
            );
            $this->assertSame([1, 2, 3, 4], array_map(fn ($entry) => $entry['index'], $data['leaderboard']));
        }
    }
}
