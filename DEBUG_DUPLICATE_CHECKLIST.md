# 🔍 Debug Duplicate Messages - Checklist

## ✅ **Đã fix:**

### 1. Database ✅
- Empty messages đã bị xóa
- Chỉ còn 1 record per message

### 2. Backend ✅  
- `sendMessage()` - KHÔNG save
- `replyMessage()` - KHÔNG save
- `receiveMessage()` - SAVE 1 lần duy nhất

### 3. Frontend ✅
- `sendTextMessage()` - KHÔNG push
- `uploadImage()` - KHÔNG push
- `sendReply()` - KHÔNG push
- `onMessage()` - PUSH 1 lần duy nhất

---

## 🔍 **Nếu VẪN thấy duplicate, check:**

### A. Browser Cache
```
1. Hard refresh: Ctrl + Shift + R
2. Clear browser cache
3. Open DevTools → Application → Clear storage
```

### B. Multiple Tabs
```
1. Đóng TẤT CẢ tabs của app
2. Chỉ mở 1 tab duy nhất
3. Test lại
```

### C. WebSocket Listener Duplicate
Có thể listener bị register nhiều lần!

**Check trong Chrome DevTools:**
```javascript
// Console
zaloSocket.listeners // Xem có bao nhiêu listeners

// Hoặc thêm log trong onMessage callback:
console.log('📨 onMessage triggered', {
  messageId: data.message?.id,
  stackTrace: new Error().stack // Để thấy được gọi từ đâu
});
```

### D. Component Mount Multiple Times
```
// Thêm log trong onMounted:
onMounted(() => {
  console.log('🔵 ZaloChatView mounted for:', props.item?.id);
  
  // ... existing code ...
});

// Thêm log trong onUnmounted:
onUnmounted(() => {
  console.log('🔴 ZaloChatView unmounted for:', props.item?.id);
  
  // ... cleanup ...
});
```

---

## 🧪 **Test Steps:**

### 1. Clean start
```
1. Đóng TẤT CẢ browser tabs
2. Clear cache (Ctrl + Shift + Delete)
3. Mở 1 tab mới duy nhất
4. Login
5. Chọn conversation
```

### 2. Send test message
```
1. Gửi 1 text message
2. Mở DevTools Console
3. Count: Có bao nhiêu log "📨 onMessage triggered"?
4. Check UI: Có bao nhiêu messages hiển thị?
```

### 3. Check database
```sql
-- Kiểm tra có duplicate trong DB không?
SELECT id, message_id, content, sent_at 
FROM zalo_messages 
WHERE recipient_id = 'YOUR_RECIPIENT_ID'
ORDER BY id DESC 
LIMIT 5;

-- Nếu có 2 records cùng lúc → Backend issue
-- Nếu có 1 record nhưng UI show 2 → Frontend issue
```

---

## 🎯 **Expected Results:**

### Database:
```
1 message sent → 1 record in DB ✅
```

### Frontend:
```
1 message sent → 1 message in UI ✅
```

### Logs:
```
📨 onMessage triggered (1 lần duy nhất) ✅
```

---

## ⚠️ **Common Issues:**

### Issue 1: Browser caching old JS
**Solution:** 
```
1. Hard refresh (Ctrl + Shift + R)
2. hoặc: npm run build lại
```

### Issue 2: Multiple component instances
**Solution:**
```
Check parent component không mount ZaloChatView nhiều lần
```

### Issue 3: Event listener không cleanup
**Solution:**
```javascript
// Phải unsubscribe khi unmount:
onUnmounted(() => {
  if (unsubscribeMessage) unsubscribeMessage();
  if (unsubscribeReaction) unsubscribeReaction();
});
```

---

## 📊 **Debug Commands:**

### Check recent messages:
```bash
cd C:\xampp\htdocs\school
php artisan tinker --execute="echo json_encode(DB::table('zalo_messages')->orderBy('id', 'desc')->limit(10)->get(['id', 'message_id', 'content', 'type', 'sent_at'])->toArray());"
```

### Count messages by recipient:
```bash
php artisan tinker --execute="echo DB::table('zalo_messages')->where('recipient_id', 'RECIPIENT_ID')->count();"
```

### Find duplicates:
```sql
SELECT message_id, COUNT(*) as count
FROM zalo_messages
GROUP BY message_id
HAVING count > 1
ORDER BY count DESC;
```

---

## 🚀 **FINAL TEST:**

1. ✅ Close ALL tabs
2. ✅ Clear cache
3. ✅ Hard refresh
4. ✅ Open 1 tab only
5. ✅ Send 1 test message
6. ✅ Check: UI shows 1 message ONLY
7. ✅ Check: DB has 1 record ONLY

**If still duplicate → Share screenshot + DB query result!**

