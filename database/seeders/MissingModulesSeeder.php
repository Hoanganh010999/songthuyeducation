<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder for Missing Modules
 * Chạy các seeders còn thiếu từ CompleteDatabaseSeeder
 *
 * Usage: php artisan db:seed --class=MissingModulesSeeder
 */
class MissingModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Seeding Missing Modules...');
        $this->command->newLine();

        // ============================================
        // ZALO MODULE
        // ============================================
        $this->command->info('📱 Zalo Module...');
        try {
            $this->call(ZaloTranslationsSeeder::class);
            $this->command->info('✓ ZaloTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ ZaloTranslationsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(ZaloPermissionsSeeder::class);
            $this->command->info('✓ ZaloPermissionsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ ZaloPermissionsSeeder failed: ' . $e->getMessage());
        }
        $this->command->newLine();

        // ============================================
        // GOOGLE DRIVE MODULE
        // ============================================
        $this->command->info('☁️ Google Drive Module...');
        try {
            $this->call(GoogleDriveTranslationsSeeder::class);
            $this->command->info('✓ GoogleDriveTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ GoogleDriveTranslationsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(GoogleDrivePermissionsSeeder::class);
            $this->command->info('✓ GoogleDrivePermissionsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ GoogleDrivePermissionsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(ClassGoogleDriveTranslationsSeeder::class);
            $this->command->info('✓ ClassGoogleDriveTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ ClassGoogleDriveTranslationsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(ClassHistoryGoogleDriveTranslationsSeeder::class);
            $this->command->info('✓ ClassHistoryGoogleDriveTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ ClassHistoryGoogleDriveTranslationsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(SubjectsGoogleDriveTranslationsSeeder::class);
            $this->command->info('✓ SubjectsGoogleDriveTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ SubjectsGoogleDriveTranslationsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(SyllabusGoogleDriveTranslationsSeeder::class);
            $this->command->info('✓ SyllabusGoogleDriveTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ SyllabusGoogleDriveTranslationsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(UserGoogleDriveTranslationsSeeder::class);
            $this->command->info('✓ UserGoogleDriveTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ UserGoogleDriveTranslationsSeeder failed: ' . $e->getMessage());
        }
        $this->command->newLine();

        // ============================================
        // HOMEWORK & COURSES MODULE
        // ============================================
        $this->command->info('📝 Homework & Courses Module...');
        try {
            $this->call(HomeworkTranslationsSeeder::class);
            $this->command->info('✓ HomeworkTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ HomeworkTranslationsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(CourseTranslationsSeeder::class);
            $this->command->info('✓ CourseTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ CourseTranslationsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(CoursePermissionsSeeder::class);
            $this->command->info('✓ CoursePermissionsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ CoursePermissionsSeeder failed: ' . $e->getMessage());
        }
        $this->command->newLine();

        // ============================================
        // ERROR & SUCCESS MESSAGES
        // ============================================
        $this->command->info('💬 Error & Success Messages...');
        try {
            $this->call(ErrorMessagesTranslationsSeeder::class);
            $this->command->info('✓ ErrorMessagesTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ ErrorMessagesTranslationsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(SuccessMessagesTranslationsSeeder::class);
            $this->command->info('✓ SuccessMessagesTranslationsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ SuccessMessagesTranslationsSeeder failed: ' . $e->getMessage());
        }
        $this->command->newLine();

        // ============================================
        // ADDITIONAL PERMISSIONS
        // ============================================
        $this->command->info('🔐 Additional Permissions...');
        try {
            $this->call(UpdateQualityPermissionsSeeder::class);
            $this->command->info('✓ UpdateQualityPermissionsSeeder completed');
        } catch (\Exception $e) {
            $this->command->error('✗ UpdateQualityPermissionsSeeder failed: ' . $e->getMessage());
        }

        try {
            $this->call(AttendanceFeeTranslations::class);
            $this->command->info('✓ AttendanceFeeTranslations completed');
        } catch (\Exception $e) {
            $this->command->error('✗ AttendanceFeeTranslations failed: ' . $e->getMessage());
        }
        $this->command->newLine();

        // ============================================
        // DONE!
        // ============================================
        $this->command->info('');
        $this->command->info('✅ ========================================');
        $this->command->info('✅ MISSING MODULES SEEDING COMPLETED!');
        $this->command->info('✅ ========================================');
        $this->command->newLine();
    }
}
