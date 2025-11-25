<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\UserCard;
use App\Services\CardPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    protected CardPriceService $priceService;

    public function __construct(CardPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    /**
     * Sell a card
     * POST /api/cards/sell
     */
    public function sell(Request $request): JsonResponse
    {
        $request->validate([
            'user_card_id' => 'required|exists:user_cards,id',
        ]);

        $userId = $request->user()->id;
        $userCardId = $request->user_card_id;

        DB::beginTransaction();
        try {
            // Get the user card with player info
            $userCard = UserCard::with('player')
                ->where('id', $userCardId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Check if card is tradeable
            if (!$userCard->player->is_tradeable) {
                return response()->json([
                    'success' => false,
                    'message' => 'This card is a reward card and cannot be sold.',
                ], 400);
            }

            // Check if user has at least one copy
            if ($userCard->count < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not own this card.',
                ], 400);
            }

            // Check if card is locked with only 1 copy
            // If locked but has multiple copies, allow selling the extras
            if ($userCard->locked && $userCard->count <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot sell your last copy of a locked card. Unlock it from your collection first.',
                ], 400);
            }

            // Get sell price
            $sellPrice = $this->priceService->getSellPrice($userCard->player->overall_rating);

            // Decrement count or delete if count becomes 0
            if ($userCard->count > 1) {
                $userCard->decrement('count');
                $remainingCount = $userCard->count;
            } else {
                $userCard->delete();
                $remainingCount = 0;
            }

            // Add stubs to user balance
            $user = $request->user();
            $user->addStubs($sellPrice);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Card sold successfully',
                'stubs_earned' => $sellPrice,
                'new_balance' => $user->fresh()->stubs_balance,
                'remaining_count' => $remainingCount,
                'card_deleted' => $remainingCount === 0,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to sell card: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get price information for a card
     * GET /api/cards/{playerId}/price
     */
    public function getPrice(int $playerId): JsonResponse
    {
        try {
            $player = Player::findOrFail($playerId);
            $prices = $this->priceService->getCardPrices($player);

            return response()->json([
                'success' => true,
                'player_id' => $playerId,
                'overall_rating' => $player->overall_rating,
                'sell_price' => $prices['sell_price'],
                'buy_price' => $prices['buy_price'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get card price: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Buy a card
     * POST /api/cards/buy
     */
    public function buy(Request $request): JsonResponse
    {
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        $userId = $request->user()->id;
        $playerId = $request->player_id;

        DB::beginTransaction();
        try {
            // Get the player
            $player = Player::findOrFail($playerId);

            // Check if card is tradeable
            if (!$player->is_tradeable) {
                return response()->json([
                    'success' => false,
                    'message' => 'This card is a reward card and cannot be purchased.',
                ], 400);
            }

            // Get buy price
            $buyPrice = $this->priceService->getBuyPrice($player->overall_rating);

            // Check if user has enough stubs
            $user = $request->user();
            if ($user->stubs_balance < $buyPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not enough stubs to purchase this card.',
                    'required' => $buyPrice,
                    'current' => $user->stubs_balance,
                    'shortage' => $buyPrice - $user->stubs_balance,
                ], 400);
            }

            // Deduct stubs
            if (!$user->deductStubs($buyPrice)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to deduct stubs.',
                ], 500);
            }

            // Add card to user's collection (or increment count)
            $userCard = UserCard::firstOrCreate(
                ['user_id' => $userId, 'player_id' => $playerId],
                ['obtained_at' => now(), 'locked' => false, 'count' => 0]
            );
            $userCard->increment('count');
            $userCard->refresh(); // Refresh to get the updated count

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Card purchased successfully',
                'stubs_spent' => $buyPrice,
                'new_balance' => $user->fresh()->stubs_balance,
                'card_count' => $userCard->count,
                'user_card_id' => $userCard->id,
                'player' => $player,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to purchase card: ' . $e->getMessage(),
            ], 500);
        }
    }
}

