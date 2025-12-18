<template>
  <div class="h-full flex flex-col overflow-hidden">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex-shrink-0">
      <h2 class="text-lg font-semibold text-gray-900">{{ t('zalo.accounts') }}</h2>
    </div>
    
    <!-- Accounts list -->
    <div class="flex-1 overflow-y-auto">
      <div v-if="loading" class="p-4 text-center text-gray-500 text-sm">
        {{ t('common.loading') }}...
      </div>
      <div v-else-if="accounts.length === 0" class="p-4 text-center text-gray-500 text-sm">
        <p class="mb-4">{{ t('zalo.no_accounts') }}</p>
        <button
          v-if="canManageAccounts"
          @click="showAddAccount = true"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          {{ t('zalo.add_account') }}
        </button>
      </div>
      <div v-else class="divide-y divide-gray-100">
        <div
          v-for="account in accounts"
          :key="account.id"
          @click="showAccountDetails(account)"
          class="w-full px-4 py-3 text-left hover:bg-gray-50 transition-colors flex items-center gap-3 border-b border-gray-100 cursor-pointer"
          :class="account.is_active ? 'bg-blue-50 border-l-4 border-blue-600' : ''"
        >
          <!-- Radio button - only this triggers active selection -->
          <label class="flex items-center cursor-pointer" @click.stop="setActiveAccount(account.id)">
            <input
              type="radio"
              :checked="account.is_active"
              class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 cursor-pointer"
            />
          </label>

          <!-- Avatar -->
          <div class="relative w-10 h-10 rounded-full flex-shrink-0 overflow-hidden bg-gray-200">
            <img 
              v-if="account.avatar_url" 
              :src="account.avatar_url" 
              :alt="account.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center bg-blue-100">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <!-- Connection indicator -->
            <div 
              class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border border-white"
              :class="account.is_connected ? 'bg-green-500' : 'bg-gray-400'"
            ></div>
          </div>

          <!-- Account info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <p class="font-medium text-gray-900 truncate text-sm">{{ account.name || account.zalo_id }}</p>
                <!-- 🔥 Primary account indicator -->
                <span
                  v-if="account.is_primary"
                  class="px-1.5 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-800 flex items-center gap-1"
                  :title="t('zalo.primary_account')"
                >
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  {{ t('zalo.primary') }}
                </span>
              </div>
              <div class="flex items-center gap-1 flex-shrink-0">
                <!-- 🔥 NEW: Unread count badge (only for users with manage permission) -->
                <span
                  v-if="canManageAccounts && unreadCounts && unreadCounts[account.id] && unreadCounts[account.id] > 0"
                  class="min-w-[20px] h-5 px-1.5 flex items-center justify-center text-xs font-bold text-white bg-red-500 rounded-full"
                >
                  {{ unreadCounts[account.id] > 99 ? '99+' : unreadCounts[account.id] }}
                </span>

                <!-- Connected badge -->
                <span
                  v-if="account.is_connected"
                  class="px-1.5 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800"
                >
                  {{ t('zalo.connected') }}
                </span>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <p class="text-xs text-gray-500 truncate">{{ account.phone || account.zalo_id }}</p>
              <!-- 🔥 Set Primary button (only for users with manage_accounts permission) -->
              <button
                v-if="canManageAccounts && !account.is_primary"
                @click.stop="setPrimaryAccount(account.id)"
                class="text-xs text-blue-600 hover:text-blue-800 hover:underline"
                :title="t('zalo.set_as_primary')"
              >
                {{ t('zalo.set_primary') }}
              </button>
            </div>
          </div>
        </div>
        
        <!-- Add Account Button (only for users with manage_accounts permission) -->
        <button
          v-if="canManageAccounts"
          @click="showAddAccount = true"
          class="w-full px-4 py-3 text-left hover:bg-gray-50 transition-colors flex items-center gap-3 border-t border-gray-200 mt-2"
        >
          <div class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-100 flex-shrink-0">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
          <span class="font-medium text-gray-900 text-sm">{{ t('zalo.add_account') }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useAuthStore } from '../../../stores/auth';
import axios from 'axios';
import Swal from 'sweetalert2';

const { t } = useI18n();
const authStore = useAuthStore();

const props = defineProps({
  selectedAccount: {
    type: Object,
    default: null
  },
  unreadCounts: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['account-selected', 'add-account']);

const accounts = ref([]);
const loading = ref(false);
const showAddAccount = ref(false);

// Check if user can manage accounts (see all accounts and set primary)
const canManageAccounts = computed(() => {
  return authStore.hasRole('super-admin') || authStore.hasPermission('zalo.manage_accounts');
});

const loadAccounts = async () => {
  loading.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = branchId ? { branch_id: branchId } : {};

    const response = await axios.get('/api/zalo/accounts', { params });
    if (response.data.success) {
      accounts.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to load accounts:', error);
  } finally {
    loading.value = false;
  }
};

const showAccountDetails = (account) => {
  // Emit to parent to show account details
  emit('account-selected', account);
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

    // Emit event to notify other components
    window.dispatchEvent(new CustomEvent('zalo-account-changed', {
      detail: {
        accountId,
        account: updatedAccount
      }
    }));

    // Emit to parent to show details
    emit('account-selected', updatedAccount);

    Swal.fire({
      icon: 'success',
      title: t('common.success'),
      text: t('zalo.account_selected', { name: updatedAccount?.name || accountId }),
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

const setPrimaryAccount = async (accountId) => {
  try {
    const account = accounts.value.find(acc => acc.id === accountId);

    // Confirm action
    const result = await Swal.fire({
      title: t('zalo.set_primary_account'),
      html: t('zalo.set_primary_account_confirm', { name: account.name || account.zalo_id }),
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: t('zalo.confirm'),
      cancelButtonText: t('zalo.cancel'),
      confirmButtonColor: '#3085d6',
    });

    if (!result.isConfirmed) {
      return;
    }

    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.post('/api/zalo/accounts/primary', {
      account_id: accountId,
      branch_id: branchId
    });

    if (response.data.success) {
      await loadAccounts(); // Reload to update is_primary status

      Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.primary_account_set_success', { name: account.name || account.zalo_id }),
        timer: 3000,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
      });
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('zalo.cannot_set_primary'),
    });
  }
};

// Watch for add account
watch(showAddAccount, (value) => {
  if (value) {
    emit('add-account');
  }
});

// 🔥 NEW: Watch unreadCounts to debug
watch(() => props.unreadCounts, (newCounts) => {
  console.log('📊 [ZaloAccountManager] unreadCounts changed:', newCounts);
}, { deep: true });

onMounted(() => {
  loadAccounts();
  console.log('📊 [ZaloAccountManager] Initial unreadCounts:', props.unreadCounts);
});
</script>

