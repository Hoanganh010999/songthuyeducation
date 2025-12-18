# 🚀 DATABASE QUICK REFERENCE CARD
## School Management System - Tra cứu nhanh

---

## 📋 CÁC BẢNG CHÍNH (QUICK TABLE REFERENCE)

### Core System
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `users` | Người dùng | id, email, name, manager_id |
| `roles` | Vai trò | id, name |
| `permissions` | Quyền hạn | id, name, module, action |
| `branches` | Chi nhánh | id, code, name, manager_id |
| `languages` | Ngôn ngữ | id, code, is_default |

### CRM & Sales
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `customers` | Khách hàng | id, code, stage, branch_id |
| `customer_interactions` | Tương tác | id, customer_id, type_id |
| `enrollments` | Đăng ký học | id, code, customer_id, status |
| `products` | Sản phẩm | id, name, price, product_type |
| `vouchers` | Mã giảm giá | id, code, discount_value |

### Academic
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `classes` | Lớp học | id, code, homeroom_teacher_id |
| `students` | Học sinh | id, user_id, student_code |
| `subjects` | Môn học | id, name, code |
| `lesson_plans` | Giáo án | id, subject_id, total_sessions |
| `attendances` | Điểm danh | id, student_id, session_id, status |

### Finance
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `wallets` | Ví điện tử | id, owner_type, owner_id, balance |
| `financial_transactions` | Giao dịch | id, type, amount, status |
| `income_reports` | Báo cáo thu | id, amount, status |
| `expense_proposals` | Đề xuất chi | id, amount, status |

### Integration
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `zalo_accounts` | Tài khoản Zalo | id, zalo_id, branch_id |
| `zalo_messages` | Tin nhắn Zalo | id, content, sender_id |
| `google_drive_items` | Files Drive | id, drive_id, name, type |

---

## 🔗 RELATIONSHIPS CHEAT SHEET

### User Relationships
```php
User::find($id)
    ->roles                  // Many-to-Many
    ->branches              // Many-to-Many (pivot: is_primary)
    ->departments           // Many-to-Many
    ->subjects              // Many-to-Many (teacher)
    ->homeroomClasses       // One-to-Many
    ->managedBranches       // One-to-Many
    ->subordinates          // One-to-Many (self-ref)
    ->manager               // BelongsTo (self-ref)
```

### Customer Relationships
```php
Customer::find($id)
    ->interactions          // Has-Many
    ->children              // Has-Many
    ->enrollments           // Has-Many
    ->trialClasses          // MorphMany
    ->wallet                // MorphOne
    ->calendarEvents        // MorphMany
    ->branch                // BelongsTo
    ->assignedUser          // BelongsTo
```

### Class Relationships
```php
ClassModel::find($id)
    ->branch                // BelongsTo
    ->homeroomTeacher       // BelongsTo User
    ->subject               // BelongsTo
    ->subjects              // Many-to-Many (with teachers)
    ->students              // Has-Many ClassStudent
    ->schedules             // Has-Many
    ->lessonSessions        // Has-Many
    ->lessonPlan            // BelongsTo
```

### Enrollment Relationships
```php
Enrollment::find($id)
    ->customer              // BelongsTo
    ->student               // MorphTo (polymorphic)
    ->product               // BelongsTo
    ->branch                // BelongsTo
    ->voucher               // BelongsTo
    ->walletTransactions    // MorphMany
```

---

## 💡 COMMON QUERIES (COPY-PASTE READY)

### Get User with Permissions
```php
$user = User::with(['roles.permissions', 'branches'])
    ->find($userId);

// Check permission
if ($user->hasPermission('customers.view')) {
    // ...
}
```

### Get Customers by Stage (CRM)
```php
$customers = Customer::where('stage', 'lead')
    ->where('branch_id', $branchId)
    ->with(['assignedUser', 'latestInteraction'])
    ->orderBy('stage_order')
    ->paginate(20);
```

### Get Active Classes
```php
$classes = ClassModel::where('status', 'active')
    ->where('branch_id', $branchId)
    ->with([
        'homeroomTeacher',
        'students' => fn($q) => $q->where('status', 'active'),
        'subjects.teachers'
    ])
    ->get();
```

### Get Students in Class
```php
$students = Student::whereHas('classes', fn($q) => 
        $q->where('classes.id', $classId)
          ->where('class_students.status', 'active')
    )
    ->with(['user', 'parents'])
    ->get();
```

### Get Unpaid Enrollments
```php
$enrollments = Enrollment::where('status', 'approved')
    ->where('remaining_amount', '>', 0)
    ->with(['customer', 'product', 'branch'])
    ->get();
```

### Search Users
```php
$users = User::where(function($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%")
          ->orWhere('phone', 'like', "%{$search}%");
    })
    ->with('roles')
    ->paginate(20);
```

---

## 🎯 SCOPES QUICK REFERENCE

### User Scopes
```php
User::active()->get()
User::byBranch($branchId)->get()
```

### Customer Scopes
```php
Customer::active()->get()
Customer::byStage('lead')->get()
Customer::byBranch($branchId)->get()
Customer::accessibleBy($user)->get()
Customer::search($term)->get()
```

### Student Scopes
```php
Student::active()->get()
Student::byBranch($branchId)->get()
```

### ZaloAccount Scopes
```php
ZaloAccount::active()->get()
ZaloAccount::connected()->get()
ZaloAccount::primary()->get()
ZaloAccount::forBranch($branchId)->get()
ZaloAccount::accessibleBy($user)->get()
```

---

## 🔐 AUTHORIZATION CHECKS

### Role & Permission
```php
// Check role
if ($user->hasRole('admin')) { }
if ($user->hasAnyRole(['admin', 'teacher'])) { }

// Check permission
if ($user->hasPermission('customers.create')) { }
if ($user->hasPermissionInBranch('classes.edit', $branchId)) { }

// Check super-admin
if ($user->isSuperAdmin()) { }
```

### Access Control
```php
// Customer access
$customers = Customer::accessibleBy($user)->get();

// Zalo access
$accounts = ZaloAccount::accessibleBy($user)->get();

// Check user can access another user
if ($user->canAccessUserData($targetUserId, $branchId)) { }
```

---

## 📊 AGGREGATIONS & STATS

### Count Relationships
```php
$users = User::withCount(['subordinates', 'homeroomClasses'])
    ->get();

// Access: $user->subordinates_count
```

### Sum, Avg, Min, Max
```php
// Total revenue
$total = Enrollment::where('status', 'paid')
    ->sum('final_price');

// Average price
$avg = Product::avg('price');

// Min/Max
$minPrice = Product::min('price');
$maxPrice = Product::max('price');
```

### Group By
```php
// Revenue by branch
$revenue = Enrollment::selectRaw('
        branch_id, 
        COUNT(*) as total,
        SUM(final_price) as revenue
    ')
    ->groupBy('branch_id')
    ->get();
```

---

## ⚡ PERFORMANCE TIPS

### Eager Loading
```php
// BAD (N+1)
$users = User::all();
foreach ($users as $user) {
    echo $user->branch->name;  // N queries
}

// GOOD
$users = User::with('branches')->get();
```

### Select Specific Columns
```php
// BAD
$users = User::all();

// GOOD
$users = User::select('id', 'name', 'email')->get();
```

### Chunk for Large Data
```php
// Process in chunks
Student::chunk(100, function($students) {
    foreach ($students as $student) {
        // Process
    }
});

// Or lazy
Student::lazy()->each(function($student) {
    // Process
});
```

### Caching
```php
// Cache query result
$branches = Cache::remember('branches.active', 3600, function() {
    return Branch::where('is_active', true)->get();
});

// Clear cache
Cache::forget('branches.active');
```

---

## 🔨 USEFUL ARTISAN COMMANDS

### Migration
```bash
php artisan migrate                    # Run migrations
php artisan migrate:rollback          # Rollback last batch
php artisan migrate:fresh             # Drop & re-create
php artisan migrate:fresh --seed      # With seeding
```

### Seeding
```bash
php artisan db:seed                   # Run DatabaseSeeder
php artisan db:seed --class=BranchSeeder
```

### Tinker (Testing)
```bash
php artisan tinker

>>> User::count()
>>> Customer::where('stage', 'lead')->count()
>>> DB::table('users')->get()
```

### Model
```bash
php artisan make:model NewModel -mfs
# -m: migration
# -f: factory  
# -s: seeder
```

---

## 🐛 DEBUGGING

### Query Log
```php
DB::enableQueryLog();

// Your queries here
$users = User::with('branches')->get();

dd(DB::getQueryLog());
```

### Explain Query
```php
// Get SQL
$sql = User::with('branches')->toSql();

// With bindings
$query = User::where('id', 1);
echo $query->toSql();
print_r($query->getBindings());
```

### Check Relationships
```bash
php artisan tinker

>>> $user = User::find(1)
>>> $user->branches
>>> $user->load('roles')
>>> $user->loadCount('subordinates')
```

---

## 📝 STATUS ENUMS

### Enrollment Status
```
pending → approved → paid → active → completed
                              ↓
                          cancelled
```

### Customer Stage
```
lead → contacted → qualified → proposal → negotiation
                                             ↓
                                    closed_won / closed_lost
```

### Class Status
```
planning → active → completed
             ↓
         cancelled
```

### Attendance Status
```
present | absent | late | excused
```

---

## 🔢 AUTO-GENERATED CODES

| Model | Format | Example |
|-------|--------|---------|
| Customer | CUS + YYYYMMDD + 4 digits | CUS202411240001 |
| Student | STD + YYYY + 5 digits | STD202500001 |
| Enrollment | ENR + YYYYMMDD + 4 digits | ENR202411240001 |

---

## 🗄️ DATABASE CONFIG

### SQLite (Default)
```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

### MySQL
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school
DB_USERNAME=root
DB_PASSWORD=
```

---

## 🔧 COMMON FIXES

### Reset Database
```bash
php artisan migrate:fresh --seed
```

### Fix Foreign Key
```bash
# Disable check temporarily
DB::statement('SET FOREIGN_KEY_CHECKS=0');
// Your operation
DB::statement('SET FOREIGN_KEY_CHECKS=1');
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 📞 QUICK CONTACTS

- **Documentation**: See DATABASE_README.md
- **Structure**: See DATABASE_STRUCTURE_ANALYSIS.md
- **ERD**: See DATABASE_ERD_DIAGRAM.md
- **Models**: See DATABASE_MODELS_ANALYSIS.md
- **Queries**: See DATABASE_QUERIES_GUIDE.md

---

**Version:** 1.0  
**Last Updated:** 2025-11-24  
**Keep this card handy for quick reference! 🚀**

