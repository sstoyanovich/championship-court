<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPack extends Model
{
    protected $fillable = [
        'user_id',
        'pack_id',
        'source',
        'opened',
        'obtained_at',
        'opened_at',
    ];

    protected $casts = [
        'opened' => 'boolean',
        'obtained_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    /**
     * Get the user who owns this pack.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pack definition.
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * Mark this pack as opened.
     */
    public function markAsOpened(): void
    {
        $this->update([
            'opened' => true,
            'opened_at' => now(),
        ]);
    }

    /**
     * Scope to get only unopened packs.
     */
    public function scopeUnopened($query)
    {
        return $query->where('opened', false);
    }

    /**
     * Scope to get only opened packs.
     */
    public function scopeOpened($query)
    {
        return $query->where('opened', true);
    }
}
