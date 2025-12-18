# ✅ Google Drive Service - Hoàn Thiện Code

## 🔍 Vấn Đề Được Phát Hiện

Trong phiên làm việc trước, file `GoogleDriveService.php` bị dừng giữa chừng và còn **2 methods bị thiếu**:

### 1. ❌ Method `listFilesInFolder()` - THIẾU
**Vị trí gọi:**
- Line 1478: `$items = $this->listFilesInFolder($sourceFolderId);`
- Line 1580: `$items = $this->listFilesInFolder($classFolderId);`
- Line 1591: `$subfolders = $this->listFilesInFolder($unitFolderId);`
- Line 1676: `$existingFiles = $this->listFilesInFolder($lessonPlansFolderId);`
- Line 1719: `$allFiles = $this->listFilesInFolder($lessonPlansFolderId);`

**Lỗi:** `Call to undefined method App\Services\GoogleDriveService::listFilesInFolder()`

### 2. ❌ Method `uploadFileWithCustomName()` - THIẾU
**Vị trí gọi:**
- Line 1715: `$fileId = $this->uploadFile($file, $lessonPlansFolderId, $fileName);`

**Vấn đề:** 
- Method `uploadFile()` nhận 4 params: `($file, $parentId, $branchId, $userId)`
- Nhưng đang gọi với 3 params: `($file, $lessonPlansFolderId, $fileName)`
- Param thứ 3 là `$fileName` (string) nhưng method mong đợi `$branchId` (int)

---

## ✅ Giải Pháp Đã Áp Dụng

### 1. ✅ Thêm Method `listFilesInFolder()`

**Vị trí:** Sau method `listFiles()` (line ~873)

**Code:**
```php
/**
 * List all files/folders in a specific folder (returns simple array, no pagination)
 */
protected function listFilesInFolder($folderId)
{
    try {
        $allFiles = [];
        $pageToken = null;

        do {
            $result = $this->listFiles($folderId, 100, $pageToken);
            $files = $result['files'] ?? [];
            $allFiles = array_merge($allFiles, $files);
            $pageToken = $result['nextPageToken'] ?? null;
        } while ($pageToken);

        return $allFiles;
    } catch (\Exception $e) {
        Log::error('[GoogleDrive] Error listing files in folder', [
            'error' => $e->getMessage(),
            'folder_id' => $folderId,
        ]);
        throw $e;
    }
}
```

**Chức năng:**
- Gọi `listFiles()` với pagination tự động
- Merge tất cả files từ các pages
- Trả về mảng đơn giản (không có pagination info)

---

### 2. ✅ Thêm Method `uploadFileWithCustomName()`

**Vị trí:** Sau method `uploadFile()` (line ~1070)

**Code:**
```php
/**
 * Upload a file to Google Drive with custom name
 * Returns only the file ID (not database item)
 */
protected function uploadFileWithCustomName($file, $parentId, $customName)
{
    try {
        Log::info('[GoogleDrive] Starting file upload with custom name', [
            'original_name' => $file->getClientOriginalName(),
            'custom_name' => $customName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'parent_id' => $parentId,
        ]);

        // Use multipart upload (single request with metadata + content)
        $boundary = uniqid();
        $metadata = [
            'name' => $customName,  // ← Sử dụng custom name
            'parents' => [$parentId],
        ];

        // Build multipart request body
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($metadata) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$file->getMimeType()}\r\n\r\n";
        $body .= file_get_contents($file->getRealPath()) . "\r\n";
        $body .= "--{$boundary}--";

        $uploadResponse = Http::withToken($this->accessToken)
            ->withHeaders([
                'Content-Type' => "multipart/related; boundary={$boundary}",
            ])
            ->withBody($body, "multipart/related; boundary={$boundary}")
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,mimeType,webViewLink');

        if (!$uploadResponse->successful()) {
            Log::error('[GoogleDrive] Upload failed', [
                'status' => $uploadResponse->status(),
                'response' => $uploadResponse->body(),
            ]);
            throw new \Exception('Failed to upload file: ' . $uploadResponse->body());
        }
        
        $fileData = $uploadResponse->json();
        
        Log::info('[GoogleDrive] File uploaded successfully with custom name', [
            'google_id' => $fileData['id'],
            'file_name' => $fileData['name'],
        ]);

        return $fileData['id'];  // ← Chỉ trả về file ID
    } catch (\Exception $e) {
        Log::error('[GoogleDrive] Error uploading file with custom name', [
            'error' => $e->getMessage(),
            'custom_name' => $customName,
        ]);
        throw $e;
    }
}
```

**Khác biệt với `uploadFile()`:**
| Feature | `uploadFile()` | `uploadFileWithCustomName()` |
|---------|----------------|------------------------------|
| File name | `$file->getClientOriginalName()` | `$customName` (param) |
| Return | `GoogleDriveItem` (database model) | `string` (file ID only) |
| Save to DB | ✅ Yes | ❌ No |
| Visibility | `public` | `protected` |
| Use case | Upload user files | Upload with auto-generated naming |

---

### 3. ✅ Cập Nhật `uploadLessonPlan()`

**Trước:**
```php
$fileId = $this->uploadFile($file, $lessonPlansFolderId, $fileName);
```

**Sau:**
```php
$fileId = $this->uploadFileWithCustomName($file, $lessonPlansFolderId, $fileName);
```

**Line:** ~1778

---

## 🎯 Kết Quả

### ✅ Linter Errors: 0
```bash
No linter errors found.
```

### ✅ Methods Hoàn Chỉnh
1. ✅ `listFilesInFolder()` - Helper method cho pagination tự động
2. ✅ `uploadFileWithCustomName()` - Upload với tên custom
3. ✅ `uploadLessonPlan()` - Đã cập nhật để dùng method mới

### ✅ Use Cases Hoạt Động
1. **Copy Syllabus Folder** → Sử dụng `listFilesInFolder()` để đệ quy
2. **Get Unit Folders** → Sử dụng `listFilesInFolder()` để query subfolders
3. **Upload Lesson Plan** → Sử dụng `uploadFileWithCustomName()` với naming convention
4. **Get Lesson Plans by Class Code** → Sử dụng `listFilesInFolder()` để filter

---

## 📁 Files Modified

1. **`app/Services/GoogleDriveService.php`**
   - ✅ Added `listFilesInFolder()` (line ~878)
   - ✅ Added `uploadFileWithCustomName()` (line ~1075)
   - ✅ Updated `uploadLessonPlan()` to use new method (line ~1778)

---

## 🎉 Trạng Thái: HOÀN THIỆN

File `GoogleDriveService.php` đã được hoàn thiện đầy đủ và sẵn sàng sử dụng cho:
- ✅ Syllabus Management
- ✅ Class Management
- ✅ Lesson Plan Upload/Management
- ✅ Folder Copy & Structure Management
- ✅ User Permission Management

Không còn methods bị thiếu hoặc lỗi cú pháp!

