# PHÂN TÍCH KIẾN TRÚC HỆ THỐNG - SUBJECTS & TEACHERS

## 🔍 KIẾN TRÚC HIỆN TẠI

### Schema hiện tại:

```sql
subjects:
├─ id (PK)
├─ branch_id (FK) ← MÔN HỌC THUỘC CHI NHÁNH
├─ name, code, description
└─ UNIQUE(branch_id, code)

subject_teacher:
├─ id (PK)
├─ subject_id (FK)
├─ user_id (FK)
├─ is_head (boolean)
└─ UNIQUE(subject_id, user_id)
```

### Cách hoạt động:
1. Mỗi **chi nhánh tạo môn học riêng**
2. Giáo viên gán vào **từng môn học** (không có branch_id)
3. 1 giáo viên chỉ gán 1 lần vào 1 môn học

### Vấn đề thực tế:
```
Branch Yên Tâm:
  └─ Subject "Tiếng Anh" (ID: 2)
      ├─ Teacher: Mr. Mike
      ├─ Teacher: Ms. Linh
      └─ Teacher: Mrs. Phượng

Branch Thống Nhất:
  └─ Subject "Tiếng Anh" (ID: 4)  ← DUPLICATE!
      ├─ Teacher: Mr. Mike         ← PHẢI GÁN LẠI!
      ├─ Teacher: Ms. Linh         ← PHẢI GÁN LẠI!
      └─ Teacher: Mrs. Phượng      ← PHẢI GÁN LẠI!
```

---

## ❌ VẤN ĐỀ VỚI THIẾT KẾ HIỆN TẠI

### 1. **Data Duplication**
- 10 chi nhánh = 10 bản "Tiếng Anh" giống nhau
- Waste storage, khó maintain

### 2. **Assignment Nightmare**
- Giáo viên dạy 3 chi nhánh = phải gán 3 lần vào 3 môn học khác nhau
- Constraint `UNIQUE(subject_id, user_id)` ngăn không cho gán 1 teacher vào nhiều subjects cùng tên

### 3. **UI/UX Confusion**
- User tạo subject "Tiếng Anh" ở branch A
- Chuyển sang branch B → Không thấy subject
- Phải tạo lại "Tiếng Anh" ở branch B
- Confusion: "Tại sao phải tạo lại môn học đã có?"

### 4. **Reporting Issues**
- Query: "Có bao nhiêu giáo viên dạy Toán?"
- Phải JOIN qua nhiều subjects có tên "Toán"
- Phức tạp và dễ sai

### 5. **Scaling Problem**
```
Scenario: Thêm 20 chi nhánh mới
→ Phải tạo ~10 môn học × 20 chi nhánh = 200 subjects
→ Phải gán ~50 giáo viên × 200 subjects = 10,000 assignments!
```

---

## ✅ HAI HƯỚNG GIẢI QUYẾT

### **OPTION A: GLOBAL SUBJECTS (Recommended)**

#### Migration thay đổi:

```php
// 1. Sửa subjects table - XÓA branch_id
Schema::table('subjects', function (Blueprint $table) {
    $table->dropForeign(['branch_id']);
    $table->dropUnique(['branch_id', 'code']);
    $table->dropColumn('branch_id');

    // Code phải unique toàn hệ thống
    $table->unique('code');
});

// 2. Thêm branch_id vào subject_teacher
Schema::table('subject_teacher', function (Blueprint $table) {
    $table->foreignId('branch_id')
          ->after('user_id')
          ->constrained('branches')
          ->onDelete('cascade');

    // Unique: 1 teacher chỉ gán 1 lần vào 1 subject tại 1 branch
    $table->dropUnique(['subject_id', 'user_id']);
    $table->unique(['subject_id', 'user_id', 'branch_id']);
});
```

#### Schema mới:

```sql
subjects: (GLOBAL - MASTER DATA)
├─ id (PK)
├─ name (Tiếng Anh, Toán, Văn)
├─ code (UNIQUE globally: ENG, MATH, LIT)
└─ description

subject_teacher: (ASSIGNMENT BY BRANCH)
├─ id (PK)
├─ subject_id (FK) ← Subject nào
├─ user_id (FK)    ← Giáo viên nào
├─ branch_id (FK)  ← Ở chi nhánh nào
├─ is_head
└─ UNIQUE(subject_id, user_id, branch_id)
```

#### Ví dụ sử dụng:

```sql
-- Tạo 1 lần, dùng mọi nơi
INSERT INTO subjects (name, code) VALUES
  ('Tiếng Anh', 'ENG'),
  ('Toán', 'MATH');

-- Gán giáo viên vào subject theo branch
INSERT INTO subject_teacher (subject_id, user_id, branch_id, is_head) VALUES
  (1, 191, 1, true),   -- Mr. Mike dạy ENG tại Yên Tâm (head)
  (1, 191, 2, false),  -- Mr. Mike dạy ENG tại Thống Nhất
  (1, 193, 2, true),   -- Ms. Linh dạy ENG tại Thống Nhất (head)
  (2, 191, 1, false);  -- Mr. Mike dạy MATH tại Yên Tâm
```

#### Ưu điểm:

✅ **Single Source of Truth**: 1 subject = 1 record
✅ **Flexible Assignment**: 1 teacher dạy nhiều branches dễ dàng
✅ **Easy Reporting**: COUNT teachers by subject trực tiếp
✅ **Scalable**: Thêm branch mới → chỉ cần assign teachers
✅ **No Duplication**: Tiết kiệm storage
✅ **Better UX**: User thấy list subjects global, chọn assign

#### Nhược điểm:

⚠️ **Migration phức tạp**: Phải merge duplicate subjects
⚠️ **Code changes**: Phải update controllers, models
⚠️ **Breaking change**: Có thể ảnh hưởng API/UI hiện tại

---

### **OPTION B: KEEP CURRENT + IMPROVE**

Giữ nguyên schema, cải thiện workflow:

#### Giải pháp 1: Auto-sync subjects across branches

```php
// Khi tạo subject ở branch A
SubjectObserver::created(function ($subject) {
    // Tự động tạo subject tương tự ở tất cả branches
    $otherBranches = Branch::where('id', '!=', $subject->branch_id)->get();

    foreach ($otherBranches as $branch) {
        Subject::firstOrCreate([
            'branch_id' => $branch->id,
            'code' => $subject->code,
        ], [
            'name' => $subject->name,
            'description' => $subject->description,
            'color' => $subject->color,
        ]);
    }
});
```

**Pros:**
✅ Không cần migration
✅ Tự động sync

**Cons:**
❌ Vẫn duplicate data
❌ Vẫn phải gán teachers nhiều lần

#### Giải pháp 2: UI Helper - Bulk Assignment

```php
// API endpoint: Assign teacher to subject across all branches
POST /api/teachers/{teacherId}/assign-subject
{
  "subject_code": "ENG",
  "branch_ids": [1, 2, 3, 4],  // null = all branches
  "is_head": false
}

// Backend tự động gán vào tất cả branches
```

**Pros:**
✅ UX tốt hơn
✅ Giảm manual work

**Cons:**
❌ Vẫn duplicate data
❌ Complexity ở business logic

---

## 🎯 ĐỀ XUẤT CUỐI CÙNG

### **Recommendation: OPTION A - Global Subjects**

**Lý do:**

1. **Long-term maintainability**: Dễ maintain, scale
2. **Data integrity**: Single source of truth
3. **Performance**: Less data, faster queries
4. **Industry standard**: Đa số LMS/School systems dùng cách này

### **Migration Strategy:**

```sql
-- STEP 1: Deduplicate subjects
-- Merge all "Tiếng Anh" into 1 subject
-- Merge all "Toán" into 1 subject
-- etc.

-- STEP 2: Migrate subject_teacher
-- Add branch_id to existing records
UPDATE subject_teacher st
JOIN subjects s ON st.subject_id = s.id
SET st.branch_id = s.branch_id;

-- STEP 3: Update schema
-- Remove branch_id from subjects
-- Add branch_id to subject_teacher

-- STEP 4: Update code
-- Models, Controllers, APIs, UI
```

### **Rollout Plan:**

1. **Week 1**: Create migration scripts + test trên staging
2. **Week 2**: Update models, controllers
3. **Week 3**: Update UI components
4. **Week 4**: Deploy production (với downtime window)

---

## 📊 SO SÁNH

| Feature | Current (Branch-Based) | Option A (Global) | Option B (Improved) |
|---------|----------------------|------------------|-------------------|
| Data duplication | ❌ Cao | ✅ Không | ❌ Cao |
| Assignment effort | ❌ Nhiều lần | ✅ 1 lần | ⚠️ Bulk assign |
| Scalability | ❌ Kém | ✅ Tốt | ⚠️ Trung bình |
| Migration cost | ✅ 0 (current) | ❌ Cao | ✅ Thấp |
| Maintenance | ❌ Khó | ✅ Dễ | ⚠️ Trung bình |
| Reporting | ❌ Phức tạp | ✅ Đơn giản | ❌ Phức tạp |

---

## 🤔 QUYẾT ĐỊNH

**Nếu hệ thống còn nhỏ (< 5 branches):**
→ Có thể giữ **Option B** + bulk assignment UI

**Nếu hệ thống sẽ scale (> 5 branches):**
→ Nên migrate sang **Option A** sớm càng tốt

**Nếu đã có nhiều data:**
→ Migration phức tạp, cần test kỹ

---

## 📝 ACTION ITEMS

### Ngắn hạn (Current workaround):
- [x] Script auto-assign teachers to Thống Nhất subjects
- [ ] UI helper: "Copy teachers from another branch"
- [ ] Documentation: Giải thích workflow cho admins

### Dài hạn (Architecture improvement):
- [ ] Design migration plan
- [ ] Estimate effort & downtime
- [ ] Get stakeholder approval
- [ ] Implement & test migration
- [ ] Update codebase
- [ ] Deploy

---

**Kết luận:** Thiết kế hiện tại phù hợp với **single-branch systems** nhưng **không scale tốt** cho multi-branch. Nên cân nhắc migrate sang Global Subjects nếu có kế hoạch mở rộng.
