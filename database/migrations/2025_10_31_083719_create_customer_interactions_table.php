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
        Schema::create('customer_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Nhân viên thực hiện
            $table->foreignId('interaction_type_id')->nullable()->constrained('customer_interaction_types')->onDelete('set null');
            $table->foreignId('interaction_result_id')->nullable()->constrained('customer_interaction_results')->onDelete('set null');
            $table->text('notes'); // Ghi chú của nhân viên
            $table->datetime('interaction_date'); // Ngày giờ tương tác
            $table->datetime('next_follow_up')->nullable(); // Ngày hẹn tiếp theo (nếu có)
            $table->json('metadata')->nullable(); // Thông tin bổ sung
            $table->timestamps();
            
            // Indexes
            $table->index('customer_id');
            $table->index('user_id');
            $table->index('interaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_interactions');
    }
};
