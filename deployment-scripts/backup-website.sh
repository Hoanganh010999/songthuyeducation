#!/bin/bash

################################################################################
# BACKUP SCRIPT CHO DỰ ÁN WEBSITE (WORDPRESS)
#
# Script này sẽ:
# 1. Export WordPress database
# 2. Đóng gói wp-content (themes, plugins, uploads)
# 3. Backup wp-config.php
# 4. Tạo file .tar.gz để upload lên VPS
#
# Sử dụng: bash backup-website.sh
################################################################################

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}====================================${NC}"
echo -e "${GREEN}  BACKUP DỰ ÁN WEBSITE (WORDPRESS)  ${NC}"
echo -e "${GREEN}====================================${NC}"
echo ""

# Configuration
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="website-backup-${TIMESTAMP}"
CURRENT_DIR=$(pwd)
PROJECT_ROOT="c:/xampp/htdocs/website"

# Database configuration (đọc từ wp-config.php)
if [ -f "$PROJECT_ROOT/wp-config.php" ]; then
    DB_NAME=$(grep "DB_NAME" "$PROJECT_ROOT/wp-config.php" | cut -d "'" -f 4)
    DB_USER=$(grep "DB_USER" "$PROJECT_ROOT/wp-config.php" | cut -d "'" -f 4)
    DB_PASSWORD=$(grep "DB_PASSWORD" "$PROJECT_ROOT/wp-config.php" | cut -d "'" -f 4)
    DB_HOST=$(grep "DB_HOST" "$PROJECT_ROOT/wp-config.php" | cut -d "'" -f 4)
else
    echo -e "${RED}Error: wp-config.php not found!${NC}"
    exit 1
fi

echo -e "${YELLOW}📁 Tạo thư mục backup...${NC}"
mkdir -p "$BACKUP_DIR"

echo -e "${YELLOW}📊 Export WordPress database...${NC}"
mysqldump -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" > "$BACKUP_DIR/wordpress-database.sql"
echo -e "${GREEN}  ✓ Database exported: wordpress-database.sql${NC}"

echo -e "${YELLOW}📦 Đóng gói WordPress files...${NC}"

# Backup wp-content (themes, plugins, uploads)
echo "  Copying wp-content..."
mkdir -p "$BACKUP_DIR/wp-content"
cp -r "$PROJECT_ROOT/wp-content/themes" "$BACKUP_DIR/wp-content/" 2>/dev/null || true
cp -r "$PROJECT_ROOT/wp-content/plugins" "$BACKUP_DIR/wp-content/" 2>/dev/null || true
cp -r "$PROJECT_ROOT/wp-content/uploads" "$BACKUP_DIR/wp-content/" 2>/dev/null || true

# Backup wp-config.php (as template)
echo "  Copying wp-config.php..."
cp "$PROJECT_ROOT/wp-config.php" "$BACKUP_DIR/wp-config.php.backup"

# Backup .htaccess
if [ -f "$PROJECT_ROOT/.htaccess" ]; then
    echo "  Copying .htaccess..."
    cp "$PROJECT_ROOT/.htaccess" "$BACKUP_DIR/.htaccess.backup"
fi

# Copy setup scripts
echo "  Copying setup scripts..."
for script in "$PROJECT_ROOT"/*.php; do
    filename=$(basename "$script")
    # Skip wp-*.php files, only copy setup scripts
    if [[ $filename == create-* ]] || [[ $filename == setup-* ]] || [[ $filename == add-* ]]; then
        cp "$script" "$BACKUP_DIR/" 2>/dev/null || true
    fi
done

echo -e "${GREEN}  ✓ WordPress files packaged${NC}"

echo -e "${YELLOW}📝 Tạo danh sách plugins đã cài...${NC}"
cat > "$BACKUP_DIR/installed-plugins.txt" << EOF
# Danh sách plugins đã cài đặt
# Cài lại bằng WP-CLI hoặc WordPress admin

$(ls -1 "$PROJECT_ROOT/wp-content/plugins" 2>/dev/null || echo "No plugins found")
EOF
echo -e "${GREEN}  ✓ Plugin list created${NC}"

echo -e "${YELLOW}📝 Tạo file README cho backup...${NC}"
cat > "$BACKUP_DIR/RESTORE_INSTRUCTIONS.md" << 'EOF'
# Hướng Dẫn Restore WordPress Trên VPS

## Bước 1: Upload file backup lên VPS

```bash
# Trên máy local, nén backup
tar -czf website-backup.tar.gz website-backup-TIMESTAMP/

# Upload lên VPS
scp website-backup.tar.gz root@YOUR_VPS_IP:/root/
```

## Bước 2: Giải nén và cài WordPress trên VPS

```bash
# SSH vào VPS
ssh root@YOUR_VPS_IP

# Cài WordPress mới
cd /var/www
wget https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
mv wordpress website
chown -R www-data:www-data /var/www/website

# Tạo database
mysql -u root -p
CREATE DATABASE website_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'website_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON website_db.* TO 'website_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Bước 3: Restore backup

```bash
# Giải nén backup
cd /root
tar -xzf website-backup.tar.gz
cd website-backup-TIMESTAMP/

# Import database
mysql -u website_user -p website_db < wordpress-database.sql

# Restore wp-content
cp -r wp-content/* /var/www/website/wp-content/

# Restore wp-config.php (chỉnh sửa thông tin database)
cp wp-config.php.backup /var/www/website/wp-config.php
nano /var/www/website/wp-config.php  # Sửa DB credentials

# Restore .htaccess
cp .htaccess.backup /var/www/website/.htaccess

# Set permissions
chown -R www-data:www-data /var/www/website
chmod -R 755 /var/www/website
```

## Bước 4: Cập nhật URLs trong database

```bash
# Nếu domain mới khác domain cũ
cd /var/www/website
wp search-replace 'http://localhost/website' 'https://yourdomain.com' --allow-root

# Hoặc dùng plugin Better Search Replace trong WordPress admin
```

## Bước 5: Cài lại plugins (nếu cần)

Xem file `installed-plugins.txt` để biết danh sách plugins cần cài.

## Bước 6: Test

- Truy cập website: https://yourdomain.com
- Login admin: https://yourdomain.com/wp-admin
- Kiểm tra images, themes, plugins

EOF
echo -e "${GREEN}  ✓ README created${NC}"

echo -e "${YELLOW}📝 Tạo wp-config.php template cho VPS...${NC}"
cat > "$BACKUP_DIR/wp-config-vps.php" << 'EOF'
<?php
/**
 * WordPress Configuration for VPS
 *
 * Chỉnh sửa các thông tin sau:
 * - Database credentials
 * - Authentication Unique Keys and Salts
 * - Table prefix (nếu cần)
 */

// ** Database settings ** //
define( 'DB_NAME', 'website_db' );
define( 'DB_USER', 'website_user' );
define( 'DB_PASSWORD', 'YOUR_STRONG_PASSWORD_HERE' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts
 * Generate new keys: https://api.wordpress.org/secret-key/1.1/salt/
 */
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');

// ** WordPress Database Table prefix ** //
$table_prefix = 'wp_';

// ** WordPress debugging mode ** //
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );

// ** SSL ** //
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
    $_SERVER['HTTPS'] = 'on';
}

// ** Increase memory limit ** //
define( 'WP_MEMORY_LIMIT', '256M' );

// ** Absolute path to the WordPress directory ** //
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

// ** Sets up WordPress vars and included files ** //
require_once ABSPATH . 'wp-settings.php';
EOF
echo -e "${GREEN}  ✓ wp-config template created${NC}"

echo -e "${YELLOW}🗜️  Nén backup thành file .tar.gz...${NC}"
tar -czf "${BACKUP_DIR}.tar.gz" "$BACKUP_DIR"
BACKUP_SIZE=$(du -h "${BACKUP_DIR}.tar.gz" | cut -f1)
echo -e "${GREEN}  ✓ Backup created: ${BACKUP_DIR}.tar.gz (${BACKUP_SIZE})${NC}"

echo -e "${YELLOW}🧹 Dọn dẹp thư mục tạm...${NC}"
rm -rf "$BACKUP_DIR"
echo -e "${GREEN}  ✓ Cleanup completed${NC}"

echo ""
echo -e "${GREEN}====================================${NC}"
echo -e "${GREEN}  ✅ BACKUP HOÀN TẤT!              ${NC}"
echo -e "${GREEN}====================================${NC}"
echo ""
echo -e "📦 File backup: ${YELLOW}${BACKUP_DIR}.tar.gz${NC}"
echo -e "📊 Kích thước: ${YELLOW}${BACKUP_SIZE}${NC}"
echo ""
echo -e "🚀 ${YELLOW}Bước tiếp theo:${NC}"
echo "   1. Upload file này lên VPS"
echo "   2. Cài WordPress mới trên VPS"
echo "   3. Import database và restore files"
echo "   4. Xem RESTORE_INSTRUCTIONS.md để biết chi tiết"
echo ""
