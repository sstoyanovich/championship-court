# Collection Rewards System - Implementation Summary

## Overview

Implemented a multi-tier collection rewards system where users automatically receive rewards (stubs, player cards, or packs) when they lock a specific number of cards within a collection. Admins can manage these rewards through an enhanced admin panel.

## Completed Features

### 1. Database Schema ✅

**Updated `collection_rewards` table:**

- Added `required_cards` (integer) - number of locked cards needed to earn reward
- Added `player_id` (nullable foreign key) - for player card rewards
- Added `pack_id` (nullable foreign key) - for pack rewards
- Added `order` (integer) - display order for rewards

**Created `user_collection_rewards` table:**

- Tracks which rewards each user has claimed
- Fields: `user_id`, `collection_reward_id`, `claimed_at`
- Unique constraint prevents duplicate claims (one-time only per user per reward)

### 2. Backend Models ✅

**Updated Models:**

- `CollectionReward` - Added relationships to Player, Pack, and UserCollectionReward
- `UserCollectionReward` - New model tracking reward claims
- `User` - Added collectionRewards() relationship

### 3. Automatic Reward Granting ✅

**Enhanced `CollectionController::lockCard()`:**

- After locking a card, checks all unclaimed rewards for that collection
- Automatically grants rewards when `locked_cards >= required_cards`
- Creates `UserCollectionReward` record to prevent duplicate claims
- Returns list of newly earned rewards in API response

**Reward Granting Logic:**

- **Stubs**: Adds to user's balance via `User::addStubs()`
- **Player Card**: Creates new `UserCard` record (unlocked)
- **Pack**: Creates `UserPack` record(s) based on quantity

### 4. Admin API Endpoints ✅

**New Routes:**

```php
GET    /admin/collections/{id}/rewards      - List rewards for collection
POST   /admin/collections/{id}/rewards      - Create new reward
GET    /admin/rewards/{id}                  - Get reward details
PUT    /admin/rewards/{id}                  - Update reward
DELETE /admin/rewards/{id}                  - Delete reward
```

**Controller:** `AdminCollectionRewardController`

- Full CRUD operations for collection rewards
- Auto-increments `order` field if not provided
- Validates reward types and relationships

### 5. Frontend Admin UI ✅

**New Components:**

**`RewardFormModal.vue`:**

- Modal form for creating/editing rewards
- Reward type selector: Stubs / Player Card / Pack
- Required cards input (threshold for earning reward)
- Conditional fields based on reward type:
  - Stubs: amount input
  - Player Card: searchable player dropdown
  - Pack: pack dropdown + quantity
- Description and order fields

**`RewardManager.vue`:**

- Displays list of rewards for a collection
- Shows reward type badges, requirements, and details
- Add/Edit/Delete actions for each reward
- Integrated into Collection edit page

**Updated `CollectionForm.vue`:**

- Shows RewardManager component when editing a collection
- Only visible in edit mode (not when creating new collection)

**Updated `admin.ts` store:**

- `fetchCollectionRewards(collectionId)` - Load rewards
- `createCollectionReward(collectionId, data)` - Create reward
- `updateCollectionReward(rewardId, data)` - Update reward
- `deleteCollectionReward(rewardId)` - Delete reward

### 6. User-Facing Collection View ✅

**Enhanced `CollectionView.vue`:**

**Rewards Display Section:**

- Shows all reward tiers with progress bars
- Visual cards for each reward showing:
  - Required cards threshold
  - Reward type and amount
  - Progress bar showing how close user is
  - "CLAIMED" badge on earned rewards
  - Different styling for claimed vs unclaimed

**Enhanced Lock Card Notification:**

- When user locks card and earns reward(s), shows alert with:
  - What rewards were earned
  - Formatted display: "500 Stubs" or "LeBron James" or "2x Premium Pack"

**API Response Enhancements:**

- `getCollectionCards()` and `getTeamCards()` now return:
  - All rewards with `claimed` status for current user
  - Rewards ordered by `required_cards` ascending
  - Full player and pack details loaded

## How It Works

### For Users:

1. User navigates to a collection (e.g., Live Series Miami Heat)
2. Sees reward tiers displayed at top:
   - "Lock 5 cards → 500 Stubs"
   - "Lock 15 cards (all) → Diamond LeBron James"
3. User locks cards one by one
4. When threshold is reached (e.g., 5 locked), reward is **automatically granted**
5. Alert shows: "🎉 Reward Earned! You received: 500 Stubs"
6. Reward card shows "CLAIMED" badge
7. Can continue to earn higher tier rewards

### For Admins:

1. Navigate to Admin → Collections → Edit Collection
2. Scroll to "Collection Rewards" section
3. Click "+ Add Reward"
4. Fill in form:
   - Required Cards: 5
   - Reward Type: Stubs
   - Amount: 500
   - Description: "Starter reward"
5. Save reward
6. Repeat for multiple tiers (e.g., 10 cards, 15 cards)
7. Rewards appear in list with Edit/Delete options

## Key Implementation Details

- ✅ Rewards granted **automatically** when threshold met
- ✅ One-time only per collection per user (via unique constraint)
- ✅ Locked cards count toward progress (owned-but-unlocked don't count)
- ✅ Admin can create unlimited reward tiers per collection
- ✅ Rewards display in order of `required_cards` ascending
- ✅ Frontend shows real-time progress bars
- ✅ Backend validates reward types and relationships
- ✅ Supports three reward types: stubs, player_card, pack

## Database Migrations

To apply the schema changes:

```bash
cd backend
php artisan migrate
```

## Files Modified/Created

### Backend:

- `database/migrations/2025_10_19_211049_create_collection_rewards_table.php` - Updated
- `database/migrations/2025_10_23_175410_create_user_collection_rewards_table.php` - Created
- `app/Models/CollectionReward.php` - Updated
- `app/Models/UserCollectionReward.php` - Created
- `app/Models/User.php` - Updated
- `app/Http/Controllers/CollectionController.php` - Updated
- `app/Http/Controllers/Admin/AdminCollectionRewardController.php` - Created
- `routes/api.php` - Updated

### Frontend:

- `src/stores/admin.ts` - Updated
- `src/components/admin/RewardFormModal.vue` - Created
- `src/components/admin/RewardManager.vue` - Created
- `src/views/admin/collections/CollectionForm.vue` - Updated
- `src/views/CollectionView.vue` - Updated

## Testing Checklist

### Admin Flow:

- [ ] Create a new collection
- [ ] Edit collection and add multiple reward tiers
- [ ] Verify rewards show correct player/pack names
- [ ] Edit and delete rewards
- [ ] Verify order field works for custom sorting

### User Flow:

- [ ] Navigate to collection with rewards
- [ ] View reward cards showing thresholds
- [ ] Lock cards incrementally
- [ ] Verify reward notification when threshold met
- [ ] Check claimed badge appears
- [ ] Verify stubs added to balance
- [ ] Verify player cards added to collection
- [ ] Verify packs added to inventory
- [ ] Attempt to claim same reward twice (should not grant)

## Future Enhancements (Optional)

- Drag-and-drop reordering of rewards
- Reward preview before locking card
- Collection leaderboards
- Seasonal/time-limited rewards
- Reward bundles (multiple items in one reward)
- Progress notifications at 25%, 50%, 75%

## Notes

- TypeScript import warnings in Vue components are safe to ignore (type declarations only)
- Rewards are checked on every card lock, not just at exact thresholds
- User can unlock cards, but claimed rewards are not revoked
- Admin must manually set `total_items` on collection for accurate percentages
