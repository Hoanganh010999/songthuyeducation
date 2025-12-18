const express = require('express');
const router = express.Router();
const { verifyApiKey } = require('../middleware/auth');
const { getZaloClient } = require('../services/zaloClient');

/**
 * GET /api/group/list
 * Get all groups for specific account
 */
router.get('/list', verifyApiKey, async (req, res) => {
  try {
    console.log('📋 [GET /api/group/list] Getting groups list...');

    // 🔥 FIX: Get account ID from header or query
    const accountId = req.headers['x-account-id'] || req.query.account_id;

    // 🚀 LAZY LOADING: Support offset and limit for batching
    const offset = parseInt(req.query.offset) || 0;
    const limit = parseInt(req.query.limit) || 50;

    if (!accountId) {
      console.error('   ❌ No account_id provided');
      return res.status(400).json({
        success: false,
        message: 'account_id is required (header X-Account-Id or query param)'
      });
    }

    console.log('   Account ID:', accountId);
    console.log('   Batch: offset =', offset, ', limit =', limit);

    // 🔥 FIX: Get session for specific account
    const { getSession } = require('../services/zaloClient');
    const zalo = getSession(parseInt(accountId));

    if (!zalo) {
      console.error('   ❌ Zalo session not found for account:', accountId);
      return res.status(400).json({
        success: false,
        message: `Zalo session not found for account ${accountId}. Please login first.`
      });
    }

    console.log('   ✅ Zalo session found');
    console.log('   Available methods:', Object.getOwnPropertyNames(zalo)
      .filter(name => typeof zalo[name] === 'function')
      .sort()
      .join(', '));

    let groups = null;

    // Try different method names
    const methodNames = ['getAllGroups', 'getGroups', 'listGroups', 'getGroupList'];
    let methodFound = false;

    for (const methodName of methodNames) {
      if (typeof zalo[methodName] === 'function') {
        console.log(`   ✅ Found method: ${methodName}()`);
        methodFound = true;
        try {
          groups = await zalo[methodName]();
          console.log(`   ✅ ${methodName}() success!`);
          break;
        } catch (error) {
          console.error(`   ❌ ${methodName}() error:`, error.message);
          // Try next method
          continue;
        }
      }
    }
    
    if (!methodFound) {
      console.error('❌ No groups method found!');
      console.log('   Please check zalo-api-final documentation for correct method name');
      
      return res.status(500).json({
        success: false,
        message: 'Groups method not available. Available methods: ' + 
          Object.getOwnPropertyNames(zalo)
            .filter(name => typeof zalo[name] === 'function')
            .sort()
            .join(', ')
      });
    }
    
    if (!groups) {
      return res.status(500).json({
        success: false,
        message: 'Failed to retrieve groups - all methods failed'
      });
    }
    
    console.log('✅ getAllGroups() called successfully');
    console.log('   Response type:', typeof groups);
    console.log('   Is array:', Array.isArray(groups));
    console.log('   Response keys:', groups && typeof groups === 'object' ? Object.keys(groups).join(', ') : 'N/A');
    
    // Handle different response formats
    let groupsList = [];
    
    if (Array.isArray(groups)) {
      groupsList = groups;
      console.log('   ✅ Response is array, using directly');
    } else if (groups && typeof groups === 'object') {
      // Check if response has gridVerMap (Zalo API format)
      if (groups.gridVerMap && typeof groups.gridVerMap === 'object') {
        console.log('   ✅ Found gridVerMap with', Object.keys(groups.gridVerMap).length, 'group IDs');
        console.log('   Response keys:', Object.keys(groups));
        console.log('   Has gridInfoMap?', !!groups.gridInfoMap);

        // Extract group IDs from gridVerMap
        const groupIds = Object.keys(groups.gridVerMap);
        const totalGroups = groupIds.length;
        
        // 🔥 OPTIMIZATION: Instead of calling getGroupInfo() for each group (which can fail),
        // use getAllGroups() response which already contains gridInfoMap with group details
        // Only fetch individual group info if needed (when user clicks on specific group)
        
        // 🔥 ROOT CAUSE ANALYSIS:
        // getAllGroups() chỉ trả về gridVerMap (danh sách IDs), KHÔNG có gridInfoMap ở top level
        // gridInfoMap chỉ xuất hiện khi gọi getGroupInfo() cho từng group
        // Vì vậy chúng ta PHẢI gọi getGroupInfo() cho từng group để lấy thông tin chi tiết
        
        // Tuy nhiên, vấn đề là:
        // 1. Khi gọi getGroupInfo() cho nhiều group liên tiếp → có thể bị rate limit
        // 2. Một số group có thể fail với "Retry limit" từ thư viện zalo-api-final
        // 3. Khi fail, code vẫn thêm group với tên placeholder → overwrite dữ liệu cũ trong DB
        
        // Giải pháp: 
        // - Tăng delay giữa các request để tránh rate limit
        // - Xử lý error tốt hơn ở Laravel để preserve dữ liệu cũ
        // - Chỉ gọi getGroupInfo() khi thực sự cần (lazy loading)
        
        // Check if getAllGroups response has gridInfoMap (rare case)
        if (groups.gridInfoMap && typeof groups.gridInfoMap === 'object') {
          console.log('   ✅ Found gridInfoMap in getAllGroups response (rare), using it directly');
          console.log('   gridInfoMap has', Object.keys(groups.gridInfoMap).length, 'groups');
          
          // Check which groups are missing from gridInfoMap
          const missingGroups = groupIds.filter(id => !groups.gridInfoMap[id]);
          if (missingGroups.length > 0) {
            console.log('   ⚠️  Groups missing from gridInfoMap:', missingGroups.length, missingGroups.slice(0, 5));
          }
          
          // Use gridInfoMap from getAllGroups response
          for (const groupId of groupIds) {
            if (groups.gridInfoMap[groupId]) {
              groupsList.push({
                id: groupId,
                version: groups.gridVerMap[groupId],
                ...groups.gridInfoMap[groupId]
              });
            } else {
              // Group not in gridInfoMap - this is why we need to call getGroupInfo()
              console.log(`   ⚠️  Group ${groupId} not in gridInfoMap, will need to fetch individually`);
              groupsList.push({
                id: groupId,
                version: groups.gridVerMap[groupId],
                name: `Group ${groupId}`,
                _needsFetch: true // Mark for later fetching if needed
              });
            }
          }
        } else {
          // Fallback: Fetch group info individually (with better error handling)
          // 🚀 LAZY LOADING: Use offset and limit for batching
          const batchStart = offset;
          const batchEnd = Math.min(offset + limit, totalGroups);
          const batchIds = groupIds.slice(batchStart, batchEnd);

          console.log(`   🚀 LAZY LOADING: Fetching batch ${batchStart}-${batchEnd} (${batchIds.length} groups) out of ${totalGroups} total`);
          console.log(`   ⚠️  No gridInfoMap found, fetching details individually...`);

          // Use Promise.allSettled to continue even if some groups fail
          // 🔥 FIX: Increase delay to avoid rate limiting and "Retry limit" errors
          const groupInfoPromises = batchIds.map(async (groupId, index) => {
            // Add delay to avoid rate limiting - increased from 150ms to 300ms
            // Rate limiting thường xảy ra khi gọi quá nhiều API liên tiếp
            if (index > 0) {
              await new Promise(resolve => setTimeout(resolve, 300));
            }
            
            try {
              // 🔥 FIX: Add timeout wrapper to prevent hanging
              const timeoutPromise = new Promise((_, reject) => 
                setTimeout(() => reject(new Error('getGroupInfo timeout after 10s')), 10000)
              );
              
              const groupInfo = await Promise.race([
                zalo.getGroupInfo(groupId),
                timeoutPromise
              ]);
              
              if (groupInfo) {
                return {
                  status: 'fulfilled',
                  value: {
                    id: groupId,
                    version: groups.gridVerMap[groupId],
                    ...groupInfo
                  }
                };
              }
            } catch (error) {
              // Log detailed error for debugging
              const errorMsg = error.message || 'Unknown error';
              console.log(`   ⚠️  Failed to get info for group ${groupId}:`, errorMsg);
              
              // Check if it's a retry limit error
              if (errorMsg.includes('Retry limit') || errorMsg.includes('retry')) {
                console.log(`   🔍 Retry limit error for group ${groupId} - likely rate limiting or API issue`);
              }
              
              return {
                status: 'rejected',
                reason: errorMsg,
                value: {
                  id: groupId,
                  version: groups.gridVerMap[groupId],
                  name: `Group ${groupId}`,
                  error: errorMsg,
                  _failed: true // Mark as failed so Laravel can preserve existing data
                }
              };
            }
            
            return {
              status: 'fulfilled',
              value: {
                id: groupId,
                version: groups.gridVerMap[groupId],
                name: `Group ${groupId}`
              }
            };
          });
          
          const results = await Promise.allSettled(groupInfoPromises);
          
          // Extract successful results
          for (const result of results) {
            if (result.status === 'fulfilled' && result.value) {
              groupsList.push(result.value.value || result.value);
            } else if (result.status === 'rejected' && result.reason) {
              // Handle rejected promise
              console.log(`   ⚠️  Promise rejected:`, result.reason);
            }
          }
        }
        
        console.log(`   ✅ Processed ${groupsList.length} group details`);
      } else if (groups.data && Array.isArray(groups.data)) {
        groupsList = groups.data;
        console.log('   ✅ Found groups.data array');
      } else if (groups.groups && Array.isArray(groups.groups)) {
        groupsList = groups.groups;
        console.log('   ✅ Found groups.groups array');
      } else if (groups.list && Array.isArray(groups.list)) {
        groupsList = groups.list;
        console.log('   ✅ Found groups.list array');
      } else if (groups.result && Array.isArray(groups.result)) {
        groupsList = groups.result;
        console.log('   ✅ Found groups.result array');
      } else {
        // Try to extract array from object values
        const values = Object.values(groups);
        const arrayValue = values.find(v => Array.isArray(v));
        if (arrayValue) {
          groupsList = arrayValue;
          console.log('   ✅ Found array in object values');
        } else {
          console.log('   ⚠️  No array found in response object');
          console.log('   Object structure:', Object.keys(groups));
        }
      }
    }
    
    console.log('✅ Groups list extracted:', groupsList.length, 'groups');
    if (groupsList.length > 0) {
      console.log('   First group sample:', JSON.stringify(groupsList[0], null, 2).substring(0, 500));
    } else {
      console.log('   ⚠️  No groups found - this might be normal if account has no groups');
    }
    
    // Normalize group data structure
    const normalizedGroups = await Promise.all(groupsList.map(async (group) => {
      // Extract from gridInfoMap if available (Zalo API format)
      let groupInfo = null;
      if (group.gridInfoMap && typeof group.gridInfoMap === 'object') {
        const groupId = group.id || Object.keys(group.gridInfoMap)[0];
        groupInfo = group.gridInfoMap[groupId];
      }
      
      const groupId = group.id || group.groupId || group._id || group.gid || group.group_id || groupInfo?.groupId;
      
      // Calculate members count - try multiple sources
      let membersCount = 0;
      
      // DEBUG: Log for R.E.C - MOVERS
      if (groupId === "937352231569092258") {
        console.log("   🐛 DEBUG R.E.C - MOVERS:");
        console.log("      groupInfo exists:", !!groupInfo);
        console.log("      groupInfo.memberIds:", groupInfo?.memberIds?.length || "none");
        console.log("      group.memberIds:", group.memberIds?.length || "none");
        console.log("      group.memberIds is array:", Array.isArray(group.memberIds));
        console.log("      groupInfo.totalMember:", groupInfo?.totalMember);
        console.log("      groupInfo.memVerList exists:", !!groupInfo?.memVerList);
        console.log("      groupInfo.memVerList length:", groupInfo?.memVerList?.length || 0);
      }
      
      if (groupInfo?.memberIds && Array.isArray(groupInfo.memberIds) && groupInfo.memberIds.length > 0) {
        membersCount = groupInfo.memberIds.length;
        if (groupId === "937352231569092258") {
          console.log("      ✅ Matched condition 1: groupInfo.memberIds, count =", membersCount);
        }
      } else if (group.memberIds && Array.isArray(group.memberIds) && group.memberIds.length > 0) {
        membersCount = group.memberIds.length;
        if (groupId === "937352231569092258") {
          console.log("      ✅ Matched condition 2: group.memberIds, count =", membersCount);
        }
      } else if (groupInfo?.totalMember && typeof groupInfo.totalMember === 'number' && groupInfo.totalMember > 0) {
        // Use totalMember from gridInfoMap if memberIds is empty
        membersCount = groupInfo.totalMember;
        if (groupId === "937352231569092258") {
          console.log("      ✅ Matched condition 3: groupInfo.totalMember, count =", membersCount);
        }
      } else if (groupInfo?.memVerList && Array.isArray(groupInfo.memVerList)) {
        // Count unique member IDs from memVerList (format: "userId_version")
        const uniqueMembers = new Set();
        groupInfo.memVerList.forEach(memVer => {
          if (typeof memVer === 'string') {
            const parts = memVer.split('_');
            if (parts[0]) {
              uniqueMembers.add(parts[0]);
            }
          }
        });
        membersCount = uniqueMembers.size;
      } else if (groupInfo?.memberCount) {
        membersCount = groupInfo.memberCount;
      } else if (group.members_count) {
        membersCount = group.members_count;
      } else if (group.memberCount) {
        membersCount = group.memberCount;
      }
      
      // If still 0, try to fetch members directly (with timeout to avoid blocking)
      if (membersCount === 0 && groupId && typeof zalo.getGroupMembersInfo === 'function') {
        try {
          // Use Promise.race to timeout after 3 seconds
          const membersPromise = zalo.getGroupMembersInfo(groupId);
          const timeoutPromise = new Promise((_, reject) => 
            setTimeout(() => reject(new Error('Timeout')), 3000)
          );
          
          const membersData = await Promise.race([membersPromise, timeoutPromise]);
          
          // Extract members array
          let members = [];
          if (Array.isArray(membersData)) {
            members = membersData;
          } else if (membersData && Array.isArray(membersData.members)) {
            members = membersData.members;
          } else if (membersData && membersData.profiles) {
            // Extract from profiles (Zalo format)
            if (Array.isArray(membersData.profiles)) {
            // Filter out group ID from array (Zalo API bug)
            members = membersData.profiles.filter(member => {
              const memberId = member.id || member.userId || member.uid;
              return memberId && memberId !== groupId;
            });
            } else if (typeof membersData.profiles === 'object') {
            // Extract members and FILTER OUT group ID (Zalo API bug)
            members = Object.values(membersData.profiles).filter(member => {
              const memberId = member.id || member.userId || member.uid;
              return memberId && memberId !== groupId;
            });
            }
          } else if (membersData && membersData.gridInfoMap) {
            members = Object.values(membersData.gridInfoMap);
          }
          
          if (members.length > 0) {
            membersCount = members.length;
            console.log(`   ✅ Fetched members for group ${groupId}: ${membersCount} members`);
          }
        } catch (error) {
          console.log(`   ⚠️  Could not fetch members for group ${groupId}:`, error.message);
          // Keep membersCount as 0 if fetch fails
        }
      }
      
      // Try to extract common fields
      const normalized = {
        id: groupId,
        name: groupInfo?.name || group.name || group.groupName || group.title || group.group_name || `Group ${groupId}`,
        avatar: groupInfo?.avt || groupInfo?.fullAvt || group.avatar || group.avatarUrl || group.image || group.avatar_url || '',
        description: groupInfo?.desc || group.description || group.desc || '',
        members_count: membersCount,
        admin_ids: groupInfo?.adminIds || group.adminIds || [],
        creator_id: groupInfo?.creatorId || group.creatorId || group.creator_id,
        type: groupInfo?.type || group.type,
        version: group.version || groupInfo?.version,
        // Keep all original data for debugging
        ...group
      };
      
      return normalized;
    }));
    
    // Log first group for debugging
    if (normalizedGroups.length > 0) {
      console.log('   📊 Sample normalized group:', {
        id: normalizedGroups[0].id,
        name: normalizedGroups[0].name,
        members_count: normalizedGroups[0].members_count,
        has_gridInfoMap: !!groupsList[0]?.gridInfoMap
      });
    }

    // 🚀 LAZY LOADING: Calculate pagination metadata
    const totalGroupsCount = (groups && groups.gridVerMap) ? Object.keys(groups.gridVerMap).length : normalizedGroups.length;
    const hasMore = (offset + limit) < totalGroupsCount;
    const nextOffset = hasMore ? offset + limit : null;

    console.log(`   📊 Pagination: returned ${normalizedGroups.length} groups, total=${totalGroupsCount}, hasMore=${hasMore}, nextOffset=${nextOffset}`);

    res.json({
      success: true,
      data: normalizedGroups,
      count: normalizedGroups.length,
      // 🚀 LAZY LOADING metadata
      pagination: {
        offset: offset,
        limit: limit,
        total: totalGroupsCount,
        has_more: hasMore,
        next_offset: nextOffset
      }
    });
  } catch (error) {
    console.error('❌ Get groups error:', error);
    console.error('   Error message:', error.message);
    if (error.stack) {
      console.error('   Stack:', error.stack);
    }
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to get groups'
    });
  }
});

/**
 * GET /api/group/info/:groupId
 * Get group information
 */
router.get('/info/:groupId', verifyApiKey, async (req, res) => {
  try {
    const { groupId } = req.params;

    console.log('📋 [GET /api/group/info/:groupId] Getting group info...');
    console.log('   Group ID:', groupId);

    // 🔥 FIX: Get account ID from header or query (same as /list endpoint)
    const accountId = req.headers['x-account-id'] || req.query.accountId || req.query.account_id;

    if (!accountId) {
      console.error('   ❌ No account_id provided');
      return res.status(400).json({
        success: false,
        message: 'account_id is required (header X-Account-Id or query param)'
      });
    }

    console.log('   Account ID:', accountId);

    // 🔥 FIX: Get session for specific account
    const { getSession } = require('../services/zaloClient');
    const zalo = getSession(parseInt(accountId));

    if (!zalo) {
      console.error('   ❌ Zalo session not found for account:', accountId);
      return res.status(400).json({
        success: false,
        message: `Zalo session not found for account ${accountId}. Please login first.`
      });
    }

    console.log('   ✅ Zalo session found');
    console.log('   Calling getGroupInfo()...');

    const groupInfoRaw = await zalo.getGroupInfo(groupId);

    console.log('   ✅ getGroupInfo() success!');
    console.log('   Raw response type:', typeof groupInfoRaw);
    console.log('   Raw response keys:', groupInfoRaw ? Object.keys(groupInfoRaw) : 'null');

    // Extract group info from gridInfoMap if needed
    let groupInfo = null;
    if (groupInfoRaw && groupInfoRaw.gridInfoMap) {
      // gridInfoMap format: { [groupId]: {...groupInfo} }
      // 🔥 FIX: Try to get the specific group by groupId first
      if (groupInfoRaw.gridInfoMap[groupId]) {
        groupInfo = groupInfoRaw.gridInfoMap[groupId];
        console.log('   ✅ Extracted group info from gridInfoMap using groupId:', groupId);
      } else {
        // Fallback: get first value if groupId not found
        const gridInfoValues = Object.values(groupInfoRaw.gridInfoMap);
        if (gridInfoValues.length > 0) {
          groupInfo = gridInfoValues[0];
          console.log('   ⚠️  GroupId not found in gridInfoMap, using first group:', Object.keys(groupInfoRaw.gridInfoMap)[0]);
          console.log('   ⚠️  Requested groupId:', groupId, 'but got:', groupInfo.groupId || Object.keys(groupInfoRaw.gridInfoMap)[0]);
        }
      }
    } else {
      groupInfo = groupInfoRaw;
    }

    if (!groupInfo) {
      console.error('   ❌ No group info found');
      return res.status(404).json({
        success: false,
        message: 'Group not found'
      });
    }

    console.log('   Group info keys:', Object.keys(groupInfo));

    // Normalize group data structure
    const normalized = {
      id: groupId,
      name: groupInfo.name || groupInfo.groupName || 'Unknown Group',
      avatar: groupInfo.avt || groupInfo.fullAvt || groupInfo.avatar || groupInfo.avatarUrl || null,
      avatar_url: groupInfo.avt || groupInfo.fullAvt || groupInfo.avatar || groupInfo.avatarUrl || null,
      description: groupInfo.desc || groupInfo.description || '',
      type: groupInfo.type || 0,
      creator_id: groupInfo.creatorId || null,
      admin_ids: groupInfo.adminIds || [],
      members_count: groupInfo.totalMember || (groupInfo.memberIds ? groupInfo.memberIds.length : 0),
      // Keep all original data
      ...groupInfo
    };

    console.log('   ✅ Normalized group:', {
      id: normalized.id,
      name: normalized.name,
      has_avatar: !!normalized.avatar
    });

    res.json({
      success: true,
      data: normalized
    });
  } catch (error) {
    console.error('Get group info error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to get group info'
    });
  }
});

/**
 * GET /api/group/members/:groupId
 * Get group members
 */
router.get('/members/:groupId', verifyApiKey, async (req, res) => {
  try {
    const { groupId } = req.params;

    console.log('📋 Getting members for group:', groupId);

    // 🔥 FIX: Get account ID from header or query
    const accountId = req.headers['x-account-id'] || req.query.accountId || req.query.account_id;

    if (!accountId) {
      console.error('   ❌ No account_id provided');
      return res.status(400).json({
        success: false,
        message: 'account_id is required (header X-Account-Id or query param)'
      });
    }

    console.log('   Account ID:', accountId);

    // 🔥 FIX: Get session for specific account
    const { getSession } = require('../services/zaloClient');
    const zalo = getSession(parseInt(accountId));

    if (!zalo) {
      console.error('   ❌ Zalo session not found for account:', accountId);
      return res.status(400).json({
        success: false,
        message: `Zalo session not found for account ${accountId}. Please login first.`
      });
    }

    console.log('   ✅ Zalo session found');
    
    // Check available methods
    const hasGetGroupMembersInfo = typeof zalo.getGroupMembersInfo === 'function';
    const hasGetGroupInfo = typeof zalo.getGroupInfo === 'function';
    
    console.log('   Available methods:', {
      getGroupMembersInfo: hasGetGroupMembersInfo,
      getGroupInfo: hasGetGroupInfo,
    });
    
    let members = [];
    
    // Try getGroupMembersInfo first
    if (hasGetGroupMembersInfo) {
      try {
        console.log('   Trying getGroupMembersInfo()...');
        const membersData = await zalo.getGroupMembersInfo(groupId);
        console.log('   ✅ getGroupMembersInfo() success!');
        console.log('   Raw response type:', typeof membersData);
        console.log('   Raw response keys:', membersData ? Object.keys(membersData) : 'null');

        // 🔍 DEBUG for R.E.C - MOVERS group
        if (groupId === '937352231569092258') {
          console.log('   🐛 DEBUG FULL membersData:', JSON.stringify(membersData, null, 2));
          if (membersData && membersData.profiles) {
            console.log('   🐛 profiles type:', typeof membersData.profiles);
            console.log('   🐛 profiles is array:', Array.isArray(membersData.profiles));
            if (typeof membersData.profiles === 'object' && !Array.isArray(membersData.profiles)) {
              const keys = Object.keys(membersData.profiles);
              console.log('   🐛 profiles keys:', keys);
              keys.forEach(key => {
                console.log(`   🐛 profiles[${key}]:`, JSON.stringify(membersData.profiles[key], null, 2));
              });
            }
          }
          if (membersData && membersData.gridInfoMap) {
            console.log('   🐛 gridInfoMap keys:', Object.keys(membersData.gridInfoMap));
          }
        }
        
        // Debug: log profiles structure
        if (membersData && membersData.profiles) {
          console.log('   Profiles type:', typeof membersData.profiles);
          console.log('   Profiles is array?', Array.isArray(membersData.profiles));
          if (typeof membersData.profiles === 'object' && !Array.isArray(membersData.profiles)) {
            console.log('   Profiles keys:', Object.keys(membersData.profiles));
            const profileValues = Object.values(membersData.profiles);
            console.log('   Profiles values count:', profileValues.length);
            if (profileValues.length > 0) {
              console.log('   First profile value sample:', JSON.stringify(profileValues[0], null, 2));
            }
          }
        }
        
        // Extract members array from response
        if (Array.isArray(membersData)) {
          members = membersData;
        } else if (membersData && Array.isArray(membersData.members)) {
          members = membersData.members;
        } else if (membersData && Array.isArray(membersData.data)) {
          members = membersData.data;
        } else if (membersData && membersData.profiles) {
          // Extract from profiles (Zalo format from getGroupMembersInfo)
          if (Array.isArray(membersData.profiles)) {
            members = membersData.profiles.filter(m => { const id = m.id || m.userId || m.uid; return id && id !== groupId; });
          } else if (typeof membersData.profiles === 'object') {
            members = Object.values(membersData.profiles).filter(m => { const id = m.id || m.userId || m.uid; return id && id !== groupId; });
          }
        } else if (membersData && membersData.gridInfoMap) {
          // Extract from gridInfoMap (Zalo format)
          members = Object.values(membersData.gridInfoMap);
        }
        
        console.log('   Members extracted from getGroupMembersInfo:', members.length);
        if (members.length > 0) {
          console.log('   First member sample:', JSON.stringify(members[0], null, 2));
        }
      } catch (error) {
        console.error('   ❌ getGroupMembersInfo() error:', error.message);
        // Continue to fallback
      }
    }
    
    // Fallback: try getGroupInfo to extract member IDs
    if (members.length === 0 && hasGetGroupInfo) {
      try {
        console.log('   Trying getGroupInfo() as fallback...');
        const groupInfo = await zalo.getGroupInfo(groupId);
        console.log('   ✅ getGroupInfo() success!');
        console.log('   Group info keys:', groupInfo ? Object.keys(groupInfo) : 'null');
        
        // Extract member IDs from memVerList and create basic member objects
        if (groupInfo && groupInfo.gridInfoMap) {
          const gridInfoValues = Object.values(groupInfo.gridInfoMap);
          console.log('   GridInfoMap values count:', gridInfoValues.length);
          
          // Check if gridInfoMap contains group info (has groupId) or member info
          if (gridInfoValues.length > 0) {
            const firstValue = gridInfoValues[0];
            console.log('   First gridInfoMap value has groupId?', !!firstValue.groupId);
            console.log('   First gridInfoMap value keys:', Object.keys(firstValue));
            
            // If first value has groupId, it's group info, extract member IDs from memVerList
            if (firstValue.groupId && firstValue.memVerList) {
              console.log('   Extracting member IDs from memVerList:', firstValue.memVerList.length);
              
              // Parse memVerList (format: "uid_version")
              members = firstValue.memVerList.map(memVer => {
                const [uid, version] = memVer.split('_');
                return {
                  id: uid,
                  uid: uid,
                  version: version,
                  // We only have ID, no other info yet
                  displayName: null,
                  avatar: null,
                  type: firstValue.adminIds?.includes(uid) ? 1 : 0,
                };
              });
              
              console.log('   Created', members.length, 'member objects from memVerList');
            } else {
              // gridInfoMap contains actual member info
              members = gridInfoValues;
            }
          }
        }
        
        console.log('   Members from groupInfo:', members.length);
        if (members.length > 0) {
          console.log('   First member from groupInfo:', JSON.stringify(members[0], null, 2));
        }
      } catch (error) {
        console.error('   ❌ getGroupInfo() error:', error.message);
      }
    }
    
    // If members only have IDs (no displayName), fetch user info
    const membersNeedInfo = members.filter(m => !m.displayName && !m.name);
    console.log(`   🔍 Members needing info: ${membersNeedInfo.length}/${members.length}`);
    console.log(`   🔍 getUserInfo available: ${typeof zalo.getUserInfo === 'function'}`);
    
    if (membersNeedInfo.length > 0 && typeof zalo.getUserInfo === 'function') {
      console.log(`   📥 Fetching user info for ${membersNeedInfo.length} members...`);
      
      try {
        // Fetch user info in batches (max 10 at a time to avoid timeout)
        const batchSize = 10;
        for (let i = 0; i < membersNeedInfo.length; i += batchSize) {
          const batch = membersNeedInfo.slice(i, i + batchSize);
          
          await Promise.all(batch.map(async (member) => {
            try {
              console.log(`   🔄 Calling getUserInfo for: ${member.id || member.uid}`);
              const userInfo = await zalo.getUserInfo(member.id || member.uid);
              
              // Log raw response to debug structure
              console.log(`   📦 getUserInfo response for ${member.id}:`, {
                type: typeof userInfo,
                isNull: userInfo === null,
                isUndefined: userInfo === undefined,
                keys: userInfo ? Object.keys(userInfo) : 'no keys',
                sample: userInfo ? JSON.stringify(userInfo).substring(0, 200) : 'null',
              });
              
              if (userInfo) {
                // Extract user data from changed_profiles
                const userId = member.id || member.uid;
                let userData = null;
                
                // getUserInfo returns: { changed_profiles: { [userId]: {...} } }
                if (userInfo.changed_profiles && userInfo.changed_profiles[userId]) {
                  userData = userInfo.changed_profiles[userId];
                } else if (userInfo.displayName) {
                  // Fallback: direct access (in case structure is different)
                  userData = userInfo;
                }
                
                if (userData) {
                  member.displayName = userData.displayName || userData.zaloName || userData.name || null;
                  member.avatar = userData.avatar || userData.avatarUrl || userData.avatar_url || null;
                  member.phone = userData.phoneNumber || userData.phone || null;
                  
                  console.log(`   ✅ Fetched info for ${member.id}: ${member.displayName}`);
                } else {
                  console.log(`   ⚠️  No user data found in changed_profiles for ${member.id}`);
                }
              } else {
                console.log(`   ⚠️  getUserInfo returned null/undefined for ${member.id}`);
              }
            } catch (err) {
              console.error(`   ⚠️  Failed to fetch info for ${member.id}:`, err.message);
              // Keep member with ID only
            }
          }));
        }
        
        console.log('   ✅ User info fetching completed');
      } catch (error) {
        console.error('   ⚠️  Error fetching user info:', error.message);
        // Continue with members as-is
      }
    }
    
    // Normalize member data structure
    const normalizedMembers = members.map(member => {
      return {
        id: member.id || member.uid || member.userId || member.zalo_user_id,
        display_name: member.displayName || member.name || member.display_name || `User ${(member.id || member.uid || '').slice(-4)}`,
        avatar_url: member.avatar || member.avatarUrl || member.avatar_url || null,
        is_admin: member.type === 1 || member.isAdmin || member.is_admin || false,
        phone: member.phone || null,
        // Keep original data for reference
        ...member
      };
    });
    
    console.log('   ✅ Returning', normalizedMembers.length, 'normalized members');
    
    res.json({
      success: true,
      data: normalizedMembers,
      count: normalizedMembers?.length || 0
    });
  } catch (error) {
    console.error('Get group members error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to get group members'
    });
  }
});

/**
 * POST /api/group/create
 * Create a new group
 */
router.post('/create', verifyApiKey, async (req, res) => {
  try {
    console.log('📋 [POST /api/group/create] Creating new group...');
    console.log('   Request body:', JSON.stringify(req.body));
    
    const { name, members, avatarPath, accountId } = req.body;
    
    if (!members || !Array.isArray(members) || members.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'members array is required and must not be empty'
      });
    }
    
    const zalo = getZaloClient(accountId);
    if (!zalo) {
      return res.status(400).json({
        success: false,
        message: 'Zalo client not initialized'
      });
    }
    
    // Check if createGroup method exists
    if (typeof zalo.createGroup !== 'function') {
      console.error('   ❌ createGroup method not found');
      return res.status(500).json({
        success: false,
        message: 'createGroup method not available in zalo client'
      });
    }
    
    console.log('   ✅ Calling createGroup()...');
    console.log('   Options:', { name, members: members.length, hasAvatar: !!avatarPath });
    
    const options = {
      members: members.map(id => String(id)), // Ensure all IDs are strings
    };
    
    if (name) {
      options.name = name;
    }
    
    if (avatarPath) {
      options.avatarSource = avatarPath;
    }
    
    const result = await zalo.createGroup(options);
    
    console.log('   ✅ Group created successfully');
    console.log('   Result:', JSON.stringify(result));
    
    res.json({
      success: true,
      message: 'Group created successfully',
      data: {
        groupId: result.groupId,
        groupType: result.groupType,
        successMembers: result.sucessMembers || result.successMembers || [],
        errorMembers: result.errorMembers || [],
        error_data: result.error_data || {},
      }
    });
  } catch (error) {
    console.error('❌ Create group error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to create group',
      error: process.env.NODE_ENV === 'development' ? error.stack : undefined,
    });
  }
});

/**
 * POST /api/group/add-members/:groupId
 * Add members to an existing group
 */
router.post('/add-members/:groupId', verifyApiKey, async (req, res) => {
  try {
    const { groupId } = req.params;
    const { memberIds, accountId } = req.body;
    
    console.log('📋 [POST /api/group/add-members] Adding members to group...');
    console.log('   Group ID:', groupId);
    console.log('   Member IDs:', memberIds);
    
    if (!memberIds || (Array.isArray(memberIds) && memberIds.length === 0) || (!Array.isArray(memberIds) && !memberIds)) {
      return res.status(400).json({
        success: false,
        message: 'memberIds is required (string or array of strings)'
      });
    }
    
    const zalo = getZaloClient(accountId);
    if (!zalo) {
      return res.status(400).json({
        success: false,
        message: 'Zalo client not initialized'
      });
    }
    
    // Check if addUserToGroup method exists
    if (typeof zalo.addUserToGroup !== 'function') {
      console.error('   ❌ addUserToGroup method not found');
      return res.status(500).json({
        success: false,
        message: 'addUserToGroup method not available in zalo client'
      });
    }
    
    console.log('   ✅ Calling addUserToGroup()...');
    
    // Ensure memberIds are strings
    const normalizedMemberIds = Array.isArray(memberIds) 
      ? memberIds.map(id => String(id))
      : String(memberIds);
    
    const result = await zalo.addUserToGroup(normalizedMemberIds, String(groupId));
    
    console.log('   ✅ Members added successfully');
    console.log('   Result:', JSON.stringify(result));
    
    // Calculate success count
    const requestedCount = Array.isArray(normalizedMemberIds) ? normalizedMemberIds.length : 1;
    const errorCount = result.errorMembers ? result.errorMembers.length : 0;
    const successCount = requestedCount - errorCount;
    
    res.json({
      success: true,
      message: `Successfully added ${successCount} member(s) to group`,
      data: {
        groupId: groupId,
        requestedCount: requestedCount,
        successCount: successCount,
        errorMembers: result.errorMembers || [],
        error_data: result.error_data || {},
      }
    });
  } catch (error) {
    console.error('❌ Add members to group error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to add members to group',
      error: process.env.NODE_ENV === 'development' ? error.stack : undefined,
    });
  }
});

/**
 * POST /api/group/change-avatar/:groupId
 * Change group avatar
 */
router.post('/change-avatar/:groupId', verifyApiKey, async (req, res) => {
  try {
    console.log('📋 [POST /api/group/change-avatar/:groupId] Changing group avatar...');
    console.log('   Request body:', JSON.stringify(req.body));
    
    const { groupId } = req.params;
    const { avatarPath, accountId } = req.body; // avatarPath can be file path or URL
    
    if (!groupId || !avatarPath) {
      return res.status(400).json({
        success: false,
        message: 'groupId and avatarPath are required'
      });
    }
    
    const zalo = getZaloClient(accountId);
    if (!zalo) {
      return res.status(400).json({
        success: false,
        message: 'Zalo client not initialized'
      });
    }
    
    // Check if changeGroupAvatar method exists
    if (typeof zalo.changeGroupAvatar !== 'function') {
      console.error('   ❌ changeGroupAvatar method not found');
      return res.status(500).json({
        success: false,
        message: 'changeGroupAvatar method not available in zalo client'
      });
    }
    
    console.log('   ✅ Calling changeGroupAvatar()...');
    const result = await zalo.changeGroupAvatar(avatarPath, String(groupId));
    
    console.log('   ✅ Group avatar changed successfully');
    console.log('   Result:', JSON.stringify(result));
    
    res.json({
      success: true,
      message: 'Group avatar changed successfully',
      data: result,
    });
  } catch (error) {
    console.error('❌ Change group avatar error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to change group avatar',
      error: process.env.NODE_ENV === 'development' ? error.stack : undefined,
    });
  }
});

module.exports = router;

