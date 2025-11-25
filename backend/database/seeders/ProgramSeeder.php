<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramChallenge;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Preseason XP Program
        $preseason = Program::create([
            'name' => 'Preseason',
            'description' => 'Welcome to Championship Court! Complete challenges and play games to earn rewards throughout the preseason.',
            'type' => 'xp',
            'total_xp_required' => 500000,
            'stars_required' => 50,
            'active' => true,
            'image_url' => null,
        ]);

        // Create XP threshold rewards for Preseason
        $preseasonRewards = [
            ['xp' => 10000, 'type' => 'stubs', 'amount' => 2500, 'reward_id' => null, 'desc' => '2,500 stubs'],
            ['xp' => 25000, 'type' => 'pack', 'amount' => 1, 'reward_id' => 1, 'desc' => 'Bronze Pack'],
            ['xp' => 50000, 'type' => 'stubs', 'amount' => 7500, 'reward_id' => null, 'desc' => '7,500 stubs'],
            ['xp' => 75000, 'type' => 'pack', 'amount' => 1, 'reward_id' => 2, 'desc' => 'Silver Pack'],
            ['xp' => 100000, 'type' => 'stubs', 'amount' => 15000, 'reward_id' => null, 'desc' => '15,000 stubs'],
            ['xp' => 150000, 'type' => 'pack', 'amount' => 1, 'reward_id' => 3, 'desc' => 'Gold Pack'],
            ['xp' => 200000, 'type' => 'stubs', 'amount' => 25000, 'reward_id' => null, 'desc' => '25,000 stubs'],
            ['xp' => 250000, 'type' => 'pack', 'amount' => 2, 'reward_id' => 3, 'desc' => '2x Gold Packs'],
            ['xp' => 300000, 'type' => 'stubs', 'amount' => 35000, 'reward_id' => null, 'desc' => '35,000 stubs'],
            ['xp' => 350000, 'type' => 'pack', 'amount' => 1, 'reward_id' => 4, 'desc' => 'Deluxe Pack'],
            ['xp' => 400000, 'type' => 'stubs', 'amount' => 45000, 'reward_id' => null, 'desc' => '45,000 stubs'],
            ['xp' => 500000, 'type' => 'pack', 'amount' => 1, 'reward_id' => 5, 'desc' => 'Elite Choice Pack - FINAL REWARD!'],
        ];

        foreach ($preseasonRewards as $reward) {
            ProgramReward::create([
                'program_id' => $preseason->id,
                'xp_threshold' => $reward['xp'],
                'reward_type' => $reward['type'],
                'reward_id' => $reward['reward_id'],
                'reward_amount' => $reward['amount'],
                'description' => $reward['desc'],
            ]);
        }

        // Create challenges for Preseason
        $preseasonChallenges = [
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 500,
                'constraint_type' => null,
                'constraint_value' => null,
                'xp_reward' => 5000,
                'description' => 'Score 500 total points',
            ],
            [
                'type' => 'stat',
                'target_stat' => 'assists',
                'target_value' => 100,
                'constraint_type' => null,
                'constraint_value' => null,
                'xp_reward' => 3000,
                'description' => 'Record 100 total assists',
            ],
            [
                'type' => 'stat',
                'target_stat' => 'rebounds',
                'target_value' => 200,
                'constraint_type' => null,
                'constraint_value' => null,
                'xp_reward' => 3000,
                'description' => 'Grab 200 total rebounds',
            ],
            [
                'type' => 'game_count',
                'target_stat' => null,
                'target_value' => 20,
                'constraint_type' => null,
                'constraint_value' => null,
                'xp_reward' => 4000,
                'description' => 'Play 20 games',
            ],
            [
                'type' => 'stat',
                'target_stat' => 'steals',
                'target_value' => 50,
                'constraint_type' => null,
                'constraint_value' => null,
                'xp_reward' => 2500,
                'description' => 'Get 50 total steals',
            ],
            [
                'type' => 'stat',
                'target_stat' => 'blocks',
                'target_value' => 50,
                'constraint_type' => null,
                'constraint_value' => null,
                'xp_reward' => 2500,
                'description' => 'Block 50 total shots',
            ],
        ];

        foreach ($preseasonChallenges as $challenge) {
            ProgramChallenge::create([
                'program_id' => $preseason->id,
                'type' => $challenge['type'],
                'target_stat' => $challenge['target_stat'],
                'target_value' => $challenge['target_value'],
                'constraint_type' => $challenge['constraint_type'],
                'constraint_value' => $challenge['constraint_value'],
                'stars_reward' => null,
                'xp_reward' => $challenge['xp_reward'],
                'description' => $challenge['description'],
            ]);
        }

        // Create Miami Heat Collection Star Program
        $heatProgram = Program::create([
            'name' => 'Miami Heat Collection',
            'description' => 'Complete challenges with Miami Heat players to earn stars and unlock an elite Heat player pack!',
            'type' => 'star',
            'total_xp_required' => null,
            'stars_required' => 50,
            'active' => true,
            'image_url' => null,
        ]);

        // Create final reward for Heat program
        ProgramReward::create([
            'program_id' => $heatProgram->id,
            'xp_threshold' => null,
            'stars_threshold' => 50,
            'reward_type' => 'pack',
            'reward_id' => 5, // Elite Choice Pack
            'reward_amount' => 1,
            'description' => '50 Stars - Elite Choice Pack',
        ]);

        // Create challenges for Miami Heat program
        $heatChallenges = [
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 100,
                'constraint_type' => 'team',
                'constraint_value' => 'Miami Heat',
                'stars_reward' => 5,
                'description' => 'Score 100 points with Miami Heat players',
            ],
            [
                'type' => 'stat',
                'target_stat' => 'assists',
                'target_value' => 50,
                'constraint_type' => 'team',
                'constraint_value' => 'Miami Heat',
                'stars_reward' => 5,
                'description' => 'Get 50 assists with Miami Heat players',
            ],
            [
                'type' => 'stat',
                'target_stat' => 'rebounds',
                'target_value' => 75,
                'constraint_type' => 'team',
                'constraint_value' => 'Miami Heat',
                'stars_reward' => 5,
                'description' => 'Grab 75 rebounds with Miami Heat players',
            ],
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 1000,
                'constraint_type' => 'team',
                'constraint_value' => 'Miami Heat',
                'stars_reward' => 10,
                'description' => 'Earn 1,000 PXP with Miami Heat players',
            ],
            [
                'type' => 'game_count',
                'target_stat' => null,
                'target_value' => 10,
                'constraint_type' => null,
                'constraint_value' => null,
                'stars_reward' => 5,
                'description' => 'Play 10 games with at least 3 Miami Heat players',
            ],
            [
                'type' => 'stat',
                'target_stat' => 'steals',
                'target_value' => 25,
                'constraint_type' => 'team',
                'constraint_value' => 'Miami Heat',
                'stars_reward' => 5,
                'description' => 'Get 25 steals with Miami Heat players',
            ],
            [
                'type' => 'stat',
                'target_stat' => 'blocks',
                'target_value' => 20,
                'constraint_type' => 'team',
                'constraint_value' => 'Miami Heat',
                'stars_reward' => 5,
                'description' => 'Block 20 shots with Miami Heat players',
            ],
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 2500,
                'constraint_type' => 'team',
                'constraint_value' => 'Miami Heat',
                'stars_reward' => 10,
                'description' => 'Earn 2,500 PXP with Miami Heat players',
            ],
        ];

        foreach ($heatChallenges as $challenge) {
            ProgramChallenge::create([
                'program_id' => $heatProgram->id,
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
    }
}
