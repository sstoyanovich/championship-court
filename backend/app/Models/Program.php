<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'team',
        'category',
        'total_xp_required',
        'stars_required',
        'active',
        'image_url',
    ];

    protected $casts = [
        'active' => 'boolean',
        'total_xp_required' => 'integer',
        'stars_required' => 'integer',
    ];

    /**
     * Get the rewards for this program.
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(ProgramReward::class);
    }

    /**
     * Get the challenges for this program.
     */
    public function challenges(): HasMany
    {
        return $this->hasMany(ProgramChallenge::class);
    }

    /**
     * Get user progress records for this program.
     */
    public function userPrograms(): HasMany
    {
        return $this->hasMany(UserProgram::class);
    }

    /**
     * Scope to get only XP-based programs.
     */
    public function scopeXpType($query)
    {
        return $query->where('type', 'xp');
    }

    /**
     * Scope to get only star-based programs.
     */
    public function scopeStarType($query)
    {
        return $query->where('type', 'star');
    }

    /**
     * Scope to get only active programs.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Check if this is an XP program.
     */
    public function isXpProgram(): bool
    {
        return $this->type === 'xp';
    }

    /**
     * Check if this is a star program.
     */
    public function isStarProgram(): bool
    {
        return $this->type === 'star';
    }

    /**
     * Scope to get only Team Affinity programs.
     */
    public function scopeTeamAffinity($query)
    {
        return $query->where('category', 'team_affinity');
    }

    /**
     * Scope to get only general programs.
     */
    public function scopeGeneral($query)
    {
        return $query->where('category', 'general');
    }

    /**
     * Check if this is a Team Affinity program.
     */
    public function isTeamAffinity(): bool
    {
        return $this->category === 'team_affinity';
    }
}
