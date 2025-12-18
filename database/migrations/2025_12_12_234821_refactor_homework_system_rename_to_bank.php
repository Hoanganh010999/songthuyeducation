<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rename homework_assignments to homework_bank and update structure
     * Old homework_assignments contained both template AND assignment data (wrong logic)
     * New homework_bank is ONLY for templates (no class_id, deadline, assigned_to)
     */
    public function up(): void
    {
        // Step 1: Rename table (check if source exists and target doesn't)
        if (Schema::hasTable('homework_assignments') && !Schema::hasTable('homework_bank')) {
            Schema::rename('homework_assignments', 'homework_bank');
        }

        // Step 2: Modify structure - remove assignment-specific columns (only if homework_bank exists)
        if (Schema::hasTable('homework_bank')) {
            Schema::table('homework_bank', function (Blueprint $table) {
                // Drop foreign key if class_id column exists
                if (Schema::hasColumn('homework_bank', 'class_id')) {
                    // Use original constraint name (before table rename)
                    $table->dropForeign('homework_assignments_class_id_foreign');
                    $table->dropColumn(['class_id', 'deadline', 'assigned_to', 'file_ids']);
                }

                // Add template-specific columns (check if they don't exist)
                if (!Schema::hasColumn('homework_bank', 'subject_id')) {
                    $table->foreignId('subject_id')->nullable()->after('lesson_plan_session_id')->constrained('subjects')->onDelete('set null');
                }
                if (!Schema::hasColumn('homework_bank', 'grade_level')) {
                    $table->string('grade_level')->nullable()->after('subject_id');
                }

                // Keep created_by as is (no need to rename)
            });
        }

        // Step 3: Rename pivot table (check if source table exists)
        if (Schema::hasTable('homework_assignment_exercises')) {
            Schema::rename('homework_assignment_exercises', 'homework_bank_exercises');
        }

        // Only modify if the table exists (either after rename or already exists)
        if (Schema::hasTable('homework_bank_exercises')) {
            if (Schema::hasColumn('homework_bank_exercises', 'assignment_id')) {
                Schema::table('homework_bank_exercises', function (Blueprint $table) {
                    // Drop foreign key constraint FIRST (if it exists)
                    $sql = "SELECT COUNT(*) as count
                            FROM information_schema.TABLE_CONSTRAINTS
                            WHERE CONSTRAINT_SCHEMA = DATABASE()
                            AND TABLE_NAME = 'homework_bank_exercises'
                            AND CONSTRAINT_NAME = 'homework_assignment_exercises_assignment_id_foreign'";
                    $result = \DB::select($sql);
                    if ($result[0]->count > 0) {
                        $table->dropForeign('homework_assignment_exercises_assignment_id_foreign');
                    }

                    // Then drop unique constraint (if it exists)
                    $sql2 = "SELECT COUNT(*) as count
                            FROM information_schema.STATISTICS
                            WHERE TABLE_SCHEMA = DATABASE()
                            AND TABLE_NAME = 'homework_bank_exercises'
                            AND INDEX_NAME = 'homework_assignment_exercises_assignment_id_exercise_id_unique'";
                    $result2 = \DB::select($sql2);
                    if ($result2[0]->count > 0) {
                        $table->dropUnique('homework_assignment_exercises_assignment_id_exercise_id_unique');
                    }

                    // Then drop index (if it exists)
                    $sql3 = "SELECT COUNT(*) as count
                            FROM information_schema.STATISTICS
                            WHERE TABLE_SCHEMA = DATABASE()
                            AND TABLE_NAME = 'homework_bank_exercises'
                            AND INDEX_NAME = 'homework_assignment_exercises_assignment_id_index'";
                    $result3 = \DB::select($sql3);
                    if ($result3[0]->count > 0) {
                        $table->dropIndex('homework_assignment_exercises_assignment_id_index');
                    }

                    // RENAME column instead of drop/add to preserve data
                    $table->renameColumn('assignment_id', 'homework_bank_id');
                });
            }

            // Add foreign key constraint to renamed column (check if it doesn't exist)
            if (Schema::hasColumn('homework_bank_exercises', 'homework_bank_id')) {
                // Check if foreign key already exists
                $sql = "SELECT COUNT(*) as count
                        FROM information_schema.TABLE_CONSTRAINTS
                        WHERE CONSTRAINT_SCHEMA = DATABASE()
                        AND TABLE_NAME = 'homework_bank_exercises'
                        AND CONSTRAINT_NAME = 'homework_bank_exercises_homework_bank_id_foreign'";
                $result = \DB::select($sql);

                if ($result[0]->count == 0) {
                    Schema::table('homework_bank_exercises', function (Blueprint $table) {
                        $table->foreign('homework_bank_id')->references('id')->on('homework_bank')->onDelete('cascade');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse pivot table
        Schema::table('homework_bank_exercises', function (Blueprint $table) {
            $table->dropForeign(['homework_bank_id']);
            $table->dropColumn('homework_bank_id');

            $table->foreignId('assignment_id')->after('id')->constrained('homework_assignments')->onDelete('cascade');
        });

        Schema::rename('homework_bank_exercises', 'homework_assignment_exercises');

        // Reverse homework_bank changes
        Schema::table('homework_bank', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn(['subject_id', 'grade_level']);

            $table->foreignId('class_id')->after('id')->constrained('classes')->onDelete('cascade');
            $table->timestamp('deadline')->nullable()->after('description');
            $table->json('assigned_to')->nullable()->after('deadline');
            $table->json('file_ids')->nullable()->after('assigned_to');
        });

        // Rename table back
        Schema::rename('homework_bank', 'homework_assignments');
    }
};
