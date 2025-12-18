# 🎓 Thiết Kế Chức Năng Học Thử (Trial Class)

**Date:** November 5, 2025  
**Status:** 📋 DESIGN  
**Priority:** High

---

## 🎯 Mục Tiêu

Cho phép khách hàng (Customer) và con của khách hàng (CustomerChild) đăng ký học thử tại các lớp học hiện có. Hệ thống sẽ:
- Hiển thị nút "Học thử" bên cạnh nút "Đặt lịch test" trong Customer Detail
- Cho phép chọn lớp và buổi học cụ thể để học thử
- Hiển thị biểu tượng đặc biệt trên Calendar khi buổi học có học viên thử
- Quản lý danh sách học viên thử trong chi tiết lớp học

---

## 📊 Phân Tích Yêu Cầu

### Use Cases:

**UC1: Đăng Ký Học Thử**
- **Actor:** Nhân viên tư vấn
- **Flow:**
  1. Mở Customer Detail Modal
  2. Click nút "Học thử" (cho customer hoặc child)
  3. Modal hiển thị danh sách lớp học (có thể filter)
  4. Chọn lớp → Hiển thị danh sách buổi học chưa diễn ra
  5. Tick chọn buổi học cụ thể (có thể chọn nhiều buổi)
  6. Nhập ghi chú (optional)
  7. Xác nhận → Hệ thống tạo record trial_students

**UC2: Xem Học Viên Thử Trên Calendar**
- **Actor:** Giáo viên, Quản lý
- **Flow:**
  1. Xem Calendar
  2. Buổi học có học viên thử hiển thị biểu tượng đặc biệt (🎓👤 hoặc badge số)
  3. Click vào event → Popup hiển thị danh sách học viên thử

**UC3: Quản Lý Học Viên Thử Trong Lớp**
- **Actor:** Giáo viên, Quản lý
- **Flow:**
  1. Vào chi tiết lớp học → Tab "Lesson Sessions"
  2. Mỗi buổi học hiển thị số học viên thử
  3. Click xem danh sách học viên thử
  4. Có thể đánh dấu "đã tham gia" hoặc "vắng"

**UC4: Chuyển Đổi Học Viên Thử Thành Chính Thức**
- **Actor:** Nhân viên tư vấn, Quản lý
- **Flow:**
  1. Sau khi học thử
  2. Nếu đồng ý học chính thức → Chuyển customer/child thành học viên chính thức của lớp
  3. Xóa/archive record trial_students

---

## 🗄️ Database Schema

### 1. Bảng Mới: `trial_students`

```sql
CREATE TABLE trial_students (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    
    -- Trial student có thể là Customer hoặc CustomerChild
    trialable_type VARCHAR(255) NOT NULL,  -- App\Models\Customer hoặc App\Models\CustomerChild
    trialable_id BIGINT NOT NULL,
    
    -- Lớp học và buổi học cụ thể
    class_id BIGINT NOT NULL,
    class_lesson_session_id BIGINT NOT NULL,
    
    -- Thông tin đăng ký
    registered_by BIGINT NOT NULL,  -- User ID người đăng ký
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Trạng thái tham gia
    status ENUM('registered', 'attended', 'absent', 'cancelled', 'converted') DEFAULT 'registered',
    -- registered: Đã đăng ký
    -- attended: Đã tham gia học thử
    -- absent: Vắng buổi học thử
    -- cancelled: Hủy học thử
    -- converted: Đã chuyển thành học viên chính thức
    
    -- Feedback sau học thử
    feedback TEXT NULL,  -- Nhận xét của giáo viên
    rating INT NULL,     -- Đánh giá 1-5 sao
    
    -- Ghi chú
    notes TEXT NULL,
    
    -- Metadata
    branch_id BIGINT NULL,  -- Chi nhánh
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_trialable (trialable_type, trialable_id),
    INDEX idx_class (class_id),
    INDEX idx_session (class_lesson_session_id),
    INDEX idx_status (status),
    INDEX idx_registered_at (registered_at),
    
    -- Foreign Keys
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (class_lesson_session_id) REFERENCES class_lesson_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    
    -- Constraint: Không cho phép đăng ký trùng
    UNIQUE KEY unique_trial_registration (trialable_type, trialable_id, class_lesson_session_id)
);
```

**Lý do thiết kế:**
- ✅ **Polymorphic:** Trial student có thể là Customer hoặc Child
- ✅ **Session-specific:** Đăng ký cho từng buổi học cụ thể, không phải cả lớp
- ✅ **Status tracking:** Theo dõi trạng thái từ đăng ký → tham gia → chuyển đổi
- ✅ **Feedback:** Giáo viên có thể đánh giá sau buổi học thử
- ✅ **Unique constraint:** Tránh đăng ký trùng lặp

---

## 🏗️ Kiến Trúc Hệ Thống

### 1. Models

#### a. TrialStudent Model (NEW)

**File:** `app/Models/TrialStudent.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TrialStudent extends Model
{
    protected $fillable = [
        'trialable_type',
        'trialable_id',
        'class_id',
        'class_lesson_session_id',
        'registered_by',
        'registered_at',
        'status',
        'feedback',
        'rating',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'rating' => 'integer',
    ];

    /**
     * Polymorphic: Customer or CustomerChild
     */
    public function trialable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Class relationship
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Lesson session relationship
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassLessonSession::class, 'class_lesson_session_id');
    }

    /**
     * Registered by (User)
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Branch relationship
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['registered', 'attended']);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('class_lesson_session_id', $sessionId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Get trial student name
     */
    public function getTrialStudentNameAttribute(): string
    {
        return $this->trialable?->name ?? 'Unknown';
    }

    /**
     * Get trial student type display
     */
    public function getTrialStudentTypeAttribute(): string
    {
        if ($this->trialable_type === 'App\Models\Customer') {
            return 'Khách hàng';
        }
        return 'Con của KH';
    }
}
```

#### b. Customer Model - Thêm Relationships

```php
// app/Models/Customer.php

/**
 * Trial class registrations
 */
public function trialClasses()
{
    return $this->morphMany(TrialStudent::class, 'trialable');
}

/**
 * Active trial registrations
 */
public function activeTrials()
{
    return $this->trialClasses()->active();
}
```

#### c. CustomerChild Model - Thêm Relationships

```php
// app/Models/CustomerChild.php

/**
 * Trial class registrations
 */
public function trialClasses()
{
    return $this->morphMany(TrialStudent::class, 'trialable');
}

/**
 * Active trial registrations
 */
public function activeTrials()
{
    return $this->trialClasses()->active();
}
```

#### d. ClassLessonSession Model - Thêm Relationships

```php
// app/Models/ClassLessonSession.php

/**
 * Trial students for this session
 */
public function trialStudents()
{
    return $this->hasMany(TrialStudent::class, 'class_lesson_session_id');
}

/**
 * Active trial students (registered or attended)
 */
public function activeTrialStudents()
{
    return $this->trialStudents()->active();
}

/**
 * Count active trial students
 */
public function getTrialStudentsCountAttribute(): int
{
    return $this->activeTrialStudents()->count();
}
```

---

### 2. Controllers

#### a. TrialStudentController (NEW)

**File:** `app/Http/Controllers/Api/TrialStudentController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrialStudent;
use App\Models\Customer;
use App\Models\CustomerChild;
use App\Models\ClassModel;
use App\Models\ClassLessonSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TrialStudentController extends Controller
{
    /**
     * Get available classes for trial
     */
    public function getAvailableClasses(Request $request)
    {
        $branchId = $request->input('branch_id');
        $level = $request->input('level');
        
        $query = ClassModel::with(['homeroomTeacher', 'subject', 'branch'])
            ->where('status', 'active')
            ->where('is_active', true);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        if ($level) {
            $query->where('level', $level);
        }
        
        $classes = $query->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    /**
     * Get available sessions for a class (chưa diễn ra)
     */
    public function getAvailableSessions($classId)
    {
        $sessions = ClassLessonSession::where('class_id', $classId)
            ->where('status', 'scheduled')
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('session_number')
            ->get();
        
        // Add trial students count to each session
        $sessions->each(function ($session) {
            $session->trial_count = $session->activeTrialStudents()->count();
        });
        
        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Register for trial class
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'trialable_type' => 'required|in:customer,child',
            'trialable_id' => 'required|integer',
            'class_id' => 'required|exists:classes,id',
            'session_ids' => 'required|array|min:1',
            'session_ids.*' => 'exists:class_lesson_sessions,id',
            'notes' => 'nullable|string',
        ]);
        
        $user = Auth::user();
        
        // Determine trialable model
        if ($validated['trialable_type'] === 'customer') {
            $trialable = Customer::findOrFail($validated['trialable_id']);
            $trialableType = Customer::class;
        } else {
            $trialable = CustomerChild::findOrFail($validated['trialable_id']);
            $trialableType = CustomerChild::class;
        }
        
        $class = ClassModel::findOrFail($validated['class_id']);
        
        $registeredSessions = [];
        $skippedSessions = [];
        
        DB::transaction(function () use ($validated, $trialableType, $class, $user, &$registeredSessions, &$skippedSessions) {
            foreach ($validated['session_ids'] as $sessionId) {
                // Check if already registered
                $exists = TrialStudent::where('trialable_type', $trialableType)
                    ->where('trialable_id', $validated['trialable_id'])
                    ->where('class_lesson_session_id', $sessionId)
                    ->exists();
                
                if ($exists) {
                    $skippedSessions[] = $sessionId;
                    continue;
                }
                
                // Create trial registration
                $trial = TrialStudent::create([
                    'trialable_type' => $trialableType,
                    'trialable_id' => $validated['trialable_id'],
                    'class_id' => $validated['class_id'],
                    'class_lesson_session_id' => $sessionId,
                    'registered_by' => $user->id,
                    'registered_at' => now(),
                    'status' => 'registered',
                    'notes' => $validated['notes'] ?? null,
                    'branch_id' => $class->branch_id,
                ]);
                
                $registeredSessions[] = $trial;
            }
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Đăng ký học thử thành công!',
            'data' => [
                'registered' => count($registeredSessions),
                'skipped' => count($skippedSessions),
                'total' => count($validated['session_ids'])
            ]
        ], 201);
    }

    /**
     * Get trial students for a session
     */
    public function getSessionTrialStudents($sessionId)
    {
        $trialStudents = TrialStudent::with(['trialable', 'registeredBy'])
            ->where('class_lesson_session_id', $sessionId)
            ->active()
            ->orderBy('registered_at')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $trialStudents
        ]);
    }

    /**
     * Update trial student status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:attended,absent,cancelled,converted',
            'feedback' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);
        
        $trial = TrialStudent::findOrFail($id);
        $trial->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $trial->fresh(['trialable', 'session'])
        ]);
    }

    /**
     * Cancel trial registration
     */
    public function cancel($id)
    {
        $trial = TrialStudent::findOrFail($id);
        $trial->update(['status' => 'cancelled']);
        
        return response()->json([
            'success' => true,
            'message' => 'Đã hủy đăng ký học thử'
        ]);
    }
}
```

---

### 3. Routes

**File:** `routes/api.php`

```php
// Trial Students
Route::prefix('trial-students')->group(function () {
    Route::get('/available-classes', [TrialStudentController::class, 'getAvailableClasses'])
        ->middleware('permission:calendar.create');
    Route::get('/classes/{classId}/sessions', [TrialStudentController::class, 'getAvailableSessions'])
        ->middleware('permission:calendar.create');
    Route::post('/register', [TrialStudentController::class, 'register'])
        ->middleware('permission:calendar.create');
    Route::get('/sessions/{sessionId}', [TrialStudentController::class, 'getSessionTrialStudents'])
        ->middleware('permission:classes.view');
    Route::put('/{id}/status', [TrialStudentController::class, 'updateStatus'])
        ->middleware('permission:classes.edit');
    Route::delete('/{id}', [TrialStudentController::class, 'cancel'])
        ->middleware('permission:classes.edit');
});
```

---

## 🎨 Frontend Implementation

### 1. Component: TrialClassModal.vue (NEW)

**File:** `resources/js/components/customers/TrialClassModal.vue`

```vue
<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
      @click.self="close"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between rounded-t-lg">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Đăng Ký Học Thử</h2>
            <p class="text-sm text-gray-600 mt-1">{{ trialStudentName }}</p>
          </div>
          <button @click="close" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Steps -->
        <div class="px-6 py-4 border-b">
          <div class="flex items-center justify-center space-x-4">
            <div :class="['flex items-center', step === 1 ? 'text-blue-600' : 'text-gray-400']">
              <div :class="['w-8 h-8 rounded-full flex items-center justify-center', step === 1 ? 'bg-blue-600 text-white' : 'bg-gray-200']">
                1
              </div>
              <span class="ml-2">Chọn lớp</span>
            </div>
            <div class="w-16 h-0.5 bg-gray-300"></div>
            <div :class="['flex items-center', step === 2 ? 'text-blue-600' : 'text-gray-400']">
              <div :class="['w-8 h-8 rounded-full flex items-center justify-center', step === 2 ? 'bg-blue-600 text-white' : 'bg-gray-200']">
                2
              </div>
              <span class="ml-2">Chọn buổi học</span>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
          <!-- Step 1: Select Class -->
          <div v-if="step === 1">
            <div class="mb-4">
              <input
                v-model="searchClass"
                type="text"
                placeholder="Tìm kiếm lớp..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div v-if="loadingClasses" class="text-center py-8">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="filteredClasses.length === 0" class="text-center py-8 text-gray-500">
              Không tìm thấy lớp học nào
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="cls in filteredClasses"
                :key="cls.id"
                @click="selectClass(cls)"
                :class="[
                  'border rounded-lg p-4 cursor-pointer transition',
                  selectedClass?.id === cls.id
                    ? 'border-blue-600 bg-blue-50'
                    : 'border-gray-200 hover:border-blue-400 hover:bg-gray-50'
                ]"
              >
                <div class="flex items-center justify-between mb-2">
                  <h4 class="font-semibold text-gray-900">{{ cls.name }}</h4>
                  <span v-if="selectedClass?.id === cls.id" class="text-blue-600">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </span>
                </div>
                <div class="space-y-1 text-sm text-gray-600">
                  <div><span class="font-medium">Mã lớp:</span> {{ cls.code }}</div>
                  <div><span class="font-medium">Giáo viên:</span> {{ cls.homeroom_teacher?.name || 'N/A' }}</div>
                  <div><span class="font-medium">Học viên:</span> {{ cls.current_students }}/{{ cls.capacity }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Select Sessions -->
          <div v-if="step === 2">
            <div class="mb-4">
              <button @click="step = 1" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Quay lại chọn lớp
              </button>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white">
                  🎓
                </div>
                <div>
                  <h4 class="font-semibold text-gray-900">{{ selectedClass?.name }}</h4>
                  <p class="text-sm text-gray-600">{{ selectedClass?.code }}</p>
                </div>
              </div>
            </div>

            <div v-if="loadingSessions" class="text-center py-8">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="availableSessions.length === 0" class="text-center py-8 text-gray-500">
              Không có buổi học nào sắp tới
            </div>

            <div v-else>
              <div class="mb-4">
                <label class="flex items-center gap-2">
                  <input
                    v-model="selectAll"
                    type="checkbox"
                    @change="toggleSelectAll"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                  <span class="text-sm font-medium text-gray-700">Chọn tất cả ({{ availableSessions.length }} buổi)</span>
                </label>
              </div>

              <div class="space-y-3">
                <label
                  v-for="session in availableSessions"
                  :key="session.id"
                  :class="[
                    'flex items-center gap-4 p-4 border rounded-lg cursor-pointer transition',
                    selectedSessions.includes(session.id)
                      ? 'border-blue-600 bg-blue-50'
                      : 'border-gray-200 hover:border-blue-400 hover:bg-gray-50'
                  ]"
                >
                  <input
                    v-model="selectedSessions"
                    type="checkbox"
                    :value="session.id"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                  <div class="flex-1">
                    <div class="flex items-center justify-between">
                      <h5 class="font-semibold text-gray-900">
                        Buổi {{ session.session_number }}: {{ session.lesson_title }}
                      </h5>
                      <span v-if="session.trial_count > 0" class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">
                        {{ session.trial_count }} học thử
                      </span>
                    </div>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                      <span>📅 {{ formatDate(session.scheduled_date) }}</span>
                      <span>🕐 {{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}</span>
                    </div>
                  </div>
                </label>
              </div>

              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú (tùy chọn)</label>
                <textarea
                  v-model="notes"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Ghi chú về học thử..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t px-6 py-4 flex justify-between">
          <button
            @click="close"
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
          >
            Hủy
          </button>
          <button
            v-if="step === 1"
            @click="goToStep2"
            :disabled="!selectedClass"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Tiếp theo →
          </button>
          <button
            v-if="step === 2"
            @click="register"
            :disabled="selectedSessions.length === 0 || registering"
            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ registering ? 'Đang đăng ký...' : `Đăng ký (${selectedSessions.length} buổi)` }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';
import dayjs from 'dayjs';

const props = defineProps({
  show: { type: Boolean, default: false },
  trialableType: { type: String, required: true }, // 'customer' or 'child'
  trialableId: { type: Number, required: true },
  trialableName: { type: String, required: true },
});

const emit = defineEmits(['close', 'registered']);

const swal = useSwal();

const step = ref(1);
const searchClass = ref('');
const classes = ref([]);
const selectedClass = ref(null);
const availableSessions = ref([]);
const selectedSessions = ref([]);
const selectAll = ref(false);
const notes = ref('');
const loadingClasses = ref(false);
const loadingSessions = ref(false);
const registering = ref(false);

const trialStudentName = computed(() => props.trialableName);

const filteredClasses = computed(() => {
  if (!searchClass.value) return classes.value;
  const search = searchClass.value.toLowerCase();
  return classes.value.filter(cls => 
    cls.name.toLowerCase().includes(search) || 
    cls.code.toLowerCase().includes(search)
  );
});

const loadClasses = async () => {
  loadingClasses.value = true;
  try {
    const response = await api.get('/api/trial-students/available-classes');
    if (response.data.success) {
      classes.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load classes:', error);
    swal.error('Không thể tải danh sách lớp');
  } finally {
    loadingClasses.value = false;
  }
};

const selectClass = (cls) => {
  selectedClass.value = cls;
};

const goToStep2 = async () => {
  if (!selectedClass.value) return;
  
  step.value = 2;
  await loadSessions();
};

const loadSessions = async () => {
  loadingSessions.value = true;
  try {
    const response = await api.get(`/api/trial-students/classes/${selectedClass.value.id}/sessions`);
    if (response.data.success) {
      availableSessions.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load sessions:', error);
    swal.error('Không thể tải danh sách buổi học');
  } finally {
    loadingSessions.value = false;
  }
};

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedSessions.value = availableSessions.value.map(s => s.id);
  } else {
    selectedSessions.value = [];
  }
};

const register = async () => {
  if (selectedSessions.value.length === 0) return;
  
  registering.value = true;
  try {
    const response = await api.post('/api/trial-students/register', {
      trialable_type: props.trialableType,
      trialable_id: props.trialableId,
      class_id: selectedClass.value.id,
      session_ids: selectedSessions.value,
      notes: notes.value,
    });
    
    if (response.data.success) {
      swal.success(response.data.message);
      emit('registered');
      close();
    }
  } catch (error) {
    console.error('Failed to register:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi đăng ký');
  } finally {
    registering.value = false;
  }
};

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY');
};

const formatTime = (time) => {
  return dayjs(time, 'HH:mm:ss').format('HH:mm');
};

const close = () => {
  step.value = 1;
  selectedClass.value = null;
  selectedSessions.value = [];
  notes.value = '';
  selectAll.value = false;
  emit('close');
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    loadClasses();
  }
});

watch(selectedSessions, () => {
  selectAll.value = selectedSessions.value.length === availableSessions.value.length && availableSessions.value.length > 0;
});
</script>

<style scoped>
/* Modal transition */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
```

---

### 2. Update CustomerDetailModal.vue

**Changes:**
1. Thêm import TrialClassModal
2. Thêm nút "Học thử" bên cạnh nút "Đặt lịch test"
3. Thêm logic mở TrialClassModal

```vue
<!-- Line 37-42: Thêm nút Học thử bên cạnh nút Test -->
<div class="flex gap-2">
  <button 
    v-if="authStore.hasPermission('calendar.create')" 
    @click="scheduleTestForCustomer" 
    class="px-3 py-1.5 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition text-sm flex items-center gap-2"
  >
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
    </svg>
    Đặt lịch test
  </button>
  
  <!-- NEW: Trial Class Button -->
  <button 
    v-if="authStore.hasPermission('calendar.create')" 
    @click="scheduleTrialForCustomer" 
    class="px-3 py-1.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition text-sm flex items-center gap-2"
  >
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
    </svg>
    Học thử
  </button>
</div>

<!-- Line 87-91: Thêm nút Học thử cho children -->
<div class="flex gap-2">
  <button 
    v-if="authStore.hasPermission('calendar.create')" 
    @click="scheduleTestForChild(child)" 
    class="text-cyan-600 hover:text-cyan-800" 
    title="Đặt lịch test"
  >
    <!-- Test icon -->
  </button>
  
  <!-- NEW: Trial Class Button for Child -->
  <button 
    v-if="authStore.hasPermission('calendar.create')" 
    @click="scheduleTrialForChild(child)" 
    class="text-teal-600 hover:text-teal-800" 
    title="Học thử"
  >
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
    </svg>
  </button>
  
  <!-- Edit, Delete buttons... -->
</div>

<!-- Add at end of template -->
<TrialClassModal
  :show="showTrialModal"
  :trialable-type="trialType"
  :trialable-id="trialId"
  :trialable-name="trialName"
  @close="closeTrialModal"
  @registered="handleTrialRegistered"
/>
```

```javascript
// Script section - add these
import TrialClassModal from './TrialClassModal.vue';

const showTrialModal = ref(false);
const trialType = ref('customer');
const trialId = ref(null);
const trialName = ref('');

const scheduleTrialForCustomer = () => {
  trialType.value = 'customer';
  trialId.value = props.customer.id;
  trialName.value = props.customer.name;
  showTrialModal.value = true;
};

const scheduleTrialForChild = (child) => {
  trialType.value = 'child';
  trialId.value = child.id;
  trialName.value = child.name;
  showTrialModal.value = true;
};

const closeTrialModal = () => {
  showTrialModal.value = false;
};

const handleTrialRegistered = () => {
  swal.success('Đăng ký học thử thành công!');
  // Optionally reload data
};
```

---

### 3. Update CalendarEventService.php

Cập nhật `extractCustomerInfo()` để hiển thị trial students:

```php
// Nếu là ClassLessonSession
if ($eventable instanceof \App\Models\ClassLessonSession) {
    $eventable->load('class.homeroomTeacher', 'trialStudents');
    $class = $eventable->class;
    
    if (!$class) {
        return null;
    }
    
    // Count active trial students
    $trialCount = $eventable->activeTrialStudents()->count();
    
    return [
        'type' => 'class_session',
        'id' => $eventable->id,
        'class_id' => $class->id,
        'class_name' => $class->name,
        'class_code' => $class->code,
        'session_number' => $eventable->session_number,
        'lesson_title' => $eventable->lesson_title,
        'teacher_name' => $class->homeroomTeacher->name ?? 'N/A',
        'student_count' => $class->current_students,
        'room_number' => $class->room_number,
        'status' => $eventable->status,
        'total_sessions' => $class->total_sessions,
        'trial_students_count' => $trialCount, // NEW
    ];
}
```

---

### 4. Update CalendarView.vue

Hiển thị trial badge trên calendar:

```javascript
// Update popup detail
if (customerInfo.type === 'class_session') {
  customerSection = `
    <div class="toastui-calendar-section-detail" style="margin-top: 10px;">
      <div style="background: #f0fdfa; border-left: 3px solid #14B8A6; padding: 12px; border-radius: 4px;">
        <div style="font-size: 14px; font-weight: 600; color: #0f766e; margin-bottom: 8px;">
          📚 ${customerInfo.class_name}
          ${customerInfo.trial_students_count > 0 ? `<span style="background: #FFA500; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-left: 8px;">👤 ${customerInfo.trial_students_count} học thử</span>` : ''}
        </div>
        <!-- Rest of popup content -->
      </div>
    </div>
  `;
}
```

---

### 5. Update LessonSessionsTab.vue

Hiển thị trial students count trong danh sách buổi học:

```vue
<td class="px-4 py-3">
  <div class="flex items-center gap-2">
    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
      {{ session.students_attended || 0 }}/{{ classData.current_students }}
    </span>
    <span 
      v-if="session.trial_students_count > 0" 
      class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full"
      title="Học viên học thử"
    >
      👤 {{ session.trial_students_count }}
    </span>
  </div>
</td>
```

---

## ✅ Checklist Triển Khai

### Backend (8 tasks)
- [ ] Tạo migration `create_trial_students_table`
- [ ] Tạo model `TrialStudent`
- [ ] Thêm relationships vào `Customer`, `CustomerChild`, `ClassLessonSession`
- [ ] Tạo controller `TrialStudentController`
- [ ] Thêm routes `/api/trial-students/*`
- [ ] Cập nhật `CalendarEventService::extractCustomerInfo()`
- [ ] Cập nhật `ClassDetailController::getLessonSessions()` để load trial count
- [ ] Chạy migration và test API

### Frontend (4 tasks)
- [ ] Tạo component `TrialClassModal.vue`
- [ ] Cập nhật `CustomerDetailModal.vue` (thêm nút + logic)
- [ ] Cập nhật `CalendarView.vue` (hiển thị trial badge)
- [ ] Cập nhật `LessonSessionsTab.vue` (hiển thị trial count)

### Testing (3 tasks)
- [ ] Test đăng ký học thử cho Customer
- [ ] Test đăng ký học thử cho Child
- [ ] Test hiển thị trên Calendar

---

## 🎨 UI/UX Preview

### Customer Detail:
```
┌─────────────────────────────────────┐
│  Thông tin cơ bản                   │
│                                     │
│  [Đặt lịch test] [Học thử] ← NEW   │
└─────────────────────────────────────┘
```

### Trial Modal Step 1:
```
┌──────────────────────────────────────┐
│  Đăng Ký Học Thử                     │
│  Nguyễn Văn A                        │
├──────────────────────────────────────┤
│  ① Chọn lớp  ━━━━  ② Chọn buổi      │
├──────────────────────────────────────┤
│  [Search...]                         │
│                                      │
│  ┌──────────┐  ┌──────────┐         │
│  │ IELTS 5.0│  │ TOEIC    │  ← Cards│
│  │ TN-K2    │  │ TC-A1    │         │
│  └──────────┘  └──────────┘         │
│                                      │
│  [Hủy]              [Tiếp theo →]   │
└──────────────────────────────────────┘
```

### Trial Modal Step 2:
```
┌──────────────────────────────────────┐
│  Đăng Ký Học Thử                     │
│  Nguyễn Văn A                        │
├──────────────────────────────────────┤
│  ① Chọn lớp  ━━━━  ② Chọn buổi ✓    │
├──────────────────────────────────────┤
│  [← Quay lại]                        │
│                                      │
│  🎓 IELTS 5.0 (TN-K2)                │
│                                      │
│  □ Chọn tất cả (48 buổi)            │
│                                      │
│  ☑ Buổi 1: Introduction              │
│     📅 03/11/2025  🕐 14:00-16:00    │
│                                      │
│  □ Buổi 2: Listening Skills          │
│     📅 05/11/2025  🕐 14:00-16:00    │
│     [1 học thử] ← Badge              │
│                                      │
│  Ghi chú: [........................] │
│                                      │
│  [Hủy]           [Đăng ký (1 buổi)]│
└──────────────────────────────────────┘
```

### Calendar với Trial Badge:
```
┌────────────────────────────────────┐
│  📅 Tháng 11, 2025                 │
├────────────────────────────────────┤
│  Thứ 2    Thứ 3    Thứ 4    Thứ 5 │
├────────────────────────────────────┤
│   3         4         5         6   │
│  🎓 14:00   🎓 14:00   🎓 14:00     │
│  TN-K2      TN-K2      TN-K2        │
│  Buổi 1     Buổi 2     Buổi 3       │
│  👤2        👤1                      │
│  ↑ Trial badge                      │
└────────────────────────────────────┘
```

---

## 📝 Notes

### Quyết định Thiết Kế:

1. **Polymorphic cho Trial Student:** 
   - Linh hoạt cho cả Customer và Child
   - Dễ mở rộng sau này

2. **Session-specific Registration:**
   - Đăng ký theo từng buổi, không theo lớp
   - Linh hoạt hơn cho customer

3. **Status Tracking:**
   - Theo dõi từ đăng ký → tham gia → chuyển đổi
   - Có thể thống kê tỷ lệ chuyển đổi

4. **Calendar Integration:**
   - Hiển thị badge ngay trên calendar
   - Không tạo event riêng, chỉ gắn vào event hiện có

---

**Ready for Implementation?** 🚀

