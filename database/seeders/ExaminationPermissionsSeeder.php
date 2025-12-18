<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ExaminationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define examination module permissions
        $permissions = [
            // Module-level permissions
            [
                'module' => 'examination',
                'action' => 'view',
                'name' => 'examination.view',
                'display_name' => 'Xem module Examination',
                'description' => 'Quyền xem module kiểm tra đánh giá',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'manage',
                'name' => 'examination.manage',
                'display_name' => 'Quản lý Examination',
                'description' => 'Quyền quản lý toàn bộ module kiểm tra đánh giá',
                'sort_order' => 2,
                'is_active' => true,
            ],

            // Question Bank permissions
            [
                'module' => 'examination',
                'action' => 'view',
                'name' => 'examination.questions.view',
                'display_name' => 'Xem ngân hàng câu hỏi',
                'description' => 'Quyền xem danh sách câu hỏi trong ngân hàng',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'create',
                'name' => 'examination.questions.create',
                'display_name' => 'Tạo câu hỏi',
                'description' => 'Quyền tạo câu hỏi mới trong ngân hàng',
                'sort_order' => 11,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'edit',
                'name' => 'examination.questions.edit',
                'display_name' => 'Sửa câu hỏi',
                'description' => 'Quyền chỉnh sửa câu hỏi trong ngân hàng',
                'sort_order' => 12,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'delete',
                'name' => 'examination.questions.delete',
                'display_name' => 'Xóa câu hỏi',
                'description' => 'Quyền xóa câu hỏi khỏi ngân hàng',
                'sort_order' => 13,
                'is_active' => true,
            ],

            // Test Bank permissions
            [
                'module' => 'examination',
                'action' => 'view',
                'name' => 'examination.tests.view',
                'display_name' => 'Xem ngân hàng đề thi',
                'description' => 'Quyền xem danh sách đề thi',
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'create',
                'name' => 'examination.tests.create',
                'display_name' => 'Tạo đề thi',
                'description' => 'Quyền tạo đề thi mới',
                'sort_order' => 21,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'edit',
                'name' => 'examination.tests.edit',
                'display_name' => 'Sửa đề thi',
                'description' => 'Quyền chỉnh sửa đề thi',
                'sort_order' => 22,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'delete',
                'name' => 'examination.tests.delete',
                'display_name' => 'Xóa đề thi',
                'description' => 'Quyền xóa đề thi',
                'sort_order' => 23,
                'is_active' => true,
            ],

            // Assignment permissions
            [
                'module' => 'examination',
                'action' => 'view',
                'name' => 'examination.assignments.view',
                'display_name' => 'Xem bài giao',
                'description' => 'Quyền xem danh sách bài giao cho học viên',
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'create',
                'name' => 'examination.assignments.create',
                'display_name' => 'Tạo bài giao',
                'description' => 'Quyền tạo bài giao mới cho học viên',
                'sort_order' => 31,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'edit',
                'name' => 'examination.assignments.edit',
                'display_name' => 'Sửa bài giao',
                'description' => 'Quyền chỉnh sửa bài giao',
                'sort_order' => 32,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'delete',
                'name' => 'examination.assignments.delete',
                'display_name' => 'Xóa bài giao',
                'description' => 'Quyền xóa bài giao',
                'sort_order' => 33,
                'is_active' => true,
            ],

            // Submission permissions
            [
                'module' => 'examination',
                'action' => 'view',
                'name' => 'examination.submissions.view',
                'display_name' => 'Xem bài làm',
                'description' => 'Quyền xem bài làm của học viên',
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'grade',
                'name' => 'examination.submissions.grade',
                'display_name' => 'Chấm điểm bài làm',
                'description' => 'Quyền chấm điểm và đánh giá bài làm',
                'sort_order' => 41,
                'is_active' => true,
            ],

            // IELTS permissions
            [
                'module' => 'examination',
                'action' => 'view',
                'name' => 'examination.ielts.view',
                'display_name' => 'Xem đề IELTS',
                'description' => 'Quyền xem các đề thi IELTS',
                'sort_order' => 50,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'manage',
                'name' => 'examination.ielts.manage',
                'display_name' => 'Quản lý đề IELTS',
                'description' => 'Quyền tạo, sửa, xóa đề thi IELTS',
                'sort_order' => 51,
                'is_active' => true,
            ],

            // Cambridge permissions
            [
                'module' => 'examination',
                'action' => 'view',
                'name' => 'examination.cambridge.view',
                'display_name' => 'Xem đề Cambridge',
                'description' => 'Quyền xem các đề thi Cambridge (Starters, Movers, Flyers)',
                'sort_order' => 60,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'manage',
                'name' => 'examination.cambridge.manage',
                'display_name' => 'Quản lý đề Cambridge',
                'description' => 'Quyền tạo, sửa, xóa đề thi Cambridge',
                'sort_order' => 61,
                'is_active' => true,
            ],

            // Audio & Reading Passage permissions
            [
                'module' => 'examination',
                'action' => 'view',
                'name' => 'examination.audio.view',
                'display_name' => 'Xem thư viện audio',
                'description' => 'Quyền xem thư viện file audio',
                'sort_order' => 70,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'manage',
                'name' => 'examination.audio.manage',
                'display_name' => 'Quản lý thư viện audio',
                'description' => 'Quyền upload, sửa, xóa file audio',
                'sort_order' => 71,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'view',
                'name' => 'examination.passages.view',
                'display_name' => 'Xem thư viện đoạn văn',
                'description' => 'Quyền xem thư viện đoạn văn Reading',
                'sort_order' => 72,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'manage',
                'name' => 'examination.passages.manage',
                'display_name' => 'Quản lý thư viện đoạn văn',
                'description' => 'Quyền tạo, sửa, xóa đoạn văn Reading',
                'sort_order' => 73,
                'is_active' => true,
            ],

            // Report permissions
            [
                'module' => 'examination',
                'action' => 'view',
                'name' => 'examination.reports.view',
                'display_name' => 'Xem báo cáo',
                'description' => 'Quyền xem báo cáo thống kê kiểm tra đánh giá',
                'sort_order' => 80,
                'is_active' => true,
            ],
            [
                'module' => 'examination',
                'action' => 'export',
                'name' => 'examination.reports.export',
                'display_name' => 'Xuất báo cáo',
                'description' => 'Quyền xuất báo cáo ra file Excel/PDF',
                'sort_order' => 81,
                'is_active' => true,
            ],

            // Settings permissions
            [
                'module' => 'examination',
                'action' => 'manage',
                'name' => 'examination.settings.manage',
                'display_name' => 'Quản lý cài đặt',
                'description' => 'Quyền quản lý cài đặt module kiểm tra đánh giá',
                'sort_order' => 90,
                'is_active' => true,
            ],
        ];

        // Create or update permissions
        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        $this->command->info('Examination permissions seeded successfully!');
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles(): void
    {
        $superAdmin = Role::where('name', 'super-admin')->first();
        $admin = Role::where('name', 'admin')->first();
        $teacher = Role::where('name', 'teacher')->first();

        // Super Admin: All permissions (including examination)
        if ($superAdmin) {
            $allPermissions = Permission::pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($allPermissions);
        }

        // Admin: All examination permissions except settings
        if ($admin) {
            $adminPermissions = Permission::where('module', 'examination')
                ->where('name', '!=', 'examination.settings.manage')
                ->pluck('id');
            $admin->permissions()->syncWithoutDetaching($adminPermissions);
        }

        // Teacher: View and manage own content permissions
        if ($teacher) {
            $teacherPermissions = Permission::whereIn('name', [
                'examination.view',
                'examination.questions.view',
                'examination.questions.create',
                'examination.questions.edit',
                'examination.tests.view',
                'examination.tests.create',
                'examination.tests.edit',
                'examination.assignments.view',
                'examination.assignments.create',
                'examination.assignments.edit',
                'examination.submissions.view',
                'examination.submissions.grade',
                'examination.ielts.view',
                'examination.cambridge.view',
                'examination.audio.view',
                'examination.audio.manage',
                'examination.passages.view',
                'examination.passages.manage',
                'examination.reports.view',
            ])->pluck('id');
            $teacher->permissions()->syncWithoutDetaching($teacherPermissions);
        }
    }
}
