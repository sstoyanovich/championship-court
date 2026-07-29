<?php

namespace App\Services;

use App\Models\Player;

class ChallengeGeneratorService
{
    /**
     * Generate challenges for a Spotlight program.
     *
     * @param array $programPlayers Array of Player models for program rewards
     * @param array $packPlayers Array of Player models for pack
     * @return array
     */
    public function generateChallenges(array $programPlayers, array $packPlayers): array
    {
        $challenges = [];

        // Player-specific PXP challenges for program reward players
        foreach ($programPlayers as $player) {
            $cardType = $this->getCardTypeLabel($player->card_art);
            $challenges[] = [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 250,
                'constraint_type' => 'player',
                'constraint_value' => (string)$player->id,
                'stars_reward' => 5,
                'description' => "Tally 250 PXP with {$player->overall_rating} {$cardType} {$player->name}",
            ];
        }

        // Team-based stat challenges for pack players
        $teamChallenges = [];
        $challengeCount = 0;
        $maxTeamChallenges = min(3, count($packPlayers)); // Limit to 3 team challenges
        
        foreach ($packPlayers as $player) {
            if ($challengeCount >= $maxTeamChallenges) {
                break;
            }
            
            $team = $player->team ?? 'N/A';
            
            // Skip if we already have a challenge for this team or team is N/A
            if (isset($teamChallenges[$team]) || $team === 'N/A') {
                continue;
            }

            // Generate challenge based on player's OVR (estimate PPG from OVR)
            $ovr = $player->overall_rating ?? 86;
            // Rough estimate: OVR 86 = ~15 PPG, OVR 99 = ~35 PPG
            $estimatedPpg = 15 + (($ovr - 86) / 13) * 20;
            $targetPoints = max(20, min(60, round($estimatedPpg * 0.8))); // 80% of estimated PPG, capped between 20-60
            
            $challenges[] = [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => $targetPoints,
                'constraint_type' => 'team',
                'constraint_value' => $team,
                'stars_reward' => 5,
                'description' => "Score {$targetPoints} points with any {$team} player",
            ];
            
            $teamChallenges[$team] = true;
            $challengeCount++;
        }

        // Generic challenges
        $challenges[] = [
            'type' => 'game_count',
            'target_stat' => null,
            'target_value' => 1,
            'constraint_type' => null,
            'constraint_value' => null,
            'stars_reward' => 10,
            'description' => 'Play a game',
        ];

        $challenges[] = [
            'type' => 'stat',
            'target_stat' => 'points',
            'target_value' => 100,
            'constraint_type' => null,
            'constraint_value' => null,
            'stars_reward' => 5,
            'description' => 'Score 100 points with any player',
        ];

        // Category challenges
        $challenges[] = [
            'type' => 'pxp',
            'target_stat' => null,
            'target_value' => 500,
            'constraint_type' => null,
            'constraint_value' => 'spotlight',
            'stars_reward' => 10,
            'description' => 'Tally 500 PXP with any Spotlight player',
        ];

        $challenges[] = [
            'type' => 'pxp',
            'target_stat' => null,
            'target_value' => 500,
            'constraint_type' => null,
            'constraint_value' => 'topps_now',
            'stars_reward' => 10,
            'description' => 'Tally 500 PXP with any Topps Now player',
        ];

        return $challenges;
    }

    /**
     * Get card type label from card_art.
     *
     * @param string|null $cardArt
     * @return string
     */
    private function getCardTypeLabel(?string $cardArt): string
    {
        if (str_contains($cardArt ?? '', 'spotlight')) {
            return 'Spotlight';
        } elseif (str_contains($cardArt ?? '', 'topps_now')) {
            return 'Topps Now';
        }
        return 'Card';
    }

}
