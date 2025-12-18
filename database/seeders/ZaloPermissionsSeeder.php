<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class ZaloPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Zalo Module Permissions
            [
                'module' => 'zalo',
                'action' => 'view',
                'name' => 'zalo.view',
                'display_name' => 'Xem module Zalo',
                'description' => 'Xem danh sách tài khoản Zalo, friends, groups, lịch sử',
                'sort_order' => 1,
            ],
            [
                'module' => 'zalo',
                'action' => 'send',
                'name' => 'zalo.send',
                'display_name' => 'Gửi tin nhắn Zalo',
                'description' => 'Gửi tin nhắn đơn lẻ qua Zalo',
                'sort_order' => 2,
            ],
            [
                'module' => 'zalo',
                'action' => 'send_bulk',
                'name' => 'zalo.send_bulk',
                'display_name' => 'Gửi tin nhắn hàng loạt',
                'description' => 'Gửi tin nhắn hàng loạt qua Zalo',
                'sort_order' => 3,
            ],
            [
                'module' => 'zalo',
                'action' => 'manage_accounts',
                'name' => 'zalo.manage_accounts',
                'display_name' => 'Quản lý tài khoản Zalo',
                'description' => 'Tạo, chỉnh sửa, xóa, đăng nhập lại tài khoản Zalo',
                'sort_order' => 4,
            ],
            [
                'module' => 'zalo',
                'action' => 'manage_settings',
                'name' => 'zalo.manage_settings',
                'display_name' => 'Quản lý cài đặt Zalo',
                'description' => 'Cấu hình cài đặt module Zalo',
                'sort_order' => 5,
            ],
            [
                'module' => 'zalo',
                'action' => 'view_friends',
                'name' => 'zalo.view_friends',
                'display_name' => 'Xem danh sách bạn bè Zalo',
                'description' => 'Xem danh sách bạn bè của các tài khoản Zalo',
                'sort_order' => 6,
            ],
            [
                'module' => 'zalo',
                'action' => 'view_groups',
                'name' => 'zalo.view_groups',
                'display_name' => 'Xem danh sách nhóm Zalo',
                'description' => 'Xem danh sách nhóm của các tài khoản Zalo',
                'sort_order' => 7,
            ],
            [
                'module' => 'zalo',
                'action' => 'all_conversation_management',
                'name' => 'zalo.all_conversation_management',
                'display_name' => 'Quản lý tất cả cuộc trò chuyện',
                'description' => 'Xem và quản lý tất cả cuộc trò chuyện Zalo bao gồm cả tin nhắn global',
                'sort_order' => 8,
            ],
            [
                'module' => 'zalo',
                'action' => 'assign_groups',
                'name' => 'zalo.assign_groups',
                'display_name' => 'Phân công nhóm Zalo',
                'description' => 'Phân công nhóm Zalo cho chi nhánh và phòng ban',
                'sort_order' => 9,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('Zalo permissions seeded successfully!');
    }
}

