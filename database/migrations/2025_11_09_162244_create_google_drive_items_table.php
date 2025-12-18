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
        Schema::create('google_drive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('google_id')->unique(); // ID của file/folder trên Google Drive
            $table->string('name'); // Tên file/folder
            $table->enum('type', ['file', 'folder'])->default('file'); // Loại
            $table->string('mime_type')->nullable(); // MIME type của file
            $table->string('parent_id')->nullable(); // ID của folder cha (null = root của School Drive)
            $table->bigInteger('size')->nullable(); // Kích thước file (bytes)
            $table->string('web_view_link')->nullable(); // Link xem file trên Google Drive
            $table->string('web_content_link')->nullable(); // Link download file
            $table->string('thumbnail_link')->nullable(); // Link thumbnail (cho hình ảnh/video)
            $table->string('icon_link')->nullable(); // Link icon của file type
            $table->timestamp('google_created_at')->nullable(); // Thời gian tạo trên Google Drive
            $table->timestamp('google_modified_at')->nullable(); // Thời gian sửa đổi trên Google Drive
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // User tạo
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null'); // User cập nhật
            $table->boolean('is_trashed')->default(false); // File đã bị xóa chưa
            $table->timestamp('trashed_at')->nullable(); // Thời gian xóa
            $table->json('permissions')->nullable(); // Quyền của file (share settings)
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            
            // Indexes
            $table->index('branch_id');
            $table->index('google_id');
            $table->index('parent_id');
            $table->index('type');
            $table->index('is_trashed');
            $table->index(['parent_id', 'name']); // Composite index for folder listing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_drive_items');
    }
};
