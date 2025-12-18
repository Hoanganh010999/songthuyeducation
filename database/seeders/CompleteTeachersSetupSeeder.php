<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CompleteTeachersSetupSeeder extends Seeder
{
    /**
     * Run all teacher-related seeders in correct order
     */
    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('Starting Complete Teachers Setup');
        $this->command->info('========================================');

        // Step 1: Create teacher positions with codes
        $this->command->info("\n[1/3] Creating teacher positions...");
        $this->call(TeacherPositionsSeeder::class);

        // Step 2: Create teachers and assign to branches/departments
        $this->command->info("\n[2/3] Creating teachers and assignments...");
        $this->call(TeachersSeeder::class);

        // Step 3: Setup teacher position codes settings for branches
        $this->command->info("\n[3/3] Setting up teacher filters...");
        $this->call(TeacherSettingsSeeder::class);

        $this->command->info("\n========================================");
        $this->command->info('✓ Complete Teachers Setup Finished!');
        $this->command->info('========================================');
        $this->command->info("\nSummary:");
        $this->command->info("• Created 5 teacher positions (GV01, GV02, GV03, GVTT, GVCN)");
        $this->command->info("• Created 18 teachers across 3 branches:");
        $this->command->info("  - Branch 1 (Hà Nội): 7 teachers");
        $this->command->info("  - Branch 2 (Hồ Chí Minh): 6 teachers");
        $this->command->info("  - Branch 3 (Đà Nẵng): 5 teachers");
        $this->command->info("• Setup teacher position code filters for all branches");
        $this->command->info("\nDefault password: password123");
        $this->command->info("\nNext steps:");
        $this->command->info("1. Go to Quality Management → Danh sách Giáo viên");
        $this->command->info("2. You should see all teachers listed");
        $this->command->info("3. Create subjects and assign teachers");
        $this->command->info("4. Set head teachers for subjects");
    }
}

