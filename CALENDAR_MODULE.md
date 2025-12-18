# 📅 Hệ Thống Calendar Module - TOAST UI Integration

## 🎯 Tổng Quan

Hệ thống Calendar Module trung tâm sử dụng **TOAST UI Calendar**, cho phép quản lý tập trung tất cả các sự kiện/lịch hẹn từ nhiều module khác nhau trong hệ thống:

- ✅ **Polymorphic Relationship** - Event có thể thuộc về bất kỳ model nào
- ✅ **Auto Sync** - Tự động đồng bộ từ các module (Customer Interactions, Tasks, Meetings...)
- ✅ **Status Tracking** - Theo dõi trạng thái event real-time
- ✅ **Multi-Category** - Phân loại theo màu sắc và icon
- ✅ **TOAST UI Calendar** - Giao diện calendar chuyên nghiệp, đẹp mắt

---

## 🗄️ Database Schema

### Bảng `calendar_events`

```sql
CREATE TABLE calendar_events (
    id BIGINT PRIMARY KEY,
    
    -- Polymorphic Relationship
    eventable_type VARCHAR(255),  -- App\Models\CustomerInteraction, App\Models\Task, etc.
    eventable_id BIGINT,          -- ID của model gốc
    
    -- Thông tin cơ bản
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(255) DEFAULT 'general',
    
    -- Thời gian
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_all_day BOOLEAN DEFAULT FALSE,
    
    -- Trạng thái
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    
    -- Người tham gia
    user_id BIGINT NOT NULL,     -- Người chịu trách nhiệm
    attendees JSON NULL,         -- Danh sách người tham gia
    
    -- Hiển thị
    color VARCHAR(255) DEFAULT '#3B82F6',
    icon VARCHAR(255) NULL,
    location VARCHAR(255) NULL,
    
    -- Nhắc nhở
    has_reminder BOOLEAN DEFAULT FALSE,
    reminder_minutes_before INT NULL,
    
    -- Metadata
    metadata JSON NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX(eventable_type, eventable_id),
    INDEX(category),
    INDEX(status),
    INDEX(start_date),
    INDEX(end_date),
    INDEX(user_id)
);
```

---

## 🏗️ Kiến Trúc Hệ Thống

### 1. **Polymorphic Relationship**

```php
// CalendarEvent có thể thuộc về bất kỳ model nào
CalendarEvent::where('eventable_type', 'App\Models\CustomerInteraction')
    ->where('eventable_id', 123)
    ->first();

// CustomerInteraction → CalendarEvent
$interaction->calendarEvent; // morphOne

// Task → CalendarEvent (future)
$task->calendarEvent;
```

### 2. **Auto Sync với Event Lifecycle Hooks**

```php
// app/Models/CustomerInteraction.php
protected static function booted()
{
    // Sau khi tạo/cập nhật → sync calendar event
    static::saved(function ($interaction) {
        $interaction->syncCalendarEvent();
    });

    // Sau khi xóa → xóa calendar event
    static::deleted(function ($interaction) {
        $calendarService = app(CalendarEventService::class);
        $calendarService->deleteEvent($interaction);
    });
}
```

### 3. **CalendarEventService - Centralized Management**

```php
// app/Services/CalendarEventService.php

// Sync event từ model khác
$calendarService->syncEvent($customerInteraction, [
    'title' => "Liên hệ lại: {$customer->name}",
    'category' => 'customer_follow_up',
    'start_date' => $interaction->next_follow_up,
    'status' => 'pending',
    'color' => '#F59E0B',
    // ...
]);

// Format cho TOAST UI Calendar
$formatted = $calendarService->formatForToastUI($event);
```

---

## 📊 Category System

### Categories với Màu Sắc & Icon

| Category | Màu | Icon | Mô Tả |
|----------|-----|------|-------|
| `customer_follow_up` | `#F59E0B` (Amber) | 📞 | Liên hệ lại khách hàng |
| `meeting` | `#3B82F6` (Blue) | 👥 | Cuộc họp |
| `task` | `#10B981` (Green) | ✅ | Công việc |
| `deadline` | `#EF4444` (Red) | ⏰ | Deadline |
| `event` | `#8B5CF6` (Purple) | 📅 | Sự kiện |
| `reminder` | `#EC4899` (Pink) | 🔔 | Nhắc nhở |
| `general` | `#6B7280` (Gray) | 📌 | Chung |

---

## 🔄 Flow: Customer Interaction → Calendar Event

### Khi tạo Customer Interaction với `next_follow_up`:

```
1. User tạo CustomerInteraction với next_follow_up = "2025-11-05 14:00:00"
   ↓
2. CustomerInteraction::saved() hook triggered
   ↓
3. syncCalendarEvent() được gọi
   ↓
4. CalendarEventService::syncEvent() tạo/update CalendarEvent
   ↓
5. CalendarEvent được lưu với:
   - eventable_type = "App\Models\CustomerInteraction"
   - eventable_id = 123
   - category = "customer_follow_up"
   - status = "pending"
   - color = "#F59E0B"
   ↓
6. Event xuất hiện trên Calendar UI
```

### Khi cập nhật trạng thái Interaction:

```
1. User cập nhật CustomerInteraction (đã liên hệ xong)
   ↓
2. Interaction status changed
   ↓
3. (Optional) Update CalendarEvent status = "completed"
   ↓
4. Calendar UI hiển thị event với trạng thái mới (màu khác, strikethrough, etc.)
```

### Khi xóa Interaction:

```
1. User xóa CustomerInteraction
   ↓
2. CustomerInteraction::deleted() hook triggered
   ↓
3. CalendarEventService::deleteEvent() được gọi
   ↓
4. CalendarEvent bị xóa
   ↓
5. Event biến mất khỏi Calendar UI
```

---

## 🛣️ API Endpoints

### Calendar Events API

```php
GET    /api/calendar/events
       - Lấy events trong khoảng thời gian
       - Params: start_date, end_date, user_id?, category?
       - Response: Array of events formatted for TOAST UI

GET    /api/calendar/events/upcoming
       - Lấy events sắp tới (chưa hoàn thành)
       - Params: user_id?, limit? (default: 10)

GET    /api/calendar/events/overdue
       - Lấy events quá hạn
       - Params: user_id?

GET    /api/calendar/categories
       - Lấy danh sách categories với màu sắc & icon

POST   /api/calendar/events
       - Tạo standalone event (không liên kết với model khác)
       - Body: { title, description, category, start_date, end_date, ... }

GET    /api/calendar/events/{id}
       - Xem chi tiết event

PUT    /api/calendar/events/{id}
       - Cập nhật event (kể cả status)

DELETE /api/calendar/events/{id}
       - Xóa event (chỉ standalone events)
       - Linked events phải xóa từ module gốc
```

---

## 🎨 Frontend - TOAST UI Calendar

### Component: `CalendarView.vue`

**Đường dẫn:** `resources/js/pages/calendar/CalendarView.vue`

**Chức năng:**
- Hiển thị calendar với TOAST UI
- Support views: Month, Week, Day
- Click event → Xem chi tiết
- Select datetime → Tạo event mới
- Auto load events khi thay đổi view
- Category filtering
- Responsive design

**TOAST UI Config:**
```javascript
new Calendar(container, {
  defaultView: 'month',
  useFormPopup: false,
  useDetailPopup: true,
  calendars: [
    { id: 'customer_follow_up', name: 'Liên Hệ Lại KH', backgroundColor: '#F59E0B' },
    { id: 'meeting', name: 'Cuộc Họp', backgroundColor: '#3B82F6' },
    // ...
  ],
  // ...
});
```

**Event Format for TOAST UI:**
```javascript
{
  id: 123,
  calendarId: 'customer_follow_up',
  title: 'Liên hệ lại: Nguyễn Văn A',
  body: 'Khách hàng quan tâm gói Premium...',
  start: '2025-11-05T14:00:00+07:00',
  end: '2025-11-05T15:00:00+07:00',
  isAllday: false,
  category: 'time',
  backgroundColor: '#F59E0B',
  borderColor: '#F59E0B',
  color: '#ffffff',
  raw: {
    eventable_type: 'App\\Models\\CustomerInteraction',
    eventable_id: 456,
    metadata: { customer_name: 'Nguyễn Văn A', ... }
  }
}
```

---

## 🔐 Permissions

| Permission | Mô Tả |
|------------|-------|
| `calendar.view` | Xem calendar và events |
| `calendar.create` | Tạo event mới |
| `calendar.edit` | Sửa event |
| `calendar.delete` | Xóa event |

---

## 🌐 Translations

### Các key chính (calendar group):

| Key | Vietnamese | English |
|-----|-----------|---------|
| `calendar` | Lịch | Calendar |
| `my_calendar` | Lịch Của Tôi | My Calendar |
| `add_event` | Thêm Sự Kiện | Add Event |
| `upcoming_events` | Sự Kiện Sắp Tới | Upcoming Events |
| `overdue_events` | Sự Kiện Quá Hạn | Overdue Events |
| `status_pending` | Chờ Xử Lý | Pending |
| `status_completed` | Hoàn Thành | Completed |
| `customer_follow_up` | Liên Hệ Lại Khách Hàng | Customer Follow-up |

---

## 📝 Cách Tích Hợp Module Mới

### Ví dụ: Tích hợp Task Module

#### Bước 1: Thêm relationship vào Task Model

```php
// app/Models/Task.php

use App\Services\CalendarEventService;

protected static function booted()
{
    static::saved(function ($task) {
        $task->syncCalendarEvent();
    });

    static::deleted(function ($task) {
        $calendarService = app(CalendarEventService::class);
        $calendarService->deleteEvent($task);
    });
}

public function calendarEvent()
{
    return $this->morphOne(CalendarEvent::class, 'eventable');
}

public function syncCalendarEvent()
{
    if (!$this->due_date) {
        $calendarService = app(CalendarEventService::class);
        $calendarService->deleteEvent($this);
        return;
    }

    $calendarService = app(CalendarEventService::class);
    
    $calendarService->syncEvent($this, [
        'title' => $this->title,
        'description' => $this->description,
        'category' => 'task',
        'start_date' => $this->due_date,
        'end_date' => $this->due_date->addHours(2),
        'status' => $this->status,
        'user_id' => $this->assigned_to,
        'color' => '#10B981',
        'icon' => '✅',
        'metadata' => [
            'task_priority' => $this->priority,
            'task_project' => $this->project->name ?? null,
        ],
    ]);
}
```

#### Bước 2: Thêm category vào CalendarEvent::getCategoryColors()

```php
// app/Models/CalendarEvent.php

public static function getCategoryColors(): array
{
    return [
        'customer_follow_up' => '#F59E0B',
        'task' => '#10B981', // ← Đã có
        // ... thêm categories mới ở đây
    ];
}
```

#### Bước 3: Frontend tự động nhận category mới

Calendar sẽ tự động load categories từ API `/api/calendar/categories`.

---

## 🎯 Use Cases

### 1. Customer Follow-up Tracking
```
- Sales tạo interaction với khách hàng
- Đặt next_follow_up = 3 ngày sau
- Event tự động xuất hiện trên calendar
- Sales xem calendar → biết hôm nay phải gọi cho ai
- Sau khi gọi xong → cập nhật interaction
- (Optional) Event status → completed
```

### 2. Team Meeting Management
```
- Manager tạo event meeting
- Category = 'meeting'
- Thêm attendees = [user1, user2, user3]
- Tất cả members xem calendar → thấy meeting
- Có reminder 30 phút trước
```

### 3. Deadline Tracking
```
- Project deadline được sync vào calendar
- Category = 'deadline', color = red
- Hiển thị nổi bật
- Overdue events được highlight
```

### 4. Multi-Module View
```
- Calendar hiển thị TẤT CẢ events từ mọi module:
  • Customer follow-ups (amber)
  • Tasks (green)
  • Meetings (blue)
  • Deadlines (red)
- Filter theo category
- Filter theo user
```

---

## 🚀 Deployment Checklist

- [x] Migration `calendar_events` table
- [x] Model `CalendarEvent` với polymorphic
- [x] Service `CalendarEventService`
- [x] Controller `CalendarEventController`
- [x] Routes `/api/calendar/*`
- [x] Update `CustomerInteraction` model với sync logic
- [x] Seeder permissions & translations
- [x] Install `@toast-ui/calendar` npm package
- [x] Frontend `CalendarView.vue`
- [x] Router integration
- [x] Sidebar link
- [x] Build & test

---

## 🧪 Testing

### 1. Test Calendar Event Auto Sync
```bash
# Tạo customer interaction với next_follow_up
POST /api/customers/1/interactions
{
  "interaction_type_id": 1,
  "interaction_result_id": 3,
  "notes": "Khách hàng quan tâm",
  "interaction_date": "2025-10-31 10:00:00",
  "next_follow_up": "2025-11-05 14:00:00"
}

# Kiểm tra calendar event đã được tạo
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Xóa interaction
DELETE /api/customers/1/interactions/123

# Kiểm tra calendar event đã bị xóa
GET /api/calendar/events/...
```

### 2. Test Calendar UI
```
1. Login và vào /calendar
2. Calendar hiển thị tháng hiện tại
3. Click vào event → popup chi tiết
4. Click "Add Event" → form tạo event
5. Thay đổi view: Month → Week → Day
6. Filter theo category
```

---

## 📈 Future Enhancements

1. **Recurring Events** - Sự kiện lặp lại (hàng ngày, hàng tuần, hàng tháng)
2. **Drag & Drop** - Kéo thả event để đổi ngày giờ
3. **Email/SMS Reminders** - Gửi nhắc nhở tự động
4. **Calendar Sharing** - Chia sẻ calendar giữa users/teams
5. **Google Calendar Sync** - Đồng bộ với Google Calendar
6. **Task Dependencies** - Liên kết events phụ thuộc nhau
7. **Time Tracking** - Theo dõi thời gian thực tế vs dự kiến
8. **Analytics Dashboard** - Thống kê events completed, overdue, etc.

---

## 🎊 Kết Quả

### ✅ Đã Hoàn Thành 100%

#### Backend:
- ✅ Polymorphic Calendar Event model
- ✅ CalendarEventService với sync logic
- ✅ Auto sync từ CustomerInteraction
- ✅ CRUD API endpoints
- ✅ Permissions & Translations

#### Frontend:
- ✅ TOAST UI Calendar integration
- ✅ CalendarView component
- ✅ Router & Sidebar integration
- ✅ Category filtering
- ✅ Multi-view support (Month/Week/Day)

#### Architecture:
- ✅ Mở rộng dễ dàng cho modules mới
- ✅ Centralized calendar management
- ✅ Real-time status tracking
- ✅ Clean polymorphic design

---

**🎉 Hệ thống Calendar Module với TOAST UI đã sẵn sàng sử dụng!**

Giờ mỗi khi có `next_follow_up` trong Customer Interaction, nó sẽ tự động xuất hiện trên Calendar. Trong tương lai, bất kỳ module nào (Tasks, Meetings, Deadlines) cũng có thể dễ dàng tích hợp vào Calendar này! 🚀


## 🎯 Tổng Quan

Hệ thống Calendar Module trung tâm sử dụng **TOAST UI Calendar**, cho phép quản lý tập trung tất cả các sự kiện/lịch hẹn từ nhiều module khác nhau trong hệ thống:

- ✅ **Polymorphic Relationship** - Event có thể thuộc về bất kỳ model nào
- ✅ **Auto Sync** - Tự động đồng bộ từ các module (Customer Interactions, Tasks, Meetings...)
- ✅ **Status Tracking** - Theo dõi trạng thái event real-time
- ✅ **Multi-Category** - Phân loại theo màu sắc và icon
- ✅ **TOAST UI Calendar** - Giao diện calendar chuyên nghiệp, đẹp mắt

---

## 🗄️ Database Schema

### Bảng `calendar_events`

```sql
CREATE TABLE calendar_events (
    id BIGINT PRIMARY KEY,
    
    -- Polymorphic Relationship
    eventable_type VARCHAR(255),  -- App\Models\CustomerInteraction, App\Models\Task, etc.
    eventable_id BIGINT,          -- ID của model gốc
    
    -- Thông tin cơ bản
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(255) DEFAULT 'general',
    
    -- Thời gian
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_all_day BOOLEAN DEFAULT FALSE,
    
    -- Trạng thái
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    
    -- Người tham gia
    user_id BIGINT NOT NULL,     -- Người chịu trách nhiệm
    attendees JSON NULL,         -- Danh sách người tham gia
    
    -- Hiển thị
    color VARCHAR(255) DEFAULT '#3B82F6',
    icon VARCHAR(255) NULL,
    location VARCHAR(255) NULL,
    
    -- Nhắc nhở
    has_reminder BOOLEAN DEFAULT FALSE,
    reminder_minutes_before INT NULL,
    
    -- Metadata
    metadata JSON NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX(eventable_type, eventable_id),
    INDEX(category),
    INDEX(status),
    INDEX(start_date),
    INDEX(end_date),
    INDEX(user_id)
);
```

---

## 🏗️ Kiến Trúc Hệ Thống

### 1. **Polymorphic Relationship**

```php
// CalendarEvent có thể thuộc về bất kỳ model nào
CalendarEvent::where('eventable_type', 'App\Models\CustomerInteraction')
    ->where('eventable_id', 123)
    ->first();

// CustomerInteraction → CalendarEvent
$interaction->calendarEvent; // morphOne

// Task → CalendarEvent (future)
$task->calendarEvent;
```

### 2. **Auto Sync với Event Lifecycle Hooks**

```php
// app/Models/CustomerInteraction.php
protected static function booted()
{
    // Sau khi tạo/cập nhật → sync calendar event
    static::saved(function ($interaction) {
        $interaction->syncCalendarEvent();
    });

    // Sau khi xóa → xóa calendar event
    static::deleted(function ($interaction) {
        $calendarService = app(CalendarEventService::class);
        $calendarService->deleteEvent($interaction);
    });
}
```

### 3. **CalendarEventService - Centralized Management**

```php
// app/Services/CalendarEventService.php

// Sync event từ model khác
$calendarService->syncEvent($customerInteraction, [
    'title' => "Liên hệ lại: {$customer->name}",
    'category' => 'customer_follow_up',
    'start_date' => $interaction->next_follow_up,
    'status' => 'pending',
    'color' => '#F59E0B',
    // ...
]);

// Format cho TOAST UI Calendar
$formatted = $calendarService->formatForToastUI($event);
```

---

## 📊 Category System

### Categories với Màu Sắc & Icon

| Category | Màu | Icon | Mô Tả |
|----------|-----|------|-------|
| `customer_follow_up` | `#F59E0B` (Amber) | 📞 | Liên hệ lại khách hàng |
| `meeting` | `#3B82F6` (Blue) | 👥 | Cuộc họp |
| `task` | `#10B981` (Green) | ✅ | Công việc |
| `deadline` | `#EF4444` (Red) | ⏰ | Deadline |
| `event` | `#8B5CF6` (Purple) | 📅 | Sự kiện |
| `reminder` | `#EC4899` (Pink) | 🔔 | Nhắc nhở |
| `general` | `#6B7280` (Gray) | 📌 | Chung |

---

## 🔄 Flow: Customer Interaction → Calendar Event

### Khi tạo Customer Interaction với `next_follow_up`:

```
1. User tạo CustomerInteraction với next_follow_up = "2025-11-05 14:00:00"
   ↓
2. CustomerInteraction::saved() hook triggered
   ↓
3. syncCalendarEvent() được gọi
   ↓
4. CalendarEventService::syncEvent() tạo/update CalendarEvent
   ↓
5. CalendarEvent được lưu với:
   - eventable_type = "App\Models\CustomerInteraction"
   - eventable_id = 123
   - category = "customer_follow_up"
   - status = "pending"
   - color = "#F59E0B"
   ↓
6. Event xuất hiện trên Calendar UI
```

### Khi cập nhật trạng thái Interaction:

```
1. User cập nhật CustomerInteraction (đã liên hệ xong)
   ↓
2. Interaction status changed
   ↓
3. (Optional) Update CalendarEvent status = "completed"
   ↓
4. Calendar UI hiển thị event với trạng thái mới (màu khác, strikethrough, etc.)
```

### Khi xóa Interaction:

```
1. User xóa CustomerInteraction
   ↓
2. CustomerInteraction::deleted() hook triggered
   ↓
3. CalendarEventService::deleteEvent() được gọi
   ↓
4. CalendarEvent bị xóa
   ↓
5. Event biến mất khỏi Calendar UI
```

---

## 🛣️ API Endpoints

### Calendar Events API

```php
GET    /api/calendar/events
       - Lấy events trong khoảng thời gian
       - Params: start_date, end_date, user_id?, category?
       - Response: Array of events formatted for TOAST UI

GET    /api/calendar/events/upcoming
       - Lấy events sắp tới (chưa hoàn thành)
       - Params: user_id?, limit? (default: 10)

GET    /api/calendar/events/overdue
       - Lấy events quá hạn
       - Params: user_id?

GET    /api/calendar/categories
       - Lấy danh sách categories với màu sắc & icon

POST   /api/calendar/events
       - Tạo standalone event (không liên kết với model khác)
       - Body: { title, description, category, start_date, end_date, ... }

GET    /api/calendar/events/{id}
       - Xem chi tiết event

PUT    /api/calendar/events/{id}
       - Cập nhật event (kể cả status)

DELETE /api/calendar/events/{id}
       - Xóa event (chỉ standalone events)
       - Linked events phải xóa từ module gốc
```

---

## 🎨 Frontend - TOAST UI Calendar

### Component: `CalendarView.vue`

**Đường dẫn:** `resources/js/pages/calendar/CalendarView.vue`

**Chức năng:**
- Hiển thị calendar với TOAST UI
- Support views: Month, Week, Day
- Click event → Xem chi tiết
- Select datetime → Tạo event mới
- Auto load events khi thay đổi view
- Category filtering
- Responsive design

**TOAST UI Config:**
```javascript
new Calendar(container, {
  defaultView: 'month',
  useFormPopup: false,
  useDetailPopup: true,
  calendars: [
    { id: 'customer_follow_up', name: 'Liên Hệ Lại KH', backgroundColor: '#F59E0B' },
    { id: 'meeting', name: 'Cuộc Họp', backgroundColor: '#3B82F6' },
    // ...
  ],
  // ...
});
```

**Event Format for TOAST UI:**
```javascript
{
  id: 123,
  calendarId: 'customer_follow_up',
  title: 'Liên hệ lại: Nguyễn Văn A',
  body: 'Khách hàng quan tâm gói Premium...',
  start: '2025-11-05T14:00:00+07:00',
  end: '2025-11-05T15:00:00+07:00',
  isAllday: false,
  category: 'time',
  backgroundColor: '#F59E0B',
  borderColor: '#F59E0B',
  color: '#ffffff',
  raw: {
    eventable_type: 'App\\Models\\CustomerInteraction',
    eventable_id: 456,
    metadata: { customer_name: 'Nguyễn Văn A', ... }
  }
}
```

---

## 🔐 Permissions

| Permission | Mô Tả |
|------------|-------|
| `calendar.view` | Xem calendar và events |
| `calendar.create` | Tạo event mới |
| `calendar.edit` | Sửa event |
| `calendar.delete` | Xóa event |

---

## 🌐 Translations

### Các key chính (calendar group):

| Key | Vietnamese | English |
|-----|-----------|---------|
| `calendar` | Lịch | Calendar |
| `my_calendar` | Lịch Của Tôi | My Calendar |
| `add_event` | Thêm Sự Kiện | Add Event |
| `upcoming_events` | Sự Kiện Sắp Tới | Upcoming Events |
| `overdue_events` | Sự Kiện Quá Hạn | Overdue Events |
| `status_pending` | Chờ Xử Lý | Pending |
| `status_completed` | Hoàn Thành | Completed |
| `customer_follow_up` | Liên Hệ Lại Khách Hàng | Customer Follow-up |

---

## 📝 Cách Tích Hợp Module Mới

### Ví dụ: Tích hợp Task Module

#### Bước 1: Thêm relationship vào Task Model

```php
// app/Models/Task.php

use App\Services\CalendarEventService;

protected static function booted()
{
    static::saved(function ($task) {
        $task->syncCalendarEvent();
    });

    static::deleted(function ($task) {
        $calendarService = app(CalendarEventService::class);
        $calendarService->deleteEvent($task);
    });
}

public function calendarEvent()
{
    return $this->morphOne(CalendarEvent::class, 'eventable');
}

public function syncCalendarEvent()
{
    if (!$this->due_date) {
        $calendarService = app(CalendarEventService::class);
        $calendarService->deleteEvent($this);
        return;
    }

    $calendarService = app(CalendarEventService::class);
    
    $calendarService->syncEvent($this, [
        'title' => $this->title,
        'description' => $this->description,
        'category' => 'task',
        'start_date' => $this->due_date,
        'end_date' => $this->due_date->addHours(2),
        'status' => $this->status,
        'user_id' => $this->assigned_to,
        'color' => '#10B981',
        'icon' => '✅',
        'metadata' => [
            'task_priority' => $this->priority,
            'task_project' => $this->project->name ?? null,
        ],
    ]);
}
```

#### Bước 2: Thêm category vào CalendarEvent::getCategoryColors()

```php
// app/Models/CalendarEvent.php

public static function getCategoryColors(): array
{
    return [
        'customer_follow_up' => '#F59E0B',
        'task' => '#10B981', // ← Đã có
        // ... thêm categories mới ở đây
    ];
}
```

#### Bước 3: Frontend tự động nhận category mới

Calendar sẽ tự động load categories từ API `/api/calendar/categories`.

---

## 🎯 Use Cases

### 1. Customer Follow-up Tracking
```
- Sales tạo interaction với khách hàng
- Đặt next_follow_up = 3 ngày sau
- Event tự động xuất hiện trên calendar
- Sales xem calendar → biết hôm nay phải gọi cho ai
- Sau khi gọi xong → cập nhật interaction
- (Optional) Event status → completed
```

### 2. Team Meeting Management
```
- Manager tạo event meeting
- Category = 'meeting'
- Thêm attendees = [user1, user2, user3]
- Tất cả members xem calendar → thấy meeting
- Có reminder 30 phút trước
```

### 3. Deadline Tracking
```
- Project deadline được sync vào calendar
- Category = 'deadline', color = red
- Hiển thị nổi bật
- Overdue events được highlight
```

### 4. Multi-Module View
```
- Calendar hiển thị TẤT CẢ events từ mọi module:
  • Customer follow-ups (amber)
  • Tasks (green)
  • Meetings (blue)
  • Deadlines (red)
- Filter theo category
- Filter theo user
```

---

## 🚀 Deployment Checklist

- [x] Migration `calendar_events` table
- [x] Model `CalendarEvent` với polymorphic
- [x] Service `CalendarEventService`
- [x] Controller `CalendarEventController`
- [x] Routes `/api/calendar/*`
- [x] Update `CustomerInteraction` model với sync logic
- [x] Seeder permissions & translations
- [x] Install `@toast-ui/calendar` npm package
- [x] Frontend `CalendarView.vue`
- [x] Router integration
- [x] Sidebar link
- [x] Build & test

---

## 🧪 Testing

### 1. Test Calendar Event Auto Sync
```bash
# Tạo customer interaction với next_follow_up
POST /api/customers/1/interactions
{
  "interaction_type_id": 1,
  "interaction_result_id": 3,
  "notes": "Khách hàng quan tâm",
  "interaction_date": "2025-10-31 10:00:00",
  "next_follow_up": "2025-11-05 14:00:00"
}

# Kiểm tra calendar event đã được tạo
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Xóa interaction
DELETE /api/customers/1/interactions/123

# Kiểm tra calendar event đã bị xóa
GET /api/calendar/events/...
```

### 2. Test Calendar UI
```
1. Login và vào /calendar
2. Calendar hiển thị tháng hiện tại
3. Click vào event → popup chi tiết
4. Click "Add Event" → form tạo event
5. Thay đổi view: Month → Week → Day
6. Filter theo category
```

---

## 📈 Future Enhancements

1. **Recurring Events** - Sự kiện lặp lại (hàng ngày, hàng tuần, hàng tháng)
2. **Drag & Drop** - Kéo thả event để đổi ngày giờ
3. **Email/SMS Reminders** - Gửi nhắc nhở tự động
4. **Calendar Sharing** - Chia sẻ calendar giữa users/teams
5. **Google Calendar Sync** - Đồng bộ với Google Calendar
6. **Task Dependencies** - Liên kết events phụ thuộc nhau
7. **Time Tracking** - Theo dõi thời gian thực tế vs dự kiến
8. **Analytics Dashboard** - Thống kê events completed, overdue, etc.

---

## 🎊 Kết Quả

### ✅ Đã Hoàn Thành 100%

#### Backend:
- ✅ Polymorphic Calendar Event model
- ✅ CalendarEventService với sync logic
- ✅ Auto sync từ CustomerInteraction
- ✅ CRUD API endpoints
- ✅ Permissions & Translations

#### Frontend:
- ✅ TOAST UI Calendar integration
- ✅ CalendarView component
- ✅ Router & Sidebar integration
- ✅ Category filtering
- ✅ Multi-view support (Month/Week/Day)

#### Architecture:
- ✅ Mở rộng dễ dàng cho modules mới
- ✅ Centralized calendar management
- ✅ Real-time status tracking
- ✅ Clean polymorphic design

---

**🎉 Hệ thống Calendar Module với TOAST UI đã sẵn sàng sử dụng!**

Giờ mỗi khi có `next_follow_up` trong Customer Interaction, nó sẽ tự động xuất hiện trên Calendar. Trong tương lai, bất kỳ module nào (Tasks, Meetings, Deadlines) cũng có thể dễ dàng tích hợp vào Calendar này! 🚀


## 🎯 Tổng Quan

Hệ thống Calendar Module trung tâm sử dụng **TOAST UI Calendar**, cho phép quản lý tập trung tất cả các sự kiện/lịch hẹn từ nhiều module khác nhau trong hệ thống:

- ✅ **Polymorphic Relationship** - Event có thể thuộc về bất kỳ model nào
- ✅ **Auto Sync** - Tự động đồng bộ từ các module (Customer Interactions, Tasks, Meetings...)
- ✅ **Status Tracking** - Theo dõi trạng thái event real-time
- ✅ **Multi-Category** - Phân loại theo màu sắc và icon
- ✅ **TOAST UI Calendar** - Giao diện calendar chuyên nghiệp, đẹp mắt

---

## 🗄️ Database Schema

### Bảng `calendar_events`

```sql
CREATE TABLE calendar_events (
    id BIGINT PRIMARY KEY,
    
    -- Polymorphic Relationship
    eventable_type VARCHAR(255),  -- App\Models\CustomerInteraction, App\Models\Task, etc.
    eventable_id BIGINT,          -- ID của model gốc
    
    -- Thông tin cơ bản
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(255) DEFAULT 'general',
    
    -- Thời gian
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_all_day BOOLEAN DEFAULT FALSE,
    
    -- Trạng thái
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    
    -- Người tham gia
    user_id BIGINT NOT NULL,     -- Người chịu trách nhiệm
    attendees JSON NULL,         -- Danh sách người tham gia
    
    -- Hiển thị
    color VARCHAR(255) DEFAULT '#3B82F6',
    icon VARCHAR(255) NULL,
    location VARCHAR(255) NULL,
    
    -- Nhắc nhở
    has_reminder BOOLEAN DEFAULT FALSE,
    reminder_minutes_before INT NULL,
    
    -- Metadata
    metadata JSON NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX(eventable_type, eventable_id),
    INDEX(category),
    INDEX(status),
    INDEX(start_date),
    INDEX(end_date),
    INDEX(user_id)
);
```

---

## 🏗️ Kiến Trúc Hệ Thống

### 1. **Polymorphic Relationship**

```php
// CalendarEvent có thể thuộc về bất kỳ model nào
CalendarEvent::where('eventable_type', 'App\Models\CustomerInteraction')
    ->where('eventable_id', 123)
    ->first();

// CustomerInteraction → CalendarEvent
$interaction->calendarEvent; // morphOne

// Task → CalendarEvent (future)
$task->calendarEvent;
```

### 2. **Auto Sync với Event Lifecycle Hooks**

```php
// app/Models/CustomerInteraction.php
protected static function booted()
{
    // Sau khi tạo/cập nhật → sync calendar event
    static::saved(function ($interaction) {
        $interaction->syncCalendarEvent();
    });

    // Sau khi xóa → xóa calendar event
    static::deleted(function ($interaction) {
        $calendarService = app(CalendarEventService::class);
        $calendarService->deleteEvent($interaction);
    });
}
```

### 3. **CalendarEventService - Centralized Management**

```php
// app/Services/CalendarEventService.php

// Sync event từ model khác
$calendarService->syncEvent($customerInteraction, [
    'title' => "Liên hệ lại: {$customer->name}",
    'category' => 'customer_follow_up',
    'start_date' => $interaction->next_follow_up,
    'status' => 'pending',
    'color' => '#F59E0B',
    // ...
]);

// Format cho TOAST UI Calendar
$formatted = $calendarService->formatForToastUI($event);
```

---

## 📊 Category System

### Categories với Màu Sắc & Icon

| Category | Màu | Icon | Mô Tả |
|----------|-----|------|-------|
| `customer_follow_up` | `#F59E0B` (Amber) | 📞 | Liên hệ lại khách hàng |
| `meeting` | `#3B82F6` (Blue) | 👥 | Cuộc họp |
| `task` | `#10B981` (Green) | ✅ | Công việc |
| `deadline` | `#EF4444` (Red) | ⏰ | Deadline |
| `event` | `#8B5CF6` (Purple) | 📅 | Sự kiện |
| `reminder` | `#EC4899` (Pink) | 🔔 | Nhắc nhở |
| `general` | `#6B7280` (Gray) | 📌 | Chung |

---

## 🔄 Flow: Customer Interaction → Calendar Event

### Khi tạo Customer Interaction với `next_follow_up`:

```
1. User tạo CustomerInteraction với next_follow_up = "2025-11-05 14:00:00"
   ↓
2. CustomerInteraction::saved() hook triggered
   ↓
3. syncCalendarEvent() được gọi
   ↓
4. CalendarEventService::syncEvent() tạo/update CalendarEvent
   ↓
5. CalendarEvent được lưu với:
   - eventable_type = "App\Models\CustomerInteraction"
   - eventable_id = 123
   - category = "customer_follow_up"
   - status = "pending"
   - color = "#F59E0B"
   ↓
6. Event xuất hiện trên Calendar UI
```

### Khi cập nhật trạng thái Interaction:

```
1. User cập nhật CustomerInteraction (đã liên hệ xong)
   ↓
2. Interaction status changed
   ↓
3. (Optional) Update CalendarEvent status = "completed"
   ↓
4. Calendar UI hiển thị event với trạng thái mới (màu khác, strikethrough, etc.)
```

### Khi xóa Interaction:

```
1. User xóa CustomerInteraction
   ↓
2. CustomerInteraction::deleted() hook triggered
   ↓
3. CalendarEventService::deleteEvent() được gọi
   ↓
4. CalendarEvent bị xóa
   ↓
5. Event biến mất khỏi Calendar UI
```

---

## 🛣️ API Endpoints

### Calendar Events API

```php
GET    /api/calendar/events
       - Lấy events trong khoảng thời gian
       - Params: start_date, end_date, user_id?, category?
       - Response: Array of events formatted for TOAST UI

GET    /api/calendar/events/upcoming
       - Lấy events sắp tới (chưa hoàn thành)
       - Params: user_id?, limit? (default: 10)

GET    /api/calendar/events/overdue
       - Lấy events quá hạn
       - Params: user_id?

GET    /api/calendar/categories
       - Lấy danh sách categories với màu sắc & icon

POST   /api/calendar/events
       - Tạo standalone event (không liên kết với model khác)
       - Body: { title, description, category, start_date, end_date, ... }

GET    /api/calendar/events/{id}
       - Xem chi tiết event

PUT    /api/calendar/events/{id}
       - Cập nhật event (kể cả status)

DELETE /api/calendar/events/{id}
       - Xóa event (chỉ standalone events)
       - Linked events phải xóa từ module gốc
```

---

## 🎨 Frontend - TOAST UI Calendar

### Component: `CalendarView.vue`

**Đường dẫn:** `resources/js/pages/calendar/CalendarView.vue`

**Chức năng:**
- Hiển thị calendar với TOAST UI
- Support views: Month, Week, Day
- Click event → Xem chi tiết
- Select datetime → Tạo event mới
- Auto load events khi thay đổi view
- Category filtering
- Responsive design

**TOAST UI Config:**
```javascript
new Calendar(container, {
  defaultView: 'month',
  useFormPopup: false,
  useDetailPopup: true,
  calendars: [
    { id: 'customer_follow_up', name: 'Liên Hệ Lại KH', backgroundColor: '#F59E0B' },
    { id: 'meeting', name: 'Cuộc Họp', backgroundColor: '#3B82F6' },
    // ...
  ],
  // ...
});
```

**Event Format for TOAST UI:**
```javascript
{
  id: 123,
  calendarId: 'customer_follow_up',
  title: 'Liên hệ lại: Nguyễn Văn A',
  body: 'Khách hàng quan tâm gói Premium...',
  start: '2025-11-05T14:00:00+07:00',
  end: '2025-11-05T15:00:00+07:00',
  isAllday: false,
  category: 'time',
  backgroundColor: '#F59E0B',
  borderColor: '#F59E0B',
  color: '#ffffff',
  raw: {
    eventable_type: 'App\\Models\\CustomerInteraction',
    eventable_id: 456,
    metadata: { customer_name: 'Nguyễn Văn A', ... }
  }
}
```

---

## 🔐 Permissions

| Permission | Mô Tả |
|------------|-------|
| `calendar.view` | Xem calendar và events |
| `calendar.create` | Tạo event mới |
| `calendar.edit` | Sửa event |
| `calendar.delete` | Xóa event |

---

## 🌐 Translations

### Các key chính (calendar group):

| Key | Vietnamese | English |
|-----|-----------|---------|
| `calendar` | Lịch | Calendar |
| `my_calendar` | Lịch Của Tôi | My Calendar |
| `add_event` | Thêm Sự Kiện | Add Event |
| `upcoming_events` | Sự Kiện Sắp Tới | Upcoming Events |
| `overdue_events` | Sự Kiện Quá Hạn | Overdue Events |
| `status_pending` | Chờ Xử Lý | Pending |
| `status_completed` | Hoàn Thành | Completed |
| `customer_follow_up` | Liên Hệ Lại Khách Hàng | Customer Follow-up |

---

## 📝 Cách Tích Hợp Module Mới

### Ví dụ: Tích hợp Task Module

#### Bước 1: Thêm relationship vào Task Model

```php
// app/Models/Task.php

use App\Services\CalendarEventService;

protected static function booted()
{
    static::saved(function ($task) {
        $task->syncCalendarEvent();
    });

    static::deleted(function ($task) {
        $calendarService = app(CalendarEventService::class);
        $calendarService->deleteEvent($task);
    });
}

public function calendarEvent()
{
    return $this->morphOne(CalendarEvent::class, 'eventable');
}

public function syncCalendarEvent()
{
    if (!$this->due_date) {
        $calendarService = app(CalendarEventService::class);
        $calendarService->deleteEvent($this);
        return;
    }

    $calendarService = app(CalendarEventService::class);
    
    $calendarService->syncEvent($this, [
        'title' => $this->title,
        'description' => $this->description,
        'category' => 'task',
        'start_date' => $this->due_date,
        'end_date' => $this->due_date->addHours(2),
        'status' => $this->status,
        'user_id' => $this->assigned_to,
        'color' => '#10B981',
        'icon' => '✅',
        'metadata' => [
            'task_priority' => $this->priority,
            'task_project' => $this->project->name ?? null,
        ],
    ]);
}
```

#### Bước 2: Thêm category vào CalendarEvent::getCategoryColors()

```php
// app/Models/CalendarEvent.php

public static function getCategoryColors(): array
{
    return [
        'customer_follow_up' => '#F59E0B',
        'task' => '#10B981', // ← Đã có
        // ... thêm categories mới ở đây
    ];
}
```

#### Bước 3: Frontend tự động nhận category mới

Calendar sẽ tự động load categories từ API `/api/calendar/categories`.

---

## 🎯 Use Cases

### 1. Customer Follow-up Tracking
```
- Sales tạo interaction với khách hàng
- Đặt next_follow_up = 3 ngày sau
- Event tự động xuất hiện trên calendar
- Sales xem calendar → biết hôm nay phải gọi cho ai
- Sau khi gọi xong → cập nhật interaction
- (Optional) Event status → completed
```

### 2. Team Meeting Management
```
- Manager tạo event meeting
- Category = 'meeting'
- Thêm attendees = [user1, user2, user3]
- Tất cả members xem calendar → thấy meeting
- Có reminder 30 phút trước
```

### 3. Deadline Tracking
```
- Project deadline được sync vào calendar
- Category = 'deadline', color = red
- Hiển thị nổi bật
- Overdue events được highlight
```

### 4. Multi-Module View
```
- Calendar hiển thị TẤT CẢ events từ mọi module:
  • Customer follow-ups (amber)
  • Tasks (green)
  • Meetings (blue)
  • Deadlines (red)
- Filter theo category
- Filter theo user
```

---

## 🚀 Deployment Checklist

- [x] Migration `calendar_events` table
- [x] Model `CalendarEvent` với polymorphic
- [x] Service `CalendarEventService`
- [x] Controller `CalendarEventController`
- [x] Routes `/api/calendar/*`
- [x] Update `CustomerInteraction` model với sync logic
- [x] Seeder permissions & translations
- [x] Install `@toast-ui/calendar` npm package
- [x] Frontend `CalendarView.vue`
- [x] Router integration
- [x] Sidebar link
- [x] Build & test

---

## 🧪 Testing

### 1. Test Calendar Event Auto Sync
```bash
# Tạo customer interaction với next_follow_up
POST /api/customers/1/interactions
{
  "interaction_type_id": 1,
  "interaction_result_id": 3,
  "notes": "Khách hàng quan tâm",
  "interaction_date": "2025-10-31 10:00:00",
  "next_follow_up": "2025-11-05 14:00:00"
}

# Kiểm tra calendar event đã được tạo
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Xóa interaction
DELETE /api/customers/1/interactions/123

# Kiểm tra calendar event đã bị xóa
GET /api/calendar/events/...
```

### 2. Test Calendar UI
```
1. Login và vào /calendar
2. Calendar hiển thị tháng hiện tại
3. Click vào event → popup chi tiết
4. Click "Add Event" → form tạo event
5. Thay đổi view: Month → Week → Day
6. Filter theo category
```

---

## 📈 Future Enhancements

1. **Recurring Events** - Sự kiện lặp lại (hàng ngày, hàng tuần, hàng tháng)
2. **Drag & Drop** - Kéo thả event để đổi ngày giờ
3. **Email/SMS Reminders** - Gửi nhắc nhở tự động
4. **Calendar Sharing** - Chia sẻ calendar giữa users/teams
5. **Google Calendar Sync** - Đồng bộ với Google Calendar
6. **Task Dependencies** - Liên kết events phụ thuộc nhau
7. **Time Tracking** - Theo dõi thời gian thực tế vs dự kiến
8. **Analytics Dashboard** - Thống kê events completed, overdue, etc.

---

## 🎊 Kết Quả

### ✅ Đã Hoàn Thành 100%

#### Backend:
- ✅ Polymorphic Calendar Event model
- ✅ CalendarEventService với sync logic
- ✅ Auto sync từ CustomerInteraction
- ✅ CRUD API endpoints
- ✅ Permissions & Translations

#### Frontend:
- ✅ TOAST UI Calendar integration
- ✅ CalendarView component
- ✅ Router & Sidebar integration
- ✅ Category filtering
- ✅ Multi-view support (Month/Week/Day)

#### Architecture:
- ✅ Mở rộng dễ dàng cho modules mới
- ✅ Centralized calendar management
- ✅ Real-time status tracking
- ✅ Clean polymorphic design

---

**🎉 Hệ thống Calendar Module với TOAST UI đã sẵn sàng sử dụng!**

Giờ mỗi khi có `next_follow_up` trong Customer Interaction, nó sẽ tự động xuất hiện trên Calendar. Trong tương lai, bất kỳ module nào (Tasks, Meetings, Deadlines) cũng có thể dễ dàng tích hợp vào Calendar này! 🚀
















