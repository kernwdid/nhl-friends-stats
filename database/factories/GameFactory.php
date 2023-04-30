<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $homeUser = User::inRandomOrder()->whereNot(function (Builder $query) {
                $query->where('name', 'admin');
            })->first();

        $awayUser = User::inRandomOrder()->whereNot(function (Builder $query) use ($homeUser) {
            $query->where('id', $homeUser->id)
                ->orWhere('name', 'admin');
        })->first();

        $homePPReceived = $this->faker->numberBetween(0, 5);
        $awayPPReceived = $this->faker->numberBetween(0, 5);

        $homePPUsed = $this->faker->numberBetween(0, $homePPReceived);
        $awayPPUsed = $this->faker->numberBetween(0, $awayPPReceived);
        $goalsHome = $this->faker->randomDigit();
        $goalsAway = $this->faker->randomDigit();
        return [
            'home_user_id' => $homeUser,
            'away_user_id' => $awayUser,
            'home_team_id' => Team::inRandomOrder()->first(),
            'away_team_id' => Team::inRandomOrder()->first(),
            'goals_home' => $goalsHome,
            'goals_away' => $goalsAway,
            'shots_home' => $this->faker->numberBetween(0, 40),
            'shots_away' => $this->faker->numberBetween(0, 40),
            'time_in_offense_home_in_seconds' => $this->faker->numberBetween(0, 5400),
            'time_in_offense_away_in_seconds' => $this->faker->numberBetween(0, 5400),
            'hits_home' => $this->faker->numberBetween(0, 10),
            'hits_away' => $this->faker->numberBetween(0, 10),
            'pass_percentage_home' => $this->faker->randomFloat(1, 0, 100),
            'pass_percentage_away' => $this->faker->randomFloat(1, 0, 100),
            'faceoffs_won_home' => $this->faker->numberBetween(0, 25),
            'faceoffs_won_away' => $this->faker->numberBetween(0, 25),
            'penalty_minutes_home_in_seconds' => $this->faker->numberBetween(0, 5400),
            'penalty_minutes_away_in_seconds' => $this->faker->numberBetween(0, 5400),
            'powerplays_used_home' => $homePPUsed,
            'powerplays_used_away' => $awayPPUsed,
            'powerplays_received_home' => $homePPReceived,
            'powerplays_received_away' => $awayPPReceived,
            'powerplay_time_home_in_seconds' => $homePPReceived*120,
            'powerplay_time_away_in_seconds' => $awayPPReceived*120,
            'shorthanded_goals_home' => $awayPPReceived ? $this->faker->numberBetween(0, $goalsHome) : 0,
            'shorthanded_goals_away' => $homePPReceived ? $this->faker->numberBetween(0, $goalsAway): 0
        ];
    }
}
