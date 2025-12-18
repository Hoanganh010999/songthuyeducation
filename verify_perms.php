<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('email', 'nguyenthuy@songthuy.edu.vn')->first();
if (!$user) { echo "❌ User not found!\n"; exit; }

echo "👤 {$user->name}\n\n";

$perms = [
    'examination.view',
    'examination.grading.view',
    'examination.submissions.view',
    'examination.submissions.grade',
    'examination.questions.view',
    'examination.tests.view',
    'examination.assignments.view',
];

foreach ($perms as $p) {
    $has = $user->hasPermission($p);
    echo ($has ? '✅' : '❌') . " {$p}\n";
}

$canGrade = $user->hasPermission('examination.grading.view') || 
            $user->hasPermission('examination.submissions.grade') ||
            $user->hasPermission('examination.submissions.view');

echo "\n🎯 Can Access Grading: " . ($canGrade ? "✅ YES" : "❌ NO") . "\n";

