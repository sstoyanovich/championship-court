<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;

class UpdatePlayerImages extends Command
{
    protected $signature = 'players:update-images';
    protected $description = 'Update existing players with NBA IDs and image URLs';

    public function handle()
    {
        $this->info('Updating player images...');
        $this->newLine();

        // Get all players
        $players = Player::whereNull('nba_id')->get();

        if ($players->isEmpty()) {
            $this->info('All players already have NBA IDs!');
            return 0;
        }

        $this->info("Fetching NBA player IDs from API...");

        // Fetch active NBA players
        $command = "cd " . base_path('scripts') . " && python3 -c \"
from nba_api.stats.static import players as nba_players
import json

all_players = nba_players.get_players()
# Create a mapping of name to player ID
player_map = {}
for p in all_players:
    player_map[p['full_name'].lower()] = p['id']

print(json.dumps(player_map))
\" 2>&1";

        $output = shell_exec($command);

        if (!$output) {
            $this->error('Failed to fetch NBA player data');
            return 1;
        }

        $playerMap = json_decode(trim($output), true);

        if (!$playerMap) {
            $this->error('Failed to parse NBA player data');
            return 1;
        }

        $this->info("Found " . count($playerMap) . " NBA players in database");
        $this->newLine();

        $updated = 0;
        $notFound = 0;

        $bar = $this->output->createProgressBar($players->count());
        $bar->start();

        foreach ($players as $player) {
            $bar->advance();

            $nameLower = strtolower($player->name);

            if (isset($playerMap[$nameLower])) {
                $nbaId = $playerMap[$nameLower];
                $player->nba_id = (string)$nbaId;
                // Use NBA CDN for player headshots
                $player->image_url = "https://cdn.nba.com/headshots/nba/latest/1040x760/{$nbaId}.png";
                $player->save();
                $updated++;
            } else {
                $notFound++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Updated {$updated} players with NBA IDs and images");

        if ($notFound > 0) {
            $this->warn("⚠ {$notFound} players not found in NBA database");
        }

        return 0;
    }
}
