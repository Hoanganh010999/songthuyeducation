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
        Schema::create('zalo_account_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zalo_account_id');
            $table->unsignedBigInteger('branch_id');
            $table->enum('role', ['owner', 'shared'])->default('shared');

            // Granular permissions (8 permissions)
            $table->boolean('can_send_to_customers')->default(false);
            $table->boolean('can_send_to_teachers')->default(false);
            $table->boolean('can_send_to_class_groups')->default(false);
            $table->boolean('can_send_to_friends')->default(false);
            $table->boolean('can_send_to_groups')->default(false);
            $table->boolean('view_all_friends')->default(false);
            $table->boolean('view_all_groups')->default(false);
            $table->boolean('view_all_conversations')->default(false);

            $table->timestamps();

            // Foreign keys
            $table->foreign('zalo_account_id')->references('id')->on('zalo_accounts')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');

            // Unique constraint: One record per account-branch combination
            $table->unique(['zalo_account_id', 'branch_id']);

            // Indexes for performance
            $table->index('zalo_account_id');
            $table->index('branch_id');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_account_branches');
    }
};
