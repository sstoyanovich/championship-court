<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramChallenge;
use App\Models\UserProgramReward;
use App\Models\UserCard;
use App\Models\UserPack;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminProgramController extends Controller
{
    /**
     * Display a listing of programs with search and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Program::query()->with(['rewards', 'challenges']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $programs = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $programs,
        ]);
    }

    /**
     * Store a newly created program
     */
    public function store(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Create the program
            $programData = $request->except(['rewards', 'challenges']);
            $program = Program::create($programData);

            // Create rewards if provided
            if ($request->has('rewards')) {
                foreach ($request->rewards as $rewardData) {
                    $program->rewards()->create($rewardData);
                }
            }

            // Create challenges if provided
            if ($request->has('challenges')) {
                foreach ($request->challenges as $challengeData) {
                    $program->challenges()->create($challengeData);
                }
            }

            $program->load(['rewards', 'challenges']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $program,
                'message' => 'Program created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create program: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified program
     */
    public function show(string $id): JsonResponse
    {
        $program = Program::with(['rewards', 'challenges'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $program,
        ]);
    }

    /**
     * Update the specified program
     */
    public function update(Request $request, string $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $program = Program::findOrFail($id);

            // Update program data
            $programData = $request->except(['rewards', 'challenges']);
            $program->update($programData);

            // Update rewards
            if ($request->has('rewards')) {
                // Delete existing rewards
                $program->rewards()->delete();

                // Create new rewards
                foreach ($request->rewards as $rewardData) {
                    $program->rewards()->create($rewardData);
                }
            }

            // Update challenges
            if ($request->has('challenges')) {
                // Delete existing challenges
                $program->challenges()->delete();

                // Create new challenges
                foreach ($request->challenges as $challengeData) {
                    $program->challenges()->create($challengeData);
                }
            }

            $program->load(['rewards', 'challenges']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $program,
                'message' => 'Program updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update program: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified program
     */
    public function destroy(string $id): JsonResponse
    {
        $program = Program::findOrFail($id);
        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'Program deleted successfully',
        ]);
    }

    /**
     * Mark a reward as available and distribute to users who earned it
     */
    public function unlockReward(Request $request, int $rewardId): JsonResponse
    {
        DB::beginTransaction();
        try {
            $reward = ProgramReward::findOrFail($rewardId);

            // Update the reward to be available
            $reward->update([
                'available' => true,
                'reward_id' => $request->input('reward_id'), // Set the actual player/pack ID
            ]);

            // Find all users who earned this reward but haven't claimed it yet (claimed_at is null)
            $pendingClaims = UserProgramReward::where('program_reward_id', $rewardId)
                ->whereNull('claimed_at')
                ->get();

            $distributedCount = 0;

            foreach ($pendingClaims as $userReward) {
                $userId = $userReward->user_id;
                $user = User::find($userId);

                if (!$user) {
                    continue;
                }

                // Distribute the reward based on type
                if ($reward->isStubs()) {
                    $user->addStubs($reward->reward_amount);
                    Log::info("Pending stubs reward distributed", [
                        'user_id' => $userId,
                        'amount' => $reward->reward_amount,
                    ]);
                } elseif ($reward->isPlayer() && $reward->reward_id) {
                    $userCard = UserCard::firstOrCreate(
                        ['user_id' => $userId, 'player_id' => $reward->reward_id],
                        ['obtained_at' => now(), 'locked' => false, 'count' => 0]
                    );
                    $userCard->increment('count');
                    Log::info("Pending player reward distributed", [
                        'user_id' => $userId,
                        'player_id' => $reward->reward_id,
                    ]);
                } elseif ($reward->isPack() && $reward->reward_id) {
                    $packCount = $reward->reward_amount ?? 1;
                    for ($i = 0; $i < $packCount; $i++) {
                        UserPack::create([
                            'user_id' => $userId,
                            'pack_id' => $reward->reward_id,
                            'source' => 'reward',
                            'opened' => false,
                            'obtained_at' => now(),
                        ]);
                    }
                    Log::info("Pending pack reward distributed", [
                        'user_id' => $userId,
                        'pack_id' => $reward->reward_id,
                        'quantity' => $packCount,
                    ]);
                }

                // Update the user reward to mark as claimed
                $userReward->update(['claimed_at' => now()]);
                $distributedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Reward unlocked and distributed to {$distributedCount} users",
                'reward' => $reward->fresh(),
                'distributed_count' => $distributedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock reward',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
