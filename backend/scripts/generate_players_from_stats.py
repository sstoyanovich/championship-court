#!/usr/bin/env python3
"""
Generate NBA player ratings based on real stats from the NBA API.
This creates realistic attribute ratings based on actual player performance.
"""

import json
import os
import sqlite3
import sys
import time
from typing import Dict, List, Optional
from nba_api.stats.static import players, teams
from nba_api.stats.endpoints import commonplayerinfo, playercareerstats, playergamelog


# Database connection
DB_PATH = "../database/database.sqlite"


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


def player_exists_in_db(conn, nba_id: str) -> bool:
    """Check if player already exists in database."""
    cursor = conn.cursor()
    cursor.execute("SELECT id FROM players WHERE nba_id = ?", (nba_id,))
    return cursor.fetchone() is not None


def get_existing_nba_ids(conn) -> set:
    """Get all NBA IDs that are already in the database."""
    cursor = conn.cursor()
    cursor.execute("SELECT nba_id FROM players")
    return {row[0] for row in cursor.fetchall()}


def save_player_to_db(conn, player_data: Dict) -> bool:
    """Save player data directly to database."""
    try:
        # Get collection ID for the team
        collection_id = get_collection_id_by_team(conn, player_data["team"])

        cursor = conn.cursor()
        cursor.execute(
            """
            INSERT INTO players (
                name, nba_id, overall_rating, position, team, card_tier, image_url, collection_id,
                outside_scoring, inside_scoring, defense, athleticism, playmaking, rebounding,
                close_shot, mid_range_shot, three_point_shot, free_throw, shot_iq, offensive_consistency,
                layup, standing_dunk, driving_dunk, post_hook, post_fade, post_control, draw_foul, hands,
                interior_defense, perimeter_defense, steal, block, help_defense_iq, pass_perception, defensive_consistency,
                speed, agility, strength, vertical, stamina, hustle, overall_durability,
                pass_accuracy, ball_handle, speed_with_ball, pass_iq, pass_vision,
                offensive_rebound, defensive_rebound, intangibles, potential,
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                datetime('now'), datetime('now')
            )
        """,
            (
                player_data["name"],
                player_data["nba_id"],
                player_data["overall_rating"],
                player_data["position"],
                player_data["team"],
                player_data["card_tier"],
                player_data["image_url"],
                collection_id,
                player_data["outside_scoring"],
                player_data["inside_scoring"],
                player_data["defense"],
                player_data["athleticism"],
                player_data["playmaking"],
                player_data["rebounding"],
                player_data["close_shot"],
                player_data["mid_range_shot"],
                player_data["three_point_shot"],
                player_data["free_throw"],
                player_data["shot_iq"],
                player_data["offensive_consistency"],
                player_data["layup"],
                player_data["standing_dunk"],
                player_data["driving_dunk"],
                player_data["post_hook"],
                player_data["post_fade"],
                player_data["post_control"],
                player_data["draw_foul"],
                player_data["hands"],
                player_data["interior_defense"],
                player_data["perimeter_defense"],
                player_data["steal"],
                player_data["block"],
                player_data["help_defense_iq"],
                player_data["pass_perception"],
                player_data["defensive_consistency"],
                player_data["speed"],
                player_data["agility"],
                player_data["strength"],
                player_data["vertical"],
                player_data["stamina"],
                player_data["hustle"],
                player_data["overall_durability"],
                player_data["pass_accuracy"],
                player_data["ball_handle"],
                player_data["speed_with_ball"],
                player_data["pass_iq"],
                player_data["pass_vision"],
                player_data["offensive_rebound"],
                player_data["defensive_rebound"],
                player_data["intangibles"],
                player_data["potential"],
            ),
        )
        conn.commit()
        return True
    except Exception as e:
        print(f"\n  ❌ Database error: {e}")
        conn.rollback()
        return False


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


def get_team_name(team_id: int) -> str:
    """Get team name from team ID."""
    all_teams = teams.get_teams()
    for team in all_teams:
        if team["id"] == team_id:
            return team["full_name"]
    return "Free Agent"


def calculate_attributes_from_real_stats(stats: Dict, position: str) -> Dict:
    """
    Calculate 2K-style attributes from real NBA stats.
    Stats should include: PPG, FG%, 3P%, FT%, RPG, APG, SPG, BPG, MPG, etc.
    """

    # Extract stats (with defaults for missing data)
    ppg = stats.get("ppg", 0)
    fg_pct = stats.get("fg_pct", 0.400) * 100  # Convert to percentage
    three_pct = stats.get("three_pct", 0.300) * 100
    ft_pct = stats.get("ft_pct", 0.700) * 100
    rpg = stats.get("rpg", 0)
    apg = stats.get("apg", 0)
    spg = stats.get("spg", 0)
    bpg = stats.get("bpg", 0)
    tov = stats.get("tov", 0)
    mpg = stats.get("mpg", 0)

    # Initialize attributes
    attrs = {}

    # OUTSIDE SCORING - Based on shooting stats and scoring
    # Close Shot (25-99): Based on FG% and scoring near basket
    attrs["close_shot"] = min(99, max(25, int(40 + (fg_pct - 40) * 1.5 + ppg * 2.2)))

    # Mid-Range Shot (25-99): Based on FG% and position
    mid_range_bonus = 15 if position in ["SG", "SF"] else 8
    attrs["mid_range_shot"] = min(99, max(25, int(35 + (fg_pct - 40) * 1.3 + ppg * 1.2 + mid_range_bonus)))

    # Three-Point Shot: 45%+ = 97+, 40.8% = 89, 35% = 75, 30% = 50
    attrs["three_point_shot"] = min(99, max(25, int(25 + (three_pct - 25) * 4.05)))

    # Free Throw (25-99): Directly from FT%
    attrs["free_throw"] = min(99, max(25, int(ft_pct * 0.95 + 15)))

    # Shot IQ (25-99): Based on FG% and turnovers
    attrs["shot_iq"] = min(99, max(25, int(50 + (fg_pct - 40) * 0.8 + ppg * 0.5 - tov * 2)))

    # Offensive Consistency (25-99): Based on games played and FG%
    attrs["offensive_consistency"] = min(99, max(25, int(55 + (fg_pct - 40) * 0.8 + ppg * 0.5)))

    # INSIDE SCORING - more generous for bigs
    attrs["layup"] = min(99, max(25, int(40 + (fg_pct - 40) * 1.5 + ppg * 2.8)))
    dunk_base = 75 if position in ["PF", "C"] else (50 if position == "SF" else 25)
    attrs["standing_dunk"] = min(99, max(25, int(dunk_base + ppg * 0.7)))
    driving_base = 65 if position in ["PF", "C"] else (55 if position == "SF" else 40)
    attrs["driving_dunk"] = min(99, max(25, int(driving_base + ppg * 1.0)))
    post_base = 70 if position in ["PF", "C"] else 35
    attrs["post_hook"] = min(99, max(25, int(post_base + ppg * 0.6)))
    attrs["post_fade"] = min(99, max(25, int(post_base + ppg * 0.6)))
    attrs["post_control"] = min(99, max(25, int(post_base + ppg * 0.5)))
    attrs["draw_foul"] = min(99, max(25, int(55 + ppg * 1.8)))

    hands_base = 78 if position in ["PF", "C"] else 65
    attrs["hands"] = min(99, max(25, int(hands_base + rpg * 1.7)))

    # DEFENSE - balanced for defensive specialists
    interior_base = 75 if position in ["PF", "C"] else 40
    attrs["interior_defense"] = min(99, max(25, int(interior_base + bpg * 10 + rpg * 0.6)))
    perimeter_base = 75 if position in ["PG", "SG"] else (65 if position == "SF" else 50)
    attrs["perimeter_defense"] = min(99, max(25, int(perimeter_base + spg * 8)))
    attrs["steal"] = min(99, max(25, int(56 + spg * 16)))
    attrs["block"] = min(99, max(25, int(46 + bpg * 18)))

    # Help Defense IQ (25-99): Based on defensive stats
    attrs["help_defense_iq"] = min(99, max(25, int(55 + (spg + bpg) * 4)))

    # Pass Perception (25-99): Based on steals
    attrs["pass_perception"] = min(99, max(25, int(55 + spg * 6)))

    # Defensive Consistency (25-99): Average of defensive stats
    attrs["defensive_consistency"] = min(99, max(25, int(55 + (spg + bpg) * 3)))

    # ATHLETICISM
    # Speed (25-99): Position-based with adjustment for minutes
    speed_base = 75 if position in ["PG", "SG"] else (70 if position == "SF" else 55)
    attrs["speed"] = min(99, max(25, int(speed_base + (mpg - 25) * 0.3)))

    # Agility (25-99): Similar to speed
    attrs["agility"] = min(99, max(25, int(speed_base - 5 + (mpg - 25) * 0.3)))

    # Strength (25-99): Position-based with rebounding bonus
    strength_base = 75 if position in ["PF", "C"] else (60 if position == "SF" else 50)
    attrs["strength"] = min(99, max(25, int(strength_base + rpg * 0.8)))

    # Vertical (25-99): Based on blocks and dunks
    vertical_base = 65 if position in ["PF", "C"] else 70
    attrs["vertical"] = min(99, max(25, int(vertical_base + bpg * 5)))

    # Stamina (25-99): Based on minutes played
    attrs["stamina"] = min(99, max(25, int(60 + mpg * 0.8)))

    # Hustle (25-99): Based on overall activity (steals, rebounds, etc.)
    attrs["hustle"] = min(99, max(25, int(60 + (spg + rpg * 0.5) * 2)))

    # Overall Durability (25-99): Based on games played and minutes
    attrs["overall_durability"] = min(99, max(25, int(70 + mpg * 0.4)))

    # PLAYMAKING
    # Pass Accuracy (25-99): Based on assists and turnovers
    attrs["pass_accuracy"] = min(99, max(25, int(45 + apg * 6 - tov * 2.5)))

    # Ball Handle (25-99): Position-based with assist bonus
    handle_base = 85 if position == "PG" else (75 if position == "SG" else 65)
    attrs["ball_handle"] = min(99, max(25, int(handle_base + apg * 2.5 - tov * 1.5)))

    # Speed with Ball (25-99): Similar to speed but with handling penalty
    attrs["speed_with_ball"] = min(99, max(25, int(attrs["speed"] - 5 + apg * 0.8)))

    # Pass IQ (25-99): Based on assist-to-turnover ratio
    ast_to_ratio = (apg / max(tov, 0.1)) if tov > 0 else apg * 2
    attrs["pass_iq"] = min(99, max(25, int(50 + ast_to_ratio * 10)))

    # Pass Vision (25-99): Based on assists
    attrs["pass_vision"] = min(99, max(25, int(45 + apg * 7)))

    # REBOUNDING - balanced for good rebounders
    orb_base = 68 if position in ["PF", "C"] else 40
    attrs["offensive_rebound"] = min(99, max(25, int(orb_base + rpg * 2.3)))
    drb_base = 72 if position in ["PF", "C"] else 45
    attrs["defensive_rebound"] = min(99, max(25, int(drb_base + rpg * 2.5)))

    # OTHER
    # Intangibles (25-99): Based on overall performance
    attrs["intangibles"] = min(99, max(25, int(60 + ppg * 0.8 + apg * 1.5 + rpg * 0.5)))

    # Potential (A, B, C): Based on age (would need age data)
    attrs["potential"] = "B"  # Default, would adjust based on age if available

    # Calculate category ratings
    attrs["outside_scoring"] = int(
        (
            attrs["close_shot"]
            + attrs["mid_range_shot"]
            + attrs["three_point_shot"]
            + attrs["free_throw"]
            + attrs["shot_iq"]
            + attrs["offensive_consistency"]
        )
        / 6
    )

    attrs["inside_scoring"] = int(
        (
            attrs["layup"]
            + attrs["standing_dunk"]
            + attrs["driving_dunk"]
            + attrs["post_hook"]
            + attrs["post_fade"]
            + attrs["post_control"]
            + attrs["draw_foul"]
            + attrs["hands"]
        )
        / 8
    )

    attrs["defense"] = int(
        (
            attrs["interior_defense"]
            + attrs["perimeter_defense"]
            + attrs["steal"]
            + attrs["block"]
            + attrs["help_defense_iq"]
            + attrs["pass_perception"]
            + attrs["defensive_consistency"]
        )
        / 7
    )

    attrs["athleticism"] = int(
        (
            attrs["speed"]
            + attrs["agility"]
            + attrs["strength"]
            + attrs["vertical"]
            + attrs["stamina"]
            + attrs["hustle"]
            + attrs["overall_durability"]
        )
        / 7
    )

    attrs["playmaking"] = int(
        (
            attrs["pass_accuracy"]
            + attrs["ball_handle"]
            + attrs["speed_with_ball"]
            + attrs["pass_iq"]
            + attrs["pass_vision"]
        )
        / 5
    )

    attrs["rebounding"] = int((attrs["offensive_rebound"] + attrs["defensive_rebound"]) / 2)

    return attrs


def get_player_season_stats(player_id: int) -> Optional[Dict]:
    """Get player's stats from the 2023-24 season."""
    try:
        time.sleep(0.6)  # Rate limiting
        career_stats = playercareerstats.PlayerCareerStats(player_id=player_id)
        career_data = career_stats.get_normalized_dict()

        if "SeasonTotalsRegularSeason" in career_data:
            seasons = career_data["SeasonTotalsRegularSeason"]

            # Find 2023-24 season (most recent complete season)
            season_2024 = None
            for season in seasons:
                if season.get("SEASON_ID") == "2023-24":
                    season_2024 = season
                    break

            # If no 2023-24, get most recent season
            if not season_2024 and seasons:
                season_2024 = seasons[-1]

            if season_2024:
                gp = season_2024.get("GP", 1)
                if gp == 0:
                    gp = 1

                return {
                    "ppg": season_2024.get("PTS", 0) / gp,
                    "fg_pct": season_2024.get("FG_PCT", 0.400),
                    "three_pct": season_2024.get("FG3_PCT", 0.300),
                    "ft_pct": season_2024.get("FT_PCT", 0.700),
                    "rpg": season_2024.get("REB", 0) / gp,
                    "apg": season_2024.get("AST", 0) / gp,
                    "spg": season_2024.get("STL", 0) / gp,
                    "bpg": season_2024.get("BLK", 0) / gp,
                    "tov": season_2024.get("TOV", 0) / gp,
                    "mpg": season_2024.get("MIN", 0) / gp,
                    "gp": gp,
                }

        return None
    except Exception as e:
        print(f"  Error fetching stats: {e}")
        return None


def generate_rookie_attributes(position: str) -> Dict:
    """Generate default attributes for rookies or players without stats."""
    base_rating = 75  # Rookie base

    # Use default stats for rookies
    default_stats = {
        "ppg": 8.0,
        "fg_pct": 0.420,
        "three_pct": 0.320,
        "ft_pct": 0.750,
        "rpg": 3.5,
        "apg": 2.0,
        "spg": 0.8,
        "bpg": 0.4,
        "tov": 1.5,
        "mpg": 18.0,
    }

    return calculate_attributes_from_real_stats(default_stats, position)


def calculate_overall_from_attributes(
    attrs: Dict,
    ppg: float = 0,
    apg: float = 0,
    rpg: float = 0,
    three_pct: float = 0,
    ft_pct: float = 0,
    spg: float = 0,
    bpg: float = 0,
    position: str = "SF",
) -> int:
    """Calculate overall rating from attributes."""
    # Position-based weighting - calibrated to 2K ratings with higher floor
    if position in ["PF", "C"]:
        # Bigs: defense, rebounding, inside scoring matter most
        base_overall = (
            attrs["outside_scoring"] * 0.18
            + attrs["inside_scoring"] * 0.32
            + attrs["defense"] * 0.28
            + attrs["athleticism"] * 0.08
            + attrs["playmaking"] * 0.06
            + attrs["rebounding"] * 0.08
        )
    elif position in ["PG", "SG"]:
        # Guards: outside scoring, playmaking matter most
        base_overall = (
            attrs["outside_scoring"] * 0.38
            + attrs["inside_scoring"] * 0.16
            + attrs["defense"] * 0.20  # Increased for defensive guards like Holiday
            + attrs["athleticism"] * 0.08
            + attrs["playmaking"] * 0.16
            + attrs["rebounding"] * 0.02
        )
    else:  # SF - balanced wings
        base_overall = (
            attrs["outside_scoring"] * 0.32
            + attrs["inside_scoring"] * 0.26
            + attrs["defense"] * 0.20
            + attrs["athleticism"] * 0.10
            + attrs["playmaking"] * 0.10
            + attrs["rebounding"] * 0.02
        )

    # Removed global baseline bonus - made superstars hit 99 cap

    # Add bonus for well-rounded players (high in multiple categories)
    high_categories = sum(
        1
        for cat in [
            attrs["outside_scoring"],
            attrs["inside_scoring"],
            attrs["defense"],
            attrs["playmaking"],
            attrs["athleticism"],
        ]
        if cat >= 70
    )

    if high_categories >= 3:
        base_overall += 4
    if high_categories >= 4:
        base_overall += 6

    # Elite player boost - balanced bonuses
    elite_categories = sum(
        1
        for cat in [attrs["outside_scoring"], attrs["inside_scoring"], attrs["defense"], attrs["playmaking"]]
        if cat >= 80
    )
    if elite_categories >= 1:
        base_overall += 8
    if elite_categories >= 2:
        base_overall += 3  # Additional bonus for 2+ elite categories (well-rounded stars)

    # Superstar stat bonuses - calibrated for proper scaling and position
    if position in ["PF", "C"]:
        # Bigs - lower PPG thresholds
        if ppg >= 25:
            base_overall += 8
        elif ppg >= 20:
            base_overall += 6
        elif ppg >= 16:
            base_overall += 3
    else:
        # Guards/Wings - higher PPG expectations
        if ppg >= 30:
            base_overall += 7
        elif ppg >= 27:
            base_overall += 5
        elif ppg >= 24:
            base_overall += 3
        elif ppg >= 20:
            base_overall += 1

    # Elite playmaker bonus
    if apg >= 9:
        base_overall += 4
    elif apg >= 7:
        base_overall += 2
    elif apg >= 5:
        base_overall += 1

    # Elite rebounder bonus
    if rpg >= 12:
        base_overall += 5
    elif rpg >= 10:
        base_overall += 3
    elif rpg >= 8:
        base_overall += 2

    # Elite defender bonuses - more generous for defensive specialists
    if spg >= 2.0:
        base_overall += 6
    elif spg >= 1.5:
        base_overall += 5
    elif spg >= 1.0:
        base_overall += 3
    elif spg >= 0.8:  # Good defender
        base_overall += 1

    if bpg >= 2.0:
        base_overall += 7
    elif bpg >= 1.5:
        base_overall += 5
    elif bpg >= 1.0:
        base_overall += 3
    elif bpg >= 0.5:  # Decent shot blocker
        base_overall += 1

    # Elite 3PT shooter bonus - premium skill (more generous)
    three_pct_rating = three_pct * 100  # Convert to percentage
    if three_pct_rating >= 42:
        base_overall += 8
    elif three_pct_rating >= 40:
        base_overall += 6
    elif three_pct_rating >= 38:
        base_overall += 4
    elif three_pct_rating >= 36:  # Solid shooter (like Hardaway)
        base_overall += 3
    elif three_pct_rating >= 34:  # Decent shooter
        base_overall += 1

    # High-volume scorer bonus for guards/wings
    if position in ["PG", "SG"] and ppg >= 27:
        base_overall += 5
    elif position in ["PG", "SG"] and ppg >= 24:
        base_overall += 3
    elif position in ["PG", "SG"] and ppg >= 21:
        base_overall += 1
    # SF gets reduced bonuses
    elif position == "SF" and ppg >= 28:
        base_overall += 4
    elif position == "SF" and ppg >= 25:
        base_overall += 2

    # Role player scoring bonus - credit for consistent contribution
    if position in ["PG", "SG", "SF"] and 10 <= ppg < 15:
        base_overall += 5  # Solid rotation scorer
    elif position in ["PF", "C"] and 8 <= ppg < 12:
        base_overall += 4  # Contributing big

    # Elite efficiency + high volume combo (Curry-tier players)
    if three_pct_rating >= 39 and ppg >= 23 and position in ["PG", "SG"]:
        base_overall += 2

    # Elite FT shooter bonus - pure shooters get a boost
    ft_pct_rating = ft_pct * 100  # Convert to percentage
    if ft_pct_rating >= 90:
        base_overall += 2
    elif ft_pct_rating >= 85:
        base_overall += 1

    overall = int(base_overall)
    return min(99, max(40, overall))


def process_player(player_info: Dict, index: int, total: int) -> Optional[Dict]:
    """Process a single player to generate their full data."""
    player_name = player_info["full_name"]
    player_id = player_info["id"]

    print(f"Processing {index}/{total}: {player_name}... ", end="", flush=True)

    try:
        # Get player info
        time.sleep(0.6)
        info = commonplayerinfo.CommonPlayerInfo(player_id=player_id)
        info_data = info.get_normalized_dict()

        if "CommonPlayerInfo" not in info_data or not info_data["CommonPlayerInfo"]:
            print("No info found")
            return None

        player_data = info_data["CommonPlayerInfo"][0]
        position_raw = player_data.get("POSITION", "SF") or "SF"
        position = position_raw[:2] if len(position_raw) >= 2 else "SF"
        # Handle common position formats and abbreviations
        if position_raw in ["Guard", "Forward", "Center"]:
            position = "SG" if position_raw == "Guard" else ("SF" if position_raw == "Forward" else "C")
        elif position == "Ce":  # "Center" shortened
            position = "C"
        elif position == "Fo":  # "Forward" shortened
            position = "SF"
        elif position == "Gu":  # "Guard" shortened
            position = "SG"
        team_id = player_data.get("TEAM_ID")
        team = get_team_name(team_id) if team_id else "Free Agent"

        # Include free agents (they'll be assigned to Free Agent collection)
        if not team_id:
            print("Free Agent", end=" - ")

        # Get season stats
        season_stats = get_player_season_stats(player_id)

        if season_stats and season_stats["gp"] >= 10:
            # Use real stats
            attributes = calculate_attributes_from_real_stats(season_stats, position)
            overall_rating = calculate_overall_from_attributes(
                attributes,
                season_stats["ppg"],
                season_stats["apg"],
                season_stats["rpg"],
                season_stats["three_pct"],
                season_stats["ft_pct"],
                season_stats["spg"],
                season_stats["bpg"],
                position,
            )
            print(f"✓ ({season_stats['gp']} games, {season_stats['ppg']:.1f} PPG)")
        else:
            # Use rookie/default attributes
            attributes = generate_rookie_attributes(position)
            overall_rating = calculate_overall_from_attributes(attributes)
            print("✓ (Rookie/limited stats)")

        return {
            "name": player_name,
            "nba_id": str(player_id),
            "overall_rating": overall_rating,
            "position": position,
            "team": team,
            "card_tier": get_card_tier(overall_rating),
            "image_url": f"https://cdn.nba.com/headshots/nba/latest/1040x760/{player_id}.png",
            **attributes,
        }

    except Exception as e:
        print(f"Error: {e}")
        return None


def main():
    """Main function to fetch and generate player data."""
    print("=" * 80)
    print("NBA Players Data Generator - Saves to Database")
    print("=" * 80)
    print()

    # Connect to database
    print("📁 Connecting to database...")
    try:
        conn = get_db_connection()
        print("✓ Database connected")
    except Exception as e:
        print(f"❌ Could not connect to database: {e}")
        print(f"   Make sure the database exists at: {DB_PATH}")
        return

    # Get already imported players from database
    print("🔍 Checking existing players in database...")
    existing_nba_ids = get_existing_nba_ids(conn)
    print(f"✓ Found {len(existing_nba_ids)} players already in database")
    print()

    # Fetch all active players from NBA API
    print("📥 Fetching all active NBA players from API...")
    all_players = players.get_players()
    active_players = [p for p in all_players if p["is_active"]]

    # Filter out already imported players
    remaining_players = [p for p in active_players if str(p["id"]) not in existing_nba_ids]

    print(f"✓ Found {len(active_players)} active players")
    print(f"  Already in database: {len(existing_nba_ids)}")
    print(f"  Remaining to import: {len(remaining_players)}")
    print()

    if not remaining_players:
        print("🎉 All players are already imported!")
        conn.close()
        return

    # Process remaining players
    successful = 0
    failed = 0
    backup_data = []

    print("🚀 Starting import...")
    print("   (Press Ctrl+C to stop - progress is saved after each player)")
    print()

    try:
        for i, player in enumerate(remaining_players, 1):
            player_data = process_player(player, len(existing_nba_ids) + i, len(active_players))

            if player_data:
                # Save to database immediately
                if save_player_to_db(conn, player_data):
                    successful += 1
                    backup_data.append(player_data)
                else:
                    failed += 1
            else:
                failed += 1

            # Progress checkpoint every 10 players
            if i % 10 == 0:
                print(f"  💾 Checkpoint: {successful} imported, {failed} failed")
                # Also save backup JSON
                backup_file = "nba_players_backup.json"
                with open(backup_file, "w") as f:
                    json.dump(backup_data, f, indent=2)

    except KeyboardInterrupt:
        print("\n\n⚠️  Import interrupted by user!")
        print(f"✓ {successful} players saved to database before interruption")
        print(f"✓ Run the script again to continue from where you left off")
    except Exception as e:
        print(f"\n\n⚠️  Import stopped due to error: {e}")
        print(f"✓ {successful} players saved to database before error")
        print(f"✓ Run the script again to continue")

    print()
    print("=" * 80)
    print(f"✓ Total players in database: {len(existing_nba_ids) + successful}")
    print(f"✓ This session: {successful} imported, {failed} failed")

    # Update collection totals
    print()
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

    # Show tier breakdown from database
    if successful > 0:
        print()
        print("📊 Tier Breakdown (this session):")
        tier_counts = {}
        for player in backup_data:
            tier = player["card_tier"]
            tier_counts[tier] = tier_counts.get(tier, 0) + 1

        tier_order = [
            "pink_diamond",
            "diamond",
            "amethyst",
            "sapphire",
            "emerald",
            "gold",
            "silver",
            "bronze",
            "common",
        ]
        for tier in tier_order:
            if tier in tier_counts:
                tier_name = tier.replace("_", " ").title()
                print(f"  {tier_name}: {tier_counts[tier]} players")

    conn.close()
    print("=" * 80)


if __name__ == "__main__":
    main()
