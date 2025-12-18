# 🧪 Test Hệ Thống Đa Ngôn Ngữ

## 🚀 Bước 1: Khởi động lại

```bash
# Dừng server nếu đang chạy (Ctrl+C)

# Khởi động lại
php artisan serve
```

## 🔍 Bước 2: Test trong Browser

### 1. Mở trình duyệt
```
http://127.0.0.1:8000
```

### 2. Mở Console (F12)
Bạn sẽ thấy các logs:
```
🌍 Initializing i18n...
✅ Languages loaded: 2
✅ i18n initialized with language: vi
📊 Total translation groups: 7
Vue app mounted successfully
```

### 3. Kiểm tra translations
Trong Console, gõ:
```javascript
// Kiểm tra translations đã load
console.log(localStorage.getItem('app_translations'))

// Kiểm tra ngôn ngữ hiện tại
console.log(localStorage.getItem('app_language'))
```

### 4. Đăng nhập
- Email: `admin@example.com`
- Password: `password`

### 5. Kiểm tra hiển thị

**Nếu thấy:**
- ✅ "Chào mừng trở lại" → **HOẠT ĐỘNG!**
- ✅ "Tổng người dùng" → **HOẠT ĐỘNG!**
- ✅ "Quản lý người dùng" → **HOẠT ĐỘNG!**

**Nếu thấy:**
- ❌ "dashboard.welcome_message" → Translations chưa load
- ❌ "users.create" → Translations chưa load

## 🔧 Nếu Chưa Hoạt Động

### Fix 1: Clear Cache
```javascript
// Trong Console (F12)
localStorage.clear()
location.reload()
```

### Fix 2: Kiểm tra API
Mở tab Network trong DevTools (F12), reload page, tìm:
- `GET /api/languages` → Phải có response
- `GET /api/languages/vi/translations` → Phải có response với data

### Fix 3: Test API trực tiếp
Mở tab mới:
```
http://127.0.0.1:8000/api/languages
```
Phải thấy JSON với 2 ngôn ngữ (English, Tiếng Việt)

```
http://127.0.0.1:8000/api/languages/vi/translations
```
Phải thấy JSON với tất cả translations tiếng Việt

### Fix 4: Kiểm tra Database
```bash
php artisan tinker
```

Trong tinker:
```php
// Kiểm tra languages
\App\Models\Language::all();

// Kiểm tra translations
\App\Models\Translation::where('language_id', 2)->count();

// Xem một vài translations
\App\Models\Translation::where('language_id', 2)
    ->where('group', 'common')
    ->get(['key', 'value']);
```

### Fix 5: Re-seed Database
```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

## 📊 Expected Results

### Console Logs
```
🌍 Initializing i18n...
✅ Languages loaded: 2
📦 Using cached translations for: vi (hoặc)
✅ i18n initialized with language: vi
📊 Total translation groups: 7
i18n initialized
Vue app mounted successfully
```

### LocalStorage
```javascript
localStorage.getItem('app_language')
// "vi"

localStorage.getItem('app_translations')
// {"common":{"welcome":"Chào mừng",...},"auth":{...},...}
```

### Dashboard Page
- Header: "Chào mừng trở lại, Super Admin! 👋"
- Stats: "Tổng người dùng", "Tổng vai trò", "Tổng quyền"
- Section: "Vai trò của bạn", "Thao tác nhanh"

### Users Page
- Header: "Quản lý người dùng"
- Subtitle: "Danh sách người dùng"
- Button: "Tạo người dùng"

## 🎯 Đổi Ngôn Ngữ

1. Click vào **🇻🇳 Tiếng Việt** (top right)
2. Chọn **🇬🇧 English**
3. Page reload
4. Tất cả text chuyển sang tiếng Anh

## ✅ Success Checklist

- [ ] Console hiển thị logs đúng
- [ ] localStorage có `app_language` và `app_translations`
- [ ] Dashboard hiển thị tiếng Việt
- [ ] Users page hiển thị tiếng Việt
- [ ] Language Switcher hoạt động
- [ ] Đổi sang English thành công
- [ ] Đổi lại Vietnamese thành công

## 🐛 Common Issues

### Issue: "users.create" thay vì "Tạo người dùng"
**Nguyên nhân:** Translations chưa load

**Fix:**
```javascript
localStorage.clear()
location.reload()
```

### Issue: Language Switcher không hiển thị
**Nguyên nhân:** API không hoạt động

**Fix:**
```bash
# Kiểm tra server đang chạy
php artisan serve

# Test API
curl http://127.0.0.1:8000/api/languages
```

### Issue: Console error "Failed to load translations"
**Nguyên nhân:** API endpoint không đúng hoặc CORS

**Fix:** Kiểm tra `routes/api.php` có routes đúng không

---

## 📞 Cần Giúp Đỡ?

Nếu vẫn không hoạt động, gửi cho tôi:
1. Screenshot Console (F12)
2. Screenshot Network tab (F12)
3. Output của: `php artisan tinker --execute="echo \App\Models\Translation::count();"`

---

🎉 **Chúc bạn test thành công!**

