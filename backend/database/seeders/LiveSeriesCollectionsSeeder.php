<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\CollectionReward;
use Illuminate\Database\Seeder;

class LiveSeriesCollectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // All 30 NBA teams for Live Series
        $teams = [
            'Atlanta Hawks',
            'Boston Celtics',
            'Brooklyn Nets',
            'Charlotte Hornets',
            'Chicago Bulls',
            'Cleveland Cavaliers',
            'Dallas Mavericks',
            'Denver Nuggets',
            'Detroit Pistons',
            'Golden State Warriors',
            'Houston Rockets',
            'Indiana Pacers',
            'Los Angeles Clippers',
            'Los Angeles Lakers',
            'Memphis Grizzlies',
            'Miami Heat',
            'Milwaukee Bucks',
            'Minnesota Timberwolves',
            'New Orleans Pelicans',
            'New York Knicks',
            'Oklahoma City Thunder',
            'Orlando Magic',
            'Philadelphia 76ers',
            'Phoenix Suns',
            'Portland Trail Blazers',
            'Sacramento Kings',
            'San Antonio Spurs',
            'Toronto Raptors',
            'Utah Jazz',
            'Washington Wizards',
        ];

        // Create a collection for each team
        foreach ($teams as $team) {
            $collection = Collection::create([
                'name' => 'Live Series',
                'type' => 'team',
                'sub_collection' => $team,
                'description' => "Collect all current {$team} players",
                'total_items' => 0, // Will be updated when players are imported
                'active' => true,
            ]);

            // Create a reward for completing the team collection
            CollectionReward::create([
                'collection_id' => $collection->id,
                'reward_type' => 'stubs',
                'reward_value' => '5000',
                'reward_quantity' => 1,
                'description' => "5,000 stubs for completing {$team}",
            ]);
        }

        // Create Free Agent collection
        $freeAgentCollection = Collection::create([
            'name' => 'Live Series',
            'type' => 'team',
            'sub_collection' => 'Free Agent',
            'description' => 'Collect all players not currently on a team',
            'total_items' => 0,
            'active' => true,
        ]);

        CollectionReward::create([
            'collection_id' => $freeAgentCollection->id,
            'reward_type' => 'stubs',
            'reward_value' => '1000',
            'reward_quantity' => 1,
            'description' => '1,000 stubs for completing Free Agents',
        ]);

        echo "✓ Created " . (count($teams) + 1) . " Live Series collections\n";
    }
}
