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
        Schema::table('zalo_messages', function (Blueprint $table) {
            $table->foreignId('zalo_conversation_id')->nullable()->after('zalo_account_id')
                ->constrained('zalo_conversations')->onDelete('cascade')
                ->comment('Link to conversation');

            $table->index('zalo_conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_messages', function (Blueprint $table) {
            $table->dropForeign(['zalo_conversation_id']);
            $table->dropIndex(['zalo_conversation_id']);
            $table->dropColumn('zalo_conversation_id');
        });
    }
};
