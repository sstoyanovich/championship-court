<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ProgramChallenge;
use App\Models\ProgramReward;
use Illuminate\Database\Seeder;

class UpdateTeamAffinityChallengesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🎯 Updating Team Affinity Challenge Player Constraints...\n\n";

        // Get all Team Affinity programs
        $programs = Program::where('name', 'LIKE', '%Team Affinity')->get();

        $totalUpdated = 0;

        foreach ($programs as $program) {
            echo "Processing {$program->name}...\n";

            // Get the player rewards for this program
            $rewards = $program->rewards()
                ->where('reward_type', 'player')
                ->whereNotNull('reward_id')
                ->orderBy('stars_threshold')
                ->get();

            // Map rewards by star threshold
            $rewardMap = [];
            foreach ($rewards as $reward) {
                $rewardMap[$reward->stars_threshold] = $reward->reward_id;
            }

            // Get the 5 key player rewards
            $goldPlayer = $rewardMap[5] ?? null;           // 79 OVR - "TA Gold"
            $emeraldPlayer = $rewardMap[25] ?? null;       // 84 OVR - "TA Emerald" 
            $secondEmeraldPlayer = $rewardMap[55] ?? null; // 85 OVR - "TA Sapphire" (but called Emerald in description)
            $sapphirePlayer = $rewardMap[135] ?? null;     // 91 OVR - "TA Amethyst" (but actually Sapphire)
            $pinkDiamondPlayer = $rewardMap[285] ?? null;  // 99 OVR - "TA Pink Diamond"

            // Update the PXP challenges based on their descriptions
            $challenges = $program->challenges()->get();

            foreach ($challenges as $challenge) {
                $updated = false;
                $player = null;
                $updateData = [];

                // Match challenges by their description patterns
                if (str_contains($challenge->description, 'TA Gold reward card')) {
                    if ($goldPlayer) {
                        $player = \App\Models\Player::find($goldPlayer);
                        $updateData = [
                            'type' => 'pxp',
                            'target_stat' => 'pxp',
                            'target_value' => 180,
                            'constraint_type' => 'player',
                            'constraint_value' => (string)$goldPlayer,
                            'description' => $player 
                                ? "Earn 180 PXP with {$player->name} ({$player->overall_rating} OVR {$player->card_tier})"
                                : $challenge->description,
                        ];
                        $updated = true;
                        echo "  ✓ Updated Gold challenge: 180 PXP with " . ($player ? $player->name : "ID {$goldPlayer}") . "\n";
                    }
                } elseif (str_contains($challenge->description, 'TA Emerald reward card')) {
                    if ($emeraldPlayer) {
                        $player = \App\Models\Player::find($emeraldPlayer);
                        $updateData = [
                            'type' => 'pxp',
                            'target_stat' => 'pxp',
                            'target_value' => 300,
                            'constraint_type' => 'player',
                            'constraint_value' => (string)$emeraldPlayer,
                            'description' => $player 
                                ? "Earn 300 PXP with {$player->name} ({$player->overall_rating} OVR {$player->card_tier})"
                                : $challenge->description,
                        ];
                        $updated = true;
                        echo "  ✓ Updated Emerald challenge: 300 PXP with " . ($player ? $player->name : "ID {$emeraldPlayer}") . "\n";
                    }
                } elseif (str_contains($challenge->description, 'TA Sapphire reward card')) {
                    if ($secondEmeraldPlayer) {
                        $player = \App\Models\Player::find($secondEmeraldPlayer);
                        $updateData = [
                            'type' => 'pxp',
                            'target_stat' => 'pxp',
                            'target_value' => 500,
                            'constraint_type' => 'player',
                            'constraint_value' => (string)$secondEmeraldPlayer,
                            'description' => $player 
                                ? "Earn 500 PXP with {$player->name} ({$player->overall_rating} OVR {$player->card_tier})"
                                : $challenge->description,
                        ];
                        $updated = true;
                        echo "  ✓ Updated Sapphire challenge: 500 PXP with " . ($player ? $player->name : "ID {$secondEmeraldPlayer}") . "\n";
                    }
                } elseif (str_contains($challenge->description, 'TA Amethyst reward card')) {
                    if ($sapphirePlayer) {
                        $player = \App\Models\Player::find($sapphirePlayer);
                        $updateData = [
                            'type' => 'pxp',
                            'target_stat' => 'pxp',
                            'target_value' => 500,
                            'constraint_type' => 'player',
                            'constraint_value' => (string)$sapphirePlayer,
                            'description' => $player 
                                ? "Earn 500 PXP with {$player->name} ({$player->overall_rating} OVR {$player->card_tier})"
                                : $challenge->description,
                        ];
                        $updated = true;
                        echo "  ✓ Updated Amethyst challenge: 500 PXP with " . ($player ? $player->name : "ID {$sapphirePlayer}") . "\n";
                    }
                } elseif (str_contains($challenge->description, 'TA Pink Diamond reward card')) {
                    if ($pinkDiamondPlayer) {
                        $player = \App\Models\Player::find($pinkDiamondPlayer);
                        $updateData = [
                            'type' => 'pxp',
                            'target_stat' => 'pxp',
                            'target_value' => 500,
                            'constraint_type' => 'player',
                            'constraint_value' => (string)$pinkDiamondPlayer,
                            'description' => $player 
                                ? "Earn 500 PXP with {$player->name} ({$player->overall_rating} OVR {$player->card_tier})"
                                : $challenge->description,
                        ];
                        $updated = true;
                        echo "  ✓ Updated Pink Diamond challenge: 500 PXP with " . ($player ? $player->name : "ID {$pinkDiamondPlayer}") . "\n";
                    }
                }

                if ($updated && !empty($updateData)) {
                    $challenge->update($updateData);
                    $totalUpdated++;
                }
            }
        }

        echo "\n✅ Team Affinity Challenges update complete!\n";
        echo "Total challenges updated: {$totalUpdated}\n";
        echo "Programs processed: " . $programs->count() . "\n";
    }
}

