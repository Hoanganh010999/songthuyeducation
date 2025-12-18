# Google Drive Branch-Specific Folders

## Tổng quan

Hệ thống Google Drive hiện đã được cấu hình để tự động tạo và quản lý các folder riêng biệt cho từng chi nhánh (branch) với format:

```
{branch_id} - {branch_name}
```

Ví dụ:
- `1 - Chi Nhánh Hà Nội`
- `2 - Chi Nhánh TP.HCM`
- `3 - Chi Nhánh Đà Nẵng`

## Cơ chế hoạt động

### 1. Tự động tạo folder khi khởi tạo

Khi branch đầu tiên kết nối Google Drive, hệ thống sẽ:

1. **Kiểm tra branch ID và name** từ database
2. **Tạo folder name** theo format `{branch_id} - {branch_name}`
3. **Tìm kiếm folder** trên Google Drive theo tên này
4. **Tạo mới nếu chưa tồn tại**, hoặc sử dụng folder hiện có
5. **Lưu folder ID** vào database cho lần sau

### 2. Tự động cập nhật tên folder

Nếu tên branch thay đổi trong database, hệ thống sẽ:

1. Phát hiện sự khác biệt giữa tên cũ và tên mới
2. Gọi Google Drive API để update tên folder
3. Cập nhật lại database

### 3. Cấu trúc folder

```
Google Drive Root
└── {branch_id} - {branch_name}
    ├── Syllabus/
    │   └── (các file giáo trình)
    └── Lesson Plan/
        └── (các file giáo án)
```

## Code thay đổi chính

### GoogleDriveService.php

```php
/**
 * Generate branch-specific folder name
 * Format: "{branch_id} - {branch_name}"
 */
protected function generateBranchFolderName()
{
    $branch = $this->setting->branch;
    
    if (!$branch) {
        return "Branch {$this->setting->branch_id} - School Drive";
    }
    
    return "{$branch->id} - {$branch->name}";
}
```

### Tự động kiểm tra và update

```php
// Check if folder name changed
if ($this->setting->school_drive_folder_name !== $folderName) {
    Log::info('[GoogleDrive] Folder name changed, updating...');
    $this->updateFolderName($this->setting->school_drive_folder_id, $folderName);
    
    // Update database
    $this->setting->update(['school_drive_folder_name' => $folderName]);
}
```

## Artisan Command

### Sync folder names cho tất cả branches

Sử dụng command sau để sync lại tất cả folder names:

```bash
php artisan google-drive:sync-branch-folders
```

Output mẫu:
```
Starting Google Drive branch folders sync...
Found 3 active Google Drive setting(s).

Processing branch: Chi Nhánh Hà Nội (ID: 1)
✓ Folder synced successfully
  Folder ID: 1-sdpIxYDg-U9b2OOOWD0SxPwaphLun4H
  Folder Name: 1 - Chi Nhánh Hà Nội

Processing branch: Chi Nhánh TP.HCM (ID: 2)
✓ Folder synced successfully
  Folder ID: 2-xYzAbC123-456DEF789GHI012JKL345
  Folder Name: 2 - Chi Nhánh TP.HCM

Processing branch: Chi Nhánh Đà Nẵng (ID: 3)
✓ Folder synced successfully
  Folder ID: 3-mNoPqR678-901STU234VWX567YZA890
  Folder Name: 3 - Chi Nhánh Đà Nẵng

✓ Sync completed!
```

## Testing

### 1. Test với branch mới

```bash
# 1. Tạo branch mới trong database
INSERT INTO branches (name, code, is_active) VALUES ('Chi Nhánh Cần Thơ', 'CN004', true);

# 2. Tạo Google Drive setting cho branch này
INSERT INTO google_drive_settings (branch_id, client_id, client_secret, is_active) 
VALUES (4, 'your_client_id', 'your_client_secret', true);

# 3. Authorize và test
# Truy cập: https://admin.songthuy.edu.vn/settings
# Click "Authorize with Google" cho branch mới

# 4. Kiểm tra folder đã được tạo
# Folder name sẽ là: "4 - Chi Nhánh Cần Thơ"
```

### 2. Test update tên branch

```bash
# 1. Update tên branch trong database
UPDATE branches SET name = 'Chi Nhánh Hà Nội - Mới' WHERE id = 1;

# 2. Chạy sync command
php artisan google-drive:sync-branch-folders

# 3. Kiểm tra trên Google Drive
# Folder name đã đổi từ "1 - Chi Nhánh Hà Nội" 
# thành "1 - Chi Nhánh Hà Nội - Mới"
```

### 3. Test multiple branches cùng lúc

```bash
# 1. Tạo nhiều branches
php artisan tinker
>>> Branch::create(['name' => 'Branch A', 'code' => 'BRA', 'is_active' => true]);
>>> Branch::create(['name' => 'Branch B', 'code' => 'BRB', 'is_active' => true]);
>>> Branch::create(['name' => 'Branch C', 'code' => 'BRC', 'is_active' => true]);

# 2. Setup Google Drive cho từng branch
# (Thực hiện authorize từ UI)

# 3. Test upload file
# Upload file vào branch A → file sẽ nằm trong folder "5 - Branch A"
# Upload file vào branch B → file sẽ nằm trong folder "6 - Branch B"
```

## Lưu ý quan trọng

### 1. Permissions

- Mỗi branch chỉ có thể truy cập vào folder của chính mình
- Folder được tạo với quyền owner của Google account đã authorize
- Không có cross-branch access

### 2. Folder ID caching

- Folder ID được cache trong database (`google_drive_settings.school_drive_folder_id`)
- Giảm số lần gọi API tìm kiếm folder
- Tự động verify và update nếu folder bị xóa

### 3. Error handling

- Nếu folder bị xóa trên Google Drive → hệ thống tự động tạo lại
- Nếu branch bị xóa → folder vẫn tồn tại trên Google Drive (cần manual cleanup)
- Nếu token expired → tự động refresh trước khi thực hiện bất kỳ thao tác nào

## Migration từ hệ thống cũ

Nếu bạn đã có data từ hệ thống cũ (folder name = "School Drive"):

```bash
# Chạy command sync để update tất cả
php artisan google-drive:sync-branch-folders
```

Command này sẽ:
1. Tìm tất cả settings có `is_active = true`
2. Generate tên folder mới cho từng branch
3. Update tên folder trên Google Drive
4. Update database

## API Endpoints

### Get folder info for current branch

```http
GET /api/google-drive/settings
```

Response:
```json
{
  "success": true,
  "data": {
    "branch_id": 1,
    "school_drive_folder_id": "1-sdpIxYDg-U9b2OOOWD0SxPwaphLun4H",
    "school_drive_folder_name": "1 - Chi Nhánh Hà Nội",
    "is_active": true
  }
}
```

### Test connection (auto creates folder if needed)

```http
POST /api/google-drive/test-connection
```

Response:
```json
{
  "success": true,
  "message": "Google Drive connection successful",
  "folder": {
    "id": "1-sdpIxYDg-U9b2OOOWD0SxPwaphLun4H",
    "name": "1 - Chi Nhánh Hà Nội"
  }
}
```

## Troubleshooting

### Folder name không đổi sau khi update branch name

```bash
# Solution: Chạy sync command
php artisan google-drive:sync-branch-folders
```

### Nhiều folders cùng tên trên Google Drive

```bash
# Prevention: Hệ thống tự động tìm kiếm theo tên trước khi tạo mới
# Nếu đã có duplicate:
# 1. Xóa các folder duplicate thủ công trên Google Drive
# 2. Clear folder ID trong database
UPDATE google_drive_settings SET school_drive_folder_id = NULL WHERE branch_id = X;
# 3. Chạy lại sync
php artisan google-drive:sync-branch-folders
```

### Token expired errors

```bash
# Hệ thống tự động refresh token
# Nếu refresh token cũng expired:
# 1. Truy cập Settings UI
# 2. Click "Re-authorize with Google"
```

## Monitoring

### Check logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep GoogleDrive

# Filter for specific branch
tail -f storage/logs/laravel.log | grep "branch_id.*1"
```

### Log messages

- `[GoogleDrive] Finding or creating School Drive folder` - Bắt đầu tìm/tạo folder
- `[GoogleDrive] School Drive folder already exists` - Folder đã tồn tại, đang verify
- `[GoogleDrive] Folder name changed, updating...` - Phát hiện tên đổi, đang update
- `[GoogleDrive] School Drive folder ready` - Folder sẵn sàng sử dụng

## Security

### Đảm bảo mỗi branch chỉ thấy data của mình

Service tự động filter theo `branch_id`:

```php
// Trong GoogleDriveController
$branchId = $this->getBranchId($request);
$setting = GoogleDriveSetting::where('branch_id', $branchId)
    ->where('is_active', true)
    ->first();
```

### Không hard-code credentials

Tất cả credentials được lưu trong database, encrypted:
- `client_id`
- `client_secret` 
- `refresh_token`
- `access_token`

## Future enhancements

1. **Auto-cleanup**: Tự động xóa folder khi branch bị xóa
2. **Folder templates**: Tạo sẵn cấu trúc folder chuẩn cho branch mới
3. **Usage reporting**: Báo cáo dung lượng sử dụng của từng branch
4. **Folder sharing**: Cho phép share folder giữa các branches (nếu cần)

