<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\CollectionReward;
use App\Models\Player;
use Illuminate\Database\Seeder;

class LiveSeriesRewardsSeeder extends Seeder
{
    private $createdPlayers = [];
    private $specialCollections = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🏀 Setting up Live Series Rewards System...\n\n";

        // Step 1: Create special collections (Awards, All-Star, etc.)
        $this->createSpecialCollections();

        // Step 2: Create reward players and assign to collections
        $this->createRewardPlayers();

        // Step 3: Update team collection rewards
        $this->updateTeamCollectionRewards();

        // Step 4: Create division collections
        $this->createDivisionCollections();

        // Step 5: Create conference collections
        $this->createConferenceCollections();

        // Step 6: Create NBA collection
        $this->createNBACollection();

        echo "\n✅ Live Series Rewards System setup complete!\n";
    }

    private function createSpecialCollections()
    {
        echo "📚 Creating special collections...\n";

        $collections = [
            ['name' => 'Awards', 'description' => 'Award-winning players throughout NBA history'],
            ['name' => 'All-Star', 'description' => 'NBA All-Star players'],
            ['name' => 'Postseason', 'description' => 'Playoff and championship performers'],
            ['name' => 'Veteran', 'description' => 'Veteran NBA players'],
            ['name' => 'Standout', 'description' => 'Stand

out performers'],
            ['name' => '2nd Half Heroes', 'description' => 'Players who dominated in the second half'],
            ['name' => 'Contributor', 'description' => 'Key contributors to their teams'],
            ['name' => 'Breakout', 'description' => 'Breakout stars'],
            ['name' => 'Milestone', 'description' => 'Players who achieved major milestones'],
            ['name' => 'Last Ride', 'description' => 'Final seasons of legendary players'],
            ['name' => 'Hall of Fame', 'description' => 'Basketball Hall of Fame inductees'],
            ['name' => 'Signature', 'description' => 'Signature series players'],
        ];

        foreach ($collections as $collectionData) {
            $collection = Collection::create([
                'name' => $collectionData['name'],
                'type' => 'special',
                'sub_collection' => null,
                'description' => $collectionData['description'],
                'total_items' => 0,
                'active' => true,
            ]);
            $this->specialCollections[$collectionData['name']] = $collection;
            echo "  ✓ Created {$collectionData['name']} collection\n";
        }
    }

    private function createRewardPlayers()
    {
        echo "\n🎁 Creating reward players...\n";

        $rewards = [
            // Team Rewards
            ['name' => 'Joe Johnson', 'team' => 'Atlanta Hawks', 'overall' => 91, 'tier' => 'Sapphire', 'set' => 'Standout'],
            ['name' => 'Paul Pierce', 'team' => 'Boston Celtics', 'overall' => 95, 'tier' => 'Pink Diamond', 'set' => 'Postseason'],
            ['name' => 'Brook Lopez', 'team' => 'Brooklyn Nets', 'overall' => 84, 'tier' => 'Gold', 'set' => 'Veteran'],
            ['name' => 'Kemba Walker', 'team' => 'Charlotte Hornets', 'overall' => 89, 'tier' => 'Emerald', 'set' => 'All-Star'],
            ['name' => 'Joakim Noah', 'team' => 'Chicago Bulls', 'overall' => 85, 'tier' => 'Gold', 'set' => 'Awards'],
            ['name' => 'Kevin Love', 'team' => 'Cleveland Cavaliers', 'overall' => 94, 'tier' => 'Diamond', 'set' => 'Postseason'],
            ['name' => 'Dirk Nowitzki', 'team' => 'Dallas Mavericks', 'overall' => 94, 'tier' => 'Diamond', 'set' => 'Awards'],
            ['name' => 'David Thompson', 'team' => 'Denver Nuggets', 'overall' => 98, 'tier' => 'Galaxy Opal', 'set' => 'All-Star'],
            ['name' => 'Chauncey Billups', 'team' => 'Detroit Pistons', 'overall' => 93, 'tier' => 'Amethyst', 'set' => 'Postseason'],
            ['name' => 'Draymond Green', 'team' => 'Golden State Warriors', 'overall' => 95, 'tier' => 'Pink Diamond', 'set' => '2nd Half Heroes'],
            ['name' => 'Hakeem Olajuwon', 'team' => 'Houston Rockets', 'overall' => 94, 'tier' => 'Diamond', 'set' => 'Awards'],
            ['name' => 'Reggie Miller', 'team' => 'Indiana Pacers', 'overall' => 94, 'tier' => 'Diamond', 'set' => 'All-Star'],
            ['name' => 'Elton Brand', 'team' => 'Los Angeles Clippers', 'overall' => 93, 'tier' => 'Amethyst', 'set' => 'Standout'],
            ['name' => 'Kobe Bryant', 'team' => 'Los Angeles Lakers', 'overall' => 96, 'tier' => 'Pink Diamond', 'set' => 'Awards'],
            ['name' => 'Marc Gasol', 'team' => 'Memphis Grizzlies', 'overall' => 93, 'tier' => 'Amethyst', 'set' => 'Veteran'],
            ['name' => 'Chris Bosh', 'team' => 'Miami Heat', 'overall' => 90, 'tier' => 'Sapphire', 'set' => 'Postseason'],
            ['name' => 'Kareem Abdul-Jabbar', 'team' => 'Milwaukee Bucks', 'overall' => 98, 'tier' => 'Galaxy Opal', 'set' => 'Awards'],
            ['name' => 'Kevin Garnett', 'team' => 'Minnesota Timberwolves', 'overall' => 96, 'tier' => 'Pink Diamond', 'set' => 'Awards'],
            ['name' => 'Jrue Holiday', 'team' => 'New Orleans Pelicans', 'overall' => 89, 'tier' => 'Emerald', 'set' => 'Contributor'],
            ['name' => 'Carmelo Anthony', 'team' => 'New York Knicks', 'overall' => 94, 'tier' => 'Diamond', 'set' => 'All-Star'],
            ['name' => 'Russell Westbrook', 'team' => 'Oklahoma City Thunder', 'overall' => 98, 'tier' => 'Galaxy Opal', 'set' => 'Milestone'],
            ['name' => 'Dwight Howard', 'team' => 'Orlando Magic', 'overall' => 91, 'tier' => 'Sapphire', 'set' => 'All-Star'],
            ['name' => 'Andre Iguodala', 'team' => 'Philadelphia 76ers', 'overall' => 93, 'tier' => 'Amethyst', 'set' => 'Breakout'],
            ['name' => 'Steve Nash', 'team' => 'Phoenix Suns', 'overall' => 93, 'tier' => 'Amethyst', 'set' => 'Awards'],
            ['name' => 'Brandon Roy', 'team' => 'Portland Trail Blazers', 'overall' => 90, 'tier' => 'Sapphire', 'set' => 'Breakout'],
            ['name' => 'Chris Webber', 'team' => 'Sacramento Kings', 'overall' => 89, 'tier' => 'Emerald', 'set' => 'All-Star'],
            ['name' => 'Tim Duncan', 'team' => 'San Antonio Spurs', 'overall' => 95, 'tier' => 'Pink Diamond', 'set' => 'Awards'],
            ['name' => 'DeMar DeRozan', 'team' => 'Toronto Raptors', 'overall' => 87, 'tier' => 'Emerald', 'set' => '2nd Half Heroes'],
            ['name' => 'Karl Malone', 'team' => 'Utah Jazz', 'overall' => 86, 'tier' => 'Gold', 'set' => 'Veteran'],
            ['name' => 'Michael Jordan', 'team' => 'Washington Wizards', 'overall' => 83, 'tier' => 'Gold', 'set' => 'Last Ride'],

            // Division Rewards
            ['name' => 'Bob Cousy', 'team' => 'Free Agent', 'overall' => 96, 'tier' => 'Pink Diamond', 'set' => 'All-Star', 'division' => 'Atlantic'],
            ['name' => 'Derrick Rose', 'team' => 'Free Agent', 'overall' => 98, 'tier' => 'Galaxy Opal', 'set' => 'Awards', 'division' => 'Central'],
            ['name' => 'Alonzo Mourning', 'team' => 'Free Agent', 'overall' => 93, 'tier' => 'Amethyst', 'set' => 'Postseason', 'division' => 'Southeast'],
            ['name' => 'Kevin Durant', 'team' => 'Free Agent', 'overall' => 98, 'tier' => 'Galaxy Opal', 'set' => 'Awards', 'division' => 'Northwest'],
            ['name' => 'Shaquille O\'Neal', 'team' => 'Free Agent', 'overall' => 98, 'tier' => 'Galaxy Opal', 'set' => 'Postseason', 'division' => 'Pacific'],
            ['name' => 'James Harden', 'team' => 'Free Agent', 'overall' => 97, 'tier' => 'Pink Diamond', 'set' => 'Awards', 'division' => 'Southwest'],

            // Conference Rewards
            ['name' => 'Julius Erving', 'team' => 'Free Agent', 'overall' => 99, 'tier' => 'Galaxy Opal', 'set' => 'Awards', 'conference' => 'Eastern'],
            ['name' => 'Charles Barkley', 'team' => 'Free Agent', 'overall' => 99, 'tier' => 'Galaxy Opal', 'set' => 'Hall of Fame', 'conference' => 'Western'],

            // NBA Reward
            ['name' => 'Wilt Chamberlain', 'team' => 'Free Agent', 'overall' => 99, 'tier' => 'Galaxy Opal', 'set' => 'Signature', 'nba' => true],
        ];

        foreach ($rewards as $rewardData) {
            $collection = $this->specialCollections[$rewardData['set']] ?? null;

            $player = Player::create([
                'name' => $rewardData['name'],
                'team' => $rewardData['team'],
                'overall_rating' => $rewardData['overall'],
                'position' => 'SG', // Default position
                'card_tier' => $rewardData['tier'],
                'collection_id' => $collection ? $collection->id : null,
                'image_url' => null,
            ]);

            $this->createdPlayers[$rewardData['name']] = $player;

            $label = isset($rewardData['division']) ? " (Division: {$rewardData['division']})" : (isset($rewardData['conference']) ? " (Conference: {$rewardData['conference']})" : (isset($rewardData['nba']) ? " (NBA)" : " ({$rewardData['team']})"));

            echo "  ✓ Created {$rewardData['name']} - {$rewardData['overall']} OVR {$rewardData['tier']}{$label}\n";
        }
    }

    private function updateTeamCollectionRewards()
    {
        echo "\n🎯 Updating team collection rewards...\n";

        $teamRewards = [
            'Atlanta Hawks' => 'Joe Johnson',
            'Boston Celtics' => 'Paul Pierce',
            'Brooklyn Nets' => 'Brook Lopez',
            'Charlotte Hornets' => 'Kemba Walker',
            'Chicago Bulls' => 'Joakim Noah',
            'Cleveland Cavaliers' => 'Kevin Love',
            'Dallas Mavericks' => 'Dirk Nowitzki',
            'Denver Nuggets' => 'David Thompson',
            'Detroit Pistons' => 'Chauncey Billups',
            'Golden State Warriors' => 'Draymond Green',
            'Houston Rockets' => 'Hakeem Olajuwon',
            'Indiana Pacers' => 'Reggie Miller',
            'Los Angeles Clippers' => 'Elton Brand',
            'Los Angeles Lakers' => 'Kobe Bryant',
            'Memphis Grizzlies' => 'Marc Gasol',
            'Miami Heat' => 'Chris Bosh',
            'Milwaukee Bucks' => 'Kareem Abdul-Jabbar',
            'Minnesota Timberwolves' => 'Kevin Garnett',
            'New Orleans Pelicans' => 'Jrue Holiday',
            'New York Knicks' => 'Carmelo Anthony',
            'Oklahoma City Thunder' => 'Russell Westbrook',
            'Orlando Magic' => 'Dwight Howard',
            'Philadelphia 76ers' => 'Andre Iguodala',
            'Phoenix Suns' => 'Steve Nash',
            'Portland Trail Blazers' => 'Brandon Roy',
            'Sacramento Kings' => 'Chris Webber',
            'San Antonio Spurs' => 'Tim Duncan',
            'Toronto Raptors' => 'DeMar DeRozan',
            'Utah Jazz' => 'Karl Malone',
            'Washington Wizards' => 'Michael Jordan',
        ];

        foreach ($teamRewards as $team => $playerName) {
            $collection = Collection::where('name', 'Live Series')
                ->where('sub_collection', $team)
                ->first();

            if (!$collection) {
                echo "  ⚠ Collection not found for {$team}\n";
                continue;
            }

            $player = $this->createdPlayers[$playerName] ?? null;
            if (!$player) {
                echo "  ⚠ Player not found: {$playerName}\n";
                continue;
            }

            // Delete old stubs reward
            CollectionReward::where('collection_id', $collection->id)->delete();

            // Create new player card reward (requires all team cards)
            CollectionReward::create([
                'collection_id' => $collection->id,
                'required_cards' => $collection->total_items ?: 15, // Approximate, will be updated
                'reward_type' => 'player_card',
                'reward_quantity' => 1,
                'player_id' => $player->id,
                'pack_id' => null,
                'order' => 1,
                'description' => "Complete the {$team} collection",
            ]);

            echo "  ✓ Updated {$team} reward to {$playerName}\n";
        }
    }

    private function createDivisionCollections()
    {
        echo "\n🗺️ Creating division collections...\n";

        $divisions = [
            'Atlantic' => [
                'teams' => ['Boston Celtics', 'Brooklyn Nets', 'New York Knicks', 'Philadelphia 76ers', 'Toronto Raptors'],
                'reward' => 'Bob Cousy',
            ],
            'Central' => [
                'teams' => ['Chicago Bulls', 'Cleveland Cavaliers', 'Detroit Pistons', 'Indiana Pacers', 'Milwaukee Bucks'],
                'reward' => 'Derrick Rose',
            ],
            'Southeast' => [
                'teams' => ['Atlanta Hawks', 'Charlotte Hornets', 'Miami Heat', 'Orlando Magic', 'Washington Wizards'],
                'reward' => 'Alonzo Mourning',
            ],
            'Northwest' => [
                'teams' => ['Denver Nuggets', 'Minnesota Timberwolves', 'Oklahoma City Thunder', 'Portland Trail Blazers', 'Utah Jazz'],
                'reward' => 'Kevin Durant',
            ],
            'Pacific' => [
                'teams' => ['Golden State Warriors', 'Los Angeles Clippers', 'Los Angeles Lakers', 'Phoenix Suns', 'Sacramento Kings'],
                'reward' => 'Shaquille O\'Neal',
            ],
            'Southwest' => [
                'teams' => ['Dallas Mavericks', 'Houston Rockets', 'Memphis Grizzlies', 'New Orleans Pelicans', 'San Antonio Spurs'],
                'reward' => 'James Harden',
            ],
        ];

        foreach ($divisions as $divisionName => $data) {
            $collection = Collection::create([
                'name' => 'Live Series',
                'type' => 'division',
                'sub_collection' => "{$divisionName} Division",
                'description' => "Complete all {$divisionName} Division team collections",
                'total_items' => count($data['teams']),
                'active' => true,
            ]);

            $player = $this->createdPlayers[$data['reward']] ?? null;
            if ($player) {
                // Reward requires locking in all 5 team rewards
                CollectionReward::create([
                    'collection_id' => $collection->id,
                    'required_cards' => count($data['teams']),
                    'reward_type' => 'player_card',
                    'reward_quantity' => 1,
                    'player_id' => $player->id,
                    'pack_id' => null,
                    'order' => 1,
                    'description' => "Complete the {$divisionName} Division",
                ]);
            }

            echo "  ✓ Created {$divisionName} Division collection (reward: {$data['reward']})\n";
        }
    }

    private function createConferenceCollections()
    {
        echo "\n🏆 Creating conference collections...\n";

        $conferences = [
            'Eastern' => [
                'divisions' => ['Atlantic', 'Central', 'Southeast'],
                'reward' => 'Julius Erving',
            ],
            'Western' => [
                'divisions' => ['Northwest', 'Pacific', 'Southwest'],
                'reward' => 'Charles Barkley',
            ],
        ];

        foreach ($conferences as $conferenceName => $data) {
            $collection = Collection::create([
                'name' => 'Live Series',
                'type' => 'conference',
                'sub_collection' => "{$conferenceName} Conference",
                'description' => "Complete all {$conferenceName} Conference division collections",
                'total_items' => count($data['divisions']),
                'active' => true,
            ]);

            $player = $this->createdPlayers[$data['reward']] ?? null;
            if ($player) {
                // Reward requires locking in all 3 division rewards
                CollectionReward::create([
                    'collection_id' => $collection->id,
                    'required_cards' => count($data['divisions']),
                    'reward_type' => 'player_card',
                    'reward_quantity' => 1,
                    'player_id' => $player->id,
                    'pack_id' => null,
                    'order' => 1,
                    'description' => "Complete the {$conferenceName} Conference",
                ]);
            }

            echo "  ✓ Created {$conferenceName} Conference collection (reward: {$data['reward']})\n";
        }
    }

    private function createNBACollection()
    {
        echo "\n🌟 Creating NBA collection...\n";

        $collection = Collection::create([
            'name' => 'Live Series',
            'type' => 'nba',
            'sub_collection' => 'NBA',
            'description' => 'Complete both conference collections to earn the ultimate reward',
            'total_items' => 2, // Eastern and Western conferences
            'active' => true,
        ]);

        $player = $this->createdPlayers['Wilt Chamberlain'] ?? null;
        if ($player) {
            // Reward requires locking in both conference rewards
            CollectionReward::create([
                'collection_id' => $collection->id,
                'required_cards' => 2,
                'reward_type' => 'player_card',
                'reward_quantity' => 1,
                'player_id' => $player->id,
                'pack_id' => null,
                'order' => 1,
                'description' => 'Complete the entire Live Series',
            ]);
        }

        echo "  ✓ Created NBA collection (reward: Wilt Chamberlain)\n";
    }
}
