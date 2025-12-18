# 🧪 Test Gửi Ảnh Zalo - Phương án mới

## ✅ Thay đổi đã thực hiện

### Trước (Chậm & Không có CDN URL):
```javascript
1. Laravel → zalo-service: send public URL
2. zalo-service: download từ URL
3. zalo-service → Zalo: send image file
4. Zalo: upload to CDN (không trả về CDN URL trong response)
5. Database: lưu local URL ❌
```

### Sau (Nhanh & Có CDN URL ngay lập tức):
```javascript
1. Laravel → zalo-service: send public URL
2. zalo-service: download từ URL (nếu cần)
3. zalo-service → Zalo: uploadAttachment() TRƯỚC ✅
   → Nhận ngay CDN URL: https://f20-zpc.zdn.vn/jpg/...
4. zalo-service → Zalo: sendMessage() với image đã upload
5. zalo-service → Laravel: trả về msgId + uploadedCdnUrl
6. Database: lưu Zalo CDN URL ngay ✅
```

---

## 🧪 Các bước test

### Bước 1: Hard refresh browser
```
Ctrl + Shift + R
```

### Bước 2: Chọn ảnh và click "Gửi"

### Bước 3: Quan sát logs

#### ✅ Laravel logs (storage/logs/laravel.log):
```
[Zalo] Converted file path to public URL
  file_path: C:\xampp\...
  public_url: http://127.0.0.1:8000/storage/zalo/images/...
  
[Zalo] Sending image
  final_image_url: http://127.0.0.1:8000/storage/...
```

#### ✅ zalo-service logs:
```
📥 [zalo-service] POST /api/message/send-image received
  hasImageUrl: true
  
📥 [zalo-service] Downloading image from URL: http://...

✅ [zalo-service] Image downloaded to: C:\...\temp\image_...

📤 [zalo-service] Step 1: Uploading image to Zalo CDN...

✅ [zalo-service] Upload result:
  hasResult: true
  isArray: true
  length: 1
  firstItem: { normalUrl: 'https://f20-zpc.zdn.vn/jpg/...', photoId: '...', ... }

📎 [zalo-service] Extracted from upload:
  cdnUrl: 'https://f20-zpc.zdn.vn/jpg/...'
  photoId: '...'

📤 [zalo-service] Step 2: Sending message with uploaded image...

✅ [zalo-service] Image sent successfully:
  msgId: '7224...'
  uploadedCdnUrl: 'https://f20-zpc.zdn.vn/jpg/...' ← QUAN TRỌNG!
  photoId: '...'

📝 [zalo-service] Stored image URL mapping:
  zaloCdnUrl: 'https://f20-zpc.zdn.vn/jpg/...'
  
🧹 [zalo-service] Temporary image file deleted
```

#### ✅ Database (zalo_messages):
```sql
SELECT id, message_id, content, content_type, media_url 
FROM zalo_messages 
ORDER BY id DESC LIMIT 1;

-- Kết quả mong đợi:
-- content: 'https://f20-zpc.zdn.vn/jpg/...'
-- content_type: 'image'
-- media_url: 'https://f20-zpc.zdn.vn/jpg/...'
```

---

## 🎯 Kỳ vọng

### Tốc độ:
- **Trước**: 30-90 giây (phải đợi WebSocket callback)
- **Sau**: 5-15 giây (upload ngay, không phải đợi)

### Database:
- **Trước**: Local URL (C:\xampp\... hoặc http://127.0.0.1:8000/...)
- **Sau**: Zalo CDN URL (https://f20-zpc.zdn.vn/...)

### User Experience:
- Image hiển thị ngay trong chat
- Không cần reload page
- URL ổn định (Zalo CDN, không phụ thuộc localhost)

---

## ⚠️ Nếu gặp lỗi

### Lỗi "uploadedCdnUrl: 'NOT UPLOADED'"
→ `uploadAttachment()` không trả về CDN URL
→ Check zalo-service logs để xem upload result

### Lỗi "Cannot read properties of undefined"
→ `uploadResult` hoặc `uploadResult[0]` null
→ Check upload result logs để debug

### Lỗi timeout
→ Upload quá lâu
→ Check image file size (nên < 5MB)

---

## 📊 Debug commands

### Check message mới nhất:
```bash
cd C:\xampp\htdocs\school
php artisan tinker --execute="echo json_encode(DB::table('zalo_messages')->orderBy('id', 'desc')->first(['id', 'message_id', 'content', 'content_type', 'media_url']));"
```

### Check messages với CDN URL:
```bash
php artisan tinker --execute="echo json_encode(DB::table('zalo_messages')->where('content', 'like', '%zdn.vn%')->orderBy('id', 'desc')->limit(5)->get(['id', 'message_id', 'content']));"
```

### Check zalo-service logs (real-time):
```bash
# Watch zalo-service console output
```

---

## 🚀 READY TO TEST!
**Hard refresh browser → Chọn ảnh → Click Gửi → Xem logs!**

