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
        Schema::table('homework_exercises', function (Blueprint $table) {
            // Add subject and category fields (synchronized with examination module)
            $table->foreignId('subject_id')->nullable()->after('branch_id')
                ->constrained('exam_subjects')->onDelete('set null');
            $table->foreignId('subject_category_id')->nullable()->after('subject_id')
                ->constrained('exam_subject_categories')->onDelete('set null');

            // Add index for faster filtering
            $table->index(['subject_id', 'subject_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homework_exercises', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropForeign(['subject_category_id']);
            $table->dropIndex(['subject_id', 'subject_category_id']);
            $table->dropColumn(['subject_id', 'subject_category_id']);
        });
    }
};
