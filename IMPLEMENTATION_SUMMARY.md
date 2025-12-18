# Hướng Dẫn Triển Khai: Customer View All Permission & Zalo Unread Count

## 📋 Tổng Quan
Triển khai permission `customers.view_all` để cho phép user xem tất cả khách hàng (không giới hạn `assigned_to`) và fix lỗi hiển thị tổng tin nhắn Zalo chưa đọc của customers trong DashboardLayout.

## 🎯 Mục Tiêu
1. ✅ Thêm permission `customers.view_all` vào database
2. ✅ Cập nhật Customer Model để áp dụng permission
3. ✅ Tạo API endpoint mới để lấy tổng unread count của customers
4. ✅ Fix DashboardLayout để gọi đúng endpoint

## 📝 Các Bước Thực Hiện

### Bước 1: Chạy Seeder để Tạo Permission Mới
```bash
php artisan db:seed --class=CustomersViewAllPermissionSeeder
```

**Kết quả:** Tạo permission `customers.view_all` và gán cho roles:
- ✅ Super Admin (có sẵn)
- ✅ Admin
- ⚠️ Manager (comment - bật nếu muốn manager cũng xem tất cả)

---

### Bước 2: Cập Nhật Customer Model
**File:** `app/Models/Customer.php`
**Line:** Khoảng 238-263 (method `scopeAccessibleBy`)

**Thay đổi:** Thêm 5 dòng code sau vào giữa method (sau check super admin):

```php
// Check if user has 'customers.view_all' permission
if ($user->hasPermission('customers.view_all')) {
    // User can see all customers, no filter needed
    return $query;
}
```

📄 **Xem chi tiết:** [CUSTOMER_MODEL_UPDATE.php](./CUSTOMER_MODEL_UPDATE.php)

---

### Bước 3: Thêm Method Mới vào ZaloController
**File:** `app/Http/Controllers/Api/ZaloController.php`
**Vị trí:** Sau method `getCustomerUnreadCounts()` (khoảng line 2966)

**Copy toàn bộ method:** `getCustomerUnreadTotal()` từ file:
📄 **Xem chi tiết:** [ZALO_CONTROLLER_ADD_METHOD.php](./ZALO_CONTROLLER_ADD_METHOD.php)

---

### Bước 4: Thêm Route Mới
**File:** `routes/api.php`
**Vị trí:** Trong `Route::prefix('zalo')->middleware(['auth:sanctum', 'branch.access'])` group

**Thêm dòng sau:** (sau route `/customer-unread-counts`)
```php
Route::get('/customers/unread-total', [\App\Http\Controllers\Api\ZaloController::class, 'getCustomerUnreadTotal'])->middleware('permission:zalo.view');
```

📄 **Xem chi tiết:** [API_ROUTES_UPDATE.php](./API_ROUTES_UPDATE.php)

---

### Bước 5: Fix DashboardLayout.vue
**File:** `resources/js/layouts/DashboardLayout.vue`
**Line:** Khoảng 524 (trong method `fetchCustomerZaloUnreadCount`)

**Thay đổi 1 dòng:**
```javascript
// TỪ:
const response = await api.get('/api/zalo/customer-unread-counts', {

// THÀNH:
const response = await api.get('/api/zalo/customers/unread-total', {
```

📄 **Xem chi tiết:** [DASHBOARDLAYOUT_FIX.js](./DASHBOARDLAYOUT_FIX.js)

---

### Bước 6: Build Frontend (Nếu cần)
```bash
npm run build
```

---

## 🧪 Kiểm Tra & Test

### 1. Test Permission
```sql
-- Kiểm tra permission đã được tạo chưa
SELECT * FROM permissions WHERE name = 'customers.view_all';

-- Kiểm tra role nào có permission này
SELECT r.name, p.name
FROM roles r
JOIN permission_role pr ON r.id = pr.role_id
JOIN permissions p ON pr.permission_id = p.id
WHERE p.name = 'customers.view_all';
```

### 2. Test API Endpoint
```bash
# Gọi API endpoint mới (cần có access token)
curl -X GET "https://admin.songthuy.edu.vn/api/zalo/customers/unread-total?branch_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "total_unread": 14
  }
}
```

### 3. Test Frontend
1. Login vào hệ thống
2. Mở Console (F12)
3. Kiểm tra logs:
   - ✅ `🔄 [DashboardLayout] Fetching Customer Zalo unread count...`
   - ✅ `📥 [DashboardLayout] Customer Zalo unread response: Object`
   - ✅ `📊 [DashboardLayout] Customer Zalo unread count set to: 14`
4. Kiểm tra badge trên icon "Sales" (Customers) ở sidebar có hiển thị số đúng không

### 4. Test Phân Quyền
**Test Case 1: User không có `customers.view_all`**
- Chỉ nhìn thấy customers assigned cho mình
- Badge chỉ đếm unread của customers mình được assign

**Test Case 2: User có `customers.view_all` (Admin)**
- Nhìn thấy tất cả customers trong hệ thống
- Badge đếm tất cả unread của toàn bộ customers

**Test Case 3: Super Admin**
- Nhìn thấy tất cả customers
- Badge đếm tất cả unread

---

## 🔍 Cách Hoạt Động

### Flow Diagram:
```
User Login
    ↓
DashboardLayout mounted
    ↓
startZaloUnreadPolling() [nếu có zalo.view]
    ↓
fetchCustomerZaloUnreadCount() [nếu có customers.view]
    ↓
GET /api/zalo/customers/unread-total
    ↓
ZaloController@getCustomerUnreadTotal
    ↓
Customer::accessibleBy($user) ← Check permission
    ↓
    ├─ Super Admin? → Tất cả customers
    ├─ Has customers.view_all? → Tất cả customers
    └─ Regular user → Chỉ assigned customers
    ↓
Tìm Zalo friends matching customer phones
    ↓
Đếm total unread messages
    ↓
Return { total_unread: 14 }
    ↓
Update badge trên sidebar
```

---

## 📊 Cấu Trúc Permission

```
customers.view          - Xem customers (với giới hạn theo assigned_to)
customers.view_all      - Xem TẤT CẢ customers (không giới hạn) ← MỚI
customers.create        - Tạo customer mới
customers.edit          - Sửa customer
customers.delete        - Xóa customer
customers.settings      - Quản lý settings của customers
```

---

## ⚠️ Lưu Ý Quan Trọng

1. **Không deploy lỗi permission:**
   - Chạy seeder trước khi deploy code
   - Test kỹ trên local trước

2. **Cache issues:**
   - Sau khi cập nhật permissions, có thể cần:
     ```bash
     php artisan cache:clear
     php artisan config:clear
     ```

3. **Frontend build:**
   - Nếu có thay đổi Vue file, phải run `npm run build`
   - Nếu deploy build assets riêng, nhớ upload file build mới

4. **Database seeder:**
   - Seeder an toàn - sử dụng `firstOrCreate()` nên không duplicate
   - Có thể chạy nhiều lần không sợ lỗi

---

## 🐛 Troubleshooting

### Lỗi: API trả về HTML thay vì JSON
**Nguyên nhân:** Route chưa được add hoặc cache route cũ
**Giải pháp:**
```bash
php artisan route:clear
php artisan config:clear
```

### Lỗi: Permission không hoạt động
**Nguyên nhân:** User chưa được gán permission
**Giải pháp:**
```sql
-- Gán permission cho user cụ thể
INSERT INTO permission_user (permission_id, user_id)
SELECT id, YOUR_USER_ID
FROM permissions
WHERE name = 'customers.view_all';
```

### Lỗi: Badge không cập nhật
**Nguyên nhân:** Frontend chưa build lại
**Giải pháp:**
```bash
npm run build
```

---

## ✅ Checklist Triển Khai

- [ ] Đã chạy seeder: `CustomersViewAllPermissionSeeder`
- [ ] Đã cập nhật `Customer.php` - thêm check permission
- [ ] Đã thêm method `getCustomerUnreadTotal()` vào `ZaloController.php`
- [ ] Đã thêm route mới vào `api.php`
- [ ] Đã sửa `DashboardLayout.vue` - đổi endpoint
- [ ] Đã build frontend: `npm run build`
- [ ] Đã test API endpoint trả về đúng JSON
- [ ] Đã test badge hiển thị đúng số unread
- [ ] Đã test phân quyền: Admin vs Regular user
- [ ] Đã clear cache nếu cần

---

## 📞 Hỗ Trợ
Nếu gặp vấn đề, kiểm tra logs:
- Laravel: `storage/logs/laravel.log`
- Browser Console: F12 → Console tab
- Network: F12 → Network tab → filter "customers"

---

**Ngày tạo:** 2025-11-23
**Tác giả:** Claude AI Assistant
**Version:** 1.0
