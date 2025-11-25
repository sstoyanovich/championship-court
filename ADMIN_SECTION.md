# Admin Section Documentation

## Overview

The Championship Court admin section provides a Laravel Nova-style interface for managing players, programs, and packs. The admin panel is accessible only to users with the `is_admin` flag set to `true`.

## Features

### Admin Dashboard

- Overview page showing counts of players, programs, and packs
- Quick action buttons to create new resources
- Clean, modern interface with Nova-inspired design

### Player Management

- View all players in a paginated table with ID column
- Search players by name, team, or position
- Filter players by collection (dropdown)
- **Bulk Edit Operations**: Select multiple players and quickly update:
  - Mark as obtainable/not obtainable from packs
  - Mark as tradeable/not tradeable
- Create new players with all 70+ attributes organized in sections:
  - Basic Information (name, team, position, rating, tier, etc.)
  - Player image URL (for headshot)
  - **Custom Card Art** - Select from available card art images stored in `/public/images/card_art`
  - Collection assignment via dropdown (shows team name for Live Series/team collections, collection name for others)
  - Category Ratings (outside scoring, inside scoring, defense, etc.)
  - Detailed Attributes for each category
- All rating attributes default to 80 for new players
- Edit existing players
- Delete players with confirmation

### Program Management

- View all programs in a paginated table with reward/challenge counts
- Search programs by name or type
- Create new programs with:
  - Basic info (name, description, type)
  - XP/Star requirements
  - Active status
  - Image URL
  - **Rewards** - Add multiple rewards with:
    - XP threshold or Stars threshold for unlocking
    - Reward type (Stubs, Player, or Pack)
    - Player/Pack selection via dropdown (no manual ID entry needed)
    - Reward amount for stubs
    - Description
  - **Challenges** - Add multiple challenges with:
    - Challenge type (Stat, Game Count, or PXP)
    - Target stat and value
    - Constraints (position, team, or tier)
    - Stars and XP rewards
    - Description
- Edit existing programs including their rewards and challenges
- Delete programs with confirmation (cascades to rewards and challenges)

### Pack Management

- View all packs in a paginated table
- Search packs by name or type
- Create new packs with:
  - Basic info (name, description, type, cost)
  - Card count and choice count
  - Odds configuration (JSON editor)
  - Active status
- Edit existing packs
- Delete packs with confirmation

### Collection Management

- View all collections in a paginated table
- Search collections by name, type, or sub-collection
- Create new collections with:
  - Name and type (team, season, special, legends, rewards)
  - Sub-collection (e.g., specific team name)
  - Total items count
  - Description
  - Active status
- Edit existing collections
- Delete collections with confirmation

## Getting Started

### 1. Making a User an Admin

To promote a user to admin, use the artisan command:

```bash
cd backend
php artisan user:make-admin user@example.com
```

Replace `user@example.com` with the email address of the user you want to promote.

### 2. Accessing the Admin Panel

Once you've been granted admin access:

1. Log in to the application
2. You'll see an "⚙️ Admin" link in the main navigation bar
3. Click the Admin link to access the admin dashboard

### 3. Navigating the Admin Panel

The admin panel has a sidebar navigation with the following sections:

- **Dashboard** - Overview and quick actions
- **Players** - Manage all players
- **Programs** - Manage all programs
- **Packs** - Manage all packs
- **Collections** - Manage all collections
- **Back to App** - Return to the main application

## API Endpoints

All admin endpoints are prefixed with `/api/admin` and require authentication + admin privileges.

### Players

- `GET /api/admin/players` - List all players (with pagination, search, and collection filter)
  - Query params: `page`, `search`, `per_page`, `collection_id`
- `GET /api/admin/players/{id}` - Get single player
- `POST /api/admin/players` - Create player
- `PUT /api/admin/players/{id}` - Update player
- `DELETE /api/admin/players/{id}` - Delete player
- `POST /api/admin/players/bulk-update` - Bulk update multiple players
  - Body: `{ player_ids: [1,2,3], updates: { obtainable_from_packs: true, is_tradeable: false } }`
  - Allowed fields: `obtainable_from_packs`, `is_tradeable`, `collection_id`, `card_tier`
- `GET /api/admin/card-art-images` - Get list of available card art images from `/public/images/card_art`

### Programs

- `GET /api/admin/programs` - List all programs (with pagination & search, includes rewards and challenges)
- `GET /api/admin/programs/{id}` - Get single program (includes rewards and challenges)
- `POST /api/admin/programs` - Create program (with nested rewards and challenges arrays)
- `PUT /api/admin/programs/{id}` - Update program (replaces rewards and challenges)
- `DELETE /api/admin/programs/{id}` - Delete program (cascades to rewards and challenges)

### Packs

- `GET /api/admin/packs` - List all packs (with pagination & search)
- `GET /api/admin/packs/{id}` - Get single pack
- `POST /api/admin/packs` - Create pack
- `PUT /api/admin/packs/{id}` - Update pack
- `DELETE /api/admin/packs/{id}` - Delete pack

### Collections

- `GET /api/admin/collections` - List all collections (with pagination & search)
- `GET /api/admin/collections/{id}` - Get single collection (includes rewards)
- `POST /api/admin/collections` - Create collection
- `PUT /api/admin/collections/{id}` - Update collection
- `DELETE /api/admin/collections/{id}` - Delete collection

## Security

- All admin routes are protected by the `AdminMiddleware`
- Non-admin users attempting to access admin routes will receive a 403 Forbidden response
- The frontend router prevents non-admin users from accessing admin pages
- Admin link only appears in navigation for users with `is_admin = true`

## Architecture

### Backend

- **Middleware**: `AdminMiddleware` checks `is_admin` flag on authenticated users
- **Controllers**: Four admin controllers in `app/Http/Controllers/Admin/`
  - `AdminPlayerController`
  - `AdminProgramController`
  - `AdminPackController`
  - `AdminCollectionController`
- **Routes**: Admin routes defined in `routes/api.php` with `admin` middleware

### Frontend

- **Store**: `admin.ts` - Centralized state management for all admin operations
- **Layout**: `AdminLayout.vue` - Nova-style layout with sidebar navigation
- **Views**: Organized in `views/admin/` directory
  - `AdminDashboard.vue` - Overview page
  - `players/PlayersIndex.vue` & `players/PlayerForm.vue`
  - `programs/ProgramsIndex.vue` & `programs/ProgramForm.vue`
  - `packs/PacksIndex.vue` & `packs/PackForm.vue`
  - `collections/CollectionsIndex.vue` & `collections/CollectionForm.vue`
- **Routes**: Admin routes nested under `/admin` with `requiresAdmin` meta flag

## Development Notes

### Adding New Admin Features

To add a new resource to the admin panel:

1. **Backend**: Create controller in `app/Http/Controllers/Admin/`
2. **Backend**: Add routes to admin group in `routes/api.php`
3. **Frontend**: Add methods to `stores/admin.ts`
4. **Frontend**: Create index and form views in `views/admin/`
5. **Frontend**: Add routes to router with `requiresAdmin` meta
6. **Frontend**: Add navigation link to `AdminLayout.vue`

### Form Validation

Currently, forms trust the admin to provide valid data. For production use, consider adding:

- Backend validation rules in controllers
- Frontend validation feedback
- Required field indicators
- Data type validation

## Troubleshooting

### "Unauthorized. Admin access required" error

- Ensure your user has `is_admin = true` in the database
- Try logging out and logging back in to refresh your session

### Can't see Admin link in navigation

- Verify your user has `is_admin = true`
- Check browser console for any errors
- Ensure you're logged in

### TypeScript errors in IDE

- These are common after adding new files
- Try restarting your TypeScript language server
- The errors should resolve when the TS server picks up the new files

## Program Rewards & Challenges

### Reward Types

Rewards can be unlocked at specific thresholds based on the program type:

- For **XP programs**: Use `xp_threshold` (e.g., 500 XP)
- For **Star programs**: Use `stars_threshold` (e.g., 10 stars)

**Stubs Reward**

- `reward_type: "stubs"`
- `reward_amount`: Number of stubs to award (required for stubs)
- `reward_id`: Not used for stubs rewards
- Example: Award 1000 stubs at 500 XP or 10 stars

**Player Reward**

- `reward_type: "player"`
- `reward_id`: ID of the player to award (selected from dropdown)
- `reward_amount`: Not used for player rewards (leave blank)
- Dropdown shows: Player name, team, and tier
- Example: Award LeBron James card at 1000 XP or 20 stars

**Pack Reward**

- `reward_type: "pack"`
- `reward_id`: ID of the pack to award (selected from dropdown)
- `reward_amount`: Not used for pack rewards (leave blank)
- Dropdown shows: Pack name and cost
- Example: Award a Premium Pack at 1500 XP or 30 stars

### Challenge Types

**Stat Challenge**

- `type: "stat"`
- `target_stat`: points, rebounds, assists, steals, blocks, threes
- `target_value`: Required stat amount
- Example: Score 100 points total

**Game Count Challenge**

- `type: "game_count"`
- `target_value`: Number of games to play
- Example: Play 10 games

**PXP Challenge**

- `type: "pxp"`
- `target_value`: Player XP required
- Example: Earn 500 PXP

### Challenge Constraints

Challenges can have optional constraints:

- `constraint_type: "position"` - Must use players at specific position (e.g., "PG")
- `constraint_type: "team"` - Must use players from specific team (e.g., "Lakers")
- `constraint_type: "tier"` - Must use players of specific tier (e.g., "Gold")

## Bulk Edit Operations

The player management table includes powerful bulk edit capabilities to quickly update multiple players at once.

### How to Use Bulk Edit

1. **Select Players**:

   - Click the checkbox in the header row to select all players on the current page
   - Or click individual checkboxes next to each player you want to update
   - Selected rows will be highlighted in purple

2. **Bulk Actions Bar**:

   - When players are selected, a purple action bar appears at the top
   - Shows the number of selected players
   - Provides buttons for different bulk operations

3. **Available Bulk Operations**:

   - **✓ Mark Obtainable from Packs** - Sets `obtainable_from_packs` to `true`
   - **✗ Mark Not Obtainable from Packs** - Sets `obtainable_from_packs` to `false` (for program-exclusive rewards)
   - **✓ Mark Tradeable** - Sets `is_tradeable` to `true`
   - **✗ Mark Not Tradeable** - Sets `is_tradeable` to `false` (for untradeable rewards)

4. **Confirmation**:

   - A confirmation dialog appears before applying bulk updates
   - Shows exactly how many players will be affected
   - Changes are applied to all selected players at once

5. **Tips**:
   - Use the collection filter to narrow down players first (e.g., select a specific program collection)
   - Selections are cleared when changing pages or filters
   - You can select players across multiple operations without reloading

### Use Cases

- **Program Rewards**: Mark all program reward cards as not obtainable from packs and not tradeable
- **Collection Management**: Quickly update all cards in a collection
- **Market Control**: Batch update tradeable status for seasonal cards

## Custom Card Art

The admin panel supports assigning custom card art to players. This allows you to override the default card appearance with custom artwork.

### How to Use Custom Card Art

1. **Add Card Art Files**:

   - Place your card art images in `/backend/public/images/card_art/`
   - Supported formats: JPG, PNG, GIF, WebP
   - You can organize images in subdirectories (e.g., `/card_art/starters/`, `/card_art/legends/`)

2. **Assign to Players**:

   - When creating or editing a player, look for the "Custom Card Art" dropdown
   - The dropdown will show all available images from the card_art directory
   - Select an image or choose "No Custom Art (Use Default)" to use the standard card appearance

3. **Card Art Path**:
   - The `card_art` field stores the relative path from the `/images/card_art/` directory
   - Example: `starters/carmelo_starter.png`
   - The full URL is automatically constructed when displaying cards

### Example Card Art Files

Currently available card art:

- `/images/card_art/starters/carmelo_starter.png`
- `/images/card_art/starters/shai_starter.png`

## Future Enhancements

Potential improvements for the admin section:

- Bulk operations (delete multiple items)
- Export data to CSV
- Import data from CSV
- Direct image upload for card art (instead of manual file placement)
- Advanced filtering and sorting
- Activity logs for admin actions
- Role-based permissions (super admin, content admin, etc.)
- Visual odds configurator for packs
- Reward/challenge templates for quick program creation
- Search/filter functionality in dropdowns for large datasets (players, packs, collections)
- Inline editing in tables (edit without navigating to form)
- Image preview when selecting card art
