/**
 * List of all 30 NBA teams
 * Organized by division for easy reference
 */
export const NBA_TEAMS = [
  // Atlantic Division
  'Boston Celtics',
  'Brooklyn Nets',
  'New York Knicks',
  'Philadelphia 76ers',
  'Toronto Raptors',

  // Central Division
  'Chicago Bulls',
  'Cleveland Cavaliers',
  'Detroit Pistons',
  'Indiana Pacers',
  'Milwaukee Bucks',

  // Southeast Division
  'Atlanta Hawks',
  'Charlotte Hornets',
  'Miami Heat',
  'Orlando Magic',
  'Washington Wizards',

  // Northwest Division
  'Denver Nuggets',
  'Minnesota Timberwolves',
  'Oklahoma City Thunder',
  'Portland Trail Blazers',
  'Utah Jazz',

  // Pacific Division
  'Golden State Warriors',
  'LA Clippers',
  'Los Angeles Lakers',
  'Phoenix Suns',
  'Sacramento Kings',

  // Southwest Division
  'Dallas Mavericks',
  'Houston Rockets',
  'Memphis Grizzlies',
  'New Orleans Pelicans',
  'San Antonio Spurs',
] as const;

export type NBATeam = typeof NBA_TEAMS[number];

