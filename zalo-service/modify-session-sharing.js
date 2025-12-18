/**
 * Script to modify zaloClient.js for session sharing by zalo_id
 * This script will patch the zaloClient.js file to enable session sharing across branches
 */

const fs = require("fs");
const path = require("path");

const filePath = path.join(__dirname, "services", "zaloClient.js");
const backupPath = filePath + ".backup-before-sharing";

// Create backup
if (!fs.existsSync(backupPath)) {
  fs.copyFileSync(filePath, backupPath);
  console.log("✅ Backup created:", backupPath);
}

// Read the file
let content = fs.readFileSync(filePath, "utf8");

// 1. Add accountToZaloId Map after sessionData Map declaration
const sessionDataDeclaration = "const sessionData = new Map();";
const accountMapDeclaration = `

/**
 * Map to lookup zalo_id from account_id
 * Key: accountId (number)
 * Value: zaloId (string)
 */
const accountToZaloId = new Map();`;

if (!content.includes("accountToZaloId")) {
  content = content.replace(
    sessionDataDeclaration,
    sessionDataDeclaration + accountMapDeclaration
  );
  console.log("✅ Added accountToZaloId Map");
}

// 2. Add helper function to fetch zalo_id from Laravel
const fetchZaloIdFunction = `

/**
 * Fetch zalo_id from Laravel API for a given account_id
 * @param {number} accountId - Account ID
 * @returns {Promise<string|null>} zalo_id or null
 */
async function fetchZaloIdFromLaravel(accountId) {
  try {
    const laravelUrl = process.env.LARAVEL_URL || 'http://127.0.0.1:8000';
    const url = \`\${laravelUrl}/api/zalo/accounts/\${accountId}/zalo-id\`;
    
    console.log(\`🔍 [Session Sharing] Fetching zalo_id for account \${accountId} from Laravel...\`);
    
    const https = require('https');
    const http = require('http');
    
    return new Promise((resolve, reject) => {
      const protocol = url.startsWith('https') ? https : http;
      
      protocol.get(url, (res) => {
        let data = ''';
        
        res.on('data', (chunk) => {
          data += chunk;
        });
        
        res.on('end', () => {
          try {
            const parsed = JSON.parse(data);
            if (parsed.success && parsed.data && parsed.data.zalo_id) {
              console.log(\`   ✅ Got zalo_id: \${parsed.data.zalo_id}\`);
              resolve(parsed.data.zalo_id);
            } else {
              console.log(\`   ⚠️  No zalo_id found in response\`);
              resolve(null);
            }
          } catch (error) {
            console.error(\`   ❌ Failed to parse response: \${error.message}\`);
            resolve(null);
          }
        });
      }).on('error', (error) => {
        console.error(\`   ❌ HTTP request failed: \${error.message}\`);
        resolve(null);
      });
    });
  } catch (error) {
    console.error(\`[Session Sharing] Failed to fetch zalo_id for account \${accountId}:\`, error);
    return null;
  }
}`;

if (!content.includes("fetchZaloIdFromLaravel")) {
  // Insert after ensureSessionsDir function
  content = content.replace(
    /function ensureSessionsDir\(\) \{[\s\S]*?\n\}/,
    (match) => match + fetchZaloIdFunction
  );
  console.log("✅ Added fetchZaloIdFromLaravel function");
}

// 3. Replace getSession function to support zalo_id lookup
const newGetSessionFunction = `/**
 * Get session for a specific account
 * If no accountId provided, use activeAccountId
 * 
 * SESSION SHARING: Sessions are keyed by zalo_id, not account_id
 * Multiple accounts with same zalo_id share the same session
 */
async function getSession(accountId = null) {
  const targetId = accountId || activeAccountId;

  if (!targetId) {
    console.log('⚠️  No accountId provided and no active account set');
    return null;
  }

  // 1. Get zalo_id for this account_id
  let zaloId = accountToZaloId.get(targetId);
  
  if (!zaloId) {
    console.log(\`🔍 [Session Sharing] No cached zalo_id for account \${targetId}, fetching from Laravel...\`);
    
    // Fetch from Laravel API
    zaloId = await fetchZaloIdFromLaravel(targetId);
    
    if (zaloId) {
      // Cache the mapping
      accountToZaloId.set(targetId, zaloId);
      console.log(\`   ✅ Cached mapping: account_id=\${targetId} -> zalo_id=\${zaloId}\`);
    } else {
      console.log(\`   ❌ Failed to get zalo_id for account \${targetId}\`);
      return null;
    }
  } else {
    console.log(\`📌 [Session Sharing] Using cached zalo_id=\${zaloId} for account_id=\${targetId}\`);
  }

  // 2. Get session by zalo_id (not account_id)
  const session = sessions.get(zaloId);
  
  if (!session) {
    console.log(\`⚠️  [Session Sharing] No session found for zalo_id: \${zaloId}\`);
    console.log(\`   Available sessions (by zalo_id): \${Array.from(sessions.keys()).join(', ')}  \`);
    return null;
  }

  console.log(\`✅ [Session Sharing] Found shared session for zalo_id=\${zaloId} (account_id=\${targetId})\`);
  return session;
}`;

// Replace old getSession with new one
content = content.replace(
  /function getSession\(accountId = null\) \{[\s\S]*?\n\}/,
  newGetSessionFunction
);
console.log("✅ Replaced getSession function with session-sharing version");

// Write the modified file
fs.writeFileSync(filePath, content, "utf8");
console.log("✅ zaloClient.js modified successfully!");
console.log("");
console.log("🎉 Session sharing by zalo_id is now enabled!");
console.log("   - Sessions are keyed by zalo_id (not account_id)");
console.log("   - Multiple accounts with same zalo_id share the same session");
console.log("   - No need to re-login when switching branches!");
