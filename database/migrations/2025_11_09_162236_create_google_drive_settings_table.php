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
        Schema::create('google_drive_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('name')->default('Google Drive Configuration');
            $table->text('client_id')->nullable(); // Google OAuth Client ID
            $table->text('client_secret')->nullable(); // Google OAuth Client Secret
            $table->text('refresh_token')->nullable(); // OAuth Refresh Token
            $table->text('access_token')->nullable(); // OAuth Access Token
            $table->timestamp('token_expires_at')->nullable(); // Token expiry time
            $table->string('school_drive_folder_id')->nullable(); // ID của folder "School Drive" trên Google Drive
            $table->string('school_drive_folder_name')->default('School Drive'); // Tên folder
            $table->boolean('is_active')->default(false); // Cài đặt có đang active không
            $table->timestamp('last_synced_at')->nullable(); // Lần sync cuối cùng
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            
            // Index
            $table->index('branch_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_drive_settings');
    }
};
