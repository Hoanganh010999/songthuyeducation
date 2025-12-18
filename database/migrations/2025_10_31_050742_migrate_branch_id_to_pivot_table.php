<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing branch_id data to pivot table
        $users = DB::table('users')->whereNotNull('branch_id')->get();
        
        foreach ($users as $user) {
            DB::table('branch_user')->insert([
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'is_primary' => true, // Đánh dấu là chi nhánh chính
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Drop old branch_id column from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back branch_id column
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('language_id')->constrained('branches')->nullOnDelete();
        });
        
        // Migrate data back from pivot table (only primary branches)
        $pivotData = DB::table('branch_user')->where('is_primary', true)->get();
        
        foreach ($pivotData as $pivot) {
            DB::table('users')
                ->where('id', $pivot->user_id)
                ->update(['branch_id' => $pivot->branch_id]);
        }
    }
};
