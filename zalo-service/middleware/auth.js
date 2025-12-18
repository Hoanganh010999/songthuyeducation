/**
 * Middleware to verify API requests from Laravel
 */
const verifyApiKey = (req, res, next) => {
  const apiKey = req.headers['x-api-key'] || req.headers['authorization']?.replace('Bearer ', '');
  const expectedKey = process.env.API_SECRET_KEY || process.env.ZALO_API_KEY;
  
  console.log('🔐 API Key verification:');
  console.log('   Method:', req.method);
  console.log('   Path:', req.path);
  console.log('   Has API key:', !!apiKey);
  console.log('   Expected key set:', !!expectedKey);
  
  if (!apiKey) {
    console.log('   ❌ No API key provided');
    return res.status(401).json({
      success: false,
      message: 'API key is required'
    });
  }
  
  if (!expectedKey) {
    console.log('   ❌ API_SECRET_KEY or ZALO_API_KEY not set in environment');
    return res.status(500).json({
      success: false,
      message: 'Server configuration error: API key not configured'
    });
  }
  
  if (apiKey !== expectedKey) {
    console.log('   ❌ API key mismatch');
    console.log('   Received:', apiKey.substring(0, 10) + '...');
    console.log('   Expected:', expectedKey.substring(0, 10) + '...');
    return res.status(403).json({
      success: false,
      message: 'Invalid API key'
    });
  }
  
  console.log('   ✅ API key verified');
  next();
};

module.exports = { verifyApiKey };

