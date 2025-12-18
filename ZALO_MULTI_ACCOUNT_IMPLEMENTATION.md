# Zalo Multi-Account Implementation Plan

## ✅ Đã hoàn thành

1. ✅ Fix translation key `zalo.members`
2. ✅ Fix group members count - Thêm logic fetch members nếu count = 0
3. ✅ Tạo migrations cho:
   - `zalo_accounts` - Lưu nhiều tài khoản Zalo
   - `zalo_friends` - Cache danh sách bạn bè
   - `zalo_groups` - Cache danh sách nhóm
   - `zalo_messages` - Lưu lịch sử chat
4. ✅ Tạo models: ZaloAccount, ZaloFriend, ZaloGroup, ZaloMessage

## 🔄 Cần implement tiếp

### 1. Models với Relationships

Cần cập nhật models với:
- Fillable fields
- Relationships (hasMany, belongsTo)
- Accessors/Mutators cho encrypted cookie
- Scopes

### 2. Services

#### ZaloAccountService
- Quản lý multiple accounts
- Login/logout accounts
- Switch active account
- Encrypt/decrypt cookies

#### ZaloCacheService
- Sync friends từ API → Database
- Sync groups từ API → Database
- Chỉ update khi có thay đổi
- Compare và update incremental

#### ZaloAvatarService
- Download avatars từ URL
- Lưu vào storage
- Generate thumbnails
- Cleanup old avatars

#### ZaloMessageService
- Lưu messages vào database
- Query chat history
- Pagination
- Search

### 3. Refactor zaloClient.js

- Hỗ trợ multiple accounts
- Store credentials per account
- Switch between accounts
- Maintain WebSocket per account

### 4. API Endpoints

- GET /api/zalo/accounts - List all accounts
- POST /api/zalo/accounts - Add new account
- PUT /api/zalo/accounts/{id} - Update account
- DELETE /api/zalo/accounts/{id} - Delete account
- POST /api/zalo/accounts/{id}/login - Login account
- POST /api/zalo/accounts/{id}/sync - Sync friends/groups
- GET /api/zalo/accounts/{id}/friends - Get cached friends
- GET /api/zalo/accounts/{id}/groups - Get cached groups
- GET /api/zalo/accounts/{id}/messages - Get chat history

## 📝 Next Steps

1. Update models với relationships
2. Create services
3. Refactor zaloClient
4. Update API endpoints
5. Update frontend để hỗ trợ multiple accounts

