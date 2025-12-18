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
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->after('branch_id')->constrained('users')->onDelete('set null')->comment('Employee/User assigned to manage this Zalo account');
            
            $table->index('branch_id');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['assigned_to']);
            $table->dropColumn(['branch_id', 'assigned_to']);
        });
    }
};

