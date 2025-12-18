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
            $table->string('syllabus_folder_id')->nullable()->after('school_drive_folder_name');
            $table->string('syllabus_folder_name')->default('Syllabus')->after('syllabus_folder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('google_drive_settings', function (Blueprint $table) {
            $table->dropColumn(['syllabus_folder_id', 'syllabus_folder_name']);
        });
    }
};
