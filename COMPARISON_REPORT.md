# 📊 Báo Cáo So Sánh: Local vs VPS Production

**Ngày:** 2025-11-23
**Local:** c:/xampp/htdocs/school
**VPS:** root@103.121.90.143:/var/www/school

---

## 🔍 Kết Quả So Sánh

### ✅ GIỐNG NHAU (Cần nâng cấp trên CẢ 2)

| Component | Tình trạng hiện tại | Vấn đề |
|-----------|---------------------|---------|
| **Customer.php** | ✅ Giống nhau | ❌ Chưa có check permission `customers.view_all` |
| **ZaloController.php** | ✅ Giống nhau | ❌ Chưa có method `getCustomerUnreadTotal()` |
| **routes/api.php** | ✅ Giống nhau | ❌ Chưa có route GET `/customers/unread-total` |
| **DashboardLayout.vue** | ✅ Giống nhau | ❌ Đang gọi sai endpoint (GET → endpoint POST) |
| **Permissions** | ✅ Giống nhau | ❌ Chưa có permission `customers.view_all` |

### 📋 Chi Tiết Từng File

#### 1. **Customer Model** (`app/Models/Customer.php`)
```diff
VPS:  ✅ Giống LOCAL
Local: ✅ Giống VPS

Scope accessibleBy():
- ✅ Có check super admin
- ❌ CHƯA CÓ check permission 'customers.view_all'
- ✅ Có filter by assigned_to

⚠️ CẦN THÊM: Check permission 'customers.view_all'
```

#### 2. **ZaloController** (`app/Http/Controllers/Api/ZaloController.php`)
```diff
VPS Methods:
- ✅ getCustomerUnreadCounts(Request $request) - Line 2896
- ❌ CHƯA CÓ getCustomerUnreadTotal()

Local Methods:
- ✅ getCustomerUnreadCounts(Request $request)
- ❌ CHƯA CÓ getCustomerUnreadTotal()

⚠️ CẦN THÊM: Method getCustomerUnreadTotal() để tính tổng unread
```

#### 3. **API Routes** (`routes/api.php`)
```diff
VPS:
POST /api/zalo/customer-unread-counts ✅ Có
GET  /api/zalo/customers/unread-total  ❌ CHƯA CÓ

Local:
POST /api/zalo/customer-unread-counts ✅ Có
GET  /api/zalo/customers/unread-total  ❌ CHƯA CÓ

⚠️ CẦN THÊM: Route mới cho GET /customers/unread-total
```

#### 4. **DashboardLayout.vue** (`resources/js/layouts/DashboardLayout.vue`)
```javascript
// VPS & Local - ĐỀU SAI:
const response = await api.get('/api/zalo/customer-unread-counts', { // ❌
  params: { branch_id: branchId }
});

// Endpoint này yêu cầu POST method, không phải GET!
// ⚠️ CẦN SỬA: Đổi sang endpoint mới
```

#### 5. **Database Permissions**
```sql
-- VPS Database (school_db):
customers.settings  ✅
customers.view      ✅
customers.create    ✅
customers.edit      ✅
customers.delete    ✅
customers.view_all  ❌ CHƯA CÓ

-- ⚠️ CẦN THÊM: Permission 'customers.view_all'
```

#### 6. **Seeders**
```diff
VPS Seeders:
- ✅ CustomerSeeder.php
- ✅ CustomerSettingsPermissionSeeder.php
- ✅ CustomerSettingsSeeder.php
- ✅ CustomerChildrenTranslationsSeeder.php
- ✅ CustomerInteractionTranslationsSeeder.php
- ✅ CustomerSettingsTranslationsSeeder.php
- ✅ CustomersTranslationsSeeder.php
- ❌ CHƯA CÓ CustomersViewAllPermissionSeeder.php

⚠️ CẦN UPLOAD: CustomersViewAllPermissionSeeder.php
```

---

## 🎯 Kết Luận

### Tình Trạng Hiện Tại
- **LOCAL & VPS**: ✅ **HOÀN TOÀN GIỐNG NHAU**
- **Vấn đề**: ❌ **CẢ HAI ĐỀU BỊ LỖI GIỐNG NHAU**

### Nguyên Nhân Lỗi
1. DashboardLayout gọi **GET** `/api/zalo/customer-unread-counts`
2. Nhưng route này chỉ hỗ trợ **POST** method
3. → API trả về **HTML error page** thay vì JSON
4. → Console log: `📥 [DashboardLayout] Customer Zalo unread response: <!DOCTYPE html>`

---

## 📝 Phương Án Nâng Cấp

### ✅ KHUYẾN NGHỊ: Nâng cấp ĐỒNG BỘ cho cả Local & VPS

**Lý do:**
- Cả 2 đều bị cùng 1 lỗi
- Code base giống hệt nhau
- Dễ maintain và debug
- Đảm bảo consistency

### 🚀 Deployment Strategy

#### **Option 1: Nâng cấp LOCAL trước, test, rồi deploy lên VPS** ⭐ KHUYẾN NGHỊ
```
1. Fix LOCAL
2. Test kỹ trên LOCAL
3. Deploy lên VPS (sử dụng script tự động)
```

**Ưu điểm:**
- ✅ An toàn - test kỹ trước khi lên production
- ✅ Rollback dễ dàng nếu có vấn đề
- ✅ Không ảnh hưởng users trong quá trình test

**Nhược điểm:**
- ⚠️ VPS vẫn bị lỗi cho đến khi deploy xong

---

#### **Option 2: Nâng cấp ĐỒNG THỜI cả Local & VPS**
```
1. Fix cả 2 cùng lúc
2. Test parallel
```

**Ưu điểm:**
- ✅ Nhanh - fix lỗi VPS ngay lập tức

**Nhược điểm:**
- ❌ Rủi ro cao - chưa test kỹ
- ❌ Nếu có bug → ảnh hưởng production
- ❌ Khó rollback

---

## 📦 Files Cần Deploy Lên VPS

### Backend Files (PHP)
```
1. database/seeders/CustomersViewAllPermissionSeeder.php  [NEW]
2. app/Models/Customer.php                                [MODIFIED]
3. app/Http/Controllers/Api/ZaloController.php            [MODIFIED]
4. routes/api.php                                         [MODIFIED]
```

### Frontend Files (Vue/JS)
```
5. resources/js/layouts/DashboardLayout.vue               [MODIFIED]
```

### Build Assets (sau khi npm run build)
```
6. public/build/assets/*  [GENERATED]
```

---

## ⚙️ Quy Trình Deploy Lên VPS

### Bước 1: Backup VPS
```bash
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143 << 'EOF'
cd /var/www/school
# Backup database
mysqldump -u root -p'Kh0ngbiet@' school_db > backup_$(date +%Y%m%d_%H%M%S).sql
# Backup code
tar -czf backup_code_$(date +%Y%m%d_%H%M%S).tar.gz app routes resources database
EOF
```

### Bước 2: Upload Files
```bash
# Upload seeder
scp -i ~/.ssh/vps_key -P 26266 \
  database/seeders/CustomersViewAllPermissionSeeder.php \
  root@103.121.90.143:/var/www/school/database/seeders/

# Upload modified files (sẽ có script tự động)
```

### Bước 3: Chạy Migration/Seeder
```bash
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143 << 'EOF'
cd /var/www/school
php artisan db:seed --class=CustomersViewAllPermissionSeeder
php artisan config:clear
php artisan route:clear
php artisan cache:clear
EOF
```

### Bước 4: Build Frontend
```bash
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143 << 'EOF'
cd /var/www/school
npm run build
EOF
```

### Bước 5: Verify
```bash
# Test API endpoint
curl -X GET "https://admin.songthuy.edu.vn/api/zalo/customers/unread-total" \
  -H "Authorization: Bearer TOKEN"

# Kiểm tra permissions
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143 \
  "mysql -u root -p'Kh0ngbiet@' school_db -e 'SELECT * FROM permissions WHERE name=\"customers.view_all\"'"
```

---

## 🔧 Deployment Mode

### Không có Git trên VPS
- VPS **KHÔNG** sử dụng Git
- Deploy bằng cách **upload files trực tiếp** qua SCP
- Cần script automation để đảm bảo không miss files

---

## ⏱️ Timeline Dự Kiến

### Nâng cấp LOCAL (Development)
- ⏱️ 15-20 phút
- ✅ Đã có sẵn code & hướng dẫn

### Test trên LOCAL
- ⏱️ 10-15 phút
- Kiểm tra tất cả chức năng

### Deploy lên VPS
- ⏱️ 10 phút (với script tự động)
- ⏱️ 20-30 phút (manual)

### **TỔNG:** ~35-65 phút

---

## 🎯 Next Steps - KHUYẾN NGHỊ

### 1️⃣ **Nâng cấp LOCAL ngay** ✅
   - Thực hiện theo [IMPLEMENTATION_SUMMARY.md](./IMPLEMENTATION_SUMMARY.md)
   - Test kỹ trên local

### 2️⃣ **Tạo deployment script** 🤖
   - Script tự động upload files lên VPS
   - Chạy migration/seeder
   - Build frontend
   - Verify deployment

### 3️⃣ **Deploy lên VPS** 🚀
   - Sử dụng script tự động
   - Monitor logs
   - Verify badge hiển thị đúng

---

## 🛡️ Rollback Plan

### Nếu có vấn đề trên VPS:
```bash
# Restore database
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143 << 'EOF'
cd /var/www/school
mysql -u root -p'Kh0ngbiet@' school_db < backup_YYYYMMDD_HHMMSS.sql
EOF

# Restore code
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143 << 'EOF'
cd /var/www/school
tar -xzf backup_code_YYYYMMDD_HHMMSS.tar.gz
php artisan config:clear
php artisan route:clear
npm run build
EOF
```

---

**Prepared by:** Claude AI Assistant
**Date:** 2025-11-23
**Status:** Ready for deployment
