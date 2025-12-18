<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tạo languages và translations trước
        $this->call(LanguageSeeder::class);
        
        // Core translations
        $this->call(CustomersTranslationsSeeder::class);
        $this->call(BranchTranslationsSeeder::class);
        $this->call(SettingsTranslationsSeeder::class);
        $this->call(SwalTranslationsSeeder::class);
        
        // Module translations
        $this->call(CustomerInteractionTranslationsSeeder::class);
        $this->call(CustomerChildrenTranslationsSeeder::class);
        $this->call(CustomerSettingsTranslationsSeeder::class);
        $this->call(PlacementTestTranslationsSeeder::class);
        $this->call(CalendarFeedbackTranslationsSeeder::class);
        $this->call(QualityManagementTranslationsSeeder::class);
        $this->call(SubjectsTranslationsSeeder::class);
        $this->call(ClassesTranslationsSeeder::class);
        $this->call(ClassDetailTranslationsSeeder::class);
        $this->call(UpdateSyllabusTranslationsSeeder::class);
        $this->call(HolidaysTranslationsSeeder::class);
        $this->call(AccountingTranslationsSeeder::class);
        
        // Tạo roles và permissions
        $this->call(RolePermissionSeeder::class);
        
        // Module permissions
        $this->call(HRPermissionsSeeder::class);
        $this->call(CustomerSettingsPermissionSeeder::class);
        $this->call(QualityManagementPermissionsSeeder::class);
        $this->call(SubjectsPermissionsSeeder::class);
        $this->call(ClassesPermissionsSeeder::class);
        $this->call(UpdateSyllabusPermissionsSeeder::class);
        $this->call(HolidaysPermissionsSeeder::class);
        $this->call(CalendarFeedbackPermissionsSeeder::class);
        $this->call(SystemSettingsPermissionsSeeder::class);
        $this->call(AccountingPermissionsSeeder::class);
        
        // Tạo branches
        $this->call(BranchSeeder::class);
        
        // Tạo accounting sample data
        $this->call(AccountingSampleDataSeeder::class);
        
        // Tạo positions (chức vụ, vị trí)
        $this->call(PositionsSeeder::class);
        $this->call(TeacherPositionsSeeder::class);
        
        // Tạo sample data cho HR (teachers)
        $this->call(TeachersSeeder::class);
        $this->call(CompleteTeachersSetupSeeder::class);
        
        // Tạo customer settings và sample customers
        $this->call(CustomerSettingsSeeder::class);
        $this->call(CustomerSeeder::class);
        
        // Tạo subjects và syllabus
        $this->call(IELTSSyllabusSeeder::class);
        
        // Tạo sample classes
        $this->call(ClassesSampleDataSeeder::class);
        
        // Tạo sample students
        $this->call(StudentsSeeder::class);
        
        // Tạo calendar module data
        $this->call(CalendarModuleSeeder::class);
        
        // Tạo teacher settings
        $this->call(TeacherSettingsSeeder::class);

        // Lấy branches
        $hanoiBranch = \App\Models\Branch::where('code', 'HN01')->first();
        $hcmBranch = \App\Models\Branch::where('code', 'HCM01')->first();
        $danangBranch = \App\Models\Branch::where('code', 'DN01')->first();

        // Tạo user test với role super-admin (HQ - có thể access tất cả branches)
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
        ]);
        $superAdmin->assignRole('super-admin');
        // Super admin có thể access tất cả branches
        if ($hanoiBranch) $superAdmin->assignBranch($hanoiBranch->id, true); // Primary
        if ($hcmBranch) $superAdmin->assignBranch($hcmBranch->id);
        if ($danangBranch) $superAdmin->assignBranch($danangBranch->id);

        // Tạo user test với role admin (HN)
        $admin = User::factory()->create([
            'name' => 'Admin Hà Nội',
            'email' => 'admin.hn@example.com',
        ]);
        $admin->assignRole('admin');
        if ($hanoiBranch) $admin->assignBranch($hanoiBranch->id, true); // Primary branch

        // Đặt admin làm manager của HN branch
        if ($hanoiBranch) {
            $hanoiBranch->update(['manager_id' => $admin->id]);
        }

        // Tạo user test với role manager (HCM và DN - quản lý 2 chi nhánh)
        $manager = User::factory()->create([
            'name' => 'Manager Multi-Branch',
            'email' => 'manager.multi@example.com',
        ]);
        $manager->assignRole('manager');
        if ($hcmBranch) $manager->assignBranch($hcmBranch->id, true); // Primary
        if ($danangBranch) $manager->assignBranch($danangBranch->id); // Secondary

        // Đặt manager làm manager của HCM branch
        if ($hcmBranch) {
            $hcmBranch->update(['manager_id' => $manager->id]);
        }

        // Tạo user test với role staff (DN only)
        $staff = User::factory()->create([
            'name' => 'Staff Đà Nẵng',
            'email' => 'staff.dn@example.com',
        ]);
        $staff->assignRole('staff');
        if ($danangBranch) $staff->assignBranch($danangBranch->id, true);

        // Tạo user test với role user (HCM only)
        $user = User::factory()->create([
            'name' => 'User TP.HCM',
            'email' => 'user.hcm@example.com',
        ]);
        $user->assignRole('user');
        if ($hcmBranch) $user->assignBranch($hcmBranch->id, true);

        $this->command->info('✅ Đã tạo 5 users mẫu với các roles và branches (many-to-many)');
    }
}
