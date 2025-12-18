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
        Schema::create('zalo_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zalo_group_id')->constrained('zalo_groups')->onDelete('cascade');
            $table->string('zalo_user_id')->comment('Zalo user ID of member');
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('avatar_path')->nullable()->comment('Local cached avatar');
            $table->boolean('is_admin')->default(false);
            $table->json('metadata')->nullable()->comment('Additional member data');
            $table->timestamps();
            
            $table->unique(['zalo_group_id', 'zalo_user_id']);
            $table->index('zalo_group_id');
            $table->index('zalo_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_group_members');
    }
};
