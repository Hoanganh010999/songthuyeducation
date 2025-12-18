# 🐛 Debug Frontend - Trang Trắng

## Vấn đề: Trang trắng khi truy cập

### Các bước kiểm tra:

#### 1. Mở Browser Console (F12)
- Chrome: F12 → Console tab
- Firefox: F12 → Console tab
- Edge: F12 → Console tab

Kiểm tra xem có lỗi JavaScript không?

#### 2. Kiểm tra Network Tab
- F12 → Network tab
- Reload trang (Ctrl+F5)
- Kiểm tra:
  - `app.js` có load được không? (Status 200?)
  - `app.css` có load được không? (Status 200?)

#### 3. Các lỗi thường gặp:

**Lỗi 1: "Failed to fetch dynamically imported module"**
```
Nguyên nhân: Vite build không đúng
Giải pháp: npm run build
```

**Lỗi 2: "Cannot find module"**
```
Nguyên nhân: Thiếu dependencies
Giải pháp: npm install
```

**Lỗi 3: "404 Not Found" cho assets**
```
Nguyên nhân: Assets chưa được build
Giải pháp: npm run build
```

**Lỗi 4: Trang trắng, không có lỗi console**
```
Nguyên nhân: Vue chưa mount
Giải pháp: Kiểm tra #app element
```

### Giải pháp nhanh:

#### Option 1: Chạy Dev Mode
```bash
# Terminal 1
npm run dev

# Terminal 2  
php artisan serve

# Truy cập: http://localhost:8000
```

#### Option 2: Build Production
```bash
npm run build
php artisan serve

# Truy cập: http://localhost:8000
```

#### Option 3: Clear Cache
```bash
# Clear browser cache (Ctrl+Shift+Del)
# Hoặc hard reload (Ctrl+F5)

# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Test đơn giản:

#### 1. Kiểm tra Vue có load không:
Mở Console và gõ:
```javascript
console.log('Vue:', window.Vue);
```

#### 2. Kiểm tra router:
```javascript
console.log('Router:', window.$router);
```

#### 3. View source (Ctrl+U):
Kiểm tra xem có thẻ script và link CSS không?
```html
<link rel="stylesheet" href="/build/assets/app-xxx.css">
<script type="module" src="/build/assets/app-xxx.js"></script>
```

### Nếu vẫn không được:

#### Thử trang test đơn giản:
Truy cập: `http://localhost:8000`

Nếu thấy "Loading..." spinner → Vue chưa mount
Nếu thấy trang trắng hoàn toàn → Assets không load

### Debug với npm run dev:

```bash
npm run dev
```

Output sẽ hiển thị:
```
VITE v7.x.x  ready in xxx ms

➜  Local:   http://localhost:5173/
➜  Network: use --host to expose
➜  press h + enter to show help
```

Sau đó truy cập: `http://localhost:8000`

### Kiểm tra file manifest:

```bash
cat public/build/manifest.json
```

Nếu file không tồn tại → Chạy `npm run build`

### Log để debug:

Thêm vào `resources/js/app.js`:
```javascript
console.log('App.js loaded');
console.log('Router:', router);
console.log('Store:', pinia);
```

### Kiểm tra routes:

```bash
php artisan route:list
```

Đảm bảo có route catch-all:
```
GET|HEAD  /{any} .................... Closure
```

### Nếu tất cả đều OK nhưng vẫn trắng:

1. Xóa `node_modules` và cài lại:
```bash
rm -rf node_modules
npm install
npm run build
```

2. Xóa `public/build` và build lại:
```bash
rm -rf public/build
npm run build
```

3. Restart server:
```bash
# Ctrl+C để dừng
php artisan serve
```

### Kiểm tra phiên bản:

```bash
node --version  # Nên >= 18
npm --version   # Nên >= 9
php --version   # Nên >= 8.2
```

### Test URL khác:

Thử các URL sau:
- `http://localhost:8000/` → Nên redirect hoặc show login
- `http://localhost:8000/auth/login` → Login page
- `http://localhost:8000/dashboard` → Redirect to login nếu chưa auth

### Lỗi CORS:

Nếu thấy lỗi CORS trong console:
```javascript
// Thêm vào .env
VITE_API_URL=http://localhost:8000
```

### Kết luận:

Nếu sau tất cả các bước trên vẫn không được:
1. Chụp màn hình Console (F12)
2. Chụp màn hình Network tab
3. Copy toàn bộ lỗi trong console
4. Gửi để được hỗ trợ

---

## ✅ Checklist:

- [ ] `npm install` đã chạy
- [ ] `npm run build` đã chạy thành công
- [ ] `public/build/manifest.json` tồn tại
- [ ] `php artisan serve` đang chạy
- [ ] Browser console không có lỗi
- [ ] Network tab shows 200 for assets
- [ ] Hard reload (Ctrl+F5) đã thử
- [ ] Clear cache đã thử

Nếu tất cả đều ✅ → Ứng dụng sẽ hoạt động!

