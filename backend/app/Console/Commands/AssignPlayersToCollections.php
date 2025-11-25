<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\Collection;
use Illuminate\Console\Command;

class AssignPlayersToCollections extends Command
{
    protected $signature = 'collections:assign-players';
    protected $description = 'Assign players to their team collections based on the team field';

    public function handle()
    {
        $this->info('===========================================');
        $this->info('Assigning Players to Collections');
        $this->info('===========================================');
        $this->newLine();

        // Get all Live Series collections (team collections)
        $collections = Collection::where('name', 'Live Series')
            ->where('active', true)
            ->get();

        if ($collections->isEmpty()) {
            $this->error('No collections found! Please run the LiveSeriesCollectionsSeeder first.');
            $this->info('Run: php artisan db:seed --class=LiveSeriesCollectionsSeeder');
            return 1;
        }

        $this->info("Found {$collections->count()} team collections");
        $this->newLine();

        // Create a mapping of team names to collection IDs
        $teamToCollectionId = [];
        foreach ($collections as $collection) {
            $teamToCollectionId[$collection->sub_collection] = $collection->id;
        }

        // Get all players
        $players = Player::all();

        if ($players->isEmpty()) {
            $this->error('No players found in the database!');
            $this->info('Please import players first using one of these commands:');
            $this->info('  php artisan nba:scrape-players');
            $this->info('  php artisan nba:import-from-stats');
            $this->info('  php artisan nba:generate-players');
            return 1;
        }

        $this->info("Found {$players->count()} players to assign");
        $this->newLine();

        $assigned = 0;
        $notFound = 0;
        $missingTeams = [];

        $bar = $this->output->createProgressBar($players->count());
        $bar->start();

        foreach ($players as $player) {
            $bar->advance();

            // Skip if already assigned
            if ($player->collection_id) {
                continue;
            }

            $team = $player->team;

            // Check if we have a collection for this team
            if (isset($teamToCollectionId[$team])) {
                $player->collection_id = $teamToCollectionId[$team];
                $player->save();
                $assigned++;
            } else {
                $notFound++;
                if (!in_array($team, $missingTeams)) {
                    $missingTeams[] = $team;
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Successfully assigned {$assigned} players to collections!");

        if ($notFound > 0) {
            $this->newLine();
            $this->warn("⚠ {$notFound} players could not be assigned (team not found in collections)");
            $this->warn('Missing teams: ' . implode(', ', $missingTeams));
        }

        $this->newLine();

        // Update total_items count for each collection
        $this->info('Updating collection totals...');
        foreach ($collections as $collection) {
            $count = Player::where('collection_id', $collection->id)->count();
            $collection->total_items = $count;
            $collection->save();
        }

        $this->info('✓ Collection totals updated!');
        $this->newLine();

        // Show summary by team
        $this->info('Players per team:');
        foreach ($collections as $collection) {
            $count = Player::where('collection_id', $collection->id)->count();
            if ($count > 0) {
                $this->info("  {$collection->sub_collection}: {$count} players");
            }
        }

        $this->newLine();
        $this->info('===========================================');

        return 0;
    }
}
