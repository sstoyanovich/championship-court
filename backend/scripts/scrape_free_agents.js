const puppeteer = require('puppeteer');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const DB_PATH = path.join(__dirname, '../database/database.sqlite');
const FREE_AGENCY_URL = 'https://www.2kratings.com/teams/free-agency';

/**
 * Get card tier based on overall rating
 */
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

/**
 * Get collection ID for Free Agent
 */
function getFreeAgentCollectionId(db) {
    return new Promise((resolve, reject) => {
        db.get(
            'SELECT id FROM collections WHERE type = ? OR sub_collection = ? LIMIT 1',
            ['free_agent', 'Free Agent'],
            (err, row) => {
                if (err) reject(err);
                else resolve(row ? row.id : null);
            }
        );
    });
}

/**
 * Check if player already exists
 */
function playerExists(db, name, team) {
    return new Promise((resolve, reject) => {
        db.get(
            'SELECT id FROM players WHERE name = ? AND team = ? LIMIT 1',
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
 * Scrape free agents from the page
 */
async function scrapeFreeAgents(page) {
    try {
        console.log(`  🔍 Loading Free Agency page...`);
        
        // Navigate to the page
        await page.goto(FREE_AGENCY_URL, {
            waitUntil: 'networkidle2',
            timeout: 90000
        });
        
        // Wait for the roster table to load
        await page.waitForSelector('table tbody tr', { timeout: 45000 });
        
        // Extract player data from the page
        const players = await page.evaluate(() => {
            const players = [];
            
            // Get all tables on the page
            const tables = document.querySelectorAll('table');
            
            console.log(`Found ${tables.length} tables on Free Agency page`);
            
            if (tables.length === 0) return players;
            
            // Find the first table with a decent number of rows (should be the free agent roster)
            let currentTable = null;
            for (let i = 0; i < Math.min(5, tables.length); i++) {
                const testRows = tables[i].querySelectorAll('tbody tr');
                console.log(`Table ${i}: ${testRows.length} rows`);
                if (testRows.length > 10) { // Free agents should have many players
                    currentTable = tables[i];
                    console.log(`Using table ${i} as free agent roster table`);
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
                    
                    if (cells.length < 3) return;
                    
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
                        team: 'Free Agent',
                        image_url: imageUrl
                    });
                } catch (e) {
                    console.log('Error processing row:', e.message);
                }
            });
            
            return players;
        });
        
        // Add card tier to each player
        players.forEach(player => {
            player.card_tier = getCardTier(player.overall_rating);
        });
        
        console.log(`  ✓ Found ${players.length} free agents`);
        return players;
        
    } catch (error) {
        console.log(`  ❌ Error: ${error.message}`);
        return [];
    }
}

/**
 * Main execution
 */
async function main() {
    console.log('='.repeat(80));
    console.log('2K Ratings Scraper - Free Agents');
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
        headless: true,
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
    
    console.log('🏀 Scraping Free Agents...');
    console.log();
    
    let totalImported = 0;
    let totalSkipped = 0;
    
    try {
        // Scrape free agents
        const players = await scrapeFreeAgents(page);
        
        if (players.length > 0) {
            // Get collection ID for Free Agent
            const collectionId = await getFreeAgentCollectionId(db);
            
            if (!collectionId) {
                console.log('❌ Free Agent collection not found in database!');
                console.log('   Please ensure the collection exists before running this script.');
                await browser.close();
                db.close();
                return;
            }
            
            console.log(`📦 Saving ${players.length} free agents to database...`);
            console.log();
            
            // Save each player
            for (const player of players) {
                try {
                    const exists = await playerExists(db, player.name, player.team);
                    if (!exists) {
                        await savePlayer(db, player, collectionId);
                        totalImported++;
                        if (totalImported % 20 === 0) {
                            console.log(`   Saved ${totalImported}/${players.length}...`);
                        }
                    } else {
                        totalSkipped++;
                    }
                } catch (err) {
                    console.log(`   ⚠️  Could not save ${player.name}: ${err.message}`);
                }
            }
            
            console.log();
            console.log(`✓ Import complete!`);
            console.log(`   Imported: ${totalImported}`);
            console.log(`   Skipped (already existed): ${totalSkipped}`);
        } else {
            console.log('⚠️  No free agents found');
        }
    } catch (error) {
        console.log(`❌ Failed: ${error.message}`);
    }
    
    console.log();
    
    // Update collection total
    console.log('📊 Updating Free Agent collection total...');
    await new Promise((resolve, reject) => {
        db.run(`
            UPDATE collections 
            SET total_items = (
                SELECT COUNT(*) FROM players 
                WHERE players.collection_id = collections.id
            )
            WHERE type = 'free_agent' OR sub_collection = 'Free Agent'
        `, (err) => {
            if (err) reject(err);
            else resolve();
        });
    });
    console.log('✓ Collection total updated');
    console.log();
    
    // Get tier breakdown for free agents
    const tierBreakdown = await new Promise((resolve, reject) => {
        db.all(`
            SELECT card_tier, COUNT(*) as count 
            FROM players 
            WHERE team = 'Free Agent'
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
        `, (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
    
    // Get total player count
    const totalPlayers = await new Promise((resolve, reject) => {
        db.get('SELECT COUNT(*) as count FROM players', (err, row) => {
            if (err) reject(err);
            else resolve(row.count);
        });
    });
    
    console.log('='.repeat(80));
    console.log('📊 Free Agent Tier Breakdown:');
    tierBreakdown.forEach(tier => {
        const tierName = tier.card_tier.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        console.log(`   ${tierName.padEnd(15)} ${tier.count.toString().padStart(3)}`);
    });
    console.log();
    console.log(`📊 Total Players in Database: ${totalPlayers}`);
    console.log('='.repeat(80));
    
    // Close browser and database
    await browser.close();
    db.close();
}

// Run the scraper
main().catch(console.error);

