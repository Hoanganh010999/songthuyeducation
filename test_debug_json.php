<?php
$file = "/var/www/school/storage/logs/material_debug_1765421824.json";
$content = file_get_contents($file);

echo "File size: " . strlen($content) . " bytes\n";
echo "Number of actual newlines: " . substr_count($content, "\n") . "\n\n";

// Check first few lines
$lines = explode("\n", $content);
echo "First 10 lines:\n";
for ($i = 0; $i < min(10, count($lines)); $i++) {
    echo sprintf("%2d: %s\n", $i, substr($lines[$i], 0, 100));
}

echo "\n\nThe problem: JSON should not have actual newlines in strings!\n";
echo "They should be escaped as \n\n\n";

// Fix: Anthropic returned JSON with actual newlines instead of escaped \n
// We need to tell AI to return properly formatted JSON OR
// fix it on our end by asking for escaped JSON
echo "Solution: Modify prompt to ask AI to return minified JSON without newlines\n";
echo "OR: Use JSON_UNESCAPED_UNICODE when parsing\n";
