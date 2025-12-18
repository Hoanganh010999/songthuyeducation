# ✅ Khắc Phục Lỗi Sanctum

## Lỗi: `Call to undefined method App\Models\User::createToken()`

### Nguyên nhân:
Laravel Sanctum chưa được cài đặt hoặc chưa được cấu hình đúng.

### Đã khắc phục:

#### 1. ✅ Cài đặt Laravel Sanctum
```bash
composer require laravel/sanctum
```

#### 2. ✅ Thêm HasApiTokens vào User Model
File: `app/Models/User.php`
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    // ...
}
```

#### 3. ✅ Cấu hình Middleware
File: `bootstrap/app.php`
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();
    // ...
})
```

#### 4. ✅ Chạy Migrations
```bash
php artisan migrate:fresh --seed
```

### Bây giờ có thể:

#### ✅ Đăng nhập thành công
```bash
POST http://localhost:8000/api/login
{
    "email": "admin@example.com",
    "password": "password"
}
```

Response:
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

#### ✅ Sử dụng Token
```bash
GET http://localhost:8000/api/users
Authorization: Bearer {token}
```

### Test ngay:

1. **Truy cập:** `http://localhost:8000/auth/login`
2. **Đăng nhập với:**
   - Email: `admin@example.com`
   - Password: `password`
3. **Sẽ redirect về Dashboard** ✅

### Tài khoản test:

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | super-admin |
| admin2@example.com | password | admin |
| manager@example.com | password | manager |
| staff@example.com | password | staff |
| user@example.com | password | user |

### Kiểm tra Token:

Sau khi đăng nhập, mở Console (F12):
```javascript
// Xem token
console.log(localStorage.getItem('token'));

// Xem user
console.log(localStorage.getItem('user'));
```

### Nếu vẫn lỗi:

```bash
# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Restart server
# Ctrl+C để dừng
php artisan serve
```

---

## ✅ Đã hoàn thành!

Hệ thống authentication với Sanctum đã sẵn sàng! 🎉

Bây giờ bạn có thể:
- ✅ Đăng nhập/Đăng xuất
- ✅ Quản lý Users (CRUD)
- ✅ Phân quyền tự động
- ✅ API authentication với JWT tokens

