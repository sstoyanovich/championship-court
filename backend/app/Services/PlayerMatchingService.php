<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PlayerMatchingService
{
    /**
     * Find existing player by name using fuzzy matching.
     *
     * @param string $name
     * @param string|null $cardArtType
     * @return Player|null
     */
    public function findExistingPlayer(string $name, ?string $cardArtType = null): ?Player
    {
        // Normalize name for comparison
        $normalizedName = trim($name);
        
        // Try exact match first (case-insensitive)
        $exactMatch = Player::whereRaw('LOWER(name) = ?', [strtolower($normalizedName)])->first();
        if ($exactMatch) {
            return $exactMatch;
        }

        // Try fuzzy matching with a larger threshold
        $allPlayers = Player::select('name')->distinct()->get();
        $bestMatch = null;
        $bestScore = PHP_INT_MAX;
        $threshold = 5; // Increased threshold for better matching

        foreach ($allPlayers as $player) {
            $distance = levenshtein(
                strtolower($normalizedName),
                strtolower($player->name)
            );

            if ($distance < $bestScore && $distance <= $threshold) {
                $bestScore = $distance;
                // Get the full player record
                $bestMatch = Player::where('name', $player->name)->first();
            }
        }

        return $bestMatch;
    }

    /**
     * Get all existing Spotlight/Topps Now cards for a player.
     *
     * @param string $playerName
     * @param string|null $cardArtType
     * @return Collection
     */
    public function getExistingCards(string $playerName, ?string $cardArtType = null): Collection
    {
        $normalizedName = trim($playerName);
        
        // First try exact case-insensitive match
        $matchingNames = Player::select('name')
            ->whereRaw('LOWER(name) = ?', [strtolower($normalizedName)])
            ->distinct()
            ->pluck('name')
            ->toArray();
        
        // If no exact match, try fuzzy matching to find similar names
        if (empty($matchingNames)) {
            $allNames = Player::select('name')->distinct()->pluck('name')->toArray();
            $threshold = 5;
            
            foreach ($allNames as $name) {
                $distance = levenshtein(
                    strtolower($normalizedName),
                    strtolower($name)
                );
                
                if ($distance <= $threshold) {
                    $matchingNames[] = $name;
                }
            }
        }
        
        if (empty($matchingNames)) {
            return collect([]);
        }

        // Get player IDs with matching names
        $playerIds = Player::whereIn('name', $matchingNames)->pluck('id')->toArray();
        
        if (empty($playerIds)) {
            return collect([]);
        }

        // Find cards that are in Spotlight program packs
        $spotlightPackPlayerIds = \App\Models\Pack::where('name', 'LIKE', '%Spotlight%')
            ->orWhere('name', 'LIKE', '%spotlight%')
            ->whereNotNull('specified_players')
            ->get()
            ->pluck('specified_players')
            ->flatten()
            ->unique()
            ->toArray();

        // Find cards that are Spotlight program rewards
        $spotlightRewardPlayerIds = \App\Models\ProgramReward::where('reward_type', 'player')
            ->whereHas('program', function ($q) {
                $q->where('name', 'LIKE', '%Spotlight%')
                  ->orWhere('name', 'LIKE', '%spotlight%');
            })
            ->pluck('reward_id')
            ->unique()
            ->toArray();

        // Combine all Spotlight-related player IDs
        $spotlightPlayerIds = array_unique(array_merge($spotlightPackPlayerIds, $spotlightRewardPlayerIds));

        // Query all players with matching names that are either:
        // 1. Have card_art set (Spotlight/Topps Now paths), OR
        // 2. Are in Spotlight program packs/rewards
        $query = Player::whereIn('name', $matchingNames)
            ->where(function ($q) use ($cardArtType, $playerIds, $spotlightPlayerIds) {
                // Cards with card_art set
                $q->where(function ($subQ) use ($cardArtType) {
                    $subQ->whereNotNull('card_art');
                    if ($cardArtType) {
                        // Filter by specific card art type
                        if ($cardArtType === 'spotlight') {
                            $subQ->where('card_art', 'LIKE', '%spotlight%');
                        } elseif ($cardArtType === 'topps_now') {
                            $subQ->where(function ($toppsQ) {
                                $toppsQ->where('card_art', 'LIKE', '%topps_now%')
                                       ->orWhere('card_art', 'LIKE', '%topps%');
                            });
                        }
                    } else {
                        // Get all Spotlight and Topps Now cards
                        $subQ->where(function ($artQ) {
                            $artQ->where('card_art', 'LIKE', '%spotlight%')
                                 ->orWhere('card_art', 'LIKE', '%topps_now%')
                                 ->orWhere('card_art', 'LIKE', '%topps%');
                        });
                    }
                })
                // OR cards that are in Spotlight programs
                ->orWhereIn('id', array_intersect($playerIds, $spotlightPlayerIds));
            });

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Check if player has a card within OVR threshold.
     *
     * @param string $playerName
     * @param int $targetOvr
     * @param string|null $cardArtType
     * @param int $ovrThreshold
     * @return bool
     */
    public function hasSimilarOvrCard(string $playerName, int $targetOvr, ?string $cardArtType = null, int $ovrThreshold = 3): bool
    {
        $existingCards = $this->getExistingCards($playerName, $cardArtType);

        foreach ($existingCards as $card) {
            if (!$card->overall_rating) {
                continue;
            }
            $ovrDiff = abs($card->overall_rating - $targetOvr);
            if ($ovrDiff <= $ovrThreshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get yearly card count for a player.
     *
     * @param string $playerName
     * @param int $year
     * @param string|null $cardArtType
     * @return int
     */
    public function getYearlyCardCount(string $playerName, int $year, ?string $cardArtType = null): int
    {
        $normalizedName = trim($playerName);
        
        // First try exact case-insensitive match
        $matchingNames = Player::select('name')
            ->whereRaw('LOWER(name) = ?', [strtolower($normalizedName)])
            ->distinct()
            ->pluck('name')
            ->toArray();
        
        // If no exact match, try fuzzy matching
        if (empty($matchingNames)) {
            $allNames = Player::select('name')->distinct()->pluck('name')->toArray();
            $threshold = 5;
            
            foreach ($allNames as $name) {
                $distance = levenshtein(
                    strtolower($normalizedName),
                    strtolower($name)
                );
                
                if ($distance <= $threshold) {
                    $matchingNames[] = $name;
                }
            }
        }
        
        if (empty($matchingNames)) {
            return 0;
        }

        // Query all players with matching names
        // card_art stores paths, so use LIKE queries
        $query = Player::whereIn('name', $matchingNames)
            ->whereYear('created_at', $year)
            ->whereNotNull('card_art')
            ->where(function ($q) use ($cardArtType) {
                if ($cardArtType) {
                    // Filter by specific card art type
                    if ($cardArtType === 'spotlight') {
                        $q->where('card_art', 'LIKE', '%spotlight%');
                    } elseif ($cardArtType === 'topps_now') {
                        $q->where(function ($subQ) {
                            $subQ->where('card_art', 'LIKE', '%topps_now%')
                                 ->orWhere('card_art', 'LIKE', '%topps%');
                        });
                    }
                } else {
                    // Get all Spotlight and Topps Now cards
                    $q->where(function ($subQ) {
                        $subQ->where('card_art', 'LIKE', '%spotlight%')
                             ->orWhere('card_art', 'LIKE', '%topps_now%')
                             ->orWhere('card_art', 'LIKE', '%topps%');
                    });
                }
            });

        return $query->count();
    }

    /**
     * Check if player has reached yearly card limit.
     *
     * @param string $playerName
     * @param int $year
     * @param int $maxCardsPerYear
     * @return bool
     */
    public function hasReachedYearlyLimit(string $playerName, int $year, int $maxCardsPerYear = 7): bool
    {
        $count = $this->getYearlyCardCount($playerName, $year);
        return $count >= $maxCardsPerYear;
    }

    /**
     * Get total card count for a player (all cards, not just Spotlight/Topps Now).
     *
     * @param string $playerName
     * @return int
     */
    public function getTotalCardCount(string $playerName): int
    {
        $normalizedName = trim($playerName);
        
        // First try exact case-insensitive match
        $matchingNames = Player::select('name')
            ->whereRaw('LOWER(name) = ?', [strtolower($normalizedName)])
            ->distinct()
            ->pluck('name')
            ->toArray();
        
        // If no exact match, try fuzzy matching
        if (empty($matchingNames)) {
            $allNames = Player::select('name')->distinct()->pluck('name')->toArray();
            $threshold = 5;
            
            foreach ($allNames as $name) {
                $distance = levenshtein(
                    strtolower($normalizedName),
                    strtolower($name)
                );
                
                if ($distance <= $threshold) {
                    $matchingNames[] = $name;
                }
            }
        }
        
        if (empty($matchingNames)) {
            return 0;
        }

        // Count all cards for matching names
        return Player::whereIn('name', $matchingNames)->count();
    }

    /**
     * Get formatted candidate information for display.
     *
     * @param array $playerStats
     * @param int $maxOvr
     * @return array
     */
    public function getCandidateInfo(array $playerStats, int $maxOvr): array
    {
        $playerName = $playerStats['player_name'] ?? 'Unknown';
        $year = now()->year;
        
        $existingCards = $this->getExistingCards($playerName); // Spotlight/Topps Now cards for summary
        $totalCount = $this->getTotalCardCount($playerName); // Total count of ALL cards
        $yearlyCount = $this->getYearlyCardCount($playerName, $year); // Yearly count for limit checking
        
        // Calculate recommended OVR based on stats and max OVR
        $recommendedOvr = $this->calculateRecommendedOvr($playerStats, $maxOvr);
        
        // Check for warnings
        $warnings = [];
        if ($this->hasReachedYearlyLimit($playerName, $year)) {
            $warnings[] = 'yearly_limit';
        }
        if ($this->hasSimilarOvrCard($playerName, $recommendedOvr)) {
            $warnings[] = 'similar_ovr';
        }

        // Format existing cards summary
        // Show up to 5 cards, then indicate more
        $displayLimit = 5;
        $existingCardsSummary = $existingCards->take($displayLimit)->map(function ($card) {
            $month = $card->created_at ? $card->created_at->format('M') : 'Unknown';
            $ovr = $card->overall_rating ?? '?';
            
            // Extract card type from card_art path
            $cardType = 'Card';
            if (str_contains(strtolower($card->card_art ?? ''), 'spotlight')) {
                $cardType = 'Spotlight';
            } elseif (str_contains(strtolower($card->card_art ?? ''), 'topps')) {
                $cardType = 'Topps Now';
            }
            
            return "{$ovr} {$cardType} ({$month})";
        })->implode(', ');

        if ($existingCards->count() > $displayLimit) {
            $remaining = $existingCards->count() - $displayLimit;
            $existingCardsSummary .= " (+{$remaining} more)";
        }

        if (empty($existingCardsSummary)) {
            $existingCardsSummary = 'None';
        }

        return [
            'player_name' => $playerName,
            'player_id' => $playerStats['player_id'] ?? null,
            'team' => $playerStats['team'] ?? 'N/A',
            'position' => $playerStats['position'] ?? 'N/A',
            'stats' => [
                'ppg' => $playerStats['ppg'] ?? 0,
                'rpg' => $playerStats['rpg'] ?? 0,
                'apg' => $playerStats['apg'] ?? 0,
                'composite_score' => $playerStats['composite_score'] ?? 0,
            ],
            'existing_cards' => $existingCards->toArray(),
            'existing_cards_summary' => $existingCardsSummary,
            'total_card_count' => $totalCount, // Total count of all cards
            'yearly_card_count' => $yearlyCount, // Yearly count for limit checking
            'yearly_card_limit' => 7,
            'recommended_ovr' => $recommendedOvr,
            'warnings' => $warnings,
        ];
    }

    /**
     * Calculate recommended OVR based on player stats and max OVR.
     *
     * @param array $playerStats
     * @param int $maxOvr
     * @return int
     */
    private function calculateRecommendedOvr(array $playerStats, int $maxOvr): int
    {
        $compositeScore = $playerStats['composite_score'] ?? 0;
        $ppg = $playerStats['ppg'] ?? 0;
        
        // Minimum OVR is maxOvr - 3
        $minOvr = max(86, $maxOvr - 3);
        
        // Base OVR calculation: use composite score and PPG as factors
        // Scale composite score to OVR range (rough approximation)
        // Higher composite score = higher OVR, but cap at maxOvr
        
        // Simple calculation: use PPG as primary factor
        // Scale PPG (typically 15-35) to OVR range (minOvr-maxOvr)
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
        
        // Factor in composite score
        $compositeFactor = min(1, $compositeScore / 1000);
        $calculatedOvr = round($calculatedOvr + ($compositeFactor * 3));
        
        // Ensure OVR is within bounds (minOvr to maxOvr)
        return max($minOvr, min($maxOvr, $calculatedOvr));
    }
}
