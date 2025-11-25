<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramChallenge extends Model
{
    protected $fillable = [
        'program_id',
        'type',
        'target_stat',
        'target_value',
        'constraint_type',
        'constraint_value',
        'stars_reward',
        'xp_reward',
        'description',
    ];

    protected $casts = [
        'target_value' => 'integer',
        'stars_reward' => 'integer',
        'xp_reward' => 'integer',
    ];

    /**
     * Get the program this challenge belongs to.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get user progress records for this challenge.
     */
    public function userChallenges(): HasMany
    {
        return $this->hasMany(UserProgramChallenge::class);
    }

    /**
     * Check if this is a stat-based challenge.
     */
    public function isStatChallenge(): bool
    {
        return $this->type === 'stat';
    }

    /**
     * Check if this is a game count challenge.
     */
    public function isGameCountChallenge(): bool
    {
        return $this->type === 'game_count';
    }

    /**
     * Check if this is a PXP challenge.
     */
    public function isPxpChallenge(): bool
    {
        return $this->type === 'pxp';
    }

    /**
     * Check if this challenge has constraints.
     */
    public function hasConstraint(): bool
    {
        return !empty($this->constraint_type) && !empty($this->constraint_value);
    }

    /**
     * Check if this is a player constraint challenge.
     */
    public function isPlayerConstraint(): bool
    {
        return $this->constraint_type === 'player';
    }
}
