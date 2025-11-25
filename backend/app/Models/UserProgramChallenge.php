<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgramChallenge extends Model
{
    protected $fillable = [
        'user_id',
        'program_challenge_id',
        'current_progress',
        'completed',
        'completed_at',
    ];

    protected $casts = [
        'current_progress' => 'integer',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user this challenge progress belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the challenge this progress is for.
     */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(ProgramChallenge::class, 'program_challenge_id');
    }

    /**
     * Add progress to this challenge.
     */
    public function addProgress(int $amount): bool
    {
        if ($this->completed) {
            return false;
        }

        $this->increment('current_progress', $amount);
        $this->refresh();

        // Check if challenge is now complete
        if ($this->current_progress >= $this->challenge->target_value) {
            $this->markComplete();
            return true;
        }

        return false;
    }

    /**
     * Mark this challenge as complete.
     */
    public function markComplete(): void
    {
        $this->update([
            'completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if ($this->challenge->target_value == 0) {
            return 0;
        }
        return min(100, ($this->current_progress / $this->challenge->target_value) * 100);
    }
}
