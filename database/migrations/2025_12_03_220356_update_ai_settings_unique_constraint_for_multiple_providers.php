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
        // Use raw SQL to handle the constraint change
        // Step 1: Drop the foreign key constraint
        DB::statement('ALTER TABLE ai_settings DROP FOREIGN KEY ai_settings_branch_id_foreign');

        // Step 2: Drop the old unique index
        DB::statement('ALTER TABLE ai_settings DROP INDEX ai_settings_branch_id_module_unique');

        // Step 3: Add the new unique index (branch_id + module + provider)
        DB::statement('ALTER TABLE ai_settings ADD UNIQUE KEY ai_settings_branch_id_module_provider_unique (branch_id, module, provider)');

        // Step 4: Recreate the foreign key constraint
        DB::statement('ALTER TABLE ai_settings ADD CONSTRAINT ai_settings_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Use raw SQL to reverse the constraint change
        // Step 1: Drop the foreign key constraint
        DB::statement('ALTER TABLE ai_settings DROP FOREIGN KEY ai_settings_branch_id_foreign');

        // Step 2: Drop the new unique index
        DB::statement('ALTER TABLE ai_settings DROP INDEX ai_settings_branch_id_module_provider_unique');

        // Step 3: Restore the old unique index (branch_id + module only)
        DB::statement('ALTER TABLE ai_settings ADD UNIQUE KEY ai_settings_branch_id_module_unique (branch_id, module)');

        // Step 4: Recreate the foreign key constraint
        DB::statement('ALTER TABLE ai_settings ADD CONSTRAINT ai_settings_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE');
    }
};
