<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionReward extends Model
{
    protected $fillable = [
        'collection_id',
        'required_cards',
        'reward_type',
        'reward_value',
        'reward_quantity',
        'player_id',
        'pack_id',
        'order',
        'description',
    ];

    protected $casts = [
        'required_cards' => 'integer',
        'reward_quantity' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the collection that owns this reward.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Get the player card reward (if applicable).
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the pack reward (if applicable).
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * Get all user claims for this reward.
     */
    public function userClaims(): HasMany
    {
        return $this->hasMany(UserCollectionReward::class);
    }
}
