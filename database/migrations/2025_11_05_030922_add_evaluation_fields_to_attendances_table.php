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
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('homework_score')->nullable()->after('check_in_time')->comment('Điểm bài tập 1-10');
            $table->integer('participation_score')->nullable()->after('homework_score')->comment('Điểm tương tác 1-10');
            $table->json('evaluation_data')->nullable()->after('participation_score')->comment('Dữ liệu đánh giá từ evaluation form');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['homework_score', 'participation_score', 'evaluation_data']);
        });
    }
};
