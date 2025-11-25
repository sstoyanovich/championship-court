const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const DB_PATH = path.join(__dirname, '../database/database.sqlite');

// Current 2024-25 rosters from 2kratings.com
const CORRECT_ROSTERS = {
    'Orlando Magic': [
        { name: 'Paolo Banchero', rating: 89, position: 'PF' },
        { name: 'Franz Wagner', rating: 87, position: 'SF' },
        { name: 'Jalen Suggs', rating: 81, position: 'SG' },
        { name: 'Wendell Carter Jr.', rating: 78, position: 'C' },
        { name: 'Kentavious Caldwell-Pope', rating: 77, position: 'SG' },
        { name: 'Jonathan Isaac', rating: 76, position: 'PF' },
        { name: 'Cole Anthony', rating: 75, position: 'PG' },
        { name: 'Gary Harris', rating: 74, position: 'SG' },
        { name: 'Goga Bitadze', rating: 74, position: 'C' },
        { name: 'Tristan da Silva', rating: 73, position: 'SF' },
        { name: 'Anthony Black', rating: 73, position: 'PG' },
        { name: 'Moritz Wagner', rating: 72, position: 'C' },
        { name: 'Jett Howard', rating: 70, position: 'SG' },
        { name: 'Trevelin Queen', rating: 69, position: 'SG' },
        { name: 'Caleb Houstan', rating: 68, position: 'SF' },
        { name: 'Mac McClung', rating: 67, position: 'PG' }
    ],
    'Brooklyn Nets': [
        { name: 'Cam Thomas', rating: 84, position: 'SG' },
        { name: 'Nic Claxton', rating: 83, position: 'C' },
        { name: 'Cameron Johnson', rating: 80, position: 'SF' },
        { name: 'Dennis Schröder', rating: 78, position: 'PG' },
        { name: 'Dorian Finney-Smith', rating: 77, position: 'PF' },
        { name: 'Day\'Ron Sharpe', rating: 75, position: 'C' },
        { name: 'Ben Simmons', rating: 75, position: 'PG' },
        { name: 'Ziaire Williams', rating: 74, position: 'SF' },
        { name: 'Shake Milton', rating: 73, position: 'PG' },
        { name: 'Trendon Watford', rating: 73, position: 'PF' },
        { name: 'Jalen Wilson', rating: 72, position: 'SF' },
        { name: 'Noah Clowney', rating: 71, position: 'PF' },
        { name: 'Keon Johnson', rating: 70, position: 'SG' },
        { name: 'Tyrese Martin', rating: 69, position: 'SF' },
        { name: 'Dariq Whitehead', rating: 68, position: 'SG' },
        { name: 'Reece Beekman', rating: 67, position: 'PG' }
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

async function fixTeams() {
    console.log('='.repeat(80));
    console.log('Fixing Orlando Magic and Brooklyn Nets Rosters');
    console.log('='.repeat(80));
    console.log();
    
    const db = new sqlite3.Database(DB_PATH);
    
    for (const [teamName, players] of Object.entries(CORRECT_ROSTERS)) {
        console.log(`\n🔧 Fixing ${teamName}...`);
        
        // Delete all current players for this team
        const deleteCount = await new Promise((resolve, reject) => {
            db.run('DELETE FROM players WHERE team = ?', [teamName], function(err) {
                if (err) reject(err);
                else resolve(this.changes);
            });
        });
        
        console.log(`   ✓ Removed ${deleteCount} old/incorrect players`);
        
        // Get collection ID
        const collectionId = await getCollectionId(db, teamName);
        
        if (!collectionId) {
            console.log(`   ⚠️  Collection not found for ${teamName}, skipping...`);
            continue;
        }
        
        // Add correct roster
        console.log(`   📋 Adding ${players.length} current roster players...`);
        
        for (const playerData of players) {
            const cardTier = getCardTier(playerData.rating);
            
            await new Promise((resolve, reject) => {
                db.run(
                    `INSERT INTO players (name, overall_rating, position, team, card_tier, image_url, collection_id, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))`,
                    [playerData.name, playerData.rating, playerData.position, teamName, cardTier, null, collectionId],
                    (err) => {
                        if (err) reject(err);
                        else resolve();
                    }
                );
            });
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
        
        // Show top players
        const topPlayers = await new Promise((resolve, reject) => {
            db.all(
                'SELECT name, overall_rating FROM players WHERE team = ? ORDER BY overall_rating DESC LIMIT 5',
                [teamName],
                (err, rows) => {
                    if (err) reject(err);
                    else resolve(rows);
                }
            );
        });
        
        console.log(`   📊 Top players:`);
        topPlayers.forEach(p => {
            console.log(`      • ${p.name} (${p.overall_rating})`);
        });
    }
    
    db.close();
    
    console.log();
    console.log('='.repeat(80));
    console.log('✓ Teams fixed successfully!');
    console.log('='.repeat(80));
}

fixTeams().catch(console.error);

