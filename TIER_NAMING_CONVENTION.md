# Tier Naming Convention

## Standard Format

**All tier names in the database and code should use lowercase with underscores:**

```
common
bronze
silver
gold
emerald
sapphire
ruby
amethyst
diamond
pink_diamond
galaxy_opal
```

## ❌ Avoid These Formats

- ~~Bronze~~ (Capitalized)
- ~~Pink Diamond~~ (Spaces)
- ~~Ruby~~ (Capitalized)
- ~~Galaxy Opal~~ (Spaces - use `galaxy_opal` instead)

## Where This Matters

### 1. Database (`players` table)

The `card_tier` column must use the lowercase underscore format:

```sql
INSERT INTO players (name, card_tier, ...)
VALUES ('LeBron James', 'pink_diamond', ...);
```

### 2. Admin Panel Player Form

The dropdown options now correctly use lowercase values:

```html
<option value="bronze">Bronze</option>
<option value="pink_diamond">Pink Diamond</option>
```

### 3. Pack Odds Configuration

When creating packs, use the lowercase format:

```json
{
  "common": 35.0,
  "bronze": 30.0,
  "silver": 20.0,
  "gold": 10.0,
  "emerald": 3.5,
  "sapphire": 1.0,
  "ruby": 0.3,
  "amethyst": 0.15,
  "diamond": 0.04,
  "pink_diamond": 0.01,
  "galaxy_opal": 0.001
}
```

### 4. Guaranteed Tier Slots

Pack guaranteed slots use lowercase:

```json
[{ "slot": 5, "min_tier": "emerald" }]
```

## Why This Matters

Case sensitivity caused a bug where:

- Pack odds: `{"emerald": 100}`
- Database: `card_tier = "Emerald"`
- Query: `WHERE card_tier = 'emerald'` → No match! ❌

Using consistent lowercase prevents this issue.

## Display vs Storage

- **Storage (Database/API):** `pink_diamond` (lowercase, underscore)
- **Display (UI):** "Pink Diamond" (formatted for users)

The frontend can format the tier names for display while keeping the backend consistent.

## Tier Hierarchy

From lowest to highest:

1. `common`
2. `bronze`
3. `silver`
4. `gold`
5. `emerald`
6. `sapphire`
7. `ruby`
8. `amethyst`
9. `diamond`
10. `pink_diamond`
11. `galaxy_opal`

This hierarchy is used in:

- `PackController::TIER_HIERARCHY` constant
- Guaranteed tier slot filtering
- UI sorting and filtering

## Migration Notes

If you have inconsistent tier names in your database:

```sql
-- Fix capitalization issues
UPDATE players SET card_tier = LOWER(card_tier);

-- Fix space issues
UPDATE players SET card_tier = REPLACE(card_tier, ' ', '_');

-- Verify consistency
SELECT DISTINCT card_tier FROM players ORDER BY card_tier;
```

Expected output should only show: `amethyst`, `bronze`, `common`, `diamond`, `emerald`, `galaxy_opal`, `gold`, `pink_diamond`, `ruby`, `sapphire`, `silver`
