const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const DB_PATH = path.join(__dirname, '../database/database.sqlite');

/**
 * Transliterate special characters to ASCII equivalents
 */
function transliterate(text) {
    const charMap = {
        'á': 'a', 'à': 'a', 'â': 'a', 'ä': 'a', 'ã': 'a', 'å': 'a', 'ā': 'a',
        'é': 'e', 'è': 'e', 'ê': 'e', 'ë': 'e', 'ē': 'e', 'ė': 'e', 'ę': 'e',
        'í': 'i', 'ì': 'i', 'î': 'i', 'ï': 'i', 'ī': 'i', 'į': 'i',
        'ó': 'o', 'ò': 'o', 'ô': 'o', 'ö': 'o', 'õ': 'o', 'ō': 'o', 'ø': 'o',
        'ú': 'u', 'ù': 'u', 'û': 'u', 'ü': 'u', 'ū': 'u', 'ų': 'u',
        'ý': 'y', 'ÿ': 'y',
        'ñ': 'n', 'ń': 'n',
        'ç': 'c', 'ć': 'c', 'č': 'c',
        'ß': 'ss',
        'ž': 'z', 'ź': 'z', 'ż': 'z',
        'š': 's', 'ś': 's',
        'đ': 'd', 'ď': 'd',
        'ř': 'r',
        'ł': 'l',
        'Á': 'A', 'À': 'A', 'Â': 'A', 'Ä': 'A', 'Ã': 'A', 'Å': 'A', 'Ā': 'A',
        'É': 'E', 'È': 'E', 'Ê': 'E', 'Ë': 'E', 'Ē': 'E', 'Ė': 'E', 'Ę': 'E',
        'Í': 'I', 'Ì': 'I', 'Î': 'I', 'Ï': 'I', 'Ī': 'I', 'Į': 'I',
        'Ó': 'O', 'Ò': 'O', 'Ô': 'O', 'Ö': 'O', 'Õ': 'O', 'Ō': 'O', 'Ø': 'O',
        'Ú': 'U', 'Ù': 'U', 'Û': 'U', 'Ü': 'U', 'Ū': 'U', 'Ų': 'U',
        'Ý': 'Y', 'Ÿ': 'Y',
        'Ñ': 'N', 'Ń': 'N',
        'Ç': 'C', 'Ć': 'C', 'Č': 'C',
        'Ž': 'Z', 'Ź': 'Z', 'Ż': 'Z',
        'Š': 'S', 'Ś': 'S',
        'Đ': 'D', 'Ď': 'D',
        'Ř': 'R',
        'Ł': 'L'
    };
    
    return text.split('').map(char => charMap[char] || char).join('');
}

/**
 * Convert player name to 2K image URL format
 * Example: "Trae Young" -> "https://www.2kratings.com/wp-content/uploads/Trae-Young-2K-Rating.png"
 * Example: "Nikola Jokić" -> "https://www.2kratings.com/wp-content/uploads/Nikola-Jokic-2K-Rating.png"
 */
function generateImageUrl(playerName) {
    // Transliterate special characters first
    const transliterated = transliterate(playerName);
    
    // Replace spaces with dashes, handle special characters
    const formattedName = transliterated
        .trim()
        .replace(/\s+/g, '-')  // Replace spaces with dashes
        .replace(/'/g, '')     // Remove apostrophes  
        .replace(/'/g, '')     // Remove curly apostrophes
        .replace(/\./g, '')    // Remove periods
        .replace(/[^A-Za-z0-9\-]/g, ''); // Remove any remaining non-alphanumeric chars except dashes
    
    return `https://www.2kratings.com/wp-content/uploads/${formattedName}-2K-Rating.png`;
}

/**
 * Update all player images in database
 */
async function updateAllPlayerImages() {
    console.log('='.repeat(80));
    console.log('Generate Player Image URLs');
    console.log('='.repeat(80));
    console.log();
    
    const db = new sqlite3.Database(DB_PATH, (err) => {
        if (err) {
            console.error('❌ Could not connect to database:', err.message);
            process.exit(1);
        }
    });
    
    console.log('✓ Database connected');
    console.log();
    
    // Get all players
    const players = await new Promise((resolve, reject) => {
        db.all('SELECT id, name FROM players', (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
    
    console.log(`📊 Found ${players.length} players`);
    console.log('🔄 Generating image URLs...');
    console.log();
    
    let updated = 0;
    let failed = 0;
    
    for (const player of players) {
        try {
            const imageUrl = generateImageUrl(player.name);
            
            await new Promise((resolve, reject) => {
                db.run(
                    'UPDATE players SET image_url = ? WHERE id = ?',
                    [imageUrl, player.id],
                    (err) => {
                        if (err) reject(err);
                        else resolve();
                    }
                );
            });
            
            updated++;
            
            if (updated % 50 === 0) {
                console.log(`   Updated ${updated}/${players.length}...`);
            }
        } catch (err) {
            console.log(`   ⚠️  Failed to update ${player.name}: ${err.message}`);
            failed++;
        }
    }
    
    console.log();
    console.log('='.repeat(80));
    console.log('✓ Image URL generation complete!');
    console.log(`   Updated: ${updated}`);
    console.log(`   Failed: ${failed}`);
    console.log('='.repeat(80));
    
    // Show sample URLs
    console.log();
    console.log('📸 Sample Image URLs:');
    const samples = await new Promise((resolve, reject) => {
        db.all('SELECT name, image_url FROM players LIMIT 5', (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
    
    samples.forEach(s => {
        console.log(`   ${s.name}: ${s.image_url}`);
    });
    
    db.close();
}

updateAllPlayerImages().catch(console.error);

