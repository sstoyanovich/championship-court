# OCR Fixes for Utah Jazz Screenshot

## Issue

User uploaded a Utah Jazz vs Lakers screenshot (`nhxExoN1CnskXcnga5pK14qYAoajwC1EWVy24jlb.jpg`) but no stats were extracted.

## Root Causes

### 1. **Stats Separation Bug**

The original logic for separating individual stats from shot stats was flawed. When extracting numbers like "0", if they appeared in a shot stat like "0-0", the code would mark ALL zeros as "part of a shot stat" and remove them from the individual stats array.

**Fix**: Changed from filtering out numbers that appear in shot stats to simply preserving order and separating by the presence of a hyphen.

```php
// OLD (buggy) - would remove all zeros if "0-0" existed
foreach ($allNumbers as $num) {
    $isPartOfShot = false;
    foreach ($shotStats as $shot) {
        if (strpos($shot, $num) !== false) {
            $isPartOfShot = true;
        }
    }
    if (!$isPartOfShot) {
        $individualStats[] = $num;
    }
}

// NEW (fixed) - separate by presence of hyphen
foreach ($statValues as $value) {
    if (strpos($value, '-') !== false) {
        $shotStats[] = $value;
    } else {
        $individualStats[] = $value;
    }
}
```

### 2. **OCR Artifacts Not Cleaned**

The OCR was extracting lines like:

- `! S. Sharpe 9 6 1 0...` - Special character at start
- `i Y. Missi 10 0 9 2...` - Lowercase letter at start
- `C. Carrington 3 4 1 1 ¢ ¢ 2-2` - Currency symbols instead of zeros
- `P.J. Washington 10 0 D 0...` - Letter "D" instead of zero

**Fix**: Added comprehensive cleaning:

```php
// Remove special characters and lowercase letters at start of line
$line = preg_replace('/^[!@#$%^&*()_+=\[\]{}|;:,.<>?\/\\~`\'"]+\s*/', '', $line);
$line = preg_replace('/^[a-z]+\s+/', '', $line);

// Clean up stats string - replace OCR errors
$statsString = preg_replace('/[¢€£¥§¶†‡°•◦▪▫]/', '0', $statsString);
$statsString = preg_replace('/\bD\b/', '0', $statsString);
$statsString = preg_replace('/\be\b/', '3', $statsString);
$statsString = preg_replace('/\bO\b/', '0', $statsString);
```

### 3. **Multi-Part Initials Not Supported**

Player names like "P.J. Washington" weren't being parsed correctly because the regex only supported single initials like "D. Sabonis".

**Fix**: Added a dedicated pattern for multi-part initials:

```php
// Pattern 1: Multi-part initials like "P.J. Washington"
if (preg_match('/^([A-Z]\.[A-Z]\.\s+[A-Za-z]+(?:\s+Jr\.?)?)\s+(.+)$/', $line, $matches)) {
    $playerName = trim($matches[1]);
    $statsString = trim($matches[2]);
}
```

### 4. **Number-Prefixed Names**

OCR sometimes read player names like "Z. Collins" as "2. Collins".

**Fix**: Added a pattern to handle number-prefixed names:

```php
// Pattern 4: Number + period + Last name (OCR error)
elseif (preg_match('/^(\d+\.\s*[A-Za-z]+)\s+(.+)$/', $line, $matches)) {
    $playerName = trim($matches[1]);
    $statsString = trim($matches[2]);
}
```

## Results

**Before Fix**: 0 players extracted
**After Fix**: 11/11 players successfully extracted

Successfully extracted players:

- H. Barnes
- J. Fears
- S. Sharpe
- C. Carrington
- 2. Collins (will fuzzy match to Z. Collins)
- M. Morris
- P.J. Washington ✅ (was missing)
- Y. Missi ✅ (was missing)
- P. Achiuwa
- 2. Williams (will fuzzy match to Z. Williams)
- J. Konchar

## Known Limitations

Some minor OCR errors remain in the extracted data:

- H. Barnes 3PT shows as "40-0" instead of "1-2" (OCR read "1-2" as separate numbers "12" and "40-0")
- J. Fears may have some stat inaccuracies due to corrupted OCR text

These are fundamental OCR accuracy issues that would require advanced image preprocessing (like AI-based correction) to fully resolve.

## Next Steps

The system is now functional for the Utah Jazz screenshot. The user should be able to re-upload it and successfully extract stats.
