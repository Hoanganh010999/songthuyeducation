<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking current participation values...\n";

// Check current values
$current = DB::table('session_comments')
    ->select('participation')
    ->whereNotNull('participation')
    ->distinct()
    ->get();

echo "Current values:\n";
foreach ($current as $row) {
    echo "  - {$row->participation}\n";
}

// Update enum
echo "\nUpdating enum to add 'Tốt'...\n";

try {
    DB::statement("ALTER TABLE session_comments MODIFY COLUMN participation ENUM('Tích cực', 'Tốt', 'Bình thường', 'Ít tham gia', 'Thụ động', 'Không tham gia') NULL");
    echo "✓ Successfully updated participation enum!\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nAttempting to update existing data first...\n";
    
    // Map old English values to Vietnamese
    $mapping = [
        'active' => 'Tích cực',
        'moderate' => 'Bình thường',
        'passive' => 'Thụ động',
    ];
    
    foreach ($mapping as $old => $new) {
        $count = DB::table('session_comments')
            ->where('participation', $old)
            ->update(['participation' => $new]);
        if ($count > 0) {
            echo "  Updated $count records from '$old' to '$new'\n";
        }
    }
    
    echo "\nRetrying enum update...\n";
    try {
        DB::statement("ALTER TABLE session_comments MODIFY COLUMN participation ENUM('Tích cực', 'Tốt', 'Bình thường', 'Ít tham gia', 'Thụ động', 'Không tham gia') NULL");
        echo "✓ Successfully updated participation enum!\n";
    } catch (\Exception $e2) {
        echo "✗ Error: " . $e2->getMessage() . "\n";
    }
}

echo "\nDone!\n";

