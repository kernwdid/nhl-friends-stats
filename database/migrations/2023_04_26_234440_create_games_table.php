<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, "home_user_id");
            $table->foreignIdFor(User::class, "away_user_id");
            $table->foreignIdFor(Team::class, "home_team_id");
            $table->foreignIdFor(Team::class, "away_team_id");
            $table->integer('goals_home');
            $table->integer('goals_away');
            $table->integer('shots_home');
            $table->integer('shots_away');
            $table->integer('time_in_offense_home_in_seconds');
            $table->integer('time_in_offense_away_in_seconds');
            $table->integer('hits_home');
            $table->integer('hits_away');
            $table->float('pass_percentage_home');
            $table->float('pass_percentage_away');
            $table->integer('faceoffs_won_home');
            $table->integer('faceoffs_won_away');
            $table->integer('penalty_minutes_home_in_seconds')->nullable();
            $table->integer('penalty_minutes_away_in_seconds')->nullable();
            $table->integer('powerplays_used_home')->nullable();
            $table->integer('powerplays_used_away')->nullable();
            $table->integer('powerplays_received_home')->nullable();
            $table->integer('powerplays_received_away')->nullable();
            $table->integer('powerplay_time_home_in_seconds')->nullable();
            $table->integer('powerplay_time_away_in_seconds')->nullable();
            $table->integer('shorthanded_goals_home')->nullable();
            $table->integer('shorthanded_goals_away')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
};
