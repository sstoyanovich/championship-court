<?php

namespace App\Http\Controllers;

use App\Models\Pack;
use App\Models\Player;
use App\Models\UserCard;
use App\Models\UserPack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackController extends Controller
{
    /**
     * Tier hierarchy (from lowest to highest)
     */
    private const TIER_HIERARCHY = [
        'common',
        'bronze',
        'silver',
        'gold',
        'emerald',
        'sapphire',
        'ruby',
        'amethyst',
        'diamond',
        'pink_diamond',
        'galaxy_opal',
    ];

    /**
     * Get all available packs (store view) and user's pack inventory
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Get packs available in the store (both active and available_in_shop)
        $packs = Pack::active()->availableInShop()->get();

        // Get user's unopened packs
        $userPacks = UserPack::with('pack')
            ->where('user_id', $userId)
            ->unopened()
            ->orderBy('obtained_at', 'desc')
            ->get()
            ->groupBy('pack_id')
            ->map(function ($group) {
                return [
                    'pack' => $group->first()->pack,
                    'quantity' => $group->count(),
                    'user_pack_ids' => $group->pluck('id')->toArray(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'store_packs' => $packs,
            'inventory' => $userPacks,
        ]);
    }

    /**
     * Open a specific pack from inventory
     * For standard packs: returns cards immediately
     * For choice packs: returns options for user to choose from
     */
    public function open(Request $request, int $packId): JsonResponse
    {
        $request->validate([
            'user_pack_id' => 'nullable|exists:user_packs,id',
        ]);

        $userId = $request->user()->id;
        $userPackId = $request->user_pack_id;

        // If user_pack_id is provided, open from inventory
        if ($userPackId) {
            $userPack = UserPack::with('pack')
                ->where('id', $userPackId)
                ->where('user_id', $userId)
                ->where('opened', false)
                ->firstOrFail();

            $pack = $userPack->pack;

            if ($pack->isChoicePack()) {
                return $this->generateChoicePackOptions($pack, $userPack);
            }

            $result = $this->openStandardPack($pack, $userId);

            // Mark the pack as opened
            $userPack->markAsOpened();

            return $result;
        }

        // Otherwise, allow direct purchase opening (for store packs)
        $pack = Pack::active()->findOrFail($packId);
        $user = $request->user();

        // Check if user has enough stubs
        if ($user->stubs_balance < $pack->cost) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough stubs to purchase this pack.',
                'required' => $pack->cost,
                'current' => $user->stubs_balance,
                'shortage' => $pack->cost - $user->stubs_balance,
            ], 400);
        }

        // Deduct stubs for the purchase
        DB::beginTransaction();
        try {
            if (!$user->deductStubs($pack->cost)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to deduct stubs.',
                ], 500);
            }

            if ($pack->isChoicePack()) {
                // Create a UserPack record to track this purchase
                $userPack = UserPack::create([
                    'user_id' => $userId,
                    'pack_id' => $pack->id,
                    'obtained_at' => now(),
                    'opened' => false,
                ]);

                DB::commit();
                return $this->generateChoicePackOptions($pack, $userPack);
            }

            // For standard packs, open and return cards
            $result = $this->openStandardPack($pack, $userId);
            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to open pack: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete a choice pack by selecting cards
     */
    public function selectCards(Request $request, int $packId): JsonResponse
    {
        $pack = Pack::active()->findOrFail($packId);

        if (!$pack->isChoicePack()) {
            return response()->json([
                'success' => false,
                'message' => 'This pack is not a choice pack',
            ], 400);
        }

        $request->validate([
            'selected_player_ids' => 'required|array',
            'selected_player_ids.*' => 'required|integer|exists:players,id',
            'user_pack_id' => 'required|exists:user_packs,id',
        ]);

        $selectedPlayerIds = $request->input('selected_player_ids');
        $userId = $request->user()->id;
        $userPackId = $request->user_pack_id;

        // Verify the user_pack belongs to this user and hasn't been opened
        $userPack = UserPack::where('id', $userPackId)
            ->where('user_id', $userId)
            ->where('opened', false)
            ->first();

        if (!$userPack) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or already opened pack',
            ], 400);
        }

        // Validate selection count
        if (count($selectedPlayerIds) !== $pack->choice_count) {
            return response()->json([
                'success' => false,
                'message' => "Please select exactly {$pack->choice_count} card(s)",
            ], 400);
        }

        // Add selected cards to user's collection
        $pulledCards = [];
        foreach ($selectedPlayerIds as $playerId) {
            $player = Player::find($playerId);
            if ($player) {
                $userCard = UserCard::firstOrCreate(
                    ['user_id' => $userId, 'player_id' => $player->id],
                    ['obtained_at' => now(), 'locked' => false, 'count' => 0]
                );
                $userCard->increment('count');
                $userCard->refresh(); // Refresh to get updated count

                $pulledCards[] = [
                    'id' => $userCard->id,
                    'player' => $player,
                    'obtained_at' => $userCard->obtained_at,
                    'count' => $userCard->count,
                ];
            }
        }

        // Mark the pack as opened
        $userPack->markAsOpened();

        return response()->json([
            'success' => true,
            'cards' => $pulledCards,
        ]);
    }

    /**
     * Open a standard pack and return cards
     */
    private function openStandardPack(Pack $pack, int $userId): JsonResponse
    {
        $pulledCards = [];

        for ($i = 0; $i < $pack->card_count; $i++) {
            $slotNumber = $i + 1; // Slots are 1-indexed

            // First check for featured players with special odds
            $featuredPlayer = $this->rollForFeaturedPlayer($pack);

            if ($featuredPlayer) {
                $player = $featuredPlayer;
            } elseif ($pack->hasPlayerPools()) {
                // Use player pools if configured
                $player = $this->getPlayerFromPools($pack);
            } else {
                // Check if this slot has a guaranteed minimum tier
                $minimumTier = $pack->getMinimumTierForSlot($slotNumber);

                if ($minimumTier) {
                    // Get a tier that meets or exceeds the minimum
                    $tier = $this->getTierMeetingMinimum($minimumTier, $pack->getTierOdds());
                } else {
                    // Determine tier based on pack's weighted probabilities
                    $tier = $this->getWeightedRandomTier($pack->getTierOdds());
                }

                // Get a random player of that tier (with optional collection filtering)
                $player = $this->getRandomPlayer($tier, $pack->collection_id);
            }

            if ($player) {
                // Add the card to user's collection
                $userCard = UserCard::firstOrCreate(
                    ['user_id' => $userId, 'player_id' => $player->id],
                    ['obtained_at' => now(), 'locked' => false, 'count' => 0]
                );
                $userCard->increment('count');
                $userCard->refresh(); // Refresh to get updated count

                $pulledCards[] = [
                    'id' => $userCard->id,
                    'player' => $player,
                    'obtained_at' => $userCard->obtained_at,
                    'count' => $userCard->count,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'pack' => $pack,
            'type' => 'standard',
            'cards' => $pulledCards,
        ]);
    }

    /**
     * Generate options for a choice pack
     */
    private function generateChoicePackOptions(Pack $pack, ?UserPack $userPack): JsonResponse
    {
        $options = [];

        // Check if pack has specified players (admin-defined choices)
        if ($pack->hasSpecifiedPlayers()) {
            // Use the specified players directly
            $specifiedPlayerIds = $pack->getSpecifiedPlayerIds();
            $players = Player::whereIn('id', $specifiedPlayerIds)->get();

            // Return players in the order specified by admin
            foreach ($specifiedPlayerIds as $playerId) {
                $player = $players->firstWhere('id', $playerId);
                if ($player) {
                    $options[] = $player;
                }
            }
        } else {
            // Original random generation logic
            for ($i = 0; $i < $pack->card_count; $i++) {
                $slotNumber = $i + 1; // Slots are 1-indexed

                // First check for featured players with special odds
                $featuredPlayer = $this->rollForFeaturedPlayer($pack);

                if ($featuredPlayer) {
                    $options[] = $featuredPlayer;
                } elseif ($pack->hasPlayerPools()) {
                    // Use player pools if configured
                    $player = $this->getPlayerFromPools($pack);
                    if ($player) {
                        $options[] = $player;
                    }
                } else {
                    // Check if this slot has a guaranteed minimum tier
                    $minimumTier = $pack->getMinimumTierForSlot($slotNumber);

                    if ($minimumTier) {
                        // Get a tier that meets or exceeds the minimum
                        $tier = $this->getTierMeetingMinimum($minimumTier, $pack->getTierOdds());
                    } else {
                        // Determine tier based on pack's weighted probabilities
                        $tier = $this->getWeightedRandomTier($pack->getTierOdds());
                    }

                    // Get a random player of that tier (with optional collection filtering)
                    $player = $this->getRandomPlayer($tier, $pack->collection_id);

                    if ($player) {
                        $options[] = $player;
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'pack' => $pack,
            'type' => 'choice',
            'options' => $options,
            'must_select' => $pack->choice_count,
            'user_pack_id' => $userPack ? $userPack->id : null,
        ]);
    }

    /**
     * Get a random tier based on weighted probabilities
     */
    private function getWeightedRandomTier(array $weights): string
    {
        // Convert percentages to weights (multiply by 100 for precision)
        $processedWeights = [];
        foreach ($weights as $tier => $percentage) {
            $processedWeights[$tier] = (int)($percentage * 100);
        }

        $totalWeight = array_sum($processedWeights);
        $random = rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($processedWeights as $tier => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $tier;
            }
        }

        // Fallback to the first tier in the weights
        return array_key_first($weights) ?? 'bronze';
    }

    /**
     * Get a tier that meets or exceeds the minimum tier requirement
     * Uses weighted odds from tiers at or above the minimum
     */
    private function getTierMeetingMinimum(string $minimumTier, array $allOdds): string
    {
        // Find the index of the minimum tier in the hierarchy
        $minTierIndex = array_search($minimumTier, self::TIER_HIERARCHY);

        if ($minTierIndex === false) {
            // If minimum tier not found, fall back to normal weighted random
            return $this->getWeightedRandomTier($allOdds);
        }

        // Filter odds to only include tiers at or above the minimum
        $eligibleOdds = [];
        foreach ($allOdds as $tier => $odds) {
            $tierIndex = array_search($tier, self::TIER_HIERARCHY);
            if ($tierIndex !== false && $tierIndex >= $minTierIndex) {
                $eligibleOdds[$tier] = $odds;
            }
        }

        // If no eligible tiers found, return the minimum tier
        if (empty($eligibleOdds)) {
            return $minimumTier;
        }

        // Get weighted random tier from eligible tiers
        return $this->getWeightedRandomTier($eligibleOdds);
    }

    /**
     * Get a random player of a specific tier, optionally filtered by collection
     */
    private function getRandomPlayer(string $tier, ?int $collectionId): ?Player
    {
        $query = Player::where('card_tier', $tier)
            ->obtainableFromPacks(); // Exclude program-exclusive cards

        // Apply collection filter if specified
        if ($collectionId !== null) {
            $query->where('collection_id', $collectionId);
        }

        return $query->inRandomOrder()->first();
    }

    /**
     * Check if a featured player should be selected based on their odds
     * Returns the featured player if successful, null otherwise
     */
    private function rollForFeaturedPlayer(Pack $pack): ?Player
    {
        if (!$pack->hasFeaturedPlayers()) {
            return null;
        }

        $featuredPlayers = $pack->getFeaturedPlayers();

        // Roll for each featured player
        foreach ($featuredPlayers as $featuredConfig) {
            $playerId = $featuredConfig['player_id'] ?? null;
            $odds = $featuredConfig['odds'] ?? 0;

            if (!$playerId || $odds <= 0) {
                continue;
            }

            // Roll random number between 0 and 100
            $roll = mt_rand(0, 10000) / 100; // Gives precision to 2 decimal places

            // Check if the roll hits the odds
            if ($roll <= $odds) {
                // Featured player hit! Return the player
                $player = Player::find($playerId);
                if ($player) {
                    return $player;
                }
            }
        }

        return null;
    }

    /**
     * Select a player from configured player pools based on odds
     * Returns a player from the selected pool, null if no pools or invalid config
     */
    private function getPlayerFromPools(Pack $pack): ?Player
    {
        $pools = $pack->getPlayerPools();

        if (empty($pools)) {
            return null;
        }

        // Build odds array for weighted selection
        $poolOdds = [];
        foreach ($pools as $index => $pool) {
            $odds = $pool['odds'] ?? 0;
            if ($odds > 0) {
                $poolOdds[$index] = $odds;
            }
        }

        if (empty($poolOdds)) {
            return null;
        }

        // Select a pool based on weighted odds
        $selectedPoolIndex = $this->getWeightedRandomKey($poolOdds);
        $selectedPool = $pools[$selectedPoolIndex] ?? null;

        if (!$selectedPool || empty($selectedPool['player_ids'])) {
            return null;
        }

        // Get a random player from the selected pool
        $playerIds = $selectedPool['player_ids'];
        $randomPlayerId = $playerIds[array_rand($playerIds)];

        return Player::find($randomPlayerId);
    }

    /**
     * Helper method to get a random key from an associative array based on weighted values
     */
    private function getWeightedRandomKey(array $weights)
    {
        $totalWeight = array_sum($weights);
        $random = mt_rand(1, $totalWeight);

        foreach ($weights as $key => $weight) {
            $random -= $weight;
            if ($random <= 0) {
                return $key;
            }
        }

        // Fallback to first key
        return array_key_first($weights);
    }
}
