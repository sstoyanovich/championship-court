<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPlayerStats extends Model
{
    protected $fillable = [
        'user_id',
        'player_id',
        'player_xp',
        'games_played',
        'total_minutes',
        'total_points',
        'total_rebounds',
        'total_assists',
        'total_steals',
        'total_blocks',
        'total_turnovers',
        'total_fgm',
        'total_fga',
        'total_3pm',
        'total_3pa',
    ];

    protected $casts = [
        'player_xp' => 'integer',
        'games_played' => 'integer',
        'total_minutes' => 'integer',
        'total_points' => 'integer',
        'total_rebounds' => 'integer',
        'total_assists' => 'integer',
        'total_steals' => 'integer',
        'total_blocks' => 'integer',
        'total_turnovers' => 'integer',
        'total_fgm' => 'integer',
        'total_fga' => 'integer',
        'total_3pm' => 'integer',
        'total_3pa' => 'integer',
    ];

    /**
     * Get the user these stats belong to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the player these stats are for.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Add stats from a game to this player's cumulative totals.
     */
    public function addGameStats(array $stats): void
    {
        $this->increment('games_played');
        $this->increment('total_minutes', $stats['minutes'] ?? 0);
        $this->increment('total_points', $stats['points'] ?? 0);
        $this->increment('total_rebounds', $stats['rebounds'] ?? 0);
        $this->increment('total_assists', $stats['assists'] ?? 0);
        $this->increment('total_steals', $stats['steals'] ?? 0);
        $this->increment('total_blocks', $stats['blocks'] ?? 0);
        $this->increment('total_turnovers', $stats['turnovers'] ?? 0);
        $this->increment('total_fgm', $stats['fgm'] ?? 0);
        $this->increment('total_fga', $stats['fga'] ?? 0);
        $this->increment('total_3pm', $stats['tpm'] ?? 0);
        $this->increment('total_3pa', $stats['tpa'] ?? 0);
    }

    /**
     * Add PXP to this player.
     */
    public function addPxp(int $amount): void
    {
        $this->increment('player_xp', $amount);
    }

    /**
     * Get field goal percentage.
     */
    public function getFGPercentage(): float
    {
        if ($this->total_fga == 0) {
            return 0.0;
        }
        return round(($this->total_fgm / $this->total_fga) * 100, 1);
    }

    /**
     * Get 3-point percentage.
     */
    public function get3PPercentage(): float
    {
        if ($this->total_3pa == 0) {
            return 0.0;
        }
        return round(($this->total_3pm / $this->total_3pa) * 100, 1);
    }

    /**
     * Get points per game.
     */
    public function getPPG(): float
    {
        if ($this->games_played == 0) {
            return 0.0;
        }
        return round($this->total_points / $this->games_played, 1);
    }

    /**
     * Get rebounds per game.
     */
    public function getRPG(): float
    {
        if ($this->games_played == 0) {
            return 0.0;
        }
        return round($this->total_rebounds / $this->games_played, 1);
    }

    /**
     * Get assists per game.
     */
    public function getAPG(): float
    {
        if ($this->games_played == 0) {
            return 0.0;
        }
        return round($this->total_assists / $this->games_played, 1);
    }

    /**
     * Get the current parallel level based on player XP.
     * 
     * @return int Parallel level (0-7)
     */
    public function getParallelLevel(): int
    {
        $pxp = $this->player_xp;

        if ($pxp >= 8000) return 7;
        if ($pxp >= 5000) return 6;
        if ($pxp >= 3000) return 5;
        if ($pxp >= 1500) return 4;
        if ($pxp >= 1000) return 3;
        if ($pxp >= 600) return 2;
        if ($pxp >= 300) return 1;
        return 0;
    }

    /**
     * Get comprehensive parallel data including level, numeral, color, and progress.
     * 
     * @return array
     */
    public function getParallelData(): array
    {
        $parallelLevels = [
            ['level' => 0, 'threshold' => 0, 'numeral' => '', 'color' => '#FFFFFF', 'name' => 'Base'],
            ['level' => 1, 'threshold' => 300, 'numeral' => 'I', 'color' => '#10B981', 'name' => 'Green'],
            ['level' => 2, 'threshold' => 600, 'numeral' => 'II', 'color' => '#F97316', 'name' => 'Orange'],
            ['level' => 3, 'threshold' => 1000, 'numeral' => 'III', 'color' => '#A855F7', 'name' => 'Purple'],
            ['level' => 4, 'threshold' => 1500, 'numeral' => 'IV', 'color' => '#EF4444', 'name' => 'Red'],
            ['level' => 5, 'threshold' => 3000, 'numeral' => 'V', 'color' => '#14B8A6', 'name' => 'Teal'],
            ['level' => 6, 'threshold' => 5000, 'numeral' => 'VI', 'color' => '#EAB308', 'name' => 'Yellow'],
            ['level' => 7, 'threshold' => 8000, 'numeral' => 'VII', 'color' => '#000000', 'name' => 'Black'],
        ];

        $currentLevel = $this->getParallelLevel();
        $currentData = $parallelLevels[$currentLevel];

        // Calculate progress to next level
        $nextLevel = $currentLevel < 7 ? $parallelLevels[$currentLevel + 1] : null;
        $progressToNext = 0;
        $pxpToNext = 0;

        if ($nextLevel) {
            $currentThreshold = $currentData['threshold'];
            $nextThreshold = $nextLevel['threshold'];
            $pxpInLevel = $this->player_xp - $currentThreshold;
            $pxpNeeded = $nextThreshold - $currentThreshold;
            $progressToNext = $pxpNeeded > 0 ? round(($pxpInLevel / $pxpNeeded) * 100, 1) : 0;
            $pxpToNext = $nextThreshold - $this->player_xp;
        }

        return [
            'level' => $currentData['level'],
            'numeral' => $currentData['numeral'],
            'color' => $currentData['color'],
            'name' => $currentData['name'],
            'current_pxp' => $this->player_xp,
            'threshold' => $currentData['threshold'],
            'next_threshold' => $nextLevel ? $nextLevel['threshold'] : null,
            'progress_to_next' => $progressToNext,
            'pxp_to_next' => $pxpToNext,
        ];
    }
}
