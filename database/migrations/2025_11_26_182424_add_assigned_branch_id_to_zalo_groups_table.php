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
        Schema::table('zalo_groups', function (Blueprint $table) {
            // Add assigned_branch_id column for branch assignment feature
            $table->unsignedBigInteger('assigned_branch_id')->nullable()->after('branch_id');
            $table->foreign('assigned_branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->index('assigned_branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_groups', function (Blueprint $table) {
            $table->dropForeign(['assigned_branch_id']);
            $table->dropIndex(['assigned_branch_id']);
            $table->dropColumn('assigned_branch_id');
        });
    }
};
