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
        Schema::table('classes', function (Blueprint $table) {
            $table->string('google_drive_folder_id')->nullable()->after('lesson_plan_id')
                ->comment('Google Drive folder ID for class materials');
            $table->string('google_drive_folder_name')->nullable()->after('google_drive_folder_id')
                ->comment('Google Drive folder name (Class Name - CODE)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['google_drive_folder_id', 'google_drive_folder_name']);
        });
    }
};
