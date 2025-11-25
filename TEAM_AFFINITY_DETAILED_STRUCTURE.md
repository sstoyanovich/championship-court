# Team Affinity - Detailed Structure Applied

## Summary

All 30 Team Affinity programs have been updated with the complete detailed structure from the example. Each team now has **27 rewards** and **27 challenges** for a full year-long journey.

## Stats

- ✅ **30 Teams** - One program for each NBA team
- ✅ **810 Rewards** (27 × 30 teams)
- ✅ **810 Challenges** (27 × 30 teams)
- ✅ **365 Stars Required** for completion

## Reward Structure (27 Rewards per Team)

All rewards follow this exact structure for each team:

| Stars | Reward Type | Description                                      |
| ----- | ----------- | ------------------------------------------------ |
| 5     | Player Card | Gold 78 OVR Team Affinity Player                 |
| 10    | Pack        | Standard Pack                                    |
| 15    | Stubs       | 1,000 Stubs                                      |
| 20    | Pack        | 2x Standard Pack                                 |
| 25    | Player Card | Emerald 84 OVR Team Affinity Player              |
| 30    | XP          | 4,500 Season XP                                  |
| 35    | Pack        | 2x Standard Pack                                 |
| 40    | Pack        | Team Specific Pack                               |
| 45    | Pack        | Premium Pack                                     |
| 50    | XP          | 5,000 Season XP                                  |
| 55    | Player Card | Sapphire 85 OVR Team Affinity Player             |
| 65    | Stubs       | 1,000 Stubs                                      |
| 75    | Pack        | 3x Standard Pack                                 |
| 90    | Pack        | Premium Pack                                     |
| 105   | Stubs       | 1,000 Stubs                                      |
| 120   | Pack        | 5x Standard Pack                                 |
| 135   | Player Card | Amethyst 91 OVR Team Affinity Player             |
| 165   | Stubs       | 1,000 Stubs                                      |
| 195   | Pack        | 3x Team Specific Pack                            |
| 215   | Pack        | 5x Standard Pack                                 |
| 235   | Pack        | 5x Team Specific Pack                            |
| 255   | Stubs       | 1,000 Stubs                                      |
| 285   | Player Card | Pink Diamond 96 OVR Team Affinity Player         |
| 315   | Pack        | 5x Team Specific Pack                            |
| 330   | Player Card | Pink Diamond 99 OVR Team Affinity Player         |
| 345   | Pack        | Deluxe Choice Pack                               |
| 365   | Player Card | Pink Diamond 99 OVR Team Affinity Player (Final) |

### Player Card Rewards to Configure

Each team needs **7 player cards** to be created and assigned:

1. **5 stars** - Gold 78 OVR
2. **25 stars** - Emerald 84 OVR
3. **55 stars** - Sapphire 85 OVR
4. **135 stars** - Amethyst 91 OVR
5. **285 stars** - Pink Diamond 96 OVR
6. **330 stars** - Pink Diamond 99 OVR
7. **365 stars** - Pink Diamond 99 OVR (Final)

**Total player cards needed: 210** (7 cards × 30 teams)

## Challenge Structure (27 Challenges per Team)

Each team has 27 challenges organized into categories:

### Basic Team Stat Challenges (6 challenges - 5 stars each)

1. Score 100 points with [Team] players
2. Record 25 assists with [Team] players
3. Make 10 three-pointers with [Team] players
4. Grab 50 rebounds with [Team] players
5. Record 10 steals with [Team] players
6. Record 5 blocks with [Team] players

### PXP Challenges with Reward Cards (5 challenges - varying stars)

These require specific player cards to be set by admin:

1. Earn 180 PXP with the TA Gold reward card - **10 stars**
2. Earn 300 PXP with the TA Emerald reward card - **10 stars**
3. Earn 500 PXP with the TA Sapphire reward card - **10 stars**
4. Earn 500 PXP with the TA Amethyst reward card - **150 stars**
5. Earn 500 PXP with the first TA Pink Diamond reward card - **50 stars**

### PXP Challenges with Any Team Card (4 challenges)

1. Earn 1,000 PXP with any [Team] card - **5 stars**
2. Earn 2,000 PXP with any [Team] card - **5 stars**
3. Earn 4,000 PXP with any [Team] card - **10 stars**
4. Earn 8,000 PXP with any [Team] card - **25 stars**

### Medium Stat Challenges (6 challenges - 5 stars each)

1. Score 500 points with [Team] players
2. Grab 1,200 rebounds with [Team] players
3. Record 250 assists with [Team] players
4. Record 30 steals with [Team] players
5. Record 20 blocks with [Team] players
6. Make 50 three-pointers with [Team] players

### Large Stat Challenges (6 challenges - 15 stars each)

1. Score 2,000 points with [Team] players
2. Grab 1,000 rebounds with [Team] players
3. Record 400 assists with [Team] players
4. Record 100 steals with [Team] players
5. Record 50 blocks with [Team] players
6. Make 200 three-pointers with [Team] players

## Total Stars Available from Challenges

Each team can earn stars from challenges:

- Basic team stats: 6 × 5 = **30 stars**
- PXP with reward cards: 10 + 10 + 10 + 150 + 50 = **230 stars**
- PXP with any team card: 5 + 5 + 10 + 25 = **45 stars**
- Medium stat challenges: 6 × 5 = **30 stars**
- Large stat challenges: 6 × 15 = **90 stars**

**Total: 425 stars available from challenges** (more than the 365 needed!)

## Admin Configuration Needed

### 1. Create Player Cards (210 total)

For each of the 30 teams, create 7 player cards:

- Gold 78 OVR
- Emerald 84 OVR
- Sapphire 85 OVR
- Amethyst 91 OVR
- Pink Diamond 96 OVR
- Pink Diamond 99 OVR
- Pink Diamond 99 OVR (Final)

### 2. Assign Player Cards to Rewards

In the admin panel, for each team:

- Edit the program
- Set `reward_id` for each player card reward (stars: 5, 25, 55, 135, 285, 330, 365)

### 3. Configure PXP Challenge Constraints

For each team, update the 5 PXP challenges that require specific reward cards:

- Set `constraint_value` to the player_id of the corresponding reward card
- Gold card (5 stars reward) → 180 PXP challenge
- Emerald card (25 stars reward) → 300 PXP challenge
- Sapphire card (55 stars reward) → 500 PXP challenge
- Amethyst card (135 stars reward) → 500 PXP challenge
- First Pink Diamond card (285 stars reward) → 500 PXP challenge

### 4. Optional: Configure Packs

- Create team-specific packs if desired
- Assign pack IDs to the Team Specific Pack rewards
- Assign pack IDs to other pack rewards (Standard, Premium, Deluxe Choice)

### 5. Optional: Add Team Logos

- Set `image_url` field for each program with the team's logo

## Example: Miami Heat Verification

```bash
Team: Miami Heat
Rewards: 27
Challenges: 27

First 5 rewards:
  5 stars: Gold 78 OVR Team Affinity Player
  10 stars: Standard Pack
  15 stars: 1,000 Stubs
  20 stars: 2x Standard Pack
  25 stars: Emerald 84 OVR Team Affinity Player

Last 3 rewards:
  330 stars: Pink Diamond 99 OVR Team Affinity Player
  345 stars: Deluxe Choice Pack
  365 stars: Pink Diamond 99 OVR Team Affinity Player (Final)

First 10 challenges:
  5 stars: Score 100 points with Miami Heat players
  5 stars: Record 25 assists with Miami Heat players
  5 stars: Make 10 three-pointers with Miami Heat players
  5 stars: Grab 50 rebounds with Miami Heat players
  5 stars: Record 10 steals with Miami Heat players
  5 stars: Record 5 blocks with Miami Heat players
  10 stars: Earn 180 PXP with the TA Gold reward card
  10 stars: Earn 300 PXP with the TA Emerald reward card
  10 stars: Earn 500 PXP with the TA Sapphire reward card
  150 stars: Earn 500 PXP with the TA Amethyst reward card
```

## Quick Start Commands

### Recreate all programs from scratch:

```bash
cd backend
php artisan programs:create-team-affinity --fresh
```

### View programs in database:

```bash
php artisan tinker
# Check all TA programs
Program::where('category', 'team_affinity')->count()

# Check specific team
$heat = Program::where('team', 'Miami Heat')->with(['rewards', 'challenges'])->first()
$heat->rewards->count()  // Should be 27
$heat->challenges->count()  // Should be 27
```

## UI Access

- **User-facing**: Navigate to `/programs` → Click Team Affinity banner → Select team
- **Admin**: Go to `/admin/programs/team-affinity` for bulk management
- **Edit individual team**: Click "Edit" on any team in the admin panel

## Notes

- All stubs rewards are set to 1,000
- All XP rewards add to active season programs (e.g., "Preseason")
- Pack rewards with quantities (2x, 3x, 5x) use the `reward_amount` field
- Player card rewards have `reward_id` set to NULL initially (admin configures)
- PXP challenges requiring specific players have `constraint_value` set to NULL initially
- All team-specific challenges use the actual team name (e.g., "Miami Heat")

## Completion

✅ All 30 teams have been configured with the complete structure
✅ Ready for admin to assign player cards and configure rewards
✅ UI is ready to display all programs and challenges
✅ Bulk management tools available in admin panel
