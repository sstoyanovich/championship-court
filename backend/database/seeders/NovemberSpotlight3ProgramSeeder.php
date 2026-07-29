<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramChallenge;
use App\Models\Player;
use App\Models\Pack;

class NovemberSpotlight3ProgramSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create the November Spotlight 3 program
        $program = Program::firstOrCreate(
            ['name' => 'November Spotlight 3'],
            [
                'description' => 'Complete challenges and earn stars to unlock exclusive November Spotlight 3 rewards',
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
                'description' => 'Complete challenges and earn stars to unlock exclusive November Spotlight 3 rewards',
                'type' => 'star',
                'stars_required' => 50,
                'category' => 'general',
                'active' => true,
            ]);
        }

        // Delete existing rewards and challenges
        $program->rewards()->delete();
        $program->challenges()->delete();

        // Find players
        $franzWagner = Player::find(1870); // 88 Topps Now
        $donovanClingan = Player::find(1877); // 88 Spotlight (actually 89, but has spotlight card_art)
        $tyreseMaxey = Player::find(1876); // 90 Spotlight

        // Find packs
        $standardPack = Pack::find(1); // Standard Pack
        $ballinPack = Pack::find(11); // Ballin is a Habit Pack

        // Create November Spotlight Pack 3
        $novemberSpotlightPack = Pack::firstOrCreate(
            ['name' => 'November Spotlight Pack 3'],
            [
                'description' => 'November Spotlight Pack 3 - Contains 3 Spotlight and Topps Now cards',
                'type' => 'standard',
                'card_count' => 3,
                'specified_players' => [
                    1869, // James Harden (89 Topps Now)
                    1875, // Jamal Murray (88 Spotlight - actually 89, but has spotlight card_art)
                    1878, // Josh Giddey (88 Spotlight - actually 89, but has spotlight card_art)
                ],
                'cost' => 0, // Program pack, not purchasable
                'active' => true,
                'available_in_shop' => false,
            ]
        );

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
            // 10 stars: 88 Topps Now Franz Wagner
            [
                'stars' => 10,
                'type' => 'player',
                'player_id' => $franzWagner ? $franzWagner->id : null,
                'amount' => null,
                'desc' => $franzWagner ? "88 {$franzWagner->name} (Topps Now)" : '88 Topps Now Franz Wagner',
            ],
            // 15 stars: 2x Standard Pack
            [
                'stars' => 15,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 2,
                'desc' => '2x Standard Pack',
            ],
            // 20 stars: 88 Spotlight Donovan Clingan
            [
                'stars' => 20,
                'type' => 'player',
                'player_id' => $donovanClingan ? $donovanClingan->id : null,
                'amount' => null,
                'desc' => $donovanClingan ? "88 Spotlight {$donovanClingan->name}" : '88 Spotlight Donovan Clingan',
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
            // 45 stars: November Spotlight Pack 3
            [
                'stars' => 45,
                'type' => 'pack',
                'pack_id' => $novemberSpotlightPack->id,
                'amount' => 1,
                'desc' => 'November Spotlight Pack 3',
            ],
            // 50 stars: 90 Spotlight Tyrese Maxey
            [
                'stars' => 50,
                'type' => 'player',
                'player_id' => $tyreseMaxey ? $tyreseMaxey->id : null,
                'amount' => null,
                'desc' => $tyreseMaxey ? "90 Spotlight {$tyreseMaxey->name}" : '90 Spotlight Tyrese Maxey',
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
            // 5 stars - Tally 250pxp with 88 Topps Now Franz Wagner
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $franzWagner ? (string)$franzWagner->id : null,
                'stars_reward' => 5,
                'description' => $franzWagner ? "Tally 250 PXP with 88 Topps Now {$franzWagner->name}" : 'Tally 250 PXP with 88 Topps Now Franz Wagner',
            ],
            // 5 stars - Tally 250pxp with 88 Spotlight Donovan Clingan
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $donovanClingan ? (string)$donovanClingan->id : null,
                'stars_reward' => 5,
                'description' => $donovanClingan ? "Tally 250 PXP with 88 Spotlight {$donovanClingan->name}" : 'Tally 250 PXP with 88 Spotlight Donovan Clingan',
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
            // 5 stars - Score 20 points with any Orlando Magic player
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 20,
                'constraint_type' => 'team',
                'constraint_value' => 'Orlando Magic',
                'stars_reward' => 5,
                'description' => 'Score 20 points with any Orlando Magic player',
            ],
            // 5 stars - Score 54 points with any Philadelphia 76ers player
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 54,
                'constraint_type' => 'team',
                'constraint_value' => 'Philadelphia 76ers',
                'stars_reward' => 5,
                'description' => 'Score 54 points with any Philadelphia 76ers player',
            ],
            // 5 stars - Grab 10 rebounds with any Portland Trailblazers player
            [
                'type' => 'stat',
                'target_stat' => 'rebounds',
                'target_value' => 10,
                'constraint_type' => 'team',
                'constraint_value' => 'Portland Trail Blazers',
                'stars_reward' => 5,
                'description' => 'Grab 10 rebounds with any Portland Trail Blazers player',
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

        $this->command->info('November Spotlight 3 program created successfully!');
        $this->command->info('Program ID: ' . $program->id);
        $this->command->info('Created ' . count($rewards) . ' rewards');
        $this->command->info('Created ' . count($challenges) . ' challenges');
        $this->command->info('Created pack: November Spotlight Pack 3 (ID: ' . $novemberSpotlightPack->id . ')');
    }
}
