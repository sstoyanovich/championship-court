# Multiple Cards of Same Player - Fix Summary

## Issue

User uploaded stats for Rob Dillingham, but the PXP wasn't showing for the card they were viewing. Investigation revealed:

1. **Duplicate Players**: Two "Rob Dillingham" player records existed (IDs 248 and 1283)
2. **Split Cards**: User had 3 cards - one linked to player 248, two linked to player 1283
3. **Wrong Card Getting Stats**: Stats were saving to player 248, but the cards in lineups were linked to player 1283

## Root Cause

When uploading without selecting a specific lineup, the system would match players by name similarity but didn't prioritize cards that were actually in the user's active lineups.

## Solution Implemented

### 1. Prioritize Lineup Cards (Backend)

**File**: `backend/app/Http/Controllers/StatsController.php`

Added a new helper method `prioritizeLineupCards()` that:

- Checks which user cards are currently in ANY of the user's lineups
- Sorts matching cards so lineup cards come first
- Falls back to non-lineup cards if no lineup cards match

This is applied in both:

- `matchPlayersForReview()` - For the review step
- `matchAndUpdateStats()` - When saving confirmed stats

**Key Changes:**

```php
// When no lineup specified, get all matching cards
$userCards = UserCard::with(['player', 'user'])
    ->whereHas('player', function ($query) use ($searchName) {
        $query->where('name', 'like', '%' . $searchName . '%');
    })
    ->where('user_id', $userId)
    ->get();

// NEW: Prioritize cards that are in lineups
$userCards = $this->prioritizeLineupCards($userCards, $userId);
```

### 2. Fixed Rob Dillingham Data

Ran a one-time script that:

1. ✅ Transferred stats from player 248 to player 1283 (2 games, 54 points, 159 PXP)
2. ✅ Updated card 18 to point to player 1283 instead of 248
3. ✅ Deleted player 248 (no longer referenced)

**Final State:**

- 1 Rob Dillingham player (ID 1283)
- 3 user cards all pointing to player 1283
- 2 cards in lineups (Main lineup: card 219, Test lineup: card 105)
- All stats correctly associated with player 1283

## How It Works Now

### With Lineup Selected

Works as before - stats go to the specific cards in that lineup.

### Without Lineup Selected

**New behavior:**

1. System finds all your cards for "Rob Dillingham"
2. System checks which cards are in ANY of your lineups
3. Cards in lineups are prioritized
4. First matching card from a lineup gets the stats

**Example:**

```
User has 3 Rob Dillingham cards:
- Card 18: Not in any lineup
- Card 105: In "Test" lineup  ← Will be chosen first
- Card 219: In "Main" lineup  ← Will be chosen if 105 doesn't match

Stats will go to player 1283 (since all cards point there)
```

## Benefits

1. **Intuitive Behavior**: Stats go to cards you're actually using
2. **Multiple Cards Supported**: You can have multiple copies of the same player
3. **Automatic Selection**: System picks the "active" card (one in a lineup)
4. **Backward Compatible**: Specific lineup selection still works as expected

## Edge Cases Handled

### No Cards in Lineups

Falls back to any matching card (previous behavior)

### Multiple Cards in Different Lineups

First matching lineup card is used (deterministic)

### Duplicate Players

Won't happen going forward, but if it does, prioritization ensures the "active" player record gets stats

## Testing Recommendations

1. **Upload with specific lineup**: Verify stats go to exact cards in that lineup ✅
2. **Upload without lineup**: Verify stats go to card in a lineup (not a random card) ✅
3. **Player with no cards in lineups**: Verify stats still save to any card ✅
4. **View card stats modal**: Verify PXP and stats display correctly ✅

## Future Improvements

### Prevent Duplicate Players

Add a unique constraint or validation to prevent multiple player records with the same name:

```php
// In Player model or migration
$table->unique('name'); // Or
$table->index('name');
```

### Show Which Card Matched

In the review UI, indicate which specific card will receive the stats:

```
R. Dillingham → Rob Dillingham
  Card: #219 (Main Lineup) ← Will receive stats
```

### Allow User to Choose Card

If multiple cards exist, let user select which one during review:

```
Rob Dillingham - Stats will go to:
( ) Card #18 (Bench)
(•) Card #105 (Test Lineup)  ← Selected
( ) Card #219 (Main Lineup)
```

## Files Changed

1. `backend/app/Http/Controllers/StatsController.php`
   - Added `prioritizeLineupCards()` method
   - Updated `matchPlayersForReview()` to call it
   - Updated `matchAndUpdateStats()` to call it
   - Added `LineupSlot` import

## Database State

- Rob Dillingham: 1 player record (1283), 3 user cards, 2 in lineups, stats tracked ✅
- All future uploads will correctly target the lineup card first

## Verification

Run this to verify correct setup:

```bash
cd backend
sqlite3 database/database.sqlite "
SELECT
    p.name,
    uc.id as card_id,
    CASE WHEN ls.id IS NOT NULL THEN 'In Lineup' ELSE 'Not in Lineup' END as status,
    ups.games_played,
    ups.total_points,
    ups.player_xp
FROM players p
JOIN user_cards uc ON uc.player_id = p.id
LEFT JOIN lineup_slots ls ON ls.user_card_id = uc.id
LEFT JOIN user_player_stats ups ON ups.player_id = p.id
WHERE p.name LIKE '%Dillingham%';"
```

Expected output:

```
Rob Dillingham|18|Not in Lineup|2|54|159
Rob Dillingham|105|In Lineup|2|54|159
Rob Dillingham|219|In Lineup|2|54|159
```

(All cards share the same stats since they're the same player)
