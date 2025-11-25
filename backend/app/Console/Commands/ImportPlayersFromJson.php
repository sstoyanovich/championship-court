<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\Player;
use Illuminate\Console\Command;

class ImportPlayersFromJson extends Command
{
    protected $signature = 'nba:import-json {--batch=50 : Number of players to import per batch}';
    protected $description = 'Import NBA players from JSON file and assign to collections';

    public function handle()
    {
        $this->info('===============================================');
        $this->info('NBA Players JSON Import');
        $this->info('===============================================');
        $this->newLine();

        $jsonPath = base_path('../backend/scripts/nba_players_from_stats.json');

        if (!file_exists($jsonPath)) {
            $this->error('❌ JSON file not found: ' . $jsonPath);
            $this->warn('Please run the Python script first:');
            $this->warn('  cd backend/scripts && python3 generate_players_from_stats.py');
            return Command::FAILURE;
        }

        $this->info('📁 Reading player data from JSON...');
        $playersData = json_decode(file_get_contents($jsonPath), true);

        if (!$playersData) {
            $this->error('❌ Could not parse JSON file');
            return Command::FAILURE;
        }

        $totalPlayers = count($playersData);
        $this->info("✓ Found {$totalPlayers} players in JSON file");
        $this->newLine();

        // Get all collections for mapping
        $this->info('🗂️  Loading collections...');
        $collections = Collection::all()->keyBy('sub_collection');
        $this->info("✓ Loaded {$collections->count()} collections");
        $this->newLine();

        // Check which players are already imported
        $existingNbaIds = Player::pluck('nba_id')->toArray();
        $playersToImport = array_filter($playersData, function ($player) use ($existingNbaIds) {
            return !in_array($player['nba_id'], $existingNbaIds);
        });

        $alreadyImported = $totalPlayers - count($playersToImport);

        if ($alreadyImported > 0) {
            $this->warn("⚠️  {$alreadyImported} players already in database (skipping)");
        }

        if (empty($playersToImport)) {
            $this->info('✓ All players already imported!');
            return Command::SUCCESS;
        }

        $this->info("📥 Importing " . count($playersToImport) . " new players...");
        $this->newLine();

        $batchSize = (int) $this->option('batch');
        $imported = 0;
        $failed = 0;
        $noCollection = [];

        $bar = $this->output->createProgressBar(count($playersToImport));
        $bar->start();

        foreach ($playersToImport as $playerData) {
            try {
                // Find the collection for this player's team
                $collectionId = null;
                if (isset($collections[$playerData['team']])) {
                    $collectionId = $collections[$playerData['team']]->id;
                } else {
                    $noCollection[] = $playerData['name'] . ' (' . $playerData['team'] . ')';
                }

                // Create the player
                Player::create([
                    'name' => $playerData['name'],
                    'nba_id' => $playerData['nba_id'],
                    'overall_rating' => $playerData['overall_rating'],
                    'position' => $playerData['position'],
                    'team' => $playerData['team'],
                    'card_tier' => $playerData['card_tier'],
                    'image_url' => $playerData['image_url'],
                    'collection_id' => $collectionId,
                    // Category ratings
                    'outside_scoring' => $playerData['outside_scoring'],
                    'inside_scoring' => $playerData['inside_scoring'],
                    'defense' => $playerData['defense'],
                    'athleticism' => $playerData['athleticism'],
                    'playmaking' => $playerData['playmaking'],
                    'rebounding' => $playerData['rebounding'],
                    // Detailed attributes
                    'close_shot' => $playerData['close_shot'],
                    'mid_range_shot' => $playerData['mid_range_shot'],
                    'three_point_shot' => $playerData['three_point_shot'],
                    'free_throw' => $playerData['free_throw'],
                    'shot_iq' => $playerData['shot_iq'],
                    'offensive_consistency' => $playerData['offensive_consistency'],
                    'layup' => $playerData['layup'],
                    'standing_dunk' => $playerData['standing_dunk'],
                    'driving_dunk' => $playerData['driving_dunk'],
                    'post_hook' => $playerData['post_hook'],
                    'post_fade' => $playerData['post_fade'],
                    'post_control' => $playerData['post_control'],
                    'draw_foul' => $playerData['draw_foul'],
                    'hands' => $playerData['hands'],
                    'interior_defense' => $playerData['interior_defense'],
                    'perimeter_defense' => $playerData['perimeter_defense'],
                    'steal' => $playerData['steal'],
                    'block' => $playerData['block'],
                    'help_defense_iq' => $playerData['help_defense_iq'],
                    'pass_perception' => $playerData['pass_perception'],
                    'defensive_consistency' => $playerData['defensive_consistency'],
                    'speed' => $playerData['speed'],
                    'agility' => $playerData['agility'],
                    'strength' => $playerData['strength'],
                    'vertical' => $playerData['vertical'],
                    'stamina' => $playerData['stamina'],
                    'hustle' => $playerData['hustle'],
                    'overall_durability' => $playerData['overall_durability'],
                    'pass_accuracy' => $playerData['pass_accuracy'],
                    'ball_handle' => $playerData['ball_handle'],
                    'speed_with_ball' => $playerData['speed_with_ball'],
                    'pass_iq' => $playerData['pass_iq'],
                    'pass_vision' => $playerData['pass_vision'],
                    'offensive_rebound' => $playerData['offensive_rebound'],
                    'defensive_rebound' => $playerData['defensive_rebound'],
                    'intangibles' => $playerData['intangibles'],
                    'potential' => $playerData['potential'],
                ]);

                $imported++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to import {$playerData['name']}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('===============================================');
        $this->info('Import Summary');
        $this->info('===============================================');
        $this->info("✓ Successfully imported: {$imported} players");

        if ($failed > 0) {
            $this->warn("⚠️  Failed: {$failed} players");
        }

        if (!empty($noCollection)) {
            $this->warn("⚠️  {count($noCollection)} players without collection assignment:");
            foreach (array_slice($noCollection, 0, 5) as $player) {
                $this->warn("   - {$player}");
            }
            if (count($noCollection) > 5) {
                $this->warn("   ... and " . (count($noCollection) - 5) . " more");
            }
        }

        // Update collection totals
        $this->newLine();
        $this->info('📊 Updating collection totals...');
        foreach ($collections as $collection) {
            $total = Player::where('collection_id', $collection->id)->count();
            $collection->update(['total_items' => $total]);
        }
        $this->info('✓ Collection totals updated');

        // Show tier distribution
        $this->newLine();
        $this->info('📊 Tier Distribution:');
        $tierCounts = Player::selectRaw('card_tier, COUNT(*) as count')
            ->groupBy('card_tier')
            ->get()
            ->pluck('count', 'card_tier');

        $tierOrder = ['pink_diamond', 'diamond', 'amethyst', 'sapphire', 'emerald', 'gold', 'silver', 'bronze', 'common'];
        foreach ($tierOrder as $tier) {
            if (isset($tierCounts[$tier])) {
                $tierName = str_replace('_', ' ', ucwords($tier, '_'));
                $this->info("   {$tierName}: {$tierCounts[$tier]} players");
            }
        }

        $this->newLine();
        $this->info('===============================================');
        $this->info('✓ Import complete!');
        $this->info('===============================================');

        return Command::SUCCESS;
    }
}
