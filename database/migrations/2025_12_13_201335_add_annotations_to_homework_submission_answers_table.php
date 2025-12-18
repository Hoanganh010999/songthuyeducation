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
        Schema::table('homework_submission_answers', function (Blueprint $table) {
            $table->json('annotations')->nullable()->after('is_correct');
            $table->text('grading_notes')->nullable()->after('annotations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homework_submission_answers', function (Blueprint $table) {
            $table->dropColumn(['annotations', 'grading_notes']);
        });
    }
};
