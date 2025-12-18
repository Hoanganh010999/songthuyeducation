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
        // Update behavior enum to Vietnamese
        DB::statement("ALTER TABLE session_comments MODIFY COLUMN behavior ENUM('Tốt', 'TB', 'Cần nhắc nhở') NULL");
        
        // Update participation enum to Vietnamese (including 'Tốt' option)
        DB::statement("ALTER TABLE session_comments MODIFY COLUMN participation ENUM('Tích cực', 'Tốt', 'Bình thường', 'Ít tham gia', 'Thụ động', 'Không tham gia') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to English enum values
        DB::statement("ALTER TABLE session_comments MODIFY COLUMN behavior ENUM('excellent', 'good', 'average', 'needs_improvement') NULL");
        DB::statement("ALTER TABLE session_comments MODIFY COLUMN participation ENUM('active', 'moderate', 'passive') NULL");
    }
};

