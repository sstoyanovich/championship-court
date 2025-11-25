<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSession extends Model
{
    protected $fillable = [
        'user_id',
        'lineup_id',
        'screenshot_path',
        'raw_ocr_data',
        'processed_at',
    ];

    protected $casts = [
        'raw_ocr_data' => 'array',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lineup(): BelongsTo
    {
        return $this->belongsTo(Lineup::class);
    }
}
