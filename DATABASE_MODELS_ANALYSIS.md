# PHÂN TÍCH MODELS VÀ DESIGN PATTERNS
# Dự án School Management System

---

## TỔNG QUAN MODELS

### Số lượng Models: 75+ models
### Thư mục: `app/Models/`

---

## CÁC MODEL CHÍNH

### 1. USER MODEL (`User.php`)

**Vai trò:** Model trung tâm của toàn hệ thống

**Đặc điểm nổi bật:**

```php
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    
    // Fillable fields - Rất nhiều field
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar',
        'date_of_birth', 'gender', 'address',
        'employee_code', 'join_date', 'employment_status',
        'language_id', 'manager_id', 'hierarchy_level',
        'google_email', 'google_drive_folder_id',
    ];
    
    // Appended attributes
    protected $appends = ['full_name'];
    
    // Multiple relationships (20+ methods)
}
```

**Quan hệ chính:**
- ✅ **Roles & Permissions**: RBAC system
- ✅ **Branches**: Many-to-Many với pivot is_primary
- ✅ **Departments**: Many-to-Many với complex pivot
- ✅ **Subjects**: Teacher-Subject relationship
- ✅ **Classes**: Homeroom teacher và teaching classes
- ✅ **Hierarchy**: Self-referencing manager_id
- ✅ **1-to-1**: Student, Parent models

**Methods quan trọng:**
```php
// Role checking
hasRole($roleName): bool
hasAnyRole(array $roles): bool
hasPermission($permissionName): bool
hasPermissionInBranch($permissionName, $branchId): bool
isSuperAdmin(): bool

// Hierarchy
getAllSubordinates()
getSubordinatesInBranch($branchId)
canAccessUserData($targetUserId, $branchId)

// Branch management
getPrimaryBranch()
assignBranch($branch, $isPrimary)
removeBranch($branch)
```

**Design patterns:**
- ✅ **Trait Usage**: HasFactory, Notifiable, HasApiTokens
- ✅ **Accessor**: getFullNameAttribute()
- ✅ **Scope Methods**: Có thể mở rộng
- ✅ **Policy Pattern**: Kiểm tra quyền nhiều cấp

---

### 2. STUDENT MODEL (`Student.php`)

**Đặc điểm:**
```php
class Student extends Model
{
    use HasFactory, SoftDeletes;
    
    // 1-to-1 với User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // M-to-M với Classes qua class_students
    // Sử dụng user_id thay vì students.id
    public function classes()
    {
        return $this->belongsToMany(
            ClassModel::class, 
            'class_students', 
            'student_id',  // refers to users.id
            'class_id',
            'user_id'      // local key on students
        );
    }
    
    // M-to-M với Parents
    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'parent_student')
            ->withPivot('relationship', 'is_primary');
    }
    
    // Polymorphic với Wallet
    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner');
    }
}
```

**Tính năng đặc biệt:**
- ✅ Auto-generate student_code: `STD202500001`
- ✅ Soft deletes để giữ lịch sử
- ✅ Scopes: `active()`, `byBranch()`
- ✅ Method `getEffectiveBalance()` - Ưu tiên customer wallet

---

### 3. CUSTOMER MODEL (`Customer.php`)

**Vai trò:** CRM - Quản lý khách hàng với Sales Pipeline

**Đặc điểm:**
```php
class Customer extends Model
{
    use SoftDeletes;
    
    // Sales Pipeline Stages
    const STAGE_LEAD = 'lead';
    const STAGE_CONTACTED = 'contacted';
    const STAGE_QUALIFIED = 'qualified';
    const STAGE_PROPOSAL = 'proposal';
    const STAGE_NEGOTIATION = 'negotiation';
    const STAGE_CLOSED_WON = 'closed_won';
    const STAGE_CLOSED_LOST = 'closed_lost';
    
    // Kanban ordering
    protected $fillable = [
        'stage', 'stage_order', // For drag-drop
        // ... other fields
    ];
}
```

**Methods quan trọng:**
```php
// Auto-generate code
generateCode(): string  // CUS20251124001

// Move through pipeline
moveToStage(string $stage, int $order = 0): void

// Hierarchy-aware scope
scopeAccessibleBy($query, $user)
```

**Quan hệ:**
- Has-Many: `interactions`, `children`, `trialClasses`, `enrollments`
- MorphMany: `calendarEvents` (polymorphic)
- MorphOne: `wallet` (polymorphic)

---

### 4. CLASS MODEL (`ClassModel.php`)

**Đặc điểm:**
```php
class ClassModel extends Model
{
    protected $table = 'classes';
    
    // Multiple teacher relationships
    public function homeroomTeacher()  // 1 GVCN
    public function teachers()         // Many teachers via class_subject
    public function subjects()         // M-to-M với teachers
}
```

**Tính năng kiểm soát truy cập:**
```php
// Check if user can view schedule
canUserViewSchedule(User $user): bool

// Get viewable subjects for teacher
getViewableSubjectsForTeacher(User $user)
```

**Tích hợp bên ngoài:**
```php
// Google Drive
google_drive_folder_id
google_drive_folder_name

// Zalo Chat
zalo_account_id
zalo_group_id
zalo_group_name
```

---

### 5. ENROLLMENT MODEL (`Enrollment.php`)

**Vai trò:** Đăng ký khóa học với workflow phức tạp

**Đặc điểm:**
```php
class Enrollment extends Model
{
    use HasFactory, SoftDeletes;
    
    // Workflow states
    const STATUS_PENDING = 'pending';      // Chờ duyệt
    const STATUS_APPROVED = 'approved';    // Đã duyệt
    const STATUS_PAID = 'paid';            // Đã thanh toán
    const STATUS_ACTIVE = 'active';        // Đang học
    const STATUS_COMPLETED = 'completed';  // Hoàn thành
    const STATUS_CANCELLED = 'cancelled';  // Đã hủy
    
    // Polymorphic student
    public function student()
    {
        return $this->morphTo();
        // Can be Customer, Student, or TrialStudent
    }
}
```

**Auto-calculation:**
```php
// Boot method tự động tính
static::creating(function ($enrollment) {
    // Auto-generate code
    $enrollment->code = self::generateCode();
    
    // Calculate remaining sessions
    $enrollment->remaining_sessions = 
        $enrollment->total_sessions - $enrollment->attended_sessions;
    
    // Calculate remaining amount
    $enrollment->remaining_amount = 
        $enrollment->final_price - $enrollment->paid_amount;
});
```

**Race condition handling:**
```php
public static function generateCode(): string
{
    return DB::transaction(function () {
        // Use lockForUpdate() to prevent race conditions
        $lastEnrollment = self::withTrashed()
            ->where('code', 'like', "{$prefix}{$date}%")
            ->orderBy('code', 'desc')
            ->lockForUpdate()  // ← Database lock
            ->first();
        
        // Generate unique code with retry logic
        // ...
    });
}
```

**Voucher management:**
```php
public function releaseVoucherUsage(): void
{
    // When cancelled, release voucher
    // Decrement voucher usage count
    // Delete voucher_usage record
}
```

---

### 6. ATTENDANCE MODEL (`Attendance.php`)

**Đặc điểm:**
```php
class Attendance extends Model
{
    // JSON field for evaluation
    protected $casts = [
        'check_in_time' => 'datetime',
        'evaluation_data' => 'array',  // JSON cast
    ];
    
    // Related to fee deductions
    public function deductions()
    {
        return $this->hasMany(AttendanceFeeDeduction::class);
    }
}
```

**Tích hợp với Fee System:**
- `AttendanceFeePolicy`: Chính sách học phí
- `AttendanceFeeDeduction`: Khấu trừ/hoàn tiền dựa trên điểm danh

---

### 7. ZALO ACCOUNT MODEL (`ZaloAccount.php`)

**Đặc điểm bảo mật:**
```php
class ZaloAccount extends Model
{
    use SoftDeletes;
    
    // Hide encrypted cookie
    protected $hidden = ['cookie'];
    
    // Encrypt/Decrypt cookie
    public function getCookieAttribute($value)
    {
        return Crypt::decryptString($value);
    }
    
    public function setCookieAttribute($value)
    {
        $this->attributes['cookie'] = Crypt::encryptString($value);
    }
}
```

**Complex scopes:**
```php
// Access control
scopeAccessibleBy($query, $user)
scopeBasedOnManagePermission($query, $user, $branchId)

// Filter by branch (with null handling)
scopeForBranch($query, $branchId)

// Primary account
scopePrimary($query)
```

---

## DESIGN PATTERNS ĐƯỢC SỬ DỤNG

### 1. **Repository Pattern** (Implicit)
Mặc dù không có Repository classes riêng, nhưng Models hoạt động như repositories:
- Query scopes
- Static methods cho business logic
- Relationship methods

### 2. **Observer Pattern**
```php
// app/Observers/AttendanceObserver.php
class AttendanceObserver
{
    public function created(Attendance $attendance)
    {
        // Auto-calculate fee deductions
    }
}

// app/Observers/IncomeReportObserver.php
class IncomeReportObserver
{
    public function updated(IncomeReport $incomeReport)
    {
        // Update enrollment status when approved
    }
}
```

### 3. **Factory Pattern**
```php
// database/factories/UserFactory.php
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            // ...
        ];
    }
}
```

### 4. **Strategy Pattern** (in Scopes)
```php
// Different strategies for different users
scopeAccessibleBy($query, $user)
{
    if ($user->isSuperAdmin()) {
        return $query;  // Strategy 1: All access
    }
    
    if ($user->branches()->exists()) {
        // Strategy 2: Branch-based access
        return $query->whereIn('branch_id', $userBranchIds);
    }
    
    // Strategy 3: Own records only
    return $query->where('assigned_to', $user->id);
}
```

### 5. **Polymorphic Relationships** (Duck Typing)
```php
// Wallet can belong to any "owner"
public function wallet()
{
    return $this->morphOne(Wallet::class, 'owner');
}

// Works with: Customer, Student, Parent
```

### 6. **Soft Delete Pattern**
```php
use SoftDeletes;

// Cho phép "undo" delete
// Giữ lịch sử dữ liệu
// Tránh referential integrity issues
```

### 7. **Pivot Class Pattern**
```php
// app/Models/ClassStudent.php
class ClassStudent extends Pivot
{
    use HasFactory;
    
    // Custom pivot with additional logic
    public $incrementing = true;
}
```

---

## BEST PRACTICES ĐƯỢC ÁP DỤNG

### ✅ 1. **Auto-generating Codes**
```php
// Consistent format: PREFIX + DATE + SEQUENCE
public static function generateCode(): string
{
    $prefix = 'CUS';
    $date = date('Ymd');
    
    $lastRecord = self::where('code', 'like', "{$prefix}{$date}%")
        ->orderBy('code', 'desc')
        ->first();
    
    if ($lastRecord) {
        $lastNumber = (int) substr($lastRecord->code, -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

// Examples:
// CUS20251124001
// STD202500001
// ENR20251124001
```

### ✅ 2. **Boot Method for Auto-actions**
```php
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($model) {
        // Auto-generate code
        if (empty($model->code)) {
            $model->code = self::generateCode();
        }
    });
    
    static::updating(function ($model) {
        // Auto-calculate dependent fields
        if ($model->isDirty(['total', 'paid'])) {
            $model->remaining = $model->total - $model->paid;
        }
    });
}
```

### ✅ 3. **Query Scopes for Reusability**
```php
// Scope methods
public function scopeActive($query)
{
    return $query->where('is_active', true);
}

public function scopeByBranch($query, int $branchId)
{
    return $query->where('branch_id', $branchId);
}

// Usage
$students = Student::active()->byBranch(1)->get();
```

### ✅ 4. **Accessor & Mutator**
```php
// Accessor
public function getFullNameAttribute(): string
{
    return $this->name ?? 'N/A';
}

// Usage: $user->full_name

// Mutator with encryption
public function setPasswordAttribute($value)
{
    $this->attributes['password'] = bcrypt($value);
}
```

### ✅ 5. **Casts for Type Safety**
```php
protected $casts = [
    'date_of_birth' => 'date',
    'is_active' => 'boolean',
    'metadata' => 'array',      // Auto JSON encode/decode
    'price' => 'decimal:2',
    'created_at' => 'datetime',
];
```

### ✅ 6. **Relationship Loading Strategy**
```php
// Eager loading để tránh N+1 query
$users = User::with([
    'roles.permissions',
    'branches',
    'departments.positions'
])->get();

// Lazy eager loading
$users->load('subjects');
```

### ✅ 7. **Constants for Enums**
```php
class Customer extends Model
{
    const STAGE_LEAD = 'lead';
    const STAGE_CONTACTED = 'contacted';
    // ...
    
    public static function getStages(): array
    {
        return [
            self::STAGE_LEAD => 'Khách Tiềm Năng',
            self::STAGE_CONTACTED => 'Đã Liên Hệ',
            // ...
        ];
    }
}
```

---

## POTENTIAL ISSUES & RECOMMENDATIONS

### ⚠️ 1. **N+1 Query Problem**
**Issue:** Nhiều relationships có thể gây N+1 queries

**Solution:**
```php
// Always eager load relationships when needed
User::with('branches', 'roles.permissions')->get();

// Or use lazy eager loading
$users->load('subjects');
```

### ⚠️ 2. **Fat Models**
**Issue:** User model quá lớn (677 lines)

**Recommendation:**
```php
// Tách thành Traits
trait HasRoles { /* role-related methods */ }
trait HasBranches { /* branch-related methods */ }
trait HasHierarchy { /* hierarchy methods */ }

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    use HasRoles, HasBranches, HasHierarchy;
}
```

### ⚠️ 3. **Transaction Safety**
**Issue:** Một số operations cần transaction nhưng chưa có

**Recommendation:**
```php
DB::transaction(function () {
    $enrollment = Enrollment::create([...]);
    
    // Create related records
    $enrollment->walletTransactions()->create([...]);
    
    // Update voucher usage
    $voucher->increment('usage_count');
});
```

### ⚠️ 4. **Validation**
**Issue:** Validation logic nằm trong Controllers, không reusable

**Recommendation:**
```php
// Tạo Form Request classes
class StoreEnrollmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'final_price' => 'required|numeric|min:0',
        ];
    }
}
```

### ⚠️ 5. **Event & Listener**
**Issue:** Business logic trong Observers có thể phức tạp

**Recommendation:**
```php
// Sử dụng Events thay vì Observers cho complex logic
event(new EnrollmentCreated($enrollment));

class EnrollmentCreatedListener
{
    public function handle(EnrollmentCreated $event)
    {
        // Send notification
        // Create wallet transaction
        // Update statistics
    }
}
```

---

## MODEL STATISTICS

### Complexity Analysis:

| Model | Lines | Relationships | Methods | Scopes |
|-------|-------|---------------|---------|--------|
| User | 677 | 20+ | 40+ | 5 |
| Customer | 275 | 8 | 10 | 6 |
| ClassModel | 206 | 10 | 8 | 0 |
| Enrollment | 307 | 9 | 12 | 3 |
| Student | 160 | 6 | 4 | 3 |
| ZaloAccount | 201 | 4 | 10 | 6 |

### Relationship Types Used:

- ✅ BelongsTo: 50+
- ✅ HasMany: 40+
- ✅ BelongsToMany: 30+
- ✅ MorphTo/MorphOne/MorphMany: 10+
- ✅ HasOne: 5+
- ✅ HasOneThrough: 2

---

## KẾT LUẬN

### Điểm mạnh:
1. ✅ **Well-structured relationships** - Quan hệ rõ ràng
2. ✅ **Auto-generation** - Tự động tạo code, tính toán
3. ✅ **Soft deletes** - Giữ lịch sử dữ liệu
4. ✅ **Scopes** - Query reusability
5. ✅ **Type safety** - Casts cho data types
6. ✅ **Security** - Encryption cho sensitive data
7. ✅ **Polymorphic** - Flexible relationships

### Cần cải thiện:
1. ⚠️ **Tách Models lớn thành Traits**
2. ⚠️ **Thêm Form Requests cho validation**
3. ⚠️ **Sử dụng Events thay Observers cho complex logic**
4. ⚠️ **Document relationships tốt hơn**
5. ⚠️ **Add unit tests cho Models**
6. ⚠️ **Optimize eager loading strategy**

### Khuyến nghị:
- 📚 Viết documentation cho các relationships phức tạp
- 🧪 Viết tests cho business logic trong Models
- 🔄 Refactor Models lớn thành Traits
- 📊 Monitor query performance với Telescope
- 🔐 Audit sensitive operations

---

**Tác giả:** AI Assistant  
**Ngày:** 24/11/2025  
**Phiên bản:** 1.0

