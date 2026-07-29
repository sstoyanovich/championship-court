<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramChallenge;
use App\Models\Player;
use App\Models\Pack;

class NovemberSpotlight5ProgramSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create the November Spotlight 5 program
        $program = Program::firstOrCreate(
            ['name' => 'November Spotlight 5'],
            [
                'description' => 'Complete challenges and earn stars to unlock exclusive November Spotlight 5 rewards',
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
                'description' => 'Complete challenges and earn stars to unlock exclusive November Spotlight 5 rewards',
                'type' => 'star',
                'stars_required' => 100,
                'category' => 'general',
                'active' => true,
            ]);
        }

        // Delete existing rewards and challenges
        $program->rewards()->delete();
        $program->challenges()->delete();

        // Find players - using closest matches that exist
        // Note: Some Spotlight versions may need to be created with card_art set
        $jaylenBrown = Player::where('name', 'Jaylen Brown')->where('overall_rating', 90)->first(); // ID: 19
        $devinBooker = Player::where('name', 'Devin Booker')->where('overall_rating', 90)->first() 
                     ?? Player::where('name', 'Devin Booker')->where('overall_rating', 91)->first(); // Use 91 if 90 doesn't exist
        $anthonyEdwards = Player::where('name', 'Anthony Edwards')->where('overall_rating', 90)->first();
        $ivicaZubac = Player::where('name', 'Ivica Zubac')->where('overall_rating', 90)->first()
                    ?? Player::where('name', 'Ivica Zubac')->where('overall_rating', 87)->first(); // Use 87 if 90 doesn't exist
        $jaimeJaquez = Player::where('name', 'LIKE', '%Jaquez%')->where('overall_rating', 91)->first();
        $donovanMitchell = Player::where('name', 'Donovan Mitchell')->where('overall_rating', 91)->first()
                         ?? Player::where('name', 'Donovan Mitchell')->where('overall_rating', 93)->first(); // Use 93 if 91 doesn't exist
        
        // Pack players
        $lauriMarkkanen = Player::where('name', 'Lauri Markkanen')->where('overall_rating', 90)->first()
                        ?? Player::where('name', 'Lauri Markkanen')->where('overall_rating', 84)->first();
        $cooperFlagg = Player::where('name', 'Cooper Flagg')->where('overall_rating', 90)->first()
                     ?? Player::where('name', 'Cooper Flagg')->where('overall_rating', 86)->first();
        $cadeCunningham = Player::where('name', 'Cade Cunningham')->where('overall_rating', 91)->first()
                        ?? Player::where('name', 'Cade Cunningham')->where('overall_rating', 92)->first();
        $karlAnthonyTowns = Player::where('name', 'Karl-Anthony Towns')->where('overall_rating', 91)->first()
                          ?? Player::where('name', 'Karl-Anthony Towns')->where('overall_rating', 92)->first();

        // Find packs
        $standardPack = Pack::find(1); // Standard Pack
        $ballinPack = Pack::find(11); // Ballin is a Habit Pack

        // Create November Spotlight Pack 5
        $novemberSpotlightPack5 = Pack::firstOrCreate(
            ['name' => 'November Spotlight Pack 5'],
            [
                'description' => 'November Spotlight Pack 5 - Contains 4 Spotlight cards',
                'type' => 'standard',
                'card_count' => 4,
                'specified_players' => array_filter([
                    $lauriMarkkanen ? $lauriMarkkanen->id : null,
                    $cooperFlagg ? $cooperFlagg->id : null,
                    $cadeCunningham ? $cadeCunningham->id : null,
                    $karlAnthonyTowns ? $karlAnthonyTowns->id : null,
                ]),
                'cost' => 0, // Program pack, not purchasable
                'active' => true,
                'available_in_shop' => false,
            ]
        );

        // Update specified_players to remove nulls
        $packPlayers = array_filter($novemberSpotlightPack5->specified_players, fn($id) => $id !== null);
        $novemberSpotlightPack5->update(['specified_players' => array_values($packPlayers)]);

        // Get all Spotlight players that are obtainable from packs
        // This includes Spotlight players with card_art containing "spotlight"
        $allSpotlightPlayers = Player::where('card_art', 'LIKE', '%spotlight%')
            ->where('obtainable_from_packs', true)
            ->pluck('id')
            ->toArray();

        // If no Spotlight players are marked obtainable_from_packs, include all Spotlight players
        if (empty($allSpotlightPlayers)) {
            $allSpotlightPlayers = Player::where('card_art', 'LIKE', '%spotlight%')
                ->pluck('id')
                ->toArray();
        }

        // Create November Spotlight Pack (comprehensive pack with all Spotlight players)
        $novemberSpotlightPack = Pack::firstOrCreate(
            ['name' => 'November Spotlight Pack'],
            [
                'description' => 'November Spotlight Pack - Contains Spotlight players attainable from packs',
                'type' => 'standard',
                'card_count' => 5,
                'specified_players' => $allSpotlightPlayers,
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
            // 10 stars: 90 Spotlight Jaylen Brown
            [
                'stars' => 10,
                'type' => 'player',
                'player_id' => $jaylenBrown ? $jaylenBrown->id : null,
                'amount' => null,
                'desc' => $jaylenBrown ? "90 Spotlight {$jaylenBrown->name}" : '90 Spotlight Jaylen Brown',
            ],
            // 15 stars: Standard Pack
            [
                'stars' => 15,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 1,
                'desc' => 'Standard Pack',
            ],
            // 20 stars: 90 Spotlight Devin Booker
            [
                'stars' => 20,
                'type' => 'player',
                'player_id' => $devinBooker ? $devinBooker->id : null,
                'amount' => null,
                'desc' => $devinBooker ? "90 Spotlight {$devinBooker->name}" : '90 Spotlight Devin Booker',
            ],
            // 25 stars: Ballin is a Habit Pack
            [
                'stars' => 25,
                'type' => 'pack',
                'pack_id' => $ballinPack ? $ballinPack->id : null,
                'amount' => 1,
                'desc' => 'Ballin is a Habit Pack',
            ],
            // 30 stars: 5000 xp
            [
                'stars' => 30,
                'type' => 'program_xp',
                'program_id' => null,
                'amount' => 5000,
                'desc' => '5000 XP',
            ],
            // 35 stars: 2000 stubs
            [
                'stars' => 35,
                'type' => 'stubs',
                'player_id' => null,
                'amount' => 2000,
                'desc' => '2000 Stubs',
            ],
            // 40 stars: 5x Standard Pack
            [
                'stars' => 40,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 5,
                'desc' => '5x Standard Pack',
            ],
            // 45 stars: 90 Spotlight Anthony Edwards
            [
                'stars' => 45,
                'type' => 'player',
                'player_id' => $anthonyEdwards ? $anthonyEdwards->id : null,
                'amount' => null,
                'desc' => $anthonyEdwards ? "90 Spotlight {$anthonyEdwards->name}" : '90 Spotlight Anthony Edwards',
            ],
            // 50 stars: Ballin is a Habit Pack
            [
                'stars' => 50,
                'type' => 'pack',
                'pack_id' => $ballinPack ? $ballinPack->id : null,
                'amount' => 1,
                'desc' => 'Ballin is a Habit Pack',
            ],
            // 55 stars: 5000 xp
            [
                'stars' => 55,
                'type' => 'program_xp',
                'program_id' => null,
                'amount' => 5000,
                'desc' => '5000 XP',
            ],
            // 60 stars: 90 Spotlight Ivica Zubac
            [
                'stars' => 60,
                'type' => 'player',
                'player_id' => $ivicaZubac ? $ivicaZubac->id : null,
                'amount' => null,
                'desc' => $ivicaZubac ? "90 Spotlight {$ivicaZubac->name}" : '90 Spotlight Ivica Zubac',
            ],
            // 65 stars: Standard Pack
            [
                'stars' => 65,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 1,
                'desc' => 'Standard Pack',
            ],
            // 70 stars: November Spotlight 5 Pack
            [
                'stars' => 70,
                'type' => 'pack',
                'pack_id' => $novemberSpotlightPack5->id,
                'amount' => 1,
                'desc' => 'November Spotlight Pack 5',
            ],
            // 75 stars: Standard Pack
            [
                'stars' => 75,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 1,
                'desc' => 'Standard Pack',
            ],
            // 80 stars: 3x Ballin is a Habit Pack
            [
                'stars' => 80,
                'type' => 'pack',
                'pack_id' => $ballinPack ? $ballinPack->id : null,
                'amount' => 3,
                'desc' => '3x Ballin is a Habit Pack',
            ],
            // 85 stars: 91 Spotlight Jaime Jaquez Jr
            [
                'stars' => 85,
                'type' => 'player',
                'player_id' => $jaimeJaquez ? $jaimeJaquez->id : null,
                'amount' => null,
                'desc' => $jaimeJaquez ? "91 Spotlight {$jaimeJaquez->name}" : '91 Spotlight Jaime Jaquez Jr',
            ],
            // 90 stars: 10000 xp
            [
                'stars' => 90,
                'type' => 'program_xp',
                'program_id' => null,
                'amount' => 10000,
                'desc' => '10000 XP',
            ],
            // 95 stars: November Spotlight Pack
            [
                'stars' => 95,
                'type' => 'pack',
                'pack_id' => $novemberSpotlightPack->id,
                'amount' => 1,
                'desc' => 'November Spotlight Pack',
            ],
            // 100 stars: 91 Spotlight Donovan Mitchell
            [
                'stars' => 100,
                'type' => 'player',
                'player_id' => $donovanMitchell ? $donovanMitchell->id : null,
                'amount' => null,
                'desc' => $donovanMitchell ? "91 Spotlight {$donovanMitchell->name}" : '91 Spotlight Donovan Mitchell',
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
            // 5 stars - Tally 250pxp with 90 Spotlight Jaylen Brown
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $jaylenBrown ? (string)$jaylenBrown->id : null,
                'stars_reward' => 5,
                'description' => 'Tally 250 PXP with 90 Spotlight Jaylen Brown',
            ],
            // 5 stars - Tally 250pxp with 90 Spotlight Devin Booker
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $devinBooker ? (string)$devinBooker->id : null,
                'stars_reward' => 5,
                'description' => 'Tally 250 PXP with 90 Spotlight Devin Booker',
            ],
            // 5 stars - Tally 250pxp with 90 Spotlight Anthony Edwards
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $anthonyEdwards ? (string)$anthonyEdwards->id : null,
                'stars_reward' => 5,
                'description' => 'Tally 250 PXP with 90 Spotlight Anthony Edwards',
            ],
            // 5 stars - Tally 250pxp with 90 Spotlight Ivica Zubac
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $ivicaZubac ? (string)$ivicaZubac->id : null,
                'stars_reward' => 5,
                'description' => 'Tally 250 PXP with 90 Spotlight Ivica Zubac',
            ],
            // 5 stars - Tally 250pxp with 91 Spotlight Jaime Jaquez Jr
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => $jaimeJaquez ? (string)$jaimeJaquez->id : null,
                'stars_reward' => 5,
                'description' => 'Tally 250 PXP with 91 Spotlight Jaime Jaquez Jr',
            ],
            // 20 stars - Tally 500pxp with any Spotlight player
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 500,
                'constraint_type' => null, // Note: Application logic should check card_art contains "spotlight"
                'constraint_value' => 'spotlight', // Stored as hint for application logic
                'stars_reward' => 20,
                'description' => 'Tally 500 PXP with any Spotlight player',
            ],
            // 20 stars - Tally 500pxp with any Topps now player
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 500,
                'constraint_type' => null, // Note: Application logic should check card_art contains "topps_now"
                'constraint_value' => 'topps_now', // Stored as hint for application logic
                'stars_reward' => 20,
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
            // 5 stars - Score 20 points with any Boston Celtics player
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 20,
                'constraint_type' => 'team',
                'constraint_value' => 'Boston Celtics',
                'stars_reward' => 5,
                'description' => 'Score 20 points with any Boston Celtics player',
            ],
            // 5 stars - Dish 5 assists with any Phoenix Suns player
            [
                'type' => 'stat',
                'target_stat' => 'assists',
                'target_value' => 5,
                'constraint_type' => 'team',
                'constraint_value' => 'Phoenix Suns',
                'stars_reward' => 5,
                'description' => 'Dish 5 assists with any Phoenix Suns player',
            ],
            // 5 stars - Grab 10 rebounds with any New York Knicks player
            [
                'type' => 'stat',
                'target_stat' => 'rebounds',
                'target_value' => 10,
                'constraint_type' => 'team',
                'constraint_value' => 'New York Knicks',
                'stars_reward' => 5,
                'description' => 'Grab 10 rebounds with any New York Knicks player',
            ],
            // 10 stars - Score 100 points with any player
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 100,
                'constraint_type' => null,
                'constraint_value' => null,
                'stars_reward' => 10,
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

        $this->command->info('November Spotlight 5 program created successfully!');
        $this->command->info('Program ID: ' . $program->id);
        $this->command->info('Created ' . count($rewards) . ' rewards');
        $this->command->info('Created ' . count($challenges) . ' challenges');
        $this->command->info('Created pack: November Spotlight Pack 5 (ID: ' . $novemberSpotlightPack5->id . ')');
        $this->command->info('Created pack: November Spotlight Pack (ID: ' . $novemberSpotlightPack->id . ') with ' . count($allSpotlightPlayers) . ' Spotlight players');
    }
}
