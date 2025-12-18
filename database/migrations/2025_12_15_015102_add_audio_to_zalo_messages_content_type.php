<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the content_type enum to include 'audio'
        DB::statement("ALTER TABLE zalo_messages MODIFY COLUMN content_type ENUM('text', 'image', 'file', 'video', 'audio', 'link', 'sticker') NOT NULL DEFAULT 'text'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'audio' from the enum
        DB::statement("ALTER TABLE zalo_messages MODIFY COLUMN content_type ENUM('text', 'image', 'file', 'video', 'link', 'sticker') NOT NULL DEFAULT 'text'");
    }
};
