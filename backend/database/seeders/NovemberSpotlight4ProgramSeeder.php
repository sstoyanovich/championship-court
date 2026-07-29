<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramChallenge;
use App\Models\Player;
use App\Models\Pack;

class NovemberSpotlight4ProgramSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create the November Spotlight 4 program
        $program = Program::firstOrCreate(
            ['name' => 'November Spotlight 4'],
            [
                'description' => 'Complete challenges and earn stars to unlock exclusive November Spotlight 4 rewards',
                'type' => 'star',
                'stars_required' => 50,
                'category' => 'general',
                'active' => true,
                'image_url' => null,
            ]
        );

        // Update the program if it already exists
        if (!$program->wasRecentlyCreated) {
            $program->update([
                'description' => 'Complete challenges and earn stars to unlock exclusive November Spotlight 4 rewards',
                'type' => 'star',
                'stars_required' => 50,
                'category' => 'general',
                'active' => true,
            ]);
        }

        // Delete existing rewards and challenges
        $program->rewards()->delete();
        $program->challenges()->delete();

        // Find players (using the 90 versions that exist)
        $reedSheppard = Player::find(1899); // 90 Topps Now
        $joshHart = Player::find(1880); // 90 Spotlight (assuming this is the spotlight version)
        $zachEdey = Player::find(1879); // 90 Spotlight
        $pascalSiakam = Player::find(1883); // 90 Spotlight
        $deAaronFox = Player::find(1882); // 90 Spotlight
        $scottieBarnes = Player::find(1881); // 91 Spotlight
        $rasheedWallace = Player::find(1898); // 91 Spotlight

        // Find packs
        $standardPack = Pack::find(1); // Standard Pack
        $ballinPack = Pack::find(11); // Ballin is a Habit Pack
        $novemberSpotlightPack2 = Pack::where('name', 'November Spotlight Pack 2')->first();

        // Create November Spotlight Pack 4
        $novemberSpotlightPack4 = Pack::firstOrCreate(
            ['name' => 'November Spotlight Pack 4'],
            [
                'description' => 'November Spotlight Pack 4 - Contains 4 Spotlight cards',
                'type' => 'standard',
                'card_count' => 4,
                'specified_players' => [
                    $pascalSiakam ? $pascalSiakam->id : null, // 90 Spotlight Pascal Siakam
                    $deAaronFox ? $deAaronFox->id : null, // 90 Spotlight De'Aaron Fox
                    $scottieBarnes ? $scottieBarnes->id : null, // 91 Spotlight Scottie Barnes
                    $rasheedWallace ? $rasheedWallace->id : null, // 91 Spotlight Rasheed Wallace
                ],
                'cost' => 0, // Program pack, not purchasable
                'active' => true,
                'available_in_shop' => false,
            ]
        );

        // Filter out null values from specified_players
        $packPlayers = array_filter($novemberSpotlightPack4->specified_players, fn($id) => $id !== null);
        $novemberSpotlightPack4->update(['specified_players' => array_values($packPlayers)]);

        // Create rewards
        $rewards = [
            // 5 stars: Standard Pack
            [
                'stars' => 5,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 1,
                'desc' => 'Standard Pack',
            ],
            // 10 stars: 90 Topps Now Reed Sheppard
            [
                'stars' => 10,
                'type' => 'player',
                'player_id' => $reedSheppard ? $reedSheppard->id : null,
                'amount' => null,
                'desc' => $reedSheppard ? "90 Topps Now {$reedSheppard->name}" : '90 Topps Now Reed Sheppard',
            ],
            // 15 stars: 2x Standard Pack
            [
                'stars' => 15,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 2,
                'desc' => '2x Standard Pack',
            ],
            // 20 stars: 90 Spotlight Josh Hart
            [
                'stars' => 20,
                'type' => 'player',
                'player_id' => $joshHart ? $joshHart->id : null,
                'amount' => null,
                'desc' => $joshHart ? "90 Spotlight {$joshHart->name}" : '90 Spotlight Josh Hart',
            ],
            // 25 stars: 3x Standard Pack
            [
                'stars' => 25,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 3,
                'desc' => '3x Standard Pack',
            ],
            // 30 stars: 2x Ballin is a Habit Packs
            [
                'stars' => 30,
                'type' => 'pack',
                'pack_id' => $ballinPack ? $ballinPack->id : null,
                'amount' => 2,
                'desc' => '2x Ballin is a Habit Pack',
            ],
            // 35 stars: 5x Standard Pack
            [
                'stars' => 35,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 5,
                'desc' => '5x Standard Pack',
            ],
            // 40 stars: 5000xp
            [
                'stars' => 40,
                'type' => 'program_xp',
                'program_id' => null, // This would need to be set to the target program for XP
                'amount' => 5000,
                'desc' => '5000 XP',
            ],
            // 45 stars: November Spotlight Pack 2
            [
                'stars' => 45,
                'type' => 'pack',
                'pack_id' => $novemberSpotlightPack2 ? $novemberSpotlightPack2->id : null,
                'amount' => 1,
                'desc' => 'November Spotlight Pack 2',
            ],
            // 50 stars: 90 Spotlight Zach Edey
            [
                'stars' => 50,
                'type' => 'player',
                'player_id' => $zachEdey ? $zachEdey->id : null,
                'amount' => null,
                'desc' => $zachEdey ? "90 Spotlight {$zachEdey->name}" : '90 Spotlight Zach Edey',
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
        // Note: Challenge descriptions mention "87 Topps Now Reed Sheppard" and "88 Spotlight Josh Hart"
        // but only 90 versions exist, so using those for constraint_value
        $challenges = [
            // 5 stars - Tally 250pxp with 87 Topps Now Reed Sheppard (using 90 version that exists)
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $reedSheppard ? (string)$reedSheppard->id : null,
                'stars_reward' => 5,
                'description' => 'Tally 250 PXP with 87 Topps Now Reed Sheppard',
            ],
            // 5 stars - Tally 250pxp with 88 Spotlight Josh Hart (using 90 version that exists)
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $joshHart ? (string)$joshHart->id : null,
                'stars_reward' => 5,
                'description' => 'Tally 250 PXP with 88 Spotlight Josh Hart',
            ],
            // 10 stars - Tally 500pxp with any Spotlight player
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 500,
                'constraint_type' => null, // Note: Application logic should check card_art contains "spotlight"
                'constraint_value' => 'spotlight', // Stored as hint for application logic
                'stars_reward' => 10,
                'description' => 'Tally 500 PXP with any Spotlight player',
            ],
            // 10 stars - Tally 500pxp with any Topps now player
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 500,
                'constraint_type' => null, // Note: Application logic should check card_art contains "topps_now"
                'constraint_value' => 'topps_now', // Stored as hint for application logic
                'stars_reward' => 10,
                'description' => 'Tally 500 PXP with any Topps Now player',
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
            // 5 stars - Dish 5 assists with any New York Knicks player
            [
                'type' => 'stat',
                'target_stat' => 'assists',
                'target_value' => 5,
                'constraint_type' => 'team',
                'constraint_value' => 'New York Knicks',
                'stars_reward' => 5,
                'description' => 'Dish 5 assists with any New York Knicks player',
            ],
            // 5 stars - Grab 10 rebounds with any Memphis Grizzlies player
            [
                'type' => 'stat',
                'target_stat' => 'rebounds',
                'target_value' => 10,
                'constraint_type' => 'team',
                'constraint_value' => 'Memphis Grizzlies',
                'stars_reward' => 5,
                'description' => 'Grab 10 rebounds with any Memphis Grizzlies player',
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

        $this->command->info('November Spotlight 4 program created successfully!');
        $this->command->info('Program ID: ' . $program->id);
        $this->command->info('Created ' . count($rewards) . ' rewards');
        $this->command->info('Created ' . count($challenges) . ' challenges');
        $this->command->info('Created pack: November Spotlight Pack 4 (ID: ' . $novemberSpotlightPack4->id . ')');
    }
}
