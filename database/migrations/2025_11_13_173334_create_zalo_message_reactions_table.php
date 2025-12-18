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
        Schema::create('zalo_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zalo_message_id')->constrained('zalo_messages')->onDelete('cascade');
            $table->string('zalo_message_id_external')->nullable()->comment('Zalo message ID (msgId)');
            $table->string('zalo_user_id')->comment('User who added the reaction');
            $table->string('reaction_icon')->comment('Reaction icon/type (e.g., HEART, LIKE, HAHA)');
            $table->integer('reaction_type')->default(0)->comment('Reaction type number');
            $table->integer('reaction_source')->default(0)->comment('Reaction source number');
            $table->json('reaction_data')->nullable()->comment('Full reaction data from Zalo API');
            $table->timestamp('reacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint: one user can only have one reaction per message
            $table->unique(['zalo_message_id', 'zalo_user_id']);
            $table->index('zalo_message_id');
            $table->index('zalo_user_id');
            $table->index('reaction_icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_message_reactions');
    }
};
