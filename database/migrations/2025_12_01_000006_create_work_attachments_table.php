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
        Schema::create('work_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable'); // work_items, work_discussions, work_submissions
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');

            $table->string('file_name');
            $table->string('file_path'); // Google Drive file ID hoặc path
            $table->string('file_type', 100);
            $table->bigInteger('file_size'); // bytes
            $table->string('mime_type', 100);

            // Google Drive integration
            $table->string('google_drive_id')->nullable();
            $table->string('google_drive_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_attachments');
    }
};
