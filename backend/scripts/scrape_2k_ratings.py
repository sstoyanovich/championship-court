#!/usr/bin/env python3
"""
Scrape NBA player ratings from 2kratings.com
Saves directly to the SQLite database.
"""

import os
import sqlite3
import time
import requests
from bs4 import BeautifulSoup
from typing import Dict, List, Optional

# Database connection
DB_PATH = "../database/database.sqlite"

# All 30 NBA teams with their 2kratings URLs
NBA_TEAMS = {
    "Atlanta Hawks": "https://www.2kratings.com/teams/atlanta-hawks",
    "Boston Celtics": "https://www.2kratings.com/teams/boston-celtics",
    "Brooklyn Nets": "https://www.2kratings.com/teams/brooklyn-nets",
    "Charlotte Hornets": "https://www.2kratings.com/teams/charlotte-hornets",
    "Chicago Bulls": "https://www.2kratings.com/teams/chicago-bulls",
    "Cleveland Cavaliers": "https://www.2kratings.com/teams/cleveland-cavaliers",
    "Dallas Mavericks": "https://www.2kratings.com/teams/dallas-mavericks",
    "Denver Nuggets": "https://www.2kratings.com/teams/denver-nuggets",
    "Detroit Pistons": "https://www.2kratings.com/teams/detroit-pistons",
    "Golden State Warriors": "https://www.2kratings.com/teams/golden-state-warriors",
    "Houston Rockets": "https://www.2kratings.com/teams/houston-rockets",
    "Indiana Pacers": "https://www.2kratings.com/teams/indiana-pacers",
    "Los Angeles Clippers": "https://www.2kratings.com/teams/los-angeles-clippers",
    "Los Angeles Lakers": "https://www.2kratings.com/teams/los-angeles-lakers",
    "Memphis Grizzlies": "https://www.2kratings.com/teams/memphis-grizzlies",
    "Miami Heat": "https://www.2kratings.com/teams/miami-heat",
    "Milwaukee Bucks": "https://www.2kratings.com/teams/milwaukee-bucks",
    "Minnesota Timberwolves": "https://www.2kratings.com/teams/minnesota-timberwolves",
    "New Orleans Pelicans": "https://www.2kratings.com/teams/new-orleans-pelicans",
    "New York Knicks": "https://www.2kratings.com/teams/new-york-knicks",
    "Oklahoma City Thunder": "https://www.2kratings.com/teams/oklahoma-city-thunder",
    "Orlando Magic": "https://www.2kratings.com/teams/orlando-magic",
    "Philadelphia 76ers": "https://www.2kratings.com/teams/philadelphia-76ers",
    "Phoenix Suns": "https://www.2kratings.com/teams/phoenix-suns",
    "Portland Trail Blazers": "https://www.2kratings.com/teams/portland-trail-blazers",
    "Sacramento Kings": "https://www.2kratings.com/teams/sacramento-kings",
    "San Antonio Spurs": "https://www.2kratings.com/teams/san-antonio-spurs",
    "Toronto Raptors": "https://www.2kratings.com/teams/toronto-raptors",
    "Utah Jazz": "https://www.2kratings.com/teams/utah-jazz",
    "Washington Wizards": "https://www.2kratings.com/teams/washington-wizards",
}


def get_db_connection():
    """Get SQLite database connection."""
    db_path = os.path.join(os.path.dirname(__file__), DB_PATH)
    return sqlite3.connect(db_path)


def get_collection_id_by_team(conn, team_name: str) -> Optional[int]:
    """Get collection ID for a team name."""
    cursor = conn.cursor()
    cursor.execute("SELECT id FROM collections WHERE sub_collection = ? AND type = 'team'", (team_name,))
    result = cursor.fetchone()
    return result[0] if result else None


def player_exists_in_db(conn, name: str, team: str) -> bool:
    """Check if player already exists in database."""
    cursor = conn.cursor()
    cursor.execute("SELECT id FROM players WHERE name = ? AND team = ?", (name, team))
    return cursor.fetchone() is not None


def get_card_tier(overall_rating: int) -> str:
    """Determine card tier based on overall rating."""
    if overall_rating >= 99:
        return "galaxy_opal"
    elif overall_rating >= 96:
        return "pink_diamond"
    elif overall_rating >= 93:
        return "diamond"
    elif overall_rating >= 90:
        return "amethyst"
    elif overall_rating >= 88:
        return "ruby"
    elif overall_rating >= 85:
        return "sapphire"
    elif overall_rating >= 80:
        return "emerald"
    elif overall_rating >= 75:
        return "gold"
    elif overall_rating >= 72:
        return "silver"
    elif overall_rating >= 69:
        return "bronze"
    else:
        return "common"


def scrape_team_players(team_name: str, team_url: str) -> List[Dict]:
    """Scrape players from a team page on 2kratings.com"""
    headers = {
        "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
        "Accept-Language": "en-US,en;q=0.5",
        "Accept-Encoding": "gzip, deflate, br",
        "DNT": "1",
        "Connection": "keep-alive",
        "Upgrade-Insecure-Requests": "1",
        "Sec-Fetch-Dest": "document",
        "Sec-Fetch-Mode": "navigate",
        "Sec-Fetch-Site": "none",
        "Cache-Control": "max-age=0",
    }

    try:
        print(f"  🔍 Fetching {team_name}...", end=" ", flush=True)
        response = requests.get(team_url, headers=headers, timeout=10)
        response.raise_for_status()

        soup = BeautifulSoup(response.content, "html.parser")
        players = []

        # Find all player rows in the roster table
        # The structure is typically: table with class containing player rows
        player_rows = soup.select(".roster-table tbody tr, .table tbody tr, table tbody tr")

        if not player_rows:
            # Try alternative selectors
            player_rows = soup.find_all("tr", class_=lambda x: x and "player" in x.lower())

        for row in player_rows:
            try:
                # Try to find player name
                name_elem = row.select_one('td.player-name a, td a[href*="/"], .player-name, td:first-child a')
                if not name_elem:
                    continue

                name = name_elem.get_text(strip=True)
                if not name or len(name) < 2:
                    continue

                # Try to find overall rating
                rating_elem = row.select_one(
                    'td.overall, td.rating, .overall, td:nth-child(2), td[data-label*="Overall"]'
                )
                if not rating_elem:
                    # Try to find any td with a number between 60-99
                    for td in row.find_all("td"):
                        text = td.get_text(strip=True)
                        if text.isdigit() and 60 <= int(text) <= 99:
                            rating_elem = td
                            break

                if not rating_elem:
                    continue

                rating_text = rating_elem.get_text(strip=True)
                rating = int(rating_text)

                # Try to find position
                position_elem = row.select_one("td.position, .position, td:nth-child(3)")
                position = position_elem.get_text(strip=True) if position_elem else "G"

                # Clean position (sometimes has extra text)
                position = position.split()[0] if position else "G"
                if len(position) > 2:
                    position = position[:2]

                # Try to find image URL
                img_elem = row.select_one("img")
                image_url = img_elem.get("src", "") if img_elem else None

                players.append(
                    {
                        "name": name,
                        "overall_rating": rating,
                        "position": position,
                        "team": team_name,
                        "card_tier": get_card_tier(rating),
                        "image_url": image_url,
                    }
                )

            except (ValueError, AttributeError) as e:
                continue

        print(f"✓ Found {len(players)} players")
        return players

    except Exception as e:
        print(f"❌ Error: {e}")
        return []


def save_player_to_db(conn, player_data: Dict) -> bool:
    """Save player data to database (simple version without detailed attributes)."""
    try:
        # Check if player already exists
        if player_exists_in_db(conn, player_data["name"], player_data["team"]):
            return False

        # Get collection ID for the team
        collection_id = get_collection_id_by_team(conn, player_data["team"])

        cursor = conn.cursor()
        cursor.execute(
            """
            INSERT INTO players (
                name, overall_rating, position, team, card_tier, image_url, collection_id,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            """,
            (
                player_data["name"],
                player_data["overall_rating"],
                player_data["position"],
                player_data["team"],
                player_data["card_tier"],
                player_data["image_url"],
                collection_id,
            ),
        )
        conn.commit()
        return True
    except Exception as e:
        print(f"\n  ❌ Database error for {player_data['name']}: {e}")
        conn.rollback()
        return False


def main():
    """Main function to scrape and import all teams."""
    print("=" * 80)
    print("2K Ratings Scraper - Import NBA Player Ratings")
    print("=" * 80)
    print()

    # Connect to database
    print("📁 Connecting to database...")
    try:
        conn = get_db_connection()
        print("✓ Database connected")
    except Exception as e:
        print(f"❌ Could not connect to database: {e}")
        return

    print()
    print(f"🏀 Scraping {len(NBA_TEAMS)} NBA teams from 2kratings.com...")
    print("   (Using delays between requests to avoid rate limiting)")
    print()

    total_imported = 0
    total_skipped = 0
    total_teams = len(NBA_TEAMS)

    for i, (team_name, team_url) in enumerate(NBA_TEAMS.items(), 1):
        print(f"[{i}/{total_teams}] {team_name}")

        # Scrape players from team page
        players = scrape_team_players(team_name, team_url)

        if players:
            # Save each player to database
            for player in players:
                if save_player_to_db(conn, player):
                    total_imported += 1
                else:
                    total_skipped += 1

            print(f"      ✓ Imported {len(players)} players")
        else:
            print(f"      ⚠️  No players found")

        # Delay between requests to be respectful
        if i < total_teams:
            time.sleep(2)  # 2 second delay between teams

        print()

    # Update collection totals
    print("📊 Updating collection totals...")
    try:
        cursor = conn.cursor()
        cursor.execute(
            """
            UPDATE collections 
            SET total_items = (
                SELECT COUNT(*) FROM players 
                WHERE players.collection_id = collections.id
            )
            """
        )
        conn.commit()
        print("✓ Collection totals updated")
    except Exception as e:
        print(f"⚠️  Could not update collection totals: {e}")

    print()
    print("=" * 80)
    print(f"✓ Import complete!")
    print(f"  Total players imported: {total_imported}")
    print(f"  Duplicates skipped: {total_skipped}")

    # Show tier breakdown
    cursor = conn.cursor()
    cursor.execute(
        """
        SELECT card_tier, COUNT(*) as count 
        FROM players 
        GROUP BY card_tier 
        ORDER BY 
            CASE card_tier
                WHEN 'pink_diamond' THEN 1
                WHEN 'diamond' THEN 2
                WHEN 'amethyst' THEN 3
                WHEN 'sapphire' THEN 4
                WHEN 'emerald' THEN 5
                WHEN 'gold' THEN 6
                WHEN 'silver' THEN 7
                WHEN 'bronze' THEN 8
                WHEN 'common' THEN 9
            END
    """
    )

    print()
    print("📊 Tier Breakdown:")
    for tier, count in cursor.fetchall():
        tier_name = tier.replace("_", " ").title()
        print(f"  {tier_name}: {count} players")

    conn.close()
    print("=" * 80)


if __name__ == "__main__":
    main()
