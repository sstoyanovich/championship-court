<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class GetTopPerformers extends Command
{
    protected $signature = 'stats:top-performers 
                            {start_date : Start date (YYYY-MM-DD)}
                            {end_date : End date (YYYY-MM-DD)}
                            {--limit=50 : Number of top performers to return}
                            {--sort=points : Sort by: points, rebounds, assists, games, or composite}
                            {--output=table : Output format: table, json, or csv}';

    protected $description = 'Get top performing NBA players between two dates based on real NBA stats';

    public function handle()
    {
        $startDate = $this->argument('start_date');
        $endDate = $this->argument('end_date');
        $limit = (int) $this->option('limit');
        $sortBy = $this->option('sort');
        $outputFormat = $this->option('output');

        // Validate dates
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } catch (\Exception $e) {
            $this->error('Invalid date format. Please use YYYY-MM-DD format.');
            return Command::FAILURE;
        }

        if ($start->isAfter($end)) {
            $this->error('Start date must be before end date.');
            return Command::FAILURE;
        }

        $this->info("Fetching top performers from {$start->format('Y-m-d')} to {$end->format('Y-m-d')}...");
        $this->info("Using real NBA stats from the NBA API");
        $this->newLine();

        // Check if Python script exists
        $scriptPath = base_path('scripts/get_top_performers.py');
        if (!file_exists($scriptPath)) {
            $this->error("Python script not found at: {$scriptPath}");
            return Command::FAILURE;
        }

        // Check for virtual environment
        $venvPath = base_path('scripts/venv');
        $pythonCmd = 'python3';
        
        if (is_dir($venvPath)) {
            // Use virtual environment Python
            $pythonCmd = $venvPath . '/bin/python';
            $this->info('Using virtual environment Python');
        } else {
            // Check if nba_api is installed in system Python
            $this->info('Checking Python dependencies...');
            $checkCmd = 'python3 -c "import nba_api" 2>&1';
            exec($checkCmd, $output, $returnCode);

            if ($returnCode !== 0) {
                $this->error('nba_api package not found!');
                $this->info('Please create a virtual environment:');
                $this->info('  cd backend/scripts && python3 -m venv venv');
                $this->info('  source venv/bin/activate');
                $this->info('  pip install -r requirements.txt');
                return Command::FAILURE;
            }
        }
        
        $this->info('✓ Python dependencies OK');
        $this->newLine();

        // Run the Python script
        $this->info('Fetching player stats from NBA API...');
        $this->info('This may take several minutes due to API rate limiting...');
        $this->newLine();

        $command = sprintf(
            'cd %s && %s get_top_performers.py %s %s %d %s',
            escapeshellarg(base_path('scripts')),
            escapeshellarg($pythonCmd),
            escapeshellarg($startDate),
            escapeshellarg($endDate),
            $limit,
            escapeshellarg($sortBy)
        );

        $process = proc_open($command, [
            1 => ['pipe', 'w'], // stdout (JSON only)
            2 => ['pipe', 'w'], // stderr (progress messages)
        ], $pipes);

        if (!is_resource($process)) {
            $this->error('Failed to execute Python script');
            return Command::FAILURE;
        }

        // Read output in real-time
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdoutBuffer = '';
        $stderrBuffer = '';

        while (true) {
            $stdout = fgets($pipes[1]);
            $stderr = fgets($pipes[2]);

            // Collect stdout (JSON) without displaying
            if ($stdout !== false) {
                $stdoutBuffer .= $stdout;
            }

            // Display stderr (progress messages) in real-time
            if ($stderr !== false) {
                $stderrBuffer .= $stderr;
                $this->line(trim($stderr));
            }

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            usleep(100000); // 0.1 seconds
        }

        // Read any remaining output
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
            $this->error("Python script failed with exit code: {$returnCode}");
            if (!empty($stderrBuffer)) {
                $this->error("Error output: " . trim($stderrBuffer));
            }
            return Command::FAILURE;
        }

        // Parse JSON output (only from stdout, stderr is separate)
        $stdoutBuffer = trim($stdoutBuffer);
        
        if (empty($stdoutBuffer)) {
            $this->warn('No data returned from Python script.');
            if (!empty($stderrBuffer)) {
                $this->line('Progress output: ' . trim($stderrBuffer));
            }
            return Command::SUCCESS;
        }

        $topPerformers = json_decode($stdoutBuffer, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Failed to parse JSON output from Python script: ' . json_last_error_msg());
            $this->error('Raw output: ' . substr($stdoutBuffer, 0, 500));
            return Command::FAILURE;
        }

        if (empty($topPerformers)) {
            $this->warn('No players found with games in the specified date range.');
            return Command::SUCCESS;
        }

        $this->newLine();
        
        // Output results
        switch ($outputFormat) {
            case 'json':
                $this->line(json_encode($topPerformers, JSON_PRETTY_PRINT));
                break;

            case 'csv':
                $this->outputCsv($topPerformers);
                break;

            case 'table':
            default:
                $this->outputTable($topPerformers, $sortBy);
                break;
        }

        $this->newLine();
        $this->info("✓ Found " . count($topPerformers) . " top performers");

        return Command::SUCCESS;
    }

    private function outputTable(array $performers, string $sortBy): void
    {
        $headers = [
            'Rank',
            'Player',
            'Team',
            'Pos',
            'Games',
            'PPG',
            'RPG',
            'APG',
            'SPG',
            'BPG',
            'Total Pts',
            'Total Reb',
            'Total Ast',
            'FG%',
            '3P%',
        ];

        if ($sortBy === 'composite') {
            $headers[] = 'Composite';
        }

        $rows = [];
        $rank = 1;

        foreach ($performers as $player) {
            $row = [
                $rank++,
                $player['player_name'] ?? 'N/A',
                $player['team'] ?? 'N/A',
                $player['position'] ?? 'N/A',
                $player['games'] ?? 0,
                $player['ppg'] ?? 0,
                $player['rpg'] ?? 0,
                $player['apg'] ?? 0,
                $player['spg'] ?? 0,
                $player['bpg'] ?? 0,
                $player['total_points'] ?? 0,
                $player['total_rebounds'] ?? 0,
                $player['total_assists'] ?? 0,
                ($player['fg_percentage'] ?? 0) . '%',
                ($player['3p_percentage'] ?? 0) . '%',
            ];

            if ($sortBy === 'composite') {
                $row[] = round($player['composite_score'] ?? 0, 1);
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);
    }

    private function outputCsv(array $performers): void
    {
        $headers = [
            'Rank',
            'Player Name',
            'Team',
            'Position',
            'Games',
            'PPG',
            'RPG',
            'APG',
            'SPG',
            'BPG',
            'Total Points',
            'Total Rebounds',
            'Total Assists',
            'Total Steals',
            'Total Blocks',
            'Total Turnovers',
            'FG%',
            '3P%',
            'Composite Score',
        ];

        $this->line(implode(',', $headers));

        $rank = 1;
        foreach ($performers as $player) {
            $row = [
                $rank++,
                '"' . ($player['player_name'] ?? 'N/A') . '"',
                '"' . ($player['team'] ?? 'N/A') . '"',
                '"' . ($player['position'] ?? 'N/A') . '"',
                $player['games'] ?? 0,
                $player['ppg'] ?? 0,
                $player['rpg'] ?? 0,
                $player['apg'] ?? 0,
                $player['spg'] ?? 0,
                $player['bpg'] ?? 0,
                $player['total_points'] ?? 0,
                $player['total_rebounds'] ?? 0,
                $player['total_assists'] ?? 0,
                $player['total_steals'] ?? 0,
                $player['total_blocks'] ?? 0,
                $player['total_turnovers'] ?? 0,
                $player['fg_percentage'] ?? 0,
                $player['3p_percentage'] ?? 0,
                round($player['composite_score'] ?? 0, 1),
            ];

            $this->line(implode(',', $row));
        }
    }
}
