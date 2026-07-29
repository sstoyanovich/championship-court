<?php

namespace App\Services;

use App\Models\Pack;
use App\Models\Player;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramChallenge;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SpotlightProgramGenerator
{
    protected PlayerMatchingService $playerMatchingService;
    protected SpotlightCardCreatorService $cardCreatorService;
    protected ChallengeGeneratorService $challengeGeneratorService;

    public function __construct(
        PlayerMatchingService $playerMatchingService,
        SpotlightCardCreatorService $cardCreatorService,
        ChallengeGeneratorService $challengeGeneratorService
    ) {
        $this->playerMatchingService = $playerMatchingService;
        $this->cardCreatorService = $cardCreatorService;
        $this->challengeGeneratorService = $challengeGeneratorService;
    }

    /**
     * Generate a Spotlight program with selected players.
     *
     * @param array $selectedPlayers Array of player stats arrays
     * @param int $maxOvr Maximum OVR for the week
     * @param Carbon $date Date for program naming
     * @param int|null $weekNumber Week number in month
     * @return Program
     */
    public function generateProgram(array $selectedPlayers, int $maxOvr, Carbon $date, ?int $weekNumber = null): Program
    {
        // Generate program name
        $programName = $this->generateProgramName($date, $weekNumber);

        // Create or update program
        $program = Program::firstOrCreate(
            ['name' => $programName],
            [
                'description' => "Complete challenges and earn stars to unlock exclusive {$programName} rewards",
                'type' => 'star',
                'stars_required' => 50,
                'category' => 'general',
                'active' => true,
                'image_url' => null,
            ]
        );

        if (!$program->wasRecentlyCreated) {
            $program->update([
                'description' => "Complete challenges and earn stars to unlock exclusive {$programName} rewards",
                'type' => 'star',
                'stars_required' => 50,
                'category' => 'general',
                'active' => true,
            ]);
        }

        // Delete existing rewards and challenges
        $program->rewards()->delete();
        $program->challenges()->delete();

        // Distribute players: 3 for program rewards (with OVR spacing), rest for pack
        // Calculate OVR for each player and sort
        $playersWithOvr = [];
        foreach ($selectedPlayers as $playerStats) {
            $ovr = $this->cardCreatorService->calculateOvrFromStats($playerStats, $maxOvr);
            $playersWithOvr[] = [
                'stats' => $playerStats,
                'ovr' => $ovr,
            ];
        }

        // Sort by OVR
        usort($playersWithOvr, function ($a, $b) {
            return $a['ovr'] <=> $b['ovr'];
        });

        // Select 3 players with OVR spacing: 1 low (bottom third), 1 mid (middle), 1 high (top third)
        $totalPlayers = count($playersWithOvr);
        $programIndices = [];
        if ($totalPlayers >= 3) {
            // Low OVR (bottom third) - index 0 or near start
            $programIndices[] = max(0, (int)floor($totalPlayers / 3) - 1);
            // Mid OVR (middle)
            $programIndices[] = (int)floor($totalPlayers / 2);
            // High OVR (top third) - near end
            $programIndices[] = min($totalPlayers - 1, (int)floor($totalPlayers * 2 / 3));
        } else {
            $programIndices = [0, min(1, $totalPlayers - 1), min(2, $totalPlayers - 1)];
        }

        $programPlayers = [];
        $packPlayers = [];
        
        foreach ($playersWithOvr as $index => $playerData) {
            if (in_array($index, $programIndices) && count($programPlayers) < 3) {
                $programPlayers[] = $playerData['stats'];
            } else {
                $packPlayers[] = $playerData['stats'];
            }
        }

        // Create player cards
        $createdProgramPlayers = [];
        $createdPackPlayers = [];

        foreach ($programPlayers as $playerStats) {
            $player = $this->createPlayerCard($playerStats, $maxOvr, 'spotlight');
            $createdProgramPlayers[] = $player;
        }

        foreach ($packPlayers as $playerStats) {
            // Mix of Spotlight and Topps Now for pack
            $cardType = rand(0, 1) === 0 ? 'spotlight' : 'topps_now';
            $player = $this->createPlayerCard($playerStats, $maxOvr, $cardType);
            $createdPackPlayers[] = $player;
        }

        // Create pack
        $pack = $this->createPack($programName, $createdPackPlayers);

        // Create rewards
        $this->createRewards($program, $createdProgramPlayers, $pack);

        // Create challenges
        $this->createChallenges($program, $createdProgramPlayers, $createdPackPlayers);

        return $program;
    }

    /**
     * Generate program name based on date and week number.
     *
     * @param Carbon $date
     * @param int|null $weekNumber
     * @return string
     */
    private function generateProgramName(Carbon $date, ?int $weekNumber = null): string
    {
        $monthName = $date->format('F');
        
        if ($weekNumber !== null) {
            return "{$monthName} Spotlight {$weekNumber}";
        }

        // Auto-determine week number
        $weekInMonth = ceil($date->day / 7);
        $weekInMonth = min(4, max(1, $weekInMonth));
        
        return "{$monthName} Spotlight {$weekInMonth}";
    }

    /**
     * Create a player card.
     *
     * @param array $playerStats
     * @param int $maxOvr
     * @param string $cardType 'spotlight' or 'topps_now'
     * @return Player
     */
    private function createPlayerCard(array $playerStats, int $maxOvr, string $cardType): Player
    {
        // Calculate OVR
        $targetOvr = $this->cardCreatorService->calculateOvrFromStats($playerStats, $maxOvr);

        // Find base player if exists
        $playerName = $playerStats['player_name'] ?? 'Unknown';
        $basePlayer = $this->playerMatchingService->findExistingPlayer($playerName);

        // Create card
        if ($cardType === 'spotlight') {
            return $this->cardCreatorService->createSpotlightCard($playerStats, $targetOvr, $basePlayer);
        } else {
            return $this->cardCreatorService->createToppsNowCard($playerStats, $targetOvr, $basePlayer);
        }
    }

    /**
     * Create pack for Spotlight program.
     *
     * @param string $programName
     * @param array $players
     * @return Pack
     */
    private function createPack(string $programName, array $players): Pack
    {
        $packName = str_replace('Spotlight', 'Spotlight Pack', $programName);
        
        $playerIds = array_map(function ($player) {
            return $player->id;
        }, $players);

        $pack = Pack::firstOrCreate(
            ['name' => $packName],
            [
                'description' => "{$packName} - Contains Spotlight and Topps Now cards",
                'type' => 'standard',
                'card_count' => count($players),
                'specified_players' => $playerIds,
                'cost' => 0,
                'active' => true,
                'available_in_shop' => false,
            ]
        );

        if (!$pack->wasRecentlyCreated) {
            $pack->update([
                'description' => "{$packName} - Contains Spotlight and Topps Now cards",
                'card_count' => count($players),
                'specified_players' => $playerIds,
            ]);
        }

        return $pack;
    }

    /**
     * Create program rewards.
     *
     * @param Program $program
     * @param array $programPlayers
     * @param Pack $pack
     * @return void
     */
    private function createRewards(Program $program, array $programPlayers, Pack $pack): void
    {
        $standardPack = Pack::find(1);
        $ballinPack = Pack::find(11);

        $rewards = [
            // 5 stars: Standard Pack
            [
                'stars' => 5,
                'type' => 'pack',
                'pack_id' => $standardPack ? $standardPack->id : null,
                'amount' => 1,
                'desc' => 'Standard Pack',
            ],
        ];

        // Add program reward players at 10, 20, and 50 stars
        $rewardStars = [10, 20, 50];
        foreach ($programPlayers as $index => $player) {
            if (isset($rewardStars[$index])) {
                $cardType = $this->getCardTypeLabel($player->card_art);
                $rewards[] = [
                    'stars' => $rewardStars[$index],
                    'type' => 'player',
                    'player_id' => $player->id,
                    'amount' => null,
                    'desc' => "{$player->overall_rating} {$cardType} {$player->name}",
                ];
            }
        }

        // Fill in remaining reward tiers
        $rewards[] = [
            'stars' => 15,
            'type' => 'pack',
            'pack_id' => $standardPack ? $standardPack->id : null,
            'amount' => 2,
            'desc' => '2x Standard Pack',
        ];

        $rewards[] = [
            'stars' => 25,
            'type' => 'pack',
            'pack_id' => $standardPack ? $standardPack->id : null,
            'amount' => 3,
            'desc' => '3x Standard Pack',
        ];

        $rewards[] = [
            'stars' => 30,
            'type' => 'pack',
            'pack_id' => $ballinPack ? $ballinPack->id : null,
            'amount' => 2,
            'desc' => '2x Ballin is a Habit Pack',
        ];

        $rewards[] = [
            'stars' => 35,
            'type' => 'pack',
            'pack_id' => $standardPack ? $standardPack->id : null,
            'amount' => 5,
            'desc' => '5x Standard Pack',
        ];

        $rewards[] = [
            'stars' => 40,
            'type' => 'program_xp',
            'program_id' => null,
            'amount' => 5000,
            'desc' => '5000 XP',
        ];

        $rewards[] = [
            'stars' => 45,
            'type' => 'pack',
            'pack_id' => $pack->id,
            'amount' => 1,
            'desc' => $pack->name,
        ];

        // Sort rewards by stars
        usort($rewards, function ($a, $b) {
            return $a['stars'] <=> $b['stars'];
        });

        // Create rewards
        foreach ($rewards as $reward) {
            ProgramReward::create([
                'program_id' => $program->id,
                'xp_threshold' => null,
                'stars_threshold' => $reward['stars'],
                'reward_type' => $reward['type'],
                'reward_id' => $reward['type'] === 'player' ? $reward['player_id'] : ($reward['type'] === 'pack' ? $reward['pack_id'] : ($reward['type'] === 'program_xp' ? $reward['program_id'] : null)),
                'reward_amount' => $reward['amount'],
                'description' => $reward['desc'],
                'available' => true,
            ]);
        }
    }

    /**
     * Create program challenges.
     *
     * @param Program $program
     * @param array $programPlayers
     * @param array $packPlayers
     * @return void
     */
    private function createChallenges(Program $program, array $programPlayers, array $packPlayers): void
    {
        $challenges = $this->challengeGeneratorService->generateChallenges($programPlayers, $packPlayers);

        foreach ($challenges as $challenge) {
            ProgramChallenge::create([
                'program_id' => $program->id,
                'type' => $challenge['type'],
                'target_stat' => $challenge['target_stat'],
                'target_value' => $challenge['target_value'],
                'constraint_type' => $challenge['constraint_type'],
                'constraint_value' => $challenge['constraint_value'],
                'stars_reward' => $challenge['stars_reward'],
                'xp_reward' => null,
                'description' => $challenge['description'],
            ]);
        }
    }

    /**
     * Get card type label from card_art.
     *
     * @param string|null $cardArt
     * @return string
     */
    private function getCardTypeLabel(?string $cardArt): string
    {
        if (str_contains($cardArt ?? '', 'spotlight')) {
            return 'Spotlight';
        } elseif (str_contains($cardArt ?? '', 'topps_now')) {
            return 'Topps Now';
        }
        return 'Card';
    }
}
