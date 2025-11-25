<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\Player;
use Illuminate\Database\Seeder;

class UpdateTeamAffinityRewardsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🎯 Updating Team Affinity Program Rewards...\n\n";

        $teamRewards = [
            'Atlanta Hawks' => [
                ['name' => 'Kyle Korver', 'overall' => 79, 'stars' => 5],
                ['name' => 'Jeff Teague', 'overall' => 84, 'stars' => 25],
                ['name' => 'Dominique Wilkins', 'overall' => 85, 'stars' => 55],
                ['name' => 'Kristaps Porziņģis', 'overall' => 91, 'stars' => 135],
                ['name' => 'Dejounte Murray', 'overall' => 99, 'stars' => 285],
            ],
            'Boston Celtics' => [
                ['name' => 'Danny Ainge', 'overall' => 79, 'stars' => 5],
                ['name' => 'Kelly Olynyk', 'overall' => 84, 'stars' => 25],
                ['name' => 'Kevin McHale', 'overall' => 85, 'stars' => 55],
                ['name' => 'Derrick White', 'overall' => 91, 'stars' => 135],
                ['name' => 'Jaylen Brown', 'overall' => 99, 'stars' => 285],
            ],
            'Brooklyn Nets' => [
                ['name' => 'Kerry Kittles', 'overall' => 79, 'stars' => 5],
                ['name' => 'Joe Harris', 'overall' => 84, 'stars' => 25],
                ['name' => 'Egor Demin', 'overall' => 85, 'stars' => 55],
                ['name' => 'Nic Claxton', 'overall' => 91, 'stars' => 135],
                ['name' => 'Cam Thomas', 'overall' => 99, 'stars' => 285],
            ],
            'Charlotte Hornets' => [
                ['name' => 'LaMelo Ball', 'overall' => 79, 'stars' => 5],
                ['name' => 'Jamal Mashburn', 'overall' => 84, 'stars' => 25],
                ['name' => 'Baron Davis', 'overall' => 85, 'stars' => 55],
                ['name' => 'Kon Knueppel', 'overall' => 91, 'stars' => 135],
                ['name' => 'Brandon Miller', 'overall' => 99, 'stars' => 285],
            ],
            'Chicago Bulls' => [
                ['name' => 'Steve Kerr', 'overall' => 79, 'stars' => 5],
                ['name' => 'Lonzo Ball', 'overall' => 84, 'stars' => 25],
                ['name' => 'Artis Gilmore', 'overall' => 85, 'stars' => 55],
                ['name' => 'Josh Giddey', 'overall' => 91, 'stars' => 135],
                ['name' => 'Coby White', 'overall' => 99, 'stars' => 285],
            ],
            'Cleveland Cavaliers' => [
                ['name' => 'Kyrie Irving', 'overall' => 79, 'stars' => 5],
                ['name' => 'Ty Jerome', 'overall' => 84, 'stars' => 25],
                ['name' => 'Zydrunas Ilgauskas', 'overall' => 85, 'stars' => 55],
                ['name' => 'Jarrett Allen', 'overall' => 91, 'stars' => 135],
                ['name' => 'Evan Mobley', 'overall' => 99, 'stars' => 285],
            ],
            'Dallas Mavericks' => [
                ['name' => 'Josh Green', 'overall' => 79, 'stars' => 5],
                ['name' => 'Derek Harper', 'overall' => 84, 'stars' => 25],
                ['name' => 'Jason Kidd', 'overall' => 85, 'stars' => 55],
                ['name' => 'Dereck Lively II', 'overall' => 91, 'stars' => 135],
                ['name' => 'Cooper Flagg', 'overall' => 99, 'stars' => 285],
            ],
            'Denver Nuggets' => [
                ['name' => 'Christian Braun', 'overall' => 79, 'stars' => 5],
                ['name' => 'Danilo Gallinari', 'overall' => 84, 'stars' => 25],
                ['name' => 'Alex English', 'overall' => 85, 'stars' => 55],
                ['name' => 'Aaron Gordon', 'overall' => 91, 'stars' => 135],
                ['name' => 'Jamal Murray', 'overall' => 99, 'stars' => 285],
            ],
            'Detroit Pistons' => [
                ['name' => 'Ausar Thompson', 'overall' => 79, 'stars' => 5],
                ['name' => 'Saddiq Bey', 'overall' => 84, 'stars' => 25],
                ['name' => 'Chauncey Billups', 'overall' => 85, 'stars' => 55],
                ['name' => 'Jalen Duren', 'overall' => 91, 'stars' => 135],
                ['name' => 'Jaden Ivey', 'overall' => 99, 'stars' => 285],
            ],
            'Golden State Warriors' => [
                ['name' => 'Kevon Looney', 'overall' => 79, 'stars' => 5],
                ['name' => 'Harrison Barnes', 'overall' => 84, 'stars' => 25],
                ['name' => 'Tim Hardaway', 'overall' => 85, 'stars' => 55],
                ['name' => 'Jonathan Kuminga', 'overall' => 91, 'stars' => 135],
                ['name' => 'Klay Thompson', 'overall' => 99, 'stars' => 285],
            ],
            'Houston Rockets' => [
                ['name' => 'Cam Whitmore', 'overall' => 79, 'stars' => 5],
                ['name' => 'Dillon Brooks', 'overall' => 84, 'stars' => 25],
                ['name' => 'Tracy McGrady', 'overall' => 85, 'stars' => 55],
                ['name' => 'Alperen Şengün', 'overall' => 91, 'stars' => 135],
                ['name' => 'Jabari Smith Jr.', 'overall' => 99, 'stars' => 285],
            ],
            'Indiana Pacers' => [
                ['name' => 'Andrew Nembhard', 'overall' => 79, 'stars' => 5],
                ['name' => 'T.J. McConnell', 'overall' => 84, 'stars' => 25],
                ['name' => 'Jermaine O\'Neal', 'overall' => 85, 'stars' => 55],
                ['name' => 'Bennedict Mathurin', 'overall' => 91, 'stars' => 135],
                ['name' => 'Obi Toppin', 'overall' => 99, 'stars' => 285],
            ],
            'Los Angeles Clippers' => [
                ['name' => 'Bones Hyland', 'overall' => 79, 'stars' => 5],
                ['name' => 'Norman Powell', 'overall' => 84, 'stars' => 25],
                ['name' => 'Elton Brand', 'overall' => 85, 'stars' => 55],
                ['name' => 'Paul George', 'overall' => 91, 'stars' => 135],
                ['name' => 'Blake Griffin', 'overall' => 99, 'stars' => 285],
            ],
            'Los Angeles Lakers' => [
                ['name' => 'Austin Reaves', 'overall' => 79, 'stars' => 5],
                ['name' => 'D\'Angelo Russell', 'overall' => 84, 'stars' => 25],
                ['name' => 'Shaquille O\'Neal', 'overall' => 85, 'stars' => 55],
                ['name' => 'Rui Hachimura', 'overall' => 91, 'stars' => 135],
                ['name' => 'James Worthy', 'overall' => 99, 'stars' => 285],
            ],
            'Memphis Grizzlies' => [
                ['name' => 'GG Jackson', 'overall' => 79, 'stars' => 5],
                ['name' => 'Desmond Bane', 'overall' => 84, 'stars' => 25],
                ['name' => 'Zach Randolph', 'overall' => 85, 'stars' => 55],
                ['name' => 'Jaylen Wells', 'overall' => 91, 'stars' => 135],
                ['name' => 'Marc Gasol', 'overall' => 99, 'stars' => 285],
            ],
            'Miami Heat' => [
                ['name' => 'Michael Beasley', 'overall' => 79, 'stars' => 5],
                ['name' => 'P.J. Brown', 'overall' => 84, 'stars' => 25],
                ['name' => 'Dwyane Wade', 'overall' => 85, 'stars' => 55],
                ['name' => 'Nikola Jovic', 'overall' => 91, 'stars' => 135],
                ['name' => 'Chris Bosh', 'overall' => 99, 'stars' => 285],
            ],
            'Milwaukee Bucks' => [
                ['name' => 'A.J. Green', 'overall' => 79, 'stars' => 5],
                ['name' => 'Bobby Portis', 'overall' => 84, 'stars' => 25],
                ['name' => 'Michael Redd', 'overall' => 85, 'stars' => 55],
                ['name' => 'Ryan Rollins', 'overall' => 91, 'stars' => 135],
                ['name' => 'Oscar Robertson', 'overall' => 99, 'stars' => 285],
            ],
            'Minnesota Timberwolves' => [
                ['name' => 'Naz Reid', 'overall' => 79, 'stars' => 5],
                ['name' => 'Jaden McDaniels', 'overall' => 84, 'stars' => 25],
                ['name' => 'Kevin Garnett', 'overall' => 85, 'stars' => 55],
                ['name' => 'Rudy Gobert', 'overall' => 91, 'stars' => 135],
                ['name' => 'Karl-Anthony Towns', 'overall' => 99, 'stars' => 285],
            ],
            'New Orleans Pelicans' => [
                ['name' => 'Jordan Hawkins', 'overall' => 79, 'stars' => 5],
                ['name' => 'Herb Jones', 'overall' => 84, 'stars' => 25],
                ['name' => 'Chris Paul', 'overall' => 85, 'stars' => 55],
                ['name' => 'Trey Murphy III', 'overall' => 91, 'stars' => 135],
                ['name' => 'Brandon Ingram', 'overall' => 99, 'stars' => 285],
            ],
            'New York Knicks' => [
                ['name' => 'Quentin Grimes', 'overall' => 79, 'stars' => 5],
                ['name' => 'Immanuel Quickley', 'overall' => 84, 'stars' => 25],
                ['name' => 'Patrick Ewing', 'overall' => 85, 'stars' => 55],
                ['name' => 'Josh Hart', 'overall' => 91, 'stars' => 135],
                ['name' => 'OG Anunoby', 'overall' => 99, 'stars' => 285],
            ],
            'Oklahoma City Thunder' => [
                ['name' => 'Cason Wallace', 'overall' => 79, 'stars' => 5],
                ['name' => 'Luguentz Dort', 'overall' => 84, 'stars' => 25],
                ['name' => 'Shawn Kemp', 'overall' => 85, 'stars' => 55],
                ['name' => 'Alex Caruso', 'overall' => 91, 'stars' => 135],
                ['name' => 'Chet Holmgren', 'overall' => 99, 'stars' => 285],
            ],
            'Orlando Magic' => [
                ['name' => 'Anthony Black', 'overall' => 79, 'stars' => 5],
                ['name' => 'Franz Wagner', 'overall' => 84, 'stars' => 25],
                ['name' => 'Hedo Türkoğlu', 'overall' => 85, 'stars' => 55],
                ['name' => 'Jalen Suggs', 'overall' => 91, 'stars' => 135],
                ['name' => 'Penny Hardaway', 'overall' => 99, 'stars' => 285],
            ],
            'Philadelphia 76ers' => [
                ['name' => 'Ricky Council IV', 'overall' => 79, 'stars' => 5],
                ['name' => 'Nicolas Batum', 'overall' => 84, 'stars' => 25],
                ['name' => 'Allen Iverson', 'overall' => 85, 'stars' => 55],
                ['name' => 'V.J. Edgecombe', 'overall' => 91, 'stars' => 135],
                ['name' => 'Allen Iverson', 'overall' => 99, 'stars' => 285],
            ],
            'Phoenix Suns' => [
                ['name' => 'Bol Bol', 'overall' => 79, 'stars' => 5],
                ['name' => 'Jusuf Nurkić', 'overall' => 84, 'stars' => 25],
                ['name' => 'Amar\'e Stoudemire', 'overall' => 85, 'stars' => 55],
                ['name' => 'Jalen Green', 'overall' => 91, 'stars' => 135],
                ['name' => 'Steve Nash', 'overall' => 99, 'stars' => 285],
            ],
            'Portland Trail Blazers' => [
                ['name' => 'Scoot Henderson', 'overall' => 79, 'stars' => 5],
                ['name' => 'Anfernee Simons', 'overall' => 84, 'stars' => 25],
                ['name' => 'Brandon Roy', 'overall' => 85, 'stars' => 55],
                ['name' => 'Donovan Clingan', 'overall' => 91, 'stars' => 135],
                ['name' => 'Shaedon Sharpe', 'overall' => 99, 'stars' => 285],
            ],
            'Sacramento Kings' => [
                ['name' => 'Keon Ellis', 'overall' => 79, 'stars' => 5],
                ['name' => 'Kevin Huerter', 'overall' => 84, 'stars' => 25],
                ['name' => 'Chris Webber', 'overall' => 85, 'stars' => 55],
                ['name' => 'Russell Westbrook', 'overall' => 91, 'stars' => 135],
                ['name' => 'Keegan Murray', 'overall' => 99, 'stars' => 285],
            ],
            'San Antonio Spurs' => [
                ['name' => 'Malaki Branham', 'overall' => 79, 'stars' => 5],
                ['name' => 'Jeremy Sochan', 'overall' => 84, 'stars' => 25],
                ['name' => 'Manu Ginóbili', 'overall' => 85, 'stars' => 55],
                ['name' => 'Devin Vassell', 'overall' => 91, 'stars' => 135],
                ['name' => 'Keldon Johnson', 'overall' => 99, 'stars' => 285],
            ],
            'Toronto Raptors' => [
                ['name' => 'Gradey Dick', 'overall' => 79, 'stars' => 5],
                ['name' => 'Gary Trent Jr.', 'overall' => 84, 'stars' => 25],
                ['name' => 'Vince Carter', 'overall' => 85, 'stars' => 55],
                ['name' => 'Brandon Ingram', 'overall' => 91, 'stars' => 135],
                ['name' => 'RJ Barrett', 'overall' => 99, 'stars' => 285],
            ],
            'Utah Jazz' => [
                ['name' => 'Keyonte George', 'overall' => 79, 'stars' => 5],
                ['name' => 'Walker Kessler', 'overall' => 84, 'stars' => 25],
                ['name' => 'Karl Malone', 'overall' => 85, 'stars' => 55],
                ['name' => 'Kyle Filipowski', 'overall' => 91, 'stars' => 135],
                ['name' => 'Ace Bailey', 'overall' => 99, 'stars' => 285],
            ],
            'Washington Wizards' => [
                ['name' => 'Bilal Coulibaly', 'overall' => 79, 'stars' => 5],
                ['name' => 'Kyle Kuzma', 'overall' => 84, 'stars' => 25],
                ['name' => 'Gilbert Arenas', 'overall' => 85, 'stars' => 55],
                ['name' => 'Alex Sarr', 'overall' => 91, 'stars' => 135],
                ['name' => 'Corey Kispert', 'overall' => 99, 'stars' => 285],
            ],
        ];

        // Map team names to handle variations
        $teamNameMap = [
            'LA Clippers' => 'Los Angeles Clippers',
        ];

        $totalUpdated = 0;
        $notFound = [];

        foreach ($teamRewards as $team => $rewards) {
            // Find the program
            $program = Program::where('name', $team . ' Team Affinity')->first();
            
            // Try alternative names if not found
            if (!$program && isset($teamNameMap[$team])) {
                $altTeam = $teamNameMap[$team];
                $program = Program::where('name', $altTeam . ' Team Affinity')->first();
            }

            if (!$program) {
                echo "  ⚠ Program not found: {$team} Team Affinity\n";
                continue;
            }

            echo "Updating {$team}...\n";

            foreach ($rewards as $rewardData) {
                // Find the player
                $player = Player::where('name', $rewardData['name'])
                    ->where('overall_rating', $rewardData['overall'])
                    ->first();

                if (!$player) {
                    $notFound[] = "{$rewardData['name']} ({$rewardData['overall']} OVR) for {$team}";
                    echo "  ⚠ Player not found: {$rewardData['name']} ({$rewardData['overall']} OVR)\n";
                    continue;
                }

                // Find and update the reward at this star threshold
                $reward = ProgramReward::where('program_id', $program->id)
                    ->where('reward_type', 'player')
                    ->where('stars_threshold', $rewardData['stars'])
                    ->first();

                if ($reward) {
                    $reward->update([
                        'reward_id' => $player->id,
                        'description' => "{$rewardData['overall']} OVR {$rewardData['name']}",
                    ]);
                    $totalUpdated++;
                    echo "  ✓ Updated {$rewardData['stars']} stars: {$player->name} ({$player->overall_rating} OVR)\n";
                } else {
                    echo "  ⚠ Reward slot not found at {$rewardData['stars']} stars\n";
                }
            }
        }

        echo "\n✅ Team Affinity Rewards update complete!\n";
        echo "Total rewards updated: {$totalUpdated}\n";
        
        if (count($notFound) > 0) {
            echo "\n⚠ Players not found:\n";
            foreach ($notFound as $missing) {
                echo "  - {$missing}\n";
            }
        }
    }
}

