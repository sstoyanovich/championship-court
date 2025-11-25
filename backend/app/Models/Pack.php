<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pack extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'card_count',
        'choice_count',
        'odds_config',
        'guaranteed_slots',
        'collection_id',
        'cost',
        'active',
        'available_in_shop',
        'specified_players',
        'featured_players',
        'player_pools',
    ];

    protected $casts = [
        'odds_config' => 'array',
        'guaranteed_slots' => 'array',
        'specified_players' => 'array',
        'featured_players' => 'array',
        'player_pools' => 'array',
        'active' => 'boolean',
        'available_in_shop' => 'boolean',
    ];

    /**
     * Get the collection that this pack is restricted to.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Get the weighted tier odds for this pack
     */
    public function getTierOdds(): array
    {
        return $this->odds_config ?? [];
    }

    /**
     * Get guaranteed slots configuration
     */
    public function getGuaranteedSlots(): array
    {
        return $this->guaranteed_slots ?? [];
    }

    /**
     * Check if a specific slot has a guaranteed minimum tier
     */
    public function hasGuaranteedSlot(int $slot): bool
    {
        $guaranteedSlots = $this->getGuaranteedSlots();
        foreach ($guaranteedSlots as $config) {
            if ($config['slot'] === $slot) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the minimum tier for a guaranteed slot
     */
    public function getMinimumTierForSlot(int $slot): ?string
    {
        $guaranteedSlots = $this->getGuaranteedSlots();
        foreach ($guaranteedSlots as $config) {
            if ($config['slot'] === $slot) {
                return $config['min_tier'] ?? null;
            }
        }
        return null;
    }

    /**
     * Check if this pack is restricted to a collection
     */
    public function isCollectionRestricted(): bool
    {
        return $this->collection_id !== null;
    }

    /**
     * Scope to get only active packs
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Check if this is a choice pack
     */
    public function isChoicePack(): bool
    {
        return $this->type === 'choice';
    }

    /**
     * Check if this pack has specified players
     */
    public function hasSpecifiedPlayers(): bool
    {
        return !empty($this->specified_players);
    }

    /**
     * Get the list of specified player IDs
     */
    public function getSpecifiedPlayerIds(): array
    {
        return $this->specified_players ?? [];
    }

    /**
     * Scope to get only packs available in shop
     */
    public function scopeAvailableInShop($query)
    {
        return $query->where('available_in_shop', true);
    }

    /**
     * Check if this pack has featured players with special odds
     */
    public function hasFeaturedPlayers(): bool
    {
        return !empty($this->featured_players);
    }

    /**
     * Get the featured players configuration
     * Returns array of [['player_id' => int, 'odds' => float], ...]
     */
    public function getFeaturedPlayers(): array
    {
        return $this->featured_players ?? [];
    }

    /**
     * Check if this pack has player pools with custom odds
     */
    public function hasPlayerPools(): bool
    {
        return !empty($this->player_pools);
    }

    /**
     * Get the player pools configuration
     * Returns array of [['name' => string, 'odds' => float, 'player_ids' => array], ...]
     */
    public function getPlayerPools(): array
    {
        return $this->player_pools ?? [];
    }
}
