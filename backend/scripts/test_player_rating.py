#!/usr/bin/env python3
"""
Test player rating generation for a specific player and season.
Usage: python3 test_player_rating.py "Player Name" "YYYY-YY"
Example: python3 test_player_rating.py "LeBron James" "2023-24"
"""

import sys
import json
import time
from typing import Dict, List, Optional
from nba_api.stats.static import players, teams
from nba_api.stats.endpoints import commonplayerinfo, playercareerstats


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
    attrs["close_shot"] = min(99, max(25, int(40 + (fg_pct - 40) * 1.5 + ppg * 2.2)))
    mid_range_bonus = 15 if position in ["SG", "SF"] else 8
    attrs["mid_range_shot"] = min(99, max(25, int(35 + (fg_pct - 40) * 1.3 + ppg * 1.2 + mid_range_bonus)))
    # Three-Point Shot: 45%+ = 97+, 40.8% = 89, 35% = 75, 30% = 50
    attrs["three_point_shot"] = min(99, max(25, int(25 + (three_pct - 25) * 4.05)))
    attrs["free_throw"] = min(99, max(25, int(ft_pct * 0.95 + 15)))
    attrs["shot_iq"] = min(99, max(25, int(50 + (fg_pct - 40) * 0.8 + ppg * 0.5 - tov * 2)))
    attrs["offensive_consistency"] = min(99, max(25, int(55 + (fg_pct - 40) * 0.8 + ppg * 0.5)))

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

    # DEFENSE - balanced for defensive specialists
    interior_base = 75 if position in ["PF", "C"] else 40
    attrs["interior_defense"] = min(99, max(25, int(interior_base + bpg * 10 + rpg * 0.6)))
    perimeter_base = 75 if position in ["PG", "SG"] else (65 if position == "SF" else 50)
    attrs["perimeter_defense"] = min(99, max(25, int(perimeter_base + spg * 8)))
    attrs["steal"] = min(99, max(25, int(56 + spg * 16)))
    attrs["block"] = min(99, max(25, int(46 + bpg * 18)))
    attrs["help_defense_iq"] = min(99, max(25, int(55 + (spg + bpg) * 4)))
    attrs["pass_perception"] = min(99, max(25, int(55 + spg * 6)))
    attrs["defensive_consistency"] = min(99, max(25, int(55 + (spg + bpg) * 3)))

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

    # ATHLETICISM
    speed_base = 75 if position in ["PG", "SG"] else (70 if position == "SF" else 55)
    attrs["speed"] = min(99, max(25, int(speed_base + (mpg - 25) * 0.3)))
    attrs["agility"] = min(99, max(25, int(speed_base - 5 + (mpg - 25) * 0.3)))
    strength_base = 75 if position in ["PF", "C"] else (60 if position == "SF" else 50)
    attrs["strength"] = min(99, max(25, int(strength_base + rpg * 0.8)))
    vertical_base = 65 if position in ["PF", "C"] else 70
    attrs["vertical"] = min(99, max(25, int(vertical_base + bpg * 5)))
    attrs["stamina"] = min(99, max(25, int(60 + mpg * 0.8)))
    attrs["hustle"] = min(99, max(25, int(60 + (spg + rpg * 0.5) * 2)))
    attrs["overall_durability"] = min(99, max(25, int(70 + mpg * 0.4)))

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

    # PLAYMAKING
    attrs["pass_accuracy"] = min(99, max(25, int(45 + apg * 6 - tov * 2.5)))
    handle_base = 85 if position == "PG" else (75 if position == "SG" else 65)
    attrs["ball_handle"] = min(99, max(25, int(handle_base + apg * 2.5 - tov * 1.5)))
    attrs["speed_with_ball"] = min(99, max(25, int(attrs["speed"] - 5 + apg * 0.8)))
    ast_to_ratio = (apg / max(tov, 0.1)) if tov > 0 else apg * 2
    attrs["pass_iq"] = min(99, max(25, int(50 + ast_to_ratio * 10)))
    attrs["pass_vision"] = min(99, max(25, int(45 + apg * 7)))

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

    # REBOUNDING - balanced for good rebounders
    orb_base = 68 if position in ["PF", "C"] else 40
    attrs["offensive_rebound"] = min(99, max(25, int(orb_base + rpg * 2.3)))
    drb_base = 72 if position in ["PF", "C"] else 45
    attrs["defensive_rebound"] = min(99, max(25, int(drb_base + rpg * 2.5)))

    attrs["rebounding"] = int((attrs["offensive_rebound"] + attrs["defensive_rebound"]) / 2)

    # OTHER
    attrs["intangibles"] = min(99, max(25, int(60 + ppg * 0.8 + apg * 1.5 + rpg * 0.5)))
    attrs["potential"] = "B"

    return attrs


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


def get_player_season_stats(player_id: int, season: str) -> Optional[Dict]:
    """Get player's stats from a specific season."""
    try:
        time.sleep(0.6)  # Rate limiting
        career_stats = playercareerstats.PlayerCareerStats(player_id=player_id)
        career_data = career_stats.get_normalized_dict()

        if "SeasonTotalsRegularSeason" in career_data:
            seasons = career_data["SeasonTotalsRegularSeason"]

            # Find the requested season
            season_data = None
            for s in seasons:
                if s.get("SEASON_ID") == season:
                    season_data = s
                    break

            if not season_data:
                print(f"  Season {season} not found. Available seasons:")
                for s in seasons:
                    print(f"    - {s.get('SEASON_ID')}")
                return None

            if season_data:
                gp = season_data.get("GP", 1)
                if gp == 0:
                    gp = 1

                return {
                    "ppg": season_data.get("PTS", 0) / gp,
                    "fg_pct": season_data.get("FG_PCT", 0.400),
                    "three_pct": season_data.get("FG3_PCT", 0.300),
                    "ft_pct": season_data.get("FT_PCT", 0.700),
                    "rpg": season_data.get("REB", 0) / gp,
                    "apg": season_data.get("AST", 0) / gp,
                    "spg": season_data.get("STL", 0) / gp,
                    "bpg": season_data.get("BLK", 0) / gp,
                    "tov": season_data.get("TOV", 0) / gp,
                    "mpg": season_data.get("MIN", 0) / gp,
                    "gp": gp,
                }

        return None
    except Exception as e:
        print(f"  Error fetching stats: {e}")
        return None


def find_player(player_name: str):
    """Find a player by name."""
    all_players = players.get_players()

    # Try exact match first
    for p in all_players:
        if p["full_name"].lower() == player_name.lower():
            return p

    # Try partial match
    matches = [p for p in all_players if player_name.lower() in p["full_name"].lower()]

    if not matches:
        return None

    if len(matches) == 1:
        return matches[0]

    # Multiple matches - show options
    print(f"\nMultiple players found matching '{player_name}':")
    for i, p in enumerate(matches[:10], 1):
        print(f"  {i}. {p['full_name']}")

    if len(matches) > 10:
        print(f"  ... and {len(matches) - 10} more")

    return None


def main():
    if len(sys.argv) < 3:
        print('Usage: python3 test_player_rating.py "Player Name" "YYYY-YY"')
        print('Example: python3 test_player_rating.py "LeBron James" "2023-24"')
        sys.exit(1)

    player_name = sys.argv[1]
    season = sys.argv[2]

    print("=" * 80)
    print(f"Testing Player Rating Generation")
    print("=" * 80)
    print(f"Player: {player_name}")
    print(f"Season: {season}")
    print()

    # Find the player
    print("Searching for player...")
    player_info = find_player(player_name)

    if not player_info:
        print(f"❌ Player '{player_name}' not found!")
        print("\nTry searching with a more specific or different name.")
        sys.exit(1)

    print(f"✓ Found: {player_info['full_name']}")
    print()

    # Get player details
    print("Fetching player information...")
    time.sleep(0.6)
    info = commonplayerinfo.CommonPlayerInfo(player_id=player_info["id"])
    info_data = info.get_normalized_dict()

    if "CommonPlayerInfo" not in info_data or not info_data["CommonPlayerInfo"]:
        print("❌ Could not fetch player information")
        sys.exit(1)

    player_data = info_data["CommonPlayerInfo"][0]
    position_raw = player_data.get("POSITION", "SF") or "SF"
    position = position_raw[:2] if len(position_raw) >= 2 else "SF"  # Get first 2 chars
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

    print(f"  Position: {position}")
    print(f"  Team: {team}")
    print()

    # Get season stats
    print(f"Fetching {season} season stats...")
    season_stats = get_player_season_stats(player_info["id"], season)

    if not season_stats:
        print(f"❌ Could not fetch stats for {season}")
        sys.exit(1)

    print(f"  Games Played: {season_stats['gp']}")
    print(f"  PPG: {season_stats['ppg']:.1f}")
    print(f"  RPG: {season_stats['rpg']:.1f}")
    print(f"  APG: {season_stats['apg']:.1f}")
    print(f"  FG%: {season_stats['fg_pct']*100:.1f}%")
    print(f"  3P%: {season_stats['three_pct']*100:.1f}%")
    print(f"  FT%: {season_stats['ft_pct']*100:.1f}%")
    print(f"  SPG: {season_stats['spg']:.1f}")
    print(f"  BPG: {season_stats['bpg']:.1f}")
    print(f"  TOV: {season_stats['tov']:.1f}")
    print(f"  MPG: {season_stats['mpg']:.1f}")
    print()

    # Calculate attributes
    print("Calculating attributes...")
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
    card_tier = get_card_tier(overall_rating)

    print()
    print("=" * 80)
    print("CARD RATING RESULTS")
    print("=" * 80)
    print(f"Overall Rating: {overall_rating}")
    print(f"Card Tier: {card_tier.replace('_', ' ').upper()}")
    print()
    print("Category Ratings:")
    print(f"  Outside Scoring: {attributes['outside_scoring']}")
    print(f"  Inside Scoring:  {attributes['inside_scoring']}")
    print(f"  Defense:         {attributes['defense']}")
    print(f"  Athleticism:     {attributes['athleticism']}")
    print(f"  Playmaking:      {attributes['playmaking']}")
    print(f"  Rebounding:      {attributes['rebounding']}")
    print()
    print("Key Attributes:")
    print(f"  Three-Point Shot: {attributes['three_point_shot']}")
    print(f"  Close Shot:       {attributes['close_shot']}")
    print(f"  Mid-Range Shot:   {attributes['mid_range_shot']}")
    print(f"  Free Throw:       {attributes['free_throw']}")
    print(f"  Ball Handle:      {attributes['ball_handle']}")
    print(f"  Pass Accuracy:    {attributes['pass_accuracy']}")
    print(f"  Perimeter Defense: {attributes['perimeter_defense']}")
    print(f"  Interior Defense:  {attributes['interior_defense']}")
    print(f"  Speed:            {attributes['speed']}")
    print(f"  Strength:         {attributes['strength']}")
    print("=" * 80)


if __name__ == "__main__":
    main()
