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
            $table->foreignId('practice_test_id')
                ->nullable()
                ->after('assignment_id')
                ->constrained('practice_tests')
                ->nullOnDelete();

            $table->index(['practice_test_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['practice_test_id']);
            $table->dropIndex(['practice_test_id', 'user_id']);
            $table->dropColumn('practice_test_id');
        });
    }
};
