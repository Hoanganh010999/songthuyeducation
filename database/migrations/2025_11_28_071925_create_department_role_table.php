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
        Schema::create('department_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();

            // Ensure unique department-role combination
            $table->unique(['department_id', 'role_id']);
        });

        // Auto-assign sale role to "Tư vấn tuyển sinh" department (if exists)
        $saleRole = DB::table('roles')->where('name', 'sale')->first();
        $tvtsDepart = DB::table('departments')->where('name', 'Tư vấn tuyển sinh')->first();

        if ($saleRole && $tvtsDepart) {
            DB::table('department_role')->insert([
                'department_id' => $tvtsDepart->id,
                'role_id' => $saleRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_role');
    }
};
