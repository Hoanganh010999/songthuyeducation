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
        Schema::table('department_user', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropForeign(['position_id']);
            
            // Drop the unique constraint that includes position_id
            $table->dropUnique(['department_id', 'user_id', 'position_id']);
            
            // Make position_id nullable
            $table->foreignId('position_id')->nullable()->change();
            
            // Re-add the foreign key
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('set null');
            
            // Add new unique constraint without position_id (a user can only be in a department once)
            $table->unique(['department_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_user', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique(['department_id', 'user_id']);
            
            // Drop the foreign key
            $table->dropForeign(['position_id']);
            
            // Make position_id NOT NULL again
            $table->foreignId('position_id')->nullable(false)->change();
            
            // Re-add the old foreign key
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('cascade');
            
            // Re-add the old unique constraint
            $table->unique(['department_id', 'user_id', 'position_id']);
        });
    }
};
