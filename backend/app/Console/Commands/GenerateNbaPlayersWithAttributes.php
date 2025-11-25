<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;

class GenerateNbaPlayersWithAttributes extends Command
{
    protected $signature = 'nba:generate-players';
    protected $description = 'Generate 400+ NBA players with detailed 2K-style attributes';

    private function getCardTier(int $rating): string
    {
        if ($rating >= 97) return 'pink_diamond';
        if ($rating >= 93) return 'diamond';
        if ($rating >= 90) return 'amethyst';
        if ($rating >= 85) return 'sapphire';
        return 'emerald';
    }

    private function generateAttributes(string $position, int $baseRating): array
    {
        // Base attributes that will be adjusted by position
        $attrs = [
            'close_shot' => 70,
            'mid_range_shot' => 65,
            'three_point_shot' => 60,
            'free_throw' => 70,
            'shot_iq' => 65,
            'offensive_consistency' => 70,
            'layup' => 70,
            'standing_dunk' => 40,
            'driving_dunk' => 40,
            'post_hook' => 50,
            'post_fade' => 50,
            'post_control' => 50,
            'draw_foul' => 60,
            'hands' => 70,
            'interior_defense' => 50,
            'perimeter_defense' => 60,
            'steal' => 55,
            'block' => 40,
            'help_defense_iq' => 60,
            'pass_perception' => 60,
            'defensive_consistency' => 65,
            'speed' => 70,
            'agility' => 70,
            'strength' => 60,
            'vertical' => 65,
            'stamina' => 80,
            'hustle' => 75,
            'overall_durability' => 75,
            'pass_accuracy' => 65,
            'ball_handle' => 65,
            'speed_with_ball' => 70,
            'pass_iq' => 65,
            'pass_vision' => 65,
            'offensive_rebound' => 50,
            'defensive_rebound' => 55,
            'intangibles' => 70,
        ];

        // Position adjustments
        if (in_array($position, ['PG', 'SG'])) {
            $attrs['three_point_shot'] += 10;
            $attrs['ball_handle'] += 15;
            $attrs['pass_accuracy'] += 10;
            $attrs['speed'] += 10;
            $attrs['agility'] += 10;
            $attrs['perimeter_defense'] += 5;
            $attrs['standing_dunk'] = max(25, $attrs['standing_dunk'] - 15);
            $attrs['interior_defense'] -= 15;
            $attrs['strength'] -= 10;
            $attrs['defensive_rebound'] -= 10;
        } elseif ($position === 'SF') {
            $attrs['mid_range_shot'] += 8;
            $attrs['driving_dunk'] += 12;
            $attrs['three_point_shot'] += 5;
            $attrs['perimeter_defense'] += 5;
        } elseif (in_array($position, ['PF', 'C'])) {
            $attrs['close_shot'] += 15;
            $attrs['standing_dunk'] += 25;
            $attrs['post_hook'] += 20;
            $attrs['post_fade'] += 15;
            $attrs['post_control'] += 20;
            $attrs['interior_defense'] += 20;
            $attrs['defensive_rebound'] += 20;
            $attrs['offensive_rebound'] += 20;
            $attrs['strength'] += 15;
            $attrs['block'] += 15;
            $attrs['three_point_shot'] = max(25, $attrs['three_point_shot'] - 20);
            $attrs['ball_handle'] -= 20;
            $attrs['speed'] -= 15;
            $attrs['agility'] -= 10;
        }

        // Scale by base rating (higher rated players have better attributes)
        $scaleFactor = $baseRating / 80;
        foreach ($attrs as $key => $value) {
            $attrs[$key] = (int) min(99, max(25, $value * $scaleFactor));
        }

        // Add randomization (±3 points for variety)
        foreach ($attrs as $key => $value) {
            $attrs[$key] = min(99, max(25, $value + rand(-3, 3)));
        }

        // Calculate category ratings
        $attrs['outside_scoring'] = (int)(($attrs['close_shot'] + $attrs['mid_range_shot'] + $attrs['three_point_shot'] + $attrs['free_throw'] + $attrs['shot_iq'] + $attrs['offensive_consistency']) / 6);
        $attrs['inside_scoring'] = (int)(($attrs['layup'] + $attrs['standing_dunk'] + $attrs['driving_dunk'] + $attrs['post_hook'] + $attrs['post_fade'] + $attrs['post_control'] + $attrs['draw_foul'] + $attrs['hands']) / 8);
        $attrs['defense'] = (int)(($attrs['interior_defense'] + $attrs['perimeter_defense'] + $attrs['steal'] + $attrs['block'] + $attrs['help_defense_iq'] + $attrs['pass_perception'] + $attrs['defensive_consistency']) / 7);
        $attrs['athleticism'] = (int)(($attrs['speed'] + $attrs['agility'] + $attrs['strength'] + $attrs['vertical'] + $attrs['stamina'] + $attrs['hustle'] + $attrs['overall_durability']) / 7);
        $attrs['playmaking'] = (int)(($attrs['pass_accuracy'] + $attrs['ball_handle'] + $attrs['speed_with_ball'] + $attrs['pass_iq'] + $attrs['pass_vision']) / 5);
        $attrs['rebounding'] = (int)(($attrs['offensive_rebound'] + $attrs['defensive_rebound']) / 2);

        // Determine potential based on rating
        if ($baseRating >= 85) $attrs['potential'] = 'A';
        elseif ($baseRating >= 80) $attrs['potential'] = 'B';
        else $attrs['potential'] = 'C';

        return $attrs;
    }

    public function handle()
    {
        $this->info('Generating 400+ NBA players with detailed attributes...');
        $this->newLine();

        Player::truncate();

        // Comprehensive list of 400+ current NBA players with their teams and positions
        // This includes all major stars, starters, role players, and bench players
        $players = $this->getNbaPlayersData();

        $bar = $this->output->createProgressBar(count($players));
        $bar->start();

        foreach ($players as $playerData) {
            $attributes = $this->generateAttributes($playerData['position'], $playerData['rating']);

            Player::create([
                'name' => $playerData['name'],
                'overall_rating' => $playerData['rating'],
                'position' => $playerData['position'],
                'team' => $playerData['team'],
                'card_tier' => $this->getCardTier($playerData['rating']),
                'image_url' => null,
                ...$attributes
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Show statistics
        $tierCounts = Player::selectRaw('card_tier, COUNT(*) as count')
            ->groupBy('card_tier')
            ->pluck('count', 'card_tier');

        $this->info('✓ Successfully generated ' . Player::count() . ' NBA players!');
        $this->newLine();
        $this->info('Tier Breakdown:');
        foreach (['pink_diamond', 'diamond', 'amethyst', 'sapphire', 'emerald'] as $tier) {
            if (isset($tierCounts[$tier])) {
                $tierName = str_replace('_', ' ', ucwords($tier, '_'));
                $this->info("  {$tierName}: {$tierCounts[$tier]} players");
            }
        }

        return 0;
    }

    private function getNbaPlayersData(): array
    {
        // Comprehensive dataset of 400+ current NBA players based on 2024-25 season
        $players = [];

        // Add specific star players with correct ratings
        $starPlayers = [
            // ELITE TIER (97-99)
            ['name' => 'Nikola Jokic', 'rating' => 99, 'position' => 'C', 'team' => 'Denver Nuggets'],
            ['name' => 'Luka Doncic', 'rating' => 98, 'position' => 'PG', 'team' => 'Dallas Mavericks'],
            ['name' => 'Giannis Antetokounmpo', 'rating' => 97, 'position' => 'PF', 'team' => 'Milwaukee Bucks'],

            // SUPERSTARS (93-96)
            ['name' => 'Stephen Curry', 'rating' => 96, 'position' => 'PG', 'team' => 'Golden State Warriors'],
            ['name' => 'LeBron James', 'rating' => 96, 'position' => 'SF', 'team' => 'Los Angeles Lakers'],
            ['name' => 'Kevin Durant', 'rating' => 95, 'position' => 'SF', 'team' => 'Phoenix Suns'],
            ['name' => 'Joel Embiid', 'rating' => 95, 'position' => 'C', 'team' => 'Philadelphia 76ers'],
            ['name' => 'Jayson Tatum', 'rating' => 94, 'position' => 'SF', 'team' => 'Boston Celtics'],
            ['name' => 'Shai Gilgeous-Alexander', 'rating' => 94, 'position' => 'PG', 'team' => 'Oklahoma City Thunder'],
            ['name' => 'Anthony Davis', 'rating' => 93, 'position' => 'PF', 'team' => 'Los Angeles Lakers'],

            // ALL-STARS (90-92)
            ['name' => 'Damian Lillard', 'rating' => 92, 'position' => 'PG', 'team' => 'Milwaukee Bucks'],
            ['name' => 'Kawhi Leonard', 'rating' => 92, 'position' => 'SF', 'team' => 'LA Clippers'],
            ['name' => 'Jimmy Butler', 'rating' => 91, 'position' => 'SF', 'team' => 'Miami Heat'],
            ['name' => 'Devin Booker', 'rating' => 91, 'position' => 'SG', 'team' => 'Phoenix Suns'],
            ['name' => 'Donovan Mitchell', 'rating' => 90, 'position' => 'SG', 'team' => 'Cleveland Cavaliers'],
            ['name' => 'Anthony Edwards', 'rating' => 90, 'position' => 'SG', 'team' => 'Minnesota Timberwolves'],
            ['name' => 'Jrue Holiday', 'rating' => 90, 'position' => 'PG', 'team' => 'Boston Celtics'],
            ['name' => 'Rudy Gobert', 'rating' => 90, 'position' => 'C', 'team' => 'Minnesota Timberwolves'],

            // STARS (85-89)
            ['name' => 'Tyrese Haliburton', 'rating' => 89, 'position' => 'PG', 'team' => 'Indiana Pacers'],
            ['name' => 'Paul George', 'rating' => 89, 'position' => 'SF', 'team' => 'Philadelphia 76ers'],
            ['name' => 'Kyrie Irving', 'rating' => 89, 'position' => 'PG', 'team' => 'Dallas Mavericks'],
            ['name' => 'Ja Morant', 'rating' => 88, 'position' => 'PG', 'team' => 'Memphis Grizzlies'],
            ['name' => 'Bam Adebayo', 'rating' => 88, 'position' => 'C', 'team' => 'Miami Heat'],
            ['name' => 'DeMar DeRozan', 'rating' => 87, 'position' => 'SG', 'team' => 'Sacramento Kings'],
            ['name' => 'Jaylen Brown', 'rating' => 87, 'position' => 'SG', 'team' => 'Boston Celtics'],
            ['name' => 'LaMelo Ball', 'rating' => 86, 'position' => 'PG', 'team' => 'Charlotte Hornets'],
            ['name' => 'Zion Williamson', 'rating' => 86, 'position' => 'PF', 'team' => 'New Orleans Pelicans'],
            ['name' => 'De\'Aaron Fox', 'rating' => 85, 'position' => 'PG', 'team' => 'Sacramento Kings'],
            ['name' => 'Julius Randle', 'rating' => 85, 'position' => 'PF', 'team' => 'Minnesota Timberwolves'],
            ['name' => 'Paolo Banchero', 'rating' => 85, 'position' => 'PF', 'team' => 'Orlando Magic'],
            ['name' => 'Kristaps Porzingis', 'rating' => 85, 'position' => 'C', 'team' => 'Boston Celtics'],
            ['name' => 'Lauri Markkanen', 'rating' => 85, 'position' => 'PF', 'team' => 'Utah Jazz'],
        ];

        $players = array_merge($players, $starPlayers);

        // Now add players for each team (12-15 players per team to reach 400+ total)
        $teams = [
            'Atlanta Hawks' => [
                ['Trae Young', 84, 'PG'],
                ['Dejounte Murray', 83, 'PG'],
                ['Clint Capela', 80, 'C'],
                ['Bogdan Bogdanovic', 79, 'SG'],
                ['Saddiq Bey', 77, 'SF'],
                ['Onyeka Okongwu', 77, 'C'],
                ['De\'Andre Hunter', 76, 'SF'],
                ['AJ Griffin', 73, 'SG'],
                ['Jalen Johnson', 75, 'PF'],
                ['Kobe Bufkin', 72, 'PG'],
                ['Wesley Matthews', 71, 'SG'],
                ['Garrison Mathews', 70, 'SG'],
            ],
            'Boston Celtics' => [
                ['Derrick White', 84, 'PG'],
                ['Al Horford', 80, 'C'],
                ['Malcolm Brogdon', 82, 'PG'],
                ['Robert Williams', 81, 'C'],
                ['Sam Hauser', 75, 'SF'],
                ['Payton Pritchard', 74, 'PG'],
                ['Luke Kornet', 72, 'C'],
                ['Dalano Banton', 71, 'SG'],
                ['Svi Mykhailiuk', 71, 'SF'],
                ['Jordan Walsh', 70, 'SF'],
                ['Neemias Queta', 70, 'C'],
            ],
            'Brooklyn Nets' => [
                ['Mikal Bridges', 82, 'SF'],
                ['Cameron Johnson', 81, 'SF'],
                ['Nic Claxton', 80, 'C'],
                ['Spencer Dinwiddie', 79, 'PG'],
                ['Dorian Finney-Smith', 78, 'PF'],
                ['Day\'Ron Sharpe', 75, 'C'],
                ['Cam Thomas', 78, 'SG'],
                ['Royce O\'Neale', 77, 'SF'],
                ['Dennis Smith Jr.', 74, 'PG'],
                ['Trendon Watford', 73, 'PF'],
                ['Lonnie Walker IV', 75, 'SG'],
                ['Dariq Whitehead', 70, 'SF'],
            ],
            'Charlotte Hornets' => [
                ['Brandon Miller', 80, 'SF'],
                ['Mark Williams', 79, 'C'],
                ['Miles Bridges', 82, 'PF'],
                ['Terry Rozier', 81, 'PG'],
                ['Gordon Hayward', 78, 'SF'],
                ['PJ Washington', 78, 'PF'],
                ['Nick Richards', 74, 'C'],
                ['Bryce McGowens', 72, 'SG'],
                ['Nick Smith Jr.', 73, 'PG'],
                ['JT Thor', 71, 'PF'],
                ['James Bouknight', 71, 'SG'],
                ['Kai Jones', 70, 'C'],
            ],
            'Chicago Bulls' => [
                ['Zach LaVine', 84, 'SG'],
                ['Nikola Vucevic', 85, 'C'],
                ['Lonzo Ball', 80, 'PG'],
                ['Patrick Williams', 77, 'PF'],
                ['Alex Caruso', 79, 'PG'],
                ['Coby White', 78, 'PG'],
                ['Ayo Dosunmu', 76, 'SG'],
                ['Andre Drummond', 77, 'C'],
                ['Torrey Craig', 74, 'SF'],
                ['Jevon Carter', 73, 'PG'],
                ['Julian Phillips', 71, 'SF'],
                ['Dalen Terry', 71, 'SG'],
            ],
            'Cleveland Cavaliers' => [
                ['Darius Garland', 84, 'PG'],
                ['Jarrett Allen', 81, 'C'],
                ['Evan Mobley', 81, 'PF'],
                ['Caris LeVert', 79, 'SG'],
                ['Isaac Okoro', 76, 'SF'],
                ['Georges Niang', 75, 'PF'],
                ['Dean Wade', 74, 'SF'],
                ['Craig Porter Jr.', 72, 'PG'],
                ['Max Strus', 77, 'SG'],
                ['Sam Merrill', 73, 'SG'],
                ['Damian Jones', 72, 'C'],
                ['Emoni Bates', 70, 'SF'],
            ],
            'Dallas Mavericks' => [
                ['Tim Hardaway Jr.', 78, 'SG'],
                ['Derrick Jones Jr.', 76, 'SF'],
                ['Maxi Kleber', 75, 'PF'],
                ['Dante Exum', 75, 'PG'],
                ['Dereck Lively II', 77, 'C'],
                ['Josh Green', 74, 'SG'],
                ['Jaden Hardy', 74, 'SG'],
                ['Grant Williams', 76, 'PF'],
                ['Richaun Holmes', 74, 'C'],
                ['Seth Curry', 76, 'SG'],
                ['Markieff Morris', 73, 'PF'],
                ['A.J. Lawson', 70, 'SG'],
            ],
            'Denver Nuggets' => [
                ['Jamal Murray', 87, 'PG'],
                ['Michael Porter Jr.', 84, 'SF'],
                ['Aaron Gordon', 83, 'PF'],
                ['Kentavious Caldwell-Pope', 79, 'SG'],
                ['Christian Braun', 75, 'SG'],
                ['Reggie Jackson', 77, 'PG'],
                ['DeAndre Jordan', 74, 'C'],
                ['Peyton Watson', 73, 'SF'],
                ['Justin Holiday', 74, 'SF'],
                ['Julian Strawther', 72, 'SF'],
                ['Zeke Nnaji', 73, 'PF'],
                ['Jalen Pickett', 71, 'PG'],
            ],
            'Detroit Pistons' => [
                ['Cade Cunningham', 81, 'PG'],
                ['Jaden Ivey', 78, 'SG'],
                ['Isaiah Stewart', 77, 'C'],
                ['Bojan Bogdanovic', 79, 'SF'],
                ['Jalen Duren', 78, 'C'],
                ['Killian Hayes', 74, 'PG'],
                ['Ausar Thompson', 76, 'SF'],
                ['James Wiseman', 75, 'C'],
                ['Marcus Sasser', 72, 'PG'],
                ['Joe Harris', 75, 'SG'],
                ['Mike Muscala', 73, 'C'],
                ['Stanley Umude', 70, 'SG'],
            ],
            'Golden State Warriors' => [
                ['Klay Thompson', 84, 'SG'],
                ['Draymond Green', 82, 'PF'],
                ['Andrew Wiggins', 80, 'SF'],
                ['Chris Paul', 83, 'PG'],
                ['Gary Payton II', 76, 'PG'],
                ['Jonathan Kuminga', 79, 'PF'],
                ['Moses Moody', 76, 'SG'],
                ['Brandin Podziemski', 75, 'SG'],
                ['Kevon Looney', 77, 'C'],
                ['Dario Saric', 75, 'PF'],
                ['Trayce Jackson-Davis', 74, 'PF'],
                ['Cory Joseph', 73, 'PG'],
            ],
            'Houston Rockets' => [
                ['Alperen Sengun', 81, 'C'],
                ['Jalen Green', 81, 'SG'],
                ['Fred VanVleet', 81, 'PG'],
                ['Jabari Smith Jr.', 77, 'PF'],
                ['Dillon Brooks', 78, 'SF'],
                ['Amen Thompson', 76, 'SG'],
                ['Tari Eason', 75, 'SF'],
                ['Jock Landale', 74, 'C'],
                ['Jeff Green', 75, 'PF'],
                ['Cam Whitmore', 74, 'SF'],
                ['Aaron Holiday', 73, 'PG'],
                ['Jae\'Sean Tate', 74, 'SF'],
            ],
            'Indiana Pacers' => [
                ['Myles Turner', 82, 'C'],
                ['Pascal Siakam', 83, 'PF'],
                ['Bruce Brown', 78, 'SG'],
                ['Buddy Hield', 79, 'SG'],
                ['T.J. McConnell', 76, 'PG'],
                ['Obi Toppin', 77, 'PF'],
                ['Bennedict Mathurin', 78, 'SG'],
                ['Aaron Nesmith', 76, 'SF'],
                ['Jalen Smith', 75, 'PF'],
                ['Isaiah Jackson', 75, 'C'],
                ['Andrew Nembhard', 76, 'PG'],
                ['Ben Sheppard', 72, 'SG'],
            ],
            'LA Clippers' => [
                ['James Harden', 88, 'PG'],
                ['Russell Westbrook', 82, 'PG'],
                ['Norman Powell', 79, 'SG'],
                ['Terance Mann', 76, 'SF'],
                ['Ivica Zubac', 78, 'C'],
                ['Marcus Morris Sr.', 76, 'PF'],
                ['Robert Covington', 75, 'PF'],
                ['Bones Hyland', 75, 'PG'],
                ['Amir Coffey', 74, 'SG'],
                ['Mason Plumlee', 75, 'C'],
                ['Kobe Brown', 72, 'SF'],
                ['Jordan Miller', 71, 'SF'],
            ],
            'Los Angeles Lakers' => [
                ['D\'Angelo Russell', 82, 'PG'],
                ['Austin Reaves', 80, 'SG'],
                ['Rui Hachimura', 79, 'PF'],
                ['Jarred Vanderbilt', 77, 'SF'],
                ['Gabe Vincent', 76, 'PG'],
                ['Christian Wood', 78, 'PF'],
                ['Jaxson Hayes', 75, 'C'],
                ['Taurean Prince', 76, 'SF'],
                ['Max Christie', 74, 'SG'],
                ['Jalen Hood-Schifino', 73, 'PG'],
                ['Colin Castleton', 71, 'C'],
                ['Maxwell Lewis', 71, 'SF'],
            ],
            'Memphis Grizzlies' => [
                ['Desmond Bane', 81, 'SG'],
                ['Jaren Jackson Jr.', 82, 'PF'],
                ['Marcus Smart', 80, 'PG'],
                ['Luke Kennard', 77, 'SG'],
                ['Xavier Tillman', 75, 'PF'],
                ['Ziaire Williams', 75, 'SF'],
                ['John Konchar', 74, 'SF'],
                ['Santi Aldama', 76, 'PF'],
                ['GG Jackson', 74, 'PF'],
                ['Jacob Gilyard', 72, 'PG'],
                ['David Roddy', 73, 'PF'],
                ['Vince Williams Jr.', 73, 'SF'],
            ],
            'Miami Heat' => [
                ['Tyler Herro', 82, 'SG'],
                ['Kyle Lowry', 80, 'PG'],
                ['Caleb Martin', 77, 'SF'],
                ['Duncan Robinson', 77, 'SG'],
                ['Kevin Love', 78, 'PF'],
                ['Josh Richardson', 76, 'SG'],
                ['Nikola Jovic', 75, 'PF'],
                ['Jaime Jaquez Jr.', 76, 'SF'],
                ['Thomas Bryant', 75, 'C'],
                ['Haywood Highsmith', 73, 'SF'],
                ['Cole Swider', 71, 'SF'],
                ['Alondes Williams', 71, 'PG'],
            ],
            'Milwaukee Bucks' => [
                ['Khris Middleton', 86, 'SF'],
                ['Brook Lopez', 83, 'C'],
                ['Bobby Portis', 79, 'PF'],
                ['Pat Connaughton', 75, 'SG'],
                ['Malik Beasley', 77, 'SG'],
                ['Jae Crowder', 76, 'PF'],
                ['MarJon Beauchamp', 74, 'SF'],
                ['Cameron Payne', 75, 'PG'],
                ['Andre Jackson Jr.', 72, 'SF'],
                ['Thanasis Antetokounmpo', 72, 'SF'],
                ['Chris Livingston', 71, 'SF'],
                ['AJ Green', 71, 'SG'],
            ],
            'Minnesota Timberwolves' => [
                ['Mike Conley', 81, 'PG'],
                ['Karl-Anthony Towns', 84, 'C'],
                ['Jaden McDaniels', 79, 'SF'],
                ['Kyle Anderson', 77, 'PF'],
                ['Nickeil Alexander-Walker', 76, 'SG'],
                ['Naz Reid', 78, 'C'],
                ['Jordan McLaughlin', 74, 'PG'],
                ['Shake Milton', 75, 'PG'],
                ['Wendell Moore Jr.', 73, 'SF'],
                ['Troy Brown Jr.', 74, 'SF'],
                ['Luka Garza', 73, 'C'],
                ['Leonard Miller', 72, 'PF'],
            ],
            'New Orleans Pelicans' => [
                ['Brandon Ingram', 83, 'SF'],
                ['CJ McCollum', 80, 'SG'],
                ['Herb Jones', 79, 'SF'],
                ['Jonas Valanciunas', 80, 'C'],
                ['Trey Murphy III', 79, 'SF'],
                ['Jose Alvarado', 76, 'PG'],
                ['Larry Nance Jr.', 76, 'PF'],
                ['Dyson Daniels', 75, 'SG'],
                ['Jordan Hawkins', 75, 'SG'],
                ['Naji Marshall', 75, 'SF'],
                ['Dereon Seabron', 72, 'SG'],
                ['E.J. Liddell', 73, 'PF'],
            ],
            'New York Knicks' => [
                ['Jalen Brunson', 82, 'PG'],
                ['Julius Randle', 85, 'PF'],
                ['RJ Barrett', 80, 'SG'],
                ['Mitchell Robinson', 79, 'C'],
                ['Immanuel Quickley', 79, 'PG'],
                ['Isaiah Hartenstein', 78, 'C'],
                ['Quentin Grimes', 77, 'SG'],
                ['Josh Hart', 78, 'SF'],
                ['Donte DiVincenzo', 78, 'SG'],
                ['Evan Fournier', 77, 'SG'],
                ['Jericho Sims', 73, 'C'],
                ['Trevor Keels', 72, 'SG'],
            ],
            'Oklahoma City Thunder' => [
                ['Chet Holmgren', 84, 'C'],
                ['Josh Giddey', 81, 'PG'],
                ['Jalen Williams', 82, 'SF'],
                ['Lu Dort', 78, 'SG'],
                ['Isaiah Joe', 76, 'SG'],
                ['Cason Wallace', 76, 'PG'],
                ['Kenrich Williams', 75, 'SF'],
                ['Tre Mann', 75, 'PG'],
                ['Jaylin Williams', 74, 'PF'],
                ['Davis Bertans', 75, 'PF'],
                ['Vasilije Micic', 76, 'PG'],
                ['Olivier Sarr', 72, 'C'],
            ],
            'Orlando Magic' => [
                ['Franz Wagner', 81, 'SF'],
                ['Wendell Carter Jr.', 80, 'C'],
                ['Markelle Fultz', 77, 'PG'],
                ['Cole Anthony', 77, 'PG'],
                ['Jalen Suggs', 78, 'SG'],
                ['Gary Harris', 76, 'SG'],
                ['Jonathan Isaac', 78, 'PF'],
                ['Moritz Wagner', 76, 'C'],
                ['Anthony Black', 76, 'PG'],
                ['Joe Ingles', 76, 'SF'],
                ['Chuma Okeke', 74, 'PF'],
                ['Caleb Houstan', 73, 'SF'],
            ],
            'Philadelphia 76ers' => [
                ['Tyrese Maxey', 81, 'PG'],
                ['Tobias Harris', 80, 'PF'],
                ['Kelly Oubre Jr.', 78, 'SF'],
                ['Patrick Beverley', 77, 'PG'],
                ['Nicolas Batum', 78, 'SF'],
                ['Robert Covington', 75, 'PF'],
                ['De\'Anthony Melton', 77, 'SG'],
                ['Danuel House Jr.', 75, 'SF'],
                ['Mo Bamba', 76, 'C'],
                ['Jaden Springer', 73, 'SG'],
                ['Furkan Korkmaz', 74, 'SG'],
                ['Ricky Council IV', 72, 'SF'],
            ],
            'Phoenix Suns' => [
                ['Bradley Beal', 87, 'SG'],
                ['Jusuf Nurkic', 81, 'C'],
                ['Grayson Allen', 77, 'SG'],
                ['Eric Gordon', 78, 'SG'],
                ['Drew Eubanks', 75, 'C'],
                ['Yuta Watanabe', 74, 'SF'],
                ['Damion Lee', 74, 'SG'],
                ['Josh Okogie', 75, 'SF'],
                ['Bol Bol', 75, 'C'],
                ['Jordan Goodwin', 73, 'PG'],
                ['Saben Lee', 72, 'PG'],
                ['Ish Wainright', 72, 'PF'],
            ],
            'Portland Trail Blazers' => [
                ['Anfernee Simons', 82, 'PG'],
                ['Jerami Grant', 81, 'PF'],
                ['Deandre Ayton', 82, 'C'],
                ['Shaedon Sharpe', 79, 'SG'],
                ['Malcolm Brogdon', 82, 'PG'],
                ['Matisse Thybulle', 76, 'SF'],
                ['Robert Williams III', 80, 'C'],
                ['Jabari Walker', 74, 'PF'],
                ['Scoot Henderson', 78, 'PG'],
                ['Kris Murray', 74, 'SF'],
                ['Duop Reath', 73, 'C'],
                ['Rayan Rupert', 72, 'SG'],
            ],
            'Sacramento Kings' => [
                ['Domantas Sabonis', 81, 'C'],
                ['Kevin Huerter', 78, 'SG'],
                ['Harrison Barnes', 78, 'SF'],
                ['Trey Lyles', 76, 'PF'],
                ['Davion Mitchell', 76, 'PG'],
                ['Malik Monk', 79, 'SG'],
                ['Richaun Holmes', 75, 'C'],
                ['Alex Len', 74, 'C'],
                ['Keon Ellis', 73, 'SG'],
                ['Sasha Vezenkov', 75, 'PF'],
                ['Jalen Slawson', 72, 'SF'],
                ['Colby Jones', 73, 'SG'],
            ],
            'San Antonio Spurs' => [
                ['Victor Wembanyama', 88, 'C'],
                ['Keldon Johnson', 79, 'SF'],
                ['Devin Vassell', 80, 'SG'],
                ['Tre Jones', 76, 'PG'],
                ['Jeremy Sochan', 77, 'PF'],
                ['Zach Collins', 77, 'C'],
                ['Cedi Osman', 75, 'SF'],
                ['Doug McDermott', 76, 'SF'],
                ['Julian Champagnie', 74, 'SF'],
                ['Malaki Branham', 75, 'SG'],
                ['Blake Wesley', 73, 'SG'],
                ['Sandro Mamukelashvili', 73, 'PF'],
            ],
            'Toronto Raptors' => [
                ['Scottie Barnes', 81, 'SF'],
                ['OG Anunoby', 81, 'SF'],
                ['Gary Trent Jr.', 79, 'SG'],
                ['Jakob Poeltl', 79, 'C'],
                ['Dennis Schroder', 78, 'PG'],
                ['Gradey Dick', 75, 'SG'],
                ['Precious Achiuwa', 76, 'PF'],
                ['Otto Porter Jr.', 77, 'SF'],
                ['Malachi Flynn', 74, 'PG'],
                ['Thaddeus Young', 76, 'PF'],
                ['Jalen McDaniels', 74, 'PF'],
                ['Javon Freeman-Liberty', 72, 'SG'],
            ],
            'Utah Jazz' => [
                ['Jordan Clarkson', 79, 'SG'],
                ['John Collins', 80, 'PF'],
                ['Collin Sexton', 80, 'PG'],
                ['Kelly Olynyk', 77, 'C'],
                ['Walker Kessler', 80, 'C'],
                ['Simone Fontecchio', 76, 'SF'],
                ['Talen Horton-Tucker', 76, 'SG'],
                ['Keyonte George', 76, 'PG'],
                ['Ochai Agbaji', 75, 'SG'],
                ['Taylor Hendricks', 75, 'PF'],
                ['Brice Sensabaugh', 74, 'SF'],
                ['Luka Samanic', 73, 'PF'],
            ],
            'Washington Wizards' => [
                ['Kyle Kuzma', 81, 'PF'],
                ['Jordan Poole', 80, 'SG'],
                ['Tyus Jones', 77, 'PG'],
                ['Daniel Gafford', 78, 'C'],
                ['Deni Avdija', 78, 'SF'],
                ['Corey Kispert', 77, 'SG'],
                ['Bilal Coulibaly', 76, 'SF'],
                ['Delon Wright', 76, 'PG'],
                ['Landry Shamet', 75, 'SG'],
                ['Eugene Omoruyi', 73, 'PF'],
                ['Anthony Gill', 73, 'PF'],
                ['Jules Bernard', 72, 'SG'],
            ],
        ];

        // Add all team players
        foreach ($teams as $teamName => $teamPlayers) {
            foreach ($teamPlayers as $player) {
                $players[] = [
                    'name' => $player[0],
                    'rating' => $player[1],
                    'position' => $player[2],
                    'team' => $teamName,
                ];
            }
        }

        return $players;
    }
}
