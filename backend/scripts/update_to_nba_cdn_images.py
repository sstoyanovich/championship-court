#!/usr/bin/env python3
"""
Update player image URLs to use NBA CDN instead of 2kratings.com
"""

import sqlite3
import sys
from pathlib import Path
from nba_api.stats.static import players

# Database path
DB_PATH = Path(__file__).parent.parent / "database" / "database.sqlite"


def normalize_name(name):
    """Normalize player name for matching"""
    return name.lower().strip().replace("'", "").replace(".", "").replace("-", " ")


def get_nba_cdn_url(player_id):
    """Generate NBA CDN image URL from player ID"""
    return f"https://cdn.nba.com/headshots/nba/latest/1040x760/{player_id}.png"


def main():
    print("=" * 80)
    print("Updating Player Images to NBA CDN")
    print("=" * 80)
    print()

    # Connect to database
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    # Get all players from database
    cursor.execute("SELECT id, name FROM players")
    db_players = cursor.fetchall()

    print(f"📊 Found {len(db_players)} players in database")
    print()

    # Get all NBA players from API
    print("🔍 Fetching NBA player data from API...")
    nba_players = players.get_players()
    print(f"✓ Found {len(nba_players)} NBA players from API")
    print()

    # Create lookup dictionary
    nba_lookup = {}
    for player in nba_players:
        normalized = normalize_name(player["full_name"])
        nba_lookup[normalized] = player["id"]

    print("🔄 Matching players and updating image URLs...")
    print()

    matched = 0
    not_matched = []

    for db_id, db_name in db_players:
        normalized_db_name = normalize_name(db_name)

        if normalized_db_name in nba_lookup:
            nba_id = nba_lookup[normalized_db_name]
            image_url = get_nba_cdn_url(nba_id)

            # Update database
            cursor.execute("UPDATE players SET image_url = ?, nba_id = ? WHERE id = ?", (image_url, str(nba_id), db_id))

            matched += 1
            if matched % 50 == 0:
                print(f"   Updated {matched}/{len(db_players)}...")
        else:
            not_matched.append(db_name)

    conn.commit()
    conn.close()

    print()
    print("=" * 80)
    print("✓ Image URL update complete!")
    print(f"   Matched: {matched}/{len(db_players)} ({matched/len(db_players)*100:.1f}%)")
    print(f"   Not matched: {len(not_matched)}")
    print("=" * 80)

    if not_matched:
        print()
        print("⚠️  Players not matched with NBA API:")
        for name in sorted(not_matched)[:20]:  # Show first 20
            print(f"   • {name}")
        if len(not_matched) > 20:
            print(f"   ... and {len(not_matched) - 20} more")

    print()
    print("📸 Sample NBA CDN URLs:")
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    cursor.execute("SELECT name, image_url FROM players WHERE image_url IS NOT NULL LIMIT 5")
    samples = cursor.fetchall()
    for name, url in samples:
        print(f"   {name}: {url}")
    conn.close()
    print()


if __name__ == "__main__":
    main()
