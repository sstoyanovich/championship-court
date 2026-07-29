<?php

namespace App\Services;

use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Log;

class OcrService
{
    /**
     * Process an image file and extract text using Tesseract OCR
     */
    public function extractTextFromImage(string $imagePath): string
    {
        try {
            // Verify image file exists
            if (!file_exists($imagePath)) {
                throw new \Exception("Image file not found at path: {$imagePath}");
            }

            // Verify file is readable
            if (!is_readable($imagePath)) {
                throw new \Exception("Image file is not readable: {$imagePath}");
            }

            // Verify it's a valid image file
            $imageInfo = @getimagesize($imagePath);
            if ($imageInfo === false) {
                throw new \Exception("Invalid image file format. Please upload a valid image (JPEG, PNG, JPG, or GIF).");
            }

            // Try preprocessing the image for better OCR results
            $preprocessedPath = $this->preprocessImage($imagePath);

            $ocr = new TesseractOCR($preprocessedPath);

            // Configure Tesseract for better accuracy with NBA 2K screenshots
            $ocr->psm(4); // PSM 4: Assume a single column of text of variable sizes (best for tables)
            $ocr->lang('eng'); // English language

            // Use a config for better number recognition
            $ocr->config('tessedit_char_blacklist', '|[]{}@#$%^&*()+=<>?/\\');

            $text = $ocr->run();

            // Post-process to clean up common OCR mistakes
            $text = $this->cleanOcrText($text);

            // Clean up the preprocessed file
            if ($preprocessedPath !== $imagePath && file_exists($preprocessedPath)) {
                @unlink($preprocessedPath);
            }

            // Log the extracted text for debugging
            Log::info('OCR Extracted Text (cleaned):', ['text' => $text]);

            return $text;
        } catch (\thiagoalessio\TesseractOCR\TesseractOCRException $e) {
            Log::error('Tesseract OCR error: ' . $e->getMessage());
            // Check if Tesseract is installed
            $tesseractPath = shell_exec('which tesseract 2>&1');
            if (empty($tesseractPath) || strpos($tesseractPath, 'not found') !== false) {
                throw new \Exception('Tesseract OCR is not installed or not found in PATH. Please install Tesseract OCR to process screenshots.');
            }
            throw new \Exception('OCR processing failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('OCR extraction failed: ' . $e->getMessage(), [
                'image_path' => $imagePath,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Failed to extract text from image: ' . $e->getMessage());
        }
    }

    /**
     * Preprocess image for better OCR results
     */
    private function preprocessImage(string $imagePath): string
    {
        try {
            // Check if GD or Imagick is available
            if (!extension_loaded('gd') && !extension_loaded('imagick')) {
                Log::warning('No image processing extension available, skipping preprocessing');
                return $imagePath;
            }

            // For now, just return the original
            // In production, you could add: grayscale, contrast adjustment, noise reduction
            return $imagePath;
        } catch (\Exception $e) {
            Log::warning('Image preprocessing failed, using original: ' . $e->getMessage());
            return $imagePath;
        }
    }

    /**
     * Clean up common OCR mistakes in text
     */
    private function cleanOcrText(string $text): string
    {
        $lines = explode("\n", $text);
        $cleanedLines = [];

        foreach ($lines as $line) {
            $originalLine = $line;

            // Common single-character OCR mistakes (word boundaries)
            $line = preg_replace('/\bZ\b/', '2', $line); // Z commonly misread as 2
            $line = preg_replace('/\bO\b/', '0', $line); // O commonly misread as 0
            $line = preg_replace('/\bl\b/', '1', $line); // lowercase L commonly misread as 1
            $line = preg_replace('/\bI\b/', '1', $line); // capital I commonly misread as 1

            // Remove obvious garbage strings that don't look like stats
            // Keep shot attempts (e.g., "12-22") but remove gibberish
            $line = preg_replace('/\b[A-Za-z]+[0-9]+[A-Za-z0-9]*e\b/i', '', $line); // "Opoonl2e" patterns
            $line = preg_replace('/\bf+o+0+7+0+\b/i', '', $line); // "fo000070" patterns
            $line = preg_replace('/\b[o0]+n+[o0]+\b/i', '', $line); // "ono" patterns

            // Log if significant changes were made
            if ($line !== $originalLine) {
                Log::debug("Cleaned line: '$originalLine' => '$line'");
            }

            $cleanedLines[] = $line;
        }

        return implode("\n", $cleanedLines);
    }

    /**
     * Parse NBA 2K stat sheet text and extract player statistics
     * 
     * Expected format from NBA 2K:
     * Player Name | MIN | PTS | REB | AST | STL | BLK | TO | FG | 3PT
     */
    public function parseStatSheet(string $ocrText, array $expectedPlayers = []): array
    {
        $lines = explode("\n", $ocrText);
        $playerStats = [];

        Log::info('Parsing OCR text, total lines: ' . count($lines));

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Skip header lines
            if (
                stripos($line, 'NAME') !== false || stripos($line, 'MIN') !== false ||
                stripos($line, 'Lakers') !== false || stripos($line, 'Kings') !== false ||
                stripos($line, 'ATTENDANCE') !== false || stripos($line, 'Box Score') !== false
            ) {
                continue;
            }

            // Try to match a stat line
            // Pattern: Name followed by numbers separated by spaces or tabs
            $statData = $this->extractStatsFromLine($line, $expectedPlayers);

            if ($statData) {
                Log::info("Parsed player stats on line $lineNum", $statData);
                $playerStats[] = $statData;
            }
        }

        Log::info('Total players parsed: ' . count($playerStats));
        return $playerStats;
    }

    /**
     * Extract stats from a single line of OCR text
     */
    private function extractStatsFromLine(string $line, array $expectedPlayers = []): ?array
    {
        // Clean up common OCR artifacts at the start (special characters AND lowercase letters before names)
        $line = preg_replace('/^[!@#$%^&*()_+=\[\]{}|;:,.<>?\/\\~`\'"]+\s*/', '', $line);
        $line = preg_replace('/^[a-z]+\s+/', '', $line); // Remove lowercase letters at start
        $line = trim($line);

        Log::info("Attempting to parse line: $line");

        // Look for player name patterns: 
        // 1. Initial + period + Last name (e.g., "D. Sabonis", "J. Jaquez Jr.")
        // 2. Initial + dot + Last name (e.g., "P.J. Washington")
        // 3. Number + period + Last name (OCR error, e.g., "2. Collins" should be "Z. Collins")
        // 4. Full name (e.g., "Domantas Sabonis")

        // Try to match player name at the start of the line
        // Pattern 1: Multi-part initials like "P.J. Washington"
        if (preg_match('/^([A-Z]\.[A-Z]\.\s+[A-Za-z]+(?:\s+Jr\.?)?)\s+(.+)$/', $line, $matches)) {
            $playerName = trim($matches[1]);
            $statsString = trim($matches[2]);
        }
        // Pattern 2: Single initial + last name like "D. Sabonis"
        elseif (preg_match('/^([A-Z]\.?\s*[A-Za-z]+(?:\s+Jr\.?)?)\s+(.+)$/', $line, $matches)) {
            $playerName = trim($matches[1]);
            $statsString = trim($matches[2]);
        }
        // Pattern 3: Full name like "Domantas Sabonis"
        elseif (preg_match('/^([A-Z][a-z]+\s+[A-Za-z]+(?:\s+Jr\.?)?)\s+(.+)$/', $line, $matches)) {
            $playerName = trim($matches[1]);
            $statsString = trim($matches[2]);
        }
        // Pattern 4: Number + period + Last name (OCR error, e.g., "2. Collins")
        elseif (preg_match('/^(\d+\.\s*[A-Za-z]+)\s+(.+)$/', $line, $matches)) {
            $playerName = trim($matches[1]);
            $statsString = trim($matches[2]);
        } else {
            // Try splitting by spaces and looking for where numbers start
            $parts = preg_split('/\s+/', $line);
            $parts = array_filter($parts, fn($p) => !empty(trim($p)));
            $parts = array_values($parts);

            if (count($parts) < 3) {
                Log::info("Not enough parts in line, skipping");
                return null;
            }

            // Find where numbers start
            $nameEndIndex = 0;
            for ($i = 0; $i < count($parts); $i++) {
                // Check if this looks like a number or stat (including patterns like "16-24")
                if (preg_match('/^\d+(-\d+)?$/', $parts[$i])) {
                    $nameEndIndex = $i;
                    break;
                }
            }

            if ($nameEndIndex === 0 || $nameEndIndex >= count($parts) - 1) {
                Log::info("Could not find name/stats boundary, skipping");
                return null;
            }

            $playerName = implode(' ', array_slice($parts, 0, $nameEndIndex));
            $statsString = implode(' ', array_slice($parts, $nameEndIndex));
        }

        // Clean up the stats string - replace common OCR errors
        $statsString = preg_replace('/[¢€£¥§¶†‡°•◦▪▫]/', '0', $statsString); // Replace currency symbols with 0
        $statsString = preg_replace('/\bD\b/', '0', $statsString); // Replace standalone D with 0
        $statsString = preg_replace('/\be\b/', '3', $statsString); // Replace standalone e with 3
        $statsString = preg_replace('/\bO\b/', '0', $statsString); // Replace standalone O with 0

        Log::info("Extracted player name: $playerName, stats string: $statsString");

        // Extract all numbers and shot patterns, preserving order
        // Pattern: either "X-Y" (shot stat) or standalone number
        preg_match_all('/\d+-\d+|\d+/', $statsString, $matches);
        $statValues = $matches[0] ?? [];

        // Separate into individual stats vs shot stats
        $individualStats = [];
        $shotStats = [];

        foreach ($statValues as $value) {
            if (strpos($value, '-') !== false) {
                $shotStats[] = $value;
            } else {
                $individualStats[] = $value;
            }
        }

        Log::info("Extracted stat values: " . implode(', ', $statValues));
        Log::info("Individual stats: " . implode(', ', $individualStats) . " | Shot stats: " . implode(', ', $shotStats));

        if (count($individualStats) < 7) {
            Log::info("Not enough stat values found (" . count($individualStats) . " individual stats), skipping");
            return null; // Need at least MIN, PTS, REB, AST, STL, BLK, TO
        }

        // Fuzzy match the name to expected players
        $matchedPlayer = $this->fuzzyMatchPlayerName($playerName, $expectedPlayers);

        // Parse the stats - NBA 2K order: MIN, PTS, REB, AST, STL, BLK, TO, FG, 3PT, FT, OR, FLS
        // We care about: MIN, PTS, REB, AST, STL, BLK, TO, FG, 3PT
        return $this->parseStatValues($matchedPlayer ?: $playerName, $statValues);
    }

    /**
     * Fuzzy match a name to a list of expected player names
     */
    private function fuzzyMatchPlayerName(string $extractedName, array $expectedPlayers): ?string
    {
        if (empty($expectedPlayers)) {
            return $extractedName;
        }

        $extractedName = strtolower(trim($extractedName));
        $bestMatch = null;
        $highestSimilarity = 0;

        foreach ($expectedPlayers as $playerName) {
            $playerNameLower = strtolower($playerName);

            // Check for exact match first
            if ($extractedName === $playerNameLower) {
                return $playerName;
            }

            // Handle abbreviated names (e.g., "d. sabonis" matches "domantas sabonis")
            // Extract last name from both
            $extractedParts = explode(' ', $extractedName);
            $playerParts = explode(' ', $playerNameLower);

            // If extracted name starts with initial (single letter + period), match by last name
            if (count($extractedParts) >= 2 && strlen($extractedParts[0]) <= 2 && strpos($extractedParts[0], '.') !== false) {
                $extractedLastName = $extractedParts[count($extractedParts) - 1];
                $playerLastName = $playerParts[count($playerParts) - 1];

                // Check if last names match
                if ($extractedLastName === $playerLastName) {
                    return $playerName;
                }

                // Check if last names are very similar
                similar_text($extractedLastName, $playerLastName, $lastNamePercent);
                if ($lastNamePercent > 85) {
                    return $playerName;
                }
            }

            // Calculate overall similarity
            similar_text($extractedName, $playerNameLower, $percent);

            if ($percent > $highestSimilarity && $percent > 60) { // At least 60% similarity
                $highestSimilarity = $percent;
                $bestMatch = $playerName;
            }
        }

        Log::info("Fuzzy match result for '$extractedName': " . ($bestMatch ?: 'no match') . " (similarity: $highestSimilarity%)");
        return $bestMatch;
    }

    /**
     * Parse stat values from array of strings
     * Note: statValues array is structured as: [individual stats...] [shot stats...]
     */
    private function parseStatValues(string $playerName, array $statValues): array
    {
        // Clean and convert stat values
        $stats = [
            'player_name' => $playerName,
            'minutes' => 0,
            'points' => 0,
            'rebounds' => 0,
            'assists' => 0,
            'steals' => 0,
            'blocks' => 0,
            'turnovers' => 0,
            'fgm' => 0,
            'fga' => 0,
            'tpm' => 0,
            'tpa' => 0,
        ];

        // Separate individual stats from shot stats
        $individualStats = [];
        $shotStats = [];

        foreach ($statValues as $value) {
            if (strpos($value, '-') !== false) {
                $shotStats[] = $value;
            } else {
                $individualStats[] = $value;
            }
        }

        // NBA 2K actual order: MIN, PTS, REB, AST, STL, BLK, TO
        // Parse first 7 individual stats
        $index = 0;

        // Minutes
        if (isset($individualStats[$index])) {
            $stats['minutes'] = (int)$this->extractNumber($individualStats[$index]);
            $index++;
        }

        // Points
        if (isset($individualStats[$index])) {
            $stats['points'] = (int)$this->extractNumber($individualStats[$index]);
            $index++;
        }

        // Rebounds
        if (isset($individualStats[$index])) {
            $stats['rebounds'] = (int)$this->extractNumber($individualStats[$index]);
            $index++;
        }

        // Assists
        if (isset($individualStats[$index])) {
            $stats['assists'] = (int)$this->extractNumber($individualStats[$index]);
            $index++;
        }

        // Steals
        if (isset($individualStats[$index])) {
            $stats['steals'] = (int)$this->extractNumber($individualStats[$index]);
            $index++;
        }

        // Blocks
        if (isset($individualStats[$index])) {
            $stats['blocks'] = (int)$this->extractNumber($individualStats[$index]);
            $index++;
        }

        // Turnovers
        if (isset($individualStats[$index])) {
            $turnovers = (int)$this->extractNumber($individualStats[$index]);
            // Sanity check: turnovers shouldn't be > 48 (game is only 48 minutes)
            if ($turnovers > 48) {
                Log::warning("Unusual turnover value detected for $playerName: $turnovers (likely OCR error)");
                // Try to extract just the first digit if it's a multi-digit garbage number
                $turnovers = (int)substr((string)$turnovers, 0, 1);
            }
            $stats['turnovers'] = $turnovers;
            $index++;
        }

        // Shot stats: FG, 3PT, FT (we only care about first 2)
        // Field goals (X-Y format)
        if (isset($shotStats[0])) {
            list($fgm, $fga) = $this->parseShotStat($shotStats[0]);
            $stats['fgm'] = $fgm;
            $stats['fga'] = $fga;
        }

        // 3-pointers (X-Y format)
        if (isset($shotStats[1])) {
            list($tpm, $tpa) = $this->parseShotStat($shotStats[1]);
            $stats['tpm'] = $tpm;
            $stats['tpa'] = $tpa;
        }

        // Log warning if critical shooting stats are missing
        if ($stats['fgm'] == 0 && $stats['fga'] == 0 && $stats['points'] > 0) {
            Log::warning("Missing FG stats for $playerName who scored {$stats['points']} points - likely OCR error");
        }

        Log::info("Parsed stat values for $playerName", $stats);
        return $stats;
    }

    /**
     * Extract a number from a string (handles OCR errors)
     */
    private function extractNumber(string $value): int
    {
        // Remove non-numeric characters except dash and slash
        $cleaned = preg_replace('/[^0-9\-\/]/', '', $value);

        // If it contains dash or slash, take the first number
        if (strpos($cleaned, '-') !== false || strpos($cleaned, '/') !== false) {
            $parts = preg_split('/[-\/]/', $cleaned);
            return (int)($parts[0] ?? 0);
        }

        return (int)$cleaned;
    }

    /**
     * Parse a shot stat (like "5-10" or "5/10") into made and attempted
     */
    private function parseShotStat(string $value): array
    {
        // Handle formats like "5-10", "5/10", "5 10", etc.
        $cleaned = preg_replace('/[^0-9\-\/\s]/', '', $value);

        if (preg_match('/(\d+)[-\/\s]+(\d+)/', $cleaned, $matches)) {
            return [(int)$matches[1], (int)$matches[2]];
        }

        // If we can't parse it, return zeros
        return [0, 0];
    }

    /**
     * Validate stats are within reasonable ranges
     */
    public function validateStats(array $stats): array
    {
        $errors = [];

        if ($stats['minutes'] < 0 || $stats['minutes'] > 48) {
            $errors[] = "Invalid minutes: {$stats['minutes']} for {$stats['player_name']}";
        }

        if ($stats['points'] < 0 || $stats['points'] > 100) {
            $errors[] = "Invalid points: {$stats['points']} for {$stats['player_name']}";
        }

        if ($stats['fga'] < $stats['fgm']) {
            $errors[] = "FG attempted ({$stats['fga']}) less than FG made ({$stats['fgm']}) for {$stats['player_name']}";
        }

        if ($stats['tpa'] < $stats['tpm']) {
            $errors[] = "3PT attempted ({$stats['tpa']}) less than 3PT made ({$stats['tpm']}) for {$stats['player_name']}";
        }

        return $errors;
    }
}
