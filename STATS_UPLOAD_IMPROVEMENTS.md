# Stats Upload UI Improvements

## Issues Fixed

### 1. ✅ Screenshot Not Displaying

**Problem**: Uploaded screenshot wasn't showing in the review screen.

**Root Cause**: Laravel's `Storage::url()` returns a relative path like `/storage/screenshots/filename.jpg`, but the frontend needs the full URL with the backend domain.

**Solution**: Added `getFullImageUrl()` helper function that prepends `http://localhost:8000` to relative URLs.

```javascript
const getFullImageUrl = (url) => {
  if (!url) return "";
  if (url.startsWith("http")) return url;
  return `http://localhost:8000${url}`;
};
```

**Updated Template**:

```vue
<img
  :src="getFullImageUrl(extractedData.screenshot_url)"
  alt="Game Screenshot"
  class="screenshot-img"
/>
```

---

### 2. ✅ Lineups Dropdown Empty

**Problem**: The lineup selector showed no options even though the user had created lineups.

**Root Cause**: The backend returns `{ success: true, lineups: [...] }`, but the frontend was trying to access `response.data` directly instead of `response.data.lineups`.

**Solution**: Updated the `onMounted` hook to correctly extract lineups from the API response:

```javascript
// Before (WRONG):
lineups.value = response.data;

// After (CORRECT):
lineups.value = response.data.lineups || response.data || [];
```

This handles both response formats gracefully.

---

### 3. ✅ Manual Entry for Missing Lineup Players

**Problem**: If OCR couldn't detect a player that was in the selected lineup, there was no way to manually enter their stats.

**Solution**: Implemented automatic addition of missing lineup players with empty stat rows for manual entry.

#### How It Works

1. **Store Selected Lineup**: When a user uploads with a lineup selected, we store the full lineup object:

   ```javascript
   selectedLineup.value = selectedLineupId.value
     ? lineups.value.find((l) => l.id === selectedLineupId.value)
     : null;
   ```

2. **Add Missing Players**: After OCR processing, check which lineup players weren't matched:

   ```javascript
   const addMissingLineupPlayers = () => {
     // Get all player IDs that were matched by OCR
     const matchedPlayerIds = editableStats.value.matched.map(
       (m) => m.player_id
     );

     // For each player in the lineup
     selectedLineup.value.slots.forEach((slot) => {
       if (slot.user_card && slot.user_card.player) {
         const playerId = slot.user_card.player.id;

         // If not matched by OCR, add empty row
         if (!matchedPlayerIds.includes(playerId)) {
           editableStats.value.matched.push({
             player_name: slot.user_card.player.name,
             player_id: playerId,
             user_card_id: slot.user_card.id,
             extracted_name: "Manual Entry",
             similarity: 0,
             stats: {
               player_name: slot.user_card.player.name,
               minutes: 0,
               points: 0,
               rebounds: 0,
               assists: 0,
               steals: 0,
               blocks: 0,
               turnovers: 0,
               fgm: 0,
               fga: 0,
               tpm: 0,
               tpa: 0,
             },
           });
         }
       }
     });
   };
   ```

3. **Visual Indicators**: Manual entry rows are highlighted with:

   - Yellow background (`#fffbeb`)
   - Yellow left border (`#fbbf24`)
   - "✏️ Manual" badge next to player name

   ```css
   .manual-entry-row {
     background: #fffbeb !important;
     border-left: 4px solid #fbbf24;
   }

   .manual-badge {
     background: #fbbf24;
     color: #78350f;
     font-size: 0.75rem;
     font-weight: 700;
   }
   ```

#### User Experience

**Scenario**: User uploads a screenshot with a lineup selected.

1. **OCR Success**: Most players are auto-detected and stats are filled in
2. **OCR Miss**: Some players in the lineup aren't detected (bad angle, obscured, etc.)
3. **Manual Entry**: System adds those missing players with empty (0) stats
4. **User Edits**: User can fill in the stats manually for those players
5. **Visual Distinction**: Manual entry rows are highlighted in yellow with a badge

**Benefits**:

- ✅ No stats are lost if OCR fails
- ✅ Users can verify and complete incomplete data
- ✅ Clear visual indication of which data is OCR vs manual
- ✅ Lineup-based uploads are now foolproof

---

## Files Changed

### Frontend

- **`frontend/src/views/StatsUploadView.vue`**
  - Added `getFullImageUrl()` helper
  - Fixed lineups data extraction in `onMounted()`
  - Added `selectedLineup` ref to store lineup reference
  - Added `addMissingLineupPlayers()` function
  - Updated template to show manual entry badge
  - Added CSS for manual entry row highlighting

### Backend

No backend changes required (all fixes were frontend)

---

## Testing Checklist

### Screenshot Display

- [x] Upload screenshot with lineup selected
- [x] Verify screenshot displays in review screen
- [x] Check image loads with correct URL

### Lineups Dropdown

- [x] Create at least one lineup
- [x] Open stats upload page
- [x] Verify lineup appears in dropdown
- [x] Select lineup and verify it's used

### Manual Entry

- [x] Create lineup with 5 players
- [x] Upload screenshot showing only 3 of those players
- [x] Verify 2 missing players appear with "Manual Entry" badge
- [x] Verify manual entry rows are highlighted yellow
- [x] Fill in stats manually
- [x] Confirm and verify all stats saved correctly

---

## Future Enhancements

### 1. Smart Auto-Fill

If a player has previous game stats, pre-fill with their average stats as a starting point:

```javascript
stats: {
  minutes: previousAvg.minutes || 0,
  points: previousAvg.ppg || 0,
  // etc.
}
```

### 2. DNP (Did Not Play) Option

Add a checkbox for "Did Not Play" to quickly skip players without entering zeros:

```vue
<input
  type="checkbox"
  v-model="player.dnp"
  @change="setAllStatsToZero(player)"
/>
```

### 3. Copy Stats from Previous Game

Button to copy stats from the player's last game as a starting point.

### 4. Keyboard Shortcuts

- Tab through stat inputs efficiently
- Enter to save
- Escape to cancel

### 5. Validation Warnings

Real-time warnings for suspicious values:

- Minutes > 48
- FGA < FGM
- 3PA < 3PM
- Points don't match shooting (rough estimate)

---

## Summary

All three issues are now resolved:

1. ✅ **Screenshot displays** correctly with full backend URL
2. ✅ **Lineups dropdown** shows user's lineups properly
3. ✅ **Manual entry** for missing lineup players with clear visual indicators

The stats upload system is now more robust and user-friendly, handling both successful OCR detection and manual entry scenarios seamlessly.
