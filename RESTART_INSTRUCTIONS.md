# 🔄 HƯỚNG DẪN RESTART LARAVEL SERVER

## ⚠️ VẤN ĐỀ PHÁT HIỆN

Có **2 Laravel dev servers** đang chạy cùng lúc trên port 8000!
- PID 211856
- PID 208812

Một trong số đó đang chạy **code cũ** (chưa có fix), nên vẫn gặp lỗi SQL.

---

## ✅ GIẢI PHÁP: RESTART SERVER

### Bước 1: Dừng tất cả PHP processes

**Mở Command Prompt với quyền Administrator**, chạy:

```cmd
taskkill /F /PID 211856
taskkill /F /PID 208812
```

Hoặc dừng TẤT CẢ php.exe:
```cmd
taskkill /F /IM php.exe
```

### Bước 2: Verify không còn process nào

```cmd
netstat -ano | findstr ":8000"
```

Nếu vẫn còn → kill tiếp theo PID hiển thị.

### Bước 3: Xóa tất cả Laravel cache

```cmd
cd c:\xampp\htdocs\school
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Bước 4: Khởi động lại server

```cmd
cd c:\xampp\htdocs\school
php artisan serve
```

**QUAN TRỌNG:** Chỉ khởi động 1 lần! Không mở nhiều terminal!

### Bước 5: Kiểm tra server đang chạy

Mở terminal mới:
```cmd
netstat -ano | findstr ":8000"
```

Chỉ nên thấy **1 connection** với status LISTENING.

---

## 🧪 TEST LẠI

1. Truy cập: http://localhost:8000/zalo
2. Click "Thêm tài khoản Zalo"
3. Scan QR code
4. **EXPECTED:** ✅ Không còn lỗi SQL nữa!

---

## 🔍 VERIFY CODE ĐÃ ĐÚNG

Kiểm tra file đã có fix:

```cmd
cd c:\xampp\htdocs\school
findstr /n "CRITICAL: Add zalo_account_id" app\Services\ZaloCacheService.php
```

Phải thấy 2 dòng:
- Line ~68: `$friendDataNormalized['zalo_account_id'] = $account->id;`
- Line ~311: `$groupDataNormalized['zalo_account_id'] = $account->id;`

---

## ❌ NẾU VẪN LỖI

1. **Check Laravel log:**
   ```cmd
   tail -100 storage\logs\laravel.log
   ```

2. **Check SQL query có `zalo_account_id` chưa:**
   - Nếu SQL vẫn thiếu `zalo_account_id` → Server chưa restart đúng
   - Nếu SQL có `zalo_account_id` nhưng vẫn lỗi → Vấn đề khác

3. **Restart lại XAMPP:**
   - Stop Apache
   - Stop MySQL
   - Start lại cả 2
   - Chạy lại `php artisan serve`

---

## 📌 GHI CHÚ

- **Lý do lỗi:** Code đã sửa nhưng server cũ vẫn đang chạy code cũ trong memory
- **Giải pháp:** Kill tất cả processes và restart fresh
- **Prevention:** Chỉ chạy 1 `php artisan serve` tại 1 thời điểm
