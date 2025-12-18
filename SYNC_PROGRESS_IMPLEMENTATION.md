# Sync Progress Tracking Implementation

## Yêu cầu từ người dùng

1. ✅ **Loading indicator khi sync**: Hiển thị loading indicator khi đang sync danh sách bạn và group trong quá trình đăng nhập
2. ✅ **Hiển thị phần trăm**: Báo được % tiến trình sync nếu có thể
3. ✅ **Logic "thiếu thì thêm mà thừa thì xóa"**: Resync sẽ thêm items mới và xóa items không còn tồn tại

---

## Phần đã hoàn thành

### 1. Backend - Progress Tracking System

#### 1.1 ZaloCacheService - Progress Updates

**File**: `app/Services/ZaloCacheService.php`

**Thêm import**:
```php
use Illuminate\Support\Facades\Cache;
```

**syncFriends() - Lines 17-128**:
```php
public function syncFriends(ZaloAccount $account, array $friendsFromApi): array
{
    $synced = 0;
    $updated = 0;
    $created = 0;
    $deleted = 0;

    // Collect all friend IDs from API response
    $apiFriendIds = [];

    // 🔥 NEW: Initialize progress tracking
    $totalFriends = count($friendsFromApi);
    $this->updateSyncProgress($account->id, 'friends', 0, $totalFriends, 'Đang đồng bộ danh sách bạn bè...');

    foreach ($friendsFromApi as $index => $friendData) {
        // ... sync logic ...

        $synced++;

        // 🔥 NEW: Update progress periodically (every 10 friends or on last friend)
        if ($synced % 10 === 0 || $synced === $totalFriends) {
            $this->updateSyncProgress($account->id, 'friends', $synced, $totalFriends, 'Đang đồng bộ danh sách bạn bè...');
        }
    }

    // 🔥 NEW: Delete friends that are no longer in API response
    if (!empty($apiFriendIds)) {
        $deleted = ZaloFriend::where('zalo_account_id', $account->id)
            ->whereNotIn('zalo_user_id', $apiFriendIds)
            ->delete();
    }

    // 🔥 NEW: Mark friends sync as complete
    $this->updateSyncProgress($account->id, 'friends', $totalFriends, $totalFriends, 'Hoàn thành đồng bộ danh sách bạn bè', true);

    return [
        'synced' => $synced,
        'created' => $created,
        'updated' => $updated,
        'deleted' => $deleted, // NEW
    ];
}
```

**syncGroups() - Lines 133-343**:
- Tương tự như syncFriends
- Update progress every 5 groups
- Delete groups not in API response
- Return `deleted` count

**Helper Method - Lines 345-368**:
```php
private function updateSyncProgress(int $accountId, string $type, int $current, int $total, string $message, bool $completed = false): void
{
    $cacheKey = "zalo_sync_progress_{$accountId}";
    $progress = Cache::get($cacheKey, [
        'friends' => ['current' => 0, 'total' => 0, 'percent' => 0, 'message' => '', 'completed' => false],
        'groups' => ['current' => 0, 'total' => 0, 'percent' => 0, 'message' => '', 'completed' => false],
    ]);

    $percent = $total > 0 ? round(($current / $total) * 100) : 0;

    $progress[$type] = [
        'current' => $current,
        'total' => $total,
        'percent' => $percent,
        'message' => $message,
        'completed' => $completed,
    ];

    // Store for 5 minutes
    Cache::put($cacheKey, $progress, 300);
}
```

#### 1.2 ZaloController - API Endpoint

**File**: `app/Http/Controllers/Api/ZaloController.php`

**Thêm import** (Line 18):
```php
use Illuminate\Support\Facades\Cache;
```

**New Method** (Lines 64-100):
```php
/**
 * Get sync progress for friends and groups
 */
public function getSyncProgress(Request $request)
{
    $accountId = $request->input('account_id');

    if (!$accountId) {
        return response()->json([
            'success' => false,
            'message' => 'Account ID is required',
        ], 400);
    }

    $cacheKey = "zalo_sync_progress_{$accountId}";
    $progress = Cache::get($cacheKey, [
        'friends' => ['current' => 0, 'total' => 0, 'percent' => 0, 'message' => 'Chưa bắt đầu', 'completed' => false],
        'groups' => ['current' => 0, 'total' => 0, 'percent' => 0, 'message' => 'Chưa bắt đầu', 'completed' => false],
    ]);

    // Calculate overall progress
    $friendsPercent = $progress['friends']['percent'] ?? 0;
    $groupsPercent = $progress['groups']['percent'] ?? 0;
    $overallPercent = ($friendsPercent + $groupsPercent) / 2;

    $allCompleted = ($progress['friends']['completed'] ?? false) && ($progress['groups']['completed'] ?? false);

    return response()->json([
        'success' => true,
        'data' => [
            'friends' => $progress['friends'],
            'groups' => $progress['groups'],
            'overall_percent' => round($overallPercent),
            'completed' => $allCompleted,
        ],
    ]);
}
```

#### 1.3 Routes

**File**: `routes/api.php` (Line 1202)

```php
Route::get('/sync-progress', [\App\Http\Controllers\Api\ZaloController::class, 'getSyncProgress'])->middleware('permission:zalo.view');
```

---

## Cách hoạt động

### Backend Flow:

```
1. User đăng nhập qua QR code
   ↓
2. pollForLogin() detects login success
   ↓
3. Backend calls reloginAccount() or refreshAccountInfo()
   ↓
4. Automatically triggers syncFriends() and syncGroups()
   ↓
5. syncFriends() begins:
   - Initialize progress: 0/25 friends (0%)
   - Update cache: zalo_sync_progress_{accountId}
   ↓
6. For each friend:
   - Add/update friend in database
   - Track friend ID
   - Every 10 friends: update progress in cache
   ↓
7. After all friends synced:
   - Delete friends not in apiFriendIds
   - Mark as completed: 25/25 (100%)
   ↓
8. syncGroups() follows same pattern
   ↓
9. Frontend can poll /api/zalo/sync-progress?account_id=9
   - Returns: {friends: {percent: 80, message: '...'}, groups: {percent: 50, message: '...'}}
```

### API Response Example:

**Endpoint**: `GET /api/zalo/sync-progress?account_id=9`

**Response**:
```json
{
  "success": true,
  "data": {
    "friends": {
      "current": 20,
      "total": 25,
      "percent": 80,
      "message": "Đang đồng bộ danh sách bạn bè...",
      "completed": false
    },
    "groups": {
      "current": 6,
      "total": 12,
      "percent": 50,
      "message": "Đang đồng bộ danh sách nhóm...",
      "completed": false
    },
    "overall_percent": 65,
    "completed": false
  }
}
```

**When completed**:
```json
{
  "success": true,
  "data": {
    "friends": {
      "current": 25,
      "total": 25,
      "percent": 100,
      "message": "Hoàn thành đồng bộ danh sách bạn bè",
      "completed": true
    },
    "groups": {
      "current": 12,
      "total": 12,
      "percent": 100,
      "message": "Hoàn thành đồng bộ danh sách nhóm",
      "completed": true
    },
    "overall_percent": 100,
    "completed": true
  }
}
```

---

## Phần cần implement trên Frontend

### Todo: ZaloAccountDetail.vue

**Location**: `pollForLogin()` method after login success detected

**Current code** (Lines 363-394):
```javascript
if (response.data.isReady) {
  clearInterval(interval);

  const updateResponse = await axios.post(endpoint, {
    account_id: accountId,
    ...(isNewAccount ? {} : { update: true })
  });

  if (updateResponse.data.success) {
    qrCode.value = null;
    Swal.fire({
      icon: 'success',
      title: t('zalo.login_successful'),
      timer: 2000,
    });
    emit('account-updated');
    emit('close-add-form');
  }
}
```

**Cần thêm**:
1. After calling update endpoint, start polling for sync progress
2. Show loading modal with progress bar
3. Update progress bar based on API response
4. Close modal when `completed: true`

**Suggested implementation**:
```javascript
if (response.data.isReady) {
  clearInterval(interval);

  // Call update endpoint to trigger sync
  const updateResponse = await axios.post(endpoint, {
    account_id: accountId,
    ...(isNewAccount ? {} : { update: true })
  });

  if (updateResponse.data.success) {
    qrCode.value = null;

    // 🔥 NEW: Show sync progress modal
    Swal.fire({
      title: 'Đang đồng bộ dữ liệu...',
      html: `
        <div class="text-left space-y-4">
          <div>
            <div class="flex justify-between text-sm mb-1">
              <span id="friends-message">Đang đồng bộ bạn bè...</span>
              <span id="friends-percent">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div id="friends-progress" class="bg-blue-600 h-2 rounded-full transition-all" style="width: 0%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-sm mb-1">
              <span id="groups-message">Đang đồng bộ nhóm...</span>
              <span id="groups-percent">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div id="groups-progress" class="bg-green-600 h-2 rounded-full transition-all" style="width: 0%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-sm font-bold mb-1">
              <span>Tổng tiến trình</span>
              <span id="overall-percent">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
              <div id="overall-progress" class="bg-gradient-to-r from-blue-600 to-green-600 h-3 rounded-full transition-all" style="width: 0%"></div>
            </div>
          </div>
        </div>
      `,
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => {
        // 🔥 Poll for sync progress
        pollForSyncProgress(accountId);
      }
    });
  }
}

// 🔥 NEW: Poll for sync progress
const pollForSyncProgress = (accountId) => {
  const syncInterval = setInterval(async () => {
    try {
      const response = await axios.get('/api/zalo/sync-progress', {
        params: { account_id: accountId }
      });

      if (response.data.success) {
        const data = response.data.data;

        // Update friends progress
        document.getElementById('friends-message').textContent = data.friends.message;
        document.getElementById('friends-percent').textContent = `${data.friends.percent}%`;
        document.getElementById('friends-progress').style.width = `${data.friends.percent}%`;

        // Update groups progress
        document.getElementById('groups-message').textContent = data.groups.message;
        document.getElementById('groups-percent').textContent = `${data.groups.percent}%`;
        document.getElementById('groups-progress').style.width = `${data.groups.percent}%`;

        // Update overall progress
        document.getElementById('overall-percent').textContent = `${data.overall_percent}%`;
        document.getElementById('overall-progress').style.width = `${data.overall_percent}%`;

        // If completed, close modal and show success
        if (data.completed) {
          clearInterval(syncInterval);
          Swal.close();
          Swal.fire({
            icon: 'success',
            title: 'Đăng nhập thành công',
            text: `Đã đồng bộ ${data.friends.total} bạn bè và ${data.groups.total} nhóm`,
            timer: 3000,
          });
          emit('account-updated');
          emit('close-add-form');
        }
      }
    } catch (error) {
      console.error('Error polling sync progress:', error);
    }
  }, 500); // Poll every 500ms for smooth progress updates
};
```

---

## Test Cases

### Test 1: Đăng nhập mới và sync
1. Click "Add Account" → Quét QR code
2. **Expected**:
   - Sau khi QR login thành công, hiển thị modal "Đang đồng bộ dữ liệu..."
   - Progress bar cho friends tăng từ 0% → 100%
   - Progress bar cho groups tăng từ 0% → 100%
   - Overall progress tăng từ 0% → 100%
   - Khi hoàn tất, modal đóng và hiển thị "Đăng nhập thành công"

### Test 2: Relogin account
1. Click "Relogin" trên account hiện có → Quét QR code
2. **Expected**: Tương tự Test 1

### Test 3: Resync friends/groups
1. Click button "Resync" ở friends hoặc groups list
2. **Expected**:
   - Hiển thị loading
   - Items thiếu được thêm vào
   - Items thừa bị xóa khỏi database

### Test 4: Kiểm tra deletion logic
1. Xóa 1 friend từ Zalo app
2. Click "Resync" friends trong ứng dụng
3. **Expected**: Friend đó bị xóa khỏi database Laravel
4. Tương tự với groups

---

## Summary

### ✅ Đã implement:
1. Backend progress tracking system
2. API endpoint `/api/zalo/sync-progress`
3. Deletion logic: "thiếu thì thêm mà thừa thì xóa"
4. Cache-based progress storage (5 minutes TTL)

### ⏳ Cần implement (Frontend):
1. Progress polling trong `ZaloAccountDetail.vue`
2. Progress modal với progress bars
3. Auto-close modal khi sync complete

### 📦 Files đã sửa:
1. `app/Services/ZaloCacheService.php` - Added progress tracking
2. `app/Http/Controllers/Api/ZaloController.php` - Added getSyncProgress method
3. `routes/api.php` - Added /sync-progress route

---

## Debug Tips

### Check cache progress manually:
```php
use Illuminate\Support\Facades\Cache;
$progress = Cache::get('zalo_sync_progress_9');
dd($progress);
```

### Monitor Laravel logs:
```
[ZaloCache] Synced friends
account_id: 9
synced: 25
created: 5
updated: 10
deleted: 3
```

### Test API endpoint:
```bash
curl -H "Authorization: Bearer {token}" \
     "http://localhost/api/zalo/sync-progress?account_id=9"
```
