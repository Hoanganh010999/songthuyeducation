#!/bin/bash
# =====================================================================
# Deploy Customer View All Feature to VPS
# =====================================================================
# Author: Claude AI Assistant
# Date: 2025-11-23
# Purpose: Deploy customer view_all permission and zalo unread count fix
# Usage: bash deploy-customer-feature-to-vps.sh
# =====================================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
SSH_KEY="$HOME/.ssh/vps_key"
SSH_PORT=26266
SSH_HOST="root@103.121.90.143"
REMOTE_PATH="/var/www/school"
LOCAL_PATH="/c/xampp/htdocs/school"

echo ""
echo "========================================"
echo " DEPLOY CUSTOMER FEATURE TO VPS"
echo "========================================"
echo ""

# Check if running in Git Bash on Windows
if [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "win32" ]]; then
    LOCAL_PATH="c:/xampp/htdocs/school"
fi

echo -e "${YELLOW}[1/8]${NC} Checking local files..."
if [ ! -f "$LOCAL_PATH/database/seeders/CustomersViewAllPermissionSeeder.php" ]; then
    echo -e "${RED}ERROR: CustomersViewAllPermissionSeeder.php not found!${NC}"
    exit 1
fi
echo -e "${GREEN}  ✓ Seeder file found${NC}"

echo ""
echo -e "${YELLOW}[2/8]${NC} Creating backup on VPS..."
ssh -i "$SSH_KEY" -p $SSH_PORT $SSH_HOST "cd $REMOTE_PATH && mysqldump -u root -p'Kh0ngbiet@' school_db > backup_before_customer_feature_\$(date +%Y%m%d_%H%M%S).sql && echo '✓ Database backed up'"

echo ""
echo -e "${YELLOW}[3/8]${NC} Backing up code on VPS..."
ssh -i "$SSH_KEY" -p $SSH_PORT $SSH_HOST "cd $REMOTE_PATH && tar -czf backup_code_\$(date +%Y%m%d_%H%M%S).tar.gz app routes resources database && echo '✓ Code backed up'"

echo ""
echo -e "${YELLOW}[4/8]${NC} Uploading seeder file..."
scp -i "$SSH_KEY" -P $SSH_PORT "$LOCAL_PATH/database/seeders/CustomersViewAllPermissionSeeder.php" $SSH_HOST:$REMOTE_PATH/database/seeders/
echo -e "${GREEN}  ✓ Seeder uploaded${NC}"

echo ""
echo -e "${YELLOW}[5/8]${NC} Uploading Customer Model..."
scp -i "$SSH_KEY" -P $SSH_PORT "$LOCAL_PATH/app/Models/Customer.php" $SSH_HOST:$REMOTE_PATH/app/Models/
echo -e "${GREEN}  ✓ Customer.php uploaded${NC}"

echo ""
echo -e "${YELLOW}[6/8]${NC} Uploading ZaloController..."
scp -i "$SSH_KEY" -P $SSH_PORT "$LOCAL_PATH/app/Http/Controllers/Api/ZaloController.php" $SSH_HOST:$REMOTE_PATH/app/Http/Controllers/Api/
echo -e "${GREEN}  ✓ ZaloController.php uploaded${NC}"

echo ""
echo -e "${YELLOW}[7/8]${NC} Uploading routes..."
scp -i "$SSH_KEY" -P $SSH_PORT "$LOCAL_PATH/routes/api.php" $SSH_HOST:$REMOTE_PATH/routes/
echo -e "${GREEN}  ✓ api.php uploaded${NC}"

echo ""
echo -e "${YELLOW}[8/8]${NC} Uploading DashboardLayout..."
scp -i "$SSH_KEY" -P $SSH_PORT "$LOCAL_PATH/resources/js/layouts/DashboardLayout.vue" $SSH_HOST:$REMOTE_PATH/resources/js/layouts/
echo -e "${GREEN}  ✓ DashboardLayout.vue uploaded${NC}"

echo ""
echo "========================================"
echo " FILES UPLOADED SUCCESSFULLY"
echo "========================================"
echo ""
echo "Now running post-deployment tasks..."
echo ""

echo -e "${YELLOW}[BACKEND]${NC} Running seeder on VPS..."
if ssh -i "$SSH_KEY" -p $SSH_PORT $SSH_HOST "cd $REMOTE_PATH && php artisan db:seed --class=CustomersViewAllPermissionSeeder"; then
    echo -e "${GREEN}  ✓ Seeder completed${NC}"
else
    echo -e "${RED}  ✗ Seeder failed! Check VPS logs.${NC}"
fi

echo ""
echo -e "${YELLOW}[BACKEND]${NC} Clearing caches..."
ssh -i "$SSH_KEY" -p $SSH_PORT $SSH_HOST "cd $REMOTE_PATH && php artisan config:clear && php artisan route:clear && php artisan cache:clear"
echo -e "${GREEN}  ✓ Caches cleared${NC}"

echo ""
echo -e "${YELLOW}[FRONTEND]${NC} Building assets on VPS..."
echo "This may take 1-2 minutes..."
if ssh -i "$SSH_KEY" -p $SSH_PORT $SSH_HOST "cd $REMOTE_PATH && npm run build"; then
    echo -e "${GREEN}  ✓ Build completed${NC}"
else
    echo -e "${RED}  ✗ Build failed! Check VPS logs.${NC}"
fi

echo ""
echo -e "${YELLOW}[VERIFY]${NC} Checking permission in database..."
ssh -i "$SSH_KEY" -p $SSH_PORT $SSH_HOST "mysql -u root -p'Kh0ngbiet@' school_db -e \"SELECT name, display_name FROM permissions WHERE name='customers.view_all'\""

echo ""
echo "========================================"
echo " DEPLOYMENT COMPLETED!"
echo "========================================"
echo ""
echo "Next steps:"
echo "1. Test the application: https://admin.songthuy.edu.vn"
echo "2. Check console logs (F12) for errors"
echo "3. Verify badge on Sales (Customers) icon"
echo "4. Test with different user roles"
echo ""
echo "If there are issues, rollback with:"
echo "  ssh -i $SSH_KEY -p $SSH_PORT $SSH_HOST \"cd $REMOTE_PATH && ls -lt backup_*.sql backup_*.tar.gz\""
echo ""
