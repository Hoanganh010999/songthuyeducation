# 🔧 Hướng Dẫn Fix Multi-Session Issues

## ❌ VẤN ĐỀ HIỆN TẠI

Từ screenshot bạn gửi, tôi thấy 3 vấn đề:

1. **Chỉ connect 1 account tại 1 thời điểm**
   - ✅ FIXED: Đã enable multi-session trong zalo-service

2. **Không thấy nút "Chuyển"**
   - Nguyên nhân: Cả 2 accounts đều có `is_active = true` trong database
   - Frontend đã đúng, nhưng database sai

3. **Dữ liệu không đổi khi switch account**
   - Friends/Groups/Messages không reload theo account mới
   - Frontend components chưa listen sự kiện `zalo-account-changed`

---

## ✅ GIẢI PHÁP

### Bước 1: Fix Database (CHỈ 1 ACCOUNT ACTIVE)

**Vấn đề:** Cả 2 accounts đều có `is_active = true`

**Cách fix:**

```sql
-- Xem accounts hiện tại
SELECT id, name, zalo_id, is_active FROM zalo_accounts;

-- Set chỉ account đầu tiên làm active
UPDATE zalo_accounts SET is_active = 0;
UPDATE zalo_accounts SET is_active = 1 WHERE id = 1 LIMIT 1;

-- Verify
SELECT id, name, is_active FROM zalo_accounts;
```

**Hoặc dùng PHP:**

```php
// File: fix-active-account.php
<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ZaloAccount;

// Set tất cả về inactive
ZaloAccount::query()->update(['is_active' => false]);

// Set account đầu tiên làm active
$firstAccount = ZaloAccount::first();
if ($firstAccount) {
    $firstAccount->update(['is_active' => true]);
    echo "✅ Set account {$firstAccount->name} (ID: {$firstAccount->id}) as active\n";
} else {
    echo "❌ No accounts found\n";
}

// Show current status
echo "\nCurrent accounts:\n";
foreach (ZaloAccount::all() as $acc) {
    echo sprintf("  - %s (ID: %d) - Active: %s\n",
        $acc->name,
        $acc->id,
        $acc->is_active ? 'YES' : 'NO'
    );
}
```

**Chạy:**
```bash
php fix-active-account.php
```

---

### Bước 2: Restart Zalo-Service với Multi-Session

```bash
cd zalo-service

# Verify multi-session đã enable
node enable-multi-session.js

# Start service
npm start
```

**Verify logs:**
```
✅ Using multi-session architecture
📁 Sessions directory ready
```

---

### Bước 3: Hard Refresh Browser

Sau khi rebuild frontend, bạn cần:

1. Mở DevTools (F12)
2. Right-click vào nút Refresh
3. Chọn **"Empty Cache and Hard Reload"**

Hoặc:

- Windows: `Ctrl + Shift + R`
- Mac: `Cmd + Shift + R`

---

### Bước 4: Verify Multi-Session Hoạt Động

#### Test 1: Kiểm tra nút "Chuyển"

Sau khi fix database và refresh browser, bạn sẽ thấy:

```
Account 1 (Active)
  [Badge: Active] [Badge: Connected]
  [Relogin] [Sync]

Account 2 (Inactive)
  [Nút: Chuyển] [Relogin] [Sync]  ← Nút "Chuyển" xuất hiện!
```

#### Test 2: Switch Account

1. Click nút **"Chuyển"** trên Account 2
2. Verify:
   - Account 2 hiện badge "Active"
   - Account 1 hiện nút "Chuyển"
   - Database: Account 2 có `is_active = 1`

#### Test 3: Multi-Session Connect

1. **Login Account 1:**
   - Nếu chưa connect, click "Relogin"
   - Scan QR code
   - Verify connected

2. **Login Account 2:**
   - Switch sang Account 2
   - Click "Relogin"
   - Scan QR code với **SỐ ĐIỆN THOẠI KHÁC**
   - Verify connected

3. **Kiểm tra cả 2 đều connected:**

```bash
# Call zalo-service API
curl -H "X-API-Key: school-zalo-service-key-2024" \
     http://localhost:3001/api/auth/sessions
```

**Expected response:**
```json
{
  "success": true,
  "sessions": [
    {
      "accountId": 1,
      "sessionId": "zalo_1",
      "isConnected": true,
      "zaloId": "422130881766855970",
      "name": "Tuấn Lệ",
      "isActive": false
    },
    {
      "accountId": 2,
      "sessionId": "zalo_2",
      "isConnected": true,
      "zaloId": "688678230773032494",
      "name": "Hoàng Anh",
      "isActive": true
    }
  ],
  "total": 2
}
```

---

## 🔄 VẤN ĐỀ 3: DATA KHÔNG RELOAD KHI SWITCH

### Nguyên nhân:

Frontend components (Friends, Groups, Messages) chưa:
1. Listen sự kiện `zalo-account-changed`
2. Reload data với accountId mới

### Giải pháp ngắn hạn:

**Sau khi switch account, refresh lại trang (F5)**

Dữ liệu sẽ load đúng theo account active.

### Giải pháp dài hạn (Cần update code):

Các files cần update:

1. **ZaloFriends.vue** - Reload friends khi switch account
2. **ZaloGroups.vue** - Reload groups khi switch account
3. **ZaloChatView.vue** - Reload messages khi switch account
4. **ZaloIndex.vue** - Coordinate reload across all tabs

**Ví dụ pattern cần implement:**

```vue
<!-- ZaloFriends.vue -->
<script setup>
import { onMounted, onUnmounted } from 'vue';

const loadFriends = async () => {
  // Load friends with active account
  const response = await axios.get('/api/zalo/friends');
  // ...
};

// Listen for account change
const handleAccountChange = () => {
  console.log('Account changed, reloading friends...');
  loadFriends();
};

onMounted(() => {
  loadFriends();
  window.addEventListener('zalo-account-changed', handleAccountChange);
});

onUnmounted(() => {
  window.removeEventListener('zalo-account-changed', handleAccountChange);
});
</script>
```

**Event được emit từ ZaloAccounts.vue (line 384-386):**
```javascript
window.dispatchEvent(new CustomEvent('zalo-account-changed', {
  detail: { accountId }
}));
```

---

## 📝 CHECKLIST

### Immediate Fixes (Làm ngay):

- [ ] Fix database: Chỉ 1 account active
  ```bash
  php fix-active-account.php
  ```

- [ ] Verify zalo-service multi-session
  ```bash
  cd zalo-service
  node enable-multi-session.js
  npm start
  ```

- [ ] Hard refresh browser
  - Ctrl+Shift+R (Windows)
  - Cmd+Shift+R (Mac)

- [ ] Verify nút "Chuyển" xuất hiện

### Testing Multi-Session:

- [ ] Login Account 1 với QR code
- [ ] Switch sang Account 2
- [ ] Login Account 2 với QR code **KHÁC**
- [ ] Verify cả 2 accounts đều connected
  ```bash
  curl -H "X-API-Key: school-zalo-service-key-2024" \
       http://localhost:3001/api/auth/sessions
  ```

- [ ] Switch qua lại giữa 2 accounts
- [ ] **Refresh page (F5)** sau khi switch để load data đúng

### Future Improvements (Optional):

- [ ] Update ZaloFriends.vue to auto-reload
- [ ] Update ZaloGroups.vue to auto-reload
- [ ] Update ZaloChatView.vue to auto-reload
- [ ] Update ZaloIndex.vue to coordinate reloads

---

## 🎯 KẾT QUẢ MONG ĐỢI

Sau khi làm theo hướng dẫn:

### ✅ Issue 1: Multi-Session
- Cả 2 accounts connected cùng lúc
- Không bị disconnect khi add account mới

### ✅ Issue 2: Nút "Chuyển"
- Account ACTIVE: Badge "Active" + không có nút "Chuyển"
- Account INACTIVE: Nút "Chuyển" hiển thị rõ ràng

### ⚠️ Issue 3: Data Reload
- **Workaround hiện tại:** Refresh page (F5) sau khi switch
- **Long-term fix:** Cần update frontend components (optional)

---

## 🆘 TRO GIÚP

Nếu vẫn gặp vấn đề:

1. **Check zalo-service logs:**
   ```bash
   cd zalo-service
   npm start
   # Xem logs khi switch account
   ```

2. **Check browser console (F12):**
   - Có errors không?
   - Event 'zalo-account-changed' có fire không?

3. **Check database:**
   ```sql
   SELECT id, name, is_active FROM zalo_accounts;
   ```

4. **Check API response:**
   ```bash
   curl -H "X-API-Key: school-zalo-service-key-2024" \
        http://localhost:3001/api/auth/sessions
   ```

---

**Hãy làm theo từng bước và báo lại kết quả!** 🚀
