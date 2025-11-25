<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramChallenge;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class AdminTeamAffinityController extends Controller
{
    /**
     * Get all Team Affinity programs with their status
     */
    public function index(): JsonResponse
    {
        try {
            $programs = Program::with(['rewards', 'challenges'])
                ->teamAffinity()
                ->orderBy('team')
                ->get()
                ->map(function ($program) {
                    return [
                        'id' => $program->id,
                        'name' => $program->name,
                        'team' => $program->team,
                        'stars_required' => $program->stars_required,
                        'active' => $program->active,
                        'rewards_count' => $program->rewards->count(),
                        'challenges_count' => $program->challenges->count(),
                        'created_at' => $program->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $programs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Team Affinity programs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create all Team Affinity programs
     */
    public function createAll(Request $request): JsonResponse
    {
        try {
            $fresh = $request->input('fresh', false);

            if ($fresh) {
                Artisan::call('programs:create-team-affinity', ['--fresh' => true]);
            } else {
                Artisan::call('programs:create-team-affinity');
            }

            return response()->json([
                'success' => true,
                'message' => 'Team Affinity programs created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Team Affinity programs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk add rewards to multiple Team Affinity programs
     */
    public function bulkAddRewards(Request $request): JsonResponse
    {
        $request->validate([
            'team_ids' => 'required|array',
            'team_ids.*' => 'exists:programs,id',
            'rewards' => 'required|array',
            'rewards.*.stars_threshold' => 'required|integer|min:1',
            'rewards.*.reward_type' => 'required|in:pack,stubs,player,xp,program_xp',
            'rewards.*.reward_id' => 'nullable|integer',
            'rewards.*.reward_amount' => 'nullable|integer',
            'rewards.*.description' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $teamIds = $request->input('team_ids');
            $rewards = $request->input('rewards');

            foreach ($teamIds as $programId) {
                foreach ($rewards as $rewardData) {
                    ProgramReward::create([
                        'program_id' => $programId,
                        'xp_threshold' => null,
                        'stars_threshold' => $rewardData['stars_threshold'],
                        'reward_type' => $rewardData['reward_type'],
                        'reward_id' => $rewardData['reward_id'] ?? null,
                        'reward_amount' => $rewardData['reward_amount'] ?? null,
                        'description' => $rewardData['description'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rewards added successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add rewards',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk add challenges to multiple Team Affinity programs
     */
    public function bulkAddChallenges(Request $request): JsonResponse
    {
        $request->validate([
            'team_ids' => 'required|array',
            'team_ids.*' => 'exists:programs,id',
            'challenges' => 'required|array',
            'challenges.*.type' => 'required|in:stat,game_count,pxp',
            'challenges.*.target_stat' => 'nullable|string',
            'challenges.*.target_value' => 'required|integer|min:1',
            'challenges.*.constraint_type' => 'nullable|in:position,team,tier,player',
            'challenges.*.constraint_value' => 'nullable|string',
            'challenges.*.stars_reward' => 'required|integer|min:0',
            'challenges.*.xp_reward' => 'nullable|integer|min:0',
            'challenges.*.description' => 'required|string',
            'challenges.*.use_team_name' => 'nullable|boolean', // If true, replace {TEAM} with actual team name
        ]);

        DB::beginTransaction();

        try {
            $teamIds = $request->input('team_ids');
            $challenges = $request->input('challenges');

            foreach ($teamIds as $programId) {
                $program = Program::findOrFail($programId);

                foreach ($challenges as $challengeData) {
                    $description = $challengeData['description'];
                    $constraintValue = $challengeData['constraint_value'] ?? null;

                    // Replace {TEAM} placeholder with actual team name if requested
                    if (isset($challengeData['use_team_name']) && $challengeData['use_team_name']) {
                        $description = str_replace('{TEAM}', $program->team, $description);

                        // If constraint type is team and no value set, use program's team
                        if ($challengeData['constraint_type'] === 'team' && empty($constraintValue)) {
                            $constraintValue = $program->team;
                        }
                    }

                    ProgramChallenge::create([
                        'program_id' => $programId,
                        'type' => $challengeData['type'],
                        'target_stat' => $challengeData['target_stat'] ?? null,
                        'target_value' => $challengeData['target_value'],
                        'constraint_type' => $challengeData['constraint_type'] ?? null,
                        'constraint_value' => $constraintValue,
                        'stars_reward' => $challengeData['stars_reward'],
                        'xp_reward' => $challengeData['xp_reward'] ?? 0,
                        'description' => $description,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Challenges added successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add challenges',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a specific reward for a team
     */
    public function updateReward(Request $request, $programId, $rewardId): JsonResponse
    {
        $request->validate([
            'reward_type' => 'nullable|in:pack,stubs,player,xp,program_xp',
            'reward_id' => 'nullable|integer',
            'reward_amount' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        try {
            $program = Program::teamAffinity()->findOrFail($programId);
            $reward = ProgramReward::where('program_id', $programId)
                ->findOrFail($rewardId);

            $reward->update($request->only([
                'reward_type',
                'reward_id',
                'reward_amount',
                'description',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Reward updated successfully',
                'data' => $reward,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reward',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
