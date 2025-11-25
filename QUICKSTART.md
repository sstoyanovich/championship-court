# Quick Start Guide

## Championship Court - NBA Card Collection Game

### Current Status

✅ Both servers are running and functional!

- **Backend API**: http://localhost:8000
- **Frontend App**: http://localhost:5174

### What's Been Built

#### Backend (Laravel + SQLite)

1. **Database Schema**

   - Players table with 36 NBA players seeded
   - User Cards table to track collected cards
   - 5 card tiers: Emerald, Sapphire, Amethyst, Diamond, Pink Diamond

2. **API Endpoints**

   - `POST /api/packs/open` - Opens a pack with 5 random cards
   - `GET /api/collection` - Gets all collected cards (with optional filters)
   - `GET /api/collection/stats` - Gets collection statistics

3. **Pack Opening Logic**

   - Weighted random selection based on rarity
   - Emerald (50%), Sapphire (30%), Amethyst (12%), Diamond (6%), Pink Diamond (2%)

4. **Data Management**
   - Scraper command: `php artisan nba:scrape-players`
   - Currently uses sample data with real NBA players

#### Frontend (Vue 3 + Vite)

1. **Pages**

   - Home: Landing page with navigation
   - Pack Opening: Animated pack opening with card reveals
   - Collection: View and filter your collected cards

2. **Components**

   - PlayerCard: Beautiful gradient-based card design with tier colors
   - Navigation: Clean header with routing

3. **Features**
   - Animated card reveals (one by one)
   - Filter by tier, team, and position
   - Collection statistics display
   - Responsive design

### How to Use

1. **Open the app in your browser**:

   ```
   http://localhost:5174
   ```

2. **Navigate to "Open Packs"**

   - Click the "Open Pack" button
   - Watch as 5 cards are revealed one by one
   - Each card shows: player name, rating, position, team, and tier

3. **View Your Collection**
   - Navigate to "Collection"
   - See all your collected cards
   - Use filters to find specific cards
   - View statistics about your collection

### Testing the API Directly

You can also test the API using curl:

```bash
# Open a pack
curl -X POST http://localhost:8000/api/packs/open

# View collection
curl http://localhost:8000/api/collection

# View stats
curl http://localhost:8000/api/collection/stats
```

### Players Database

The database is seeded with **60 NBA players** based on NBA 2K25 ratings:

**Pink Diamonds (3)**: Jokic (99), Doncic (98), Giannis (97)
**Diamonds (8)**: Curry, LeBron, Durant, Embiid, Tatum, SGA, AD, Vucevic
**Amethysts (8)**: Dame, Kawhi, Butler, Booker, Mitchell, Edwards, Jrue Holiday, Gobert
**Sapphires (14)**: Haliburton, Paul George, Kyrie, Ja, Bam, DeRozan, Jaylen, LaMelo, Zion, Fox, Randle, Paolo, Porzingis, Markkanen
**Emeralds (27)**: Including top young stars like Trae, Garland, KAT, Cade, Tyrese Maxey, Evan Mobley, and many more

### Next Steps

Future features to implement:

- Currency system (Stubs)
- Pack purchasing
- Lineup building (5-man starting lineup)
- Collections system with rewards
- Marketplace/trading
- User authentication (for multiplayer)

### Current Database

The SQLite database is located at:

```
backend/database/database.sqlite
```

To reset and reseed:

```bash
cd backend
php artisan migrate:fresh
php artisan nba:scrape-players
```

### Stopping the Servers

The servers are running in the background. You can stop them at any time or let them continue running.

### Enjoy!

Start collecting your favorite NBA players and build your ultimate card collection! 🏀✨
