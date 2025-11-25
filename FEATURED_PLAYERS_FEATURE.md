# Featured Players with Individual Odds

## Overview

This feature allows admins to add special "chase cards" or featured players to packs with individual odds. For example, you can add Dwyane Wade with a 0.02% chance to appear in any pack.

Featured players are checked first before the normal tier-based selection logic runs, making them truly special and rare.

## Use Case

This is perfect for:

- **Limited Edition Cards**: Add rare legendary players with very low odds
- **Event-Specific Cards**: Feature special cards during events
- **Chase Cards**: Create excitement with ultra-rare pulls
- **Promotional Cards**: Add exclusive players with custom odds

## How It Works

### Pack Opening Flow

1. **User opens a pack** (standard or choice)
2. **For each card slot:**
   - **First**: System rolls against each featured player's odds
   - If a featured player hits → Use that player
   - If no featured player hits → Proceed with normal tier-based selection
3. User receives their cards

### Odds Calculation

- Featured players use **percentage odds** (0-100)
- Each featured player is rolled independently
- Roll precision: 2 decimal places (e.g., 0.02% = 2 in 10,000)
- Formula: `random(0, 100) <= odds`

**Example:**

- Dwyane Wade: 0.02% odds
- System generates random number: 0.00 to 100.00
- If number ≤ 0.02 → Player gets Dwyane Wade! 🎉
- If number > 0.02 → Continue to tier-based selection

## Implementation Details

### Database Changes

#### Migration: `2025_11_06_175356_add_featured_players_to_packs_table.php`

Added `featured_players` JSON column to `packs` table:

```php
$table->json('featured_players')->nullable()->after('specified_players');
```

**Structure:**

```json
[
  {
    "player_id": 123,
    "odds": 0.02
  },
  {
    "player_id": 456,
    "odds": 1.5
  }
]
```

### Backend Changes

#### 1. Pack Model (`backend/app/Models/Pack.php`)

**Added to `$fillable`:**

- `featured_players`

**Added to `$casts`:**

- `featured_players` => `'array'`

**New Methods:**

```php
// Check if pack has featured players
public function hasFeaturedPlayers(): bool

// Get featured players configuration
public function getFeaturedPlayers(): array
```

#### 2. PackController (`backend/app/Http/Controllers/PackController.php`)

**New Method:**

```php
private function rollForFeaturedPlayer(Pack $pack): ?Player
{
    if (!$pack->hasFeaturedPlayers()) {
        return null;
    }

    $featuredPlayers = $pack->getFeaturedPlayers();

    // Roll for each featured player
    foreach ($featuredPlayers as $featuredConfig) {
        $playerId = $featuredConfig['player_id'] ?? null;
        $odds = $featuredConfig['odds'] ?? 0;

        if (!$playerId || $odds <= 0) {
            continue;
        }

        // Roll random number between 0 and 100
        $roll = mt_rand(0, 10000) / 100; // 2 decimal precision

        // Check if the roll hits the odds
        if ($roll <= $odds) {
            $player = Player::find($playerId);
            if ($player) {
                return $player;
            }
        }
    }

    return null;
}
```

**Updated Methods:**

Both `openStandardPack()` and `generateChoicePackOptions()` now check for featured players:

```php
// First check for featured players with special odds
$featuredPlayer = $this->rollForFeaturedPlayer($pack);

if ($featuredPlayer) {
    $player = $featuredPlayer;
} else {
    // Normal tier-based selection...
}
```

### Frontend Changes

#### 1. Admin Store (`frontend/src/stores/admin.ts`)

**Updated Pack Interface:**

```typescript
interface Pack {
  // ... existing fields
  featured_players?: Array<{ player_id: number; odds: number }>;
}
```

#### 2. PackForm Component (`frontend/src/views/admin/packs/PackForm.vue`)

**New Section: "Featured Players"**

Added a dedicated UI section with:

- Player selection dropdown (shows all players with team and tier)
- Odds input field (percentage, up to 2 decimal places)
- Add/Remove buttons
- Special golden styling to distinguish from other sections

**Features:**

- Real-time validation (odds must be > 0)
- Hint text showing example: "e.g., 0.02 for 0.02%"
- Filter out invalid entries on save
- Load existing featured players when editing a pack

**Styling:**

```css
.featured-player-item {
  background: #fef3c7; /* Golden background */
  border: 1px solid #fbbf24; /* Golden border */
  /* Special styling to make it stand out */
}
```

## How to Use

### Creating a Pack with Featured Players

1. **Go to Admin → Packs → Create/Edit Pack**

2. **Scroll to "Featured Players (Optional)" section**

3. **Click "+ Add Featured Player"**

4. **Select a player** from the dropdown

   - Shows player name, team, and card tier
   - Example: "Dwyane Wade - Heat (galaxy_opal)"

5. **Enter the odds** as a percentage

   - For 0.02%: Enter `0.02`
   - For 1%: Enter `1`
   - For 10%: Enter `10`

6. **Add more featured players** if desired

   - Each rolls independently
   - Can have different odds

7. **Save the pack**

### Example Configurations

#### Ultra-Rare Legend Pack

```json
"featured_players": [
  {"player_id": 42, "odds": 0.02},  // Dwyane Wade - 0.02%
  {"player_id": 89, "odds": 0.05}   // LeBron James - 0.05%
]
```

#### Event Pack with Moderate Odds

```json
"featured_players": [
  {"player_id": 123, "odds": 5},    // Event Card 1 - 5%
  {"player_id": 456, "odds": 3}     // Event Card 2 - 3%
]
```

#### Guaranteed Featured Player

```json
"featured_players": [
  {"player_id": 789, "odds": 100}   // Always appears
]
```

## Interaction with Other Pack Features

### Featured Players + Tier Odds

- ✅ **Compatible**: Featured players roll first, then tier odds apply
- Example: Pack with 90% gold + Dwyane Wade at 0.02%
  - Each card: 0.02% chance for Wade, otherwise 90% gold

### Featured Players + Guaranteed Slots

- ✅ **Compatible**: Featured players can override guaranteed slots
- Example: Slot 5 guaranteed Emerald + Wade at 0.02%
  - 0.02% chance for Wade, otherwise guaranteed Emerald+

### Featured Players + Collection Restriction

- ✅ **Compatible**: Featured players ignore collection restriction
- Example: Lakers pack + Wade at 0.02%
  - Wade can appear even though he's not a Laker

### Featured Players + Specified Players (Choice Packs)

- ⚠️ **Overrides**: Specified players take full precedence
- If pack has specified_players, featured_players are ignored

## Important Notes

### 1. Multiple Featured Players

- Each featured player rolls **independently**
- It's possible (though rare) to get multiple featured players in one pack
- Order matters: First featured player to hit is used

### 2. Odds Precision

- System uses 2 decimal precision: 0.01% is the minimum
- Odds below 0.01% will be treated as 0%

### 3. Featured Players Don't Count Toward Total Odds

- Featured players are "bonus" rolls
- Normal tier odds remain at 100%
- Example: If you add Wade at 0.02%, your pack odds are still 100% + bonus 0.02% Wade chance

### 4. Player Validation

- If a featured player is deleted from database, it's silently skipped
- Pack will continue to work normally

### 5. Performance

- Featured player rolls are very fast (O(n) where n = number of featured players)
- No impact on pack opening performance

## Testing Examples

### Test 1: Very Low Odds (0.02%)

```
Expected: ~1 in 5,000 packs
Reality: Over 10,000 packs, should see ~2 Wade pulls
```

### Test 2: Multiple Featured Players

```
Pack Configuration:
- Wade: 0.02%
- LeBron: 0.05%

Open 1000 packs:
- Expected Wade: ~0.2 (might not see any)
- Expected LeBron: ~0.5 (might see 0-2)
```

### Test 3: Moderate Odds (5%)

```
Expected: ~1 in 20 packs
Reality: Over 100 packs, should see ~5 pulls
```

## SQL Examples

### View packs with featured players

```sql
SELECT id, name, featured_players
FROM packs
WHERE featured_players IS NOT NULL;
```

### Add featured player to existing pack

```sql
UPDATE packs
SET featured_players = '[{"player_id": 42, "odds": 0.02}]'
WHERE id = 15;
```

### Remove featured players

```sql
UPDATE packs
SET featured_players = NULL
WHERE id = 15;
```

## Benefits

1. **Excitement**: Creates "jackpot moments" for players
2. **Flexibility**: Easy to add/remove/adjust featured players
3. **Marketing**: Can promote special featured cards
4. **Events**: Perfect for limited-time events
5. **Economy**: Helps maintain rare card value

## Best Practices

1. **Keep odds realistic**: 0.01% to 5% is typical for chase cards
2. **Don't overuse**: Too many featured players dilutes excitement
3. **Tier matters**: Featured players should be high-tier (Diamond+)
4. **Communication**: Tell players about featured cards (marketing!)
5. **Testing**: Always test odds in dev before production
6. **Balance**: Consider impact on card economy

## Future Enhancements (Ideas)

- Featured player pools (rotate daily/weekly)
- Guaranteed featured player after X packs
- Pity timer system
- Featured player tracking/statistics
- Notification when someone pulls a featured player
- Featured player odds boosters/events

## Troubleshooting

### Featured player never appears

- Check odds are > 0
- Verify player_id exists in database
- Check pack is using random generation (not specified_players)
- Odds might just be very low (0.02% = 1 in 5,000)

### Featured player appears too often

- Verify odds entered correctly (0.02 vs 2 vs 20)
- Check for duplicate entries in featured_players array

### Featured player section not showing in admin

- Refresh page
- Check browser console for errors
- Verify you're on latest code version
