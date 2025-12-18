<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Direct manager for hierarchy
            $table->foreignId('manager_id')->nullable()->after('email')->constrained('users')->nullOnDelete();
            
            // Hierarchy level (0 = top, higher = lower in hierarchy)
            $table->integer('hierarchy_level')->default(999)->after('manager_id');
            
            // Index for faster hierarchy queries
            $table->index(['manager_id', 'hierarchy_level']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropIndex(['manager_id', 'hierarchy_level']);
            $table->dropColumn(['manager_id', 'hierarchy_level']);
        });
    }
};
