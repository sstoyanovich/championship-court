<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;

class UpdatePlayerTiers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'players:update-tiers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all player card tiers based on their overall rating';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating player card tiers based on overall ratings...');
        
        $players = Player::all();
        $updated = 0;
        $unchanged = 0;
        
        $tierChanges = [];
        
        foreach ($players as $player) {
            $oldTier = $player->card_tier;
            $newTier = $this->getCardTier($player->overall_rating);
            
            if ($oldTier !== $newTier) {
                $player->card_tier = $newTier;
                $player->save();
                $updated++;
                
                if (!isset($tierChanges[$oldTier])) {
                    $tierChanges[$oldTier] = [];
                }
                if (!isset($tierChanges[$oldTier][$newTier])) {
                    $tierChanges[$oldTier][$newTier] = 0;
                }
                $tierChanges[$oldTier][$newTier]++;
                
                $this->line("Updated {$player->name} ({$player->overall_rating}): {$oldTier} → {$newTier}");
            } else {
                $unchanged++;
            }
        }
        
        $this->newLine();
        $this->info("✓ Update complete!");
        $this->info("  Total players: " . $players->count());
        $this->info("  Updated: {$updated}");
        $this->info("  Unchanged: {$unchanged}");
        
        if (!empty($tierChanges)) {
            $this->newLine();
            $this->info("Tier Changes Summary:");
            foreach ($tierChanges as $oldTier => $newTiers) {
                foreach ($newTiers as $newTier => $count) {
                    $this->line("  {$oldTier} → {$newTier}: {$count} players");
                }
            }
        }
        
        return 0;
    }
    
    /**
     * Determine card tier based on overall rating
     * Based on CardTiers.md definitions
     */
    private function getCardTier(int $overallRating): string
    {
        if ($overallRating >= 99) {
            return 'galaxy_opal';
        } elseif ($overallRating >= 96) {
            return 'pink_diamond';
        } elseif ($overallRating >= 93) {
            return 'diamond';
        } elseif ($overallRating >= 90) {
            return 'amethyst';
        } elseif ($overallRating >= 88) {
            return 'ruby';
        } elseif ($overallRating >= 85) {
            return 'sapphire';
        } elseif ($overallRating >= 80) {
            return 'emerald';
        } elseif ($overallRating >= 75) {
            return 'gold';
        } elseif ($overallRating >= 72) {
            return 'silver';
        } elseif ($overallRating >= 69) {
            return 'bronze';
        } else {
            return 'common';
        }
    }
}
