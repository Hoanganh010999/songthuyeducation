<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('email', 'nguyenthuy@songthuy.edu.vn')->first();
echo "=== User: {$user->name} ===\n\n";

$perms = [
    'examination.view',
    'examination.tests.view',
    'examination.ielts.view',
    'examination.ielts.tests.view',
    'examination.ielts.tests.create',
    'examination.ielts.tests.edit',
    'examination.grading.view',
];

foreach ($perms as $p) {
    $has = $user->hasPermission($p);
    echo ($has ? '✅' : '❌') . " {$p}\n";
}

echo "\n🎯 Kết luận:\n";
$canAccessTestBank = $user->hasPermission('examination.ielts.tests.view') || 
                     $user->hasPermission('examination.tests.view');
echo "  Test Bank Access: " . ($canAccessTestBank ? "✅ YES" : "❌ NO") . "\n";

$canAccessGrading = $user->hasPermission('examination.grading.view') || 
                    $user->hasPermission('examination.submissions.grade');
echo "  Grading Access: " . ($canAccessGrading ? "✅ YES" : "❌ NO") . "\n";

