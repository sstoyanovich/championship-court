<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\UserProgram;
use App\Models\UserProgramReward;
use App\Models\UserProgramChallenge;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProgramController extends Controller
{
    /**
     * Get all active programs.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Program::with(['rewards.player', 'rewards.pack', 'challenges'])
                ->where('active', true);

            // Filter by category if provided
            if ($request->has('category')) {
                $category = $request->input('category');
                if ($category === 'team_affinity') {
                    $query->teamAffinity()->orderBy('team');
                } elseif ($category === 'general') {
                    $query->general();
                }
            } else {
                // By default, exclude team affinity programs
                $query->general();
            }

            $programs = $query->get();

            return response()->json([
                'success' => true,
                'data' => $programs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch programs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific program with user progress.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $program = Program::with(['rewards.player', 'rewards.pack', 'challenges'])->findOrFail($id);

            // Get user progress for this program
            $userProgram = UserProgram::firstOrCreate(
                ['user_id' => $userId, 'program_id' => $id],
                ['current_xp' => 0, 'current_stars' => 0]
            );

            // Get user's challenge progress
            $userChallenges = UserProgramChallenge::where('user_id', $userId)
                ->whereIn('program_challenge_id', $program->challenges->pluck('id'))
                ->get()
                ->keyBy('program_challenge_id');

            // Enrich challenges with user progress
            $program->challenges->transform(function ($challenge) use ($userChallenges) {
                $userChallenge = $userChallenges->get($challenge->id);

                $currentProgress = $userChallenge ? $userChallenge->current_progress : 0;
                $progressPercentage = $challenge->target_value > 0
                    ? min(100, ($currentProgress / $challenge->target_value) * 100)
                    : 0;

                $challenge->current_progress = $currentProgress;
                $challenge->progress_percentage = $progressPercentage;
                $challenge->completed = $userChallenge ? $userChallenge->completed : false;

                return $challenge;
            });

            // Get completed challenge IDs
            $completedChallenges = $userChallenges->filter(fn($uc) => $uc->completed)
                ->pluck('program_challenge_id')
                ->toArray();

            // Get claimed rewards (existence of record means it's claimed or earned but pending)
            $userRewards = UserProgramReward::where('user_id', $userId)
                ->whereIn('program_reward_id', $program->rewards->pluck('id'))
                ->get()
                ->keyBy('program_reward_id');

            $claimedRewards = $userRewards->pluck('program_reward_id')->toArray();

            // Add claimed status and pending status to each reward
            $program->rewards->transform(function ($reward) use ($userRewards) {
                $userReward = $userRewards->get($reward->id);
                $reward->claimed = $userReward !== null;
                $reward->pending = $userReward && $userReward->claimed_at === null;
                $reward->can_claim = $reward->isAvailable() && $reward->canBeClaimed();
                return $reward;
            });

            // Calculate overall progress percentage
            $progressPercentage = 0;
            if ($program->type === 'xp' && $program->total_xp_required > 0) {
                $progressPercentage = min(100, ($userProgram->current_xp / $program->total_xp_required) * 100);
            } elseif ($program->type === 'star' && $program->stars_required > 0) {
                $progressPercentage = min(100, ($userProgram->current_stars / $program->stars_required) * 100);
            }

            // Add progress info to the program
            $program->user_progress = [
                'current_xp' => $userProgram->current_xp,
                'current_stars' => $userProgram->current_stars,
                'stars_earned' => $userProgram->current_stars, // For backward compatibility
                'completed_challenges' => $completedChallenges,
                'claimed_rewards' => $claimedRewards,
                'progress_percentage' => $progressPercentage,
                'completed' => $userProgram->completed,
            ];

            return response()->json([
                'success' => true,
                'data' => $program,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch program',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all user's program progress.
     */
    public function getUserProgress(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $userProgress = UserProgram::with('program')
                ->where('user_id', $userId)
                ->get()
                ->map(function ($up) use ($userId) {
                    $program = $up->program;

                    // Get completed challenges count
                    $completedChallenges = UserProgramChallenge::where('user_id', $userId)
                        ->whereIn('program_challenge_id', $program->challenges->pluck('id'))
                        ->where('completed', true)
                        ->count();

                    $totalChallenges = $program->challenges->count();

                    // Get claimed rewards count (existence of record means it's claimed)
                    $claimedRewards = UserProgramReward::where('user_id', $userId)
                        ->whereIn('program_reward_id', $program->rewards->pluck('id'))
                        ->count();

                    $totalRewards = $program->rewards->count();

                    return [
                        'program_id' => $program->id,
                        'program_name' => $program->name,
                        'program_type' => $program->type,
                        'current_xp' => $up->current_xp,
                        'total_xp_required' => $program->total_xp_required,
                        'stars_earned' => $up->current_stars,
                        'stars_required' => $program->stars_required,
                        'completed_challenges' => $completedChallenges,
                        'total_challenges' => $totalChallenges,
                        'claimed_rewards' => $claimedRewards,
                        'total_rewards' => $totalRewards,
                        'progress_percentage' => $program->type === 'xp'
                            ? ($program->total_xp_required > 0 ? ($up->current_xp / $program->total_xp_required) * 100 : 0)
                            : ($program->stars_required > 0 ? ($up->current_stars / $program->stars_required) * 100 : 0),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $userProgress,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Claim a reward.
     */
    public function claimReward(Request $request, $rewardId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = $request->user()->id;

            $reward = ProgramReward::with('program')->findOrFail($rewardId);

            // Check if already claimed (existence of record means it's claimed)
            $existingClaim = UserProgramReward::where('user_id', $userId)
                ->where('program_reward_id', $rewardId)
                ->first();

            if ($existingClaim) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reward already claimed',
                ], 400);
            }

            // Get user's progress
            $userProgram = UserProgram::where('user_id', $userId)
                ->where('program_id', $reward->program_id)
                ->first();

            if (!$userProgram) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has not started this program',
                ], 400);
            }

            // Check if user meets requirements
            $meetsRequirement = false;
            if ($reward->program->type === 'xp') {
                $meetsRequirement = $userProgram->current_xp >= ($reward->xp_threshold ?? 0);
            } else if ($reward->program->type === 'star') {
                $meetsRequirement = $userProgram->current_stars >= ($reward->stars_threshold ?? 0);
            }

            if (!$meetsRequirement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Requirements not met for this reward',
                ], 400);
            }

            // Check if reward is available to claim
            if (!$reward->isAvailable()) {
                // Reward is "coming soon" - mark as earned but don't give reward yet
                UserProgramReward::create([
                    'user_id' => $userId,
                    'program_id' => $reward->program_id,
                    'program_reward_id' => $rewardId,
                    'claimed_at' => null, // Not claimed yet, just earned
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Reward earned! It will be available once released.',
                    'reward' => $reward,
                    'pending' => true,
                    'coming_soon_label' => $reward->coming_soon_label,
                ]);
            }

            // Claim the reward
            UserProgramReward::create([
                'user_id' => $userId,
                'program_id' => $reward->program_id,
                'program_reward_id' => $rewardId,
                'claimed_at' => now(),
            ]);

            // Actually give the user the reward
            $rewardDetails = $this->distributeReward($userId, $reward);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reward claimed successfully',
                'reward' => $reward,
                'reward_details' => $rewardDetails,
                'pending' => false,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to claim reward',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Distribute the actual reward to the user
     */
    private function distributeReward($userId, ProgramReward $reward): array
    {
        $details = [
            'type' => $reward->reward_type,
            'given' => false,
        ];

        switch ($reward->reward_type) {
            case 'pack':
                // Give user pack(s)
                if ($reward->reward_id) {
                    $packCount = $reward->reward_amount ?? 1; // Default to 1 if not specified
                    for ($i = 0; $i < $packCount; $i++) {
                        \App\Models\UserPack::create([
                            'user_id' => $userId,
                            'pack_id' => $reward->reward_id,
                        ]);
                    }
                    $details['given'] = true;
                    $details['pack_id'] = $reward->reward_id;
                    $details['quantity'] = $packCount;
                    $details['message'] = $packCount > 1
                        ? "{$packCount} Packs added to your inventory"
                        : 'Pack added to your inventory';
                }
                break;

            case 'stubs':
                // Give user stubs (currency)
                if ($reward->reward_amount) {
                    $user = \App\Models\User::find($userId);
                    $user->increment('stubs_balance', $reward->reward_amount);
                    $details['given'] = true;
                    $details['amount'] = $reward->reward_amount;
                    $details['message'] = "{$reward->reward_amount} Stubs added";
                }
                break;

            case 'player':
                // Give user a specific player card
                if ($reward->reward_id) {
                    $userCard = \App\Models\UserCard::firstOrCreate(
                        ['user_id' => $userId, 'player_id' => $reward->reward_id],
                        ['obtained_at' => now(), 'locked' => false, 'count' => 0]
                    );
                    $userCard->increment('count');
                    $details['given'] = true;
                    $details['player_id'] = $reward->reward_id;
                    $details['message'] = 'Player card added to your collection';
                }
                break;

            case 'xp':
                // Give user XP towards active season programs
                if ($reward->reward_amount) {
                    // Find all active XP-based season programs
                    $seasonPrograms = Program::where('active', true)
                        ->where('type', 'xp')
                        ->where('category', 'general')
                        ->get();

                    foreach ($seasonPrograms as $seasonProgram) {
                        $userProgram = UserProgram::firstOrCreate(
                            ['user_id' => $userId, 'program_id' => $seasonProgram->id],
                            ['current_xp' => 0, 'current_stars' => 0]
                        );
                        $userProgram->increment('current_xp', $reward->reward_amount);
                    }

                    $details['given'] = true;
                    $details['amount'] = $reward->reward_amount;
                    $details['message'] = "{$reward->reward_amount} XP added to season programs";
                }
                break;

            case 'program_xp':
                // Give user XP towards a specific program
                if ($reward->reward_id && $reward->reward_amount) {
                    $targetProgram = Program::find($reward->reward_id);

                    if ($targetProgram && $targetProgram->type === 'xp') {
                        $userProgram = UserProgram::firstOrCreate(
                            ['user_id' => $userId, 'program_id' => $targetProgram->id],
                            ['current_xp' => 0, 'current_stars' => 0]
                        );
                        $userProgram->increment('current_xp', $reward->reward_amount);

                        $details['given'] = true;
                        $details['amount'] = $reward->reward_amount;
                        $details['program_id'] = $targetProgram->id;
                        $details['program_name'] = $targetProgram->name;
                        $details['message'] = "{$reward->reward_amount} XP added to {$targetProgram->name}";
                    } else {
                        $details['message'] = 'Target program not found or is not an XP program';
                    }
                }
                break;

            default:
                $details['message'] = 'Unknown reward type';
                break;
        }

        return $details;
    }
}
