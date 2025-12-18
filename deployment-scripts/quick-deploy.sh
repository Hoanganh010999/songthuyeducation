#!/bin/bash

################################################################################
# QUICK DEPLOY SCRIPT
#
# Script nhanh để deploy/update code lên VPS
# Sử dụng sau khi đã setup xong VPS lần đầu
#
# Sử dụng: bash quick-deploy.sh
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}  QUICK DEPLOY TO VPS          ${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

# Configuration
APP_DIR="/var/www/school"

echo -e "${YELLOW}📥 Step 1: Pull latest code (or upload new files)...${NC}"
cd $APP_DIR
# If using Git:
# git pull origin main
echo -e "${YELLOW}  Upload your updated files to $APP_DIR${NC}"
read -p "Press Enter when files are updated..."

echo -e "${YELLOW}📦 Step 2: Install dependencies...${NC}"
composer install --no-dev --optimize-autoloader
npm install
npm run build
echo -e "${GREEN}  ✓ Dependencies installed${NC}"

echo -e "${YELLOW}🗄️  Step 3: Run migrations...${NC}"
php artisan migrate --force
echo -e "${GREEN}  ✓ Migrations completed${NC}"

echo -e "${YELLOW}🔄 Step 4: Clear and cache...${NC}"
php artisan down
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
echo -e "${GREEN}  ✓ Cache cleared and rebuilt${NC}"

echo -e "${YELLOW}🔌 Step 5: Restart services...${NC}"
# Restart PHP-FPM
systemctl restart php8.2-fpm

# Restart Queue workers
supervisorctl restart school-worker:*

# Restart Zalo service
pm2 restart school-zalo

# Restart Nginx
systemctl reload nginx

echo -e "${GREEN}  ✓ Services restarted${NC}"

echo -e "${YELLOW}🔐 Step 6: Set permissions...${NC}"
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR/storage
chmod -R 755 $APP_DIR/bootstrap/cache
echo -e "${GREEN}  ✓ Permissions set${NC}"

echo ""
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}  ✅ DEPLOY COMPLETED!          ${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo -e "${YELLOW}Check logs:${NC}"
echo "  Laravel: tail -f $APP_DIR/storage/logs/laravel.log"
echo "  Nginx:   tail -f /var/log/nginx/error.log"
echo "  Zalo:    pm2 logs school-zalo"
echo ""
