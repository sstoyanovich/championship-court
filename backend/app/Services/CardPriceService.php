<?php

namespace App\Services;

use App\Models\Player;

class CardPriceService
{
    /**
     * Get sell price for a card based on overall rating
     * Prices from CardPrices.md
     */
    public function getSellPrice(int $overallRating): int
    {
        // Galaxy Opal (99)
        if ($overallRating >= 99) {
            return 200000;
        }
        
        // Pink Diamond (96-98)
        if ($overallRating >= 98) {
            return 100000;
        }
        if ($overallRating >= 97) {
            return 75000;
        }
        if ($overallRating >= 96) {
            return 50000;
        }
        
        // Diamond (93-95)
        if ($overallRating >= 95) {
            return 30000;
        }
        if ($overallRating >= 94) {
            return 20000;
        }
        if ($overallRating >= 93) {
            return 15000;
        }
        
        // Amethyst (90-92)
        if ($overallRating >= 92) {
            return 10000;
        }
        if ($overallRating >= 91) {
            return 9000;
        }
        if ($overallRating >= 90) {
            return 8000;
        }
        
        // Ruby (88-89)
        if ($overallRating >= 89) {
            return 6000;
        }
        if ($overallRating >= 88) {
            return 5000;
        }
        
        // Sapphire (85-87)
        if ($overallRating >= 87) {
            return 4500;
        }
        if ($overallRating >= 86) {
            return 3750;
        }
        if ($overallRating >= 85) {
            return 3000;
        }
        
        // Emerald (80-84)
        if ($overallRating >= 84) {
            return 1500;
        }
        if ($overallRating >= 83) {
            return 1200;
        }
        if ($overallRating >= 82) {
            return 900;
        }
        if ($overallRating >= 81) {
            return 600;
        }
        if ($overallRating >= 80) {
            return 400;
        }
        
        // Gold (75-79)
        if ($overallRating >= 79) {
            return 350;
        }
        if ($overallRating >= 78) {
            return 375;
        }
        if ($overallRating >= 77) {
            return 200;
        }
        if ($overallRating >= 76) {
            return 150;
        }
        if ($overallRating >= 75) {
            return 125;
        }
        
        // Silver (72-74)
        if ($overallRating >= 72) {
            return 100;
        }
        
        // Bronze (69-71)
        if ($overallRating >= 69) {
            return 25;
        }
        
        // Common (<69)
        return 5;
    }

    /**
     * Get buy price for a card (10% markup on sell price)
     */
    public function getBuyPrice(int $overallRating): int
    {
        $sellPrice = $this->getSellPrice($overallRating);
        return (int) round($sellPrice * 1.10);
    }

    /**
     * Get both buy and sell prices for a player
     */
    public function getCardPrices(Player $player): array
    {
        $sellPrice = $this->getSellPrice($player->overall_rating);
        $buyPrice = $this->getBuyPrice($player->overall_rating);
        
        return [
            'sell_price' => $sellPrice,
            'buy_price' => $buyPrice,
        ];
    }
}



