<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_user', function (Blueprint $table) {
            // Manager in this department/branch context
            $table->foreignId('manager_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            
            // Hierarchy level within this department (0 = department head)
            $table->integer('dept_hierarchy_level')->default(999)->after('manager_user_id');
            
            // Index for hierarchy queries
            $table->index(['department_id', 'manager_user_id', 'dept_hierarchy_level'], 'dept_hierarchy_idx');
        });
    }

    public function down(): void
    {
        Schema::table('department_user', function (Blueprint $table) {
            $table->dropForeign(['manager_user_id']);
            $table->dropIndex('dept_hierarchy_idx');
            $table->dropColumn(['manager_user_id', 'dept_hierarchy_level']);
        });
    }
};
