const puppeteer = require('puppeteer');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const DB_PATH = path.join(__dirname, '../database/database.sqlite');

// Problem teams
const PROBLEM_TEAMS = {
    'Brooklyn Nets': 'https://www.2kratings.com/teams/brooklyn-nets',
    'Denver Nuggets': 'https://www.2kratings.com/teams/denver-nuggets',
    'Philadelphia 76ers': 'https://www.2kratings.com/teams/philadelphia-76ers',
    'Sacramento Kings': 'https://www.2kratings.com/teams/sacramento-kings',
    'San Antonio Spurs': 'https://www.2kratings.com/teams/san-antonio-spurs'
};

// Get collection ID
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

// Get card tier
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

// Alternative scraping strategy - try multiple approaches
async function scrapeTeamPlayersRobust(page, teamName, teamUrl) {
    try {
        console.log(`  🔍 Loading ${teamName}...`);
        await page.goto(teamUrl, { 
            waitUntil: 'domcontentloaded',
            timeout: 90000 
        });
        
        // Wait longer for dynamic content
        await page.waitForTimeout(8000);
        
        // Scroll to trigger lazy loading
        await page.evaluate(() => {
            window.scrollTo(0, document.body.scrollHeight);
        });
        await page.waitForTimeout(2000);
        
        const players = await page.evaluate((teamName) => {
            const players = [];
            
            // Strategy 1: Look for ANY table with player data
            const allTables = document.querySelectorAll('table');
            console.log(`Found ${allTables.length} tables total`);
            
            allTables.forEach((table, tableIndex) => {
                const rows = table.querySelectorAll('tr');
                console.log(`Table ${tableIndex}: ${rows.length} rows`);
                
                rows.forEach((row, rowIndex) => {
                    try {
                        const cells = Array.from(row.querySelectorAll('td'));
                        
                        // Skip if not enough cells
                        if (cells.length < 3) return;
                        
                        // Look for patterns: number/jersey, name, rating
                        for (let i = 0; i < cells.length - 2; i++) {
                            const cell1 = cells[i];
                            const cell2 = cells[i + 1];
                            const cell3 = cells[i + 2];
                            
                            // Check if cell3 looks like a rating (60-99)
                            const ratingSpan = cell3.querySelector('span[data-order]');
                            let possibleRating = null;
                            
                            if (ratingSpan) {
                                possibleRating = parseInt(parseFloat(ratingSpan.getAttribute('data-order')));
                            } else {
                                const text = cell3.textContent.trim();
                                possibleRating = parseInt(text);
                            }
                            
                            if (possibleRating >= 60 && possibleRating <= 99) {
                                // This might be a player row!
                                let name = cell2.textContent.trim();
                                
                                // Try to extract clean name
                                // Pattern: "16   Trae Young     PG | 6'1" | ..."
                                const nameMatch = name.match(/^\d*\s*([\w\s''\.\-ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ]+?)(?:\s{2,}|\s+[A-Z]{1,3}\s*\||\s+\d+\s*$)/);
                                if (nameMatch) {
                                    name = nameMatch[1].trim();
                                } else {
                                    // Fallback: just take first words before position/stats
                                    name = name.split(/\s{2,}|[A-Z]{2,}\s*\|/)[0].trim();
                                    // Remove leading numbers/jersey
                                    name = name.replace(/^\d+\s+/, '').trim();
                                }
                                
                                if (name && name.length >= 2 && !name.match(/^(No\.|#|Player|OVR|Position)/i)) {
                                    // Check for duplicates in this batch
                                    const isDuplicate = players.some(p => 
                                        p.name === name && p.overall_rating === possibleRating
                                    );
                                    
                                    if (!isDuplicate) {
                                        // Try to get image
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
                                            overall_rating: possibleRating,
                                            position: 'G',
                                            team: teamName,
                                            image_url: imageUrl
                                        });
                                        
                                        console.log(`Found: ${name} (${possibleRating})`);
                                    }
                                }
                                break; // Found a player in this row, move to next row
                            }
                        }
                    } catch (e) {
                        // Skip problematic rows
                    }
                });
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
    console.log('Scraping Problem Teams with Enhanced Detection');
    console.log('='.repeat(80));
    console.log();
    
    const db = new sqlite3.Database(DB_PATH);
    console.log('✓ Database connected');
    console.log();
    
    console.log('🏀 Scraping 5 problem teams with robust detection...');
    console.log();
    
    // Launch browser with more permissive settings
    const browser = await puppeteer.launch({ 
        headless: true,
        args: [
            '--no-sandbox', 
            '--disable-setuid-sandbox',
            '--disable-blink-features=AutomationControlled'
        ]
    });
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    
    // Enable console logging from page
    page.on('console', msg => {
        const text = msg.text();
        if (text.includes('Found') || text.includes('Table')) {
            console.log(`    BROWSER: ${text}`);
        }
    });
    
    console.log('✓ Browser ready');
    console.log();
    
    let totalPlayers = 0;
    const teamNames = Object.keys(PROBLEM_TEAMS);
    
    for (let i = 0; i < teamNames.length; i++) {
        const teamName = teamNames[i];
        const teamUrl = PROBLEM_TEAMS[teamName];
        
        console.log(`[${i + 1}/${teamNames.length}] ${teamName}`);
        
        const players = await scrapeTeamPlayersRobust(page, teamName, teamUrl);
        
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
            console.log(`      ⚠️  No players found - may need manual review`);
        }
        
        console.log();
        
        // Longer delay between teams
        await page.waitForTimeout(3000);
    }
    
    await browser.close();
    db.close();
    
    console.log('='.repeat(80));
    console.log('✓ Scraping complete!');
    console.log(`   Total players added: ${totalPlayers}`);
    console.log('='.repeat(80));
}

main().catch(console.error);

