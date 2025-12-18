# SƠ ĐỒ QUAN HỆ CƠ SỞ DỮ LIỆU (ERD)
# Dự án School Management System

---

## CORE ENTITIES - HỆ THỐNG CỐT LÕI

```
┌─────────────────────────────────────────────────────────────┐
│                        USERS TABLE                          │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                             │  │
│  │ - name, email, password                               │  │
│  │ - phone, avatar                                       │  │
│  │ - employee_code                                       │  │
│  │ - manager_id (FK → users.id) [Self Reference]        │  │
│  │ - hierarchy_level                                     │  │
│  │ - language_id (FK → languages.id)                    │  │
│  │ - google_email, google_drive_folder_id              │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
      ┌─────────────────────┼─────────────────────┐
      │                     │                     │
      ▼                     ▼                     ▼
┌──────────┐         ┌───────────┐         ┌──────────┐
│ ROLES    │◄────────┤ ROLE_USER ├────────►│  USERS   │
│          │         │ (pivot)   │         │          │
│ - id     │         │ - user_id │         │          │
│ - name   │         │ - role_id │         │          │
│ - desc   │         └───────────┘         └──────────┘
└──────────┘               │
      │                    │
      │                    ▼
      │              ┌──────────────┐
      └──────────────┤ PERMISSIONS  │
                     │ - id         │
                     │ - name       │
                     │ - module     │
                     │ - action     │
                     └──────────────┘
```

---

## BRANCH MANAGEMENT - QUẢN LÝ CHI NHÁNH

```
┌───────────────────────────────────────────────────────┐
│                    BRANCHES TABLE                     │
│  ┌─────────────────────────────────────────────────┐  │
│  │ - id (PK)                                       │  │
│  │ - code (unique)         Example: HN01, HCM01   │  │
│  │ - name                                          │  │
│  │ - manager_id (FK → users.id)                   │  │
│  │ - phone, email, address                        │  │
│  │ - is_active, is_headquarters                   │  │
│  └─────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────┘
                        │
                        │ Many-to-Many
                        ▼
                ┌───────────────┐
                │  BRANCH_USER  │ (pivot)
                │  - branch_id  │
                │  - user_id    │
                │  - is_primary │ ← User's primary branch
                └───────────────┘
                        │
                        ▼
                  ┌──────────┐
                  │  USERS   │
                  └──────────┘
```

---

## DEPARTMENT & POSITION - TỔ CHỨC & CHỨC VỤ

```
┌─────────────────┐          ┌──────────────────┐
│   DEPARTMENTS   │          │    POSITIONS     │
│                 │          │                  │
│ - id (PK)      │          │ - id (PK)       │
│ - name         │          │ - name          │
│ - branch_id    │          │ - branch_id     │
│ - description  │          │ - level         │
└────────┬────────┘          └────────┬─────────┘
         │                            │
         │    ┌───────────────────────┼────────────────┐
         │    │   DEPARTMENT_USER (pivot - Complex)   │
         └────┤   - department_id                     │
              │   - user_id                           │
              │   - position_id (FK → positions.id)  │
              │   - is_head, is_deputy               │
              │   - manager_user_id (FK → users.id)  │
              │   - start_date, end_date, status     │
              └───────────────────────────────────────┘
                              │
                              ▼
                        ┌──────────┐
                        │  USERS   │
                        └──────────┘
```

---

## CUSTOMER RELATIONSHIP MANAGEMENT (CRM)

```
┌───────────────────────────────────────────────────────────────┐
│                      CUSTOMERS TABLE                          │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                               │  │
│  │ - code (unique)        Example: CUS20251124001         │  │
│  │ - name, phone, email                                   │  │
│  │ - branch_id (FK → branches.id)                        │  │
│  │ - assigned_to (FK → users.id) ← Người phụ trách      │  │
│  │ - user_id (FK → users.id) ← Link khi trở thành User  │  │
│  │                                                         │  │
│  │ SALES PIPELINE (Kanban):                               │  │
│  │ - stage: lead → contacted → qualified → proposal       │  │
│  │          → negotiation → closed_won / closed_lost      │  │
│  │ - stage_order (for drag-drop)                          │  │
│  │                                                         │  │
│  │ - source, estimated_value, expected_close_date        │  │
│  │ - closed_at, notes, metadata                          │  │
│  └─────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────────┘
          │               │               │               │
          ▼               ▼               ▼               ▼
┌─────────────────┐  ┌──────────────┐  ┌──────────┐  ┌─────────┐
│ CUSTOMER_       │  │ CUSTOMER_    │  │ CALENDAR │  │ TRIAL_  │
│ INTERACTIONS    │  │ CHILDREN     │  │ _EVENTS  │  │ STUDENTS│
│                 │  │              │  │          │  │         │
│ - customer_id   │  │ - customer_id│  │(morphed) │  │(morphed)│
│ - type_id       │  │ - name       │  │          │  │         │
│ - result_id     │  │ - dob        │  │          │  │         │
│ - date, notes   │  │ - gender     │  │          │  │         │
└─────────────────┘  └──────────────┘  └──────────┘  └─────────┘
```

---

## STUDENT & PARENT - HỌC SINH & PHỤ HUYNH

```
┌─────────────────────────────────────────────────────┐
│                  STUDENTS TABLE                     │
│  ┌───────────────────────────────────────────────┐  │
│  │ - id (PK)                                     │  │
│  │ - user_id (FK → users.id, unique) [1-to-1]  │  │
│  │ - student_code (auto: STD202500001)         │  │
│  │ - branch_id (FK → branches.id)              │  │
│  │ - enrollment_date                            │  │
│  │ - is_active                                  │  │
│  └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
                    │
                    │ Many-to-Many
                    ▼
            ┌─────────────────┐
            │ PARENT_STUDENT  │ (pivot)
            │ - parent_id     │
            │ - student_id    │
            │ - relationship  │ ← father, mother, guardian
            │ - is_primary    │
            └─────────────────┘
                    │
                    ▼
        ┌────────────────────┐
        │   PARENTS TABLE    │
        │ - id (PK)         │
        │ - user_id (FK)    │
        │ - parent_code     │
        │ - branch_id       │
        └────────────────────┘
```

---

## CLASS & SUBJECT - LỚP HỌC & MÔN HỌC

```
┌──────────────────────────────────────────────────────────────┐
│                        CLASSES TABLE                         │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                              │  │
│  │ - branch_id (FK → branches.id)                        │  │
│  │ - code (unique), name                                  │  │
│  │ - homeroom_teacher_id (FK → users.id) [GVCN]         │  │
│  │ - subject_id (FK → subjects.id) [Môn chính]          │  │
│  │ - lesson_plan_id (FK → lesson_plans.id)              │  │
│  │ - semester_id (FK → semesters.id)                    │  │
│  │                                                        │  │
│  │ - academic_year, level, capacity                      │  │
│  │ - start_date, end_date                                │  │
│  │ - total_sessions, completed_sessions                  │  │
│  │ - status: planning, active, completed, cancelled      │  │
│  │                                                        │  │
│  │ INTEGRATIONS:                                          │  │
│  │ - google_drive_folder_id                              │  │
│  │ - zalo_group_id, zalo_account_id                     │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
         │                  │                    │
         │                  │                    │
         ▼                  ▼                    ▼
┌─────────────────┐  ┌──────────────┐  ┌────────────────┐
│ CLASS_STUDENTS  │  │ CLASS_SUBJECT│  │ CLASS_SCHEDULES│
│                 │  │    (pivot)   │  │                │
│ - class_id      │  │ - class_id   │  │ - class_id     │
│ - student_id    │  │ - subject_id │  │ - subject_id   │
│   (FK → users)  │  │ - teacher_id │  │ - day_of_week  │
│ - enrollment_   │  │ - periods/wk │  │ - study_period │
│   date          │  │ - status     │  │ - room_id      │
│ - status        │  │              │  │                │
│ - discount_%    │  └──────────────┘  └────────────────┘
└─────────────────┘          │
         │                   │
         ▼                   ▼
    ┌─────────┐       ┌───────────┐
    │ USERS   │       │ SUBJECTS  │
    │(student)│       │           │
    └─────────┘       │ - id      │
                      │ - name    │
                      │ - code    │
                      └───────────┘
                            │
                            │ Many-to-Many
                            ▼
                    ┌─────────────────┐
                    │ SUBJECT_TEACHER │ (pivot)
                    │ - subject_id    │
                    │ - user_id       │
                    │ - is_head       │ ← Tổ trưởng môn
                    │ - start/end date│
                    └─────────────────┘
                            │
                            ▼
                      ┌──────────┐
                      │  USERS   │
                      │(teacher) │
                      └──────────┘
```

---

## LESSON PLANS & SESSIONS - GIÁO ÁN & BUỔI HỌC

```
┌────────────────────────────────────────────────────┐
│               LESSON_PLANS TABLE                   │
│  ┌──────────────────────────────────────────────┐  │
│  │ - id (PK)                                    │  │
│  │ - subject_id (FK → subjects.id)             │  │
│  │ - name, description                          │  │
│  │ - total_sessions                             │  │
│  │ - google_drive_folder_id                    │  │
│  └──────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────┘
                    │
                    │ Has Many
                    ▼
        ┌────────────────────────────┐
        │ LESSON_PLAN_SESSIONS TABLE │
        │ - id (PK)                  │
        │ - lesson_plan_id           │
        │ - session_number           │
        │ - title, content           │
        │ - objectives               │
        │ - duration_minutes         │
        │ - homework_description     │
        │ - valuation_form_id        │
        │ - google_drive_folder_id   │
        └────────────────────────────┘
                    │
                    │ Referenced by
                    ▼
        ┌────────────────────────────────────┐
        │   CLASS_LESSON_SESSIONS TABLE      │
        │   (Buổi học thực tế của lớp)      │
        │                                    │
        │ - id (PK)                         │
        │ - class_id (FK)                   │
        │ - lesson_plan_session_id (FK)     │
        │ - teacher_id (FK → users.id)      │ ← Giáo viên dạy
        │                                    │
        │ - scheduled_date, scheduled_time   │
        │ - actual_date, actual_time        │
        │ - status: scheduled/completed/     │
        │           cancelled                │
        │                                    │
        │ - content_taught                   │
        │ - homework_assigned                │
        │                                    │
        │ - google_drive_folder_id          │
        │ - homework_folder_id              │
        └────────────────────────────────────┘
                    │
                    │ Has Many
                    ▼
            ┌───────────────┐
            │  ATTENDANCES  │
            │               │
            │ - session_id  │
            │ - student_id  │
            │   (FK → users)│
            │ - status:     │
            │   present/    │
            │   absent/late │
            │ - check_in    │
            │ - evaluation  │
            └───────────────┘
```

---

## ATTENDANCE & EVALUATION - ĐIỂM DANH & ĐÁNH GIÁ

```
┌─────────────────────────────────────────────────────┐
│          ATTENDANCE FEE SYSTEM                      │
│  ┌───────────────────────────────────────────────┐  │
│  │     ATTENDANCE_FEE_POLICIES                   │  │
│  │ - id (PK)                                     │  │
│  │ - class_id (FK)                               │  │
│  │ - branch_id (FK)                              │  │
│  │ - base_fee_per_session                        │  │
│  │ - late_minutes_threshold                      │  │
│  │ - late_penalty_percent                        │  │
│  │ - absent_refund_percent                       │  │
│  └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
                    │
                    │ Applied to
                    ▼
        ┌───────────────────────────┐
        │      ATTENDANCES          │
        │ - id (PK)                 │
        │ - session_id              │
        │ - student_id              │
        │ - status                  │
        │ - check_in_time          │
        │ - evaluation_data (JSON)  │
        │ - evaluation_pdf_url     │
        └───────────────────────────┘
                    │
                    │ Has Many
                    ▼
        ┌─────────────────────────────────┐
        │ ATTENDANCE_FEE_DEDUCTIONS       │
        │ - attendance_id                 │
        │ - deduction_type:               │
        │   * absent_refund               │
        │   * late_penalty                │
        │ - amount                        │
        └─────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│            VALUATION SYSTEM                         │
│  ┌───────────────────────────────────────────────┐  │
│  │     VALUATION_FORMS                           │  │
│  │ - id (PK)                                     │  │
│  │ - name, description                           │  │
│  │ - branch_id                                   │  │
│  └───────────────────────────────────────────────┘  │
│                    │                                │
│                    │ Has Many                       │
│                    ▼                                │
│  ┌───────────────────────────────────────────────┐  │
│  │     VALUATION_FORM_FIELDS                     │  │
│  │ - valuation_form_id                           │  │
│  │ - field_type: text, number, select,           │  │
│  │               checkbox, rating                 │  │
│  │ - field_title, field_description              │  │
│  │ - options (JSON)                              │  │
│  │ - sort_order                                  │  │
│  └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

---

## HOMEWORK SYSTEM - HỆ THỐNG BÀI TẬP

```
┌──────────────────────────────────────────────────┐
│         HOMEWORK_ASSIGNMENTS TABLE               │
│  ┌────────────────────────────────────────────┐  │
│  │ - id (PK)                                  │  │
│  │ - class_id (FK → classes.id)              │  │
│  │ - title, description                       │  │
│  │ - due_date                                 │  │
│  │ - max_score                                │  │
│  │ - google_drive_folder_id                  │  │
│  └────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────┘
                    │
                    │ Has Many
                    ▼
        ┌─────────────────────────────┐
        │ HOMEWORK_SUBMISSIONS        │
        │ - homework_assignment_id    │
        │ - student_id (FK → users)   │
        │ - submission_date           │
        │ - content                   │
        │ - score, feedback           │
        │ - google_drive_folder_id    │
        │ - unit_folder_link          │
        └─────────────────────────────┘
```

---

## COURSE POSTS - DIỄN ĐÀN LỚP HỌC

```
┌─────────────────────────────────────────────────────┐
│              COURSE_POSTS TABLE                     │
│  ┌───────────────────────────────────────────────┐  │
│  │ - id (PK)                                     │  │
│  │ - class_id (FK → classes.id)                 │  │
│  │ - user_id (FK → users.id) [Author]           │  │
│  │ - post_type:                                  │  │
│  │   * announcement                              │  │
│  │   * material                                  │  │
│  │   * homework                                  │  │
│  │   * event                                     │  │
│  │   * discussion                                │  │
│  │ - title, content                              │  │
│  │ - event_date (if type=event)                 │  │
│  │ - is_pinned                                   │  │
│  └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
         │              │              │
         ▼              ▼              ▼
┌─────────────┐  ┌──────────────┐  ┌─────────────┐
│ COURSE_POST │  │ COURSE_POST  │  │ COURSE_POST │
│ _COMMENTS   │  │ _LIKES       │  │ _MEDIA      │
│             │  │              │  │             │
│ - post_id   │  │ - post_id    │  │ - post_id   │
│ - user_id   │  │ - user_id    │  │ - file_name │
│ - content   │  │ - reaction:  │  │ - file_path │
│             │  │   like, love,│  │ - file_type │
│             │  │   care, haha,│  │             │
│             │  │   wow, sad   │  │             │
└─────────────┘  └──────────────┘  └─────────────┘
```

---

## ACCOUNTING & FINANCE - KẾ TOÁN & TÀI CHÍNH

```
┌───────────────────────────────────────────────────────┐
│         ACCOUNT STRUCTURE (Chart of Accounts)         │
│                                                       │
│  ┌──────────────────────┐      ┌──────────────────┐  │
│  │ ACCOUNT_CATEGORIES   │      │  ACCOUNT_ITEMS   │  │
│  │ - id (PK)           │      │  - id (PK)       │  │
│  │ - code              │◄─────┤  - category_id   │  │
│  │ - name              │      │  - code          │  │
│  │ - type:             │      │  - name          │  │
│  │   * income          │      │  - description   │  │
│  │   * expense         │      └──────────────────┘  │
│  │   * asset           │                            │
│  │   * liability       │                            │
│  │ - cost_type:        │                            │
│  │   * fixed           │                            │
│  │   * variable        │                            │
│  └──────────────────────┘                            │
└───────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│            CASH ACCOUNTS (Tài khoản tiền mặt)            │
│  ┌────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                          │  │
│  │ - branch_id (FK)                                   │  │
│  │ - name, account_number, bank_name                 │  │
│  │ - current_balance                                  │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│              FINANCIAL PLANS (Kế hoạch TC)               │
│  ┌────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                          │  │
│  │ - branch_id (FK)                                   │  │
│  │ - period: monthly, quarterly, yearly               │  │
│  │ - start_date, end_date                            │  │
│  │ - status: draft, pending, approved, rejected      │  │
│  └────────────────────────────────────────────────────┘  │
│                    │                                     │
│                    │ Has Many                            │
│                    ▼                                     │
│  ┌────────────────────────────────────────────────────┐  │
│  │     FINANCIAL_PLAN_ITEMS                           │  │
│  │ - financial_plan_id                                │  │
│  │ - account_item_id                                  │  │
│  │ - planned_amount                                   │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│           EXPENSE_PROPOSALS (Đề xuất chi tiêu)           │
│  ┌────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                          │  │
│  │ - account_item_id (FK)                             │  │
│  │ - cash_account_id (FK)                             │  │
│  │ - amount, purpose                                   │  │
│  │ - requested_by (FK → users)                        │  │
│  │ - approved_by (FK → users)                         │  │
│  │ - status: pending, approved, rejected, disbursed   │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│             INCOME_REPORTS (Báo cáo thu nhập)            │
│  ┌────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                          │  │
│  │ - branch_id (FK)                                   │  │
│  │ - account_item_id (FK)                             │  │
│  │ - amount, source, payment_method                   │  │
│  │ - verified_by, approved_by (FK → users)            │  │
│  │ - status: pending, verified, approved, rejected    │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│          FINANCIAL_TRANSACTIONS (Giao dịch TC)           │
│  ┌────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                          │  │
│  │ - transaction_type: income, expense                │  │
│  │ - amount, description                              │  │
│  │ - status: pending, completed, cancelled            │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
```

---

## WALLET & ENROLLMENT - VÍ & ĐĂNG KÝ

```
┌──────────────────────────────────────────────────────────┐
│                WALLETS (Polymorphic)                     │
│  ┌────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                          │  │
│  │ - owner_type (Customer, Student, Parent)           │  │
│  │ - owner_id                                         │  │
│  │ - balance, currency                                │  │
│  │ - is_active                                        │  │
│  └────────────────────────────────────────────────────┘  │
│                    │                                     │
│                    │ Has Many                            │
│                    ▼                                     │
│  ┌────────────────────────────────────────────────────┐  │
│  │     WALLET_TRANSACTIONS                            │  │
│  │ - wallet_id                                        │  │
│  │ - transaction_type:                                │  │
│  │   * deposit, withdrawal, purchase, refund          │  │
│  │ - amount, description                              │  │
│  │ - status: pending, completed, failed, cancelled    │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│                   PRODUCTS TABLE                         │
│  ┌────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                          │  │
│  │ - branch_id (FK)                                   │  │
│  │ - name, description                                │  │
│  │ - product_type: course, service, package           │  │
│  │ - price, duration_months                           │  │
│  │ - max_students, is_active                          │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│        VOUCHERS & CAMPAIGNS (Marketing)                  │
│  ┌────────────────────────────────────────────────────┐  │
│  │ VOUCHERS                                           │  │
│  │ - id, code (unique)                                │  │
│  │ - discount_type: percent, fixed                    │  │
│  │ - discount_value                                   │  │
│  │ - usage_limit, times_used                          │  │
│  │ - start_date, end_date                            │  │
│  └────────────────────────────────────────────────────┘  │
│                                                          │
│  ┌────────────────────────────────────────────────────┐  │
│  │ CAMPAIGNS                                          │  │
│  │ - id, name, description                            │  │
│  │ - start_date, end_date                            │  │
│  │ - discount_percent                                 │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│                    ENROLLMENTS TABLE                         │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ - id (PK)                                              │  │
│  │ - code (auto: ENR20251124001)                          │  │
│  │                                                        │  │
│  │ WHO:                                                   │  │
│  │ - customer_id (FK) ← Người đăng ký                   │  │
│  │ - student_type, student_id (polymorphic) ← Người học  │  │
│  │                                                        │  │
│  │ WHAT:                                                  │  │
│  │ - product_id (FK)                                     │  │
│  │ - class_id (FK) [Optional]                            │  │
│  │                                                        │  │
│  │ PRICING:                                               │  │
│  │ - original_price, discount_amount, final_price        │  │
│  │ - paid_amount, remaining_amount                       │  │
│  │ - voucher_id, campaign_id, voucher_code               │  │
│  │                                                        │  │
│  │ SESSIONS:                                              │  │
│  │ - total_sessions, attended_sessions                    │  │
│  │ - remaining_sessions, price_per_session               │  │
│  │                                                        │  │
│  │ STATUS:                                                │  │
│  │ - status: pending → approved → paid →                 │  │
│  │           active → completed / cancelled               │  │
│  │ - start_date, end_date, completed_at                  │  │
│  │                                                        │  │
│  │ - branch_id, assigned_to, notes                       │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

---

## ZALO INTEGRATION - TÍCH HỢP ZALO CHAT

```
┌────────────────────────────────────────────────────────┐
│               ZALO_ACCOUNTS TABLE                      │
│  ┌──────────────────────────────────────────────────┐  │
│  │ - id (PK)                                        │  │
│  │ - zalo_id (unique)                               │  │
│  │ - name, phone                                    │  │
│  │ - branch_id (FK)                                 │  │
│  │ - assigned_to (FK → users.id)                   │  │
│  │ - cookie (encrypted) ← Session data              │  │
│  │ - imei, user_agent                               │  │
│  │ - avatar_url, avatar_path                        │  │
│  │ - is_active, is_connected, is_primary            │  │
│  │ - last_sync_at, last_login_at                   │  │
│  └──────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────┘
         │              │              │
         ▼              ▼              ▼
┌─────────────┐  ┌──────────────┐  ┌──────────────┐
│ ZALO_       │  │ ZALO_GROUPS  │  │ ZALO_        │
│ FRIENDS     │  │              │  │ CONVERSATIONS│
│             │  │ - zalo_acc_id│  │              │
│ - zalo_acc  │  │ - group_id   │  │ - zalo_acc_id│
│   _id       │  │ - name       │  │ - conv_type: │
│ - friend_   │  │ - avatar_url │  │   friend/    │
│   zalo_id   │  │ - total_     │  │   group      │
│ - display   │  │   members    │  │ - conv_id    │
│   _name     │  │ - type       │  │ - last_msg_at│
│ - avatar_url│  └──────────────┘  └──────────────┘
└─────────────┘         │                   │
                        │                   │
                        ▼                   │
                ┌────────────────┐          │
                │ ZALO_GROUP_    │          │
                │ MEMBERS        │          │
                │ - group_id     │          │
                │ - member_id    │          │
                │ - display_name │          │
                └────────────────┘          │
                                            │
                        ┌───────────────────┘
                        │
                        ▼
                ┌────────────────────────────────┐
                │     ZALO_MESSAGES              │
                │ - id (PK)                      │
                │ - zalo_account_id              │
                │ - conversation_id              │
                │ - message_id, global_id        │
                │ - content                      │
                │ - content_type:                │
                │   text, image, sticker,        │
                │   file, video, link            │
                │ - sender_id, sender_name       │
                │ - quote (reply to message)     │
                │ - sent_at                      │
                └────────────────────────────────┘
                        │
                        │ Has Many
                        ▼
                ┌────────────────────┐
                │ ZALO_MESSAGE_      │
                │ REACTIONS          │
                │ - message_id       │
                │ - user_id          │
                │ - reaction_type    │
                └────────────────────┘
```

---

## GOOGLE DRIVE INTEGRATION

```
┌────────────────────────────────────────────────────────┐
│           GOOGLE_DRIVE_SETTINGS TABLE                  │
│  ┌──────────────────────────────────────────────────┐  │
│  │ - id (PK)                                        │  │
│  │ - branch_id (FK)                                 │  │
│  │ - root_folder_id, root_folder_name               │  │
│  │ - teachers_folder_id                             │  │
│  │ - syllabus_folder_id                             │  │
│  │ - lesson_plan_folder_id                          │  │
│  └──────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────┐
│            GOOGLE_DRIVE_ITEMS TABLE                    │
│  ┌──────────────────────────────────────────────────┐  │
│  │ - id (PK)                                        │  │
│  │ - drive_id (Google Drive ID)                     │  │
│  │ - name, type (file/folder)                       │  │
│  │ - parent_id                                      │  │
│  │ - mime_type, size                                │  │
│  │ - web_view_link                                  │  │
│  └──────────────────────────────────────────────────┘  │
│                    │                                   │
│                    │ Has Many                          │
│                    ▼                                   │
│  ┌──────────────────────────────────────────────────┐  │
│  │   GOOGLE_DRIVE_PERMISSIONS                       │  │
│  │ - user_id (FK → users.id)                        │  │
│  │ - google_drive_item_id                           │  │
│  │ - permission_type: reader, writer, owner         │  │
│  │ - is_verified                                    │  │
│  └──────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────┘
```

---

## MULTI-LANGUAGE SYSTEM - ĐA NGÔN NGỮ

```
┌────────────────────────────────────────┐
│        LANGUAGES TABLE                 │
│  ┌──────────────────────────────────┐  │
│  │ - id (PK)                        │  │
│  │ - code (vi, en)                  │  │
│  │ - name                           │  │
│  │ - is_active, is_default          │  │
│  └──────────────────────────────────┘  │
└────────────────────────────────────────┘
                    │
                    │ Has Many
                    ▼
        ┌────────────────────────┐
        │  TRANSLATIONS TABLE    │
        │ - id (PK)              │
        │ - language_id          │
        │ - key:                 │
        │   module.section.item  │
        │   Ex: users.form.name  │
        │ - value: Translated    │
        │   text                 │
        └────────────────────────┘
```

---

## KEY RELATIONSHIPS SUMMARY

### 🔑 1-to-1 Relationships:
- User ←→ Student
- User ←→ Parent
- Class ←→ Google Drive Folder
- Class ←→ Zalo Group

### 🔗 1-to-Many Relationships:
- Branch → Users, Classes, Students, Departments
- Class → Class Students, Schedules, Lesson Sessions
- Lesson Plan → Lesson Plan Sessions
- User (Manager) → Subordinates

### ⚡ Many-to-Many Relationships:
- User ←→ Branches (branch_user)
- User ←→ Roles (role_user)
- User ←→ Departments (department_user)
- User ←→ Subjects (subject_teacher)
- Class ←→ Subjects (class_subject)
- Student ←→ Parents (parent_student)

### 🎭 Polymorphic Relationships:
- Wallet: owner → Customer, Student, Parent
- Enrollment: student → Customer, Student, TrialStudent
- CalendarEvent: eventable → Customer, Class

---

## DATA FLOW EXAMPLES

### Example 1: Customer Journey

```
1. LEAD GENERATION
   Customer created → stage = 'lead'
   
2. INTERACTION
   CustomerInteraction records created
   
3. TRIAL CLASS
   TrialStudent record → links Customer to Class
   
4. ENROLLMENT
   Enrollment created:
   - customer_id → Customer
   - student_type → Customer/Student
   - product_id → Product
   
5. PAYMENT
   - Wallet transaction
   - IncomeReport approved
   - Enrollment status: pending → approved → paid
   
6. ACTIVE LEARNING
   - ClassStudent record created
   - Attendances tracked
   - Homework assigned/submitted
   
7. COMPLETION
   - Enrollment completed
   - Certificate issued
```

### Example 2: Class Lifecycle

```
1. CLASS PLANNING
   Class created → status = 'planning'
   - Assign branch, homeroom teacher
   - Set academic year, level, capacity
   
2. CURRICULUM DESIGN
   - Assign lesson_plan_id
   - Link subjects via class_subject
   - Assign teachers per subject
   
3. SCHEDULE CREATION
   - ClassSchedule: day_of_week, study_period, room
   - Generate ClassLessonSessions
   
4. ACTIVE CLASS
   Class status → 'active'
   - Students enrolled via ClassStudent
   - Attendance tracking per session
   - Homework assignments
   - Course posts & discussions
   
5. COMPLETION
   Class status → 'completed'
   - Final evaluations
   - Certificates issued
```

---

**Tác giả:** AI Assistant  
**Ngày:** 24/11/2025  
**Phiên bản:** 1.0

