# Team Affinity Implementation Summary

## Overview

Successfully implemented Team Affinity programs - 30 team-specific year-long journeys with standardized rewards/challenges and dedicated UI filtering.

## What Was Implemented

### Backend Changes

#### 1. Database Schema

- Added `team` (nullable string) and `category` (enum: general/team_affinity) fields to `programs` table
- Updated `program_challenges.constraint_type` to include `'player'` option for specific player card requirements

#### 2. Models

**Program Model (`app/Models/Program.php`)**

- Added `team` and `category` to fillable fields
- Added scope methods: `scopeTeamAffinity()` and `scopeGeneral()`
- Added helper method: `isTeamAffinity()` returns boolean

**ProgramChallenge Model (`app/Models/ProgramChallenge.php`)**

- Added `isPlayerConstraint()` method to check if constraint_type is 'player'

#### 3. API Updates

**ProgramController (`app/Http/Controllers/ProgramController.php`)**

- Modified `index()` to accept optional `category` query parameter
- By default, excludes Team Affinity programs (shows only general programs)
- Updated `distributeReward()` to handle XP rewards that add to active season programs

#### 4. Artisan Command

**CreateTeamAffinityPrograms Command**

- Command: `php artisan programs:create-team-affinity`
- Optional `--fresh` flag to delete existing TA programs first
- Creates programs for all 30 NBA teams
- Each program includes:
  - 6 default rewards (5 stars: Silver Card, 10: Pack, 15: Stubs, 20: XP, 25: Gold Card, 365: Final Reward)
  - 3 default challenges (Win games, Score points, Earn PXP)
  - Player card rewards set to NULL (admin configures later)

**Status:** ✅ Created 30 programs, 180 rewards, 90 challenges

#### 5. Admin Controller

**AdminTeamAffinityController (`app/Http/Controllers/Admin/AdminTeamAffinityController.php`)**

- `GET /api/admin/team-affinity` - List all TA programs
- `POST /api/admin/team-affinity/create-all` - Run artisan command to create all programs
- `POST /api/admin/team-affinity/bulk-rewards` - Add same rewards to multiple teams
- `POST /api/admin/team-affinity/bulk-challenges` - Add same challenges to multiple teams
- `PATCH /api/admin/team-affinity/{programId}/rewards/{rewardId}` - Update specific reward

### Frontend Changes

#### 1. Services

**api.js**

- Added `category` parameter support to `getPrograms()`
- Added Team Affinity admin endpoints:
  - `getTeamAffinityPrograms()`
  - `createAllTeamAffinity(fresh)`
  - `bulkAddRewards(teamIds, rewards)`
  - `bulkAddChallenges(teamIds, challenges)`
  - `updateTeamAffinityReward(programId, rewardId, data)`

**programService.js**

- Updated `getPrograms()` to accept category parameter
- Added `getTeamAffinityPrograms()` method

#### 2. Store Updates

**programStore.ts**

- Added `teamAffinityPrograms` state
- Added `teamAffinityLoading` state
- Updated getters to exclude Team Affinity from xpPrograms/starPrograms
- Added `teamAffinityByDivision` getter (organizes 30 teams by NBA division)
- Added `fetchTeamAffinityPrograms()` action
- Updated `fetchPrograms()` to request only 'general' category by default

#### 3. Views

**TeamAffinityView.vue (NEW)**

- Route: `/programs/team-affinity`
- Displays all 30 teams organized by division (Atlantic, Central, Southeast, Northwest, Pacific, Southwest)
- Shows progress bars for each team
- Displays next milestone information
- Clicking team card navigates to program detail view

**ProgramsView.vue (UPDATED)**

- Added prominent Team Affinity banner with gradient styling
- Banner displays key info: 30 Teams, 365 Star Journey, Exclusive Cards
- Clicking banner navigates to Team Affinity view
- General programs now exclude Team Affinity category

**TeamAffinityManagement.vue (NEW - Admin)**

- Route: `/admin/programs/team-affinity`
- Bulk management interface for all 30 teams
- Features:
  - Create all Team Affinity programs button
  - Select multiple teams with checkboxes
  - Bulk add rewards to selected teams
  - Bulk add challenges to selected teams
  - Edit individual team programs
  - View rewards/challenges count per team

**ProgramForm.vue (UPDATED - Admin)**

- Added "Category" dropdown (General/Team Affinity)
- Added "Team" input field (shown only for Team Affinity programs)
- Updated constraint type to include "Specific Player" option
- When player constraint selected, shows dropdown of all players

#### 4. Router

- Added `/programs/team-affinity` route (user-facing)
- Added `/admin/programs/team-affinity` route (admin)

## Team Affinity Program Structure

### Default Rewards (per team)

1. **5 Stars** - Silver Team Affinity Player Card (NULL - admin sets)
2. **10 Stars** - Team Affinity Pack (NULL - admin sets)
3. **15 Stars** - 1,000 Stubs
4. **20 Stars** - 5,000 Season XP (adds to active season programs)
5. **25 Stars** - Gold Team Affinity Player Card (NULL - admin sets)
6. **365 Stars** - Final Team Affinity Reward (NULL - admin sets)

### Default Challenges (per team)

1. Win 5 games with [Team] players - 2 stars
2. Score 100 points with [Team] players - 3 stars
3. Earn 180 PXP with Silver Team Affinity card - 10 stars (player ID NULL - admin sets)

### Challenge Constraint Types

- **team** - Require players from specific team
- **player** - Require specific player card (by player_id)
- **position** - Require specific position
- **tier** - Require specific card tier

## Usage Guide

### For Users

1. Navigate to Programs page
2. Click the Team Affinity banner
3. Choose from 30 NBA teams organized by division
4. Click team to view detailed program
5. Complete challenges to earn stars
6. Claim rewards at milestone thresholds

### For Admins

#### Initial Setup

```bash
# Create all Team Affinity programs
cd backend
php artisan programs:create-team-affinity

# Or delete existing and recreate
php artisan programs:create-team-affinity --fresh
```

#### Bulk Management

1. Navigate to Admin → Programs → Team Affinity
2. Select multiple teams using checkboxes
3. Use "Add Reward to Selected Teams" or "Add Challenge to Selected Teams"
4. Configure rewards/challenges that apply to all selected teams

#### Individual Team Management

1. Navigate to Admin → Programs → Team Affinity
2. Click "Edit" for specific team
3. Configure team-specific player cards for rewards
4. Set player_id for PXP challenges
5. Add/edit/remove rewards and challenges

#### Setting Team-Specific Player Cards

1. Create player cards with `category='team_affinity'` or appropriate collection
2. In Team Affinity Management, edit each team's program
3. Set `reward_id` for Silver (5 stars), Gold (25 stars), and Final (365 stars) rewards
4. Update PXP challenge `constraint_value` to the player_id of reward cards

## Testing

### Verify Installation

```bash
# Check programs created
php artisan tinker --execute="echo \App\Models\Program::where('category', 'team_affinity')->count();"
# Should output: 30

# Check rewards created
php artisan tinker --execute="echo \App\Models\ProgramReward::whereIn('program_id', \App\Models\Program::where('category', 'team_affinity')->pluck('id'))->count();"
# Should output: 180 (6 rewards × 30 teams)

# Check challenges created
php artisan tinker --execute="echo \App\Models\ProgramChallenge::whereIn('program_id', \App\Models\Program::where('category', 'team_affinity')->pluck('id'))->count();"
# Should output: 90 (3 challenges × 30 teams)
```

### Frontend Testing

1. Visit `/programs` - Should see Team Affinity banner
2. Click banner → Should navigate to `/programs/team-affinity`
3. Should see 30 teams organized by 6 divisions
4. Click team → Should navigate to program detail view
5. Admin: Visit `/admin/programs/team-affinity` for bulk management

## Next Steps

1. **Create Team Affinity Player Cards**

   - Create 90 player cards (Silver, Gold, Final for each of 30 teams)
   - Set appropriate ratings and attributes
   - Assign to Team Affinity collection/tier

2. **Configure Rewards**

   - Edit each team's program in admin
   - Set `reward_id` for player card rewards (5, 25, 365 stars)
   - Set `reward_id` for pack rewards (10 stars) if using team-specific packs

3. **Configure Challenges**

   - Update PXP challenge `constraint_value` to match silver card player_id
   - Add more team-specific challenges as desired
   - Use bulk add feature for challenges common across teams

4. **Add Team Logos**
   - Update each program's `image_url` field with team logo
   - Can be done in bulk or individually through admin panel

## Files Modified/Created

### Backend

- ✅ `database/migrations/*_add_team_and_category_to_programs_table.php`
- ✅ `database/migrations/*_update_program_challenges_constraint_type.php`
- ✅ `app/Models/Program.php`
- ✅ `app/Models/ProgramChallenge.php`
- ✅ `app/Http/Controllers/ProgramController.php`
- ✅ `app/Console/Commands/CreateTeamAffinityPrograms.php`
- ✅ `app/Http/Controllers/Admin/AdminTeamAffinityController.php`
- ✅ `routes/api.php`

### Frontend

- ✅ `src/services/api.js`
- ✅ `src/services/programService.js`
- ✅ `src/stores/programStore.ts`
- ✅ `src/views/TeamAffinityView.vue` (NEW)
- ✅ `src/views/ProgramsView.vue`
- ✅ `src/views/admin/programs/TeamAffinityManagement.vue` (NEW)
- ✅ `src/views/admin/programs/ProgramForm.vue`
- ✅ `src/router/index.ts`

## Summary

All Team Affinity functionality has been successfully implemented:

- ✅ 30 Team-specific programs created (365 stars each)
- ✅ 180 Rewards configured (6 per team)
- ✅ 90 Challenges configured (3 per team)
- ✅ User-facing Team Affinity page with division organization
- ✅ Admin bulk management interface
- ✅ XP rewards integrate with season programs
- ✅ Player-specific challenge constraints
- ✅ All routes and navigation working

The system is ready for use! Admins just need to configure team-specific player cards for the reward placeholders.
