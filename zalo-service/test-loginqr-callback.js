/**
 * Test loginQR with callback as per documentation
 */
require('dotenv').config();
const { Zalo } = require('zalo-api-final');

console.log('🧪 Testing loginQR with callback\n');

const zalo = new Zalo();

console.log('🔐 Starting loginQR with callback...\n');

// According to docs: await zalo.loginQR((qr) => { ... });
zalo.loginQR((qr) => {
  console.log('📱 QR Code callback received!');
  console.log('   QR type:', typeof qr);
  console.log('   QR length:', qr ? qr.length : 0);
  console.log('   QR preview:', qr ? qr.substring(0, 100) + '...' : 'null');
  console.log('\n✅ QR callback works! Now waiting for scan...');
}).then(() => {
  console.log('\n✅✅✅ LOGIN SUCCESSFUL! Promise resolved!');
  console.log('   This means QR was scanned and login completed!');
  process.exit(0);
}).catch((error) => {
  console.error('\n❌ loginQR error:', error.message);
  console.error('   Stack:', error.stack);
  process.exit(1);
});

// Keep process alive
console.log('⏳ Waiting for QR scan... (this may take a while)');

