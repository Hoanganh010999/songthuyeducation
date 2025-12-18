# ✅ Sales Module Structure - FIXED

## Cấu trúc đã sửa (Đúng theo thiết kế)

### 1. **Sidebar Chính** (Main Navigation)
```
📊 Dashboard
🏢 Branches
🛒 Sales ← CHỈ CÓ 1 MENU ITEM
📅 Calendar
👥 HR
📋 Quality
...
```

### 2. **Sales Module - Sidebar Thứ Cấp** (Secondary Sidebar bên trong)
Khi click vào "Sales" trong sidebar chính, sẽ mở layout với **sidebar thứ cấp bên trái**:

```
📂 Sales (Bán hàng)
├─ 👥 Customers (Khách hàng)
├─ 📦 Products (Sản phẩm) ← MỚI THÊM
├─ 📋 Enrollments (Đăng ký học) ← ĐÃ SỬA TỪ PLACEHOLDER
├─ 📢 Campaigns (Chiến dịch)
├─ 🎫 Vouchers (Voucher)
└─ ⚙️  Settings (Cài đặt)
```

## Những gì đã sửa

### ❌ Trước đây (SAI):
- **ProductsList** và **EnrollmentsList** là routes riêng (`/products`, `/enrollments`)
- Hiển thị ở **sidebar chính** ngang hàng với Sales
- Sidebar thứ cấp có hardcode text tiếng Việt
- RegistrationsList là component placeholder rỗng

### ✅ Bây giờ (ĐÚNG):
- **ProductsList** và **EnrollmentsList** nằm TRONG **SalesIndex.vue**
- Hiển thị ở **sidebar thứ cấp** bên trong Sales module
- Tất cả text dùng **i18n** (EN/VI)
- RegistrationsList được thay bằng EnrollmentsList có chức năng đầy đủ
- Xóa routes `/products` và `/enrollments` khỏi router chính

## Files đã thay đổi

### 1. **SalesIndex.vue** (Main Changes)
```vue
<template>
  <div class="flex">
    <!-- Sidebar thứ cấp (bên trái) -->
    <div class="w-64 sidebar">
      <h1>{{ t('sales.title') }}</h1>
      
      <!-- Menu items với i18n -->
      <button @click="selectItem('customers')">{{ t('customers.title') }}</button>
      <button @click="selectItem('products')">{{ t('products.title') }}</button>
      <button @click="selectItem('enrollments')">{{ t('enrollments.title') }}</button>
      <button @click="selectItem('campaigns')">{{ t('campaigns.title') }}</button>
      <button @click="selectItem('vouchers')">{{ t('vouchers.title') }}</button>
      <button @click="selectItem('settings')">{{ t('common.settings') }}</button>
    </div>
    
    <!-- Content area (bên phải) -->
    <div class="flex-1">
      <CustomersList v-if="selectedItem === 'customers'" />
      <ProductsList v-else-if="selectedItem === 'products'" />
      <EnrollmentsList v-else-if="selectedItem === 'enrollments'" />
      <SalesCampaignsList v-else-if="selectedItem === 'campaigns'" />
      <VouchersList v-else-if="selectedItem === 'vouchers'" />
      <SalesSettings v-else-if="selectedItem === 'settings'" />
    </div>
  </div>
</template>

<script setup>
import { useI18n } from '../../composables/useI18n';
import ProductsList from '../products/ProductsList.vue';
import EnrollmentsList from '../enrollments/EnrollmentsList.vue';
// ...
</script>
```

### 2. **DashboardLayout.vue**
```vue
<!-- XÓA 2 items này khỏi sidebar chính -->
<!-- ❌ <router-link to="/products">Products</router-link> -->
<!-- ❌ <router-link to="/enrollments">Enrollments</router-link> -->

<!-- ✅ GIỮ LẠI CHỈ 1 ITEM -->
<router-link to="/sales">{{ t('sales.title') }}</router-link>
```

### 3. **router/index.js**
```javascript
// ❌ XÓA 2 routes này
// { path: 'products', component: ProductsList }
// { path: 'enrollments', component: EnrollmentsList }

// ✅ GIỮ LẠI CHỈ 1 ROUTE
{ path: 'sales', component: SalesIndex }
```

## Translations Mới

Đã thêm các translation keys cho sidebar thứ cấp:
```javascript
sales.description = 'Quản lý khách hàng và chiến dịch'
sales.settings_description = 'Tương tác & nguồn KH'
enrollments.description = 'Đã đóng tiền chờ verify'
campaigns.description = 'Giảm giá, tặng quà...'
vouchers.description = 'Mã giảm giá'
```

## Cách sử dụng

1. **Truy cập Sales Module:**
   ```
   Click "Sales" trong sidebar chính → Mở layout với sidebar thứ cấp
   ```

2. **Navigate bên trong Sales:**
   ```
   Click "Customers" → Hiển thị CustomersList
   Click "Products" → Hiển thị ProductsList
   Click "Enrollments" → Hiển thị EnrollmentsList
   Click "Campaigns" → Hiển thị SalesCampaignsList
   Click "Vouchers" → Hiển thị VouchersList
   Click "Settings" → Hiển thị SalesSettings
   ```

3. **Switch Language:**
   ```
   Tất cả text trong sidebar thứ cấp tự động đổi theo ngôn ngữ được chọn
   ```

## Lợi ích của cấu trúc mới

✅ **Tổ chức rõ ràng:** Products & Enrollments là sub-modules của Sales
✅ **Sidebar gọn gàng:** Không làm phình sidebar chính
✅ **i18n hoàn chỉnh:** Tất cả text dùng translation
✅ **Consistent UX:** Giống với các modules khác (HR, Quality...)
✅ **Scalable:** Dễ thêm sub-modules mới vào Sales

## Database Seeded

- ✅ 120+ translation keys (products, enrollments, vouchers, campaigns, wallets, sales)
- ✅ Permissions (products, vouchers, campaigns, enrollments)
- ✅ Migrations (products, vouchers, campaigns, wallets, enrollments)

## Testing

```bash
# 1. Truy cập Sales module
http://localhost:8000/sales

# 2. Click các menu items trong sidebar thứ cấp
- Customers → Xem danh sách khách hàng
- Products → Xem danh sách sản phẩm (với i18n)
- Enrollments → Xem danh sách đăng ký (với i18n)
- Campaigns → Placeholder (đang phát triển)
- Vouchers → Placeholder (đang phát triển)
- Settings → Customer sources & interaction types

# 3. Test i18n
Switch language → Tất cả text trong sidebar thứ cấp và content tự động đổi
```

## Kết luận

✅ **Cấu trúc Sales Module đã ĐÚNG theo thiết kế ban đầu**
✅ **Products & Enrollments nằm trong sidebar thứ cấp**
✅ **Không còn trùng lặp chức năng**
✅ **100% sử dụng i18n**

