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
            // Quote/reply information
            $table->foreignId('reply_to_message_id')->nullable()->after('message_id')
                ->constrained('zalo_messages')->onDelete('set null')
                ->comment('Reference to the message being replied to');
            $table->string('reply_to_zalo_message_id')->nullable()->after('reply_to_message_id')
                ->comment('Original Zalo message ID being replied to');
            $table->string('reply_to_cli_msg_id')->nullable()->after('reply_to_zalo_message_id')
                ->comment('Original Zalo CLI message ID being replied to');
            $table->json('quote_data')->nullable()->after('reply_to_cli_msg_id')
                ->comment('Full quote data from Zalo API');
            
            $table->index('reply_to_message_id');
            $table->index('reply_to_zalo_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_messages', function (Blueprint $table) {
            $table->dropIndex(['reply_to_zalo_message_id']);
            $table->dropIndex(['reply_to_message_id']);
            $table->dropForeign(['reply_to_message_id']);
            $table->dropColumn(['reply_to_message_id', 'reply_to_zalo_message_id', 'reply_to_cli_msg_id', 'quote_data']);
        });
    }
};
