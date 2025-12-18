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
        Schema::create('zalo_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Account display name');
            $table->string('phone')->nullable()->comment('Phone number');
            $table->string('zalo_id')->unique()->comment('Zalo user ID');
            $table->text('cookie')->nullable()->comment('Zalo session cookie (encrypted)');
            $table->string('imei')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('avatar_url')->nullable()->comment('Account avatar URL');
            $table->string('avatar_path')->nullable()->comment('Local avatar file path');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_connected')->default(false)->comment('WebSocket connection status');
            $table->timestamp('last_sync_at')->nullable()->comment('Last sync of friends/groups');
            $table->timestamp('last_login_at')->nullable();
            $table->json('metadata')->nullable()->comment('Additional account data');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('zalo_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_accounts');
    }
};
