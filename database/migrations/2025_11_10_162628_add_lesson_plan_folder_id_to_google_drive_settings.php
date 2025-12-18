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
        Schema::table('google_drive_settings', function (Blueprint $table) {
            $table->string('lesson_plan_folder_id')->nullable()->after('syllabus_folder_name');
            $table->string('lesson_plan_folder_name')->default('Lesson Plan')->after('lesson_plan_folder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('google_drive_settings', function (Blueprint $table) {
            $table->dropColumn(['lesson_plan_folder_id', 'lesson_plan_folder_name']);
        });
    }
};
