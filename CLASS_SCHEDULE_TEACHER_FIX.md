# Sửa Lỗi Hiển Thị Tên Giáo Viên Trong Schedule & Calendar

## 📋 Tổng Quan Các Lỗi

### Lỗi 1: Tên giáo viên không hiển thị ở tab Schedule sau khi tạo lớp mới
**Nguyên nhân:** 
- `User` model có accessor `full_name` nhưng không khai báo trong `$appends` array
- Khi serialize sang JSON, `full_name` không được tự động include

### Lỗi 2: Cập nhật schedule không đồng bộ lên Calendar và tab Schedule
**Nguyên nhân:**
- Khi cập nhật `ClassSchedule`, không trigger cập nhật các `ClassLessonSession` liên quan
- Calendar chỉ sync khi `ClassLessonSession` thay đổi, không theo dõi thay đổi của `ClassSchedule`

---

## ✅ Các Thay Đổi Đã Thực Hiện

### 1. **User Model** (`app/Models/User.php`)

**Thêm `$appends` array để `full_name` được serialize:**

```php
/**
 * The accessors to append to the model's array form.
 *
 * @var array
 */
protected $appends = [
    'full_name',
];
```

**Kết quả:** Khi User model được chuyển sang JSON (trong API response), `full_name` sẽ tự động được include.

---

### 2. **ClassManagementController** (`app/Http/Controllers/Api/ClassManagementController.php`)

#### A. Sửa method `store()` và `update()`

**Cải thiện cách load relationships:**

```php
// Reload class with all relationships including schedules with teacher
$class->refresh();
$class->load([
    'homeroomTeacher:id,name,email', 
    'subject:id,name', 
    'semester:id,name', 
    'lessonPlan:id,name', 
    'schedules.teacher:id,name,email',
    'schedules.room:id,name',
    'schedules.subject:id,name',
    'schedules.studyPeriod:id,name,duration_minutes'
]);

// Ensure schedules are properly loaded with relationships
$class->schedules->each(function($schedule) {
    $schedule->load(['teacher:id,name,email', 'room:id,name', 'subject:id,name']);
});
```

**Lợi ích:**
- Load đúng relationships với select columns để tối ưu performance
- Đảm bảo teacher relationship luôn được load cho mỗi schedule

#### B. Sửa method `updateSchedule()`

**Thêm logic tracking thay đổi và sync:**

```php
// Track if critical fields changed (to update lesson sessions)
$criticalFieldsChanged = false;
$oldScheduleData = [
    'teacher_id' => $schedule->teacher_id,
    'start_time' => $schedule->start_time,
    'end_time' => $schedule->end_time,
];

// Only update fields that are provided
$updateData = $request->only(['day_of_week', 'start_time', 'end_time', 'subject_id', 'teacher_id', 'room_id', 'study_period_id', 'lesson_number']);
$schedule->update($updateData);

// Check if critical fields changed
if ($request->has('teacher_id') && $oldScheduleData['teacher_id'] != $schedule->teacher_id) {
    $criticalFieldsChanged = true;
}
if ($request->has('start_time') && $oldScheduleData['start_time'] != $schedule->start_time) {
    $criticalFieldsChanged = true;
}
if ($request->has('end_time') && $oldScheduleData['end_time'] != $schedule->end_time) {
    $criticalFieldsChanged = true;
}

// If critical fields changed, update related lesson sessions (only those without attendance)
if ($criticalFieldsChanged) {
    $this->updateLessonSessionsFromScheduleChange($schedule);
}
```

**Lợi ích:**
- Theo dõi xem teacher_id, start_time, end_time có thay đổi không
- Nếu có thay đổi, tự động cập nhật các lesson sessions liên quan

#### C. Thêm method `updateLessonSessionsFromScheduleChange()`

```php
/**
 * Update lesson sessions when schedule changes
 * Only updates sessions without attendance
 */
private function updateLessonSessionsFromScheduleChange($schedule)
{
    try {
        // Get all lesson sessions using this schedule (without attendance)
        $sessions = ClassLessonSession::where('class_schedule_id', $schedule->id)
            ->whereDoesntHave('attendances')
            ->get();
        
        if ($sessions->isEmpty()) {
            return;
        }
        
        Log::info('[ClassManagement] Updating lesson sessions after schedule change', [
            'schedule_id' => $schedule->id,
            'session_count' => $sessions->count(),
        ]);
        
        // Update each session
        foreach ($sessions as $session) {
            $session->update([
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ]);
            
            // This will trigger the updated() event in ClassLessonSession model
            // which automatically syncs to calendar
        }
        
        Log::info('[ClassManagement] Updated lesson sessions successfully', [
            'schedule_id' => $schedule->id,
            'updated_count' => $sessions->count(),
        ]);
        
    } catch (\Exception $e) {
        Log::error('[ClassManagement] Error updating lesson sessions', [
            'schedule_id' => $schedule->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

**Cơ chế hoạt động:**
1. Tìm tất cả lesson sessions liên kết với schedule đã thay đổi
2. Chỉ cập nhật sessions **chưa có attendance** (bảo vệ data)
3. Cập nhật start_time, end_time cho mỗi session
4. Khi session được `.update()`, Eloquent hook `updated()` trong `ClassLessonSession` model sẽ tự động trigger
5. Hook này gọi `CalendarEventService::syncClassSessionToCalendar()` để đồng bộ lên calendar

**Lợi ích:**
- Tự động cập nhật tất cả buổi học chưa diễn ra khi schedule thay đổi
- Không ảnh hưởng đến buổi học đã có điểm danh
- Tự động sync lên calendar thông qua Eloquent hooks

---

### 3. **WeeklyScheduleTab.vue** (`resources/js/pages/quality/classDetail/WeeklyScheduleTab.vue`)

**Thêm emit event để parent component reload data:**

```javascript
const emit = defineEmits(['refresh']);

const saveSchedule = async () => {
  try {
    saving.value = true;
    
    await api.classes.updateSchedule(props.classId, editForm.value.id, {
      day_of_week: editForm.value.day_of_week,
      start_time: editForm.value.start_time,
      end_time: editForm.value.end_time
    });
    
    showEditModal.value = false;
    
    // Reload schedule to get fresh data with teacher relationship
    await loadSchedule();
    
    // Emit refresh event to parent to reload class data (including calendar)
    emit('refresh');
    
    const Swal = (await import('sweetalert2')).default;
    Swal.fire({
      icon: 'success',
      title: t('common.success') || 'Success',
      text: t('class_detail.schedule_updated') || 'Lịch học đã được cập nhật. Calendar và các buổi học chưa điểm danh đã được đồng bộ.',
      timer: 3000,
      showConfirmButton: false
    });
  } catch (error) {
    // ... error handling
  }
};
```

**Lợi ích:**
- Reload schedule để hiển thị teacher info mới
- Emit event để parent component (ClassDetail) reload toàn bộ data
- Thông báo rõ ràng cho user về những gì đã được cập nhật

---

### 4. **ClassDetail.vue** (`resources/js/pages/quality/ClassDetail.vue`)

**Thêm handler cho refresh event từ WeeklyScheduleTab:**

```vue
<WeeklyScheduleTab 
  v-if="activeTab === 'schedule'" 
  :class-id="classId" 
  :class-data="classData" 
  @refresh="loadClassData" 
/>
```

**Lợi ích:**
- Khi schedule thay đổi, toàn bộ class data được reload
- Đảm bảo tất cả tabs đều có data mới nhất

---

## 🔄 Luồng Hoạt Động Sau Khi Sửa

### Khi Tạo Lớp Mới:

```
1. User tạo lớp với schedules (có teacher_id)
   ↓
2. Backend tạo ClassModel và ClassSchedules
   ↓
3. Backend load relationships bao gồm schedules.teacher
   ↓
4. User model có $appends = ['full_name']
   ↓
5. API response bao gồm schedule.teacher.full_name
   ↓
6. Frontend hiển thị tên giáo viên ở tab Schedule ✅
```

### Khi Edit Schedule:

```
1. User edit schedule từ WeeklyScheduleTab
   ↓
2. Backend nhận request updateSchedule()
   ↓
3. So sánh oldScheduleData vs newScheduleData
   ↓
4. Nếu teacher_id/start_time/end_time thay đổi:
   ↓
5. Gọi updateLessonSessionsFromScheduleChange()
   ↓
6. Tìm tất cả sessions chưa có attendance
   ↓
7. Cập nhật start_time, end_time cho mỗi session
   ↓
8. Eloquent hook updated() trigger
   ↓
9. CalendarEventService::syncClassSessionToCalendar()
   ↓
10. Calendar event được cập nhật với teacher info mới
   ↓
11. Frontend reload schedule + emit refresh
   ↓
12. ClassDetail reload toàn bộ data
   ↓
13. Tab Schedule và Calendar đều hiển thị data mới ✅
```

---

## 🧪 Cách Kiểm Tra

### Test 1: Tạo lớp mới
1. Tạo lớp mới với schedules và chọn teacher
2. Kiểm tra tab Schedule → phải hiển thị tên giáo viên
3. Kiểm tra module Calendar → phải hiển thị tên giáo viên trong metadata

### Test 2: Edit schedule
1. Vào ClassDetail → tab Schedule
2. Click vào một ô lịch để edit
3. Thay đổi giờ học hoặc chọn teacher khác
4. Save
5. Kiểm tra:
   - Tab Schedule hiển thị thông tin mới ✅
   - Module Calendar hiển thị thông tin mới ✅
   - Các buổi học chưa điểm danh được cập nhật ✅
   - Các buổi học đã điểm danh không bị ảnh hưởng ✅

### Test 3: Kiểm tra trong Console/Log
```bash
# Xem log khi update schedule
tail -f storage/logs/laravel.log | grep ClassManagement
```

Sẽ thấy:
```
[ClassManagement] Updating lesson sessions after schedule change
[ClassManagement] Updated lesson sessions successfully
```

---

## 📊 Tổng Kết

### Các File Đã Sửa:
1. ✅ `app/Models/User.php` - Thêm $appends['full_name']
2. ✅ `app/Http/Controllers/Api/ClassManagementController.php` - Sửa store(), update(), updateSchedule() + thêm updateLessonSessionsFromScheduleChange()
3. ✅ `resources/js/pages/quality/classDetail/WeeklyScheduleTab.vue` - Thêm emit('refresh')
4. ✅ `resources/js/pages/quality/ClassDetail.vue` - Handle @refresh event

### Tính Năng Mới:
- ✅ Tự động sync lesson sessions khi schedule thay đổi
- ✅ Tự động sync calendar khi lesson sessions thay đổi
- ✅ Bảo vệ data: chỉ cập nhật sessions chưa có attendance
- ✅ Logging đầy đủ để debug

### Lỗi Đã Fix:
- ✅ Lỗi 1: Tên giáo viên hiển thị ở tab Schedule
- ✅ Lỗi 2: Edit schedule đồng bộ lên cả Calendar và tab Schedule

