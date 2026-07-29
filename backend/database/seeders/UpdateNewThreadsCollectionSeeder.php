<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Collection;
use App\Models\Player;
use App\Models\Pack;

class UpdateNewThreadsCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating New Threads collection...');

        // Create or find the New Threads collection
        $collection = Collection::firstOrCreate(
            ['name' => 'New Threads'],
            [
                'type' => 'program',
                'sub_collection' => null,
                'description' => 'Collect all New Threads player cards',
                'total_items' => 0,
                'active' => true,
            ]
        );

        // Get all New Threads players
        $newThreadsPlayers = Player::where('card_art', 'LIKE', '%new_threads%')->get();

        $this->command->info('Updating ' . $newThreadsPlayers->count() . ' New Threads players...');

        // Update Jalen Green's rating to 88 (as per pack specification)
        $jalenGreen = $newThreadsPlayers->where('name', 'Jalen Green')->first();
        if ($jalenGreen && $jalenGreen->overall_rating !== 88) {
            $this->command->info("Updating Jalen Green from {$jalenGreen->overall_rating} to 88...");
            $jalenGreen->update([
                'overall_rating' => 88,
                'card_tier' => 'ruby', // 88 = ruby tier
            ]);
        }

        // Assign all New Threads players to the collection
        $updated = 0;
        foreach ($newThreadsPlayers as $player) {
            if ($player->collection_id !== $collection->id) {
                $player->update(['collection_id' => $collection->id]);
                $updated++;
            }
        }

        // Update collection total_items
        $collection->update(['total_items' => $newThreadsPlayers->count()]);

        $this->command->info("✓ Updated {$updated} players with collection_id");

        // Update the New Threads Pack to only include the 9 specified players
        $this->command->info('Updating New Threads Pack...');

        $pack = Pack::where('name', 'New Threads Pack')->first();

        if ($pack) {
            // Get the 9 players specified in the pack (by tier)
            // Base Tier: Chris Paul (84), Anfernee Simons (84), Duncan Robinson (84)
            // Mid Tier: Michael Porter Jr (85), Damian Lillard (85), Cameron Johnson (85)
            // Rare Tier: Kristaps Porzingis (88), Jalen Green (88), Norman Powell (88)

            $packPlayers = Player::where('card_art', 'LIKE', '%new_threads%')
                ->whereIn('name', [
                    'Chris Paul',
                    'Anfernee Simons',
                    'Duncan Robinson',
                    'Michael Porter Jr.',
                    'Damian Lillard',
                    'Cameron Johnson',
                    'Kristaps Porzingis',
                    'Jalen Green',
                    'Norman Powell',
                ])
                ->pluck('id')
                ->toArray();

            if (count($packPlayers) === 9) {
                $pack->update([
                    'specified_players' => $packPlayers,
                    'card_count' => 9,
                ]);

                $this->command->info('✓ Updated New Threads Pack with 9 players');
                $this->command->info('  Base Tier: Chris Paul, Anfernee Simons, Duncan Robinson');
                $this->command->info('  Mid Tier: Michael Porter Jr., Damian Lillard, Cameron Johnson');
                $this->command->info('  Rare Tier: Kristaps Porzingis, Jalen Green, Norman Powell');
            } else {
                $this->command->warn('Expected 9 players but found ' . count($packPlayers));
                $this->command->warn('Players found: ' . implode(', ', Player::whereIn('id', $packPlayers)->pluck('name')->toArray()));
            }
        } else {
            $this->command->error('New Threads Pack not found!');
        }

        $this->command->info('');
        $this->command->info('✅ New Threads collection setup complete!');
        $this->command->info("Collection ID: {$collection->id}");
        $this->command->info("Total players in collection: {$collection->total_items}");
    }
}
