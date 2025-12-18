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
        Schema::create('zalo_conversation_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zalo_conversation_id')->constrained('zalo_conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Permissions
            $table->boolean('can_view')->default(true)->comment('Can view conversation and messages');
            $table->boolean('can_reply')->default(true)->comment('Can send replies in this conversation');

            // Assignment info
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null')->comment('Who assigned this user');
            $table->timestamp('assigned_at')->nullable();
            $table->text('assignment_note')->nullable()->comment('Note about why this user was assigned');

            $table->timestamps();

            // Indexes
            $table->index('zalo_conversation_id');
            $table->index('user_id');
            $table->index(['zalo_conversation_id', 'user_id'], 'idx_conversation_user');

            // Unique constraint: User can only be assigned once per conversation
            $table->unique(['zalo_conversation_id', 'user_id'], 'unique_conversation_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_conversation_users');
    }
};
