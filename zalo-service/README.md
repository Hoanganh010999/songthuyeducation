# Zalo API Service for School Management System

Node.js service to integrate Zalo messaging into Laravel backend.

**Package:** [zalo-api-final](https://github.com/hiennguyen270995/zalo-api-final) v2.1.0 (MIT License)

## Quick Setup (5 minutes)

### 1. Install dependencies:
```bash
npm install
```

### 2. Configure .env:
```bash
cp env.example .env
notepad .env
```

Edit `.env`:
```env
API_SECRET_KEY=my-super-secret-key-2024

# Leave these EMPTY for QR login
ZALO_COOKIE=
ZALO_IMEI=
ZALO_USER_AGENT=
```

### 3. Start service (first time):
```bash
npm run dev
```

**📱 QR Code will appear** → Scan with Zalo app → Login success!

Service will display credentials → **Copy to .env** for next time.

### 4. Configure Laravel:
Add to Laravel `.env`:
```env
ZALO_SERVICE_URL=http://localhost:3001
ZALO_API_KEY=my-super-secret-key-2024
```

✅ Done! Check with: `curl http://localhost:3001/health`

## API Endpoints

All requests require header: `X-API-Key: your_secret_key`

### Authentication
- `POST /api/auth/initialize` - Initialize Zalo connection
- `GET /api/auth/status` - Check connection status

### Messaging
- `POST /api/message/send` - Send text message
- `POST /api/message/send-bulk` - Send to multiple recipients
- `POST /api/message/send-image` - Send image

### User Management
- `GET /api/user/info/:userId` - Get user info
- `GET /api/user/friends` - Get friends list
- `POST /api/user/find` - Find user by phone

### Group Management
- `GET /api/group/list` - Get all groups
- `GET /api/group/info/:groupId` - Get group info
- `GET /api/group/members/:groupId` - Get group members
- `POST /api/group/create` - Create new group

## Health Check

```bash
curl http://localhost:3001/health
```

## Port

Default: 3001

