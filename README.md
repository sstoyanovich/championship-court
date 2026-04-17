# Championship Court - NBA Card Collection Game

-- This is a purely Vibe coded project, to test the limits and capabilities of ai. I made little to no edits to any of the code that was produced

A web application inspired by MLB The Show's Diamond Dynasty mode, designed for NBA card collecting. Built with Laravel (backend) and Vue 3 (frontend).

## Features

### Current Features (v1.0)

- **Pack Opening**: Open packs containing 5 random NBA player cards with weighted rarity
- **Card Collection**: View and manage all collected cards
- **Filtering**: Filter cards by tier, team, and position
- **Statistics**: Track collection stats including total cards and breakdown by tier
- **Card Tiers**: 5 rarity tiers based on player ratings:
  - Emerald (80-84)
  - Sapphire (85-89)
  - Amethyst (90-92)
  - Diamond (93-96)
  - Pink Diamond (97-99)

### Coming Soon

- Currency system (Stubs)
- Pack purchasing with stubs
- Lineup building
- Collections system with rewards
- Marketplace for buying/selling cards

## Technology Stack

- **Backend**: Laravel 11 with SQLite database
- **Frontend**: Vue 3 with Vite
- **Styling**: Custom CSS with gradient designs
- **API Communication**: Axios

## Project Structure

```
championship-court/
├── backend/              # Laravel backend
│   ├── app/
│   │   ├── Console/
│   │   │   └── Commands/
│   │   │       └── ScrapeNbaPlayers.php
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       ├── PackController.php
│   │   │       └── CollectionController.php
│   │   └── Models/
│   │       ├── Player.php
│   │       └── UserCard.php
│   ├── database/
│   │   └── migrations/
│   ├── routes/
│   │   └── api.php
│   └── config/
│       └── cors.php
└── frontend/            # Vue 3 frontend
    ├── src/
    │   ├── components/
    │   │   └── PlayerCard.vue
    │   ├── views/
    │   │   ├── HomeView.vue
    │   │   ├── PackOpeningView.vue
    │   │   └── CollectionView.vue
    │   ├── services/
    │   │   └── api.js
    │   └── router/
    │       └── index.ts
    └── package.json
```

## Setup Instructions

### Backend Setup

1. Navigate to the backend directory:

   ```bash
   cd backend
   ```

2. Install PHP dependencies (if needed):

   ```bash
   composer install
   ```

3. The database migrations should already be run, but if needed:

   ```bash
   php artisan migrate
   ```

4. Seed the database with NBA players:

   ```bash
   php artisan nba:scrape-players
   ```

   Note: This will create sample data with 36 NBA players across all tiers.

5. Start the Laravel development server:
   ```bash
   php artisan serve
   ```
   The backend will be available at http://localhost:8000

### Frontend Setup

1. Navigate to the frontend directory:

   ```bash
   cd frontend
   ```

2. Install dependencies (if needed):

   ```bash
   npm install
   ```

3. Start the Vue development server:
   ```bash
   npm run dev
   ```
   The frontend will be available at http://localhost:5173 or http://localhost:5174 (depending on port availability)

## API Endpoints

### Pack Management

- `POST /api/packs/open` - Open a pack and receive 5 random cards

### Collection Management

- `GET /api/collection` - Get all cards in collection (supports filtering)
  - Query params: `tier`, `team`, `position`
- `GET /api/collection/stats` - Get collection statistics

## Database Schema

### Players Table

- `id`: Primary key
- `name`: Player name
- `overall_rating`: Player rating (80-99)
- `position`: Player position (PG, SG, SF, PF, C)
- `team`: NBA team
- `card_tier`: Card rarity tier
- `image_url`: Player image URL (optional)

### User Cards Table

- `id`: Primary key
- `player_id`: Foreign key to players table
- `obtained_at`: Timestamp when card was obtained

## Pack Opening Mechanics

Each pack contains 5 cards with the following probability distribution:

- Emerald: 50%
- Sapphire: 30%
- Amethyst: 12%
- Diamond: 6%
- Pink Diamond: 2%

## Card Tier Breakdown

The database includes **60 NBA players** based on NBA 2K25 ratings:

- **3 Pink Diamonds** (97-99 rating): Jokic (99), Doncic (98), Giannis (97)
- **8 Diamonds** (93-96 rating): Curry, LeBron, Durant, Embiid, Tatum, SGA, AD, Vucevic
- **8 Amethysts** (90-92 rating): Dame, Kawhi, Butler, Booker, Mitchell, Edwards, Jrue, Gobert
- **14 Sapphires** (85-89 rating): Haliburton, Paul George, Kyrie, Ja, Bam, DeRozan, Jaylen, LaMelo, Zion, Fox, Randle, Paolo, Porzingis, Markkanen
- **27 Emeralds** (80-84 rating): Including Trae, Garland, KAT, Ingram, Siakam, Brunson, Maxey, Cade, Mobley, and many more

## Future Enhancements

1. **Currency System**: Add stubs as in-game currency
2. **Pack Store**: Purchase different pack types with stubs
3. **Lineup Builder**: Create starting lineups with your cards
4. **Collections**: Complete team/tier collections for rewards
5. **Card Trading**: Trade cards with other users (multiplayer)
6. **Auction House**: Buy and sell cards on marketplace
7. **Challenges**: Complete challenges for rewards
8. **Real Player Images**: Integrate actual player photos

## Development Notes

- The application uses SQLite for easy setup and portability
- CORS is configured to allow requests from Vue dev server (localhost:5173)
- The scraper command attempts to fetch from 2kratings.com but falls back to sample data
- Single-player experience (no authentication required in current version)

## Contributing

Feel free to contribute to this project by:

1. Adding new features
2. Improving UI/UX
3. Fixing bugs
4. Adding real player data integration
5. Implementing the roadmap features

## License

This project is for educational and entertainment purposes.
