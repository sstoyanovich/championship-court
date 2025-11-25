<?php

namespace App\Services;

use App\Models\Program;
use App\Models\ProgramChallenge;
use App\Models\ProgramReward;
use App\Models\User;
use App\Models\UserProgram;
use App\Models\UserProgramChallenge;
use App\Models\UserProgramReward;
use App\Models\UserPlayerStats;
use App\Models\UserCard;
use App\Models\UserPack;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgramService
{
    /**
     * Main entry point after a game is completed.
     * Processes all program-related updates.
     */
    public function processGameCompletion(int $userId, array $gameStats, array $matchedCards): array
    {
        $results = [
            'xp_earned' => 0,
            'programs_updated' => [],
            'challenges_completed' => [],
            'challenges_updated' => [],
            'rewards_earned' => [],
            'pxp_earned' => [],
        ];

        DB::beginTransaction();
        try {
            // 1. Calculate and award game XP to XP programs
            $gameXp = $this->calculateGameXP($gameStats);
            $results['xp_earned'] = $gameXp;
            if ($gameXp > 0) {
                $this->awardGameXP($userId, $gameXp);
            }

            // 2. Calculate and award PXP to each player that played
            $pxpEarned = [];
            foreach ($matchedCards as $match) {
                $stats = $match['stats'];
                $userCardId = $match['user_card_id'];
                $playerId = $match['player_id'] ?? null;

                if ($playerId) {
                    // Get current parallel data before awarding PXP
                    $userPlayerStats = UserPlayerStats::where('user_id', $userId)
                        ->where('player_id', $playerId)
                        ->first();
                    $oldParallelData = $userPlayerStats ? $userPlayerStats->getParallelData() : null;
                    $oldPxp = $userPlayerStats ? $userPlayerStats->player_xp : 0;

                    // Award PXP
                    $pxpData = $this->calculatePlayerXPWithDetails($stats);
                    $this->awardPlayerXP($userId, $playerId, $stats, $pxpData['pxp']);

                    // Get updated parallel data after awarding PXP
                    $userPlayerStats = UserPlayerStats::where('user_id', $userId)
                        ->where('player_id', $playerId)
                        ->first();
                    $newParallelData = $userPlayerStats->getParallelData();

                    $pxpEarned[] = [
                        'player_id' => $playerId,
                        'player_name' => $match['player_name'],
                        'pxp_earned' => $pxpData['pxp'],
                        'achievement' => $pxpData['achievement'],
                        'bonus' => $pxpData['bonus'],
                        'old_pxp' => $oldPxp,
                        'new_pxp' => $userPlayerStats->player_xp,
                        'old_parallel' => $oldParallelData,
                        'new_parallel' => $newParallelData,
                        'level_up' => $oldParallelData && $newParallelData['level'] > $oldParallelData['level'],
                    ];
                }
            }
            $results['pxp_earned'] = $pxpEarned;

            // 3. Update challenge progress across all active programs
            $challengeResults = $this->updateChallengeProgress($userId, $gameStats, $matchedCards);
            $results['challenges_completed'] = $challengeResults['completed'];
            $results['challenges_updated'] = $challengeResults['updated'];
            $results['programs_updated'] = $challengeResults['programs_updated'];

            // 4. Check and award any newly unlocked rewards
            $userPrograms = UserProgram::where('user_id', $userId)
                ->where('completed', false)
                ->get();

            foreach ($userPrograms as $userProgram) {
                $rewards = $this->checkAndAwardRewards($userId, $userProgram->program_id);
                $results['rewards_earned'] = array_merge($results['rewards_earned'], $rewards);
            }

            DB::commit();
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Program processing failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate game XP from overall team performance.
     */
    public function calculateGameXP(array $gameStats): int
    {
        $totalPoints = 0;
        $totalAssists = 0;
        $totalRebounds = 0;
        $totalSteals = 0;
        $totalBlocks = 0;

        // Sum up all player stats
        foreach ($gameStats as $playerStat) {
            $totalPoints += $playerStat['points'] ?? 0;
            $totalAssists += $playerStat['assists'] ?? 0;
            $totalRebounds += $playerStat['rebounds'] ?? 0;
            $totalSteals += $playerStat['steals'] ?? 0;
            $totalBlocks += $playerStat['blocks'] ?? 0;
        }

        // Calculate XP with weighted values
        $xp = ($totalPoints * 10) +
            ($totalAssists * 15) +
            ($totalRebounds * 10) +
            ($totalSteals * 20) +
            ($totalBlocks * 20);

        return max(0, $xp);
    }

    /**
     * Calculate PXP for an individual player with detailed achievement info.
     * Returns array with pxp, achievement, and bonus.
     */
    public function calculatePlayerXPWithDetails(array $stats): array
    {
        $minutes = $stats['minutes'] ?? 0;
        $points = $stats['points'] ?? 0;
        $assists = $stats['assists'] ?? 0;
        $rebounds = $stats['rebounds'] ?? 0;
        $steals = $stats['steals'] ?? 0;
        $blocks = $stats['blocks'] ?? 0;
        $fgm = $stats['fgm'] ?? 0;  // Field goals made
        $tpm = $stats['tpm'] ?? 0;  // Three-pointers made

        // Base PXP calculation
        $basePxp = ($minutes * 5) +
            ($points * 2) +
            ($assists * 3) +
            ($rebounds * 2) +
            ($steals * 5) +
            ($blocks * 5) +
            ($fgm * 2) +      // +2 PXP per made field goal
            ($tpm * 3);       // +3 PXP per made three-pointer

        // Check for double-double and triple-double bonuses
        $doubleDigitCategories = 0;
        $statCategories = [$points, $rebounds, $assists, $steals, $blocks];

        foreach ($statCategories as $stat) {
            if ($stat >= 10) {
                $doubleDigitCategories++;
            }
        }

        $bonus = 0;
        $achievement = null;

        // Award bonuses
        if ($doubleDigitCategories >= 3) {
            // Triple-double: 500 PXP bonus
            $bonus = 500;
            $achievement = 'Triple-Double';
        } elseif ($doubleDigitCategories >= 2) {
            // Double-double: 250 PXP bonus
            $bonus = 250;
            $achievement = 'Double-Double';
        }

        $totalPxp = $basePxp + $bonus;

        return [
            'pxp' => max(0, $totalPxp),
            'achievement' => $achievement,
            'bonus' => $bonus,
        ];
    }

    /**
     * Calculate PXP for an individual player based on their performance.
     * Includes bonuses for double-doubles and triple-doubles.
     */
    public function calculatePlayerXP(array $stats): int
    {
        $result = $this->calculatePlayerXPWithDetails($stats);
        return $result['pxp'];
    }

    /**
     * Award game XP to all active XP-based programs.
     */
    public function awardGameXP(int $userId, int $xpAmount): void
    {
        $xpPrograms = Program::where('type', 'xp')
            ->where('active', true)
            ->get();

        foreach ($xpPrograms as $program) {
            $userProgram = $this->initializeUserProgram($userId, $program->id);
            if (!$userProgram->completed) {
                $userProgram->addXp($xpAmount);
            }
        }
    }

    /**
     * Award PXP to a player.
     * Note: Game stats are already added in matchAndUpdateStats(), so we only add PXP here.
     */
    public function awardPlayerXP(int $userId, int $playerId, array $stats, int $pxp): void
    {
        $userPlayerStats = UserPlayerStats::firstOrCreate(
            ['user_id' => $userId, 'player_id' => $playerId],
            ['player_xp' => 0]
        );

        // Only add PXP - stats are already added in StatsController::matchAndUpdateStats()
        $userPlayerStats->addPxp($pxp);
    }

    /**
     * Update challenge progress across all active programs.
     */
    public function updateChallengeProgress(int $userId, array $gameStats, array $matchedCards): array
    {
        $results = [
            'completed' => [],
            'updated' => [],
            'programs_updated' => [],
        ];

        $activePrograms = Program::where('active', true)->with('challenges')->get();

        foreach ($activePrograms as $program) {
            $userProgram = $this->initializeUserProgram($userId, $program->id);

            if ($userProgram->completed) {
                continue;
            }

            $programUpdated = false;

            foreach ($program->challenges as $challenge) {
                $userChallenge = UserProgramChallenge::firstOrCreate(
                    ['user_id' => $userId, 'program_challenge_id' => $challenge->id],
                    ['current_progress' => 0, 'completed' => false]
                );

                if ($userChallenge->completed) {
                    continue;
                }

                $progressAdded = $this->calculateChallengeProgress($challenge, $gameStats, $matchedCards, $userId);

                if ($progressAdded > 0) {
                    $completed = $userChallenge->addProgress($progressAdded);
                    $programUpdated = true;

                    if ($completed) {
                        // Award stars or XP based on program type
                        if ($program->isStarProgram() && $challenge->stars_reward) {
                            $userProgram->addStars($challenge->stars_reward);
                        } elseif ($program->isXpProgram() && $challenge->xp_reward) {
                            $userProgram->addXp($challenge->xp_reward);
                        }

                        $results['completed'][] = [
                            'program_id' => $program->id,
                            'program_name' => $program->name,
                            'program_type' => $program->type,
                            'challenge_id' => $challenge->id,
                            'challenge_description' => $challenge->description,
                            'challenge_type' => $challenge->type,
                            'progress_added' => $progressAdded,
                            'current_progress' => $userChallenge->current_progress,
                            'target_value' => $challenge->target_value,
                            'reward' => $program->isStarProgram() ?
                                "{$challenge->stars_reward} stars" :
                                "{$challenge->xp_reward} XP",
                        ];
                    } else {
                        // Challenge updated but not completed
                        $results['updated'][] = [
                            'program_id' => $program->id,
                            'program_name' => $program->name,
                            'program_type' => $program->type,
                            'challenge_id' => $challenge->id,
                            'challenge_description' => $challenge->description,
                            'challenge_type' => $challenge->type,
                            'progress_added' => $progressAdded,
                            'current_progress' => $userChallenge->current_progress,
                            'target_value' => $challenge->target_value,
                            'progress_percentage' => $userChallenge->getProgressPercentage(),
                        ];
                    }
                }
            }

            if ($programUpdated) {
                $results['programs_updated'][] = [
                    'program_id' => $program->id,
                    'program_name' => $program->name,
                    'current_xp' => $userProgram->current_xp,
                    'current_stars' => $userProgram->current_stars,
                ];
            }
        }

        return $results;
    }

    /**
     * Calculate how much progress was made on a specific challenge.
     */
    private function calculateChallengeProgress(ProgramChallenge $challenge, array $gameStats, array $matchedCards, int $userId): int
    {
        if ($challenge->isGameCountChallenge()) {
            // Each game counts as 1
            return 1;
        }

        if ($challenge->isStatChallenge()) {
            $total = 0;
            $statName = $challenge->target_stat;

            foreach ($gameStats as $playerStat) {
                $value = $playerStat[$statName] ?? 0;

                // Check constraints if any
                if ($challenge->hasConstraint()) {
                    if (!$this->meetsConstraint($playerStat, $matchedCards, $challenge, $userId)) {
                        continue;
                    }
                }

                $total += $value;
            }

            return $total;
        }

        if ($challenge->isPxpChallenge()) {
            $totalPxp = 0;

            foreach ($matchedCards as $match) {
                $playerId = $match['player_id'] ?? null;
                if (!$playerId) {
                    continue;
                }

                // Check constraints
                if ($challenge->hasConstraint()) {
                    if (!$this->playerMeetsConstraint($playerId, $challenge)) {
                        continue;
                    }
                }

                $pxp = $this->calculatePlayerXP($match['stats']);
                $totalPxp += $pxp;
            }

            return $totalPxp;
        }

        return 0;
    }

    /**
     * Check if a player stat meets the challenge constraint.
     */
    private function meetsConstraint(array $playerStat, array $matchedCards, ProgramChallenge $challenge, int $userId): bool
    {
        // Find the matched card for this player stat
        $playerName = $playerStat['player_name'] ?? '';
        $matchedCard = null;

        foreach ($matchedCards as $match) {
            if (strtolower($match['extracted_name']) === strtolower($playerName)) {
                $matchedCard = $match;
                break;
            }
        }

        if (!$matchedCard) {
            return false;
        }

        $playerId = $matchedCard['player_id'] ?? null;
        if (!$playerId) {
            return false;
        }

        return $this->playerMeetsConstraint($playerId, $challenge);
    }

    /**
     * Check if a player meets the challenge constraint.
     */
    private function playerMeetsConstraint(int $playerId, ProgramChallenge $challenge): bool
    {
        $player = Player::find($playerId);
        if (!$player) {
            return false;
        }

        if ($challenge->constraint_type === 'player') {
            return $playerId == $challenge->constraint_value;
        }

        if ($challenge->constraint_type === 'team') {
            return strtolower($player->team) === strtolower($challenge->constraint_value);
        }

        if ($challenge->constraint_type === 'position') {
            return strtolower($player->position) === strtolower($challenge->constraint_value);
        }

        if ($challenge->constraint_type === 'collection') {
            // Check if this player belongs to the specified collection
            $collectionId = $challenge->constraint_value;
            return \App\Models\Collection::where('id', $collectionId)
                ->whereHas('players', function ($query) use ($playerId) {
                    $query->where('players.id', $playerId);
                })
                ->exists();
        }

        return true;
    }

    /**
     * Claim a reward and add it to the user's inventory.
     */
    public function claimReward(int $userId, int $programRewardId): bool
    {
        $reward = ProgramReward::find($programRewardId);
        if (!$reward) {
            return false;
        }

        // Check if already claimed
        $existingClaim = UserProgramReward::where('user_id', $userId)
            ->where('program_reward_id', $programRewardId)
            ->exists();

        if ($existingClaim) {
            return false;
        }

        // Get program and user progress
        $program = Program::find($reward->program_id);
        if (!$program) {
            return false;
        }

        $userProgram = UserProgram::where('user_id', $userId)
            ->where('program_id', $reward->program_id)
            ->first();

        if (!$userProgram) {
            return false;
        }

        // Validate user has met the requirements
        if ($program->isXpProgram() && $reward->xp_threshold !== null) {
            // For XP programs, check if user has reached the XP threshold
            if ($userProgram->current_xp < $reward->xp_threshold) {
                return false;
            }
        } elseif ($program->isStarProgram()) {
            // For star programs, check if user has reached the stars threshold
            $requiredStars = $reward->stars_threshold ?? $program->stars_required;
            if ($userProgram->current_stars < $requiredStars) {
                return false;
            }
        }

        // Check if reward is available to claim
        if (!$reward->isAvailable()) {
            // Reward is "coming soon" - mark as earned but don't give reward yet
            UserProgramReward::create([
                'user_id' => $userId,
                'program_id' => $reward->program_id,
                'program_reward_id' => $programRewardId,
                'claimed_at' => null, // Not claimed yet, just earned
            ]);

            Log::info("Reward earned but pending availability", [
                'user_id' => $userId,
                'program_reward_id' => $programRewardId,
                'coming_soon_label' => $reward->coming_soon_label,
            ]);

            return true; // Successfully recorded as earned
        }

        // Record the claim
        UserProgramReward::create([
            'user_id' => $userId,
            'program_id' => $reward->program_id,
            'program_reward_id' => $programRewardId,
            'claimed_at' => now(),
        ]);

        // Add reward to user's inventory
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        if ($reward->isStubs()) {
            $user->addStubs($reward->reward_amount);
            Log::info("Stubs awarded", [
                'user_id' => $userId,
                'amount' => $reward->reward_amount,
                'new_balance' => $user->fresh()->stubs_balance,
            ]);
        } elseif ($reward->isPlayer() && $reward->reward_id) {
            // Add player card to user's collection
            $userCard = UserCard::firstOrCreate(
                ['user_id' => $userId, 'player_id' => $reward->reward_id],
                ['obtained_at' => now(), 'locked' => false, 'count' => 0]
            );
            $userCard->increment('count');
        } elseif ($reward->isPack() && $reward->reward_id) {
            // Add pack to user's inventory
            $packCount = $reward->reward_amount ?? 1; // Default to 1 if NULL
            for ($i = 0; $i < $packCount; $i++) {
                UserPack::create([
                    'user_id' => $userId,
                    'pack_id' => $reward->reward_id,
                    'source' => 'reward',
                    'opened' => false,
                    'obtained_at' => now(),
                ]);
            }
            Log::info("Pack(s) added to inventory", [
                'user_id' => $userId,
                'pack_id' => $reward->reward_id,
                'quantity' => $reward->reward_amount,
            ]);
        }

        return true;
    }

    /**
     * Check for and automatically claim any newly unlocked rewards.
     */
    public function checkAndAwardRewards(int $userId, int $programId): array
    {
        $rewards = [];

        $program = Program::with(['rewards.player', 'rewards.pack'])->find($programId);
        if (!$program) {
            return $rewards;
        }

        $userProgram = UserProgram::where('user_id', $userId)
            ->where('program_id', $programId)
            ->first();

        if (!$userProgram) {
            return $rewards;
        }

        // Get all rewards that haven't been claimed yet
        $claimedRewardIds = UserProgramReward::where('user_id', $userId)
            ->where('program_id', $programId)
            ->pluck('program_reward_id')
            ->toArray();

        $unclaimedRewards = $program->rewards->whereNotIn('id', $claimedRewardIds);

        foreach ($unclaimedRewards as $reward) {
            // Check if user meets the requirement for this reward
            $meetsRequirement = false;

            if ($program->isXpProgram() && $reward->xp_threshold !== null) {
                $meetsRequirement = $userProgram->current_xp >= $reward->xp_threshold;
            } elseif ($program->isStarProgram() && $reward->stars_threshold !== null) {
                $meetsRequirement = $userProgram->current_stars >= $reward->stars_threshold;
            }

            if ($meetsRequirement && $reward->isAvailable()) {
                // Automatically claim the reward
                $claimed = $this->claimReward($userId, $reward->id);

                if ($claimed) {
                    $rewardData = [
                        'program_id' => $program->id,
                        'program_name' => $program->name,
                        'reward_id' => $reward->id,
                        'reward_type' => $reward->reward_type,
                        'description' => $reward->description,
                    ];

                    // Add type-specific data
                    if ($reward->isStubs()) {
                        $rewardData['amount'] = $reward->reward_amount;
                    } elseif ($reward->isPlayer()) {
                        $rewardData['player_id'] = $reward->reward_id;
                        $rewardData['player'] = $reward->player;
                    } elseif ($reward->isPack()) {
                        $rewardData['pack_id'] = $reward->reward_id;
                        $rewardData['pack'] = $reward->pack;
                        $rewardData['quantity'] = $reward->reward_amount ?? 1;
                    }

                    $rewards[] = $rewardData;
                }
            }
        }

        return $rewards;
    }

    /**
     * Initialize or get existing user program progress.
     */
    public function initializeUserProgram(int $userId, int $programId): UserProgram
    {
        return UserProgram::firstOrCreate(
            ['user_id' => $userId, 'program_id' => $programId],
            [
                'current_xp' => 0,
                'current_stars' => 0,
                'completed' => false,
            ]
        );
    }
}
