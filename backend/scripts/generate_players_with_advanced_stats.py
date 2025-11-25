#!/usr/bin/env python3
"""
Enhanced NBA Player Data Generator with Advanced Shot Chart Stats

This script fetches NBA player data and generates 2K-style attributes using:
- Basic stats (PPG, RPG, APG, etc.)
- **ADVANCED SHOT CHART DATA** (shooting % by distance, zone-specific accuracy)
- Position-specific rating formulas calibrated to 2K25 ratings

Usage:
    python3 generate_players_with_advanced_stats.py

Output:
    nba_players_advanced.json - JSON file with all player data
"""

import json
import sys
import time
from typing import Dict, Optional

from nba_api.stats.endpoints import (
    playercareerstats,
    commonplayerinfo,
    shotchartdetail,
)
from nba_api.stats.static import players as nba_players, teams as nba_teams


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


def get_team_name(team_id: Optional[int]) -> str:
    """Get team name from team ID."""
    if not team_id:
        return "Free Agent"
    try:
        all_teams = nba_teams.get_teams()
        team = next((t for t in all_teams if t["id"] == team_id), None)
        return team["full_name"] if team else "Free Agent"
    except:
        return "Unknown"


def get_shot_chart_stats(player_id: int, season: str = "2024-25") -> Optional[Dict]:
    """
    Fetch shot chart data and aggregate by distance/zone.
    Returns dict with shooting percentages by distance range.
    """
    try:
        time.sleep(0.6)  # Rate limiting
        shot_chart = shotchartdetail.ShotChartDetail(
            player_id=player_id,
            team_id=0,
            season_nullable=season,
            context_measure_simple="FGA",
        )

        shots_df = shot_chart.get_data_frames()[0]

        if len(shots_df) == 0:
            return None

        # Calculate shooting percentages by distance
        stats = {}

        # Restricted Area (0-3ft) - for close shot/layup
        restricted = shots_df[shots_df["SHOT_DISTANCE"] <= 3]
        if len(restricted) > 0:
            stats["restricted_area_pct"] = restricted["SHOT_MADE_FLAG"].sum() / len(restricted) * 100
            stats["restricted_area_attempts"] = len(restricted)
        else:
            stats["restricted_area_pct"] = 0
            stats["restricted_area_attempts"] = 0

        # Close range (3-5ft) - for close shot
        close = shots_df[(shots_df["SHOT_DISTANCE"] > 3) & (shots_df["SHOT_DISTANCE"] <= 5)]
        if len(close) > 0:
            stats["close_range_pct"] = close["SHOT_MADE_FLAG"].sum() / len(close) * 100
            stats["close_range_attempts"] = len(close)
        else:
            stats["close_range_pct"] = 0
            stats["close_range_attempts"] = 0

        # Short mid-range (5-10ft) - floater range
        short_mid = shots_df[(shots_df["SHOT_DISTANCE"] > 5) & (shots_df["SHOT_DISTANCE"] <= 10)]
        if len(short_mid) > 0:
            stats["short_mid_pct"] = short_mid["SHOT_MADE_FLAG"].sum() / len(short_mid) * 100
            stats["short_mid_attempts"] = len(short_mid)
        else:
            stats["short_mid_pct"] = 0
            stats["short_mid_attempts"] = 0

        # Mid-range (10-16ft)
        mid_range = shots_df[(shots_df["SHOT_DISTANCE"] > 10) & (shots_df["SHOT_DISTANCE"] <= 16)]
        if len(mid_range) > 0:
            stats["mid_range_pct"] = mid_range["SHOT_MADE_FLAG"].sum() / len(mid_range) * 100
            stats["mid_range_attempts"] = len(mid_range)
        else:
            stats["mid_range_pct"] = 0
            stats["mid_range_attempts"] = 0

        # Long mid-range (16-24ft)
        long_mid = shots_df[(shots_df["SHOT_DISTANCE"] > 16) & (shots_df["SHOT_DISTANCE"] < 24)]
        if len(long_mid) > 0:
            stats["long_mid_pct"] = long_mid["SHOT_MADE_FLAG"].sum() / len(long_mid) * 100
            stats["long_mid_attempts"] = len(long_mid)
        else:
            stats["long_mid_pct"] = 0
            stats["long_mid_attempts"] = 0

        # Three-point (24+ft)
        three_pt = shots_df[shots_df["SHOT_DISTANCE"] >= 24]
        if len(three_pt) > 0:
            stats["three_pt_pct"] = three_pt["SHOT_MADE_FLAG"].sum() / len(three_pt) * 100
            stats["three_pt_attempts"] = len(three_pt)
        else:
            stats["three_pt_pct"] = 0
            stats["three_pt_attempts"] = 0

        # Zone-specific stats
        paint = shots_df[shots_df["SHOT_ZONE_BASIC"].isin(["In The Paint (Non-RA)", "Restricted Area"])]
        if len(paint) > 0:
            stats["paint_pct"] = paint["SHOT_MADE_FLAG"].sum() / len(paint) * 100
        else:
            stats["paint_pct"] = 0

        corner_3 = shots_df[shots_df["SHOT_ZONE_BASIC"].isin(["Left Corner 3", "Right Corner 3"])]
        if len(corner_3) > 0:
            stats["corner_3_pct"] = corner_3["SHOT_MADE_FLAG"].sum() / len(corner_3) * 100
        else:
            stats["corner_3_pct"] = 0

        stats["total_shots"] = len(shots_df)

        return stats

    except Exception as e:
        print(f"      ⚠️  Shot chart unavailable: {str(e)[:50]}")
        return None


def calculate_attributes_with_advanced_stats(season_stats: Dict, shot_stats: Optional[Dict], position: str) -> Dict:
    """
    Calculate 2K-style attributes using both basic stats and advanced shot chart data.
    If shot_stats is None, falls back to basic formulas.
    """
    attrs = {}

    # Extract basic stats
    ppg = season_stats["ppg"]
    rpg = season_stats["rpg"]
    apg = season_stats["apg"]
    spg = season_stats["spg"]
    bpg = season_stats["bpg"]
    tov = season_stats["tov"]
    fg_pct = season_stats["fg_pct"] * 100  # Convert to percentage
    three_pct = season_stats["three_pct"] * 100
    ft_pct = season_stats["ft_pct"] * 100
    mpg = season_stats["mpg"]

    # OUTSIDE SCORING - Enhanced with shot chart data
    if shot_stats and shot_stats["close_range_attempts"] >= 10:
        # Use actual 0-5ft shooting % for close shot
        close_pct = (shot_stats["restricted_area_pct"] + shot_stats["close_range_pct"]) / 2
        attrs["close_shot"] = min(99, max(25, int(30 + close_pct * 0.7 + ppg * 1.5)))
    else:
        # Fallback to basic formula
        attrs["close_shot"] = min(99, max(25, int(40 + (fg_pct - 40) * 1.3 + ppg * 2.2)))

    # Mid-Range Shot - use actual mid-range data if available
    if shot_stats and shot_stats["mid_range_attempts"] + shot_stats["long_mid_attempts"] >= 10:
        mid_pct = shot_stats["mid_range_pct"] * 0.6 + shot_stats["long_mid_pct"] * 0.4
        mid_range_bonus = 15 if position in ["SG", "SF"] else 8
        attrs["mid_range_shot"] = min(99, max(25, int(25 + mid_pct * 0.8 + mid_range_bonus)))
    else:
        # Fallback
        mid_range_bonus = 15 if position in ["SG", "SF"] else 8
        attrs["mid_range_shot"] = min(99, max(25, int(35 + (fg_pct - 40) * 1.3 + ppg * 1.2 + mid_range_bonus)))

    # Three-Point Shot - use actual 3PT data (already have this)
    attrs["three_point_shot"] = min(99, max(25, int(20 + (three_pct - 25) * 3.2)))

    attrs["free_throw"] = min(99, max(25, int(ft_pct * 0.95 + 15)))
    attrs["shot_iq"] = min(99, max(25, int(50 + (fg_pct - 40) * 0.8 + ppg * 0.5 - tov * 2)))
    attrs["offensive_consistency"] = min(99, max(25, int(55 + (fg_pct - 40) * 0.8 + ppg * 0.5)))

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

    # INSIDE SCORING - Enhanced with paint data
    if shot_stats and shot_stats["paint_pct"] > 0:
        paint_pct = shot_stats["paint_pct"]
        attrs["layup"] = min(99, max(25, int(25 + paint_pct * 0.75 + ppg * 2.5)))
    else:
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

    # DEFENSE
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
    attrs["speed"] = min(99, max(25, int(speed_base + ppg * 0.3 - (rpg * 0.5))))
    attrs["agility"] = min(99, max(25, int(speed_base + apg * 1.5)))
    strength_base = 70 if position in ["PF", "C"] else 50
    attrs["strength"] = min(99, max(25, int(strength_base + rpg * 1.5 + bpg * 2)))
    vertical_base = 65 if position in ["PF", "C"] else 55
    attrs["vertical"] = min(99, max(25, int(vertical_base + bpg * 5)))
    attrs["stamina"] = min(99, max(25, int(60 + mpg * 1.0)))
    attrs["hustle"] = min(99, max(25, int(60 + (spg + bpg) * 4)))
    attrs["overall_durability"] = min(99, max(25, int(70 + mpg * 0.5)))

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
    handle_base = 85 if position == "PG" else (75 if position == "SG" else 65)
    attrs["ball_handle"] = min(99, max(25, int(handle_base + apg * 2.5 - tov * 1.5)))
    attrs["speed_with_ball"] = min(99, max(25, int(attrs["speed"] * 0.9 + apg * 0.5 - tov * 0.5)))
    attrs["pass_accuracy"] = min(99, max(25, int(45 + apg * 6 - tov * 2.5)))
    ast_to_ratio = apg / max(tov, 1)
    attrs["pass_iq"] = min(99, max(25, int(50 + ast_to_ratio * 10)))
    attrs["pass_vision"] = min(99, max(25, int(45 + apg * 7)))

    attrs["playmaking"] = int(
        (
            attrs["ball_handle"]
            + attrs["speed_with_ball"]
            + attrs["pass_accuracy"]
            + attrs["pass_iq"]
            + attrs["pass_vision"]
        )
        / 5
    )

    # REBOUNDING
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
    """Calculate overall rating from attributes (same as calibrated version)."""
    # Position-based weighting - calibrated to 2K ratings
    if position in ["PF", "C"]:
        base_overall = (
            attrs["outside_scoring"] * 0.16
            + attrs["inside_scoring"] * 0.30
            + attrs["defense"] * 0.26
            + attrs["athleticism"] * 0.08
            + attrs["playmaking"] * 0.06
            + attrs["rebounding"] * 0.06
        )
    elif position in ["PG", "SG"]:
        base_overall = (
            attrs["outside_scoring"] * 0.35
            + attrs["inside_scoring"] * 0.16
            + attrs["defense"] * 0.16
            + attrs["athleticism"] * 0.08
            + attrs["playmaking"] * 0.16
            + attrs["rebounding"] * 0.02
        )
    else:  # SF
        base_overall = (
            attrs["outside_scoring"] * 0.30
            + attrs["inside_scoring"] * 0.24
            + attrs["defense"] * 0.18
            + attrs["athleticism"] * 0.10
            + attrs["playmaking"] * 0.10
            + attrs["rebounding"] * 0.02
        )

    # [Rest of the bonuses - same as calibrated version]
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

    elite_categories = sum(
        1
        for cat in [attrs["outside_scoring"], attrs["inside_scoring"], attrs["defense"], attrs["playmaking"]]
        if cat >= 80
    )
    if elite_categories >= 1:
        base_overall += 8
    if elite_categories >= 2:
        base_overall += 3

    if position in ["PF", "C"]:
        if ppg >= 25:
            base_overall += 8
        elif ppg >= 20:
            base_overall += 6
        elif ppg >= 16:
            base_overall += 3
    else:
        if ppg >= 30:
            base_overall += 7
        elif ppg >= 27:
            base_overall += 5
        elif ppg >= 24:
            base_overall += 3
        elif ppg >= 20:
            base_overall += 1

    if apg >= 9:
        base_overall += 4
    elif apg >= 7:
        base_overall += 2
    elif apg >= 5:
        base_overall += 1

    if rpg >= 12:
        base_overall += 5
    elif rpg >= 10:
        base_overall += 3
    elif rpg >= 8:
        base_overall += 2

    if spg >= 2.0:
        base_overall += 4
    elif spg >= 1.5:
        base_overall += 3
    elif spg >= 1.0:
        base_overall += 1

    if bpg >= 2.0:
        base_overall += 5
    elif bpg >= 1.5:
        base_overall += 3
    elif bpg >= 1.0:
        base_overall += 2

    three_pct_rating = three_pct * 100
    if three_pct_rating >= 42:
        base_overall += 6
    elif three_pct_rating >= 40:
        base_overall += 4
    elif three_pct_rating >= 38:
        base_overall += 2

    if position in ["PG", "SG"] and ppg >= 27:
        base_overall += 5
    elif position in ["PG", "SG"] and ppg >= 24:
        base_overall += 3
    elif position in ["PG", "SG"] and ppg >= 21:
        base_overall += 1
    elif position == "SF" and ppg >= 28:
        base_overall += 4
    elif position == "SF" and ppg >= 25:
        base_overall += 2

    if three_pct_rating >= 39 and ppg >= 23 and position in ["PG", "SG"]:
        base_overall += 2

    ft_pct_rating = ft_pct * 100
    if ft_pct_rating >= 90:
        base_overall += 2
    elif ft_pct_rating >= 85:
        base_overall += 1

    overall = int(base_overall)
    return min(99, max(40, overall))


def get_player_season_stats(player_id: int, season: str = "2024-25") -> Optional[Dict]:
    """Get player's season stats (same as before)."""
    try:
        time.sleep(0.6)
        career_stats = playercareerstats.PlayerCareerStats(player_id=player_id)
        career_data = career_stats.get_normalized_dict()

        if "SeasonTotalsRegularSeason" not in career_data:
            return None

        seasons = career_data["SeasonTotalsRegularSeason"]
        season_data = next((s for s in seasons if s["SEASON_ID"] == season), None)

        if not season_data:
            return None

        gp = season_data.get("GP", 0)
        if gp == 0:
            return None

        return {
            "gp": gp,
            "ppg": round(season_data.get("PTS", 0) / gp, 1),
            "rpg": round(season_data.get("REB", 0) / gp, 1),
            "apg": round(season_data.get("AST", 0) / gp, 1),
            "spg": round(season_data.get("STL", 0) / gp, 1),
            "bpg": round(season_data.get("BLK", 0) / gp, 1),
            "tov": round(season_data.get("TOV", 0) / gp, 1),
            "mpg": round(season_data.get("MIN", 0) / gp, 1),
            "fg_pct": season_data.get("FG_PCT", 0.400),
            "three_pct": season_data.get("FG3_PCT", 0.300),
            "ft_pct": season_data.get("FT_PCT", 0.750),
        }
    except Exception as e:
        return None


def process_player(player_info: Dict, index: int, total: int) -> Optional[Dict]:
    """Process a single player with advanced stats."""
    player_name = player_info["full_name"]
    player_id = player_info["id"]

    print(f"\n[{index}/{total}] {player_name}...", end=" ")

    try:
        time.sleep(0.6)
        info = commonplayerinfo.CommonPlayerInfo(player_id=player_id)
        info_data = info.get_normalized_dict()

        if "CommonPlayerInfo" not in info_data or not info_data["CommonPlayerInfo"]:
            print("No info found")
            return None

        player_data = info_data["CommonPlayerInfo"][0]
        position_raw = player_data.get("POSITION", "SF") or "SF"
        position = position_raw[:2] if len(position_raw) >= 2 else "SF"
        if position_raw in ["Guard", "Forward", "Center"]:
            position = "SG" if position_raw == "Guard" else ("SF" if position_raw == "Forward" else "C")
        elif position == "Ce":
            position = "C"
        elif position == "Fo":
            position = "SF"
        elif position == "Gu":
            position = "SG"
        team_id = player_data.get("TEAM_ID")
        team = get_team_name(team_id) if team_id else "Free Agent"

        if not team_id:
            print("✗ (Free agent)")
            return None

        season_stats = get_player_season_stats(player_id)

        if season_stats and season_stats["gp"] >= 10:
            print(f"✓ ({season_stats['gp']} GP, {season_stats['ppg']:.1f} PPG)", end="")

            # Fetch shot chart data for enhanced accuracy
            print(" - fetching shot chart...", end="")
            shot_stats = get_shot_chart_stats(player_id)

            if shot_stats:
                print(f" ✓ ({shot_stats['total_shots']} shots)")
            else:
                print(" ⚠️  (using fallback)")

            attributes = calculate_attributes_with_advanced_stats(season_stats, shot_stats, position)
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

            return {
                "name": player_name,
                "overall_rating": overall_rating,
                "position": position,
                "team": team,
                "card_tier": get_card_tier(overall_rating),
                "image_url": None,
                **attributes,
            }
        else:
            print("✗ (insufficient data)")
            return None

    except Exception as e:
        print(f"✗ Error: {str(e)[:50]}")
        return None


def main():
    """Main execution function."""
    print("=" * 80)
    print("NBA Player Data Generator - ENHANCED WITH ADVANCED STATS")
    print("=" * 80)
    print("\n⚠️  NOTE: Fetching shot chart data will add ~5-10 minutes to import time")
    print("         due to API rate limiting, but provides much more accurate ratings!\n")

    all_players = nba_players.get_active_players()
    print(f"Found {len(all_players)} active NBA players")

    processed_players = []
    for i, player in enumerate(all_players, 1):
        result = process_player(player, i, len(all_players))
        if result:
            processed_players.append(result)

    output_file = "nba_players_advanced.json"
    with open(output_file, "w") as f:
        json.dump(processed_players, f, indent=2)

    print("\n" + "=" * 80)
    print(f"✅ Successfully processed {len(processed_players)} players!")
    print(f"📁 Saved to: {output_file}")

    # Show tier breakdown
    tier_counts = {}
    for player in processed_players:
        tier = player["card_tier"]
        tier_counts[tier] = tier_counts.get(tier, 0) + 1

    print("\n📊 Tier Breakdown:")
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
            print(f"   {tier_name:15s}: {tier_counts[tier]:3d} players")

    print("=" * 80)


if __name__ == "__main__":
    main()
