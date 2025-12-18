# 🏫 School Management System

Hệ thống quản lý trường học với **Hệ thống phân quyền đa cấp** (Module-based & Action-based Permissions)

## 🚀 Tính Năng Chính

✅ **Hệ thống phân quyền 2 cấp**
- Phân quyền theo Module (users, products, orders, reports, etc.)
- Phân quyền theo Action (view, create, edit, delete, etc.)

✅ **Quản lý Users & Roles**
- CRUD Users với gán vai trò
- CRUD Roles với gán quyền
- Middleware bảo mật cho mọi endpoint

✅ **RESTful API hoàn chỉnh**
- Authentication (Login/Logout)
- Users Management
- Roles Management  
- Permissions Management

✅ **Dữ liệu mẫu sẵn có**
- 5 Roles: super-admin, admin, manager, staff, user
- 21 Permissions trên 5 modules
- 5 Users test với các roles khác nhau

## 📋 Yêu Cầu Hệ Thống

- PHP >= 8.2
- Composer
- MySQL/MariaDB
- Node.js & NPM
- Laravel 11.x

## 🔧 Cài Đặt

### 1. Clone Repository

```bash
git clone <repository-url>
cd school
```

### 2. Cài Đặt Dependencies

```bash
# Backend
composer install

# Frontend
npm install
```

### 3. Cấu Hình Environment

```bash
cp .env.example .env
php artisan key:generate
```

Cập nhật file `.env` với thông tin database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Chạy Migrations & Seeders

```bash
php artisan migrate:fresh --seed
```

Lệnh này sẽ tạo:
- ✅ Bảng users, roles, permissions và các bảng pivot
- ✅ 5 roles mặc định
- ✅ 21 permissions trên 5 modules
- ✅ 5 users test

### 5. Build Assets

```bash
npm run build
```

Hoặc chạy dev server:

```bash
npm run dev
```

### 6. Khởi Động Server

```bash
php artisan serve
```

Truy cập: `http://localhost:8000`

## 👤 Tài Khoản Test

| Email | Password | Role | Mô tả |
|-------|----------|------|-------|
| admin@example.com | password | super-admin | Toàn quyền (21 permissions) |
| admin2@example.com | password | admin | Quản lý hệ thống (16 permissions) |
| manager@example.com | password | manager | Quản lý SP & ĐH (11 permissions) |
| staff@example.com | password | staff | Nhân viên (2 permissions) |
| user@example.com | password | user | Người dùng (1 permission) |

## 📚 Documentation

- **[PERMISSION_SYSTEM.md](PERMISSION_SYSTEM.md)** - Hướng dẫn chi tiết hệ thống phân quyền
- **[API_TESTING.md](API_TESTING.md)** - Hướng dẫn test API với Postman
- **[SYSTEM_SUMMARY.md](SYSTEM_SUMMARY.md)** - Tóm tắt toàn bộ hệ thống

## 🔐 API Endpoints

### Authentication

```http
POST   /api/login              # Đăng nhập
POST   /api/logout             # Đăng xuất
GET    /api/user               # Lấy thông tin user hiện tại
```

### Users Management

```http
GET    /api/users              # Danh sách users
POST   /api/users              # Tạo user mới
GET    /api/users/{id}         # Chi tiết user
PUT    /api/users/{id}         # Cập nhật user
DELETE /api/users/{id}         # Xóa user
POST   /api/users/{id}/assign-role   # Gán role
POST   /api/users/{id}/remove-role   # Thu hồi role
```

### Roles Management

```http
GET    /api/roles              # Danh sách roles
POST   /api/roles              # Tạo role mới
GET    /api/roles/{id}         # Chi tiết role
PUT    /api/roles/{id}         # Cập nhật role
DELETE /api/roles/{id}         # Xóa role
POST   /api/roles/{id}/assign-permission   # Gán permission
POST   /api/roles/{id}/revoke-permission   # Thu hồi permission
```

### Permissions Management

```http
GET    /api/permissions        # Danh sách permissions
GET    /api/permissions/modules # Danh sách modules
GET    /api/permissions/by-module/{module} # Permissions theo module
GET    /api/permissions/{id}   # Chi tiết permission
POST   /api/permissions        # Tạo permission (super-admin only)
PUT    /api/permissions/{id}   # Cập nhật permission (super-admin only)
DELETE /api/permissions/{id}   # Xóa permission (super-admin only)
```

## 💡 Ví Dụ Sử Dụng

### Test API với cURL

**1. Đăng nhập:**

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

**2. Lấy danh sách users:**

```bash
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Sử dụng trong Code

**Kiểm tra quyền:**

```php
// Trong Controller
if (auth()->user()->hasPermission('users.create')) {
    // Cho phép tạo user
}

// Kiểm tra role
if (auth()->user()->hasRole('admin')) {
    // User là admin
}
```

**Sử dụng Middleware:**

```php
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:users.view');

Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('role:admin,super-admin');
```

## 🗄️ Database Schema

### Roles
- Lưu trữ các vai trò (Admin, Manager, User, etc.)
- Có thể kích hoạt/vô hiệu hóa

### Permissions
- Lưu trữ quyền theo format: `module.action`
- Ví dụ: `users.create`, `products.edit`, `orders.delete`
- Nhóm theo module để dễ quản lý

### Role-User (Many-to-Many)
- Một user có thể có nhiều roles
- Một role có thể được gán cho nhiều users

### Permission-Role (Many-to-Many)
- Một role có thể có nhiều permissions
- Một permission có thể thuộc nhiều roles

## 🎯 Modules Có Sẵn

1. **users** - Quản lý người dùng (5 permissions)
2. **roles** - Quản lý vai trò (5 permissions)
3. **products** - Quản lý sản phẩm (4 permissions)
4. **orders** - Quản lý đơn hàng (5 permissions)
5. **reports** - Báo cáo (2 permissions)

## 🔄 Mở Rộng Hệ Thống

### Thêm Module Mới

1. Tạo permissions trong seeder:

```php
[
    'module' => 'new_module',
    'actions' => [
        ['action' => 'view', 'display_name' => 'Xem module mới'],
        ['action' => 'create', 'display_name' => 'Tạo mới'],
    ],
]
```

2. Gán permissions cho roles
3. Tạo controller và routes với middleware

### Thêm Permission Mới

```php
Permission::create([
    'module' => 'products',
    'action' => 'export',
    'name' => 'products.export',
    'display_name' => 'Xuất sản phẩm',
    'is_active' => true
]);
```

## 🧪 Testing

### Chạy Tests

```bash
php artisan test
```

### Test với Tinker

```bash
php artisan tinker

>>> $user = User::find(1);
>>> $user->hasPermission('users.create');
>>> $user->getAllPermissions();
```

## 📊 Cấu Trúc Thư Mục

```
school/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── UserController.php
│   │   │   ├── RoleController.php
│   │   │   └── PermissionController.php
│   │   └── Middleware/
│   │       ├── CheckPermission.php
│   │       └── CheckRole.php
│   └── Models/
│       ├── User.php
│       ├── Role.php
│       └── Permission.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── docs/
    ├── PERMISSION_SYSTEM.md
    ├── API_TESTING.md
    └── SYSTEM_SUMMARY.md
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Credits

Built with ❤️ using:
- [Laravel 11](https://laravel.com)
- [Vue.js 3](https://vuejs.org)
- [Tailwind CSS 4](https://tailwindcss.com)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)

---

**Developed by:** Your Team
**Last Updated:** October 31, 2025
