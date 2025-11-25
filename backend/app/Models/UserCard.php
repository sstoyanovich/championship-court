<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCard extends Model
{
    protected $fillable = [
        'player_id',
        'user_id',
        'obtained_at',
        'locked',
        'locked_at',
        'count',
    ];

    protected $casts = [
        'obtained_at' => 'datetime',
        'locked' => 'boolean',
        'locked_at' => 'datetime',
        'count' => 'integer',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
