<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgram extends Model
{
    protected $fillable = [
        'user_id',
        'program_id',
        'current_xp',
        'current_stars',
        'completed',
        'completed_at',
    ];

    protected $casts = [
        'current_xp' => 'integer',
        'current_stars' => 'integer',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user this progress belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the program this progress is for.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Add XP to this user's program progress.
     */
    public function addXp(int $amount): void
    {
        $this->increment('current_xp', $amount);
        $this->refresh();

        // Check if program is now complete
        if (
            $this->program->isXpProgram() &&
            $this->current_xp >= $this->program->total_xp_required &&
            !$this->completed
        ) {
            $this->markComplete();
        }
    }

    /**
     * Add stars to this user's program progress.
     */
    public function addStars(int $amount): void
    {
        $this->increment('current_stars', $amount);
        $this->refresh();

        // Check if program is now complete
        if (
            $this->program->isStarProgram() &&
            $this->current_stars >= $this->program->stars_required &&
            !$this->completed
        ) {
            $this->markComplete();
        }
    }

    /**
     * Mark this program as complete.
     */
    public function markComplete(): void
    {
        $this->update([
            'completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get progress percentage for XP programs.
     */
    public function getXpProgressPercentage(): float
    {
        if (!$this->program->isXpProgram() || $this->program->total_xp_required == 0) {
            return 0;
        }
        return min(100, ($this->current_xp / $this->program->total_xp_required) * 100);
    }

    /**
     * Get progress percentage for star programs.
     */
    public function getStarProgressPercentage(): float
    {
        if (!$this->program->isStarProgram() || $this->program->stars_required == 0) {
            return 0;
        }
        return min(100, ($this->current_stars / $this->program->stars_required) * 100);
    }
}
