# Team Affinity Programs System

## Overview

Team Affinity is a star-based program system where players earn stars by completing team-specific challenges. Each NBA team has its own Team Affinity program with 27 challenges and 27 rewards (including player cards, packs, stubs, and XP).

## Implementation Summary

### Existing Structure

Team Affinity programs were already created with:

- **Type**: Star-based progression (not XP)
- **Challenges**: 27 per team
- **Rewards**: 27 per team (mixed types: players, packs, stubs, XP)

### What Was Updated

The player rewards at specific star thresholds were updated to assign the correct player cards from the Team Affinity specification:

## Reward Structure

Each Team Affinity program has 5 key player reward tiers:

1. **Level 1 (5 stars)**: 79 OVR player
2. **Level 2 (25 stars)**: 84 OVR player
3. **Level 3 (55 stars)**: 85 OVR player
4. **Level 4 (135 stars)**: 91 OVR player
5. **Level 5 (285 stars)**: 99 OVR player

_Note: Programs also have additional player reward slots at 330 and 365 stars that remain unassigned._

## Challenge Structure

Each Team Affinity program has 27 challenges total, including:

### Player-Specific PXP Challenges

These challenges require earning PXP with specific reward players:

1. **180 PXP** with the 79 OVR player (10 stars)
2. **300 PXP** with the 84 OVR player (10 stars)
3. **500 PXP** with the 85 OVR player (10 stars)
4. **500 PXP** with the 91 OVR player (150 stars)
5. **500 PXP** with the 99 OVR player (50 stars)

### Team-Based Challenges

- Score points, assists, rebounds, steals, blocks, and three-pointers with team players
- Earn cumulative PXP with any player from the team (1,000 / 2,000 / 4,000 / 8,000 PXP)

### Example: Atlanta Hawks Team Affinity

**Player Rewards:**

- **5 stars**: Kyle Korver (79 OVR Silver)
- **25 stars**: Jeff Teague (84 OVR Gold)
- **55 stars**: Dominique Wilkins (85 OVR Gold)
- **135 stars**: Kristaps Porziņģis (91 OVR Sapphire)
- **285 stars**: Dejounte Murray (99 OVR Galaxy Opal)

**Player-Specific PXP Challenges:**

- **180 PXP** with Kyle Korver → 10 stars
- **300 PXP** with Jeff Teague → 10 stars
- **500 PXP** with Dominique Wilkins → 10 stars
- **500 PXP** with Kristaps Porziņģis → 150 stars
- **500 PXP** with Dejounte Murray → 50 stars

### Example: Los Angeles Lakers Team Affinity

**Player Rewards:**

- **5 stars**: Austin Reaves (79 OVR Silver)
- **25 stars**: D'Angelo Russell (84 OVR Gold)
- **55 stars**: Shaquille O'Neal (85 OVR Gold)
- **135 stars**: Rui Hachimura (91 OVR Sapphire)
- **285 stars**: James Worthy (99 OVR Galaxy Opal)

**Player-Specific PXP Challenges:**

- **180 PXP** with Austin Reaves → 10 stars
- **300 PXP** with D'Angelo Russell → 10 stars
- **500 PXP** with Shaquille O'Neal → 10 stars
- **500 PXP** with Rui Hachimura → 150 stars
- **500 PXP** with James Worthy → 50 stars

## Technical Details

### Seeders

**UpdateTeamAffinityRewardsSeeder.php**

- Updates existing program rewards (does not create new programs)
- Links 150 existing player cards to the appropriate reward slots (5 per team × 30 teams)
- Uses player name + overall rating to match players
- Updates the reward description for each player reward

**UpdateTeamAffinityChallengesSeeder.php**

- Updates PXP challenges to link them with specific reward players
- Sets `constraint_type` to 'player' and `constraint_value` to player ID
- Updates 5 player-specific challenges per team (150 total)
- Challenges track PXP earned with specific reward cards
- Each challenge now references the exact player required (e.g., "Earn 180 PXP with Kyle Korver")

### Key Features

- All programs were already active
- Programs use star-based progression (earned through challenges)
- Player rewards are automatically granted when star thresholds are reached
- Player cards were already created by previous seeders (Live Series Rewards, etc.)
- **Player-specific PXP challenges now track the exact reward player's progress**
- The challenge system uses `constraint_type: 'player'` to identify player-specific challenges
- Existing challenges and other reward types (packs, stubs, XP) remain unchanged

## Statistics

- **Total Programs**: 30 (one per team)
- **Player Rewards Updated**: 150 (5 per team)
- **Challenges Updated**: 150 (5 PXP challenges per team)
- **Star Range for Player Rewards**: 5 - 285
- **OVR Range**: 79 - 99
- **Total Challenges per Program**: 27 (including stats, PXP, and game count challenges)

## All Teams Included

1. Atlanta Hawks
2. Boston Celtics
3. Brooklyn Nets
4. Charlotte Hornets
5. Chicago Bulls
6. Cleveland Cavaliers
7. Dallas Mavericks
8. Denver Nuggets
9. Detroit Pistons
10. Golden State Warriors
11. Houston Rockets
12. Indiana Pacers
13. LA Clippers
14. Los Angeles Lakers
15. Memphis Grizzlies
16. Miami Heat
17. Milwaukee Bucks
18. Minnesota Timberwolves
19. New Orleans Pelicans
20. New York Knicks
21. Oklahoma City Thunder
22. Orlando Magic
23. Philadelphia 76ers
24. Phoenix Suns
25. Portland Trail Blazers
26. Sacramento Kings
27. San Antonio Spurs
28. Toronto Raptors
29. Utah Jazz
30. Washington Wizards

## Notes

- Team Affinity is a **program system**, not a collection system
- Players earn stars through completing team-specific challenges
- Each team's program is independent with its own challenge set
- All reward players belong to special collections (Contributor, Veteran, Jolt, Color Storm, Neon, Rookie, Breakout, Standout)
- The reward players were created by previous seeders
- **Two updates were made**:
  1. Player reward assignments to the 5 key reward slots
  2. Challenge player constraints to link PXP challenges to specific reward players
- The program structure, challenge requirements, and star rewards remain unchanged
