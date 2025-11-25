<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollectionReward;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminCollectionRewardController extends Controller
{
    /**
     * Get all rewards for a specific collection
     */
    public function index($collectionId): JsonResponse
    {
        // Cast collectionId to integer
        $collectionId = (int) $collectionId;

        $collection = Collection::findOrFail($collectionId);

        $rewards = CollectionReward::where('collection_id', $collectionId)
            ->with(['player', 'pack'])
            ->orderBy('order', 'asc')
            ->orderBy('required_cards', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rewards,
        ]);
    }

    /**
     * Store a new reward for a collection
     */
    public function store(Request $request, $collectionId): JsonResponse
    {
        // Cast collectionId to integer
        $collectionId = (int) $collectionId;

        $collection = Collection::findOrFail($collectionId);

        $validated = $request->validate([
            'required_cards' => 'required|integer|min:1',
            'reward_type' => 'required|in:stubs,player_card,pack',
            'reward_quantity' => 'required|integer|min:1',
            'player_id' => 'nullable|exists:players,id',
            'pack_id' => 'nullable|exists:packs,id',
            'order' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $validated['collection_id'] = $collectionId;

        // Set order to highest + 1 if not provided
        if (!isset($validated['order'])) {
            $maxOrder = (int) (CollectionReward::where('collection_id', $collectionId)->max('order') ?? 0);
            $validated['order'] = $maxOrder + 1;
        }

        $reward = CollectionReward::create($validated);
        $reward->load(['player', 'pack']);

        return response()->json([
            'success' => true,
            'data' => $reward,
            'message' => 'Reward created successfully',
        ], 201);
    }

    /**
     * Display a specific reward
     */
    public function show($rewardId): JsonResponse
    {
        $rewardId = (int) $rewardId;
        $reward = CollectionReward::with(['player', 'pack', 'collection'])->findOrFail($rewardId);

        return response()->json([
            'success' => true,
            'data' => $reward,
        ]);
    }

    /**
     * Update a specific reward
     */
    public function update(Request $request, $rewardId): JsonResponse
    {
        $rewardId = (int) $rewardId;
        $reward = CollectionReward::findOrFail($rewardId);

        $validated = $request->validate([
            'required_cards' => 'sometimes|integer|min:1',
            'reward_type' => 'sometimes|in:stubs,player_card,pack',
            'reward_quantity' => 'sometimes|integer|min:1',
            'player_id' => 'nullable|exists:players,id',
            'pack_id' => 'nullable|exists:packs,id',
            'order' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $reward->update($validated);
        $reward->load(['player', 'pack']);

        return response()->json([
            'success' => true,
            'data' => $reward,
            'message' => 'Reward updated successfully',
        ]);
    }

    /**
     * Delete a specific reward
     */
    public function destroy($rewardId): JsonResponse
    {
        $rewardId = (int) $rewardId;
        $reward = CollectionReward::findOrFail($rewardId);
        $reward->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reward deleted successfully',
        ]);
    }
}
