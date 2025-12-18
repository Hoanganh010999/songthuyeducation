/**
 * Test zalo-api-final package directly
 */
require('dotenv').config();

console.log('🧪 Testing zalo-api-final package\n');

// Try to require the package
try {
  const { Zalo } = require('zalo-api-final');
  console.log('✅ Package loaded successfully');
  console.log('   Zalo constructor:', typeof Zalo);
  
  // Try to create instance
  const zalo = new Zalo({
    cookie: '',
    imei: '',
    userAgent: ''
  });
  console.log('✅ Zalo instance created');
  console.log('   Instance type:', typeof zalo);
  console.log('   Has login method:', typeof zalo.login === 'function');
  console.log('   Has getAppState method:', typeof zalo.getAppState === 'function');
  
  // Try to start login with QR
  console.log('\n🔐 Testing QR login...');
  let qrReceived = false;
  let loginError = null;
  
  const loginTimeout = setTimeout(() => {
    if (!qrReceived) {
      console.log('⏱️  QR not received after 5 seconds');
      process.exit(1);
    }
  }, 5000);
  
  zalo.login({
    qrCode: (qr) => {
      qrReceived = true;
      clearTimeout(loginTimeout);
      console.log('✅ QR Code received!');
      console.log('   QR type:', typeof qr);
      console.log('   QR length:', qr ? qr.length : 0);
      console.log('   QR preview:', qr ? qr.substring(0, 50) + '...' : 'null');
      
      // Don't wait for scan
      setTimeout(() => {
        console.log('\n✅ Test completed successfully!');
        console.log('   Package is working correctly.');
        process.exit(0);
      }, 1000);
    }
  }).catch((error) => {
    loginError = error;
    console.error('❌ Login error:', error.message);
    process.exit(1);
  });
  
} catch (error) {
  console.error('❌ Package loading failed:', error.message);
  console.error('   Stack:', error.stack);
  process.exit(1);
}

