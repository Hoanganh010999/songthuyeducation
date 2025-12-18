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
        Schema::table('class_lesson_sessions', function (Blueprint $table) {
            // Add teacher_id to override the default teacher from class_schedule
            $table->unsignedBigInteger('teacher_id')->nullable()->after('class_schedule_id');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('set null');

            $table->index('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_lesson_sessions', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn('teacher_id');
        });
    }
};
