# ✅ HỆ THỐNG TRỪ HỌC PHÍ THEO ĐIỂM DANH - HOÀN THÀNH 100%

## 📋 TỔNG QUAN

Hệ thống tự động trừ học phí từ ví học viên dựa trên trạng thái điểm danh:
- ✅ **Vắng không lý do**: Trừ 100% (hoặc theo % cấu hình)
- ✅ **Vắng có lý do**: Miễn phí 2 buổi/tháng, vượt quá trừ 50%
- ✅ **Đi trễ**: Cho phép trễ 15 phút, vượt quá trừ 30%
- ✅ **Đúng giờ**: Không trừ tiền

---

## 🗄️ DATABASE

### 1. Bảng `attendance_fee_policies`
Lưu trữ các chính sách trừ học phí (có thể có nhiều chính sách, chỉ 1 active)

```sql
- id (PK)
- branch_id (FK, nullable - null = áp dụng toàn hệ thống)
- name (varchar)
- description (text, nullable)
- is_active (boolean) - chỉ 1 policy active/branch
- absence_unexcused_deduct_percent (decimal 5,2) - % trừ vắng không lý do
- absence_consecutive_threshold (int) - số buổi vắng liên tiếp mới trừ
- absence_excused_free_limit (int) - số buổi vắng có lý do miễn phí/tháng
- absence_excused_deduct_percent (decimal 5,2) - % trừ khi vượt giới hạn
- late_deduct_percent (decimal 5,2) - % trừ khi đi trễ
- late_grace_minutes (int) - cho phép trễ tối đa (phút)
- created_at, updated_at
```

### 2. Bảng `attendance_fee_deductions`
Log các lần trừ tiền (audit trail)

```sql
- id (PK)
- attendance_id (FK -> attendances)
- student_id (FK -> students)
- class_id (FK -> classes)
- session_id (FK -> class_lesson_sessions)
- policy_id (FK -> attendance_fee_policies, nullable)
- deduction_type (enum: unexcused_absence, excused_over_limit, late)
- hourly_rate (decimal 10,2) - giá gốc/giờ của lớp
- deduction_percent (decimal 5,2) - % trừ áp dụng
- deduction_amount (decimal 10,2) - số tiền thực trừ
- wallet_transaction_id (FK -> wallet_transactions, nullable)
- notes (text, nullable)
- applied_at (timestamp)
- created_at, updated_at
```

### 3. Migration Files
```
✅ database/migrations/2025_11_08_135441_create_attendance_fee_policies_table.php
✅ database/migrations/2025_11_08_135457_create_attendance_fee_deductions_table.php
```

---

## 🎯 BACKEND

### 1. Models

#### `App\Models\AttendanceFeePolicy`
```php
✅ Fillable: name, description, branch_id, is_active, các trường policy
✅ Casts: is_active (boolean), các percent fields (decimal:2)
✅ Relationships:
   - branch() -> belongsTo(Branch)
✅ Scopes:
   - scopeActive($query) - lọc policy đang active
```

#### `App\Models\AttendanceFeeDeduction`
```php
✅ Fillable: attendance_id, student_id, class_id, session_id, policy_id,
   deduction_type, hourly_rate, deduction_percent, deduction_amount,
   wallet_transaction_id, notes, applied_at
✅ Casts: applied_at (datetime), các amount fields (decimal:2)
✅ Relationships:
   - attendance() -> belongsTo(Attendance)
   - student() -> belongsTo(Student)
   - class() -> belongsTo(ClassModel)
   - session() -> belongsTo(ClassLessonSession)
   - policy() -> belongsTo(AttendanceFeePolicy)
   - walletTransaction() -> belongsTo(WalletTransaction)
```

#### `App\Models\Attendance` (updated)
```php
✅ New relationship:
   - deductions() -> hasMany(AttendanceFeeDeduction)
```

### 2. Service

#### `App\Services\AttendanceFeeService`
```php
✅ processDeduction(Attendance $attendance): ?AttendanceFeeDeduction
   - Lấy policy active
   - Tính toán deduction dựa trên:
     * Status attendance (absent/late/excused)
     * Consecutive absences (cho unexcused)
     * Monthly excused count (cho excused)
     * Late duration (cho late)
   - Trừ tiền từ wallet
   - Tạo log deduction
   - Wrap in DB transaction
   - Extensive logging

✅ getActivePolicy(int $branchId): ?AttendanceFeePolicy
   - Ưu tiên policy theo branch
   - Fallback về global policy (branch_id = null)

✅ getMonthlyExcusedAbsencesCount(int $studentId, int $classId, $month, $year): int
   - Đếm số buổi vắng có lý do trong tháng
```

### 3. Observer

#### `App\Observers\AttendanceObserver`
```php
✅ Registered in AppServiceProvider::boot()
✅ Events:
   - created(Attendance) -> trigger processDeduction
   - updated(Attendance) -> trigger nếu status/check_in_time thay đổi
```

### 4. Controller

#### `App\Http\Controllers\Api\AttendanceFeePolicyController`
```php
✅ index(Request) - list policies (paginated, searchable)
✅ getActive(Request) - get active policy by branch_id
✅ store(Request) - create new policy
✅ show(AttendanceFeePolicy) - get policy detail
✅ update(Request, AttendanceFeePolicy) - update policy
✅ destroy(AttendanceFeePolicy) - delete policy (không được active)
✅ activate(AttendanceFeePolicy) - activate policy (deactivate others)

Validation:
- name: required, unique
- percentages: 0-100
- thresholds/limits: >= 0
```

### 5. Routes

#### `routes/api.php`
```php
✅ Prefix: /api/quality/attendance-fee-policies
✅ Middleware: permission:quality.view
✅ Routes:
   GET    / - list policies
   GET    /active - get active policy
   POST   / - create policy (permission:quality.manage_settings)
   GET    /{id} - show policy
   PUT    /{id} - update policy (permission:quality.manage_settings)
   DELETE /{id} - delete policy (permission:quality.manage_settings)
   POST   /{id}/activate - activate policy (permission:quality.manage_settings)
```

### 6. Seeder

#### `database/seeders/AttendanceFeePolicySeeder`
```php
✅ Creates default policy:
   - Name: "Chính sách mặc định"
   - Is active: true
   - Unexcused: 100%, threshold 1
   - Excused: 50% over 2/month
   - Late: 30% over 15 minutes
```

---

## 🎨 FRONTEND

### 1. Pages

#### `resources/js/pages/quality/QualitySettings.vue`
```vue
✅ Main settings page với tabs
✅ Tab "Attendance Fee Policy" (có thể mở rộng thêm tabs)
✅ Tích hợp vào Quality Management sidebar
```

#### `resources/js/pages/quality/settings/AttendanceFeeSettings.vue`
```vue
✅ List policies với:
   - Active badge
   - Color-coded cards (red/orange/yellow cho 3 loại absence)
   - Hiển thị % deduction, thresholds, limits
   - Actions: Activate, Edit, Delete
✅ Button "Create Policy"
✅ Loading states, empty states
```

#### `resources/js/pages/quality/settings/PolicyModal.vue`
```vue
✅ Full CRUD modal:
   - Form fields cho tất cả policy settings
   - Color-coded sections (red/orange/yellow)
   - Helper text giải thích mỗi field
   - Validation
   - Save/Cancel buttons
```

### 2. Router

#### `resources/js/router/index.js`
```javascript
✅ New route:
   {
     path: 'quality/settings',
     name: 'quality.settings',
     component: QualitySettings,
     meta: { permission: 'quality.view' }
   }
```

### 3. Navigation

#### `resources/js/pages/quality/QualityIndex.vue`
```vue
✅ New menu item "Settings" ở cuối sidebar
✅ Divider phân cách với các module khác
✅ Icon: Settings gear
✅ Router-link to /quality/settings
```

### 4. Translations

#### Added to `translations` table:
```
✅ quality.settings - "Cài đặt" / "Settings"
✅ quality.settings_description - "Cấu hình hệ thống" / "System configuration"
✅ attendance_fee.* (30+ keys):
   - title, policies, create_policy, edit_policy
   - policy_name, description, is_active
   - unexcused_absence, excused_absence, late
   - absence_unexcused_percent, absence_consecutive_threshold
   - absence_excused_free_limit, absence_excused_percent
   - late_deduct_percent, late_grace_minutes
   - activate, policy_activated, policy_created, policy_updated, policy_deleted
   - confirm_delete, cannot_delete_active, no_policies
```

---

## 🔄 WORKFLOW TỰ ĐỘNG

### Khi Điểm Danh Học Viên:

1. **Teacher đánh dấu attendance** (qua ClassDetail -> Attendance Tab)
   ```
   POST /api/classes/{classId}/attendance
   Body: {
     session_id: 1,
     students: [
       { student_id: 10, status: 'absent', is_excused: false, notes: '' },
       { student_id: 11, status: 'late', check_in_time: '09:20:00' }
     ]
   }
   ```

2. **AttendanceObserver tự động trigger**
   ```php
   created(Attendance $attendance)
   ↓
   AttendanceFeeService::processDeduction($attendance)
   ```

3. **AttendanceFeeService xử lý**
   ```php
   a. Lấy active policy (ưu tiên branch, fallback global)
   b. Kiểm tra status:
      - 'present' hoặc is_excused=false → SKIP
      - 'absent' + is_excused=false → tính unexcused
      - 'absent' + is_excused=true → tính excused over limit
      - 'late' → tính late over grace period
   c. Tính deduction_amount = hourly_rate * (deduction_percent / 100)
   d. Trừ tiền từ wallet (withdraw transaction)
   e. Tạo AttendanceFeeDeduction record (audit log)
   f. Log toàn bộ process
   ```

4. **Kết quả**
   ```
   ✅ Wallet balance giảm
   ✅ WalletTransaction type 'deduction' được tạo
   ✅ AttendanceFeeDeduction record được tạo (link to attendance, wallet_transaction)
   ✅ Log chi tiết trong Laravel log
   ```

---

## 📊 CÁCH SỬ DỤNG

### A. Quản lý Policies (Admin)

1. **Truy cập Settings**
   ```
   Quality Management → Settings → Attendance Fee Policy
   ```

2. **Tạo Policy Mới**
   ```
   - Click "Tạo chính sách mới"
   - Điền thông tin:
     * Tên chính sách
     * Mô tả (optional)
     * % trừ vắng không lý do (0-100%)
     * Số buổi liên tiếp (1+)
     * Số buổi vắng có lý do miễn phí/tháng (0+)
     * % trừ khi vượt giới hạn (0-100%)
     * % trừ đi trễ (0-100%)
     * Cho phép trễ tối đa (0+ phút)
   - Click "Save"
   ```

3. **Kích hoạt Policy**
   ```
   - Click "Kích hoạt" trên policy muốn sử dụng
   - Policy cũ tự động deactivate
   - Chỉ có 1 active policy/branch
   ```

4. **Chỉnh sửa/Xóa Policy**
   ```
   - Edit: Click "Chỉnh sửa" trên bất kỳ policy nào
   - Delete: Chỉ xóa được policy INACTIVE
   ```

### B. Điểm Danh Học Viên (Teacher)

1. **Truy cập Class Detail**
   ```
   Quality Management → Classes → Click class → Attendance tab
   ```

2. **Đánh dấu điểm danh**
   ```
   - Chọn session (buổi học)
   - Chọn date
   - Đánh dấu từng học viên:
     * ✅ Đúng giờ → không trừ
     * ❌ Vắng (checkbox "Có lý do" nếu có phép) → tự động trừ
     * ⏰ Trễ (nhập giờ check-in) → tự động trừ nếu vượt grace period
   - Click "Save Attendance"
   ```

3. **Hệ thống tự động**
   ```
   ✅ Tính toán deduction
   ✅ Trừ tiền từ ví học viên
   ✅ Ghi log transaction
   ```

### C. Xem Lịch Sử Trừ Tiền

```sql
-- Xem deductions của 1 học viên
SELECT * FROM attendance_fee_deductions 
WHERE student_id = 10 
ORDER BY applied_at DESC;

-- Xem wallet transactions liên quan
SELECT wt.* 
FROM wallet_transactions wt
JOIN attendance_fee_deductions afd ON afd.wallet_transaction_id = wt.id
WHERE afd.student_id = 10;
```

---

## 🧪 TESTING

### Test 1: Policy CRUD
```bash
# Tạo policy mới
curl -X POST http://127.0.0.1:8000/api/quality/attendance-fee-policies \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Policy",
    "absence_unexcused_percent": 80,
    "absence_consecutive_threshold": 2,
    "absence_excused_free_limit": 3,
    "absence_excused_percent": 40,
    "late_deduct_percent": 20,
    "late_grace_minutes": 10,
    "is_active": false
  }'

# List policies
curl http://127.0.0.1:8000/api/quality/attendance-fee-policies

# Get active policy
curl http://127.0.0.1:8000/api/quality/attendance-fee-policies/active

# Activate policy
curl -X POST http://127.0.0.1:8000/api/quality/attendance-fee-policies/2/activate
```

### Test 2: Attendance + Auto Deduction
```php
// Via Tinker
php artisan tinker

// Tạo attendance absent không lý do
$attendance = App\Models\Attendance::create([
    'student_id' => 10,
    'class_id' => 1,
    'session_id' => 1,
    'status' => 'absent',
    'is_excused' => false,
    'notes' => 'Test deduction'
]);

// Check deduction đã tạo
App\Models\AttendanceFeeDeduction::where('attendance_id', $attendance->id)->first();

// Check wallet transaction
$deduction = App\Models\AttendanceFeeDeduction::where('attendance_id', $attendance->id)->first();
App\Models\WalletTransaction::find($deduction->wallet_transaction_id);
```

### Test 3: Frontend
```
1. Đăng nhập hệ thống
2. Vào Quality Management → Settings
3. Xem danh sách policies
4. Tạo policy mới
5. Kích hoạt policy
6. Vào Class Detail → Attendance → đánh dấu absent
7. Check log Laravel (storage/logs/laravel.log)
8. Check wallet balance của học viên
```

---

## 📁 FILES CREATED/MODIFIED

### Backend
```
✅ database/migrations/2025_11_08_135441_create_attendance_fee_policies_table.php
✅ database/migrations/2025_11_08_135457_create_attendance_fee_deductions_table.php
✅ database/seeders/AttendanceFeePolicySeeder.php
✅ database/seeders/AttendanceFeeTranslations.php (fixed)
✅ app/Models/AttendanceFeePolicy.php
✅ app/Models/AttendanceFeeDeduction.php
✅ app/Models/Attendance.php (updated)
✅ app/Services/AttendanceFeeService.php
✅ app/Observers/AttendanceObserver.php
✅ app/Providers/AppServiceProvider.php (updated)
✅ app/Http/Controllers/Api/AttendanceFeePolicyController.php
✅ routes/api.php (updated)
```

### Frontend
```
✅ resources/js/pages/quality/QualitySettings.vue
✅ resources/js/pages/quality/settings/AttendanceFeeSettings.vue
✅ resources/js/pages/quality/settings/PolicyModal.vue
✅ resources/js/pages/quality/QualityIndex.vue (updated - added Settings menu)
✅ resources/js/router/index.js (updated - added route)
```

### Database
```
✅ translations table (30+ new keys added via script)
✅ attendance_fee_policies table (1 default policy seeded)
✅ attendance_fee_deductions table (empty, ready for logs)
```

---

## 🎉 KẾT LUẬN

**Hệ thống hoàn chỉnh 100%:**
- ✅ Database schema
- ✅ Backend logic (Service, Observer, Controller)
- ✅ Frontend UI (Settings page, Policy CRUD)
- ✅ API routes
- ✅ Translations
- ✅ Seeder (default policy)
- ✅ Auto deduction workflow
- ✅ Audit logging

**Ready for production:**
- Teachers điểm danh → hệ thống tự động trừ tiền
- Admin quản lý policies qua UI
- Full audit trail trong database
- Extensive logging cho debugging

**Tài liệu đầy đủ:**
- Workflow chi tiết
- Testing guide
- Usage guide

---

🚀 **Hệ thống sẵn sàng sử dụng!**

