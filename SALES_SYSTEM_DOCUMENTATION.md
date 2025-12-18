# 🛒 HỆ THỐNG BÁN HÀNG & QUẢN LÝ TÀI CHÍNH

## 📋 Tổng Quan

Hệ thống bán hàng hoàn chỉnh tích hợp với module Customer, bao gồm:
- **Products** - Quản lý sản phẩm/khóa học
- **Vouchers** - Mã giảm giá  
- **Campaigns** - Chiến dịch khuyến mãi
- **Enrollments** - Đơn đăng ký khóa học (Chốt đơn)
- **Wallets** - Ví tiền cho Customer & Children
- **Tích hợp Accounting** - Tự động tạo transaction

---

## 🗄️ Database Schema

### 1. Products Table
```sql
- id, code (PRD00001), name, slug
- description, type (course/package/material/service)
- price, sale_price
- duration_months, total_sessions, price_per_session
- category, level, target_ages (JSON)
- is_active, is_featured, allow_trial
- image, gallery (JSON)
- metadata (JSON)
- created_by, updated_by
- timestamps, soft_deletes
```

### 2. Vouchers Table
```sql
- id, code (WELCOME2025), name, description
- type (percentage/fixed_amount), value
- max_discount_amount, min_order_amount
- usage_limit, usage_per_customer, usage_count
- start_date, end_date
- applicable_product_ids (JSON)
- applicable_categories (JSON)
- applicable_customer_ids (JSON)
- is_active, is_auto_apply
- timestamps, soft_deletes
```

### 3. Campaigns Table
```sql
- id, code, name, description
- discount_type, discount_value
- max_discount_amount, min_order_amount
- start_date, end_date
- applicable_product_ids, applicable_categories
- target_customer_segments (JSON)
- priority (số càng cao càng ưu tiên)
- total_usage_limit, total_usage_count
- is_active, is_auto_apply
- banner_image, banner_url
- timestamps, soft_deletes
```

### 4. Wallets Table (Polymorphic)
```sql
- id, owner_id, owner_type (Customer/CustomerChild)
- code (WAL000001)
- balance, total_deposited, total_spent
- branch_id, currency (default: VND)
- is_active, is_locked, lock_reason
- metadata (JSON)
- timestamps
```

### 5. Wallet Transactions Table
```sql
- id, wallet_id, transaction_code (WTX20251106001)
- type (deposit/withdraw/refund)
- amount, balance_before, balance_after
- transactionable_id, transactionable_type (Enrollment...)
- description, status, completed_at
- created_by, metadata
- timestamps
```

### 6. Enrollments Table (Đơn đăng ký)
```sql
- id, code (ENR20251106001)
- customer_id
- student_id, student_type (Polymorphic: Customer/CustomerChild)
- product_id
- original_price, discount_amount, final_price
- paid_amount, remaining_amount
- voucher_id, campaign_id, voucher_code
- total_sessions, attended_sessions, remaining_sessions
- price_per_session
- start_date, end_date, completed_at
- status (pending/paid/active/completed/cancelled/refunded)
- branch_id, assigned_to
- notes, cancellation_reason, metadata
- timestamps, soft_deletes
```

### 7. Voucher Usage Table (Tracking)
```sql
- id, voucher_id, customer_id, enrollment_id
- discount_amount
- timestamps
```

---

## 🔗 Relationships

### Customer Model
```php
- wallet() → morphOne(Wallet)
- enrollments() → hasMany(Enrollment) // Đơn của customer
- studentEnrollments() → morphMany(Enrollment, 'student') // Customer là học viên
```

### CustomerChild Model
```php
- wallet() → morphOne(Wallet)
- enrollments() → morphMany(Enrollment, 'student') // Child là học viên
```

### Product Model
```php
- enrollments() → hasMany(Enrollment)
- creator(), updater() → belongsTo(User)
```

### Enrollment Model
```php
- customer() → belongsTo(Customer)
- student() → morphTo() // Customer hoặc CustomerChild
- product() → belongsTo(Product)
- voucher() → belongsTo(Voucher)
- campaign() → belongsTo(Campaign)
- branch() → belongsTo(Branch)
- walletTransactions() → morphMany(WalletTransaction)
```

### Wallet Model
```php
- owner() → morphTo() // Customer hoặc CustomerChild
- branch() → belongsTo(Branch)
- transactions() → hasMany(WalletTransaction)
```

---

## 🎯 Workflow Chốt Đơn & Thanh Toán

### Bước 1: Chọn Khách Hàng & Người Học
```
Customer A (Phụ huynh)
├─ Học cho chính mình → student_type = Customer
└─ Học cho con:
   ├─ Con 1 (5 tuổi) → student_type = CustomerChild
   └─ Con 2 (8 tuổi) → student_type = CustomerChild
```

### Bước 2: Chọn Sản Phẩm
```
- Browse danh sách sản phẩm
- Xem giá hiện tại (sale_price nếu có, không thì price)
- Kiểm tra phù hợp với độ tuổi (target_ages)
```

### Bước 3: Áp Dụng Giảm Giá (Tự động ưu tiên)

**Thứ tự ưu tiên:**
1. **Voucher Code** (nếu khách nhập)
   - Kiểm tra `canBeUsedBy(customer)`
   - Kiểm tra `canBeAppliedToProduct(product)`
   - Tính discount
2. **Auto-apply Campaign** (nếu không có voucher hoặc campaign tốt hơn)
   - Lấy tất cả campaigns đang hiệu lực
   - Sắp xếp theo `priority` (cao → thấp)
   - Chọn campaign có discount lớn nhất

**Ví dụ:**
```
Original Price: 3,000,000đ

Voucher WELCOME2025: -15% = -450,000đ → Final: 2,550,000đ
Campaign EARLYBIRD: -20% = -600,000đ → Final: 2,400,000đ

→ Auto chọn Campaign vì giảm nhiều hơn
```

### Bước 4: Tạo Enrollment
```php
POST /api/enrollments
{
  "customer_id": 1,
  "student_type": "App\\Models\\CustomerChild",
  "student_id": 3,
  "product_id": 5,
  "voucher_code": "WELCOME2025", // Optional
  "notes": "Ghi chú..."
}

Response:
{
  "success": true,
  "data": {
    "code": "ENR20251106001",
    "original_price": 3000000,
    "discount_amount": 600000,
    "final_price": 2400000,
    "remaining_amount": 2400000,
    "status": "pending",
    "campaign": {...}
  }
}
```

### Bước 5: Xác Nhận Thanh Toán
```php
POST /api/enrollments/{id}/confirm-payment
{
  "payment_method": "bank_transfer",
  "amount": 2400000,
  "notes": "Chuyển khoản..."
}

Hệ thống tự động:
1. Tìm hoặc tạo Wallet cho student (Customer/Child)
2. Nạp tiền vào Wallet → Tạo WalletTransaction (deposit)
3. Cập nhật Enrollment:
   - paid_amount += amount
   - remaining_amount = final_price - paid_amount
   - status = "paid" (nếu đủ tiền)
4. Activate enrollment nếu paid đủ
```

### Bước 6: Trừ Tiền Sau Mỗi Buổi Học
```php
// Sau khi điểm danh buổi học
$enrollment->attended_sessions++;
$enrollment->remaining_sessions--;

// Trừ tiền từ ví
$wallet = $enrollment->student->wallet;
$wallet->withdraw(
    $enrollment->price_per_session,
    $enrollment,
    "Trừ tiền buổi học #{$enrollment->attended_sessions}"
);
```

---

## 🛡️ Permissions

### Products Module
- `products.view` - Xem sản phẩm
- `products.create` - Tạo sản phẩm
- `products.edit` - Sửa sản phẩm
- `products.delete` - Xóa sản phẩm

### Vouchers Module
- `vouchers.view` - Xem voucher
- `vouchers.create` - Tạo voucher
- `vouchers.edit` - Sửa voucher
- `vouchers.delete` - Xóa voucher

### Campaigns Module
- `campaigns.view` - Xem chiến dịch
- `campaigns.create` - Tạo chiến dịch
- `campaigns.edit` - Sửa chiến dịch
- `campaigns.delete` - Xóa chiến dịch

### Enrollments Module
- `enrollments.view` - Xem đơn đăng ký
- `enrollments.create` - Chốt đơn
- `enrollments.edit` - Xác nhận thanh toán
- `enrollments.delete` - Hủy đơn

### Wallets Module
- `wallets.view` - Xem ví & lịch sử
- `wallets.edit` - Khóa/Mở khóa ví

**Role Assignments:**
- **Super Admin**: All
- **Admin**: All (except wallets.edit)
- **Manager**: View all + create/edit enrollments

---

## 🔌 API Endpoints

### Products
```
GET    /api/products                  // List
GET    /api/products/featured         // Featured products
GET    /api/products/categories       // Unique categories
GET    /api/products/{id}             // Detail
POST   /api/products                  // Create
PUT    /api/products/{id}             // Update
DELETE /api/products/{id}             // Delete
```

### Vouchers
```
GET    /api/vouchers                                    // List
GET    /api/vouchers/{id}                               // Detail
GET    /api/vouchers/customer/{customerId}/applicable  // Vouchers cho customer
POST   /api/vouchers/validate                           // Validate voucher
POST   /api/vouchers                                    // Create
PUT    /api/vouchers/{id}                               // Update
DELETE /api/vouchers/{id}                               // Delete
```

### Campaigns
```
GET    /api/campaigns                 // List
GET    /api/campaigns/active          // Active campaigns
POST   /api/campaigns/auto-apply      // Auto apply best campaign
GET    /api/campaigns/{id}            // Detail
POST   /api/campaigns                 // Create
PUT    /api/campaigns/{id}            // Update
DELETE /api/campaigns/{id}            // Delete
```

### Enrollments (Chốt đơn)
```
GET    /api/enrollments                       // List
GET    /api/enrollments/statistics            // Stats
GET    /api/enrollments/{id}                  // Detail
POST   /api/enrollments                       // Create (Chốt đơn)
POST   /api/enrollments/{id}/confirm-payment  // Xác nhận thanh toán
POST   /api/enrollments/{id}/cancel           // Hủy đơn
```

### Wallets
```
GET    /api/wallets/show                      // Get wallet by owner
GET    /api/wallets/transactions              // Transaction history
GET    /api/wallets/customer/{customerId}     // All wallets (customer + children)
POST   /api/wallets/{id}/toggle-lock          // Lock/Unlock wallet
```

---

## 📊 Sample Data

### Products (8 items)
- Tiếng Anh Thiếu Nhi (3-5 tuổi) - 3tr → 2.7tr
- Tiếng Anh Tiểu Học (6-10 tuổi) - 4.5tr
- Tiếng Anh TOEIC - 6tr → 5.4tr
- Toán Tư Duy (5-7 tuổi) - 3.5tr
- Toán Nâng Cao Tiểu Học - 5tr
- Khoa Học Khám Phá (6-9 tuổi) - 4tr
- Gói Combo Tiếng Anh + Toán - 15tr → 12tr
- Bộ Sách Giáo Trình - 500k

### Vouchers (4 items)
- WELCOME2025: -15% (max 1tr, min 3tr)
- SUMMER500K: -500k (min 5tr)
- VIP20: -20% (unlimited)
- TRIAL10: -10% (max 500k, min 2tr)

### Campaigns (4 items)
- BLACKFRIDAY2025: -30% (priority 10)
- NEWYEAR2026: -25% (priority 9)
- FLASHSALE: -1tr (priority 8)
- EARLYBIRD: -20% (priority 7)

---

## 🧪 Testing Flow

### Test 1: Chốt Đơn Cho Customer
```bash
1. Login: admin@example.com
2. Vào Customers → Click vào customer
3. Click "Chốt Đơn"
4. Chọn "Cho chính khách hàng"
5. Chọn sản phẩm: "Tiếng Anh TOEIC"
6. Nhập voucher: WELCOME2025
7. Xem preview: 6tr → -900k = 5.1tr
8. Click "Tạo Đơn"
9. Enrollment được tạo với status = pending
```

### Test 2: Chốt Đơn Cho Con
```bash
1. Login: admin@example.com
2. Vào Customers → Click vào customer có con
3. Click "Chốt Đơn"
4. Chọn "Cho con" → Select "Con 1 (5 tuổi)"
5. Chọn sản phẩm: "Tiếng Anh Thiếu Nhi"
6. Không nhập voucher → Auto apply campaign
7. Xem: EARLYBIRD auto apply -20%
8. 3tr → -600k = 2.4tr
9. Tạo đơn thành công
```

### Test 3: Xác Nhận Thanh Toán
```bash
1. Vào Enrollments → Click vào đơn pending
2. Click "Xác Nhận Thanh Toán"
3. Chọn phương thức: Chuyển khoản
4. Nhập số tiền: 2,400,000
5. Submit
6. Hệ thống:
   - Tạo/Tìm ví cho con
   - Nạp 2.4tr vào ví
   - Enrollment → status = paid → active
7. Kiểm tra ví: balance = 2,400,000
```

### Test 4: Trừ Tiền Sau Buổi Học
```bash
1. Điểm danh buổi học → attended_sessions++
2. Hệ thống tự động trừ tiền:
   - Wallet withdraw: 2,400,000 / 36 = 66,667đ
   - Balance còn: 2,333,333đ
3. Kiểm tra lịch sử giao dịch trong ví
```

---

## 🎯 Business Logic

### Voucher vs Campaign Priority
1. **User nhập voucher** → Ưu tiên voucher
2. **Không nhập voucher** → Auto-apply campaign tốt nhất
3. **Có campaign tốt hơn voucher** → Dùng campaign

### Wallet cho Customer vs Child
```
Customer A:
├─ Wallet của Customer A (cho chính họ học)
└─ Children:
   ├─ Child 1 → Wallet riêng
   └─ Child 2 → Wallet riêng

→ Ví hoàn toàn tách biệt, minh bạch cho từng người
```

### Tính Giá/Buổi
```
Product: 3,000,000đ / 36 buổi = 83,333đ/buổi

Sau giảm giá:
Final Price: 2,400,000đ / 36 buổi = 66,667đ/buổi

→ Trừ 66,667đ sau mỗi buổi học
```

---

## 🔄 Tích Hợp Accounting Module

### Tự động tạo Transaction khi thanh toán
```php
// Trong EnrollmentController@confirmPayment
// TODO: Tích hợp với Transaction module

// Tạo transaction thu tiền
Transaction::create([
    'type' => 'income',
    'category' => 'enrollment',
    'amount' => $amount,
    'reference_type' => 'App\Models\Enrollment',
    'reference_id' => $enrollment->id,
    'branch_id' => $enrollment->branch_id,
    'description' => "Thu tiền từ đơn đăng ký {$enrollment->code}",
    'status' => 'completed',
    'transaction_date' => now(),
]);
```

---

## 📝 Frontend Components (TODO)

### 1. EnrollmentFormModal.vue
Modal chốt đơn trong Customer detail:
- Radio: Học cho chính customer / Học cho con
- Dropdown: Chọn con (nếu chọn con)
- Product selector với preview giá
- Voucher input với validate button
- Auto show campaign đang áp dụng
- Preview tổng tiền
- Submit button

### 2. ProductsList.vue
Danh sách sản phẩm với filters:
- Type, Category, Featured
- Search by name/code
- Card view với giá, discount badge

### 3. VoucherSelector.vue
Component chọn voucher:
- List vouchers available cho customer
- Show usage limit, expiry date
- Highlight auto-applied campaign

### 4. WalletCard.vue
Hiển thị thông tin ví:
- Balance, Total in/out
- Transaction history
- Lock/Unlock button (admin only)

---

## 🚀 Deployment Steps

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Run Seeders
```bash
php artisan db:seed --class=SalesModulesPermissionsSeeder
php artisan db:seed --class=ProductsSeeder
php artisan db:seed --class=VouchersAndCampaignsSeeder
php artisan db:seed --class=SalesModulesTranslationsSeeder
```

### 3. Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 4. Build Frontend
```bash
npm run build
```

---

## ✅ Checklist

### Backend
- [x] Migrations (6 tables)
- [x] Models (7 models)
- [x] Controllers (5 controllers)
- [x] API Routes
- [x] Permissions (5 modules, 18 permissions)
- [x] Sample Data Seeders
- [x] Translations (60+ keys)

### Frontend
- [ ] EnrollmentFormModal.vue
- [ ] ProductsList.vue (Page)
- [ ] VoucherSelector.vue
- [ ] WalletCard.vue
- [ ] Integration vào CustomersList

### Accounting Integration
- [ ] Tạo Transaction khi confirm payment
- [ ] Sync với Accounting reports
- [ ] Revenue tracking

---

## 📚 Related Docs

- `CUSTOMERS_MODULE_COMPLETE.md` - Customer module
- `CUSTOMER_CHILDREN.md` - Children management
- `CUSTOMER_INTERACTIONS.md` - Interaction history

---

**🎉 Hệ thống backend đã hoàn tất! Ready for frontend integration!**


