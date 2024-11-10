<?php

use App\Models\Team;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Team::where('abbreviation', '=', 'ARI')->delete();
        Team::firstOrCreate([
            'name' => 'Utah Hockey Club',
            'abbreviation' => 'UTA',
            'division' => 'CENTRAL',
            'overall_rating' => 86,
            'offense_rating' => 86,
            'defense_rating' => 86,
            'goaltender_rating' => 85
        ]);

        $newTeamData = [
            ['name' => 'Anaheim Ducks', 'abbreviation' => 'ANA', 'division' => 'PACIFIC', 'overall_rating' => 84, 'goaltender_rating' => 80, 'defense_rating' => 84, 'offense_rating' => 87],
            ['name' => 'Boston Bruins', 'abbreviation' => 'BOS', 'division' => 'ATLANTIC', 'overall_rating' => 88, 'goaltender_rating' => 89, 'defense_rating' => 89, 'offense_rating' => 85],
            ['name' => 'Buffalo Sabres', 'abbreviation' => 'BUF', 'division' => 'ATLANTIC', 'overall_rating' => 89, 'goaltender_rating' => 85, 'defense_rating' => 93, 'offense_rating' => 88],
            ['name' => 'Calgary Flames', 'abbreviation' => 'CGY', 'division' => 'PACIFIC', 'overall_rating' => 84, 'goaltender_rating' => 82, 'defense_rating' => 85, 'offense_rating' => 84],
            ['name' => 'Carolina Hurricanes', 'abbreviation' => 'CAR', 'division' => 'METROPOLITAN', 'overall_rating' => 90, 'goaltender_rating' => 90, 'defense_rating' => 90, 'offense_rating' => 89],
            ['name' => 'Chicago Blackhawks', 'abbreviation' => 'CHI', 'division' => 'CENTRAL', 'overall_rating' => 85, 'goaltender_rating' => 83, 'defense_rating' => 84, 'offense_rating' => 87],
            ['name' => 'Colorado Avalanche', 'abbreviation' => 'COL', 'division' => 'CENTRAL', 'overall_rating' => 86, 'goaltender_rating' => 84, 'defense_rating' => 88, 'offense_rating' => 84],
            ['name' => 'Columbus Blue Jackets', 'abbreviation' => 'CBJ', 'division' => 'METROPOLITAN', 'overall_rating' => 84, 'goaltender_rating' => 81, 'defense_rating' => 87, 'offense_rating' => 83],
            ['name' => 'Dallas Stars', 'abbreviation' => 'DAL', 'division' => 'CENTRAL', 'overall_rating' => 91, 'goaltender_rating' => 90, 'defense_rating' => 92, 'offense_rating' => 92],
            ['name' => 'Detroit Red Wings', 'abbreviation' => 'DET', 'division' => 'ATLANTIC', 'overall_rating' => 86, 'goaltender_rating' => 85, 'defense_rating' => 83, 'offense_rating' => 90],
            ['name' => 'Edmonton Oilers', 'abbreviation' => 'EDM', 'division' => 'PACIFIC', 'overall_rating' => 88, 'goaltender_rating' => 84, 'defense_rating' => 88, 'offense_rating' => 91],
            ['name' => 'Florida Panthers', 'abbreviation' => 'FLA', 'division' => 'ATLANTIC', 'overall_rating' => 88, 'goaltender_rating' => 90, 'defense_rating' => 89, 'offense_rating' => 86],
            ['name' => 'Los Angeles Kings', 'abbreviation' => 'LAK', 'division' => 'PACIFIC', 'overall_rating' => 83, 'goaltender_rating' => 81, 'defense_rating' => 84, 'offense_rating' => 84],
            ['name' => 'Minnesota Wild', 'abbreviation' => 'MIN', 'division' => 'CENTRAL', 'overall_rating' => 88, 'goaltender_rating' => 86, 'defense_rating' => 90, 'offense_rating' => 89],
            ['name' => 'Montreal Canadiens', 'abbreviation' => 'MTL', 'division' => 'ATLANTIC', 'overall_rating' => 85, 'goaltender_rating' => 82, 'defense_rating' => 86, 'offense_rating' => 86],
            ['name' => 'Nashville Predators', 'abbreviation' => 'NSH', 'division' => 'CENTRAL', 'overall_rating' => 89, 'goaltender_rating' => 89, 'defense_rating' => 91, 'offense_rating' => 89],
            ['name' => 'New Jersey Devils', 'abbreviation' => 'NJD', 'division' => 'METROPOLITAN', 'overall_rating' => 87, 'goaltender_rating' => 87, 'defense_rating' => 86, 'offense_rating' => 89],
            ['name' => 'New York Islanders', 'abbreviation' => 'NYI', 'division' => 'METROPOLITAN', 'overall_rating' => 89, 'goaltender_rating' => 91, 'defense_rating' => 90, 'offense_rating' => 85],
            ['name' => 'New York Rangers', 'abbreviation' => 'NYR', 'division' => 'METROPOLITAN', 'overall_rating' => 90, 'goaltender_rating' => 92, 'defense_rating' => 88, 'offense_rating' => 89],
            ['name' => 'Ottawa Senators', 'abbreviation' => 'OTT', 'division' => 'ATLANTIC', 'overall_rating' => 87, 'goaltender_rating' => 88, 'defense_rating' => 86, 'offense_rating' => 87],
            ['name' => 'Philadelphia Flyers', 'abbreviation' => 'PHI', 'division' => 'METROPOLITAN', 'overall_rating' => 85, 'goaltender_rating' => 82, 'defense_rating' => 85, 'offense_rating' => 87],
            ['name' => 'Pittsburgh Penguins', 'abbreviation' => 'PIT', 'division' => 'METROPOLITAN', 'overall_rating' => 87, 'goaltender_rating' => 86, 'defense_rating' => 87, 'offense_rating' => 87],
            ['name' => 'San Jose Sharks', 'abbreviation' => 'SJS', 'division' => 'PACIFIC', 'overall_rating' => 84, 'goaltender_rating' => 82, 'defense_rating' => 85, 'offense_rating' => 84],
            ['name' => 'Seattle Kraken', 'abbreviation' => 'SEA', 'division' => 'PACIFIC', 'overall_rating' => 88, 'goaltender_rating' => 85, 'defense_rating' => 88, 'offense_rating' => 90],
            ['name' => 'St. Louis Blues', 'abbreviation' => 'STL', 'division' => 'CENTRAL', 'overall_rating' => 87, 'goaltender_rating' => 86, 'defense_rating' => 84, 'offense_rating' => 87],
            ['name' => 'Tampa Bay Lightning', 'abbreviation' => 'TBL', 'division' => 'ATLANTIC', 'overall_rating' => 89, 'goaltender_rating' => 91, 'defense_rating' => 91, 'offense_rating' => 89],
            ['name' => 'Toronto Maple Leafs', 'abbreviation' => 'TOR', 'division' => 'ATLANTIC', 'overall_rating' => 86, 'goaltender_rating' => 80, 'defense_rating' => 89, 'offense_rating' => 90],
            ['name' => 'Vancouver Canucks', 'abbreviation' => 'VAN', 'division' => 'PACIFIC', 'overall_rating' => 87, 'goaltender_rating' => 79, 'defense_rating' => 90, 'offense_rating' => 91],
            ['name' => 'Vegas Golden Knights', 'abbreviation' => 'VGK', 'division' => 'PACIFIC', 'overall_rating' => 89, 'goaltender_rating' => 86, 'defense_rating' => 92, 'offense_rating' => 85],
            ['name' => 'Washington Capitals', 'abbreviation' => 'WSH', 'division' => 'METROPOLITAN', 'overall_rating' => 87, 'goaltender_rating' => 86, 'defense_rating' => 90, 'offense_rating' => 84],
            ['name' => 'Winnipeg Jets', 'abbreviation' => 'WIN', 'division' => 'CENTRAL', 'overall_rating' => 89, 'goaltender_rating' => 90, 'defense_rating' => 88, 'offense_rating' => 90],
            ['name' => 'Atlantic All-Stars', 'abbreviation' => 'ATL', 'division' => 'NONE', 'overall_rating' => 93, 'goaltender_rating' => 93, 'defense_rating' => 93, 'offense_rating' => 93],
            ['name' => 'Central All-Stars', 'abbreviation' => 'CEN', 'division' => 'NONE', 'overall_rating' => 94, 'goaltender_rating' => 94, 'defense_rating' => 94, 'offense_rating' => 94],
            ['name' => 'Pacific All-Stars', 'abbreviation' => 'PAC', 'division' => 'NONE', 'overall_rating' => 93, 'goaltender_rating' => 93, 'defense_rating' => 93, 'offense_rating' => 93],
            ['name' => 'Metropolitan All-Stars', 'abbreviation' => 'MET', 'division' => 'NONE', 'overall_rating' => 92, 'goaltender_rating' => 92, 'defense_rating' => 92, 'offense_rating' => 92],
        ];

        foreach ($newTeamData as $teamData) {
            Team::where('abbreviation', '=', $teamData['abbreviation'])
                ->update($teamData);
        }

        // You can perform multiple updates as needed
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
