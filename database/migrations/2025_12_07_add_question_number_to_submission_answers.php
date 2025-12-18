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
            // Make question_id nullable for practice tests
            $table->foreignId('question_id')->nullable()->change();
            
            // Add question_number column for practice tests
            $table->integer('question_number')->nullable()->after('test_question_id')->comment('Question number for practice tests without question records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submission_answers', function (Blueprint $table) {
            $table->dropColumn('question_number');
            
            // Revert question_id to NOT NULL (if needed)
            // $table->foreignId('question_id')->change();
        });
    }
};

