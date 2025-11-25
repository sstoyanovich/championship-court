<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramChallenge;
use Illuminate\Support\Facades\DB;

class CreateTeamAffinityPrograms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'programs:create-team-affinity {--fresh : Delete existing TA programs first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Team Affinity programs for all 30 NBA teams';

    /**
     * All 30 NBA teams organized by division
     */
    private array $teams = [
        // Atlantic Division
        'Boston Celtics',
        'Brooklyn Nets',
        'New York Knicks',
        'Philadelphia 76ers',
        'Toronto Raptors',

        // Central Division
        'Chicago Bulls',
        'Cleveland Cavaliers',
        'Detroit Pistons',
        'Indiana Pacers',
        'Milwaukee Bucks',

        // Southeast Division
        'Atlanta Hawks',
        'Charlotte Hornets',
        'Miami Heat',
        'Orlando Magic',
        'Washington Wizards',

        // Northwest Division
        'Denver Nuggets',
        'Minnesota Timberwolves',
        'Oklahoma City Thunder',
        'Portland Trail Blazers',
        'Utah Jazz',

        // Pacific Division
        'Golden State Warriors',
        'LA Clippers',
        'Los Angeles Lakers',
        'Phoenix Suns',
        'Sacramento Kings',

        // Southwest Division
        'Dallas Mavericks',
        'Houston Rockets',
        'Memphis Grizzlies',
        'New Orleans Pelicans',
        'San Antonio Spurs',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::beginTransaction();

        try {
            // Check if --fresh flag is set
            if ($this->option('fresh')) {
                $this->info('Deleting existing Team Affinity programs...');
                Program::where('category', 'team_affinity')->delete();
            }

            $this->info('Creating Team Affinity programs for all 30 NBA teams...');
            $progressBar = $this->output->createProgressBar(count($this->teams));
            $progressBar->start();

            foreach ($this->teams as $team) {
                $this->createTeamProgram($team);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            DB::commit();

            $this->info('✓ Successfully created Team Affinity programs for all 30 teams!');
            $this->info('Note: Player card rewards are set to NULL and need to be configured in the admin panel.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to create Team Affinity programs: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Create a Team Affinity program for a specific team
     */
    private function createTeamProgram(string $teamName): void
    {
        // Check if program already exists
        $existing = Program::where('team', $teamName)
            ->where('category', 'team_affinity')
            ->first();

        if ($existing) {
            $this->warn("\nSkipping {$teamName} - already exists");
            return;
        }

        // Create the program
        $program = Program::create([
            'name' => "{$teamName} Team Affinity",
            'description' => "Complete challenges and earn rewards for {$teamName}. This year-long journey celebrates the history and players of {$teamName}.",
            'type' => 'star',
            'team' => $teamName,
            'category' => 'team_affinity',
            'stars_required' => 365,
            'active' => true,
            'image_url' => null, // Admin can set team logo later
        ]);

        // Create detailed rewards
        $this->createDetailedRewards($program);

        // Create detailed challenges
        $this->createDetailedChallenges($program, $teamName);
    }

    /**
     * Create detailed rewards for a program based on the example structure
     */
    private function createDetailedRewards(Program $program): void
    {
        $rewards = [
            // Gold 78 OVR
            ['stars' => 5, 'type' => 'player', 'id' => null, 'amount' => null, 'desc' => 'Gold 78 OVR Team Affinity Player'],
            // Standard Pack
            ['stars' => 10, 'type' => 'pack', 'id' => null, 'amount' => null, 'desc' => 'Standard Pack'],
            // 1000 stubs
            ['stars' => 15, 'type' => 'stubs', 'id' => null, 'amount' => 1000, 'desc' => '1,000 Stubs'],
            // 2x Standard Pack
            ['stars' => 20, 'type' => 'pack', 'id' => null, 'amount' => 2, 'desc' => '2x Standard Pack'],
            // Emerald 84 OVR player
            ['stars' => 25, 'type' => 'player', 'id' => null, 'amount' => null, 'desc' => 'Emerald 84 OVR Team Affinity Player'],
            // 4500 XP
            ['stars' => 30, 'type' => 'xp', 'id' => null, 'amount' => 4500, 'desc' => '4,500 Season XP'],
            // 2x Standard Pack
            ['stars' => 35, 'type' => 'pack', 'id' => null, 'amount' => 2, 'desc' => '2x Standard Pack'],
            // Team specific Pack
            ['stars' => 40, 'type' => 'pack', 'id' => null, 'amount' => null, 'desc' => 'Team Specific Pack'],
            // Premium Pack
            ['stars' => 45, 'type' => 'pack', 'id' => null, 'amount' => null, 'desc' => 'Premium Pack'],
            // 5000 XP
            ['stars' => 50, 'type' => 'xp', 'id' => null, 'amount' => 5000, 'desc' => '5,000 Season XP'],
            // Sapphire 85 OVR player
            ['stars' => 55, 'type' => 'player', 'id' => null, 'amount' => null, 'desc' => 'Sapphire 85 OVR Team Affinity Player'],
            // 1000 stubs
            ['stars' => 65, 'type' => 'stubs', 'id' => null, 'amount' => 1000, 'desc' => '1,000 Stubs'],
            // 3x Standard Pack
            ['stars' => 75, 'type' => 'pack', 'id' => null, 'amount' => 3, 'desc' => '3x Standard Pack'],
            // Premium Pack
            ['stars' => 90, 'type' => 'pack', 'id' => null, 'amount' => null, 'desc' => 'Premium Pack'],
            // 1000 stubs
            ['stars' => 105, 'type' => 'stubs', 'id' => null, 'amount' => 1000, 'desc' => '1,000 Stubs'],
            // 5x Standard Pack
            ['stars' => 120, 'type' => 'pack', 'id' => null, 'amount' => 5, 'desc' => '5x Standard Pack'],
            // Amethyst 91 OVR player
            ['stars' => 135, 'type' => 'player', 'id' => null, 'amount' => null, 'desc' => 'Amethyst 91 OVR Team Affinity Player'],
            // 1000 stubs
            ['stars' => 165, 'type' => 'stubs', 'id' => null, 'amount' => 1000, 'desc' => '1,000 Stubs'],
            // 3x Team specific Pack
            ['stars' => 195, 'type' => 'pack', 'id' => null, 'amount' => 3, 'desc' => '3x Team Specific Pack'],
            // 5x Standard Pack
            ['stars' => 215, 'type' => 'pack', 'id' => null, 'amount' => 5, 'desc' => '5x Standard Pack'],
            // 5x Team specific Pack
            ['stars' => 235, 'type' => 'pack', 'id' => null, 'amount' => 5, 'desc' => '5x Team Specific Pack'],
            // 1000 stubs
            ['stars' => 255, 'type' => 'stubs', 'id' => null, 'amount' => 1000, 'desc' => '1,000 Stubs'],
            // Pink Diamond 96 OVR player
            ['stars' => 285, 'type' => 'player', 'id' => null, 'amount' => null, 'desc' => 'Pink Diamond 96 OVR Team Affinity Player'],
            // 5x Team specific Pack
            ['stars' => 315, 'type' => 'pack', 'id' => null, 'amount' => 5, 'desc' => '5x Team Specific Pack'],
            // Pink Diamond 99 OVR player
            ['stars' => 330, 'type' => 'player', 'id' => null, 'amount' => null, 'desc' => 'Pink Diamond 99 OVR Team Affinity Player'],
            // Deluxe Choice Pack
            ['stars' => 345, 'type' => 'pack', 'id' => null, 'amount' => null, 'desc' => 'Deluxe Choice Pack'],
            // Pink Diamond 99 OVR player (Final)
            ['stars' => 365, 'type' => 'player', 'id' => null, 'amount' => null, 'desc' => 'Pink Diamond 99 OVR Team Affinity Player (Final)'],
        ];

        foreach ($rewards as $reward) {
            ProgramReward::create([
                'program_id' => $program->id,
                'xp_threshold' => null,
                'stars_threshold' => $reward['stars'],
                'reward_type' => $reward['type'],
                'reward_id' => $reward['id'],
                'reward_amount' => $reward['amount'],
                'description' => $reward['desc'],
            ]);
        }
    }

    /**
     * Create detailed challenges for a program
     */
    private function createDetailedChallenges(Program $program, string $teamName): void
    {
        $challenges = [
            // Basic team stat challenges
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 100,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Score 100 points with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'assists',
                'target_value' => 25,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Record 25 assists with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'threes',
                'target_value' => 10,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Make 10 three-pointers with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'rebounds',
                'target_value' => 50,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Grab 50 rebounds with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'steals',
                'target_value' => 10,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Record 10 steals with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'blocks',
                'target_value' => 5,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Record 5 blocks with {$teamName} players",
            ],

            // PXP challenges with reward cards (admin needs to set player IDs)
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 180,
                'constraint_type' => 'player',
                'constraint_value' => null, // Admin sets Gold card player ID
                'stars_reward' => 10,
                'description' => "Earn 180 PXP with the TA Gold reward card (Admin: Set player in constraint_value)",
            ],
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 300,
                'constraint_type' => 'player',
                'constraint_value' => null, // Admin sets Emerald card player ID
                'stars_reward' => 10,
                'description' => "Earn 300 PXP with the TA Emerald reward card (Admin: Set player in constraint_value)",
            ],
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 500,
                'constraint_type' => 'player',
                'constraint_value' => null, // Admin sets Sapphire card player ID
                'stars_reward' => 10,
                'description' => "Earn 500 PXP with the TA Sapphire reward card (Admin: Set player in constraint_value)",
            ],
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 500,
                'constraint_type' => 'player',
                'constraint_value' => null, // Admin sets Amethyst card player ID
                'stars_reward' => 150,
                'description' => "Earn 500 PXP with the TA Amethyst reward card (Admin: Set player in constraint_value)",
            ],
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 500,
                'constraint_type' => 'player',
                'constraint_value' => null, // Admin sets first Pink Diamond card player ID
                'stars_reward' => 50,
                'description' => "Earn 500 PXP with the first TA Pink Diamond reward card (Admin: Set player in constraint_value)",
            ],

            // PXP challenges with any team card
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 1000,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Earn 1,000 PXP with any {$teamName} card",
            ],
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 2000,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Earn 2,000 PXP with any {$teamName} card",
            ],
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 4000,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 10,
                'description' => "Earn 4,000 PXP with any {$teamName} card",
            ],
            [
                'type' => 'pxp',
                'target_stat' => null,
                'target_value' => 8000,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 25,
                'description' => "Earn 8,000 PXP with any {$teamName} card",
            ],

            // Medium stat challenges
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 500,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Score 500 points with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'rebounds',
                'target_value' => 1200,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Grab 1,200 rebounds with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'assists',
                'target_value' => 250,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Record 250 assists with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'steals',
                'target_value' => 30,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Record 30 steals with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'blocks',
                'target_value' => 20,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Record 20 blocks with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'threes',
                'target_value' => 50,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 5,
                'description' => "Make 50 three-pointers with {$teamName} players",
            ],

            // Large stat challenges
            [
                'type' => 'stat',
                'target_stat' => 'points',
                'target_value' => 2000,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 15,
                'description' => "Score 2,000 points with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'rebounds',
                'target_value' => 1000,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 15,
                'description' => "Grab 1,000 rebounds with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'assists',
                'target_value' => 400,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 15,
                'description' => "Record 400 assists with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'steals',
                'target_value' => 100,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 15,
                'description' => "Record 100 steals with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'blocks',
                'target_value' => 50,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 15,
                'description' => "Record 50 blocks with {$teamName} players",
            ],
            [
                'type' => 'stat',
                'target_stat' => 'threes',
                'target_value' => 200,
                'constraint_type' => 'team',
                'constraint_value' => $teamName,
                'stars_reward' => 15,
                'description' => "Make 200 three-pointers with {$teamName} players",
            ],
        ];

        foreach ($challenges as $challenge) {
            ProgramChallenge::create([
                'program_id' => $program->id,
                'type' => $challenge['type'],
                'target_stat' => $challenge['target_stat'],
                'target_value' => $challenge['target_value'],
                'constraint_type' => $challenge['constraint_type'],
                'constraint_value' => $challenge['constraint_value'],
                'stars_reward' => $challenge['stars_reward'],
                'xp_reward' => 0,
                'description' => $challenge['description'],
            ]);
        }
    }
}
