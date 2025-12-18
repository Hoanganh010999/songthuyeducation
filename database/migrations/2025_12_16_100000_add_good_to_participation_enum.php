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
        // For MySQL, we need to alter the enum column
        DB::statement("ALTER TABLE session_comments MODIFY COLUMN participation ENUM('Tích cực', 'Tốt', 'Bình thường', 'Ít tham gia', 'Thụ động', 'Không tham gia') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE session_comments MODIFY COLUMN participation ENUM('Tích cực', 'Bình thường', 'Ít tham gia', 'Thụ động', 'Không tham gia') NULL");
    }
};

