# Customer Source Dropdown from Settings

## Tổng quan

Thay đổi field "Nguồn khách hàng" từ text input thành dropdown select, lấy dữ liệu từ Customer Settings (Sources).

## Changes Made

### 1. CustomerModal.vue

#### Before (Text Input):
```vue
<input
  v-model="form.source"
  type="text"
  :placeholder="t('customers.source_placeholder')"
  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
/>
```

#### After (Dropdown Select):
```vue
<select
  v-model="form.source"
  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
>
  <option value="">{{ t('common.select') }}</option>
  <option v-for="source in sources" :key="source.id" :value="source.name">
    {{ source.name }}
  </option>
</select>
```

### 2. Script Changes

#### Add sources ref:
```javascript
const branches = ref([]);
const sources = ref([]);  // ← NEW
const loading = ref(false);
```

#### Add loadSources function:
```javascript
const loadSources = async () => {
  try {
    const response = await api.get('/api/customers/settings/sources');
    if (response.data.success) {
      sources.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load sources:', error);
  }
};
```

#### Call loadSources on mount:
```javascript
onMounted(() => {
  if (authStore.isSuperAdmin) {
    loadBranches();
  }
  loadSources();  // ← NEW
});
```

## API Endpoint Used

```http
GET /api/customers/settings/sources
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Facebook",
      "code": "facebook",
      "icon": "facebook",
      "color": "#1877F2",
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-11-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Google",
      "code": "google",
      "icon": "google",
      "color": "#4285F4",
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-11-01T10:00:00.000000Z"
    }
  ]
}
```

## Benefits

### 1. Data Consistency
- Nguồn khách hàng được chuẩn hóa
- Không có lỗi chính tả hoặc format khác nhau
- Dễ dàng filter và báo cáo

### 2. Centralized Management
- Admin có thể quản lý danh sách sources tập trung
- Thêm/sửa/xóa sources từ Settings modal
- Không cần hardcode trong code

### 3. Better UX
- User không cần nhớ hoặc gõ tên nguồn
- Dropdown dễ dàng chọn hơn text input
- Giảm thiểu lỗi nhập liệu

### 4. Scalability
- Dễ dàng thêm sources mới
- Có thể thêm icon, color cho mỗi source
- Có thể thêm metadata (tracking code, cost, etc.)

## Related Features

### Customer Settings Modal
Quản lý 3 loại settings:
1. **Interaction Types** (Loại tương tác)
2. **Interaction Results** (Kết quả tương tác)
3. **Sources** (Nguồn khách hàng) ← Used here

### Database Table: `customer_sources`
```sql
- id (bigint, primary key)
- name (string) - Tên nguồn
- code (string, unique) - Mã nguồn
- icon (string, nullable) - Icon name
- color (string, nullable) - Hex color
- is_active (boolean) - Trạng thái
- sort_order (integer) - Thứ tự sắp xếp
- timestamps
```

## Testing

### Test Scenarios:

1. **Load Sources on Modal Open**
   - Mở modal tạo/sửa customer
   - Dropdown "Nguồn" hiển thị danh sách từ settings
   - Có option "Chọn" ở đầu

2. **Create Customer with Source**
   - Chọn source từ dropdown
   - Save customer
   - Source được lưu vào database

3. **Edit Customer with Existing Source**
   - Mở modal edit customer có source
   - Dropdown pre-select đúng source
   - Có thể đổi sang source khác

4. **Empty Sources List**
   - Nếu chưa có sources trong settings
   - Dropdown chỉ có option "Chọn"
   - Vẫn có thể save customer (source nullable)

5. **Add New Source**
   - Vào Settings → Sources
   - Thêm source mới
   - Quay lại Customer modal
   - Source mới xuất hiện trong dropdown

## Future Enhancements

### 1. Source Analytics
```javascript
// Thống kê khách hàng theo nguồn
GET /api/customers/analytics/by-source

Response:
{
  "facebook": 150,
  "google": 80,
  "referral": 45
}
```

### 2. Source Icons in Dropdown
```vue
<option v-for="source in sources" :key="source.id" :value="source.name">
  <span :style="{ color: source.color }">
    {{ getIcon(source.icon) }} {{ source.name }}
  </span>
</option>
```

### 3. Source-based Automation
- Auto-assign customer to specific staff based on source
- Different follow-up workflows per source
- Source-specific email templates

### 4. Source Performance Tracking
- Conversion rate by source
- Average deal value by source
- Time to close by source

## Notes

- **Backward Compatibility:** Customers đã có source dạng text vẫn hoạt động bình thường
- **Nullable Field:** Source không bắt buộc, có thể để trống
- **Case Sensitive:** Lưu exact name từ dropdown (case-sensitive)
- **No Free Text:** User không thể nhập text tự do, chỉ chọn từ list

## Migration Path (if needed)

Nếu đã có data source dạng text cần migrate:

```sql
-- 1. Tạo sources từ data hiện có
INSERT INTO customer_sources (name, code, created_at, updated_at)
SELECT DISTINCT source, LOWER(REPLACE(source, ' ', '_')), NOW(), NOW()
FROM customers
WHERE source IS NOT NULL AND source != '';

-- 2. Chuẩn hóa data (optional)
UPDATE customers c
JOIN customer_sources cs ON c.source = cs.name
SET c.source = cs.name;
```


## Tổng quan

Thay đổi field "Nguồn khách hàng" từ text input thành dropdown select, lấy dữ liệu từ Customer Settings (Sources).

## Changes Made

### 1. CustomerModal.vue

#### Before (Text Input):
```vue
<input
  v-model="form.source"
  type="text"
  :placeholder="t('customers.source_placeholder')"
  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
/>
```

#### After (Dropdown Select):
```vue
<select
  v-model="form.source"
  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
>
  <option value="">{{ t('common.select') }}</option>
  <option v-for="source in sources" :key="source.id" :value="source.name">
    {{ source.name }}
  </option>
</select>
```

### 2. Script Changes

#### Add sources ref:
```javascript
const branches = ref([]);
const sources = ref([]);  // ← NEW
const loading = ref(false);
```

#### Add loadSources function:
```javascript
const loadSources = async () => {
  try {
    const response = await api.get('/api/customers/settings/sources');
    if (response.data.success) {
      sources.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load sources:', error);
  }
};
```

#### Call loadSources on mount:
```javascript
onMounted(() => {
  if (authStore.isSuperAdmin) {
    loadBranches();
  }
  loadSources();  // ← NEW
});
```

## API Endpoint Used

```http
GET /api/customers/settings/sources
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Facebook",
      "code": "facebook",
      "icon": "facebook",
      "color": "#1877F2",
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-11-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Google",
      "code": "google",
      "icon": "google",
      "color": "#4285F4",
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-11-01T10:00:00.000000Z"
    }
  ]
}
```

## Benefits

### 1. Data Consistency
- Nguồn khách hàng được chuẩn hóa
- Không có lỗi chính tả hoặc format khác nhau
- Dễ dàng filter và báo cáo

### 2. Centralized Management
- Admin có thể quản lý danh sách sources tập trung
- Thêm/sửa/xóa sources từ Settings modal
- Không cần hardcode trong code

### 3. Better UX
- User không cần nhớ hoặc gõ tên nguồn
- Dropdown dễ dàng chọn hơn text input
- Giảm thiểu lỗi nhập liệu

### 4. Scalability
- Dễ dàng thêm sources mới
- Có thể thêm icon, color cho mỗi source
- Có thể thêm metadata (tracking code, cost, etc.)

## Related Features

### Customer Settings Modal
Quản lý 3 loại settings:
1. **Interaction Types** (Loại tương tác)
2. **Interaction Results** (Kết quả tương tác)
3. **Sources** (Nguồn khách hàng) ← Used here

### Database Table: `customer_sources`
```sql
- id (bigint, primary key)
- name (string) - Tên nguồn
- code (string, unique) - Mã nguồn
- icon (string, nullable) - Icon name
- color (string, nullable) - Hex color
- is_active (boolean) - Trạng thái
- sort_order (integer) - Thứ tự sắp xếp
- timestamps
```

## Testing

### Test Scenarios:

1. **Load Sources on Modal Open**
   - Mở modal tạo/sửa customer
   - Dropdown "Nguồn" hiển thị danh sách từ settings
   - Có option "Chọn" ở đầu

2. **Create Customer with Source**
   - Chọn source từ dropdown
   - Save customer
   - Source được lưu vào database

3. **Edit Customer with Existing Source**
   - Mở modal edit customer có source
   - Dropdown pre-select đúng source
   - Có thể đổi sang source khác

4. **Empty Sources List**
   - Nếu chưa có sources trong settings
   - Dropdown chỉ có option "Chọn"
   - Vẫn có thể save customer (source nullable)

5. **Add New Source**
   - Vào Settings → Sources
   - Thêm source mới
   - Quay lại Customer modal
   - Source mới xuất hiện trong dropdown

## Future Enhancements

### 1. Source Analytics
```javascript
// Thống kê khách hàng theo nguồn
GET /api/customers/analytics/by-source

Response:
{
  "facebook": 150,
  "google": 80,
  "referral": 45
}
```

### 2. Source Icons in Dropdown
```vue
<option v-for="source in sources" :key="source.id" :value="source.name">
  <span :style="{ color: source.color }">
    {{ getIcon(source.icon) }} {{ source.name }}
  </span>
</option>
```

### 3. Source-based Automation
- Auto-assign customer to specific staff based on source
- Different follow-up workflows per source
- Source-specific email templates

### 4. Source Performance Tracking
- Conversion rate by source
- Average deal value by source
- Time to close by source

## Notes

- **Backward Compatibility:** Customers đã có source dạng text vẫn hoạt động bình thường
- **Nullable Field:** Source không bắt buộc, có thể để trống
- **Case Sensitive:** Lưu exact name từ dropdown (case-sensitive)
- **No Free Text:** User không thể nhập text tự do, chỉ chọn từ list

## Migration Path (if needed)

Nếu đã có data source dạng text cần migrate:

```sql
-- 1. Tạo sources từ data hiện có
INSERT INTO customer_sources (name, code, created_at, updated_at)
SELECT DISTINCT source, LOWER(REPLACE(source, ' ', '_')), NOW(), NOW()
FROM customers
WHERE source IS NOT NULL AND source != '';

-- 2. Chuẩn hóa data (optional)
UPDATE customers c
JOIN customer_sources cs ON c.source = cs.name
SET c.source = cs.name;
```


## Tổng quan

Thay đổi field "Nguồn khách hàng" từ text input thành dropdown select, lấy dữ liệu từ Customer Settings (Sources).

## Changes Made

### 1. CustomerModal.vue

#### Before (Text Input):
```vue
<input
  v-model="form.source"
  type="text"
  :placeholder="t('customers.source_placeholder')"
  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
/>
```

#### After (Dropdown Select):
```vue
<select
  v-model="form.source"
  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
>
  <option value="">{{ t('common.select') }}</option>
  <option v-for="source in sources" :key="source.id" :value="source.name">
    {{ source.name }}
  </option>
</select>
```

### 2. Script Changes

#### Add sources ref:
```javascript
const branches = ref([]);
const sources = ref([]);  // ← NEW
const loading = ref(false);
```

#### Add loadSources function:
```javascript
const loadSources = async () => {
  try {
    const response = await api.get('/api/customers/settings/sources');
    if (response.data.success) {
      sources.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load sources:', error);
  }
};
```

#### Call loadSources on mount:
```javascript
onMounted(() => {
  if (authStore.isSuperAdmin) {
    loadBranches();
  }
  loadSources();  // ← NEW
});
```

## API Endpoint Used

```http
GET /api/customers/settings/sources
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Facebook",
      "code": "facebook",
      "icon": "facebook",
      "color": "#1877F2",
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-11-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Google",
      "code": "google",
      "icon": "google",
      "color": "#4285F4",
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-11-01T10:00:00.000000Z"
    }
  ]
}
```

## Benefits

### 1. Data Consistency
- Nguồn khách hàng được chuẩn hóa
- Không có lỗi chính tả hoặc format khác nhau
- Dễ dàng filter và báo cáo

### 2. Centralized Management
- Admin có thể quản lý danh sách sources tập trung
- Thêm/sửa/xóa sources từ Settings modal
- Không cần hardcode trong code

### 3. Better UX
- User không cần nhớ hoặc gõ tên nguồn
- Dropdown dễ dàng chọn hơn text input
- Giảm thiểu lỗi nhập liệu

### 4. Scalability
- Dễ dàng thêm sources mới
- Có thể thêm icon, color cho mỗi source
- Có thể thêm metadata (tracking code, cost, etc.)

## Related Features

### Customer Settings Modal
Quản lý 3 loại settings:
1. **Interaction Types** (Loại tương tác)
2. **Interaction Results** (Kết quả tương tác)
3. **Sources** (Nguồn khách hàng) ← Used here

### Database Table: `customer_sources`
```sql
- id (bigint, primary key)
- name (string) - Tên nguồn
- code (string, unique) - Mã nguồn
- icon (string, nullable) - Icon name
- color (string, nullable) - Hex color
- is_active (boolean) - Trạng thái
- sort_order (integer) - Thứ tự sắp xếp
- timestamps
```

## Testing

### Test Scenarios:

1. **Load Sources on Modal Open**
   - Mở modal tạo/sửa customer
   - Dropdown "Nguồn" hiển thị danh sách từ settings
   - Có option "Chọn" ở đầu

2. **Create Customer with Source**
   - Chọn source từ dropdown
   - Save customer
   - Source được lưu vào database

3. **Edit Customer with Existing Source**
   - Mở modal edit customer có source
   - Dropdown pre-select đúng source
   - Có thể đổi sang source khác

4. **Empty Sources List**
   - Nếu chưa có sources trong settings
   - Dropdown chỉ có option "Chọn"
   - Vẫn có thể save customer (source nullable)

5. **Add New Source**
   - Vào Settings → Sources
   - Thêm source mới
   - Quay lại Customer modal
   - Source mới xuất hiện trong dropdown

## Future Enhancements

### 1. Source Analytics
```javascript
// Thống kê khách hàng theo nguồn
GET /api/customers/analytics/by-source

Response:
{
  "facebook": 150,
  "google": 80,
  "referral": 45
}
```

### 2. Source Icons in Dropdown
```vue
<option v-for="source in sources" :key="source.id" :value="source.name">
  <span :style="{ color: source.color }">
    {{ getIcon(source.icon) }} {{ source.name }}
  </span>
</option>
```

### 3. Source-based Automation
- Auto-assign customer to specific staff based on source
- Different follow-up workflows per source
- Source-specific email templates

### 4. Source Performance Tracking
- Conversion rate by source
- Average deal value by source
- Time to close by source

## Notes

- **Backward Compatibility:** Customers đã có source dạng text vẫn hoạt động bình thường
- **Nullable Field:** Source không bắt buộc, có thể để trống
- **Case Sensitive:** Lưu exact name từ dropdown (case-sensitive)
- **No Free Text:** User không thể nhập text tự do, chỉ chọn từ list

## Migration Path (if needed)

Nếu đã có data source dạng text cần migrate:

```sql
-- 1. Tạo sources từ data hiện có
INSERT INTO customer_sources (name, code, created_at, updated_at)
SELECT DISTINCT source, LOWER(REPLACE(source, ' ', '_')), NOW(), NOW()
FROM customers
WHERE source IS NOT NULL AND source != '';

-- 2. Chuẩn hóa data (optional)
UPDATE customers c
JOIN customer_sources cs ON c.source = cs.name
SET c.source = cs.name;
```
















