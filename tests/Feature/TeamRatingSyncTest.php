<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Services\TeamRatingSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeamRatingSyncTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return json_decode(file_get_contents(database_path('data/nhl27-ratings-2026-09-13.json')), true);
    }

    private function populate(): void
    {
        DB::table('teams')->delete();
        foreach ($this->payload()['teams'] as $team) {
            DB::table('teams')->insert([
                'name' => $team['name'], 'abbreviation' => $team['abbreviation'], 'division' => 'ATLANTIC',
                'overall_rating' => 70, 'offense_rating' => 71, 'defense_rating' => 72, 'goaltender_rating' => 73,
            ]);
        }
    }

    public function test_complete_import_preserves_ids_and_names_and_dry_run_is_read_only(): void
    {
        $this->populate();
        $before = DB::table('teams')->orderBy('id')->get()->toJson();
        app(TeamRatingSync::class)->update($this->payload(), true);
        $this->assertSame($before, DB::table('teams')->orderBy('id')->get()->toJson());
        $names = Team::pluck('name', 'id')->all();
        $this->assertSame(32, app(TeamRatingSync::class)->update($this->payload()));
        $this->assertSame($names, Team::pluck('name', 'id')->all());
        $this->assertSame(86, Team::where('abbreviation', 'ANA')->first()->overall_rating);
    }

    public function test_invalid_import_does_not_partially_update(): void
    {
        $this->populate();
        $data = $this->payload();
        $data['teams'][31]['overall_rating'] = 999;
        try {
            app(TeamRatingSync::class)->update($data);
            $this->fail('Invalid ratings accepted.');
        } catch (ValidationException) {
            $this->assertSame(32, Team::where('overall_rating', 70)->count());
        }
    }

    public function test_failed_remote_fetch_keeps_previous_ratings(): void
    {
        $this->populate();
        Http::fake(['*' => Http::response('Forbidden', 403)]);
        $this->artisan('teams:sync-ratings')->assertExitCode(1);
        $this->assertSame(32, Team::where('overall_rating', 70)->count());
    }

    public function test_remote_parser_imports_only_complete_matching_edition(): void
    {
        $this->populate();
        $responses = [];
        foreach ($this->payload()['teams'] as $row) {
            $responses[$row['source_url']] = Http::response('<h2>'.$row['name'].' on NHL 27</h2> Team Overall Rating of <b>86</b> (OFF:85, DEF:84, GOA:87)');
        }
        Http::fake($responses);
        $this->artisan('teams:sync-ratings')->assertSuccessful();
        Http::assertSentCount(32);
        foreach (Http::recorded() as [$request]) {
            $this->assertSame(
                ['NHLFriendsStats/1.0 (+https://github.com/kernwdid/nhl-friends-stats)'],
                $request->header('User-Agent')
            );
            $this->assertSame(['text/html'], $request->header('Accept'));
        }
        $this->assertSame(32, Team::where('overall_rating', 86)->count());
    }

    public function test_rating_migration_rolls_back_original_values(): void
    {
        $migration = require database_path('migrations/2026_09_13_000002_update_nhl27_ratings.php');
        $migration->down();
        $this->populate();
        $before = DB::table('teams')->orderBy('id')->get()->toJson();
        $migration->up();
        $this->assertSame(86, Team::where('abbreviation', 'ANA')->first()->overall_rating);
        $migration->down();
        $this->assertSame($before, DB::table('teams')->orderBy('id')->get()->toJson());
    }
}
