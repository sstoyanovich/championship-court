# Stats Viewing Feature Implementation

## Overview

Added comprehensive stats viewing capabilities to both the Lineups and Collections screens, allowing users to inspect detailed player statistics at any time.

## What Was Implemented

### 1. StatsModal Component (`frontend/src/components/StatsModal.vue`)

A reusable modal component for displaying player statistics:

**Features:**

- **Loading State**: Shows spinner while fetching stats
- **Error Handling**: Displays error message with retry button
- **Stats Card Integration**: Uses the existing StatsCard component
- **No Stats Message**: Prompts users to upload game stats if none exist
- **Quick Link**: Direct link to stats upload page
- **Smooth Animations**: Enter/exit transitions for better UX
- **Responsive Design**: Works on mobile and desktop
- **Teleport to Body**: Modal renders at body level for proper z-index

**Props:**

- `show` (Boolean): Controls modal visibility
- `userCardId` (Number): ID of the user card to display stats for

**Emits:**

- `close`: Emitted when user closes the modal

### 2. PlayerCard Component Updates (`frontend/src/components/PlayerCard.vue`)

Enhanced the PlayerCard component to support stats viewing:

**New Props:**

- `showStats` (Boolean, default: false): Enables the stats button

**New Emit:**

- `view-stats`: Emitted with userCardId when stats button is clicked

**UI Changes:**

- Added **📊 STATS** button next to the lock button for owned cards
- Both buttons now displayed in a flex container
- Stats button has purple gradient matching app theme
- Hover effects with smooth animations

**CSS Updates:**

- Renamed `.lock-button-container` to `.action-buttons-container`
- Buttons are now flex items that share space equally
- Added `.stats-button` styles with purple gradient

### 3. CollectionView Updates (`frontend/src/views/CollectionView.vue`)

Integrated stats viewing into the Collections screen:

**Changes:**

- Imported `StatsModal` component
- Added state management:
  - `showStatsModal` (Boolean): Controls modal visibility
  - `selectedUserCardId` (Number): Tracks which card's stats to show
- Added handler functions:
  - `handleViewStats(userCardId)`: Opens stats modal
  - `closeStatsModal()`: Closes stats modal
- Updated PlayerCard instances:
  - Added `:show-stats="card.owned"` prop
  - Added `@view-stats="handleViewStats"` event handler
- Added StatsModal component to template

**User Flow:**

1. User views their collection
2. Clicks "📊 STATS" button on any owned card
3. Modal opens showing detailed statistics
4. User can close modal and continue browsing

### 4. LineupEditorView Updates (`frontend/src/views/LineupEditorView.vue`)

Integrated stats viewing into the Lineup Editor:

**Changes:**

- Imported `StatsModal` component
- Added state management (same as CollectionView)
- Added handler functions (same as CollectionView)
- Updated both Starters and Bench sections:
  - Added "📊" stats button next to the remove (×) button
  - Stats button positioned at `right: 42px` to avoid overlapping remove button
  - Both buttons overlay the player card in filled slots
- Added CSS for `.stats-btn`:
  - Purple gradient background
  - Circular button (28px × 28px)
  - Positioned in top-right corner
  - Hover effect with scale transform
- Added StatsModal component to template

**User Flow:**

1. User edits their lineup
2. Clicks "📊" icon on any player in starters or bench
3. Modal opens showing that player's statistics
4. User can close modal and continue editing lineup

## Features

### Stats Modal Display

When users view stats, they see:

1. **Player Name**: Displayed prominently
2. **Games Played**: Number of games tracked
3. **Per Game Averages**:
   - PPG (Points Per Game)
   - RPG (Rebounds Per Game)
   - APG (Assists Per Game)
4. **Shooting Percentages**:
   - FG% (Field Goal Percentage)
   - 3P% (3-Point Percentage)
5. **Other Stats**:
   - STL (Steals)
   - BLK (Blocks)
   - TO (Turnovers)
6. **Expandable Career Totals**:
   - Total Points
   - Total Rebounds
   - Total Assists
   - Total Minutes

### No Stats Scenario

If a player hasn't played any games yet:

- Shows a friendly message explaining no stats are available
- Provides a direct link to "/stats/upload"
- Encourages user to upload game screenshots

## UI/UX Improvements

### Button Layout

- **Collections**: Stats and Lock buttons displayed side-by-side at bottom of card
- **Lineups**: Stats (📊) and Remove (×) buttons overlay card in top-right corner

### Visual Design

- Stats button uses purple gradient matching app theme (`#667eea` to `#764ba2`)
- Smooth hover effects with color darkening and scale transform
- Consistent button sizing and styling across all views
- Clear visual hierarchy with icons (📊 for stats, 🔒 for lock, × for remove)

### Responsive Behavior

- Modal adapts to screen size
- Buttons maintain visibility on smaller screens
- Touch-friendly button sizes (28px minimum)

## API Integration

### Endpoint Used

```
GET /api/stats/card/{userCardId}
```

### Response Format

```json
{
  "success": true,
  "data": {
    "player_name": "Domantas Sabonis",
    "games_played": 5,
    "total_stats": {
      "minutes": 175,
      "points": 140,
      "rebounds": 40,
      "assists": 50,
      "steals": 10,
      "blocks": 5,
      "turnovers": 15,
      "fgm": 50,
      "fga": 100,
      "3pm": 10,
      "3pa": 30
    },
    "percentages": {
      "fg_percentage": 50.0,
      "3p_percentage": 33.3
    },
    "averages": {
      "ppg": 28.0,
      "rpg": 8.0,
      "apg": 10.0
    }
  }
}
```

## Files Modified

### New Files

1. `frontend/src/components/StatsModal.vue` - Modal component for displaying stats

### Modified Files

1. `frontend/src/components/PlayerCard.vue` - Added stats button and emit
2. `frontend/src/views/CollectionView.vue` - Integrated stats viewing
3. `frontend/src/views/LineupEditorView.vue` - Integrated stats viewing

## Testing Checklist

✅ **Collections Screen**

- [ ] Stats button appears on owned cards only
- [ ] Stats button opens modal with correct player data
- [ ] Modal closes properly
- [ ] Loading state displays while fetching
- [ ] Error state displays if API fails
- [ ] "No stats" message shows for players with 0 games
- [ ] Link to upload stats works

✅ **Lineup Editor Screen**

- [ ] Stats button appears on all lineup slots (starters & bench)
- [ ] Stats button doesn't interfere with remove button
- [ ] Modal opens with correct player data
- [ ] All modal features work same as Collections

✅ **Player Card**

- [ ] Stats button only shows when `showStats` prop is true
- [ ] Stats button only shows for owned cards
- [ ] Hover effects work smoothly
- [ ] Button styling matches app theme

✅ **Stats Modal**

- [ ] Displays all stat categories correctly
- [ ] Percentages calculated accurately
- [ ] Averages calculated correctly
- [ ] Expandable career totals section works
- [ ] Modal closes on overlay click
- [ ] Modal closes on X button click
- [ ] Smooth enter/exit animations
- [ ] Responsive on mobile devices

## Usage Examples

### Viewing Stats in Collection

```
1. Navigate to /collection
2. Browse to a team with owned players
3. Click "📊 STATS" button on any player card
4. View detailed statistics
5. Click X or outside modal to close
```

### Viewing Stats in Lineup

```
1. Navigate to /lineups
2. Open any lineup
3. Click "📊" icon on any player in lineup
4. View detailed statistics
5. Click X or outside modal to close
```

### First-Time User (No Stats)

```
1. Click "📊 STATS" on any card
2. See message "This player hasn't played any games yet"
3. Click "Upload Game Stats" link
4. Gets redirected to /stats/upload
5. Upload NBA 2K screenshot
6. Return to collection/lineup to see stats
```

## Future Enhancements

### Potential Improvements

1. **Historical Trends**: Graph showing performance over time
2. **Comparison View**: Compare stats between multiple players
3. **Best Game**: Highlight the player's best individual performance
4. **Recent Games**: Show list of last 5-10 games with stats
5. **Export Stats**: Download stats as CSV or PDF
6. **Stat Goals**: Set personal goals (e.g., "Average 20 PPG")
7. **Achievements**: Badges for milestones (100 points, 50 rebounds, etc.)
8. **League Stats**: Compare player to others in collection

### Technical Improvements

1. **Caching**: Cache stat data to reduce API calls
2. **Prefetching**: Load stats in background when hovering over button
3. **Skeleton Loading**: Show stat layout while loading
4. **Offline Support**: Cache stats locally for offline viewing

## Known Limitations

1. **Stats require games**: Players show "0 games" until stats are uploaded
2. **Manual upload**: Stats must be manually uploaded from NBA 2K screenshots
3. **No real-time sync**: Stats don't update automatically from NBA 2K

## Conclusion

The stats viewing feature is fully integrated and provides users with:

- ✅ Easy access to player statistics from Collections and Lineups
- ✅ Comprehensive stat display with averages and percentages
- ✅ Smooth, intuitive UI with clear visual feedback
- ✅ Responsive design for all screen sizes
- ✅ Helpful guidance for first-time users

Users can now track their players' performance across games and make data-driven decisions about their lineups and collections!

---

**Status**: ✅ Complete and Ready for Testing
