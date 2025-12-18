# 🔧 FIX: Translation Cache Issue

## ❌ Vấn Đề

Khi thêm translation mới vào database:
- **Không hiển thị trong danh sách quản lý translation**
- **Vẫn hiển thị key thay vì value** (VD: `common.reset` thay vì "Reset")

### Nguyên Nhân
Frontend cache translations trong `localStorage` để tăng performance. Khi thêm translation mới, frontend vẫn dùng cache cũ.

---

## ✅ Giải Pháp Đã Triển Khai

### 1. Nút "Refresh Translations" trong Language Switcher

**Location:** Top-right navbar → Click language icon

**Tính năng:**
- Clear localStorage cache
- Reload translations từ API
- Reload page để apply

**Cách dùng:**
```
1. Click vào icon ngôn ngữ (🇻🇳 hoặc 🇬🇧)
2. Click "Refresh Translations" (icon refresh)
3. Trang sẽ reload với translations mới
```

### 2. Auto-Reload Sau Khi Save Translation

**Tự động trigger khi:**
- Thêm translation mới
- Edit translation
- Trong TranslationModal hoặc TranslationEditModal

**Logic:**
```javascript
// Sau khi save thành công:
1. Clear localStorage cache
2. Reload translations từ API
3. Emit 'saved' event
4. Parent component reload danh sách
```

---

## 🚀 Cách Sử Dụng

### Scenario 1: Thêm Translation Mới

**Trước đây:**
```
1. Vào System Settings → Languages
2. Click "Translations" trên language card
3. Add translation: common.reset = "Reset"
4. ❌ Không thấy trong danh sách
5. ❌ Frontend vẫn hiển thị "common.reset"
```

**Bây giờ:**
```
1. Vào System Settings → Languages
2. Click "Translations" trên language card
3. Add translation: common.reset = "Reset"
4. ✅ Tự động reload translations
5. ✅ Thấy ngay trong danh sách
6. ✅ Frontend hiển thị "Reset"
```

### Scenario 2: Manual Refresh (Nếu Cần)

**Nếu vẫn chưa thấy translation:**
```
1. Click icon ngôn ngữ (top-right)
2. Click "Refresh Translations"
3. Trang reload với translations mới
```

### Scenario 3: Clear Cache Hoàn Toàn

**Nếu gặp vấn đề:**
```javascript
// Mở Console (F12) và chạy:
localStorage.clear();
location.reload();
```

---

## 📝 Files Đã Cập Nhật

### 1. `resources/js/components/LanguageSwitcher.vue`
```vue
<!-- Thêm nút Refresh Translations -->
<button @click="refreshTranslations">
  <svg>...</svg>
  Refresh Translations
</button>

<script>
const refreshTranslations = async () => {
  // Clear cache
  localStorage.removeItem('app_translations');
  
  // Reload from API
  await changeLanguage(currentLanguageCode.value);
  
  // Reload page
  window.location.reload();
};
</script>
```

### 2. `resources/js/components/settings/TranslationModal.vue`
```javascript
// Sau khi save thành công
if (response.data.success) {
  alert(response.data.message);
  
  // Clear cache & reload
  localStorage.removeItem('app_translations');
  await loadTranslations(currentLanguageCode.value);
  
  emit('saved');
}
```

### 3. `resources/js/components/settings/TranslationEditModal.vue`
```javascript
// Tương tự TranslationModal
// Auto-reload sau khi save
```

---

## 🧪 Test Scenarios

### Test 1: Thêm Translation Mới
```
1. Login: admin@example.com
2. System Settings → Languages → Translations
3. Add new: common.test = "Test Value"
4. ✅ Thấy ngay trong danh sách
5. Vào trang Customers
6. ✅ Nếu dùng t('common.test') → hiển thị "Test Value"
```

### Test 2: Edit Translation
```
1. Edit translation: common.reset = "Đặt Lại"
2. Save
3. ✅ Tự động reload
4. ✅ Thấy value mới
```

### Test 3: Manual Refresh
```
1. Thêm translation bằng SQL trực tiếp:
   INSERT INTO translations (language_id, group, key, value) 
   VALUES (2, 'common', 'manual_test', 'Manual Test');
2. Click language icon → Refresh Translations
3. ✅ Thấy translation mới
```

---

## 🔍 Debug: Kiểm Tra Cache

### Check localStorage
```javascript
// Mở Console (F12)

// 1. Xem translations hiện tại
console.log(JSON.parse(localStorage.getItem('app_translations')));

// 2. Xem ngôn ngữ hiện tại
console.log(localStorage.getItem('app_language'));

// 3. Clear cache
localStorage.removeItem('app_translations');
localStorage.removeItem('app_language');

// 4. Reload
location.reload();
```

### Check API Response
```javascript
// Mở Network tab (F12)
// Filter: "translations"
// Xem response của API call:
// GET /api/languages/vi/translations

// Response format:
{
  "success": true,
  "data": {
    "language": {...},
    "translations": {
      "common": {
        "reset": "Reset",
        "test": "Test Value"
      },
      "customers": {...}
    }
  }
}
```

---

## 💡 Best Practices

### Khi Thêm Translation Mới

**✅ DO:**
```
1. Dùng UI (System Settings → Languages → Translations)
2. Save → Tự động reload
3. Verify ngay trong danh sách
```

**❌ DON'T:**
```
1. Thêm trực tiếp vào database bằng SQL
   → Phải manual refresh
2. Quên reload sau khi thêm
   → Frontend vẫn dùng cache cũ
```

### Khi Develop

**Seeder:**
```php
// Sau khi chạy seeder
php artisan db:seed --class=CustomersTranslationsSeeder

// Frontend: Click Refresh Translations
// Hoặc: Clear cache và reload
```

**Testing:**
```
1. Thêm translation
2. Verify trong danh sách
3. Verify trong UI (dùng t('group.key'))
4. Test với cả EN và VI
```

---

## 🎯 Tóm Tắt

### Vấn Đề Gốc
- Frontend cache translations trong localStorage
- Không tự động reload khi có translation mới

### Giải Pháp
1. ✅ Nút "Refresh Translations" (manual)
2. ✅ Auto-reload sau save (automatic)
3. ✅ Clear cache + reload API

### Kết Quả
- ✅ Thêm translation → Thấy ngay
- ✅ Edit translation → Update ngay
- ✅ Manual refresh nếu cần
- ✅ Performance vẫn tốt (vẫn dùng cache)

---

## 📊 Flow Chart

```
User adds translation
        ↓
Save to database
        ↓
Clear localStorage cache
        ↓
Reload from API
        ↓
Update frontend state
        ↓
Emit 'saved' event
        ↓
Parent reload list
        ↓
✅ Translation visible!
```

---

**Build thành công! Reload browser và test ngay!** 🎉

