<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Player;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateLiveSeriesRostersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🏀 Updating Live Series Rosters...\n\n";

        // Read the roster data
        $rostersPath = base_path('scripts/live_series_rosters.json');

        if (!file_exists($rostersPath)) {
            echo "❌ Error: live_series_rosters.json not found at {$rostersPath}\n";
            return;
        }

        $rostersJson = file_get_contents($rostersPath);
        $rosters = json_decode($rostersJson, true);

        if (!$rosters) {
            echo "❌ Error: Failed to parse live_series_rosters.json\n";
            return;
        }

        $stats = [
            'created' => 0,
            'deleted' => 0,
        ];

        foreach ($rosters as $teamName => $players) {
            echo "📋 Processing {$teamName}...\n";

            // Find the collection for this team
            $collection = Collection::where('name', 'Live Series')
                ->where('type', 'team')
                ->where('sub_collection', $teamName)
                ->first();

            if (!$collection) {
                echo "  ⚠️  Collection not found for {$teamName}, skipping...\n";
                continue;
            }

            // Delete all existing players for this team (that are obtainable from packs)
            $deletedCount = Player::where('team', $teamName)
                ->where('collection_id', $collection->id)
                ->where('obtainable_from_packs', true)
                ->delete();

            if ($deletedCount > 0) {
                echo "  🗑️  Deleted {$deletedCount} old players\n";
                $stats['deleted'] += $deletedCount;
            }

            // Now create all the correct players
            foreach ($players as $playerData) {
                // Determine card tier based on overall rating
                $cardTier = $this->getCardTier($playerData['overall_rating']);

                $playerAttributes = [
                    'name' => $playerData['name'],
                    'team' => $teamName,
                    'overall_rating' => $playerData['overall_rating'],
                    'position' => $playerData['position'],
                    'card_tier' => $cardTier,
                    'collection_id' => $collection->id,
                    'obtainable_from_packs' => true,
                    'is_tradeable' => true,
                    'image_url' => null, // Will be populated later
                ];

                Player::create($playerAttributes);
                $stats['created']++;
                echo "  + Created {$playerData['name']} ({$playerData['overall_rating']} OVR)\n";
            }

            echo "\n";
        }

        echo "✅ Roster update complete!\n";
        echo "   Deleted old players: {$stats['deleted']}\n";
        echo "   Created new players: {$stats['created']}\n";
    }

    /**
     * Determine card tier based on overall rating
     * Based on CardTiers.md:
     * 99 - Galaxy Opal
     * 96-98 - Pink Diamond
     * 93-95 - Diamond
     * 90-92 - Amethyst
     * 88-89 - Ruby
     * 85-87 - Sapphire
     * 80-84 - Emerald
     * 75-79 - Gold
     * 72-74 - Silver
     * 69-71 - Bronze
     * <69 - Common
     */
    private function getCardTier(int $overall): string
    {
        if ($overall >= 99) return 'galaxy_opal';
        if ($overall >= 96) return 'pink_diamond';
        if ($overall >= 93) return 'diamond';
        if ($overall >= 90) return 'amethyst';
        if ($overall >= 88) return 'ruby';
        if ($overall >= 85) return 'sapphire';
        if ($overall >= 80) return 'emerald';
        if ($overall >= 75) return 'gold';
        if ($overall >= 72) return 'silver';
        if ($overall >= 69) return 'bronze';
        return 'common';
    }
}
