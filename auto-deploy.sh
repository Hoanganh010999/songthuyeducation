#!/bin/bash

################################################################################
# AUTO DEPLOYMENT SCRIPT - SIMPLIFIED VERSION
# Chạy trên VPS Ubuntu 22.04 LTS
################################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}  VPS AUTO DEPLOYMENT - SCHOOL        ${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""

# Get current directory
BACKUP_DIR=$(pwd)
echo -e "${BLUE}Current directory: ${BACKUP_DIR}${NC}"
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo -e "${RED}Error: composer.json not found!${NC}"
    echo "Please run this script from the school backup directory"
    echo "Example: cd /root/school-backup-XXXXXX/"
    exit 1
fi

echo -e "${YELLOW}[1/15] Collecting information...${NC}"
read -p "Enter your domain (or press Enter to use IP): " DOMAIN
if [ -z "$DOMAIN" ]; then
    DOMAIN="103.121.90.143"
    echo "Using IP: $DOMAIN"
fi

read -p "Enter your email for SSL: " EMAIL
if [ -z "$EMAIL" ]; then
    EMAIL="admin@localhost"
fi

DB_NAME="school_db"
DB_USER="school_user"
read -sp "Enter MySQL password for school_user: " DB_PASSWORD
echo ""

APP_DIR="/var/www/school"

echo ""
echo -e "${BLUE}Configuration:${NC}"
echo "  Domain: $DOMAIN"
echo "  Email: $EMAIL"
echo "  Database: $DB_NAME"
echo "  DB User: $DB_USER"
echo "  App Directory: $APP_DIR"
echo ""
read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

echo -e "${YELLOW}[2/15] Updating system...${NC}"
apt update && apt upgrade -y

echo -e "${YELLOW}[3/15] Installing essential packages...${NC}"
apt install -y software-properties-common curl wget git unzip vim ufw

echo -e "${YELLOW}[4/15] Installing PHP 8.2...${NC}"
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y \
    php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring \
    php8.2-curl php8.2-xml php8.2-bcmath php8.2-redis php8.2-intl

echo -e "${YELLOW}[5/15] Installing Composer...${NC}"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

echo -e "${YELLOW}[6/15] Installing MySQL...${NC}"
apt install -y mysql-server

# Start MySQL
systemctl start mysql
systemctl enable mysql

# Create database and user
echo -e "${YELLOW}[7/15] Creating database...${NC}"
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo -e "${YELLOW}[8/15] Installing Redis...${NC}"
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

echo -e "${YELLOW}[9/15] Installing Node.js 20...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
npm install -g pm2
pm2 startup systemd -u root --hp /root
systemctl enable pm2-root

echo -e "${YELLOW}[10/15] Installing Nginx...${NC}"
apt install -y nginx
systemctl enable nginx

echo -e "${YELLOW}[11/15] Deploying Laravel application...${NC}"
mkdir -p $APP_DIR
cp -r * $APP_DIR/
cd $APP_DIR

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Set permissions
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR/storage
chmod -R 755 $APP_DIR/bootstrap/cache

echo -e "${YELLOW}[12/15] Configuring environment...${NC}"
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Update .env
sed -i "s|APP_URL=.*|APP_URL=http://${DOMAIN}|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env

echo -e "${YELLOW}[13/15] Importing database...${NC}"
if [ -f "${BACKUP_DIR}/database.sql" ]; then
    mysql -u${DB_USER} -p${DB_PASSWORD} ${DB_NAME} < "${BACKUP_DIR}/database.sql"
    echo "Database imported successfully"
else
    echo "No database.sql found, running migrations..."
    php artisan migrate --force
fi

echo -e "${YELLOW}[14/15] Setting up Zalo service...${NC}"
cd $APP_DIR/zalo-service
npm install --production

cat > .env << EOF
NODE_ENV=production
PORT=3001
LARAVEL_URL=http://127.0.0.1:8000
EOF

pm2 start server.js --name school-zalo
pm2 save

echo -e "${YELLOW}[15/15] Configuring Nginx...${NC}"
cat > /etc/nginx/sites-available/school << 'NGINXCONF'
server {
    listen 80;
    server_name DOMAIN_PLACEHOLDER;
    root /var/www/school/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /socket.io/ {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_read_timeout 86400;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINXCONF

sed -i "s|DOMAIN_PLACEHOLDER|${DOMAIN}|" /etc/nginx/sites-available/school

ln -sf /etc/nginx/sites-available/school /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

nginx -t && systemctl restart nginx

echo -e "${YELLOW}Configuring firewall...${NC}"
ufw --force enable
ufw allow 22/tcp
ufw allow 26266/tcp
ufw allow 80/tcp
ufw allow 443/tcp

cd $APP_DIR
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}  ✅ DEPLOYMENT COMPLETED!            ${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""
echo -e "${BLUE}🌐 Website: http://${DOMAIN}${NC}"
echo -e "${BLUE}📂 App Directory: ${APP_DIR}${NC}"
echo -e "${BLUE}🗄️  Database: ${DB_NAME}${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Visit: http://${DOMAIN}"
echo "2. Check services: pm2 status"
echo "3. Check logs: tail -f ${APP_DIR}/storage/logs/laravel.log"
echo ""
echo -e "${GREEN}Deployment successful! 🎉${NC}"
