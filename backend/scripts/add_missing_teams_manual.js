const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const DB_PATH = path.join(__dirname, '../database/database.sqlite');

// Missing teams data - manually curated from 2kratings.com
const MISSING_TEAMS_DATA = {
    'Denver Nuggets': [
        { name: 'Nikola Jokić', rating: 97, position: 'C' },
        { name: 'Jamal Murray', rating: 86, position: 'PG' },
        { name: 'Aaron Gordon', rating: 82, position: 'PF' },
        { name: 'Michael Porter Jr.', rating: 82, position: 'SF' },
        { name: 'Russell Westbrook', rating: 77, position: 'PG' },
        { name: 'Christian Braun', rating: 76, position: 'SG' },
        { name: 'Peyton Watson', rating: 75, position: 'SF' },
        { name: 'Julian Strawther', rating: 74, position: 'SG' },
        { name: 'Dario Šarić', rating: 74, position: 'PF' },
        { name: 'Zeke Nnaji', rating: 73, position: 'PF' },
        { name: 'Hunter Tyson', rating: 72, position: 'SF' },
        { name: 'Vlatko Čančar', rating: 71, position: 'SF' },
        { name: 'DeAndre Jordan', rating: 71, position: 'C' },
        { name: 'Jalen Pickett', rating: 70, position: 'PG' },
        { name: 'Trey Alexander', rating: 68, position: 'SG' },
        { name: 'Spencer Jones', rating: 67, position: 'SF' },
        { name: 'PJ Hall', rating: 67, position: 'PF' }
    ],
    'Philadelphia 76ers': [
        { name: 'Joel Embiid', rating: 96, position: 'C' },
        { name: 'Tyrese Maxey', rating: 90, position: 'PG' },
        { name: 'Paul George', rating: 88, position: 'SF' },
        { name: 'Caleb Martin', rating: 77, position: 'PF' },
        { name: 'Kelly Oubre Jr.', rating: 77, position: 'SF' },
        { name: 'Kyle Lowry', rating: 76, position: 'PG' },
        { name: 'Eric Gordon', rating: 75, position: 'SG' },
        { name: 'Andre Drummond', rating: 75, position: 'C' },
        { name: 'Reggie Jackson', rating: 74, position: 'PG' },
        { name: 'Guerschon Yabusele', rating: 73, position: 'PF' },
        { name: 'KJ Martin', rating: 72, position: 'PF' },
        { name: 'Ricky Council IV', rating: 71, position: 'SG' },
        { name: 'Adem Bona', rating: 70, position: 'C' },
        { name: 'Jared McCain', rating: 70, position: 'PG' },
        { name: 'Justin Edwards', rating: 68, position: 'SG' },
        { name: 'Jeff Dowtin Jr.', rating: 67, position: 'PG' },
        { name: 'Lester Quinones', rating: 66, position: 'SG' }
    ],
    'San Antonio Spurs': [
        { name: 'Victor Wembanyama', rating: 93, position: 'C' },
        { name: 'Devin Vassell', rating: 83, position: 'SG' },
        { name: 'Harrison Barnes', rating: 78, position: 'SF' },
        { name: 'Chris Paul', rating: 78, position: 'PG' },
        { name: 'Stephon Castle', rating: 76, position: 'SG' },
        { name: 'Keldon Johnson', rating: 76, position: 'SF' },
        { name: 'Jeremy Sochan', rating: 75, position: 'PF' },
        { name: 'Tre Jones', rating: 74, position: 'PG' },
        { name: 'Zach Collins', rating: 74, position: 'C' },
        { name: 'Julian Champagnie', rating: 73, position: 'SF' },
        { name: 'Blake Wesley', rating: 71, position: 'SG' },
        { name: 'Malaki Branham', rating: 71, position: 'SG' },
        { name: 'Sandro Mamukelashvili', rating: 70, position: 'PF' },
        { name: 'Charles Bassey', rating: 70, position: 'C' },
        { name: 'David Duke Jr.', rating: 69, position: 'SG' },
        { name: 'Riley Minix', rating: 68, position: 'PG' },
        { name: 'Harrison Ingram', rating: 67, position: 'SF' }
    ]
};

function getCardTier(rating) {
    if (rating >= 97) return 'pink_diamond';
    if (rating >= 93) return 'diamond';
    if (rating >= 90) return 'amethyst';
    if (rating >= 85) return 'sapphire';
    if (rating >= 80) return 'emerald';
    if (rating >= 75) return 'gold';
    if (rating >= 70) return 'silver';
    if (rating >= 66) return 'bronze';
    return 'common';
}

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

async function addMissingTeams() {
    console.log('='.repeat(80));
    console.log('Adding Missing NBA Teams Manually');
    console.log('='.repeat(80));
    console.log();
    
    const db = new sqlite3.Database(DB_PATH);
    
    let totalAdded = 0;
    
    for (const [teamName, players] of Object.entries(MISSING_TEAMS_DATA)) {
        console.log(`\n📋 ${teamName}`);
        console.log(`   Adding ${players.length} players...`);
        
        const collectionId = await getCollectionId(db, teamName);
        
        if (!collectionId) {
            console.log(`   ⚠️  Collection not found for ${teamName}, skipping...`);
            continue;
        }
        
        for (const playerData of players) {
            const cardTier = getCardTier(playerData.rating);
            
            // Temporary image URL - will be updated to NBA CDN later
            const tempImageUrl = null;
            
            await new Promise((resolve, reject) => {
                db.run(
                    `INSERT INTO players (name, overall_rating, position, team, card_tier, image_url, collection_id, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))`,
                    [playerData.name, playerData.rating, playerData.position, teamName, cardTier, tempImageUrl, collectionId],
                    (err) => {
                        if (err) {
                            // Check if it's a duplicate
                            if (err.message.includes('UNIQUE')) {
                                console.log(`   ⚠️  ${playerData.name} already exists, skipping...`);
                                resolve();
                            } else {
                                reject(err);
                            }
                        } else {
                            resolve();
                        }
                    }
                );
            });
            
            totalAdded++;
        }
        
        // Update collection total
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
        
        console.log(`   ✓ Added ${players.length} players`);
    }
    
    db.close();
    
    console.log();
    console.log('='.repeat(80));
    console.log('✓ Manual import complete!');
    console.log(`   Total players added: ${totalAdded}`);
    console.log('='.repeat(80));
}

addMissingTeams().catch(console.error);

