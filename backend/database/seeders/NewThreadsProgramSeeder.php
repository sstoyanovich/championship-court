<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramChallenge;
use App\Models\Player;
use App\Models\Pack;

class NewThreadsProgramSeeder extends Seeder
{
    /**
     * Map rating to card tier
     */
    private function getCardTier(int $rating): string
    {
        if ($rating >= 96) return 'pink_diamond';
        if ($rating >= 93) return 'diamond';
        if ($rating >= 90) return 'amethyst';
        if ($rating >= 88) return 'ruby';
        if ($rating >= 85) return 'sapphire';
        if ($rating >= 80) return 'emerald';
        if ($rating >= 75) return 'gold';
        if ($rating >= 72) return 'silver';
        if ($rating >= 69) return 'bronze';
        return 'common';
    }

    /**
     * Create a New Threads player card
     */
    private function createNewThreadsPlayer(string $name, int $rating, ?int $basePlayerId = null): Player
    {
        $cardTier = $this->getCardTier($rating);
        
        // Try to find base player for attribute copying
        $basePlayer = null;
        if ($basePlayerId) {
            $basePlayer = Player::find($basePlayerId);
        } else {
            $basePlayer = Player::where('name', $name)->first();
        }

        // Generate card art path
        $cardArtName = strtolower(str_replace([' ', "'"], '', $name));
        $cardArtPath = "new_threads/{$cardArtName}NewThreads.png";

        $playerData = [
            'name' => $name,
            'overall_rating' => $rating,
            'card_tier' => $cardTier,
            'card_art' => $cardArtPath,
            'obtainable_from_packs' => false, // Program-exclusive
        ];

        // Copy attributes from base player if available
        if ($basePlayer) {
            $playerData['team'] = $basePlayer->team;
            $playerData['position'] = $basePlayer->position;
            
            // Copy category ratings if available
            $categoryRatings = ['outside_scoring', 'inside_scoring', 'defense', 'athleticism', 'playmaking', 'rebounding'];
            foreach ($categoryRatings as $category) {
                if ($basePlayer->$category !== null) {
                    $playerData[$category] = $basePlayer->$category;
                }
            }
        } else {
            // Defaults if base player not found
            $playerData['team'] = 'Free Agent';
            $playerData['position'] = 'G';
        }

        return Player::create($playerData);
    }

    public function run(): void
    {
        // Create all New Threads players
        $this->command->info('Creating New Threads player cards...');

        // Reward players
        $bradleyBeal = $this->createNewThreadsPlayer('Bradley Beal', 84, 161);
        $mylesTurner = $this->createNewThreadsPlayer('Myles Turner', 85, 224);
        $deandreAyton = $this->createNewThreadsPlayer('DeAndre Ayton', 86, 176);
        $desmondBane = $this->createNewThreadsPlayer('Desmond Bane', 87, 1399);
        $kevinDurant = $this->createNewThreadsPlayer('Kevin Durant', 88, 121);

        // Pack players
        $chrisPaul = $this->createNewThreadsPlayer('Chris Paul', 84, 162);
        $anferneeSimons = $this->createNewThreadsPlayer('Anfernee Simons', 84, 21);
        $duncanRobinson = $this->createNewThreadsPlayer('Duncan Robinson', 84, 94);
        $michaelPorterJr = $this->createNewThreadsPlayer('Michael Porter Jr.', 85);
        $damianLillard = $this->createNewThreadsPlayer('Damian Lillard', 85, 1776);
        $cameronJohnson = $this->createNewThreadsPlayer('Cameron Johnson', 85, 1257);
        $kristapsPorzingis = $this->createNewThreadsPlayer('Kristaps Porzingis', 88, 2);
        $jalenGreen = $this->createNewThreadsPlayer('Jalen Green', 87, 1760);
        $normanPowell = $this->createNewThreadsPlayer('Norman Powell', 88, 208);

        // Find or create the New Threads program
        $program = Program::firstOrCreate(
            ['name' => 'New Threads'],
            [
                'description' => 'Complete challenges and earn stars to unlock exclusive New Threads rewards',
                'type' => 'star',
                'stars_required' => 100,
                'category' => 'general',
                'active' => true,
                'image_url' => null,
            ]
        );

        // Update the program if it already exists
        if (!$program->wasRecentlyCreated) {
            $program->update([
                'description' => 'Complete challenges and earn stars to unlock exclusive New Threads rewards',
                'type' => 'star',
                'stars_required' => 100,
                'category' => 'general',
                'active' => true,
            ]);
        }

        // Delete existing rewards and challenges
        $program->rewards()->delete();
        $program->challenges()->delete();

        // Find packs
        $standardPack = Pack::find(1); // Standard Pack
        $ballinPack = Pack::find(11); // Ballin is a Habit Pack

        // Create New Threads Pack
        $newThreadsPack = Pack::firstOrCreate(
            ['name' => 'New Threads Pack'],
            [
                'description' => 'New Threads Pack - Contains 9 New Threads cards',
                'type' => 'standard',
                'card_count' => 9,
                'specified_players' => [
                    $chrisPaul->id,
                    $anferneeSimons->id,
                    $duncanRobinson->id,
                    $michaelPorterJr->id,
                    $damianLillard->id,
                    $cameronJohnson->id,
                    $kristapsPorzingis->id,
                    $jalenGreen->id,
                    $normanPowell->id,
                ],
                'cost' => 0, // Program pack, not purchasable
                'active' => true,
                'available_in_shop' => false,
            ]
        );

        // Create rewards
        $rewards = [
            // 5 stars: 2000 xp
            [
                'stars' => 5,
                'type' => 'program_xp',
                'program_id' => null,
                'amount' => 2000,
                'desc' => '2000 XP',
            ],
            // 10 stars: Standard Pack
            [
                'stars' => 10,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 1,
                'desc' => 'Standard Pack',
            ],
            // 15 stars: 250 stubs
            [
                'stars' => 15,
                'type' => 'stubs',
                'player_id' => null,
                'amount' => 250,
                'desc' => '250 Stubs',
            ],
            // 20 stars: 84 New Threads Bradley Beal
            [
                'stars' => 20,
                'type' => 'player',
                'player_id' => $bradleyBeal->id,
                'amount' => null,
                'desc' => "84 New Threads {$bradleyBeal->name}",
            ],
            // 25 stars: 2000 xp
            [
                'stars' => 25,
                'type' => 'program_xp',
                'program_id' => null,
                'amount' => 2000,
                'desc' => '2000 XP',
            ],
            // 30 stars: Standard Pack
            [
                'stars' => 30,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 1,
                'desc' => 'Standard Pack',
            ],
            // 35 stars: 250 stubs
            [
                'stars' => 35,
                'type' => 'stubs',
                'player_id' => null,
                'amount' => 250,
                'desc' => '250 Stubs',
            ],
            // 40 stars: 85 New Threads Myles Turner
            [
                'stars' => 40,
                'type' => 'player',
                'player_id' => $mylesTurner->id,
                'amount' => null,
                'desc' => "85 New Threads {$mylesTurner->name}",
            ],
            // 45 stars: 3000 xp
            [
                'stars' => 45,
                'type' => 'program_xp',
                'program_id' => null,
                'amount' => 3000,
                'desc' => '3000 XP',
            ],
            // 50 stars: Standard Pack
            [
                'stars' => 50,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 1,
                'desc' => 'Standard Pack',
            ],
            // 55 stars: 3000 xp
            [
                'stars' => 55,
                'type' => 'program_xp',
                'program_id' => null,
                'amount' => 3000,
                'desc' => '3000 XP',
            ],
            // 60 stars: 86 New Threads DeAndre Ayton
            [
                'stars' => 60,
                'type' => 'player',
                'player_id' => $deandreAyton->id,
                'amount' => null,
                'desc' => "86 New Threads {$deandreAyton->name}",
            ],
            // 65 stars: Standard Pack
            [
                'stars' => 65,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 1,
                'desc' => 'Standard Pack',
            ],
            // 70 stars: 5000 xp
            [
                'stars' => 70,
                'type' => 'program_xp',
                'program_id' => null,
                'amount' => 5000,
                'desc' => '5000 XP',
            ],
            // 75 stars: Ballin is a Habit Pack
            [
                'stars' => 75,
                'type' => 'pack',
                'pack_id' => $ballinPack ? $ballinPack->id : null,
                'amount' => 1,
                'desc' => 'Ballin is a Habit Pack',
            ],
            // 80 stars: 87 New Threads Desmond Bane
            [
                'stars' => 80,
                'type' => 'player',
                'player_id' => $desmondBane->id,
                'amount' => null,
                'desc' => "87 New Threads {$desmondBane->name}",
            ],
            // 85 stars: Standard Pack
            [
                'stars' => 85,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 1,
                'desc' => 'Standard Pack',
            ],
            // 90 stars: 5000xp
            [
                'stars' => 90,
                'type' => 'program_xp',
                'program_id' => null,
                'amount' => 5000,
                'desc' => '5000 XP',
            ],
            // 95 stars: New Threads Pack
            [
                'stars' => 95,
                'type' => 'pack',
                'pack_id' => $newThreadsPack->id,
                'amount' => 1,
                'desc' => 'New Threads Pack',
            ],
            // 100 stars: 88 New Threads Kevin Durant
            [
                'stars' => 100,
                'type' => 'player',
                'player_id' => $kevinDurant->id,
                'amount' => null,
                'desc' => "88 New Threads {$kevinDurant->name}",
            ],
        ];

        foreach ($rewards as $reward) {
            ProgramReward::create([
                'program_id' => $program->id,
                'xp_threshold' => null,
                'stars_threshold' => $reward['stars'],
                'reward_type' => $reward['type'],
                'reward_id' => $reward['type'] === 'player' ? $reward['player_id'] : ($reward['type'] === 'pack' ? $reward['pack_id'] : ($reward['type'] === 'program_xp' ? $reward['program_id'] : null)),
                'reward_amount' => $reward['amount'],
                'description' => $reward['desc'],
                'available' => true,
            ]);
        }

        // Create challenges
        $challenges = [
            // 10 stars - Tally 250pxp with 84 New Threads Bradley Beal
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => (string)$bradleyBeal->id,
                'stars_reward' => 10,
                'description' => "Tally 250 PXP with 84 New Threads {$bradleyBeal->name}",
            ],
            // 10 stars - Tally 250pxp with 85 New Threads Myles Turner
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => (string)$mylesTurner->id,
                'stars_reward' => 10,
                'description' => "Tally 250 PXP with 85 New Threads {$mylesTurner->name}",
            ],
            // 10 stars - Tally 250pxp with 86 New Threads DeAndre Ayton
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => (string)$deandreAyton->id,
                'stars_reward' => 10,
                'description' => "Tally 250 PXP with 86 New Threads {$deandreAyton->name}",
            ],
            // 10 stars - Tally 250pxp with 87 New Threads Desmond Bane
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => (string)$desmondBane->id,
                'stars_reward' => 10,
                'description' => "Tally 250 PXP with 87 New Threads {$desmondBane->name}",
            ],
            // 10 stars - Tally 500pxp with any New Threads player
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 500,
                'constraint_type' => null, // Note: Application logic should check card_art contains "new_threads"
                'constraint_value' => 'new_threads', // Stored as hint for application logic
                'stars_reward' => 10,
                'description' => 'Tally 500 PXP with any New Threads player',
            ],
            // 10 stars - Tally 1000pxp with any New Threads player
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 1000,
                'constraint_type' => null, // Note: Application logic should check card_art contains "new_threads"
                'constraint_value' => 'new_threads', // Stored as hint for application logic
                'stars_reward' => 10,
                'description' => 'Tally 1000 PXP with any New Threads player',
            ],
            // 10 stars - Tally 1500pxp with any New Threads player
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 1500,
                'constraint_type' => null, // Note: Application logic should check card_art contains "new_threads"
                'constraint_value' => 'new_threads', // Stored as hint for application logic
                'stars_reward' => 10,
                'description' => 'Tally 1500 PXP with any New Threads player',
            ],
            // 10 stars - Play a game
            [
                'type' => 'game_count',
                'target_stat' => null,
                'target_value' => 1,
                'constraint_type' => null,
                'constraint_value' => null,
                'stars_reward' => 10,
                'description' => 'Play a game',
            ],
            // 5 stars - Score 20 points with any Houston Rockets player
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 20,
                'constraint_type' => 'team',
                'constraint_value' => 'Houston Rockets',
                'stars_reward' => 5,
                'description' => 'Score 20 points with any Houston Rockets player',
            ],
            // 5 stars - Make 3 3pters with any Milwaukee Bucks player
            [
                'type' => 'stat',
                'target_stat' => 'threes',
                'target_value' => 3,
                'constraint_type' => 'team',
                'constraint_value' => 'Milwaukee Bucks',
                'stars_reward' => 5,
                'description' => 'Make 3 three-pointers with any Milwaukee Bucks player',
            ],
            // 5 stars - Dish 10 assists with any Los Angeles Clippers player
            [
                'type' => 'stat',
                'target_stat' => 'assists',
                'target_value' => 10,
                'constraint_type' => 'team',
                'constraint_value' => 'LA Clippers',
                'stars_reward' => 5,
                'description' => 'Dish 10 assists with any Los Angeles Clippers player',
            ],
            // 5 stars - Score 100 points with any player
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 100,
                'constraint_type' => null,
                'constraint_value' => null,
                'stars_reward' => 5,
                'description' => 'Score 100 points with any player',
            ],
        ];

        foreach ($challenges as $challenge) {
            ProgramChallenge::create([
                'program_id' => $program->id,
                'type' => $challenge['type'],
                'target_stat' => $challenge['target_stat'],
                'target_value' => $challenge['target_value'],
                'constraint_type' => $challenge['constraint_type'],
                'constraint_value' => $challenge['constraint_value'],
                'stars_reward' => $challenge['stars_reward'],
                'xp_reward' => null,
                'description' => $challenge['description'],
            ]);
        }

        $this->command->info('New Threads program created successfully!');
        $this->command->info('Program ID: ' . $program->id);
        $this->command->info('Created ' . count($rewards) . ' rewards');
        $this->command->info('Created ' . count($challenges) . ' challenges');
        $this->command->info('Created pack: New Threads Pack (ID: ' . $newThreadsPack->id . ')');
        $this->command->info('Created 14 New Threads player cards');
    }
}
