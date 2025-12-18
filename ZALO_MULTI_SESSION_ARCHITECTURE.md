# 🔄 Zalo Multi-Session Architecture - Thiết kế & Giải pháp

## 📊 **HIỆN TRẠNG**

### Vấn đề hiện tại
- **Zalo-service** chỉ duy trì **1 session duy nhất**
- Khi thêm account mới → Account cũ bị disconnect
- Biến global `zaloClient` bị ghi đè khi login mới

### Code hiện tại ([zaloClient.js:6](zalo-service/services/zaloClient.js#L6))
```javascript
let zaloClient = null;  // ❌ Chỉ 1 instance global
let isInitialized = false;
let loginCompleted = false;
```

**Hệ quả**: Không thể duy trì nhiều Zalo accounts connected cùng lúc!

---

## ✅ **GIẢI PHÁP: MULTI-SESSION ARCHITECTURE**

### Dựa trên tài liệu zalo-api-final
Theo README của zalo-api-final, library HỖ TRỢ multi-session qua parameter `sessionId`:

```javascript
const accounts = [
  new Zalo({ sessionId: 'account1' }),
  new Zalo({ sessionId: 'account2' }),
  new Zalo({ sessionId: 'account3' })
];
```

---

## 🏗️ **KIẾN TRÚC MỚI**

### 1. Thay đổi trong zalo-service

#### a) Quản lý nhiều clients ([zaloClient.js](zalo-service/services/zaloClient.js))

**Thay đổi từ:**
```javascript
let zaloClient = null;  // Single instance
```

**Sang:**
```javascript
const zaloClients = new Map();  // Map<accountId, clientInstance>
let activeAccountId = null;     // Account hiện tại đang active

// Structure of each entry:
// {
//   client: ZaloInstance,
//   credentials: { cookie, imei, userAgent, zaloId },
//   isConnected: boolean,
//   listener: WebSocketListener,
//   keepAliveInterval: IntervalId
// }
```

#### b) API Methods cần refactor

**Hiện tại:**
```javascript
async function initializeZalo(qrCallback, forceNew = false) {
  zaloClient = new Zalo({ ... });  // ❌ Ghi đè global
}

function getZaloClient() {
  return zaloClient;  // ❌ Chỉ trả về 1 client
}

function isZaloReady() {
  return zaloClient && loginCompleted;  // ❌ Check 1 client
}
```

**Thay đổi thành:**
```javascript
async function initializeZalo(accountId, qrCallback, forceNew = false) {
  const client = new Zalo({
    sessionId: `zalo_${accountId}`,  // ✅ Unique session
    cookie: credentials.cookie,
    imei: credentials.imei,
    userAgent: credentials.userAgent,
    selfListen: true
  });

  zaloClients.set(accountId, {
    client,
    credentials,
    isConnected: false,
    listener: null,
    keepAliveInterval: null
  });

  return client;
}

function getZaloClient(accountId) {
  if (!accountId) accountId = activeAccountId;
  return zaloClients.get(accountId)?.client;
}

function isZaloReady(accountId) {
  if (!accountId) accountId = activeAccountId;
  const entry = zaloClients.get(accountId);
  return entry && entry.isConnected;
}

function switchActiveAccount(accountId) {
  if (zaloClients.has(accountId)) {
    activeAccountId = accountId;
    return true;
  }
  return false;
}

function getAllClients() {
  return Array.from(zaloClients.entries()).map(([id, entry]) => ({
    accountId: id,
    isConnected: entry.isConnected,
    zaloId: entry.credentials.zaloId
  }));
}
```

### 2. WebSocket Listeners - Mỗi account 1 listener

```javascript
async function startListener(accountId) {
  const entry = zaloClients.get(accountId);
  if (!entry) return;

  // Stop old listener if exists
  if (entry.listener) {
    entry.listener.stop();
  }

  const listener = entry.client.listen();

  listener.on('message', (message) => {
    // Forward to Laravel với accountId
    axios.post(`${LARAVEL_URL}/api/zalo/messages/receive`, {
      account_id: accountId,  // ✅ Identify which account
      ...message
    });
  });

  entry.listener = listener;
  zaloClients.set(accountId, entry);
}
```

### 3. Keep-Alive cho từng account

```javascript
function startKeepAlive(accountId) {
  const entry = zaloClients.get(accountId);
  if (!entry) return;

  // Clear old interval
  if (entry.keepAliveInterval) {
    clearInterval(entry.keepAliveInterval);
  }

  entry.keepAliveInterval = setInterval(() => {
    try {
      entry.client.keepAlive();
      console.log(`[${accountId}] Keep-alive sent`);
    } catch (error) {
      console.error(`[${accountId}] Keep-alive failed`, error);
      // Try reconnect
      reconnectAccount(accountId);
    }
  }, 45000);

  zaloClients.set(accountId, entry);
}
```

---

## 🔌 **API ENDPOINTS MỚI**

### 1. Initialize with Account ID
```javascript
// POST /api/auth/initialize
router.post('/initialize', verifyApiKey, async (req, res) => {
  const { accountId, forceNew } = req.body;

  if (!accountId) {
    return res.status(400).json({
      success: false,
      message: 'accountId is required'
    });
  }

  const result = await initializeZalo(accountId, (qr) => {
    // QR callback
  }, forceNew);

  res.json({ success: true, accountId });
});
```

### 2. Switch Active Account
```javascript
// POST /api/auth/switch
router.post('/switch', verifyApiKey, async (req, res) => {
  const { accountId } = req.body;

  if (switchActiveAccount(accountId)) {
    res.json({
      success: true,
      activeAccount: accountId
    });
  } else {
    res.status(404).json({
      success: false,
      message: 'Account not found or not connected'
    });
  }
});
```

### 3. Get All Sessions
```javascript
// GET /api/auth/sessions
router.get('/sessions', verifyApiKey, (req, res) => {
  const sessions = getAllClients();
  res.json({
    success: true,
    sessions,
    activeAccountId
  });
});
```

### 4. Disconnect Account
```javascript
// POST /api/auth/disconnect/:accountId
router.post('/disconnect/:accountId', verifyApiKey, async (req, res) => {
  const { accountId } = req.params;
  const entry = zaloClients.get(accountId);

  if (entry) {
    // Stop listener
    if (entry.listener) entry.listener.stop();

    // Clear keep-alive
    if (entry.keepAliveInterval) clearInterval(entry.keepAliveInterval);

    // Remove from map
    zaloClients.delete(accountId);

    res.json({ success: true });
  } else {
    res.status(404).json({
      success: false,
      message: 'Account not found'
    });
  }
});
```

---

## 📝 **THAY ĐỔI TRONG LARAVEL**

### 1. ZaloNotificationService

**Thêm accountId vào mọi request:**
```php
public function sendMessage($accountId, $userId, $message) {
    $response = Http::withHeaders([
        'X-API-Key' => $this->apiKey,
        'X-Account-Id' => $accountId,  // ✅ Identify account
    ])->post("{$this->baseUrl}/api/messages/send", [
        'userId' => $userId,
        'message' => $message,
    ]);

    return $response->json();
}
```

### 2. ZaloController - setActiveAccount

```php
public function setActiveAccount(Request $request) {
    $accountId = $request->input('account_id');
    $account = ZaloAccount::find($accountId);

    if (!$account) {
        return response()->json(['error' => 'Account not found'], 404);
    }

    // Call zalo-service to switch
    $response = $this->zalo->switchAccount($accountId);

    if ($response['success']) {
        // Update database
        ZaloAccount::where('is_active', true)->update(['is_active' => false]);
        $account->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'account' => $account
        ]);
    }

    return response()->json(['error' => 'Failed to switch account'], 500);
}
```

---

## 🗄️ **QUẢN LÝ CREDENTIALS**

### Lưu credentials cho từng account

**Option 1: Database (Laravel)**
- Mỗi account có cookie, imei, userAgent trong DB
- Khi init, Laravel gửi credentials cho zalo-service
- Zalo-service sử dụng để tạo Zalo instance

**Option 2: File-based (zalo-service)**
```javascript
// Save credentials to file
function saveCredentials(accountId, credentials) {
  const filePath = path.join(__dirname, 'sessions', `${accountId}.json`);
  fs.writeFileSync(filePath, JSON.stringify({
    cookie: credentials.cookie,
    imei: credentials.imei,
    userAgent: credentials.userAgent,
    zaloId: credentials.zaloId,
    savedAt: new Date().toISOString()
  }));
}

// Load credentials from file
function loadCredentials(accountId) {
  const filePath = path.join(__dirname, 'sessions', `${accountId}.json`);
  if (fs.existsSync(filePath)) {
    return JSON.parse(fs.readFileSync(filePath, 'utf8'));
  }
  return null;
}

// On startup, load all saved sessions
async function restoreAllSessions() {
  const sessionsDir = path.join(__dirname, 'sessions');
  if (!fs.existsSync(sessionsDir)) {
    fs.mkdirSync(sessionsDir);
    return;
  }

  const files = fs.readdirSync(sessionsDir);
  for (const file of files) {
    if (file.endsWith('.json')) {
      const accountId = file.replace('.json', '');
      const credentials = loadCredentials(accountId);

      if (credentials) {
        await initializeZalo(accountId, null, false);
        console.log(`✅ Restored session for account ${accountId}`);
      }
    }
  }
}
```

---

## 🔄 **FLOW SAU KHI IMPLEMENT**

### Add Account
```
1. User click "Add Account"
   ↓
2. Laravel: POST /api/zalo/initialize { forceNew: true, accountId: null }
   ↓
3. Zalo-service: Tạo QR, chờ scan
   ↓
4. User scan QR
   ↓
5. Zalo-service: Login success → Lưu credentials
   ↓
6. Laravel: Save account to DB (với credentials mới)
   ↓
7. Zalo-service: Add to zaloClients Map
   ↓
8. ✅ Account mới connected, account cũ VẪN CONNECTED
```

### Switch Account
```
1. User click "Switch" trên account B
   ↓
2. Laravel: POST /api/zalo/accounts/active { account_id: B }
   ↓
3. Laravel: Gọi zalo-service POST /api/auth/switch { accountId: B }
   ↓
4. Zalo-service: activeAccountId = B
   ↓
5. ✅ Tất cả API calls sẽ dùng account B
   ↓
6. Account A VẪN CONNECTED, chỉ không active
```

### Send Message
```
1. User gửi tin nhắn
   ↓
2. Laravel: Xác định account nào đang active
   ↓
3. Laravel: POST /api/messages/send với X-Account-Id header
   ↓
4. Zalo-service: Lấy client từ zaloClients.get(accountId)
   ↓
5. Client.sendMessage(...)
```

---

## ⚠️ **CHÚ Ý & GIỚI HẠN**

### 1. Resource Usage
- Mỗi account cần:
  - 1 Zalo client instance (~10-20MB RAM)
  - 1 WebSocket connection
  - 1 Keep-alive interval

→ **Giới hạn khuyến nghị**: Tối đa 5-10 accounts cùng lúc

### 2. Zalo Rate Limiting
- Zalo có thể giới hạn số lượng requests từ cùng 1 IP
- Nhiều accounts có thể trigger anti-spam

→ **Giải pháp**: Implement request queue, rate limiting

### 3. Session Expiration
- Cookie có thể expire sau 7-30 ngày
- Cần có cơ chế auto-relogin

→ **Giải pháp**: Check session validity định kỳ, prompt relogin khi cần

### 4. File Storage
- Sessions folder có thể lớn nếu lưu nhiều accounts
- Cần cleanup sessions cũ không dùng

---

## 📋 **ROADMAP IMPLEMENTATION**

### Phase 1: Core Multi-Session (Ưu tiên cao)
- [ ] Refactor zaloClient.js sang Map structure
- [ ] Implement sessionId parameter
- [ ] Update all API endpoints với accountId
- [ ] Test với 2 accounts simultaneously

### Phase 2: Persistence (Ưu tiên trung bình)
- [ ] Implement file-based session storage
- [ ] Auto-restore sessions on startup
- [ ] Credentials encryption

### Phase 3: Advanced Features (Ưu tiên thấp)
- [ ] Session health monitoring
- [ ] Auto-relogin khi session expire
- [ ] Request queue & rate limiting
- [ ] Resource usage monitoring

---

## 🎯 **KẾT LUẬN**

**Có thể implement multi-session!** Library zalo-api-final hỗ trợ qua parameter `sessionId`.

**Thời gian ước tính**:
- Phase 1 (Core): 2-3 ngày
- Phase 2 (Persistence): 1-2 ngày
- Phase 3 (Advanced): 2-3 ngày

**Tổng**: ~5-8 ngày công để hoàn thiện

---

**Bắt đầu với Phase 1?** 🚀