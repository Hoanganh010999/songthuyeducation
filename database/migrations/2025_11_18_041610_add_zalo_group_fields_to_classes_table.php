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
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('zalo_account_id')->nullable()->after('homeroom_teacher_id')->constrained('zalo_accounts')->nullOnDelete()->comment('Zalo account used for this class group');
            $table->string('zalo_group_id')->nullable()->after('zalo_account_id')->comment('Zalo group ID for class chat');
            $table->string('zalo_group_name')->nullable()->after('zalo_group_id')->comment('Zalo group name for display');

            $table->index('zalo_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['zalo_account_id']);
            $table->dropIndex(['zalo_group_id']);
            $table->dropColumn(['zalo_account_id', 'zalo_group_id', 'zalo_group_name']);
        });
    }
};
