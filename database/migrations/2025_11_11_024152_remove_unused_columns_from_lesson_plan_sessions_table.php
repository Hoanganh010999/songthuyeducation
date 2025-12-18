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
        Schema::table('lesson_plan_sessions', function (Blueprint $table) {
            // Remove unused columns
            // google_drive_folder_name: Can be generated from "Unit {number}"
            // lesson_plan_file_id: Replaced by lesson_plans_folder_id in new system
            $table->dropColumn([
                'google_drive_folder_name',
                'lesson_plan_file_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_plan_sessions', function (Blueprint $table) {
            $table->string('google_drive_folder_name')->nullable()->after('google_drive_folder_id');
            $table->string('lesson_plan_file_id')->nullable()->after('google_drive_folder_name');
        });
    }
};
