# Pending Rewards System (Coming Soon Rewards)

## Overview

The program rewards system now supports "pending" or "coming soon" rewards that players can earn before the actual reward items (cards, packs) are created. This is perfect for seasonal rewards like All-Star and Finest cards that depend on real NBA season data.

---

## How It Works

### 1. **Creating Pending Rewards**

When creating a program reward in the admin panel, you can mark it as "coming soon" by:

- Setting `available` to `false`
- Optionally adding a `coming_soon_label` (e.g., "All-Star Card", "Finest Series")
- Leaving `reward_id` as `null` initially

**Example:**

```json
{
  "program_id": 15,
  "xp_threshold": 100000,
  "reward_type": "player",
  "reward_id": null,
  "description": "All-Star LeBron James Card",
  "available": false,
  "coming_soon_label": "All-Star"
}
```

### 2. **Players Earn the Reward**

When a player reaches the XP/Stars threshold:

1. ✅ The system creates a `UserProgramReward` record
2. ✅ Sets `claimed_at` to `null` (marking it as "earned but not claimed")
3. ✅ Shows player they earned it with "Coming Soon" status
4. ❌ **Does NOT** give them the actual card/pack/stubs yet

**User sees:** "Reward Earned! It will be available once released."

### 3. **When the Reward Becomes Available**

Once you create the actual card (e.g., All-Star LeBron):

1. Admin calls the unlock endpoint with the new `player_id`
2. System updates the reward: `available = true`, `reward_id = [new_card_id]`
3. System finds all users with pending claims (`claimed_at IS NULL`)
4. **Automatically distributes** the reward to all those users
5. Updates their `claimed_at` to `now()`

**Users see:** Their card automatically appears in their collection!

---

## Database Changes

### Migration: `add_available_flag_to_program_rewards_table`

Added two fields to `program_rewards`:

```php
$table->boolean('available')->default(true);
$table->string('coming_soon_label')->nullable();
```

- **`available`**: `false` = coming soon, `true` = claimable now
- **`coming_soon_label`**: Display label like "All-Star", "Finest", "Playoffs"

---

## Backend API

### New Methods in ProgramReward Model

```php
isAvailable(): bool          // Check if reward can be claimed
isPending(): bool            // Check if reward is "coming soon"
canBeClaimed(): bool         // Check if has required data and is available
```

### Updated Claim Logic (ProgramController)

When a player tries to claim a reward:

**If Available:**

```php
// Normal flow - give reward immediately
UserProgramReward::create([...,'claimed_at' => now()]);
distributeReward($userId, $reward);
return 'Reward claimed successfully';
```

**If Pending:**

```php
// Mark as earned but don't give reward yet
UserProgramReward::create([...,'claimed_at' => null]);
return 'Reward earned! It will be available once released.';
```

### New Admin Endpoint

**POST** `/api/admin/program-rewards/{rewardId}/unlock`

**Request Body:**

```json
{
  "reward_id": 1234 // The newly created player/pack ID
}
```

**What It Does:**

1. Updates reward to `available = true`
2. Sets the `reward_id`
3. Finds all pending claims
4. Distributes rewards to all users automatically
5. Returns count of users who received it

**Response:**

```json
{
  "success": true,
  "message": "Reward unlocked and distributed to 47 users",
  "distributed_count": 47,
  "reward": {...}
}
```

---

## Frontend Integration

### Program Reward Display

Rewards now have three states:

1. **Not Earned** - Gray/locked
2. **Pending** - Earned but coming soon (yellow/orange)
3. **Claimable** - Can claim now (green)

### Reward Object Properties

```typescript
{
  id: number,
  reward_type: string,
  description: string,
  available: boolean,
  coming_soon_label?: string,
  claimed: boolean,           // User has record in UserProgramReward
  pending: boolean,           // claimed=true BUT claimed_at=null
  can_claim: boolean          // available=true AND has reward_id
}
```

### Display Logic

```typescript
if (reward.pending) {
  // Show "Earned - Coming Soon" with coming_soon_label
  status = "coming_soon";
  message = `${reward.coming_soon_label || "Coming Soon"} - Earned!`;
} else if (reward.claimed) {
  // Already claimed
  status = "claimed";
  message = "Claimed";
} else if (reward.can_claim) {
  // Can claim now
  status = "claimable";
  message = "Claim Reward";
} else {
  // Not earned yet
  status = "locked";
  message = "Locked";
}
```

---

## Use Cases

### Team Affinity All-Star Rewards

**Setup (Before All-Star Break):**

```json
{
  "reward_type": "player",
  "reward_id": null,
  "available": false,
  "coming_soon_label": "All-Star",
  "description": "All-Star Team Captain Card"
}
```

Players can complete the program and "earn" the reward.

**After All-Star Game:**

1. Create the All-Star card in admin (e.g., `player_id = 5678`)
2. Call unlock endpoint: `POST /api/admin/program-rewards/123/unlock` with `{"reward_id": 5678}`
3. All 47 users who earned it automatically receive the card!

### Finest Series (End of Season)

**Setup (During Season):**

```json
{
  "reward_type": "player",
  "reward_id": null,
  "available": false,
  "coming_soon_label": "Finest",
  "description": "Finest Series LeBron James"
}
```

**After Season Ends:**

1. Determine Finest players based on stats
2. Create Finest cards
3. Unlock rewards with new card IDs
4. Auto-distribute to all players who earned them

---

## Admin Workflow

### Step 1: Create Team Affinity Programs with Pending Rewards

```bash
# In admin panel, create program with 2 pending rewards:

Reward 1:
- Type: Player
- Threshold: 50,000 XP
- Available: false ❌
- Coming Soon Label: "All-Star"
- Description: "All-Star Lakers Team Captain"

Reward 2:
- Type: Player
- Threshold: 100,000 XP
- Available: false ❌
- Coming Soon Label: "Finest"
- Description: "Finest Lakers Series"
```

### Step 2: Players Progress Through Season

- Players earn XP, complete challenges
- Hit thresholds and "earn" the pending rewards
- Rewards show as "Coming Soon - All-Star" etc
- System tracks who earned what

### Step 3: Create Actual Reward Cards

```bash
# After All-Star Break, create the card:
# Admin -> Players -> Create Player

Name: LeBron James (All-Star)
Overall: 99
Tier: pink_diamond
Team: Los Angeles Lakers
Obtainable from Packs: false ✓ (Program exclusive)
```

### Step 4: Unlock the Reward

```bash
# Use the unlock endpoint
POST /api/admin/program-rewards/45/unlock
{
  "reward_id": 1285  # The new LeBron All-Star card ID
}
```

**Result:** All users who earned it instantly get the card!

### Step 5: Repeat for Finest

Same process after regular season ends.

---

## Benefits

### ✅ For Game Design

- Create programs before content exists
- Build anticipation for upcoming rewards
- Tie rewards to real-world events (All-Star, Playoffs, Finals)
- No need to retroactively award rewards

### ✅ For Players

- Clear progression goals
- See what they'll earn
- Automatic delivery when available
- Fair - everyone who earned it gets it

### ✅ For Development

- Clean separation of program logic and content
- Easy to add new seasonal content
- Bulk distribution handled automatically
- Full audit trail of who earned what and when

---

## Database Queries

### Find All Pending Rewards

```sql
SELECT * FROM program_rewards
WHERE available = false;
```

### Find Users Who Earned a Specific Pending Reward

```sql
SELECT u.id, u.name, upr.created_at as earned_at
FROM user_program_rewards upr
JOIN users u ON upr.user_id = u.id
WHERE upr.program_reward_id = 45
AND upr.claimed_at IS NULL;
```

### Count Pending Claims Per Reward

```sql
SELECT
  pr.id,
  pr.description,
  pr.coming_soon_label,
  COUNT(upr.id) as pending_claims
FROM program_rewards pr
LEFT JOIN user_program_rewards upr ON pr.id = upr.program_reward_id AND upr.claimed_at IS NULL
WHERE pr.available = false
GROUP BY pr.id;
```

---

## Migration Path

### For Existing Programs

All existing rewards default to `available = true`, so they work exactly as before. No changes needed!

### For New Team Affinity Programs

Use the new system:

1. Create program with pending rewards
2. Set `available = false` and `coming_soon_label`
3. Launch program
4. Create cards when data available
5. Unlock and auto-distribute

---

## Testing Checklist

- [ ] Create program with pending reward (`available = false`)
- [ ] Player earns reward (reaches threshold)
- [ ] Verify `UserProgramReward` created with `claimed_at = null`
- [ ] Verify player sees "Coming Soon" status
- [ ] Create the actual reward card
- [ ] Call unlock endpoint with new `reward_id`
- [ ] Verify reward marked as `available = true`
- [ ] Verify player automatically received the card
- [ ] Verify `claimed_at` updated to timestamp
- [ ] Create second player, reach threshold
- [ ] Verify they get reward immediately (now available)

---

## Future Enhancements

### Potential Improvements

1. **Notification System**

   - Notify users when pending rewards become available
   - "Your All-Star card is now available!"

2. **Preview System**

   - Show card preview/placeholder before available
   - "This card will be based on All-Star performance"

3. **Bulk Unlock**

   - Unlock multiple rewards at once
   - Useful for season transitions

4. **Scheduled Unlock**

   - Set automatic unlock date
   - Auto-distribute at specific time

5. **Analytics Dashboard**
   - Track pending claims per reward
   - Forecast distribution impact
   - Monitor user engagement with upcoming rewards

---

## Summary

✅ **Implemented:**

- Database fields for tracking availability
- Backend logic for pending rewards
- Automatic distribution system
- Admin unlock endpoint
- Frontend display states

✅ **Key Files Modified:**

- Migration: `2025_11_02_004802_add_available_flag_to_program_rewards_table.php`
- Model: `backend/app/Models/ProgramReward.php`
- Service: `backend/app/Services/ProgramService.php`
- Controller: `backend/app/Http/Controllers/ProgramController.php`
- Admin: `backend/app/Http/Controllers/Admin/AdminProgramController.php`
- Routes: `backend/routes/api.php`

The system provides a complete solution for managing rewards that depend on future real-world events, perfect for sports card games with seasonal content!
