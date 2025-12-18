#!/bin/bash

################################################################################
# FIX LARAVEL DEPLOYMENT ERROR
# Fix lỗi: artisan on line 14 / package:discover error
################################################################################

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}  FIX LARAVEL ERROR                   ${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""

APP_DIR="/var/www/school"
DB_NAME="school_db"
DB_USER="school_user"

# Check if we're in the right directory
if [ -d "$APP_DIR" ]; then
    cd $APP_DIR
    echo -e "${GREEN}✓ Found Laravel app at: $APP_DIR${NC}"
else
    echo -e "${RED}✗ Laravel app not found at: $APP_DIR${NC}"
    echo "Please run deployment script first"
    exit 1
fi

echo -e "${YELLOW}[1/10] Checking .env file...${NC}"
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ Created .env from .env.example${NC}"
    else
        echo -e "${RED}✗ No .env.example found!${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✓ .env file exists${NC}"
fi

echo -e "${YELLOW}[2/10] Asking for database password...${NC}"
read -sp "Enter MySQL password for ${DB_USER}: " DB_PASSWORD
echo ""

echo -e "${YELLOW}[3/10] Updating .env configuration...${NC}"
sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|APP_URL=.*|APP_URL=http://103.121.90.143|" .env
sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
sed -i "s|DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|DB_PORT=.*|DB_PORT=3306|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
sed -i "s|CACHE_DRIVER=.*|CACHE_DRIVER=file|" .env
sed -i "s|SESSION_DRIVER=.*|SESSION_DRIVER=file|" .env
sed -i "s|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=sync|" .env

echo -e "${GREEN}✓ .env configured${NC}"

echo -e "${YELLOW}[4/10] Generating APP_KEY...${NC}"
php artisan key:generate --force
echo -e "${GREEN}✓ APP_KEY generated${NC}"

echo -e "${YELLOW}[5/10] Clearing caches...${NC}"
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
echo -e "${GREEN}✓ Caches cleared${NC}"

echo -e "${YELLOW}[6/10] Fixing permissions...${NC}"
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR/storage
chmod -R 755 $APP_DIR/bootstrap/cache
chmod 644 .env
echo -e "${GREEN}✓ Permissions fixed${NC}"

echo -e "${YELLOW}[7/10] Running composer dump-autoload...${NC}"
composer dump-autoload --optimize
echo -e "${GREEN}✓ Autoload dumped${NC}"

echo -e "${YELLOW}[8/10] Installing composer dependencies...${NC}"
composer install --no-dev --optimize-autoload --no-interaction
echo -e "${GREEN}✓ Composer dependencies installed${NC}"

echo -e "${YELLOW}[9/10] Installing npm dependencies and building...${NC}"
if [ -f "package.json" ]; then
    npm install --production
    npm run build
    echo -e "${GREEN}✓ Assets built${NC}"
else
    echo -e "${YELLOW}⚠ No package.json found, skipping npm${NC}"
fi

echo -e "${YELLOW}[10/10] Running package:discover...${NC}"
php artisan package:discover --ansi
echo -e "${GREEN}✓ Packages discovered${NC}"

echo ""
echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}  ✅ ERRORS FIXED!                    ${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""
echo -e "${YELLOW}Now running final setup...${NC}"

# Import database if exists
BACKUP_DIR=$(find /root -name "school-backup-*" -type d | head -1)
if [ -n "$BACKUP_DIR" ] && [ -f "$BACKUP_DIR/database.sql" ]; then
    echo -e "${YELLOW}Found database backup, importing...${NC}"
    mysql -u${DB_USER} -p${DB_PASSWORD} ${DB_NAME} < "$BACKUP_DIR/database.sql" 2>/dev/null && echo -e "${GREEN}✓ Database imported${NC}" || echo -e "${YELLOW}⚠ Database import skipped (may already exist)${NC}"
else
    echo -e "${YELLOW}Running migrations...${NC}"
    php artisan migrate --force
fi

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo -e "${GREEN}All done! 🎉${NC}"
echo ""
echo "Next steps:"
echo "1. Visit: http://103.121.90.143"
echo "2. Test the website"
echo ""
