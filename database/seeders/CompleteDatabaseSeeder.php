<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Complete Database Seeder
 * Chạy tất cả seeders theo thứ tự đúng
 * 
 * Usage: php artisan db:seed --class=CompleteDatabaseSeeder
 */
class CompleteDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Complete Database Seeding...');
        $this->command->newLine();
        
        // ============================================
        // STEP 1: LANGUAGES (Phải tạo đầu tiên)
        // ============================================
        $this->command->info('📚 Step 1: Creating Languages...');
        $this->call(LanguageSeeder::class);
        
        // ============================================
        // STEP 2: ALL TRANSLATIONS
        // ============================================
        $this->command->info('🌐 Step 2: Seeding ALL Translations...');
        $this->command->newLine();
        
        // Core translations
        $this->call(CustomersTranslationsSeeder::class);
        $this->call(BranchTranslationsSeeder::class);
        $this->call(SettingsTranslationsSeeder::class);
        $this->call(SwalTranslationsSeeder::class);
        $this->call(MissingTranslationsSeeder::class);
        $this->call(SidebarTranslations::class);
        $this->call(UserMenuTranslations::class);
        $this->call(ResetPasswordTranslations::class);
        $this->call(ChangePasswordTranslations::class);
        
        // Module translations
        $this->call(CustomerInteractionTranslationsSeeder::class);
        $this->call(CustomerChildrenTranslationsSeeder::class);
        $this->call(CustomerSettingsTranslationsSeeder::class);
        $this->call(PlacementTestTranslationsSeeder::class);
        $this->call(CalendarFeedbackTranslationsSeeder::class);
        
        // Sales module translations
        $this->call(SalesTranslationsSeeder::class);
        $this->call(SalesTranslationsAdditional::class);
        $this->call(SalesModulesTranslationsSeeder::class);
        $this->call(SalesSecondaryMenuTranslations::class);
        $this->call(CampaignsVouchersTranslations::class);
        $this->call(EnrollmentsAdditionalTranslations::class);
        
        // Quality/Classes translations
        $this->call(QualityManagementTranslationsSeeder::class);
        $this->call(QualityStudentsParentsTranslations::class);
        $this->call(SubjectsTranslationsSeeder::class);
        $this->call(ClassesTranslationsSeeder::class);
        $this->call(ClassDetailTranslationsSeeder::class);
        $this->call(UpdateSyllabusTranslationsSeeder::class);
        
        // Other module translations
        $this->call(HolidaysTranslationsSeeder::class);
        $this->call(AccountingTranslationsSeeder::class);
        
        // Additional translations (bổ sung)
        $this->call(CompleteSalesTranslations::class);
        $this->call(CompleteAllTranslations::class);
        
        $this->command->newLine();
        
        // ============================================
        // STEP 3: ROLES & PERMISSIONS
        // ============================================
        $this->command->info('🔐 Step 3: Creating Roles & ALL Permissions...');
        $this->command->newLine();
        
        // Base roles và permissions
        $this->call(RolePermissionSeeder::class);
        $this->call(AddParentStudentRolesSeeder::class);
        
        // Module permissions
        $this->call(HRPermissionsSeeder::class);
        $this->call(CustomerSettingsPermissionSeeder::class);
        $this->call(SalesPermissionsSeederSimple::class);
        $this->call(SalesModulesPermissionsSeeder::class);
        $this->call(QualityManagementPermissionsSeeder::class);
        $this->call(SubjectsPermissionsSeeder::class);
        $this->call(ClassesPermissionsSeeder::class);
        $this->call(UpdateSyllabusPermissionsSeeder::class);
        $this->call(HolidaysPermissionsSeeder::class);
        $this->call(CalendarFeedbackPermissionsSeeder::class);
        $this->call(SystemSettingsPermissionsSeeder::class);
        $this->call(AccountingPermissionsSeeder::class);
        
        $this->command->newLine();
        
        // ============================================
        // STEP 4: BRANCHES
        // ============================================
        $this->command->info('🏢 Step 4: Creating Branches...');
        $this->call(BranchSeeder::class);
        $this->command->newLine();
        
        // ============================================
        // STEP 5: POSITIONS & HR DATA
        // ============================================
        $this->command->info('👔 Step 5: Creating Positions & HR Data...');
        $this->call(PositionsSeeder::class);
        $this->call(TeacherPositionsSeeder::class);
        $this->call(TeachersSeeder::class);
        $this->call(CompleteTeachersSetupSeeder::class);
        $this->call(TeacherSettingsSeeder::class);
        $this->command->newLine();
        
        // ============================================
        // STEP 6: CUSTOMER SETTINGS & DATA
        // ============================================
        $this->command->info('👥 Step 6: Creating Customer Settings & Data...');
        $this->call(CustomerSettingsSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->command->newLine();
        
        // ============================================
        // STEP 7: PRODUCTS, VOUCHERS, CAMPAIGNS
        // ============================================
        $this->command->info('🛍️ Step 7: Creating Products, Vouchers & Campaigns...');
        $this->call(ProductsSeeder::class);
        $this->call(VouchersAndCampaignsSeeder::class);
        $this->command->newLine();
        
        // ============================================
        // STEP 8: SUBJECTS & SYLLABUS
        // ============================================
        $this->command->info('📖 Step 8: Creating Subjects & Syllabus...');
        $this->call(IELTSSyllabusSeeder::class);
        $this->command->newLine();
        
        // ============================================
        // STEP 9: CLASSES & STUDENTS
        // ============================================
        $this->command->info('🎓 Step 9: Creating Classes & Students...');
        $this->call(ClassesSampleDataSeeder::class);
        $this->call(StudentsSeeder::class);
        $this->command->newLine();
        
        // ============================================
        // STEP 10: CALENDAR MODULE
        // ============================================
        $this->command->info('📅 Step 10: Creating Calendar Data...');
        $this->call(CalendarModuleSeeder::class);
        $this->command->newLine();
        
        // ============================================
        // STEP 11: ACCOUNTING DATA
        // ============================================
        $this->command->info('💰 Step 11: Creating Accounting Data...');
        $this->call(AccountingSampleDataSeeder::class);
        $this->command->newLine();
        
        // ============================================
        // STEP 12: TEST USERS
        // ============================================
        $this->command->info('👤 Step 12: Creating Test Users...');
        $this->createTestUsers();
        $this->command->newLine();
        
        // ============================================
        // DONE!
        // ============================================
        $this->command->info('');
        $this->command->info('✅ ========================================');
        $this->command->info('✅ COMPLETE DATABASE SEEDING FINISHED!');
        $this->command->info('✅ ========================================');
        $this->command->newLine();
        
        $this->displayTestAccounts();
    }
    
    /**
     * Tạo test users với các roles và branches
     */
    private function createTestUsers(): void
    {
        $hanoiBranch = \App\Models\Branch::where('code', 'HN01')->first();
        $hcmBranch = \App\Models\Branch::where('code', 'HCM01')->first();
        $danangBranch = \App\Models\Branch::where('code', 'DN01')->first();

        // Super Admin (access tất cả branches)
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
        ]);
        $superAdmin->assignRole('super-admin');
        if ($hanoiBranch) $superAdmin->assignBranch($hanoiBranch->id, true);
        if ($hcmBranch) $superAdmin->assignBranch($hcmBranch->id);
        if ($danangBranch) $superAdmin->assignBranch($danangBranch->id);

        // Admin Hà Nội
        $admin = User::factory()->create([
            'name' => 'Admin Hà Nội',
            'email' => 'admin.hn@example.com',
        ]);
        $admin->assignRole('admin');
        if ($hanoiBranch) {
            $admin->assignBranch($hanoiBranch->id, true);
            $hanoiBranch->update(['manager_id' => $admin->id]);
        }

        // Manager Multi-Branch
        $manager = User::factory()->create([
            'name' => 'Manager Multi-Branch',
            'email' => 'manager.multi@example.com',
        ]);
        $manager->assignRole('manager');
        if ($hcmBranch) {
            $manager->assignBranch($hcmBranch->id, true);
            $hcmBranch->update(['manager_id' => $manager->id]);
        }
        if ($danangBranch) $manager->assignBranch($danangBranch->id);

        // Staff Đà Nẵng
        $staff = User::factory()->create([
            'name' => 'Staff Đà Nẵng',
            'email' => 'staff.dn@example.com',
        ]);
        $staff->assignRole('staff');
        if ($danangBranch) $staff->assignBranch($danangBranch->id, true);

        // User TP.HCM
        $user = User::factory()->create([
            'name' => 'User TP.HCM',
            'email' => 'user.hcm@example.com',
        ]);
        $user->assignRole('user');
        if ($hcmBranch) $user->assignBranch($hcmBranch->id, true);

        $this->command->info('✓ Created 5 test users with roles and branches');
    }
    
    /**
     * Hiển thị thông tin test accounts
     */
    private function displayTestAccounts(): void
    {
        $this->command->info('📝 TEST ACCOUNTS:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', 'admin@example.com', 'password'],
                ['Admin HN', 'admin.hn@example.com', 'password'],
                ['Manager', 'manager.multi@example.com', 'password'],
                ['Staff', 'staff.dn@example.com', 'password'],
                ['User', 'user.hcm@example.com', 'password'],
            ]
        );
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();
    }
}

