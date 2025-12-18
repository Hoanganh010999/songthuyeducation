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
            $table->boolean('is_recalled')->default(false)->after('content');
            $table->timestamp('recalled_at')->nullable()->after('is_recalled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_messages', function (Blueprint $table) {
            $table->dropColumn(['is_recalled', 'recalled_at']);
        });
    }
};

