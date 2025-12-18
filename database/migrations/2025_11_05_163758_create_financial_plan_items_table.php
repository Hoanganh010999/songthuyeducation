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
        Schema::create('financial_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_plan_id')->constrained('financial_plans')->onDelete('cascade')->comment('Kế hoạch');
            $table->foreignId('account_item_id')->constrained('account_items')->onDelete('cascade')->comment('Khoản thu chi');
            $table->enum('type', ['income', 'expense'])->comment('Thu/Chi');
            $table->decimal('planned_amount', 15, 2)->comment('Số tiền dự kiến');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->timestamps();
            
            // Indexes
            $table->index('financial_plan_id');
            $table->index('account_item_id');
            $table->index('type');
            $table->unique(['financial_plan_id', 'account_item_id'], 'unique_plan_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_plan_items');
    }
};
