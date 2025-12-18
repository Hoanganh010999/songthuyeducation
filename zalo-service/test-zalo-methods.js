/**
 * Test Zalo client methods
 */
require('dotenv').config();
const { getZaloClient, isZaloReady } = require('./services/zaloClient');

console.log('🧪 Testing Zalo Client Methods\n');

if (!isZaloReady()) {
  console.error('❌ Zalo client is not ready! Please login first.');
  process.exit(1);
}

try {
  const zalo = getZaloClient();
  
  console.log('✅ Zalo client obtained\n');
  console.log('📋 Available methods:');
  
  const methods = Object.getOwnPropertyNames(zalo)
    .filter(name => typeof zalo[name] === 'function')
    .sort();
  
  methods.forEach(method => {
    console.log(`   - ${method}`);
  });
  
  console.log('\n🔍 Testing getAllGroups()...');
  
  if (typeof zalo.getAllGroups === 'function') {
    console.log('✅ getAllGroups() method exists');
    
    zalo.getAllGroups()
      .then(groups => {
        console.log('✅ getAllGroups() success!');
        console.log('   Groups count:', groups?.length || 0);
        if (groups && groups.length > 0) {
          console.log('   First group:', JSON.stringify(groups[0], null, 2));
        }
        process.exit(0);
      })
      .catch(error => {
        console.error('❌ getAllGroups() error:', error.message);
        if (error.stack) {
          console.error('   Stack:', error.stack);
        }
        process.exit(1);
      });
  } else {
    console.error('❌ getAllGroups() method NOT found');
    console.log('\n💡 Trying alternative method names...');
    
    // Try common alternatives
    const alternatives = ['getGroups', 'listGroups', 'getGroupList'];
    for (const alt of alternatives) {
      if (typeof zalo[alt] === 'function') {
        console.log(`✅ Found alternative: ${alt}()`);
        zalo[alt]()
          .then(groups => {
            console.log('✅ Success with', alt);
            console.log('   Groups:', groups);
            process.exit(0);
          })
          .catch(error => {
            console.error(`❌ ${alt}() error:`, error.message);
          });
        break;
      }
    }
    
    process.exit(1);
  }
  
} catch (error) {
  console.error('❌ Error:', error.message);
  process.exit(1);
}

