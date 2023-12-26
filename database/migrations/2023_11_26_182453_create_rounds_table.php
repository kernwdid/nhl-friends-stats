<?php

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->integer('round');
            $table->foreignIdFor(User::class, "home_user_id");
            $table->foreignIdFor(User::class, "away_user_id");
            $table->foreignIdFor(Team::class, "home_team_id");
            $table->foreignIdFor(Team::class, "away_team_id");
            $table->foreignIdFor(Tournament::class, "tournament_id");
            $table->foreignIdFor(Game::class, "game_id")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
