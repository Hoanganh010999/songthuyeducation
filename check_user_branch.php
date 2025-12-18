<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::where('email', 'user00129@songthuy.edu.vn')->first();

if ($user) {
    echo "=== USER INFO ===\n";
    echo "User ID: {$user->id}\n";
    echo "User Name: {$user->name}\n";
    echo "Primary Branch ID (accessor): " . ($user->primary_branch_id ?? 'null') . "\n\n";
    
    echo "=== STUDENT RECORD ===\n";
    $student = \DB::table('students')->where('user_id', $user->id)->where('is_active', true)->first();
    if ($student) {
        echo "  Student ID: {$student->id}\n";
        echo "  Branch ID: {$student->branch_id}\n";
        $branch = \App\Models\Branch::find($student->branch_id);
        if ($branch) echo "  Branch Name: {$branch->name}\n";
    } else {
        echo "  No student record found\n";
    }
    
    echo "\n=== BRANCH_USER RECORDS (Employee) ===\n";
    $branches = $user->branches;
    if ($branches->count() > 0) {
        foreach ($branches as $branch) {
            echo "  - Branch ID: {$branch->id}, Name: {$branch->name}\n";
        }
    } else {
        echo "  No branch_user records\n";
    }
    
    echo "\n=== CLASS ENROLLMENTS ===\n";
    if ($student) {
        $enrollments = \DB::table('class_students')
            ->join('classes', 'class_students.class_id', '=', 'classes.id')
            ->join('branches', 'classes.branch_id', '=', 'branches.id')
            ->where('class_students.student_id', $student->id)
            ->select('classes.name as class_name', 'classes.branch_id', 'branches.name as branch_name', 'class_students.status')
            ->get();
        
        if ($enrollments->count() > 0) {
            foreach ($enrollments as $enrollment) {
                echo "  - Class: {$enrollment->class_name}, Branch ID: {$enrollment->branch_id}, Branch: {$enrollment->branch_name}, Status: {$enrollment->status}\n";
            }
        } else {
            echo "  No class enrollments\n";
        }
    }
} else {
    echo "User not found\n";
}
