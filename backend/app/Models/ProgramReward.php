<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramReward extends Model
{
    protected $fillable = [
        'program_id',
        'xp_threshold',
        'stars_threshold',
        'reward_type',
        'reward_id',
        'reward_amount',
        'description',
        'available',
        'coming_soon_label',
    ];

    protected $casts = [
        'xp_threshold' => 'integer',
        'stars_threshold' => 'integer',
        'reward_id' => 'integer',
        'reward_amount' => 'integer',
        'available' => 'boolean',
    ];

    protected $attributes = [
        'reward_amount' => null,
    ];

    /**
     * Get the program this reward belongs to.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the player if this is a player reward.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'reward_id');
    }

    /**
     * Get the pack if this is a pack reward.
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class, 'reward_id');
    }

    /**
     * Get the target program if this is a program_xp reward.
     */
    public function targetProgram(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'reward_id');
    }

    /**
     * Check if this is a pack reward.
     */
    public function isPack(): bool
    {
        return $this->reward_type === 'pack';
    }

    /**
     * Check if this is a stubs reward.
     */
    public function isStubs(): bool
    {
        return $this->reward_type === 'stubs';
    }

    /**
     * Check if this is a player reward.
     */
    public function isPlayer(): bool
    {
        return $this->reward_type === 'player';
    }

    /**
     * Check if this is a program XP reward.
     */
    public function isProgramXp(): bool
    {
        return $this->reward_type === 'program_xp';
    }

    /**
     * Check if the reward is available to claim.
     * Returns false if reward is "coming soon" (earned but not yet available).
     */
    public function isAvailable(): bool
    {
        return $this->available === true;
    }

    /**
     * Check if the reward is pending (earned but not yet available).
     */
    public function isPending(): bool
    {
        return $this->available === false;
    }

    /**
     * Check if the reward can be claimed (has required data and is available).
     */
    public function canBeClaimed(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        // For player and pack rewards, need reward_id
        if ($this->isPlayer() || $this->isPack() || $this->isProgramXp()) {
            return $this->reward_id !== null;
        }

        // For stubs, need reward_amount
        if ($this->isStubs()) {
            return $this->reward_amount !== null;
        }

        return false;
    }
}
