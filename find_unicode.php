<?php
$content = file_get_contents("/var/www/school/storage/logs/material_debug_1765421824.json");

// Find Unicode checkbox characters
echo "Searching for Unicode characters...\n\n";

$unicode_chars = [];
for ($i = 0; $i < strlen($content); $i++) {
    $byte = ord($content[$i]);
    // Check for multi-byte UTF-8 sequences
    if ($byte > 127) {
        $char = mb_substr($content, $i, 1, 'UTF-8');
        $code = mb_ord($char, 'UTF-8');
        if (!isset($unicode_chars[$code])) {
            $unicode_chars[$code] = [
                'char' => $char,
                'hex' => dechex($code),
                'count' => 1,
                'pos' => $i
            ];
        } else {
            $unicode_chars[$code]['count']++;
        }
    }
}

echo "Found " . count($unicode_chars) . " unique Unicode characters:\n";
foreach ($unicode_chars as $code => $info) {
    printf("U+%04X (%s) - appears %d times, first at pos %d\n", 
        $code, $info['char'], $info['count'], $info['pos']);
    if ($code == 0x2610) {
        echo "  ^ This is a BALLOT BOX (checkbox) - might cause issues!\n";
    }
}

echo "\n\nNow trying to parse with mb_convert_encoding...\n";
$fixed = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
$result = json_decode($fixed, true);
if ($result) {
    echo "SUCCESS after mb_convert_encoding!\n";
} else {
    echo "Still failed: " . json_last_error_msg() . "\n";
}
