/**
 * Test loginQR method
 */
require('dotenv').config();
const { Zalo } = require('zalo-api-final');

console.log('🧪 Testing loginQR method\n');

const zalo = new Zalo();

let qrReceived = false;

const timeout = setTimeout(() => {
  if (!qrReceived) {
    console.log('❌ QR not received after 5 seconds');
    process.exit(1);
  }
}, 5000);

console.log('🔐 Starting loginQR...');

zalo.loginQR((qr) => {
  qrReceived = true;
  clearTimeout(timeout);
  
  console.log('✅ QR Code received!');
  console.log('   QR type:', typeof qr);
  console.log('   QR length:', qr ? qr.length : 0);
  console.log('   QR preview:', qr ? qr.substring(0, 50) + '...' : 'null');
  
  console.log('\n✅ loginQR() works correctly!');
  
  // Don't wait for scan, exit immediately
  setTimeout(() => {
    process.exit(0);
  }, 1000);
}).catch((error) => {
  console.error('❌ loginQR error:', error.message);
  process.exit(1);
});

