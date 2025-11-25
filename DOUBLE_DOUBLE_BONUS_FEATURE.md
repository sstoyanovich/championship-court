# Double-Double & Triple-Double PXP Bonus Feature

## Overview

Players now earn bonus PXP (Player XP) for achieving double-doubles and triple-doubles in games!

## What is a Double-Double / Triple-Double?

In basketball statistics:

- **Double-Double**: Getting 10+ in **two** stat categories (Points, Rebounds, Assists, Steals, Blocks)
- **Triple-Double**: Getting 10+ in **three** stat categories

### Examples:

**Double-Double Examples:**

- 15 points, 12 rebounds, 5 assists → Double-Double ✅ (PTS + REB)
- 8 points, 11 rebounds, 10 assists → Double-Double ✅ (REB + AST)
- 22 points, 4 rebounds, 11 assists → Double-Double ✅ (PTS + AST)

**Triple-Double Examples:**

- 18 points, 12 rebounds, 10 assists → Triple-Double 🏆 (PTS + REB + AST)
- 10 points, 15 rebounds, 10 blocks → Triple-Double 🏆 (PTS + REB + BLK)
- 12 points, 8 rebounds, 11 assists, 10 steals → Triple-Double 🏆 (PTS + AST + STL)

**Rare but Possible:**

- 8 points, 12 rebounds, 15 assists, 10 steals → Triple-Double 🏆 (REB + AST + STL - without 10+ points!)

---

## PXP Bonuses

### Base PXP Formula:

```
Base PXP = (Minutes × 5) + (Points × 2) + (Assists × 3) + (Rebounds × 2) +
           (Steals × 5) + (Blocks × 5) + (FGM × 2) + (3PM × 3)
```

**Shooting Bonuses**:

- **+2 PXP per field goal made** - Rewards efficient shooting
- **+3 PXP per three-pointer made** - Extra value for long-range shots

### New Bonuses:

- **Double-Double**: +250 PXP bonus
- **Triple-Double**: +500 PXP bonus

### Examples:

#### Example 1: Regular Performance

**Stats**: 25 mins, 12 pts, 5 reb, 3 ast, 1 stl, 0 blk, 5-10 FG, 2-5 3PT

```
Base PXP = (25×5) + (12×2) + (3×3) + (5×2) + (1×5) + (0×5) + (5×2) + (2×3)
         = 125 + 24 + 9 + 10 + 5 + 0 + 10 + 6 = 189 PXP
No bonus (only 1 category with 10+)
Total: 189 PXP
```

#### Example 2: Double-Double Performance

**Stats**: 35 mins, 18 pts, 12 reb, 4 ast, 2 stl, 1 blk, 7-14 FG, 4-8 3PT

```
Base PXP = (35×5) + (18×2) + (4×3) + (12×2) + (2×5) + (1×5) + (7×2) + (4×3)
         = 175 + 36 + 12 + 24 + 10 + 5 + 14 + 12 = 288 PXP
Double-Double Bonus: +250 PXP (PTS + REB both ≥ 10)
Total: 538 PXP
```

#### Example 3: Triple-Double Performance

**Stats**: 38 mins, 22 pts, 11 reb, 10 ast, 3 stl, 0 blk, 9-18 FG, 4-9 3PT

```
Base PXP = (38×5) + (22×2) + (10×3) + (11×2) + (3×5) + (0×5) + (9×2) + (4×3)
         = 190 + 44 + 30 + 22 + 15 + 0 + 18 + 12 = 331 PXP
Triple-Double Bonus: +500 PXP (PTS + REB + AST all ≥ 10)
Total: 831 PXP
```

---

## Implementation Details

### Backend Changes

#### File: `backend/app/Services/ProgramService.php`

**New Method: `calculatePlayerXPWithDetails()`**

```php
public function calculatePlayerXPWithDetails(array $stats): array
{
    // ... base calculation ...

    // Count categories with 10+
    $doubleDigitCategories = 0;
    $statCategories = [$points, $rebounds, $assists, $steals, $blocks];

    foreach ($statCategories as $stat) {
        if ($stat >= 10) {
            $doubleDigitCategories++;
        }
    }

    $bonus = 0;
    $achievement = null;

    if ($doubleDigitCategories >= 3) {
        $bonus = 500;
        $achievement = 'Triple-Double';
    } elseif ($doubleDigitCategories >= 2) {
        $bonus = 250;
        $achievement = 'Double-Double';
    }

    return [
        'pxp' => max(0, $basePxp + $bonus),
        'achievement' => $achievement,
        'bonus' => $bonus,
    ];
}
```

**Updated: `processGameCompletion()`**

- Now returns `pxp_earned` with achievement details:
  ```php
  [
      'player_id' => 123,
      'player_name' => 'Rob Dillingham',
      'pxp' => 512,
      'achievement' => 'Double-Double',
      'bonus' => 250
  ]
  ```

---

### Frontend Changes

#### File: `frontend/src/views/StatsUploadView.vue`

**New Section: Player PXP Summary**

Displays after stats are saved, showing:

- Each player's PXP earned
- Achievement badge (if earned)
- Bonus amount (if earned)

**Visual Design:**

1. **Regular Performance**

   ```
   ┌─────────────────────────────────────┐
   │ Rob Dillingham         168 PXP      │
   └─────────────────────────────────────┘
   ```

2. **Double-Double Achievement** (Yellow highlight)

   ```
   ┌─────────────────────────────────────┐
   │ ⭐ Rob Dillingham [DOUBLE-DOUBLE]   │
   │                           512 PXP   │
   │                         +250 bonus  │
   └─────────────────────────────────────┘
   ```

   - Yellow gradient background
   - Gold badge with star icon
   - Green bonus indicator

3. **Triple-Double Achievement** (Purple highlight)
   ```
   ┌─────────────────────────────────────┐
   │ 🏆 Rob Dillingham [TRIPLE-DOUBLE]   │
   │                           831 PXP   │
   │                         +500 bonus  │
   └─────────────────────────────────────┘
   ```
   - Yellow gradient background (highlighted)
   - **Purple badge with trophy icon** (animated pulse)
   - Green bonus indicator

**CSS Features:**

- Triple-double badges pulse with animation
- Hover effects on all PXP items
- Responsive design for mobile
- Color-coded badges:
  - **Double-Double**: Gold gradient (#fbbf24 → #f59e0b)
  - **Triple-Double**: Purple gradient (#a855f7 → #7c3aed)

---

## User Experience Flow

### Upload Stats → Review → **See Achievements!**

1. **Upload screenshot** with game stats
2. **Review and confirm** extracted stats
3. **Results screen shows**:
   - ⭐ **Player XP Earned** section (NEW!)
     - Each player listed with their PXP
     - Achievement badges for double-doubles/triple-doubles
     - Bonus PXP clearly shown
   - 🎯 Challenge Progress section
   - 🎁 Rewards Earned section

### Example Results Screen:

```
✅ Stats Saved Successfully!

⭐ Player XP Earned
┌─────────────────────────────────────────────┐
│ 🏆 Rob Dillingham [TRIPLE-DOUBLE]           │
│                                   831 PXP   │
│                                 +500 bonus  │
└─────────────────────────────────────────────┘
┌─────────────────────────────────────────────┐
│ Harrison Barnes                   145 PXP   │
└─────────────────────────────────────────────┘
┌─────────────────────────────────────────────┐
│ ⭐ Andrew Wiggins [DOUBLE-DOUBLE]            │
│                                   387 PXP   │
│                                 +250 bonus  │
└─────────────────────────────────────────────┘

🎯 Challenge Progress
[... challenges ...]

🎁 Rewards Earned
[... rewards ...]
```

---

## Testing Scenarios

### Test Case 1: Regular Performance (No Bonus)

**Input**: 20 mins, 8 pts, 6 reb, 4 ast, 1 stl, 0 blk
**Expected**: Base PXP only, no achievement badge

### Test Case 2: Double-Double (Points + Rebounds)

**Input**: 35 mins, 18 pts, 12 reb, 4 ast, 2 stl, 1 blk
**Expected**:

- Achievement: "Double-Double"
- Bonus: +250 PXP
- Badge: Gold with ⭐

### Test Case 3: Double-Double (Rebounds + Assists)

**Input**: 30 mins, 8 pts, 11 reb, 10 ast, 1 stl, 0 blk
**Expected**:

- Achievement: "Double-Double"
- Bonus: +250 PXP
- Note: Works even without 10+ points!

### Test Case 4: Triple-Double (Points + Rebounds + Assists)

**Input**: 38 mins, 22 pts, 11 reb, 10 ast, 3 stl, 0 blk
**Expected**:

- Achievement: "Triple-Double"
- Bonus: +500 PXP
- Badge: Purple with 🏆 (pulsing)

### Test Case 5: Triple-Double (Steals Included)

**Input**: 40 mins, 15 pts, 12 reb, 8 ast, 10 stl, 2 blk
**Expected**:

- Achievement: "Triple-Double"
- Bonus: +500 PXP
- Note: Any 3 categories work!

### Test Case 6: Quad-Double?! (4 categories with 10+)

**Input**: 45 mins, 18 pts, 15 reb, 12 ast, 10 stl, 3 blk
**Expected**:

- Achievement: "Triple-Double" (we only track triple, not quad)
- Bonus: +500 PXP

---

## Benefits

### For Players:

- ✅ **Encourages well-rounded play** - rewards players who contribute across multiple categories
- ✅ **Makes rare achievements meaningful** - triple-doubles are rewarded significantly
- ✅ **Clear visual feedback** - beautiful badges and animations celebrate achievements
- ✅ **Motivating** - players want to see those achievement badges!

### For Game Balance:

- ✅ **Fair system** - bonuses are substantial but not gamebreaking
- ✅ **Skill-based** - can't easily "farm" triple-doubles, requires genuine good performance
- ✅ **Flexible** - any combination of 2-3 stats works, not just points-focused

### For User Engagement:

- ✅ **Immediate gratification** - see achievements right after uploading
- ✅ **Share-worthy** - players will want to show off their triple-doubles
- ✅ **Clear progression** - bonuses help cards level up faster

---

## Future Enhancements

### Potential Additions:

1. **Stat Combo Bonuses**

   - "Defensive Domination": 10+ steals + 10+ blocks = +300 PXP
   - "Point Guard Excellence": 10+ assists + 5+ steals = +200 PXP
   - "Efficient Scorer": 20+ points on 60%+ FG = +150 PXP

2. **Achievement History**

   - Track how many double-doubles/triple-doubles each card has earned
   - Display on player card details page
   - "Career Achievements" section

3. **Special Challenges**

   - "Record a Triple-Double with any player" = reward
   - "Get 5 double-doubles with the same player" = reward
   - Program-specific achievement challenges

4. **Leaderboards**
   - Most triple-doubles recorded
   - Highest single-game PXP earned
   - Most double-doubles this season

---

## Summary

The double-double and triple-double bonus system is now live! Players will earn:

- **+250 PXP** for double-doubles (2 stats with 10+)
- **+500 PXP** for triple-doubles (3 stats with 10+)

The system is automatic - it checks every stat line uploaded, awards bonuses, and displays beautiful achievement badges in the results screen. No configuration needed, just upload your stats and watch the achievements roll in! 🏆⭐
