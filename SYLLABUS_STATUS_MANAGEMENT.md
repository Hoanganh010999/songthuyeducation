# Syllabus Status Management

## 📋 Overview

Added status dropdown to Syllabus edit form, allowing users to change syllabus status from `draft` to other states (`approved`, `in_use`, `archived`).

---

## 🎯 User Request

> "trong edit của Syllabus List chưa có chỗ để chuyển trạng thái syllabus từ draft sang các trạng thái khác"

---

## 📊 Status Values

Based on migration `2025_11_04_150020_create_lesson_plans_table.php`:

```php
enum('status', ['draft', 'approved', 'in_use', 'archived'])->default('draft');
```

### Status Definitions

| Status | Vietnamese | Description (VI) | Description (EN) |
|--------|-----------|------------------|------------------|
| `draft` | Bản nháp | Giáo án đang được soạn thảo, chưa sẵn sàng sử dụng | Syllabus is being drafted, not ready for use |
| `approved` | Đã phê duyệt | Giáo án đã được phê duyệt và sẵn sàng sử dụng | Syllabus has been approved and ready for use |
| `in_use` | Đang sử dụng | Giáo án đang được sử dụng trong các lớp học | Syllabus is currently being used in classes |
| `archived` | Đã lưu trữ | Giáo án đã được lưu trữ, không còn sử dụng | Syllabus has been archived, no longer in use |

---

## 🎨 UI Implementation

### Changes to `SyllabusForm.vue`

#### 1. Added Status Dropdown (Only in Edit Mode)

```vue
<!-- Status -->
<div v-if="props.syllabus">
  <label class="block text-sm font-medium text-gray-700 mb-1">
    {{ t('syllabus.status') }} *
  </label>
  <select v-model="form.status" required class="w-full px-3 py-2 border rounded-lg">
    <option value="draft">{{ t('syllabus.status_draft') }}</option>
    <option value="approved">{{ t('syllabus.status_approved') }}</option>
    <option value="in_use">{{ t('syllabus.status_in_use') }}</option>
    <option value="archived">{{ t('syllabus.status_archived') }}</option>
  </select>
  <p class="text-sm text-gray-500 mt-1">{{ statusDescription(form.status) }}</p>
</div>
```

**Features**:
- ✅ Only shows when editing (not when creating new)
- ✅ Shows current status value
- ✅ All 4 status options available
- ✅ Dynamic description based on selected status

#### 2. Added `statusDescription` Method

```javascript
const statusDescription = (status) => {
  const descriptions = {
    draft: t('syllabus.status_draft_desc'),
    approved: t('syllabus.status_approved_desc'),
    in_use: t('syllabus.status_in_use_desc'),
    archived: t('syllabus.status_archived_desc')
  };
  return descriptions[status] || '';
};
```

#### 3. Added Default Status to Form

```javascript
const form = ref({
  name: '',
  code: '',
  subject_id: '',
  total_sessions: 30,
  description: '',
  status: 'draft',  // ← Added default
  branch_id: localStorage.getItem('current_branch_id')
});
```

---

## 🌐 Translation Keys

### New Keys Added to `SyllabusGoogleDriveTranslationsSeeder`

| Key | Vietnamese | English |
|-----|-----------|---------|
| `syllabus.status_draft_desc` | Giáo án đang được soạn thảo, chưa sẵn sàng sử dụng | Syllabus is being drafted, not ready for use |
| `syllabus.status_approved_desc` | Giáo án đã được phê duyệt và sẵn sàng sử dụng | Syllabus has been approved and ready for use |
| `syllabus.status_in_use_desc` | Giáo án đang được sử dụng trong các lớp học | Syllabus is currently being used in classes |
| `syllabus.status_archived_desc` | Giáo án đã được lưu trữ, không còn sử dụng | Syllabus has been archived, no longer in use |

**Total new keys**: 4

---

## 🔄 Workflow

### Edit Syllabus Status

```
User clicks "Edit" on a syllabus in SyllabusList
  → Modal opens with SyllabusForm
    → Form loads with current syllabus data (including status)
    → User sees current status in dropdown
    → User can select new status from:
      - Draft (Bản nháp)
      - Approved (Đã phê duyệt)
      - In Use (Đang sử dụng)
      - Archived (Đã lưu trữ)
    → Description updates dynamically based on selection
    → User clicks "Save"
      → PUT /api/lesson-plans/{id} with new status
      → Backend updates lesson_plans.status
      → Success message displayed
      → Modal closes, list refreshes
      → Status badge updated in list view
```

---

## 🎯 Status Badge Display (Existing)

In `SyllabusList.vue`, status is already displayed with color-coded badges:

```javascript
const statusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',       // Gray
    approved: 'bg-blue-100 text-blue-800',    // Blue
    active: 'bg-green-100 text-green-800',    // Green
    in_use: 'bg-green-100 text-green-800',    // Green
    archived: 'bg-gray-100 text-gray-800'     // Gray
  };
  return classes[status] || classes.draft;
};
```

**Visual Representation**:
- 🟤 **Draft**: Gray badge
- 🔵 **Approved**: Blue badge
- 🟢 **In Use**: Green badge
- ⚫ **Archived**: Gray badge

---

## 📋 Backend Compatibility

### LessonPlan Model

**Fillable**: `status` is already in `$fillable` array

```php
protected $fillable = [
    'branch_id', 'subject_id', 'created_by', 'name', 'code',
    'description', 'google_drive_folder_id', 'google_drive_folder_name',
    'total_sessions', 'level', 'academic_year',
    'status', 'is_active'  // ← Already fillable
];
```

**Scope**: Model already has `scopeApproved()` for filtering

```php
public function scopeApproved($query)
{
    return $query->where('status', 'approved');
}
```

---

## 🧪 Testing Checklist

### Frontend
- [x] Status dropdown appears in edit mode
- [x] Status dropdown hidden in create mode
- [x] All 4 status options available
- [x] Current status is pre-selected
- [x] Description updates when status changes
- [x] Form submits with selected status

### Backend
- [x] PUT endpoint accepts status field
- [x] Status is validated (enum values)
- [x] Status persists to database
- [x] Updated status reflects in list view

### UI/UX
- [x] Status badge color matches status type
- [x] Translations display correctly (VI/EN)
- [x] Description provides helpful context
- [x] Form is responsive

---

## 📝 Files Modified

### Frontend (2 files)
1. **`resources/js/pages/quality/SyllabusForm.vue`**
   - Added status dropdown (edit mode only)
   - Added `statusDescription()` method
   - Added default status to form

2. **`database/seeders/SyllabusGoogleDriveTranslationsSeeder.php`**
   - Added 4 new status description keys

### No Backend Changes Required
- ✅ Model already supports status field
- ✅ Migration already defines enum
- ✅ Controller already handles updates

---

## 🚀 Deployment

```bash
# Seed new translations
php artisan db:seed --class=SyllabusGoogleDriveTranslationsSeeder

# Build frontend
npm run build
```

**Status**: ✅ Complete and deployed

---

## 📸 Visual Example

### Before (No Status Control)
```
[ Name Input     ]  [ Code Input  ]
[ Subject Select ]  [ Units Input ]
[ Description Text Area           ]

[Cancel]  [Save]
```

### After (With Status Control)
```
[ Name Input     ]  [ Code Input  ]
[ Subject Select ]  [ Units Input ]
[ Description Text Area           ]
[ Status Dropdown ] ← NEW
  └─ Description: "Giáo án đã được phê duyệt..."

[Cancel]  [Save]
```

---

## 💡 Future Enhancements

1. **Status Transition Rules**
   - Validate allowed transitions (e.g., can't go from `archived` to `in_use` directly)
   - Add confirmation dialog for critical transitions

2. **Status History**
   - Track status changes in a separate table
   - Show who changed status and when

3. **Permissions**
   - Restrict status changes based on user role
   - E.g., only admin can approve or archive

4. **Workflow Integration**
   - Auto-change status when syllabus is used in a class (`draft` → `in_use`)
   - Auto-archive when all classes finish

5. **Bulk Actions**
   - Select multiple syllabi and change status at once

---

## 🎉 Summary

### ✅ Completed
1. ✅ Added status dropdown to Syllabus edit form
2. ✅ Shows all 4 status options with translations
3. ✅ Dynamic description based on selected status
4. ✅ Only visible in edit mode (not create)
5. ✅ 4 new translation keys added
6. ✅ Seeded and built successfully

### 📦 Impact
- **Files Modified**: 2
- **New Translation Keys**: 4
- **Backend Changes**: None (already compatible)
- **User Benefit**: Full control over syllabus lifecycle

---

**Document Version**: 1.0  
**Last Updated**: November 10, 2025  
**Status**: ✅ Complete  
**Author**: AI Assistant

