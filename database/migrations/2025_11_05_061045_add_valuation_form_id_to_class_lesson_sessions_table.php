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
        Schema::table('class_lesson_sessions', function (Blueprint $table) {
            $table->foreignId('valuation_form_id')->nullable()->after('duration_minutes')->constrained('valuation_forms')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_lesson_sessions', function (Blueprint $table) {
            $table->dropForeign(['valuation_form_id']);
            $table->dropColumn('valuation_form_id');
        });
    }
};
