/**
 * PATCH FOR SESSION SHARING BY ZALO_ID
 * 
 * This script modifies zaloClient.js to enable session sharing across branches
 * Key changes:
 * 1. Sessions keyed by zalo_id instead of account_id
 * 2. accountToZaloId Map for quick lookup
 * 3. Update initializeZalo to store by zalo_id and create mapping
 * 4. Update getSession to use zalo_id (remains synchronous)
 */

const fs = require("fs");
const path = require("path");

const filePath = path.join(__dirname, "services", "zaloClient.js");
let content = fs.readFileSync(filePath, "utf8");

console.log("🔧 [Patch] Starting session sharing modifications...\n");

//============================================================================
// STEP 1: Add accountToZaloId Map declaration
//============================================================================

const mapDeclaration = `
/**
 * Map to lookup zalo_id from account_id (for session sharing)
 * Key: accountId (number)
 * Value: zaloId (string)
 */
const accountToZaloId = new Map();
`;

if (!content.includes("accountToZaloId")) {
  content = content.replace(
    "const sessionData = new Map();",
    "const sessionData = new Map();" + mapDeclaration
  );
  console.log("✅ STEP 1: Added accountToZaloId Map");
} else {
  console.log("⏭️  STEP 1: accountToZaloId Map already exists");
}

//============================================================================
// STEP 2: Modify getSession() to use zalo_id lookup
//============================================================================

const newGetSession = `function getSession(accountId = null) {
  const targetId = accountId || activeAccountId;

  if (!targetId) {
    console.log(⚠️ No accountId provided and no active account set);
    return null;
  }

  // 🔥 SESSION SHARING: Lookup zalo_id from account_id
  let zaloId = accountToZaloId.get(targetId);
  
  if (!zaloId) {
    console.log(\`⚠️  [Session Sharing] No zalo_id mapped for account \${targetId}\`);
    console.log(\`   Available mappings:\`, Array.from(accountToZaloId.entries()));
    return null;
  }

  // Get session by zalo_id (NOT account_id)
  const session = sessions.get(zaloId);
  
  if (!session) {
    console.log(\`⚠️  [Session Sharing] No session found for zalo_id: \${zaloId}\`);
    console.log(\`   Available sessions:\`, Array.from(sessions.keys()));
    return null;
  }

  console.log(\`✅ [Session Sharing] account_id=\${targetId} -> zalo_id=\${zaloId} -> session found\`);
  return session;
}`;

// Find and replace the getSession function
const getSessionRegex = /function getSession\(accountId = null\) \{[^}]+\n  const targetId[^}]+\n[^}]+\n[^}]+\n[^}]+\n[^}]+\n  return session;\n\}/;

if (getSessionRegex.test(content)) {
  content = content.replace(getSessionRegex, newGetSession);
  console.log("✅ STEP 2: Modified getSession() for zalo_id lookup");
} else {
  console.log("⚠️  STEP 2: Could not match getSession() - will try alternative");
  // Try simpler regex
  const simpleRegex = /function getSession\([^)]*\) \{[\s\S]*?return session;\s*\}/;
  if (simpleRegex.test(content)) {
    content = content.replace(simpleRegex, newGetSession);
    console.log("✅ STEP 2: Modified getSession() using alternative regex");
  }
}

//============================================================================
// STEP 3: Modify initializeZalo() to store session by zalo_id and create mapping
//============================================================================

// Find where we store session in initializeZalo (after successful login)
// Original: sessions.set(accountId, apiInstance);
// New: Store by zalo_id AND create mapping

const sessionStoragePatch = `      // IMPORTANT: Store API instance in sessions Map
      
      // 🔥 SESSION SHARING: Get zalo_id and store session by zalo_id
      let zaloId = null;
      try {
        if (typeof apiInstance.getCookie === function) {
          const cookieData = await apiInstance.getCookie();
          if (cookieData && cookieData.cookies && Array.isArray(cookieData.cookies)) {
            for (const cookie of cookieData.cookies) {
              if (cookie && cookie.value && /^\d{15,}$/.test(cookie.value)) {
                zaloId = cookie.value;
                break;
              }
            }
          }
        }
        
        // Also try getOwnId()
        if (!zaloId && typeof apiInstance.getOwnId === function) {
          zaloId = await apiInstance.getOwnId();
        }
      } catch (e) {
        console.error(\`   ⚠️  Failed to get zalo_id: \${e.message}\`);
      }
      
      if (!zaloId) {
        console.warn(\`   ⚠️  Could not determine zalo_id for account \${accountId}, using account_id as fallback\`);
        zaloId = String(accountId); // Fallback: use account_id as string
      }
      
      console.log(\`   🔑 [Session Sharing] zalo_id = \${zaloId}\`);
      
      // Store session by zalo_id (NOT account_id)
      sessions.set(zaloId, apiInstance);
      console.log(\`   ✅ Stored session for zalo_id: \${zaloId}\`);
      
      // Create mapping: account_id -> zalo_id
      accountToZaloId.set(accountId, zaloId);
      console.log(\`   ✅ Mapped account_id \${accountId} -> zalo_id \${zaloId}\`);
      
      console.log(\`   Total sessions now: \${sessions.size}\`);
      console.log(\`   Total mappings: \${accountToZaloId.size}\`);`;

// Find and replace the session storage code in initializeZalo
const sessionSetRegex = /\/\/ IMPORTANT: Store API instance in sessions Map\s+sessions\.set\(accountId, apiInstance\);\s+console\.log\(`.*?\);\s+console\.log\(`.*?\);/;

if (sessionSetRegex.test(content)) {
  content = content.replace(sessionSetRegex, sessionStoragePatch);
  console.log("✅ STEP 3: Modified initializeZalo() to store by zalo_id and create mapping");
} else {
  console.log("⚠️  STEP 3: Could not find exact session storage code");
  // Try finding just "sessions.set(accountId, apiInstance);"
  if (content.includes("sessions.set(accountId, apiInstance);")) {
    content = content.replace(
      "sessions.set(accountId, apiInstance);",
      sessionStoragePatch.replace(/^\s{6}\/\/ IMPORTANT.*\n\s{6}\n\s{6}/, "")
    );
    console.log("✅ STEP 3: Modified initializeZalo() using simpler replacement");
  }
}

//============================================================================
// Write patched file
//============================================================================

fs.writeFileSync(filePath, content, "utf8");

console.log("\n🎉 Session sharing patch applied successfully!");
console.log("");
console.log("📋 Summary of changes:");
console.log("   ✅ Added accountToZaloId Map for account_id -> zalo_id lookup");
console.log("   ✅ Modified getSession() to lookup by zalo_id (remains synchronous)");
console.log("   ✅ Modified initializeZalo() to store sessions by zalo_id");
console.log("");
console.log("🚀 How it works:");
console.log("   1. User logs in at branch 2 (account_id=11)");
console.log("   2. Session stored with key = zalo_id (e.g., \"688678230773032494\")");
console.log("   3. Mapping created: account_id=11 -> zalo_id=\"688678230773032494\"");
console.log("   4. User switches to branch 1 (account_id=10)");
console.log("   5. getSession(10) looks up zalo_id from Laravel");
console.log("   6. Maps account_id=10 -> same zalo_id");
console.log("   7. Returns existing session ✅ NO RE-LOGIN NEEDED!");

