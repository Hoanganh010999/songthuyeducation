# 📁 Hướng Dẫn Upload File cho IELTS Tests

## 🎯 Tổng Quan

Hệ thống IELTS hiện hỗ trợ upload file audio (Listening) và images (Writing) lên local server.

---

## 📂 Cấu Trúc Thư Mục

```
public/storage/examination/
├── audio/                      # File audio cho Listening tests
│   ├── listening-test-1.mp3
│   ├── listening-test-2.mp3
│   └── ...
├── images/                     # Hình ảnh cho Writing Task 1
│   ├── chart-1.jpg
│   ├── graph-1.png
│   └── ...
└── documents/                  # File PDF, docs khác
    └── ...
```

---

## 🔧 Setup Ban Đầu

### 1. Tạo Symbolic Link (nếu chưa có)

```bash
php artisan storage:link
```

Lệnh này tạo symlink từ `public/storage` → `storage/app/public`

### 2. Tạo Thư Mục Examination

```bash
# Windows PowerShell
New-Item -Path "storage\app\public\examination\audio" -ItemType Directory -Force
New-Item -Path "storage\app\public\examination\images" -ItemType Directory -Force
New-Item -Path "storage\app\public\examination\documents" -ItemType Directory -Force

# Linux/Mac
mkdir -p storage/app/public/examination/{audio,images,documents}
```

---

## 📤 Cách Upload File

### Phương Pháp 1: Upload Thủ Công (Nhanh)

1. **Chuẩn bị file audio/image**
2. **Copy vào thư mục**:
   - Audio: `storage/app/public/examination/audio/`
   - Images: `storage/app/public/examination/images/`

3. **Sử dụng URL trong Test Builder**:
   ```
   /storage/examination/audio/your-file.mp3
   /storage/examination/images/your-image.jpg
   ```

### Phương Pháp 2: Upload Qua UI (Đang Phát Triển)

Tính năng upload trực tiếp trong Test Builder sẽ được thêm sau.

---

## 🎵 File Audio Cho Listening

### Yêu Cầu File Audio:
- **Format**: MP3, WAV, OGG
- **Size**: < 50MB
- **Sample Rate**: 44.1kHz hoặc 48kHz
- **Bit Rate**: 128kbps - 320kbps

### Đặt Tên File:
```
listening-test-{số}-part-{part}.mp3

Ví dụ:
- listening-test-1-part-1.mp3  (Test 1, Part 1)
- listening-test-1-part-2.mp3  (Test 1, Part 2)
- listening-test-2-part-1.mp3  (Test 2, Part 1)
```

### URL Sử Dụng:
```json
{
  "audio_url": "/storage/examination/audio/listening-test-1.mp3",
  "parts": [
    {
      "audio_url": "/storage/examination/audio/listening-test-1-part-1.mp3"
    }
  ]
}
```

---

## 🖼️ Hình Ảnh Cho Writing Task 1

### Yêu Cầu Hình Ảnh:
- **Format**: JPG, PNG, SVG
- **Size**: < 5MB
- **Resolution**: 800x600 trở lên
- **DPI**: 72-150

### Loại Hình Ảnh:
- Bar charts / Column charts
- Line graphs
- Pie charts
- Tables
- Maps
- Process diagrams
- Mixed charts

### Đặt Tên File:
```
writing-task1-{type}-{number}.{ext}

Ví dụ:
- writing-task1-bar-chart-1.jpg
- writing-task1-line-graph-1.png
- writing-task1-pie-chart-1.jpg
- writing-task1-table-1.png
```

---

## 🛠️ Troubleshooting

### Lỗi: File không load được

**Kiểm tra:**
1. Symbolic link đã tạo chưa?
   ```bash
   php artisan storage:link
   ```

2. File có tồn tại không?
   ```bash
   ls storage/app/public/examination/audio/
   ```

3. Permissions đúng chưa?
   ```bash
   # Linux/Mac
   chmod -R 755 storage/app/public/examination/
   
   # Windows: Right click → Properties → Security
   ```

4. URL đúng format?
   ```
   ✅ Đúng:  /storage/examination/audio/file.mp3
   ❌ Sai:   storage/examination/audio/file.mp3
   ❌ Sai:   /public/storage/examination/audio/file.mp3
   ```

### Lỗi: Audio không phát

**Kiểm tra:**
- File có phải MP3/WAV không?
- Corrupt file: Thử mở bằng media player
- Browser console có lỗi gì?

---

## 📝 Sample Data trong Seeder

File seeder đã được cập nhật để dùng local URLs:

```php
'audio_url' => '/storage/examination/audio/listening-test-1.mp3'
```

**Lưu ý**: File audio mẫu KHÔNG có sẵn. Bạn cần:
1. Tự upload audio files
2. Hoặc sửa URL trong test settings sau khi tạo

---

## 🚀 Tương Lai

### Tính năng sẽ thêm:
- [ ] UI Upload trực tiếp trong Test Builder
- [ ] Drag & drop upload
- [ ] Audio preview trước khi save
- [ ] Image crop/resize tool
- [ ] File manager để quản lý tất cả media
- [ ] Cloud storage integration (Google Drive, AWS S3)

---

## 💡 Tips

1. **Tổ chức file tốt**:
   - Đặt tên rõ ràng, có số thứ tự
   - Group theo test number

2. **Backup files**:
   - Copy folder `storage/app/public/examination/` thường xuyên
   - Hoặc push lên Git (nếu file nhỏ)

3. **Optimize files**:
   - Compress audio để giảm size (MP3 128kbps đủ dùng)
   - Optimize images với TinyPNG/ImageOptim
   - Không dùng file quá lớn

4. **Testing**:
   - Test audio trên nhiều browsers
   - Kiểm tra mobile playback
   - Đảm bảo format tương thích

---

## 📞 Support

Nếu gặp vấn đề về upload files, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Browser console (F12)
3. Network tab để xem HTTP requests


