# Subjects Teacher Management - Google Drive Integration

## Tổng Quan

Khi thêm hoặc xóa giáo viên khỏi môn học (Subject), hệ thống sẽ tự động:
1. Kiểm tra folder **Syllabus** có tồn tại trong Google Drive root chưa
2. Nếu chưa có → Tạo folder Syllabus
3. Nếu đã có → Bỏ qua
4. Cấp hoặc thu hồi quyền truy cập folder Syllabus cho giáo viên được thêm/xóa

---

## Quy Trình Thêm Giáo Viên

### 1. Kiểm Tra Google Email
```php
if (!$teacher->google_email) {
    return response()->json([
        'success' => false,
        'message' => __('errors.teacher_no_google_email'),
        'error_code' => 'NO_GOOGLE_EMAIL',
    ], 400);
}
```

**Nếu giáo viên chưa có Google email**:
- ❌ Không thể thêm vào môn học
- 🔴 Hiển thị cảnh báo với hướng dẫn khắc phục:
  1. Vào Users Management
  2. Click nút gán Google email
  3. Sau đó quay lại thêm giáo viên

### 2. Đảm Bảo Folder Syllabus Tồn Tại
```php
$syllabusFolderId = $service->findOrCreateSyllabusFolder();
```

**Phương thức `findOrCreateSyllabusFolder()`**:
- Kiểm tra trong settings đã có `syllabus_folder_id` chưa
- Nếu có → Verify folder còn tồn tại trên Google Drive
- Nếu chưa → Tìm kiếm folder tên "Syllabus" trong root
- Nếu không tìm thấy → Tạo mới folder "Syllabus"
- Cache folder ID vào `google_drive_settings.syllabus_folder_id`

### 3. Cấp Quyền Writer
```php
$service->shareFile($syllabusFolderId, $teacher->google_email, 'writer');
```

**Quyền được cấp**: `writer` (có thể xem, tải, sửa, tạo file mới)

**Lưu vào database**:
```php
GoogleDrivePermission::updateOrCreate([
    'user_id' => $teacher->id,
    'google_drive_item_id' => $item->id,
], [
    'role' => 'writer',
    'is_verified' => true,
    'verified_at' => now(),
    'synced_at' => now(),
]);
```

### 4. Gán Giáo Viên Vào Môn Học
```php
$subject->teachers()->attach($request->user_id, [
    'is_head' => false,
    'start_date' => now(),
    'status' => 'active',
]);
```

---

## Quy Trình Xóa Giáo Viên

### 1. Thu Hồi Quyền Google Drive
```php
if ($teacher && $teacher->google_email) {
    $service->revokePermission($syllabusFolderId, $teacher->google_email);
}
```

**Phương thức `revokePermission()`**:
1. Lấy danh sách permissions của folder
2. Tìm permission ID tương ứng với email giáo viên
3. Gọi Google Drive API để xóa permission
4. Xóa record trong bảng `google_drive_permissions`

**Xử lý lỗi**:
- Nếu permission không tồn tại → Log warning, tiếp tục xóa
- Nếu Google Drive API lỗi → Log warning, vẫn tiếp tục xóa giáo viên

### 2. Xóa Giáo Viên Khỏi Môn Học
```php
$subject->teachers()->detach($request->user_id);
```

---

## API Endpoints

### POST `/api/quality/subjects/{subject}/assign-teacher`

**Request**:
```json
{
  "user_id": 10,
  "is_head": false,
  "start_date": "2025-11-10",
  "end_date": null
}
```

**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "Đã gán giáo viên vào môn học và cấp quyền Google Drive",
  "data": {
    "id": 5,
    "name": "Tiếng Anh",
    "teachers": [...]
  }
}
```

**Response (Error - 400)**:
```json
{
  "success": false,
  "message": "Giáo viên chưa được gán tài khoản Google Drive. Vui lòng liên hệ Admin để gán Google email trước khi thêm vào môn học.",
  "error_code": "NO_GOOGLE_EMAIL"
}
```

**Response (Error - 500)**:
```json
{
  "success": false,
  "message": "Không thể cấp quyền Google Drive: ...",
  "details": "..."
}
```

### POST `/api/quality/subjects/{subject}/remove-teacher`

**Request**:
```json
{
  "user_id": 10
}
```

**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "Đã gỡ giáo viên khỏi môn học và thu hồi quyền Google Drive"
}
```

---

## Files Modified

### Backend
- **`app/Http/Controllers/Api/SubjectController.php`**
  - `assignTeacher()`: Thêm kiểm tra Google email & phân quyền
  - `removeTeacher()`: Thêm thu hồi quyền
  - `manageSyllabusFolderPermissions()`: Method mới xử lý phân quyền

- **`app/Services/GoogleDriveService.php`**
  - `revokePermission()`: Method mới thu hồi quyền bằng email

- **`database/seeders/ErrorMessagesTranslationsSeeder.php`**
  - Thêm 2 keys: `teacher_no_google_email`, `google_drive_permission_failed`

- **`database/seeders/SubjectsGoogleDriveTranslationsSeeder.php`** (NEW)
  - 4 keys cho hướng dẫn khắc phục

### Frontend
- **`resources/js/pages/quality/ManageTeachersModal.vue`**
  - Enhanced error handling với hướng dẫn chi tiết
  - Dialog cảnh báo với step-by-step instructions

---

## Frontend Error Handling

### Dialog Cảnh Báo (NO_GOOGLE_EMAIL)
```javascript
if (errorCode === 'NO_GOOGLE_EMAIL') {
  await Swal.fire({
    icon: 'warning',
    title: t('common.warning'),
    html: `
      <div class="text-left">
        <p class="mb-3">${error.response.data.message}</p>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-2">
          <p class="text-sm text-yellow-800">
            <strong>💡 ${t('subjects.how_to_fix')}:</strong><br>
            1. ${t('subjects.go_to_users_management')}<br>
            2. ${t('subjects.click_assign_google_email')}<br>
            3. ${t('subjects.then_add_teacher_to_subject')}
          </p>
        </div>
      </div>
    `,
    width: '600px'
  });
}
```

**Hiển thị**:
```
⚠️ Cảnh báo
Giáo viên chưa được gán tài khoản Google Drive. 
Vui lòng liên hệ Admin để gán Google email trước khi thêm vào môn học.

┌─────────────────────────────────────────┐
│ 💡 Cách khắc phục:                      │
│ 1. Vào quản lý Users                    │
│ 2. Click nút gán Google email cho GV   │
│ 3. Sau đó quay lại thêm GV vào môn học │
└─────────────────────────────────────────┘
```

---

## Translation Keys

### Errors (`errors` group)
```javascript
{
  'errors.teacher_no_google_email': 
    'Giáo viên chưa được gán tài khoản Google Drive. Vui lòng liên hệ Admin để gán Google email trước khi thêm vào môn học.',
  
  'errors.google_drive_permission_failed': 
    'Không thể cấp quyền Google Drive',
}
```

### Subjects (`subjects` group)
```javascript
{
  'subjects.how_to_fix': 'Cách khắc phục',
  'subjects.go_to_users_management': 'Vào quản lý Users',
  'subjects.click_assign_google_email': 'Click nút gán Google email cho giáo viên',
  'subjects.then_add_teacher_to_subject': 'Sau đó quay lại thêm giáo viên vào môn học',
}
```

---

## Flow Chart

```
┌─────────────────────────────────────────────────────┐
│ Admin thêm giáo viên vào môn học                    │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
        ┌────────────────┐
        │ Có Google      │  ❌ NO
        │ email?         ├──────────► Show warning dialog
        └────────┬───────┘              với hướng dẫn
                 │ ✅ YES
                 ▼
        ┌────────────────┐
        │ Folder         │  ❌ Create
        │ Syllabus       ├──────────► "Syllabus" folder
        │ tồn tại?       │             in root
        └────────┬───────┘
                 │ ✅ Exists
                 ▼
        ┌────────────────┐
        │ Cấp quyền      │
        │ writer cho     │
        │ giáo viên      │
        └────────┬───────┘
                 │
                 ▼
        ┌────────────────┐
        │ Lưu vào        │
        │ google_drive_  │
        │ permissions    │
        └────────┬───────┘
                 │
                 ▼
        ┌────────────────┐
        │ Gán giáo viên  │
        │ vào môn học    │
        └────────────────┘
```

---

## Database Schema

### Table: `google_drive_permissions`
```sql
CREATE TABLE google_drive_permissions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    google_drive_item_id BIGINT NOT NULL,
    google_permission_id VARCHAR(255) NULL,
    role VARCHAR(50) DEFAULT 'reader', -- reader, writer, commenter, owner
    is_verified BOOLEAN DEFAULT FALSE,
    verified_at TIMESTAMP NULL,
    synced_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY user_item_unique (user_id, google_drive_item_id),
    INDEX idx_is_verified (is_verified),
    INDEX idx_synced_at (synced_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (google_drive_item_id) REFERENCES google_drive_items(id) ON DELETE CASCADE
);
```

**Ý nghĩa các cột**:
- `user_id`: ID của giáo viên
- `google_drive_item_id`: ID của folder Syllabus trong bảng `google_drive_items`
- `role`: Loại quyền (writer cho giáo viên môn học)
- `is_verified`: Đã verify trên Google Drive chưa
- `synced_at`: Lần cuối sync từ Google Drive

---

## Testing Checklist

### Test Cases

#### ✅ TC1: Thêm giáo viên có Google email
**Steps**:
1. Giáo viên đã có `google_email` trong Users Management
2. Admin thêm giáo viên vào môn học
3. Kiểm tra folder Syllabus trên Google Drive

**Expected**:
- ✅ Giáo viên được thêm thành công
- ✅ Giáo viên có quyền `writer` trên folder Syllabus
- ✅ Record được tạo trong `google_drive_permissions`

#### ❌ TC2: Thêm giáo viên chưa có Google email
**Steps**:
1. Giáo viên chưa có `google_email`
2. Admin thêm giáo viên vào môn học

**Expected**:
- ❌ Hiển thị dialog cảnh báo
- 📋 Hướng dẫn 3 bước để khắc phục
- ⛔ Giáo viên KHÔNG được thêm vào môn học

#### ✅ TC3: Folder Syllabus chưa tồn tại
**Steps**:
1. Xóa folder Syllabus trên Google Drive hoặc chưa có
2. Admin thêm giáo viên vào môn học

**Expected**:
- ✅ Folder "Syllabus" được tạo tự động trong root
- ✅ Folder ID được lưu vào `google_drive_settings.syllabus_folder_id`
- ✅ Giáo viên được cấp quyền writer

#### ✅ TC4: Xóa giáo viên khỏi môn học
**Steps**:
1. Giáo viên đang có quyền writer trên folder Syllabus
2. Admin xóa giáo viên khỏi môn học

**Expected**:
- ✅ Quyền trên Google Drive bị thu hồi
- ✅ Record trong `google_drive_permissions` bị xóa
- ✅ Giáo viên bị xóa khỏi môn học

#### ⚠️ TC5: Xóa giáo viên - Google Drive lỗi
**Steps**:
1. Disconnect internet hoặc Google Drive API lỗi
2. Admin xóa giáo viên khỏi môn học

**Expected**:
- ⚠️ Log warning về lỗi Google Drive
- ✅ Giáo viên VẪN bị xóa khỏi môn học (graceful degradation)

---

## Logging

### Info Logs
```php
Log::info('[SubjectController] Managing Syllabus folder permissions', [
    'subject_id' => $subject->id,
    'subject_name' => $subject->name,
    'teacher_id' => $teacher->id,
    'teacher_email' => $teacher->google_email,
    'action' => 'add', // or 'remove'
]);

Log::info('[SubjectController] Permission granted to teacher', [
    'folder_id' => $syllabusFolderId,
    'teacher_email' => $teacher->google_email,
]);
```

### Warning Logs
```php
Log::warning('[SubjectController] Failed to remove Syllabus folder permissions', [
    'subject_id' => $subject->id,
    'teacher_id' => $teacher->id,
    'error' => $e->getMessage(),
]);

Log::warning('[SubjectController] No Google Drive settings for branch', [
    'branch_id' => $subject->branch_id,
]);
```

### Error Logs
```php
Log::error('[SubjectController] Failed to grant permission', [
    'folder_id' => $syllabusFolderId,
    'teacher_email' => $teacher->google_email,
    'error' => $e->getMessage(),
]);
```

---

## Notes & Best Practices

### 1. Graceful Degradation
- Khi **thêm** giáo viên: Nếu Google Drive lỗi → THROW error, không thêm GV
- Khi **xóa** giáo viên: Nếu Google Drive lỗi → LOG warning, VẪN xóa GV

**Lý do**: Tránh trường hợp giáo viên bị "kẹt" trong môn học vì Google Drive lỗi.

### 2. Permission Caching
Record trong `google_drive_permissions` giúp:
- Query nhanh hơn (không cần gọi Google API mỗi lần)
- Track lịch sử cấp quyền
- Sync định kỳ để đảm bảo consistency

### 3. Folder Syllabus
- **Tên cố định**: "Syllabus" (không thay đổi)
- **Vị trí**: Root của School Drive
- **Quyền**: Writer cho tất cả giáo viên thuộc bất kỳ môn học nào
- **Mục đích**: Chứa tất cả các syllabus của các môn học

### 4. Security
- Chỉ cấp quyền `writer`, không phải `owner`
- Giáo viên có thể:
  - ✅ Xem, tải, sửa, tạo file/folder
  - ❌ Xóa folder Syllabus
  - ❌ Thay đổi quyền của người khác

---

## Troubleshooting

### Vấn đề 1: "Không tìm thấy thư mục Syllabus"
**Nguyên nhân**: Folder Syllabus bị xóa hoặc chưa được tạo

**Khắc phục**:
1. Vào Google Drive settings trong Admin panel
2. Chạy sync để tạo lại folder structure
3. Hoặc thêm giáo viên vào bất kỳ môn học nào → Folder sẽ được tạo tự động

### Vấn đề 2: "Không thể cấp quyền Google Drive"
**Nguyên nhân**: Google Drive API lỗi, credential hết hạn, hoặc quota vượt mức

**Khắc phục**:
1. Kiểm tra Google Drive settings → Verify credentials
2. Check quota usage trong Google Cloud Console
3. Xem logs để biết chi tiết lỗi

### Vấn đề 3: Giáo viên không thấy folder Syllabus
**Nguyên nhân**: Permission chưa sync hoặc bị xóa

**Khắc phục**:
1. Xóa giáo viên khỏi môn học
2. Thêm lại giáo viên → Permission sẽ được cấp lại
3. Hoặc chạy sync permissions từ Google Drive module

---

**Last Updated**: November 10, 2025  
**Version**: 1.0.0  
**Status**: ✅ Complete & Tested

