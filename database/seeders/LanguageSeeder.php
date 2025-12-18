<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo ngôn ngữ
        $english = Language::create([
            'name' => 'English',
            'code' => 'en',
            'flag' => '🇬🇧',
            'direction' => 'ltr',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $vietnamese = Language::create([
            'name' => 'Tiếng Việt',
            'code' => 'vi',
            'flag' => '🇻🇳',
            'direction' => 'ltr',
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Translations cho Common
        $this->createCommonTranslations($english, $vietnamese);
        
        // Translations cho Auth
        $this->createAuthTranslations($english, $vietnamese);
        
        // Translations cho Dashboard
        $this->createDashboardTranslations($english, $vietnamese);
        
        // Translations cho Users
        $this->createUsersTranslations($english, $vietnamese);
        
        // Translations cho Roles
        $this->createRolesTranslations($english, $vietnamese);
        
        // Translations cho Permissions
        $this->createPermissionsTranslations($english, $vietnamese);
        
        // Translations cho Settings
        $this->createSettingsTranslations($english, $vietnamese);
    }

    private function createCommonTranslations(Language $en, Language $vi): void
    {
        $translations = [
            'welcome' => ['Welcome', 'Chào mừng'],
            'home' => ['Home', 'Trang chủ'],
            'dashboard' => ['Dashboard', 'Bảng điều khiển'],
            'settings' => ['Settings', 'Cài đặt'],
            'profile' => ['Profile', 'Hồ sơ'],
            'logout' => ['Logout', 'Đăng xuất'],
            'login' => ['Login', 'Đăng nhập'],
            'search' => ['Search', 'Tìm kiếm'],
            'save' => ['Save', 'Lưu'],
            'cancel' => ['Cancel', 'Hủy'],
            'delete' => ['Delete', 'Xóa'],
            'edit' => ['Edit', 'Sửa'],
            'create' => ['Create', 'Tạo mới'],
            'view' => ['View', 'Xem'],
            'actions' => ['Actions', 'Hành động'],
            'status' => ['Status', 'Trạng thái'],
            'active' => ['Active', 'Kích hoạt'],
            'inactive' => ['Inactive', 'Vô hiệu'],
            'yes' => ['Yes', 'Có'],
            'no' => ['No', 'Không'],
            'confirm' => ['Confirm', 'Xác nhận'],
            'success' => ['Success', 'Thành công'],
            'error' => ['Error', 'Lỗi'],
            'warning' => ['Warning', 'Cảnh báo'],
            'info' => ['Info', 'Thông tin'],
            'loading' => ['Loading...', 'Đang tải...'],
            'no_data' => ['No data available', 'Không có dữ liệu'],
            'page' => ['Page', 'Trang'],
            'of' => ['of', 'của'],
            'showing' => ['Showing', 'Hiển thị'],
            'to' => ['to', 'đến'],
            'entries' => ['entries', 'mục'],
        ];

        foreach ($translations as $key => [$enValue, $viValue]) {
            Translation::create(['language_id' => $en->id, 'group' => 'common', 'key' => $key, 'value' => $enValue]);
            Translation::create(['language_id' => $vi->id, 'group' => 'common', 'key' => $key, 'value' => $viValue]);
        }
    }

    private function createAuthTranslations(Language $en, Language $vi): void
    {
        $translations = [
            'login_title' => ['Login to your account', 'Đăng nhập tài khoản'],
            'email' => ['Email', 'Email'],
            'password' => ['Password', 'Mật khẩu'],
            'remember_me' => ['Remember me', 'Ghi nhớ đăng nhập'],
            'forgot_password' => ['Forgot password?', 'Quên mật khẩu?'],
            'login_button' => ['Sign in', 'Đăng nhập'],
            'logout_success' => ['Logged out successfully', 'Đăng xuất thành công'],
            'login_success' => ['Logged in successfully', 'Đăng nhập thành công'],
            'login_failed' => ['Invalid credentials', 'Thông tin đăng nhập không đúng'],
            'unauthorized' => ['Unauthorized', 'Không có quyền truy cập'],
        ];

        foreach ($translations as $key => [$enValue, $viValue]) {
            Translation::create(['language_id' => $en->id, 'group' => 'auth', 'key' => $key, 'value' => $enValue]);
            Translation::create(['language_id' => $vi->id, 'group' => 'auth', 'key' => $key, 'value' => $viValue]);
        }
    }

    private function createDashboardTranslations(Language $en, Language $vi): void
    {
        $translations = [
            'welcome_message' => ['Welcome back', 'Chào mừng trở lại'],
            'total_users' => ['Total Users', 'Tổng người dùng'],
            'total_roles' => ['Total Roles', 'Tổng vai trò'],
            'total_permissions' => ['Total Permissions', 'Tổng quyền'],
            'your_permissions' => ['Your Permissions', 'Quyền của bạn'],
            'your_roles' => ['Your Roles', 'Vai trò của bạn'],
            'recent_activities' => ['Recent Activities', 'Hoạt động gần đây'],
            'quick_actions' => ['Quick Actions', 'Thao tác nhanh'],
        ];

        foreach ($translations as $key => [$enValue, $viValue]) {
            Translation::create(['language_id' => $en->id, 'group' => 'dashboard', 'key' => $key, 'value' => $enValue]);
            Translation::create(['language_id' => $vi->id, 'group' => 'dashboard', 'key' => $key, 'value' => $viValue]);
        }
    }

    private function createUsersTranslations(Language $en, Language $vi): void
    {
        $translations = [
            'title' => ['Users Management', 'Quản lý người dùng'],
            'list' => ['Users List', 'Danh sách người dùng'],
            'create' => ['Create User', 'Tạo người dùng'],
            'edit' => ['Edit User', 'Sửa người dùng'],
            'delete' => ['Delete User', 'Xóa người dùng'],
            'name' => ['Name', 'Tên'],
            'email' => ['Email', 'Email'],
            'roles' => ['Roles', 'Vai trò'],
            'created_at' => ['Created At', 'Ngày tạo'],
            'updated_at' => ['Updated At', 'Ngày cập nhật'],
            'assign_role' => ['Assign Role', 'Gán vai trò'],
            'remove_role' => ['Remove Role', 'Xóa vai trò'],
            'create_success' => ['User created successfully', 'Tạo người dùng thành công'],
            'update_success' => ['User updated successfully', 'Cập nhật người dùng thành công'],
            'delete_success' => ['User deleted successfully', 'Xóa người dùng thành công'],
            'delete_confirm' => ['Are you sure you want to delete this user?', 'Bạn có chắc muốn xóa người dùng này?'],
        ];

        foreach ($translations as $key => [$enValue, $viValue]) {
            Translation::create(['language_id' => $en->id, 'group' => 'users', 'key' => $key, 'value' => $enValue]);
            Translation::create(['language_id' => $vi->id, 'group' => 'users', 'key' => $key, 'value' => $viValue]);
        }
    }

    private function createRolesTranslations(Language $en, Language $vi): void
    {
        $translations = [
            'title' => ['Roles Management', 'Quản lý vai trò'],
            'list' => ['Roles List', 'Danh sách vai trò'],
            'create' => ['Create Role', 'Tạo vai trò'],
            'edit' => ['Edit Role', 'Sửa vai trò'],
            'delete' => ['Delete Role', 'Xóa vai trò'],
            'name' => ['Name', 'Tên'],
            'display_name' => ['Display Name', 'Tên hiển thị'],
            'description' => ['Description', 'Mô tả'],
            'permissions' => ['Permissions', 'Quyền'],
            'assign_permission' => ['Assign Permission', 'Gán quyền'],
            'create_success' => ['Role created successfully', 'Tạo vai trò thành công'],
            'update_success' => ['Role updated successfully', 'Cập nhật vai trò thành công'],
            'delete_success' => ['Role deleted successfully', 'Xóa vai trò thành công'],
        ];

        foreach ($translations as $key => [$enValue, $viValue]) {
            Translation::create(['language_id' => $en->id, 'group' => 'roles', 'key' => $key, 'value' => $enValue]);
            Translation::create(['language_id' => $vi->id, 'group' => 'roles', 'key' => $key, 'value' => $viValue]);
        }
    }

    private function createPermissionsTranslations(Language $en, Language $vi): void
    {
        $translations = [
            'title' => ['Permissions Management', 'Quản lý quyền'],
            'list' => ['Permissions List', 'Danh sách quyền'],
            'module' => ['Module', 'Module'],
            'action' => ['Action', 'Hành động'],
            'display_name' => ['Display Name', 'Tên hiển thị'],
            'description' => ['Description', 'Mô tả'],
        ];

        foreach ($translations as $key => [$enValue, $viValue]) {
            Translation::create(['language_id' => $en->id, 'group' => 'permissions', 'key' => $key, 'value' => $enValue]);
            Translation::create(['language_id' => $vi->id, 'group' => 'permissions', 'key' => $key, 'value' => $viValue]);
        }
    }

    private function createSettingsTranslations(Language $en, Language $vi): void
    {
        $translations = [
            'title' => ['System Settings', 'Cài đặt hệ thống'],
            'general' => ['General Settings', 'Cài đặt chung'],
            'languages' => ['Languages', 'Ngôn ngữ'],
            'language_management' => ['Language Management', 'Quản lý ngôn ngữ'],
            'language_name' => ['Language Name', 'Tên ngôn ngữ'],
            'language_code' => ['Language Code', 'Mã ngôn ngữ'],
            'language_flag' => ['Flag', 'Cờ'],
            'language_direction' => ['Direction', 'Hướng'],
            'is_default' => ['Default', 'Mặc định'],
            'is_active' => ['Active', 'Kích hoạt'],
            'sort_order' => ['Sort Order', 'Thứ tự'],
            'add_language' => ['Add Language', 'Thêm ngôn ngữ'],
            'edit_language' => ['Edit Language', 'Sửa ngôn ngữ'],
            'delete_language' => ['Delete Language', 'Xóa ngôn ngữ'],
            'set_default' => ['Set as Default', 'Đặt làm mặc định'],
            'translations' => ['Translations', 'Bản dịch'],
            'translation_key' => ['Key', 'Khóa'],
            'translation_value' => ['Value', 'Giá trị'],
            'translation_group' => ['Group', 'Nhóm'],
            'manage_translations' => ['Manage Translations', 'Quản lý bản dịch'],
            'language_create_success' => ['Language created successfully', 'Tạo ngôn ngữ thành công'],
            'language_update_success' => ['Language updated successfully', 'Cập nhật ngôn ngữ thành công'],
            'language_delete_success' => ['Language deleted successfully', 'Xóa ngôn ngữ thành công'],
            'select_language' => ['Select Language', 'Chọn ngôn ngữ'],
        ];

        foreach ($translations as $key => [$enValue, $viValue]) {
            Translation::create(['language_id' => $en->id, 'group' => 'settings', 'key' => $key, 'value' => $enValue]);
            Translation::create(['language_id' => $vi->id, 'group' => 'settings', 'key' => $key, 'value' => $viValue]);
        }
    }
}
