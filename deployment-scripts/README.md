# 📦 Deployment Scripts

Bộ scripts để đóng gói và triển khai dự án School lên VPS.

## 📋 Danh Sách Scripts

### 1. `backup-school.sh` - Backup Dự Án Laravel
Đóng gói toàn bộ dự án School (Laravel + Zalo service) để upload lên VPS.

**Sử dụng:**
```bash
cd c:/xampp/htdocs/school/deployment-scripts
bash backup-school.sh
```

**Output:**
- `school-backup-TIMESTAMP.tar.gz` - File nén chứa toàn bộ dự án
- Bao gồm: source code, database dump, configs

### 2. `backup-website.sh` - Backup Dự Án WordPress
Đóng gói dự án Website (WordPress) để upload lên VPS.

**Sử dụng:**
```bash
cd c:/xampp/htdocs/school/deployment-scripts
bash backup-website.sh
```

**Output:**
- `website-backup-TIMESTAMP.tar.gz` - File nén WordPress
- Bao gồm: wp-content, database dump, wp-config

### 3. `deploy-vps.sh` - Cài Đặt VPS Từ Đầu
Script cài đặt và cấu hình VPS hoàn chỉnh từ đầu.

**Yêu cầu:**
- VPS với Ubuntu 22.04 LTS
- Root access hoặc sudo
- File backup đã upload lên /root/

**Sử dụng:**
```bash
# 1. Upload backup file lên VPS
scp school-backup-*.tar.gz root@YOUR_VPS_IP:/root/

# 2. SSH vào VPS
ssh root@YOUR_VPS_IP

# 3. Giải nén backup
cd /root
tar -xzf school-backup-*.tar.gz
cd school-backup-*/

# 4. Chạy deploy script
sudo bash deployment-scripts/deploy-vps.sh
```

**Script sẽ cài đặt:**
- ✅ Nginx
- ✅ PHP 8.2 + PHP-FPM
- ✅ MySQL 8.0
- ✅ Node.js 20 LTS + PM2
- ✅ Redis
- ✅ Composer
- ✅ SSL Certificate (Let's Encrypt)
- ✅ Firewall (UFW)
- ✅ Supervisor (Queue workers)
- ✅ Cron jobs
- ✅ Auto backup

### 4. `quick-deploy.sh` - Deploy Nhanh (Cập Nhật Code)
Script để update code sau khi đã setup VPS.

**Sử dụng:**
```bash
# Chạy trên VPS sau khi upload code mới
cd /var/www/school
sudo bash deployment-scripts/quick-deploy.sh
```

**Script sẽ:**
- ✅ Install dependencies
- ✅ Run migrations
- ✅ Clear & rebuild cache
- ✅ Restart services
- ✅ Set permissions

## 🚀 Quy Trình Deploy Hoàn Chỉnh

### Lần Đầu Tiên (Fresh Install)

1. **Trên máy local:**
   ```bash
   cd c:/xampp/htdocs/school/deployment-scripts
   bash backup-school.sh
   ```

2. **Upload lên VPS:**
   ```bash
   scp school-backup-*.tar.gz root@YOUR_VPS_IP:/root/
   ```

3. **Trên VPS:**
   ```bash
   ssh root@YOUR_VPS_IP
   cd /root
   tar -xzf school-backup-*.tar.gz
   cd school-backup-*/
   sudo bash deployment-scripts/deploy-vps.sh
   ```

4. **Kiểm tra:**
   - Truy cập: https://your-domain.com
   - Check logs: `tail -f /var/www/school/storage/logs/laravel.log`

### Cập Nhật Code (Đã Setup VPS)

1. **Upload code mới:**
   ```bash
   # Option 1: SCP
   scp -r app/ root@YOUR_VPS_IP:/var/www/school/

   # Option 2: Git (khuyến nghị)
   ssh root@YOUR_VPS_IP
   cd /var/www/school
   git pull origin main
   ```

2. **Chạy quick deploy:**
   ```bash
   cd /var/www/school
   sudo bash deployment-scripts/quick-deploy.sh
   ```

## 🐳 Deploy Với Docker (Optional)

Xem files:
- `Dockerfile` - PHP/Laravel container
- `docker-compose.yml` - Full stack

**Sử dụng:**
```bash
docker-compose up -d
```

## 📝 Checklist Trước Khi Deploy

- [ ] Đã test code trên local
- [ ] Database migrations đã test
- [ ] .env đã cấu hình đúng
- [ ] Domain đã trỏ về VPS IP
- [ ] VPS đã có Ubuntu 22.04 LTS
- [ ] Đã backup database hiện tại (nếu update)
- [ ] Đã thông báo downtime cho users (nếu cần)

## ⚠️ Lưu Ý Quan Trọng

1. **Backup trước khi deploy:**
   - Luôn backup database trước khi run migrations
   - Backup file .env
   - Backup uploads/storage

2. **Security:**
   - Đổi SSH port sau khi deploy
   - Setup SSH key authentication
   - Disable password login
   - Review firewall rules

3. **Performance:**
   - Enable OPcache (script tự động enable)
   - Configure Redis cho cache và sessions
   - Setup CDN cho static assets (nếu cần)

4. **Monitoring:**
   - Setup uptime monitoring (UptimeRobot)
   - Configure error alerts
   - Monitor disk space
   - Check logs thường xuyên

## 🔧 Troubleshooting

### Lỗi Permission
```bash
sudo chown -R www-data:www-data /var/www/school
sudo chmod -R 755 /var/www/school/storage
sudo chmod -R 755 /var/www/school/bootstrap/cache
```

### Lỗi Database Connection
```bash
# Check MySQL
sudo systemctl status mysql

# Check .env
cat /var/www/school/.env | grep DB_

# Test connection
php artisan tinker
DB::connection()->getPdo();
```

### Lỗi Queue Không Chạy
```bash
# Check supervisor
sudo supervisorctl status school-worker

# Restart
sudo supervisorctl restart school-worker:*

# Check logs
tail -f /var/www/school/storage/logs/worker.log
```

### Lỗi WebSocket
```bash
# Check PM2
pm2 status
pm2 logs school-zalo

# Restart
pm2 restart school-zalo

# Check port
netstat -tulpn | grep 3001
```

### Clear Cache Khi Gặp Lỗi
```bash
cd /var/www/school
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

## 📞 Hỗ Trợ

- Xem file `DEPLOYMENT_GUIDE.md` cho hướng dẫn chi tiết
- Check Laravel docs: https://laravel.com/docs/deployment
- Ubuntu Server guide: https://ubuntu.com/server/docs

## 📜 License

Các scripts này được tạo bởi Claude Code để hỗ trợ deployment.
Tự do sử dụng và chỉnh sửa theo nhu cầu.
