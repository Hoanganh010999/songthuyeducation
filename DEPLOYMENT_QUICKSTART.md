# 🚀 Hướng Dẫn Nhanh - Deploy Lên VPS

## 📌 TÓM TẮT NHANH

### Yêu Cầu 1: Đóng Gói Dự Án ✅
### Yêu Cầu 2: VPS Nên Dùng OS Gì? ✅

---

## 💻 KHUYẾN NGHỊ HỆ ĐIỀU HÀNH

### ⭐ **Ubuntu Server 22.04 LTS** (KHUYẾN NGHỊ MẠNH)

**Tại sao?**
- ✅ Hỗ trợ đến 2027 (LTS)
- ✅ PHP 8.2+ có sẵn
- ✅ Node.js 20 LTS dễ cài
- ✅ Cộng đồng lớn, tài liệu nhiều
- ✅ Tương thích hoàn hảo với Laravel + Node.js
- ✅ Package manager (APT) mạnh mẽ

**Download:** https://ubuntu.com/download/server

### Cấu Hình VPS Khuyến Nghị

**Tối Thiểu (Test):**
- CPU: 2 cores
- RAM: 4GB
- Disk: 40GB SSD
- Giá: ~$12/tháng

**Production:**
- CPU: 4 cores
- RAM: 8GB
- Disk: 100GB SSD NVMe
- Giá: ~$24-48/tháng

**VPS Providers:**
- Vultr (Singapore): $12-24/tháng
- DigitalOcean (Singapore): $12-24/tháng
- Linode (Singapore): $12-24/tháng

---

## 📦 BƯỚC 1: ĐÓNG GÓI DỰ ÁN

### Trên Windows (XAMPP)

```bash
# Mở Git Bash hoặc PowerShell

# 1. Backup dự án School (Laravel)
cd c:/xampp/htdocs/school/deployment-scripts
bash backup-school.sh

# Output: school-backup-TIMESTAMP.tar.gz

# 2. Backup dự án Website (WordPress) - nếu cần
bash backup-website.sh

# Output: website-backup-TIMESTAMP.tar.gz
```

**Kết quả:**
- ✅ File `school-backup-TIMESTAMP.tar.gz` - Khoảng 50-200MB
- ✅ Chứa: Laravel code, database, Zalo service, configs

---

## 🚀 BƯỚC 2: UPLOAD VÀ DEPLOY LÊN VPS

### A. Mua VPS và Cài OS

1. Đăng ký VPS (Vultr/DigitalOcean)
2. Chọn:
   - **OS:** Ubuntu 22.04 LTS
   - **Location:** Singapore
   - **Size:** 4GB RAM ($12/tháng)
3. Trỏ domain về VPS IP:
   ```
   school.yourdomain.com → VPS_IP_ADDRESS
   ```

### B. Upload Backup Lên VPS

```bash
# Trên máy local (thay YOUR_VPS_IP)
scp school-backup-*.tar.gz root@YOUR_VPS_IP:/root/
```

### C. Chạy Deploy Script

```bash
# 1. SSH vào VPS
ssh root@YOUR_VPS_IP

# 2. Giải nén backup
cd /root
tar -xzf school-backup-*.tar.gz
cd school-backup-*/

# 3. Chạy script tự động cài đặt (15-30 phút)
sudo bash deployment-scripts/deploy-vps.sh
```

**Script sẽ hỏi:**
- Domain name: `school.yourdomain.com`
- Email cho SSL: `your@email.com`
- Database name: `school_db` (Enter để dùng mặc định)
- Database user: `school_user`
- Database password: (nhập password mạnh)

**Script sẽ tự động cài:**
1. ✅ Nginx web server
2. ✅ PHP 8.2 + PHP-FPM
3. ✅ MySQL 8.0
4. ✅ Node.js 20 + PM2
5. ✅ Redis
6. ✅ Composer
7. ✅ SSL Certificate (Let's Encrypt)
8. ✅ Firewall
9. ✅ Deploy Laravel app
10. ✅ Start Zalo service
11. ✅ Queue workers
12. ✅ Cron jobs
13. ✅ Auto backup

### D. Kiểm Tra

```bash
# Truy cập website
https://school.yourdomain.com

# Check services
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
pm2 status
```

---

## 🔧 SAU KHI DEPLOY

### Bảo Mật

```bash
# 1. Đổi SSH port
sudo nano /etc/ssh/sshd_config
# Đổi Port 22 → Port 2222
sudo systemctl restart sshd

# 2. Setup SSH key (khuyến nghị)
# 3. Disable password login
```

### Monitoring

```bash
# Check logs
tail -f /var/www/school/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
pm2 logs school-zalo
```

### Backup

```bash
# Backup tự động mỗi ngày 2 AM
# Xem: /root/backup-daily.sh
# Logs: /var/log/backup.log
```

---

## 📝 QUY TRÌNH CẬP NHẬT CODE SAU NÀY

```bash
# 1. Upload code mới lên VPS
scp -r app/ root@YOUR_VPS_IP:/var/www/school/

# 2. SSH vào VPS
ssh root@YOUR_VPS_IP

# 3. Chạy quick deploy
cd /var/www/school
sudo bash deployment-scripts/quick-deploy.sh
```

---

## 🐳 OPTION: DEPLOY VỚI DOCKER

Nếu muốn dùng Docker:

```bash
# 1. Cài Docker trên VPS
curl -fsSL https://get.docker.com | sh

# 2. Upload docker-compose.yml
scp docker-compose.yml root@YOUR_VPS_IP:/root/

# 3. Start
docker-compose up -d
```

---

## 📊 TỔNG QUAN FILES ĐÃ TẠO

```
school/
├── DEPLOYMENT_GUIDE.md          # Hướng dẫn chi tiết
├── DEPLOYMENT_QUICKSTART.md     # File này (hướng dẫn nhanh)
├── Dockerfile                   # Docker config
├── docker-compose.yml           # Docker compose
└── deployment-scripts/
    ├── README.md                # Hướng dẫn scripts
    ├── backup-school.sh         # ⭐ Backup Laravel
    ├── backup-website.sh        # Backup WordPress
    ├── deploy-vps.sh            # ⭐ Deploy tự động
    └── quick-deploy.sh          # Update code nhanh
```

---

## ⚡ CHECKLIST TRƯỚC KHI GO LIVE

- [ ] Domain đã trỏ về VPS
- [ ] VPS đã cài Ubuntu 22.04 LTS
- [ ] File backup đã tạo: `school-backup-*.tar.gz`
- [ ] Upload file lên VPS thành công
- [ ] Chạy `deploy-vps.sh` thành công
- [ ] Website truy cập được: `https://school.yourdomain.com`
- [ ] SSL certificate đã cài (tự động)
- [ ] Services đang chạy (nginx, php, mysql, redis, pm2)
- [ ] Database đã import
- [ ] Zalo service hoạt động
- [ ] Queue worker chạy
- [ ] Firewall đã bật
- [ ] Backup tự động đã setup

---

## 🆘 GẶP LỖI?

### Lỗi Permission
```bash
sudo chown -R www-data:www-data /var/www/school
sudo chmod -R 755 /var/www/school/storage
```

### Lỗi Database
```bash
sudo systemctl status mysql
php artisan tinker
>>> DB::connection()->getPdo();
```

### Website Không Mở
```bash
sudo nginx -t
sudo systemctl restart nginx
tail -f /var/log/nginx/error.log
```

### Xem Logs
```bash
# Laravel
tail -f /var/www/school/storage/logs/laravel.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP
tail -f /var/log/php8.2-fpm.log

# Zalo
pm2 logs school-zalo
```

---

## 📚 TÀI LIỆU CHI TIẾT

- **DEPLOYMENT_GUIDE.md** - Hướng dẫn đầy đủ
- **deployment-scripts/README.md** - Chi tiết các scripts

---

## 🎉 HOÀN TẤT!

Sau khi chạy xong script, bạn sẽ có:
- ✅ Website Laravel chạy trên `https://school.yourdomain.com`
- ✅ SSL certificate tự động
- ✅ Zalo WebSocket hoạt động
- ✅ Queue workers chạy background
- ✅ Backup tự động mỗi ngày
- ✅ Firewall bảo vệ
- ✅ Monitoring tools

**Thời gian:** ~20-30 phút (tùy VPS)
**Chi phí:** ~$12-48/tháng

---

**Chúc mừng bạn đã deploy thành công! 🚀**

Nếu gặp vấn đề, xem file `DEPLOYMENT_GUIDE.md` để biết chi tiết hoặc check logs.
