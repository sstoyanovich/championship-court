<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OcrService;
use App\Services\ProgramService;
use App\Models\GameSession;
use App\Models\Lineup;
use App\Models\LineupSlot;
use App\Models\UserCard;
use App\Models\UserPlayerStats;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    protected $ocrService;
    protected $programService;

    public function __construct(OcrService $ocrService, ProgramService $programService)
    {
        $this->ocrService = $ocrService;
        $this->programService = $programService;
    }

    /**
     * Upload and process a game screenshot (extraction only, no saving)
     */
    public function uploadScreenshot(Request $request)
    {
        $request->validate([
            'screenshot' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // Max 10MB
            'lineup_id' => 'nullable|exists:lineups,id',
        ]);

        try {
            // Store the uploaded image
            $image = $request->file('screenshot');
            
            if (!$image || !$image->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image file. Please upload a valid image (JPEG, PNG, JPG, or GIF).',
                ], 400);
            }
            
            $imagePath = $image->store('screenshots', 'public');
            $fullPath = storage_path('app/public/' . $imagePath);

            // Verify the file was stored successfully
            if (!file_exists($fullPath)) {
                Log::error('Image file not found after storage', ['path' => $fullPath]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save image file. Please try again.',
                ], 500);
            }

            // Extract text using OCR
            $ocrText = $this->ocrService->extractTextFromImage($fullPath);

            // Get lineup info if provided
            $lineup = null;
            $expectedPlayers = [];

            if ($request->lineup_id) {
                $lineup = Lineup::with('slots.userCard.player')->find($request->lineup_id);

                // Get list of player names from lineup for fuzzy matching
                if ($lineup) {
                    $expectedPlayers = $lineup->slots
                        ->map(fn($slot) => $slot->userCard->player->name ?? null)
                        ->filter()
                        ->toArray();
                }
            }

            // Parse stats from OCR text
            $playerStats = $this->ocrService->parseStatSheet($ocrText, $expectedPlayers);

            // Validate stats
            $validationErrors = [];
            foreach ($playerStats as $stats) {
                $errors = $this->ocrService->validateStats($stats);
                if (!empty($errors)) {
                    $validationErrors = array_merge($validationErrors, $errors);
                }
            }

            // Save game session (but don't update stats yet)
            $gameSession = GameSession::create([
                'user_id' => $request->user()->id,
                'lineup_id' => $request->lineup_id,
                'screenshot_path' => $imagePath,
                'raw_ocr_data' => [
                    'ocr_text' => $ocrText,
                    'parsed_stats' => $playerStats,
                ],
                'processed_at' => null, // Will be set when confirmed
            ]);

            // Match players (but don't save stats yet)
            $matchResults = $this->matchPlayersForReview($playerStats, $lineup, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Stats extracted successfully - please review before saving',
                'data' => [
                    'game_session_id' => $gameSession->id,
                    'screenshot_url' => Storage::url($imagePath),
                    'player_stats' => $playerStats,
                    'match_results' => $matchResults,
                    'validation_errors' => $validationErrors,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Screenshot processing failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Include more detailed error message in development
            $errorMessage = config('app.debug') 
                ? 'Failed to process screenshot: ' . $e->getMessage()
                : 'Failed to process screenshot. Please check that the image is valid and try again.';

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Confirm and save user-reviewed stats
     */
    public function confirmStats(Request $request)
    {
        $request->validate([
            'game_session_id' => 'required|exists:game_sessions,id',
            'player_stats' => 'required|array',
            'player_stats.*.player_name' => 'required|string',
            'player_stats.*.minutes' => 'required|integer|min:0|max:48',
            'player_stats.*.points' => 'required|integer|min:0',
            'player_stats.*.rebounds' => 'required|integer|min:0',
            'player_stats.*.assists' => 'required|integer|min:0',
            'player_stats.*.steals' => 'required|integer|min:0',
            'player_stats.*.blocks' => 'required|integer|min:0',
            'player_stats.*.turnovers' => 'required|integer|min:0',
            'player_stats.*.fgm' => 'required|integer|min:0',
            'player_stats.*.fga' => 'required|integer|min:0',
            'player_stats.*.tpm' => 'required|integer|min:0',
            'player_stats.*.tpa' => 'required|integer|min:0',
        ]);

        try {
            $gameSession = GameSession::findOrFail($request->game_session_id);

            // Verify the session belongs to the user
            if ($gameSession->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Verify session hasn't already been processed
            if ($gameSession->processed_at !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'This game session has already been processed',
                ], 400);
            }

            $lineup = $gameSession->lineup_id ? Lineup::with('slots.userCard.player')->find($gameSession->lineup_id) : null;
            $playerStats = $request->player_stats;

            // Add "threes" alias for tpm (for challenge compatibility)
            $playerStatsWithAliases = array_map(function ($stat) {
                $stat['threes'] = $stat['tpm'] ?? 0;  // Add alias
                return $stat;
            }, $playerStats);

            // Match players and update stats
            $matchResults = $this->matchAndUpdateStats($playerStats, $lineup, $request->user()->id);

            // Process programs after stats are matched
            $programResults = [];
            if (!empty($matchResults['matched'])) {
                try {
                    $programResults = $this->programService->processGameCompletion(
                        $request->user()->id,
                        $playerStatsWithAliases,  // Use version with aliases
                        $matchResults['matched']
                    );
                } catch (\Exception $e) {
                    Log::error('Program processing error: ' . $e->getMessage());
                    // Don't fail the whole request if program processing fails
                }
            }

            // Mark game session as processed
            $gameSession->update([
                'processed_at' => now(),
                'raw_ocr_data' => array_merge(
                    $gameSession->raw_ocr_data ?? [],
                    ['confirmed_stats' => $playerStats]
                ),
            ]);

            // Format rewards summary for better display
            $rewardsSummary = $this->formatRewardsSummary($programResults);

            return response()->json([
                'success' => true,
                'message' => 'Stats saved successfully',
                'data' => [
                    'match_results' => $matchResults,
                    'program_results' => $programResults,
                    'rewards_summary' => $rewardsSummary,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Stats confirmation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Match players for review (without saving stats)
     */
    private function matchPlayersForReview(array $playerStats, $lineup, $userId): array
    {
        $results = [
            'matched' => [],
            'unmatched' => [],
        ];

        foreach ($playerStats as $stats) {
            $matched = false;

            // Try to find matching user card/player
            if ($lineup) {
                // Match by lineup
                foreach ($lineup->slots as $slot) {
                    $player = $slot->userCard->player;

                    // Fuzzy match player name
                    similar_text(
                        strtolower($stats['player_name']),
                        strtolower($player->name),
                        $percent
                    );

                    if ($percent > 70) {
                        $results['matched'][] = [
                            'player_name' => $player->name,
                            'player_id' => $player->id,
                            'extracted_name' => $stats['player_name'],
                            'similarity' => round($percent, 2),
                            'user_card_id' => $slot->userCard->id,
                            'stats' => $stats,
                        ];

                        $matched = true;
                        break;
                    }
                }
            } else {
                // No lineup provided, try to match by name
                // Prioritize cards that are in lineups
                $searchName = $stats['player_name'];
                $nameParts = explode(' ', $searchName);

                if (count($nameParts) >= 2 && strlen($nameParts[0]) <= 2 && strpos($nameParts[0], '.') !== false) {
                    $lastName = $nameParts[count($nameParts) - 1];
                    $userCards = UserCard::with(['player', 'user'])
                        ->whereHas('player', function ($query) use ($lastName) {
                            $query->where('name', 'like', '%' . $lastName . '%');
                        })
                        ->where('user_id', $userId)
                        ->get();
                } else {
                    $userCards = UserCard::with(['player', 'user'])
                        ->whereHas('player', function ($query) use ($searchName) {
                            $query->where('name', 'like', '%' . $searchName . '%');
                        })
                        ->where('user_id', $userId)
                        ->get();
                }

                // Prioritize cards that are in lineups
                $userCards = $this->prioritizeLineupCards($userCards, $userId);

                foreach ($userCards as $userCard) {
                    $extractedLower = strtolower($stats['player_name']);
                    $playerLower = strtolower($userCard->player->name);

                    $extractedParts = explode(' ', $extractedLower);
                    $playerParts = explode(' ', $playerLower);

                    if (count($extractedParts) >= 2 && strlen($extractedParts[0]) <= 2 && strpos($extractedParts[0], '.') !== false) {
                        $extractedLastName = $extractedParts[count($extractedParts) - 1];
                        $playerLastName = $playerParts[count($playerParts) - 1];

                        if ($extractedLastName === $playerLastName) {
                            $results['matched'][] = [
                                'player_name' => $userCard->player->name,
                                'player_id' => $userCard->player->id,
                                'extracted_name' => $stats['player_name'],
                                'similarity' => 100.0,
                                'user_card_id' => $userCard->id,
                                'stats' => $stats,
                            ];

                            $matched = true;
                            break;
                        }
                    }

                    similar_text($extractedLower, $playerLower, $percent);

                    if ($percent > 70) {
                        $results['matched'][] = [
                            'player_name' => $userCard->player->name,
                            'player_id' => $userCard->player->id,
                            'extracted_name' => $stats['player_name'],
                            'similarity' => round($percent, 2),
                            'user_card_id' => $userCard->id,
                            'stats' => $stats,
                        ];

                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                $results['unmatched'][] = [
                    'extracted_name' => $stats['player_name'],
                    'stats' => $stats,
                ];
            }
        }

        return $results;
    }

    /**
     * Match parsed stats to user cards and update cumulative stats
     */
    private function matchAndUpdateStats(array $playerStats, $lineup, $userId): array
    {
        $results = [
            'matched' => [],
            'unmatched' => [],
        ];

        foreach ($playerStats as $stats) {
            $matched = false;

            // Try to find matching user card
            if ($lineup) {
                // Match by lineup
                foreach ($lineup->slots as $slot) {
                    $player = $slot->userCard->player;

                    // Fuzzy match player name
                    similar_text(
                        strtolower($stats['player_name']),
                        strtolower($player->name),
                        $percent
                    );

                    if ($percent > 70) { // 70% similarity threshold
                        // Update user player stats (not individual card)
                        $userPlayerStats = UserPlayerStats::firstOrCreate(
                            ['user_id' => $userId, 'player_id' => $player->id],
                            ['player_xp' => 0]
                        );
                        $userPlayerStats->addGameStats($stats);

                        $results['matched'][] = [
                            'player_name' => $player->name,
                            'extracted_name' => $stats['player_name'],
                            'similarity' => round($percent, 2),
                            'user_card_id' => $slot->userCard->id,
                            'player_id' => $player->id,
                            'stats' => $stats,
                        ];

                        $matched = true;
                        break;
                    }
                }
            } else {
                // No lineup provided, try to match by name to any user card
                // Prioritize cards that are in lineups
                $searchName = $stats['player_name'];
                $nameParts = explode(' ', $searchName);

                // If name looks abbreviated (e.g., "D. Sabonis"), use last name for search
                if (count($nameParts) >= 2 && strlen($nameParts[0]) <= 2 && strpos($nameParts[0], '.') !== false) {
                    $lastName = $nameParts[count($nameParts) - 1];
                    $userCards = UserCard::with(['player', 'user'])
                        ->whereHas('player', function ($query) use ($lastName) {
                            $query->where('name', 'like', '%' . $lastName . '%');
                        })
                        ->where('user_id', $userId)
                        ->get();
                } else {
                    $userCards = UserCard::with(['player', 'user'])
                        ->whereHas('player', function ($query) use ($searchName) {
                            $query->where('name', 'like', '%' . $searchName . '%');
                        })
                        ->where('user_id', $userId)
                        ->get();
                }

                // Prioritize cards that are in lineups
                $userCards = $this->prioritizeLineupCards($userCards, $userId);

                foreach ($userCards as $userCard) {
                    $extractedLower = strtolower($stats['player_name']);
                    $playerLower = strtolower($userCard->player->name);

                    // Check for last name match if abbreviated
                    $extractedParts = explode(' ', $extractedLower);
                    $playerParts = explode(' ', $playerLower);

                    if (count($extractedParts) >= 2 && strlen($extractedParts[0]) <= 2 && strpos($extractedParts[0], '.') !== false) {
                        $extractedLastName = $extractedParts[count($extractedParts) - 1];
                        $playerLastName = $playerParts[count($playerParts) - 1];

                        if ($extractedLastName === $playerLastName) {
                            // Perfect last name match
                            $userPlayerStats = UserPlayerStats::firstOrCreate(
                                ['user_id' => $userId, 'player_id' => $userCard->player->id],
                                ['player_xp' => 0]
                            );
                            $userPlayerStats->addGameStats($stats);

                            $results['matched'][] = [
                                'player_name' => $userCard->player->name,
                                'extracted_name' => $stats['player_name'],
                                'similarity' => 100.0,
                                'user_card_id' => $userCard->id,
                                'player_id' => $userCard->player->id,
                                'stats' => $stats,
                            ];

                            $matched = true;
                            break;
                        }
                    }

                    // Fall back to fuzzy matching
                    similar_text($extractedLower, $playerLower, $percent);

                    if ($percent > 70) {
                        $userPlayerStats = UserPlayerStats::firstOrCreate(
                            ['user_id' => $userId, 'player_id' => $userCard->player->id],
                            ['player_xp' => 0]
                        );
                        $userPlayerStats->addGameStats($stats);

                        $results['matched'][] = [
                            'player_name' => $userCard->player->name,
                            'extracted_name' => $stats['player_name'],
                            'similarity' => round($percent, 2),
                            'user_card_id' => $userCard->id,
                            'player_id' => $userCard->player->id,
                            'stats' => $stats,
                        ];

                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                $results['unmatched'][] = [
                    'extracted_name' => $stats['player_name'],
                    'stats' => $stats,
                ];
            }
        }

        return $results;
    }

    /**
     * Prioritize user cards that are in lineups over those that aren't
     * When a player has multiple cards, prefer the one in a lineup
     */
    private function prioritizeLineupCards($userCards, $userId)
    {
        // Get all card IDs that are in lineups for this user
        $cardsInLineups = LineupSlot::whereHas('lineup', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->pluck('user_card_id')->toArray();

        // Separate cards into those in lineups and those not
        $inLineup = [];
        $notInLineup = [];

        foreach ($userCards as $card) {
            if (in_array($card->id, $cardsInLineups)) {
                $inLineup[] = $card;
            } else {
                $notInLineup[] = $card;
            }
        }

        // Return cards in lineups first, then others
        return collect(array_merge($inLineup, $notInLineup));
    }

    /**
     * Get stats for a specific user card (now uses UserPlayerStats)
     */
    public function getCardStats(Request $request, $userCardId)
    {
        $userCard = UserCard::with('player')->findOrFail($userCardId);

        // Get stats from UserPlayerStats table
        $userPlayerStats = UserPlayerStats::where('user_id', $userCard->user_id)
            ->where('player_id', $userCard->player_id)
            ->first();

        if (!$userPlayerStats) {
            return response()->json([
                'success' => true,
                'data' => [
                    'player_name' => $userCard->player->name,
                    'player_xp' => 0,
                    'games_played' => 0,
                    'total_stats' => [],
                    'percentages' => [],
                    'averages' => [],
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'player_name' => $userCard->player->name,
                'player_xp' => $userPlayerStats->player_xp,
                'games_played' => $userPlayerStats->games_played,
                'total_stats' => [
                    'minutes' => $userPlayerStats->total_minutes,
                    'points' => $userPlayerStats->total_points,
                    'rebounds' => $userPlayerStats->total_rebounds,
                    'assists' => $userPlayerStats->total_assists,
                    'steals' => $userPlayerStats->total_steals,
                    'blocks' => $userPlayerStats->total_blocks,
                    'turnovers' => $userPlayerStats->total_turnovers,
                    'fgm' => $userPlayerStats->total_fgm,
                    'fga' => $userPlayerStats->total_fga,
                    '3pm' => $userPlayerStats->total_3pm,
                    '3pa' => $userPlayerStats->total_3pa,
                ],
                'percentages' => [
                    'fg_percentage' => $userPlayerStats->getFGPercentage(),
                    '3p_percentage' => $userPlayerStats->get3PPercentage(),
                ],
                'averages' => [
                    'ppg' => $userPlayerStats->getPPG(),
                    'rpg' => $userPlayerStats->getRPG(),
                    'apg' => $userPlayerStats->getAPG(),
                ],
            ],
        ]);
    }

    /**
     * Format rewards summary for display
     */
    private function formatRewardsSummary(array $programResults): array
    {
        $summary = [
            'total_xp' => $programResults['xp_earned'] ?? 0,
            'total_stubs' => 0,
            'players' => [],
            'packs' => [],
        ];

        if (isset($programResults['rewards_earned'])) {
            foreach ($programResults['rewards_earned'] as $reward) {
                switch ($reward['reward_type']) {
                    case 'stubs':
                        $summary['total_stubs'] += $reward['amount'] ?? 0;
                        break;

                    case 'player':
                        if (isset($reward['player'])) {
                            $summary['players'][] = [
                                'id' => $reward['player_id'],
                                'name' => $reward['player']['name'],
                                'overall_rating' => $reward['player']['overall_rating'],
                                'card_tier' => $reward['player']['card_tier'],
                                'image_url' => $reward['player']['image_url'],
                                'from_program' => $reward['program_name'],
                            ];
                        }
                        break;

                    case 'pack':
                        if (isset($reward['pack'])) {
                            $quantity = $reward['quantity'] ?? 1;
                            $summary['packs'][] = [
                                'id' => $reward['pack_id'],
                                'name' => $reward['pack']['name'],
                                'quantity' => $quantity,
                                'from_program' => $reward['program_name'],
                            ];
                        }
                        break;
                }
            }
        }

        return $summary;
    }
}
