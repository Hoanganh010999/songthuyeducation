# 🌍 Hệ Thống Đa Ngôn Ngữ (i18n) - Hướng Dẫn Đầy Đủ

## 📋 Tổng Quan

Hệ thống đa ngôn ngữ cho phép ứng dụng hiển thị nội dung bằng nhiều ngôn ngữ khác nhau. Người dùng có thể chọn ngôn ngữ ưa thích và tất cả nội dung sẽ được hiển thị tương ứng.

### ✨ Tính Năng Chính

- ✅ Quản lý nhiều ngôn ngữ (English, Tiếng Việt, ...)
- ✅ Quản lý bản dịch theo nhóm (common, auth, users, ...)
- ✅ Language Switcher cho người dùng
- ✅ Settings Management cho Super Admin
- ✅ Tự động load và cache translations
- ✅ Dễ dàng mở rộng thêm ngôn ngữ mới

---

## 🗄️ Cấu Trúc Database

### Bảng `languages`
Lưu trữ thông tin các ngôn ngữ trong hệ thống.

```sql
- id: Primary Key
- name: Tên ngôn ngữ (English, Tiếng Việt)
- code: Mã ngôn ngữ (en, vi)
- flag: Icon cờ (🇬🇧, 🇻🇳)
- direction: Hướng văn bản (ltr, rtl)
- is_default: Ngôn ngữ mặc định
- is_active: Trạng thái kích hoạt
- sort_order: Thứ tự hiển thị
```

### Bảng `translations`
Lưu trữ các bản dịch.

```sql
- id: Primary Key
- language_id: Foreign Key -> languages
- group: Nhóm (common, auth, users, ...)
- key: Khóa dịch (welcome, login_button, ...)
- value: Nội dung dịch
- UNIQUE(language_id, group, key)
```

### Bảng `users`
Thêm cột `language_id` để lưu ngôn ngữ ưa thích của user.

---

## 🔧 Backend API

### Public APIs (Không cần authentication)

#### 1. Lấy danh sách ngôn ngữ active
```http
GET /api/languages
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "English",
      "code": "en",
      "flag": "🇬🇧",
      "direction": "ltr",
      "is_default": true,
      "is_active": true,
      "sort_order": 1
    },
    {
      "id": 2,
      "name": "Tiếng Việt",
      "code": "vi",
      "flag": "🇻🇳",
      "direction": "ltr",
      "is_default": false,
      "is_active": true,
      "sort_order": 2
    }
  ]
}
```

#### 2. Lấy tất cả translations của một ngôn ngữ
```http
GET /api/languages/{code}/translations
```

**Example:** `GET /api/languages/en/translations`

**Response:**
```json
{
  "success": true,
  "data": {
    "language": {
      "id": 1,
      "name": "English",
      "code": "en",
      "flag": "🇬🇧"
    },
    "translations": {
      "common": {
        "welcome": "Welcome",
        "home": "Home",
        "dashboard": "Dashboard",
        "save": "Save",
        "cancel": "Cancel"
      },
      "auth": {
        "login_title": "Login to your account",
        "email": "Email",
        "password": "Password",
        "login_button": "Sign in"
      },
      "users": {
        "title": "Users Management",
        "create": "Create User",
        "edit": "Edit User"
      }
    }
  }
}
```

### Super Admin APIs (Cần role: super-admin)

#### 3. Quản lý Languages

**Lấy tất cả languages (bao gồm inactive):**
```http
GET /api/settings/languages
```

**Tạo language mới:**
```http
POST /api/settings/languages
Content-Type: application/json

{
  "name": "Français",
  "code": "fr",
  "flag": "🇫🇷",
  "direction": "ltr",
  "is_active": true,
  "is_default": false,
  "sort_order": 3
}
```

**Cập nhật language:**
```http
PUT /api/settings/languages/{id}
Content-Type: application/json

{
  "name": "French",
  "is_active": true
}
```

**Đặt làm ngôn ngữ mặc định:**
```http
POST /api/settings/languages/{id}/set-default
```

**Xóa language:**
```http
DELETE /api/settings/languages/{id}
```

#### 4. Quản lý Translations

**Lấy danh sách translations (có filter và search):**
```http
GET /api/settings/translations?language_id=1&group=common&search=welcome&page=1
```

**Tạo translation mới:**
```http
POST /api/settings/translations
Content-Type: application/json

{
  "language_id": 1,
  "group": "products",
  "key": "add_to_cart",
  "value": "Add to Cart"
}
```

**Cập nhật translation:**
```http
PUT /api/settings/translations/{id}
Content-Type: application/json

{
  "value": "Add to Shopping Cart"
}
```

**Xóa translation:**
```http
DELETE /api/settings/translations/{id}
```

**Bulk update translations:**
```http
POST /api/settings/translations/bulk-update
Content-Type: application/json

{
  "translations": [
    { "id": 1, "value": "New value 1" },
    { "id": 2, "value": "New value 2" }
  ]
}
```

**Sync translations giữa các ngôn ngữ:**
```http
POST /api/settings/translations/sync-languages
Content-Type: application/json

{
  "source_language_id": 1,
  "target_language_id": 2
}
```
*Tính năng này copy tất cả keys từ ngôn ngữ nguồn sang ngôn ngữ đích (hữu ích khi thêm ngôn ngữ mới)*

---

## 🎨 Frontend Implementation

### 1. Vue Composable: `useI18n()`

File: `resources/js/composables/useI18n.js`

**Sử dụng trong component:**

```vue
<script setup>
import { useI18n } from '../composables/useI18n';

const { t, currentLanguage, availableLanguages, changeLanguage } = useI18n();
</script>

<template>
  <div>
    <h1>{{ t('common.welcome') }}</h1>
    <p>{{ t('auth.login_title') }}</p>
    <button>{{ t('common.save') }}</button>
  </div>
</template>
```

**API của useI18n:**

```javascript
const {
  // State
  currentLanguage,        // Object: Ngôn ngữ hiện tại
  currentLanguageCode,    // String: Mã ngôn ngữ (en, vi)
  currentLanguageName,    // String: Tên ngôn ngữ (English, Tiếng Việt)
  translations,           // Object: Tất cả translations
  availableLanguages,     // Array: Danh sách ngôn ngữ
  isLoading,             // Boolean: Đang load
  isReady,               // Boolean: Đã load xong

  // Methods
  initI18n,              // Khởi tạo i18n
  loadLanguages,         // Load danh sách ngôn ngữ
  loadTranslations,      // Load translations cho 1 ngôn ngữ
  changeLanguage,        // Đổi ngôn ngữ
  t,                     // Lấy translation: t('common.welcome')
  tGroup,                // Lấy cả nhóm: tGroup('common')
  hasTranslation,        // Kiểm tra tồn tại: hasTranslation('common.welcome')
} = useI18n();
```

### 2. Language Switcher Component

File: `resources/js/components/LanguageSwitcher.vue`

Component này hiển thị dropdown cho phép user chọn ngôn ngữ.

**Đã được tích hợp vào:**
- `DashboardLayout.vue` (top navigation bar)

**Tự động:**
- Load danh sách ngôn ngữ
- Hiển thị ngôn ngữ hiện tại
- Lưu lựa chọn vào localStorage
- Reload page sau khi đổi ngôn ngữ

### 3. Settings Management UI (Super Admin)

#### Languages Management
**Route:** `/settings/languages`  
**Component:** `resources/js/pages/settings/LanguagesList.vue`

**Chức năng:**
- Xem danh sách tất cả ngôn ngữ
- Thêm/Sửa/Xóa ngôn ngữ
- Đặt ngôn ngữ mặc định
- Kích hoạt/Vô hiệu hóa ngôn ngữ
- Xem translations của từng ngôn ngữ

#### Translations Management
**Route:** `/settings/translations`  
**Component:** `resources/js/pages/settings/TranslationsList.vue`

**Chức năng:**
- Xem danh sách tất cả translations
- Filter theo ngôn ngữ, nhóm
- Search theo key hoặc value
- Thêm/Sửa/Xóa translation
- Pagination

---

## 📝 Cách Sử Dụng

### Cho Developer: Thêm Translation Mới

#### Bước 1: Thêm vào Seeder
File: `database/seeders/LanguageSeeder.php`

```php
private function createProductsTranslations(Language $en, Language $vi): void
{
    $translations = [
        'title' => ['Products', 'Sản phẩm'],
        'add_to_cart' => ['Add to Cart', 'Thêm vào giỏ'],
        'price' => ['Price', 'Giá'],
        'stock' => ['Stock', 'Tồn kho'],
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

Gọi trong `run()`:
```php
public function run(): void
{
    // ... existing code ...
    $this->createProductsTranslations($english, $vietnamese);
}
```

#### Bước 2: Chạy Seeder
```bash
php artisan db:seed --class=LanguageSeeder
```

#### Bước 3: Sử dụng trong Vue
```vue
<template>
  <div>
    <h1>{{ t('products.title') }}</h1>
    <button>{{ t('products.add_to_cart') }}</button>
  </div>
</template>

<script setup>
import { useI18n } from '../composables/useI18n';
const { t } = useI18n();
</script>
```

### Cho Super Admin: Quản lý qua UI

1. **Đăng nhập với tài khoản Super Admin:**
   - Email: `admin@example.com`
   - Password: `password`

2. **Truy cập Settings:**
   - Sidebar → System Settings → Languages
   - Sidebar → System Settings → Translations

3. **Thêm ngôn ngữ mới:**
   - Click "Add Language"
   - Điền thông tin (Name, Code, Flag, ...)
   - Save

4. **Thêm translations:**
   - Vào Translations page
   - Click "Add Translation"
   - Chọn Language, Group, Key, Value
   - Save

5. **Sync translations:**
   - Khi thêm ngôn ngữ mới, dùng "Sync Languages"
   - Chọn source language (ví dụ: English)
   - Chọn target language (ngôn ngữ mới)
   - Tất cả keys sẽ được copy sang

---

## 🔄 Flow Hoạt Động

### 1. Khởi động ứng dụng
```
1. User mở trang web
2. Vue app khởi động
3. initI18n() được gọi
4. Load danh sách languages từ API
5. Kiểm tra localStorage có ngôn ngữ đã lưu không
6. Load translations cho ngôn ngữ đó (hoặc default)
7. Cache translations vào localStorage
8. Render UI với translations
```

### 2. User đổi ngôn ngữ
```
1. User click vào Language Switcher
2. Chọn ngôn ngữ mới
3. changeLanguage(code) được gọi
4. Load translations mới từ API
5. Lưu vào localStorage
6. Reload page để apply translations mới
```

### 3. Super Admin thêm translation
```
1. Super Admin login
2. Vào Settings → Translations
3. Click "Add Translation"
4. Điền form và Save
5. API tạo record mới trong DB
6. Users sẽ thấy translation mới khi reload page
```

---

## 🎯 Best Practices

### 1. Đặt tên Keys
- Sử dụng snake_case: `welcome_message`, `login_button`
- Ngắn gọn, mô tả rõ ràng
- Nhóm theo chức năng

### 2. Tổ chức Groups
- `common`: Các từ dùng chung (save, cancel, delete, ...)
- `auth`: Liên quan đến authentication
- `users`: Quản lý users
- `products`: Quản lý products
- `orders`: Quản lý orders
- `settings`: Cài đặt
- ...

### 3. Sử dụng trong Code
```vue
<!-- ✅ GOOD -->
<h1>{{ t('users.title') }}</h1>
<button>{{ t('common.save') }}</button>

<!-- ❌ BAD -->
<h1>Users Management</h1>
<button>Save</button>
```

### 4. Fallback
Composable `t()` tự động fallback về key nếu không tìm thấy translation:
```javascript
t('missing.key') // Returns: 'missing.key'
t('missing.key', 'Default Value') // Returns: 'Default Value'
```

---

## 🚀 Mở Rộng

### Thêm ngôn ngữ mới (ví dụ: Tiếng Pháp)

#### Bước 1: Tạo Language qua UI hoặc API
```http
POST /api/settings/languages
{
  "name": "Français",
  "code": "fr",
  "flag": "🇫🇷",
  "direction": "ltr",
  "is_active": true,
  "sort_order": 3
}
```

#### Bước 2: Sync translations từ English
```http
POST /api/settings/translations/sync-languages
{
  "source_language_id": 1,  // English
  "target_language_id": 3   // Français
}
```

#### Bước 3: Dịch từng translation
- Vào Translations page
- Filter by Language: Français
- Edit từng translation để dịch sang tiếng Pháp

#### Bước 4: Kích hoạt
- Ngôn ngữ mới sẽ tự động xuất hiện trong Language Switcher
- Users có thể chọn và sử dụng

---

## 🧪 Testing

### Test API với cURL

**1. Lấy danh sách ngôn ngữ:**
```bash
curl http://127.0.0.1:8000/api/languages
```

**2. Lấy translations:**
```bash
curl http://127.0.0.1:8000/api/languages/en/translations
```

**3. Tạo language (cần token):**
```bash
curl -X POST http://127.0.0.1:8000/api/settings/languages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Français",
    "code": "fr",
    "flag": "🇫🇷",
    "direction": "ltr",
    "is_active": true
  }'
```

### Test trong Vue DevTools
1. Mở Vue DevTools
2. Tìm component sử dụng `useI18n`
3. Xem state: `currentLanguage`, `translations`
4. Gọi method: `changeLanguage('vi')`

---

## 📊 Dữ Liệu Mẫu

Hệ thống đã có sẵn translations cho:

### Groups:
- `common`: 30+ keys (welcome, home, save, cancel, ...)
- `auth`: 10+ keys (login, password, logout, ...)
- `dashboard`: 6+ keys (welcome_message, total_users, ...)
- `users`: 15+ keys (title, create, edit, delete, ...)
- `roles`: 10+ keys (title, permissions, assign, ...)
- `permissions`: 6+ keys (title, module, action, ...)
- `settings`: 25+ keys (languages, translations, ...)

### Languages:
- English (en) - Default
- Tiếng Việt (vi)

---

## 🔍 Troubleshooting

### Lỗi: Translations không hiển thị
**Giải pháp:**
1. Kiểm tra console: `console.log(translations.value)`
2. Kiểm tra localStorage: `app_translations`
3. Clear cache và reload: `localStorage.clear()`
4. Kiểm tra API response

### Lỗi: Language Switcher không hiển thị
**Giải pháp:**
1. Kiểm tra `availableLanguages` có data không
2. Kiểm tra API `/api/languages` có hoạt động không
3. Kiểm tra component đã import đúng chưa

### Lỗi: Super Admin không thấy Settings menu
**Giải pháp:**
1. Kiểm tra user có role `super-admin` không
2. Kiểm tra `authStore.hasRole('super-admin')`
3. Re-login để refresh permissions

---

## 📚 Tài Liệu Tham Khảo

- **Models:** `app/Models/Language.php`, `app/Models/Translation.php`
- **Controllers:** `app/Http/Controllers/Api/LanguageController.php`, `TranslationController.php`
- **Routes:** `routes/api.php`
- **Composable:** `resources/js/composables/useI18n.js`
- **Components:** `resources/js/components/LanguageSwitcher.vue`
- **Pages:** `resources/js/pages/settings/`

---

## ✅ Checklist Hoàn Thành

- [x] Database migrations (languages, translations)
- [x] Models với relationships
- [x] Seeders với dữ liệu EN & VI
- [x] API Controllers (Language, Translation)
- [x] API Routes (public & admin)
- [x] Vue Composable (useI18n)
- [x] Language Switcher component
- [x] Settings Management UI
- [x] Tích hợp vào DashboardLayout
- [x] Router guards cho super-admin
- [x] Documentation đầy đủ

---

🎉 **Hệ thống đa ngôn ngữ đã sẵn sàng sử dụng!**

