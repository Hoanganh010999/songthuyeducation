#!/bin/bash

################################################################################
# BACKUP SCRIPT CHO DỰ ÁN SCHOOL (LARAVEL)
#
# Script này sẽ:
# 1. Export database
# 2. Đóng gói source code
# 3. Backup node_modules config
# 4. Tạo file .tar.gz để upload lên VPS
#
# Sử dụng: bash backup-school.sh
################################################################################

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=================================${NC}"
echo -e "${GREEN}  BACKUP DỰ ÁN SCHOOL (LARAVEL)  ${NC}"
echo -e "${GREEN}=================================${NC}"
echo ""

# Configuration
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="school-backup-${TIMESTAMP}"
CURRENT_DIR=$(pwd)
PROJECT_ROOT="c:/xampp/htdocs/school"

# Database configuration (đọc từ .env)
if [ -f "$PROJECT_ROOT/.env" ]; then
    DB_CONNECTION=$(grep DB_CONNECTION "$PROJECT_ROOT/.env" | cut -d '=' -f2)
    DB_DATABASE=$(grep DB_DATABASE "$PROJECT_ROOT/.env" | cut -d '=' -f2)
    DB_USERNAME=$(grep DB_USERNAME "$PROJECT_ROOT/.env" | cut -d '=' -f2)
    DB_PASSWORD=$(grep DB_PASSWORD "$PROJECT_ROOT/.env" | cut -d '=' -f2)
else
    echo -e "${RED}Error: .env file not found!${NC}"
    exit 1
fi

echo -e "${YELLOW}📁 Tạo thư mục backup...${NC}"
mkdir -p "$BACKUP_DIR"

echo -e "${YELLOW}📊 Export database...${NC}"
if [ "$DB_CONNECTION" == "mysql" ]; then
    echo "  Database type: MySQL"
    mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/database.sql"
    echo -e "${GREEN}  ✓ Database exported: database.sql${NC}"
elif [ "$DB_CONNECTION" == "sqlite" ]; then
    echo "  Database type: SQLite"
    if [ -f "$PROJECT_ROOT/database/database.sqlite" ]; then
        cp "$PROJECT_ROOT/database/database.sqlite" "$BACKUP_DIR/database.sqlite"
        echo -e "${GREEN}  ✓ Database copied: database.sqlite${NC}"
    else
        echo -e "${YELLOW}  ⚠ SQLite database not found${NC}"
    fi
else
    echo -e "${YELLOW}  ⚠ Unknown database type: $DB_CONNECTION${NC}"
fi

echo -e "${YELLOW}📦 Đóng gói source code...${NC}"

# Copy essential files
echo "  Copying Laravel files..."
cp -r "$PROJECT_ROOT/app" "$BACKUP_DIR/"
cp -r "$PROJECT_ROOT/bootstrap" "$BACKUP_DIR/"
cp -r "$PROJECT_ROOT/config" "$BACKUP_DIR/"
cp -r "$PROJECT_ROOT/database/migrations" "$BACKUP_DIR/migrations"
cp -r "$PROJECT_ROOT/database/seeders" "$BACKUP_DIR/seeders" 2>/dev/null || true
cp -r "$PROJECT_ROOT/public" "$BACKUP_DIR/"
cp -r "$PROJECT_ROOT/resources" "$BACKUP_DIR/"
cp -r "$PROJECT_ROOT/routes" "$BACKUP_DIR/"
cp -r "$PROJECT_ROOT/storage" "$BACKUP_DIR/"

# Copy Zalo service
echo "  Copying Zalo service..."
cp -r "$PROJECT_ROOT/zalo-service" "$BACKUP_DIR/"

# Remove node_modules from zalo-service (sẽ cài lại trên VPS)
if [ -d "$BACKUP_DIR/zalo-service/node_modules" ]; then
    echo "  Removing zalo-service/node_modules (will reinstall on VPS)..."
    rm -rf "$BACKUP_DIR/zalo-service/node_modules"
fi

# Copy configuration files
echo "  Copying config files..."
cp "$PROJECT_ROOT/composer.json" "$BACKUP_DIR/"
cp "$PROJECT_ROOT/composer.lock" "$BACKUP_DIR/" 2>/dev/null || true
cp "$PROJECT_ROOT/package.json" "$BACKUP_DIR/"
cp "$PROJECT_ROOT/package-lock.json" "$BACKUP_DIR/" 2>/dev/null || true
cp "$PROJECT_ROOT/vite.config.js" "$BACKUP_DIR/" 2>/dev/null || true
cp "$PROJECT_ROOT/tailwind.config.js" "$BACKUP_DIR/" 2>/dev/null || true
cp "$PROJECT_ROOT/postcss.config.js" "$BACKUP_DIR/" 2>/dev/null || true
cp "$PROJECT_ROOT/.env.example" "$BACKUP_DIR/"
cp "$PROJECT_ROOT/artisan" "$BACKUP_DIR/"

# Copy documentation
echo "  Copying documentation..."
cp "$PROJECT_ROOT/README.md" "$BACKUP_DIR/" 2>/dev/null || true
cp "$PROJECT_ROOT/DEPLOYMENT_GUIDE.md" "$BACKUP_DIR/" 2>/dev/null || true
cp -r "$PROJECT_ROOT/deployment-scripts" "$BACKUP_DIR/" 2>/dev/null || true

# Create .env template for VPS
echo "  Creating .env template..."
cat > "$BACKUP_DIR/.env.vps.example" << 'EOF'
APP_NAME=School
APP_ENV=production
APP_KEY=  # Generate với: php artisan key:generate
APP_DEBUG=false
APP_TIMEZONE=Asia/Ho_Chi_Minh
APP_URL=https://school.yourdomain.com

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_db
DB_USERNAME=school_user
DB_PASSWORD=  # Strong password

BROADCAST_CONNECTION=log
CACHE_STORE=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# WebSocket
WS_URL=http://localhost:3001
VITE_WS_URL=https://school.yourdomain.com

# Zalo Service
ZALO_SERVICE_URL=http://localhost:3001
NODE_ENV=production
PORT=3001
LARAVEL_URL=http://127.0.0.1:8000

# Mail (configure if needed)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
EOF

echo -e "${GREEN}  ✓ Source code packaged${NC}"

echo -e "${YELLOW}📝 Tạo file README cho backup...${NC}"
cat > "$BACKUP_DIR/RESTORE_INSTRUCTIONS.md" << 'EOF'
# Hướng Dẫn Restore Trên VPS

## Bước 1: Upload file backup lên VPS

```bash
# Trên máy local, nén backup
tar -czf school-backup.tar.gz school-backup-TIMESTAMP/

# Upload lên VPS (thay YOUR_VPS_IP)
scp school-backup.tar.gz root@YOUR_VPS_IP:/root/
```

## Bước 2: Giải nén trên VPS

```bash
# SSH vào VPS
ssh root@YOUR_VPS_IP

# Giải nén
cd /root
tar -xzf school-backup.tar.gz
cd school-backup-TIMESTAMP/
```

## Bước 3: Chạy deployment script

```bash
# Chạy script deploy (trong thư mục deployment-scripts/)
bash deployment-scripts/deploy-vps.sh
```

## Bước 4: Import database

```bash
# Import database
mysql -u school_user -p school_db < database.sql
```

## Bước 5: Hoàn tất

```bash
# Set permissions
chown -R www-data:www-data /var/www/school
chmod -R 755 /var/www/school/storage
chmod -R 755 /var/www/school/bootstrap/cache

# Generate key
cd /var/www/school
php artisan key:generate

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (nếu cần)
php artisan migrate --force

# Start services
pm2 start zalo-service/server.js --name school-zalo
supervisorctl restart school-worker
```

Xem file DEPLOYMENT_GUIDE.md để biết chi tiết!
EOF

echo -e "${GREEN}  ✓ README created${NC}"

echo -e "${YELLOW}🗜️  Nén backup thành file .tar.gz...${NC}"
tar -czf "${BACKUP_DIR}.tar.gz" "$BACKUP_DIR"
BACKUP_SIZE=$(du -h "${BACKUP_DIR}.tar.gz" | cut -f1)
echo -e "${GREEN}  ✓ Backup created: ${BACKUP_DIR}.tar.gz (${BACKUP_SIZE})${NC}"

echo -e "${YELLOW}🧹 Dọn dẹp thư mục tạm...${NC}"
rm -rf "$BACKUP_DIR"
echo -e "${GREEN}  ✓ Cleanup completed${NC}"

echo ""
echo -e "${GREEN}=================================${NC}"
echo -e "${GREEN}  ✅ BACKUP HOÀN TẤT!           ${NC}"
echo -e "${GREEN}=================================${NC}"
echo ""
echo -e "📦 File backup: ${YELLOW}${BACKUP_DIR}.tar.gz${NC}"
echo -e "📊 Kích thước: ${YELLOW}${BACKUP_SIZE}${NC}"
echo ""
echo -e "🚀 ${YELLOW}Bước tiếp theo:${NC}"
echo "   1. Upload file này lên VPS"
echo "   2. Chạy script deploy-vps.sh trên VPS"
echo "   3. Xem DEPLOYMENT_GUIDE.md để biết chi tiết"
echo ""
