# Stats OCR System Implementation Summary

## Overview

Successfully implemented an OCR-based stat tracking system that processes NBA 2K game screenshots to extract and store cumulative player statistics per user card.

## What Was Implemented

### Backend (Laravel)

#### 1. Database Migrations

- **`add_stats_to_user_cards_table.php`**: Added 12 cumulative stat columns to `user_cards` table:

  - `games_played`: Counter for games played
  - `total_minutes`, `total_points`, `total_rebounds`, `total_assists`, `total_steals`, `total_blocks`, `total_turnovers`
  - `total_fgm`, `total_fga` (Field Goals Made/Attempted)
  - `total_3pm`, `total_3pa` (3-Pointers Made/Attempted)

- **`create_game_sessions_table.php`**: Created table to track individual game uploads:
  - Stores screenshot path, user_id, lineup_id
  - Stores raw OCR data in JSON format
  - Tracks processing timestamp

#### 2. Models

- **`GameSession.php`**: Model for tracking game upload sessions with relationships to User and Lineup
- **`UserCard.php` (Updated)**: Added:
  - Stats fields to fillable array
  - `addGameStats($stats)`: Method to add game stats to cumulative totals
  - `getFGPercentage()`, `get3PPercentage()`: Calculate shooting percentages
  - `getPPG()`, `getRPG()`, `getAPG()`: Calculate per-game averages

#### 3. OCR Service

- **`app/Services/OcrService.php`**: Complete OCR processing service:
  - `extractTextFromImage($imagePath)`: Uses Tesseract OCR to extract text from images
  - `parseStatSheet($ocrText, $expectedPlayers)`: Parses NBA 2K stat sheet format
  - `fuzzyMatchPlayerName()`: Matches OCR'd names to actual player names (handles typos)
  - `parseStatValues()`: Extracts individual stat values from OCR text
  - `validateStats()`: Validates that stats are within reasonable ranges
  - Handles different stat formats (e.g., "5-10" or "5/10" for shooting)

#### 4. Stats Controller

- **`app/Http/Controllers/StatsController.php`**: Handles screenshot uploads and processing:
  - `uploadScreenshot()`: Main endpoint for uploading game screenshots
    - Validates image file (JPEG, PNG, GIF up to 10MB)
    - Stores image in `storage/app/public/screenshots`
    - Runs OCR extraction
    - Parses player stats
    - Matches players to user cards (with fuzzy matching)
    - Updates cumulative stats
    - Returns detailed results with matched/unmatched players
  - `getCardStats($userCardId)`: Returns all stats for a specific user card

#### 5. API Routes

- `POST /api/stats/upload-screenshot`: Upload and process game screenshot
- `GET /api/stats/card/{userCardId}`: Get stats for a specific card

#### 6. Dependencies Installed

- **Tesseract OCR** (via Homebrew): System-level OCR engine
- **thiagoalessio/tesseract_ocr** (v2.13.0): PHP wrapper for Tesseract

### Frontend (Vue 3)

#### 1. Views

- **`StatsUploadView.vue`**: Complete upload interface with:
  - Lineup selector dropdown (optional)
  - Drag-and-drop file upload with preview
  - Image preview before upload
  - Loading state during processing
  - Results display showing:
    - Matched players (with similarity percentage)
    - Unmatched players (not in collection)
    - Validation warnings
  - Beautiful gradient design matching app theme
  - Fully responsive (mobile-friendly)

#### 2. Components

- **`StatsCard.vue`**: Reusable stats display component showing:
  - Games played counter
  - Per-game averages (PPG, RPG, APG)
  - Shooting percentages (FG%, 3P%)
  - Total stats (steals, blocks, turnovers)
  - Expandable career totals section
  - Gradient card design

#### 3. API Service Updates

- **`api.js`**: Added two new methods:
  - `uploadGameScreenshot(file, lineupId, userId)`: Uploads screenshot with FormData
  - `getCardStats(userCardId)`: Fetches stats for a card

#### 4. Router Updates

- Added `/stats/upload` route for StatsUploadView

#### 5. Navigation

- Added "Upload Stats" link to main navigation bar

## How It Works

### Upload Flow

1. User selects a lineup (optional) or uploads without one
2. User uploads a screenshot from NBA 2K game
3. Frontend sends multipart form data to backend
4. Backend stores the image and runs Tesseract OCR
5. OCR text is parsed to extract player names and stats
6. Players are matched to user's cards using fuzzy name matching
7. Stats are added to cumulative totals for matched cards
8. Results are returned showing which players were matched/unmatched
9. Frontend displays results with color-coded success/warning states

### OCR Strategy

- Tesseract processes the screenshot with PSM mode 6 (uniform block of text)
- Parses text looking for player names followed by numeric stats
- Expected stat order: MIN, PTS, REB, AST, STL, BLK, TO, FG, 3PT
- Handles different formats: "5-10", "5/10", "5 10" for shooting stats
- Fuzzy matching (60%+ similarity) handles OCR errors in player names
- Validates stats are within reasonable ranges (e.g., minutes ≤ 48)

### Data Storage

- **Cumulative stats**: Stored directly on `user_cards` table
- **Game sessions**: Each upload is logged in `game_sessions` table with raw OCR data
- **Screenshots**: Stored in `storage/app/public/screenshots/`

## Configuration

### Storage

- Screenshots directory created: `storage/app/public/screenshots/`
- Symbolic link created: `public/storage` → `storage/app/public`

### Tesseract Location

- Installed via Homebrew: `/opt/homebrew/bin/tesseract`
- Language data: English (eng)

## Testing Instructions

1. **Start the backend**: `php artisan serve` (from `backend/` directory)
2. **Start the frontend**: `npm run dev` (from `frontend/` directory)
3. **Navigate to**: http://localhost:5174/stats/upload
4. **Test flow**:
   - Optionally select a lineup
   - Upload an NBA 2K game stat screenshot
   - View the parsed results
   - Check that matched players have updated stats

## Features Implemented

✅ Database migrations for stats tracking
✅ OCR service with Tesseract integration
✅ NBA 2K stat sheet parsing with fuzzy name matching
✅ Cumulative stat tracking per user card
✅ Game session history tracking
✅ Screenshot upload with validation
✅ Beautiful upload UI with drag-and-drop
✅ Results display with matched/unmatched players
✅ Stats display component
✅ API endpoints for upload and retrieval
✅ Frontend integration with router and navigation
✅ Error handling and validation

## Future Enhancements

### Phase 2: Challenges System

- Create `challenges` table
- Define stat-based challenges (e.g., "Score 1000 total points")
- Track user progress toward challenges
- Award rewards upon completion
- Add challenges UI to frontend

### Phase 3: Enhanced Stats

- Add more detailed stats (FT%, +/-, etc.)
- Track stats per game (not just cumulative)
- Add stat history and trends
- Leaderboards for top performers

### Phase 4: Improved OCR

- Fine-tune OCR for specific NBA 2K screenshot layouts
- Add image preprocessing (crop, enhance contrast, etc.)
- Support multiple screenshot formats
- Batch upload multiple games at once

## File Structure

```
backend/
├── app/
│   ├── Http/Controllers/
│   │   └── StatsController.php
│   ├── Models/
│   │   ├── GameSession.php
│   │   └── UserCard.php (updated)
│   └── Services/
│       └── OcrService.php
├── database/migrations/
│   ├── 2025_10_21_042737_add_stats_to_user_cards_table.php
│   └── 2025_10_21_042743_create_game_sessions_table.php
├── routes/
│   └── api.php (updated)
└── storage/app/public/screenshots/

frontend/
├── src/
│   ├── components/
│   │   └── StatsCard.vue
│   ├── views/
│   │   └── StatsUploadView.vue
│   ├── router/
│   │   └── index.ts (updated)
│   ├── services/
│   │   └── api.js (updated)
│   └── App.vue (updated)
```

## API Documentation

### POST /api/stats/upload-screenshot

Upload and process a game screenshot

**Request:**

- Content-Type: multipart/form-data
- Fields:
  - `screenshot` (file, required): Image file (PNG, JPEG, GIF, max 10MB)
  - `lineup_id` (integer, optional): ID of lineup used in game
  - `user_id` (integer, required): User ID (default: 1)

**Response:**

```json
{
  "success": true,
  "message": "Screenshot processed successfully",
  "data": {
    "game_session_id": 1,
    "ocr_text": "Full OCR extracted text...",
    "player_stats": [
      {
        "player_name": "LeBron James",
        "minutes": 35,
        "points": 28,
        "rebounds": 8,
        "assists": 10,
        "steals": 2,
        "blocks": 1,
        "turnovers": 3,
        "fgm": 10,
        "fga": 20,
        "tpm": 2,
        "tpa": 6
      }
    ],
    "match_results": {
      "matched": [
        {
          "player_name": "LeBron James",
          "extracted_name": "Lebron James",
          "similarity": 95.5,
          "user_card_id": 42,
          "stats": {...}
        }
      ],
      "unmatched": []
    },
    "validation_errors": []
  }
}
```

### GET /api/stats/card/{userCardId}

Get cumulative stats for a user card

**Response:**

```json
{
  "success": true,
  "data": {
    "player_name": "LeBron James",
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

## Notes

- The OCR accuracy depends on screenshot quality and format
- Fuzzy matching helps handle common OCR errors (e.g., "Lebron" vs "LeBron")
- Unmatched players are reported but stats are not lost (stored in game session)
- Users can manually review unmatched players and potentially add them later
- The system is extensible for future stat-based challenge systems

## Conclusion

The stats OCR system is fully functional and ready for testing. Users can now:

1. Upload NBA 2K game screenshots
2. Automatically extract player statistics
3. Track cumulative stats per player card
4. View detailed stats and averages
5. Use this data for future challenge systems

The implementation follows Laravel and Vue.js best practices, with proper error handling, validation, and a beautiful user interface.
