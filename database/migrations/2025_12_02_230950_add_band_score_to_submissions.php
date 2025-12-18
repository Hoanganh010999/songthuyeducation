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
        // Add band_score to submissions table
        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('submissions', 'band_score')) {
                $table->decimal('band_score', 3, 1)->nullable();
            }
        });

        // Add band_score to submission_answers table
        Schema::table('submission_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('submission_answers', 'band_score')) {
                $table->decimal('band_score', 3, 1)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'band_score')) {
                $table->dropColumn('band_score');
            }
        });

        Schema::table('submission_answers', function (Blueprint $table) {
            if (Schema::hasColumn('submission_answers', 'band_score')) {
                $table->dropColumn('band_score');
            }
        });
    }
};
