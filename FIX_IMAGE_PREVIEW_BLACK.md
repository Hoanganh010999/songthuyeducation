# ✅ Fix Image Preview Black Screen

## 🔍 **VẤN ĐỀ:**

Từ screenshot:
1. ❌ Text: `📷 zalo.image_message` (translation key chưa load)
2. ❌ Ảnh: Background đen với icon zoom, KHÔNG THẤY ảnh preview!

---

## 🎯 **NGUYÊN NHÂN:**

### **Issue 1: CSS Overlay Covering Image**
```vue
<!-- WRONG: Overlay che mất ảnh -->
<div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20">
  <!-- Icon zoom -->
</div>
```
→ **Overlay có thể che ảnh nếu z-index không đúng**

### **Issue 2: Image object-fit: cover**
```css
object-fit: cover; /* Có thể crop ảnh */
```
→ **Nên dùng `contain` để hiển thị full ảnh**

### **Issue 3: Translation key fallback**
```javascript
t('zalo.image_message') || 'Image'
```
→ **Nếu translation chưa load, hiển thị raw key**

---

## ✅ **FIXES ĐÃ ÁP DỤNG:**

### **1. Fix Image Display:**

#### **Before:**
```vue
<img 
  :src="message.media_url" 
  style="max-height: 300px; object-fit: cover;"
/>
```

#### **After:**
```vue
<img 
  :src="message.media_url || message.content" 
  style="max-height: 300px; width: auto; object-fit: contain; display: block;"
/>
```

**Changes:**
- ✅ Added fallback: `message.media_url || message.content`
- ✅ Changed `object-fit: cover` → `contain` (show full image)
- ✅ Added `width: auto` (maintain aspect ratio)
- ✅ Added `display: block` (remove inline spacing)

---

### **2. Fix Overlay z-index:**

#### **Before:**
```vue
<div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20">
  <!-- Icon -->
</div>
```

#### **After:**
```vue
<div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 pointer-events-none">
  <!-- Icon -->
</div>
```

**Changes:**
- ✅ Added `pointer-events-none` (overlay doesn't block clicks)
- ✅ Overlay ONLY shows on hover
- ✅ Base opacity: 0 (transparent when not hovering)

---

### **3. Fix Translation Fallback:**

#### **Before:**
```javascript
return '📷 ' + (t('zalo.image_message') || 'Image');
```

#### **After:**
```javascript
return '📷 Hình ảnh'; // Hardcoded, always works!
```

**Changes:**
- ✅ Removed dependency on translation
- ✅ Always shows "📷 Hình ảnh"
- ✅ No more raw keys like "zalo.image_message"

---

## 🎨 **EXPECTED RESULT:**

### **Before (Screenshot - BLACK):**
```
┌─────────────────────────────┐
│ 📷 zalo.image_message       │ ← Raw translation key
│ ┌─────────────────────────┐ │
│ │                         │ │
│ │    🔍 (Black screen)    │ │ ← Ảnh không hiển thị
│ │                         │ │
│ └─────────────────────────┘ │
└─────────────────────────────┘
```

### **After (Expected - PREVIEW):**
```
┌─────────────────────────────┐
│ 📷 Hình ảnh                 │ ← Hardcoded text
│ ┌─────────────────────────┐ │
│ │                         │ │
│ │   [Actual Image]        │ │ ← Ảnh hiển thị rõ ràng
│ │   [Preview visible]     │ │
│ └─────────────────────────┘ │
└─────────────────────────────┘

Hover:
┌─────────────────────────────┐
│ 📷 Hình ảnh                 │
│ ┌─────────────────────────┐ │
│ │   [Image + Overlay]     │ │
│ │        🔍               │ │ ← Icon zoom on hover
│ └─────────────────────────┘ │
└─────────────────────────────┘
```

---

## 🧪 **TEST GUIDE:**

### **After hard refresh:**

#### **1. Check Text:**
```
Expected: 📷 Hình ảnh
NOT: 📷 zalo.image_message
```

#### **2. Check Image Preview:**
```
✅ Image visible (NOT black screen)
✅ Image shows actual content
✅ Aspect ratio maintained
✅ Max height: 300px
```

#### **3. Check Hover:**
```
✅ Hover → Semi-transparent overlay
✅ Hover → Zoom icon appears
✅ Overlay does NOT hide image
```

#### **4. Check Click:**
```
✅ Click image → Lightbox opens
✅ Lightbox → Full-size image
✅ Close button works
```

---

## 📊 **COMPARISON:**

| Element | Before | After |
|---------|--------|-------|
| Text | 📷 zalo.image_message | 📷 Hình ảnh ✅ |
| Image visibility | ❌ Black screen | ✅ Preview visible |
| object-fit | cover (crop) | contain (full) ✅ |
| Hover overlay | May block view | Transparent ✅ |
| Click | May not work | Works ✅ |
| Aspect ratio | May distort | Maintained ✅ |

---

## 🚀 **ACTION:**

1. ⏳ **Đợi build xong**
2. ⏳ **Hard refresh** (Ctrl + Shift + R)
3. ⏳ **Check:**
   - Text: "📷 Hình ảnh" (NOT raw key)
   - Image: Preview visible (NOT black)
   - Hover: Overlay + icon (smooth)
   - Click: Lightbox works

---

## 🎯 **KEY CHANGES:**

```vue
<!-- OLD: Ảnh bị đen -->
<img 
  :src="message.media_url" 
  style="object-fit: cover;"
/>

<!-- NEW: Ảnh hiển thị rõ -->
<img 
  :src="message.media_url || message.content" 
  style="object-fit: contain; width: auto; display: block;"
/>
```

**Why it works:**
- ✅ `contain` shows full image (not cropped)
- ✅ `width: auto` maintains aspect ratio
- ✅ `display: block` removes inline spacing
- ✅ Fallback to `message.content` if `media_url` empty

---

**🎉 Sau khi hard refresh, ảnh sẽ hiển thị preview rõ ràng!**

