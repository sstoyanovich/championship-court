<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportNbaPlayersFromStats extends Command
{
    protected $signature = 'nba:import-from-stats';
    protected $description = 'Import NBA players with stats-based ratings from Python script';

    public function handle()
    {
        $this->info('===========================================');
        $this->info('NBA Player Import (Stats-Based Ratings)');
        $this->info('===========================================');
        $this->newLine();

        // Check if Python script exists
        $scriptPath = base_path('scripts/generate_players_from_stats.py');
        if (!file_exists($scriptPath)) {
            $this->error("Python script not found at: {$scriptPath}");
            return 1;
        }

        // Check if nba_api is installed
        $this->info('Checking Python dependencies...');
        $checkCmd = 'python3 -c "import nba_api" 2>&1';
        exec($checkCmd, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('nba_api package not found!');
            $this->info('Please install it with: pip3 install nba-api');
            return 1;
        }
        $this->info('✓ Python dependencies OK');
        $this->newLine();

        // Run the Python script
        $this->info('Running Python script to fetch NBA data...');
        $this->info('This may take several minutes due to API rate limiting...');
        $this->newLine();

        $command = "cd " . base_path('scripts') . " && python3 generate_players_from_stats.py 2>&1";

        $process = proc_open($command, [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ], $pipes);

        if (is_resource($process)) {
            // Read output in real-time
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            while (true) {
                $stdout = fgets($pipes[1]);
                $stderr = fgets($pipes[2]);

                if ($stdout !== false) {
                    $this->line($stdout);
                }

                if ($stderr !== false) {
                    $this->line($stderr);
                }

                $status = proc_get_status($process);
                if (!$status['running']) {
                    break;
                }

                usleep(100000); // 0.1 seconds
            }

            // Read any remaining output
            while ($line = fgets($pipes[1])) {
                $this->line($line);
            }
            while ($line = fgets($pipes[2])) {
                $this->line($line);
            }

            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnCode = proc_close($process);

            if ($returnCode !== 0) {
                $this->error("Python script failed with exit code: {$returnCode}");
                return 1;
            }
        } else {
            $this->error('Failed to execute Python script');
            return 1;
        }

        // Check if JSON file was created
        $jsonPath = base_path('scripts/nba_players_from_stats.json');
        if (!file_exists($jsonPath)) {
            $this->error("Generated JSON file not found at: {$jsonPath}");
            return 1;
        }

        $this->newLine();
        $this->info('Importing players into database...');

        // Read and parse JSON
        $jsonContent = file_get_contents($jsonPath);
        $playersData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Failed to parse JSON: ' . json_last_error_msg());
            return 1;
        }

        // Clear existing players
        $this->info('Clearing existing player data...');
        Player::truncate();

        // Import players
        DB::beginTransaction();
        try {
            $imported = 0;
            foreach ($playersData as $playerData) {
                Player::create($playerData);
                $imported++;
            }

            DB::commit();

            $this->newLine();
            $this->info("✓ Successfully imported {$imported} players!");
            $this->newLine();

            // Show tier breakdown
            $tierCounts = Player::select('card_tier', DB::raw('count(*) as count'))
                ->groupBy('card_tier')
                ->pluck('count', 'card_tier');

            $this->info('Tier Breakdown:');
            $tierOrder = ['pink_diamond', 'diamond', 'amethyst', 'sapphire', 'emerald'];
            foreach ($tierOrder as $tier) {
                if (isset($tierCounts[$tier])) {
                    $tierName = str_replace('_', ' ', ucwords($tier, '_'));
                    $this->info("  {$tierName}: {$tierCounts[$tier]} players");
                }
            }

            $this->newLine();
            $this->info('===========================================');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Database import failed: ' . $e->getMessage());
            return 1;
        }
    }
}
