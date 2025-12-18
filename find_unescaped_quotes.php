<?php
$file = "/var/www/school/storage/logs/material_debug_1765421824.json";
$content = file_get_contents($file);

$lines = explode("\n", $content);
$contentLine = $lines[3];

echo "Finding all unescaped quotes in content line...\n\n";

// Skip the first part: "content": "
// The pattern is: "content": "HTML_CONTENT_HERE"
$start = strpos($contentLine, '"content": "') + strlen('"content": "');

echo "Content value starts at position: $start\n";
echo "Analyzing from position $start...\n\n";

$quotePositions = [];
$escaped = false;

for ($i = $start; $i < strlen($contentLine); $i++) {
    $char = $contentLine[$i];

    if ($char === '"') {
        // Check if this quote is escaped
        $backslashCount = 0;
        $j = $i - 1;
        while ($j >= 0 && $contentLine[$j] === '\\') {
            $backslashCount++;
            $j--;
        }

        // If odd number of backslashes, the quote is escaped
        $isEscaped = ($backslashCount % 2 === 1);

        if (!$isEscaped) {
            $quotePositions[] = $i;
            echo "UNESCAPED QUOTE at position $i:\n";
            $context = substr($contentLine, max(0, $i - 100), 200);
            echo "Context: ...{$context}...\n\n";

            if (count($quotePositions) >= 5) {
                echo "Showing first 5 occurrences only...\n";
                break;
            }
        }
    }
}

echo "\nTotal unescaped quotes found: " . count($quotePositions) . "\n";
echo "\nThese quotes should be escaped as \\\" in the JSON!\n";
