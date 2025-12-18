# Hướng Dẫn Fix Lớp TN-K2 Hiển Thị Đầy Đủ Trên Calendar

## 🐛 Vấn Đề

**Lớp TN-K2** (lớp cũ không có schedule từ đầu) sau khi edit thêm schedule vẫn **không hiển thị đầy đủ** như lớp **ielts2**:

### Lớp ielts2 (Lớp mới - Hoạt động tốt):
```
✅ Giáo viên: Nguyễn Thị Hoa Buổi học số 2
✅ Khung thông tin chi tiết:
   📚 ielts 2
   Mã lớp: ielts2
   Buổi học: 2/30
   Bài học: Unit 2
   Giáo viên: Vũ Thị Thu
   Số học viên: 0 người
✅ Nút "Xem chi tiết lớp →"
```

### Lớp TN-K2 (Lớp cũ - Chưa đủ thông tin):
```
❌ Chỉ có text mô tả: "Hiệu cấu trúc bài thi IELTS..."
❌ KHÔNG có khung thông tin chi tiết
❌ KHÔNG có tên giáo viên
❌ KHÔNG có nút xem chi tiết
```

---

## 🔍 Nguyên Nhân

### 1. **CalendarEventService - extractCustomerInfo() Lỗi**

File: `app/Services/CalendarEventService.php` (dòng 322-323)

**Code CŨ (SAI):**
```php
'teacher_name' => $class->homeroomTeacher->name ?? 'N/A',
'teacher_id' => $class->homeroom_teacher_id,
```

**Vấn đề:** Luôn lấy `homeroomTeacher` thay vì lấy từ `classSchedule.teacher`

**Code MỚI (ĐÚNG):**
```php
// Ưu tiên lấy teacher từ class_schedule (giáo viên đứng lớp)
$teacher = $eventable->classSchedule?->teacher ?? $class->homeroomTeacher;

'teacher_name' => $teacher?->full_name ?? $teacher?->name ?? 'N/A',
'teacher_id' => $teacher?->id ?? $class->homeroom_teacher_id,
```

### 2. **Sessions Chưa Được Map Với Schedule**

Lớp TN-K2 có sessions được tạo trước khi có schedule:
```sql
SELECT id, class_id, scheduled_date, class_schedule_id 
FROM class_lesson_sessions 
WHERE class_id = (SELECT id FROM classes WHERE code = 'TN-K2');

-- Kết quả:
-- id | class_id | scheduled_date | class_schedule_id
-- 10 | 2        | 2025-11-10     | NULL  ❌ (orphaned)
```

**Khi `class_schedule_id = NULL`:**
- Calendar không lấy được teacher từ `classSchedule.teacher`
- Fallback sang `homeroomTeacher` nhưng method cũ SAI (xem phần 1)
- Kết quả: Không hiển thị teacher info

---

## ✅ Giải Pháp Đã Implement

### 1. **Sửa CalendarEventService.php**

```php
// Nếu là ClassLessonSession
if ($eventable instanceof \App\Models\ClassLessonSession) {
    $eventable->load('class.homeroomTeacher', 'classSchedule.teacher', 'trialStudents');
    $class = $eventable->class;
    
    // ✨ NEW: Ưu tiên lấy teacher từ class_schedule
    $teacher = $eventable->classSchedule?->teacher ?? $class->homeroomTeacher;
    
    return [
        'type' => 'class_session',
        'teacher_name' => $teacher?->full_name ?? $teacher?->name ?? 'N/A',
        'teacher_id' => $teacher?->id ?? $class->homeroom_teacher_id,
        // ... other fields
    ];
}
```

### 2. **Cải Thiện syncClassToCalendar()**

```php
public function syncClassToCalendar($classId)
{
    $class = ClassModel::with('lessonSessions.classSchedule.teacher')->findOrFail($classId);
    
    // ✨ NEW: Tự động map orphaned sessions trước khi sync
    if ($class->schedules()->count() > 0) {
        $this->mapSessionsToSchedules($class);
    }
    
    // Reload sessions sau khi map
    $class->load('lessonSessions.classSchedule.teacher');
    
    foreach ($class->lessonSessions as $session) {
        // Reload với fresh relationships
        $session->refresh();
        $session->load('class.homeroomTeacher', 'classSchedule.teacher');
        
        $calendarService->syncClassSessionToCalendar($session);
    }
}
```

### 3. **Method mapSessionsToSchedules() Đã Có**

Method này tự động map orphaned sessions với schedules dựa trên day_of_week.

---

## 🔧 Cách Fix Lớp TN-K2

### Option 1: Tự Động Sync (Khuyên Dùng)

**Cách 1: Dùng API Endpoint**

```bash
# Gọi API sync lại calendar cho lớp TN-K2
POST /api/classes/{class_id}/sync-to-calendar

# Ví dụ với Postman hoặc curl:
curl -X POST "http://localhost:8000/api/classes/2/sync-to-calendar" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "success": true,
  "message": "Đã đồng bộ 30 buổi học lên calendar. Bỏ qua 0 buổi lỗi.",
  "data": {
    "synced": 30,
    "skipped": 0,
    "total": 30
  }
}
```

**Cách 2: Từ Frontend (Class Detail)**

Nếu có UI button "Sync Calendar", click vào đó sẽ gọi API trên.

### Option 2: Edit Lại Lớp (Tự Động)

Chỉ cần **edit lớp TN-K2 bất kỳ field nào** (ví dụ: đổi tên, status) và **Save**.

**Method update() sẽ tự động:**
```php
// 1. Map orphaned sessions
if ($request->has('schedules') && $class->lessonSessions()->count() > 0) {
    $this->mapSessionsToSchedules($class);  // ✅ Map sessions
}

// 2. Eloquent hook trigger
ClassLessonSession::updated() → syncClassSessionToCalendar()  // ✅ Sync calendar
```

### Option 3: Chạy Artisan Command (Nếu Có Nhiều Lớp)

**Tạo command mới:**

```php
// app/Console/Commands/SyncClassesToCalendar.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClassModel;
use App\Services\CalendarEventService;

class SyncClassesToCalendar extends Command
{
    protected $signature = 'classes:sync-calendar {class_id?}';
    protected $description = 'Sync class sessions to calendar';

    public function handle()
    {
        $classId = $this->argument('class_id');
        
        if ($classId) {
            $classes = ClassModel::where('id', $classId)->get();
        } else {
            $classes = ClassModel::all();
        }
        
        foreach ($classes as $class) {
            $this->info("Syncing class: {$class->name} (ID: {$class->id})");
            
            // Call API internally
            app(\App\Http\Controllers\Api\ClassManagementController::class)
                ->syncClassToCalendar($class->id);
        }
        
        $this->info("Done!");
    }
}
```

**Chạy command:**

```bash
# Sync lớp TN-K2 (giả sử ID = 2)
php artisan classes:sync-calendar 2

# Hoặc sync tất cả lớp
php artisan classes:sync-calendar
```

---

## 🧪 Kiểm Tra Sau Khi Fix

### 1. Check Database

```sql
-- Kiểm tra sessions đã có class_schedule_id chưa
SELECT 
    cls.id AS session_id,
    cls.scheduled_date,
    cls.class_schedule_id,
    cs.teacher_id AS schedule_teacher_id,
    u.name AS teacher_name
FROM class_lesson_sessions cls
LEFT JOIN class_schedules cs ON cls.class_schedule_id = cs.id
LEFT JOIN users u ON cs.teacher_id = u.id
WHERE cls.class_id = (SELECT id FROM classes WHERE code = 'TN-K2')
ORDER BY cls.scheduled_date
LIMIT 5;

-- Kết quả mong đợi:
-- session_id | scheduled_date | class_schedule_id | schedule_teacher_id | teacher_name
-- 10         | 2025-11-10     | 5                 | 8                   | John Doe ✅
```

### 2. Check Calendar Events

```sql
-- Kiểm tra calendar events đã có teacher info chưa
SELECT 
    ce.id AS event_id,
    ce.title,
    ce.assigned_teacher_id,
    u.name AS assigned_teacher_name,
    ce.metadata->>'$.teacher_name' AS metadata_teacher
FROM calendar_events ce
LEFT JOIN users u ON ce.assigned_teacher_id = u.id
WHERE ce.eventable_type = 'App\\Models\\ClassLessonSession'
AND ce.eventable_id IN (
    SELECT id FROM class_lesson_sessions 
    WHERE class_id = (SELECT id FROM classes WHERE code = 'TN-K2')
)
LIMIT 5;

-- Kết quả mong đợi:
-- event_id | title                  | assigned_teacher_id | assigned_teacher_name | metadata_teacher
-- 101      | TN-K2 - Buổi 1: ...    | 8                   | John Doe              | John Doe ✅
```

### 3. Check Frontend Calendar

**Reload trang Calendar và kiểm tra event của TN-K2:**

✅ **Mong đợi thấy:**
```
TN-K2 - Buổi 1: Giới thiệu khóa học & Chiến lược thi IELTS
2025.11.10 07:00 am - 11:00 am
🔒 pending
● Buổi Học

Giáo viên: John Doe  ✅ (NEW - trước đây không có)

📚 TN-K2  ✅ (NEW - khung thông tin)
Mã lớp: TN-K2
Buổi học: 1/30
Bài học: Giới thiệu khóa học...
Giáo viên: John Doe  ✅
Số học viên: 5 người

📖 Xem chi tiết lớp →  ✅ (NEW - nút này)
```

---

## 📊 So Sánh Trước & Sau Fix

### TRƯỚC FIX:

```
TN-K2 Event:
├─ ❌ Chỉ có description text
├─ ❌ Không có teacher name
├─ ❌ Không có khung thông tin chi tiết
└─ ❌ Không có nút xem chi tiết

Database:
└─ class_schedule_id = NULL ❌

Calendar Event:
├─ assigned_teacher_id = NULL ❌
└─ metadata.teacher_name = NULL ❌
```

### SAU FIX:

```
TN-K2 Event:
├─ ✅ Có teacher name: "Giáo viên: John Doe"
├─ ✅ Có khung thông tin chi tiết đầy đủ
└─ ✅ Có nút "Xem chi tiết lớp →"

Database:
└─ class_schedule_id = 5 ✅ (đã map)

Calendar Event:
├─ assigned_teacher_id = 8 ✅
├─ metadata.teacher_id = 8 ✅
└─ metadata.teacher_name = "John Doe" ✅

Giống y chang lớp ielts2! 🎉
```

---

## 🎯 Tóm Tắt

### Đã Sửa:
1. ✅ `CalendarEventService::extractCustomerInfo()` - Lấy teacher từ schedule
2. ✅ `ClassManagementController::syncClassToCalendar()` - Tự động map orphaned sessions
3. ✅ `ClassManagementController::update()` - Tự động map khi edit lớp

### Cách Fix TN-K2:
**Chọn 1 trong 3:**
1. 🎯 **Gọi API** `POST /api/classes/{id}/sync-to-calendar` (Nhanh nhất)
2. 📝 **Edit lớp** TN-K2 bất kỳ field nào và Save (Đơn giản)
3. 🔧 **Chạy command** `php artisan classes:sync-calendar 2` (Cho nhiều lớp)

### Sau khi fix:
✅ TN-K2 sẽ hiển thị đầy đủ thông tin như ielts2  
✅ Có tên giáo viên  
✅ Có khung thông tin chi tiết  
✅ Có nút "Xem chi tiết lớp"

---

## 🚀 Thực Hiện Ngay

**Cách nhanh nhất:**

```bash
# 1. Tìm ID của lớp TN-K2
# SELECT id FROM classes WHERE code = 'TN-K2';  → Giả sử ID = 2

# 2. Gọi API sync (dùng Postman hoặc curl)
POST http://localhost:8000/api/classes/2/sync-to-calendar
```

Hoặc đơn giản hơn:

**Vào frontend → Edit lớp TN-K2 → Thay đổi bất kỳ field nào → Save** 

Done! 🎉

