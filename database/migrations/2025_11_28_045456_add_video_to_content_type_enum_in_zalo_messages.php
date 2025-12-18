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
        // Use raw SQL to modify ENUM column to add 'video' type
        DB::statement("ALTER TABLE zalo_messages MODIFY COLUMN content_type ENUM('text', 'image', 'file', 'video', 'link', 'sticker') NOT NULL DEFAULT 'text'");

        // Update existing video messages from 'file' to 'video'
        DB::statement("UPDATE zalo_messages SET content_type = 'video' WHERE content_type = 'file' AND media_url LIKE '%video-%dlmd.me%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert video messages back to 'file'
        DB::statement("UPDATE zalo_messages SET content_type = 'file' WHERE content_type = 'video'");

        // Remove 'video' from ENUM
        DB::statement("ALTER TABLE zalo_messages MODIFY COLUMN content_type ENUM('text', 'image', 'file', 'link', 'sticker') NOT NULL DEFAULT 'text'");
    }
};
