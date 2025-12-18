<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft')->after('is_active');
            
            // Make academic_year nullable
            $table->string('academic_year')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('status');
            
            // Revert academic_year to not nullable
            $table->string('academic_year')->nullable(false)->change();
        });
    }
};
