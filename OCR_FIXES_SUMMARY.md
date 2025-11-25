# OCR System Fixes - October 2025

## Problem

The OCR system wasn't correctly extracting player stats from NBA 2K screenshots. Example:

- **Expected**: D. Sabonis - 41 MIN, 36 PTS, 30 REB, 3 AST, 0 STL, 0 BLK, 0 TO, 16-24 FG, 2-2 3PT
- **Actual**: Stats were not being parsed correctly

## Root Causes

### 1. Wrong PSM Mode

- **Before**: PSM 6 (uniform block of text)
- **After**: PSM 4 (single column of variable sizes)
- **Result**: Much better text extraction from the stat table

### 2. Missing Columns

The NBA 2K stat sheet has **12 columns**, not 9:

- MIN, PTS, REB, AST, STL, BLK, TO, FG, 3PT, **FT, OR, FLS**
- We were only parsing the first 9 columns
- This caused stat misalignment

### 3. Abbreviated Name Matching

- NBA 2K shows abbreviated names: "D. Sabonis", "J. Jaquez Jr."
- Database has full names: "Domantas Sabonis", "Jaime Jaquez Jr."
- Fuzzy matching wasn't handling this well

## Fixes Applied

### OcrService.php

#### 1. Updated OCR Configuration

```php
$ocr->psm(4); // PSM 4: single column of variable sizes
$ocr->config('tessedit_char_whitelist', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz.0123456789- ');
```

#### 2. Improved Name Pattern Matching

```php
// Now handles patterns like "D. Sabonis", "J. Jaquez Jr."
if (preg_match('/^([A-Z]\.\s*[A-Za-z]+(?:\s+Jr\.?)?)\s+(.+)$/', $line, $matches)) {
    $playerName = trim($matches[1]);
    $statsString = trim($matches[2]);
}
```

#### 3. Enhanced Fuzzy Matching for Abbreviated Names

```php
// If extracted name is abbreviated (e.g., "d. sabonis"), match by last name
if (count($extractedParts) >= 2 && strlen($extractedParts[0]) <= 2 && strpos($extractedParts[0], '.') !== false) {
    $extractedLastName = $extractedParts[count($extractedParts) - 1];
    $playerLastName = $playerParts[count($playerParts) - 1];

    if ($extractedLastName === $playerLastName) {
        return $playerName; // Perfect match!
    }
}
```

#### 4. Fixed Stat Column Parsing

```php
// NBA 2K actual order: MIN, PTS, REB, AST, STL, BLK, TO, FG, 3PT, FT, OR, FLS
// We extract MIN through 3PT (indices 0-8), ignore FT/OR/FLS (indices 9-11)
```

#### 5. Added Comprehensive Logging

- Logs raw OCR text
- Logs each line parsing attempt
- Logs extracted stats for each player
- Logs fuzzy matching results

### StatsController.php

#### 1. Improved Last Name Matching for Non-Lineup Uploads

```php
// Extract last name from abbreviated names for database searching
if (count($nameParts) >= 2 && strlen($nameParts[0]) <= 2 && strpos($nameParts[0], '.') !== false) {
    $lastName = $nameParts[count($nameParts) - 1];
    $userCards = UserCard::with('player')->whereHas('player', function ($query) use ($lastName) {
        $query->where('name', 'like', '%' . $lastName . '%');
    })->get();
}
```

#### 2. Perfect Match for Last Names

```php
// If abbreviated name's last name matches exactly, it's a perfect match
if ($extractedLastName === $playerLastName) {
    // Return 100% similarity instead of fuzzy matching
}
```

## Test Results

Tested with PSM modes 3, 4, 6, and 11. **PSM Mode 4** performed best:

### D. Sabonis Example (PSM 4)

```
Extracted: "D. Sabonis"
Stats: 41, 36, 30, 3, 0, 0, 0, 16-24, 2-2, 2-2, 3, 0
```

**Parsed correctly:**

- MIN: 41 ✓
- PTS: 36 ✓
- REB: 30 ✓
- AST: 3 ✓
- STL: 0 ✓
- BLK: 0 ✓
- TO: 0 ✓
- FG: 16-24 ✓
- 3PT: 2-2 ✓
- FT: 2-2 (ignored)
- OR: 3 (ignored)
- FLS: 0 (ignored)

## Debugging

To view OCR logs during processing:

```bash
tail -f backend/storage/logs/laravel.log
```

Look for:

- "OCR Extracted Text" - Raw Tesseract output
- "Attempting to parse line" - Each line being processed
- "Extracted player name" - Name and stats string
- "Extracted stat values" - Numeric values found
- "Parsed stat values for" - Final parsed stats
- "Fuzzy match result" - Name matching results

## What Should Work Now

1. **Abbreviated Names**: "D. Sabonis" matches "Domantas Sabonis" ✓
2. **Jr. Names**: "J. Jaquez Jr." handled correctly ✓
3. **Stat Extraction**: All 9 stats (MIN-3PT) extracted accurately ✓
4. **Last Name Matching**: Works even without lineup selection ✓
5. **Shooting Stats**: "16-24" parsed as FGM:16, FGA:24 ✓

## Known Limitations

1. **OCR Accuracy**: Still depends on screenshot quality
2. **Name Variations**: Very different spellings may not match
3. **DNP Players**: "Did Not Play" entries are skipped
4. **OCR Errors**: Some stats may have OCR errors (e.g., "O" vs "0")

## Recommendations

1. **Screenshot Quality**: Take high-resolution screenshots (1080p+)
2. **Use Lineups**: Select the lineup you used for better matching
3. **Check Results**: Review matched/unmatched players before trusting data
4. **PNG Format**: Use PNG for best OCR accuracy

## Future Improvements

1. **Image Preprocessing**: Crop to stat area, enhance contrast
2. **Confidence Scores**: Report OCR confidence per field
3. **Manual Correction**: UI for fixing mismatched players
4. **Training Data**: Fine-tune OCR for NBA 2K specific layout
5. **Batch Upload**: Process multiple games at once

---

**Status**: OCR system now correctly parses NBA 2K stat sheets! 🎉
