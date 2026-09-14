<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedBigInteger('home_team_id')->nullable()->change();
            $table->unsignedBigInteger('away_team_id')->nullable()->change();
            $table->unsignedSmallInteger('home_overall_rating')->nullable();
            $table->unsignedSmallInteger('away_overall_rating')->nullable();
            $table->timestamp('teams_assigned_at')->nullable();
        });
        // Existing draws are already published: preserve them, including future rounds.
    }

    public function down(): void
    {
        if (DB::table('rounds')->whereNull('home_team_id')->orWhereNull('away_team_id')->exists()) {
            throw new RuntimeException('Finish or remove pending tournaments before rolling back deferred team assignment.');
        }
        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedBigInteger('home_team_id')->nullable(false)->change();
            $table->unsignedBigInteger('away_team_id')->nullable(false)->change();
            $table->dropColumn(['home_overall_rating', 'away_overall_rating', 'teams_assigned_at']);
        });
    }
};
