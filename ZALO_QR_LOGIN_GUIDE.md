# 📱 Hướng dẫn Đăng nhập Zalo bằng QR Code (Frontend)

## 🎉 Đã hoàn thành!

Bạn giờ có thể **đăng nhập Zalo trực tiếp từ browser** với UI đẹp và dễ sử dụng!

---

## 🚀 Cách sử dụng (CỰC KỲ ĐƠN GIẢN!)

### Bước 1: Khởi động Zalo Service

```powershell
cd C:\xampp\htdocs\school\zalo-service
npm run dev
```

✅ Service sẽ chạy ở `http://localhost:3001`

### Bước 2: Truy cập Zalo Module

Mở browser: `http://localhost/zalo`

### Bước 3: Vào Settings Tab

Click vào **Settings** (⚙️) trong sidebar bên trái

### Bước 4: Click nút "Login with QR Code"

Nếu service **disconnected**, bạn sẽ thấy nút màu xanh lá:

```
[Test Connection] [Login with QR Code]
```

### Bước 5: QR Code hiện ra!

Modal sẽ popup với:
- ✅ QR Code lớn, rõ ràng
- ✅ Hướng dẫn từng bước
- ✅ Loading animation khi đang chờ
- ✅ Auto-detect khi scan thành công

### Bước 6: Quét QR bằng Zalo App

Trên **điện thoại:**

1. Mở **Zalo app**
2. Tap **Settings** (⚙️) ở góc dưới phải
3. Chọn **Zalo Web**
4. Quét QR Code trên màn hình

### Bước 7: Thành công! 🎉

- ✅ Tự động phát hiện login thành công
- ✅ Hiển thị thông báo "Login successful!"
- ✅ Modal tự động đóng
- ✅ Status chuyển sang "Connected"

---

## 🎨 UI Features

### QR Login Modal bao gồm:

1. **Loading State**
   - Spinner animation
   - Text "Generating QR Code..."

2. **QR Code Display**
   - QR Code 300x300px
   - Border 2px xám
   - Nền trắng

3. **Instructions**
   - 4 bước rõ ràng bằng tiếng Việt/English
   - Icon và numbering

4. **Auto-Detection**
   - Check status mỗi 3 giây
   - Spinner "Waiting for scan..."
   - Countdown "QR expires in 60s"

5. **Success Handling**
   - SweetAlert notification
   - Auto close modal
   - Update connection status

6. **Error Handling**
   - Retry button nếu failed
   - Auto-expire sau 60 giây
   - Warning message

---

## 📊 Technical Details

### Frontend Changes:

**`resources/js/pages/zalo/components/ZaloSettings.vue`**
- Added `showLoginModal` state
- Added `qrCodeData` for QR image
- Added `initializeLogin()` function
- Added `startStatusCheck()` with polling
- Added modal UI with responsive design

### Backend Changes:

**`zalo-service/routes/auth.js`**
- Updated `/api/auth/initialize` to return QR as base64
- Using `qrcode` npm package
- Generates 300x300px QR image

**`zalo-service/package.json`**
- Added `qrcode@^1.5.3` dependency

---

## 🔧 API Flow

```
Frontend                          Backend (Zalo Service)
   |                                      |
   |  POST /api/auth/initialize           |
   |------------------------------------->|
   |  (with X-API-Key header)             |
   |                                      |
   |                      Generate QR Code|
   |                      Call Zalo API   |
   |                                      |
   |  Response:                           |
   |  { success: true,                    |
   |    qrCode: "data:image/png;base64.."}|
   |<-------------------------------------|
   |                                      |
   | Display QR in modal                  |
   | Start polling status                 |
   |                                      |
   | GET /api/zalo/status (every 3s)      |
   |------------------------------------->|
   |                                      |
   |  { isReady: false }                  |
   |<-------------------------------------|
   |                                      |
   | (User scans QR)                      |
   |                                      |
   | GET /api/zalo/status                 |
   |------------------------------------->|
   |                                      |
   |  { isReady: true } ✅                |
   |<-------------------------------------|
   |                                      |
   | Show success notification            |
   | Close modal                          |
   | Update UI                            |
```

---

## ⚠️ Important Notes

### Credentials được lưu tự động

Sau khi login thành công, Zalo Service sẽ tự động lưu credentials vào file `.env`:

```env
ZALO_COOKIE=zpw_sek_xxxxx...
ZALO_IMEI=xxxxxxxx-xxxx-xxxx...
ZALO_USER_AGENT=Mozilla/5.0...
```

**Lần sau không cần login lại!**

### QR Code hết hạn sau 60 giây

- Nếu không quét trong 60s, modal sẽ hiện warning
- Click "Retry" để tạo QR mới

### Multiple Login Prevention

- Nếu đã connected, nút "Login with QR Code" sẽ ẩn
- Chỉ hiện khi status = "disconnected"

---

## 🎯 Troubleshooting

### 1. Nút "Login with QR Code" không hiện?

**Nguyên nhân:** Service đã connected

**Giải pháp:** Kiểm tra connection status. Nếu đã connected thì không cần login lại.

### 2. Modal hiện nhưng không có QR?

**Nguyên nhân:** 
- Zalo Service không chạy
- API Key không đúng
- Credentials đã có trong `.env`

**Giải pháp:**
```powershell
# Check service
curl http://localhost:3001/health

# Check .env - phải trống ZALO_COOKIE
cd zalo-service
cat .env
```

### 3. QR hiện nhưng scan không được?

**Nguyên nhân:**
- QR đã hết hạn (>60s)
- Zalo API lỗi

**Giải pháp:**
- Click "Retry" để tạo QR mới
- Check Zalo Service logs

### 4. Scan xong nhưng không redirect?

**Nguyên nhân:** Polling bị lỗi

**Giải pháp:**
- F12 > Console để xem errors
- Refresh page và thử lại

---

## ✅ Testing Checklist

- [ ] Service đang chạy (port 3001)
- [ ] Frontend compiled (`npm run build`)
- [ ] Access `/zalo` thành công
- [ ] Click Settings tab
- [ ] Status hiện "Disconnected"
- [ ] Nút "Login with QR Code" hiện ra
- [ ] Click nút → Modal popup
- [ ] QR Code hiện rõ ràng
- [ ] Quét bằng Zalo app
- [ ] Login successful notification
- [ ] Modal tự động đóng
- [ ] Status chuyển sang "Connected"

---

## 🎉 Summary

### Đã có:
- ✅ UI đẹp với modal popup
- ✅ QR Code tự động generate
- ✅ Auto-detect login success
- ✅ Error handling đầy đủ
- ✅ Loading states
- ✅ Responsive design
- ✅ Tiếng Việt/English

### Không cần:
- ❌ Không cần terminal
- ❌ Không cần copy/paste credentials
- ❌ Không cần cURL commands
- ❌ Không cần DevTools

---

**🎯 Bây giờ bạn chỉ cần:**

1. `cd zalo-service && npm run dev`
2. Mở browser: `http://localhost/zalo`
3. Click Settings → Login with QR Code
4. Quét bằng Zalo app
5. Done! ✨

**Đơn giản như vậy thôi!** 📱

