<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_lesson_sessions', function (Blueprint $table) {
            $table->integer('session_number')->after('class_schedule_id')->default(1);
            $table->index('session_number');
        });
    }

    public function down(): void
    {
        Schema::table('class_lesson_sessions', function (Blueprint $table) {
            $table->dropIndex(['session_number']);
            $table->dropColumn('session_number');
        });
    }
};
