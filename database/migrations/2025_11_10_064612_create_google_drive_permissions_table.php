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
        Schema::create('google_drive_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('google_drive_item_id')->constrained('google_drive_items')->onDelete('cascade');
            $table->string('google_permission_id')->nullable()->comment('Permission ID from Google Drive API');
            $table->string('role')->default('reader')->comment('reader, commenter, writer, fileOrganizer, organizer, owner');
            $table->boolean('is_verified')->default(false)->comment('Whether permission is verified on Google Drive');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('synced_at')->nullable()->comment('Last sync from Google Drive');
            $table->timestamps();
            
            // Unique constraint: one permission per user per folder/file
            $table->unique(['user_id', 'google_drive_item_id'], 'user_item_unique');
            
            // Indexes for performance
            $table->index('is_verified');
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_drive_permissions');
    }
};
