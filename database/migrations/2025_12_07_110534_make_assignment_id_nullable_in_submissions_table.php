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
        Schema::table('submissions', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['assignment_id']);
            
            // Make assignment_id nullable
            $table->foreignId('assignment_id')->nullable()->change();
            
            // Re-add the foreign key constraint
            $table->foreign('assignment_id')
                ->references('id')
                ->on('assignments')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Drop the nullable foreign key
            $table->dropForeign(['assignment_id']);
            
            // Make assignment_id not nullable again
            $table->foreignId('assignment_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint with cascade
            $table->foreign('assignment_id')
                ->references('id')
                ->on('assignments')
                ->cascadeOnDelete();
        });
    }
};
