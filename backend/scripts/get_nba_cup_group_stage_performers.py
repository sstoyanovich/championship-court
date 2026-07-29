#!/usr/bin/env python3
"""
Get top performing NBA players for NBA Cup group stage games only.
Excludes knockout stage games.

Usage:
    python3 get_nba_cup_group_stage_performers.py <season> [--limit LIMIT] [--sort SORT_BY] [--html-file PATH]
    
Example:
    python3 get_nba_cup_group_stage_performers.py 2025-26 --limit=20 --sort=composite --html-file=test.txt
"""

import json
import sys
import time
import re
from datetime import datetime
from typing import Dict, List, Optional
from nba_api.stats.endpoints import boxscoretraditionalv3
from requests.exceptions import Timeout, RequestException
import pandas as pd

def extract_game_ids_from_html(html_content: str) -> List[str]:
    """Extract NBA Cup group stage game IDs from HTML schedule."""
    # Pattern 1: /game/team-vs-team-GAMEID
    pattern1 = r'/game/[^/\"]+-(\d{10})'
    ids1 = re.findall(pattern1, html_content)
    
    # Pattern 2: data-content-id="GAMEID" 
    pattern2 = r'data-content-id=\"(\d{10})\"'
    ids2 = re.findall(pattern2, html_content)
    
    # Combine and deduplicate
    all_ids = sorted(list(set(ids1 + ids2)))
    
    # Filter group stage (exclude knockout)
    # Group stage: 00225000xx where xx < 80 (approximately)
    # Knockout: 00225012xx (quarterfinals), 0022501229-0022501230 (semifinals), 0062500001 (championship)
    group_stage = []
    knockout = []
    
    for gid in all_ids:
        if gid.startswith('00225000') and int(gid) < 200000000:
            group_stage.append(gid)
        elif gid.startswith('00225012') or gid.startswith('00625000'):
            knockout.append(gid)
    
    return sorted(group_stage), sorted(knockout)

def get_player_stats_for_games(game_ids: List[str], max_retries: int = 3) -> Optional[Dict[str, Dict]]:
    """
    Get player stats for specific game IDs.
    Returns a dictionary mapping player_id to aggregated stats.
    """
    if not game_ids:
        print("No game IDs provided", file=sys.stderr)
        return None
    
    print(f"Fetching stats for {len(game_ids)} NBA Cup group stage games...", file=sys.stderr)
    
    # Dictionary to aggregate stats by player
    player_stats = {}
    
    successful_games = 0
    failed_games = 0
    
    for idx, game_id in enumerate(game_ids):
        for attempt in range(max_retries):
            try:
                if idx > 0 and idx % 10 == 0:
                    print(f"  Processed {idx}/{len(game_ids)} games...", file=sys.stderr)
                
                timeout = 30 if attempt == 0 else 45
                
                boxscore = boxscoretraditionalv3.BoxScoreTraditionalV3(
                    game_id=game_id,
                    timeout=timeout,
                    get_request=False
                )
                
                boxscore.get_request()
                data_frames = boxscore.get_data_frames()
                
                if not data_frames or len(data_frames) == 0:
                    if attempt < max_retries - 1:
                        wait_time = (2 ** attempt) * 2
                        time.sleep(wait_time)
                        continue
                    else:
                        print(f"  Warning: No data for game {game_id}", file=sys.stderr)
                        failed_games += 1
                        break
                
                # Find the dataframe with player stats
                # BoxScoreTraditionalV3 returns multiple dataframes - first one has player stats
                player_stats_df = None
                for df in data_frames:
                    if not df.empty and ('personId' in df.columns or 'PLAYER_ID' in df.columns):
                        player_stats_df = df
                        break
                
                if player_stats_df is None or player_stats_df.empty:
                    if attempt < max_retries - 1:
                        wait_time = (2 ** attempt) * 2
                        time.sleep(wait_time)
                        continue
                    else:
                        print(f"  Warning: No player stats found for game {game_id}", file=sys.stderr)
                        failed_games += 1
                        break
                
                # BoxScoreTraditionalV3 uses camelCase column names
                # Parse minutes from "MM:SS" format to decimal minutes
                def parse_minutes(min_str):
                    """Convert 'MM:SS' format to decimal minutes."""
                    if pd.isna(min_str) or min_str == '':
                        return 0.0
                    try:
                        if isinstance(min_str, str) and ':' in min_str:
                            parts = min_str.split(':')
                            return float(parts[0]) + float(parts[1]) / 60.0
                        return float(min_str)
                    except:
                        return 0.0
                
                # Aggregate stats for each player
                for _, row in player_stats_df.iterrows():
                    player_id_val = row.get('personId')
                    if pd.isna(player_id_val) or player_id_val == 0:
                        continue
                    
                    player_id = str(int(player_id_val))
                    
                    if player_id not in player_stats:
                        player_stats[player_id] = {
                            'player_id': int(player_id),
                            'player_name': str(row.get('nameI', 'Unknown')),
                            'team': str(row.get('teamTricode', 'N/A')),
                            'position': str(row.get('position', 'N/A')),
                            'games': 0,
                            'total_points': 0,
                            'total_rebounds': 0,
                            'total_assists': 0,
                            'total_steals': 0,
                            'total_blocks': 0,
                            'total_turnovers': 0,
                            'total_fgm': 0,
                            'total_fga': 0,
                            'total_3pm': 0,
                            'total_3pa': 0,
                            'total_minutes': 0,
                        }
                    
                    # Aggregate stats
                    stats = player_stats[player_id]
                    stats['games'] += 1
                    stats['total_points'] += int(row.get('points', 0) or 0)
                    stats['total_rebounds'] += int(row.get('reboundsTotal', 0) or 0)
                    stats['total_assists'] += int(row.get('assists', 0) or 0)
                    stats['total_steals'] += int(row.get('steals', 0) or 0)
                    stats['total_blocks'] += int(row.get('blocks', 0) or 0)
                    stats['total_turnovers'] += int(row.get('turnovers', 0) or 0)
                    stats['total_fgm'] += int(row.get('fieldGoalsMade', 0) or 0)
                    stats['total_fga'] += int(row.get('fieldGoalsAttempted', 0) or 0)
                    stats['total_3pm'] += int(row.get('threePointersMade', 0) or 0)
                    stats['total_3pa'] += int(row.get('threePointersAttempted', 0) or 0)
                    stats['total_minutes'] += parse_minutes(row.get('minutes', 0))
                    
                    # Update name/team if missing (in case player played for multiple teams)
                    if stats['player_name'] == 'Unknown':
                        stats['player_name'] = str(row.get('nameI', 'Unknown'))
                    if stats['team'] == 'N/A':
                        stats['team'] = str(row.get('teamTricode', 'N/A'))
                    if stats['position'] == 'N/A':
                        stats['position'] = str(row.get('position', 'N/A'))
                
                successful_games += 1
                break  # Success, move to next game
                
            except (Timeout, RequestException) as e:
                if attempt < max_retries - 1:
                    wait_time = (2 ** attempt) * 2
                    print(f"  Timeout for game {game_id}, retrying after {wait_time}s...", file=sys.stderr)
                    time.sleep(wait_time)
                    continue
                else:
                    print(f"  Error fetching game {game_id} after {max_retries} attempts: {e}", file=sys.stderr)
                    failed_games += 1
                    break
            except Exception as e:
                print(f"  Unexpected error for game {game_id}: {e}", file=sys.stderr)
                if attempt < max_retries - 1:
                    wait_time = (2 ** attempt) * 2
                    time.sleep(wait_time)
                    continue
                failed_games += 1
                break
        
        # Small delay between games to avoid rate limiting
        if idx < len(game_ids) - 1:
            time.sleep(0.5)
    
    print(f"✓ Successfully processed {successful_games} games", file=sys.stderr)
    if failed_games > 0:
        print(f"  Warning: {failed_games} games failed", file=sys.stderr)
    
    print(f"  Found stats for {len(player_stats)} unique players", file=sys.stderr)
    
    if len(player_stats) == 0:
        print("  Warning: No player stats collected from any games", file=sys.stderr)
        return None
    
    return player_stats

def process_player_stats(player_data: Dict) -> Optional[Dict]:
    """Process a single player's aggregated stats and calculate derived metrics."""
    try:
        games = player_data.get('games', 0)
        
        if games == 0:
            return None
        
        total_points = player_data.get('total_points', 0)
        total_rebounds = player_data.get('total_rebounds', 0)
        total_assists = player_data.get('total_assists', 0)
        total_steals = player_data.get('total_steals', 0)
        total_blocks = player_data.get('total_blocks', 0)
        total_turnovers = player_data.get('total_turnovers', 0)
        total_fgm = player_data.get('total_fgm', 0)
        total_fga = player_data.get('total_fga', 0)
        total_3pm = player_data.get('total_3pm', 0)
        total_3pa = player_data.get('total_3pa', 0)
        total_minutes = player_data.get('total_minutes', 0)
        
        ppg = round(total_points / games, 1) if games > 0 else 0
        rpg = round(total_rebounds / games, 1) if games > 0 else 0
        apg = round(total_assists / games, 1) if games > 0 else 0
        spg = round(total_steals / games, 1) if games > 0 else 0
        bpg = round(total_blocks / games, 1) if games > 0 else 0
        mpg = round(total_minutes / games, 1) if games > 0 else 0
        
        fg_percentage = round((total_fgm / total_fga * 100), 1) if total_fga > 0 else 0
        three_pct = round((total_3pm / total_3pa * 100), 1) if total_3pa > 0 else 0
        
        composite_score = (
            total_points * 1.0 +
            total_rebounds * 1.2 +
            total_assists * 1.5 +
            total_steals * 3.0 +
            total_blocks * 3.0 -
            total_turnovers * 1.0
        )
        
        return {
            "player_id": player_data.get('player_id', 0),
            "player_name": player_data.get('player_name', 'Unknown'),
            "team": player_data.get('team', 'N/A'),
            "position": player_data.get('position', 'N/A'),
            "games": int(games),
            "total_points": int(total_points),
            "total_rebounds": int(total_rebounds),
            "total_assists": int(total_assists),
            "total_steals": int(total_steals),
            "total_blocks": int(total_blocks),
            "total_turnovers": int(total_turnovers),
            "total_fgm": int(total_fgm),
            "total_fga": int(total_fga),
            "total_3pm": int(total_3pm),
            "total_3pa": int(total_3pa),
            "total_minutes": round(total_minutes, 1),
            "ppg": ppg,
            "rpg": rpg,
            "apg": apg,
            "spg": spg,
            "bpg": bpg,
            "mpg": mpg,
            "fg_percentage": fg_percentage,
            "3p_percentage": three_pct,
            "composite_score": round(composite_score, 1),
        }
    except Exception as e:
        print(f"Error processing player stats: {e}", file=sys.stderr)
        return None

def main():
    """Main function to get NBA Cup group stage top performers."""
    import argparse
    
    parser = argparse.ArgumentParser(description='Get top performers for NBA Cup group stage')
    parser.add_argument('season', help='NBA season (e.g., 2025-26)')
    parser.add_argument('--limit', type=int, default=50, help='Number of top performers (default: 50)')
    parser.add_argument('--sort', default='composite', choices=['points', 'rebounds', 'assists', 'games', 'composite'],
                       help='Sort by (default: composite)')
    parser.add_argument('--html-file', help='Path to HTML file containing NBA Cup schedule')
    parser.add_argument('--game-ids', nargs='+', help='Specific game IDs (space-separated)')
    
    args = parser.parse_args()
    
    season = args.season
    limit = args.limit
    sort_by = args.sort
    
    print(f"NBA Cup Group Stage Top Performers - {season}", file=sys.stderr)
    print(f"Sort by: {sort_by}, Limit: {limit}", file=sys.stderr)
    print("", file=sys.stderr)
    
    # Get game IDs
    game_ids = []
    
    if args.game_ids:
        game_ids = args.game_ids
    elif args.html_file:
        try:
            with open(args.html_file, 'r', encoding='utf-8', errors='ignore') as f:
                html_content = f.read()
            group_stage_ids, knockout_ids = extract_game_ids_from_html(html_content)
            game_ids = group_stage_ids
            print(f"Extracted {len(group_stage_ids)} group stage game IDs from HTML", file=sys.stderr)
            if knockout_ids:
                print(f"  (Excluded {len(knockout_ids)} knockout stage games)", file=sys.stderr)
        except FileNotFoundError:
            print(f"ERROR: HTML file not found: {args.html_file}", file=sys.stderr)
            sys.exit(1)
        except Exception as e:
            print(f"ERROR: Failed to parse HTML file: {e}", file=sys.stderr)
            sys.exit(1)
    else:
        print("ERROR: Must provide either --html-file or --game-ids", file=sys.stderr)
        print("  --html-file: Path to HTML file with NBA Cup schedule", file=sys.stderr)
        print("  --game-ids: Space-separated list of game IDs", file=sys.stderr)
        sys.exit(1)
    
    if not game_ids:
        print("ERROR: No group stage game IDs found", file=sys.stderr)
        sys.exit(1)
    
    # Get player stats for these games
    player_stats_raw = get_player_stats_for_games(game_ids)
    
    if player_stats_raw is None:
        print("Failed to retrieve player stats from API", file=sys.stderr)
        sys.exit(1)
    
    if len(player_stats_raw) == 0:
        print(f"No players found with games in NBA Cup group stage", file=sys.stderr)
        print(json.dumps([]))
        sys.exit(0)
    
    print(f"Processing {len(player_stats_raw)} players...", file=sys.stderr)
    
    # Process each player's stats
    player_stats_list = []
    for player_data in player_stats_raw.values():
        processed = process_player_stats(player_data)
        if processed:
            player_stats_list.append(processed)
    
    print(f"✓ Found {len(player_stats_list)} players with games in NBA Cup group stage", file=sys.stderr)
    
    if len(player_stats_list) == 0:
        print("No players with stats found", file=sys.stderr)
        print(json.dumps([]))
        sys.exit(0)
    
    # Sort by specified criteria
    sort_field = {
        "points": "total_points",
        "rebounds": "total_rebounds",
        "assists": "total_assists",
        "games": "games",
        "composite": "composite_score",
    }.get(sort_by, "composite_score")
    
    player_stats_list.sort(key=lambda x: x.get(sort_field, 0), reverse=True)
    
    # Limit results
    top_performers = player_stats_list[:limit]
    
    # Output as JSON to stdout
    json_output = json.dumps(top_performers, indent=2)
    print(json_output, flush=True)

if __name__ == "__main__":
    main()
