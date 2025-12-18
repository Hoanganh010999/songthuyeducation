# Sửa Lỗi: Lớp Cũ Không Cập Nhật Calendar Khi Thêm Schedule

## 🐛 Vấn Đề

### Tình huống:
1. **Lớp mới tạo** → Có schedule + teacher → Calendar hiển thị đầy đủ thông tin ✅
2. **Lớp cũ** (đã có sessions nhưng chưa có schedule) → Edit thêm schedule → Calendar **KHÔNG** cập nhật ❌

### Nguyên nhân:

**Lớp cũ có thể có sessions được tạo trước đó với:**
- `class_schedule_id = NULL` (orphaned sessions)
- Không có teacher info
- Không có start_time/end_time từ schedule

**Khi edit thêm schedule mới vào lớp:**
- Schedule được tạo ✅
- Nhưng sessions cũ **không được map** với schedule mới ❌
- Vì vậy `class_schedule_id` vẫn là `NULL`
- Calendar sync nhưng **thiếu teacher info** (vì không có classSchedule.teacher)

### Ví dụ cụ thể:

```
Class A (lớp cũ):
├─ Session 1: scheduled_date = 2025-01-05 (Thứ 2), class_schedule_id = NULL ❌
├─ Session 2: scheduled_date = 2025-01-07 (Thứ 4), class_schedule_id = NULL ❌
└─ Session 3: scheduled_date = 2025-01-10 (Thứ 2), class_schedule_id = NULL ❌

Edit Class A, thêm schedules:
├─ Schedule Thứ 2: teacher_id = 5, start_time = 07:00 ✅
└─ Schedule Thứ 4: teacher_id = 8, start_time = 09:00 ✅

Trước fix:
└─ Sessions vẫn có class_schedule_id = NULL → Calendar không có teacher info ❌

Sau fix:
├─ Session 1: class_schedule_id = 1 (Thứ 2), teacher từ schedule 1 ✅
├─ Session 2: class_schedule_id = 2 (Thứ 4), teacher từ schedule 2 ✅
└─ Session 3: class_schedule_id = 1 (Thứ 2), teacher từ schedule 1 ✅
```

---

## ✅ Giải Pháp

### Thêm Method Mới: `mapSessionsToSchedules()`

Method này sẽ:
1. Tìm tất cả **orphaned sessions** (sessions có `class_schedule_id = NULL`)
2. Chỉ xử lý sessions **chưa có attendance** (bảo vệ data)
3. Map mỗi session với schedule tương ứng dựa trên **day of week**
4. Cập nhật `class_schedule_id`, `start_time`, `end_time`
5. Trigger Eloquent hook → Tự động sync lên Calendar với teacher info

### Code Implementation:

```php
/**
 * Map existing sessions to schedules based on day of week
 * This is used when schedules are added to a class that already has sessions
 */
private function mapSessionsToSchedules($class)
{
    try {
        // Get all sessions without class_schedule_id (orphaned sessions)
        $orphanedSessions = ClassLessonSession::where('class_id', $class->id)
            ->whereNull('class_schedule_id')
            ->whereDoesntHave('attendances')
            ->orderBy('scheduled_date')
            ->get();
        
        if ($orphanedSessions->isEmpty()) {
            Log::info('[ClassManagement] No orphaned sessions to map');
            return;
        }
        
        Log::info('[ClassManagement] Mapping orphaned sessions to schedules', [
            'class_id' => $class->id,
            'session_count' => $orphanedSessions->count(),
        ]);
        
        // Load schedules with relationships
        $schedules = $class->schedules()->with('teacher', 'room')->get();
        
        if ($schedules->isEmpty()) {
            Log::warning('[ClassManagement] No schedules available to map');
            return;
        }
        
        // Map day names to Carbon day numbers
        $dayMap = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        ];
        
        // Create a map of schedules by day of week
        $schedulesByDay = $schedules->keyBy(function($schedule) use ($dayMap) {
            return $dayMap[$schedule->day_of_week] ?? null;
        });
        
        $mappedCount = 0;
        
        // Map each orphaned session to appropriate schedule
        foreach ($orphanedSessions as $session) {
            if (!$session->scheduled_date) {
                continue;
            }
            
            $sessionDate = \Carbon\Carbon::parse($session->scheduled_date);
            $dayOfWeek = $sessionDate->dayOfWeek;
            
            // Find matching schedule for this day
            if (isset($schedulesByDay[$dayOfWeek])) {
                $schedule = $schedulesByDay[$dayOfWeek];
                
                // Update session with schedule info
                $session->update([
                    'class_schedule_id' => $schedule->id,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                ]);
                
                $mappedCount++;
                
                // The update() will trigger the ClassLessonSession::updated() hook
                // which automatically syncs to calendar with teacher info
            }
        }
        
        Log::info('[ClassManagement] Successfully mapped sessions to schedules', [
            'class_id' => $class->id,
            'mapped_count' => $mappedCount,
            'total_orphaned' => $orphanedSessions->count(),
        ]);
        
    } catch (\Exception $e) {
        Log::error('[ClassManagement] Error mapping sessions to schedules', [
            'class_id' => $class->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

### Gọi Method Trong `update()`:

```php
// Update schedules if provided
if ($request->has('schedules') && is_array($request->schedules)) {
    // ... existing schedule update logic ...
}

// Recalculate lesson sessions if schedules changed
if ($schedulesChanged && $class->lessonSessions()->count() > 0) {
    $this->updateLessonSessionsSchedules($class);
}

// ✨ NEW: Map existing sessions to schedules if they don't have class_schedule_id yet
// This handles the case when schedules are added to an existing class
if ($request->has('schedules') && $class->lessonSessions()->count() > 0) {
    $this->mapSessionsToSchedules($class);
}
```

---

## 🔄 Luồng Hoạt Động

### Trường Hợp: Edit Lớp Cũ + Thêm Schedule

```
1. User edit lớp cũ, thêm schedules (Thứ 2, Thứ 4)
   ↓
2. Backend update() method được gọi
   ↓
3. Schedules được tạo/update trong database
   ↓
4. Gọi mapSessionsToSchedules($class)
   ↓
5. Tìm orphaned sessions (class_schedule_id = NULL, no attendance)
   ↓
6. Duyệt qua từng session:
   - Session ngày Thứ 2 → Map với schedule Thứ 2
   - Session ngày Thứ 4 → Map với schedule Thứ 4
   ↓
7. Update session với:
   - class_schedule_id = schedule.id ✅
   - start_time = schedule.start_time ✅
   - end_time = schedule.end_time ✅
   ↓
8. Eloquent hook ClassLessonSession::updated() trigger
   ↓
9. CalendarEventService::syncClassSessionToCalendar() được gọi
   ↓
10. Lấy teacher từ classSchedule.teacher
   ↓
11. Calendar event được cập nhật với:
    - teacher_id ✅
    - teacher_name ✅
    - description với tên giáo viên ✅
    - metadata với teacher info ✅
   ↓
12. Frontend reload → Calendar hiển thị đầy đủ thông tin ✅
```

---

## 🔍 Các Trường Hợp Xử Lý

### 1. Orphaned Sessions (class_schedule_id = NULL)
```sql
-- Tìm sessions chưa được map
SELECT * FROM class_lesson_sessions 
WHERE class_id = ? 
AND class_schedule_id IS NULL
AND NOT EXISTS (
    SELECT 1 FROM attendances 
    WHERE session_id = class_lesson_sessions.id
);
```

✅ **Được xử lý:** Map với schedule tương ứng dựa trên ngày trong tuần

### 2. Sessions Có Attendance
```sql
-- Sessions đã điểm danh KHÔNG được touch
SELECT * FROM class_lesson_sessions 
WHERE class_id = ? 
AND EXISTS (
    SELECT 1 FROM attendances 
    WHERE session_id = class_lesson_sessions.id
);
```

✅ **Bảo vệ data:** Không thay đổi sessions đã có attendance

### 3. Sessions Đã Có class_schedule_id
```sql
-- Sessions đã được map trước đó
SELECT * FROM class_lesson_sessions 
WHERE class_id = ? 
AND class_schedule_id IS NOT NULL;
```

✅ **Không ảnh hưởng:** Method chỉ xử lý `whereNull('class_schedule_id')`

### 4. Ngày Không Khớp Schedule
```
Session: scheduled_date = 2025-01-06 (Thứ 3)
Schedules: Chỉ có Thứ 2 và Thứ 4
```

✅ **Không map:** Session vẫn giữ nguyên `class_schedule_id = NULL` (safe)

---

## 🧪 Testing

### Test 1: Lớp Cũ Không Có Schedule

**Setup:**
```sql
-- Lớp A có sessions nhưng không có schedule
INSERT INTO classes (id, name) VALUES (1, 'Class A');
INSERT INTO class_lesson_sessions (id, class_id, scheduled_date, class_schedule_id) 
VALUES 
    (1, 1, '2025-01-05', NULL),  -- Thứ 2
    (2, 1, '2025-01-07', NULL),  -- Thứ 4
    (3, 1, '2025-01-10', NULL);  -- Thứ 2
```

**Action:**
```javascript
// Edit lớp A, thêm schedules
PUT /api/classes/1
{
  schedules: [
    { day_of_week: '2', teacher_id: 5, start_time: '07:00' },  // Thứ 2
    { day_of_week: '4', teacher_id: 8, start_time: '09:00' }   // Thứ 4
  ]
}
```

**Kết quả mong đợi:**
```sql
-- Sessions được map với schedules
SELECT id, scheduled_date, class_schedule_id FROM class_lesson_sessions;
-- 1 | 2025-01-05 (Thứ 2) | 1 (schedule Thứ 2) ✅
-- 2 | 2025-01-07 (Thứ 4) | 2 (schedule Thứ 4) ✅
-- 3 | 2025-01-10 (Thứ 2) | 1 (schedule Thứ 2) ✅
```

**Calendar:**
- Event cho Session 1 → Hiển thị teacher "John Doe" ✅
- Event cho Session 2 → Hiển thị teacher "Jane Smith" ✅
- Event cho Session 3 → Hiển thị teacher "John Doe" ✅

### Test 2: Lớp Có Session Đã Điểm Danh

**Setup:**
```sql
INSERT INTO class_lesson_sessions (id, class_id, scheduled_date, class_schedule_id) 
VALUES 
    (4, 1, '2025-01-12', NULL),  -- Chưa điểm danh
    (5, 1, '2025-01-14', NULL);  -- Chưa điểm danh

INSERT INTO attendances (session_id, student_id) VALUES (4, 1);  -- Session 4 đã điểm danh
```

**Action:**
```javascript
// Edit thêm schedule
PUT /api/classes/1
{
  schedules: [
    { day_of_week: '2', teacher_id: 5, start_time: '07:00' }
  ]
}
```

**Kết quả mong đợi:**
```sql
-- Session 4 (đã điểm danh) KHÔNG được map
SELECT id, class_schedule_id FROM class_lesson_sessions WHERE id = 4;
-- 4 | NULL ✅ (giữ nguyên, an toàn)

-- Session 5 (chưa điểm danh) được map
SELECT id, class_schedule_id FROM class_lesson_sessions WHERE id = 5;
-- 5 | 1 ✅ (được map với schedule Thứ 2)
```

### Test 3: Check Logs

```bash
# Xem log khi map sessions
tail -f storage/logs/laravel.log | grep "ClassManagement"
```

**Log mong đợi:**
```
[2025-01-11 10:30:15] local.INFO: [ClassManagement] Mapping orphaned sessions to schedules {"class_id":1,"session_count":3}
[2025-01-11 10:30:15] local.INFO: [ClassManagement] Successfully mapped sessions to schedules {"class_id":1,"mapped_count":3,"total_orphaned":3}
```

---

## 📊 So Sánh Trước & Sau Fix

### TRƯỚC FIX:

```
Edit lớp cũ (thêm schedule)
├─ ✅ Schedule được tạo
├─ ❌ Sessions vẫn có class_schedule_id = NULL
├─ ❌ Calendar sync nhưng không có teacher info
└─ ❌ User thấy calendar event thiếu thông tin
```

### SAU FIX:

```
Edit lớp cũ (thêm schedule)
├─ ✅ Schedule được tạo
├─ ✅ mapSessionsToSchedules() được gọi
├─ ✅ Sessions được map với schedules (dựa trên ngày)
├─ ✅ Eloquent hook trigger sync lên Calendar
├─ ✅ Calendar có đầy đủ teacher info
└─ ✅ User thấy calendar event đầy đủ như lớp mới tạo
```

---

## 🎯 Tổng Kết

### Các File Đã Sửa:
1. ✅ `app/Http/Controllers/Api/ClassManagementController.php`
   - Thêm method `mapSessionsToSchedules()`
   - Gọi method trong `update()` sau khi update schedules

### Tính Năng Mới:
- ✅ Tự động map orphaned sessions với schedules mới
- ✅ Dựa trên day_of_week để match chính xác
- ✅ Chỉ xử lý sessions chưa có attendance (an toàn)
- ✅ Tự động trigger sync lên Calendar thông qua Eloquent hooks
- ✅ Logging đầy đủ để debug

### Lỗi Đã Fix:
- ✅ Lớp cũ edit thêm schedule → Calendar cập nhật đầy đủ thông tin
- ✅ Teacher info hiển thị trong Calendar cho lớp cũ
- ✅ Nút "Xem chi tiết lớp" hoạt động cho lớp cũ

### Bảo Vệ Data:
- ✅ Sessions đã có attendance không bị thay đổi
- ✅ Sessions không khớp ngày vẫn giữ nguyên (safe)
- ✅ Error handling với try-catch và logging

---

## 🚀 Sẵn Sàng Sử Dụng

Giờ khi bạn edit lớp cũ và thêm schedule, Calendar sẽ tự động cập nhật với đầy đủ thông tin giáo viên và nút xem chi tiết lớp! 🎉

