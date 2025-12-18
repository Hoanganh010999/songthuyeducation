# ✅ Fix Join Room Duplicate - ROOT CAUSE FOUND!

## 🎯 **VẤN ĐỀ TÌM RA:**

### Console logs cho thấy:
```javascript
📥 Joined Zalo account room: 1
📥 Joined Zalo conversation room: zalo:1:2269883545780343929
📡 Socket already connected  
📥 Joined Zalo account room: 1                              ← DUPLICATE!
📥 Joined Zalo conversation room: zalo:1:2269883545780343929 ← DUPLICATE!
```

→ **Join room 2 lần → Listener register 2 lần → Message push 2 lần!**

---

## 🔍 **NGUYÊN NHÂN:**

### Trong `ZaloChatView.vue`:

#### 1. `watch(() => props.item)` - Chạy khi component mount
```javascript
watch(() => props.item, (newItem, oldItem) => {
  // oldItem = undefined (lần đầu)
  // newItem = selected item
  
  // Join conversation ← LẦN 1
  zaloSocket.joinConversation(accountId, newItem.id);
});
```

#### 2. `onMounted()` - Chạy ngay sau watch
```javascript
onMounted(() => {
  // Join conversation ← LẦN 2
  zaloSocket.joinConversation(accountId, props.item.id);
  
  // Register listener
  const unsubscribeMessage = zaloSocket.onMessage(...);
});
```

→ **KẾT QUẢ: Join 2 lần, nhưng listener chỉ register 1 lần**
→ **NHƯNG: Socket.IO có thể emit message 2 lần nếu join room 2 lần!**

---

## ✅ **FIX:**

### Updated `watch()`:
```javascript
watch(() => props.item, (newItem, oldItem) => {
  // Skip if oldItem is undefined (initial mount)
  if (!oldItem || !oldItem.id) {
    console.log('⏭️ Initial mount, skipping (onMounted will handle)');
    return;
  }
  
  // Skip if same item
  if (newItem?.id === oldItem?.id) return;
  
  // Leave old room
  if (oldItem?.id) {
    zaloSocket.leaveConversation(accountId, oldItem.id);
  }
  
  // Join new room (ONLY when switching conversations)
  if (newItem?.id) {
    zaloSocket.joinConversation(accountId, newItem.id);
    loadMessages();
  }
});
```

### `onMounted()` remains the same:
```javascript
onMounted(() => {
  // Connect to WebSocket
  zaloSocket.connect();
  
  // Join account room
  if (accountId) {
    zaloSocket.joinAccount(accountId);
  }
  
  // Join conversation room (ONLY ONCE)
  if (props.item?.id && accountId) {
    zaloSocket.joinConversation(accountId, props.item.id);
  }
  
  // Register listener (ONLY ONCE)
  const unsubscribeMessage = zaloSocket.onMessage(...);
  
  // Load messages (ONLY ONCE)
  loadMessages();
});
```

---

## 🎯 **EXPECTED BEHAVIOR:**

### On component mount:
```javascript
✅ WebSocket connected
📥 Joined Zalo account room: 1                    ← 1 LẦN DUY NHẤT
📥 Joined Zalo conversation room: zalo:1:xxx      ← 1 LẦN DUY NHẤT
🔵 [ZaloChatView] Component mounted for: xxx
```

### On conversation switch:
```javascript
👁️ [ZaloChatView] props.item changed
👋 [ZaloChatView] Leaving old conversation: xxx
👋 [ZaloChatView] Joining new conversation: yyy
📥 Left conversation room: zalo:1:xxx
📥 Joined Zalo conversation room: zalo:1:yyy
```

### On message received:
```javascript
📨 [ZaloChatView] onMessage triggered              ← 1 LẦN DUY NHẤT
✅ [ZaloChatView] Adding new message to UI: 135   ← 1 LẦN DUY NHẤT
```

---

## 🧪 **TEST STEPS:**

### 1. Wait for npm build to finish

### 2. **IMPORTANT: Clear browser cache!**
```
Method 1: Hard refresh
- Ctrl + Shift + R

Method 2: Clear all
- F12 → Application → Clear site data
- Reload page

Method 3: Incognito
- Ctrl + Shift + N
- Open app in incognito mode
```

### 3. Open DevTools Console (F12)

### 4. Select a conversation

### 5. Check logs - Should see:
```
✅ WebSocket connected
📥 Joined Zalo account room: 1          ← CHỈ 1 LẦN!
📥 Joined Zalo conversation room: ...   ← CHỈ 1 LẦN!
🔵 [ZaloChatView] Component mounted     ← NEW LOG!
```

### 6. Send an image

### 7. Check logs - Should see:
```
📨 [ZaloChatView] onMessage triggered   ← NEW LOG, CHỈ 1 LẦN!
✅ [ZaloChatView] Adding new message    ← NEW LOG, CHỈ 1 LẦN!
```

### 8. Check UI - Should see:
```
1 image in chat ✅
NOT 2 images ✅
```

---

## ⚠️ **CRITICAL:**

**Nếu vẫn KHÔNG THẤY các logs mới:**
- `🔵 [ZaloChatView] Component mounted`
- `📨 [ZaloChatView] onMessage triggered`
- `✅ [ZaloChatView] Adding new message`

→ **Browser đang cache code cũ!**

**PHẢI LÀM:**
1. Wait for `npm run build` to finish
2. **Hard refresh: Ctrl + Shift + R** (3 lần liên tiếp)
3. Or **Clear site data** in DevTools
4. Or **Use Incognito mode**

---

## 📊 **EXPECTED RESULTS:**

| Test | Before Fix | After Fix |
|------|------------|-----------|
| Join account room | 2 times | 1 time ✅ |
| Join conversation room | 2 times | 1 time ✅ |
| onMessage triggered | 2 times | 1 time ✅ |
| Message in UI | 2 messages | 1 message ✅ |
| Message in DB | 1 record | 1 record ✅ |

---

## 🚀 **ACTION ITEMS:**

1. ✅ Code fixed (watch now skips initial mount)
2. ⏳ npm run build (running...)
3. ⏳ Hard refresh browser (MUST DO!)
4. ⏳ Test and verify logs
5. ⏳ Confirm no duplicates

---

**After hard refresh, share NEW console logs to confirm fix!**

