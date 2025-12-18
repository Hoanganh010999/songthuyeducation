# Fix 504 Gateway Timeout - Google Drive Sync

## Vấn đề

Khi click nút "Đồng bộ" trong Google Drive Management, xuất hiện lỗi:

```
Failed to load resource: the server responded with a status of 504 (Gateway Time-out)
```

**Nguyên nhân:** Quá trình sync mất quá nhiều thời gian (> 60 giây), vượt quá timeout của nginx/PHP.

---

## Giải pháp

### Approach: Background Job + Status Polling

Thay vì chạy sync synchronously (blocking), giờ đây:

1. ✅ **Click "Đồng bộ" → Dispatch background job**
2. ✅ **Frontend poll status mỗi 2 giây**
3. ✅ **Hiển thị progress bar real-time**
4. ✅ **Không còn timeout vì request trả về ngay lập tức**

---

## Implementation

### 1. Backend - SyncGoogleDriveJob

**New File:** `school/app/Jobs/SyncGoogleDriveJob.php`

```php
class SyncGoogleDriveJob implements ShouldQueue
{
    public $timeout = 600; // 10 minutes
    public $tries = 3;
    
    protected $branchId;
    protected $userId;

    public function __construct(int $branchId, int $userId)
    {
        $this->branchId = $branchId;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        // Update status to "in_progress"
        $this->updateSyncStatus('in_progress', 0);
        
        // Execute sync process
        // - Find/create root folder
        // - Sync files/folders
        // - Sync permissions
        // - Update progress in cache
        
        // Update status to "completed"
        $this->updateSyncStatus('completed', 100, $result);
    }
    
    protected function updateSyncStatus(string $status, int $progress, array $data = []): void
    {
        $cacheKey = "google_drive_sync_status_{$this->branchId}_{$this->userId}";
        Cache::put($cacheKey, [
            'status' => $status,
            'progress' => $progress,
            'data' => $data,
            'updated_at' => now()->toIso8601String(),
        ], 600);
    }
}
```

**Key Features:**
- ✅ Timeout: 10 phút (thay vì 60 giây)
- ✅ Progress tracking: Update mỗi step
- ✅ Error handling: Retry up to 3 times
- ✅ Cache status: Để frontend poll

---

### 2. Backend - Controller Changes

**Modified:** `GoogleDriveController::sync()`

```php
public function sync(Request $request)
{
    // Check queue configuration
    $queueConnection = config('queue.default');
    
    if ($queueConnection === 'sync' || $queueConnection === 'database') {
        // Dispatch to queue (async)
        SyncGoogleDriveJob::dispatch($branchId, $userId);
        
        return response()->json([
            'success' => true,
            'message' => 'Sync job queued',
            'data' => ['status' => 'queued']
        ]);
    } else {
        // Run synchronously with extended timeout
        set_time_limit(300); // 5 minutes
        $job = new SyncGoogleDriveJob($branchId, $userId);
        $job->handle();
        
        // Return result from cache
        return response()->json([...]);
    }
}
```

**New Endpoint:** `GET /api/google-drive/sync-status`

```php
public function getSyncStatus(Request $request)
{
    $cacheKey = "google_drive_sync_status_{$branchId}_{$userId}";
    $status = Cache::get($cacheKey);
    
    return response()->json([
        'success' => true,
        'data' => $status ?? ['status' => 'idle']
    ]);
}
```

---

### 3. Frontend - Status Polling

**Modified:** `GoogleDriveIndex.vue::showSyncDialog()`

```javascript
const showSyncDialog = async () => {
  // 1. Start sync job
  const response = await axios.post('/api/google-drive/sync');
  
  if (response.data.success) {
    // 2. Poll status every 2 seconds
    const pollInterval = setInterval(async () => {
      const statusResponse = await axios.get('/api/google-drive/sync-status');
      const status = statusResponse.data.data;
      
      if (status.status === 'in_progress') {
        // Update progress dialog
        Swal.update({
          html: `
            <div class="progress-bar">
              <div style="width: ${status.progress}%"></div>
            </div>
            <p>${status.progress}%</p>
            <p>Files synced: ${status.data.files_synced}</p>
          `
        });
      } else if (status.status === 'completed') {
        // Stop polling & show result
        clearInterval(pollInterval);
        Swal.fire({
          icon: 'success',
          title: 'Sync completed!',
          html: `...`
        });
      } else if (status.status === 'failed') {
        // Stop polling & show error
        clearInterval(pollInterval);
        showError(status.data.error);
      }
    }, 2000);
  }
};
```

**Visual Flow:**

```
User clicks "Đồng bộ"
    ↓
1. Start sync job (instant response)
    ↓
2. Show progress dialog
    ↓
3. Poll status every 2s
    │
    ├─→ Status: in_progress (0-100%)
    │   ├─ Update progress bar
    │   ├─ Show files synced
    │   └─ Show permissions synced
    │
    ├─→ Status: completed
    │   ├─ Stop polling
    │   ├─ Show success dialog
    │   └─ Reload files
    │
    └─→ Status: failed
        ├─ Stop polling
        └─ Show error message
```

---

## Progress States

### Status Types

1. **idle** - No sync running
2. **queued** - Job queued, not started yet
3. **in_progress** - Syncing... (0-100%)
4. **completed** - Sync finished successfully
5. **failed** - Sync failed with error

### Progress Breakdown

- **0-10%**: Root folder check/creation
- **10-50%**: Files/folders sync
- **50-95%**: Permissions sync
- **95-100%**: Finalization

---

## UI Demo

### Progress Dialog

```
┌─────────────────────────────────────────┐
│ Đang đồng bộ...                         │
├─────────────────────────────────────────┤
│                                         │
│ ▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░░░░░░░░░░ 45%      │
│                                         │
│ 📁 Root Folder: 1 - Chi Nhánh Hà Nội  │
│ 📄 Files synced: 23                    │
│ 🔐 Permissions synced: 45              │
│ 📂 Folders processed: 3 / 5            │
│                                         │
│            [Loading...]                 │
└─────────────────────────────────────────┘
```

### Completion Dialog

```
┌─────────────────────────────────────────┐
│ ✓ Đồng bộ hoàn tất thành công          │
├─────────────────────────────────────────┤
│ ┌───────────────────────────────────┐   │
│ │ ✓ Root Folder:                    │   │
│ │ 1 - Chi Nhánh Hà Nội             │   │
│ │ (đã được xác minh)                │   │
│ └───────────────────────────────────┘   │
│                                         │
│ Files synced: 50                        │
│ Permissions synced: 120                 │
│ Folders processed: 5                    │
│                                         │
│                            [OK]         │
└─────────────────────────────────────────┘
```

---

## Testing

### Test 1: Normal Sync

**Steps:**
1. Truy cập: https://admin.songthuy.edu.vn/google-drive
2. Click nút "Đồng bộ"
3. Quan sát progress dialog

**Expected:**
- ✅ Dialog hiện ngay lập tức
- ✅ Progress bar tăng dần từ 0-100%
- ✅ Hiển thị số files/permissions synced
- ✅ Không có lỗi 504

### Test 2: Large Folder Sync

**Setup:**
- Folder có >100 files và nhiều subfolders

**Expected:**
- ✅ Sync hoàn thành sau 2-5 phút
- ✅ Progress update mượt mà
- ✅ Không timeout

### Test 3: Network Interruption

**Scenario:** Network bị gián đoạn trong lúc sync

**Expected:**
- ✅ Job vẫn chạy ở background
- ✅ Frontend tiếp tục poll khi network trở lại
- ✅ Hiển thị kết quả cuối cùng

---

## Configuration

### Queue Driver

**File:** `.env`

```env
# Option 1: Database queue (recommended)
QUEUE_CONNECTION=database

# Option 2: Sync queue (runs immediately, no worker needed)
QUEUE_CONNECTION=sync
```

### If using database queue

**Step 1:** Create jobs table

```bash
php artisan queue:table
php artisan migrate
```

**Step 2:** Start queue worker

```bash
# Production (with supervisor)
php artisan queue:work --daemon

# Development
php artisan queue:work
```

### If using sync queue

- No worker needed
- Runs in same process with extended timeout (5 minutes)
- Good for low-traffic environments

---

## Monitoring

### Check Queue Status

```bash
# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Clear failed jobs
php artisan queue:flush
```

### Check Sync Status

```bash
# Via API
curl -H "Authorization: Bearer {token}" \
  https://admin.songthuy.edu.vn/api/google-drive/sync-status

# Response
{
  "success": true,
  "data": {
    "status": "in_progress",
    "progress": 45,
    "data": {
      "files_synced": 23,
      "permissions_synced": 45
    }
  }
}
```

### Logs

```bash
# View sync logs
tail -f storage/logs/laravel.log | grep "GoogleDrive Job"

# Example output
[GoogleDrive Job] Starting sync {"branch_id":1,"user_id":1}
[GoogleDrive Job] Root folder ready {"folder_id":"...","folder_name":"1 - Chi Nhánh Hà Nội"}
[GoogleDrive Job] Files synced {"count":50}
[GoogleDrive Job] Sync completed {...}
```

---

## Troubleshooting

### Issue 1: Still getting 504

**Cause:** Sync connection = 'sync' và job vẫn chạy quá lâu

**Solution:**
```env
# Switch to database queue
QUEUE_CONNECTION=database
```

Then start worker:
```bash
php artisan queue:work --daemon
```

### Issue 2: Progress không update

**Cause:** Cache driver không hoạt động

**Solution:**
```env
# Check cache driver
CACHE_DRIVER=file  # or redis, memcached
```

Test cache:
```bash
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
```

### Issue 3: Job failed silently

**Cause:** Job exception không được caught

**Solution:**
```bash
# Check failed jobs
php artisan queue:failed

# View job details
php artisan queue:failed-table
```

---

## Performance Optimization

### For Large Folders

**Option 1:** Chunk processing

```php
// In SyncGoogleDriveJob
$folders->chunk(10)->each(function ($chunk) {
    foreach ($chunk as $folder) {
        // Process folder
    }
    // Update progress
    $this->updateSyncStatus(...);
});
```

**Option 2:** Parallel processing

```php
use Illuminate\Support\Facades\Bus;

// Dispatch multiple jobs for different folders
$jobs = $folders->map(fn($folder) => 
    new SyncFolderPermissionsJob($folder)
);

Bus::batch($jobs)->dispatch();
```

---

## Summary

✅ **Không còn 504 timeout** - Request trả về ngay  
✅ **Real-time progress** - User thấy sync đang chạy  
✅ **Background processing** - Không block UI  
✅ **Error recovery** - Retry mechanism  
✅ **Better UX** - Progress bar + detailed stats  

Hệ thống sync giờ đây có thể handle **large folders** mà không bị timeout! 🚀

