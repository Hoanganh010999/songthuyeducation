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
        Schema::create('zalo_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zalo_account_id')->constrained('zalo_accounts')->onDelete('cascade');
            $table->string('message_id')->nullable()->comment('Zalo message ID if available');
            $table->enum('type', ['sent', 'received'])->default('sent');
            $table->enum('recipient_type', ['user', 'group'])->default('user');
            $table->string('recipient_id')->comment('Zalo user ID or group ID');
            $table->string('recipient_name')->nullable();
            $table->text('content');
            $table->enum('content_type', ['text', 'image', 'file', 'link', 'other'])->default('text');
            $table->string('media_url')->nullable()->comment('URL if message contains media');
            $table->string('media_path')->nullable()->comment('Local file path if downloaded');
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable()->comment('Additional message data');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('zalo_account_id');
            $table->index('recipient_id');
            $table->index('type');
            $table->index('sent_at');
            $table->index(['zalo_account_id', 'recipient_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_messages');
    }
};
