#!/usr/bin/env python3
"""
Fetch NBA players using nba_api and generate detailed attribute ratings.
This script creates a comprehensive database of 400+ current NBA players with 2K-style attributes.
"""

import json
import sys
from typing import Dict, List
from nba_api.stats.static import players, teams
from nba_api.stats.endpoints import commonplayerinfo, playercareerstats
import time


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


def calculate_attributes_from_stats(stats: Dict, position: str) -> Dict:
    """
    Generate 2K-style attributes based on player stats and position.
    This creates realistic attribute distributions similar to NBA 2K games.
    """
    # Default base attributes
    attributes = {
        # Outside Scoring
        "close_shot": 70,
        "mid_range_shot": 65,
        "three_point_shot": 60,
        "free_throw": 70,
        "shot_iq": 65,
        "offensive_consistency": 70,
        # Inside Scoring
        "layup": 70,
        "standing_dunk": 40,
        "driving_dunk": 40,
        "post_hook": 50,
        "post_fade": 50,
        "post_control": 50,
        "draw_foul": 60,
        "hands": 70,
        # Defense
        "interior_defense": 50,
        "perimeter_defense": 60,
        "steal": 55,
        "block": 40,
        "help_defense_iq": 60,
        "pass_perception": 60,
        "defensive_consistency": 65,
        # Athleticism
        "speed": 70,
        "agility": 70,
        "strength": 60,
        "vertical": 65,
        "stamina": 80,
        "hustle": 75,
        "overall_durability": 75,
        # Playmaking
        "pass_accuracy": 65,
        "ball_handle": 65,
        "speed_with_ball": 70,
        "pass_iq": 65,
        "pass_vision": 65,
        # Rebounding
        "offensive_rebound": 50,
        "defensive_rebound": 55,
        # Other
        "intangibles": 70,
        "potential": "C",
    }

    # Adjust based on position
    if position in ["PG", "SG"]:
        # Guards - better perimeter skills
        attributes["three_point_shot"] += 10
        attributes["ball_handle"] += 10
        attributes["pass_accuracy"] += 5
        attributes["speed"] += 5
        attributes["agility"] += 5
        attributes["perimeter_defense"] += 5
        # Worse interior
        attributes["standing_dunk"] = max(25, attributes["standing_dunk"] - 20)
        attributes["interior_defense"] -= 10
        attributes["defensive_rebound"] -= 10

    elif position in ["SF"]:
        # Forwards - balanced
        attributes["mid_range_shot"] += 5
        attributes["driving_dunk"] += 10

    elif position in ["PF", "C"]:
        # Bigs - better interior
        attributes["close_shot"] += 10
        attributes["standing_dunk"] += 20
        attributes["post_hook"] += 15
        attributes["post_fade"] += 10
        attributes["post_control"] += 15
        attributes["interior_defense"] += 15
        attributes["defensive_rebound"] += 15
        attributes["offensive_rebound"] += 15
        attributes["strength"] += 10
        # Worse perimeter
        attributes["three_point_shot"] = max(25, attributes["three_point_shot"] - 15)
        attributes["ball_handle"] -= 15
        attributes["speed"] -= 10

    # Cap all attributes at 99
    for key in attributes:
        if isinstance(attributes[key], int):
            attributes[key] = min(99, max(25, attributes[key]))

    return attributes


def calculate_overall_rating(attributes: Dict) -> int:
    """Calculate overall rating from individual attributes."""
    # Weight different attribute categories
    weights = {
        "close_shot": 0.8,
        "mid_range_shot": 0.8,
        "three_point_shot": 0.9,
        "free_throw": 0.5,
        "shot_iq": 0.6,
        "offensive_consistency": 0.5,
        "layup": 0.7,
        "standing_dunk": 0.4,
        "driving_dunk": 0.5,
        "post_hook": 0.3,
        "post_fade": 0.3,
        "post_control": 0.3,
        "draw_foul": 0.4,
        "hands": 0.3,
        "interior_defense": 0.6,
        "perimeter_defense": 0.7,
        "steal": 0.5,
        "block": 0.4,
        "help_defense_iq": 0.5,
        "pass_perception": 0.4,
        "defensive_consistency": 0.4,
        "speed": 0.6,
        "agility": 0.5,
        "strength": 0.4,
        "vertical": 0.3,
        "stamina": 0.3,
        "hustle": 0.3,
        "overall_durability": 0.2,
        "pass_accuracy": 0.6,
        "ball_handle": 0.7,
        "speed_with_ball": 0.5,
        "pass_iq": 0.6,
        "pass_vision": 0.5,
        "offensive_rebound": 0.4,
        "defensive_rebound": 0.5,
        "intangibles": 0.4,
    }

    total = 0
    weight_sum = 0

    for attr, value in attributes.items():
        if attr in weights and isinstance(value, int):
            total += value * weights[attr]
            weight_sum += weights[attr]

    overall = int(total / weight_sum) if weight_sum > 0 else 75
    return min(99, max(40, overall))


def fetch_all_active_players() -> List[Dict]:
    """Fetch all active NBA players."""
    print("Fetching all NBA players...")
    all_players = players.get_players()
    active_players = [p for p in all_players if p["is_active"]]
    print(f"Found {len(active_players)} active players")
    return active_players


def get_team_name(team_id: int) -> str:
    """Get team name from team ID."""
    all_teams = teams.get_teams()
    for team in all_teams:
        if team["id"] == team_id:
            return team["full_name"]
    return "Free Agent"


def generate_player_data(player_info: Dict, index: int, total: int) -> Dict:
    """Generate complete player data with attributes."""
    player_name = player_info["full_name"]
    print(f"Processing {index}/{total}: {player_name}")

    # Default position and team
    position = "SF"
    team = "Free Agent"

    try:
        # Try to get player info from API
        time.sleep(0.6)  # Rate limiting
        info = commonplayerinfo.CommonPlayerInfo(player_id=player_info["id"])
        info_data = info.get_normalized_dict()

        if "CommonPlayerInfo" in info_data and info_data["CommonPlayerInfo"]:
            player_data = info_data["CommonPlayerInfo"][0]
            position = player_data.get("POSITION", "SF") or "SF"
            team_id = player_data.get("TEAM_ID")
            if team_id:
                team = get_team_name(team_id)
    except Exception as e:
        print(f"  Warning: Could not fetch detailed info for {player_name}: {e}")

    # Generate attributes based on position
    attributes = calculate_attributes_from_stats({}, position)
    overall_rating = calculate_overall_rating(attributes)

    # Add some randomization for variety (±5 points)
    import random

    for key in attributes:
        if isinstance(attributes[key], int):
            variation = random.randint(-5, 5)
            attributes[key] = min(99, max(25, attributes[key] + variation))

    # Recalculate overall with variations
    overall_rating = calculate_overall_rating(attributes)

    # Calculate category ratings
    outside_scoring = int(
        (
            attributes["close_shot"]
            + attributes["mid_range_shot"]
            + attributes["three_point_shot"]
            + attributes["free_throw"]
            + attributes["shot_iq"]
            + attributes["offensive_consistency"]
        )
        / 6
    )

    inside_scoring = int(
        (
            attributes["layup"]
            + attributes["standing_dunk"]
            + attributes["driving_dunk"]
            + attributes["post_hook"]
            + attributes["post_fade"]
            + attributes["post_control"]
            + attributes["draw_foul"]
            + attributes["hands"]
        )
        / 8
    )

    defense = int(
        (
            attributes["interior_defense"]
            + attributes["perimeter_defense"]
            + attributes["steal"]
            + attributes["block"]
            + attributes["help_defense_iq"]
            + attributes["pass_perception"]
            + attributes["defensive_consistency"]
        )
        / 7
    )

    athleticism = int(
        (
            attributes["speed"]
            + attributes["agility"]
            + attributes["strength"]
            + attributes["vertical"]
            + attributes["stamina"]
            + attributes["hustle"]
            + attributes["overall_durability"]
        )
        / 7
    )

    playmaking = int(
        (
            attributes["pass_accuracy"]
            + attributes["ball_handle"]
            + attributes["speed_with_ball"]
            + attributes["pass_iq"]
            + attributes["pass_vision"]
        )
        / 5
    )

    rebounding = int((attributes["offensive_rebound"] + attributes["defensive_rebound"]) / 2)

    return {
        "name": player_name,
        "overall_rating": overall_rating,
        "position": position,
        "team": team,
        "card_tier": get_card_tier(overall_rating),
        "image_url": None,
        # Category ratings
        "outside_scoring": outside_scoring,
        "inside_scoring": inside_scoring,
        "defense": defense,
        "athleticism": athleticism,
        "playmaking": playmaking,
        "rebounding": rebounding,
        # Individual attributes (all the details)
        **attributes,
    }


def main():
    """Main function to fetch and export player data."""
    print("=" * 60)
    print("NBA Players Data Fetcher")
    print("=" * 60)

    # Fetch all active players
    active_players = fetch_all_active_players()

    # Generate data for all players
    players_data = []
    total = len(active_players)

    for i, player in enumerate(active_players, 1):
        try:
            player_data = generate_player_data(player, i, total)
            players_data.append(player_data)
        except Exception as e:
            print(f"  Error processing {player['full_name']}: {e}")
            continue

    # Save to JSON file
    output_file = "nba_players_data.json"
    with open(output_file, "w") as f:
        json.dump(players_data, f, indent=2)

    print("\n" + "=" * 60)
    print(f"✓ Successfully processed {len(players_data)} players")
    print(f"✓ Data saved to {output_file}")

    # Show tier breakdown
    tier_counts = {}
    for player in players_data:
        tier = player["card_tier"]
        tier_counts[tier] = tier_counts.get(tier, 0) + 1

    print("\nTier Breakdown:")
    for tier in ["pink_diamond", "diamond", "amethyst", "sapphire", "emerald"]:
        if tier in tier_counts:
            print(f"  {tier}: {tier_counts[tier]} players")
    print("=" * 60)


if __name__ == "__main__":
    main()
