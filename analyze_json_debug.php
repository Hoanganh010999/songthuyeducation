<?php
$file = "/var/www/school/storage/logs/material_debug_1765421824.json";
$content = file_get_contents($file);

echo "=== JSON DEBUG ANALYSIS ===\n\n";
echo "File size: " . strlen($content) . " bytes\n";
echo "Number of lines: " . substr_count($content, "\n") . "\n\n";

// Show structure
$lines = explode("\n", $content);
echo "Line count: " . count($lines) . "\n";
for ($i = 0; $i < count($lines); $i++) {
    $len = strlen($lines[$i]);
    $preview = substr($lines[$i], 0, 100);
    echo sprintf("Line %d: %d bytes, starts with: %s\n", $i, $len, $preview);
}

echo "\n\n=== CHECKING FOR UNESCAPED CHARACTERS ===\n\n";

// Check line 3 (the content line) for issues
if (isset($lines[3])) {
    $contentLine = $lines[3];
    echo "Content line length: " . strlen($contentLine) . " bytes\n";

    // Look for unescaped double quotes
    echo "\nSearching for unescaped quotes...\n";

    // The pattern: a quote NOT preceded by backslash
    // This is complex - let's just check for \" patterns
    $escapedCount = substr_count($contentLine, '\\"');
    $totalQuotes = substr_count($contentLine, '"');

    echo "Total quote characters: $totalQuotes\n";
    echo "Escaped quotes (\\\"): $escapedCount\n";
    echo "Potential unescaped: " . ($totalQuotes - $escapedCount) . "\n";

    // The content should be: "content": "VALUE"
    // So we expect 4 structural quotes + all HTML quotes escaped

    echo "\n\nThe problem is likely: HTML content has literal quotes that aren't properly escaped!\n";
    echo "Anthropic should have escaped all \" as \\\" but didn't.\n\n";

    // Find first problematic quote
    $inString = false;
    $lastBackslash = false;
    for ($i = 0; $i < min(5000, strlen($contentLine)); $i++) {
        $char = $contentLine[$i];
        if ($char === '"' && !$lastBackslash && $i > 50) { // Skip first quotes
            echo "Found potential unescaped quote at position $i\n";
            echo "Context: " . substr($contentLine, $i - 50, 100) . "\n";
            break;
        }
        $lastBackslash = ($char === '\\');
    }
}
