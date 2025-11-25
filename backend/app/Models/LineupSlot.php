<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineupSlot extends Model
{
    protected $fillable = [
        'lineup_id',
        'position',
        'user_card_id',
    ];

    public function lineup(): BelongsTo
    {
        return $this->belongsTo(Lineup::class);
    }

    public function userCard(): BelongsTo
    {
        return $this->belongsTo(UserCard::class);
    }
}
