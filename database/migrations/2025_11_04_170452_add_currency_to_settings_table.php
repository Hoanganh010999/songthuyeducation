<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create settings table if not exists
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string'); // string, number, boolean, json
                $table->string('group')->default('general');
                $table->text('description')->nullable();
                $table->timestamps();
        });
    }

        // Insert default currency settings
        DB::table('settings')->insert([
            [
                'key' => 'currency',
                'value' => 'VND',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default currency for the system',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'currency_symbol',
                'value' => 'VNĐ',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Currency symbol display',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'currency_decimals',
                'value' => '0',
                'type' => 'number',
                'group' => 'general',
                'description' => 'Number of decimal places (0 for VND, 2 for USD)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'thousand_separator',
                'value' => ',',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Thousand separator character',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'decimal_separator',
                'value' => '.',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Decimal separator character',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down(): void
    {
        // Delete currency settings
        DB::table('settings')->whereIn('key', [
            'currency',
            'currency_symbol',
            'currency_decimals',
            'thousand_separator',
            'decimal_separator'
        ])->delete();
    }
};
