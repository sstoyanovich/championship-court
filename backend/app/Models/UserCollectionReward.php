<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCollectionReward extends Model
{
    protected $fillable = [
        'user_id',
        'collection_reward_id',
        'claimed_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
    ];

    /**
     * Get the user that claimed this reward.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reward that was claimed.
     */
    public function collectionReward(): BelongsTo
    {
        return $this->belongsTo(CollectionReward::class);
    }
}
