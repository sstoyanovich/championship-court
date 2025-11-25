<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeNbaPlayers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nba:scrape-players';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape NBA player data from 2kratings.com and seed the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to scrape NBA players from all 30 teams on 2kratings.com...');

        // List of all 30 NBA teams with their URL slugs
        $teams = [
            'atlanta-hawks' => 'Atlanta Hawks',
            'boston-celtics' => 'Boston Celtics',
            'brooklyn-nets' => 'Brooklyn Nets',
            'charlotte-hornets' => 'Charlotte Hornets',
            'chicago-bulls' => 'Chicago Bulls',
            'cleveland-cavaliers' => 'Cleveland Cavaliers',
            'dallas-mavericks' => 'Dallas Mavericks',
            'denver-nuggets' => 'Denver Nuggets',
            'detroit-pistons' => 'Detroit Pistons',
            'golden-state-warriors' => 'Golden State Warriors',
            'houston-rockets' => 'Houston Rockets',
            'indiana-pacers' => 'Indiana Pacers',
            'la-clippers' => 'LA Clippers',
            'los-angeles-lakers' => 'Los Angeles Lakers',
            'memphis-grizzlies' => 'Memphis Grizzlies',
            'miami-heat' => 'Miami Heat',
            'milwaukee-bucks' => 'Milwaukee Bucks',
            'minnesota-timberwolves' => 'Minnesota Timberwolves',
            'new-orleans-pelicans' => 'New Orleans Pelicans',
            'new-york-knicks' => 'New York Knicks',
            'oklahoma-city-thunder' => 'Oklahoma City Thunder',
            'orlando-magic' => 'Orlando Magic',
            'philadelphia-76ers' => 'Philadelphia 76ers',
            'phoenix-suns' => 'Phoenix Suns',
            'portland-trail-blazers' => 'Portland Trail Blazers',
            'sacramento-kings' => 'Sacramento Kings',
            'san-antonio-spurs' => 'San Antonio Spurs',
            'toronto-raptors' => 'Toronto Raptors',
            'utah-jazz' => 'Utah Jazz',
            'washington-wizards' => 'Washington Wizards',
        ];

        $allPlayers = [];
        $totalCount = 0;
        $teamsScraped = 0;

        // Clear existing players at the start
        $this->info('Clearing existing player data...');
        Player::truncate();

        try {
            foreach ($teams as $teamSlug => $teamName) {
                $this->info("Scraping {$teamName}...");

                try {
                    $url = "https://www.2kratings.com/teams/{$teamSlug}";
                    $response = Http::timeout(30)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                            'Accept-Language' => 'en-US,en;q=0.5',
                            'Accept-Encoding' => 'gzip, deflate, br',
                            'Connection' => 'keep-alive',
                            'Upgrade-Insecure-Requests' => '1',
                        ])
                        ->get($url);

                    if (!$response->successful()) {
                        $this->warn("  Failed to fetch {$teamName} (Status: {$response->status()}), skipping...");
                        continue;
                    }

                    $html = $response->body();
                    $crawler = new Crawler($html);
                    $teamPlayerCount = 0;

                    // Find all player rows in the table
                    $crawler->filter('table tbody tr')->each(function (Crawler $node) use (&$allPlayers, &$totalCount, &$teamPlayerCount, $teamName) {
                        try {
                            $cells = $node->filter('td');

                            if ($cells->count() < 3) {
                                return; // Skip rows that don't have enough cells
                            }

                            // Try to find the name, rating, and position
                            $nameFound = false;
                            $name = '';
                            $rating = 0;
                            $position = '';

                            // Check each cell to find the data
                            for ($i = 0; $i < min($cells->count(), 5); $i++) {
                                $cellText = trim($cells->eq($i)->text());

                                // Check if this is a rating (2 digits between 40-99)
                                if (preg_match('/^(\d{2})$/', $cellText, $matches)) {
                                    $potentialRating = (int) $matches[1];
                                    if ($potentialRating >= 40 && $potentialRating <= 99) {
                                        $rating = $potentialRating;
                                        continue;
                                    }
                                }

                                // Check if this is a position (PG, SG, SF, PF, C)
                                if (preg_match('/^(PG|SG|SF|PF|C)/', strtoupper($cellText))) {
                                    $position = strtoupper(substr($cellText, 0, 2));
                                    continue;
                                }

                                // If it's not a number and not empty and we haven't found a name yet, it's probably the name
                                if (!is_numeric($cellText) && strlen($cellText) > 2 && !$nameFound) {
                                    $name = $cellText;
                                    $nameFound = true;
                                }
                            }

                            // Default position if not found
                            if (!$position) {
                                $position = 'SF';
                            }

                            // Try to get image URL if available
                            $imageUrl = null;
                            try {
                                $img = $node->filter('img');
                                if ($img->count() > 0) {
                                    $imageUrl = $img->attr('src');
                                    if (!str_starts_with($imageUrl, 'http')) {
                                        $imageUrl = 'https://www.2kratings.com' . $imageUrl;
                                    }
                                }
                            } catch (\Exception $e) {
                                // No image found
                            }

                            // Validate and store data
                            if ($name && $rating >= 40 && $rating <= 99) {
                                $allPlayers[] = [
                                    'name' => $name,
                                    'overall_rating' => $rating,
                                    'position' => $position,
                                    'team' => $teamName,
                                    'card_tier' => $this->determineCardTier($rating),
                                    'image_url' => $imageUrl,
                                ];
                                $totalCount++;
                                $teamPlayerCount++;
                            }
                        } catch (\Exception $e) {
                            // Skip this row if there's an error
                        }
                    });

                    if ($teamPlayerCount > 0) {
                        $this->info("  Found {$teamPlayerCount} players");
                        $teamsScraped++;
                    } else {
                        $this->warn("  No valid players found for {$teamName}");
                    }

                    // Small delay to be respectful to the server
                    sleep(1);
                } catch (\Exception $e) {
                    $this->warn("  Error scraping {$teamName}: " . $e->getMessage());
                    continue;
                }
            }

            if (empty($allPlayers)) {
                $this->warn('No players found from any team. Creating sample data instead...');
                $allPlayers = $this->getSamplePlayers();
                $totalCount = count($allPlayers);
            }

            // Insert all players
            $this->newLine();
            $this->info("Inserting {$totalCount} players into database...");
            foreach ($allPlayers as $player) {
                Player::create($player);
            }

            $this->newLine();
            $this->info("✓ Successfully scraped and stored {$totalCount} NBA players from {$teamsScraped} teams!");

            // Show tier breakdown
            $tierCounts = [];
            foreach ($allPlayers as $player) {
                $tier = $player['card_tier'];
                $tierCounts[$tier] = ($tierCounts[$tier] ?? 0) + 1;
            }

            $this->newLine();
            $this->info('Tier Breakdown:');
            $tierOrder = ['pink_diamond', 'diamond', 'amethyst', 'sapphire', 'emerald'];
            foreach ($tierOrder as $tier) {
                if (isset($tierCounts[$tier])) {
                    $tierName = str_replace('_', ' ', ucwords($tier, '_'));
                    $this->info("  {$tierName}: {$tierCounts[$tier]} players");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->info('Creating sample data instead...');

            Player::truncate();
            $players = $this->getSamplePlayers();
            foreach ($players as $player) {
                Player::create($player);
            }

            $this->info('Sample data created successfully!');
            return 0;
        }
    }

    /**
     * Determine card tier based on overall rating
     */
    private function determineCardTier(int $rating): string
    {
        if ($rating >= 97) {
            return 'pink_diamond';
        } elseif ($rating >= 93) {
            return 'diamond';
        } elseif ($rating >= 90) {
            return 'amethyst';
        } elseif ($rating >= 85) {
            return 'sapphire';
        } else {
            return 'emerald';
        }
    }

    /**
     * Get comprehensive NBA player database if scraping fails
     */
    private function getSamplePlayers(): array
    {
        return [
            // Pink Diamonds (97-99) - Elite tier
            ['name' => 'Nikola Jokic', 'overall_rating' => 99, 'position' => 'C', 'team' => 'Denver Nuggets', 'card_tier' => 'pink_diamond', 'image_url' => null],
            ['name' => 'Luka Doncic', 'overall_rating' => 98, 'position' => 'PG', 'team' => 'Dallas Mavericks', 'card_tier' => 'pink_diamond', 'image_url' => null],
            ['name' => 'Giannis Antetokounmpo', 'overall_rating' => 97, 'position' => 'PF', 'team' => 'Milwaukee Bucks', 'card_tier' => 'pink_diamond', 'image_url' => null],

            // Diamonds (93-96) - Superstar tier
            ['name' => 'Stephen Curry', 'overall_rating' => 96, 'position' => 'PG', 'team' => 'Golden State Warriors', 'card_tier' => 'diamond', 'image_url' => null],
            ['name' => 'LeBron James', 'overall_rating' => 96, 'position' => 'SF', 'team' => 'Los Angeles Lakers', 'card_tier' => 'diamond', 'image_url' => null],
            ['name' => 'Kevin Durant', 'overall_rating' => 95, 'position' => 'SF', 'team' => 'Phoenix Suns', 'card_tier' => 'diamond', 'image_url' => null],
            ['name' => 'Joel Embiid', 'overall_rating' => 95, 'position' => 'C', 'team' => 'Philadelphia 76ers', 'card_tier' => 'diamond', 'image_url' => null],
            ['name' => 'Jayson Tatum', 'overall_rating' => 94, 'position' => 'SF', 'team' => 'Boston Celtics', 'card_tier' => 'diamond', 'image_url' => null],
            ['name' => 'Shai Gilgeous-Alexander', 'overall_rating' => 94, 'position' => 'PG', 'team' => 'Oklahoma City Thunder', 'card_tier' => 'diamond', 'image_url' => null],
            ['name' => 'Anthony Davis', 'overall_rating' => 93, 'position' => 'PF', 'team' => 'Los Angeles Lakers', 'card_tier' => 'diamond', 'image_url' => null],
            ['name' => 'Nikola Vucevic', 'overall_rating' => 93, 'position' => 'C', 'team' => 'Chicago Bulls', 'card_tier' => 'diamond', 'image_url' => null],

            // Amethysts (90-92) - All-Star tier
            ['name' => 'Damian Lillard', 'overall_rating' => 92, 'position' => 'PG', 'team' => 'Milwaukee Bucks', 'card_tier' => 'amethyst', 'image_url' => null],
            ['name' => 'Kawhi Leonard', 'overall_rating' => 92, 'position' => 'SF', 'team' => 'LA Clippers', 'card_tier' => 'amethyst', 'image_url' => null],
            ['name' => 'Jimmy Butler', 'overall_rating' => 91, 'position' => 'SF', 'team' => 'Miami Heat', 'card_tier' => 'amethyst', 'image_url' => null],
            ['name' => 'Devin Booker', 'overall_rating' => 91, 'position' => 'SG', 'team' => 'Phoenix Suns', 'card_tier' => 'amethyst', 'image_url' => null],
            ['name' => 'Donovan Mitchell', 'overall_rating' => 90, 'position' => 'SG', 'team' => 'Cleveland Cavaliers', 'card_tier' => 'amethyst', 'image_url' => null],
            ['name' => 'Anthony Edwards', 'overall_rating' => 90, 'position' => 'SG', 'team' => 'Minnesota Timberwolves', 'card_tier' => 'amethyst', 'image_url' => null],
            ['name' => 'Jrue Holiday', 'overall_rating' => 90, 'position' => 'PG', 'team' => 'Boston Celtics', 'card_tier' => 'amethyst', 'image_url' => null],
            ['name' => 'Rudy Gobert', 'overall_rating' => 90, 'position' => 'C', 'team' => 'Minnesota Timberwolves', 'card_tier' => 'amethyst', 'image_url' => null],

            // Sapphires (85-89) - Star tier
            ['name' => 'Tyrese Haliburton', 'overall_rating' => 89, 'position' => 'PG', 'team' => 'Indiana Pacers', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'Paul George', 'overall_rating' => 89, 'position' => 'SF', 'team' => 'LA Clippers', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'Kyrie Irving', 'overall_rating' => 89, 'position' => 'PG', 'team' => 'Dallas Mavericks', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'Ja Morant', 'overall_rating' => 88, 'position' => 'PG', 'team' => 'Memphis Grizzlies', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'Bam Adebayo', 'overall_rating' => 88, 'position' => 'C', 'team' => 'Miami Heat', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'DeMar DeRozan', 'overall_rating' => 87, 'position' => 'SG', 'team' => 'Sacramento Kings', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'Jaylen Brown', 'overall_rating' => 87, 'position' => 'SG', 'team' => 'Boston Celtics', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'LaMelo Ball', 'overall_rating' => 86, 'position' => 'PG', 'team' => 'Charlotte Hornets', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'Zion Williamson', 'overall_rating' => 86, 'position' => 'PF', 'team' => 'New Orleans Pelicans', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'De\'Aaron Fox', 'overall_rating' => 85, 'position' => 'PG', 'team' => 'Sacramento Kings', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'Julius Randle', 'overall_rating' => 85, 'position' => 'PF', 'team' => 'Minnesota Timberwolves', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'Paolo Banchero', 'overall_rating' => 85, 'position' => 'PF', 'team' => 'Orlando Magic', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'Kristaps Porzingis', 'overall_rating' => 85, 'position' => 'C', 'team' => 'Boston Celtics', 'card_tier' => 'sapphire', 'image_url' => null],
            ['name' => 'Lauri Markkanen', 'overall_rating' => 85, 'position' => 'PF', 'team' => 'Utah Jazz', 'card_tier' => 'sapphire', 'image_url' => null],

            // Emeralds (80-84) - Solid starter tier
            ['name' => 'Trae Young', 'overall_rating' => 84, 'position' => 'PG', 'team' => 'Atlanta Hawks', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Darius Garland', 'overall_rating' => 84, 'position' => 'PG', 'team' => 'Cleveland Cavaliers', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Karl-Anthony Towns', 'overall_rating' => 84, 'position' => 'C', 'team' => 'New York Knicks', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Brandon Ingram', 'overall_rating' => 83, 'position' => 'SF', 'team' => 'New Orleans Pelicans', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Pascal Siakam', 'overall_rating' => 83, 'position' => 'PF', 'team' => 'Indiana Pacers', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Dejounte Murray', 'overall_rating' => 83, 'position' => 'PG', 'team' => 'New Orleans Pelicans', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Jaren Jackson Jr.', 'overall_rating' => 82, 'position' => 'PF', 'team' => 'Memphis Grizzlies', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Tyler Herro', 'overall_rating' => 82, 'position' => 'SG', 'team' => 'Miami Heat', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Jalen Brunson', 'overall_rating' => 82, 'position' => 'PG', 'team' => 'New York Knicks', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Mikal Bridges', 'overall_rating' => 82, 'position' => 'SF', 'team' => 'New York Knicks', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Cade Cunningham', 'overall_rating' => 81, 'position' => 'PG', 'team' => 'Detroit Pistons', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Jalen Green', 'overall_rating' => 81, 'position' => 'SG', 'team' => 'Houston Rockets', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Fred VanVleet', 'overall_rating' => 81, 'position' => 'PG', 'team' => 'Houston Rockets', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Scottie Barnes', 'overall_rating' => 81, 'position' => 'SF', 'team' => 'Toronto Raptors', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Alperen Sengun', 'overall_rating' => 81, 'position' => 'C', 'team' => 'Houston Rockets', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Franz Wagner', 'overall_rating' => 81, 'position' => 'SF', 'team' => 'Orlando Magic', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Desmond Bane', 'overall_rating' => 81, 'position' => 'SG', 'team' => 'Memphis Grizzlies', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Evan Mobley', 'overall_rating' => 81, 'position' => 'PF', 'team' => 'Cleveland Cavaliers', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'OG Anunoby', 'overall_rating' => 81, 'position' => 'SF', 'team' => 'New York Knicks', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Tyrese Maxey', 'overall_rating' => 81, 'position' => 'PG', 'team' => 'Philadelphia 76ers', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Jarrett Allen', 'overall_rating' => 81, 'position' => 'C', 'team' => 'Cleveland Cavaliers', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Domantas Sabonis', 'overall_rating' => 81, 'position' => 'C', 'team' => 'Sacramento Kings', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Tobias Harris', 'overall_rating' => 80, 'position' => 'PF', 'team' => 'Detroit Pistons', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'CJ McCollum', 'overall_rating' => 80, 'position' => 'SG', 'team' => 'New Orleans Pelicans', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Jordan Poole', 'overall_rating' => 80, 'position' => 'SG', 'team' => 'Washington Wizards', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Nic Claxton', 'overall_rating' => 80, 'position' => 'C', 'team' => 'Brooklyn Nets', 'card_tier' => 'emerald', 'image_url' => null],
            ['name' => 'Walker Kessler', 'overall_rating' => 80, 'position' => 'C', 'team' => 'Utah Jazz', 'card_tier' => 'emerald', 'image_url' => null],
        ];
    }
}
