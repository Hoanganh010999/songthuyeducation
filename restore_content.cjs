const fs = require('fs');
const mysql = require('mysql2/promise');

async function main() {
    console.log('=== RESTORING MATERIAL CONTENT ===\n');

    // Find debug files
    const debugFiles = fs.readdirSync('/var/www/school/storage/logs')
        .filter(f => f.startsWith('material_debug_') && f.endsWith('.json'))
        .map(f => `/var/www/school/storage/logs/${f}`)
        .sort()
        .reverse();

    console.log(`Found ${debugFiles.length} debug files\n`);

    let data = null;
    let selectedFile = null;

    for (const file of debugFiles) {
        console.log(`Checking: ${file.split('/').pop()}`);

        const content = fs.readFileSync(file, 'utf-8');
        console.log(`  File size: ${content.length.toLocaleString()} bytes`);

        try {
            const parsed = JSON.parse(content);

            if (!parsed.content || parsed.content.length < 5000) {
                console.log(`  ⚠️  Content too short or missing`);
                continue;
            }

            console.log(`  ✅ Valid JSON! Content length: ${parsed.content.length.toLocaleString()} chars`);
            data = parsed;
            selectedFile = file;
            break;

        } catch (err) {
            console.log(`  ❌ JSON parse error: ${err.message}`);
            continue;
        }
    }

    if (!data) {
        console.log('\n❌ Could not parse any debug file');
        return process.exit(1);
    }

    console.log(`\n=== SELECTED FILE ===`);
    console.log(`File: ${selectedFile.split('/').pop()}\n`);

    const title = data.title || 'Untitled';
    const htmlContent = data.content;

    console.log('=== EXTRACTED DATA ===');
    console.log(`Title: ${title}`);
    console.log(`Content length: ${htmlContent.length.toLocaleString()} chars`);
    console.log(`Preview: ${htmlContent.substring(0, 200)}...\n`);

    // Verify
    const hasTable = htmlContent.includes('<table');
    const hasVocab = htmlContent.toLowerCase().includes('vocabulary') || htmlContent.includes('PART A');
    const hasReading = htmlContent.toLowerCase().includes('reading') || htmlContent.includes('PART B');

    console.log('Content verification:');
    console.log(`${hasVocab ? '✅' : '❌'} Has vocabulary`);
    console.log(`${hasTable ? '✅' : '❌'} Has tables`);
    console.log(`${hasReading ? '✅' : '❌'} Has reading\n`);

    // Read .env for database config
    const envFile = fs.readFileSync('/var/www/school/.env', 'utf-8');
    const envLines = envFile.split('\n');
    const dbConfig = {
        host: 'localhost',
        user: 'root',
        password: '',
        database: 'school_db'
    };

    for (const line of envLines) {
        if (line.startsWith('DB_HOST=')) dbConfig.host = line.split('=')[1].trim();
        if (line.startsWith('DB_DATABASE=')) dbConfig.database = line.split('=')[1].trim();
        if (line.startsWith('DB_USERNAME=')) dbConfig.user = line.split('=')[1].trim();
        if (line.startsWith('DB_PASSWORD=')) dbConfig.password = line.split('=')[1].trim();
    }

    // Connect to database
    const connection = await mysql.createConnection(dbConfig);

    console.log(`✅ Connected to database: ${dbConfig.database}\n`);

    // Show current
    const [current] = await connection.execute(
        'SELECT id, title, CHAR_LENGTH(content) as content_len FROM session_materials WHERE id = 2'
    );

    if (current.length > 0) {
        console.log('=== CURRENT DATABASE ===');
        console.log(`ID: ${current[0].id}`);
        console.log(`Title: ${current[0].title}`);
        console.log(`Content: ${current[0].content_len.toLocaleString()} chars\n`);
    }

    // Update
    console.log('Updating database...');

    await connection.execute(
        'UPDATE session_materials SET content = ?, title = ?, updated_at = NOW() WHERE id = 2',
        [htmlContent, title]
    );

    // Verify
    const [result] = await connection.execute(
        'SELECT id, title, CHAR_LENGTH(content) as content_len FROM session_materials WHERE id = 2'
    );

    console.log('\n=== AFTER UPDATE ===');
    console.log(`ID: ${result[0].id}`);
    console.log(`Title: ${result[0].title}`);
    console.log(`Content: ${result[0].content_len.toLocaleString()} chars\n`);

    const improvement = result[0].content_len - (current[0]?.content_len || 0);
    console.log(`Increased by: ${improvement.toLocaleString()} chars\n`);

    if (result[0].content_len > 15000) {
        console.log('✅✅✅ SUCCESS! Content restored! ✅✅✅\n');
        console.log('The material now has:');
        console.log('  - Complete vocabulary tables');
        console.log('  - Full reading text');
        console.log('  - All exercises and answer keys\n');
        console.log('View it in MaterialsManager!');
    } else {
        console.log('⚠️  Content still seems short');
        console.log(`Expected: >15,000 chars`);
        console.log(`Got: ${result[0].content_len.toLocaleString()} chars`);
    }

    await connection.end();
}

main().catch(console.error);
