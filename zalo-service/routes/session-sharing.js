/**
 * SESSION SHARING ROUTES
 * Endpoints to support session sharing across branches with same zalo_id
 */

const express = require("express");
const router = express.Router();
const { verifyApiKey } = require("../middleware/auth");
const http = require("http");

// Import from zaloClient
const {
  sessions,
  accountToZaloId,
  getSession
} = require("../services/zaloClient");

/**
 * POST /api/session/create-alias
 * Create session alias for a new account_id pointing to existing session
 * 
 * This is called when user switches branches to map the new branch's account_id
 * to the existing session (keyed by zalo_id)
 * 
 * Body: { account_id: number }
 * Returns: { success: boolean, message: string }
 */
router.post("/create-alias", verifyApiKey, async (req, res) => {
  try {
    const accountId = parseInt(req.body.account_id);
    
    if (!accountId) {
      return res.status(400).json({
        success: false,
        message: "account_id is required"
      });
    }
    
    console.log(`\n🔗 [Session Sharing] Creating session alias for account ${accountId}...`);
    
    // Check if session already exists for this account
    if (getSession(accountId)) {
      console.log(`   ✅ Session already exists for account ${accountId}`);
      return res.json({
        success: true,
        message: "Session already exists",
        accountId: accountId
      });
    }
    
    // Fetch zalo_id from Laravel
    const laravelUrl = process.env.LARAVEL_URL || "http://127.0.0.1:8000";
    const fetchUrl = `${laravelUrl}/api/zalo/accounts/${accountId}/zalo-id`;
    
    console.log(`   📡 Fetching zalo_id from: ${fetchUrl}`);
    
    const zaloIdData = await new Promise((resolve, reject) => {
      http.get(fetchUrl, (resp) => {
        let data = "";
        resp.on("data", (chunk) => { data += chunk; });
        resp.on("end", () => {
          try {
            resolve(JSON.parse(data));
          } catch (e) {
            reject(e);
          }
        });
      }).on("error", reject);
    });
    
    if (!zaloIdData.success || !zaloIdData.data || !zaloIdData.data.zalo_id) {
      console.log(`   ❌ Could not fetch zalo_id for account ${accountId}`);
      return res.status(404).json({
        success: false,
        message: "Could not fetch zalo_id for account"
      });
    }
    
    const zaloId = zaloIdData.data.zalo_id;
    console.log(`   ✅ Got zalo_id: ${zaloId}`);
    
    // Check if session exists for this zalo_id
    if (!sessions.has(zaloId)) {
      console.log(`   ❌ No session found for zalo_id ${zaloId}`);
      return res.status(404).json({
        success: false,
        message: `No session found for zalo_id ${zaloId}. Please login first.`
      });
    }
    
    // Create alias: new account_id points to same session
    const existingSession = sessions.get(zaloId);
    sessions.set(accountId, existingSession);
    accountToZaloId.set(accountId, zaloId);
    
    console.log(`   ✅ Created alias: account ${accountId} -> zalo_id ${zaloId}`);
    console.log(`   📊 Total sessions: ${sessions.size}, Total mappings: ${accountToZaloId.size}`);
    
    return res.json({
      success: true,
      message: "Session alias created successfully",
      accountId: accountId,
      zaloId: zaloId
    });
    
  } catch (error) {
    console.error("❌ [Session Sharing] Create alias error:", error);
    return res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

module.exports = router;
