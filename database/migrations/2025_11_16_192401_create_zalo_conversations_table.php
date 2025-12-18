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
        Schema::create('zalo_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zalo_account_id')->constrained('zalo_accounts')->onDelete('cascade');
            $table->string('recipient_id')->comment('Zalo user ID or group ID');
            $table->enum('recipient_type', ['user', 'group'])->default('user');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_avatar_url')->nullable();

            // Assignment fields
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->comment('User who created/initiated this conversation');

            // Conversation metadata
            $table->timestamp('last_message_at')->nullable()->comment('Timestamp of last message in this conversation');
            $table->integer('unread_count')->default(0)->comment('Number of unread messages');
            $table->text('last_message_preview')->nullable()->comment('Preview of last message');
            $table->json('metadata')->nullable()->comment('Additional conversation data');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('zalo_account_id');
            $table->index('recipient_id');
            $table->index(['zalo_account_id', 'recipient_id', 'recipient_type'], 'idx_account_recipient');
            $table->index('branch_id');
            $table->index('department_id');
            $table->index('created_by');
            $table->index('last_message_at');

            // Unique constraint: One conversation per account-recipient pair
            $table->unique(['zalo_account_id', 'recipient_id', 'recipient_type'], 'unique_conversation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_conversations');
    }
};
