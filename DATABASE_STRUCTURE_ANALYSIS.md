# PHÂN TÍCH CẤU TRÚC CƠ SỞ DỮ LIỆU - DỰ ÁN SCHOOL

## Tổng Quan

Dự án School là một hệ thống quản lý trường học/trung tâm đào tạo được xây dựng trên Laravel, sử dụng SQLite làm cơ sở dữ liệu mặc định (có thể cấu hình sang MySQL/PostgreSQL).

### Thông Tin Database
- **Framework**: Laravel 11
- **Database**: SQLite (mặc định) / MySQL / PostgreSQL
- **File database**: `database/database.sqlite`
- **Số lượng migrations**: 154 file
- **Số lượng seeders**: 80+ file

---

## Các Module Chính

### 1. **HỆ THỐNG NGƯỜI DÙNG & PHÂN QUYỀN**

#### Users Table (`users`)
Bảng trung tâm lưu trữ thông tin người dùng (nhân viên, giáo viên, học sinh, phụ huynh)

**Cấu trúc chính:**
```
- id (PK)
- name, email, password
- phone, avatar
- date_of_birth, gender, address
- employee_code, join_date, employment_status
- language_id (FK)
- manager_id (FK to users) - Quản lý cấp trên
- hierarchy_level - Cấp bậc phân cấp
- google_email, google_drive_folder_id - Tích hợp Google Drive
```

**Quan hệ:**
- Many-to-Many với `branches` (qua `branch_user`)
- Many-to-Many với `roles` (qua `role_user`)
- Many-to-Many với `departments` (qua `department_user`)
- Many-to-Many với `subjects` (giáo viên dạy môn học)
- Self-referencing: `manager_id` tạo cấu trúc phân cấp

#### Roles & Permissions (`roles`, `permissions`)
Hệ thống phân quyền RBAC (Role-Based Access Control)

**Bảng liên quan:**
- `roles` - Vai trò (admin, teacher, student, parent, etc.)
- `permissions` - Quyền hạn theo module
- `role_user` - Pivot: User có role nào
- `permission_role` - Pivot: Role có permission nào
- `position_role` - Pivot: Position có role nào

**Đặc điểm:**
- Hỗ trợ super-admin bypass mọi kiểm tra quyền
- Permission theo module và action
- Phân quyền theo branch (chi nhánh)
- Phân quyền theo position (chức vụ)

---

### 2. **QUẢN LÝ CHI NHÁNH & TỔ CHỨC**

#### Branches Table (`branches`)
Quản lý các chi nhánh/cơ sở

**Cấu trúc:**
```
- id (PK)
- code (unique) - Mã chi nhánh (HN01, HCM01)
- name - Tên chi nhánh
- phone, email, address
- city, district, ward
- manager_id (FK to users)
- is_active, is_headquarters
- metadata (JSON)
```

**Quan hệ:**
- Many-to-Many với `users` (qua `branch_user`)
- One-to-Many với `classes`, `students`, `departments`

#### Departments & Positions
Cấu trúc tổ chức phòng ban

**Bảng:**
- `departments` - Phòng ban/bộ môn
- `positions` - Chức vụ
- `department_user` - Pivot phức tạp với nhiều thông tin:
  - position_id, is_head, is_deputy
  - start_date, end_date, status
  - manager_user_id - Quản lý trực tiếp

---

### 3. **QUẢN LÝ HỌC SINH & PHỤ HUYNH**

#### Students Table (`students`)
Thông tin học sinh

**Cấu trúc:**
```
- id (PK)
- user_id (FK to users, unique)
- student_code (auto-generated: STD202500001)
- branch_id (FK)
- enrollment_date
- is_active
- notes
```

**Quan hệ:**
- BelongsTo `User` (1-1)
- BelongsTo `Branch`
- Many-to-Many với `Parents` (qua `parent_student`)
- Many-to-Many với `Classes` (qua `class_students`)
- Has-Many `Attendances`, `Enrollments`

#### Parents Table (`parents`)
Thông tin phụ huynh

**Bảng liên quan:**
- `parents` - Thông tin phụ huynh
- `parent_student` - Quan hệ phụ huynh-học sinh
  - relationship: 'father', 'mother', 'guardian'
  - is_primary: Phụ huynh chính

---

### 4. **QUẢN LÝ KHÁCH HÀNG (CRM)**

#### Customers Table (`customers`)
Hệ thống CRM với pipeline bán hàng

**Cấu trúc:**
```
- id (PK)
- code (unique) - Mã khách hàng (CUS20251124001)
- name, phone, email
- date_of_birth, gender, address
- stage - Giai đoạn trong pipeline:
  * lead (Khách tiềm năng)
  * contacted (Đã liên hệ)
  * qualified (Đủ điều kiện)
  * proposal (Đã gửi đề xuất)
  * negotiation (Đang đàm phán)
  * closed_won (Chốt thành công)
  * closed_lost (Mất khách)
- stage_order - Thứ tự drag-drop trong stage
- source - Nguồn khách hàng
- branch_id (FK)
- assigned_to (FK to users) - Người phụ trách
- estimated_value - Giá trị dự kiến
- expected_close_date, closed_at
- user_id (FK) - Link với users nếu chuyển thành học sinh
```

**Quan hệ:**
- Has-Many `CustomerInteraction` - Lịch sử tương tác
- Has-Many `CustomerChild` - Con của khách hàng
- Has-Many `CalendarEvent` - Lịch hẹn
- Has-Many `TrialStudent` - Đăng ký học thử
- Has-Many `Enrollment` - Đăng ký khóa học
- MorphOne `Wallet` - Ví tiền

#### Customer Interactions (`customer_interactions`)
Lưu lịch sử tương tác với khách hàng

**Liên quan:**
- `customer_interaction_types` - Loại tương tác
- `customer_interaction_results` - Kết quả tương tác
- `customer_sources` - Nguồn khách hàng

---

### 5. **QUẢN LÝ LỚP HỌC**

#### Classes Table (`classes`)
Thông tin lớp học

**Cấu trúc:**
```
- id (PK)
- branch_id (FK)
- name - Tên lớp (10A1, IELTS 6.0)
- code (unique) - Mã lớp
- academic_year - Năm học (2024-2025)
- level - Cấp học (elementary, middle, high, university)
- capacity, current_students - Sĩ số
- homeroom_teacher_id (FK to users) - GVCN
- subject_id (FK) - Môn học chính
- lesson_plan_id (FK) - Giáo án
- semester_id (FK)
- start_date, end_date
- total_sessions, completed_sessions
- room_number
- status (planning, active, completed, cancelled)
- zalo_group_id, zalo_account_id - Tích hợp Zalo
- google_drive_folder_id - Tích hợp Google Drive
```

**Quan hệ:**
- BelongsTo `Branch`, `Semester`, `LessonPlan`
- BelongsTo `User` (homeroom teacher)
- Many-to-Many với `Subjects` (qua `class_subject`)
  - teacher_id, periods_per_week, room_number, status
- Many-to-Many với `Users` (teachers)
- Has-Many `ClassStudent`, `ClassSchedule`, `ClassLessonSession`

#### Class Students (`class_students`)
Học sinh trong lớp

**Cấu trúc:**
```
- id (PK)
- class_id (FK)
- student_id (FK to users)
- enrollment_date
- status (active, completed, dropped, transferred)
- discount_percent
- notes
```

---

### 6. **QUẢN LÝ MÔN HỌC & GIÁO ÁN**

#### Subjects Table (`subjects`)
Các môn học

**Quan hệ:**
- Many-to-Many với `Users` (giáo viên dạy môn, qua `subject_teacher`)
  - is_head: Tổ trưởng môn
- Many-to-Many với `Classes` (qua `class_subject`)

#### Lesson Plans & Sessions
Giáo án và các buổi học

**Bảng:**
- `lesson_plans` - Giáo án tổng thể
  - subject_id, name, description
  - total_sessions
  - google_drive_folder_id
  
- `lesson_plan_sessions` - Chi tiết từng buổi học
  - lesson_plan_id, session_number
  - title, content, objectives
  - duration_minutes
  - homework_description
  - valuation_form_id - Form đánh giá
  - google_drive_folder_id

- `class_lesson_sessions` - Buổi học thực tế của lớp
  - class_id, lesson_plan_session_id
  - teacher_id (FK to users) - Giáo viên dạy buổi này
  - scheduled_date, scheduled_time
  - actual_date, actual_time
  - status (scheduled, completed, cancelled)
  - content_taught, homework_assigned
  - google_drive_folder_id, homework_folder_id

---

### 7. **THỜI KHÓA BIỂU & LỊCH HỌC**

#### Class Schedules (`class_schedules`)
Thời khóa biểu lớp học

**Cấu trúc:**
```
- class_id, subject_id
- day_of_week (1-7)
- study_period_id (FK)
- room_id (FK)
- effective_from, effective_to
```

#### Study Periods (`study_periods`)
Các tiết học trong ngày

**Cấu trúc:**
```
- name (Tiết 1, Tiết 2)
- sort_order
- branch_id
```

#### Calendar Events (`calendar_events`)
Lịch sự kiện (họp, tư vấn, xét tuyển)

**Cấu trúc:**
```
- eventable_type, eventable_id (polymorphic)
- title, description
- category (meeting, placement_test, trial_class, consultation)
- start_time, end_time
- location, branch_id
- assigned_to (FK to users)
- status (scheduled, confirmed, completed, cancelled)
- reminder_sent
- feedback - Phản hồi sau sự kiện
```

#### Holidays (`holidays`)
Ngày nghỉ lễ

---

### 8. **ĐIỂM DANH & ĐÁNH GIÁ**

#### Attendances (`attendances`)
Điểm danh học sinh

**Cấu trúc:**
```
- student_id (FK to users)
- class_lesson_session_id (FK)
- status (present, absent, late, excused)
- notes
- evaluation_data (JSON) - Kết quả đánh giá
- evaluation_pdf_url - File PDF đánh giá
```

#### Valuation Forms (`valuation_forms`, `valuation_form_fields`)
Biểu mẫu đánh giá học sinh

**Cấu trúc:**
- `valuation_forms` - Form tổng
- `valuation_form_fields` - Các trường đánh giá
  - field_type (text, number, select, checkbox, rating)

#### Attendance Fee System
Hệ thống tính học phí dựa trên điểm danh

**Bảng:**
- `attendance_fee_policies` - Chính sách học phí
  - class_id, branch_id
  - base_fee_per_session
  - late_minutes_threshold
  - late_penalty_percent
  - absent_refund_percent
  
- `attendance_fee_deductions` - Chi tiết khấu trừ
  - attendance_id
  - deduction_type (absent_refund, late_penalty)
  - amount

---

### 9. **BÀI TẬP & NỘP BÀI**

#### Homework System
Hệ thống bài tập

**Bảng:**
- `homework_assignments` - Bài tập được giao
  - class_id, title, description
  - due_date, max_score
  - google_drive_folder_id

- `homework_submissions` - Bài nộp của học sinh
  - homework_assignment_id
  - student_id (FK to users)
  - submission_date, content
  - score, feedback
  - google_drive_folder_id, unit_folder_link

#### Course Posts (Forum/Newsfeed)
Diễn đàn lớp học

**Bảng:**
- `course_posts` - Bài viết
  - post_type (announcement, material, homework, event, discussion)
  - class_id, user_id
  - content
  - event_date (nếu là event)

- `course_post_comments` - Bình luận
- `course_post_likes` - Thích/reaction
  - reaction_type (like, love, care, haha, wow, sad, angry)
- `course_post_media` - Media đính kèm

- `course_assignments` - Bài tập qua post
- `course_submissions` - Bài nộp

---

### 10. **HỆ THỐNG KẾ TOÁN & TÀI CHÍNH**

#### Account Structure
Cấu trúc tài khoản kế toán

**Bảng:**
- `account_categories` - Danh mục tài khoản
  - code, name
  - type (income, expense, asset, liability)
  - cost_type (fixed, variable, null)

- `account_items` - Các khoản mục cụ thể
  - account_category_id
  - code, name, description

#### Financial Management
Quản lý tài chính

**Bảng:**
- `cash_accounts` - Tài khoản tiền mặt
  - branch_id, name
  - account_number, bank_name
  - current_balance

- `financial_plans` - Kế hoạch tài chính
  - branch_id, period (monthly, quarterly, yearly)
  - start_date, end_date
  - status (draft, pending, approved, rejected)
  
- `financial_plan_items` - Chi tiết kế hoạch

- `expense_proposals` - Đề xuất chi tiêu
  - account_item_id, cash_account_id
  - amount, purpose
  - requested_by, approved_by
  - status (pending, approved, rejected, disbursed)

- `income_reports` - Báo cáo thu nhập
  - branch_id, account_item_id
  - amount, source, payment_method
  - status (pending, verified, approved, rejected)

- `financial_transactions` - Giao dịch tài chính
  - transaction_type (income, expense)
  - amount, description
  - status (pending, completed, cancelled)

---

### 11. **HỆ THỐNG ĐĂNG KÝ & THANH TOÁN**

#### Products & Vouchers
Sản phẩm và voucher

**Bảng:**
- `products` - Sản phẩm/khóa học
  - branch_id, name, description
  - product_type (course, service, package)
  - price, duration_months
  - max_students, is_active

- `vouchers` - Mã giảm giá
  - code (unique)
  - discount_type (percent, fixed)
  - discount_value
  - min_purchase_amount, max_discount
  - usage_limit, times_used
  - start_date, end_date

- `campaigns` - Chiến dịch marketing
  - name, description
  - start_date, end_date
  - discount_percent
  - target_audience

- `voucher_usage` - Lịch sử sử dụng voucher

#### Wallet System
Hệ thống ví điện tử

**Bảng:**
- `wallets` - Ví của user/customer/student
  - owner_type, owner_id (polymorphic)
  - balance, currency
  - is_active

- `wallet_transactions` - Giao dịch ví
  - wallet_id
  - transaction_type (deposit, withdrawal, purchase, refund)
  - amount, description
  - status (pending, completed, failed, cancelled)

#### Enrollments
Đăng ký khóa học

**Bảng:**
- `enrollments` - Đơn đăng ký
  - customer_id (người đăng ký)
  - student_type, student_id (polymorphic - người học)
  - product_id, class_id
  - total_price, discount_amount, final_price
  - voucher_code
  - payment_method (wallet, cash, bank_transfer, card)
  - payment_status (pending, partial, paid, refunded)
  - approval_status (pending, approved, rejected)
  - status (pending, active, completed, cancelled)

---

### 12. **HỆ THỐNG ZALO CHAT**

#### Zalo Integration
Tích hợp chat Zalo

**Bảng:**
- `zalo_accounts` - Tài khoản Zalo
  - name, phone, zalo_id
  - cookie (encrypted), imei, user_agent
  - avatar_url, avatar_path
  - branch_id, assigned_to (FK to users)
  - is_primary, is_active, is_connected
  - last_sync_at, last_login_at

- `zalo_friends` - Bạn bè Zalo
  - zalo_account_id
  - friend_zalo_id, display_name
  - avatar_url

- `zalo_groups` - Nhóm Zalo
  - zalo_account_id
  - group_id, name
  - avatar_url
  - total_members
  - type (group, chat)

- `zalo_group_members` - Thành viên nhóm

- `zalo_conversations` - Cuộc hội thoại
  - zalo_account_id
  - conversation_type (friend, group)
  - conversation_id
  - last_message_at

- `zalo_messages` - Tin nhắn
  - zalo_account_id
  - conversation_id
  - message_id, global_id
  - content, content_type (text, image, sticker, file, video, link)
  - sender_id, sender_name
  - quote (trả lời tin nhắn)
  - sent_at

- `zalo_message_reactions` - Reaction tin nhắn

- `zalo_recent_stickers` - Sticker gần đây

---

### 13. **HỆ THỐNG GOOGLE DRIVE**

#### Google Drive Integration
Tích hợp Google Drive để quản lý tài liệu

**Bảng:**
- `google_drive_settings` - Cấu hình Google Drive
  - branch_id
  - root_folder_id, root_folder_name
  - teachers_folder_id
  - syllabus_folder_id
  - lesson_plan_folder_id

- `google_drive_items` - Các file/folder trên Drive
  - drive_id (Google Drive ID)
  - name, type (file, folder)
  - parent_id
  - mime_type, size
  - web_view_link

- `google_drive_permissions` - Quyền truy cập Drive
  - user_id
  - google_drive_item_id
  - permission_type (reader, writer, owner)
  - is_verified

---

### 14. **ĐA NGÔN NGỮ (i18n)**

#### Translations System
Hệ thống đa ngôn ngữ

**Bảng:**
- `languages` - Các ngôn ngữ
  - code (vi, en)
  - name, is_active, is_default

- `translations` - Các chuỗi dịch
  - language_id
  - key (module.section.key)
  - value (nội dung dịch)

**Seeders đa ngôn ngữ:**
- 80+ seeders để tạo translations cho tất cả module
- Hỗ trợ Tiếng Việt và Tiếng Anh

---

## SƠ ĐỒ QUAN HỆ CHÍNH

### User-Centric Relationships

```
User (users)
├─── Many-to-Many: Branches (branch_user)
├─── Many-to-Many: Roles (role_user)
├─── Many-to-Many: Departments (department_user)
├─── Many-to-Many: Subjects (subject_teacher)
├─── One-to-Many: Managed Branches (as manager)
├─── One-to-Many: Homeroom Classes (as teacher)
├─── Many-to-Many: Teaching Classes (class_subject)
├─── Self-Reference: Manager (manager_id)
├─── One-to-One: Student
└─── One-to-One: Parent
```

### Class-Centric Relationships

```
Class (classes)
├─── BelongsTo: Branch
├─── BelongsTo: Homeroom Teacher (User)
├─── BelongsTo: Subject
├─── BelongsTo: Lesson Plan
├─── BelongsTo: Semester
├─── Many-to-Many: Subjects (class_subject)
├─── Many-to-Many: Teachers (class_subject)
├─── One-to-Many: Class Students
├─── One-to-Many: Class Schedules
├─── One-to-Many: Class Lesson Sessions
├─── One-to-One: Zalo Group
└─── One-to-One: Google Drive Folder
```

### Customer Journey

```
Customer (customers)
├─── Stage Pipeline:
│    lead → contacted → qualified → proposal
│    → negotiation → closed_won / closed_lost
├─── Has-Many: Customer Interactions
├─── Has-Many: Customer Children
├─── Has-Many: Calendar Events (appointments)
├─── Has-Many: Trial Students
├─── Has-Many: Enrollments
├─── MorphOne: Wallet
└─── Optional: Link to User (becomes student)
```

---

## ĐẶC ĐIỂM NỔI BẬT

### 1. **Polymorphic Relationships**
- `Wallet`: owner_type, owner_id (Customer, Student, Parent)
- `Enrollment`: student_type, student_id (Customer, Student, TrialStudent)
- `CalendarEvent`: eventable_type, eventable_id (Customer, Class)

### 2. **Soft Deletes**
Nhiều bảng sử dụng soft deletes để giữ lịch sử:
- users, customers, students, parents
- branches, classes, subjects
- zalo_accounts

### 3. **JSON Metadata**
Nhiều bảng có trường `metadata` (JSON) để lưu thông tin linh hoạt:
- users, branches, customers
- zalo_accounts
- classes

### 4. **Hierarchy & Access Control**
- User hierarchy: manager_id, hierarchy_level
- Department hierarchy: manager trong department_user
- Branch-aware permissions
- Scope accessibleBy() để filter dữ liệu theo phân quyền

### 5. **Audit Trail**
- `timestamps()` ở hầu hết các bảng (created_at, updated_at)
- Các trường tracking: last_sync_at, last_login_at
- Status fields để theo dõi workflow

### 6. **Multi-tenancy**
Hỗ trợ nhiều chi nhánh:
- Hầu hết bảng có `branch_id`
- User có thể thuộc nhiều branches (many-to-many)
- Data isolation theo branch

---

## INDEXES QUAN TRỌNG

### Performance Indexes

1. **Foreign Keys**: Tất cả FK đều có index tự động
2. **Search Fields**:
   - users: email, phone, employee_code
   - students: student_code
   - customers: code, phone, email, stage
   - classes: code, branch_id + academic_year

3. **Status Fields**:
   - is_active, is_deleted
   - status enums

4. **Date Ranges**:
   - start_date, end_date
   - created_at, updated_at

5. **Composite Indexes**:
   - customers: (stage, stage_order) - for Kanban
   - class_students: (class_id, student_id) - unique
   - zalo_messages: (zalo_account_id, conversation_id, sent_at)

---

## MIGRATIONS TIMELINE

### Phase 1: Core System (Oct 31, 2025)
- Users, Roles, Permissions
- Branches, Departments, Positions
- Languages & Translations
- Customers & CRM

### Phase 2: Academic System (Nov 4-5, 2025)
- Subjects, Classes, Schedules
- Lesson Plans & Sessions
- Attendances, Evaluations
- Accounting & Financial

### Phase 3: Sales & Enrollment (Nov 6, 2025)
- Products, Vouchers, Campaigns
- Wallets & Transactions
- Enrollments
- Students & Parents

### Phase 4: Learning Management (Nov 8-11, 2025)
- Attendance Fee System
- Course Posts & Forum
- Homework System
- Google Drive Integration

### Phase 5: Communication (Nov 13-19, 2025)
- Zalo Integration
- Multi-account Zalo
- Message Sync
- Notifications & Reminders

---

## SEEDERS & SAMPLE DATA

### 80+ Seeders bao gồm:

1. **Core Data**:
   - DatabaseSeeder, CompleteDatabaseSeeder
   - RolePermissionSeeder
   - BranchSeeder, LanguageSeeder

2. **Permissions**:
   - Module-specific permission seeders cho từng phần
   - AccountingPermissionsSeeder
   - ClassesPermissionsSeeder
   - SubjectsPermissionsSeeder
   - etc.

3. **Translations**:
   - 50+ translation seeders cho từng module
   - Hỗ trợ đa ngôn ngữ (vi, en)

4. **Sample Data**:
   - AccountingSampleDataSeeder
   - ClassesSampleDataSeeder
   - StudentsSeeder, TeachersSeeder
   - ProductsSeeder, VouchersAndCampaignsSeeder

---

## KẾT LUẬN

Đây là một hệ thống quản lý trường học/trung tâm đào tạo rất toàn diện với:

✅ **Quản lý đa chi nhánh** (Multi-branch)
✅ **Phân quyền chi tiết** (RBAC + Hierarchy)
✅ **CRM & Sales Pipeline** (Customer journey)
✅ **Quản lý học tập** (Classes, Schedules, Attendances)
✅ **Hệ thống tài chính** (Accounting, Wallets, Enrollments)
✅ **Tích hợp bên thứ 3** (Zalo Chat, Google Drive)
✅ **Đa ngôn ngữ** (i18n)
✅ **Audit trail & Soft deletes**

### Khuyến nghị:

1. **Backup thường xuyên** - Database có nhiều quan hệ phức tạp
2. **Monitor performance** - Thêm indexes cho các query chậm
3. **Document API** - Cần tài liệu API cho frontend
4. **Testing** - Viết tests cho các quan hệ phức tạp
5. **Data validation** - Kiểm tra constraints ở application level

---

**Tác giả:** AI Assistant  
**Ngày:** 24/11/2025  
**Phiên bản:** 1.0

