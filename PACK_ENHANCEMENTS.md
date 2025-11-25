# Pack System Enhancements

## Overview

The pack system has been enhanced to support two new powerful features:

1. **Guaranteed Tier Slots** - Specific card slots that guarantee a minimum tier
2. **Collection Restrictions** - Packs that only contain cards from a specific collection

---

## New Features

### 1. Guaranteed Tier Slots

Allows you to specify that certain card slots in a pack must meet or exceed a minimum tier.

**Example Use Case:**

- A pack with 4 random cards (normal odds) + 1 guaranteed Emerald or above card

**How It Works:**

- Define slots with minimum tier requirements in the `guaranteed_slots` JSON array
- Format: `[{"slot": 5, "min_tier": "emerald"}]`
- The system uses weighted odds from only the eligible tiers (at or above the minimum)

**Tier Hierarchy:**

1. Bronze (lowest)
2. Silver
3. Gold
4. Emerald
5. Sapphire
6. Amethyst
7. Diamond
8. Pink Diamond (highest)

### 2. Collection Restrictions

Allows you to create packs that only contain cards from a specific collection.

**Example Use Cases:**

- Lakers Team Pack - Only Lakers players
- Veterans Collection Pack - Only veteran players
- Rookie Pack - Only rookie cards

**How It Works:**

- Set the `collection_id` field to link the pack to a specific collection
- The system filters player selection to only include cards from that collection
- Combines with normal odds and guaranteed slots

---

## Database Changes

### Migration: `add_enhanced_pack_features_to_packs_table`

Added two new columns to the `packs` table:

```php
$table->json('guaranteed_slots')->nullable();
// Example: [{"slot": 5, "min_tier": "emerald"}]

$table->foreignId('collection_id')->nullable()->constrained();
// Links to a specific collection for card pool restriction
```

---

## Backend Implementation

### Pack Model Updates

**New Fields:**

- `guaranteed_slots` (JSON, casted to array)
- `collection_id` (nullable foreign key)

**New Methods:**

```php
getGuaranteedSlots(): array
hasGuaranteedSlot(int $slot): bool
getMinimumTierForSlot(int $slot): ?string
isCollectionRestricted(): bool
collection(): BelongsTo  // Relationship to Collection model
```

### PackController Updates

**New Private Methods:**

1. `getTierMeetingMinimum(string $minimumTier, array $allOdds): string`

   - Filters odds to only include tiers at or above the minimum
   - Uses weighted random selection from eligible tiers

2. `getRandomPlayer(string $tier, ?int $collectionId): ?Player`
   - Gets a random player of a specific tier
   - Optionally filters by collection

**Updated Methods:**

- `openStandardPack()` - Now checks for guaranteed slots and collection filtering
- `generateChoicePackOptions()` - Now supports guaranteed slots and collection filtering

---

## Frontend Implementation

### Admin Pack Form Updates

**New UI Sections:**

1. **Collection Restriction**

   - Dropdown to select a collection
   - Shows all available collections
   - "All Collections" option for unrestricted packs

2. **Guaranteed Tier Slots**
   - Dynamic list of guaranteed slot configurations
   - For each slot: specify slot number and minimum tier
   - Add/Remove buttons for managing slots
   - Validated against pack's card count

**Updated Interface:**

```typescript
interface Pack {
  // ... existing fields
  guaranteed_slots?: Array<{ slot: number; min_tier: string }>;
  collection_id?: number;
}
```

---

## Example Packs

### 1. Guaranteed Emerald Pack

```json
{
  "name": "Guaranteed Emerald Pack",
  "type": "standard",
  "description": "4 random players plus 1 guaranteed Emerald or better!",
  "card_count": 5,
  "guaranteed_slots": [{ "slot": 5, "min_tier": "emerald" }],
  "collection_id": null,
  "cost": 4500
}
```

**Result:** Opens 4 cards with normal odds, then 1 card guaranteed to be Emerald, Sapphire, Amethyst, Diamond, or Pink Diamond.

### 2. Lakers Team Pack

```json
{
  "name": "Lakers Team Pack",
  "type": "standard",
  "description": "Only Los Angeles Lakers players!",
  "card_count": 4,
  "guaranteed_slots": null,
  "collection_id": 14,
  "cost": 2000
}
```

**Result:** Opens 4 cards, all from the Los Angeles Lakers collection.

### 3. Combined Example

You can combine both features:

```json
{
  "name": "Elite Lakers Pack",
  "card_count": 5,
  "guaranteed_slots": [{ "slot": 5, "min_tier": "gold" }],
  "collection_id": 14
}
```

**Result:** Opens 4 random Lakers players with normal odds, plus 1 Lakers player guaranteed to be Gold tier or above.

---

## Usage in Admin Panel

### Creating a Pack with Guaranteed Slots

1. Navigate to Admin → Packs → Create Pack
2. Fill in basic pack information (name, type, cost, etc.)
3. Configure odds as usual
4. Scroll to "Guaranteed Tier Slots" section
5. Click "+ Add Guaranteed Slot"
6. Enter slot number (e.g., 5 for the last card in a 5-card pack)
7. Select minimum tier (e.g., "Emerald")
8. Add more slots if needed
9. Save

### Creating a Collection-Restricted Pack

1. Navigate to Admin → Packs → Create Pack
2. Fill in basic pack information
3. In "Collection Restriction" section, select a collection from dropdown
4. Configure odds and other settings as usual
5. Save

---

## Technical Details

### Weighted Random with Minimum Tier

When a guaranteed slot requires a minimum tier:

1. System finds the tier's position in the hierarchy
2. Filters the odds_config to only include tiers at or above that position
3. Performs weighted random selection from eligible tiers
4. Selects a random player of that tier (filtered by collection if applicable)

**Example:**

```
Pack odds: Bronze 50%, Silver 35%, Gold 10%, Emerald 3.5%, Sapphire 1%, ...
Guaranteed minimum: Emerald

Eligible tiers: Emerald 3.5%, Sapphire 1%, Amethyst 0.3%, Diamond 0.15%, Pink Diamond 0.05%
Total weight: 5%

Weighted random selection from these 5 tiers only.
```

### Collection Filtering

When a pack is restricted to a collection:

```php
$query = Player::where('card_tier', $tier);
if ($collectionId !== null) {
    $query->where('collection_id', $collectionId);
}
return $query->inRandomOrder()->first();
```

This ensures all players pulled are from the specified collection.

---

## Testing

To test the new features:

1. **Create a guaranteed pack:**

   ```bash
   # Use admin panel to create a pack with guaranteed_slots
   ```

2. **Open the pack multiple times:**

   ```bash
   # Verify the guaranteed slot always has minimum tier or above
   ```

3. **Create a collection-restricted pack:**

   ```bash
   # Use admin panel to create a Lakers pack (collection_id: 14)
   ```

4. **Open the collection pack:**
   ```bash
   # Verify all cards are from the Lakers
   ```

---

## Future Enhancements

Potential improvements for the pack system:

1. **Multiple guaranteed slots per pack**

   - Already supported! Just add multiple entries to guaranteed_slots array

2. **Guaranteed specific players**

   - Add a `guaranteed_player_ids` field

3. **Topper cards**

   - Special bonus cards in certain packs

4. **Dynamic pack odds**

   - Odds that change based on time of day, events, etc.

5. **Pack bundles**
   - Buy multiple packs at once with bonuses

---

## Migration & Seeding

The enhancement is fully backward compatible:

- Existing packs continue to work (both new fields are nullable)
- New packs can use either, both, or neither feature
- Migration adds columns without breaking existing data

To apply:

```bash
cd backend
php artisan migrate
php artisan db:seed --class=PackSeeder  # Optional: adds example packs
```

---

## Summary

✅ **Implemented Features:**

- Guaranteed tier slots with weighted selection
- Collection-restricted card pools
- Full admin UI support
- Backward compatible with existing packs
- Example packs demonstrating both features

✅ **Files Modified:**

- Migration: `2025_10_31_170521_add_enhanced_pack_features_to_packs_table.php`
- Model: `backend/app/Models/Pack.php`
- Controller: `backend/app/Http/Controllers/PackController.php`
- Seeder: `backend/database/seeders/PackSeeder.php`
- Frontend Store: `frontend/src/stores/admin.ts`
- Admin Form: `frontend/src/views/admin/packs/PackForm.vue`

The pack system is now significantly more flexible and can support a wide variety of pack types for different game modes and events!

---

## Related Feature: Program-Exclusive Cards

In addition to pack enhancements, the system now supports **program-exclusive cards** that cannot be obtained from any pack.

### What are Program-Exclusive Cards?

Cards can be marked as program-exclusive, meaning they:

- ❌ Will **never** appear in any pack (standard, choice, guaranteed, or collection-restricted)
- ✅ Can **only** be obtained as rewards from programs, collections, or events
- ✅ Are automatically filtered out when opening packs

### Use Case

Perfect for:

- Season completion rewards (e.g., exclusive 99 OVR LeBron)
- Championship/tournament exclusive cards
- Collection completion bonuses
- Special event milestone rewards

### How It Works

1. **Admin marks a card as program-exclusive** via checkbox in player form
2. **PackController automatically excludes** these cards from all pack pulls
3. **Cards remain available** as program/collection rewards

### Example

```json
{
  "name": "LeBron James (Season Champion)",
  "overall_rating": 99,
  "card_tier": "pink_diamond",
  "obtainable_from_packs": false // Program-exclusive
}
```

This card:

- Won't appear in any pack, even Lakers Team Packs
- Can be awarded as a Season completion reward
- Creates exclusive incentive for program completion

### Documentation

For complete details, see: [PROGRAM_EXCLUSIVE_CARDS.md](./PROGRAM_EXCLUSIVE_CARDS.md)
