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
        // 1. Modify post_type enum to add 'homework'
        DB::statement("ALTER TABLE course_posts MODIFY COLUMN post_type ENUM('text','announcement','material','assignment','event','homework') NOT NULL");
        
        // 2. Add metadata column for storing additional post data
        Schema::table('course_posts', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('event_attendees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_posts', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
        
        // Restore old enum
        DB::statement("ALTER TABLE course_posts MODIFY COLUMN post_type ENUM('text','announcement','material','assignment','event') NOT NULL");
    }
};
