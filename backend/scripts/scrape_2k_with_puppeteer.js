#!/usr/bin/env node

/**
 * Scrape NBA player ratings from 2kratings.com using Puppeteer
 * This handles Cloudflare protection by using a real browser
 */

const puppeteer = require('puppeteer');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');

// Database path
const DB_PATH = path.join(__dirname, '../database/database.sqlite');

// All 30 NBA teams with their 2kratings URLs
const NBA_TEAMS = {
    "Atlanta Hawks": "https://www.2kratings.com/teams/atlanta-hawks",
    "Boston Celtics": "https://www.2kratings.com/teams/boston-celtics",
    "Brooklyn Nets": "https://www.2kratings.com/teams/brooklyn-nets",
    "Charlotte Hornets": "https://www.2kratings.com/teams/charlotte-hornets",
    "Chicago Bulls": "https://www.2kratings.com/teams/chicago-bulls",
    "Cleveland Cavaliers": "https://www.2kratings.com/teams/cleveland-cavaliers",
    "Dallas Mavericks": "https://www.2kratings.com/teams/dallas-mavericks",
    "Denver Nuggets": "https://www.2kratings.com/teams/denver-nuggets",
    "Detroit Pistons": "https://www.2kratings.com/teams/detroit-pistons",
    "Golden State Warriors": "https://www.2kratings.com/teams/golden-state-warriors",
    "Houston Rockets": "https://www.2kratings.com/teams/houston-rockets",
    "Indiana Pacers": "https://www.2kratings.com/teams/indiana-pacers",
    "Los Angeles Clippers": "https://www.2kratings.com/teams/los-angeles-clippers",
    "Los Angeles Lakers": "https://www.2kratings.com/teams/los-angeles-lakers",
    "Memphis Grizzlies": "https://www.2kratings.com/teams/memphis-grizzlies",
    "Miami Heat": "https://www.2kratings.com/teams/miami-heat",
    "Milwaukee Bucks": "https://www.2kratings.com/teams/milwaukee-bucks",
    "Minnesota Timberwolves": "https://www.2kratings.com/teams/minnesota-timberwolves",
    "New Orleans Pelicans": "https://www.2kratings.com/teams/new-orleans-pelicans",
    "New York Knicks": "https://www.2kratings.com/teams/new-york-knicks",
    "Oklahoma City Thunder": "https://www.2kratings.com/teams/oklahoma-city-thunder",
    "Orlando Magic": "https://www.2kratings.com/teams/orlando-magic",
    "Philadelphia 76ers": "https://www.2kratings.com/teams/philadelphia-76ers",
    "Phoenix Suns": "https://www.2kratings.com/teams/phoenix-suns",
    "Portland Trail Blazers": "https://www.2kratings.com/teams/portland-trail-blazers",
    "Sacramento Kings": "https://www.2kratings.com/teams/sacramento-kings",
    "San Antonio Spurs": "https://www.2kratings.com/teams/san-antonio-spurs",
    "Toronto Raptors": "https://www.2kratings.com/teams/toronto-raptors",
    "Utah Jazz": "https://www.2kratings.com/teams/utah-jazz",
    "Washington Wizards": "https://www.2kratings.com/teams/washington-wizards",
};

/**
 * Get card tier based on overall rating
 */
function getCardTier(rating) {
    if (rating >= 97) return "pink_diamond";
    if (rating >= 93) return "diamond";
    if (rating >= 90) return "amethyst";
    if (rating >= 85) return "sapphire";
    if (rating >= 80) return "emerald";
    if (rating >= 75) return "gold";
    if (rating >= 70) return "silver";
    if (rating >= 65) return "bronze";
    return "common";
}

/**
 * Get collection ID for a team from database
 */
function getCollectionId(db, teamName) {
    return new Promise((resolve, reject) => {
        db.get(
            "SELECT id FROM collections WHERE sub_collection = ? AND type = 'team'",
            [teamName],
            (err, row) => {
                if (err) reject(err);
                else resolve(row ? row.id : null);
            }
        );
    });
}

/**
 * Check if player already exists in database
 */
function playerExists(db, name, team) {
    return new Promise((resolve, reject) => {
        db.get(
            "SELECT id FROM players WHERE name = ? AND team = ?",
            [name, team],
            (err, row) => {
                if (err) reject(err);
                else resolve(!!row);
            }
        );
    });
}

/**
 * Save player to database
 */
function savePlayer(db, playerData, collectionId) {
    return new Promise((resolve, reject) => {
        const sql = `
            INSERT INTO players (
                name, overall_rating, position, team, card_tier, image_url, collection_id,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        `;
        
        db.run(
            sql,
            [
                playerData.name,
                playerData.overall_rating,
                playerData.position,
                playerData.team,
                playerData.card_tier,
                playerData.image_url,
                collectionId
            ],
            (err) => {
                if (err) reject(err);
                else resolve();
            }
        );
    });
}

/**
 * Scrape players from a team page
 */
async function scrapeTeamPlayers(page, teamName, teamUrl) {
    try {
        console.log(`  🔍 Loading ${teamName}...`);
        
        // Navigate to the page
        await page.goto(teamUrl, {
            waitUntil: 'networkidle2',
            timeout: 60000 // 60 second timeout for Cloudflare
        });
        
        // Wait for the roster table to load
        await page.waitForSelector('table tbody tr', { timeout: 30000 });
        
        // Extract player data from the page
        const players = await page.evaluate((teamName) => {
            const players = [];
            
            // Get all tables on the page
            const tables = document.querySelectorAll('table');
            
            console.log(`Found ${tables.length} tables on ${teamName} page`);
            
            if (tables.length === 0) return players;
            
            // Find the first table with a decent number of rows (should be the current roster)
            let currentTable = null;
            for (let i = 0; i < Math.min(5, tables.length); i++) {
                const testRows = tables[i].querySelectorAll('tbody tr');
                console.log(`Table ${i}: ${testRows.length} rows`);
                if (testRows.length > 5) { // A valid roster should have multiple players
                    currentTable = tables[i];
                    console.log(`Using table ${i} as roster table`);
                    break;
                }
            }
            
            if (!currentTable) {
                currentTable = tables[0];
                console.log('No suitable table found, using first table');
            }
            
            const rows = currentTable.querySelectorAll('tbody tr');
            console.log(`Processing ${rows.length} rows`);
            
            let debugCount = 0;
            rows.forEach(row => {
                try {
                    const cells = row.querySelectorAll('td');
                    
                    // Debug first 2 rows
                    if (debugCount < 2) {
                        console.log(`Row ${debugCount}: ${cells.length} cells`);
                        for (let i = 0; i < Math.min(3, cells.length); i++) {
                            console.log(`  Cell ${i}: "${cells[i].textContent.trim().substring(0, 50)}"`);
                        }
                        debugCount++;
                    }
                    
                    if (cells.length < 3) return; // Need at least jersey, name, rating
                    
                    // Cell 0: Jersey number (skip)
                    // Cell 1: Player name
                    // Cell 2: Overall rating
                    
                    const nameCell = cells[1];
                    const ratingCell = cells[2];
                    
                    if (!nameCell || !ratingCell) return;
                    
                    // Get player name from format like: "16   Trae Young     PG | 6'1" | ..."
                    let name = nameCell.textContent.trim();
                    // Updated regex to better handle apostrophes and special characters
                    // Matches: jersey number, then name (with letters, spaces, apostrophes, periods, hyphens, and accented chars), then position or whitespace
                    const nameMatch = name.match(/^\d+\s+([\w\s''\.\-ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ]+?)(?:\s{2,}|\s+[A-Z]{1,3}\s*\|)/);
                    if (nameMatch) {
                        name = nameMatch[1].trim();
                    }
                    
                    if (!name || name.length < 2) {
                        console.log(`Skipping row: name too short "${name}"`);
                        return;
                    }
                    
                    // Get overall rating from span with data-order attribute or text content
                    let rating = null;
                    const ratingSpan = ratingCell.querySelector('span[data-order]');
                    if (ratingSpan) {
                        const dataOrder = ratingSpan.getAttribute('data-order');
                        rating = parseInt(parseFloat(dataOrder));
                    } else {
                        rating = parseInt(ratingCell.textContent.trim());
                    }
                    
                    if (!rating || isNaN(rating) || rating < 60 || rating > 99) {
                        console.log(`Skipping row: invalid rating ${rating}`);
                        return;
                    }
                    
                    // Default position to G for now
                    let position = 'G';
                    
                    // Try to find image URL from the row
                    // Images are lazy-loaded, so check data-src first, then data-lazy-src, then src
                    const imgElem = row.querySelector('img');
                    let imageUrl = null;
                    if (imgElem) {
                        imageUrl = imgElem.getAttribute('data-src') || 
                                   imgElem.getAttribute('data-lazy-src') || 
                                   imgElem.getAttribute('data-original') ||
                                   imgElem.src;
                        // If still a placeholder, set to null
                        if (imageUrl && (imageUrl.includes('1x1.png') || imageUrl.includes('placeholder'))) {
                            imageUrl = null;
                        }
                    }
                    
                    players.push({
                        name,
                        overall_rating: rating,
                        position: position.toUpperCase(),
                        team: teamName,
                        image_url: imageUrl
                    });
                } catch (e) {
                    // Skip problematic rows
                    console.log('Error processing row:', e);
                }
            });
            
            return players;
        }, teamName);
        
        // Add card tier to each player
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

/**
 * Main function
 */
async function main() {
    console.log('='.repeat(80));
    console.log('2K Ratings Scraper with Puppeteer - Import NBA Player Ratings');
    console.log('='.repeat(80));
    console.log();
    
    // Connect to database
    console.log('📁 Connecting to database...');
    const db = new sqlite3.Database(DB_PATH, (err) => {
        if (err) {
            console.error('❌ Could not connect to database:', err.message);
            process.exit(1);
        }
    });
    console.log('✓ Database connected');
    console.log();
    
    // Launch browser
    console.log('🌐 Launching browser...');
    const browser = await puppeteer.launch({
        headless: true, // Set to false to see the browser
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--disable-gpu',
        ]
    });
    
    const page = await browser.newPage();
    
    // Listen to console messages from the browser
    page.on('console', msg => console.log('    BROWSER:', msg.text()));
    
    // Set a realistic viewport and user agent
    await page.setViewport({ width: 1920, height: 1080 });
    await page.setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    
    console.log('✓ Browser ready');
    console.log();
    
    console.log(`🏀 Scraping ${Object.keys(NBA_TEAMS).length} NBA teams...`);
    console.log('   (This may take a few minutes due to Cloudflare protection)');
    console.log();
    
    let totalImported = 0;
    let totalSkipped = 0;
    let totalFailed = 0;
    const teams = Object.entries(NBA_TEAMS);
    
    for (let i = 0; i < teams.length; i++) {
        const [teamName, teamUrl] = teams[i];
        console.log(`[${i + 1}/${teams.length}] ${teamName}`);
        
        try {
            // Scrape players from team page
            const players = await scrapeTeamPlayers(page, teamName, teamUrl);
            
            if (players.length > 0) {
                // Get collection ID for this team
                const collectionId = await getCollectionId(db, teamName);
                
                // Save each player
                for (const player of players) {
                    try {
                        const exists = await playerExists(db, player.name, player.team);
                        if (!exists) {
                            await savePlayer(db, player, collectionId);
                            totalImported++;
                        } else {
                            totalSkipped++;
                        }
                    } catch (err) {
                        console.log(`      ⚠️  Could not save ${player.name}: ${err.message}`);
                    }
                }
                
                console.log(`      ✓ Imported ${players.length} players`);
            } else {
                console.log(`      ⚠️  No players found`);
                totalFailed++;
            }
            
            // Small delay between teams
            if (i < teams.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 3000));
            }
            
        } catch (error) {
            console.log(`      ❌ Failed: ${error.message}`);
            totalFailed++;
        }
        
        console.log();
    }
    
    // Update collection totals
    console.log('📊 Updating collection totals...');
    await new Promise((resolve, reject) => {
        db.run(`
            UPDATE collections 
            SET total_items = (
                SELECT COUNT(*) FROM players 
                WHERE players.collection_id = collections.id
            )
        `, (err) => {
            if (err) reject(err);
            else resolve();
        });
    });
    console.log('✓ Collection totals updated');
    
    // Show results
    console.log();
    console.log('='.repeat(80));
    console.log('✓ Import complete!');
    console.log(`  Total players imported: ${totalImported}`);
    console.log(`  Duplicates skipped: ${totalSkipped}`);
    console.log(`  Teams failed: ${totalFailed}`);
    
    // Show tier breakdown
    db.all(`
        SELECT card_tier, COUNT(*) as count 
        FROM players 
        GROUP BY card_tier 
        ORDER BY 
            CASE card_tier
                WHEN 'pink_diamond' THEN 1
                WHEN 'diamond' THEN 2
                WHEN 'amethyst' THEN 3
                WHEN 'sapphire' THEN 4
                WHEN 'emerald' THEN 5
                WHEN 'gold' THEN 6
                WHEN 'silver' THEN 7
                WHEN 'bronze' THEN 8
                WHEN 'common' THEN 9
            END
    `, [], (err, rows) => {
        if (!err && rows.length > 0) {
            console.log();
            console.log('📊 Tier Breakdown:');
            rows.forEach(row => {
                const tierName = row.card_tier.replace('_', ' ').split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
                console.log(`  ${tierName}: ${row.count} players`);
            });
        }
        
        console.log('='.repeat(80));
        
        // Close everything
        db.close();
        browser.close();
        process.exit(0);
    });
}

// Run the script
main().catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});

