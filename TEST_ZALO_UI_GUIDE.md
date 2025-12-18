# 🧪 HƯỚNG DẪN TEST ZALO LOGIN & SYNC FLOW

## ✅ CÁC FIX ĐÃ TRIỂN KHAI

1. **Frontend**: Polling 3s (thay vì 1s) - Giảm 66% spam
2. **Backend**: Update progress khi error, không auto-trigger khi có error
3. **Backend**: Sync lock 10 phút (thay vì 5 phút)
4. **Backend**: `getFriends()` và `getGroups()` throw exception thay vì return []
5. **Zalo-service**: Retry với exponential backoff (2s, 4s, 8s)

---

## 📋 CHUẨN BỊ

### 1. Mở Developer Tools
- Trình duyệt → F12 → Tab "Console"
- Để xem logs chi tiết của frontend

### 2. Mở Log Files
**Terminal 1 - Laravel Log:**
```bash
tail -f c:/xampp/htdocs/school/storage/logs/laravel.log
```

**Terminal 2 - Zalo Service Log:**
```bash
tail -f c:/xampp/htdocs/school/zalo-service/logs/out.log
```

---

## 🚀 BƯỚC TEST

### BƯỚC 1: Login Tài Khoản Zalo Mới

1. Truy cập: http://localhost:8000/zalo
2. Click nút **"Thêm tài khoản Zalo"**
3. Scan QR code bằng Zalo app
4. Đợi đến khi thấy popup **"Đang đồng bộ dữ liệu..."**

---

### BƯỚC 2: Quan Sát Progress Modal

**❌ TRƯỚC FIX (Behavior cũ):**
```
Friends: 0% - Chưa bắt đầu
Groups: 0% - Chưa bắt đầu
Overall: 0%

[Stuck ở 0% mãi không tiến triển]
```

**✅ SAU FIX (Behavior mới - EXPECTED):**

**Scenario A: Thành công**
```
Poll 1:  Friends 0%   - "Đang lấy danh sách bạn bè từ Zalo..."
         Groups 0%    - "Chưa bắt đầu"
         Overall 0%

Poll 2:  Friends 20%  - "Đang đồng bộ danh sách bạn bè..."
         Groups 0%    - "Chưa bắt đầu"
         Overall 10%

Poll 3:  Friends 100% - "Hoàn thành đồng bộ danh sách bạn bè"
         Groups 20%   - "Đang đồng bộ danh sách nhóm..."
         Overall 60%

Poll 4:  Friends 100% - "Hoàn thành..."
         Groups 100%  - "Hoàn thành đồng bộ danh sách nhóm"
         Overall 100%

✅ Popup: "Đăng nhập thành công! Đã đồng bộ X bạn bè và Y nhóm"
```

**Scenario B: Rate Limit (429)**
```
Poll 1:  Friends 0%   - "Đang lấy danh sách bạn bè từ Zalo..."
         Groups 0%    - "Chưa bắt đầu"

Poll 2:  Friends 0%   - "⚠️ Không thể lấy danh sách bạn bè.
                         Có thể do giới hạn tần suất (rate limit).
                         Vui lòng thử lại sau vài phút."
         Groups 100%  - "Hoàn thành đồng bộ danh sách nhóm"

[Modal KHÔNG TỰ ĐÓNG - User thấy error message rõ ràng]
```

---

### BƯỚC 3: Kiểm Tra Browser Console

**Expected logs:**

```javascript
🔄 [ZaloAccountDetail] Starting sync progress polling for account: X
🔄 Poll 1: Friends 0%, Groups 0%, Overall 0%
🔄 Poll 2: Friends 20%, Groups 0%, Overall 10%
🔄 Poll 3: Friends 100%, Groups 100%, Overall 100%
✅ [ZaloAccountDetail] Sync completed!
```

**Nếu có rate limit:**

```javascript
🔄 Poll 1: Friends 0%, Groups 0%, Overall 0%
⚠️  Friends error: ⚠️ Không thể lấy danh sách bạn bè...
```

---

### BƯỚC 4: Kiểm Tra Laravel Log

**Expected logs khi THÀNH CÔNG:**

```
[2025-11-26] local.INFO: [ZaloController] ⚡ Auto-triggering sync for account {"account_id":"X"}
[2025-11-26] local.INFO: [ZaloController] 🚀 Starting background sync {"account_id":"X"}
[2025-11-26] local.INFO: [ZaloController] Syncing friends for account {"account_id":X}
[2025-11-26] local.INFO: [Zalo] Getting friends {"url":"http://localhost:3001/api/user/friends","accountId":X}
[2025-11-26] local.INFO: [Zalo] Get friends response {"status":200,"successful":true}
[2025-11-26] local.INFO: [Zalo] Friends retrieved {"count":188}
[2025-11-26] local.INFO: [ZaloCache] Synced friends {"account_id":X,"synced":188,"created":0,"updated":188,"deleted":0}
[2025-11-26] local.INFO: [ZaloController] Friends synced successfully {"count":188}
[2025-11-26] local.INFO: [ZaloController] ✅ Background sync: Friends completed
[2025-11-26] local.INFO: [ZaloController] Syncing groups for account {"account_id":X}
[2025-11-26] local.INFO: [Zalo] Getting groups {"url":"http://localhost:3001/api/group/list","accountId":X}
[2025-11-26] local.INFO: [Zalo] Groups retrieved {"count":21}
[2025-11-26] local.INFO: [ZaloCache] Synced groups {"account_id":X,"synced":21,"created":0,"updated":21,"deleted":0}
[2025-11-26] local.INFO: [ZaloController] Groups synced successfully {"count":21}
[2025-11-26] local.INFO: [ZaloController] ✅ Background sync: Groups completed
[2025-11-26] local.INFO: [ZaloController] 🎉 Background sync completed
```

**Expected logs khi BỊ RATE LIMIT:**

```
[2025-11-26] local.INFO: [ZaloController] Syncing friends for account {"account_id":X}
[2025-11-26] local.INFO: [Zalo] Getting friends {"url":"http://localhost:3001/api/user/friends","accountId":X}
[2025-11-26] local.ERROR: [Zalo] Get friends failed {"status":429,"body":"...","error":"Rate limited by Zalo API (429). Please try again later."}
[2025-11-26] local.ERROR: [ZaloController] Failed to sync friends {"account_id":X,"error":"Rate limited by Zalo API (429). Please try again later."}
[2025-11-26] local.ERROR: [ZaloController] ⚠️ Background sync: Friends failed {"error":"Rate limited by Zalo API (429). Please try again later."}
```

---

### BƯỚC 5: Kiểm Tra Zalo-Service Log

**Expected logs khi THÀNH CÔNG:**

```
📋 [GET /api/user/friends] Getting friends list...
   Account ID: X
   ✅ Zalo session found
   ✅ Found method: getAllFriends()
   ✅ getAllFriends() returned 188 friends
::ffff:127.0.0.1 - - [26/Nov/2025:XX:XX:XX +0000] "GET /api/user/friends HTTP/1.1" 200 ...
```

**Expected logs khi BỊ RATE LIMIT + RETRY:**

```
📋 [GET /api/user/friends] Getting friends list...
   Account ID: X
   ✅ Zalo session found
   ✅ Found method: getAllFriends()
   ❌ getAllFriends() error: Request failed with status code 429
   ⏳ Rate limited (429), waiting 2s before retry 1/3...
   [2 seconds pause]
   ❌ getAllFriends() error: Request failed with status code 429
   ⏳ Rate limited (429), waiting 4s before retry 2/3...
   [4 seconds pause]
   ❌ getAllFriends() error: Request failed with status code 429
   ⏳ Rate limited (429), waiting 8s before retry 3/3...
   [8 seconds pause]
   ❌ getAllFriends() error after retries: Request failed with status code 429
::ffff:127.0.0.1 - - [26/Nov/2025:XX:XX:XX +0000] "GET /api/user/friends HTTP/1.1" 429 ...
```

---

### BƯỚC 6: Verify Database

**Chạy script SQL:**
```sql
-- Check số lượng friends
SELECT COUNT(*) as total FROM zalo_friends;

-- Check số lượng groups
SELECT COUNT(*) as total FROM zalo_groups;

-- Check chi tiết account vừa sync
SELECT
    a.id,
    a.name,
    COUNT(DISTINCT f.id) as friends_count,
    COUNT(DISTINCT g.id) as groups_count
FROM zalo_accounts a
LEFT JOIN zalo_friends f ON f.zalo_account_id = a.zalo_id
LEFT JOIN zalo_groups g ON g.zalo_account_id = a.zalo_id
WHERE a.deleted_at IS NULL
GROUP BY a.id, a.name;
```

---

## ✅ CHECKLIST TEST

- [ ] **Progress modal hiển thị đúng**
  - [ ] Hiển thị "Đang lấy..." khi bắt đầu
  - [ ] Progress bar tăng dần (0% → 100%)
  - [ ] Message cập nhật theo từng bước

- [ ] **Error handling đúng** (nếu gặp rate limit)
  - [ ] Hiển thị message "⚠️ Rate limit..."
  - [ ] Modal KHÔNG tự đóng
  - [ ] User biết phải đợi

- [ ] **Không bị spam loop**
  - [ ] Chỉ trigger sync 1 lần
  - [ ] Không thấy spam request trong log
  - [ ] Polling 3 giây/lần (không phải 1 giây)

- [ ] **Retry hoạt động** (nếu có rate limit)
  - [ ] Zalo-service log show retry: 2s, 4s, 8s
  - [ ] Sau 3 retry → return 429
  - [ ] Laravel catch exception và update progress

- [ ] **Data đúng**
  - [ ] Số friends trong DB = số friends từ API (±5)
  - [ ] Số groups trong DB = số groups từ API (±5)
  - [ ] Không có duplicate records

---

## 🐛 TROUBLESHOOTING

### Vấn đề 1: Stuck ở 0%

**Nguyên nhân có thể:**
- Rate limit 429
- Zalo-service không chạy
- Session expired

**Cách check:**
```bash
# Check zalo-service running
curl http://localhost:3001/health

# Check account session
SELECT id, is_connected FROM zalo_accounts WHERE deleted_at IS NULL;
```

### Vấn đề 2: Progress nhảy lung tung

**Nguyên nhân:** Cache không sync đúng

**Fix:**
```bash
cd c:/xampp/htdocs/school
php artisan cache:clear
```

### Vấn đề 3: Vẫn thấy spam trong log

**Check:**
- Frontend có build đúng không?
- Browser cache đã clear?

**Fix:**
```bash
# Rebuild frontend
cd c:/xampp/htdocs/school
npm run build

# Hard refresh browser: Ctrl+Shift+R
```

---

## 📊 EXPECTED RESULTS

### ✅ Test PASSED nếu:

1. **Progress hiển thị smooth:** 0% → 20% → 40% → 60% → 80% → 100%
2. **Không stuck:** Mỗi poll (3s) đều có update
3. **Error clear:** Nếu có lỗi → hiển thị rõ ràng
4. **Data accurate:** DB count ≈ API count
5. **No spam:** Không thấy spam request liên tục trong log
6. **Retry works:** Nếu 429 → retry 3 lần rồi show error

### ❌ Test FAILED nếu:

1. Stuck ở 0% không progress
2. Spam request liên tục (< 3 giây interval)
3. Modal tự đóng khi có error
4. Data không sync vào DB
5. Không thấy retry logs khi 429

---

## 📝 GHI CHÚ

- **Polling interval:** 3 giây (đã giảm từ 1 giây)
- **Sync lock:** 10 phút (đã tăng từ 5 phút)
- **Retry attempts:** 3 lần với backoff: 2s, 4s, 8s
- **Max poll:** 60 lần = 3 phút

---

Sau khi test xong, vui lòng báo cáo kết quả!
