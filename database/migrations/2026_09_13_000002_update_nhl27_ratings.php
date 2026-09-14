<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = ['overall_rating', 'offense_rating', 'defense_rating', 'goaltender_rating'];

    public function up(): void
    {
        $payload = json_decode(file_get_contents(__DIR__.'/../data/nhl27-ratings-2026-09-13.json'), true, 512, JSON_THROW_ON_ERROR);
        $updates = [];
        foreach ($payload['teams'] as $row) {
            $aliases = $row['abbreviation'] === 'WIN' ? ['WIN', 'WPG'] : [$row['abbreviation']];
            $teams = DB::table('teams')->whereIn('abbreviation', $aliases)->where('division', '!=', 'NONE')->get();
            if ($teams->isEmpty()) {
                continue; // Fresh databases may be populated by seeders after migrations.
            }
            if ($teams->count() !== 1) {
                throw new RuntimeException('Missing or ambiguous NHL team: '.$row['abbreviation']);
            }
            $updates[] = [$teams->first(), array_intersect_key($row, array_flip(self::FIELDS))];
        }
        Schema::create('nhl27_rating_backups', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->primary();
            $table->text('ratings');
        });
        Schema::table('teams', function (Blueprint $table) {
            $table->timestamp('ratings_synced_at')->nullable();
        });
        DB::transaction(function () use ($updates) {
            foreach ($updates as [$team, $ratings]) {
                $previous = array_intersect_key((array) $team, array_flip([...self::FIELDS, 'updated_at']));
                DB::table('nhl27_rating_backups')->insert(['team_id' => $team->id, 'ratings' => json_encode($previous, JSON_THROW_ON_ERROR)]);
                DB::table('teams')->where('id', $team->id)->update($ratings + ['updated_at' => now(), 'ratings_synced_at' => '2026-09-13 00:00:00']);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            foreach (DB::table('nhl27_rating_backups')->get() as $backup) {
                DB::table('teams')->where('id', $backup->team_id)->update(json_decode($backup->ratings, true, 512, JSON_THROW_ON_ERROR));
            }
        });
        Schema::table('teams', fn (Blueprint $table) => $table->dropColumn('ratings_synced_at'));
        Schema::dropIfExists('nhl27_rating_backups');
    }
};
