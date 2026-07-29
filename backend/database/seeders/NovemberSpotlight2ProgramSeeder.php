<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramChallenge;
use App\Models\Player;
use App\Models\Pack;

class NovemberSpotlight2ProgramSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create the November Spotlight 2 program
        $program = Program::firstOrCreate(
            ['name' => 'November Spotlight 2'],
            [
                'description' => 'Complete challenges and earn stars to unlock exclusive November Spotlight rewards',
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
                'description' => 'Complete challenges and earn stars to unlock exclusive November Spotlight rewards',
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
        $andrewWiggins = Player::find(1867); // 87 Topps Now
        $ryanRollins = Player::find(1873); // 88 Spotlight
        $deniAvdija = Player::find(1863); // 88 Spotlight

        // Find packs
        $standardPack = Pack::find(1); // Standard Pack
        $ballinPack = Pack::find(11); // Ballin is a Habit Pack

        // Create November Spotlight Pack 2
        $novemberSpotlightPack = Pack::firstOrCreate(
            ['name' => 'November Spotlight Pack 2'],
            [
                'description' => 'November Spotlight Pack 2 - Contains 4 Spotlight and Topps Now cards',
                'type' => 'standard',
                'card_count' => 4,
                'specified_players' => [
                    1868, // Desmond Bane (87 Topps Now)
                    1874, // Stephon Castle (88 Spotlight)
                    1872, // Jalen Johnson (88 Spotlight)
                    1871, // Trey Murphy III (88 Spotlight)
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
            // 10 stars: 87 Topps Now Andrew Wiggins
            [
                'stars' => 10,
                'type' => 'player',
                'player_id' => $andrewWiggins ? $andrewWiggins->id : null,
                'amount' => null,
                'desc' => $andrewWiggins ? "87 {$andrewWiggins->name} (Topps Now)" : '87 Topps Now Andrew Wiggins',
            ],
            // 15 stars: 2x Standard Pack
            [
                'stars' => 15,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 2,
                'desc' => '2x Standard Pack',
            ],
            // 20 stars: 88 Spotlight Ryan Rollins
            [
                'stars' => 20,
                'type' => 'player',
                'player_id' => $ryanRollins ? $ryanRollins->id : null,
                'amount' => null,
                'desc' => $ryanRollins ? "88 {$ryanRollins->name} (Spotlight)" : '88 Spotlight Ryan Rollins',
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
                'pack_id' => $novemberSpotlightPack->id,
                'amount' => 1,
                'desc' => 'November Spotlight Pack 2',
            ],
            // 50 stars: 88 Spotlight Deni Avdija
            [
                'stars' => 50,
                'type' => 'player',
                'player_id' => $deniAvdija ? $deniAvdija->id : null,
                'amount' => null,
                'desc' => $deniAvdija ? "88 {$deniAvdija->name} (Spotlight)" : '88 Spotlight Deni Avdija',
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
            // 5 stars - Tally 250pxp with 87 Topps Now Andrew Wiggins
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $andrewWiggins ? (string)$andrewWiggins->id : null,
                'stars_reward' => 5,
                'description' => $andrewWiggins ? "Tally 250 PXP with 87 Topps Now {$andrewWiggins->name}" : 'Tally 250 PXP with 87 Topps Now Andrew Wiggins',
            ],
            // 5 stars - Tally 250pxp with 88 Spotlight Ryan Rollins
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $ryanRollins ? (string)$ryanRollins->id : null,
                'stars_reward' => 5,
                'description' => $ryanRollins ? "Tally 250 PXP with 88 Spotlight {$ryanRollins->name}" : 'Tally 250 PXP with 88 Spotlight Ryan Rollins',
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
            // 5 stars - Score 20 points with any Miami Heat player
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 20,
                'constraint_type' => 'team',
                'constraint_value' => 'Miami Heat',
                'stars_reward' => 5,
                'description' => 'Score 20 points with any Miami Heat player',
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

        $this->command->info('November Spotlight 2 program created successfully!');
        $this->command->info('Program ID: ' . $program->id);
        $this->command->info('Created ' . count($rewards) . ' rewards');
        $this->command->info('Created ' . count($challenges) . ' challenges');
        $this->command->info('Created pack: November Spotlight Pack 2 (ID: ' . $novemberSpotlightPack->id . ')');
    }
}

