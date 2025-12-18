<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('zalo_messages', function (Blueprint $table) {
            // Index riêng cho message_id để tìm nhanh
            $table->index(['zalo_account_id', 'message_id'], 'idx_account_message_id');
            
            // Index cho recipient_id và sent_at để tìm trong conversation
            $table->index(['zalo_account_id', 'recipient_id', 'sent_at'], 'idx_account_recipient_time');
        });
        
        // Composite unique index bằng raw SQL vì JSON column
        // Lưu ý: MySQL không hỗ trợ unique index trực tiếp với JSON column
        // Nên chúng ta sẽ dùng unique index trên (zalo_account_id, message_id)
        // và index riêng cho cliMsgId trong metadata
        try {
            DB::statement('
                CREATE INDEX idx_account_cli_msg_id 
                ON zalo_messages (zalo_account_id, (CAST(metadata->>"$.cliMsgId" AS CHAR(255))))
            ');
        } catch (\Exception $e) {
            // Index might already exist, skip
            \Log::warning('Index idx_account_cli_msg_id might already exist: ' . $e->getMessage());
        }
        
        // Note: We skip unique constraint on (zalo_account_id, message_id) because:
        // 1. message_id can be null for some messages
        // 2. MySQL doesn't support partial unique index with WHERE clause in Laravel migration
        // 3. We'll rely on application logic (updateOrCreate) to prevent duplicates
        // The indexes above are sufficient for fast lookups
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_messages', function (Blueprint $table) {
            $table->dropIndex('idx_account_message_id');
            $table->dropIndex('idx_account_recipient_time');
        });
        
        try {
            DB::statement('DROP INDEX IF EXISTS idx_account_cli_msg_id ON zalo_messages');
        } catch (\Exception $e) {
            // Index might not exist, skip
        }
    }
};
