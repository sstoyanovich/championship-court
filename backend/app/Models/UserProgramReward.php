<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgramReward extends Model
{
    protected $fillable = [
        'user_id',
        'program_id',
        'program_reward_id',
        'claimed_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
    ];

    /**
     * Get the user this claimed reward belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the program this claimed reward belongs to.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the reward that was claimed.
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(ProgramReward::class, 'program_reward_id');
    }
}
