# Program-Exclusive Cards Feature

## Overview

The card system now supports marking certain cards as **program-exclusive**, meaning they can **only** be obtained through programs (as rewards) and **cannot** be pulled from packs. This is a common feature in card collection games where special high-tier cards are exclusive rewards for completing challenges or reaching program milestones.

---

## Use Cases

### Examples of Program-Exclusive Cards

1. **Season Reward Cards** - Exclusive cards for completing seasonal programs
2. **Championship Cards** - Special cards for winning tournaments
3. **Collection Completion Rewards** - Unique cards earned by completing specific collections
4. **Event Milestone Cards** - Limited-time cards from special events
5. **Legend Cards** - Ultra-rare cards only available as top-tier program rewards

---

## How It Works

### Database Field

A new boolean field `obtainable_from_packs` has been added to the `players` table:

- `true` (default) - Card can be obtained from packs (normal behavior)
- `false` - Card is program-exclusive and will **never** appear in packs

### Automatic Filtering

When opening any pack (standard or choice), the system automatically:

1. Filters out all cards where `obtainable_from_packs = false`
2. Only selects from cards that are obtainable from packs
3. Applies collection restrictions and guaranteed tier slots as normal

### Program Rewards

Program-exclusive cards can still be awarded through:

- Program XP threshold rewards
- Program challenge completion rewards
- Collection completion rewards
- Manual admin actions

---

## Implementation Details

### Database Migration

**Migration:** `add_obtainable_from_packs_to_players_table`

```php
Schema::table('players', function (Blueprint $table) {
    $table->boolean('obtainable_from_packs')->default(true);
});
```

- **Default:** `true` (backward compatible - all existing cards remain obtainable from packs)
- **Position:** After `collection_id` field
- **Type:** Boolean

### Player Model Updates

**New Field:**

```php
protected $fillable = [
    // ...
    'obtainable_from_packs',
];

protected $casts = [
    'obtainable_from_packs' => 'boolean',
];
```

**New Query Scopes:**

```php
// Get only cards obtainable from packs
Player::obtainableFromPacks()->get();

// Get only program-exclusive cards
Player::programExclusive()->get();
```

### PackController Updates

The `getRandomPlayer()` method now automatically filters:

```php
private function getRandomPlayer(string $tier, ?int $collectionId): ?Player
{
    $query = Player::where('card_tier', $tier)
        ->obtainableFromPacks(); // Excludes program-exclusive cards

    if ($collectionId !== null) {
        $query->where('collection_id', $collectionId);
    }

    return $query->inRandomOrder()->first();
}
```

This ensures program-exclusive cards **never** appear in:

- Standard packs
- Choice packs
- Guaranteed tier slots
- Collection-restricted packs

---

## Admin Interface

### Creating/Editing Players

In the admin player form:

1. Navigate to **Admin → Players → Create/Edit Player**
2. In the **Basic Information** section, find the checkbox:
   - ☑ **Obtainable from Packs** (checked by default)
3. To make a card program-exclusive:
   - ☐ Uncheck "Obtainable from Packs"
   - Add helpful note in description if desired
4. Save the player

**Visual Indicator:**
The checkbox includes a helpful hint:

> "Uncheck if this card is program-exclusive (can only be earned as rewards)"

### Frontend Interface

**TypeScript Interface:**

```typescript
interface Player {
  id: number;
  name: string;
  // ... other fields
  obtainable_from_packs?: boolean;
}
```

---

## Usage Examples

### Example 1: Season Reward Card

Creating a special LeBron James "Season Champion" card that's only available as a season completion reward:

```json
{
  "name": "LeBron James (Season Champion)",
  "overall_rating": 99,
  "card_tier": "pink_diamond",
  "team": "Los Angeles Lakers",
  "collection_id": 14,
  "obtainable_from_packs": false,
  "description": "Exclusive Season 1 Completion Reward"
}
```

This card will:

- ✅ Be available as a program reward
- ❌ Never appear in any pack (even Lakers packs)
- ✅ Still count towards Lakers collection
- ✅ Be visible in admin interface with special status

### Example 2: Milestone Collection Card

Creating a special "All-Star MVP" card for completing all team collections:

```json
{
  "name": "Stephen Curry (All-Star MVP)",
  "overall_rating": 98,
  "card_tier": "pink_diamond",
  "team": "Golden State Warriors",
  "obtainable_from_packs": false
}
```

This card can be set as a reward for:

- Completing all 30 NBA team collections
- Reaching a specific program milestone
- Winning a tournament

### Example 3: Regular Pack Card (Default)

Standard cards work as before (default behavior):

```json
{
  "name": "Stephen Curry",
  "overall_rating": 96,
  "card_tier": "diamond",
  "team": "Golden State Warriors",
  "obtainable_from_packs": true // Default value
}
```

This card:

- ✅ Can appear in all packs
- ✅ Can be awarded as a program reward
- ✅ Normal behavior (no changes to existing system)

---

## Setting Up Program-Exclusive Rewards

### Step 1: Create the Program-Exclusive Player

```sql
-- In admin panel or via direct DB
UPDATE players
SET obtainable_from_packs = false
WHERE id = 123;
```

### Step 2: Add as Program Reward

In the program rewards configuration:

```json
{
  "xp_threshold": 500000,
  "reward_type": "player_card",
  "player_id": 123,
  "description": "Exclusive 99 OVR LeBron James"
}
```

### Step 3: Test

1. Try opening various packs - card should never appear
2. Complete the program to milestone
3. Verify card is awarded correctly

---

## Querying Program-Exclusive Cards

### Get All Program-Exclusive Cards

```php
$exclusiveCards = Player::programExclusive()->get();
```

### Get All Pack-Obtainable Cards

```php
$packCards = Player::obtainableFromPacks()->get();
```

### Get Program-Exclusive Cards by Tier

```php
$exclusivePinkDiamonds = Player::programExclusive()
    ->where('card_tier', 'pink_diamond')
    ->get();
```

### Check if a Specific Card is Program-Exclusive

```php
$player = Player::find($id);
if (!$player->obtainable_from_packs) {
    // This card is program-exclusive
}
```

---

## Statistics & Monitoring

### Count Program-Exclusive vs Pack Cards

```php
$packCards = Player::obtainableFromPacks()->count();
$exclusiveCards = Player::programExclusive()->count();

// Per tier breakdown
$exclusiveByTier = Player::programExclusive()
    ->selectRaw('card_tier, count(*) as count')
    ->groupBy('card_tier')
    ->get();
```

### Admin Dashboard Display

You can display program-exclusive status in player listings:

```vue
<template>
  <div class="player-card">
    <h3>{{ player.name }}</h3>
    <span v-if="!player.obtainable_from_packs" class="exclusive-badge">
      🏆 Program Exclusive
    </span>
  </div>
</template>
```

---

## Best Practices

### 1. **Use Sparingly**

Don't make too many cards program-exclusive. Keep most cards obtainable from packs to maintain pack value.

**Recommended Ratio:** ~5-10% of cards should be program-exclusive

### 2. **High-Tier Cards Only**

Typically reserve program exclusivity for:

- Diamond and above tiers
- Special edition cards
- Legend/historical player cards
- Event-specific cards

### 3. **Clear Communication**

When setting a card as program-exclusive:

- Include "Program Exclusive" or "Reward Only" in the description
- Document which program/challenge awards the card
- Update player card art to indicate special status

### 4. **Balance Across Programs**

Distribute exclusive cards across multiple programs:

- Season completion rewards
- Collection completion rewards
- Challenge completion rewards
- Event participation rewards

### 5. **Update Pack Descriptions**

When creating packs, clarify that program-exclusive cards are not available:

```json
{
  "name": "Premium Pack",
  "description": "5 random players from all pack-obtainable cards. Does not include program-exclusive rewards."
}
```

---

## Testing Checklist

Before deploying program-exclusive cards:

- [ ] Create a program-exclusive card (set `obtainable_from_packs = false`)
- [ ] Open 10+ standard packs - verify card never appears
- [ ] Open 10+ premium packs - verify card never appears
- [ ] Open collection-specific packs - verify card never appears
- [ ] Check guaranteed tier packs - verify card never appears
- [ ] Verify card can be awarded through program rewards
- [ ] Verify card appears in user's collection after award
- [ ] Test querying program-exclusive cards in admin panel
- [ ] Verify backward compatibility (existing cards still work)

---

## Troubleshooting

### Issue: Program-Exclusive Card Appearing in Packs

**Check:**

1. Verify `obtainable_from_packs = false` in database
2. Clear application cache: `php artisan cache:clear`
3. Check if `obtainableFromPacks()` scope is being used in PackController
4. Restart Laravel server

### Issue: Card Not Appearing as Reward

**Check:**

1. Program reward configuration is correct
2. Card `player_id` matches the program-exclusive card
3. Program is active and user meets requirements
4. Check program service logs for reward errors

### Issue: Admin Form Not Saving State

**Check:**

1. `obtainable_from_packs` is in Player model's `$fillable` array
2. Frontend form includes the checkbox in formData
3. Check browser console for errors
4. Verify API request includes the field

---

## Migration Path

### For Existing Installations

The migration is fully backward compatible:

1. **Run Migration:**

   ```bash
   cd backend
   php artisan migrate
   ```

2. **All Existing Cards:**

   - Automatically set to `obtainable_from_packs = true`
   - Continue working in packs as before
   - No changes needed

3. **Identify Future Program-Exclusive Cards:**

   - Review existing high-tier cards
   - Decide which should become program-exclusive
   - Update via admin panel or SQL

4. **Optional: Bulk Update**
   ```sql
   -- Example: Make all Galaxy Opal cards program-exclusive
   UPDATE players
   SET obtainable_from_packs = false
   WHERE card_tier = 'galaxy_opal';
   ```

---

## API Endpoints

### Get Program-Exclusive Cards

```http
GET /api/admin/players?filter=program_exclusive
```

### Update Card Status

```http
PUT /api/admin/players/{id}
Content-Type: application/json

{
  "obtainable_from_packs": false
}
```

---

## Future Enhancements

Potential improvements:

1. **Exclusivity Periods**

   - Time-limited exclusivity (exclusive for 30 days, then becomes pack-obtainable)
   - Add `pack_release_date` field

2. **Exclusivity Types**

   - Program-exclusive
   - Event-exclusive
   - Store-exclusive (purchasable only)
   - Convert boolean to enum

3. **Visual Indicators**

   - Special card borders for program-exclusive cards
   - Badges in collection view
   - Filter in pack opening animations

4. **Analytics Dashboard**
   - Track program-exclusive card acquisition rates
   - Monitor which programs award the most exclusive cards
   - User engagement with program rewards

---

## Summary

✅ **Implemented Features:**

- Database field for marking cards as program-exclusive
- Automatic filtering in pack opening logic
- Admin UI for managing card exclusivity
- Query scopes for easy filtering
- Full backward compatibility

✅ **Files Modified:**

- Migration: `2025_10_31_171608_add_obtainable_from_packs_to_players_table.php`
- Model: `backend/app/Models/Player.php`
- Controller: `backend/app/Http/Controllers/PackController.php`
- Frontend Store: `frontend/src/stores/admin.ts`
- Admin Form: `frontend/src/views/admin/players/PlayerForm.vue`

✅ **Key Benefits:**

- Increases value of program completion
- Creates exclusive rewards for dedicated players
- Maintains pack opening excitement for rare cards
- Provides flexibility in reward structure
- No impact on existing functionality

---

## Related Documentation

- [Pack Enhancements](./PACK_ENHANCEMENTS.md) - Guaranteed slots and collection restrictions
- [Collections System](./COLLECTIONS_IMPLEMENTATION_SUMMARY.md) - Collection completion rewards
- [Programs System](./TEAM_AFFINITY_DETAILED_STRUCTURE.md) - Program rewards structure

---

The program-exclusive cards system adds strategic depth to your card game economy, allowing you to create meaningful progression rewards while maintaining the excitement of pack openings!
