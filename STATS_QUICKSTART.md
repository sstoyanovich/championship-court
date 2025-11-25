# Stats OCR System - Quick Start Guide

## Prerequisites

✅ Tesseract OCR installed (via Homebrew): `brew install tesseract`
✅ PHP Tesseract wrapper installed: `composer require thiagoalessio/tesseract_ocr`
✅ Database migrations run: `php artisan migrate`
✅ Storage linked: `php artisan storage:link`

## Starting the Application

### Backend (Terminal 1)

```bash
cd backend
php artisan serve
```

Backend will run at: **http://localhost:8000**

### Frontend (Terminal 2)

```bash
cd frontend
npm run dev
```

Frontend will run at: **http://localhost:5174**

## Testing the Stats Upload Feature

### Step 1: Access the Stats Upload Page

1. Open browser to: **http://localhost:5174/stats/upload**
2. Or click "Upload Stats" in the navigation bar

### Step 2: Prepare a Screenshot

Take a screenshot of the final stat sheet from an NBA 2K game showing:

- Player names in the left column
- Stats in columns: MIN, PTS, REB, AST, STL, BLK, TO, FG, 3PT

### Step 3: Upload the Screenshot

1. **(Optional)** Select a lineup from the dropdown if you played with one of your saved lineups
2. **Drag and drop** the screenshot or click to browse
3. Preview the image to confirm it's correct
4. Click **"Process Screenshot"**

### Step 4: Review Results

The system will display:

- **✓ Matched Players**: Players successfully identified and matched to your collection
  - Shows similarity percentage
  - Displays extracted stats (PTS, REB, AST)
- **⚠ Unmatched Players**: Players not found in your collection
  - Shows extracted name and stats
  - These won't be saved but are shown for reference
- **⚠ Validation Warnings**: Any stats that seem unrealistic

### Step 5: View Updated Stats

- Go to your Collection to see updated cumulative stats
- Stats are added immediately upon successful processing

## How Player Matching Works

### With Lineup Selected

The system will only match players from your selected lineup, making matching more accurate and faster.

### Without Lineup

The system will try to match against all players in your collection by name similarity.

### Fuzzy Matching

- **70%+ similarity** required for a match
- Handles common OCR errors (e.g., "Lebron" vs "LeBron", "Ja Morant" vs "Ja Morant ")
- Case-insensitive matching

## Tracked Stats

### Per Game Stats Extracted

- Minutes (MIN)
- Points (PTS)
- Rebounds (REB)
- Assists (AST)
- Steals (STL)
- Blocks (BLK)
- Turnovers (TO)
- Field Goals Made/Attempted (FGM/FGA)
- 3-Pointers Made/Attempted (3PM/3PA)

### Cumulative Stats Stored

All stats are cumulative across all games played with that card.

### Calculated Stats

- **FG%**: Field Goal Percentage (FGM/FGA × 100)
- **3P%**: 3-Point Percentage (3PM/3PA × 100)
- **PPG**: Points Per Game (Total Points / Games Played)
- **RPG**: Rebounds Per Game (Total Rebounds / Games Played)
- **APG**: Assists Per Game (Total Assists / Games Played)

## API Endpoints

### Upload Screenshot

```bash
curl -X POST http://localhost:8000/api/stats/upload-screenshot \
  -F "screenshot=@/path/to/screenshot.png" \
  -F "user_id=1" \
  -F "lineup_id=1"
```

### Get Card Stats

```bash
curl http://localhost:8000/api/stats/card/{userCardId}
```

## Troubleshooting

### OCR Returns Empty Text

- **Issue**: Screenshot quality too low or text too small
- **Solution**: Take a higher resolution screenshot, ensure text is clear

### No Players Matched

- **Issue**: Player names don't match your collection
- **Solution**:
  - Make sure you have the players in your collection
  - Try selecting the lineup you used in the game
  - Check if OCR misread names (shown in unmatched section)

### "Failed to extract text from image"

- **Issue**: Tesseract not installed or not in PATH
- **Solution**:
  ```bash
  brew install tesseract
  which tesseract  # Should show: /opt/homebrew/bin/tesseract
  ```

### Storage Error

- **Issue**: Screenshots directory doesn't exist or isn't writable
- **Solution**:
  ```bash
  cd backend
  mkdir -p storage/app/public/screenshots
  chmod -R 775 storage
  php artisan storage:link
  ```

### Image Upload Fails

- **Issue**: File too large (>10MB)
- **Solution**: Compress or resize the screenshot before uploading

## Tips for Best Results

### Screenshot Quality

- ✅ Take screenshots at 1080p or higher resolution
- ✅ Ensure stat sheet is fully visible and not cut off
- ✅ Use PNG format for best quality
- ✅ Avoid motion blur or compression artifacts

### Player Names

- ✅ Make sure players are in your collection before uploading
- ✅ Use lineups to improve matching accuracy
- ✅ OCR works best with clear, unobstructed text

### Stat Validation

The system validates:

- Minutes: 0-48 per game
- Points: 0-100 per game
- FGA ≥ FGM
- 3PA ≥ 3PM

## What's Stored

### User Cards Table

Each card accumulates:

- Total games played
- All cumulative stats

### Game Sessions Table

Each upload is logged with:

- Screenshot file path
- Raw OCR text
- Parsed player stats
- Processing timestamp
- Associated lineup (if any)

## Next Steps: Challenges (Coming Soon)

The stats system is designed to enable challenge features:

- "Score 1000 total points with any Diamond card"
- "Average a triple-double over 10 games"
- "Shoot 50% from 3-point range over 20 games"
- "Get 100 steals with defensive players"

Stay tuned for the challenges feature!

## Demo Data

Want to test without playing NBA 2K? You can:

1. Create a mock screenshot with player names and stats in a table
2. Use any clear text image with player data
3. The OCR will attempt to parse any structured stat data

## Support

If you encounter issues:

1. Check browser console for errors (F12)
2. Check Laravel logs: `backend/storage/logs/laravel.log`
3. Verify Tesseract is working: `tesseract --version`
4. Ensure both frontend and backend servers are running

---

**Enjoy tracking your player stats and dominating Championship Court!** 🏀📊
