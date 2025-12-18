# 📱 Hướng dẫn Setup Zalo API Service (cực kỳ đơn giản!)

## 🎯 Tổng quan
- **Package:** [zalo-api-final](https://github.com/hiennguyen270995/zalo-api-final) v2.1.0
- **License:** MIT
- **Tính năng:** Gửi tin nhắn Zalo tự động cho học viên/phụ huynh

---

## 🚀 Setup siêu nhanh (3 bước - 5 phút)

### Bước 1: Cài đặt dependencies
```powershell
cd zalo-service
npm install
```

### Bước 2: Cấu hình .env
```powershell
# Copy file mẫu
cp env.example .env

# Edit file .env
notepad .env
```

**Nội dung .env:**
```env
PORT=3001
NODE_ENV=development

# Để TRỐNG các dòng này - sẽ dùng QR login
ZALO_COOKIE=
ZALO_IMEI=
ZALO_USER_AGENT=

# Tạo secret key bất kỳ
API_SECRET_KEY=my-super-secret-key-2024

LARAVEL_URL=http://127.0.0.1:8000
```

### Bước 3: Chạy service lần đầu
```powershell
npm run dev
```

**Service sẽ hiển thị QR Code trong terminal:**
```
🔐 Initiating QR Code login...
📱 Scan this QR code with Zalo app:

█████████████████████████████
█████████████████████████████
███ ▄▄▄▄▄ █▀ █▀▀██ ▄▄▄▄▄ ███
███ █   █ █▀ ▀ ▄ █ █   █ ███
███ █▄▄▄█ ██▄ ▀▄▀█ █▄▄▄█ ███
...
```

**Quét QR bằng app Zalo trên điện thoại** → Đăng nhập thành công!

Service sẽ tự động lưu credentials và hiển thị:
```
✅ QR login successful!

💾 Save these credentials to .env:
ZALO_COOKIE=zpw_sek_xxxxx...
ZALO_IMEI=xxxxxxxx-xxxx-xxxx...
ZALO_USER_AGENT=Mozilla/5.0...
```

**Copy và paste vào file .env** để lần sau không cần quét QR nữa.

---

## 🔧 Cấu hình Laravel

### 1. Thêm vào `.env` của Laravel:
```env
ZALO_SERVICE_URL=http://localhost:3001
ZALO_API_KEY=my-super-secret-key-2024
```

⚠️ **Lưu ý:** `ZALO_API_KEY` phải giống `API_SECRET_KEY` trong Zalo Service!

### 2. Clear config cache:
```bash
php artisan config:cache
```

---

## 🧪 Test ngay

### Test 1: Service đang chạy?
```bash
curl http://localhost:3001/health
```

Kết quả:
```json
{
  "status": "ok",
  "service": "Zalo API Service",
  "timestamp": "2025-11-12T..."
}
```

### Test 2: Kết nối từ Laravel
```bash
php test_zalo_service.php
```

Sẽ hiển thị:
```
🧪 Testing Zalo Service Integration
==================================================

1️⃣ Checking if Zalo service is ready...
   ✅ Zalo service is READY

2️⃣ Getting Zalo friends list...
   ✅ Found 15 friends
   First friend: Nguyễn Văn A
```

---

## 💡 Sử dụng trong Code

### 1. Gửi tin nhắn cho 1 học viên
```php
use App\Services\ZaloNotificationService;

$zalo = new ZaloNotificationService();

// Gửi tin nhắn (theo số điện thoại)
$result = $zalo->sendMessage(
    to: '0987654321',
    message: '📚 Bạn có bài tập mới từ lớp IELTS 5.0'
);

if ($result['success']) {
    Log::info('Đã gửi thông báo Zalo thành công');
}
```

### 2. Gửi hàng loạt cho nhiều học viên
```php
// Lấy tất cả students trong class
$students = $class->students;

$result = $zalo->notifyStudents(
    students: $students,
    message: "⚠️ Nhắc nhở: Lớp học nghỉ vào thứ 7 tuần sau"
);

// Kết quả
echo "Đã gửi: " . count($result['results'] ?? []) . " tin nhắn";
echo "Thất bại: " . count($result['errors'] ?? []) . " tin nhắn";
```

### 3. Gửi kèm hình ảnh
```php
$result = $zalo->sendImage(
    to: '0987654321',
    imageUrl: 'https://yoursite.com/homework-image.jpg'
);
```

---

## 🎯 Ứng dụng thực tế

### 1️⃣ Thông báo bài tập mới
**Trong `HomeworkAssignmentController.php`:**
```php
public function store(Request $request)
{
    // ... tạo homework ...
    $homework = HomeworkAssignment::create($validated);
    
    // Gửi thông báo Zalo
    $zalo = new \App\Services\ZaloNotificationService();
    
    if ($zalo->isReady()) {
        $message = "📚 Bài tập mới: {$homework->title}\n" .
                   "📅 Hạn nộp: " . $homework->deadline->format('d/m/Y H:i') . "\n" .
                   "🔗 Link: " . route('homework.detail', $homework->id);
        
        $class = $homework->class;
        $zalo->notifyStudents($class->students, $message);
    }
    
    // ... return response ...
}
```

### 2️⃣ Nhắc nhở chưa nộp bài
**Tạo scheduled command:**
```php
// app/Console/Commands/SendHomeworkReminder.php
public function handle()
{
    $zalo = new \App\Services\ZaloNotificationService();
    
    // Lấy homeworks sắp hết hạn
    $dueHomeworks = HomeworkAssignment::whereBetween('deadline', [
        now(),
        now()->addHours(24)
    ])->get();
    
    foreach ($dueHomeworks as $homework) {
        // Tìm students chưa nộp
        $notSubmitted = $homework->class->students->filter(function ($student) use ($homework) {
            return !$homework->submissions()->where('student_id', $student->id)->exists();
        });
        
        if ($notSubmitted->isNotEmpty()) {
            $message = "⏰ Nhắc nhở: Bài tập '{$homework->title}' sắp hết hạn!\n" .
                       "📅 Hạn nộp: " . $homework->deadline->format('d/m/Y H:i');
            
            $zalo->notifyStudents($notSubmitted, $message);
        }
    }
}
```

**Đăng ký trong `app/Console/Kernel.php`:**
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('homework:remind')->dailyAt('08:00');
}
```

### 3️⃣ Thông báo điểm số
**Khi giáo viên chấm bài:**
```php
// Trong HomeworkSubmissionController hoặc tương tự
$submission->update(['score' => $score, 'status' => 'graded']);

$zalo = new \App\Services\ZaloNotificationService();

if ($zalo->isReady()) {
    $message = "✅ Bài tập '{$submission->homework->title}' đã được chấm\n" .
               "📊 Điểm: {$score}/10\n" .
               "💬 Nhận xét: {$feedback}";
    
    $zalo->notifyStudent($submission->student, $message);
}
```

---

## ⚠️ Lưu ý quan trọng

### 🔐 Bảo mật
- ✅ **KHÔNG** commit file `.env` vào Git
- ✅ Dùng tài khoản Zalo **test/phụ**, không dùng tài khoản chính
- ✅ `API_SECRET_KEY` nên dùng chuỗi random phức tạp
- ✅ Giữ bí mật credentials (cookie, imei)

### ⚡ Rate Limiting
- Zalo có giới hạn ~30 tin nhắn/phút/tài khoản
- Service tự động delay 500ms giữa mỗi tin khi gửi hàng loạt
- **KHÔNG spam** - có thể bị khóa tài khoản

### 🛠️ Troubleshooting

**1. Service không khởi động:**
```powershell
# Check port có bị chiếm không
netstat -ano | findstr :3001

# Kill process nếu cần
taskkill /PID <process_id> /F

# Start lại
npm run dev
```

**2. QR login thất bại:**
- Đảm bảo quét đúng QR trong vòng 60 giây
- Thử xóa file `.env` và tạo lại
- Check internet connection

**3. Gửi tin nhắn thất bại:**
```bash
# Kiểm tra credentials có còn hợp lệ không
# Nếu hết hạn, xóa credentials và login lại
```

Trong `.env`:
```env
ZALO_COOKIE=
ZALO_IMEI=
ZALO_USER_AGENT=
```

Sau đó restart service → Quét QR lại

**4. Laravel không kết nối được service:**
```bash
# Check service đang chạy
curl http://localhost:3001/health

# Check .env Laravel
php artisan config:cache

# Check API key khớp nhau giữa 2 services
```

---

## 📊 API Endpoints Reference

| Endpoint | Method | Description | Body |
|----------|--------|-------------|------|
| `/health` | GET | Health check | - |
| `/api/auth/status` | GET | Check Zalo status | - |
| `/api/auth/initialize` | POST | Initialize Zalo | - |
| `/api/message/send` | POST | Send message | `{to, message, type}` |
| `/api/message/send-bulk` | POST | Send bulk | `{recipients[], message}` |
| `/api/message/send-image` | POST | Send image | `{to, imageUrl, type}` |
| `/api/user/friends` | GET | Get friends | - |
| `/api/user/find` | POST | Find by phone | `{phone}` |
| `/api/group/list` | GET | Get groups | - |

**Tất cả endpoints đều cần header:**
```
X-API-Key: your_api_secret_key
```

---

## 🎉 Quick Start Checklist

- [ ] Đã chạy `npm install` trong `zalo-service/`
- [ ] Đã tạo file `.env` với `API_SECRET_KEY`
- [ ] Đã chạy `npm run dev` và quét QR Code
- [ ] Đã lưu credentials vào `.env` sau khi login
- [ ] Đã cấu hình Laravel `.env` với `ZALO_SERVICE_URL` và `ZALO_API_KEY`
- [ ] Đã test với `php test_zalo_service.php`
- [ ] Đã tích hợp vào HomeworkAssignmentController

---

## 📖 Tài liệu thêm

- **Package docs:** https://hiennguyen270995.github.io/zalo-api-final/
- **GitHub repo:** https://github.com/hiennguyen270995/zalo-api-final
- **Zalo Developer:** https://developers.zalo.me/

---

## ☕ Support Package Author

Nếu thư viện hữu ích, hãy ủng hộ tác giả: **Nguyễn Thị Hiền**

**VietinBank:** 100884532014

---

🎉 **Hoàn tất! Bắt đầu gửi thông báo Zalo thôi!** 🚀

