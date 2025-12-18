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
        Schema::table('positions', function (Blueprint $table) {
            // Drop the old unique constraint on code only
            $table->dropUnique('positions_code_unique');

            // Add composite unique constraint on (code, branch_id)
            $table->unique(['code', 'branch_id'], 'positions_code_branch_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            // Drop composite unique constraint
            $table->dropUnique('positions_code_branch_unique');

            // Restore original unique constraint on code only
            $table->unique('code', 'positions_code_unique');
        });
    }
};
