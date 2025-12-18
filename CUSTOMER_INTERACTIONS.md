# Hệ Thống Lịch Sử Tương Tác Khách Hàng

## 📋 Tổng Quan

Hệ thống quản lý lịch sử tương tác với khách hàng, cho phép:
- **Xem lịch sử tương tác** của từng khách hàng
- **Thêm mới tương tác** với thông tin chi tiết
- **Hiển thị tương tác gần nhất** ngay trong danh sách khách hàng
- **Click vào tên khách hàng** → Mở modal xem toàn bộ lịch sử

---

## 🗄️ Database Schema

### Bảng `customer_interactions`

```sql
- id (bigint, PK)
- customer_id (FK → customers.id)
- user_id (FK → users.id) - Nhân viên thực hiện tương tác
- interaction_type_id (FK → customer_interaction_types.id)
- interaction_result_id (FK → customer_interaction_results.id)
- notes (text) - Ghi chú chi tiết
- interaction_date (datetime) - Ngày giờ tương tác
- next_follow_up (datetime, nullable) - Ngày hẹn liên hệ lại
- metadata (json, nullable) - Thông tin bổ sung
- created_at, updated_at
```

**Indexes:**
- `customer_id`
- `user_id`
- `interaction_date`

---

## 🔗 Relationships

### CustomerInteraction Model
```php
- customer() → belongsTo(Customer)
- user() → belongsTo(User)
- interactionType() → belongsTo(CustomerInteractionType)
- interactionResult() → belongsTo(CustomerInteractionResult)
```

### Customer Model (Updated)
```php
- interactions() → hasMany(CustomerInteraction)->latest()
- latestInteraction() → hasOne(CustomerInteraction)->latestOfMany('interaction_date')
```

---

## 🛣️ API Routes

### Customer Interactions (Protected)

```php
GET    /api/customers/{customerId}/interactions
       - Lấy danh sách interactions (paginated)
       - Middleware: permission:customers.view

POST   /api/customers/{customerId}/interactions
       - Tạo interaction mới
       - Middleware: permission:customers.create
       - Body: {
           interaction_type_id, 
           interaction_result_id, 
           notes, 
           interaction_date,
           next_follow_up? (optional)
         }

PUT    /api/customers/{customerId}/interactions/{interactionId}
       - Cập nhật interaction
       - Middleware: permission:customers.edit

DELETE /api/customers/{customerId}/interactions/{interactionId}
       - Xóa interaction
       - Middleware: permission:customers.delete
```

---

## 🎨 Frontend Components

### 1. **CustomerInteractionHistoryModal.vue**
**Đường dẫn:** `resources/js/components/customers/CustomerInteractionHistoryModal.vue`

**Chức năng:**
- Hiển thị modal toàn màn hình với lịch sử tương tác
- Timeline view với thông tin đầy đủ:
  - Loại tương tác (icon + tên)
  - Kết quả tương tác (badge màu)
  - Ghi chú chi tiết
  - Ngày giờ tương tác
  - Nhân viên thực hiện
  - Lịch hẹn liên hệ lại (nếu có)
- Nút "Thêm Tương Tác" mở form modal
- Nút xóa từng interaction
- Auto refresh khi thêm/xóa

**Props:**
- `show` (Boolean) - Hiển thị/ẩn modal
- `customer` (Object) - Thông tin khách hàng

**Events:**
- `@close` - Đóng modal

---

### 2. **CustomerInteractionFormModal.vue**
**Đường dẫn:** `resources/js/components/customers/CustomerInteractionFormModal.vue`

**Chức năng:**
- Form thêm tương tác mới
- Các trường:
  - **Ngày giờ tương tác** (datetime-local, required)
  - **Loại tương tác** (select, required)
  - **Kết quả tương tác** (select, required)
  - **Ghi chú** (textarea, required)
  - **Hẹn liên hệ lại** (datetime-local, optional)
- Auto load danh sách interaction types & results
- Default ngày giờ = hiện tại
- Validation đầy đủ

**Props:**
- `show` (Boolean)
- `customer` (Object)

**Events:**
- `@close` - Đóng form
- `@saved` - Sau khi lưu thành công

---

### 3. **CustomersList.vue (Updated)**
**Đường dẫn:** `resources/js/pages/customers/CustomersList.vue`

**Thay đổi:**

#### Table Header
- Thay cột "Branch" → "Latest Interaction"

#### Table Body
```html
<!-- Tên khách hàng - Clickable -->
<button @click="openInteractionHistory(customer)">
  <div class="text-blue-600 hover:underline">{{ customer.name }}</div>
</button>

<!-- Tương tác gần nhất -->
<td v-if="customer.latest_interaction">
  <span class="badge">{{ result.name }}</span>
  <span class="date">{{ formatShortDate(date) }}</span>
  <p class="notes line-clamp-2">{{ notes }}</p>
</td>
```

#### New Functions
```javascript
const formatShortDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const openInteractionHistory = (customer) => {
  selectedCustomerForHistory.value = customer;
  showInteractionHistoryModal.value = true;
};

const closeInteractionHistoryModal = () => {
  showInteractionHistoryModal.value = false;
  selectedCustomerForHistory.value = null;
  loadCustomers(pagination.value.current_page); // Refresh latest interaction
};
```

---

## 🌐 Translations

### Các key mới (customers group):

| Key | Vietnamese | English |
|-----|-----------|---------|
| `interaction_history` | Lịch Sử Tương Tác | Interaction History |
| `add_interaction` | Thêm Tương Tác | Add Interaction |
| `no_interactions` | Chưa có lịch sử tương tác | No interaction history yet |
| `interaction_date` | Ngày Tương Tác | Interaction Date |
| `interaction_type` | Loại Tương Tác | Interaction Type |
| `interaction_result` | Kết Quả | Result |
| `notes` | Ghi Chú | Notes |
| `notes_placeholder` | Nhập ghi chú về lần tương tác này... | Enter notes about this interaction... |
| `next_follow_up` | Hẹn Liên Hệ Lại | Next Follow-up |
| `next_follow_up_hint` | Tùy chọn: Đặt lịch nhắc liên hệ lại | Optional: Set a reminder for next contact |
| `customer` | Khách Hàng | Customer |
| `latest_interaction` | Tương Tác Gần Nhất | Latest Interaction |

---

## 🎯 User Flow

### 1. Xem lịch sử tương tác
1. Người dùng vào **Customers > List**
2. **Click vào tên khách hàng**
3. Modal "Lịch Sử Tương Tác" hiển thị
4. Xem timeline các lần tương tác từ mới → cũ

### 2. Thêm tương tác mới
1. Trong modal lịch sử, click **"Thêm Tương Tác"**
2. Form modal hiển thị
3. Điền thông tin:
   - Chọn loại tương tác (gọi điện, email, gặp mặt,...)
   - Chọn kết quả (thành công, không nghe máy, hẹn lại,...)
   - Viết ghi chú chi tiết
   - (Tùy chọn) Đặt lịch hẹn liên hệ lại
4. Click **"Lưu"**
5. Modal đóng, danh sách tự động refresh

### 3. Xem tương tác gần nhất
- Ngay trong danh sách customers, cột **"Latest Interaction"** hiển thị:
  - Badge kết quả (có màu)
  - Ngày giờ tương tác
  - 2 dòng ghi chú đầu tiên (line-clamp-2)

### 4. Xóa interaction
1. Trong modal lịch sử, click **icon thùng rác** ở interaction cần xóa
2. SweetAlert2 xác nhận (iOS style)
3. Xóa thành công → danh sách tự động refresh

---

## 🔐 Permissions

Sử dụng lại permissions của module Customers:
- `customers.view` - Xem danh sách interactions
- `customers.create` - Thêm interaction mới
- `customers.edit` - Sửa interaction
- `customers.delete` - Xóa interaction

---

## 📊 Data Flow

### Backend (CustomerController@index)
```php
Customer::with([
    'branch:id,code,name',
    'assignedUser:id,name,email',
    'latestInteraction' => function($q) {
        $q->with([
            'user:id,name',
            'interactionType:id,name,code,icon,color',
            'interactionResult:id,name,code,icon,color'
        ]);
    }
])
```

### Frontend Response
```json
{
  "id": 1,
  "name": "Nguyễn Văn A",
  "email": "a@example.com",
  "phone": "0901234567",
  "latest_interaction": {
    "id": 5,
    "interaction_date": "2025-10-31 10:30:00",
    "notes": "Khách hàng quan tâm đến gói Premium...",
    "next_follow_up": "2025-11-05 14:00:00",
    "user": {
      "id": 2,
      "name": "Admin User"
    },
    "interaction_type": {
      "id": 1,
      "name": "Gọi Điện",
      "icon": "phone",
      "color": "#3B82F6"
    },
    "interaction_result": {
      "id": 3,
      "name": "Hẹn Gặp Lại",
      "icon": "calendar",
      "color": "#F59E0B"
    }
  }
}
```

---

## ✨ UI/UX Highlights

### 1. **Click-to-View Pattern**
- Tên khách hàng = link màu xanh
- Hover → underline
- Click → Mở modal lịch sử

### 2. **Timeline View**
- Sắp xếp từ mới → cũ
- Icon + màu sắc rõ ràng
- Badge kết quả với màu tương ứng
- Ghi chú trong box riêng

### 3. **Form Validation**
- Tất cả trường required đều có dấu `*` màu đỏ
- Datetime picker mặc định = hiện tại
- Placeholder hữu ích

### 4. **Responsive Design**
- Modal chiếm tối đa 90vh
- Overflow scroll khi nội dung dài
- Sticky header trong modal

### 5. **Performance**
- Lazy load interactions chỉ khi mở modal
- Pagination cho danh sách dài
- Auto refresh thông minh

---

## 🧪 Testing Checklist

- [ ] Tạo interaction mới thành công
- [ ] Hiển thị latest interaction trong danh sách
- [ ] Click tên khách hàng → Modal mở
- [ ] Timeline hiển thị đúng thứ tự (mới → cũ)
- [ ] Icon và màu sắc hiển thị đúng
- [ ] Datetime picker hoạt động
- [ ] Next follow-up (optional) có thể bỏ trống
- [ ] Xóa interaction với xác nhận SweetAlert2
- [ ] Auto refresh sau khi thêm/xóa
- [ ] Translations đầy đủ (VI/EN)
- [ ] Permissions hoạt động đúng
- [ ] Mobile responsive

---

## 🚀 Deployment Steps

1. **Migration:**
   ```bash
   php artisan migrate --path=database/migrations/2025_10_31_083719_create_customer_interactions_table.php
   ```

2. **Seeder:**
   ```bash
   php artisan db:seed --class=CustomerInteractionTranslationsSeeder
   ```

3. **Clear Cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

4. **Build Frontend:**
   ```bash
   npm run build
   ```

5. **Clear Browser Cache/Translations:**
   - Logout/Login lại
   - Hoặc click "Refresh Translations" trong Language Switcher

---

## 📝 Notes

### Icon Emoji Map
Component sử dụng icon emoji mapping để hiển thị icon:
```javascript
{
  phone: '📞',
  envelope: '✉️',
  message: '💬',
  users: '👥',
  facebook: '📘',
  store: '🏪',
  'check-circle': '✅',
  calendar: '📅',
  // ... và nhiều icon khác
}
```

### Line Clamp CSS
```css
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
```

---

## 🎉 Kết Quả

**Trước:**
- Không có lịch sử tương tác với khách hàng
- Không biết lần tương tác gần nhất
- Khó theo dõi tiến trình chăm sóc

**Sau:**
- ✅ Đầy đủ lịch sử tương tác timeline
- ✅ Hiển thị latest interaction ngay trong danh sách
- ✅ Click tên khách hàng → Xem toàn bộ lịch sử
- ✅ Dễ dàng thêm/xóa interaction
- ✅ Đặt lịch hẹn liên hệ lại
- ✅ UI/UX đẹp với màu sắc, icon rõ ràng
- ✅ Integration hoàn chỉnh với SweetAlert2 iOS style

---

**🎊 Hệ thống lịch sử tương tác khách hàng đã hoàn thành!**

