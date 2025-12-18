# 🌍 Hướng Dẫn Nhanh - Hệ Thống Đa Ngôn Ngữ

## 🚀 Khởi Động

### 1. Chạy Migrations & Seeders
```bash
php artisan migrate:fresh --seed
```

### 2. Build Frontend
```bash
npm run build
# hoặc dev mode:
npm run dev
```

### 3. Khởi động Server
```bash
php artisan serve
```

---

## 👤 Tài Khoản Test

### Super Admin (Có quyền quản lý Settings)
- **Email:** `admin@example.com`
- **Password:** `password`

---

## 🎯 Sử Dụng Cơ Bản

### Trong Vue Component

```vue
<script setup>
import { useI18n } from '../composables/useI18n';

const { t } = useI18n();
</script>

<template>
  <div>
    <h1>{{ t('common.welcome') }}</h1>
    <button>{{ t('common.save') }}</button>
    <p>{{ t('users.title') }}</p>
  </div>
</template>
```

### Các Translation Keys Có Sẵn

#### Common (common.*)
- `welcome`, `home`, `dashboard`, `settings`
- `save`, `cancel`, `delete`, `edit`, `create`, `view`
- `search`, `loading`, `no_data`
- `active`, `inactive`, `yes`, `no`

#### Auth (auth.*)
- `login_title`, `email`, `password`
- `login_button`, `logout`, `remember_me`
- `login_success`, `login_failed`

#### Users (users.*)
- `title`, `list`, `create`, `edit`, `delete`
- `name`, `email`, `roles`
- `create_success`, `update_success`, `delete_success`

#### Settings (settings.*)
- `title`, `languages`, `translations`
- `language_name`, `language_code`, `language_flag`
- `add_language`, `edit_language`, `delete_language`
- `manage_translations`

---

## 🔧 Quản Lý (Super Admin)

### Truy Cập Settings
1. Login với tài khoản Super Admin
2. Sidebar → **System Settings**
3. Chọn **Languages** hoặc **Translations**

### Thêm Ngôn Ngữ Mới
1. Vào **Settings → Languages**
2. Click **Add Language**
3. Điền thông tin:
   - Name: `Français`
   - Code: `fr`
   - Flag: `🇫🇷`
   - Direction: `ltr`
4. Click **Save**

### Thêm Translation
1. Vào **Settings → Translations**
2. Click **Add Translation**
3. Điền:
   - Language: Chọn ngôn ngữ
   - Group: `products` (ví dụ)
   - Key: `add_to_cart`
   - Value: `Add to Cart`
4. Click **Save**

### Sync Translations (Khi thêm ngôn ngữ mới)
1. Vào **Translations** page
2. Sử dụng API hoặc tạo UI button:
```bash
POST /api/settings/translations/sync-languages
{
  "source_language_id": 1,  # English
  "target_language_id": 3   # Ngôn ngữ mới
}
```

---

## 🌐 Language Switcher

### Cho Users
- Tự động hiển thị ở **top navigation bar**
- Click vào cờ/tên ngôn ngữ để đổi
- Tự động lưu lựa chọn
- Page reload để apply ngôn ngữ mới

### Vị Trí
- `DashboardLayout.vue` - Top right, bên cạnh User Menu

---

## 📝 Thêm Translation Mới (Developer)

### Option 1: Qua Seeder (Khuyến nghị)

**File:** `database/seeders/LanguageSeeder.php`

```php
private function createProductsTranslations(Language $en, Language $vi): void
{
    $translations = [
        'title' => ['Products', 'Sản phẩm'],
        'add_to_cart' => ['Add to Cart', 'Thêm vào giỏ'],
        'price' => ['Price', 'Giá'],
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

public function run(): void
{
    // ... existing code ...
    $this->createProductsTranslations($english, $vietnamese);
}
```

**Chạy:**
```bash
php artisan db:seed --class=LanguageSeeder
```

### Option 2: Qua API

```bash
# English
curl -X POST http://127.0.0.1:8000/api/settings/translations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "language_id": 1,
    "group": "products",
    "key": "add_to_cart",
    "value": "Add to Cart"
  }'

# Vietnamese
curl -X POST http://127.0.0.1:8000/api/settings/translations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "language_id": 2,
    "group": "products",
    "key": "add_to_cart",
    "value": "Thêm vào giỏ"
  }'
```

### Option 3: Qua UI (Super Admin)
1. Login → Settings → Translations
2. Add Translation cho từng ngôn ngữ

---

## 🔍 API Endpoints

### Public (Không cần auth)
```
GET  /api/languages                        # Danh sách ngôn ngữ active
GET  /api/languages/{code}/translations    # Tất cả translations của 1 ngôn ngữ
```

### Super Admin Only
```
# Languages
GET    /api/settings/languages              # Tất cả languages
POST   /api/settings/languages              # Tạo mới
PUT    /api/settings/languages/{id}         # Cập nhật
DELETE /api/settings/languages/{id}         # Xóa
POST   /api/settings/languages/{id}/set-default  # Đặt mặc định

# Translations
GET    /api/settings/translations           # Danh sách (có filter, search)
POST   /api/settings/translations           # Tạo mới
PUT    /api/settings/translations/{id}      # Cập nhật
DELETE /api/settings/translations/{id}      # Xóa
POST   /api/settings/translations/bulk-update    # Cập nhật nhiều
POST   /api/settings/translations/sync-languages # Sync giữa ngôn ngữ
```

---

## 🎨 Components & Files

### Backend
- **Models:** `app/Models/Language.php`, `Translation.php`
- **Controllers:** `app/Http/Controllers/Api/LanguageController.php`, `TranslationController.php`
- **Routes:** `routes/api.php`
- **Migrations:** `database/migrations/*_create_languages_table.php`, `*_create_translations_table.php`
- **Seeder:** `database/seeders/LanguageSeeder.php`

### Frontend
- **Composable:** `resources/js/composables/useI18n.js`
- **Component:** `resources/js/components/LanguageSwitcher.vue`
- **Pages:** `resources/js/pages/settings/LanguagesList.vue`, `TranslationsList.vue`
- **Modals:** `resources/js/components/settings/LanguageModal.vue`, `TranslationModal.vue`

---

## 🐛 Troubleshooting

### Translations không hiển thị?
```bash
# 1. Clear cache
localStorage.clear()

# 2. Reload page
Ctrl + Shift + R

# 3. Kiểm tra console
console.log(translations.value)

# 4. Kiểm tra API
curl http://127.0.0.1:8000/api/languages/en/translations
```

### Language Switcher không xuất hiện?
```bash
# 1. Kiểm tra languages có data
GET /api/languages

# 2. Kiểm tra component import
# Trong DashboardLayout.vue:
import LanguageSwitcher from '../components/LanguageSwitcher.vue';

# 3. Rebuild
npm run build
```

### Super Admin không thấy Settings menu?
```bash
# 1. Kiểm tra role
# Login với admin@example.com

# 2. Kiểm tra database
SELECT * FROM role_user WHERE user_id = 1;
SELECT * FROM roles WHERE name = 'super-admin';

# 3. Re-seed nếu cần
php artisan migrate:fresh --seed
```

---

## ✅ Checklist

- [ ] Migrations đã chạy
- [ ] Seeders đã chạy (có data EN & VI)
- [ ] Frontend đã build
- [ ] Server đang chạy
- [ ] Login được với Super Admin
- [ ] Thấy Settings menu trong sidebar
- [ ] Language Switcher hiển thị ở top bar
- [ ] Có thể đổi ngôn ngữ
- [ ] Translations hiển thị đúng

---

## 📚 Tài Liệu Đầy Đủ

Xem file `I18N_SYSTEM_GUIDE.md` để biết chi tiết đầy đủ về:
- Cấu trúc database
- API documentation
- Best practices
- Advanced usage
- Testing

---

🎉 **Chúc bạn sử dụng hệ thống đa ngôn ngữ thành công!**

