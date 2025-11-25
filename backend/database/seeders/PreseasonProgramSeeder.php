<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\Player;
use App\Models\Pack;

class PreseasonProgramSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create the Preseason program
        $program = Program::firstOrCreate(
            ['name' => 'Preseason'],
            [
                'description' => 'Complete challenges and earn XP to unlock exclusive rewards',
                'type' => 'xp',
                'total_xp_required' => 500000,
                'category' => 'general',
                'image_url' => 'https://via.placeholder.com/400x200?text=Preseason',
            ]
        );
        
        // Update the program if it already exists
        if (!$program->wasRecentlyCreated) {
            $program->update([
                'description' => 'Complete challenges and earn XP to unlock exclusive rewards',
                'type' => 'xp',
                'total_xp_required' => 500000,
                'category' => 'general',
            ]);
        }

        // Delete existing rewards
        $program->rewards()->delete();

        // Define all the rewards from the document
        $rewards = [
            ['xp' => 0, 'type' => 'pack', 'name' => 'Starter Pack', 'amount' => 1],
            ['xp' => 5000, 'type' => 'stubs', 'amount' => 500],
            ['xp' => 10000, 'type' => 'player', 'name' => 'Darrell Armstrong', 'rating' => 78, 'tier' => 'Rookie'],
            ['xp' => 20000, 'type' => 'stubs', 'amount' => 1000],
            ['xp' => 25000, 'type' => 'player', 'name' => 'Shane Battier', 'rating' => 79, 'tier' => 'Veteran'],
            ['xp' => 35000, 'type' => 'pack', 'name' => 'Standard Pack', 'amount' => 2],
            ['xp' => 50000, 'type' => 'player', 'name' => 'Chris Kaman', 'rating' => 80, 'tier' => 'Rookie'],
            ['xp' => 65000, 'type' => 'stubs', 'amount' => 1000],
            ['xp' => 80000, 'type' => 'player', 'name' => 'Ben Gordon', 'rating' => 81, 'tier' => 'Breakout'],
            ['xp' => 95000, 'type' => 'pack', 'name' => 'Standard Pack', 'amount' => 2],
            ['xp' => 110000, 'type' => 'player', 'name' => 'Brandon Bass', 'rating' => 82, 'tier' => 'Standout'],
            ['xp' => 125000, 'type' => 'stubs', 'amount' => 2000],
            ['xp' => 140000, 'type' => 'player', 'name' => 'Jamal Crawford', 'rating' => 84, 'tier' => 'Contributor'],
            ['xp' => 160000, 'type' => 'pack', 'name' => 'Ballin is a Habit Pack', 'amount' => 1],
            ['xp' => 180000, 'type' => 'player', 'name' => 'David West', 'rating' => 85, 'tier' => 'All-Star'],
            ['xp' => 200000, 'type' => 'stubs', 'amount' => 2500],
            ['xp' => 220000, 'type' => 'player', 'name' => 'Gerald Wallace', 'rating' => 86, 'tier' => '2nd Half Heroes'],
            ['xp' => 240000, 'type' => 'pack', 'name' => 'Headliner 1', 'amount' => 1],
            ['xp' => 260000, 'type' => 'player', 'name' => 'Danny Granger', 'rating' => 87, 'tier' => 'Awards'],
            ['xp' => 280000, 'type' => 'stubs', 'amount' => 3000],
            ['xp' => 300000, 'type' => 'pack', 'name' => 'Headliner 1', 'amount' => 2],
            ['xp' => 325000, 'type' => 'pack', 'name' => 'Preseason Boss Choice Pack', 'amount' => 1],
            ['xp' => 350000, 'type' => 'pack', 'name' => 'Ballin is a Habit Pack', 'amount' => 1],
            ['xp' => 375000, 'type' => 'pack', 'name' => 'Preseason Boss Choice Pack', 'amount' => 1],
            ['xp' => 400000, 'type' => 'stubs', 'amount' => 3500],
            ['xp' => 425000, 'type' => 'pack', 'name' => 'Preseason Boss Choice Pack', 'amount' => 1],
            ['xp' => 450000, 'type' => 'stubs', 'amount' => 5000],
            ['xp' => 475000, 'type' => 'pack', 'name' => 'Elite Pack', 'amount' => 1],
            ['xp' => 500000, 'type' => 'stubs', 'amount' => 10000],
        ];

        foreach ($rewards as $reward) {
            $rewardData = [
                'program_id' => $program->id,
                'xp_threshold' => $reward['xp'],
            ];

            if ($reward['type'] === 'stubs') {
                $rewardData['reward_type'] = 'stubs';
                $rewardData['reward_amount'] = $reward['amount'];
                $rewardData['description'] = $reward['amount'] . ' Stubs';
            } elseif ($reward['type'] === 'player') {
                // Find or create the player
                $player = Player::firstOrCreate(
                    ['name' => $reward['name']],
                    [
                        'overall_rating' => $reward['rating'],
                        'card_tier' => $reward['tier'],
                        'position' => 'SF', // Default position
                        'team' => 'Free Agent',
                        'obtainable_from_packs' => false,
                    ]
                );

                $rewardData['reward_type'] = 'player';
                $rewardData['reward_id'] = $player->id;
                $rewardData['description'] = $reward['rating'] . ' ' . $reward['name'] . ' (' . $reward['tier'] . ')';
            } elseif ($reward['type'] === 'pack') {
                // Find or create the pack
                $pack = Pack::firstOrCreate(
                    ['name' => $reward['name']],
                    [
                        'description' => $reward['name'],
                        'type' => 'standard',
                        'cost' => 0, // Program packs are not purchasable
                        'card_count' => 5,
                        'odds_config' => json_encode([
                            'bronze' => 60,
                            'silver' => 30,
                            'gold' => 8,
                            'emerald' => 1.5,
                            'sapphire' => 0.4,
                            'amethyst' => 0.08,
                            'diamond' => 0.015,
                            'pink_diamond' => 0.005,
                        ]),
                        'available_in_shop' => false, // Not available in shop
                    ]
                );

                $rewardData['reward_type'] = 'pack';
                $rewardData['reward_id'] = $pack->id;
                $rewardData['reward_amount'] = $reward['amount'];
                $rewardData['description'] = $reward['name'] . ' ×' . $reward['amount'];
            }

            ProgramReward::create($rewardData);
        }

        $this->command->info('Preseason program updated successfully with ' . count($rewards) . ' rewards!');
    }
}

