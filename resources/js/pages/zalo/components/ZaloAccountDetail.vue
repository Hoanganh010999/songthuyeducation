<template>
  <div class="h-full flex flex-col overflow-hidden">
    <div v-if="!account && !showAddForm" class="h-full flex items-center justify-center text-gray-500">
      <div class="text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <p class="text-lg font-medium">{{ t('zalo.select_account') || 'Select an account' }}</p>
        <p class="text-sm mt-2">{{ t('zalo.select_account_description') || 'Choose an account from the list to view details' }}</p>
      </div>
    </div>

    <!-- Re-login QR Code Display (when relogin is triggered) -->
    <div v-if="account && !showAddForm && qrCode" class="h-full flex flex-col overflow-hidden">
      <div class="p-6 border-b border-gray-200 flex-shrink-0">
        <h2 class="text-xl font-bold text-gray-900">{{ t('zalo.relogin') || 'Re-login' }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ t('zalo.scan_qr_code') || 'Scan QR code with Zalo app' }}</p>
      </div>
      
      <div class="flex-1 overflow-y-auto px-6 py-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="text-center">
            <p class="text-sm text-gray-600 mb-4">{{ t('zalo.scan_qr_code') || 'Scan QR code with Zalo app' }}</p>
            <img :src="qrCode" alt="QR Code" class="mx-auto max-w-xs border border-gray-200 rounded-lg" />
            <p class="text-xs text-gray-500 mt-4">{{ t('zalo.qr_expires_60s') || 'QR code expires in 60 seconds' }}</p>
            <button
              @click="qrCode = null; initializing = false;"
              class="mt-4 px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ t('common.cancel') || 'Cancel' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Account Details -->
    <div v-else-if="account && !showAddForm && !qrCode" class="h-full flex flex-col overflow-hidden">
      <!-- Header -->
      <div class="p-6 border-b border-gray-200 flex-shrink-0">
        <div class="flex items-center gap-4">
          <div class="relative w-16 h-16 rounded-full flex-shrink-0 overflow-hidden bg-gray-200">
            <img 
              v-if="account.avatar_url" 
              :src="account.avatar_url" 
              :alt="account.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center bg-blue-100">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <!-- Connection indicator -->
            <div 
              class="absolute bottom-0 right-0 w-4 h-4 rounded-full border-2 border-white"
              :class="account.is_connected ? 'bg-green-500' : 'bg-gray-400'"
            ></div>
          </div>
          <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900">{{ account.name || account.zalo_id }}</h2>
            <p class="text-sm text-gray-600 mt-1">{{ account.phone || account.zalo_id }}</p>
            <div class="flex items-center gap-2 mt-2">
              <span 
                v-if="account.is_active"
                class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700"
              >
                {{ t('zalo.active') }}
              </span>
              <span 
                v-if="account.is_primary"
                class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700"
              >
                {{ t('zalo.primary') }}
              </span>
              <span 
                :class="account.is_connected ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                class="px-2 py-1 text-xs font-medium rounded-full"
              >
                {{ account.is_connected ? t('zalo.connected') : t('zalo.disconnected') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Content (only show if not showing QR code) -->
      <div v-if="!qrCode" class="flex-1 overflow-y-auto px-6 py-4 space-y-6">
        <!-- Account Info -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('zalo.account_info') || 'Account Information' }}</h3>
          <dl class="space-y-3">
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ t('zalo.zalo_id') || 'Zalo ID' }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ account.zalo_id }}</dd>
            </div>
            <div v-if="account.phone">
              <dt class="text-sm font-medium text-gray-500">{{ t('common.phone') || 'Phone' }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ account.phone }}</dd>
            </div>
            <div v-if="account.branch">
              <dt class="text-sm font-medium text-gray-500">{{ t('common.branch') || 'Branch' }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ account.branch.name }}</dd>
            </div>
            <div v-if="account.assigned_user">
              <dt class="text-sm font-medium text-gray-500">{{ t('zalo.assigned_to') || 'Assigned To' }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ account.assigned_user.name }}</dd>
            </div>
            <div v-if="account.last_sync_at">
              <dt class="text-sm font-medium text-gray-500">{{ t('zalo.last_sync') || 'Last Sync' }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDate(account.last_sync_at) }}</dd>
            </div>
            <div v-if="account.last_login_at">
              <dt class="text-sm font-medium text-gray-500">{{ t('zalo.last_login') || 'Last Login' }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDate(account.last_login_at) }}</dd>
            </div>
          </dl>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('zalo.actions') || 'Actions' }}</h3>
          <div class="space-y-3">
            <button
              v-if="!account.is_active"
              @click="setActiveAccount"
              class="w-full px-4 py-2 text-left text-sm text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50"
            >
              {{ t('zalo.set_as_active') || 'Set as Active' }}
            </button>
            <button
              v-if="!account.is_primary && (account.is_connected || account.is_active)"
              @click="setPrimaryAccount"
              class="w-full px-4 py-2 text-left text-sm text-yellow-600 border border-yellow-600 rounded-lg hover:bg-yellow-50"
            >
              {{ t('zalo.set_as_primary') || 'Set as Primary' }}
            </button>
            <button
              @click="reloginAccount"
              class="w-full px-4 py-2 text-left text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ t('zalo.relogin') || 'Re-login' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Account Form -->
    <div v-else-if="showAddForm" class="h-full flex flex-col overflow-hidden">
      <div class="p-6 border-b border-gray-200 flex-shrink-0">
        <h2 class="text-xl font-bold text-gray-900">{{ t('zalo.add_account') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ t('zalo.add_account_description') || 'Add a new Zalo account by scanning QR code' }}</p>
      </div>
      
      <div class="flex-1 overflow-y-auto px-6 py-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div v-if="initializing" class="text-center py-12">
            <svg class="animate-spin h-12 w-12 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-gray-600">{{ t('zalo.generating_qr') || 'Generating QR code...' }}</p>
          </div>
          
          <div v-else-if="qrCode" class="text-center">
            <p class="text-sm text-gray-600 mb-4">{{ t('zalo.scan_qr_code') || 'Scan QR code with Zalo app' }}</p>
            <img :src="qrCode" alt="QR Code" class="mx-auto max-w-xs border border-gray-200 rounded-lg" />
            <p class="text-xs text-gray-500 mt-4">{{ t('zalo.qr_expires_60s') || 'QR code expires in 60 seconds' }}</p>
          </div>
          
          <div v-else class="text-center py-12">
            <button
              @click="initializeNewAccount"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              {{ t('zalo.start_login') || 'Start Login' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useSwal } from '../../../composables/useSwal';
import { useZaloAccount } from '../../../composables/useZaloAccount';
import axios from 'axios';

const { t } = useI18n();
const { Swal } = useSwal();
const zaloAccount = useZaloAccount();

const props = defineProps({
  account: {
    type: Object,
    default: null
  },
  showAddForm: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['account-updated', 'close-add-form']);

const initializing = ref(false);
const qrCode = ref(null);
const newAccountId = ref(null); // Store account ID for new accounts

// Store interval IDs at component level for proper cleanup
const loginPollInterval = ref(null);
const syncPollInterval = ref(null);

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('vi-VN');
};

const setActiveAccount = async () => {
  try {
    const success = await zaloAccount.setActiveAccount(props.account.id);
    if (success) {
      Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.account_activated') || 'Account activated',
        timer: 2000,
      });
      emit('account-updated');
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.message || t('zalo.failed_to_activate'),
    });
  }
};

const setPrimaryAccount = async () => {
  try {
    const response = await axios.post('/api/zalo/accounts/primary', {
      account_id: props.account.id
    });
    
    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.primary_account_set'),
        timer: 2000,
      });
      emit('account-updated');
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message || t('zalo.failed_to_set_primary'),
    });
  }
};

const reloginAccount = async () => {
  console.log('🔄 [ZaloAccountDetail] Starting re-login for account:', props.account.id);
  initializing.value = true;
  qrCode.value = null;
  
  try {
    const response = await axios.post('/api/zalo/accounts/relogin', {
      account_id: props.account.id
    });
    
    console.log('📥 [ZaloAccountDetail] Re-login response:', {
      success: response.data.success,
      hasQrCode: !!response.data.qrCode,
    });
    
    if (response.data.success && response.data.qrCode) {
      qrCode.value = response.data.qrCode;
      initializing.value = false;
      // Poll for login completion
      pollForLogin();
    } else {
      initializing.value = false;
      Swal.fire({
        icon: 'error',
        title: t('common.error'),
        text: response.data.message || t('zalo.relogin_failed'),
      });
    }
  } catch (error) {
    console.error('❌ [ZaloAccountDetail] Re-login error:', error);
    initializing.value = false;
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message || t('zalo.relogin_failed'),
    });
  }
};

const initializeNewAccount = async () => {
  initializing.value = true;
  try {
    const response = await axios.post('/api/zalo/initialize', {
      forceNew: true
    });

    console.log('📥 [ZaloAccountDetail] Initialize new account response:', response.data);

    if (response.data.success && response.data.qrCode) {
      qrCode.value = response.data.qrCode;
      // Store the account_id returned from backend
      newAccountId.value = response.data.account_id;
      console.log('✅ [ZaloAccountDetail] Stored new account ID:', newAccountId.value);
      // Poll for login completion
      pollForLogin();
    } else {
      Swal.fire({
        icon: 'error',
        title: t('common.error'),
        text: response.data.message || t('zalo.failed_to_generate_qr'),
      });
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message || t('zalo.failed_to_generate_qr'),
    });
  } finally {
    initializing.value = false;
  }
};

const pollForLogin = () => {
  // Clear any existing login poll to prevent duplicates
  if (loginPollInterval.value) {
    console.log('🧹 [ZaloAccountDetail] Clearing existing login poll interval');
    clearInterval(loginPollInterval.value);
    loginPollInterval.value = null;
  }

  let pollCount = 0;
  const maxPolls = 60; // 60 seconds

  // Determine account ID before polling starts
  const accountId = newAccountId.value || props.account?.id;

  if (!accountId) {
    console.error('❌ [ZaloAccountDetail v2] No account ID available for polling!');
    return;
  }

  console.log('🔍 [ZaloAccountDetail v2] Starting poll for account:', accountId);

  loginPollInterval.value = setInterval(async () => {
    pollCount++;
    console.log(`🔍 [ZaloAccountDetail v2] Poll ${pollCount}: Checking status for account ${accountId}...`);

    try {
      // CRITICAL: Pass account_id to check the correct session
      const response = await axios.get('/api/zalo/status', {
        params: { account_id: accountId }
      });
      console.log(`🔍 [ZaloAccountDetail v2] Poll ${pollCount}: isReady = ${response.data.isReady}`);

      if (response.data.isReady) {
        console.log('✅✅✅ [ZaloAccountDetail v2] STATUS READY! Clearing interval and calling update endpoint...');
        clearInterval(loginPollInterval.value);
        loginPollInterval.value = null;

        // Call appropriate endpoint to update account info
        try {
          // Use different endpoints for new account vs re-login
          const isNewAccount = !!newAccountId.value;
          const endpoint = isNewAccount ? '/api/zalo/accounts/refresh' : '/api/zalo/accounts/relogin';

          console.log('📡📡📡 [ZaloAccountDetail v2] NOW CALLING endpoint to update account', {
            account_id: accountId,
            is_new_account: isNewAccount,
            endpoint: endpoint,
            timestamp: new Date().toISOString()
          });

          const updateResponse = await axios.post(endpoint, {
            account_id: accountId,
            ...(isNewAccount ? {} : { update: true })
          });

          console.log('📥 [ZaloAccountDetail v2] Update response:', updateResponse.data);

          if (updateResponse.data.success) {
            qrCode.value = null;

            // 🔥 NEW: Show sync progress modal
            Swal.fire({
              title: t('zalo.syncing_data'),
              html: `
                <div class="text-left space-y-4">
                  <div>
                    <div class="flex justify-between text-sm mb-1">
                      <span id="friends-message">${t('zalo.syncing_friends')}</span>
                      <span id="friends-percent">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div id="friends-progress" class="bg-blue-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex justify-between text-sm mb-1">
                      <span id="groups-message">${t('zalo.syncing_groups')}</span>
                      <span id="groups-percent">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div id="groups-progress" class="bg-green-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex justify-between text-sm font-bold mb-1">
                      <span>${t('zalo.overall_progress')}</span>
                      <span id="overall-percent">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                      <div id="overall-progress" class="bg-gradient-to-r from-blue-600 to-green-600 h-3 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                  </div>
                </div>
              `,
              allowOutsideClick: false,
              showConfirmButton: false,
              didOpen: () => {
                // 🔥 Poll for sync progress
                pollForSyncProgress(accountId);
              }
            });
          } else {
            // IMPORTANT: Handle validation errors from backend
            console.error('❌ [ZaloAccountDetail v2] Validation failed:', updateResponse.data);
            qrCode.value = null;

            Swal.fire({
              icon: 'error',
              title: t('common.error'),
              text: updateResponse.data.message || t('zalo.relogin_failed'),
              html: updateResponse.data.error_code === 'ACCOUNT_MISMATCH'
                ? `<p class="mb-2">${updateResponse.data.message}</p>
                   <div class="text-sm text-left bg-gray-50 p-3 rounded">
                     <p><strong>Tài khoản mong đợi:</strong> ${updateResponse.data.expected_account}</p>
                     <p><strong>Tài khoản đã dùng:</strong> ${updateResponse.data.received_account}</p>
                   </div>`
                : undefined,
            });
          }
        } catch (updateError) {
          console.error('❌ [ZaloAccountDetail v2] Account update error:', updateError);
          qrCode.value = null;

          Swal.fire({
            icon: 'error',
            title: t('common.error'),
            text: updateError.response?.data?.message || updateError.message,
          });
        }
      }
    } catch (error) {
      console.error('❌ [ZaloAccountDetail v2] Poll error:', error);
    }

    if (pollCount >= maxPolls) {
      clearInterval(loginPollInterval.value);
      loginPollInterval.value = null;
      qrCode.value = null;
      Swal.fire({
        icon: 'error',
        title: t('zalo.qr_expired'),
        text: t('zalo.please_try_again'),
      });
    }
  }, 1000);
};

/**
 * Poll for sync progress after login
 */
const pollForSyncProgress = (accountId) => {
  // CRITICAL: Clear any existing sync poll to prevent duplicate alerts
  if (syncPollInterval.value) {
    console.log('🧹 [ZaloAccountDetail] Clearing existing sync poll interval to prevent duplicates');
    clearInterval(syncPollInterval.value);
    syncPollInterval.value = null;
  }

  let pollCount = 0;
  const maxPolls = 60; // 180 seconds (3 minutes) max (3000ms interval)

  console.log('🔄 [ZaloAccountDetail] Starting sync progress polling for account:', accountId);

  syncPollInterval.value = setInterval(async () => {
    pollCount++;

    try {
      const response = await axios.get('/api/zalo/sync-progress', {
        params: { account_id: accountId }
      });

      if (response.data.success) {
        const data = response.data.data;

        console.log(`🔄 Poll ${pollCount}: Friends ${data.friends.percent}%, Groups ${data.groups.percent}%, Overall ${data.overall_percent}%`);

        // Update friends progress
        const friendsMessage = document.getElementById('friends-message');
        const friendsPercent = document.getElementById('friends-percent');
        const friendsProgress = document.getElementById('friends-progress');
        if (friendsMessage && friendsPercent && friendsProgress) {
          friendsMessage.textContent = data.friends.message;
          friendsPercent.textContent = `${data.friends.percent}%`;
          friendsProgress.style.width = `${data.friends.percent}%`;
        }

        // Update groups progress
        const groupsMessage = document.getElementById('groups-message');
        const groupsPercent = document.getElementById('groups-percent');
        const groupsProgress = document.getElementById('groups-progress');
        if (groupsMessage && groupsPercent && groupsProgress) {
          groupsMessage.textContent = data.groups.message;
          groupsPercent.textContent = `${data.groups.percent}%`;
          groupsProgress.style.width = `${data.groups.percent}%`;
        }

        // Update overall progress
        const overallPercent = document.getElementById('overall-percent');
        const overallProgress = document.getElementById('overall-progress');
        if (overallPercent && overallProgress) {
          overallPercent.textContent = `${data.overall_percent}%`;
          overallProgress.style.width = `${data.overall_percent}%`;
        }

        // If completed, close modal and show success
        if (data.completed) {
          console.log('✅ [ZaloAccountDetail] Sync completed!');
          clearInterval(syncPollInterval.value);
          syncPollInterval.value = null;
          Swal.close();

          Swal.fire({
            icon: 'success',
            title: t('zalo.login_successful'),
            html: t('zalo.synced_friends_and_groups', {
              friends: data.friends.total || 0,
              groups: data.groups.total || 0
            }),
            timer: 3000,
            showConfirmButton: false,
          });

          emit('account-updated');
          emit('close-add-form');

          // 🔥 FIX: Auto-select newly added account for smooth UX
          // Dispatch event to switch to this account
          window.dispatchEvent(new CustomEvent('zalo-account-changed', {
            detail: {
              accountId: accountId,
              account: null // Will be loaded by parent
            }
          }));

          // 🔥 Switch to history tab to show conversations immediately
          window.dispatchEvent(new Event('zalo-switch-to-history'));
        }
      }
    } catch (error) {
      console.error('❌ [ZaloAccountDetail] Error polling sync progress:', error);

      // If error persists, stop polling after 5 failed attempts
      if (pollCount >= 5 && error.response?.status) {
        clearInterval(syncPollInterval.value);
        syncPollInterval.value = null;
        Swal.close();

        Swal.fire({
          icon: 'warning',
          title: t('zalo.login_successful'),
          text: t('zalo.login_successful_but_no_sync_progress'),
          timer: 3000,
        });

        emit('account-updated');
        emit('close-add-form');
      }
    }

    // Timeout after max polls
    if (pollCount >= maxPolls) {
      console.warn('⚠️ [ZaloAccountDetail] Sync progress polling timeout');
      clearInterval(syncPollInterval.value);
      syncPollInterval.value = null;
      Swal.close();

      Swal.fire({
        icon: 'warning',
        title: t('zalo.login_successful'),
        text: t('zalo.login_successful_sync_in_background'),
        timer: 3000,
      });

      emit('account-updated');
      emit('close-add-form');
    }
  }, 3000); // 🔥 FIX: Poll every 3000ms (3 seconds) to prevent spam and rate limiting
};

// Watch for showAddForm changes
watch(() => props.showAddForm, (value) => {
  if (!value) {
    // Clean up all intervals when closing form
    if (loginPollInterval.value) {
      clearInterval(loginPollInterval.value);
      loginPollInterval.value = null;
    }
    if (syncPollInterval.value) {
      clearInterval(syncPollInterval.value);
      syncPollInterval.value = null;
    }

    qrCode.value = null;
    initializing.value = false;
    newAccountId.value = null; // Reset new account ID
  }
});

// Cleanup intervals when component unmounts
onUnmounted(() => {
  console.log('🧹 [ZaloAccountDetail] Component unmounting - cleaning up all intervals');

  if (loginPollInterval.value) {
    clearInterval(loginPollInterval.value);
    loginPollInterval.value = null;
  }

  if (syncPollInterval.value) {
    clearInterval(syncPollInterval.value);
    syncPollInterval.value = null;
  }
});
</script>

