/**
 * Test script để debug API key authentication
 */
require('dotenv').config();
const axios = require('axios');

console.log('🔍 Testing Zalo Service Authentication\n');

// 1. Check environment variables
console.log('📋 Environment Variables:');
console.log('   PORT:', process.env.PORT || 'NOT SET');
console.log('   NODE_ENV:', process.env.NODE_ENV || 'NOT SET');
console.log('   API_SECRET_KEY:', process.env.API_SECRET_KEY ? 
  (process.env.API_SECRET_KEY.substring(0, 15) + '...') : 'NOT SET');
console.log('   LARAVEL_URL:', process.env.LARAVEL_URL || 'NOT SET');

if (!process.env.API_SECRET_KEY) {
  console.error('\n❌ API_SECRET_KEY not found in .env file!');
  console.log('Please make sure .env file exists and contains API_SECRET_KEY');
  process.exit(1);
}

const API_KEY = process.env.API_SECRET_KEY;
const BASE_URL = `http://localhost:${process.env.PORT || 3001}`;

console.log('\n🔧 Testing endpoints:\n');

// 2. Test health endpoint (no auth required)
async function testHealth() {
  try {
    const response = await axios.get(`${BASE_URL}/health`);
    console.log('✅ Health check: OK');
    console.log('   Response:', response.data);
    return true;
  } catch (error) {
    console.error('❌ Health check failed:', error.message);
    return false;
  }
}

// 3. Test status endpoint with correct API key
async function testStatusWithKey() {
  try {
    const response = await axios.get(`${BASE_URL}/api/auth/status`, {
      headers: {
        'X-API-Key': API_KEY,
        'Content-Type': 'application/json'
      }
    });
    console.log('✅ Status with API key: OK');
    console.log('   Response:', response.data);
    return true;
  } catch (error) {
    console.error('❌ Status with API key failed:', error.response?.status, error.response?.data || error.message);
    return false;
  }
}

// 4. Test status endpoint without API key
async function testStatusWithoutKey() {
  try {
    const response = await axios.get(`${BASE_URL}/api/auth/status`);
    console.error('❌ Should have been rejected! Got:', response.data);
    return false;
  } catch (error) {
    if (error.response?.status === 401) {
      console.log('✅ Correctly rejected request without API key');
      console.log('   Response:', error.response.data);
      return true;
    }
    console.error('❌ Unexpected error:', error.message);
    return false;
  }
}

// 5. Test status endpoint with wrong API key
async function testStatusWithWrongKey() {
  try {
    const response = await axios.get(`${BASE_URL}/api/auth/status`, {
      headers: {
        'X-API-Key': 'wrong-key-123',
        'Content-Type': 'application/json'
      }
    });
    console.error('❌ Should have been rejected! Got:', response.data);
    return false;
  } catch (error) {
    if (error.response?.status === 403) {
      console.log('✅ Correctly rejected request with wrong API key');
      console.log('   Response:', error.response.data);
      return true;
    }
    console.error('❌ Unexpected error:', error.message);
    return false;
  }
}

// 6. Test initialize endpoint with correct API key
async function testInitialize() {
  try {
    const response = await axios.post(`${BASE_URL}/api/auth/initialize`, {}, {
      headers: {
        'X-API-Key': API_KEY,
        'Content-Type': 'application/json'
      }
    });
    console.log('✅ Initialize with API key: OK');
    console.log('   Response:', response.data.success ? 'QR generated' : response.data);
    return true;
  } catch (error) {
    console.error('❌ Initialize failed:', error.response?.status, error.response?.data || error.message);
    return false;
  }
}

// Run all tests
async function runTests() {
  console.log('1. Testing health endpoint (no auth)...');
  await testHealth();
  
  console.log('\n2. Testing status endpoint without API key...');
  await testStatusWithoutKey();
  
  console.log('\n3. Testing status endpoint with wrong API key...');
  await testStatusWithWrongKey();
  
  console.log('\n4. Testing status endpoint with correct API key...');
  await testStatusWithKey();
  
  console.log('\n5. Testing initialize endpoint with correct API key...');
  await testInitialize();
  
  console.log('\n✅ All tests completed!');
  console.log('\n💡 API Key being used:', API_KEY.substring(0, 15) + '...');
  console.log('💡 Make sure Laravel .env has the same ZALO_API_KEY value!');
}

runTests().catch(console.error);

