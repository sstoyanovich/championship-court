<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    protected $fillable = [
        'name',
        'type',
        'sub_collection',
        'description',
        'total_items',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the players in this collection.
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Get the rewards for this collection.
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(CollectionReward::class);
    }
}
