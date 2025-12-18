# Changelog: Chức Năng Thay Đổi Trạng Thái Giáo Án

## 📋 Tổng Quan

Thêm chức năng thay đổi trạng thái giáo án bằng cách **click vào badge trạng thái** trong danh sách giáo án.

## ✨ Tính Năng Mới

### 1. Click Badge để Đổi Trạng Thái
- Badge hiện nay có thể click (nếu có quyền)
- Hiển thị dropdown với 4 trạng thái: Draft, Approved, In Use, Archived
- Thay đổi trạng thái ngay lập tức không cần reload trang
- Hiển thị thông báo thành công/lỗi

### 2. Phân Quyền Linh Hoạt
- Hỗ trợ nhiều permission: `syllabus.change_status`, `lesson_plans.change_status`, hoặc `lesson_plans.edit`
- Fallback tự động nếu không có permission riêng
- Người có quyền edit tự động có thể đổi trạng thái

### 3. UI/UX Cải Thiện
- **Có quyền**: Badge có mũi tên dropdown ▼, hover có hiệu ứng
- **Không có quyền**: Badge tĩnh, không thể click
- Dropdown đóng tự động khi click ra ngoài
- Animation mượt mà

## 🔧 Các Thay Đổi

### Backend

#### 1. `app/Http/Controllers/Api/LessonPlanController.php`

**Thêm method `updateStatus()`** (line 310-365):
```php
public function updateStatus(Request $request, $id)
{
    // Check permission với fallback
    $canChangeStatus = $user->hasPermission('syllabus.change_status') ||
                      $user->hasPermission('lesson_plans.change_status') ||
                      $this->checkPermission($user, 'edit');

    // Validate status
    $validator = Validator::make($request->all(), [
        'status' => 'required|in:draft,approved,in_use,archived',
    ]);

    // Update status
    $lessonPlan->status = $newStatus;
    $lessonPlan->save();

    // Return success message
    return response()->json([
        'success' => true,
        'message' => "Đã thay đổi trạng thái từ '{$oldStatus}' thành '{$newStatus}'",
        'data' => [...]
    ]);
}
```

#### 2. `routes/api.php`

**Thêm route mới** (line 1008):
```php
Route::patch('/{id}/status', [LessonPlanController::class, 'updateStatus']);
```

**Endpoint**: `PATCH /api/lesson-plans/{id}/status`

**Request Body**:
```json
{
  "status": "approved"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Đã thay đổi trạng thái từ 'draft' thành 'approved'",
  "data": {
    "id": 1,
    "status": "approved",
    "old_status": "draft"
  }
}
```

### Frontend

#### 3. `resources/js/pages/quality/SyllabusList.vue`

**Template Changes** (line 49-84):
- Badge cũ: Static span
- Badge mới: Button với dropdown

```vue
<button
  v-if="canChangeStatus"
  @click="toggleStatusDropdown(syllabus.id)"
  :class="statusClass(syllabus.status)"
  class="px-3 py-1 text-xs rounded-full cursor-pointer hover:opacity-80 transition flex items-center space-x-1"
>
  <span>{{ statusText(syllabus.status) }}</span>
  <svg class="w-3 h-3"><!-- Dropdown icon --></svg>
</button>

<!-- Dropdown Menu -->
<div v-if="statusDropdownOpen === syllabus.id" class="absolute z-50 mt-1 bg-white rounded-lg shadow-lg...">
  <button
    v-for="status in availableStatuses"
    :key="status.value"
    @click="changeStatus(syllabus, status.value)"
  >
    {{ status.label }}
  </button>
</div>
```

**Script Changes**:

**Thêm state** (line 133):
```javascript
const statusDropdownOpen = ref(null);
```

**Thêm availableStatuses** (line 136-141):
```javascript
const availableStatuses = [
  { value: 'draft', label: 'Bản nháp' },
  { value: 'approved', label: 'Đã duyệt' },
  { value: 'in_use', label: 'Đang sử dụng' },
  { value: 'archived', label: 'Lưu trữ' }
];
```

**Thêm permission check** (line 144-146):
```javascript
const canChangeStatus = authStore.hasPermission('syllabus.change_status') ||
                        authStore.hasPermission('lesson_plans.change_status') ||
                        authStore.hasPermission('lesson_plans.edit');
```

**Thêm methods**:

1. `toggleStatusDropdown(syllabusId)` - Mở/đóng dropdown
2. `changeStatus(syllabus, newStatus)` - Gọi API và update local state
3. `closeDropdownOnClickOutside(event)` - Đóng dropdown khi click ra ngoài

**Lifecycle hooks** (line 278-285):
```javascript
onMounted(() => {
  loadSyllabi();
  document.addEventListener('click', closeDropdownOnClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdownOnClickOutside);
});
```

## 🎨 Các Trạng Thái

| Status | Label | Class | Màu | Ý nghĩa |
|--------|-------|-------|-----|---------|
| `draft` | Bản nháp | `bg-gray-100 text-gray-800` | Xám | Đang soạn thảo |
| `approved` | Đã duyệt | `bg-blue-100 text-blue-800` | Xanh dương | Đã được duyệt |
| `in_use` | Đang sử dụng | `bg-green-100 text-green-800` | Xanh lá | Đang dùng trong lớp học |
| `archived` | Lưu trữ | `bg-gray-100 text-gray-800` | Xám | Không còn dùng |

## 🔐 Permissions

### Permissions Được Kiểm Tra (Theo Thứ Tự):

1. **`syllabus.change_status`** (quyền riêng cho thay đổi trạng thái)
   - Ưu tiên cao nhất
   - Cho phép thay đổi trạng thái mà không cần quyền edit

2. **`lesson_plans.change_status`** (tương thích ngược)
   - Fallback thứ 2
   - Hỗ trợ hệ thống cũ

3. **`lesson_plans.edit`** hoặc **`syllabus.edit`** (quyền chỉnh sửa chung)
   - Fallback cuối cùng
   - Người có quyền edit tự động có thể đổi trạng thái

### Thêm Permission (Optional):

Tham khảo file [ADD_SYLLABUS_CHANGE_STATUS_PERMISSION.md](ADD_SYLLABUS_CHANGE_STATUS_PERMISSION.md)

## 📊 Testing

### Test Cases:

1. **✅ Người có quyền `lesson_plans.edit`**:
   - Badge có mũi tên dropdown
   - Click badge → Hiện dropdown
   - Chọn trạng thái mới → Update thành công

2. **✅ Người không có quyền**:
   - Badge không có mũi tên
   - Không thể click
   - Chỉ xem trạng thái

3. **✅ Thay đổi trạng thái**:
   - Draft → Approved → Thành công
   - Approved → In Use → Thành công
   - In Use → Archived → Thành công

4. **✅ Validation**:
   - Chọn trạng thái không hợp lệ → Báo lỗi
   - Giáo án không tồn tại → 404

5. **✅ UI/UX**:
   - Dropdown đóng khi click ra ngoài
   - Animation mượt mà
   - Thông báo thành công/lỗi hiển thị đúng

## 📝 Hướng Dẫn Sử Dụng

### Cho Người Dùng:

1. Vào **Quality Management** → **Danh sách Giáo Án**
2. Tìm giáo án cần thay đổi trạng thái
3. Click vào **badge trạng thái** (nếu có quyền)
4. Chọn trạng thái mới từ dropdown
5. Hệ thống tự động cập nhật và hiển thị thông báo

### Cho Admin:

**Nếu muốn phân quyền chi tiết hơn:**

1. Thêm permission `syllabus.change_status` vào database (xem [ADD_SYLLABUS_CHANGE_STATUS_PERMISSION.md](ADD_SYLLABUS_CHANGE_STATUS_PERMISSION.md))
2. Gán permission cho các roles cần thiết (ví dụ: Trưởng Khoa, Giám Đốc)
3. User sẽ có thể thay đổi trạng thái mà không cần quyền edit

## 🚀 Deployment

1. **Backend**: Đã commit code vào repository
2. **Frontend**: Đã build thành công (`npm run build`)
3. **Database**: Không cần migration (permission là optional)

## 📁 Files Changed

- ✅ `app/Http/Controllers/Api/LessonPlanController.php` (thêm updateStatus method)
- ✅ `routes/api.php` (thêm route `/api/lesson-plans/{id}/status`)
- ✅ `resources/js/pages/quality/SyllabusList.vue` (UI + logic)

## 📚 Documentation

- [ADD_SYLLABUS_CHANGE_STATUS_PERMISSION.md](ADD_SYLLABUS_CHANGE_STATUS_PERMISSION.md) - Hướng dẫn thêm permission
- [CHANGELOG_SYLLABUS_STATUS.md](CHANGELOG_SYLLABUS_STATUS.md) - File này

## ✅ Checklist

- [x] Backend API endpoint
- [x] Route configuration
- [x] Frontend UI implementation
- [x] Permission check (backend + frontend)
- [x] Build frontend
- [x] Documentation
- [ ] Add permission to database (optional)
- [ ] Assign permission to roles (optional)

---

**Date**: 2025-11-24
**Version**: 1.0.0
**Status**: ✅ Complete
