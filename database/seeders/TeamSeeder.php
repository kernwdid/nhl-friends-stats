<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Team::factory()->create(['name' => 'Anaheim Ducks', 'division' => 'PACIFIC', 'overall_rating' => 88, 'goaltender_rating' => 90, 'defense_rating' => 88, 'offense_rating' => 88]);
        Team::factory()->create(['name' => 'Arizona Coyotes', 'division' => 'CENTRAL', 'overall_rating' => 82, 'goaltender_rating' => 79, 'defense_rating' => 85, 'offense_rating' => 81]);
        Team::factory()->create(['name' => 'Boston Bruins','division' => 'ATLANTIC', 'overall_rating' => 91, 'goaltender_rating' => 87, 'defense_rating' => 93, 'offense_rating' => 91]);
        Team::factory()->create(['name' => 'Buffalo Sabres', 'division' => 'ATLANTIC', 'overall_rating' => 86, 'goaltender_rating' => 82, 'defense_rating' => 87, 'offense_rating' => 88]);
        Team::factory()->create(['name' => 'Calgary Flames', 'division' => 'PACIFIC', 'overall_rating' => 90, 'goaltender_rating' => 90, 'defense_rating' => 93, 'offense_rating' => 88]);
        Team::factory()->create(['name' => 'Carolina Hurricanes', 'division' => 'METROPOLITAN', 'overall_rating' => 92, 'goaltender_rating' => 90, 'defense_rating' => 92, 'offense_rating' => 94]);
        Team::factory()->create(['name' => 'Chicago Blackhawks', 'division' => 'CENTRAL', 'overall_rating' => 83, 'goaltender_rating' => 77, 'defense_rating' => 86, 'offense_rating' => 85]);
        Team::factory()->create(['name' => 'Colorado Avalanche', 'division' => 'CENTRAL', 'overall_rating' => 91, 'goaltender_rating' => 85, 'defense_rating' => 97, 'offense_rating' => 89]);
        Team::factory()->create(['name' => 'Columbus Blue Jackets', 'division' => 'METROPOLITAN', 'overall_rating' => 89, 'goaltender_rating' => 84, 'defense_rating' => 89, 'offense_rating' => 92]);
        Team::factory()->create(['name' => 'Dallas Stars', 'division' => 'CENTRAL', 'overall_rating' => 88, 'goaltender_rating' => 86, 'defense_rating' => 89, 'offense_rating' => 88]);
        Team::factory()->create(['name' => 'Detroit Red Wings', 'division' => 'ATLANTIC', 'overall_rating' => 89, 'goaltender_rating' => 87, 'defense_rating' => 88, 'offense_rating' => 91]);
        Team::factory()->create(['name' => 'Edmonton Oilers', 'division' => 'PACIFIC', 'overall_rating' => 88, 'goaltender_rating' => 84, 'defense_rating' => 85, 'offense_rating' => 93]);
        Team::factory()->create(['name' => 'Florida Panthers', 'division' => 'ATLANTIC', 'overall_rating' => 88, 'goaltender_rating' => 89, 'defense_rating' => 87, 'offense_rating' => 90]);
        Team::factory()->create(['name' => 'Los Angeles Kings', 'division' => 'PACIFIC', 'overall_rating' => 89, 'goaltender_rating' => 85, 'defense_rating' => 89, 'offense_rating' => 91]);
        Team::factory()->create(['name' => 'Minnesota Wild', 'division' => 'CENTRAL', 'overall_rating' => 88, 'goaltender_rating' => 85, 'defense_rating' => 90, 'offense_rating' => 89]);
        Team::factory()->create(['name' => 'Montreal Canadiens', 'division' => 'ATLANTIC', 'overall_rating' => 85, 'goaltender_rating' => 81, 'defense_rating' => 84, 'offense_rating' => 90]);
        Team::factory()->create(['name' => 'Nashville Predators', 'division' => 'CENTRAL', 'overall_rating' => 90, 'goaltender_rating' => 88, 'defense_rating' => 92, 'offense_rating' => 90]);
        Team::factory()->create(['name' => 'New Jersey Devils', 'division' => 'METROPOLITAN', 'overall_rating' => 89, 'goaltender_rating' => 87, 'defense_rating' => 89, 'offense_rating' => 91]);
        Team::factory()->create(['name' => 'New York Islanders', 'division' => 'METROPOLITAN', 'overall_rating' => 89, 'goaltender_rating' => 90, 'defense_rating' => 92, 'offense_rating' => 86]);
        Team::factory()->create(['name' => 'New York Rangers', 'division' => 'METROPOLITAN', 'overall_rating' => 98, 'goaltender_rating' => 92, 'defense_rating' => 90, 'offense_rating' => 89]);
        Team::factory()->create(['name' => 'Ottawa Senators', 'division' => 'ATLANTIC', 'overall_rating' => 86, 'goaltender_rating' => 84, 'defense_rating' => 86, 'offense_rating' => 89]);
        Team::factory()->create(['name' => 'Philadelphia Flyers', 'division' => 'METROPOLITAN', 'overall_rating' => 86, 'goaltender_rating' => 82, 'defense_rating' => 90, 'offense_rating' => 86]);
        Team::factory()->create(['name' => 'Pittsburgh Penguins', 'division' => 'METROPOLITAN', 'overall_rating' => 90, 'goaltender_rating' => 86, 'defense_rating' => 91, 'offense_rating' => 92]);
        Team::factory()->create(['name' => 'San Jose Sharks', 'division' => 'PACIFIC', 'overall_rating' => 85, 'goaltender_rating' => 86, 'defense_rating' => 83, 'offense_rating' => 87]);
        Team::factory()->create(['name' => 'Seattle Kraken', 'division' => 'PACIFIC', 'overall_rating' => 86, 'goaltender_rating' => 82, 'defense_rating' => 87, 'offense_rating' => 88]);
        Team::factory()->create(['name' => 'St. Louis Blues', 'division' => 'CENTRAL', 'overall_rating' => 88, 'goaltender_rating' => 84, 'defense_rating' => 90, 'offense_rating' => 90]);
        Team::factory()->create(['name' => 'Tampa Bay Lightning', 'division' => 'ATLANTIC', 'overall_rating' => 92, 'goaltender_rating' => 93, 'defense_rating' => 92, 'offense_rating' => 92]);
        Team::factory()->create(['name' => 'Toronto Maple Leafs', 'division' => 'ATLANTIC', 'overall_rating' => 90, 'goaltender_rating' => 85, 'defense_rating' => 92, 'offense_rating' => 92]);
        Team::factory()->create(['name' => 'Vancouver Canucks', 'division' => 'PACIFIC', 'overall_rating' => 87, 'goaltender_rating' => 85, 'defense_rating' => 88, 'offense_rating' => 89]);
        Team::factory()->create(['name' => 'Vegas Golden Knights', 'division' => 'PACIFIC', 'overall_rating' => 89, 'goaltender_rating' => 87, 'defense_rating' => 91, 'offense_rating' => 89]);
        Team::factory()->create(['name' => 'Washington Capitals', 'division' => 'METROPOLITAN', 'overall_rating' => 88, 'goaltender_rating' => 84, 'defense_rating' => 89, 'offense_rating' => 91]);
        Team::factory()->create(['name' => 'Winnipeg Jets', 'division' => 'CENTRAL', 'overall_rating' => 88, 'goaltender_rating' => 88, 'defense_rating' => 89, 'offense_rating' => 87]);
        Team::factory()->create(['name' => 'Atlantic All-Stars', 'division' => 'NONE', 'overall_rating' => 98, 'goaltender_rating' => 96, 'defense_rating' => 100, 'offense_rating' => 100]);
        Team::factory()->create(['name' => 'Central All-Stars', 'division' => 'NONE', 'overall_rating' => 96, 'goaltender_rating' => 90, 'defense_rating' => 99, 'offense_rating' => 100]);
        Team::factory()->create(['name' => 'Metropolitan All-Stars', 'division' => 'NONE', 'overall_rating' => 96, 'goaltender_rating' => 91, 'defense_rating' => 99, 'offense_rating' => 100]);
        Team::factory()->create(['name' => 'Pacific All-Stars', 'division' => 'NONE', 'overall_rating' => 96, 'goaltender_rating' => 94, 'defense_rating' => 96, 'offense_rating' => 100]);
    }
}
