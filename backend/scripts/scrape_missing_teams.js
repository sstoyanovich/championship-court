const puppeteer = require('puppeteer');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const DB_PATH = path.join(__dirname, '../database/database.sqlite');

// All 30 NBA teams
const ALL_TEAMS = {
    'Atlanta Hawks': 'https://www.2kratings.com/teams/atlanta-hawks',
    'Boston Celtics': 'https://www.2kratings.com/teams/boston-celtics',
    'Brooklyn Nets': 'https://www.2kratings.com/teams/brooklyn-nets',
    'Charlotte Hornets': 'https://www.2kratings.com/teams/charlotte-hornets',
    'Chicago Bulls': 'https://www.2kratings.com/teams/chicago-bulls',
    'Cleveland Cavaliers': 'https://www.2kratings.com/teams/cleveland-cavaliers',
    'Dallas Mavericks': 'https://www.2kratings.com/teams/dallas-mavericks',
    'Denver Nuggets': 'https://www.2kratings.com/teams/denver-nuggets',
    'Detroit Pistons': 'https://www.2kratings.com/teams/detroit-pistons',
    'Golden State Warriors': 'https://www.2kratings.com/teams/golden-state-warriors',
    'Houston Rockets': 'https://www.2kratings.com/teams/houston-rockets',
    'Indiana Pacers': 'https://www.2kratings.com/teams/indiana-pacers',
    'Los Angeles Clippers': 'https://www.2kratings.com/teams/la-clippers',
    'Los Angeles Lakers': 'https://www.2kratings.com/teams/la-lakers',
    'Memphis Grizzlies': 'https://www.2kratings.com/teams/memphis-grizzlies',
    'Miami Heat': 'https://www.2kratings.com/teams/miami-heat',
    'Milwaukee Bucks': 'https://www.2kratings.com/teams/milwaukee-bucks',
    'Minnesota Timberwolves': 'https://www.2kratings.com/teams/minnesota-timberwolves',
    'New Orleans Pelicans': 'https://www.2kratings.com/teams/new-orleans-pelicans',
    'New York Knicks': 'https://www.2kratings.com/teams/new-york-knicks',
    'Oklahoma City Thunder': 'https://www.2kratings.com/teams/oklahoma-city-thunder',
    'Orlando Magic': 'https://www.2kratings.com/teams/orlando-magic',
    'Philadelphia 76ers': 'https://www.2kratings.com/teams/philadelphia-76ers',
    'Phoenix Suns': 'https://www.2kratings.com/teams/phoenix-suns',
    'Portland Trail Blazers': 'https://www.2kratings.com/teams/portland-trail-blazers',
    'Sacramento Kings': 'https://www.2kratings.com/teams/sacramento-kings',
    'San Antonio Spurs': 'https://www.2kratings.com/teams/san-antonio-spurs',
    'Toronto Raptors': 'https://www.2kratings.com/teams/toronto-raptors',
    'Utah Jazz': 'https://www.2kratings.com/teams/utah-jazz',
    'Washington Wizards': 'https://www.2kratings.com/teams/washington-wizards'
};

// Get teams already in database
async function getExistingTeams(db) {
    return new Promise((resolve, reject) => {
        db.all('SELECT DISTINCT team FROM players', (err, rows) => {
            if (err) reject(err);
            else resolve(rows.map(r => r.team));
        });
    });
}

// Get collection ID for team
async function getCollectionId(db, teamName) {
    return new Promise((resolve, reject) => {
        db.get(
            'SELECT id FROM collections WHERE sub_collection = ? AND type = "team"',
            [teamName],
            (err, row) => {
                if (err) reject(err);
                else resolve(row ? row.id : null);
            }
        );
    });
}

// Get card tier based on overall rating
function getCardTier(rating) {
    if (rating >= 99) return 'galaxy_opal';
    if (rating >= 96) return 'pink_diamond';
    if (rating >= 93) return 'diamond';
    if (rating >= 90) return 'amethyst';
    if (rating >= 88) return 'ruby';
    if (rating >= 85) return 'sapphire';
    if (rating >= 80) return 'emerald';
    if (rating >= 75) return 'gold';
    if (rating >= 72) return 'silver';
    if (rating >= 69) return 'bronze';
    return 'common';
}

// Scrape team players
async function scrapeTeamPlayers(page, teamName, teamUrl) {
    try {
        console.log(`  🔍 Loading ${teamName}...`);
        await page.goto(teamUrl, { 
            waitUntil: 'networkidle2',
            timeout: 60000 
        });
        
        await page.waitForTimeout(3000);
        
        const players = await page.evaluate((teamName) => {
            const players = [];
            const tables = document.querySelectorAll('table');
            
            // Find the current roster table (usually the one with the most rows)
            let targetTable = null;
            let maxRows = 0;
            
            tables.forEach(table => {
                const rows = table.querySelectorAll('tbody tr');
                if (rows.length > maxRows && rows.length > 5) {
                    targetTable = table;
                    maxRows = rows.length;
                }
            });
            
            if (!targetTable) return players;
            
            const rows = targetTable.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                try {
                    const cells = row.querySelectorAll('td');
                    if (cells.length < 3) return;
                    
                    const nameCell = cells[1];
                    const ratingCell = cells[2];
                    
                    if (!nameCell || !ratingCell) return;
                    
                    // Get player name
                    let name = nameCell.textContent.trim();
                    const nameMatch = name.match(/^\d+\s+([\w\s''\.\-ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ]+?)(?:\s{2,}|\s+[A-Z]{1,3}\s*\|)/);
                    if (nameMatch) {
                        name = nameMatch[1].trim();
                    }
                    
                    if (!name || name.length < 2) return;
                    
                    // Get overall rating
                    let rating = null;
                    const ratingSpan = ratingCell.querySelector('span[data-order]');
                    if (ratingSpan) {
                        rating = parseInt(parseFloat(ratingSpan.getAttribute('data-order')));
                    } else {
                        rating = parseInt(ratingCell.textContent.trim());
                    }
                    
                    if (!rating || isNaN(rating) || rating < 60 || rating > 99) return;
                    
                    // Get image URL
                    const imgElem = row.querySelector('img');
                    let imageUrl = null;
                    if (imgElem) {
                        imageUrl = imgElem.getAttribute('data-src') || 
                                   imgElem.getAttribute('data-lazy-src') || 
                                   imgElem.getAttribute('data-original') ||
                                   imgElem.src;
                        if (imageUrl && (imageUrl.includes('1x1.png') || imageUrl.includes('placeholder'))) {
                            imageUrl = null;
                        }
                    }
                    
                    players.push({
                        name,
                        overall_rating: rating,
                        position: 'G',
                        team: teamName,
                        image_url: imageUrl
                    });
                } catch (e) {
                    // Skip problematic rows
                }
            });
            
            return players;
        }, teamName);
        
        // Add card tier
        players.forEach(player => {
            player.card_tier = getCardTier(player.overall_rating);
        });
        
        console.log(`  ✓ Found ${players.length} players`);
        return players;
        
    } catch (error) {
        console.log(`  ❌ Error: ${error.message}`);
        return [];
    }
}

// Main function
async function main() {
    console.log('='.repeat(80));
    console.log('Scraping Missing NBA Teams');
    console.log('='.repeat(80));
    console.log();
    
    // Connect to database
    const db = new sqlite3.Database(DB_PATH);
    console.log('✓ Database connected');
    
    // Get existing teams
    const existingTeams = await getExistingTeams(db);
    console.log(`✓ Found ${existingTeams.length} teams already in database`);
    console.log();
    
    // Find missing teams
    const missingTeams = Object.keys(ALL_TEAMS).filter(team => !existingTeams.includes(team));
    
    if (missingTeams.length === 0) {
        console.log('✓ All teams are already in the database!');
        db.close();
        return;
    }
    
    console.log(`🏀 Scraping ${missingTeams.length} missing teams:`);
    missingTeams.forEach(team => console.log(`   - ${team}`));
    console.log();
    
    // Launch browser
    console.log('🌐 Launching browser...');
    const browser = await puppeteer.launch({ 
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    console.log('✓ Browser ready');
    console.log();
    
    let totalPlayers = 0;
    
    // Scrape each missing team
    for (let i = 0; i < missingTeams.length; i++) {
        const teamName = missingTeams[i];
        const teamUrl = ALL_TEAMS[teamName];
        
        console.log(`[${i + 1}/${missingTeams.length}] ${teamName}`);
        
        const players = await scrapeTeamPlayers(page, teamName, teamUrl);
        
        if (players.length > 0) {
            // Get collection ID
            const collectionId = await getCollectionId(db, teamName);
            
            // Save players
            for (const player of players) {
                await new Promise((resolve, reject) => {
                    db.run(
                        `INSERT INTO players (name, overall_rating, position, team, card_tier, image_url, collection_id, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))`,
                        [player.name, player.overall_rating, player.position, player.team, player.card_tier, player.image_url, collectionId],
                        (err) => {
                            if (err) reject(err);
                            else resolve();
                        }
                    );
                });
            }
            
            // Update collection total
            if (collectionId) {
                await new Promise((resolve, reject) => {
                    db.run(
                        'UPDATE collections SET total_items = ? WHERE id = ?',
                        [players.length, collectionId],
                        (err) => {
                            if (err) reject(err);
                            else resolve();
                        }
                    );
                });
            }
            
            totalPlayers += players.length;
            console.log(`      ✓ Saved ${players.length} players`);
        } else {
            console.log(`      ⚠️  No players found`);
        }
        
        console.log();
        
        // Delay between teams
        await page.waitForTimeout(2000);
    }
    
    await browser.close();
    db.close();
    
    console.log('='.repeat(80));
    console.log('✓ Scraping complete!');
    console.log(`   Total players added: ${totalPlayers}`);
    console.log('='.repeat(80));
}

main().catch(console.error);

