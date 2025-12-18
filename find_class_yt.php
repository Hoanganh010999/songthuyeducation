<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TÌM LỚP IELTS K1 YT ===\n";
$classes = \DB::table('classes')
    ->join('branches', 'classes.branch_id', '=', 'branches.id')
    ->where(function($q) {
        $q->where('classes.name', 'LIKE', '%K1%YT%')
          ->orWhere('classes.name', 'LIKE', '%IELTS%K1%');
    })
    ->select('classes.*', 'branches.name as branch_name')
    ->get();

foreach ($classes as $class) {
    echo "\nClass ID: {$class->id}\n";
    echo "Class Name: {$class->name}\n";
    echo "Branch ID: {$class->branch_id}\n";
    echo "Branch Name: {$class->branch_name}\n";
    
    // Check if student user00129 is in this class
    $enrollment = \DB::table('class_students')
        ->join('students', 'class_students.student_id', '=', 'students.id')
        ->join('users', 'students.user_id', '=', 'users.id')
        ->where('class_students.class_id', $class->id)
        ->where('users.email', 'user00129@songthuy.edu.vn')
        ->select('class_students.*', 'users.name', 'students.id as student_id')
        ->first();
    
    if ($enrollment) {
        echo "  => Student Found: {$enrollment->name} (Student ID: {$enrollment->student_id}, Status: {$enrollment->status})\n";
    } else {
        echo "  => Student NOT in this class\n";
    }
}

echo "\n=== TÌM BRANCH YÊN TÂM ===\n";
$yenTamBranch = \DB::table('branches')->where('name', 'LIKE', '%Yên Tâm%')->first();
if ($yenTamBranch) {
    echo "Branch ID: {$yenTamBranch->id}\n";
    echo "Branch Name: {$yenTamBranch->name}\n";
} else {
    echo "Yên Tâm branch not found\n";
}

echo "\n=== ALL BRANCHES ===\n";
$allBranches = \DB::table('branches')->get();
foreach ($allBranches as $branch) {
    echo "  ID: {$branch->id}, Name: {$branch->name}\n";
}
