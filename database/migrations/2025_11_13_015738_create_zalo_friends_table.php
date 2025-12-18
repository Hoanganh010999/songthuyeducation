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
        Schema::create('zalo_friends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zalo_account_id')->constrained('zalo_accounts')->onDelete('cascade');
            $table->string('zalo_user_id')->comment('Zalo friend user ID');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('avatar_url')->nullable()->comment('Original avatar URL from Zalo');
            $table->string('avatar_path')->nullable()->comment('Local cached avatar file path');
            $table->text('bio')->nullable();
            $table->json('metadata')->nullable()->comment('Additional friend data');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['zalo_account_id', 'zalo_user_id']);
            $table->index('zalo_account_id');
            $table->index('zalo_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_friends');
    }
};
