<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change enum to include 'event'
        DB::statement("ALTER TABLE course_posts MODIFY COLUMN post_type ENUM('text', 'announcement', 'material', 'assignment', 'event') NOT NULL DEFAULT 'text'");
    }

    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE course_posts MODIFY COLUMN post_type ENUM('text', 'announcement', 'material', 'assignment') NOT NULL DEFAULT 'text'");
    }
};
