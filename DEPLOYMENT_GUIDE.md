# 🚀 Hướng Dẫn Triển Khai Dự Án Lên VPS

## 📋 Mục Lục
1. [Yêu Cầu Hệ Thống](#yêu-cầu-hệ-thống)
2. [Khuyến Nghị Hệ Điều Hành](#khuyến-nghị-hệ-điều-hành)
3. [Chuẩn Bị Dự Án](#chuẩn-bị-dự-án)
4. [Cài Đặt Trên VPS](#cài-đặt-trên-vps)
5. [Cấu Hình Production](#cấu-hình-production)
6. [Bảo Mật](#bảo-mật)
7. [Monitoring & Maintenance](#monitoring--maintenance)

---

## 🖥️ YÊU CẦU HỆ THỐNG

### Tối Thiểu (Cho Môi Trường Test)
- **CPU:** 2 cores
- **RAM:** 4GB
- **Disk:** 40GB SSD
- **Bandwidth:** 100GB/tháng

### Khuyến Nghị (Cho Production)
- **CPU:** 4 cores trở lên
- **RAM:** 8GB trở lên (16GB nếu có nhiều user đồng thời)
- **Disk:** 100GB SSD NVMe
- **Bandwidth:** Unlimited hoặc 500GB+/tháng

### Phân Tích Tài Nguyên

**Dự Án School (Laravel + Node.js):**
- PHP-FPM: ~1-2GB RAM
- MySQL: ~1-2GB RAM
- Node.js (Zalo Service): ~500MB-1GB RAM
- Redis (cache): ~256MB RAM
- Nginx: ~100MB RAM

**Dự Án Website (WordPress):**
- WordPress: ~500MB-1GB RAM
- MySQL (shared): Included above

**Tổng ước tính:** 4-6GB RAM active, 8GB để có buffer

---

## 🐧 KHUYẾN NGHỊ HỆ ĐIỀU HÀNH

### ⭐ Lựa Chọn Tốt Nhất: **Ubuntu Server 22.04 LTS**

#### Lý Do:
1. ✅ **Long Term Support (LTS)**
   - Hỗ trợ đến tháng 4/2027
   - Security updates thường xuyên
   - Ổn định cho production

2. ✅ **Tương Thích Hoàn Hảo**
   - PHP 8.2+ có sẵn trong repository
   - Node.js 18/20 LTS dễ cài đặt
   - MySQL 8.0+ hỗ trợ tốt
   - Nginx/Apache được optimize

3. ✅ **Cộng Đồng Lớn**
   - Tài liệu phong phú
   - Nhiều tutorial
   - Dễ tìm giải pháp khi gặp lỗi

4. ✅ **Package Manager Tốt**
   - APT package manager mạnh mẽ
   - PPA repositories phong phú
   - Dễ update và maintain

5. ✅ **Tối Ưu Cho Laravel & Node.js**
   - Laravel Forge hỗ trợ tốt nhất
   - PM2 hoạt động ổn định
   - Docker support tốt

### Các Lựa Chọn Khác (Tốt nhưng ít ưu tiên hơn)

#### 🔸 Debian 12 (Bookworm)
- **Ưu điểm:** Cực kỳ ổn định, ít bug
- **Nhược điểm:** Package đôi khi cũ hơn Ubuntu
- **Phù hợp:** Nếu bạn ưu tiên sự ổn định tuyệt đối

#### 🔸 Rocky Linux 9 / AlmaLinux 9
- **Ưu điểm:** Thay thế CentOS, enterprise-grade
- **Nhược điểm:** Cài đặt phức tạp hơn, ít tài liệu hơn
- **Phù hợp:** Nếu bạn quen với RHEL ecosystem

#### 🔸 Ubuntu Server 24.04 LTS
- **Ưu điểm:** Mới nhất, hỗ trợ đến 2029
- **Nhược điểm:** Mới release (4/2024), có thể có bugs
- **Phù hợp:** Nếu muốn công nghệ mới nhất và chấp nhận rủi ro

### ❌ KHÔNG Khuyến Nghị

- **Windows Server:** Chi phí cao, không tối ưu cho Laravel/Node.js
- **CentOS:** Đã discontinued
- **Fedora Server:** Chu kỳ support ngắn (13 tháng)
- **Arch Linux:** Quá bleeding edge, không ổn định cho production

---

## 🎯 QUYẾT ĐỊNH CUỐI CÙNG

### 👉 Khuyến nghị: **Ubuntu Server 22.04 LTS (Jammy Jellyfish)**

**Download:** https://ubuntu.com/download/server

**Lý do chọn:**
1. Cân bằng giữa ổn định và hiện đại
2. PHP 8.2, Node.js 20 LTS support tốt
3. Hỗ trợ đến 2027
4. Cộng đồng lớn nhất
5. Tài liệu deployment cho Laravel/Node.js nhiều nhất

---

## 📦 STACK CÔNG NGHỆ TRÊN VPS

### Web Server
**Khuyến nghị: Nginx**
- Hiệu năng cao hơn Apache
- Xử lý static files tốt
- Reverse proxy cho Node.js
- HTTP/2 và HTTP/3 support

### Database
**MySQL 8.0**
- InnoDB engine
- Performance schema enabled
- Slow query log

### Process Manager
**PM2** (cho Node.js)
- Auto-restart
- Cluster mode
- Log management
- Monitoring

**Supervisor** (cho Laravel Queue)
- Quản lý queue workers
- Auto-restart on failure

### Caching
**Redis**
- Session storage
- Cache storage
- Queue backend

### PHP
**PHP 8.2 with PHP-FPM**
- OPcache enabled
- Composer 2.x

### SSL/TLS
**Certbot (Let's Encrypt)**
- Free SSL certificates
- Auto-renewal

---

## 🔧 CÁC BƯỚC CHUẨN BỊ

Xem file `deployment-scripts/backup.sh` để export dự án
Xem file `deployment-scripts/deploy-vps.sh` để cài đặt trên VPS

---

## 🌐 DOMAIN & DNS

### Khuyến Nghị Cấu Trúc Domain

```
school.yourdomain.com    → Laravel App (School Management)
www.yourdomain.com       → WordPress Website
yourdomain.com           → WordPress Website (redirect)
api.yourdomain.com       → Laravel API (optional, nếu tách riêng)
```

### DNS Records Cần Thiết

```
A Record:
  school.yourdomain.com  → VPS IP
  www.yourdomain.com     → VPS IP
  yourdomain.com         → VPS IP

CNAME Record (optional):
  api.yourdomain.com     → school.yourdomain.com
```

---

## 📊 SO SÁNH VPS PROVIDERS (Việt Nam)

### 🇻🇳 Trong Nước

#### BKNS (BizFly Cloud)
- **Giá:** ~500k-1.5tr/tháng (4GB RAM)
- **Ưu điểm:** Hỗ trợ tiếng Việt, thanh toán VND
- **Nhược điểm:** Hiệu năng trung bình

#### Viettel IDC
- **Giá:** ~800k-2tr/tháng
- **Ưu điểm:** Bandwidth lớn, hỗ trợ tốt
- **Nhược điểm:** Đắt hơn

### 🌏 Quốc Tế (Giá Tốt)

#### Vultr
- **Giá:** $12-24/tháng (4-8GB RAM)
- **Ưu điểm:** Data center Singapore, SSD NVMe
- **Server:** Singapore location

#### DigitalOcean
- **Giá:** $12-24/tháng
- **Ưu điểm:** UI đẹp, tài liệu tốt
- **Server:** Singapore location

#### Linode (Akamai)
- **Giá:** $12-24/tháng
- **Ưu điểm:** Hiệu năng tốt
- **Server:** Singapore location

### 💰 Khuyến Nghị Ngân Sách

**Bắt Đầu:** Vultr/DigitalOcean $12/tháng (2GB RAM)
**Phát Triển:** $24/tháng (4GB RAM)
**Production:** $48/tháng (8GB RAM)

---

## 🔐 BẢO MẬT CƠ BẢN

### Firewall (UFW)
```bash
# Chỉ mở các port cần thiết
Port 22    - SSH (đổi sang port khác)
Port 80    - HTTP
Port 443   - HTTPS
Port 3001  - WebSocket (chỉ cho Nginx reverse proxy)
```

### SSH Security
- Disable root login
- Sử dụng SSH key
- Fail2ban cho brute force protection

### Database
- Không cho phép remote access (chỉ localhost)
- Strong password
- Regular backups

---

## 📈 PERFORMANCE TUNING

### PHP-FPM
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
```

### MySQL
```ini
innodb_buffer_pool_size = 2G (50% của RAM)
max_connections = 200
query_cache_size = 0 (disabled trong MySQL 8)
```

### Nginx
```nginx
worker_processes auto;
worker_connections 2048;
gzip on;
gzip_types text/plain text/css application/json application/javascript;
```

---

## 🔄 BACKUP STRATEGY

### Tự Động Backup Hàng Ngày
1. **Database:** mysqldump → compress → upload to cloud
2. **Files:** rsync/tar → compress → upload to cloud
3. **Logs:** Rotate và archive

### Retention Policy
- Daily backups: 7 ngày
- Weekly backups: 4 tuần
- Monthly backups: 6 tháng

### Backup Locations
- Primary: VPS local disk
- Secondary: Cloud storage (Google Drive, AWS S3, Backblaze B2)

---

## 📞 HỖ TRỢ & TÀI LIỆU

### Tài Liệu Tham Khảo
- Laravel Deployment: https://laravel.com/docs/deployment
- Ubuntu Server Guide: https://ubuntu.com/server/docs
- DigitalOcean Tutorials: https://www.digitalocean.com/community/tutorials

### Monitoring Tools
- **Uptime:** UptimeRobot (free)
- **Performance:** New Relic / DataDog
- **Logs:** Papertrail / Loggly
- **Server:** Netdata (free, self-hosted)

---

## ✅ CHECKLIST TRƯỚC KHI GO LIVE

- [ ] Domain đã trỏ về VPS
- [ ] SSL certificate đã cài đặt
- [ ] Database được backup
- [ ] .env configured cho production
- [ ] Debug mode = false
- [ ] Firewall được cấu hình
- [ ] SSH key authentication
- [ ] Zalo Service chạy với PM2
- [ ] Laravel Queue worker chạy với Supervisor
- [ ] Cron jobs đã setup
- [ ] Log rotation configured
- [ ] Monitoring tools đã setup
- [ ] Backup script đã test
- [ ] Performance test đã chạy
- [ ] Security scan đã chạy

---

**Tác giả:** Generated by Claude Code
**Ngày tạo:** 2025-11-21
**Phiên bản:** 1.0
