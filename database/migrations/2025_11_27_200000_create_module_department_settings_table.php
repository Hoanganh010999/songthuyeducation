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
        Schema::create('module_department_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('module', 50); // 'customers', 'enrollments', etc.
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Each branch + module can only have one department
            $table->unique(['branch_id', 'module']);
        });

        // Add permission for managing module department settings
        DB::table('permissions')->insert([
            [
                'module' => 'customers',
                'action' => 'department_settings',
                'name' => 'customers.department_settings',
                'display_name' => 'Thiết lập phòng ban phụ trách Khách hàng',
                'description' => 'Cho phép thiết lập phòng ban nào phụ trách module Khách hàng',
                'sort_order' => 10,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_department_settings');

        DB::table('permissions')->where('name', 'customers.department_settings')->delete();
    }
};
