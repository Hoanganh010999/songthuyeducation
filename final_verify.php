<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('email', 'nguyenthuy@songthuy.edu.vn')->first();
echo "=== Final Verification: {$user->name} ===\n\n";

$categories = [
    'Core' => ['examination.view'],
    'Questions' => ['examination.questions.view', 'examination.questions.create', 'examination.questions.edit'],
    'Tests' => ['examination.tests.view', 'examination.tests.create', 'examination.tests.edit'],
    'IELTS' => ['examination.ielts.view', 'examination.ielts.tests.view', 'examination.ielts.tests.create', 'examination.ielts.tests.edit'],
    'Assignments' => ['examination.assignments.view', 'examination.assignments.create', 'examination.assignments.edit'],
    'Grading' => ['examination.grading.view', 'examination.submissions.view', 'examination.submissions.grade', 'examination.submissions.special_view'],
    'Reports' => ['examination.reports.view'],
];

foreach ($categories as $cat => $perms) {
    echo "📁 {$cat}:\n";
    foreach ($perms as $p) {
        $has = $user->hasPermission($p);
        echo ($has ? '  ✅' : '  ❌') . " {$p}\n";
    }
    echo "\n";
}

echo "🎯 Access Summary:\n";
echo "  Ngân hàng đề IELTS: " . ($user->hasPermission('examination.ielts.tests.view') ? "✅" : "❌") . "\n";
echo "  Chấm điểm: " . ($user->hasPermission('examination.grading.view') ? "✅" : "❌") . "\n";
echo "  Tạo đề IELTS: " . ($user->hasPermission('examination.ielts.tests.create') ? "✅" : "❌") . "\n";

