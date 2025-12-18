# 🌍 Hướng Dẫn Sử Dụng Hệ Thống Đa Ngôn Ngữ

## 🎯 Xem Tiếng Việt Ngay Bây Giờ

### Bước 1: Khởi động ứng dụng
```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start Vite (nếu muốn dev mode)
npm run dev
```

### Bước 2: Truy cập và đăng nhập
1. Mở trình duyệt: `http://127.0.0.1:8000`
2. Đăng nhập với:
   - **Email:** `admin@example.com`
   - **Password:** `password`

### Bước 3: Đổi sang tiếng Việt
1. Nhìn lên **top navigation bar** (góc phải)
2. Thấy **Language Switcher** (🇬🇧 English)
3. Click vào đó
4. Chọn **🇻🇳 Tiếng Việt**
5. Page sẽ tự động reload
6. **Tất cả text đã được dịch sang tiếng Việt!** ✨

---

## 📝 Chỉnh Sửa Translations

### Option 1: Qua UI (Dễ nhất - Dành cho Super Admin)

1. **Đăng nhập** với Super Admin account
2. **Sidebar** → **System Settings** → **Translations**
3. Bạn sẽ thấy trang quản lý translations với:
   - **Filter theo Language:** Chọn English hoặc Tiếng Việt
   - **Filter theo Group:** common, auth, users, roles, dashboard...
   - **Search:** Tìm theo key hoặc value

4. **Chỉnh sửa translation:**
   - Click icon **Edit** (✏️) bên cạnh translation muốn sửa
   - Sửa **Value** (nội dung dịch)
   - Click **Save**

5. **Thêm translation mới:**
   - Click **Add Translation**
   - Chọn **Language** (EN hoặc VI)
   - Nhập **Group** (ví dụ: `products`)
   - Nhập **Key** (ví dụ: `add_to_cart`)
   - Nhập **Value** (ví dụ: `Thêm vào giỏ` cho VI)
   - Click **Save**

6. **Reload page** để thấy thay đổi

### Option 2: Qua Database (Nhanh)

```sql
-- Xem tất cả translations
SELECT * FROM translations;

-- Xem translations của tiếng Việt (language_id = 2)
SELECT * FROM translations WHERE language_id = 2;

-- Sửa một translation
UPDATE translations 
SET value = 'Văn bản mới' 
WHERE language_id = 2 AND `group` = 'common' AND `key` = 'welcome';

-- Thêm translation mới
INSERT INTO translations (language_id, `group`, `key`, value, created_at, updated_at)
VALUES 
(1, 'products', 'add_to_cart', 'Add to Cart', NOW(), NOW()),
(2, 'products', 'add_to_cart', 'Thêm vào giỏ', NOW(), NOW());
```

### Option 3: Qua Seeder (Cho nhiều translations)

**File:** `database/seeders/LanguageSeeder.php`

```php
// Thêm method mới
private function createProductsTranslations(Language $en, Language $vi): void
{
    $translations = [
        'title' => ['Products', 'Sản phẩm'],
        'add_to_cart' => ['Add to Cart', 'Thêm vào giỏ'],
        'price' => ['Price', 'Giá'],
        'stock' => ['Stock', 'Tồn kho'],
        'description' => ['Description', 'Mô tả'],
    ];

    foreach ($translations as $key => [$enValue, $viValue]) {
        Translation::create([
            'language_id' => $en->id,
            'group' => 'products',
            'key' => $key,
            'value' => $enValue
        ]);
        Translation::create([
            'language_id' => $vi->id,
            'group' => 'products',
            'key' => $key,
            'value' => $viValue
        ]);
    }
}

// Gọi trong run()
public function run(): void
{
    // ... existing code ...
    $this->createProductsTranslations($english, $vietnamese);
}
```

**Chạy seeder:**
```bash
php artisan db:seed --class=LanguageSeeder
```

---

## 🎨 Sử Dụng Translations Trong Code

### Trong Vue Component

```vue
<template>
  <div>
    <!-- Cách 1: Sử dụng t() -->
    <h1>{{ t('users.title') }}</h1>
    <button>{{ t('common.save') }}</button>
    <p>{{ t('users.create_success') }}</p>

    <!-- Cách 2: Với placeholder -->
    <input :placeholder="t('common.search')" />

    <!-- Cách 3: Trong attribute -->
    <button :title="t('common.delete')">
      Delete
    </button>
  </div>
</template>

<script setup>
import { useI18n } from '../composables/useI18n';

const { t } = useI18n();
</script>
```

### Translation Keys Hiện Có

#### **common.*** (Dùng chung)
```javascript
t('common.welcome')      // Welcome / Chào mừng
t('common.home')         // Home / Trang chủ
t('common.dashboard')    // Dashboard / Bảng điều khiển
t('common.save')         // Save / Lưu
t('common.cancel')       // Cancel / Hủy
t('common.delete')       // Delete / Xóa
t('common.edit')         // Edit / Sửa
t('common.create')       // Create / Tạo mới
t('common.view')         // View / Xem
t('common.search')       // Search / Tìm kiếm
t('common.loading')      // Loading... / Đang tải...
t('common.no_data')      // No data available / Không có dữ liệu
t('common.active')       // Active / Kích hoạt
t('common.inactive')     // Inactive / Vô hiệu
t('common.showing')      // Showing / Hiển thị
```

#### **auth.*** (Authentication)
```javascript
t('auth.login_title')    // Login to your account / Đăng nhập tài khoản
t('auth.email')          // Email / Email
t('auth.password')       // Password / Mật khẩu
t('auth.login_button')   // Sign in / Đăng nhập
t('auth.logout')         // Logout / Đăng xuất
```

#### **dashboard.*** (Dashboard)
```javascript
t('dashboard.welcome_message')    // Welcome back / Chào mừng trở lại
t('dashboard.total_users')        // Total Users / Tổng người dùng
t('dashboard.total_roles')        // Total Roles / Tổng vai trò
t('dashboard.total_permissions')  // Total Permissions / Tổng quyền
t('dashboard.your_permissions')   // Your Permissions / Quyền của bạn
t('dashboard.your_roles')         // Your Roles / Vai trò của bạn
t('dashboard.quick_actions')      // Quick Actions / Thao tác nhanh
```

#### **users.*** (Users Management)
```javascript
t('users.title')          // Users Management / Quản lý người dùng
t('users.list')           // Users List / Danh sách người dùng
t('users.create')         // Create User / Tạo người dùng
t('users.edit')           // Edit User / Sửa người dùng
t('users.delete')         // Delete User / Xóa người dùng
t('users.name')           // Name / Tên
t('users.email')          // Email / Email
t('users.roles')          // Roles / Vai trò
```

#### **roles.*** (Roles Management)
```javascript
t('roles.title')          // Roles Management / Quản lý vai trò
t('roles.list')           // Roles List / Danh sách vai trò
t('roles.create')         // Create Role / Tạo vai trò
t('roles.edit')           // Edit Role / Sửa vai trò
t('roles.permissions')    // Permissions / Quyền
```

#### **permissions.*** (Permissions)
```javascript
t('permissions.title')    // Permissions Management / Quản lý quyền
t('permissions.module')   // Module / Module
t('permissions.action')   // Action / Hành động
```

#### **settings.*** (Settings)
```javascript
t('settings.title')               // System Settings / Cài đặt hệ thống
t('settings.languages')           // Languages / Ngôn ngữ
t('settings.translations')        // Translations / Bản dịch
t('settings.language_name')       // Language Name / Tên ngôn ngữ
t('settings.add_language')        // Add Language / Thêm ngôn ngữ
t('settings.manage_translations') // Manage Translations / Quản lý bản dịch
```

---

## 🔧 Thêm Module Mới

### Ví dụ: Thêm module Products

#### 1. Thêm translations vào Seeder

```php
private function createProductsTranslations(Language $en, Language $vi): void
{
    $translations = [
        'title' => ['Products Management', 'Quản lý sản phẩm'],
        'list' => ['Products List', 'Danh sách sản phẩm'],
        'create' => ['Create Product', 'Tạo sản phẩm'],
        'edit' => ['Edit Product', 'Sửa sản phẩm'],
        'delete' => ['Delete Product', 'Xóa sản phẩm'],
        'name' => ['Product Name', 'Tên sản phẩm'],
        'price' => ['Price', 'Giá'],
        'stock' => ['Stock', 'Tồn kho'],
        'category' => ['Category', 'Danh mục'],
        'description' => ['Description', 'Mô tả'],
        'add_to_cart' => ['Add to Cart', 'Thêm vào giỏ'],
        'buy_now' => ['Buy Now', 'Mua ngay'],
        'out_of_stock' => ['Out of Stock', 'Hết hàng'],
    ];

    foreach ($translations as $key => [$enValue, $viValue]) {
        Translation::create([
            'language_id' => $en->id,
            'group' => 'products',
            'key' => $key,
            'value' => $enValue
        ]);
        Translation::create([
            'language_id' => $vi->id,
            'group' => 'products',
            'key' => $key,
            'value' => $viValue
        ]);
    }
}
```

#### 2. Sử dụng trong Vue

```vue
<template>
  <div>
    <h1>{{ t('products.title') }}</h1>
    <button>{{ t('products.create') }}</button>
    
    <div v-for="product in products" :key="product.id">
      <h3>{{ product.name }}</h3>
      <p>{{ t('products.price') }}: {{ product.price }}</p>
      <p>{{ t('products.stock') }}: {{ product.stock }}</p>
      <button>{{ t('products.add_to_cart') }}</button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from '../composables/useI18n';
const { t } = useI18n();
</script>
```

---

## 🌐 Thêm Ngôn Ngữ Mới (ví dụ: Tiếng Pháp)

### 1. Thêm Language qua UI

1. Login với Super Admin
2. **Settings** → **Languages**
3. Click **Add Language**
4. Điền:
   - Name: `Français`
   - Code: `fr`
   - Flag: `🇫🇷`
   - Direction: `ltr`
   - Active: ✅
5. Save

### 2. Sync Translations từ English

**Qua API:**
```bash
curl -X POST http://127.0.0.1:8000/api/settings/translations/sync-languages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "source_language_id": 1,
    "target_language_id": 3
  }'
```

### 3. Dịch từng translation

1. **Settings** → **Translations**
2. Filter by Language: **Français**
3. Edit từng translation để dịch sang tiếng Pháp

### 4. Kích hoạt

Ngôn ngữ mới tự động xuất hiện trong Language Switcher! 🎉

---

## 🐛 Troubleshooting

### Không thấy tiếng Việt?

1. **Kiểm tra Language Switcher:**
   - Có hiển thị ở top bar không?
   - Có option Tiếng Việt không?

2. **Clear cache:**
   ```javascript
   // Mở Console (F12)
   localStorage.clear()
   location.reload()
   ```

3. **Kiểm tra database:**
   ```sql
   SELECT * FROM languages WHERE code = 'vi';
   SELECT COUNT(*) FROM translations WHERE language_id = 2;
   ```

4. **Rebuild frontend:**
   ```bash
   npm run build
   ```

### Translations không cập nhật?

1. **Reload page** (Ctrl + Shift + R)
2. **Clear localStorage:**
   ```javascript
   localStorage.removeItem('app_translations')
   location.reload()
   ```

### Một số text vẫn bằng tiếng Việt hardcode?

- Đó là text chưa được chuyển sang dùng `t()`
- Bạn có thể tự cập nhật hoặc yêu cầu developer cập nhật

---

## ✅ Checklist

- [ ] Đã login được
- [ ] Thấy Language Switcher ở top bar
- [ ] Click được và thấy 🇬🇧 English, 🇻🇳 Tiếng Việt
- [ ] Đổi sang tiếng Việt → Page reload
- [ ] Dashboard hiển thị tiếng Việt
- [ ] Users page hiển thị tiếng Việt
- [ ] Có thể vào Settings → Translations (Super Admin)
- [ ] Có thể edit translations qua UI

---

## 📚 Tài Liệu Chi Tiết

- **Full Guide:** `I18N_SYSTEM_GUIDE.md`
- **Quick Start:** `QUICK_I18N_GUIDE.md`

---

🎉 **Chúc bạn sử dụng hệ thống đa ngôn ngữ vui vẻ!**

