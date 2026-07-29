#!/usr/bin/env python3
"""
Get top performing NBA players between two dates using real NBA stats from the API.
Uses PlayerCompare endpoint for efficient single-API-call retrieval.
"""

import json
import sys
import time
import signal
from datetime import datetime
from typing import Dict, List, Optional
from nba_api.stats.endpoints import leaguedashplayerstats
from requests.exceptions import Timeout, RequestException


def get_season_from_date(date_str: str) -> str:
    """Determine NBA season from a date. NBA seasons run Oct-Apr."""
    try:
        date_obj = datetime.strptime(date_str, "%Y-%m-%d")
        year = date_obj.year
        month = date_obj.month
        
        # NBA season: October (month 10) to April (month 4) of next year
        # If month is Oct-Dec (10-12), season is YYYY-(YY+1)
        # If month is Jan-Apr (1-4), season is (YYYY-1)-YY
        if month >= 10:
            # October-December: current year to next year
            season = f"{year}-{str(year + 1)[-2:]}"
        elif month <= 4:
            # January-April: previous year to current year
            season = f"{year - 1}-{str(year)[-2:]}"
        else:
            # May-September: use previous season
            season = f"{year - 1}-{str(year)[-2:]}"
        
        return season
    except:
        return '2024-25'  # Default fallback


def check_api_health(timeout=10) -> bool:
    """Quick check if NBA stats API is responding."""
    try:
        from nba_api.stats.endpoints import commonallplayers
        test = commonallplayers.CommonAllPlayers(
            is_only_current_season=1,
            league_id='00',
            season='2024-25',
            timeout=timeout,
            get_request=False
        )
        test.get_request()
        return True
    except:
        return False

def get_player_stats(date_from: str, date_to: str, season: str, max_retries: int = 3) -> Optional[List[Dict]]:
    """Get player stats for all players in date range using LeagueDashPlayerStats endpoint."""
    # Quick health check first
    print("  Checking NBA API health...", file=sys.stderr)
    if not check_api_health(timeout=15):
        print("  ⚠️  NBA Stats API appears to be unresponsive", file=sys.stderr)
        print("  This is a known issue - the API may be temporarily down or slow", file=sys.stderr)
        print("  Suggestion: Try again in a few minutes or use a different date range", file=sys.stderr)
        # Still try the main request in case it's just the health check endpoint
    else:
        print("  ✓ API health check passed", file=sys.stderr)
    
    for attempt in range(max_retries):
        try:
            print(f"Fetching player stats from API (attempt {attempt + 1}/{max_retries})...", file=sys.stderr)
            print(f"  Date range: {date_from} to {date_to}, Season: {season}", file=sys.stderr)
            
            # Try with a longer timeout and simpler parameters first
            timeout = 180 if attempt == 0 else 240  # Increase timeout on retries
            
            # Convert date format from YYYY-MM-DD to MM/DD/YYYY (NBA API format)
            try:
                date_from_dt = datetime.strptime(date_from, "%Y-%m-%d")
                date_to_dt = datetime.strptime(date_to, "%Y-%m-%d")
                date_from_formatted = date_from_dt.strftime("%m/%d/%Y")
                date_to_formatted = date_to_dt.strftime("%m/%d/%Y")
            except:
                date_from_formatted = date_from
                date_to_formatted = date_to
            
            print(f"  Using date format: {date_from_formatted} to {date_to_formatted}", file=sys.stderr)
            
            # Create the endpoint without making request immediately
            league_stats = leaguedashplayerstats.LeagueDashPlayerStats(
                date_from_nullable=date_from_formatted,
                date_to_nullable=date_to_formatted,
                per_mode_detailed='Totals',
                measure_type_detailed_defense='Base',
                season=season,
                season_type_all_star='Regular Season',
                timeout=timeout,
                get_request=False  # Don't make request in __init__
            )
            
            print(f"  Endpoint created, making API request (timeout: {timeout}s)...", file=sys.stderr)
            print(f"  URL: {league_stats.endpoint}", file=sys.stderr)
            
            # Make the request manually
            import time as time_module
            request_start = time_module.time()
            league_stats.get_request()
            request_elapsed = time_module.time() - request_start
            print(f"  Request completed in {request_elapsed:.1f}s", file=sys.stderr)
            
            # Get the data as a DataFrame
            print(f"  Parsing response...", file=sys.stderr)
            data_frames = league_stats.get_data_frames()
            
            if not data_frames or len(data_frames) == 0:
                print("  No data returned from API", file=sys.stderr)
                return None
            
            # First DataFrame contains the player stats
            df = data_frames[0]
            
            if df.empty:
                print("  DataFrame is empty - no players found for this date range", file=sys.stderr)
                return None
            
            # Filter out players with no games played
            df = df[df['GP'] > 0]
            
            # Convert DataFrame to list of dictionaries
            players = df.to_dict('records')
            
            print(f"✓ Retrieved stats for {len(players)} players with games", file=sys.stderr)
            return players
            
        except (Timeout, RequestException) as e:
            if attempt < max_retries - 1:
                wait_time = (2 ** attempt) * 5  # Exponential backoff: 5s, 10s, 20s
                print(f"  Timeout/Error: {e}", file=sys.stderr)
                print(f"  Retrying after {wait_time}s...", file=sys.stderr)
                time.sleep(wait_time)
                continue
            else:
                print(f"  Error fetching player stats after {max_retries} attempts: {e}", file=sys.stderr)
                print(f"  This might be due to:", file=sys.stderr)
                print(f"    - NBA API being slow or unavailable", file=sys.stderr)
                print(f"    - Date range {date_from} to {date_to} having no games or being in the future", file=sys.stderr)
                print(f"    - Network connectivity issues", file=sys.stderr)
                print(f"  Suggestion: Try a date range with known games (e.g., recent past dates)", file=sys.stderr)
                return None
        except Exception as e:
            print(f"  Unexpected error: {e}", file=sys.stderr)
            import traceback
            print(f"  Traceback: {traceback.format_exc()}", file=sys.stderr)
            if attempt < max_retries - 1:
                wait_time = (2 ** attempt) * 5
                print(f"  Retrying after {wait_time}s...", file=sys.stderr)
                time.sleep(wait_time)
                continue
            return None
    
    return None


def process_player_stats(player_data: Dict) -> Optional[Dict]:
    """Process a single player's stats and calculate derived metrics."""
    try:
        # Extract stats from the API response (LeagueDashPlayerStats format)
        games = player_data.get('GP', 0)
        
        if games == 0:
            return None
        
        # Get totals - handle both float and int types
        def safe_float(val):
            try:
                return float(val) if val is not None else 0.0
            except (ValueError, TypeError):
                return 0.0
        
        total_points = safe_float(player_data.get('PTS', 0))
        total_rebounds = safe_float(player_data.get('REB', 0))
        total_assists = safe_float(player_data.get('AST', 0))
        total_steals = safe_float(player_data.get('STL', 0))
        total_blocks = safe_float(player_data.get('BLK', 0))
        total_turnovers = safe_float(player_data.get('TOV', 0))
        total_fgm = safe_float(player_data.get('FGM', 0))
        total_fga = safe_float(player_data.get('FGA', 0))
        total_3pm = safe_float(player_data.get('FG3M', 0))
        total_3pa = safe_float(player_data.get('FG3A', 0))
        total_minutes = safe_float(player_data.get('MIN', 0))
        
        # Calculate per-game averages
        ppg = round(total_points / games, 1) if games > 0 else 0
        rpg = round(total_rebounds / games, 1) if games > 0 else 0
        apg = round(total_assists / games, 1) if games > 0 else 0
        spg = round(total_steals / games, 1) if games > 0 else 0
        bpg = round(total_blocks / games, 1) if games > 0 else 0
        mpg = round(total_minutes / games, 1) if games > 0 else 0
        
        # Calculate percentages
        fg_percentage = round((total_fgm / total_fga * 100), 1) if total_fga > 0 else 0
        three_pct = round((total_3pm / total_3pa * 100), 1) if total_3pa > 0 else 0
        
        # Composite score: weighted combination
        composite_score = (
            total_points * 1.0 +
            total_rebounds * 1.2 +
            total_assists * 1.5 +
            total_steals * 3.0 +
            total_blocks * 3.0 -
            total_turnovers * 1.0
        )
        
        return {
            "player_id": int(player_data.get('PLAYER_ID', 0)),
            "player_name": player_data.get('PLAYER_NAME', 'Unknown'),
            "team": player_data.get('TEAM_ABBREVIATION', 'N/A'),
            "position": player_data.get('PLAYER_POSITION', 'N/A'),
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
        import traceback
        print(traceback.format_exc(), file=sys.stderr)
        return None


def get_historical_performers_by_month(month: int, limit: int = 10, sort_by: str = "composite", years_back: int = 20) -> List[Dict]:
    """
    Get top historical performers for a specific month across multiple years.
    
    Args:
        month: Month number (1-12)
        limit: Number of top performers to return
        sort_by: Sort criteria (points, rebounds, assists, games, composite)
        years_back: How many years back to search (default: 20)
    
    Returns:
        List of player stats dictionaries
    """
    from datetime import date
    
    current_year = datetime.now().year
    all_performers = []
    
    print(f"Fetching historical performers for month {month} across {years_back} years...", file=sys.stderr)
    
    for year_offset in range(years_back):
        year = current_year - year_offset
        
        # Calculate date range for this year's month
        try:
            start_date = date(year, month, 1)
            # Get last day of month
            if month == 12:
                end_date = date(year + 1, 1, 1)
            else:
                end_date = date(year, month + 1, 1)
            
            # Subtract one day to get last day of target month
            from datetime import timedelta
            end_date = end_date - timedelta(days=1)
            
            start_date_str = start_date.strftime("%Y-%m-%d")
            end_date_str = end_date.strftime("%Y-%m-%d")
            
            season = get_season_from_date(start_date_str)
            
            print(f"  Checking {year} ({start_date_str} to {end_date_str})...", file=sys.stderr)
            
            player_stats_raw = get_player_stats(start_date_str, end_date_str, season)
            
            if player_stats_raw:
                for player_data in player_stats_raw:
                    processed = process_player_stats(player_data)
                    if processed:
                        processed['year'] = year
                        processed['historical'] = True
                        all_performers.append(processed)
        
        except Exception as e:
            print(f"  Error processing {year}: {e}", file=sys.stderr)
            continue
    
    print(f"✓ Found {len(all_performers)} total historical performers", file=sys.stderr)
    
    if not all_performers:
        return []
    
    # Sort by specified criteria
    sort_field = {
        "points": "total_points",
        "rebounds": "total_rebounds",
        "assists": "total_assists",
        "games": "games",
        "composite": "composite_score",
    }.get(sort_by, "composite_score")
    
    all_performers.sort(key=lambda x: x.get(sort_field, 0), reverse=True)
    
    # Limit results
    return all_performers[:limit]


def main():
    """Main function to get top performers."""
    import argparse
    
    parser = argparse.ArgumentParser(description='Get top performing NBA players')
    parser.add_argument('start_date', nargs='?', help='Start date (YYYY-MM-DD)')
    parser.add_argument('end_date', nargs='?', help='End date (YYYY-MM-DD)')
    parser.add_argument('--limit', type=int, default=50, help='Number of top performers (default: 50)')
    parser.add_argument('--sort', default='points', choices=['points', 'rebounds', 'assists', 'games', 'composite'],
                       help='Sort by criteria (default: points)')
    parser.add_argument('--season', help='NBA season (auto-detected if not provided)')
    parser.add_argument('--historical', action='store_true', help='Enable historical mode')
    parser.add_argument('--month', type=int, help='Month number (1-12) for historical queries')
    parser.add_argument('--years-back', type=int, default=25, help='Years back to search for historical (default: 25)')
    
    args = parser.parse_args()
    
    # Historical mode: get performers for a specific month across years
    # Check this FIRST before legacy argument handling
    if args.historical and args.month:
        if not (1 <= args.month <= 12):
            print("Error: Month must be between 1 and 12", file=sys.stderr)
            sys.exit(1)
        
        historical_performers = get_historical_performers_by_month(
            args.month,
            limit=args.limit,
            sort_by=args.sort,
            years_back=args.years_back
        )
        
        json_output = json.dumps(historical_performers, indent=2)
        print(json_output, flush=True)
        return
    
    # Support legacy positional arguments (only if not historical mode)
    if args.start_date and args.end_date:
        start_date = args.start_date
        end_date = args.end_date
        limit = args.limit
        sort_by = args.sort
        season = args.season
    elif len(sys.argv) >= 3 and not args.historical:
        # Legacy mode: positional arguments (skip if historical mode)
        start_date = sys.argv[1]
        end_date = sys.argv[2]
        limit = int(sys.argv[3]) if len(sys.argv) > 3 and sys.argv[3].isdigit() else 50
        sort_by = sys.argv[4] if len(sys.argv) > 4 else "points"
        season = sys.argv[5] if len(sys.argv) > 5 else None
    else:
        if not args.historical:
            parser.print_help()
            sys.exit(1)
        else:
            # Historical mode but missing month
            parser.print_help()
            sys.exit(1)
    
    # Validate dates (skip if historical mode)
    if not (args.historical and args.month):
        try:
            datetime.strptime(start_date, "%Y-%m-%d")
            datetime.strptime(end_date, "%Y-%m-%d")
        except ValueError:
            print("Error: Invalid date format. Use YYYY-MM-DD", file=sys.stderr)
            sys.exit(1)
    
    # Auto-detect season if not provided
    if season is None:
        season = get_season_from_date(start_date)
    
    # Check if dates are in the future
    today = datetime.now().date()
    start_dt = datetime.strptime(start_date, "%Y-%m-%d").date()
    end_dt = datetime.strptime(end_date, "%Y-%m-%d").date()
    
    if start_dt > today:
        print(f"⚠️  Warning: Start date {start_date} is in the future", file=sys.stderr)
        print(f"   The NBA API may not have data for future dates yet", file=sys.stderr)
        print("", file=sys.stderr)
    
    print(f"Fetching top performers from {start_date} to {end_date}...", file=sys.stderr)
    print(f"Season: {season}, Sort by: {sort_by}, Limit: {limit}", file=sys.stderr)
    print("", file=sys.stderr)
    
    # Get all player stats in one API call
    player_stats_raw = get_player_stats(start_date, end_date, season)
    
    if player_stats_raw is None:
        print("Failed to retrieve player stats from API", file=sys.stderr)
        sys.exit(1)
    
    if len(player_stats_raw) == 0:
        print(f"No players found with games in date range {start_date} to {end_date}", file=sys.stderr)
        print(json.dumps([]))
        sys.exit(0)
    
    print(f"Processing {len(player_stats_raw)} players...", file=sys.stderr)
    
    # Process each player's stats
    player_stats_list = []
    for player_data in player_stats_raw:
        processed = process_player_stats(player_data)
        if processed:
            player_stats_list.append(processed)
    
    print(f"✓ Found {len(player_stats_list)} players with games in date range", file=sys.stderr)
    
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
    }.get(sort_by, "total_points")
    
    player_stats_list.sort(key=lambda x: x.get(sort_field, 0), reverse=True)
    
    # Limit results
    top_performers = player_stats_list[:limit]
    
    # Output as JSON to stdout (progress messages go to stderr)
    json_output = json.dumps(top_performers, indent=2)
    print(json_output, flush=True)


if __name__ == "__main__":
    main()
