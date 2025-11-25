# Admin-Specified Packs & Shop Visibility Feature

## Overview

This feature adds two key capabilities to the pack system:

1. **Admin-Specified Players**: Allows admins to manually specify which exact players appear in choice packs (instead of random generation)
2. **Shop Visibility Control**: Allows admins to mark packs as not available in the shop (for reward-only packs)

## Changes Made

### Database Changes

#### Migration: `2025_11_06_032230_add_shop_and_specified_players_to_packs_table.php`

Added two new columns to the `packs` table:

```php
$table->boolean('available_in_shop')->default(true)->after('active');
$table->json('specified_players')->nullable()->after('available_in_shop');
```

- **`available_in_shop`** (boolean, default: true): Controls whether the pack appears in the shop
- **`specified_players`** (JSON array, nullable): Stores an array of player IDs for admin-specified choice packs

### Backend Changes

#### 1. Pack Model (`backend/app/Models/Pack.php`)

**Added to `$fillable`:**

- `available_in_shop`
- `specified_players`

**Added to `$casts`:**

- `available_in_shop` => `'boolean'`
- `specified_players` => `'array'`

**New Methods:**

```php
// Check if this pack has specified players
public function hasSpecifiedPlayers(): bool

// Get the list of specified player IDs
public function getSpecifiedPlayerIds(): array

// Scope to get only packs available in shop
public function scopeAvailableInShop($query)
```

#### 2. PackController (`backend/app/Http/Controllers/PackController.php`)

**Updated `index()` method:**

```php
// Now filters packs by both active AND available_in_shop
$packs = Pack::active()->availableInShop()->get();
```

**Updated `generateChoicePackOptions()` method:**

```php
// Check if pack has specified players (admin-defined choices)
if ($pack->hasSpecifiedPlayers()) {
    // Use the specified players directly
    $specifiedPlayerIds = $pack->getSpecifiedPlayerIds();
    $players = Player::whereIn('id', $specifiedPlayerIds)->get();

    // Return players in the order specified by admin
    foreach ($specifiedPlayerIds as $playerId) {
        $player = $players->firstWhere('id', $playerId);
        if ($player) {
            $options[] = $player;
        }
    }
} else {
    // Original random generation logic...
}
```

### Frontend Changes

#### 1. Admin Store (`frontend/src/stores/admin.ts`)

**Updated Pack Interface:**

```typescript
interface Pack {
  // ... existing fields
  available_in_shop?: boolean;
  specified_players?: number[];
}
```

#### 2. PackForm Component (`frontend/src/views/admin/packs/PackForm.vue`)

**New Form Fields:**

1. **Available in Shop Checkbox:**

   - Located after the "Active" checkbox
   - Controls whether pack shows in shop
   - Includes helpful hint: "Uncheck for reward-only packs"

2. **Specified Players Section:**
   - Only visible when pack type is "choice"
   - Allows admin to add multiple players by selecting from dropdown
   - Shows all players with their team and card tier
   - Each player can be removed individually
   - Players appear in the order they are added

**New State Variables:**

```typescript
const specifiedPlayers = ref<number[]>([]);

const formData = ref({
  // ... existing fields
  available_in_shop: true,
  specified_players: null,
});
```

**New Functions:**

```typescript
function addSpecifiedPlayer() {
  specifiedPlayers.value.push(null as any);
}

function removeSpecifiedPlayer(index: number) {
  specifiedPlayers.value.splice(index, 1);
}
```

**Updated `onMounted()`:**

- Now fetches all players for the dropdown: `await adminStore.fetchAllPlayers()`
- Loads `specified_players` when editing an existing pack

**Updated `handleSubmit()`:**

- Filters out null values from `specifiedPlayers`
- Sets `formData.specified_players` to the valid player IDs (or null if empty)

## How to Use

### Creating a Reward-Only Pack

1. Go to Admin → Packs → Create Pack
2. Fill in pack details (name, type, cost, etc.)
3. **Uncheck "Available in Shop"** checkbox
4. Configure other pack settings as needed
5. Save the pack

**Result:** The pack will not appear in the shop but can still be awarded through program rewards.

### Creating a Pack with Specified Players

1. Go to Admin → Packs → Create Pack
2. Set **Type** to "Choice"
3. Scroll to the **"Specified Players"** section
4. Click **"+ Add Player"** button
5. Select a player from the dropdown
6. Repeat to add more players
7. Reorder by removing and re-adding if needed (players appear in order added)
8. Save the pack

**Result:** When users open this choice pack, they will see exactly the players you specified (in the order you added them) instead of randomly generated options.

### Example Use Cases

#### Use Case 1: Season Reward Pack

- Create a premium choice pack with specific high-tier players
- Uncheck "Available in Shop"
- Specify 3 galaxy opal players as choices
- Use as a reward for completing season programs

#### Use Case 2: Special Event Pack

- Create a themed choice pack (e.g., "Lakers Legends")
- Keep "Available in Shop" checked
- Specify exact legendary Lakers players
- Sell in shop during special events

#### Use Case 3: Team Affinity Milestone Pack

- Create a choice pack for reaching affinity level 50
- Uncheck "Available in Shop"
- Specify the 3 best players from that team
- Award through team affinity program

## Technical Details

### Pack Opening Flow with Specified Players

1. User opens a choice pack from inventory
2. `PackController::open()` calls `generateChoicePackOptions()`
3. Controller checks `$pack->hasSpecifiedPlayers()`
4. If true:
   - Fetches players by IDs: `Player::whereIn('id', $specifiedPlayerIds)->get()`
   - Returns players in admin-specified order
5. If false:
   - Uses original random generation logic with tier odds
6. Frontend displays options to user
7. User selects their choice(s)
8. `PackController::confirmChoice()` adds selected players to user's collection

### Shop Filtering

- The shop only displays packs where:
  - `active` = true
  - `available_in_shop` = true
- Packs with `available_in_shop` = false can still be:
  - Awarded through programs
  - Viewed in admin panel
  - Opened from inventory

### Backwards Compatibility

- **Existing packs**: Migration sets `available_in_shop` to `true` by default, so all existing packs remain visible in shop
- **Random choice packs**: If `specified_players` is null or empty, the original random generation logic is used
- **Standard packs**: The `specified_players` field is ignored for standard (non-choice) packs

## Database Schema

**`packs` table additions:**

| Column              | Type    | Default | Nullable | Description                          |
| ------------------- | ------- | ------- | -------- | ------------------------------------ |
| `available_in_shop` | boolean | true    | No       | Controls shop visibility             |
| `specified_players` | JSON    | null    | Yes      | Array of player IDs for choice packs |

**Example `specified_players` value:**

```json
[42, 137, 89]
```

This would show players with IDs 42, 137, and 89 as the choice pack options.

## Notes

- Specified players override all other pack configurations (tier odds, guaranteed slots, collection restrictions)
- The order of players in the `specified_players` array determines the display order
- If a specified player is deleted from the database, it will be silently skipped (won't break the pack)
- Admins can mix specified player packs and random packs freely
- The "Guaranteed Tier Slots" section shows a note: "Ignored if using specified players"

## Testing Checklist

- [x] Create a choice pack with specified players
- [x] Verify specified players appear in correct order when pack is opened
- [x] Create a pack with `available_in_shop = false`
- [x] Verify pack doesn't appear in shop
- [x] Verify pack can still be awarded through programs
- [x] Edit existing pack to add specified players
- [x] Edit existing pack to remove from shop
- [x] Verify backwards compatibility with existing packs
- [x] Test with invalid/deleted player IDs (should skip gracefully)
