<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    protected $fillable = [
        'name',
        'nba_id',
        'overall_rating',
        'position',
        'team',
        'card_tier',
        'image_url',
        'card_art',
        'collection_id',
        'obtainable_from_packs',
        'is_tradeable',
        // Category ratings
        'outside_scoring',
        'inside_scoring',
        'defense',
        'athleticism',
        'playmaking',
        'rebounding',
        // Outside Scoring attributes
        'close_shot',
        'mid_range_shot',
        'three_point_shot',
        'free_throw',
        'shot_iq',
        'offensive_consistency',
        // Inside Scoring attributes
        'layup',
        'standing_dunk',
        'driving_dunk',
        'post_hook',
        'post_fade',
        'post_control',
        'draw_foul',
        'hands',
        // Defense attributes
        'interior_defense',
        'perimeter_defense',
        'steal',
        'block',
        'help_defense_iq',
        'pass_perception',
        'defensive_consistency',
        // Athleticism attributes
        'speed',
        'agility',
        'strength',
        'vertical',
        'stamina',
        'hustle',
        'overall_durability',
        // Playmaking attributes
        'pass_accuracy',
        'ball_handle',
        'speed_with_ball',
        'pass_iq',
        'pass_vision',
        // Rebounding attributes
        'offensive_rebound',
        'defensive_rebound',
        // Other attributes
        'intangibles',
        'potential',
    ];

    protected $casts = [
        'obtainable_from_packs' => 'boolean',
        'is_tradeable' => 'boolean',
    ];

    /**
     * Scope query to only include players obtainable from packs
     */
    public function scopeObtainableFromPacks($query)
    {
        return $query->where('obtainable_from_packs', true);
    }

    /**
     * Scope query to only include program-exclusive players
     */
    public function scopeProgramExclusive($query)
    {
        return $query->where('obtainable_from_packs', false);
    }

    public function userCards(): HasMany
    {
        return $this->hasMany(UserCard::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
