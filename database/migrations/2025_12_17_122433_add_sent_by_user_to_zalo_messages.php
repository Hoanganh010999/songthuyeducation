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
            $table->foreignId('sent_by_user_id')
                ->nullable()
                ->after('sender_name')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('App user who sent this message (for sent messages only)');
            
            $table->index('sent_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_messages', function (Blueprint $table) {
            $table->dropForeign(['sent_by_user_id']);
            $table->dropIndex(['sent_by_user_id']);
            $table->dropColumn('sent_by_user_id');
        });
    }
};
