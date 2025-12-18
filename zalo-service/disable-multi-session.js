#!/usr/bin/env node
/**
 * Script to disable multi-session and revert to single-session architecture
 */

const fs = require('fs');
const path = require('path');

console.log('🔄 Disabling Multi-Session (Reverting to Single-Session)...\n');

try {
  // Check if backups exist
  console.log('📦 Step 1: Checking for backups...');

  const backupClient = path.join(__dirname, 'services', 'zaloClient.js.backup');
  const backupAuth = path.join(__dirname, 'routes', 'auth.js.backup');

  if (!fs.existsSync(backupClient) || !fs.existsSync(backupAuth)) {
    console.error('\n❌ Error: Backup files not found!');
    console.error('   Cannot revert without backups.');
    console.error('   Multi-session files will remain active.');
    process.exit(1);
  }

  console.log('   ✅ Found backups');

  // Restore from backups
  console.log('\n📝 Step 2: Restoring from backups...');

  fs.copyFileSync(
    backupClient,
    path.join(__dirname, 'services', 'zaloClient.js')
  );
  console.log('   ✅ Restored zaloClient.js from backup');

  fs.copyFileSync(
    backupAuth,
    path.join(__dirname, 'routes', 'auth.js')
  );
  console.log('   ✅ Restored auth.js from backup');

  console.log('\n✅ Reverted to single-session architecture!');
  console.log('\n📌 Next steps:');
  console.log('   1. Restart zalo-service: npm start');
  console.log('   2. Only 1 account will be active at a time');
  console.log('\n💡 To re-enable multi-session: node enable-multi-session.js\n');

} catch (error) {
  console.error('\n❌ Error:', error.message);
  process.exit(1);
}