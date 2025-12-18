<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('semester_id');
            $table->date('end_date')->nullable()->after('start_date');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('capacity')->comment('Học phí mỗi giờ');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'hourly_rate']);
        });
    }
};
