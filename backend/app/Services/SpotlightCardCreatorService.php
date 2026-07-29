<?php

namespace App\Services;

use App\Models\Player;

class SpotlightCardCreatorService
{
    /**
     * Get card tier based on overall rating.
     *
     * @param int $overallRating
     * @return string
     */
    public function getCardTier(int $overallRating): string
    {
        if ($overallRating >= 99) {
            return 'galaxy_opal';
        } elseif ($overallRating >= 96) {
            return 'pink_diamond';
        } elseif ($overallRating >= 93) {
            return 'diamond';
        } elseif ($overallRating >= 90) {
            return 'amethyst';
        } elseif ($overallRating >= 88) {
            return 'ruby';
        } elseif ($overallRating >= 85) {
            return 'sapphire';
        } elseif ($overallRating >= 80) {
            return 'emerald';
        } elseif ($overallRating >= 75) {
            return 'gold';
        } elseif ($overallRating >= 72) {
            return 'silver';
        } elseif ($overallRating >= 69) {
            return 'bronze';
        } else {
            return 'common';
        }
    }

    /**
     * Calculate OVR from stats and max OVR.
     *
     * @param array $stats
     * @param int $maxOvr
     * @return int
     */
    public function calculateOvrFromStats(array $stats, int $maxOvr): int
    {
        $compositeScore = $stats['composite_score'] ?? 0;
        $ppg = $stats['ppg'] ?? 0;
        
        // Minimum OVR is maxOvr - 3
        $minOvr = max(86, $maxOvr - 3);
        
        // Use composite score and PPG as factors
        // Scale composite score to OVR range (rough approximation)
        $minPpg = 15;
        $maxPpg = 35;
        $ppgRange = $maxPpg - $minPpg;
        $ovrRange = $maxOvr - $minOvr;
        
        if ($ppgRange > 0) {
            $normalizedPpg = max(0, min(1, ($ppg - $minPpg) / $ppgRange));
            $calculatedOvr = $minOvr + round($normalizedPpg * $ovrRange);
        } else {
            $calculatedOvr = $minOvr;
        }
        
        // Factor in composite score (higher composite = higher OVR)
        $compositeFactor = min(1, $compositeScore / 1000); // Normalize composite score
        $calculatedOvr = round($calculatedOvr + ($compositeFactor * 3));
        
        // Ensure OVR is within bounds (minOvr to maxOvr)
        return max($minOvr, min($maxOvr, $calculatedOvr));
    }

    /**
     * Create a Spotlight card for a player.
     *
     * @param array $playerStats
     * @param int $targetOvr
     * @param Player|null $basePlayer
     * @return Player
     */
    public function createSpotlightCard(array $playerStats, int $targetOvr, ?Player $basePlayer = null): Player
    {
        $playerName = $playerStats['player_name'] ?? 'Unknown';
        
        // Generate card art path
        $cardArtName = strtolower(str_replace([' ', "'"], '', $playerName));
        $cardArtPath = "spotlight/{$cardArtName}Spotlight.png";
        
        $playerData = [
            'name' => $playerName,
            'nba_id' => $playerStats['player_id'] ?? null,
            'overall_rating' => $targetOvr,
            'card_tier' => $this->getCardTier($targetOvr),
            'card_art' => $cardArtPath,
            'obtainable_from_packs' => false, // Program-exclusive by default
            'is_tradeable' => true,
        ];

        // Copy attributes from base player if available
        if ($basePlayer) {
            $playerData['team'] = $basePlayer->team ?? $playerStats['team'] ?? 'N/A';
            $playerData['position'] = $basePlayer->position ?? $playerStats['position'] ?? 'N/A';
            
            // Copy category ratings if available
            $categoryRatings = ['outside_scoring', 'inside_scoring', 'defense', 'athleticism', 'playmaking', 'rebounding'];
            foreach ($categoryRatings as $category) {
                if ($basePlayer->$category !== null) {
                    $playerData[$category] = $basePlayer->$category;
                }
            }
            
            // Copy all detailed attributes if available
            $detailedAttributes = [
                'close_shot', 'mid_range_shot', 'three_point_shot', 'free_throw', 'shot_iq', 'offensive_consistency',
                'layup', 'standing_dunk', 'driving_dunk', 'post_hook', 'post_fade', 'post_control', 'draw_foul', 'hands',
                'interior_defense', 'perimeter_defense', 'steal', 'block', 'help_defense_iq', 'pass_perception', 'defensive_consistency',
                'speed', 'agility', 'strength', 'vertical', 'stamina', 'hustle', 'overall_durability',
                'pass_accuracy', 'ball_handle', 'speed_with_ball', 'pass_iq', 'pass_vision',
                'offensive_rebound', 'defensive_rebound',
                'intangibles', 'potential',
            ];
            
            foreach ($detailedAttributes as $attr) {
                if ($basePlayer->$attr !== null) {
                    $playerData[$attr] = $basePlayer->$attr;
                }
            }
        } else {
            // Set basic info from stats
            $playerData['team'] = $playerStats['team'] ?? 'N/A';
            $playerData['position'] = $playerStats['position'] ?? 'N/A';
        }

        return Player::create($playerData);
    }

    /**
     * Create a Topps Now card for a player.
     *
     * @param array $playerStats
     * @param int $targetOvr
     * @param Player|null $basePlayer
     * @return Player
     */
    public function createToppsNowCard(array $playerStats, int $targetOvr, ?Player $basePlayer = null): Player
    {
        $playerName = $playerStats['player_name'] ?? 'Unknown';
        
        // Generate card art path
        $cardArtName = strtolower(str_replace([' ', "'"], '', $playerName));
        $cardArtPath = "topps_now/{$cardArtName}Topps.webp";
        
        $playerData = [
            'name' => $playerName,
            'nba_id' => $playerStats['player_id'] ?? null,
            'overall_rating' => $targetOvr,
            'card_tier' => $this->getCardTier($targetOvr),
            'card_art' => $cardArtPath,
            'obtainable_from_packs' => true, // Topps Now cards are in packs
            'is_tradeable' => true,
        ];

        // Copy attributes from base player if available
        if ($basePlayer) {
            $playerData['team'] = $basePlayer->team ?? $playerStats['team'] ?? 'N/A';
            $playerData['position'] = $basePlayer->position ?? $playerStats['position'] ?? 'N/A';
            
            // Copy category ratings if available
            $categoryRatings = ['outside_scoring', 'inside_scoring', 'defense', 'athleticism', 'playmaking', 'rebounding'];
            foreach ($categoryRatings as $category) {
                if ($basePlayer->$category !== null) {
                    $playerData[$category] = $basePlayer->$category;
                }
            }
            
            // Copy all detailed attributes if available
            $detailedAttributes = [
                'close_shot', 'mid_range_shot', 'three_point_shot', 'free_throw', 'shot_iq', 'offensive_consistency',
                'layup', 'standing_dunk', 'driving_dunk', 'post_hook', 'post_fade', 'post_control', 'draw_foul', 'hands',
                'interior_defense', 'perimeter_defense', 'steal', 'block', 'help_defense_iq', 'pass_perception', 'defensive_consistency',
                'speed', 'agility', 'strength', 'vertical', 'stamina', 'hustle', 'overall_durability',
                'pass_accuracy', 'ball_handle', 'speed_with_ball', 'pass_iq', 'pass_vision',
                'offensive_rebound', 'defensive_rebound',
                'intangibles', 'potential',
            ];
            
            foreach ($detailedAttributes as $attr) {
                if ($basePlayer->$attr !== null) {
                    $playerData[$attr] = $basePlayer->$attr;
                }
            }
        } else {
            // Set basic info from stats
            $playerData['team'] = $playerStats['team'] ?? 'N/A';
            $playerData['position'] = $playerStats['position'] ?? 'N/A';
        }

        return Player::create($playerData);
    }
}
