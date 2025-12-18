# 🚀 Hướng Dẫn Nhanh

## Khởi Động Dự Án

### 1. Cài đặt (Chỉ làm 1 lần)

```bash
# Clone project
cd C:\xampp\htdocs\school

# Cài đặt dependencies
composer install
npm install

# Cấu hình database trong .env
# Sau đó chạy:
php artisan migrate:fresh --seed

# Build assets
npm run build

# Note: Laravel Sanctum đã được cài đặt sẵn
```

### 2. Chạy Server

**Terminal 1 - Laravel:**
```bash
php artisan serve
```

**Terminal 2 - Vite (Tùy chọn - nếu cần hot reload):**
```bash
npm run dev
```

## 🧪 Test Nhanh API

### 1. Đăng Nhập

**Request:**
```http
POST http://localhost:8000/api/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Đăng nhập thành công",
    "token": "1|xxxxxxxxxxxxx",
    "user": {
        "id": 1,
        "name": "Super Admin",
        "email": "admin@example.com",
        "roles": [...]
    }
}
```

### 2. Lấy Danh Sách Users

**Request:**
```http
GET http://localhost:8000/api/users
Authorization: Bearer {token_từ_bước_1}
```

### 3. Tạo User Mới

**Request:**
```http
POST http://localhost:8000/api/users
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role_ids": [5]
}
```

### 4. Lấy Danh Sách Roles

**Request:**
```http
GET http://localhost:8000/api/roles?with_permissions=true
Authorization: Bearer {token}
```

### 5. Lấy Permissions Theo Module

**Request:**
```http
GET http://localhost:8000/api/permissions?group_by_module=true
Authorization: Bearer {token}
```

## 📊 Kiểm Tra Database

```bash
php artisan tinker
```

```php
// Xem tất cả users và roles
User::with('roles')->get()

// Xem permissions của user
$user = User::find(1);
$user->getAllPermissions();

// Kiểm tra quyền
$user->hasPermission('users.create'); // true/false
$user->hasRole('admin'); // true/false

// Xem tất cả roles và permissions
Role::with('permissions')->get()

// Xem modules
Permission::getModules();
```

## 🔑 Tài Khoản Test

| Email | Password | Role | Quyền |
|-------|----------|------|-------|
| admin@example.com | password | super-admin | Tất cả |
| admin2@example.com | password | admin | Nhiều |
| manager@example.com | password | manager | Trung bình |
| staff@example.com | password | staff | Ít |
| user@example.com | password | user | Rất ít |

## 🎯 Test Phân Quyền

### Test 1: Super Admin có thể làm mọi thứ

```bash
# Đăng nhập với admin@example.com
# Thử tất cả endpoints → Tất cả đều thành công
```

### Test 2: User thường không thể tạo user

```bash
# Đăng nhập với user@example.com
# Thử POST /api/users → Lỗi 403 Forbidden
```

### Test 3: Manager có thể quản lý products

```bash
# Đăng nhập với manager@example.com
# GET /api/permissions?module=products → Thành công
```

## 📝 Các Lệnh Hữu Ích

```bash
# Xem tất cả routes
php artisan route:list

# Xem routes API
php artisan route:list --path=api

# Xem routes có middleware permission
php artisan route:list | grep permission

# Reset database và seed lại
php artisan migrate:fresh --seed

# Xóa cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## 🐛 Troubleshooting

### Lỗi: "Vite manifest not found"
```bash
npm run build
```

### Lỗi: "SQLSTATE[HY000] [1045] Access denied"
- Kiểm tra thông tin database trong file `.env`
- Đảm bảo MySQL đang chạy

### Lỗi: "Class 'Role' not found"
```bash
composer dump-autoload
```

### API trả về 401 Unauthorized
- Kiểm tra token có đúng không
- Kiểm tra header: `Authorization: Bearer {token}`

### API trả về 403 Forbidden
- User không có quyền
- Kiểm tra permissions của user: `$user->getAllPermissions()`

## 📚 Đọc Thêm

- [PERMISSION_SYSTEM.md](PERMISSION_SYSTEM.md) - Chi tiết hệ thống phân quyền
- [API_TESTING.md](API_TESTING.md) - Hướng dẫn test API đầy đủ
- [SYSTEM_SUMMARY.md](SYSTEM_SUMMARY.md) - Tóm tắt hệ thống

## ✅ Checklist Hoàn Thành

- [x] Database đã được migrate và seed
- [x] Server Laravel đang chạy
- [x] Assets đã được build
- [x] Test API login thành công
- [x] Test API users thành công
- [x] Test phân quyền hoạt động

Chúc bạn code vui vẻ! 🎉

