<?php

echo "=== RESTORING MATERIAL CONTENT FROM DEBUG FILES ===\n\n";

// Look for material_debug JSON files
$debugFiles = glob('/var/www/school/storage/logs/material_debug_*.json');

if (empty($debugFiles)) {
    echo "❌ No debug files found\n";
    exit(1);
}

rsort($debugFiles); // Most recent first

echo "Found " . count($debugFiles) . " debug files\n";
echo "Checking most recent files...\n\n";

$data = null;
$selectedFile = null;

foreach ($debugFiles as $debugFile) {
    echo "Checking: " . basename($debugFile) . "\n";

    $debugContent = file_get_contents($debugFile);

    if (empty($debugContent)) {
        echo "  ⚠️ File is empty\n";
        continue;
    }

    echo "  File size: " . number_format(strlen($debugContent)) . " bytes\n";

    $debugData = json_decode($debugContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "  ❌ JSON parse error: " . json_last_error_msg() . "\n";
        continue;
    }

    if (!isset($debugData['content'])) {
        echo "  ⚠️ No 'content' field found\n";
        continue;
    }

    if (strlen($debugData['content']) < 5000) {
        echo "  ⚠️ Content too short (" . strlen($debugData['content']) . " chars)\n";
        continue;
    }

    // This looks good!
    echo "  ✅ Valid content found! Length: " . number_format(strlen($debugData['content'])) . " chars\n";
    $data = $debugData;
    $selectedFile = $debugFile;
    break;
}

if (!$data) {
    echo "\n❌ Could not find valid content in any debug file\n";
    exit(1);
}

echo "\n=== SELECTED FILE ===\n";
echo "File: " . basename($selectedFile) . "\n\n";

$title = $data['title'] ?? 'Untitled';
$content = $data['content'];

echo "=== EXTRACTED DATA ===\n";
echo "Title: $title\n";
echo "Content length: " . number_format(strlen($content)) . " chars\n";
echo "Content preview (first 200 chars):\n";
echo substr($content, 0, 200) . "...\n\n";

// Verify it has the expected content
$hasVocab = (stripos($content, 'vocabulary') !== false || stripos($content, 'PART A') !== false);
$hasTable = (stripos($content, '<table') !== false);
$hasReading = (stripos($content, 'reading') !== false || stripos($content, 'PART B') !== false);
$hasAnswerKey = (stripos($content, 'answer') !== false && stripos($content, 'key') !== false);

echo "Content verification:\n";
echo ($hasVocab ? '✅' : '❌') . " Has vocabulary section\n";
echo ($hasTable ? '✅' : '❌') . " Has tables\n";
echo ($hasReading ? '✅' : '❌') . " Has reading section\n";
echo ($hasAnswerKey ? '✅' : '❌') . " Has answer key\n\n";

if (!$hasTable) {
    echo "⚠️ WARNING: Content doesn't seem to have tables!\n";
    echo "Do you want to continue anyway? (continuing...)\n\n";
}

// Connect to database and update
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_DATABASE'] ?? 'school_db';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database: $dbname\n\n";

    // First, show current record
    $stmt = $pdo->query("SELECT id, title, CHAR_LENGTH(content) as content_len FROM session_materials WHERE id = 2");
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current) {
        echo "=== CURRENT DATABASE RECORD ===\n";
        echo "ID: {$current['id']}\n";
        echo "Title: {$current['title']}\n";
        echo "Content length: {$current['content_len']} chars\n\n";
    }

    // Update the record
    echo "Updating record...\n";

    $stmt = $pdo->prepare("
        UPDATE session_materials
        SET content = :content,
            title = :title,
            updated_at = NOW()
        WHERE id = 2
    ");

    $stmt->execute([
        'content' => $content,
        'title' => $title
    ]);

    echo "✅ Updated session_materials record id=2\n";
    echo "Rows affected: " . $stmt->rowCount() . "\n\n";

    // Verify the update
    $stmt = $pdo->query("SELECT id, title, CHAR_LENGTH(content) as content_len FROM session_materials WHERE id = 2");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "=== AFTER UPDATE ===\n";
    echo "ID: {$result['id']}\n";
    echo "Title: {$result['title']}\n";
    echo "Content length: {$result['content_len']} chars\n\n";

    $improvement = $result['content_len'] - ($current['content_len'] ?? 0);
    echo "Content increased by: " . number_format($improvement) . " chars\n\n";

    if ($result['content_len'] > 15000) {
        echo "✅✅✅ SUCCESS! Content restored to full length! ✅✅✅\n";
        echo "\nThe material now has:\n";
        echo "  - Complete vocabulary tables\n";
        echo "  - Full reading text\n";
        echo "  - All exercises and activities\n";
        echo "  - Answer keys\n\n";
        echo "You can now view the material in MaterialsManager!\n";
    } else {
        echo "⚠️ Content is still shorter than expected\n";
        echo "Expected: >15,000 chars\n";
        echo "Got: {$result['content_len']} chars\n";
    }

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
