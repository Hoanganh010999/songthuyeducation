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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_email')) {
                $table->string('google_email')->nullable()->after('email')->comment('Google email for Drive access');
            }
            if (!Schema::hasColumn('users', 'google_drive_folder_id')) {
                $table->string('google_drive_folder_id')->nullable()->after('google_email')->comment('Personal Google Drive folder ID');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_email', 'google_drive_folder_id']);
        });
    }
};
