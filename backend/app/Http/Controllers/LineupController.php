<?php

namespace App\Http\Controllers;

use App\Models\Lineup;
use App\Models\LineupSlot;
use App\Models\UserCard;
use App\Models\Player;
use App\Models\UserPlayerStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LineupController extends Controller
{
    /**
     * Get all lineups for the user
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $lineups = Lineup::where('user_id', $userId)
            ->withCount('slots')
            ->with(['slots.userCard.player'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform lineups to include player stats
        $lineupsData = $lineups->toArray();
        
        foreach ($lineupsData as &$lineup) {
            foreach ($lineup['slots'] as &$slot) {
                if (isset($slot['user_card']) && isset($slot['user_card']['player'])) {
                    $playerId = $slot['user_card']['player']['id'];
                    $stats = UserPlayerStats::where('user_id', $userId)
                        ->where('player_id', $playerId)
                        ->first();
                    
                    $slot['user_card']['player_xp'] = $stats ? $stats->player_xp : 0;
                    $slot['user_card']['games_played'] = $stats ? $stats->games_played : 0;
                }
            }
        }

        return response()->json([
            'success' => true,
            'lineups' => $lineupsData,
        ]);
    }

    /**
     * Get a single lineup with all slots
     */
    public function show(Request $request, $id)
    {
        $userId = $request->user()->id;

        $lineup = Lineup::where('id', $id)
            ->where('user_id', $userId)
            ->with(['slots.userCard.player'])
            ->first();

        if (!$lineup) {
            return response()->json([
                'success' => false,
                'message' => 'Lineup not found',
            ], 404);
        }

        // Transform lineup to include player stats
        $lineupData = $lineup->toArray();
        
        foreach ($lineupData['slots'] as &$slot) {
            if (isset($slot['user_card']) && isset($slot['user_card']['player'])) {
                $playerId = $slot['user_card']['player']['id'];
                $stats = UserPlayerStats::where('user_id', $userId)
                    ->where('player_id', $playerId)
                    ->first();
                
                $slot['user_card']['player_xp'] = $stats ? $stats->player_xp : 0;
                $slot['user_card']['games_played'] = $stats ? $stats->games_played : 0;
            }
        }

        return response()->json([
            'success' => true,
            'lineup' => $lineupData,
        ]);
    }

    /**
     * Create a new lineup
     */
    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $lineup = Lineup::create([
            'user_id' => $userId,
            'name' => $validated['name'],
        ]);

        // Create all 14 empty slots
        $positions = ['PG', 'SG', 'SF', 'PF', 'C', '6', '7', '8', '9', '10', '11', '12', '13', '14'];
        foreach ($positions as $position) {
            LineupSlot::create([
                'lineup_id' => $lineup->id,
                'position' => $position,
                'user_card_id' => null,
            ]);
        }

        $lineup->load(['slots.userCard.player']);

        return response()->json([
            'success' => true,
            'lineup' => $lineup,
        ], 201);
    }

    /**
     * Update lineup name
     */
    public function update(Request $request, $id)
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $lineup = Lineup::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$lineup) {
            return response()->json([
                'success' => false,
                'message' => 'Lineup not found',
            ], 404);
        }

        $lineup->update(['name' => $validated['name']]);

        return response()->json([
            'success' => true,
            'lineup' => $lineup,
        ]);
    }

    /**
     * Delete a lineup
     */
    public function destroy(Request $request, $id)
    {
        $userId = $request->user()->id;

        $lineup = Lineup::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$lineup) {
            return response()->json([
                'success' => false,
                'message' => 'Lineup not found',
            ], 404);
        }

        $lineup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lineup deleted successfully',
        ]);
    }

    /**
     * Update a lineup slot (add/update/remove player)
     */
    public function updateSlot(Request $request, $lineupId)
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'position' => 'required|string|in:PG,SG,SF,PF,C,6,7,8,9,10,11,12,13,14',
            'user_card_id' => 'nullable|exists:user_cards,id',
        ]);

        // Verify lineup belongs to user
        $lineup = Lineup::where('id', $lineupId)
            ->where('user_id', $userId)
            ->first();

        if (!$lineup) {
            return response()->json([
                'success' => false,
                'message' => 'Lineup not found',
            ], 404);
        }

        // If adding a card, verify it belongs to the user
        if ($validated['user_card_id']) {
            $userCard = UserCard::where('id', $validated['user_card_id'])
                ->where('user_id', $userId)
                ->whereHas('player')
                ->first();

            if (!$userCard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Card not found',
                ], 404);
            }

            // Get the NBA player ID
            $player = $userCard->player;
            $nbaId = $player->nba_id;

            // Only check for duplicates if the player has an nba_id
            // Custom/program players without nba_id can be used multiple times
            if ($nbaId) {
                // Check if this NBA player is already in another slot of this lineup
                $existingSlot = LineupSlot::where('lineup_id', $lineupId)
                    ->where('position', '!=', $validated['position'])
                    ->whereNotNull('user_card_id')
                    ->whereHas('userCard.player', function ($query) use ($nbaId) {
                        $query->where('nba_id', $nbaId);
                    })
                    ->first();

                if ($existingSlot) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This player is already in another lineup slot',
                    ], 422);
                }
            }
        }

        // Update the slot
        $slot = LineupSlot::where('lineup_id', $lineupId)
            ->where('position', $validated['position'])
            ->first();

        if (!$slot) {
            return response()->json([
                'success' => false,
                'message' => 'Slot not found',
            ], 404);
        }

        $slot->update(['user_card_id' => $validated['user_card_id']]);
        $slot->load('userCard.player');

        // Transform slot to include player stats
        $slotData = $slot->toArray();
        
        if (isset($slotData['user_card']) && isset($slotData['user_card']['player'])) {
            $playerId = $slotData['user_card']['player']['id'];
            $stats = UserPlayerStats::where('user_id', $userId)
                ->where('player_id', $playerId)
                ->first();
            
            $slotData['user_card']['player_xp'] = $stats ? $stats->player_xp : 0;
            $slotData['user_card']['games_played'] = $stats ? $stats->games_played : 0;
        }

        return response()->json([
            'success' => true,
            'slot' => $slotData,
        ]);
    }
}
