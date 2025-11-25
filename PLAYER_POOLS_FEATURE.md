# Player Pools - Custom Tiered Packs with Specific Players

## Overview

Player Pools allow you to create custom tiers within a pack, where each tier contains specific players and has its own probability. This is perfect for curated packs where you want full control over which players appear and at what frequency.

## Use Case

You want to create a pack with:

- **Base tier**: 3 specific players with 90% chance
- **Medium tier**: 3 different specific players with 10% chance

Unlike normal tier-based odds (where players are random), Player Pools let you specify exactly which players are in each tier.

## Example Configuration

```json
"player_pools": [
  {
    "name": "Base",
    "odds": 90,
    "player_ids": [1, 2, 3]
  },
  {
    "name": "Medium",
    "odds": 10,
    "player_ids": [4, 5, 6]
  }
]
```

## How It Works

### Pack Opening Flow

1. **User opens a pack** (standard or choice)
2. **For each card slot:**
   - **First**: Check for featured players (if configured)
   - **Second**: If pack has player pools → Roll to select a pool based on odds
   - If pool selected → Pick random player from that pool's player list
   - If no pools → Use normal tier-based selection
3. User receives their cards

### Odds Calculation

- Each pool has a weight/odds value
- System uses weighted random selection to pick a pool
- Once a pool is selected, a random player from that pool is chosen
- All players within a pool have equal chance of being selected

**Example with 2 pools:**

```
Base Pool: odds = 90
Medium Pool: odds = 10
Total: 100

Roll 1-100:
- 1-90 → Base Pool (pick random player from [1,2,3])
- 91-100 → Medium Pool (pick random player from [4,5,6])
```

## Implementation Details

### Database Changes

#### Migration: `2025_11_09_225055_add_player_pools_to_packs_table.php`

Added `player_pools` JSON column to `packs` table:

```php
$table->json('player_pools')->nullable()->after('featured_players');
```

**Structure:**

```json
[
  {
    "name": "Base",
    "odds": 90,
    "player_ids": [1, 2, 3]
  },
  {
    "name": "Premium",
    "odds": 10,
    "player_ids": [10, 11, 12]
  }
]
```

### Backend Changes

#### 1. Pack Model (`backend/app/Models/Pack.php`)

**Added to `$fillable`:**

- `player_pools`

**Added to `$casts`:**

- `player_pools` => `'array'`

**New Methods:**

```php
// Check if pack has player pools
public function hasPlayerPools(): bool

// Get player pools configuration
public function getPlayerPools(): array
```

#### 2. PackController (`backend/app/Http/Controllers/PackController.php`)

**New Methods:**

```php
private function getPlayerFromPools(Pack $pack): ?Player
{
    $pools = $pack->getPlayerPools();

    if (empty($pools)) {
        return null;
    }

    // Build odds array for weighted selection
    $poolOdds = [];
    foreach ($pools as $index => $pool) {
        $odds = $pool['odds'] ?? 0;
        if ($odds > 0) {
            $poolOdds[$index] = $odds;
        }
    }

    if (empty($poolOdds)) {
        return null;
    }

    // Select a pool based on weighted odds
    $selectedPoolIndex = $this->getWeightedRandomKey($poolOdds);
    $selectedPool = $pools[$selectedPoolIndex] ?? null;

    if (!$selectedPool || empty($selectedPool['player_ids'])) {
        return null;
    }

    // Get a random player from the selected pool
    $playerIds = $selectedPool['player_ids'];
    $randomPlayerId = $playerIds[array_rand($playerIds)];

    return Player::find($randomPlayerId);
}

private function getWeightedRandomKey(array $weights)
{
    $totalWeight = array_sum($weights);
    $random = mt_rand(1, $totalWeight);

    foreach ($weights as $key => $weight) {
        $random -= $weight;
        if ($random <= 0) {
            return $key;
        }
    }

    return array_key_first($weights);
}
```

**Updated Methods:**

Both `openStandardPack()` and `generateChoicePackOptions()` now check for player pools:

```php
// First check for featured players
$featuredPlayer = $this->rollForFeaturedPlayer($pack);

if ($featuredPlayer) {
    $player = $featuredPlayer;
} elseif ($pack->hasPlayerPools()) {
    // Use player pools if configured
    $player = $this->getPlayerFromPools($pack);
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
  player_pools?: Array<{ name: string; odds: number; player_ids: number[] }>;
}
```

#### 2. PackForm Component (`frontend/src/views/admin/packs/PackForm.vue`)

**New Section: "Player Pools"**

Added a comprehensive UI section with:

- Pool name input
- Pool odds input (percentage)
- Dynamic player selection for each pool
- Add/Remove pools
- Add/Remove players from pools
- Blue styling to distinguish from other sections

**Features:**

- Hierarchical structure: Pools contain players
- Real-time validation (pools must have name, odds > 0, and at least 1 player)
- Filter out invalid entries on save
- Load existing player pools when editing a pack
- Nested add/remove buttons for intuitive management

**Styling:**

```css
.player-pool-item {
  background: #dbeafe; /* Blue background */
  border: 2px solid #3b82f6; /* Blue border */
  /* Nested structure with pool header and player list */
}
```

## How to Use

### Creating a Pack with Player Pools

1. **Go to Admin → Packs → Create/Edit Pack**

2. **Scroll to "Player Pools (Optional)" section**

3. **Click "+ Add Player Pool"**

4. **Configure the pool:**

   - **Pool Name**: e.g., "Base", "Medium", "Premium"
   - **Odds (%)**: e.g., 90 for base tier, 10 for medium tier

5. **Add players to the pool:**

   - Click "+ Add Player to Pool"
   - Select player from dropdown (shows name, team, tier)
   - Repeat to add more players

6. **Add more pools** if desired (e.g., another pool with different odds)

7. **Ensure odds add up logically** (e.g., 90 + 10 = 100)

8. **Save the pack**

### Example Configurations

#### Simple Two-Tier Pack

```json
"player_pools": [
  {
    "name": "Base",
    "odds": 90,
    "player_ids": [10, 20, 30]
  },
  {
    "name": "Premium",
    "odds": 10,
    "player_ids": [100, 101, 102]
  }
]
```

**Result**: 90% chance for Base players, 10% for Premium

#### Three-Tier Pack

```json
"player_pools": [
  {
    "name": "Common",
    "odds": 70,
    "player_ids": [1, 2, 3, 4, 5]
  },
  {
    "name": "Rare",
    "odds": 25,
    "player_ids": [10, 11, 12]
  },
  {
    "name": "Epic",
    "odds": 5,
    "player_ids": [50, 51]
  }
]
```

**Result**: 70% common, 25% rare, 5% epic

#### Event Pack with Specific Players

```json
"player_pools": [
  {
    "name": "Heat Legends",
    "odds": 60,
    "player_ids": [42, 88, 33]  // Wade, Shaq, Alonzo
  },
  {
    "name": "Lakers Legends",
    "odds": 40,
    "player_ids": [23, 24, 32]  // LeBron, Kobe, Magic
  }
]
```

**Result**: Themed pack with specific legendary players

## Interaction with Other Pack Features

### Player Pools + Featured Players

- ✅ **Compatible**: Featured players are checked first
- Example: Featured player at 0.02% + player pools
  - 0.02% chance for featured player
  - 99.98% chance goes to player pools

### Player Pools + Tier Odds

- ⚠️ **Player Pools Override Tier Odds**
- If player_pools are configured, tier odds (odds_config) are ignored
- Choose one or the other, not both

### Player Pools + Guaranteed Slots

- ⚠️ **Player Pools Override Guaranteed Slots**
- Guaranteed slots are ignored when player pools are configured

### Player Pools + Collection Restriction

- ⚠️ **Player Pools Override Collection Restriction**
- Player pools can include players from any collection
- Collection restriction is ignored

### Player Pools + Specified Players (Choice Packs)

- ⚠️ **Specified Players Take Precedence**
- If pack has specified_players, player_pools are ignored

## Important Notes

### 1. Odds Don't Need to Equal 100

- You can use any positive numbers as weights
- Example: `[{odds: 9}, {odds: 1}]` = 90%/10%
- System uses proportional weighting

### 2. All Players in a Pool Have Equal Chance

- Once a pool is selected, each player has equal odds
- 3 players in pool = 33.33% each
- 5 players in pool = 20% each

### 3. Player Pools Override Most Settings

- When configured, player pools take priority over:
  - Tier-based odds (odds_config)
  - Guaranteed slots
  - Collection restrictions
- Only featured players and specified players have higher priority

### 4. Empty Pools Are Skipped

- If a pool has odds > 0 but no players, it's skipped
- Pack will fall back to other configured selection methods

### 5. Player Validation

- If a player in a pool is deleted from database, it's handled gracefully
- Other players in the pool remain available

### 6. Multiple Pools Recommended

- Having only 1 pool defeats the purpose
- Use at least 2 pools with different odds for tiered packs

## Best Practices

1. **Name pools clearly**: "Base", "Rare", "Epic" or "Bronze Tier", "Gold Tier"
2. **Make odds intuitive**: 90/10, 70/20/10, 80/15/5
3. **Balance player counts**: Don't put 10 players in one pool and 1 in another
4. **Use for curated content**: Perfect for themed packs, event packs, or guarantee specific players
5. **Test your pools**: Open packs in dev to verify distribution feels right
6. **Document your pools**: Keep track of what players are in which pools

## When to Use Player Pools vs Other Features

### Use Player Pools When:

- ✅ You want specific players with specific odds
- ✅ Creating themed/curated packs
- ✅ Need guaranteed diversity (mix of specific players)
- ✅ Building tiered packs with known players

### Use Featured Players When:

- ✅ Adding rare "chase cards" to any pack
- ✅ Want individual player odds (not grouped)
- ✅ Ultra-low odds (0.02% type scenarios)

### Use Tier-Based Odds When:

- ✅ Want random player selection
- ✅ Don't care about specific players
- ✅ Standard randomized packs

### Use Specified Players When:

- ✅ Choice pack with exact options
- ✅ Users pick from specific list
- ✅ No randomization needed

## Troubleshooting

### Players not appearing from expected pool

- Check odds are > 0
- Verify player_ids exist in database
- Ensure pool has at least 1 valid player
- Check player_pools is not null/empty

### Odds feel wrong

- Calculate total odds (should sum to reasonable number)
- Remember: Odds are weighted, not percentages
- [10, 90] = 10% / 90% not 10% / 10%

### Player pools not working

- Verify no specified_players configured (takes precedence)
- Check pack has card_count > 0
- Ensure JSON structure is correct

### Seeing wrong players

- Check player_ids in pools match intended players
- Verify no typos in pool configuration
- Test with SQL query to confirm player IDs

## SQL Examples

### View packs with player pools

```sql
SELECT id, name, player_pools
FROM packs
WHERE player_pools IS NOT NULL;
```

### Add player pools to existing pack

```sql
UPDATE packs
SET player_pools = '[
  {"name": "Base", "odds": 90, "player_ids": [1,2,3]},
  {"name": "Premium", "odds": 10, "player_ids": [10,11,12]}
]'
WHERE id = 15;
```

### Remove player pools

```sql
UPDATE packs
SET player_pools = NULL
WHERE id = 15;
```

### Find all players in a specific pool

```sql
SELECT p.*
FROM players p
WHERE p.id IN (1, 2, 3);  -- Replace with your pool's player_ids
```

## Advanced Examples

### Weighted Pools (Non-100)

```json
"player_pools": [
  {"name": "Common", "odds": 900, "player_ids": [...]},
  {"name": "Rare", "odds": 95, "player_ids": [...]},
  {"name": "Legendary", "odds": 5, "player_ids": [...]}
]
```

Total = 1000, results in 90% / 9.5% / 0.5%

### Single Player Pools (Guaranteed Specific Players)

```json
"player_pools": [
  {"name": "Starter", "odds": 50, "player_ids": [1]},
  {"name": "Veteran", "odds": 50, "player_ids": [2]}
]
```

Result: 50% chance of player 1, 50% chance of player 2

### Many Players in Pool

```json
"player_pools": [
  {"name": "Rookies", "odds": 80, "player_ids": [1,2,3,4,5,6,7,8,9,10]},
  {"name": "Veterans", "odds": 20, "player_ids": [50,51,52]}
]
```

Result: 80% for any of 10 rookies, 20% for any of 3 veterans

## Benefits

1. **Full Control**: Know exactly which players can appear
2. **Tiered Rarity**: Create common/rare/epic tiers with specific players
3. **Themed Packs**: Lakers pack, Rookies pack, Legends pack
4. **Predictable Variety**: Guarantee mix of specific players
5. **Easy Management**: Admin UI makes it simple to configure

## Future Enhancements (Ideas)

- Pool weights based on time of day
- Dynamic pools that change daily/weekly
- Pool analytics (track which pools are hit most)
- Duplicate protection within pools
- Minimum/maximum players per pool
- Pool inheritance (pools inherit from other pools)

## Comparison Table

| Feature          | Player Pools         | Featured Players       | Tier-Based     | Specified Players |
| ---------------- | -------------------- | ---------------------- | -------------- | ----------------- |
| Specific Players | ✅ Yes               | ✅ Yes                 | ❌ No          | ✅ Yes            |
| Custom Odds      | ✅ Pool-level        | ✅ Individual          | ✅ Tier-level  | ❌ No             |
| Randomization    | ✅ Within pools      | ❌ No                  | ✅ Yes         | ❌ No             |
| Best For         | Tiered curated packs | Ultra-rare chase cards | Standard packs | Choice packs      |
| Complexity       | Medium               | Low                    | Low            | Low               |

## Summary

Player Pools give you the best of both worlds:

- **Control** over which players appear (like specified players)
- **Randomization** to keep things interesting (like tier-based odds)
- **Tiered rarity** with custom odds (like featured players)

Perfect for creating engaging, curated pack experiences where you want specific players to appear at specific frequencies!
