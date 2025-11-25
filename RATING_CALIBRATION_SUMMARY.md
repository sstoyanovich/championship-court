# NBA Player Rating Calibration Summary

## Calibration Process

The player rating formulas were calibrated using actual 2K25 ratings for four reference players:
- **Stephen Curry** (PG): Target ~95 overall
- **LeBron James** (SF): Target 93-95 overall  
- **Anthony Edwards** (SG): Target 93-94 overall
- **Bam Adebayo** (C): Target 87-89 overall

## Final Calibrated Results

| Player | Our Rating | Target 2K25 | Difference |
|--------|-----------|-------------|------------|
| **Bam Adebayo** | **88** | 87-89 | ✓ Perfect! |
| **LeBron James** | **95** | 93-95 | ✓ Perfect! |
| **Stephen Curry** | 94 | ~95 | -1 (excellent) |
| **Anthony Edwards** | 95 | 93-94 | +1 (excellent) |

All players are within **±1 point** of their 2K25 ratings!

## Key Formula Changes

### 1. Position-Based Overall Rating Weights
Different positions now use different category weights:

**Centers/Power Forwards (PF, C):**
- Inside Scoring: 30% (high)
- Defense: 26% (high)
- Outside Scoring: 16% (low)
- Rebounding: 6% (high for bigs)

**Guards (PG, SG):**
- Outside Scoring: 35% (high)
- Playmaking: 16% (high)
- Defense: 16%
- Inside Scoring: 16% (low)

**Small Forwards (SF):**
- Balanced between guards and bigs

### 2. Enhanced Defensive Attributes
- Increased base ratings for interior/perimeter defense
- Steal: `56 + SPG * 16` (was `50 + SPG * 12`)
- Block: `46 + BPG * 18` (was `40 + BPG * 15`)
- Interior Defense: `75 + BPG * 10 + RPG * 0.6` for bigs

### 3. Improved Rebounding Formulas
- Offensive Rebound: `68 + RPG * 2.3` for bigs (was `65 + RPG * 2.0`)
- Defensive Rebound: `72 + RPG * 2.5` for bigs (was `70 + RPG * 2.5`)

### 4. Inside Scoring Boosts for Bigs
- Layup: `40 + (FG% - 40) * 1.5 + PPG * 2.8`
- Standing Dunk base: 75 for bigs (was 70)
- Post moves base: 70 for bigs (was 65)

### 5. Position-Specific PPG Bonuses
**Bigs (PF, C):**
- 25+ PPG: +8
- 20+ PPG: +6
- 16+ PPG: +3

**Guards/Wings:**
- 30+ PPG: +7
- 27+ PPG: +5
- 24+ PPG: +3

**Small Forwards:**
- 28+ PPG: +4
- 25+ PPG: +2

### 6. New Defensive Stat Bonuses
**Steals:**
- 2.0+ SPG: +4
- 1.5+ SPG: +3
- 1.0+ SPG: +1

**Blocks:**
- 2.0+ BPG: +5
- 1.5+ BPG: +3
- 1.0+ BPG: +2

### 7. Elite Shooter Recognition
**3PT% Bonuses:**
- 42%+: +6 (was +5)
- 40%+: +4 (new)
- 38%+: +2

**High-Volume Scorer Bonus (Guards):**
- Guards scoring 27+ PPG: +5
- Guards scoring 24+ PPG: +3

**Elite Efficiency Combo (Curry-tier):**
- 39%+ 3PT% + 23+ PPG + PG/SG position: +2

### 8. Position Parsing Fix
Fixed "Center" -> "Ce" issue:
```python
elif position == "Ce":  # "Center" shortened
    position = "C"
elif position == "Fo":  # "Forward" shortened
    position = "SF"
elif position == "Gu":  # "Guard" shortened
    position = "SG"
```

## Testing the Changes

To test a specific player:
```bash
cd backend/scripts
python3 test_player_rating.py "Player Name" "2024-25"
```

To run the full import with calibrated formulas:
```bash
cd backend
php artisan nba:import-from-stats
```
(Note: Full import takes ~30 minutes due to API rate limiting)

## Card Tier Distribution (Expected)

With the new formulas, the tier distribution should be:
- **Pink Diamond (97-99):** ~5-10 players (superstars like Giannis, Jokic)
- **Diamond (93-96):** ~20-30 players (all-stars)
- **Amethyst (90-92):** ~40-50 players (high-level starters)
- **Sapphire (85-89):** ~60-80 players (quality starters)
- **Emerald (80-84):** ~80-100 players (solid rotation players)
- **Gold (75-79):** ~100-120 players (role players)
- **Silver (70-74):** ~80-100 players (bench players)
- **Bronze (66-69):** ~50-60 players (deep bench)
- **Common (<66):** ~50+ players (end of bench/rookies)

Total: ~500-600 active NBA players
