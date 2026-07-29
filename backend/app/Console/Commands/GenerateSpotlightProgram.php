<?php

namespace App\Console\Commands;

use App\Services\PlayerMatchingService;
use App\Services\SpotlightProgramGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateSpotlightProgram extends Command
{
    protected $signature = 'spotlight:generate 
                            {--max-ovr= : Maximum OVR for the week (required)}
                            {--start-date= : Start date for stats period (YYYY-MM-DD)}
                            {--end-date= : End date for stats period (YYYY-MM-DD)}
                            {--week-number= : Week number in month (for program naming)}
                            {--interactive : Interactive mode - shows top 15 candidates and allows manual removal (default)}
                            {--auto-confirm : Skip interactive selection and auto-generate with top candidates}
                            {--dry-run : Preview without creating database records}';

    protected $description = 'Generate a Spotlight program with top NBA performers';

    protected PlayerMatchingService $playerMatchingService;
    protected SpotlightProgramGenerator $programGenerator;

    public function __construct(
        PlayerMatchingService $playerMatchingService,
        SpotlightProgramGenerator $programGenerator
    ) {
        parent::__construct();
        $this->playerMatchingService = $playerMatchingService;
        $this->programGenerator = $programGenerator;
    }

    public function handle()
    {
        $maxOvr = $this->option('max-ovr');
        if (!$maxOvr) {
            $this->error('--max-ovr parameter is required');
            return Command::FAILURE;
        }

        $maxOvr = (int)$maxOvr;
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $weekNumber = $this->option('week-number') ? (int)$this->option('week-number') : null;
        $isInteractive = !$this->option('auto-confirm');
        $isDryRun = $this->option('dry-run');

        // Auto-calculate dates if not provided
        if (!$startDate || !$endDate) {
            $now = Carbon::now();
            $startDate = $now->copy()->startOfWeek()->format('Y-m-d');
            $endDate = $now->copy()->endOfWeek()->format('Y-m-d');
        }

        $this->info("Generating Spotlight Program");
        $this->info("Max OVR: {$maxOvr}");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->newLine();

        // Fetch top performers
        $this->info('Fetching top performers from NBA API...');
        $currentPerformers = $this->fetchTopPerformers($startDate, $endDate, 30);
        
        if (empty($currentPerformers)) {
            $this->error('No performers found for the specified date range');
            return Command::FAILURE;
        }

        // Fetch historical performers for the same month
        $currentMonth = Carbon::parse($startDate)->month;
        $this->info("Fetching historical performers for month {$currentMonth}...");
        $historicalPerformers = $this->fetchHistoricalPerformers($currentMonth, 20);
        
        if (empty($historicalPerformers)) {
            $this->warn("No historical performers found for month {$currentMonth}");
        } else {
            $this->info("Found " . count($historicalPerformers) . " historical performers");
        }

        // Combine and get candidate info
        $allPerformers = array_merge($currentPerformers, $historicalPerformers);
        $candidates = [];

        foreach ($allPerformers as $playerStats) {
            $candidateInfo = $this->playerMatchingService->getCandidateInfo($playerStats, $maxOvr);
            // Mark historical players
            if (isset($playerStats['historical']) && $playerStats['historical']) {
                $candidateInfo['historical'] = true;
            }
            $candidates[] = $candidateInfo;
        }

        // Sort by composite score
        usort($candidates, function ($a, $b) {
            return ($b['stats']['composite_score'] ?? 0) <=> ($a['stats']['composite_score'] ?? 0);
        });

        // Take top 40
        $topCandidates = array_slice($candidates, 0, 40);

        // Display candidates
        $this->displayCandidates($topCandidates, $maxOvr);

        // Interactive selection
        $selectedCandidates = $topCandidates;
        if ($isInteractive && !$isDryRun) {
            $selectedCandidates = $this->interactiveSelection($topCandidates);
        }

        if (count($selectedCandidates) < 9 || count($selectedCandidates) > 10) {
            $this->error('Must select between 9 and 10 players');
            return Command::FAILURE;
        }

        // Confirm before generating
        if (!$isDryRun && !$this->option('auto-confirm')) {
            if (!$this->confirm('Generate program with selected players?', true)) {
                $this->info('Cancelled');
                return Command::SUCCESS;
            }
        }

        if ($isDryRun) {
            $this->info('DRY RUN - Would generate program with:');
            foreach ($selectedCandidates as $candidate) {
                $this->line("  - {$candidate['player_name']} (OVR: {$candidate['recommended_ovr']})");
            }
            return Command::SUCCESS;
        }

        // Generate program
        try {
            DB::beginTransaction();

            $programDate = Carbon::parse($startDate);
            // Convert candidates back to player stats format
            $selectedPlayerStats = [];
            foreach ($selectedCandidates as $candidate) {
                // Find original player stats from allPerformers
                foreach ($allPerformers as $stats) {
                    if (($stats['player_name'] ?? '') === $candidate['player_name']) {
                        $selectedPlayerStats[] = $stats;
                        break;
                    }
                }
            }

            if (count($selectedPlayerStats) < 9) {
                $this->error('Could not find all selected players in performer data');
                return Command::FAILURE;
            }

            $program = $this->programGenerator->generateProgram(
                $selectedPlayerStats,
                $maxOvr,
                $programDate,
                $weekNumber
            );

            DB::commit();

            $this->newLine();
            $this->info("✓ Program created successfully!");
            $this->info("Program ID: {$program->id}");
            $this->info("Program Name: {$program->name}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error generating program: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Fetch top performers from Python script.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return array
     */
    private function fetchTopPerformers(string $startDate, string $endDate, int $limit): array
    {
        $scriptPath = base_path('scripts/get_top_performers.py');
        if (!file_exists($scriptPath)) {
            $this->error("Python script not found at: {$scriptPath}");
            return [];
        }

        $venvPath = base_path('scripts/venv');
        $pythonCmd = is_dir($venvPath) ? $venvPath . '/bin/python' : 'python3';

        $command = sprintf(
            'cd %s && %s get_top_performers.py %s %s --limit=%d --sort=composite',
            escapeshellarg(base_path('scripts')),
            escapeshellarg($pythonCmd),
            escapeshellarg($startDate),
            escapeshellarg($endDate),
            $limit
        );

        $process = proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            $this->error('Failed to execute Python script');
            return [];
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdoutBuffer = '';
        $stderrBuffer = '';

        while (true) {
            $stdout = fgets($pipes[1]);
            $stderr = fgets($pipes[2]);

            if ($stdout !== false) {
                $stdoutBuffer .= $stdout;
            }

            if ($stderr !== false) {
                $stderrBuffer .= $stderr;
            }

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            usleep(100000);
        }

        stream_set_blocking($pipes[1], true);
        stream_set_blocking($pipes[2], true);

        while ($line = fgets($pipes[1])) {
            $stdoutBuffer .= $line;
        }
        while ($line = fgets($pipes[2])) {
            $stderrBuffer .= $line;
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            $this->error("Python script failed with exit code: {$returnCode}");
            return [];
        }

        $stdoutBuffer = trim($stdoutBuffer);
        if (empty($stdoutBuffer)) {
            return [];
        }

        $performers = json_decode($stdoutBuffer, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Failed to parse JSON output: ' . json_last_error_msg());
            return [];
        }

        return $performers ?? [];
    }

    /**
     * Fetch historical performers for a specific month.
     *
     * @param int $month
     * @param int $limit
     * @return array
     */
    private function fetchHistoricalPerformers(int $month, int $limit): array
    {
        $scriptPath = base_path('scripts/get_top_performers.py');
        if (!file_exists($scriptPath)) {
            $this->warn('Python script not found for historical performers');
            return [];
        }

        $venvPath = base_path('scripts/venv');
        $pythonCmd = is_dir($venvPath) ? $venvPath . '/bin/python' : 'python3';

        $command = sprintf(
            'cd %s && %s get_top_performers.py --historical --month=%d --limit=%d --sort=composite --years-back=25',
            escapeshellarg(base_path('scripts')),
            escapeshellarg($pythonCmd),
            $month,
            $limit
        );

        $this->line("Running: {$command}");

        $process = proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            $this->warn('Failed to execute Python script for historical performers');
            return [];
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdoutBuffer = '';
        $stderrBuffer = '';

        while (true) {
            $stdout = fgets($pipes[1]);
            $stderr = fgets($pipes[2]);
            
            if ($stdout !== false) {
                $stdoutBuffer .= $stdout;
            }
            
            if ($stderr !== false) {
                $stderrBuffer .= $stderr;
                // Show progress messages
                $this->line(trim($stderr));
            }

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            usleep(100000);
        }

        stream_set_blocking($pipes[1], true);
        stream_set_blocking($pipes[2], true);
        
        while ($line = fgets($pipes[1])) {
            $stdoutBuffer .= $line;
        }
        while ($line = fgets($pipes[2])) {
            $stderrBuffer .= $line;
            $this->line(trim($line));
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            $this->warn("Python script exited with code {$returnCode}");
            if (!empty($stderrBuffer)) {
                $this->line("Error output: " . substr($stderrBuffer, 0, 500));
            }
            return [];
        }

        $stdoutBuffer = trim($stdoutBuffer);
        if (empty($stdoutBuffer)) {
            $this->warn('No data returned from historical performers script');
            return [];
        }

        $performers = json_decode($stdoutBuffer, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn('Failed to parse JSON: ' . json_last_error_msg());
            return [];
        }

        return $performers ?? [];
    }

    /**
     * Display candidates in a table.
     *
     * @param array $candidates
     * @param int $maxOvr
     * @return void
     */
    private function displayCandidates(array $candidates, int $maxOvr): void
    {
        $this->newLine();
        $this->info("Top " . count($candidates) . " Candidates for Spotlight Program (Max OVR: {$maxOvr})");
        $this->line(str_repeat('═', 100));

        $headers = ['#', 'Player', 'Team', 'Stats', 'Existing Cards', 'Yearly', 'Rec OVR', 'Warnings'];
        $rows = [];

        foreach ($candidates as $index => $candidate) {
            $warnings = [];
            if (in_array('yearly_limit', $candidate['warnings'] ?? [])) {
                $warnings[] = '⚠️ Limit';
            }
            if (in_array('similar_ovr', $candidate['warnings'] ?? [])) {
                $warnings[] = '⚠️ Similar';
            }
            if (isset($candidate['historical']) && $candidate['historical']) {
                $warnings[] = '📅 Historical';
            }

            $statsStr = sprintf(
                "%.1f PPG, %.1f RPG, %.1f APG",
                $candidate['stats']['ppg'] ?? 0,
                $candidate['stats']['rpg'] ?? 0,
                $candidate['stats']['apg'] ?? 0
            );

            $rows[] = [
                $index + 1,
                $candidate['player_name'],
                $candidate['team'],
                $statsStr,
                $candidate['existing_cards_summary'] ?? 'None',
                ($candidate['total_card_count'] ?? 0) . '/' . ($candidate['yearly_card_limit'] ?? 7),
                $candidate['recommended_ovr'] ?? 86,
                implode(' ', $warnings),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Interactive selection of candidates.
     *
     * @param array $candidates
     * @return array
     */
    private function interactiveSelection(array $candidates): array
    {
        // Find players with similar OVR warnings
        $similarOvrIndices = [];
        foreach ($candidates as $index => $candidate) {
            if (in_array('similar_ovr', $candidate['warnings'] ?? [])) {
                $similarOvrIndices[] = $index + 1; // +1 because display is 1-indexed
            }
        }

        $defaultInput = '';
        if (!empty($similarOvrIndices)) {
            $defaultInput = implode(',', $similarOvrIndices);
            $this->info('Players with similar OVR cards detected: ' . implode(', ', $similarOvrIndices));
        }

        $this->info('Enter numbers to remove (comma-separated), or press Enter to keep all:');
        
        $input = $this->ask('Remove players', $defaultInput);
        
        if (empty(trim($input))) {
            return $candidates;
        }

        $numbersToRemove = array_map('trim', explode(',', $input));
        $indicesToRemove = [];

        foreach ($numbersToRemove as $num) {
            $index = (int)$num - 1;
            if ($index >= 0 && $index < count($candidates)) {
                $indicesToRemove[] = $index;
            }
        }

        $selected = [];
        foreach ($candidates as $index => $candidate) {
            if (!in_array($index, $indicesToRemove)) {
                $selected[] = $candidate;
            }
        }

        $this->info('Selected ' . count($selected) . ' players');

        return $selected;
    }
}
