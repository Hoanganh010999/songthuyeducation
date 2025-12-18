# Hệ Thống Phân Quyền Đa Cấp

## 📋 Tổng Quan

Hệ thống phân quyền được xây dựng với 2 cấp độ:
1. **Phân quyền Module** - Kiểm soát quyền truy cập vào từng module (users, products, orders, etc.)
2. **Phân quyền Chức năng** - Kiểm soát quyền thực hiện các hành động cụ thể trong module (view, create, edit, delete, etc.)

## 🗄️ Cấu Trúc Database

### Bảng `roles`
- `id`: ID vai trò
- `name`: Tên vai trò (unique) - vd: admin, manager, user
- `display_name`: Tên hiển thị
- `description`: Mô tả vai trò
- `is_active`: Trạng thái kích hoạt

### Bảng `permissions`
- `id`: ID quyền
- `module`: Tên module - vd: users, products, orders
- `action`: Hành động - vd: view, create, edit, delete
- `name`: Tên đầy đủ (unique) - vd: users.view, products.create
- `display_name`: Tên hiển thị
- `description`: Mô tả quyền
- `sort_order`: Thứ tự sắp xếp
- `is_active`: Trạng thái kích hoạt

### Bảng `role_user` (Pivot)
Liên kết nhiều-nhiều giữa Users và Roles

### Bảng `permission_role` (Pivot)
Liên kết nhiều-nhiều giữa Permissions và Roles

## 👥 Roles Mặc Định

1. **Super Admin** - Toàn quyền truy cập
2. **Admin** - Quản lý hệ thống (trừ roles)
3. **Manager** - Quản lý sản phẩm, đơn hàng, báo cáo
4. **Staff** - Xem và xử lý đơn hàng
5. **User** - Chỉ xem sản phẩm

## 🔐 Modules và Permissions Mặc Định

### Module: Users
- `users.view` - Xem danh sách người dùng
- `users.create` - Tạo người dùng mới
- `users.edit` - Chỉnh sửa người dùng
- `users.delete` - Xóa người dùng
- `users.assign-role` - Gán vai trò cho người dùng

### Module: Roles
- `roles.view` - Xem danh sách vai trò
- `roles.create` - Tạo vai trò mới
- `roles.edit` - Chỉnh sửa vai trò
- `roles.delete` - Xóa vai trò
- `roles.assign-permission` - Gán quyền cho vai trò

### Module: Products
- `products.view` - Xem danh sách sản phẩm
- `products.create` - Tạo sản phẩm mới
- `products.edit` - Chỉnh sửa sản phẩm
- `products.delete` - Xóa sản phẩm

### Module: Orders
- `orders.view` - Xem danh sách đơn hàng
- `orders.create` - Tạo đơn hàng mới
- `orders.edit` - Chỉnh sửa đơn hàng
- `orders.delete` - Xóa đơn hàng
- `orders.approve` - Duyệt đơn hàng

### Module: Reports
- `reports.view` - Xem báo cáo
- `reports.export` - Xuất báo cáo

## 🔧 Sử Dụng Trong Code

### Kiểm tra quyền trong Controller

```php
// Kiểm tra user có quyền cụ thể
if (auth()->user()->hasPermission('users.create')) {
    // Cho phép tạo user
}

// Kiểm tra user có quyền trên module
if (auth()->user()->hasPermissionToModule('products')) {
    // Cho phép truy cập module products
}

// Kiểm tra user có role
if (auth()->user()->hasRole('admin')) {
    // User là admin
}

// Kiểm tra user có bất kỳ role nào
if (auth()->user()->hasAnyRole(['admin', 'manager'])) {
    // User là admin hoặc manager
}
```

### Sử dụng Middleware trong Routes

```php
// Kiểm tra permission
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:users.view');

// Kiểm tra role
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('role:admin,super-admin');
```

### Gán Role cho User

```php
$user = User::find(1);

// Gán role
$user->assignRole('admin');
// hoặc
$user->assignRole(Role::find(1));

// Thu hồi role
$user->removeRole('admin');
```

### Gán Permission cho Role

```php
$role = Role::find(1);

// Gán permission
$role->givePermissionTo('users.create');
// hoặc
$role->givePermissionTo(Permission::find(1));

// Thu hồi permission
$role->revokePermissionTo('users.create');
```

## 🌐 API Endpoints

### Users Management

```
GET    /api/users              - Lấy danh sách users (permission: users.view)
POST   /api/users              - Tạo user mới (permission: users.create)
GET    /api/users/{id}         - Xem chi tiết user (permission: users.view)
PUT    /api/users/{id}         - Cập nhật user (permission: users.edit)
DELETE /api/users/{id}         - Xóa user (permission: users.delete)
POST   /api/users/{id}/assign-role   - Gán role (permission: users.assign-role)
POST   /api/users/{id}/remove-role   - Thu hồi role (permission: users.assign-role)
```

### Roles Management

```
GET    /api/roles              - Lấy danh sách roles (permission: roles.view)
POST   /api/roles              - Tạo role mới (permission: roles.create)
GET    /api/roles/{id}         - Xem chi tiết role (permission: roles.view)
PUT    /api/roles/{id}         - Cập nhật role (permission: roles.edit)
DELETE /api/roles/{id}         - Xóa role (permission: roles.delete)
POST   /api/roles/{id}/assign-permission   - Gán permission (permission: roles.assign-permission)
POST   /api/roles/{id}/revoke-permission   - Thu hồi permission (permission: roles.assign-permission)
```

### Permissions Management

```
GET    /api/permissions        - Lấy danh sách permissions
GET    /api/permissions/modules - Lấy danh sách modules
GET    /api/permissions/by-module/{module} - Lấy permissions theo module
GET    /api/permissions/{id}   - Xem chi tiết permission
POST   /api/permissions        - Tạo permission (role: super-admin)
PUT    /api/permissions/{id}   - Cập nhật permission (role: super-admin)
DELETE /api/permissions/{id}   - Xóa permission (role: super-admin)
```

## 👤 Tài Khoản Test

```
Super Admin:
- Email: admin@example.com
- Password: password

Admin:
- Email: admin2@example.com
- Password: password

Manager:
- Email: manager@example.com
- Password: password

Staff:
- Email: staff@example.com
- Password: password

User:
- Email: user@example.com
- Password: password
```

## 🚀 Mở Rộng Hệ Thống

### Thêm Module Mới

1. Tạo permissions cho module mới trong seeder:

```php
[
    'module' => 'new_module',
    'actions' => [
        ['action' => 'view', 'display_name' => 'Xem module mới', 'sort_order' => 1],
        ['action' => 'create', 'display_name' => 'Tạo mới', 'sort_order' => 2],
        // ... thêm actions khác
    ],
]
```

2. Gán permissions cho roles phù hợp

3. Sử dụng middleware trong routes:

```php
Route::prefix('new-module')->middleware('permission:new_module.view')->group(function () {
    // Routes của module mới
});
```

### Thêm Action Mới Cho Module

```php
Permission::create([
    'module' => 'products',
    'action' => 'export',
    'name' => 'products.export',
    'display_name' => 'Xuất sản phẩm',
    'description' => 'Quyền xuất danh sách sản phẩm',
    'sort_order' => 5,
    'is_active' => true,
]);
```

## 📊 Ưu Điểm Của Hệ Thống

✅ **Linh hoạt**: Dễ dàng thêm modules và permissions mới
✅ **Phân cấp rõ ràng**: Module → Action → Permission
✅ **Dễ mở rộng**: Cấu trúc database tối ưu cho việc mở rộng
✅ **Hiệu suất cao**: Sử dụng relationships và eager loading
✅ **Bảo mật**: Middleware kiểm tra quyền ở mọi endpoint
✅ **Dễ bảo trì**: Code rõ ràng, có comments đầy đủ

## 🔍 Kiểm Tra Hệ Thống

Chạy lệnh để xem danh sách routes:
```bash
php artisan route:list --path=api
```

Kiểm tra database:
```bash
php artisan tinker

>>> User::with('roles.permissions')->first()
>>> Role::with('permissions')->get()
>>> Permission::getModules()
```

