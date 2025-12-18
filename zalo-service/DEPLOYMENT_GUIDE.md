 # Deployment Guide - WebSocket trên Hosting

## 📋 Tổng quan

WebSocket server đã được tích hợp vào zalo-service và **hoàn toàn có thể** triển khai trên hosting có SSH hoặc VPN.

## ✅ Câu trả lời ngắn gọn

### 1. WebSocket có thể dùng cho realtime comment/chat không?
**CÓ!** WebSocket server mới này được thiết kế riêng cho:
- ✅ Realtime comments
- ✅ Chat messages  
- ✅ Notifications
- ✅ Typing indicators
- ✅ Live updates

### 2. Có thể deploy trên hosting có SSH/VPN không?
**CÓ!** SSH/VPN không ảnh hưởng WebSocket. Chỉ cần:
- ✅ Mở port WebSocket
- ✅ Cấu hình reverse proxy (Nginx/Apache)
- ✅ Sử dụng PM2 để keep service alive

## 🚀 Hướng dẫn triển khai

### Bước 1: Chuẩn bị server

```bash
# SSH vào server
ssh user@your-server.com

# Cài đặt Node.js (nếu chưa có)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Cài đặt PM2
sudo npm install -g pm2
```

### Bước 2: Upload code

```bash
# Clone hoặc upload code lên server
cd /var/www
git clone your-repo.git school
cd school/zalo-service

# Cài đặt dependencies
npm install --production
```

### Bước 3: Cấu hình .env

```bash
# Tạo file .env
nano .env
```

```env
NODE_ENV=production
PORT=3001
LARAVEL_URL=https://yourdomain.com

# Zalo credentials (nếu có)
ZALO_COOKIE=your_cookie_here
ZALO_IMEI=your_imei_here
ZALO_USER_AGENT=your_user_agent_here
```

### Bước 4: Cấu hình Nginx

```bash
sudo nano /etc/nginx/sites-available/yourdomain.com
```

```nginx
# Upstream cho WebSocket
upstream websocket {
    server 127.0.0.1:3001;
    keepalive 64;
}

server {
    listen 80;
    server_name yourdomain.com;

    # WebSocket endpoint
    location /socket.io/ {
        proxy_pass http://websocket;
        proxy_http_version 1.1;
        
        # WebSocket headers
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Standard headers
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts (important for WebSocket)
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }

    # Zalo API endpoints
    location /api/zalo/ {
        proxy_pass http://127.0.0.1:3001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Laravel API
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/yourdomain.com /etc/nginx/sites-enabled/

# Test config
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### Bước 5: Cấu hình Firewall

```bash
# Mở port 3001 (nếu cần direct access)
sudo ufw allow 3001/tcp

# Hoặc chỉ cho phép localhost (khuyến nghị)
# Port sẽ chỉ accessible qua Nginx
```

### Bước 6: Chạy với PM2

```bash
cd /var/www/school/zalo-service

# Tạo PM2 ecosystem file
nano ecosystem.config.js
```

```javascript
module.exports = {
  apps: [{
    name: 'zalo-service',
    script: 'server.js',
    cwd: '/var/www/school/zalo-service',
    instances: 1,
    exec_mode: 'fork',
    env: {
      NODE_ENV: 'production',
      PORT: 3001
    },
    error_file: './logs/err.log',
    out_file: './logs/out.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G'
  }]
};
```

```bash
# Start service
pm2 start ecosystem.config.js

# Save PM2 config để auto-start sau reboot
pm2 save
pm2 startup

# Check status
pm2 status
pm2 logs zalo-service
```

### Bước 7: SSL/HTTPS (Khuyến nghị)

```bash
# Cài đặt Certbot
sudo apt-get install certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

Nginx sẽ tự động cấu hình SSL cho WebSocket.

## 🔒 Security

### 1. Authentication
WebSocket server yêu cầu JWT token từ Laravel:

```javascript
// Client side
const socket = io('https://yourdomain.com', {
  auth: {
    token: 'your-jwt-token-here'
  }
});
```

### 2. CORS
Chỉ cho phép domain của bạn trong `.env`:
```env
LARAVEL_URL=https://yourdomain.com
```

### 3. Rate Limiting
Có thể thêm rate limiting middleware nếu cần.

## 📊 Monitoring

### PM2 Monitoring
```bash
# Xem logs
pm2 logs zalo-service

# Xem metrics
pm2 monit

# Restart service
pm2 restart zalo-service
```

### Health Check
```bash
curl http://localhost:3001/health
```

Response:
```json
{
  "status": "ok",
  "service": "Zalo API Service",
  "timestamp": "2024-01-01T00:00:00.000Z",
  "websocket": {
    "enabled": true,
    "connections": 5,
    "users": 3
  }
}
```

## 🔄 Troubleshooting

### WebSocket không kết nối được

1. **Kiểm tra Nginx config**:
```bash
sudo nginx -t
sudo systemctl status nginx
```

2. **Kiểm tra service đang chạy**:
```bash
pm2 status
pm2 logs zalo-service
```

3. **Kiểm tra port**:
```bash
netstat -tulpn | grep 3001
```

4. **Kiểm tra firewall**:
```bash
sudo ufw status
```

### Connection timeout

- Tăng timeout trong Nginx config
- Kiểm tra network latency
- Verify SSL certificate

### Memory issues

```bash
# Restart service
pm2 restart zalo-service

# Hoặc tăng memory limit trong ecosystem.config.js
max_memory_restart: '2G'
```

## 🌐 VPN/SSH Specific

### Nếu server đằng sau VPN

1. **Port forwarding**: Đảm bảo port 3001 được forward
2. **Internal IP**: Sử dụng internal IP trong Nginx upstream
3. **Firewall rules**: Cho phép traffic từ VPN

### Nếu chỉ có SSH access

1. **SSH Tunnel** (development only):
```bash
ssh -L 3001:localhost:3001 user@server
```

2. **Production**: Cần public IP hoặc domain với DNS

## ✅ Checklist

- [ ] Node.js installed
- [ ] PM2 installed và configured
- [ ] .env file configured
- [ ] Nginx configured với WebSocket support
- [ ] Firewall rules set
- [ ] SSL certificate (nếu dùng HTTPS)
- [ ] Service running với PM2
- [ ] Health check working
- [ ] WebSocket connection test từ client

## 🎯 Next Steps

1. Test WebSocket connection từ client
2. Implement realtime features trong Vue.js
3. Monitor performance và connections
4. Scale nếu cần (multiple instances với Redis adapter)

