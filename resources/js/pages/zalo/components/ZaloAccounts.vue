<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('zalo.accounts') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ t('zalo.accounts_subtitle') }}</p>
      </div>
      <button
        @click="showAddAccountModal = true"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('zalo.add_account') }}
      </button>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div v-if="loading" class="text-center py-12">
        <svg class="animate-spin h-8 w-8 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-gray-600">{{ t('common.loading') }}</p>
      </div>

      <div v-else-if="accounts.length > 0" class="space-y-3">
        <div
          v-for="account in accounts"
          :key="account.id"
          class="flex items-center gap-4 p-4 border rounded-lg transition-colors cursor-pointer"
          :class="account.is_active
            ? 'border-blue-500 bg-blue-50'
            : 'border-gray-200 hover:bg-gray-50'"
          @click="account.is_active ? null : setActiveAccount(account.id)"
        >
          <!-- Radio button for selecting active account -->
          <label class="flex items-center cursor-pointer">
            <input
              type="radio"
              :checked="account.is_active"
              @click.stop="setActiveAccount(account.id)"
              class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500 cursor-pointer"
            />
          </label>

          <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden bg-blue-100">
            <img
              v-if="account.avatar_url"
              :src="account.avatar_url"
              :alt="account.name"
              class="w-full h-full object-cover"
              @error="handleImageError"
            />
            <svg v-else class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>

          <!-- Info Section -->
          <div class="flex-1 min-w-0 mr-4">
            <div class="flex items-start gap-3">
              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 truncate">{{ account.name || account.zalo_id }}</p>
                <p class="text-sm text-gray-600 truncate">{{ account.phone || account.zalo_id }}</p>
              </div>

              <!-- Badges aligned to the right of name -->
              <div class="flex flex-wrap items-center gap-1.5 flex-shrink-0">
                <span
                  v-if="account.is_active"
                  class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-600 text-white"
                >
                  ✓ {{ t('zalo.selected') }}
                </span>
                <span
                  v-if="account.is_connected"
                  class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700"
                >
                  {{ t('zalo.connected') }}
                </span>
                <span
                  v-else-if="account.is_active"
                  class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700"
                >
                  {{ t('zalo.disconnected') }}
                </span>
              </div>
            </div>

            <div v-if="account.branch || account.assigned_user" class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-gray-500">
              <span v-if="account.branch" class="whitespace-nowrap">
                {{ t('common.branch') }}: {{ account.branch.name }}
              </span>
              <span v-if="account.assigned_user" class="whitespace-nowrap">
                • {{ t('zalo.assigned_to') }}: {{ account.assigned_user.name }}
              </span>
            </div>
            <p v-if="account.last_sync_at" class="text-xs text-gray-500 mt-1">
              {{ t('zalo.last_sync') }}: {{ formatDate(account.last_sync_at) }}
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col gap-2 flex-shrink-0">
            <div class="flex items-center gap-2">
              <button
                v-if="canAssignAccount"
                @click.stop="openAssignModal(account)"
                class="px-2.5 py-1.5 text-xs text-purple-600 border border-purple-600 rounded-lg hover:bg-purple-50 whitespace-nowrap"
                :title="account.assigned_user ? t('zalo.reassign') : t('zalo.assign_account')"
              >
                <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ account.assigned_user ? t('zalo.reassign') : t('zalo.assign') }}
              </button>
              <button
                v-if="!account.is_connected"
                @click.stop="reloginAccount(account.id)"
                :disabled="relogining === account.id"
                class="px-2.5 py-1.5 text-xs text-orange-600 border border-orange-600 rounded-lg hover:bg-orange-50 disabled:opacity-50 whitespace-nowrap"
              >
                <span v-if="relogining === account.id">{{ t('common.loading') }}...</span>
                <span v-else>{{ t('zalo.relogin') }}</span>
              </button>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click.stop="syncAccount(account.id)"
                :disabled="syncing === account.id"
                class="px-2.5 py-1.5 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 whitespace-nowrap"
              >
                <span v-if="syncing === account.id">{{ t('common.syncing') }}...</span>
                <span v-else>{{ t('zalo.sync') }}</span>
              </button>
              <button
                v-if="canAssignAccount"
                @click.stop="deleteAccount(account)"
                :disabled="deleting === account.id"
                class="px-2.5 py-1.5 text-xs text-red-600 border border-red-600 rounded-lg hover:bg-red-50 disabled:opacity-50 whitespace-nowrap"
                :title="t('zalo.delete_account')"
              >
                <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span v-if="deleting === account.id">{{ t('common.deleting') }}...</span>
                <span v-else>{{ t('zalo.delete') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12 text-gray-500">
        <p>{{ t('zalo.no_accounts_found') }}</p>
        <button
          @click="showAddAccountModal = true"
          class="mt-4 px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50"
        >
          {{ t('zalo.add_first_account') }}
        </button>
      </div>
    </div>

    <!-- Add Account Modal (simplified - will use QR login) -->
    <div 
      v-if="showAddAccountModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showAddAccountModal = false"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">{{ t('zalo.add_account') }}</h3>
        <p class="text-sm text-gray-600 mb-4">
          {{ t('zalo.add_account_description') }}
        </p>
        <div class="flex justify-end gap-2">
          <button
            @click="showAddAccountModal = false"
            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            {{ t('common.cancel') }}
          </button>
          <button
            @click="initializeNewAccount"
            :disabled="initializing"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            <span v-if="initializing">{{ t('common.loading') }}...</span>
            <span v-else>{{ t('zalo.start_login') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Re-login Modal -->
    <div 
      v-if="showReloginModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showReloginModal = false"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">{{ t('zalo.relogin') }}</h3>
        <p class="text-sm text-gray-600 mb-4">
          {{ t('zalo.relogin_description') }}
        </p>
        <div v-if="reloginQrCode" class="mb-4 flex justify-center">
          <img :src="reloginQrCode" alt="QR Code" class="max-w-full h-auto border border-gray-200 rounded-lg" />
        </div>
        <div class="flex justify-end gap-2">
          <button
            @click="showReloginModal = false; reloginQrCode = null; reloginAccountId = null;"
            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            {{ t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Assign Account Modal -->
    <div 
      v-if="showAssignModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showAssignModal = false"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">{{ t('zalo.assign_account') }}</h3>
        <p class="text-sm text-gray-600 mb-4">
          {{ t('zalo.assign_account_description') }}
        </p>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('zalo.select_employee') }}
            </label>
            <select
              v-model="selectedUserId"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :disabled="loadingEmployees"
            >
              <option value="">{{ t('zalo.select_employee_placeholder') }}</option>
              <option 
                v-for="employee in employees" 
                :key="employee.id" 
                :value="employee.id"
              >
                {{ employee.name }} ({{ employee.email }})
              </option>
            </select>
            <p v-if="loadingEmployees" class="text-xs text-gray-500 mt-1">
              {{ t('common.loading') }}...
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
          <button
            @click="closeAssignModal"
            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            {{ t('common.cancel') }}
          </button>
          <button
            @click="assignAccount"
            :disabled="!selectedUserId || assigning"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50"
          >
            <span v-if="assigning">{{ t('common.loading') }}...</span>
            <span v-else>{{ t('common.save') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject, computed } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useAuthStore } from '../../../stores/auth';
import axios from 'axios';
import Swal from 'sweetalert2';

const { t } = useI18n();
const authStore = useAuthStore();
const zaloAccount = inject('zaloAccount', null);
const loading = ref(false);
const accounts = ref([]);
const activeAccount = ref(null);
const showAddAccountModal = ref(false);
const initializing = ref(false);
const syncing = ref(null);
const deleting = ref(null);
const relogining = ref(null);
const showReloginModal = ref(false);
const reloginAccountId = ref(null);
const reloginQrCode = ref(null);
const showAssignModal = ref(false);
const assignAccountId = ref(null);
const selectedUserId = ref(null);
const employees = ref([]);
const loadingEmployees = ref(false);
const assigning = ref(false);
const currentBranchId = ref(null);

const loadAccounts = async () => {
  loading.value = true;
  try {
    console.log('📋 [ZaloAccounts] Loading accounts...');
    
    const [accountsResponse, activeResponse, statusResponse] = await Promise.all([
      axios.get('/api/zalo/accounts'),
      axios.get('/api/zalo/accounts/active').catch(() => ({ data: { success: false } })),
      axios.get('/api/zalo/status').catch(() => ({ data: { isReady: false } }))
    ]);
    
    console.log('📥 [ZaloAccounts] Accounts response:', {
      count: accountsResponse.data.data?.length || 0,
    });
    console.log('📥 [ZaloAccounts] Status response:', {
      isReady: statusResponse.data.isReady,
    });
    
    accounts.value = accountsResponse.data.data || [];
    
    // Update connection status for each account based on service status
    // CRITICAL: Only mark as connected if service is actually ready
    const isServiceReady = statusResponse.data.isReady || false;
    console.log('🔌 [ZaloAccounts] Service status:', {
      isReady: isServiceReady,
      timestamp: new Date().toISOString(),
    });
    
    accounts.value = accounts.value.map(account => {
      // Account is connected ONLY if:
      // 1. Service is ready (zalo-service is initialized and logged in) AND
      // 2. Account is active
      // Note: We don't check last_login_at here because service status is the source of truth
      // If service is not ready, it means login failed or service is not initialized
      const isConnected = isServiceReady && account.is_active;
      
      if (account.is_active && !isConnected) {
        console.log('⚠️ [ZaloAccounts] Account is active but service not ready:', {
          account_id: account.id,
          account_name: account.name,
          isServiceReady: isServiceReady,
        });
      }
      
      return {
        ...account,
        is_connected: isConnected,
      };
    });
    
    if (activeResponse.data.success) {
      activeAccount.value = activeResponse.data.data;
    }
    
    // If any account is missing name/avatar and service is ready, try to refresh
    if (isServiceReady) {
      for (const account of accounts.value) {
        if ((!account.name || account.name === account.zalo_id) && account.is_active) {
          // Try to refresh account info in background
          axios.post('/api/zalo/accounts/refresh', { account_id: account.id })
            .then(() => {
              console.log('✅ [ZaloAccounts] Account info refreshed for account:', account.id);
              // Reload accounts after refresh
              setTimeout(() => loadAccounts(), 1000);
            })
            .catch(err => {
              console.warn('⚠️ [ZaloAccounts] Failed to refresh account info:', err);
            });
          break; // Only refresh one at a time
        }
      }
    }
    
    // Update the composable state
    if (zaloAccount) {
      zaloAccount.accounts.value = accounts.value;
      if (activeResponse.data.success) {
        zaloAccount.activeAccount.value = activeResponse.data.data;
        zaloAccount.activeAccountId.value = activeResponse.data.data.id;
      }
    }
  } catch (error) {
    console.error('❌ [ZaloAccounts] Failed to load accounts:', error);
  } finally {
    loading.value = false;
  }
};

const setActiveAccount = async (accountId) => {
  try {
    // Don't set active if already active
    const account = accounts.value.find(acc => acc.id === accountId);
    if (account?.is_active) {
      return;
    }

    await axios.post('/api/zalo/accounts/active', { account_id: accountId });
    await loadAccounts();

    // Get the newly activated account
    const updatedAccount = accounts.value.find(acc => acc.id === accountId);

    // Emit event to notify other components with full account object
    window.dispatchEvent(new CustomEvent('zalo-account-changed', {
      detail: {
        accountId,
        account: updatedAccount
      }
    }));

    Swal.fire({
      icon: 'success',
      title: t('common.success'),
      text: `Đã chọn tài khoản: ${updatedAccount?.name || accountId}`,
      timer: 2000,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
    });
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message,
    });
  }
};

const syncAccount = async (accountId) => {
  syncing.value = accountId;
  try {
    // Sync friends and groups
    await Promise.all([
      axios.get('/api/zalo/friends', { params: { account_id: accountId, sync: true } }),
      axios.get('/api/zalo/groups', { params: { account_id: accountId, sync: true } })
    ]);
    
    await loadAccounts();
    Swal.fire({
      icon: 'success',
      title: t('common.success'),
      text: t('zalo.sync_completed'),
      timer: 2000,
    });
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message,
    });
  } finally {
    syncing.value = null;
  }
};

const reloginAccount = async (accountId) => {
  console.log('🔄 [ZaloAccounts] Starting re-login for account:', accountId);
  relogining.value = accountId;
  reloginAccountId.value = accountId;
  
  try {
    // Call re-login endpoint with account_id
    const response = await axios.post('/api/zalo/accounts/relogin', {
      account_id: accountId
    });
    
    console.log('📥 [ZaloAccounts] Re-login response:', {
      success: response.data.success,
      hasQrCode: !!response.data.qrCode,
    });
    
    if (response.data.success && response.data.qrCode) {
      reloginQrCode.value = response.data.qrCode;
      showReloginModal.value = true;
      
      // Poll for login completion
      let pollCount = 0;
      const maxPolls = 120; // 2 minutes max
      const checkInterval = setInterval(async () => {
        pollCount++;
        console.log(`🔄 [ZaloAccounts] Polling re-login status (attempt ${pollCount})...`);
        
        if (pollCount >= maxPolls) {
          clearInterval(checkInterval);
          Swal.fire({
            icon: 'warning',
            title: t('common.timeout'),
            text: t('zalo.login_timeout'),
          });
          showReloginModal.value = false;
          relogining.value = null;
          return;
        }
        
        try {
          const statusResponse = await axios.get('/api/zalo/status');
          console.log(`🔍 [ZaloAccounts v2] Poll ${pollCount}: isReady = ${statusResponse.data.isReady}`);

          if (statusResponse.data.isReady) {
            console.log('✅✅✅ [ZaloAccounts v2] STATUS READY! Clearing interval and calling update=true...');
            clearInterval(checkInterval);

            // Update account in database
            try {
              console.log('📡📡📡 [ZaloAccounts v2] NOW CALLING /api/zalo/accounts/relogin with update=true', {
                account_id: accountId,
                endpoint: '/api/zalo/accounts/relogin',
                update: true,
                timestamp: new Date().toISOString()
              });

              const updateResponse = await axios.post('/api/zalo/accounts/relogin', {
                account_id: accountId,
                update: true
              });

              console.log('📥 [ZaloAccounts] Update response:', updateResponse.data);

              if (updateResponse.data.success) {
                Swal.fire({
                  icon: 'success',
                  title: t('common.success'),
                  text: t('zalo.relogin_success'),
                  timer: 2000,
                });

                showReloginModal.value = false;
                relogining.value = null;
                await loadAccounts();
              } else {
                // IMPORTANT: Handle validation errors from backend
                console.error('❌ [ZaloAccounts] Validation failed:', updateResponse.data);
                showReloginModal.value = false;
                relogining.value = null;

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
              console.error('❌ [ZaloAccounts] Account update error:', updateError);
              showReloginModal.value = false;
              relogining.value = null;

              Swal.fire({
                icon: 'error',
                title: t('common.error'),
                text: updateError.response?.data?.message || updateError.message,
              });
            }
          }
        } catch (error) {
          console.error('❌ [ZaloAccounts] Status check error:', error);
        }
      }, 1000); // Check every second
    } else {
      Swal.fire({
        icon: 'error',
        title: t('common.error'),
        text: response.data.message || t('zalo.relogin_failed'),
      });
    }
  } catch (error) {
    console.error('❌ [ZaloAccounts] Re-login error:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message,
    });
  } finally {
    relogining.value = null;
  }
};

const deleteAccount = async (account) => {
  const result = await Swal.fire({
    title: t('zalo.confirm_delete'),
    html: `
      <p class="mb-4">${t('zalo.delete_warning')}</p>
      <div class="text-left bg-red-50 p-4 rounded-lg mb-4">
        <p class="font-semibold text-red-800 mb-2">${t('zalo.data_to_delete')}:</p>
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
          <li>${t('zalo.all_friends')}</li>
          <li>${t('zalo.all_groups')}</li>
          <li>${t('zalo.all_messages')}</li>
          <li>${t('zalo.all_conversations')}</li>
        </ul>
      </div>
      <p class="text-red-600 font-semibold">${t('zalo.cannot_be_undone')}</p>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: t('common.delete'),
    cancelButtonText: t('common.cancel'),
  });

  if (!result.isConfirmed) {
    return;
  }

  deleting.value = account.id;
  try {
    const response = await axios.delete(`/api/zalo/accounts/${account.id}`);

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: t('common.success'),
        html: `
          <p class="mb-3">${t('zalo.delete_success')}</p>
          <div class="text-sm text-gray-600">
            <p>${t('zalo.deleted_friends')}: ${response.data.data.deleted_friends}</p>
            <p>${t('zalo.deleted_groups')}: ${response.data.data.deleted_groups}</p>
            <p>${t('zalo.deleted_messages')}: ${response.data.data.deleted_messages}</p>
          </div>
        `,
        timer: 3000,
      });

      await loadAccounts();
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message,
    });
  } finally {
    deleting.value = null;
  }
};

const initializeNewAccount = async () => {
  console.log('🚀 [ZaloAccounts] Starting account initialization...');
  initializing.value = true;
  try {
    console.log('📡 [ZaloAccounts] Calling /api/zalo/initialize with forceNew=true...');
    // This will trigger QR login - account will be created after successful login
    // Use forceNew=true to allow creating new account even if one exists
    const response = await axios.post('/api/zalo/initialize', {
      forceNew: true
    });
    
    console.log('📥 [ZaloAccounts] Response received:', {
      success: response.data.success,
      hasQrCode: !!response.data.qrCode,
      message: response.data.message,
    });
    
    if (response.data.success && response.data.qrCode) {
      console.log('✅ [ZaloAccounts] QR code received, showing modal');
      // Show QR code modal
      Swal.fire({
        title: t('zalo.scan_qr_code'),
        html: `<img src="${response.data.qrCode}" alt="QR Code" style="max-width: 300px;" />`,
        showConfirmButton: false,
        allowOutsideClick: false,
      });
      
      // Poll for login completion
      let pollCount = 0;
      const checkInterval = setInterval(async () => {
        pollCount++;
        console.log(`🔄 [ZaloAccounts] Polling status (attempt ${pollCount})...`);
        try {
          const statusResponse = await axios.get('/api/zalo/status');
          console.log('📊 [ZaloAccounts] Status response:', {
            isReady: statusResponse.data.isReady,
          });
          if (statusResponse.data.isReady) {
            console.log('✅ [ZaloAccounts] Login successful! Saving account...');
            clearInterval(checkInterval);
            
            // Save account to database
            try {
              console.log('💾 [ZaloAccounts] Calling /api/zalo/accounts/save...');
              const saveResponse = await axios.post('/api/zalo/accounts/save');
              console.log('💾 [ZaloAccounts] Save response:', saveResponse.data);
              
              if (saveResponse.data.success) {
                console.log('✅ [ZaloAccounts] Account saved successfully');
              } else {
                console.error('❌ [ZaloAccounts] Account save failed:', saveResponse.data.message);
              }
            } catch (saveError) {
              console.error('❌ [ZaloAccounts] Account save error:', saveError);
              console.error('   Response:', saveError.response?.data);
            }
            
            Swal.close();
            await loadAccounts();
            Swal.fire({
              icon: 'success',
              title: t('common.success'),
              text: t('zalo.login_successful'),
              timer: 2000,
            });
            showAddAccountModal.value = false;
          }
        } catch (e) {
          console.error('❌ [ZaloAccounts] Status check error:', e);
          // Ignore errors during polling
        }
      }, 2000);
      
      // Stop polling after 5 minutes
      setTimeout(() => {
        clearInterval(checkInterval);
        console.log('⏱️ [ZaloAccounts] Polling timeout reached');
      }, 300000);
    } else {
      console.error('❌ [ZaloAccounts] No QR code in response');
      Swal.fire({
        icon: 'error',
        title: t('common.error'),
        text: response.data.message || 'Failed to generate QR code',
      });
    }
  } catch (error) {
    console.error('❌ [ZaloAccounts] Initialize error:', error);
    console.error('   Response:', error.response?.data);
    console.error('   Status:', error.response?.status);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message || 'Failed to initialize account',
    });
  } finally {
    initializing.value = false;
    console.log('🏁 [ZaloAccounts] Initialization process completed');
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString();
};

const handleImageError = (event) => {
  event.target.style.display = 'none';
};

const getCurrentBranchId = () => {
  // Try to get from localStorage (set by branch selector)
  const stored = localStorage.getItem('current_branch_id');
  if (stored) {
    return parseInt(stored);
  }
  
  // Try to get from axios default headers
  const headers = axios.defaults.headers.common;
  if (headers['X-Branch-Id']) {
    return parseInt(headers['X-Branch-Id']);
  }
  
  return null;
};

const openAssignModal = async (account) => {
  assignAccountId.value = account.id;
  selectedUserId.value = account.assigned_to || null;
  showAssignModal.value = true;
  
  // Load employees for current branch
  await loadEmployees();
};

const closeAssignModal = () => {
  showAssignModal.value = false;
  assignAccountId.value = null;
  selectedUserId.value = null;
  employees.value = [];
};

const loadEmployees = async () => {
  loadingEmployees.value = true;
  try {
    const branchId = getCurrentBranchId();
    const params = branchId ? { branch_id: branchId } : {};
    
    const response = await axios.get('/api/users/branch-employees', { params });
    employees.value = response.data.data || [];
  } catch (error) {
    console.error('❌ [ZaloAccounts] Failed to load employees:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message,
    });
  } finally {
    loadingEmployees.value = false;
  }
};

const assignAccount = async () => {
  if (!selectedUserId.value || !assignAccountId.value) {
    return;
  }
  
  assigning.value = true;
  try {
    const branchId = getCurrentBranchId();
    const response = await axios.post('/api/zalo/accounts/assign', {
      account_id: assignAccountId.value,
      assigned_to: selectedUserId.value,
      branch_id: branchId,
    });
    
    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.assign_success'),
        timer: 2000,
      });
      
      closeAssignModal();
      await loadAccounts();
    }
  } catch (error) {
    console.error('❌ [ZaloAccounts] Assign account error:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message,
    });
  } finally {
    assigning.value = false;
  }
};

// Check if user can assign accounts (admin/super-admin or has permission)
const canAssignAccount = computed(() => {
  const user = authStore.currentUser;
  if (!user) return false;
  
  // Check if super-admin or admin
  const isAdmin = user.roles?.some(r => r.name === 'admin' || r.name === 'super-admin');
  if (isAdmin) return true;
  
  // Check if has permission
  const hasPermission = authStore.userPermissions?.some(p => p.name === 'zalo.manage_accounts');
  return hasPermission || false;
});

onMounted(() => {
  loadAccounts();
  
  // Listen for branch changes
  window.addEventListener('storage', (e) => {
    if (e.key === 'current_branch_id') {
      currentBranchId.value = e.newValue ? parseInt(e.newValue) : null;
      loadAccounts();
    }
  });
  
  // Also listen for custom event from BranchSwitcher
  window.addEventListener('branch-changed', () => {
    currentBranchId.value = getCurrentBranchId();
    loadAccounts();
  });
});
</script>

