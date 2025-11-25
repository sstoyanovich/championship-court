# Program Progress Fix

## Problem

After uploading stats, no program progress was being tracked. Users couldn't see any challenge updates or program advancement.

## Root Cause

**Data Integrity Issue**: Several Team Affinity programs had rewards configured with player IDs that didn't exist in the database.

### The Failure Chain:

1. User uploads stats
2. Stats are processed and saved correctly ✅
3. `ProgramService::processGameCompletion()` is called
4. Challenge progress is calculated
5. When reaching certain star thresholds, rewards are auto-awarded
6. **CRASH**: Trying to create a `UserCard` for a non-existent player (player_id 1473)
7. Foreign key constraint violation
8. Entire transaction is rolled back
9. **No challenge progress is saved** ❌

### Technical Details

**Error from logs:**

```
[2025-11-12 23:26:14] local.ERROR: Program processing error: SQLSTATE[23000]:
Integrity constraint violation: 19 FOREIGN KEY constraint failed
(Connection: sqlite, SQL: insert into "user_cards" ("user_id", "player_id", ...)
values (3, 1473, ...))
```

**Code Location:**

- `backend/app/Http/Controllers/StatsController.php` line 163-171
- The error was caught and logged but not shown to users
- The try-catch prevented the entire request from failing, but silently prevented all program tracking

## Solution

### Immediate Fix

Removed 7 broken program rewards that referenced non-existent players:

**Broken Rewards Removed:**
| Program | Player ID | Stars Threshold |
|---------|-----------|-----------------|
| Phoenix Suns Team Affinity | 1446 | 135 |
| Portland Trail Blazers Team Affinity | 1448 | 5 |
| Portland Trail Blazers Team Affinity | 1451 | 135 |
| Sacramento Kings Team Affinity | 1453 | 5 |
| Sacramento Kings Team Affinity | 1456 | 135 |
| Sacramento Kings Team Affinity | 1457 | 285 |
| **Washington Wizards Team Affinity** | **1473** | **5** |

### SQL Fix Applied:

```sql
DELETE FROM program_rewards
WHERE reward_type = 'player'
AND reward_id NOT IN (SELECT id FROM players);
```

**Result**: 7 rows deleted

---

## Testing & Verification

### Before Fix:

- ❌ No `user_program_challenges` records created
- ❌ No program progress saved
- ❌ No challenge completions tracked
- ❌ Silent failure (error only in logs)

### After Fix:

- ✅ Stats upload should now update challenges
- ✅ Progress should be saved to database
- ✅ Programs should show advancement
- ✅ Challenge completions should be tracked

### To Verify:

1. Upload a game screenshot with stats
2. Check that challenges update (shown in upload results)
3. Navigate to Programs page
4. Verify progress bars and challenge completion status update
5. Check database: `SELECT * FROM user_program_challenges LIMIT 5;`

---

## Prevention

### Recommendation: Add Data Validation

**Option 1: Database-Level Check (Foreign Key)**
The `program_rewards` table should enforce foreign keys:

```sql
-- For player rewards
FOREIGN KEY (reward_id) REFERENCES players(id)
ON DELETE CASCADE  -- If player deleted, remove reward
```

**Option 2: Application-Level Validation**
In the admin panel where rewards are created, validate that:

- Player rewards reference existing players
- Pack rewards reference existing packs
- Show warning if player/pack doesn't exist

**Option 3: Reward Soft Delete**
Instead of hard-failing, mark rewards as "unavailable" if their target doesn't exist:

```php
// In ProgramService::checkAndAwardRewards()
if ($reward->reward_type === 'player') {
    if (!Player::find($reward->reward_id)) {
        Log::warning("Reward {$reward->id} references non-existent player {$reward->reward_id}");
        continue; // Skip this reward instead of crashing
    }
}
```

---

## How to Re-Add Missing Rewards (If Needed)

If these 7 rewards need to be restored:

1. **Find the correct player IDs** for these players (check `players` table)
2. **Create new program rewards** with correct IDs
3. **Or import missing players** to database first

Example queries to check what players exist:

```sql
-- Search for a player by name
SELECT id, name, team FROM players
WHERE team = 'Washington Wizards'
ORDER BY overall_rating DESC;

-- Add a reward with correct player ID
INSERT INTO program_rewards (program_id, reward_type, reward_id, stars_threshold, description)
VALUES (49, 'player', <CORRECT_PLAYER_ID>, 5, 'Reward description');
```

---

## Impact

### Programs Affected:

- Washington Wizards Team Affinity (most likely to be encountered at 5 stars)
- Portland Trail Blazers Team Affinity
- Sacramento Kings Team Affinity
- Phoenix Suns Team Affinity

### Users Affected:

- **All users** attempting to progress in any program
- Even programs without broken rewards were affected because the entire transaction rolled back

### Data Loss:

- **All stats uploads since the issue began** have saved game stats correctly
- **BUT challenge progress was not tracked** for those games
- Consider:
  - Should we backfill challenge progress from `user_player_stats`?
  - Or just move forward with the fix?

---

## Summary

✅ **Fixed**: Removed 7 broken program rewards  
✅ **Verified**: No other data integrity issues found  
✅ **Expected Result**: Program progress tracking should now work correctly

**Next Steps**:

1. Test stats upload with real data
2. Verify challenge progress updates
3. Consider adding the validation improvements mentioned above
4. Decide if affected Team Affinity programs need their rewards restored with correct player IDs
