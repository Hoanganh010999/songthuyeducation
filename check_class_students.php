<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CLASS_STUDENTS TABLE STRUCTURE ===\n";
$columns = DB::select('DESCRIBE class_students');
foreach ($columns as $col) {
    echo $col->Field . ' (' . $col->Type . ') - ' . $col->Key . "\n";
}

echo "\n=== TOTAL RECORDS ===\n";
$totalRecords = DB::table('class_students')->count();
echo "Total class_students records: $totalRecords\n";

if ($totalRecords > 0) {
    echo "\n=== SAMPLE DATA (5 records) ===\n";
    $samples = DB::table('class_students')->limit(5)->get();
    foreach ($samples as $s) {
        echo "ID: {$s->id}, Student ID: {$s->student_id}, Class ID: {$s->class_id}\n";
    }

    echo "\n=== CHECK IF student_id REFERENCES students.id OR users.id ===\n";
    $testRecord = DB::table('class_students')->first();
    if ($testRecord) {
        echo "Test record: student_id = {$testRecord->student_id}\n";
        
        $studentMatch = DB::table('students')->where('id', $testRecord->student_id)->first();
        $userMatch = DB::table('users')->where('id', $testRecord->student_id)->first();
        
        echo 'Match in students table: ' . ($studentMatch ? "YES (id={$studentMatch->id})" : 'NO') . "\n";
        echo 'Match in users table: ' . ($userMatch ? "YES (id={$userMatch->id})" : 'NO') . "\n";
        
        if ($studentMatch) {
            echo "\nStudent record details:\n";
            echo "  Student ID: {$studentMatch->id}\n";
            echo "  User ID: {$studentMatch->user_id}\n";
            echo "  Branch ID: {$studentMatch->branch_id}\n";
        }
    }
    
    echo "\n=== FIND user00129@songthuy.edu.vn IN class_students ===\n";
    $user = DB::table('users')->where('email', 'user00129@songthuy.edu.vn')->first();
    if ($user) {
        $student = DB::table('students')->where('user_id', $user->id)->first();
        if ($student) {
            $enrollments = DB::table('class_students')
                ->where('student_id', $student->id)
                ->get();
            
            echo "User ID: {$user->id}\n";
            echo "Student ID: {$student->id}\n";
            echo "Enrollments found: " . count($enrollments) . "\n";
            
            foreach ($enrollments as $e) {
                $class = DB::table('classes')->where('id', $e->class_id)->first();
                echo "  - Class: {$class->name} (Branch ID: {$class->branch_id})\n";
            }
        }
    }
} else {
    echo "⚠️ class_students table is EMPTY!\n";
}
