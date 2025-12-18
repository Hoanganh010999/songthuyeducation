<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create pivot table for Many-to-Many relationship between zalo_groups and branches.
     * This allows one group to be assigned to multiple branches with different departments.
     */
    public function up(): void
    {
        Schema::create('zalo_group_branches', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('zalo_group_id')->comment('Reference to shared zalo_groups.id');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');

            // Assignment tracking
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();

            $table->timestamps();

            // Unique constraint: one group can only be assigned to a branch once
            $table->unique(['zalo_group_id', 'branch_id'], 'zalo_group_branch_unique');

            // Indexes for performance
            $table->index('zalo_group_id');
            $table->index('branch_id');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_group_branches');
    }
};
