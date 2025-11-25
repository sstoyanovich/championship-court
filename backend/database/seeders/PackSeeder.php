<?php

namespace Database\Seeders;

use App\Models\Pack;
use Illuminate\Database\Seeder;

class PackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packs = [
            [
                'name' => 'Standard Pack',
                'type' => 'standard',
                'description' => 'A basic pack with 4 random players. Good chance for silver and bronze players.',
                'card_count' => 4,
                'choice_count' => null,
                'odds_config' => [
                    'bronze' => 50.0,   // 50%
                    'silver' => 35.0,   // 35%
                    'gold' => 10.0,     // 10%
                    'emerald' => 3.5,   // 3.5%
                    'sapphire' => 1.0,  // 1%
                    'amethyst' => 0.3,  // 0.3%
                    'diamond' => 0.15,  // 0.15%
                    'pink_diamond' => 0.05, // 0.05%
                ],
                'cost' => 1000,
                'active' => true,
            ],
            [
                'name' => 'Premium Pack',
                'type' => 'standard',
                'description' => 'A premium pack with 5 random players. Better odds for high-tier cards!',
                'card_count' => 5,
                'choice_count' => null,
                'odds_config' => [
                    'bronze' => 30.0,   // 30%
                    'silver' => 35.0,   // 35%
                    'gold' => 20.0,     // 20%
                    'emerald' => 10.0,  // 10%
                    'sapphire' => 3.0,  // 3%
                    'amethyst' => 1.0,  // 1%
                    'diamond' => 0.75,  // 0.75%
                    'pink_diamond' => 0.25, // 0.25%
                ],
                'cost' => 3000,
                'active' => true,
            ],
            [
                'name' => 'Elite Choice Pack',
                'type' => 'choice',
                'description' => 'Choose 1 of 3 elite players! Guaranteed Gold or higher.',
                'card_count' => 3,
                'choice_count' => 1,
                'odds_config' => [
                    'gold' => 50.0,     // 50%
                    'emerald' => 30.0,  // 30%
                    'sapphire' => 12.0, // 12%
                    'amethyst' => 5.0,  // 5%
                    'diamond' => 2.5,   // 2.5%
                    'pink_diamond' => 0.5, // 0.5%
                ],
                'cost' => 5000,
                'active' => true,
            ],
            [
                'name' => 'Diamond Choice Pack',
                'type' => 'choice',
                'description' => 'Choose 2 of 5 premium players! Guaranteed Emerald or higher with Diamond+ odds!',
                'card_count' => 5,
                'choice_count' => 2,
                'odds_config' => [
                    'emerald' => 40.0,  // 40%
                    'sapphire' => 35.0, // 35%
                    'amethyst' => 15.0, // 15%
                    'diamond' => 7.5,   // 7.5%
                    'pink_diamond' => 2.5, // 2.5%
                ],
                'cost' => 10000,
                'active' => true,
            ],
            [
                'name' => 'Budget Pack',
                'type' => 'standard',
                'description' => 'A low-cost pack with 3 random players. Great for building your collection!',
                'card_count' => 3,
                'choice_count' => null,
                'odds_config' => [
                    'bronze' => 60.0,   // 60%
                    'silver' => 30.0,   // 30%
                    'gold' => 7.0,      // 7%
                    'emerald' => 2.0,   // 2%
                    'sapphire' => 0.7,  // 0.7%
                    'amethyst' => 0.2,  // 0.2%
                    'diamond' => 0.08,  // 0.08%
                    'pink_diamond' => 0.02, // 0.02%
                ],
                'cost' => 500,
                'active' => true,
            ],
            // NEW: Pack with guaranteed tier slot
            [
                'name' => 'Guaranteed Emerald Pack',
                'type' => 'standard',
                'description' => '4 random players plus 1 guaranteed Emerald or better! Great odds for high-tier pulls.',
                'card_count' => 5,
                'choice_count' => null,
                'odds_config' => [
                    'bronze' => 50.0,   // 50%
                    'silver' => 35.0,   // 35%
                    'gold' => 10.0,     // 10%
                    'emerald' => 3.5,   // 3.5%
                    'sapphire' => 1.0,  // 1%
                    'amethyst' => 0.3,  // 0.3%
                    'diamond' => 0.15,  // 0.15%
                    'pink_diamond' => 0.05, // 0.05%
                ],
                'guaranteed_slots' => [
                    ['slot' => 5, 'min_tier' => 'emerald'], // Last card is guaranteed Emerald or above
                ],
                'collection_id' => null,
                'cost' => 4500,
                'active' => true,
            ],
            // NEW: Pack restricted to a collection (Lakers as example)
            [
                'name' => 'Lakers Team Pack',
                'type' => 'standard',
                'description' => 'Only Los Angeles Lakers players! Perfect for building your Lakers collection.',
                'card_count' => 4,
                'choice_count' => null,
                'odds_config' => [
                    'bronze' => 50.0,   // 50%
                    'silver' => 35.0,   // 35%
                    'gold' => 10.0,     // 10%
                    'emerald' => 3.5,   // 3.5%
                    'sapphire' => 1.0,  // 1%
                    'amethyst' => 0.3,  // 0.3%
                    'diamond' => 0.15,  // 0.15%
                    'pink_diamond' => 0.05, // 0.05%
                ],
                'guaranteed_slots' => null,
                'collection_id' => 14, // Los Angeles Lakers collection ID (assuming they're 14th in seeder order)
                'cost' => 2000,
                'active' => true,
            ],
        ];

        foreach ($packs as $packData) {
            Pack::create($packData);
        }

        $this->command->info('✓ Created ' . count($packs) . ' pack types');
    }
}
