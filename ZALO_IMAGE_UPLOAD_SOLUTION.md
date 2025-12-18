# Giải pháp lỗi gửi ảnh Zalo

## Tóm tắt vấn đề

### 1. ❌ **Lỗi 500: cURL Timeout**
- **Nguyên nhân**: Laravel HTTP client timeout 30s, nhưng `zalo-service` cần >30s để:
  - Download image từ `http://127.0.0.1:8000/storage/...` 
  - Gửi qua Zalo API
  - Cleanup temp file
- **Kết quả**: Laravel trả 500 về frontend, nhưng `zalo-service` VẪN GỬI THÀNH CÔNG
- **✅ Đã sửa**: Tăng timeout từ 30s → 90s trong `ZaloNotificationService::sendImage`

### 2. ⚠️  **Content bị lưu sai: temp file path thay vì image URL**
- **Hiện tượng**: Database lưu content = `C:\xampp\htdocs\school\zalo-service\temp\image_...`
- **Nguyên nhân**: 
  - `zalo-api-final` nhận file path làm message
  - Khi send xong, WebSocket listener emit message với content = file path
  - Laravel lưu file path vào database thay vì image URL/attachment

### 3. 💡 **Về "gửi link ở Laravel chứ không phải gửi link từ zalo serve"**
- Hiện tại flow:
  1. Frontend → Laravel: Upload file physical
  2. Laravel: Lưu file vào `storage/app/public/zalo/images/`
  3. Laravel → `zalo-service`: Gửi URL `http://127.0.0.1:8000/storage/...`
  4. `zalo-service`: Download từ URL → temp file → send → delete temp
  
- **Vấn đề**: `zalo-service` download lại file đã có trong cùng server (localhost)
- **Tối ưu hơn**: Laravel nên gửi trực tiếp file path local thay vì URL:
  - Không cần download qua HTTP
  - Nhanh hơn, ít tài nguyên hơn
  - Nhưng cần access filesystem của `zalo-service` (cùng server)

## Giải pháp đề xuất

### Giải pháp ngắn hạn (đã làm):
- ✅ Tăng timeout lên 90s

### Giải pháp tối ưu (nếu cần):
1. **Cách 1: Shared storage**
   - Laravel lưu file vào folder mà `zalo-service` có thể đọc được
   - Laravel gửi absolute path thay vì URL
   - `zalo-service` đọc trực tiếp, không download

2. **Cách 2: Gửi file content qua API**
   - Laravel đọc file và encode base64
   - Gửi base64 content qua API
   - `zalo-service` decode và lưu temp file
   - (Tốn bandwidth hơn nhưng independent)

3. **Cách 3: Laravel serve static files nhanh hơn**
   - Optimize Laravel static file serving
   - Hoặc dùng nginx/apache serve trực tiếp `storage/` folder
   - (Giữ nguyên architecture hiện tại)

## Kết luận
- Vấn đề 1 (timeout) đã sửa
- Vấn đề 2 (content sai) cần kiểm tra `zaloClient.js` listener
- Vấn đề 3 (architecture) có thể tối ưu sau nếu cần

