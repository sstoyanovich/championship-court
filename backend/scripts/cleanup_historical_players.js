const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const DB_PATH = path.join(__dirname, '../database/database.sqlite');

/**
 * Clean up historical players, keeping only current roster (top ~17 players by rating)
 */
async function cleanupHistoricalPlayers() {
    console.log('='.repeat(80));
    console.log('Cleanup Historical Players - Keep Only Current Rosters');
    console.log('='.repeat(80));
    console.log();
    
    const db = new sqlite3.Database(DB_PATH);
    
    const teamsToClean = ['Brooklyn Nets', 'Sacramento Kings'];
    
    for (const team of teamsToClean) {
        console.log(`Processing ${team}...`);
        
        // Get current player count
        const beforeCount = await new Promise((resolve, reject) => {
            db.get(
                'SELECT COUNT(*) as count FROM players WHERE team = ?',
                [team],
                (err, row) => {
                    if (err) reject(err);
                    else resolve(row.count);
                }
            );
        });
        
        console.log(`  Current: ${beforeCount} players`);
        
        // Keep top 17 players by overall_rating, delete the rest
        await new Promise((resolve, reject) => {
            db.run(
                `DELETE FROM players 
                 WHERE team = ? 
                 AND id NOT IN (
                     SELECT id FROM players 
                     WHERE team = ? 
                     ORDER BY overall_rating DESC, created_at DESC 
                     LIMIT 17
                 )`,
                [team, team],
                (err) => {
                    if (err) reject(err);
                    else resolve();
                }
            );
        });
        
        // Get new count
        const afterCount = await new Promise((resolve, reject) => {
            db.get(
                'SELECT COUNT(*) as count FROM players WHERE team = ?',
                [team],
                (err, row) => {
                    if (err) reject(err);
                    else resolve(row.count);
                }
            );
        });
        
        console.log(`  ✓ Cleaned: ${afterCount} players kept, ${beforeCount - afterCount} removed`);
        console.log();
    }
    
    // Show final totals
    const totalPlayers = await new Promise((resolve, reject) => {
        db.get(
            'SELECT COUNT(*) as count FROM players',
            (err, row) => {
                if (err) reject(err);
                else resolve(row.count);
            }
        );
    });
    
    const totalTeams = await new Promise((resolve, reject) => {
        db.get(
            'SELECT COUNT(DISTINCT team) as count FROM players',
            (err, row) => {
                if (err) reject(err);
                else resolve(row.count);
            }
        );
    });
    
    db.close();
    
    console.log('='.repeat(80));
    console.log('✓ Cleanup complete!');
    console.log(`   Total players: ${totalPlayers}`);
    console.log(`   Total teams: ${totalTeams}`);
    console.log('='.repeat(80));
}

cleanupHistoricalPlayers().catch(console.error);

