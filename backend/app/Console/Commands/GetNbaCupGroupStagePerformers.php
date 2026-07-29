<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class GetNbaCupGroupStagePerformers extends Command
{
    protected $signature = 'stats:nba-cup-group-stage 
                            {season : NBA season (e.g., 2025-26)}
                            {--limit=50 : Number of top performers to return}
                            {--sort=composite : Sort by: points, rebounds, assists, games, or composite}
                            {--output=table : Output format: table, json, or csv}
                            {--html-file= : Path to HTML file containing NBA Cup schedule}
                            {--game-ids=* : Specific game IDs (space-separated)}';

    protected $description = 'Get top performing NBA players for NBA Cup group stage games only (excludes knockout stage)';

    public function handle()
    {
        $season = $this->argument('season');
        $limit = (int) $this->option('limit');
        $sortBy = $this->option('sort');
        $outputFormat = $this->option('output');
        $htmlFile = $this->option('html-file');
        $gameIds = $this->option('game-ids');

        $this->info("NBA Cup Group Stage Top Performers - {$season}");
        $this->info("Sort by: {$sortBy}, Limit: {$limit}");
        $this->newLine();

        // Check if Python script exists
        $scriptPath = base_path('scripts/get_nba_cup_group_stage_performers.py');
        if (!file_exists($scriptPath)) {
            $this->error("Python script not found at: {$scriptPath}");
            return Command::FAILURE;
        }

        // Check for virtual environment
        $venvPath = base_path('scripts/venv');
        $pythonCmd = 'python3';
        
        if (is_dir($venvPath)) {
            $pythonCmd = $venvPath . '/bin/python';
            $this->info('Using virtual environment Python');
        } else {
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

        // Build command
        $command = sprintf(
            'cd %s && %s get_nba_cup_group_stage_performers.py %s --limit=%d --sort=%s',
            escapeshellarg(base_path('scripts')),
            escapeshellarg($pythonCmd),
            escapeshellarg($season),
            $limit,
            escapeshellarg($sortBy)
        );

        // Add HTML file if provided
        if (!empty($htmlFile)) {
            // Resolve path - if relative, make it relative to project root
            if (!str_starts_with($htmlFile, '/')) {
                // Try relative to project root first
                $projectRootPath = base_path($htmlFile);
                if (file_exists($projectRootPath)) {
                    $htmlFile = $projectRootPath;
                } elseif (file_exists($htmlFile)) {
                    // File exists as-is (relative to current working directory)
                    $htmlFile = realpath($htmlFile);
                } else {
                    $this->error("HTML file not found: {$htmlFile}");
                    $this->info("Tried: " . base_path($htmlFile));
                    return Command::FAILURE;
                }
            } elseif (!file_exists($htmlFile)) {
                $this->error("HTML file not found: {$htmlFile}");
                return Command::FAILURE;
            }
            $command .= ' --html-file ' . escapeshellarg($htmlFile);
        }

        // Add game IDs if provided
        if (!empty($gameIds)) {
            foreach ($gameIds as $gameId) {
                $command .= ' --game-ids ' . escapeshellarg($gameId);
            }
        }

        $this->info('Fetching NBA Cup group stage stats from NBA API...');
        $this->newLine();

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

            if ($stdout !== false) {
                $stdoutBuffer .= $stdout;
            }

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

        // Parse JSON output
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
            $this->warn('No players found with games in NBA Cup group stage.');
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
