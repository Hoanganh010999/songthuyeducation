<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_plan_sessions', function (Blueprint $table) {
            $table->foreignId('valuation_form_id')->nullable()->after('homework_url')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_plan_sessions', function (Blueprint $table) {
            $table->dropForeign(['valuation_form_id']);
            $table->dropColumn('valuation_form_id');
        });
    }
};
