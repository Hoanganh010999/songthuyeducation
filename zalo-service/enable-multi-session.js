#!/usr/bin/env node
/**
 * Script to enable multi-session architecture
 * This will swap the old single-session files with new multi-session files
 */

const fs = require('fs');
const path = require('path');

console.log('🔄 Enabling Multi-Session Architecture...\n');

try {
  // 1. Backup current files (if not already backed up)
  console.log('📦 Step 1: Checking backups...');

  const backupClient = path.join(__dirname, 'services', 'zaloClient.js.backup');
  const backupAuth = path.join(__dirname, 'routes', 'auth.js.backup');

  if (!fs.existsSync(backupClient)) {
    console.log('   Creating backup: zaloClient.js.backup');
    fs.copyFileSync(
      path.join(__dirname, 'services', 'zaloClient.js'),
      backupClient
    );
  } else {
    console.log('   ✅ Backup already exists: zaloClient.js.backup');
  }

  if (!fs.existsSync(backupAuth)) {
    console.log('   Creating backup: auth.js.backup');
    fs.copyFileSync(
      path.join(__dirname, 'routes', 'auth.js'),
      backupAuth
    );
  } else {
    console.log('   ✅ Backup already exists: auth.js.backup');
  }

  // 2. Copy multi-session files to active location
  console.log('\n📝 Step 2: Activating multi-session files...');

  fs.copyFileSync(
    path.join(__dirname, 'services', 'zaloClientMulti.js'),
    path.join(__dirname, 'services', 'zaloClient.js')
  );
  console.log('   ✅ Copied zaloClientMulti.js → zaloClient.js');

  fs.copyFileSync(
    path.join(__dirname, 'routes', 'authMulti.js'),
    path.join(__dirname, 'routes', 'auth.js')
  );
  console.log('   ✅ Copied authMulti.js → auth.js');

  // 3. Create sessions directory
  console.log('\n📁 Step 3: Creating sessions directory...');
  const sessionsDir = path.join(__dirname, 'sessions');
  if (!fs.existsSync(sessionsDir)) {
    fs.mkdirSync(sessionsDir);
    console.log('   ✅ Created: sessions/');
  } else {
    console.log('   ✅ Already exists: sessions/');
  }

  console.log('\n✅ Multi-session architecture enabled!');
  console.log('\n📌 Next steps:');
  console.log('   1. Restart zalo-service: npm start');
  console.log('   2. Test with 2 accounts');
  console.log('   3. Check logs for multi-session indicators');
  console.log('\n💡 To revert: node disable-multi-session.js\n');

} catch (error) {
  console.error('\n❌ Error:', error.message);
  process.exit(1);
}