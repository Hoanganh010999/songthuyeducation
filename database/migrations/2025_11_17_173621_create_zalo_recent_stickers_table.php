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
        Schema::create('zalo_recent_stickers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zalo_account_id')->constrained('zalo_accounts')->onDelete('cascade');
            $table->string('sticker_id'); // Zalo sticker ID
            $table->string('cate_id')->nullable();
            $table->integer('type')->nullable();
            $table->string('text')->nullable();
            $table->text('sticker_url')->nullable();
            $table->text('sticker_webp_url')->nullable();
            $table->text('sticker_sprite_url')->nullable();
            $table->text('uri')->nullable();
            $table->timestamp('last_used_at')->useCurrent();
            $table->integer('use_count')->default(1);
            $table->timestamps();

            // Unique index: one account can only have one record per sticker_id
            $table->unique(['zalo_account_id', 'sticker_id']);

            // Index for quick lookup of recent stickers
            $table->index(['zalo_account_id', 'last_used_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_recent_stickers');
    }
};
