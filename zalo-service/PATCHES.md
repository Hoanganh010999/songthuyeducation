# Patches Applied to node_modules

## zalo-api-final - Cookie Domain Fix

**Problem:** The zalo-api-final library fails during QR login with cookie domain errors when trying to set cookies from different Zalo domains (chat.zalo.me, id.zalo.me, etc.).

**Error:**
```
ERROR Error: Cookie not in this host's domain. Cookie:chat.zalo.me Request:id.zalo.me
```

**Solution:** Modified CookieJar initialization in 3 files to use `looseMode` and `allowSpecialUseDomain` options:

### Files Modified:

1. **node_modules/zalo-api-final/dist/cjs/apis/loginQR.cjs** (line 236)
   ```javascript
   // Before:
   ctx.cookie = new toughCookie.CookieJar();

   // After:
   ctx.cookie = new toughCookie.CookieJar(null, { looseMode: true, allowSpecialUseDomain: true });
   ```

2. **node_modules/zalo-api-final/dist/cjs/utils.cjs** (line 253)
   ```javascript
   // Same change as above
   ```

3. **node_modules/zalo-api-final/dist/cjs/zalo.cjs** (line 147)
   ```javascript
   // Before:
   const jar = new toughCookie.CookieJar();

   // After:
   const jar = new toughCookie.CookieJar(null, { looseMode: true, allowSpecialUseDomain: true });
   ```

### How to Reapply:

If you need to reinstall dependencies, run these commands to reapply the patches:

```bash
cd zalo-service/node_modules/zalo-api-final/dist/cjs

# Backup
cp apis/loginQR.cjs apis/loginQR.cjs.backup

# Apply patches
sed -i "s/ctx.cookie = new toughCookie.CookieJar();/ctx.cookie = new toughCookie.CookieJar(null, { looseMode: true, allowSpecialUseDomain: true });/" apis/loginQR.cjs utils.cjs

sed -i "s/const jar = new toughCookie.CookieJar();/const jar = new toughCookie.CookieJar(null, { looseMode: true, allowSpecialUseDomain: true });/" zalo.cjs

# Verify
grep -n "CookieJar(" apis/loginQR.cjs utils.cjs zalo.cjs
```

### Date Applied:
November 16, 2025

### Library Version:
- zalo-api-final: 2.1.1
- tough-cookie: 5.1.2
