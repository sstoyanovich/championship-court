#!/usr/bin/env python3
"""
Test hybrid approach with shot chart data for specific players.
"""
import time
from typing import Dict, Optional
from nba_api.stats.endpoints import shotchartdetail, commonplayerinfo, playercareerstats
from nba_api.stats.static import players as nba_players

# Import the enhanced functions
from generate_players_with_advanced_stats import (
    get_shot_chart_stats,
    calculate_attributes_with_advanced_stats,
    calculate_overall_from_attributes,
)

def get_player_season_stats(player_id: int, season: str = "2024-25") -> Optional[Dict]:
    """Get player's season stats."""
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

def test_player_with_hybrid(player_name: str, season: str = "2024-25", target_rating: int = 0):
    """Test a player with hybrid approach (basic stats + shot chart)."""
    print(f"\n{'=' * 80}")
    print(f"Testing: {player_name} (Target: {target_rating if target_rating else '?'})")
    print('=' * 80)
    
    # Find player
    player_list = nba_players.find_players_by_full_name(player_name)
    if not player_list:
        print(f"❌ Player not found: {player_name}")
        return
    
    player = player_list[0]
    player_id = player['id']
    
    # Get player info for position
    try:
        time.sleep(0.6)
        info = commonplayerinfo.CommonPlayerInfo(player_id=player_id)
        info_data = info.get_normalized_dict()
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
    except:
        position = "SF"
    
    print(f"Position: {position}")
    
    # Get season stats
    season_stats = get_player_season_stats(player_id, season)
    if not season_stats:
        print("❌ No season stats found")
        return
    
    print(f"Season Stats: {season_stats['gp']} GP, {season_stats['ppg']:.1f} PPG, {season_stats['rpg']:.1f} RPG, {season_stats['apg']:.1f} APG")
    
    # Get shot chart data
    print("Fetching shot chart data...", end=" ")
    shot_stats = get_shot_chart_stats(player_id, season)
    
    if shot_stats:
        print(f"✓ ({shot_stats['total_shots']} shots)")
        print(f"  Restricted Area: {shot_stats['restricted_area_pct']:.1f}% ({shot_stats['restricted_area_attempts']} att)")
        print(f"  Close Range: {shot_stats['close_range_pct']:.1f}% ({shot_stats['close_range_attempts']} att)")
        print(f"  Mid-Range: {shot_stats['mid_range_pct']:.1f}% ({shot_stats['mid_range_attempts']} att)")
        print(f"  Paint: {shot_stats['paint_pct']:.1f}%")
    else:
        print("⚠️  No shot chart data")
    
    # Calculate with hybrid approach
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
    
    print(f"\n{'=' * 80}")
    print("HYBRID RESULTS")
    print('=' * 80)
    print(f"Overall Rating: {overall_rating}")
    if target_rating:
        diff = overall_rating - target_rating
        print(f"Target Rating:  {target_rating}")
        print(f"Difference:     {diff:+d}")
    
    print(f"\nCategory Ratings:")
    print(f"  Outside Scoring: {attributes['outside_scoring']}")
    print(f"  Inside Scoring:  {attributes['inside_scoring']}")
    print(f"  Defense:         {attributes['defense']}")
    
    print(f"\nKey Shooting Attributes:")
    print(f"  Three-Point:  {attributes['three_point_shot']}")
    print(f"  Close Shot:   {attributes['close_shot']}")
    print(f"  Mid-Range:    {attributes['mid_range_shot']}")
    print(f"  Layup:        {attributes['layup']}")
    print('=' * 80)

if __name__ == "__main__":
    # Test with the 4 new reference players
    # Based on 2K25 ratings from provided links
    test_players = [
        ("Kel'el Ware", "2024-25", 75),  # Assuming from 2K link
        ("Tim Hardaway Jr.", "2024-25", 75),  # Assuming from 2K link
        ("Paolo Banchero", "2024-25", 88),  # Assuming from 2K link
        ("Jrue Holiday", "2024-25", 85),  # Assuming from 2K link
    ]
    
    for player_name, season, target in test_players:
        test_player_with_hybrid(player_name, season, target)
        print("\n")
