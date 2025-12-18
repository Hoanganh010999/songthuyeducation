# Hướng Dẫn Test API Phân Quyền

## 🧪 Test Bằng Postman/Thunder Client

### 1. Đăng Nhập (Lấy Token)

Trước tiên, bạn cần cài đặt Laravel Sanctum authentication. Tạm thời để test, bạn có thể tạo một route đơn giản:

**File: routes/api.php**

```php
use Illuminate\Support\Facades\Hash;

// Route đăng nhập đơn giản (chỉ để test)
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Email hoặc mật khẩu không đúng'
        ], 401);
    }

    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'token' => $token,
        'user' => $user->load('roles.permissions')
    ]);
});
```

### 2. Test API Endpoints

#### A. Đăng Nhập

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
    "token": "1|xxxxxxxxxxxxxxxxxxxxx",
    "user": {
        "id": 1,
        "name": "Super Admin",
        "email": "admin@example.com",
        "roles": [...]
    }
}
```

#### B. Lấy Danh Sách Users

```http
GET http://localhost:8000/api/users
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "Super Admin",
                "email": "admin@example.com",
                "roles": [...]
            }
        ],
        "total": 5
    }
}
```

#### C. Tạo User Mới

```http
POST http://localhost:8000/api/users
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "New User",
    "email": "newuser@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role_ids": [5]
}
```

#### D. Lấy Danh Sách Roles

```http
GET http://localhost:8000/api/roles?with_permissions=true
Authorization: Bearer {token}
```

#### E. Gán Role Cho User

```http
POST http://localhost:8000/api/users/1/assign-role
Authorization: Bearer {token}
Content-Type: application/json

{
    "role_id": 2
}
```

#### F. Lấy Danh Sách Permissions Theo Module

```http
GET http://localhost:8000/api/permissions?group_by_module=true
Authorization: Bearer {token}
```

#### G. Gán Permission Cho Role

```http
POST http://localhost:8000/api/roles/3/assign-permission
Authorization: Bearer {token}
Content-Type: application/json

{
    "permission_id": 10
}
```

### 3. Test Phân Quyền

#### Test với User Không Có Quyền

1. Đăng nhập với user có role "user":
```http
POST http://localhost:8000/api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

2. Thử tạo user mới (sẽ bị từ chối):
```http
POST http://localhost:8000/api/users
Authorization: Bearer {token_của_user}
Content-Type: application/json

{
    "name": "Test",
    "email": "test@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

**Response (403 Forbidden):**
```json
{
    "success": false,
    "message": "Bạn không có quyền truy cập chức năng này",
    "required_permission": "users.create"
}
```

## 🔍 Test Bằng Laravel Tinker

```bash
php artisan tinker
```

### Kiểm Tra User và Roles

```php
// Lấy user
$user = User::find(1);

// Xem roles của user
$user->roles;

// Xem tất cả permissions của user
$user->getAllPermissions();

// Kiểm tra user có quyền
$user->hasPermission('users.create'); // true/false

// Kiểm tra user có role
$user->hasRole('admin'); // true/false
```

### Kiểm Tra Role và Permissions

```php
// Lấy role
$role = Role::find(1);

// Xem permissions của role
$role->permissions;

// Xem users có role này
$role->users;

// Kiểm tra role có permission
$role->hasPermission('users.create'); // true/false
```

### Thao Tác Với Permissions

```php
// Lấy tất cả modules
Permission::getModules();

// Lấy permissions theo module
Permission::getByModule('users');

// Tạo permission mới
Permission::create([
    'module' => 'settings',
    'action' => 'view',
    'name' => 'settings.view',
    'display_name' => 'Xem cài đặt',
    'is_active' => true
]);
```

### Gán Quyền

```php
$user = User::find(1);
$role = Role::where('name', 'manager')->first();

// Gán role cho user
$user->assignRole($role);

// Gán permission cho role
$permission = Permission::where('name', 'products.create')->first();
$role->givePermissionTo($permission);
```

## 📝 Test Cases Quan Trọng

### 1. Test Phân Quyền Module
- ✅ Super Admin có thể truy cập tất cả modules
- ✅ Admin không thể truy cập module roles
- ✅ Manager chỉ truy cập được products, orders, reports
- ✅ Staff chỉ truy cập được orders
- ✅ User chỉ xem được products

### 2. Test Phân Quyền Chức Năng
- ✅ User có permission "users.view" có thể xem danh sách users
- ✅ User không có permission "users.create" không thể tạo user
- ✅ User có permission "orders.edit" có thể sửa orders
- ✅ User không có permission "orders.delete" không thể xóa orders

### 3. Test Cascade Delete
- ✅ Xóa role → xóa các liên kết trong role_user và permission_role
- ✅ Xóa permission → xóa các liên kết trong permission_role
- ✅ Không thể xóa role đang được sử dụng bởi users
- ✅ Không thể xóa permission đang được sử dụng bởi roles

### 4. Test Validation
- ✅ Không thể tạo role với name trùng lặp
- ✅ Không thể tạo permission với name trùng lặp
- ✅ Không thể gán role không tồn tại cho user
- ✅ Không thể gán permission không tồn tại cho role

## 🐛 Debug

### Xem Query Log

```php
// Trong Controller hoặc Route
\DB::enableQueryLog();

// ... code của bạn ...

dd(\DB::getQueryLog());
```

### Xem Permissions Của User Hiện Tại

```php
Route::get('/debug/my-permissions', function () {
    $user = auth()->user();
    
    return response()->json([
        'user' => $user,
        'roles' => $user->roles,
        'permissions' => $user->getAllPermissions(),
    ]);
})->middleware('auth:sanctum');
```

### Kiểm Tra Routes

```bash
# Xem tất cả routes
php artisan route:list

# Xem routes có middleware permission
php artisan route:list | grep permission

# Xem routes của API
php artisan route:list --path=api
```

