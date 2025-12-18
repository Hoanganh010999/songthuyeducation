# Hệ Thống Phân Quyền Google Drive

## 📋 Tổng Quan

Hệ thống phân quyền Google Drive cho phép quản lý truy cập vào các folder/file dựa trên permissions thực tế từ Google Drive API.

### Tính Năng Chính

1. **Phân quyền dựa trên Google Drive**: User chỉ thấy những folder mà họ có quyền trên Google Drive
2. **Đồng bộ tự động**: Permissions được sync từ Google Drive về database
3. **Xác thực thời gian thực**: Có thể verify permissions trực tiếp với Google Drive khi cần
4. **Cây thư mục thông minh**: Tự động hiển thị folder cha (trừ root) của folders được phân quyền

## 🏗️ Cấu Trúc Database

### Bảng `google_drive_permissions`

```sql
- id
- user_id (FK to users)
- google_drive_item_id (FK to google_drive_items)
- google_permission_id (Permission ID from Google Drive)
- role (reader, writer, commenter, etc.)
- is_verified (boolean)
- verified_at
- synced_at
- created_at, updated_at
```

### Relationships

- **User** ← hasMany → **GoogleDrivePermission**
- **GoogleDriveItem** ← hasMany → **GoogleDrivePermission**

## 🔑 Quyền Truy Cập

### `google-drive.view_root_folder`

- **Mô tả**: Cho phép xem và truy cập root folder (School Drive)
- **Mặc định**: Chỉ `super-admin` và `admin`
- **Người dùng khác**: Chỉ thấy folders mà họ được phân quyền

### Logic Phân Quyền

```
IF user has "google-drive.view_root_folder" THEN
    → Load toàn bộ root folder
ELSE IF user has verified permissions THEN
    → Load accessible folders + parent folders (không bao gồm root)
ELSE
    → Hiển thị "Bạn chưa có quyền truy cập folder nào"
END
```

## 📡 API Endpoints

### 1. Get Accessible Folder Tree

```http
GET /api/google-drive/accessible-folders
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "google_id": "1abc...",
      "name": "Lesson Plans",
      "type": "folder",
      "parent_id": null
    }
  ]
}
```

### 2. Sync Folder Permissions

```http
POST /api/google-drive/files/{id}/sync-permissions
```

**Response:**
```json
{
  "success": true,
  "message": "Đồng bộ quyền truy cập thành công",
  "synced_count": 5
}
```

### 3. Verify User Permission

```http
GET /api/google-drive/files/{id}/verify-permission
```

**Response:**
```json
{
  "success": true,
  "has_permission": true,
  "permission": {
    "id": "12345",
    "role": "writer",
    "emailAddress": "user@example.com"
  }
}
```

## 🤖 Console Command

### Đồng Bộ Permissions Tự Động

```bash
# Sync tất cả folders
php artisan gdrive:sync-permissions

# Sync folder cụ thể
php artisan gdrive:sync-permissions --folder=1abc...

# Sync theo branch
php artisan gdrive:sync-permissions --branch=1

# Force sync (bỏ qua kiểm tra thời gian)
php artisan gdrive:sync-permissions --force
```

### Schedule trong `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Sync permissions mỗi ngày lúc 2 AM
    $schedule->command('gdrive:sync-permissions')
             ->daily()
             ->at('02:00');
}
```

## 🔄 Workflow Phân Quyền

### 1. Gán Google Email cho User

```
Admin → Users Management → Click nút email icon → Nhập Google email
→ Tự động tạo folder cá nhân và share với email đó
```

### 2. Share Folder với User

```
Option A: Từ Google Drive UI
1. Admin share folder trên Google Drive
2. Chạy command: php artisan gdrive:sync-permissions

Option B: Từ School ERP
1. Navigate to Google Drive module
2. Click "Share" trên folder
3. Nhập email và role
4. System tự động sync permission
```

### 3. User Truy Cập

```
User login → Google Drive module
→ Tự động load accessible folders
→ Navigate vào folder được phân quyền
```

## 📝 Ví Dụ Thực Tế

### Scenario: Giáo Viên Chỉ Thấy Giáo Án Của Mình

**Cấu trúc:**
```
Root (School Drive)
└── Lesson Plans
    ├── IELTS 1.0 (Teacher A có quyền)
    └── IELTS 2.0 (Teacher B có quyền)
```

**Kết quả:**
- **Teacher A** thấy: `Lesson Plans > IELTS 1.0` (KHÔNG thấy root, KHÔNG thấy IELTS 2.0)
- **Teacher B** thấy: `Lesson Plans > IELTS 2.0` (KHÔNG thấy root, KHÔNG thấy IELTS 1.0)
- **Admin** thấy: Toàn bộ cây thư mục

## 🛠️ Troubleshooting

### User không thấy folder mặc dù đã share

1. Kiểm tra user đã có `google_email`:
```sql
SELECT id, name, google_email FROM users WHERE id = ?;
```

2. Kiểm tra permission trong database:
```sql
SELECT * FROM google_drive_permissions 
WHERE user_id = ? AND google_drive_item_id = ?;
```

3. Chạy verify permission:
```bash
GET /api/google-drive/files/{folder_id}/verify-permission
```

4. Force sync:
```bash
php artisan gdrive:sync-permissions --folder={google_id} --force
```

### Folder đã unshare nhưng user vẫn thấy

1. Chạy sync để cập nhật:
```bash
php artisan gdrive:sync-permissions --folder={google_id} --force
```

2. Hoặc xóa permission thủ công:
```sql
DELETE FROM google_drive_permissions 
WHERE user_id = ? AND google_drive_item_id = ?;
```

## ⚙️ Best Practices

1. **Sync định kỳ**: Chạy command sync mỗi ngày để đảm bảo đồng bộ
2. **Verify trước khi truy cập quan trọng**: Gọi API verify cho các folder nhạy cảm
3. **Gán Google Email**: Luôn gán Google email cho user trước khi share folder
4. **Sử dụng role phù hợp**:
   - `reader`: Chỉ xem
   - `commenter`: Xem và comment
   - `writer`: Xem, edit, và upload
   - `fileOrganizer`: Quản lý files (không thể delete folder)
   - `organizer`: Quản lý toàn bộ (trừ delete folder gốc)

## 🔐 Security Notes

- Root folder (`google-drive.view_root_folder`) chỉ dành cho Admin
- Permissions được verify theo 2 lớp: Database + Google Drive API
- Command sync chỉ sync folders active (not trashed)
- Permission verification tự động cập nhật database

---

**Created:** November 10, 2025  
**Version:** 1.0.0

