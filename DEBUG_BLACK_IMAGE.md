# 🔍 DEBUG: Ảnh vẫn đen - Black Screen

## ✅ **GOOD NEWS:**
Text đã đúng: "📷 Hình ảnh" (không còn raw key)
→ Code mới đã được load!

## ❌ **BAD NEWS:**
Ảnh vẫn hiển thị đen (black background)
→ Image không load được!

---

## 🔍 **POSSIBLE CAUSES:**

### **1. Image URL không hợp lệ hoặc expired**
- Zalo CDN URLs có thể expire sau một thời gian
- URL format: `https://f21-zpc.zdn.vn/...`

### **2. CORS Issue**
- Browser block ảnh từ external domain

### **3. Image chưa load xong**
- Loading slow, chưa render

### **4. CSS Issue**
- Background đen che mất ảnh

---

## 🧪 **DEBUG STEPS:**

### **Step 1: Check Console (IMPORTANT!)**
```
1. F12 → Console tab
2. Reload page
3. Look for errors:
   - "Failed to load resource"
   - "CORS policy"
   - "403 Forbidden"
   - "404 Not Found"
```

### **Step 2: Check Network Tab**
```
1. F12 → Network tab
2. Filter: Img
3. Click vào conversation có ảnh
4. Check image requests:
   - Status: 200 OK ✅
   - Status: 403/404 ❌
   - Status: (failed) ❌
```

### **Step 3: Check Image Element**
```
1. Right-click vào ảnh đen
2. "Inspect Element"
3. Check <img> tag:
   - Has src attribute?
   - src value valid?
   - naturalWidth > 0?
```

### **Step 4: Test Image URL Directly**
```
1. Right-click ảnh → Copy image address
2. Paste vào browser address bar
3. Press Enter
4. Does image load?
   - Yes: CSS issue ✅
   - No: URL expired/invalid ❌
```

---

## 🚨 **IMMEDIATE DEBUG:**

**HAY LÀM NGAY - Mở Console và share screenshots:**

### **1. Open Console:**
```
F12 → Console tab
```

### **2. Paste this debug code:**
```javascript
// Check tất cả images trong page
document.querySelectorAll('img').forEach((img, i) => {
  console.log(`Image ${i}:`, {
    src: img.src,
    naturalWidth: img.naturalWidth,
    naturalHeight: img.naturalHeight,
    complete: img.complete,
    error: img.onerror ? 'Has error handler' : 'No error handler'
  });
});
```

### **3. Share output với tôi!**

---

## 🎯 **LIKELY ISSUE: Zalo CDN URL Expired**

Zalo CDN URLs có thể expire! Format:
```
https://f21-zpc.zdn.vn/411359770648413033/93f6563190f71ca945e6.jpg
```

**Solution:**
1. Gửi ảnh MỚI (để có URL mới)
2. Check xem ảnh mới có hiển thị không
3. Nếu vẫn đen → Other issue
4. Nếu mới OK, cũ đen → CDN expired

---

## 🔧 **TEMP FIX: Add Error Handling**

Để debug, tôi sẽ add error handler:

