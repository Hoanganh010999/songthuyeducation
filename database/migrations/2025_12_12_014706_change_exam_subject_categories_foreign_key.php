<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_subject_categories', function (Blueprint $table) {
            // Drop old foreign key constraint
            $table->dropForeign(['subject_id']);
            
            // Add new foreign key constraint to subjects table
            $table->foreign('subject_id')
                  ->references('id')
                  ->on('subjects')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('exam_subject_categories', function (Blueprint $table) {
            // Drop subjects foreign key
            $table->dropForeign(['subject_id']);
            
            // Restore exam_subjects foreign key
            $table->foreign('subject_id')
                  ->references('id')
                  ->on('exam_subjects')
                  ->onDelete('cascade');
        });
    }
};
