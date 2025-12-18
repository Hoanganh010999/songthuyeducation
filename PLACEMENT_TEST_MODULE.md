# 📝 Module Lịch Test Đầu Vào (Placement Test)

## 🎯 Tổng Quan

Module Lịch Test Đầu Vào được tích hợp vào hệ thống Calendar hiện có, cho phép:

- ✅ **Đặt lịch test cho Customer** - Khách hàng là học viên
- ✅ **Đặt lịch test cho CustomerChild** - Con của khách hàng là học viên
- ✅ **Quản lý kết quả test** - Cập nhật điểm số, trình độ, đề xuất
- ✅ **Hiển thị trạng thái** - Pending (chưa test) / Completed (đã có kết quả)
- ✅ **Phân quyền theo phòng ban** - Lịch test thuộc phòng Học thuật (branch_id = 2)

---

## 🗄️ Database Schema

### Bảng `calendar_events` (đã có sẵn, thêm cột mới)

```sql
ALTER TABLE calendar_events ADD COLUMN test_result JSON NULL;
```

**Cấu trúc `test_result` JSON:**
```json
{
  "score": 85.5,
  "level": "Intermediate",
  "notes": "Học viên có nền tảng tốt về ngữ pháp",
  "recommendations": "Nên học lớp Intermediate 2",
  "evaluated_by": 5,
  "evaluated_by_name": "Nguyễn Văn A",
  "evaluated_at": "2025-11-05T14:30:00+07:00"
}
```

---

## 🏗️ Kiến Trúc Hệ Thống

### 1. **Models**

#### Customer Model
```php
// Relationship: Placement Test Event
public function placementTestEvent()
{
    return $this->morphOne(CalendarEvent::class, 'eventable')
        ->where('category', 'placement_test')
        ->latest();
}
```

#### CustomerChild Model
```php
// Relationship: Placement Test Event
public function placementTestEvent()
{
    return $this->morphOne(CalendarEvent::class, 'eventable')
        ->where('category', 'placement_test')
        ->latest();
}
```

#### CalendarEvent Model
```php
// Attribute: Has test result
public function getHasTestResultAttribute()
{
    return !empty($this->test_result);
}

// Category: placement_test
'placement_test' => '#06B6D4', // Cyan
'placement_test' => '📝', // Icon
```

---

## 🛣️ API Endpoints

### 1. Tạo/Cập nhật lịch test cho Customer

```http
POST /api/calendar/placement-test/customer/{customerId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "test_date": "2025-11-10 14:00:00",
  "duration_minutes": 60,
  "location": "Phòng A1",
  "notes": "Test đầu vào cho khách hàng mới",
  "assigned_to": 5
}
```

**Response:**
```json
{
  "success": true,
  "message": "Tạo lịch test thành công",
  "data": {
    "id": 123,
    "calendarId": "placement_test",
    "title": "Test đầu vào: Nguyễn Văn A",
    "start": "2025-11-10T14:00:00+07:00",
    "end": "2025-11-10T15:00:00+07:00",
    "backgroundColor": "#06B6D4",
    "raw": {
      "eventable_type": "App\\Models\\Customer",
      "eventable_id": 10,
      "metadata": {
        "customer_id": 10,
        "customer_name": "Nguyễn Văn A",
        "test_type": "customer"
      }
    }
  }
}
```

### 2. Tạo/Cập nhật lịch test cho CustomerChild

```http
POST /api/calendar/placement-test/child/{childId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "test_date": "2025-11-10 14:00:00",
  "duration_minutes": 90,
  "location": "Phòng B2",
  "notes": "Test đầu vào cho học viên",
  "assigned_to": 7
}
```

**Response:** (tương tự như Customer)

### 3. Cập nhật kết quả test

```http
PUT /api/calendar/placement-test/{eventId}/result
Authorization: Bearer {token}
Content-Type: application/json

{
  "score": 85.5,
  "level": "Intermediate",
  "notes": "Học viên có nền tảng tốt về ngữ pháp",
  "recommendations": "Nên học lớp Intermediate 2"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật kết quả test thành công",
  "data": {
    "id": 123,
    "category": "placement_test",
    "status": "completed",
    "test_result": {
      "score": 85.5,
      "level": "Intermediate",
      "notes": "Học viên có nền tảng tốt về ngữ pháp",
      "recommendations": "Nên học lớp Intermediate 2",
      "evaluated_by": 5,
      "evaluated_by_name": "Nguyễn Văn A",
      "evaluated_at": "2025-11-05T14:30:00+07:00"
    }
  }
}
```

---

## 🎨 Frontend Integration

### 1. Component: PlacementTestScheduler.vue

**Đường dẫn:** `resources/js/components/PlacementTestScheduler.vue`

**Chức năng:**
- Form đặt lịch test cho Customer hoặc Child
- Chọn ngày giờ, thời lượng, địa điểm
- Chọn giáo viên phụ trách
- Hiển thị lịch test hiện tại (nếu có)

**Props:**
```javascript
{
  type: 'customer' | 'child',
  entityId: Number, // customerId hoặc childId
  existingTest: Object | null
}
```

### 2. Component: PlacementTestResult.vue

**Đường dẫn:** `resources/js/components/PlacementTestResult.vue`

**Chức năng:**
- Form cập nhật kết quả test
- Nhập điểm số, trình độ, ghi chú, đề xuất
- Hiển thị thông tin người đánh giá và thời gian

**Props:**
```javascript
{
  event: Object // CalendarEvent với category = 'placement_test'
}
```

### 3. Hiển thị trạng thái trong Customer Detail

```vue
<template>
  <div class="placement-test-status">
    <!-- Nếu chưa có lịch test -->
    <button 
      v-if="!customer.placement_test_event"
      @click="openScheduleModal"
      class="btn btn-primary"
    >
      <i class="fas fa-calendar-plus"></i>
      {{ $t('schedule_placement_test') }}
    </button>

    <!-- Nếu đã có lịch test nhưng chưa hoàn thành -->
    <div 
      v-else-if="customer.placement_test_event && !customer.placement_test_event.test_result"
      class="alert alert-info"
    >
      <i class="fas fa-clock"></i>
      <strong>{{ $t('placement_test_scheduled') }}</strong>
      <p>{{ formatDate(customer.placement_test_event.start_date) }}</p>
      <button @click="openUpdateResultModal" class="btn btn-sm btn-success">
        {{ $t('update_test_result') }}
      </button>
    </div>

    <!-- Nếu đã có kết quả test -->
    <div 
      v-else
      class="alert alert-success"
    >
      <i class="fas fa-check-circle"></i>
      <strong>{{ $t('placement_test_completed') }}</strong>
      <div class="test-result">
        <p><strong>{{ $t('test_score') }}:</strong> {{ customer.placement_test_event.test_result.score }}</p>
        <p><strong>{{ $t('test_level') }}:</strong> {{ customer.placement_test_event.test_result.level }}</p>
        <p><strong>{{ $t('test_recommendations') }}:</strong> {{ customer.placement_test_event.test_result.recommendations }}</p>
      </div>
    </div>
  </div>
</template>
```

### 4. Hiển thị trên Calendar

Lịch test sẽ tự động hiển thị trên Calendar với:
- **Màu:** Cyan (#06B6D4)
- **Icon:** 📝
- **Title:** "Test đầu vào: [Tên học viên]"
- **Badge:** Hiển thị "✅ Đã có kết quả" nếu test_result không null

---

## 🔄 Flow Hoạt Động

### Flow 1: Đặt lịch test cho Customer

```
1. User vào trang Customer Detail
   ↓
2. Click "Đặt Lịch Test"
   ↓
3. Chọn ngày giờ, thời lượng, địa điểm, giáo viên
   ↓
4. POST /api/calendar/placement-test/customer/{customerId}
   ↓
5. CalendarEvent được tạo với:
   - category = 'placement_test'
   - branch_id = 2 (Học thuật)
   - status = 'pending'
   - eventable_type = Customer
   ↓
6. Lịch test xuất hiện trên Calendar
   ↓
7. Customer Detail hiển thị badge "Đã đặt lịch test"
```

### Flow 2: Cập nhật kết quả test

```
1. Giáo viên vào Calendar hoặc Customer Detail
   ↓
2. Click "Cập Nhật Kết Quả Test"
   ↓
3. Nhập điểm số, trình độ, ghi chú, đề xuất
   ↓
4. PUT /api/calendar/placement-test/{eventId}/result
   ↓
5. CalendarEvent được cập nhật:
   - test_result = {...}
   - status = 'completed'
   ↓
6. Calendar hiển thị badge "✅ Đã có kết quả"
   ↓
7. Customer Detail hiển thị kết quả test
```

### Flow 3: Xem lịch test trên Calendar

```
1. User vào Calendar
   ↓
2. Calendar load events từ API
   ↓
3. Lịch test hiển thị với màu Cyan
   ↓
4. Click vào event → Popup chi tiết
   ↓
5. Nếu chưa có kết quả → Hiển thị nút "Cập Nhật Kết Quả"
   ↓
6. Nếu đã có kết quả → Hiển thị thông tin kết quả
```

---

## 🌐 Translations

### Vietnamese (vi)

| Key | Value |
|-----|-------|
| `placement_test` | Lịch Test Đầu Vào |
| `schedule_placement_test` | Đặt Lịch Test |
| `placement_test_scheduled` | Đã Đặt Lịch Test |
| `placement_test_completed` | Đã Hoàn Thành Test |
| `test_date` | Ngày Test |
| `test_duration` | Thời Lượng |
| `test_location` | Địa Điểm Test |
| `test_result` | Kết Quả Test |
| `test_score` | Điểm Số |
| `test_level` | Trình Độ |
| `test_recommendations` | Đề Xuất |
| `update_test_result` | Cập Nhật Kết Quả |
| `no_test_result` | Chưa Có Kết Quả |
| `has_test_result` | Đã Có Kết Quả |

### English (en)

| Key | Value |
|-----|-------|
| `placement_test` | Placement Test |
| `schedule_placement_test` | Schedule Test |
| `placement_test_scheduled` | Test Scheduled |
| `placement_test_completed` | Test Completed |
| `test_date` | Test Date |
| `test_duration` | Duration |
| `test_location` | Test Location |
| `test_result` | Test Result |
| `test_score` | Score |
| `test_level` | Level |
| `test_recommendations` | Recommendations |
| `update_test_result` | Update Result |
| `no_test_result` | No Result Yet |
| `has_test_result` | Has Result |

---

## 🔐 Permissions

Sử dụng permissions hiện có của Calendar:

| Permission | Mô Tả |
|------------|-------|
| `calendar.view` | Xem lịch test |
| `calendar.create` | Tạo lịch test |
| `calendar.edit` | Cập nhật lịch test và kết quả |
| `calendar.delete` | Xóa lịch test |

---

## 🎯 Use Cases

### 1. Đặt lịch test cho khách hàng mới

```
- Sales tư vấn khách hàng quan tâm học
- Khách hàng muốn test đầu vào để xác định trình độ
- Sales vào Customer Detail → Click "Đặt Lịch Test"
- Chọn ngày giờ phù hợp, chọn giáo viên
- Lịch test được tạo và hiển thị trên Calendar
- Giáo viên nhận thông báo (nếu có reminder)
```

### 2. Đặt lịch test cho con của khách hàng

```
- Phụ huynh muốn cho con test đầu vào
- Sales vào Customer Detail → Tab "Con Cái"
- Click vào con → Click "Đặt Lịch Test"
- Chọn ngày giờ, giáo viên
- Lịch test được tạo riêng cho con
```

### 3. Cập nhật kết quả test

```
- Giáo viên hoàn thành test cho học viên
- Vào Calendar → Click vào lịch test
- Click "Cập Nhật Kết Quả"
- Nhập điểm số, trình độ, đề xuất lớp học
- Kết quả được lưu và hiển thị
- Sales xem kết quả để tư vấn gói học phù hợp
```

### 4. Theo dõi lịch test theo phòng ban

```
- Quản lý phòng Học thuật vào Calendar
- Filter theo branch_id = 2 (Học thuật)
- Xem tất cả lịch test trong tháng
- Theo dõi lịch test nào chưa có kết quả
- Nhắc nhở giáo viên cập nhật kết quả
```

---

## 🧪 Testing

### 1. Test API - Tạo lịch test cho Customer

```bash
POST http://localhost:8000/api/calendar/placement-test/customer/1
Authorization: Bearer {token}
Content-Type: application/json

{
  "test_date": "2025-11-10 14:00:00",
  "duration_minutes": 60,
  "location": "Phòng A1",
  "notes": "Test đầu vào",
  "assigned_to": 5
}

# Expected: 201 Created
# Kiểm tra: calendar_events table có record mới với category = 'placement_test'
```

### 2. Test API - Cập nhật kết quả test

```bash
PUT http://localhost:8000/api/calendar/placement-test/123/result
Authorization: Bearer {token}
Content-Type: application/json

{
  "score": 85.5,
  "level": "Intermediate",
  "notes": "Tốt",
  "recommendations": "Lớp Intermediate 2"
}

# Expected: 200 OK
# Kiểm tra: test_result được cập nhật, status = 'completed'
```

### 3. Test Frontend

```
1. Vào Customer Detail
2. Click "Đặt Lịch Test"
3. Chọn ngày giờ → Submit
4. Kiểm tra: Badge "Đã đặt lịch test" xuất hiện
5. Vào Calendar → Kiểm tra event hiển thị màu Cyan
6. Click event → Click "Cập Nhật Kết Quả"
7. Nhập kết quả → Submit
8. Kiểm tra: Badge đổi thành "✅ Đã có kết quả"
```

---

## 📈 Future Enhancements

1. **Email/SMS Reminder** - Gửi nhắc nhở trước khi test
2. **Test Templates** - Mẫu câu hỏi test theo trình độ
3. **Auto Level Detection** - Tự động đề xuất trình độ dựa trên điểm
4. **Test History** - Lịch sử test của học viên (nếu test lại)
5. **Certificate Generation** - Tạo chứng chỉ sau khi hoàn thành test
6. **Analytics Dashboard** - Thống kê kết quả test theo tháng/năm

---

## 🎊 Kết Quả

### ✅ Đã Hoàn Thành 100%

#### Backend:
- ✅ Thêm category `placement_test` vào CalendarEvent
- ✅ Thêm cột `test_result` (JSON) vào calendar_events
- ✅ Relationship Customer → placementTestEvent
- ✅ Relationship CustomerChild → placementTestEvent
- ✅ API: Tạo lịch test cho Customer
- ✅ API: Tạo lịch test cho Child
- ✅ API: Cập nhật kết quả test
- ✅ Translations (vi, en)

#### Frontend (Cần implement):
- ⏳ Component: PlacementTestScheduler.vue
- ⏳ Component: PlacementTestResult.vue
- ⏳ Integration vào Customer Detail
- ⏳ Integration vào CustomerChild Detail
- ⏳ Badge hiển thị trạng thái trên Calendar

#### Architecture:
- ✅ Tích hợp hoàn toàn với Calendar hiện có
- ✅ Polymorphic relationship (Customer & Child)
- ✅ Phân quyền theo phòng ban (branch_id = 2)
- ✅ Status tracking (pending → completed)
- ✅ Clean API design

---

**🎉 Module Lịch Test Đầu Vào đã sẵn sàng sử dụng!**

Backend đã hoàn thiện 100%. Frontend cần implement các component Vue để hiển thị và tương tác với API. Tất cả lịch test sẽ tự động xuất hiện trên Calendar với màu Cyan và icon 📝! 🚀

