# 🚀 Hướng Dẫn Nhanh - Module Lịch Test Đầu Vào

## ✅ Đã Hoàn Thành

### Backend (100% Complete)

1. **Database**
   - ✅ Thêm cột `test_result` (JSON) vào bảng `calendar_events`
   - ✅ Category mới: `placement_test` (màu Cyan #06B6D4, icon 📝)

2. **Models**
   - ✅ `Customer` → `placementTestEvent()` relationship
   - ✅ `CustomerChild` → `placementTestEvent()` relationship
   - ✅ `CalendarEvent` → `has_test_result` attribute

3. **API Endpoints**
   ```
   POST   /api/calendar/placement-test/customer/{customerId}
   POST   /api/calendar/placement-test/child/{childId}
   PUT    /api/calendar/placement-test/{eventId}/result
   ```

4. **Translations**
   - ✅ Vietnamese & English translations seeded
   - ✅ Group: `calendar`

---

## 📋 Cách Sử Dụng

### 1. Đặt Lịch Test cho Customer

```bash
POST /api/calendar/placement-test/customer/1
Authorization: Bearer {token}

{
  "test_date": "2025-11-10 14:00:00",
  "duration_minutes": 60,
  "location": "Phòng A1",
  "notes": "Test đầu vào cho khách hàng",
  "assigned_to": 5
}
```

### 2. Đặt Lịch Test cho CustomerChild

```bash
POST /api/calendar/placement-test/child/1
Authorization: Bearer {token}

{
  "test_date": "2025-11-10 14:00:00",
  "duration_minutes": 90,
  "location": "Phòng B2",
  "notes": "Test đầu vào cho học viên",
  "assigned_to": 7
}
```

### 3. Cập Nhật Kết Quả Test

```bash
PUT /api/calendar/placement-test/123/result
Authorization: Bearer {token}

{
  "score": 85.5,
  "level": "Intermediate",
  "notes": "Học viên có nền tảng tốt",
  "recommendations": "Nên học lớp Intermediate 2"
}
```

---

## 🎨 Frontend (Cần Implement)

### Component 1: PlacementTestScheduler.vue

**Vị trí:** `resources/js/components/PlacementTestScheduler.vue`

**Chức năng:**
- Form đặt lịch test
- Chọn ngày giờ, thời lượng, địa điểm, giáo viên
- Hiển thị lịch test hiện tại

**Sử dụng:**
```vue
<PlacementTestScheduler
  type="customer"
  :entity-id="customer.id"
  :existing-test="customer.placement_test_event"
  @scheduled="handleScheduled"
/>
```

### Component 2: PlacementTestResult.vue

**Vị trí:** `resources/js/components/PlacementTestResult.vue`

**Chức năng:**
- Form cập nhật kết quả test
- Nhập điểm, trình độ, ghi chú, đề xuất
- Hiển thị người đánh giá và thời gian

**Sử dụng:**
```vue
<PlacementTestResult
  :event="placementTestEvent"
  @updated="handleResultUpdated"
/>
```

### Hiển Thị Trạng Thái trong Customer Detail

```vue
<template>
  <!-- Chưa có lịch test -->
  <button v-if="!hasPlacementTest" @click="scheduleTest">
    <i class="fas fa-calendar-plus"></i>
    {{ $t('schedule_placement_test') }}
  </button>

  <!-- Đã có lịch test, chưa có kết quả -->
  <div v-else-if="!hasTestResult" class="alert alert-info">
    <i class="fas fa-clock"></i>
    <strong>{{ $t('placement_test_scheduled') }}</strong>
    <p>{{ formatDate(placementTest.start_date) }}</p>
    <button @click="updateResult">{{ $t('update_test_result') }}</button>
  </div>

  <!-- Đã có kết quả -->
  <div v-else class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <strong>{{ $t('placement_test_completed') }}</strong>
    <p><strong>{{ $t('test_score') }}:</strong> {{ testResult.score }}</p>
    <p><strong>{{ $t('test_level') }}:</strong> {{ testResult.level }}</p>
    <p><strong>{{ $t('test_recommendations') }}:</strong> {{ testResult.recommendations }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  customer: Object
})

const placementTest = computed(() => props.customer.placement_test_event)
const hasPlacementTest = computed(() => !!placementTest.value)
const hasTestResult = computed(() => !!placementTest.value?.test_result)
const testResult = computed(() => placementTest.value?.test_result)
</script>
```

---

## 🔑 Key Features

### 1. Phân Quyền Theo Phòng Ban
- Lịch hẹn Customer (follow-up) → `branch_id = 1` (Kinh doanh)
- Lịch test → `branch_id = 2` (Học thuật)

### 2. Trạng Thái Tự Động
- Khi tạo lịch test → `status = 'pending'`
- Khi cập nhật kết quả → `status = 'completed'`

### 3. Hiển Thị Trên Calendar
- Màu: Cyan (#06B6D4)
- Icon: 📝
- Badge: "✅ Đã có kết quả" nếu có test_result

### 4. Metadata Chi Tiết
```json
{
  "customer_id": 10,
  "customer_name": "Nguyễn Văn A",
  "customer_phone": "0901234567",
  "test_type": "customer" // hoặc "child"
}
```

---

## 📊 Database Structure

### CalendarEvent với Placement Test

```sql
SELECT 
  id,
  title,
  category,
  status,
  start_date,
  branch_id,
  test_result,
  eventable_type,
  eventable_id
FROM calendar_events
WHERE category = 'placement_test';
```

**Kết quả mẫu:**
```
id  | title                    | status    | test_result
----|--------------------------|-----------|-------------
123 | Test đầu vào: Nguyễn A   | pending   | NULL
124 | Test đầu vào: Trần B     | completed | {"score": 85.5, "level": "Intermediate", ...}
```

---

## 🌐 Translations Available

### Vietnamese
- `placement_test` → "Lịch Test Đầu Vào"
- `schedule_placement_test` → "Đặt Lịch Test"
- `placement_test_scheduled` → "Đã Đặt Lịch Test"
- `placement_test_completed` → "Đã Hoàn Thành Test"
- `test_score` → "Điểm Số"
- `test_level` → "Trình Độ"
- `test_recommendations` → "Đề Xuất"
- `update_test_result` → "Cập Nhật Kết Quả"

### English
- `placement_test` → "Placement Test"
- `schedule_placement_test` → "Schedule Test"
- `placement_test_scheduled` → "Test Scheduled"
- `placement_test_completed` → "Test Completed"
- `test_score` → "Score"
- `test_level` → "Level"
- `test_recommendations` → "Recommendations"
- `update_test_result` → "Update Result"

---

## 🧪 Testing Checklist

- [ ] Tạo lịch test cho Customer qua API
- [ ] Tạo lịch test cho Child qua API
- [ ] Cập nhật kết quả test qua API
- [ ] Kiểm tra lịch test hiển thị trên Calendar (màu Cyan)
- [ ] Kiểm tra badge trạng thái trong Customer Detail
- [ ] Kiểm tra translations (vi/en)
- [ ] Kiểm tra phân quyền theo branch (branch_id = 2)
- [ ] Kiểm tra status tự động chuyển sang 'completed'

---

## 📝 Notes

1. **Không tạo lịch mới** - Module này tích hợp với Calendar hiện có
2. **Branch ID tạm thời** - branch_id = 2 (Học thuật) được hard-code, sẽ được thay thế khi có module Sơ đồ tổ chức
3. **Polymorphic Relationship** - Một Customer/Child chỉ có một placement_test_event (latest)
4. **Auto Reminder** - Mặc định nhắc trước 60 phút

---

## 🎯 Next Steps

1. **Frontend Implementation**
   - Tạo PlacementTestScheduler.vue
   - Tạo PlacementTestResult.vue
   - Tích hợp vào Customer Detail
   - Tích hợp vào CustomerChild Detail

2. **Calendar UI Enhancement**
   - Hiển thị badge "✅ Đã có kết quả"
   - Click event → Hiển thị kết quả test
   - Filter theo category = 'placement_test'

3. **Testing**
   - Unit tests cho API endpoints
   - Integration tests cho Calendar
   - E2E tests cho full flow

---

**🎉 Backend đã hoàn thiện 100%! Giờ chỉ cần implement Frontend để sử dụng!** 🚀

