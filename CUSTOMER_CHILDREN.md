# Customer Children Management

## Tổng quan

Hệ thống quản lý thông tin con của khách hàng, cho phép lưu trữ và quản lý thông tin chi tiết về từng con của khách hàng.

## Cấu trúc Database

### Bảng `customer_children`

```sql
- id (bigint, primary key)
- customer_id (bigint, foreign key → customers.id)
- name (string) - Tên con
- date_of_birth (date, nullable) - Ngày sinh
- gender (enum: male/female/other, nullable) - Giới tính
- school (string, nullable) - Trường học
- grade (string, nullable) - Lớp/Khối
- interests (text, nullable) - Sở thích
- notes (text, nullable) - Ghi chú
- metadata (json, nullable) - Thông tin bổ sung
- timestamps
```

## Relationships

### Customer Model
```php
public function children()
{
    return $this->hasMany(CustomerChild::class);
}
```

### CustomerChild Model
```php
public function customer()
{
    return $this->belongsTo(Customer::class);
}
```

## API Endpoints

### 1. Lấy danh sách children
```http
GET /api/customers/{customerId}/children
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "customer_id": 1,
      "name": "Nguyễn Văn A",
      "date_of_birth": "2015-05-20",
      "age": 8,
      "gender": "male",
      "school": "Trường Tiểu học ABC",
      "grade": "Lớp 3",
      "interests": "Toán, Tiếng Anh",
      "notes": "Học giỏi Toán",
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-11-01T10:00:00.000000Z"
    }
  ]
}
```

### 2. Tạo child mới
```http
POST /api/customers/{customerId}/children
```

**Request Body:**
```json
{
  "name": "Nguyễn Văn A",
  "date_of_birth": "2015-05-20",
  "gender": "male",
  "school": "Trường Tiểu học ABC",
  "grade": "Lớp 3",
  "interests": "Toán, Tiếng Anh",
  "notes": "Học giỏi Toán"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Thêm con thành công",
  "data": { ... }
}
```

### 3. Cập nhật child
```http
PUT /api/customers/{customerId}/children/{childId}
```

**Request Body:** (giống POST)

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật thông tin con thành công",
  "data": { ... }
}
```

### 4. Xóa child
```http
DELETE /api/customers/{customerId}/children/{childId}
```

**Response:**
```json
{
  "success": true,
  "message": "Xóa thông tin con thành công"
}
```

## Frontend Components

### 1. CustomerDetailModal.vue
Modal chính hiển thị thông tin khách hàng với 2 tabs:
- **Tab 1: Thông tin & Con cái**
  - Thông tin cơ bản của khách hàng
  - Danh sách con với card layout
  - Nút thêm/sửa/xóa con
- **Tab 2: Lịch sử tương tác**
  - Timeline tương tác với khách hàng

**Props:**
- `show` (Boolean) - Hiển thị modal
- `customer` (Object) - Thông tin khách hàng

**Events:**
- `@close` - Đóng modal

### 2. CustomerChildModal.vue
Form modal để thêm/sửa thông tin con.

**Props:**
- `show` (Boolean) - Hiển thị modal
- `customer` (Object) - Khách hàng (parent)
- `child` (Object, nullable) - Con cần sửa (null = tạo mới)

**Events:**
- `@close` - Đóng modal
- `@saved` - Sau khi lưu thành công

**Fields:**
- Tên con (required)
- Ngày sinh
- Giới tính (Nam/Nữ/Khác)
- Trường học
- Lớp/Khối
- Sở thích
- Ghi chú

### 3. CustomerInteractionHistory.vue
Component hiển thị lịch sử tương tác (embedded trong CustomerDetailModal).

**Props:**
- `customer` (Object) - Khách hàng
- `embedded` (Boolean) - Chế độ embedded (ẩn nút thêm)

## Permissions

Sử dụng permissions của module Customers:
- `customers.view` - Xem danh sách children
- `customers.create` - Thêm child mới
- `customers.edit` - Sửa thông tin child
- `customers.delete` - Xóa child

## UI/UX Features

### 1. Card Layout cho Children
- Avatar emoji theo giới tính (👦/👧/🧒)
- Hiển thị tuổi tự động tính từ ngày sinh
- Icons cho thông tin (🏫 trường, 📚 lớp, ⭐ sở thích)
- Nút edit/delete inline

### 2. Tab Navigation
- Smooth transition giữa các tabs
- Active state rõ ràng
- Responsive design

### 3. Empty States
- Icon và message khi chưa có children
- Icon và message khi chưa có interactions

## Validation Rules

### Backend (CustomerChildController)
```php
'name' => 'required|string|max:255',
'date_of_birth' => 'nullable|date',
'gender' => 'nullable|in:male,female,other',
'school' => 'nullable|string|max:255',
'grade' => 'nullable|string|max:100',
'interests' => 'nullable|string',
'notes' => 'nullable|string',
'metadata' => 'nullable|array',
```

### Frontend
- Tên con: required
- Các field khác: optional

## Translations

### Vietnamese (language_id: 1)
```
customers.info_and_children = "Thông tin & Con cái"
customers.interaction_history = "Lịch sử tương tác"
customers.basic_info = "Thông tin cơ bản"
customers.children_list = "Danh sách con"
customers.add_child = "Thêm con"
customers.edit_child = "Sửa thông tin con"
customers.no_children = "Chưa có thông tin con"
customers.child_name = "Tên con"
customers.date_of_birth = "Ngày sinh"
customers.gender = "Giới tính"
customers.male = "Nam"
customers.female = "Nữ"
customers.other = "Khác"
customers.school = "Trường học"
customers.grade = "Lớp/Khối"
customers.interests = "Sở thích"
```

### English (language_id: 2)
```
customers.info_and_children = "Info & Children"
customers.interaction_history = "Interaction History"
customers.basic_info = "Basic Information"
customers.children_list = "Children List"
customers.add_child = "Add Child"
customers.edit_child = "Edit Child Info"
customers.no_children = "No children information"
...
```

## Testing

### Test Flow
1. Click vào tên khách hàng trong danh sách
2. Modal mở với 2 tabs
3. Tab 1: Xem thông tin cơ bản + danh sách con
4. Click "Thêm con" → Form modal mở
5. Nhập thông tin → Save
6. Child mới xuất hiện trong danh sách
7. Click edit icon → Form modal mở với data
8. Sửa thông tin → Save
9. Click delete icon → Confirm → Child bị xóa
10. Chuyển sang Tab 2 → Xem lịch sử tương tác

## Notes

- **Age Calculation:** Tuổi được tính tự động từ `date_of_birth` sử dụng Carbon
- **Cascade Delete:** Khi xóa customer, tất cả children cũng bị xóa (ON DELETE CASCADE)
- **Metadata Field:** JSON field để lưu thông tin bổ sung trong tương lai
- **Z-index:** Modal children có z-index cao hơn (z-[60]) để hiển thị trên modal cha (z-50)

## Future Enhancements

1. Upload ảnh cho từng con
2. Theo dõi lịch sử học tập
3. Gắn con với các khóa học
4. Báo cáo tiến độ học tập
5. Nhắc nhở sinh nhật


## Tổng quan

Hệ thống quản lý thông tin con của khách hàng, cho phép lưu trữ và quản lý thông tin chi tiết về từng con của khách hàng.

## Cấu trúc Database

### Bảng `customer_children`

```sql
- id (bigint, primary key)
- customer_id (bigint, foreign key → customers.id)
- name (string) - Tên con
- date_of_birth (date, nullable) - Ngày sinh
- gender (enum: male/female/other, nullable) - Giới tính
- school (string, nullable) - Trường học
- grade (string, nullable) - Lớp/Khối
- interests (text, nullable) - Sở thích
- notes (text, nullable) - Ghi chú
- metadata (json, nullable) - Thông tin bổ sung
- timestamps
```

## Relationships

### Customer Model
```php
public function children()
{
    return $this->hasMany(CustomerChild::class);
}
```

### CustomerChild Model
```php
public function customer()
{
    return $this->belongsTo(Customer::class);
}
```

## API Endpoints

### 1. Lấy danh sách children
```http
GET /api/customers/{customerId}/children
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "customer_id": 1,
      "name": "Nguyễn Văn A",
      "date_of_birth": "2015-05-20",
      "age": 8,
      "gender": "male",
      "school": "Trường Tiểu học ABC",
      "grade": "Lớp 3",
      "interests": "Toán, Tiếng Anh",
      "notes": "Học giỏi Toán",
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-11-01T10:00:00.000000Z"
    }
  ]
}
```

### 2. Tạo child mới
```http
POST /api/customers/{customerId}/children
```

**Request Body:**
```json
{
  "name": "Nguyễn Văn A",
  "date_of_birth": "2015-05-20",
  "gender": "male",
  "school": "Trường Tiểu học ABC",
  "grade": "Lớp 3",
  "interests": "Toán, Tiếng Anh",
  "notes": "Học giỏi Toán"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Thêm con thành công",
  "data": { ... }
}
```

### 3. Cập nhật child
```http
PUT /api/customers/{customerId}/children/{childId}
```

**Request Body:** (giống POST)

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật thông tin con thành công",
  "data": { ... }
}
```

### 4. Xóa child
```http
DELETE /api/customers/{customerId}/children/{childId}
```

**Response:**
```json
{
  "success": true,
  "message": "Xóa thông tin con thành công"
}
```

## Frontend Components

### 1. CustomerDetailModal.vue
Modal chính hiển thị thông tin khách hàng với 2 tabs:
- **Tab 1: Thông tin & Con cái**
  - Thông tin cơ bản của khách hàng
  - Danh sách con với card layout
  - Nút thêm/sửa/xóa con
- **Tab 2: Lịch sử tương tác**
  - Timeline tương tác với khách hàng

**Props:**
- `show` (Boolean) - Hiển thị modal
- `customer` (Object) - Thông tin khách hàng

**Events:**
- `@close` - Đóng modal

### 2. CustomerChildModal.vue
Form modal để thêm/sửa thông tin con.

**Props:**
- `show` (Boolean) - Hiển thị modal
- `customer` (Object) - Khách hàng (parent)
- `child` (Object, nullable) - Con cần sửa (null = tạo mới)

**Events:**
- `@close` - Đóng modal
- `@saved` - Sau khi lưu thành công

**Fields:**
- Tên con (required)
- Ngày sinh
- Giới tính (Nam/Nữ/Khác)
- Trường học
- Lớp/Khối
- Sở thích
- Ghi chú

### 3. CustomerInteractionHistory.vue
Component hiển thị lịch sử tương tác (embedded trong CustomerDetailModal).

**Props:**
- `customer` (Object) - Khách hàng
- `embedded` (Boolean) - Chế độ embedded (ẩn nút thêm)

## Permissions

Sử dụng permissions của module Customers:
- `customers.view` - Xem danh sách children
- `customers.create` - Thêm child mới
- `customers.edit` - Sửa thông tin child
- `customers.delete` - Xóa child

## UI/UX Features

### 1. Card Layout cho Children
- Avatar emoji theo giới tính (👦/👧/🧒)
- Hiển thị tuổi tự động tính từ ngày sinh
- Icons cho thông tin (🏫 trường, 📚 lớp, ⭐ sở thích)
- Nút edit/delete inline

### 2. Tab Navigation
- Smooth transition giữa các tabs
- Active state rõ ràng
- Responsive design

### 3. Empty States
- Icon và message khi chưa có children
- Icon và message khi chưa có interactions

## Validation Rules

### Backend (CustomerChildController)
```php
'name' => 'required|string|max:255',
'date_of_birth' => 'nullable|date',
'gender' => 'nullable|in:male,female,other',
'school' => 'nullable|string|max:255',
'grade' => 'nullable|string|max:100',
'interests' => 'nullable|string',
'notes' => 'nullable|string',
'metadata' => 'nullable|array',
```

### Frontend
- Tên con: required
- Các field khác: optional

## Translations

### Vietnamese (language_id: 1)
```
customers.info_and_children = "Thông tin & Con cái"
customers.interaction_history = "Lịch sử tương tác"
customers.basic_info = "Thông tin cơ bản"
customers.children_list = "Danh sách con"
customers.add_child = "Thêm con"
customers.edit_child = "Sửa thông tin con"
customers.no_children = "Chưa có thông tin con"
customers.child_name = "Tên con"
customers.date_of_birth = "Ngày sinh"
customers.gender = "Giới tính"
customers.male = "Nam"
customers.female = "Nữ"
customers.other = "Khác"
customers.school = "Trường học"
customers.grade = "Lớp/Khối"
customers.interests = "Sở thích"
```

### English (language_id: 2)
```
customers.info_and_children = "Info & Children"
customers.interaction_history = "Interaction History"
customers.basic_info = "Basic Information"
customers.children_list = "Children List"
customers.add_child = "Add Child"
customers.edit_child = "Edit Child Info"
customers.no_children = "No children information"
...
```

## Testing

### Test Flow
1. Click vào tên khách hàng trong danh sách
2. Modal mở với 2 tabs
3. Tab 1: Xem thông tin cơ bản + danh sách con
4. Click "Thêm con" → Form modal mở
5. Nhập thông tin → Save
6. Child mới xuất hiện trong danh sách
7. Click edit icon → Form modal mở với data
8. Sửa thông tin → Save
9. Click delete icon → Confirm → Child bị xóa
10. Chuyển sang Tab 2 → Xem lịch sử tương tác

## Notes

- **Age Calculation:** Tuổi được tính tự động từ `date_of_birth` sử dụng Carbon
- **Cascade Delete:** Khi xóa customer, tất cả children cũng bị xóa (ON DELETE CASCADE)
- **Metadata Field:** JSON field để lưu thông tin bổ sung trong tương lai
- **Z-index:** Modal children có z-index cao hơn (z-[60]) để hiển thị trên modal cha (z-50)

## Future Enhancements

1. Upload ảnh cho từng con
2. Theo dõi lịch sử học tập
3. Gắn con với các khóa học
4. Báo cáo tiến độ học tập
5. Nhắc nhở sinh nhật


## Tổng quan

Hệ thống quản lý thông tin con của khách hàng, cho phép lưu trữ và quản lý thông tin chi tiết về từng con của khách hàng.

## Cấu trúc Database

### Bảng `customer_children`

```sql
- id (bigint, primary key)
- customer_id (bigint, foreign key → customers.id)
- name (string) - Tên con
- date_of_birth (date, nullable) - Ngày sinh
- gender (enum: male/female/other, nullable) - Giới tính
- school (string, nullable) - Trường học
- grade (string, nullable) - Lớp/Khối
- interests (text, nullable) - Sở thích
- notes (text, nullable) - Ghi chú
- metadata (json, nullable) - Thông tin bổ sung
- timestamps
```

## Relationships

### Customer Model
```php
public function children()
{
    return $this->hasMany(CustomerChild::class);
}
```

### CustomerChild Model
```php
public function customer()
{
    return $this->belongsTo(Customer::class);
}
```

## API Endpoints

### 1. Lấy danh sách children
```http
GET /api/customers/{customerId}/children
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "customer_id": 1,
      "name": "Nguyễn Văn A",
      "date_of_birth": "2015-05-20",
      "age": 8,
      "gender": "male",
      "school": "Trường Tiểu học ABC",
      "grade": "Lớp 3",
      "interests": "Toán, Tiếng Anh",
      "notes": "Học giỏi Toán",
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-11-01T10:00:00.000000Z"
    }
  ]
}
```

### 2. Tạo child mới
```http
POST /api/customers/{customerId}/children
```

**Request Body:**
```json
{
  "name": "Nguyễn Văn A",
  "date_of_birth": "2015-05-20",
  "gender": "male",
  "school": "Trường Tiểu học ABC",
  "grade": "Lớp 3",
  "interests": "Toán, Tiếng Anh",
  "notes": "Học giỏi Toán"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Thêm con thành công",
  "data": { ... }
}
```

### 3. Cập nhật child
```http
PUT /api/customers/{customerId}/children/{childId}
```

**Request Body:** (giống POST)

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật thông tin con thành công",
  "data": { ... }
}
```

### 4. Xóa child
```http
DELETE /api/customers/{customerId}/children/{childId}
```

**Response:**
```json
{
  "success": true,
  "message": "Xóa thông tin con thành công"
}
```

## Frontend Components

### 1. CustomerDetailModal.vue
Modal chính hiển thị thông tin khách hàng với 2 tabs:
- **Tab 1: Thông tin & Con cái**
  - Thông tin cơ bản của khách hàng
  - Danh sách con với card layout
  - Nút thêm/sửa/xóa con
- **Tab 2: Lịch sử tương tác**
  - Timeline tương tác với khách hàng

**Props:**
- `show` (Boolean) - Hiển thị modal
- `customer` (Object) - Thông tin khách hàng

**Events:**
- `@close` - Đóng modal

### 2. CustomerChildModal.vue
Form modal để thêm/sửa thông tin con.

**Props:**
- `show` (Boolean) - Hiển thị modal
- `customer` (Object) - Khách hàng (parent)
- `child` (Object, nullable) - Con cần sửa (null = tạo mới)

**Events:**
- `@close` - Đóng modal
- `@saved` - Sau khi lưu thành công

**Fields:**
- Tên con (required)
- Ngày sinh
- Giới tính (Nam/Nữ/Khác)
- Trường học
- Lớp/Khối
- Sở thích
- Ghi chú

### 3. CustomerInteractionHistory.vue
Component hiển thị lịch sử tương tác (embedded trong CustomerDetailModal).

**Props:**
- `customer` (Object) - Khách hàng
- `embedded` (Boolean) - Chế độ embedded (ẩn nút thêm)

## Permissions

Sử dụng permissions của module Customers:
- `customers.view` - Xem danh sách children
- `customers.create` - Thêm child mới
- `customers.edit` - Sửa thông tin child
- `customers.delete` - Xóa child

## UI/UX Features

### 1. Card Layout cho Children
- Avatar emoji theo giới tính (👦/👧/🧒)
- Hiển thị tuổi tự động tính từ ngày sinh
- Icons cho thông tin (🏫 trường, 📚 lớp, ⭐ sở thích)
- Nút edit/delete inline

### 2. Tab Navigation
- Smooth transition giữa các tabs
- Active state rõ ràng
- Responsive design

### 3. Empty States
- Icon và message khi chưa có children
- Icon và message khi chưa có interactions

## Validation Rules

### Backend (CustomerChildController)
```php
'name' => 'required|string|max:255',
'date_of_birth' => 'nullable|date',
'gender' => 'nullable|in:male,female,other',
'school' => 'nullable|string|max:255',
'grade' => 'nullable|string|max:100',
'interests' => 'nullable|string',
'notes' => 'nullable|string',
'metadata' => 'nullable|array',
```

### Frontend
- Tên con: required
- Các field khác: optional

## Translations

### Vietnamese (language_id: 1)
```
customers.info_and_children = "Thông tin & Con cái"
customers.interaction_history = "Lịch sử tương tác"
customers.basic_info = "Thông tin cơ bản"
customers.children_list = "Danh sách con"
customers.add_child = "Thêm con"
customers.edit_child = "Sửa thông tin con"
customers.no_children = "Chưa có thông tin con"
customers.child_name = "Tên con"
customers.date_of_birth = "Ngày sinh"
customers.gender = "Giới tính"
customers.male = "Nam"
customers.female = "Nữ"
customers.other = "Khác"
customers.school = "Trường học"
customers.grade = "Lớp/Khối"
customers.interests = "Sở thích"
```

### English (language_id: 2)
```
customers.info_and_children = "Info & Children"
customers.interaction_history = "Interaction History"
customers.basic_info = "Basic Information"
customers.children_list = "Children List"
customers.add_child = "Add Child"
customers.edit_child = "Edit Child Info"
customers.no_children = "No children information"
...
```

## Testing

### Test Flow
1. Click vào tên khách hàng trong danh sách
2. Modal mở với 2 tabs
3. Tab 1: Xem thông tin cơ bản + danh sách con
4. Click "Thêm con" → Form modal mở
5. Nhập thông tin → Save
6. Child mới xuất hiện trong danh sách
7. Click edit icon → Form modal mở với data
8. Sửa thông tin → Save
9. Click delete icon → Confirm → Child bị xóa
10. Chuyển sang Tab 2 → Xem lịch sử tương tác

## Notes

- **Age Calculation:** Tuổi được tính tự động từ `date_of_birth` sử dụng Carbon
- **Cascade Delete:** Khi xóa customer, tất cả children cũng bị xóa (ON DELETE CASCADE)
- **Metadata Field:** JSON field để lưu thông tin bổ sung trong tương lai
- **Z-index:** Modal children có z-index cao hơn (z-[60]) để hiển thị trên modal cha (z-50)

## Future Enhancements

1. Upload ảnh cho từng con
2. Theo dõi lịch sử học tập
3. Gắn con với các khóa học
4. Báo cáo tiến độ học tập
5. Nhắc nhở sinh nhật
















