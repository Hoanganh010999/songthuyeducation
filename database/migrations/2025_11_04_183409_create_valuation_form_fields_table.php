<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valuation_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valuation_form_id')->constrained()->onDelete('cascade');
            $table->enum('field_type', ['text', 'checkbox', 'dropdown']); // 3 loại field
            $table->string('field_label'); // Nhãn của field: "Đánh giá hành vi"
            $table->json('field_config')->nullable(); // Cấu hình cho field
            // field_config có thể chứa:
            // - text: { "font_family": "Arial", "font_size": 14, "is_bold": false, "is_title": false }
            // - checkbox: { "default_checked": false }
            // - dropdown: { "options": ["Tốt", "Khá", "Trung bình", "Yếu"] }
            $table->json('field_options')->nullable(); // Options cho dropdown (deprecated, dùng field_config)
            $table->integer('sort_order')->default(0); // Thứ tự hiển thị
            $table->boolean('is_required')->default(false);
            $table->timestamps();
            
            $table->index(['valuation_form_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valuation_form_fields');
    }
};
