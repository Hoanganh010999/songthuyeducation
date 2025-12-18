<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;
use App\Models\Translation;

class SettingsTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $en = Language::where('code', 'en')->first();
        $vi = Language::where('code', 'vi')->first();

        if (!$en || !$vi) {
            $this->command->error('Languages not found. Please run LanguageSeeder first.');
            return;
        }

        $settingsEn = [
            'description' => 'Configure system-wide settings and preferences',
            'welcome_title' => 'System Settings',
            'welcome_message' => 'Select a setting category from the left to get started',
            'access_control' => 'Access Control',
            'manage_access' => 'Manage roles and permissions',
            'manage_languages' => 'Manage system languages',
            'language_list' => 'Language List',
            'more_settings_coming' => 'More settings coming soon',
            'manage_system_languages' => 'Manage system languages and translations',
            'translations_for' => 'Translations for',
            'manage_translations_desc' => 'Manage translation strings for this language',
            'all_groups' => 'All Groups',
            'search_translations' => 'Search translations...',
            'add_translation' => 'Add Translation',
            'items' => 'items',
            'edit_translation' => 'Edit Translation',
            'select_group' => 'Select a group',
            'new_group' => 'New Group',
            'new_group_name' => 'New Group Name',
            'key_hint' => 'Use lowercase with underscores (e.g., button_save)',
            'value_placeholder' => 'Enter the translated text',
            'direction' => 'Direction',
            'default' => 'Default',
            'view_translations' => 'View Translations',
            'set_default' => 'Set as Default'
        ];

        $settingsVi = [
            'description' => 'Cấu hình các thiết lập và tùy chọn toàn hệ thống',
            'welcome_title' => 'Cài Đặt Hệ Thống',
            'welcome_message' => 'Chọn một danh mục cài đặt bên trái để bắt đầu',
            'access_control' => 'Kiểm Soát Truy Cập',
            'manage_access' => 'Quản lý vai trò và quyền hạn',
            'manage_languages' => 'Quản lý ngôn ngữ hệ thống',
            'language_list' => 'Danh Sách Ngôn Ngữ',
            'more_settings_coming' => 'Thêm cài đặt sẽ được bổ sung',
            'manage_system_languages' => 'Quản lý ngôn ngữ và bản dịch hệ thống',
            'translations_for' => 'Bản dịch cho',
            'manage_translations_desc' => 'Quản lý các chuỗi dịch cho ngôn ngữ này',
            'all_groups' => 'Tất Cả Nhóm',
            'search_translations' => 'Tìm kiếm bản dịch...',
            'add_translation' => 'Thêm Bản Dịch',
            'items' => 'mục',
            'edit_translation' => 'Sửa Bản Dịch',
            'select_group' => 'Chọn một nhóm',
            'new_group' => 'Nhóm Mới',
            'new_group_name' => 'Tên Nhóm Mới',
            'key_hint' => 'Dùng chữ thường với gạch dưới (vd: button_save)',
            'value_placeholder' => 'Nhập văn bản đã dịch',
            'direction' => 'Hướng',
            'default' => 'Mặc định',
            'view_translations' => 'Xem Bản Dịch',
            'set_default' => 'Đặt Làm Mặc Định'
        ];

        foreach ($settingsEn as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $en->id, 'group' => 'settings', 'key' => $key],
                ['value' => $value]
            );
        }

        foreach ($settingsVi as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $vi->id, 'group' => 'settings', 'key' => $key],
                ['value' => $value]
            );
        }

        // Add roles translations
        $rolesEn = [
            'description' => 'Manage user roles and their permissions',
            'permissions_count' => 'Permissions',
            'users_count' => 'Users',
            'name' => 'Role Name',
            'name_placeholder' => 'e.g., manager, staff',
            'name_hint' => 'Use lowercase with hyphens (e.g., content-manager)',
            'display_name' => 'Display Name',
            'display_name_placeholder' => 'e.g., Content Manager',
            'description_label' => 'Description',
            'description_placeholder' => 'Brief description of this role',
            'is_active' => 'Active',
            'manage_permissions' => 'Manage Permissions',
            'manage_permissions_desc' => 'Select which permissions this role should have',
            'permissions_selected' => 'permissions selected',
            'changes_not_saved' => 'Changes are not saved until you click Save',
            'permissions' => 'Permissions',
        ];

        $rolesVi = [
            'description' => 'Quản lý vai trò người dùng và quyền hạn của họ',
            'permissions_count' => 'Quyền hạn',
            'users_count' => 'Người dùng',
            'name' => 'Tên Vai Trò',
            'name_placeholder' => 'vd: manager, staff',
            'name_hint' => 'Dùng chữ thường với gạch ngang (vd: content-manager)',
            'display_name' => 'Tên Hiển Thị',
            'display_name_placeholder' => 'vd: Quản Lý Nội Dung',
            'description_label' => 'Mô Tả',
            'description_placeholder' => 'Mô tả ngắn gọn về vai trò này',
            'is_active' => 'Kích hoạt',
            'manage_permissions' => 'Quản Lý Quyền Hạn',
            'manage_permissions_desc' => 'Chọn quyền hạn mà vai trò này nên có',
            'permissions_selected' => 'quyền đã chọn',
            'changes_not_saved' => 'Thay đổi chưa được lưu cho đến khi bạn nhấn Lưu',
            'permissions' => 'Quyền Hạn',
        ];

        foreach ($rolesEn as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $en->id, 'group' => 'roles', 'key' => $key],
                ['value' => $value]
            );
        }

        foreach ($rolesVi as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $vi->id, 'group' => 'roles', 'key' => $key],
                ['value' => $value]
            );
        }

        // Add permissions translations
        $permissionsEn = [
            'description' => 'View and manage system permissions',
            'all_modules' => 'All Modules',
            'items' => 'items',
            'name' => 'Permission Name',
            'action' => 'Action',
            'module' => 'Module',
            'select_module' => 'Select a module',
            'new_module' => 'New Module',
            'new_module_name' => 'New Module Name',
            'new_module_placeholder' => 'e.g., products, orders',
            'module_hint' => 'Use lowercase, singular form (e.g., product, order)',
            'select_action' => 'Select an action',
            'permission_name' => 'Permission Name',
            'auto_generated' => 'Auto-generated from module and action',
            'enter_module_action' => 'Enter module and action',
            'display_name' => 'Display Name',
            'display_name_placeholder' => 'e.g., View Products',
            'description_label' => 'Description',
            'description_placeholder' => 'Brief description of this permission',
            'is_active' => 'Active',
            'create' => 'Create Permission',
        ];

        $permissionsVi = [
            'description' => 'Xem và quản lý quyền hạn hệ thống',
            'all_modules' => 'Tất Cả Module',
            'items' => 'mục',
            'name' => 'Tên Quyền',
            'action' => 'Hành Động',
            'module' => 'Module',
            'select_module' => 'Chọn một module',
            'new_module' => 'Module Mới',
            'new_module_name' => 'Tên Module Mới',
            'new_module_placeholder' => 'vd: products, orders',
            'module_hint' => 'Dùng chữ thường, dạng số ít (vd: product, order)',
            'select_action' => 'Chọn một hành động',
            'permission_name' => 'Tên Quyền',
            'auto_generated' => 'Tự động tạo từ module và hành động',
            'enter_module_action' => 'Nhập module và hành động',
            'display_name' => 'Tên Hiển Thị',
            'display_name_placeholder' => 'vd: Xem Sản Phẩm',
            'description_label' => 'Mô Tả',
            'description_placeholder' => 'Mô tả ngắn gọn về quyền này',
            'is_active' => 'Kích hoạt',
            'create' => 'Tạo Quyền',
        ];

        foreach ($permissionsEn as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $en->id, 'group' => 'permissions', 'key' => $key],
                ['value' => $value]
            );
        }

        foreach ($permissionsVi as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $vi->id, 'group' => 'permissions', 'key' => $key],
                ['value' => $value]
            );
        }

        // Add users translations
        $usersEn = [
            'name' => 'Full Name',
            'name_placeholder' => 'Enter full name',
            'email' => 'Email Address',
            'email_placeholder' => 'Enter email address',
            'email_readonly' => 'Email cannot be changed',
            'password' => 'Password',
            'password_placeholder' => 'Enter password',
            'password_hint' => 'Minimum 8 characters',
            'password_confirmation' => 'Confirm Password',
            'password_confirmation_placeholder' => 'Re-enter password',
            'roles' => 'Roles',
            'roles_hint' => 'Select one or more roles for this user',
        ];

        $usersVi = [
            'name' => 'Họ Tên',
            'name_placeholder' => 'Nhập họ tên',
            'email' => 'Địa Chỉ Email',
            'email_placeholder' => 'Nhập địa chỉ email',
            'email_readonly' => 'Email không thể thay đổi',
            'password' => 'Mật Khẩu',
            'password_placeholder' => 'Nhập mật khẩu',
            'password_hint' => 'Tối thiểu 8 ký tự',
            'password_confirmation' => 'Xác Nhận Mật Khẩu',
            'password_confirmation_placeholder' => 'Nhập lại mật khẩu',
            'roles' => 'Vai Trò',
            'roles_hint' => 'Chọn một hoặc nhiều vai trò cho người dùng này',
        ];

        foreach ($usersEn as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $en->id, 'group' => 'users', 'key' => $key],
                ['value' => $value]
            );
        }

        foreach ($usersVi as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $vi->id, 'group' => 'users', 'key' => $key],
                ['value' => $value]
            );
        }

        // Add common translations
        $commonEn = [
            'select_all' => 'Select All',
            'deselect_all' => 'Deselect All',
            'save_changes' => 'Save Changes',
            'actions' => 'Actions',
        ];

        $commonVi = [
            'select_all' => 'Chọn Tất Cả',
            'deselect_all' => 'Bỏ Chọn Tất Cả',
            'save_changes' => 'Lưu Thay Đổi',
            'actions' => 'Hành Động',
        ];

        foreach ($commonEn as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $en->id, 'group' => 'common', 'key' => $key],
                ['value' => $value]
            );
        }

        foreach ($commonVi as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $vi->id, 'group' => 'common', 'key' => $key],
                ['value' => $value]
            );
        }

        $this->command->info('Settings translations added successfully!');
    }
}

