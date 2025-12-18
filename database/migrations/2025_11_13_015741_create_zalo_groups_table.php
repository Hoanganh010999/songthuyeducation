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
        Schema::create('zalo_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zalo_account_id')->constrained('zalo_accounts')->onDelete('cascade');
            $table->string('zalo_group_id')->comment('Zalo group ID');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('avatar_url')->nullable()->comment('Original avatar URL from Zalo');
            $table->string('avatar_path')->nullable()->comment('Local cached avatar file path');
            $table->integer('members_count')->default(0);
            $table->json('admin_ids')->nullable()->comment('Array of admin user IDs');
            $table->string('creator_id')->nullable();
            $table->string('type')->nullable();
            $table->integer('version')->nullable();
            $table->json('metadata')->nullable()->comment('Additional group data');
            $table->timestamp('last_sync_at')->nullable()->comment('Last sync of members');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['zalo_account_id', 'zalo_group_id']);
            $table->index('zalo_account_id');
            $table->index('zalo_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_groups');
    }
};
