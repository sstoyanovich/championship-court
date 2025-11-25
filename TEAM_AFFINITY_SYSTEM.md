# Team Affinity System

## Overview

Team Affinity is a progression system where users earn rewards by building affinity with their favorite NBA teams. Each team has **5 progressive reward tiers**, with better players unlocked at higher levels.

## Structure

### 30 Team Affinity Collections

Each of the 30 NBA teams has its own Team Affinity collection with 5 reward tiers:

- **Tier 1 (79 OVR Silver)** - 10 affinity points
- **Tier 2 (84 OVR Gold)** - 25 affinity points
- **Tier 3 (85 OVR Gold - Jolt)** - 50 affinity points
- **Tier 4 (91 OVR Sapphire - Color Storm)** - 75 affinity points
- **Tier 5 (99 OVR Galaxy Opal - Neon)** - 100 affinity points

## Example: Miami Heat Team Affinity

| Tier | Overall | Tier | Card Type | Player | Required Points |
|------|---------|------|-----------|---------|-----------------|
| 1 | 79 | Silver | Rookie | Michael Beasley | 10 |
| 2 | 84 | Gold | Standout | P.J. Brown | 25 |
| 3 | 85 | Gold | Jolt | Dwyane Wade | 50 |
| 4 | 91 | Sapphire | Color Storm | Nikola Jovic | 75 |
| 5 | 99 | Galaxy Opal | Neon | Chris Bosh | 100 |

## Card Types by Tier

### Tier 1 (79 OVR)
- **Contributor** - Key role players
- **Veteran** - Experienced players
- **Rookie** - Impressive rookies
- **Breakout** - Emerging stars

### Tier 2 (84 OVR)
- **Standout** - Notable performers
- **Veteran** - Seasoned pros
- **Breakout** - Rising stars
- **Contributor** - Impact players

### Tier 3 (85 OVR)
- **Jolt** - All cards are "Jolt" type - electric performances

### Tier 4 (91 OVR)
- **Color Storm** - All cards are "Color Storm" type - explosive talent

### Tier 5 (99 OVR)
- **Neon** - All cards are "Neon" type - brightest stars

## Special Collections

Team Affinity introduces **4 new special collections**:

1. **Jolt** (30 cards) - 85 OVR cards from tier 3 of each team
2. **Color Storm** (30 cards) - 91 OVR cards from tier 4 of each team
3. **Neon** (30 cards) - 99 OVR cards from tier 5 of each team
4. **Rookie** (cards assigned) - Impressive rookie performances

Existing collections that Team Affinity cards belong to:
- **Contributor**
- **Veteran**
- **Breakout**
- **Standout**

## How to Earn Affinity Points

Users can earn affinity points by:

1. **Locking team cards** - Lock current roster players into the team's Live Series collection
2. **Completing team challenges** - Team-specific gameplay challenges
3. **Using team players** - Play games with team players in your lineup
4. **Moments** - Complete team-specific moments

*Note: The specific earning mechanics can be configured based on gameplay design*

## All Teams and Top Rewards

| Team | Tier 3 (Jolt) | Tier 5 (Neon) |
|------|---------------|---------------|
| Atlanta Hawks | Dominique Wilkins | Dejounte Murray |
| Boston Celtics | Kevin McHale | Jaylen Brown |
| Brooklyn Nets | Egor Demin | Cam Thomas |
| Charlotte Hornets | Baron Davis | Brandon Miller |
| Chicago Bulls | Artis Gilmore | Coby White |
| Cleveland Cavaliers | Zydrunas Ilgauskas | Evan Mobley |
| Dallas Mavericks | Jason Kidd | Cooper Flagg |
| Denver Nuggets | Alex English | Jamal Murray |
| Detroit Pistons | Chauncey Billups | Jaden Ivey |
| Golden State Warriors | Tim Hardaway | Klay Thompson |
| Houston Rockets | Tracy McGrady | Jabari Smith Jr. |
| Indiana Pacers | Jermaine O'Neal | Obi Toppin |
| Los Angeles Clippers | Elton Brand | Blake Griffin |
| Los Angeles Lakers | Shaquille O'Neal | James Worthy |
| Memphis Grizzlies | Zach Randolph | Marc Gasol |
| Miami Heat | Dwyane Wade | Chris Bosh |
| Milwaukee Bucks | Michael Redd | Oscar Robertson |
| Minnesota Timberwolves | Kevin Garnett | Karl-Anthony Towns |
| New Orleans Pelicans | Chris Paul | Brandon Ingram |
| New York Knicks | Patrick Ewing | OG Anunoby |
| Oklahoma City Thunder | Shawn Kemp | Chet Holmgren |
| Orlando Magic | Hedo Türkoğlu | Penny Hardaway |
| Philadelphia 76ers | Allen Iverson | Allen Iverson (Neon) |
| Phoenix Suns | Amar'e Stoudemire | Steve Nash |
| Portland Trail Blazers | Brandon Roy | Shaedon Sharpe |
| Sacramento Kings | Chris Webber | Keegan Murray |
| San Antonio Spurs | Manu Ginóbili | Keldon Johnson |
| Toronto Raptors | Vince Carter | RJ Barrett |
| Utah Jazz | Karl Malone | Ace Bailey |
| Washington Wizards | Gilbert Arenas | Corey Kispert |

## Database Structure

- **Collection Name:** "Team Affinity"
- **Collection Type:** "team"
- **Sub-Collection:** Team name (e.g., "Miami Heat")
- **Rewards:** 5 player card rewards per team

## Implementation Details

### Collections Created
- 30 Team Affinity collections (one per NBA team)

### Players Created
- 150 reward players total (5 per team)
- Each assigned to appropriate special collection (Jolt, Color Storm, Neon, etc.)

### Collection Rewards
- 150 collection rewards (30 teams × 5 tiers)
- Progressive thresholds: 10, 25, 50, 75, 100 points
- All rewards are player cards

### Reward Claiming
- Automatic when threshold is reached
- One-time only per tier per user
- Must reach each tier sequentially

## User Experience

1. **Collections View** shows "Team Affinity" as a collection option
2. **Select a team** to see its 5 reward tiers
3. **View progress** toward each tier with progress bars
4. **Earn affinity** through various activities
5. **Unlock rewards** automatically as thresholds are met
6. **Build collection** of special Jolt, Color Storm, and Neon cards

## Differences from Live Series

| Feature | Live Series | Team Affinity |
|---------|-------------|---------------|
| **Progression** | Lock all team players | Earn affinity points |
| **Rewards** | 1 reward per team | 5 progressive rewards per team |
| **Reward Tiers** | Single tier (varies) | 5 tiers (79, 84, 85, 91, 99) |
| **Completion** | Binary (complete/incomplete) | Progressive (5 levels) |
| **Card Types** | Various | Standardized (Jolt, Color Storm, Neon) |
| **Meta Collections** | Division → Conference → NBA | None |

## Admin Management

Admins can manage Team Affinity through:

1. **Admin → Collections → Team Affinity → Select Team**
2. View/edit all 5 reward tiers
3. Adjust required affinity points for each tier
4. Change reward players
5. Add additional intermediate rewards

## Notable Cards

### Must-Have 99 OVR Neon Cards
- **Jaylen Brown** (Celtics)
- **Cooper Flagg** (Mavericks)
- **Chet Holmgren** (Thunder)
- **Penny Hardaway** (Magic)
- **Allen Iverson** (76ers)
- **Steve Nash** (Suns)
- **Oscar Robertson** (Bucks)
- **James Worthy** (Lakers)
- **Chris Bosh** (Heat)

### Legendary 85 OVR Jolt Cards
- **Shaquille O'Neal** (Lakers)
- **Dwyane Wade** (Heat)
- **Tracy McGrady** (Rockets)
- **Kevin Garnett** (Timberwolves)
- **Patrick Ewing** (Knicks)
- **Allen Iverson** (76ers)
- **Vince Carter** (Raptors)
- **Karl Malone** (Jazz)

## Total Content

- **30 collections** (one per team)
- **150 rewards** (5 per team)
- **150 player cards** (all unique within their tier)
- **4 new special collections** (Jolt, Color Storm, Neon, Rookie)

Team Affinity provides a parallel progression path to Live Series, giving users more ways to build their ultimate team! 🏀🎯

