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
        Schema::table('submission_answers', function (Blueprint $table) {
            // Add question_number column if it doesn't exist
            if (!Schema::hasColumn('submission_answers', 'question_number')) {
                $table->integer('question_number')->nullable()->after('test_question_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submission_answers', function (Blueprint $table) {
            if (Schema::hasColumn('submission_answers', 'question_number')) {
                $table->dropColumn('question_number');
            }
        });
    }
};

