<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            // 1. Drop foreign key constraint on session_id first
            $table->dropForeign('homework_submissions_session_id_foreign');
            
            // 2. Drop old unique constraint
            $table->dropUnique('homework_submissions_session_id_student_id_unique');
            
            // 3. Add homework_assignment_id column
            $table->foreignId('homework_assignment_id')
                ->nullable()
                ->after('id')
                ->constrained('homework_assignments')
                ->onDelete('cascade');
            
            // 4. Re-add foreign key for session_id (without unique constraint)
            $table->foreign('session_id')
                ->references('id')
                ->on('class_lesson_sessions')
                ->onDelete('cascade');
            
            // 5. Add new unique constraint (homework_assignment_id, student_id)
            $table->unique(['homework_assignment_id', 'student_id'], 'homework_submissions_homework_student_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            // Reverse operations
            $table->dropUnique('homework_submissions_homework_student_unique');
            $table->dropForeign('homework_submissions_session_id_foreign');
            $table->dropForeign(['homework_assignment_id']);
            $table->dropColumn('homework_assignment_id');
            
            // Restore old unique constraint
            $table->unique(['session_id', 'student_id'], 'homework_submissions_session_id_student_id_unique');
            
            // Restore foreign key with unique constraint
            $table->foreign('session_id')
                ->references('id')
                ->on('class_lesson_sessions')
                ->onDelete('cascade');
        });
    }
};
