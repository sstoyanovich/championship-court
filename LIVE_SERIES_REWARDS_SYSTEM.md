# Live Series Rewards System

## Overview

The Live Series features a sophisticated hierarchical rewards system with **4 tiers** of collections:

1. **Team Collections** (30 NBA teams)
2. **Division Collections** (6 divisions)
3. **Conference Collections** (2 conferences)
4. **NBA Collection** (ultimate reward)

Additionally, reward cards belong to **Special Collections** based on their card type (Awards, All-Star, Postseason, etc.).

## Structure

### Tier 1: Team Collections (30 Teams)

Each NBA team has a Live Series collection. Completing a team (locking all players) rewards a special player card.

**Examples:**
- Complete **Miami Heat** → Earn **90 OVR Chris Bosh (Postseason)**
- Complete **Los Angeles Lakers** → Earn **96 OVR Kobe Bryant (Awards)**
- Complete **Golden State Warriors** → Earn **95 OVR Draymond Green (2nd Half Heroes)**

### Tier 2: Division Collections (6 Divisions)

Each division collection requires locking the **5 team reward cards** from teams in that division.

#### Atlantic Division
- **Teams:** Celtics, Nets, Knicks, 76ers, Raptors
- **Rewards to Lock:** Paul Pierce, Brook Lopez, Carmelo Anthony, Andre Iguodala, DeMar DeRozan
- **Division Reward:** **96 OVR Bob Cousy (All-Star)**

#### Central Division
- **Teams:** Bulls, Cavaliers, Pistons, Pacers, Bucks
- **Rewards to Lock:** Joakim Noah, Kevin Love, Chauncey Billups, Reggie Miller, Kareem Abdul-Jabbar
- **Division Reward:** **98 OVR Derrick Rose (Awards)**

#### Southeast Division
- **Teams:** Hawks, Hornets, Heat, Magic, Wizards
- **Rewards to Lock:** Joe Johnson, Kemba Walker, Chris Bosh, Dwight Howard, Michael Jordan
- **Division Reward:** **93 OVR Alonzo Mourning (Postseason)**

#### Northwest Division
- **Teams:** Nuggets, Timberwolves, Thunder, Trail Blazers, Jazz
- **Rewards to Lock:** David Thompson, Kevin Garnett, Russell Westbrook, Brandon Roy, Karl Malone
- **Division Reward:** **98 OVR Kevin Durant (Awards)**

#### Pacific Division
- **Teams:** Warriors, Clippers, Lakers, Suns, Kings
- **Rewards to Lock:** Draymond Green, Elton Brand, Kobe Bryant, Steve Nash, Chris Webber
- **Division Reward:** **98 OVR Shaquille O'Neal (Postseason)**

#### Southwest Division
- **Teams:** Mavericks, Rockets, Grizzlies, Pelicans, Spurs
- **Rewards to Lock:** Dirk Nowitzki, Hakeem Olajuwon, Marc Gasol, Jrue Holiday, Tim Duncan
- **Division Reward:** **97 OVR James Harden (Awards)**

### Tier 3: Conference Collections (2 Conferences)

Each conference collection requires locking the **3 division reward cards** from divisions in that conference.

#### Eastern Conference
- **Divisions:** Atlantic, Central, Southeast
- **Rewards to Lock:** Bob Cousy, Derrick Rose, Alonzo Mourning
- **Conference Reward:** **99 OVR Julius Erving (Awards)**

#### Western Conference
- **Divisions:** Northwest, Pacific, Southwest
- **Rewards to Lock:** Kevin Durant, Shaquille O'Neal, James Harden
- **Conference Reward:** **99 OVR Charles Barkley (Hall of Fame)**

### Tier 4: NBA Collection (Ultimate Reward)

The NBA collection requires locking **both conference reward cards**.

- **Conferences:** Eastern, Western
- **Rewards to Lock:** Julius Erving, Charles Barkley
- **NBA Reward:** **99 OVR Wilt Chamberlain (Signature)**

## Special Collections (Card Sets)

Reward cards also belong to special thematic collections based on their card type:

- **Awards** - Award-winning players (16 cards)
- **All-Star** - NBA All-Stars (7 cards)
- **Postseason** - Playoff performers (5 cards)
- **Veteran** - Veteran players (3 cards)
- **Standout** - Standout performers (2 cards)
- **2nd Half Heroes** - Second-half dominators (2 cards)
- **Contributor** - Key contributors (1 card)
- **Breakout** - Breakout stars (2 cards)
- **Milestone** - Milestone achievers (1 card)
- **Last Ride** - Final seasons (1 card)
- **Hall of Fame** - Hall of Famers (1 card)
- **Signature** - Signature series (1 card)

## Dual Collection Mechanic

**Reward cards serve dual purposes:**

1. **Progress toward higher-tier collections** (Division → Conference → NBA)
2. **Can be collected in their special thematic set**

### Example Flow:

1. User completes **Miami Heat** → Earns **Chris Bosh (Postseason)**
2. User can lock Chris Bosh for:
   - **Southeast Division** progress (1/5 toward Alonzo Mourning)
   - **Postseason Collection** (1/5 toward Postseason set completion)
3. Locking the card counts toward BOTH collections simultaneously

## Full Completion Path

To earn Wilt Chamberlain (NBA reward), a user must:

1. **Complete 30 team collections** → Earn 30 team rewards
2. **Lock team rewards to complete 6 divisions** → Earn 6 division rewards
3. **Lock division rewards to complete 2 conferences** → Earn 2 conference rewards
4. **Lock conference rewards to complete NBA** → Earn **Wilt Chamberlain**

**Total cards required:** 30 team rewards + 6 division rewards + 2 conference rewards = **38 special cards locked**

This represents completing the ENTIRE Live Series - all 30 teams!

## Implementation Notes

### Database Structure

All Live Series collections share `name = 'Live Series'` for grouping:

- Team collections: `type = 'team'`, `sub_collection = team_name` (e.g., "Miami Heat")
- Division collections: `type = 'division'`, `sub_collection = division_name + " Division"` (e.g., "Atlantic Division")
- Conference collections: `type = 'conference'`, `sub_collection = conference_name + " Conference"` (e.g., "Eastern Conference")
- NBA collection: `type = 'nba'`, `sub_collection = 'NBA'`
- Free Agent collection: `type = 'team'`, `sub_collection = 'Free Agent'`

Special collections (Awards, All-Star, etc.) have different names and `type = 'special'`

### Progress Tracking

For division/conference/NBA collections:
- Progress is tracked by checking if user owns and has locked the required reward cards
- The `collection_id` on reward cards points to their special collection (Awards, All-Star, etc.)
- Division/conference/NBA collections don't "own" the cards directly
- Instead, rewards specify which player_id is required

### Reward Cards

- All team reward cards: `team = actual_team_name`
- All division/conference/NBA reward cards: `team = 'Free Agent'`
- All reward cards have `collection_id` set to their special collection

## Created Data

### Special Collections (12)
✓ Awards, All-Star, Postseason, Veteran, Standout, 2nd Half Heroes, Contributor, Breakout, Milestone, Last Ride, Hall of Fame, Signature

### Reward Players (39 total)
- 30 team rewards (one per team)
- 6 division rewards (one per division)
- 2 conference rewards (one per conference)
- 1 NBA reward (ultimate)

### Collections (39 total)
- 30 team collections (existing)
- 6 division collections (new)
- 2 conference collections (new)
- 1 NBA collection (new)
- 12 special collections (new)

## Admin Management

Admins can manage rewards through:
1. **Admin → Collections → Edit Team Collection**
   - View/edit team reward (player card)
   - Set required cards threshold

2. **Admin → Collections → Edit Division Collection**
   - View/edit division reward
   - Requires 5 team rewards

3. **Admin → Collections → Edit Special Collection**
   - View all cards in that set
   - Optionally add set completion rewards

## User Experience

1. **Collections View** shows all available collections
2. **Team Collections** display individual team progress
3. **Division Collections** show which team rewards are needed
4. **Conference Collections** show which division rewards are needed
5. **NBA Collection** shows the final challenge
6. **Special Collections** show thematic card sets

When users lock cards, they automatically progress in all relevant collections!

