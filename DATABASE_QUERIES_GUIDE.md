# HƯỚNG DẪN DATABASE QUERIES & PERFORMANCE
# Dự án School Management System

---

## MỤC LỤC

1. [Common Query Patterns](#common-query-patterns)
2. [Query Optimization](#query-optimization)
3. [N+1 Query Problems](#n1-query-problems)
4. [Complex Queries Examples](#complex-queries-examples)
5. [Database Indexes](#database-indexes)
6. [Performance Tips](#performance-tips)
7. [Monitoring & Debugging](#monitoring--debugging)

---

## COMMON QUERY PATTERNS

### 1. **User Queries**

#### Lấy user với tất cả permissions
```php
// BAD - N+1 Query
$user = User::find(1);
foreach ($user->roles as $role) {
    foreach ($role->permissions as $permission) {
        // N+1 problem here
    }
}

// GOOD - Eager loading
$user = User::with(['roles.permissions', 'branches'])
    ->find(1);
```

#### Lấy users theo branch và role
```php
$users = User::whereHas('branches', function ($query) use ($branchId) {
        $query->where('branches.id', $branchId);
    })
    ->whereHas('roles', function ($query) {
        $query->where('name', 'teacher');
    })
    ->with(['roles', 'branches'])
    ->get();
```

#### Lấy users trong hierarchy
```php
// Lấy tất cả subordinates của một manager
$manager = User::find(1);
$subordinates = $manager->getAllSubordinates();

// Hoặc trong một branch cụ thể
$subordinates = $manager->getSubordinatesInBranch($branchId);
```

### 2. **Customer Queries**

#### Lấy customers theo stage (Kanban)
```php
// Lấy customers trong một stage, sắp xếp theo stage_order
$customers = Customer::where('stage', 'lead')
    ->where('branch_id', $branchId)
    ->orderBy('stage_order')
    ->with(['assignedUser', 'latestInteraction'])
    ->get();
```

#### Search customers
```php
$customers = Customer::where(function ($query) use ($search) {
        $query->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
    })
    ->where('branch_id', $branchId)
    ->with(['assignedUser', 'children'])
    ->paginate(20);
```

#### Lấy customers accessible by user (hierarchy-aware)
```php
$customers = Customer::accessibleBy(auth()->user())
    ->with(['assignedUser', 'branch', 'interactions' => function ($query) {
        $query->latest()->limit(5);
    }])
    ->paginate(20);
```

### 3. **Class Queries**

#### Lấy classes với đầy đủ thông tin
```php
$classes = ClassModel::where('branch_id', $branchId)
    ->where('status', 'active')
    ->with([
        'branch',
        'homeroomTeacher',
        'subject',
        'semester',
        'subjects.teachers',
        'activeStudents' => function ($query) {
            $query->with('user')->latest();
        }
    ])
    ->get();
```

#### Lấy classes mà teacher có thể xem
```php
$user = auth()->user();

$classes = ClassModel::where(function ($query) use ($user) {
        // Homeroom classes
        $query->where('homeroom_teacher_id', $user->id)
            // OR teaching classes
            ->orWhereHas('subjects', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })
            // OR head teacher subjects
            ->orWhereHas('subjects', function ($q) use ($user) {
                $q->whereHas('headTeacher', function ($qt) use ($user) {
                    $qt->where('users.id', $user->id);
                });
            });
    })
    ->with(['subjects', 'students'])
    ->get();
```

### 4. **Student Queries**

#### Lấy students trong class với attendance
```php
$classId = 1;

$students = Student::whereHas('classes', function ($query) use ($classId) {
        $query->where('classes.id', $classId)
              ->where('class_students.status', 'active');
    })
    ->with([
        'user',
        'parents.user',
        'attendances' => function ($query) use ($classId) {
            $query->whereHas('session', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            })->latest()->limit(10);
        }
    ])
    ->get();
```

### 5. **Enrollment Queries**

#### Lấy enrollments với tất cả related data
```php
$enrollments = Enrollment::where('status', 'active')
    ->with([
        'customer',
        'student',  // Polymorphic
        'product',
        'branch',
        'voucher',
        'assignedUser',
    ])
    ->whereHas('incomeReports', function ($query) {
        $query->where('status', 'approved');
    })
    ->get();
```

#### Check enrollment có thể activate không
```php
$enrollment = Enrollment::with('incomeReports')->find(1);

if ($enrollment->hasApprovedIncomeReport()) {
    $enrollment->activate();
}
```

---

## QUERY OPTIMIZATION

### 1. **Chỉ select các columns cần thiết**

```php
// BAD - Select tất cả columns
$users = User::all();

// GOOD - Select specific columns
$users = User::select('id', 'name', 'email')->get();

// GOOD - With relationships
$users = User::select('id', 'name', 'email')
    ->with(['roles' => function ($query) {
        $query->select('id', 'name');
    }])
    ->get();
```

### 2. **Sử dụng Pagination thay vì get()**

```php
// BAD - Load all records
$customers = Customer::all();

// GOOD - Paginate
$customers = Customer::paginate(20);

// BETTER - Cursor pagination for large datasets
$customers = Customer::cursorPaginate(20);
```

### 3. **Chunk cho large datasets**

```php
// Process large dataset in chunks
Student::where('is_active', true)
    ->chunk(100, function ($students) {
        foreach ($students as $student) {
            // Process student
        }
    });

// Or use lazy()
Student::lazy()->each(function ($student) {
    // Process one by one efficiently
});
```

### 4. **Count queries optimization**

```php
// BAD - Load all then count
$count = Customer::where('stage', 'lead')->get()->count();

// GOOD - Use count query
$count = Customer::where('stage', 'lead')->count();

// GOOD - Check existence
if (Customer::where('email', $email)->exists()) {
    // Email exists
}
```

### 5. **WhereHas vs Has**

```php
// Check existence only
if ($user->has('roles')) {
    // User has at least one role
}

// With conditions
$users = User::whereHas('branches', function ($query) use ($branchId) {
    $query->where('branches.id', $branchId);
})->get();

// Count relationships
$users = User::withCount('subordinates')->get();
// Access: $user->subordinates_count
```

---

## N+1 QUERY PROBLEMS

### Problem Example:
```php
// BAD - N+1 Query Problem
$classes = ClassModel::all();

foreach ($classes as $class) {
    echo $class->homeroomTeacher->name;     // +N query
    echo $class->branch->name;              // +N query
    
    foreach ($class->students as $student) { // +N query
        echo $student->user->name;          // +N*M query!
    }
}
```

### Solution:
```php
// GOOD - Eager Loading
$classes = ClassModel::with([
    'homeroomTeacher',
    'branch',
    'students.user'
])->get();

foreach ($classes as $class) {
    echo $class->homeroomTeacher->name;     // No extra query
    echo $class->branch->name;              // No extra query
    
    foreach ($class->students as $student) {
        echo $student->user->name;          // No extra query
    }
}
```

### Lazy Eager Loading:
```php
$classes = ClassModel::all();

// Later decide to load relationships
$classes->load('homeroomTeacher', 'branch');

// Conditional loading
if ($needStudents) {
    $classes->load('students.user');
}
```

### Debug N+1:
```php
// Enable query log
DB::enableQueryLog();

// Your code here
$users = User::all();
foreach ($users as $user) {
    echo $user->branch->name;
}

// Check queries
dd(DB::getQueryLog());

// Use Laravel Debugbar or Telescope in development
```

---

## COMPLEX QUERIES EXAMPLES

### 1. **Lấy students chưa nộp homework**

```php
$homeworkId = 1;

$studentsNotSubmitted = Student::whereHas('classes', function ($query) use ($homeworkId) {
        // Students in class that has this homework
        $query->whereHas('homeworkAssignments', function ($q) use ($homeworkId) {
            $q->where('id', $homeworkId);
        });
    })
    ->whereDoesntHave('homeworkSubmissions', function ($query) use ($homeworkId) {
        // But haven't submitted
        $query->where('homework_assignment_id', $homeworkId);
    })
    ->with('user')
    ->get();
```

### 2. **Classes với attendance rate**

```php
$classes = ClassModel::where('status', 'active')
    ->withCount([
        'lessonSessions',
        'lessonSessions as completed_sessions_count' => function ($query) {
            $query->where('status', 'completed');
        }
    ])
    ->get()
    ->map(function ($class) {
        $class->completion_rate = $class->lesson_sessions_count > 0
            ? ($class->completed_sessions_count / $class->lesson_sessions_count) * 100
            : 0;
        return $class;
    });
```

### 3. **Revenue report by branch**

```php
$revenue = Enrollment::selectRaw('
        branch_id,
        COUNT(*) as total_enrollments,
        SUM(final_price) as total_revenue,
        SUM(paid_amount) as total_paid,
        SUM(remaining_amount) as total_remaining
    ')
    ->where('status', 'active')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->groupBy('branch_id')
    ->with('branch')
    ->get();
```

### 4. **Students với multiple filters**

```php
$students = Student::query()
    ->when($branchId, function ($query) use ($branchId) {
        $query->where('branch_id', $branchId);
    })
    ->when($search, function ($query) use ($search) {
        $query->whereHas('user', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    })
    ->when($classId, function ($query) use ($classId) {
        $query->whereHas('classes', function ($q) use ($classId) {
            $q->where('classes.id', $classId);
        });
    })
    ->with(['user', 'branch', 'classes'])
    ->paginate(20);
```

### 5. **Zalo messages với conversation details**

```php
$messages = ZaloMessage::where('zalo_account_id', $accountId)
    ->where('conversation_id', $conversationId)
    ->with([
        'reactions.user',
        'quotedMessage',  // If reply to another message
    ])
    ->orderBy('sent_at', 'desc')
    ->paginate(50);
```

---

## DATABASE INDEXES

### Existing Indexes (from migrations):

#### **Users Table:**
```sql
INDEX(email)           -- Unique index
INDEX(phone)           -- Search by phone
INDEX(employee_code)   -- Search by code
INDEX(manager_id)      -- Hierarchy queries
```

#### **Customers Table:**
```sql
INDEX(code)
INDEX(phone)
INDEX(email)
INDEX(stage)
INDEX(branch_id)
INDEX(assigned_to)
INDEX(is_active)
INDEX(stage, stage_order)  -- Composite for Kanban
```

#### **Classes Table:**
```sql
INDEX(code)
INDEX(branch_id, academic_year)  -- Composite
INDEX(is_active)
INDEX(homeroom_teacher_id)
INDEX(status)
```

#### **Zalo Messages Table:**
```sql
INDEX(zalo_account_id)
INDEX(conversation_id)
INDEX(sent_at)
INDEX(zalo_account_id, conversation_id, sent_at)  -- Composite
```

### Khi nào cần thêm indexes?

1. **WHERE clauses** - Các field thường xuất hiện trong WHERE
2. **JOIN conditions** - Foreign keys
3. **ORDER BY** - Sorting fields
4. **GROUP BY** - Grouping fields
5. **Composite indexes** - Nhiều field thường query cùng nhau

### Example - Thêm index:

```php
// In migration
Schema::table('enrollments', function (Blueprint $table) {
    $table->index(['branch_id', 'status', 'created_at']);
    // For query: WHERE branch_id = ? AND status = ? ORDER BY created_at
});
```

### Kiểm tra index usage:

```sql
-- MySQL
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';

-- Check if using index
SHOW INDEX FROM users;
```

---

## PERFORMANCE TIPS

### 1. **Caching**

```php
// Cache expensive queries
$branches = Cache::remember('branches.active', 3600, function () {
    return Branch::where('is_active', true)->get();
});

// Cache user permissions
$permissions = Cache::remember("user.{$userId}.permissions", 3600, function () use ($userId) {
    return User::find($userId)->getAllPermissions();
});

// Clear cache when data changes
Cache::forget('branches.active');

// Or use tags (requires Redis/Memcached)
Cache::tags(['branches'])->flush();
```

### 2. **Database Connection Pooling**

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    // ...
    'options' => [
        PDO::ATTR_PERSISTENT => true,  // Connection pooling
    ],
];
```

### 3. **Queue Heavy Operations**

```php
// Dispatch to queue instead of synchronous
dispatch(new SyncGoogleDriveJob($class));

// For bulk operations
foreach ($students as $student) {
    SendEnrollmentEmailJob::dispatch($student)->onQueue('emails');
}
```

### 4. **Use Redis for Real-time Data**

```php
// Store online users
Redis::setex("user.{$userId}.online", 300, 1);

// Store temporary data
Redis::set("enrollment.{$enrollmentId}.draft", json_encode($data));
Redis::expire("enrollment.{$enrollmentId}.draft", 3600);
```

### 5. **Database Transactions**

```php
// For multiple related operations
DB::transaction(function () use ($enrollmentData) {
    $enrollment = Enrollment::create($enrollmentData);
    
    $enrollment->walletTransaction()->create([...]);
    
    Voucher::find($voucherId)->increment('usage_count');
});

// With explicit try-catch
DB::beginTransaction();
try {
    // Operations
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

---

## MONITORING & DEBUGGING

### 1. **Laravel Debugbar**

```bash
composer require barryvdh/laravel-debugbar --dev
```

```php
// Shows:
// - All queries executed
// - Query time
// - N+1 problems
// - Memory usage
```

### 2. **Laravel Telescope**

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

```php
// Access: http://your-app.test/telescope
// Monitor:
// - Queries
// - Slow queries
// - Failed jobs
// - Exceptions
// - Requests
```

### 3. **Query Logging**

```php
// In AppServiceProvider boot()
if (app()->environment('local')) {
    DB::listen(function ($query) {
        Log::info(
            $query->sql,
            [
                'bindings' => $query->bindings,
                'time' => $query->time
            ]
        );
    });
}
```

### 4. **Slow Query Log**

```php
// Log queries taking > 1000ms
DB::listen(function ($query) {
    if ($query->time > 1000) {
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'time' => $query->time,
            'bindings' => $query->bindings,
        ]);
    }
});
```

### 5. **Memory Usage**

```php
// Before operation
$memoryBefore = memory_get_usage();

// Your code
$students = Student::with('user', 'classes')->get();

// After
$memoryAfter = memory_get_usage();
$memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;

Log::info("Memory used: {$memoryUsed} MB");
```

---

## COMMON PITFALLS

### ❌ 1. **Loading too much data**
```php
// BAD
$users = User::with('branches', 'roles', 'departments', 'subjects')->all();

// GOOD - Only load what you need
$users = User::select('id', 'name', 'email')
    ->with(['roles' => function ($query) {
        $query->select('id', 'name');
    }])
    ->paginate(20);
```

### ❌ 2. **Not using transactions**
```php
// BAD - Race condition possible
$wallet = Wallet::find(1);
$wallet->balance -= 100;
$wallet->save();

// GOOD
DB::transaction(function () use ($walletId) {
    $wallet = Wallet::lockForUpdate()->find($walletId);
    $wallet->balance -= 100;
    $wallet->save();
});
```

### ❌ 3. **Looping over queries**
```php
// BAD
foreach ($studentIds as $studentId) {
    $student = Student::find($studentId);  // N queries
}

// GOOD
$students = Student::whereIn('id', $studentIds)->get();
```

### ❌ 4. **Not using indexes**
```php
// Slow query without index
User::where('phone', $phone)->first();

// Make sure 'phone' column has index
Schema::table('users', function (Blueprint $table) {
    $table->index('phone');
});
```

---

## TESTING QUERIES

### Using Tinker:

```bash
php artisan tinker
```

```php
// Test query
>>> $user = User::with('branches')->first();
>>> $user->branches

// Check SQL
>>> User::with('branches')->toSql();

// Enable query log
>>> DB::enableQueryLog();
>>> User::with('branches')->get();
>>> DB::getQueryLog();
```

### Unit Testing:

```php
class UserQueryTest extends TestCase
{
    /** @test */
    public function it_loads_user_with_branches_efficiently()
    {
        DB::enableQueryLog();
        
        $user = User::with('branches')->first();
        $user->branches->count();
        
        $queries = DB::getQueryLog();
        
        // Should be exactly 2 queries (user + branches)
        $this->assertCount(2, $queries);
    }
}
```

---

## QUICK REFERENCE

### Query Builder Methods:

| Method | Use Case |
|--------|----------|
| `select()` | Specify columns |
| `where()` | Filter results |
| `whereIn()` | Multiple values |
| `whereHas()` | Filter by relationship |
| `with()` | Eager load relationships |
| `withCount()` | Count relationships |
| `join()` | Join tables |
| `orderBy()` | Sort results |
| `groupBy()` | Group results |
| `having()` | Filter groups |
| `limit()` | Limit results |
| `offset()` | Skip results |
| `paginate()` | Pagination |
| `chunk()` | Process in chunks |
| `lazy()` | Lazy loading |

### Aggregates:

```php
->count()
->sum('column')
->avg('column')
->min('column')
->max('column')
```

---

**Tác giả:** AI Assistant  
**Ngày:** 24/11/2025  
**Phiên bản:** 1.0

