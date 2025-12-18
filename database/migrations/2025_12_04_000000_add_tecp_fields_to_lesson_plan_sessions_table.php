<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add TECP (Teaching English Certificate Program) Lesson Plan fields
     */
    public function up(): void
    {
        Schema::table('lesson_plan_sessions', function (Blueprint $table) {
            // Header section
            $table->string('teacher_name')->nullable()->after('lesson_title');
            $table->text('lesson_focus')->nullable()->after('teacher_name');
            $table->string('level')->nullable()->after('lesson_focus');
            $table->date('lesson_date')->nullable()->after('level');
            $table->string('tp_number')->nullable()->after('lesson_date'); // Teaching Point number

            // Lesson Aims section
            $table->text('communicative_outcome')->nullable()->after('tp_number');
            $table->text('linguistic_aim')->nullable()->after('communicative_outcome');
            $table->text('productive_subskills_focus')->nullable()->after('linguistic_aim');
            $table->text('receptive_subskills_focus')->nullable()->after('productive_subskills_focus');

            // Personal Aims section
            $table->text('teaching_aspects_to_improve')->nullable()->after('receptive_subskills_focus');
            $table->text('improvement_methods')->nullable()->after('teaching_aspects_to_improve');

            // Framework/Shape section
            $table->text('framework_shape')->nullable()->after('improvement_methods');

            // Language Analysis Sheet - Functional Language
            $table->text('language_area')->nullable()->after('framework_shape');
            $table->text('examples_of_language')->nullable()->after('language_area');
            $table->text('context')->nullable()->after('examples_of_language');
            $table->text('concept_checking_methods')->nullable()->after('context');
            $table->text('concept_checking_in_lesson')->nullable()->after('concept_checking_methods');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_plan_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'teacher_name',
                'lesson_focus',
                'level',
                'lesson_date',
                'tp_number',
                'communicative_outcome',
                'linguistic_aim',
                'productive_subskills_focus',
                'receptive_subskills_focus',
                'teaching_aspects_to_improve',
                'improvement_methods',
                'framework_shape',
                'language_area',
                'examples_of_language',
                'context',
                'concept_checking_methods',
                'concept_checking_in_lesson',
            ]);
        });
    }
};
