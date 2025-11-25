# Shooting Bonus PXP Feature

## Overview

Players now earn **bonus PXP for made shots**, rewarding efficient shooting performance!

## New PXP Bonuses

### Shooting Rewards:
- **+2 PXP per field goal made** (FGM)
- **+3 PXP per three-pointer made** (3PM)

These bonuses stack with all other PXP sources!

---

## Updated PXP Formula

### Complete Formula:

```
Base PXP = (Minutes × 5) + (Points × 2) + (Assists × 3) + (Rebounds × 2) + 
           (Steals × 5) + (Blocks × 5) + (FGM × 2) + (3PM × 3)

Plus:
- Double-Double: +250 PXP
- Triple-Double: +500 PXP
```

### Breakdown:
| Stat Category | PXP Value |
|---------------|-----------|
| Minutes | 5 per minute |
| Points | 2 per point |
| Rebounds | 2 per rebound |
| Assists | 3 per assist |
| Steals | 5 per steal |
| Blocks | 5 per block |
| **Field Goals Made** | **2 per FGM** ⭐ NEW |
| **Three-Pointers Made** | **3 per 3PM** ⭐ NEW |
| Double-Double | +250 bonus |
| Triple-Double | +500 bonus |

---

## Examples

### Example 1: Efficient Scorer

**Performance**: 30 mins, 24 pts, 6 reb, 4 ast, 1 stl, 1 blk, **10-15 FG**, **4-7 3PT**

```
Base Stats:
  Minutes:   30 × 5  = 150
  Points:    24 × 2  =  48
  Rebounds:   6 × 2  =  12
  Assists:    4 × 3  =  12
  Steals:     1 × 5  =   5
  Blocks:     1 × 5  =   5

Shooting Bonuses:
  FGM:       10 × 2  =  20  ⭐
  3PM:        4 × 3  =  12  ⭐

Total: 264 PXP
```

**Impact**: The 10 made FGs and 4 made 3-pointers add **32 bonus PXP**!

---

### Example 2: Three-Point Specialist

**Performance**: 28 mins, 21 pts, 3 reb, 2 ast, 0 stl, 0 blk, **7-12 FG**, **7-10 3PT**

```
Base Stats:
  Minutes:   28 × 5  = 140
  Points:    21 × 2  =  42
  Rebounds:   3 × 2  =   6
  Assists:    2 × 3  =   6
  Steals:     0 × 5  =   0
  Blocks:     0 × 5  =   0

Shooting Bonuses:
  FGM:        7 × 2  =  14  ⭐
  3PM:        7 × 3  =  21  ⭐ (Big bonus!)

Total: 229 PXP
```

**Impact**: Seven made three-pointers alone add **21 PXP**! Great for sharpshooters.

---

### Example 3: Double-Double + Hot Shooting

**Performance**: 36 mins, 28 pts, **14 reb**, 6 ast, 2 stl, 1 blk, **12-20 FG**, **4-6 3PT**

```
Base Stats:
  Minutes:   36 × 5  = 180
  Points:    28 × 2  =  56
  Rebounds:  14 × 2  =  28
  Assists:    6 × 3  =  18
  Steals:     2 × 5  =  10
  Blocks:     1 × 5  =   5

Shooting Bonuses:
  FGM:       12 × 2  =  24  ⭐
  3PM:        4 × 3  =  12  ⭐

Achievement Bonus:
  Double-Double     = +250  🏆

Total: 583 PXP
```

**Impact**: Hot shooting (12 FGM, 4 3PM) adds **36 PXP**, plus double-double bonus!

---

## Why This Matters

### Rewards Efficiency
- Players who shoot well earn more PXP
- Encourages shot selection
- High FG% performances are valued

### Sharpshooters Benefit
- Three-point specialists get extra rewards
- 7 made 3-pointers = 21 bonus PXP
- Steph Curry-style performances shine

### Stacks with Everything
- Still get PXP for points
- Still get PXP for the shot attempts that became points
- **Plus** bonus PXP for making the shot
- **Plus** double-double/triple-double bonuses

---

## Comparison: Before vs After

### Before (Old Formula):
**Stats**: 30 mins, 24 pts, 6 reb, 4 ast, 1 stl, 1 blk
```
PXP = 150 + 48 + 12 + 12 + 5 + 5 = 232 PXP
```

### After (New Formula):
**Stats**: 30 mins, 24 pts, 6 reb, 4 ast, 1 stl, 1 blk, **10-15 FG, 4-7 3PT**
```
PXP = 150 + 48 + 12 + 12 + 5 + 5 + 20 + 12 = 264 PXP
```

**Difference**: +32 PXP (14% increase) just from shooting bonuses!

---

## Implementation

### Backend: `backend/app/Services/ProgramService.php`

**Updated `calculatePlayerXPWithDetails()` method:**

```php
public function calculatePlayerXPWithDetails(array $stats): array
{
    $minutes = $stats['minutes'] ?? 0;
    $points = $stats['points'] ?? 0;
    $assists = $stats['assists'] ?? 0;
    $rebounds = $stats['rebounds'] ?? 0;
    $steals = $stats['steals'] ?? 0;
    $blocks = $stats['blocks'] ?? 0;
    $fgm = $stats['fgm'] ?? 0;  // ⭐ NEW
    $tpm = $stats['tpm'] ?? 0;  // ⭐ NEW

    // Base PXP calculation
    $basePxp = ($minutes * 5) +
        ($points * 2) +
        ($assists * 3) +
        ($rebounds * 2) +
        ($steals * 5) +
        ($blocks * 5) +
        ($fgm * 2) +      // ⭐ +2 PXP per made field goal
        ($tpm * 3);       // ⭐ +3 PXP per made three-pointer

    // ... rest of double-double/triple-double logic ...
}
```

**No frontend changes needed** - the stats are already captured!

---

## Testing Scenarios

### Test 1: Average Shooter
**Input**: 5-12 FG, 2-6 3PT
**Expected**: +10 FGM bonus, +6 3PM bonus = **+16 PXP**

### Test 2: Efficient Inside Scorer
**Input**: 10-12 FG (mostly layups/dunks), 0-0 3PT
**Expected**: +20 FGM bonus, +0 3PM bonus = **+20 PXP**

### Test 3: Three-Point Barrage
**Input**: 8-15 FG, 8-15 3PT (all FGM are 3PM)
**Expected**: +16 FGM bonus, +24 3PM bonus = **+40 PXP**

### Test 4: Cold Shooting Night
**Input**: 3-18 FG, 1-10 3PT
**Expected**: +6 FGM bonus, +3 3PM bonus = **+9 PXP**
**Note**: Poor efficiency still gets some PXP, but much less

---

## Balance Considerations

### Why +2 for FGM and +3 for 3PM?

1. **Value alignment**: Three-pointers are worth more (3 pts vs 2 pts)
2. **Difficulty factor**: Three-pointers are harder to make
3. **Already rewarded**: Points give PXP, so this is bonus on top
4. **Not overpowered**: A great shooting night adds ~20-40 PXP (meaningful but not dominant)

### Prevents Exploitation

- Can't "farm" PXP with bad shooting (need to make shots)
- Encourages real gameplay (shoot well to earn more)
- Balanced with other PXP sources (steals/blocks still worth more per event)

---

## Summary

✅ **Added**: +2 PXP per field goal made  
✅ **Added**: +3 PXP per three-pointer made  
✅ **Benefit**: Rewards efficient shooting  
✅ **Impact**: ~15-40 extra PXP per good shooting game  

Great performances now earn even more PXP, and sharpshooters get the recognition they deserve! 🏀🔥



