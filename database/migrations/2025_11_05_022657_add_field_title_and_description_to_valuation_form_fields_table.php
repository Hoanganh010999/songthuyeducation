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
        Schema::table('valuation_form_fields', function (Blueprint $table) {
            $table->string('field_title')->nullable()->after('field_type');
            $table->text('field_description')->nullable()->after('field_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('valuation_form_fields', function (Blueprint $table) {
            $table->dropColumn(['field_title', 'field_description']);
        });
    }
};
