# 📚 TỔNG HỢP TÀI LIỆU CƠ SỞ DỮ LIỆU
## Dự Án School Management System

---

## 🎯 GIỚI THIỆU

Dự án **School Management System** là một hệ thống quản lý trường học/trung tâm đào tạo toàn diện được xây dựng trên **Laravel 11**, với cơ sở dữ liệu được thiết kế chi tiết và có cấu trúc tốt.

### Thông số kỹ thuật:
- **Framework**: Laravel 11
- **Database**: SQLite (mặc định) / MySQL / PostgreSQL
- **Total Migrations**: 154 files
- **Total Seeders**: 80+ files
- **Total Models**: 75+ models
- **Database Size**: Khoảng 150+ tables (bao gồm pivots)

---

## 📖 TÀI LIỆU ĐÃ TẠO

### 1. **DATABASE_STRUCTURE_ANALYSIS.md** 
📄 **Phân tích cấu trúc cơ sở dữ liệu tổng quan**

**Nội dung:**
- ✅ Tổng quan về hệ thống database
- ✅ Các module chính (14 modules)
- ✅ Chi tiết cấu trúc từng bảng
- ✅ Quan hệ giữa các bảng
- ✅ Timeline migrations
- ✅ Đặc điểm nổi bật
- ✅ Indexes quan trọng
- ✅ Khuyến nghị và best practices

**Các module được phân tích:**
1. Hệ thống người dùng & phân quyền
2. Quản lý chi nhánh & tổ chức
3. Quản lý học sinh & phụ huynh
4. Quản lý khách hàng (CRM)
5. Quản lý lớp học
6. Quản lý môn học & giáo án
7. Thời khóa biểu & lịch học
8. Điểm danh & đánh giá
9. Bài tập & nộp bài
10. Hệ thống kế toán & tài chính
11. Hệ thống đăng ký & thanh toán
12. Hệ thống Zalo Chat
13. Hệ thống Google Drive
14. Đa ngôn ngữ (i18n)

**Khi nào cần đọc:**
- ⭐ Khi muốn hiểu tổng quan về database
- ⭐ Khi cần biết các bảng có trong hệ thống
- ⭐ Khi thiết kế tính năng mới
- ⭐ Khi onboarding developers mới

---

### 2. **DATABASE_ERD_DIAGRAM.md**
🎨 **Sơ đồ quan hệ thực thể (ERD) trực quan**

**Nội dung:**
- ✅ Sơ đồ ERD dạng text-based ASCII art
- ✅ Visualization của các bảng chính
- ✅ Relationships được vẽ rõ ràng
- ✅ Data flow examples
- ✅ Customer journey visualization
- ✅ Class lifecycle diagram

**Các diagram chính:**
```
1. Core Entities - Users, Roles, Permissions
2. Branch Management - Branches, Departments, Positions
3. CRM - Customers, Interactions, Pipeline
4. Student & Parent relationships
5. Class & Subject relationships
6. Lesson Plans & Sessions
7. Attendance & Evaluation
8. Homework System
9. Course Posts (Forum)
10. Accounting & Finance
11. Wallet & Enrollment
12. Zalo Integration
13. Google Drive Integration
14. Multi-language System
```

**Khi nào cần đọc:**
- ⭐ Khi cần hình dung quan hệ giữa các bảng
- ⭐ Khi thiết kế features phức tạp
- ⭐ Khi debug relationship issues
- ⭐ Khi training team mới

---

### 3. **DATABASE_MODELS_ANALYSIS.md**
🔍 **Phân tích chi tiết các Models**

**Nội dung:**
- ✅ Chi tiết 7 models quan trọng nhất
- ✅ Design patterns được sử dụng
- ✅ Best practices đã áp dụng
- ✅ Code examples cho mỗi model
- ✅ Relationship methods
- ✅ Query scopes
- ✅ Business logic methods
- ✅ Potential issues & recommendations
- ✅ Model statistics & complexity analysis

**Models được phân tích:**
1. **User Model** (677 lines) - User trung tâm
2. **Student Model** (160 lines) - Học sinh
3. **Customer Model** (275 lines) - CRM
4. **Class Model** (206 lines) - Lớp học
5. **Enrollment Model** (307 lines) - Đăng ký
6. **Attendance Model** (52 lines) - Điểm danh
7. **ZaloAccount Model** (201 lines) - Zalo integration

**Design Patterns:**
- Repository Pattern (implicit)
- Observer Pattern
- Factory Pattern
- Strategy Pattern (in Scopes)
- Polymorphic Relationships
- Soft Delete Pattern
- Pivot Class Pattern

**Khi nào cần đọc:**
- ⭐ Khi làm việc với models
- ⭐ Khi cần hiểu business logic
- ⭐ Khi refactor code
- ⭐ Khi viết unit tests

---

### 4. **DATABASE_QUERIES_GUIDE.md**
⚡ **Hướng dẫn viết queries & performance**

**Nội dung:**
- ✅ Common query patterns
- ✅ Query optimization techniques
- ✅ N+1 query problems & solutions
- ✅ Complex queries examples
- ✅ Database indexes guide
- ✅ Performance tips
- ✅ Caching strategies
- ✅ Monitoring & debugging tools
- ✅ Common pitfalls
- ✅ Testing queries

**Sections:**
1. **Common Query Patterns**
   - User queries
   - Customer queries
   - Class queries
   - Student queries
   - Enrollment queries

2. **Query Optimization**
   - Select specific columns
   - Pagination
   - Chunk processing
   - Count optimization
   - WhereHas vs Has

3. **N+1 Query Problems**
   - Problem identification
   - Eager loading solutions
   - Lazy eager loading
   - Debug techniques

4. **Complex Queries**
   - Students chưa nộp homework
   - Classes với attendance rate
   - Revenue reports
   - Multi-filter queries
   - Zalo messages queries

5. **Performance Tips**
   - Caching
   - Connection pooling
   - Queue operations
   - Redis usage
   - Transactions

6. **Monitoring**
   - Laravel Debugbar
   - Laravel Telescope
   - Query logging
   - Slow query detection
   - Memory profiling

**Khi nào cần đọc:**
- ⭐ Khi viết queries phức tạp
- ⭐ Khi optimize performance
- ⭐ Khi gặp slow queries
- ⭐ Khi debug N+1 problems

---

## 🚀 QUICK START

### Bước 1: Setup Database

```bash
# SQLite (mặc định)
touch database/database.sqlite

# Hoặc MySQL (sửa .env)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school
DB_USERNAME=root
DB_PASSWORD=
```

### Bước 2: Run Migrations

```bash
php artisan migrate
```

### Bước 3: Seed Database

```bash
# Seed all (roles, permissions, translations, sample data)
php artisan db:seed

# Hoặc seed specific seeder
php artisan db:seed --class=DatabaseSeeder
php artisan db:seed --class=CompleteDatabaseSeeder
```

### Bước 4: Verify

```bash
# Check tables
php artisan tinker
>>> DB::table('users')->count()
>>> DB::table('branches')->count()

# Or use database tool (TablePlus, DBeaver, etc.)
```

---

## 📊 DATABASE OVERVIEW

### Các Bảng Chính (Top 20):

| Table | Rows (sample) | Purpose |
|-------|---------------|---------|
| users | 100+ | Người dùng (staff, teachers, students, parents) |
| translations | 5000+ | Đa ngôn ngữ |
| permissions | 200+ | Quyền hạn |
| branches | 5-10 | Chi nhánh |
| classes | 50+ | Lớp học |
| students | 500+ | Học sinh |
| customers | 1000+ | Khách hàng CRM |
| enrollments | 500+ | Đăng ký khóa học |
| attendances | 10000+ | Điểm danh |
| zalo_messages | 50000+ | Tin nhắn Zalo |
| class_students | 500+ | Học sinh trong lớp |
| class_schedules | 200+ | Thời khóa biểu |
| homework_assignments | 100+ | Bài tập |
| homework_submissions | 500+ | Bài nộp |
| financial_transactions | 1000+ | Giao dịch tài chính |
| wallets | 500+ | Ví điện tử |
| course_posts | 1000+ | Bài viết lớp học |
| calendar_events | 200+ | Lịch sự kiện |
| google_drive_items | 500+ | Files trên Drive |
| zalo_accounts | 5-10 | Tài khoản Zalo |

---

## 🔐 SECURITY & BEST PRACTICES

### 1. **Data Protection**
```php
// Sensitive data encryption
ZaloAccount: cookie field is encrypted
User: password is hashed
```

### 2. **Soft Deletes**
```php
// Preserve data history
use SoftDeletes;

// Models with soft deletes:
- User, Customer, Student, Parent
- Branch, Class, Subject
- ZaloAccount, Enrollment
```

### 3. **Authorization**
```php
// RBAC system
$user->hasPermission('customers.view');
$user->hasPermissionInBranch('classes.edit', $branchId);
$user->isSuperAdmin();
```

### 4. **Database Transactions**
```php
DB::transaction(function () {
    // Multiple operations
    $enrollment = Enrollment::create([...]);
    $wallet->decrement('balance', $amount);
    $voucher->increment('usage_count');
});
```

### 5. **Input Validation**
```php
// Always validate before saving
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'branch_id' => 'required|exists:branches,id',
]);
```

---

## 🛠️ MAINTENANCE GUIDE

### Regular Tasks:

#### 1. **Database Backup**
```bash
# SQLite
cp database/database.sqlite backups/db_$(date +%Y%m%d).sqlite

# MySQL
mysqldump -u root -p school > backups/school_$(date +%Y%m%d).sql
```

#### 2. **Clean Old Data**
```bash
# Delete old soft-deleted records
php artisan db:clean-soft-deletes --days=30
```

#### 3. **Optimize Tables**
```bash
# MySQL
php artisan db:optimize

# Or manual
OPTIMIZE TABLE users, customers, enrollments;
```

#### 4. **Monitor Slow Queries**
```bash
# Enable slow query log (MySQL)
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;  # Log queries > 1s
```

---

## 📈 PERFORMANCE METRICS

### Recommended Response Times:

| Query Type | Target | Warning | Critical |
|------------|--------|---------|----------|
| Simple SELECT | < 10ms | 50ms | 100ms |
| With 1 JOIN | < 20ms | 100ms | 200ms |
| Complex with 3+ JOINs | < 50ms | 200ms | 500ms |
| Aggregations | < 100ms | 500ms | 1s |
| Reports | < 500ms | 2s | 5s |

### Monitoring:

```bash
# Install Laravel Telescope
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Access dashboard
http://your-app.test/telescope
```

---

## 🧪 TESTING

### Database Testing:

```php
// Use RefreshDatabase trait
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function user_can_be_created()
    {
        $user = User::factory()->create();
        
        $this->assertDatabaseHas('users', [
            'email' => $user->email,
        ]);
    }
}
```

### Factory Examples:

```bash
php artisan tinker

# Create test data
>>> User::factory()->count(10)->create()
>>> Customer::factory()->count(50)->create()
>>> Enrollment::factory()->count(20)->create()
```

---

## 🔧 TROUBLESHOOTING

### Common Issues:

#### 1. **Migration Failed**
```bash
# Reset and re-migrate
php artisan migrate:fresh --seed

# Or step by step
php artisan migrate:rollback
php artisan migrate
```

#### 2. **Relationship Not Loading**
```bash
# Check relationship definition
>>> $user = User::find(1)
>>> $user->branches
>>> $user->load('branches')
```

#### 3. **Slow Queries**
```bash
# Enable query log
DB::enableQueryLog();
// Your code
dd(DB::getQueryLog());

# Check for N+1
// Use Laravel Debugbar or Telescope
```

#### 4. **Foreign Key Constraint Failed**
```bash
# Check if referenced record exists
>>> Branch::find($branchId)

# Or temporarily disable foreign key checks (careful!)
DB::statement('SET FOREIGN_KEY_CHECKS=0');
// Your operation
DB::statement('SET FOREIGN_KEY_CHECKS=1');
```

---

## 📚 LEARNING PATH

### Recommended Reading Order:

1. **Bắt đầu:** `DATABASE_STRUCTURE_ANALYSIS.md`
   - Hiểu tổng quan về database
   - Biết có những bảng nào

2. **Tiếp theo:** `DATABASE_ERD_DIAGRAM.md`
   - Hình dung quan hệ giữa các bảng
   - Hiểu data flow

3. **Sau đó:** `DATABASE_MODELS_ANALYSIS.md`
   - Học cách làm việc với models
   - Hiểu business logic

4. **Cuối cùng:** `DATABASE_QUERIES_GUIDE.md`
   - Viết queries hiệu quả
   - Optimize performance

---

## 🤝 CONTRIBUTING

### Khi thêm bảng mới:

1. **Tạo migration:**
```bash
php artisan make:migration create_new_table_name --create=new_table_name
```

2. **Tạo model:**
```bash
php artisan make:model NewModel -mfs
# -m: migration
# -f: factory
# -s: seeder
```

3. **Update documentation:**
   - Thêm vào `DATABASE_STRUCTURE_ANALYSIS.md`
   - Vẽ ERD trong `DATABASE_ERD_DIAGRAM.md`
   - Document model trong `DATABASE_MODELS_ANALYSIS.md`

4. **Add tests:**
```bash
php artisan make:test NewModelTest
```

---

## 📞 CONTACTS & SUPPORT

### Resources:

- **Laravel Documentation:** https://laravel.com/docs
- **Database Design:** https://dbdiagram.io
- **Query Optimization:** https://use-the-index-luke.com

### Tools:

- **Database Client:** TablePlus, DBeaver, HeidiSQL
- **Monitoring:** Laravel Telescope, Laravel Debugbar
- **Testing:** PHPUnit, Pest
- **CI/CD:** GitHub Actions, GitLab CI

---

## 📝 CHANGELOG

### Version 1.0 (2025-11-24)
- ✅ Initial database design completed
- ✅ 154 migrations created
- ✅ 75+ models implemented
- ✅ 80+ seeders created
- ✅ Documentation completed

### Future Enhancements:
- 🔄 Add database views for complex reports
- 🔄 Implement database partitioning for large tables
- 🔄 Add read replicas for scaling
- 🔄 Implement full-text search
- 🔄 Add database monitoring dashboard

---

## ⚖️ LICENSE

This project is proprietary software. All rights reserved.

---

## 🙏 ACKNOWLEDGMENTS

- Laravel Team for the amazing framework
- Community contributors
- Development team

---

**Tổng hợp bởi:** AI Assistant  
**Ngày:** 24/11/2025  
**Phiên bản:** 1.0

---

## 🎯 TÓM TẮT

Đây là một hệ thống quản lý trường học **toàn diện** với:

✅ **150+ bảng** được thiết kế kỹ lưỡng  
✅ **75+ models** với business logic đầy đủ  
✅ **14 modules** chức năng phong phú  
✅ **Tích hợp bên thứ 3** (Zalo, Google Drive)  
✅ **Multi-branch** & **Multi-language**  
✅ **RBAC** phân quyền chi tiết  
✅ **CRM** với sales pipeline  
✅ **Tài chính** đầy đủ  
✅ **Documentation** chi tiết  

**Chúc bạn làm việc hiệu quả! 🚀**

