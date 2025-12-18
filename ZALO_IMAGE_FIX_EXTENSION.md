# 🔧 Fix: Giữ nguyên extension khi download image

## ❌ **Vấn đề trước đây:**

```javascript
// File được lưu với extension .tmp
const fileName = `image_${Date.now()}_xxx.tmp`;

// Khi upload lên Zalo:
uploadAttachment(file.tmp) → Zalo nhận diện là "fileType: 'others'"

// Response KHÔNG CÓ:
{
  normalUrl: undefined,  ← KHÔNG CÓ!
  hdUrl: undefined,      ← KHÔNG CÓ!
  photoId: undefined     ← KHÔNG CÓ!
}
```

→ **Zalo KHÔNG nhận diện file `.tmp` là image!**

---

## ✅ **Fix đã áp dụng:**

### 1. Giữ nguyên extension từ URL
```javascript
// Extract extension from URL
const urlPath = new URL(imageSource).pathname;
// → /storage/zalo/images/1/20251114133738_z7042...jpg

const ext = path.extname(urlPath);  // → '.jpg'

const fileName = `image_${Date.now()}_xxx${ext}`;  // → image_xxx.jpg
```

### 2. Tăng timeout
```php
// Laravel: app/Services/ZaloNotificationService.php
$response = Http::timeout(120)->withHeaders([  // 90s → 120s
```

### 3. Validation cho extension
```javascript
if (['.jpg', '.jpeg', '.png', '.gif', '.webp', '.avif'].includes(ext.toLowerCase())) {
  // Extension hợp lệ
} else {
  ext = '.jpg';  // Fallback to .jpg
}
```

---

## 🧪 **Test lại:**

### Bước 1: Hard refresh browser
```
Ctrl + Shift + R
```

### Bước 2: Chọn ảnh và click "Gửi"

### Bước 3: Quan sát logs

#### ✅ zalo-service logs (QUAN TRỌNG):
```javascript
📝 [zalo-service] Temp file will be saved as:
  fileName: 'image_1763127458988_xxx.jpg'  ← .jpg, KHÔNG PHẢI .tmp!
  extension: '.jpg'
  
✅ [zalo-service] Image downloaded to: 
  C:\...\temp\image_xxx.jpg  ← .jpg extension!

📤 [zalo-service] Step 1: Uploading image to Zalo CDN...

✅ [zalo-service] Upload result:
  firstItem: {
    fileType: 'image',  ← PHẢI LÀ 'image', KHÔNG PHẢI 'others'!
    normalUrl: 'https://f20-zpc.zdn.vn/jpg/...',  ← PHẢI CÓ!
    hdUrl: 'https://f20-zpc.zdn.vn/...',
    photoId: '...',  ← PHẢI CÓ!
    width: 1920,
    height: 1080,
  }

📎 [zalo-service] Extracted from upload:
  cdnUrl: 'https://f20-zpc.zdn.vn/jpg/...'  ← PHẢI CÓ!
  photoId: '...'  ← PHẢI CÓ!

uploadedCdnUrl: 'https://f20-zpc.zdn.vn/...'  ← KHÔNG PHẢI 'NOT UPLOADED'!
```

#### ✅ Database:
```sql
SELECT content, media_url 
FROM zalo_messages 
ORDER BY id DESC LIMIT 1;

-- Kết quả mong đợi:
-- content: 'https://f20-zpc.zdn.vn/jpg/...'
-- media_url: 'https://f20-zpc.zdn.vn/jpg/...'
```

---

## 🎯 **Kỳ vọng sau fix:**

### Upload result type:
- **Trước**: `fileType: 'others'` ❌
- **Sau**: `fileType: 'image'` ✅

### CDN URL:
- **Trước**: `cdnUrl: null` ❌
- **Sau**: `cdnUrl: 'https://f20-zpc.zdn.vn/...'` ✅

### Photo ID:
- **Trước**: `photoId: null` ❌
- **Sau**: `photoId: '...'` ✅

### Timeout:
- **Trước**: 90s (có thể timeout) ❌
- **Sau**: 120s (đủ thời gian) ✅

---

## ⚠️ **Nếu vẫn lỗi:**

### Nếu `fileType: 'others'` vẫn xuất hiện:
→ Check xem file extension có đúng không:
```javascript
📝 Temp file will be saved as:
  extension: '???'  ← Phải là .jpg, .png, etc
```

### Nếu vẫn timeout:
→ Image quá lớn (> 10MB)
→ Check file size trong Laravel log

### Nếu `normalUrl` vẫn undefined:
→ `uploadAttachment` có thể không work với API version hiện tại
→ Cần chuyển sang phương án khác (đợi WebSocket callback)

---

## 📊 Debug commands:

### Check upload result structure:
```javascript
// Trong zalo-service logs, tìm:
✅ [zalo-service] Upload result:
  firstItem: { ... }  ← Copy toàn bộ object này
```

### Check extension extraction:
```javascript
📝 [zalo-service] Temp file will be saved as:
  extension: ???  ← Phải là '.jpg', '.png', etc
```

---

## 🚀 READY TO TEST AGAIN!
**Hard refresh → Chọn ảnh → Gửi → Xem logs `fileType` & `normalUrl`!**

