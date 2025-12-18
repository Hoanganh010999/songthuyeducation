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
            $table->string('google_drive_folder_id')->nullable()->after('notes');
            $table->string('google_drive_folder_name')->nullable()->after('google_drive_folder_id');
            $table->string('lesson_plan_file_id')->nullable()->after('google_drive_folder_name');
            $table->string('materials_folder_id')->nullable()->after('lesson_plan_file_id');
            $table->string('homework_folder_id')->nullable()->after('materials_folder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_plan_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'google_drive_folder_id',
                'google_drive_folder_name',
                'lesson_plan_file_id',
                'materials_folder_id',
                'homework_folder_id'
            ]);
        });
    }
};
