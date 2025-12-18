# 🔧 FIX: Customer Settings Modal Không Load Data Lần Đầu

## ❌ Vấn Đề

**Hiện tượng:** Khi click nút Settings lần đầu tiên, modal mở nhưng không có data (danh sách trống)

**Nguyên nhân:**

### 1. Lifecycle Hook Timing Issue

```vue
<!-- ❌ TRƯỚC - Dùng onMounted -->
<script setup>
onMounted(() => {
  if (props.show) {
    loadInteractionTypes();
    loadInteractionResults();
    loadSources();
  }
});
</script>
```

**Vấn đề:**
- `onMounted()` chỉ chạy **1 lần** khi component được mount vào DOM
- Lúc component mount, `props.show` có thể là `false` (modal chưa mở)
- Khi user click Settings button → `props.show` thành `true` → nhưng `onMounted` không chạy lại
- Kết quả: Data không được load

### 2. Component Lifecycle Flow

```
1. Component được mount (onMounted chạy)
   props.show = false ❌
   → if (props.show) → false → không load data

2. User click Settings button
   props.show = true ✅
   → nhưng onMounted không chạy lại
   → data vẫn trống

3. User đóng modal
   props.show = false

4. User mở lại modal
   props.show = true
   → onMounted vẫn không chạy
   → data vẫn trống
```

---

## ✅ Giải Pháp

### Dùng `watch` Thay Vì `onMounted`

```vue
<!-- ✅ SAU - Dùng watch -->
<script setup>
import { watch } from 'vue';

// Load data khi modal được mở
watch(() => props.show, (newVal) => {
  if (newVal) {
    loadInteractionTypes();
    loadInteractionResults();
    loadSources();
  }
}, { immediate: true });
</script>
```

**Tại sao hoạt động:**
- `watch` theo dõi `props.show`
- Mỗi khi `props.show` thay đổi từ `false` → `true` → callback chạy
- `{ immediate: true }` → chạy ngay lần đầu khi component mount
- Kết quả: Data được load mỗi khi modal mở

---

## 🔍 So Sánh

### onMounted vs watch

#### onMounted
```javascript
onMounted(() => {
  // ❌ Chỉ chạy 1 lần khi component mount
  // ❌ Không chạy lại khi props thay đổi
  if (props.show) {
    loadData();
  }
});
```

**Khi nào dùng:**
- Load data 1 lần khi component mount
- Data không phụ thuộc vào props
- Ví dụ: Load static config, setup event listeners

#### watch
```javascript
watch(() => props.show, (newVal) => {
  // ✅ Chạy mỗi khi props.show thay đổi
  // ✅ Chạy ngay lần đầu nếu có immediate: true
  if (newVal) {
    loadData();
  }
}, { immediate: true });
```

**Khi nào dùng:**
- Load data khi props thay đổi
- Modal/Dialog components
- Conditional rendering
- Ví dụ: Load data khi modal mở, filter data khi search query thay đổi

---

## 📊 Flow Sau Khi Fix

```
1. Component được mount
   watch chạy với immediate: true
   props.show = false
   → if (newVal) → false → không load (OK)

2. User click Settings button
   props.show = false → true
   → watch callback chạy
   → if (newVal) → true
   → loadInteractionTypes() ✅
   → loadInteractionResults() ✅
   → loadSources() ✅
   → Data hiển thị trong modal ✅

3. User đóng modal
   props.show = true → false
   → watch callback chạy
   → if (newVal) → false → không load (OK)

4. User mở lại modal
   props.show = false → true
   → watch callback chạy lại
   → Data được refresh ✅
```

---

## 🎯 Code Changes

### Before (❌)

```vue
<script setup>
import { ref, onMounted, computed } from 'vue';

// ... other code ...

onMounted(() => {
  if (props.show) {
    loadInteractionTypes();
    loadInteractionResults();
    loadSources();
  }
});
</script>
```

### After (✅)

```vue
<script setup>
import { ref, onMounted, computed, watch } from 'vue';

// ... other code ...

// Load data khi modal được mở
watch(() => props.show, (newVal) => {
  if (newVal) {
    loadInteractionTypes();
    loadInteractionResults();
    loadSources();
  }
}, { immediate: true });
</script>
```

**Changes:**
1. ✅ Import `watch` from Vue
2. ✅ Replace `onMounted` with `watch`
3. ✅ Watch `props.show` instead of checking once
4. ✅ Add `{ immediate: true }` option

---

## 🧪 Testing

### Test Case 1: Mở Modal Lần Đầu

```bash
# 1. Reload browser
Ctrl + Shift + R

# 2. Login
admin@example.com / password

# 3. Navigate to Customers
Click "Customers" in sidebar

# 4. Click Settings button (⚙️)
Expected:
✅ Modal mở
✅ Tab "Loại tương tác" active
✅ 7 items hiển thị ngay lập tức
✅ Không có delay hoặc loading state

# 5. Switch tabs
Click "Kết quả tương tác" tab
✅ 7 results hiển thị

Click "Nguồn khách hàng" tab
✅ 9 sources hiển thị
```

### Test Case 2: Đóng và Mở Lại

```bash
# 1. Đóng modal
Click X hoặc click outside

# 2. Mở lại modal
Click Settings button

Expected:
✅ Modal mở
✅ Data vẫn hiển thị
✅ Không bị trống
```

### Test Case 3: Refresh Data Sau Khi Edit

```bash
# 1. Mở modal
Click Settings button

# 2. Edit item
Click Edit icon → Modify → Save

# 3. Đóng modal
Click X

# 4. Mở lại modal
Click Settings button

Expected:
✅ Data được refresh
✅ Changes reflected
```

---

## 📝 Key Takeaways

### 1. onMounted vs watch

**onMounted:**
- ❌ Chỉ chạy 1 lần
- ❌ Không reactive với props
- ✅ Dùng cho one-time setup

**watch:**
- ✅ Chạy mỗi khi dependency thay đổi
- ✅ Reactive với props/refs
- ✅ Dùng cho data loading based on conditions

### 2. Modal Components Best Practice

Khi làm modal/dialog components, **luôn dùng `watch`** để load data:

```vue
<script setup>
const props = defineProps({
  show: Boolean
});

// ✅ GOOD
watch(() => props.show, (isOpen) => {
  if (isOpen) {
    loadData();
  }
}, { immediate: true });

// ❌ BAD
onMounted(() => {
  if (props.show) {
    loadData();
  }
});
</script>
```

### 3. immediate Option

```javascript
watch(source, callback, { immediate: true })
```

- `immediate: true` → Chạy callback ngay lần đầu
- Hữu ích khi cần load data ngay khi component mount
- Tránh duplicate code giữa `onMounted` và `watch`

---

## 🎉 Summary

**Vấn đề:**
- ❌ Modal mở lần đầu không có data

**Nguyên nhân:**
- ❌ Dùng `onMounted` thay vì `watch`
- ❌ `onMounted` chỉ chạy 1 lần khi mount
- ❌ Không reactive với `props.show`

**Giải pháp:**
- ✅ Dùng `watch(() => props.show, ...)`
- ✅ Load data mỗi khi modal mở
- ✅ Add `{ immediate: true }` để chạy lần đầu

**Kết quả:**
- ✅ Data load ngay khi mở modal
- ✅ Data refresh mỗi lần mở
- ✅ Không còn bị trống

---

**Build thành công! Reload browser và test ngay!** 🚀

