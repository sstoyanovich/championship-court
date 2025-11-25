<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionReward;
use App\Models\UserCard;
use App\Models\Player;
use App\Models\User;
use App\Models\UserCollectionReward;
use App\Models\UserPack;
use App\Models\UserPlayerStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    /**
     * Get all cards in user's collection
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $query = UserCard::with('player.collection')
            ->where('user_id', $userId)
            ->orderBy('obtained_at', 'desc');

        // Apply filters if provided
        if ($request->has('tier') && $request->tier) {
            $query->whereHas('player', function ($q) use ($request) {
                $q->where('card_tier', $request->tier);
            });
        }

        if ($request->has('team') && $request->team) {
            $query->whereHas('player', function ($q) use ($request) {
                $q->where('team', $request->team);
            });
        }

        if ($request->has('position') && $request->position) {
            $query->whereHas('player', function ($q) use ($request) {
                $q->where('position', $request->position);
            });
        }

        $cards = $query->get()->map(function ($card) use ($userId) {
            // Get stats from user_player_stats
            $stats = UserPlayerStats::where('user_id', $userId)
                ->where('player_id', $card->player_id)
                ->first();

            $cardData = $card->toArray();

            if ($stats) {
                $cardData['player_xp'] = $stats->player_xp;
                $cardData['games_played'] = $stats->games_played;
                $cardData['stats'] = [
                    'minutes' => $stats->total_minutes,
                    'points' => $stats->total_points,
                    'rebounds' => $stats->total_rebounds,
                    'assists' => $stats->total_assists,
                    'steals' => $stats->total_steals,
                    'blocks' => $stats->total_blocks,
                    'turnovers' => $stats->total_turnovers,
                    'fgm' => $stats->total_fgm,
                    'fga' => $stats->total_fga,
                    '3pm' => $stats->total_3pm,
                    '3pa' => $stats->total_3pa,
                ];
                $cardData['averages'] = [
                    'ppg' => $stats->getPPG(),
                    'rpg' => $stats->getRPG(),
                    'apg' => $stats->getAPG(),
                ];
                $cardData['percentages'] = [
                    'fg_percentage' => $stats->getFGPercentage(),
                    '3p_percentage' => $stats->get3PPercentage(),
                ];
            } else {
                // No stats yet
                $cardData['player_xp'] = 0;
                $cardData['games_played'] = 0;
                $cardData['stats'] = null;
                $cardData['averages'] = null;
                $cardData['percentages'] = null;
            }

            return $cardData;
        });

        return response()->json([
            'success' => true,
            'cards' => $cards,
        ]);
    }

    /**
     * Get collection statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $totalCards = UserCard::where('user_id', $userId)->count();

        $cardsByTier = UserCard::join('players', 'user_cards.player_id', '=', 'players.id')
            ->where('user_cards.user_id', $userId)
            ->selectRaw('players.card_tier, count(*) as count')
            ->groupBy('players.card_tier')
            ->get()
            ->pluck('count', 'card_tier');

        $cardsByTeam = UserCard::join('players', 'user_cards.player_id', '=', 'players.id')
            ->where('user_cards.user_id', $userId)
            ->selectRaw('players.team, count(*) as count')
            ->groupBy('players.team')
            ->get()
            ->pluck('count', 'team');

        $cardsByPosition = UserCard::join('players', 'user_cards.player_id', '=', 'players.id')
            ->where('user_cards.user_id', $userId)
            ->selectRaw('players.position, count(*) as count')
            ->groupBy('players.position')
            ->get()
            ->pluck('count', 'position');

        return response()->json([
            'success' => true,
            'stats' => [
                'total_cards' => $totalCards,
                'by_tier' => $cardsByTier,
                'by_team' => $cardsByTeam,
                'by_position' => $cardsByPosition,
            ],
        ]);
    }

    /**
     * Get all collections with user's progress
     */
    public function getCollections(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Get distinct collection names and their properties
        $distinctCollections = Collection::where('active', true)
            ->get()
            ->unique('name')
            ->values();

        $collections = $distinctCollections->map(function ($collectionGroup) use ($userId) {
            // Get total items for this collection type
            $totalItems = Player::whereHas('collection', function ($q) use ($collectionGroup) {
                $q->where('name', $collectionGroup->name);
            })->count();

            // Get locked items for this collection type
            $lockedItems = UserCard::whereHas('player.collection', function ($q) use ($collectionGroup) {
                $q->where('name', $collectionGroup->name);
            })
                ->where('user_id', $userId)
                ->where('locked', true)
                ->count();

            // Get owned items for this collection type
            $ownedItems = UserCard::whereHas('player.collection', function ($q) use ($collectionGroup) {
                $q->where('name', $collectionGroup->name);
            })
                ->where('user_id', $userId)
                ->count();

            // Check if this collection has sub-collections (like teams)
            $hasSubCollections = Collection::where('name', $collectionGroup->name)
                ->whereNotNull('sub_collection')
                ->exists();

            return [
                'name' => $collectionGroup->name,
                'description' => $collectionGroup->description,
                'type' => $collectionGroup->type,
                'has_sub_collections' => $hasSubCollections,
                'progress' => [
                    'total' => $totalItems,
                    'locked' => $lockedItems,
                    'owned' => $ownedItems,
                    'percentage' => $totalItems > 0 ? round(($lockedItems / $totalItems) * 100, 1) : 0,
                    'completed' => $totalItems > 0 && $lockedItems === $totalItems,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'collections' => $collections,
        ]);
    }

    /**
     * Lock a card into its collection
     */
    public function lockCard(Request $request, $cardId): JsonResponse
    {
        $userCard = UserCard::with('player.collection')->findOrFail($cardId);

        if ($userCard->locked) {
            return response()->json([
                'success' => false,
                'message' => 'Card is already locked',
            ], 400);
        }

        $userCard->update([
            'locked' => true,
            'locked_at' => now(),
        ]);

        $earnedRewards = [];

        // Check if collection rewards should be granted
        $collection = $userCard->player->collection;
        if ($collection) {
            $totalItems = Player::where('collection_id', $collection->id)->count();
            $lockedItems = UserCard::whereHas('player', function ($q) use ($collection) {
                $q->where('collection_id', $collection->id);
            })
                ->where('user_id', $userCard->user_id)
                ->where('locked', true)
                ->count();

            $completed = $totalItems > 0 && $lockedItems === $totalItems;

            // Check for rewards that should be automatically granted
            $rewards = CollectionReward::where('collection_id', $collection->id)
                ->where('required_cards', '<=', $lockedItems)
                ->orderBy('required_cards', 'asc')
                ->with(['player', 'pack'])
                ->get();

            foreach ($rewards as $reward) {
                // Check if user has already claimed this reward
                $alreadyClaimed = UserCollectionReward::where('user_id', $userCard->user_id)
                    ->where('collection_reward_id', $reward->id)
                    ->exists();

                if (!$alreadyClaimed) {
                    // Grant the reward
                    $this->grantReward($userCard->user, $reward);

                    // Record the claim
                    UserCollectionReward::create([
                        'user_id' => $userCard->user_id,
                        'collection_reward_id' => $reward->id,
                        'claimed_at' => now(),
                    ]);

                    $earnedRewards[] = $reward;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Card locked successfully',
                'card' => $userCard,
                'collection_completed' => $completed,
                'collection' => $completed ? $collection->load('rewards') : null,
                'earned_rewards' => $earnedRewards,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Card locked successfully',
            'card' => $userCard,
            'earned_rewards' => [],
        ]);
    }

    /**
     * Grant a reward to a user
     */
    private function grantReward(User $user, CollectionReward $reward): void
    {
        switch ($reward->reward_type) {
            case 'stubs':
                $user->addStubs($reward->reward_quantity);
                break;

            case 'player_card':
                if ($reward->player_id) {
                    // Create a new user card for the rewarded player
                    $userCard = UserCard::firstOrCreate(
                        ['user_id' => $user->id, 'player_id' => $reward->player_id],
                        ['obtained_at' => now(), 'locked' => false, 'count' => 0]
                    );
                    $userCard->increment('count');
                }
                break;

            case 'pack':
                if ($reward->pack_id) {
                    // Add pack(s) to user's inventory
                    for ($i = 0; $i < $reward->reward_quantity; $i++) {
                        UserPack::create([
                            'user_id' => $user->id,
                            'pack_id' => $reward->pack_id,
                            'opened' => false,
                        ]);
                    }
                }
                break;
        }
    }

    /**
     * Unlock a card from its collection
     */
    public function unlockCard(Request $request, $cardId): JsonResponse
    {
        $userCard = UserCard::findOrFail($cardId);

        if (!$userCard->locked) {
            return response()->json([
                'success' => false,
                'message' => 'Card is not locked',
            ], 400);
        }

        $userCard->update([
            'locked' => false,
            'locked_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Card unlocked successfully',
            'card' => $userCard,
        ]);
    }

    /**
     * Get all teams/sub-collections for a specific collection with progress
     */
    public function getCollectionTeams(Request $request, string $collectionName): JsonResponse
    {
        $userId = $request->user()->id;

        // Get all collections matching the name
        $collections = Collection::where('name', $collectionName)
            ->where('active', true)
            ->get();

        if ($collections->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found',
            ], 404);
        }

        // Get teams with progress stats
        $teams = $collections->map(function ($collection) use ($userId) {
            $totalCards = Player::where('collection_id', $collection->id)->count();

            // Get owned cards count
            $ownedCards = UserCard::whereHas('player', function ($q) use ($collection) {
                $q->where('collection_id', $collection->id);
            })
                ->where('user_id', $userId)
                ->count();

            // Get locked cards count
            $lockedCards = UserCard::whereHas('player', function ($q) use ($collection) {
                $q->where('collection_id', $collection->id);
            })
                ->where('user_id', $userId)
                ->where('locked', true)
                ->count();

            return [
                'collection_id' => $collection->id,
                'name' => $collection->sub_collection ?? $collection->name,
                'description' => $collection->description,
                'total_cards' => $totalCards,
                'owned_cards' => $ownedCards,
                'locked_cards' => $lockedCards,
                'percentage' => $totalCards > 0 ? round(($lockedCards / $totalCards) * 100, 1) : 0,
                'completed' => $totalCards > 0 && $lockedCards === $totalCards,
            ];
        });

        return response()->json([
            'success' => true,
            'collection_name' => $collectionName,
            'teams' => $teams,
        ]);
    }

    /**
     * Get all cards directly for a collection (no sub-collections)
     */
    public function getCollectionCards(Request $request, string $collectionName): JsonResponse
    {
        $userId = $request->user()->id;

        // Get all collection IDs with this name
        $collectionIds = Collection::where('name', $collectionName)
            ->where('active', true)
            ->pluck('id');

        if ($collectionIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found',
            ], 404);
        }

        // Get all players for this collection
        $players = Player::whereIn('collection_id', $collectionIds)
            ->orderBy('overall_rating', 'desc')
            ->get()
            ->map(function ($player) use ($userId) {
                return $this->formatPlayerCardData($player, $userId);
            });

        // Calculate progress stats
        $totalCards = $players->count();
        $ownedCards = $players->where('owned', true)->count();
        $lockedCards = $players->where('locked', true)->count();

        // Get collection info
        $collection = Collection::where('name', $collectionName)->first();

        // Get rewards for this collection with claimed status
        $rewards = CollectionReward::whereIn('collection_id', $collectionIds)
            ->with(['player', 'pack'])
            ->orderBy('required_cards', 'asc')
            ->get()
            ->map(function ($reward) use ($userId) {
                $claimed = UserCollectionReward::where('user_id', $userId)
                    ->where('collection_reward_id', $reward->id)
                    ->exists();
                
                $rewardData = $reward->toArray();
                $rewardData['claimed'] = $claimed;
                
                // Ensure player and pack data are included
                if ($reward->player) {
                    $rewardData['player'] = $reward->player->toArray();
                }
                if ($reward->pack) {
                    $rewardData['pack'] = $reward->pack->toArray();
                }
                
                return $rewardData;
            });

        return response()->json([
            'success' => true,
            'cards' => $players->values(),
            'progress' => [
                'total_cards' => $totalCards,
                'owned_cards' => $ownedCards,
                'locked_cards' => $lockedCards,
                'percentage' => $totalCards > 0 ? round(($lockedCards / $totalCards) * 100, 1) : 0,
                'completed' => $totalCards > 0 && $lockedCards === $totalCards,
            ],
            'collection_info' => [
                'name' => $collectionName,
                'description' => $collection ? $collection->description : '',
            ],
            'rewards' => $rewards,
        ]);
    }

    /**
     * Get all cards for a specific team in a collection
     */
    public function getTeamCards(Request $request, string $collectionName, string $team): JsonResponse
    {
        $userId = $request->user()->id;

        // Find the collection with rewards
        $collection = Collection::where('name', $collectionName)
            ->where('sub_collection', $team)
            ->where('active', true)
            ->with(['rewards.player', 'rewards.pack'])
            ->first();

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Collection not found',
            ], 404);
        }

        // For meta-collections (division, conference, NBA), get required reward cards
        if (in_array($collection->type, ['division', 'conference', 'nba'])) {
            $players = $this->getRequiredRewardCards($collection, $userId);
        } else {
            // For regular team collections, get players by collection_id
            $players = Player::where('collection_id', $collection->id)
                ->orderBy('overall_rating', 'desc')
                ->get()
                ->map(function ($player) use ($userId) {
                    return $this->formatPlayerCardData($player, $userId);
                });
        }

        // Calculate progress stats
        $totalCards = $players->count();
        $ownedCards = $players->where('owned', true)->count();
        $lockedCards = $players->where('locked', true)->count();

        // Get rewards with claimed status, sorted by required_cards
        $rewards = $collection->rewards
            ->sortBy('required_cards')
            ->map(function ($reward) use ($userId) {
                $claimed = UserCollectionReward::where('user_id', $userId)
                    ->where('collection_reward_id', $reward->id)
                    ->exists();
                
                $rewardData = $reward->toArray();
                $rewardData['claimed'] = $claimed;
                
                // Ensure player and pack data are included
                if ($reward->player) {
                    $rewardData['player'] = $reward->player->toArray();
                }
                if ($reward->pack) {
                    $rewardData['pack'] = $reward->pack->toArray();
                }
                
                return $rewardData;
            })
            ->values(); // Re-index array

        return response()->json([
            'success' => true,
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'team' => $collection->sub_collection,
                'description' => $collection->description,
            ],
            'cards' => $players,
            'progress' => [
                'total_cards' => $totalCards,
                'owned_cards' => $ownedCards,
                'locked_cards' => $lockedCards,
                'percentage' => $totalCards > 0 ? round(($lockedCards / $totalCards) * 100, 1) : 0,
                'completed' => $totalCards > 0 && $lockedCards === $totalCards,
            ],
            'rewards' => $rewards,
        ]);
    }

    /**
     * Get required reward cards for meta-collections (division, conference, NBA)
     */
    private function getRequiredRewardCards(Collection $collection, int $userId)
    {
        $playerIds = [];

        if ($collection->type === 'division') {
            // For divisions, get team reward cards
            // Parse division name (e.g., "Atlantic Division" -> "Atlantic")
            $divisionName = str_replace(' Division', '', $collection->sub_collection);
            
            // Map divisions to teams
            $divisionTeams = [
                'Atlantic' => ['Boston Celtics', 'Brooklyn Nets', 'New York Knicks', 'Philadelphia 76ers', 'Toronto Raptors'],
                'Central' => ['Chicago Bulls', 'Cleveland Cavaliers', 'Detroit Pistons', 'Indiana Pacers', 'Milwaukee Bucks'],
                'Southeast' => ['Atlanta Hawks', 'Charlotte Hornets', 'Miami Heat', 'Orlando Magic', 'Washington Wizards'],
                'Northwest' => ['Denver Nuggets', 'Minnesota Timberwolves', 'Oklahoma City Thunder', 'Portland Trail Blazers', 'Utah Jazz'],
                'Pacific' => ['Golden State Warriors', 'Los Angeles Clippers', 'Los Angeles Lakers', 'Phoenix Suns', 'Sacramento Kings'],
                'Southwest' => ['Dallas Mavericks', 'Houston Rockets', 'Memphis Grizzlies', 'New Orleans Pelicans', 'San Antonio Spurs'],
            ];

            $teams = $divisionTeams[$divisionName] ?? [];
            
            // Get team collections and their reward players
            foreach ($teams as $team) {
                $teamCollection = Collection::where('name', 'Live Series')
                    ->where('type', 'team')
                    ->where('sub_collection', $team)
                    ->first();
                
                if ($teamCollection) {
                    $reward = CollectionReward::where('collection_id', $teamCollection->id)
                        ->where('reward_type', 'player_card')
                        ->first();
                    
                    if ($reward && $reward->player_id) {
                        $playerIds[] = $reward->player_id;
                    }
                }
            }
        } elseif ($collection->type === 'conference') {
            // For conferences, get division reward cards
            $conferenceName = str_replace(' Conference', '', $collection->sub_collection);
            
            $conferenceDivisions = [
                'Eastern' => ['Atlantic Division', 'Central Division', 'Southeast Division'],
                'Western' => ['Northwest Division', 'Pacific Division', 'Southwest Division'],
            ];

            $divisions = $conferenceDivisions[$conferenceName] ?? [];
            
            foreach ($divisions as $division) {
                $divisionCollection = Collection::where('name', 'Live Series')
                    ->where('type', 'division')
                    ->where('sub_collection', $division)
                    ->first();
                
                if ($divisionCollection) {
                    $reward = CollectionReward::where('collection_id', $divisionCollection->id)
                        ->where('reward_type', 'player_card')
                        ->first();
                    
                    if ($reward && $reward->player_id) {
                        $playerIds[] = $reward->player_id;
                    }
                }
            }
        } elseif ($collection->type === 'nba') {
            // For NBA, get conference reward cards
            $conferences = ['Eastern Conference', 'Western Conference'];
            
            foreach ($conferences as $conference) {
                $conferenceCollection = Collection::where('name', 'Live Series')
                    ->where('type', 'conference')
                    ->where('sub_collection', $conference)
                    ->first();
                
                if ($conferenceCollection) {
                    $reward = CollectionReward::where('collection_id', $conferenceCollection->id)
                        ->where('reward_type', 'player_card')
                        ->first();
                    
                    if ($reward && $reward->player_id) {
                        $playerIds[] = $reward->player_id;
                    }
                }
            }
        }

        // Fetch all required players
        return Player::whereIn('id', $playerIds)
            ->orderBy('overall_rating', 'desc')
            ->get()
            ->map(function ($player) use ($userId) {
                return $this->formatPlayerCardData($player, $userId);
            });
    }

    /**
     * Format player card data with user ownership and stats
     */
    private function formatPlayerCardData(Player $player, int $userId): array
    {
        // Check if user owns this card
        $userCard = UserCard::where('player_id', $player->id)
            ->where('user_id', $userId)
            ->first();

        // Get stats from user_player_stats
        $stats = UserPlayerStats::where('user_id', $userId)
            ->where('player_id', $player->id)
            ->first();

        // Get sell price using CardPriceService
        $priceService = app(\App\Services\CardPriceService::class);
        $sellPrice = $priceService->getSellPrice($player->overall_rating);
        $buyPrice = $priceService->getBuyPrice($player->overall_rating);

        $cardData = [
            'player' => $player,
            'owned' => $userCard !== null,
            'locked' => $userCard ? $userCard->locked : false,
            'user_card_id' => $userCard ? $userCard->id : null,
            'count' => $userCard ? $userCard->count : 0,
            'sell_price' => $sellPrice,
            'buy_price' => $buyPrice,
            'is_tradeable' => $player->is_tradeable,
            'player_xp' => 0,
            'games_played' => 0,
        ];

        if ($stats) {
            $cardData['player_xp'] = $stats->player_xp;
            $cardData['games_played'] = $stats->games_played;
            $cardData['stats'] = [
                'points' => $stats->total_points,
                'rebounds' => $stats->total_rebounds,
                'assists' => $stats->total_assists,
            ];
            $cardData['averages'] = [
                'ppg' => $stats->getPPG(),
                'rpg' => $stats->getRPG(),
                'apg' => $stats->getAPG(),
            ];
        }

        return $cardData;
    }
}
