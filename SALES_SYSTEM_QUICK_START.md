# 🚀 HỆ THỐNG BÁN HÀNG - QUICK START GUIDE

## 📦 Setup Nhanh

### 1. Chạy Migrations
```bash
php artisan migrate
```

### 2. Chạy Seeders
```bash
# Permissions & Modules
php artisan db:seed --class=SalesModulesPermissionsSeeder

# Sample Products
php artisan db:seed --class=ProductsSeeder

# Vouchers & Campaigns
php artisan db:seed --class=VouchersAndCampaignsSeeder

# Translations
php artisan db:seed --class=SalesModulesTranslationsSeeder
```

### 3. Clear Cache & Build
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
npm run build
```

---

## 🎯 Workflow Cơ Bản

### Bước 1: Tạo Sản Phẩm
```
1. Login với quyền products.create
2. API: POST /api/products
{
  "name": "Khóa học Tiếng Anh",
  "price": 3000000,
  "type": "course",
  "total_sessions": 36
}
```

### Bước 2: Tạo Voucher (Optional)
```
API: POST /api/vouchers
{
  "code": "WELCOME10",
  "name": "Giảm 10%",
  "type": "percentage",
  "value": 10,
  "is_active": true
}
```

### Bước 3: Tạo Campaign (Optional)
```
API: POST /api/campaigns
{
  "code": "SUMMER2025",
  "name": "Khuyến mãi hè",
  "discount_type": "percentage",
  "discount_value": 20,
  "start_date": "2025-06-01",
  "end_date": "2025-08-31",
  "is_auto_apply": true
}
```

### Bước 4: Chốt Đơn
```
API: POST /api/enrollments
{
  "customer_id": 1,
  "student_type": "App\\Models\\Customer",
  "student_id": 1,
  "product_id": 5,
  "voucher_code": "WELCOME10"  // Optional
}

Response:
{
  "code": "ENR20251106001",
  "original_price": 3000000,
  "discount_amount": 300000,
  "final_price": 2700000,
  "status": "pending"
}
```

### Bước 5: Xác Nhận Thanh Toán
```
API: POST /api/enrollments/{id}/confirm-payment
{
  "payment_method": "cash",
  "amount": 2700000
}

Hệ thống tự động:
- Tạo/Tìm Wallet cho student
- Nạp tiền vào Wallet
- Enrollment → status = "paid" → "active"
```

### Bước 6: Xem Ví
```
API: GET /api/wallets/customer/{customerId}

Response: Danh sách ví của customer + children
```

---

## 🧪 Test Scenarios

### Scenario 1: Chốt đơn cho Customer (cho chính họ)
```bash
# 1. Tạo enrollment
curl -X POST /api/enrollments \
  -H "Authorization: Bearer {token}" \
  -d '{
    "customer_id": 1,
    "student_type": "App\\Models\\Customer",
    "student_id": 1,
    "product_id": 1,
    "voucher_code": "WELCOME2025"
  }'

# 2. Xác nhận thanh toán
curl -X POST /api/enrollments/1/confirm-payment \
  -H "Authorization: Bearer {token}" \
  -d '{
    "payment_method": "cash",
    "amount": 2700000
  }'

# 3. Kiểm tra ví
curl /api/wallets/customer/1 \
  -H "Authorization: Bearer {token}"
```

### Scenario 2: Chốt đơn cho Con
```bash
# 1. Lấy danh sách con
curl /api/customers/1/children \
  -H "Authorization: Bearer {token}"

# 2. Tạo enrollment cho con
curl -X POST /api/enrollments \
  -H "Authorization: Bearer {token}" \
  -d '{
    "customer_id": 1,
    "student_type": "App\\Models\\CustomerChild",
    "student_id": 3,
    "product_id": 2
  }'

# Hệ thống tự động auto-apply campaign tốt nhất
```

### Scenario 3: Validate Voucher
```bash
curl -X POST /api/vouchers/validate \
  -H "Authorization: Bearer {token}" \
  -d '{
    "code": "WELCOME2025",
    "customer_id": 1,
    "product_id": 1,
    "amount": 3000000
  }'

Response:
{
  "success": true,
  "data": {
    "voucher": {...},
    "discount_amount": 450000,
    "final_amount": 2550000
  }
}
```

---

## 📊 Sample Data Overview

### Products (8 items)
| Code | Name | Price | Type |
|------|------|-------|------|
| PRD00001 | Tiếng Anh Thiếu Nhi (3-5 tuổi) | 2.7tr (sale) | course |
| PRD00002 | Tiếng Anh Tiểu Học (6-10 tuổi) | 4.5tr | course |
| PRD00003 | Tiếng Anh TOEIC | 5.4tr (sale) | course |
| PRD00004 | Toán Tư Duy (5-7 tuổi) | 3.5tr | course |
| PRD00005 | Toán Nâng Cao Tiểu Học | 5tr | course |
| PRD00006 | Khoa Học Khám Phá (6-9 tuổi) | 4tr | course |
| PRD00007 | Gói Combo Tiếng Anh + Toán | 12tr (sale) | package |
| PRD00008 | Bộ Sách Giáo Trình | 500k | material |

### Vouchers (4 items)
| Code | Discount | Min Order |
|------|----------|-----------|
| WELCOME2025 | -15% (max 1tr) | 3tr |
| SUMMER500K | -500k | 5tr |
| VIP20 | -20% | - |
| TRIAL10 | -10% (max 500k) | 2tr |

### Campaigns (4 items - Auto Apply)
| Code | Discount | Priority |
|------|----------|----------|
| BLACKFRIDAY2025 | -30% | 10 |
| NEWYEAR2026 | -25% | 9 |
| FLASHSALE | -1tr | 8 |
| EARLYBIRD | -20% | 7 |

---

## 🔑 Key Concepts

### 1. Student Type (Polymorphic)
```
Enrollment.student_type:
- "App\Models\Customer" → Khách hàng học cho chính họ
- "App\Models\CustomerChild" → Khách hàng đăng ký cho con
```

### 2. Discount Priority
```
1. Voucher (user nhập) → Ưu tiên cao nhất
2. Campaign (auto-apply) → Tự động áp dụng campaign tốt nhất
3. Nếu campaign tốt hơn voucher → Dùng campaign
```

### 3. Wallet Separation
```
Customer A:
├─ Wallet của Customer A (balance: 5tr)
└─ Children:
   ├─ Child 1 → Wallet riêng (balance: 2.4tr)
   └─ Child 2 → Wallet riêng (balance: 3tr)
```

### 4. Enrollment Status Flow
```
pending → paid → active → completed
           ↓
        cancelled
```

### 5. Price Calculation
```
Original Price: 3,000,000đ
- Discount: -600,000đ (Campaign EARLYBIRD -20%)
= Final Price: 2,400,000đ

→ Nạp vào ví: 2,400,000đ
→ Trừ sau mỗi buổi: 2,400,000 / 36 = 66,667đ
```

---

## 🛡️ Permissions Check

```bash
# Check user permissions
$user->can('products.view')
$user->can('enrollments.create')
$user->can('wallets.view')

# Middleware trong routes
Route::middleware('permission:enrollments.create')->post('/enrollments', ...)
```

---

## 🐛 Common Issues & Solutions

### Issue 1: Voucher không áp dụng được
```
Kiểm tra:
✓ Voucher còn hiệu lực (start_date, end_date)
✓ Chưa hết số lần dùng (usage_limit)
✓ Customer chưa dùng quá giới hạn (usage_per_customer)
✓ Đơn hàng đạt giá trị tối thiểu (min_order_amount)
✓ Sản phẩm nằm trong applicable_product_ids
```

### Issue 2: Không tạo được Wallet
```
Kiểm tra:
✓ Student (Customer/Child) tồn tại
✓ Branch_id hợp lệ
✓ User có quyền tạo enrollment
```

### Issue 3: Campaign không auto-apply
```
Kiểm tra:
✓ Campaign.is_active = true
✓ Campaign.is_auto_apply = true
✓ Trong thời gian hiệu lực (start_date → end_date)
✓ Sản phẩm phù hợp (applicable_product_ids/categories)
✓ Đơn hàng đạt min_order_amount
```

---

## 📞 API Endpoints Quick Reference

```bash
# Products
GET    /api/products                    # List all
GET    /api/products/featured           # Featured only
POST   /api/products                    # Create

# Vouchers
GET    /api/vouchers                    # List all
POST   /api/vouchers/validate           # Validate code
GET    /api/vouchers/customer/{id}/applicable  # Available for customer

# Campaigns
GET    /api/campaigns/active            # Active campaigns
POST   /api/campaigns/auto-apply        # Auto-apply best

# Enrollments
POST   /api/enrollments                 # Chốt đơn
POST   /api/enrollments/{id}/confirm-payment  # Thanh toán
GET    /api/enrollments/statistics      # Stats

# Wallets
GET    /api/wallets/customer/{id}       # Customer wallets
GET    /api/wallets/transactions        # Transaction history
```

---

## 🎉 Next Steps

1. ✅ Test tất cả API endpoints với Postman
2. ⏳ Tích hợp frontend EnrollmentFormModal
3. ⏳ Tích hợp với Accounting module (tạo Transaction)
4. ⏳ Thêm tính năng trừ tiền sau mỗi buổi học
5. ⏳ Báo cáo doanh thu theo sản phẩm/campaign

---

**Tài liệu chi tiết:** `SALES_SYSTEM_DOCUMENTATION.md`

