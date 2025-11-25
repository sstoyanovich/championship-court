# Multiple Pack Rewards Support

## Overview

Updated the program rewards system to support awarding multiple packs at once through the `reward_amount` field.

## Changes Made

### Backend Changes

#### 1. ProgramController.php

**File:** `backend/app/Http/Controllers/ProgramController.php`

Updated the `distributeReward()` method to create multiple pack entries based on `reward_amount`:

```php
case 'pack':
    // Give user pack(s)
    if ($reward->reward_id) {
        $packCount = $reward->reward_amount ?? 1; // Default to 1 if not specified
        for ($i = 0; $i < $packCount; $i++) {
            \App\Models\UserPack::create([
                'user_id' => $userId,
                'pack_id' => $reward->reward_id,
            ]);
        }
        $details['given'] = true;
        $details['pack_id'] = $reward->reward_id;
        $details['quantity'] = $packCount;
        $details['message'] = $packCount > 1
            ? "{$packCount} Packs added to your inventory"
            : 'Pack added to your inventory';
    }
    break;
```

**Note:** The following services already supported multiple packs:

- `ProgramService::claimReward()` - Uses `reward_amount` for pack quantity
- `AdminProgramController::unlockReward()` - Uses `reward_amount` for pending pack rewards

### Frontend Changes

#### 2. ProgramForm.vue

**File:** `frontend/src/views/admin/programs/ProgramForm.vue`

**Added Pack Quantity Field:**
Added an input field to specify how many packs should be awarded:

```vue
<div class="form-field" v-if="reward.reward_type === 'pack'">
  <label>Pack Quantity *</label>
  <input v-model.number="reward.reward_amount" type="number" min="1" required />
  <span class="field-hint">How many packs to award</span>
</div>
```

**Added Reward Availability Fields:**
Added fields for the pending rewards system (from previous update):

```vue
<div class="form-field">
  <label>Availability</label>
  <div class="checkbox-field">
    <input
      v-model="reward.available"
      type="checkbox"
      :id="'available-' + index"
    />
    <label :for="'available-' + index">Reward is available to claim</label>
  </div>
  <span class="field-hint">Uncheck if reward should be pending (e.g. All-Star cards)</span>
</div>
<div class="form-field" v-if="!reward.available">
  <label>Coming Soon Label</label>
  <input
    v-model="reward.coming_soon_label"
    type="text"
    placeholder="e.g., Available after All-Star Game"
  />
  <span class="field-hint">Message shown to users when reward is pending</span>
</div>
```

**Updated addReward Function:**
Updated to initialize new rewards with `available` and `coming_soon_label` fields:

```typescript
function addReward() {
  formData.value.rewards.push({
    xp_threshold: undefined,
    stars_threshold: undefined,
    reward_type: "",
    reward_id: undefined,
    reward_amount: undefined,
    description: "",
    available: true,
    coming_soon_label: "",
  });
}
```

## How to Use

### Awarding Multiple Packs

1. Go to Admin → Programs → Edit/Create Program
2. Add or edit a reward
3. Select "Pack" as the reward type
4. Choose the desired pack from the dropdown
5. Enter the quantity in the "Pack Quantity" field (e.g., 3 for three packs)
6. Set the XP or Stars threshold
7. Save the program

When a user reaches that reward threshold, they will receive the specified number of packs in their inventory.

### Example Use Cases

- **Team Affinity Level 10**: Award 3x Standard Packs
- **Season Completion**: Award 5x Premium Packs
- **Special Milestone**: Award 10x Base Packs

## Database Schema

The `program_rewards` table already has a `reward_amount` column (integer, nullable):

- For **stubs**: Amount of stubs to award
- For **packs**: Number of packs to award
- For **program_xp**: Amount of XP to award to another program
- For **player**: Not used (always 1 player card)

## Testing

Test the following scenarios:

1. **Single Pack Reward**: Set `reward_amount` to 1, verify user receives 1 pack
2. **Multiple Pack Reward**: Set `reward_amount` to 3, verify user receives 3 packs
3. **Legacy Compatibility**: Rewards with `reward_amount` = NULL should default to 1 pack
4. **Pending Rewards**: Test with `available = false` to ensure packs are properly distributed when unlocked

## Notes

- The default pack quantity is 1 if `reward_amount` is not set
- The system creates individual `UserPack` entries for each pack (not a single entry with quantity)
- This allows for individual pack opening and tracking
- The success message dynamically adjusts based on quantity (singular vs. plural)
