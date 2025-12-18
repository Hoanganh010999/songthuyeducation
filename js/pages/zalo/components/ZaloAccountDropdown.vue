<template>
  <div class="relative">
    <!-- Avatar button -->
    <div 
      @click="showDropdown = !showDropdown"
      class="relative w-10 h-10 rounded-full bg-white flex items-center justify-center cursor-pointer hover:bg-blue-500 transition"
    >
      <img 
        v-if="currentAccount?.avatar_url" 
        :src="currentAccount.avatar_url" 
        alt="Profile"
        class="w-full h-full rounded-full object-cover"
      />
      <svg v-else class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
      </svg>
      
      <!-- Connection indicator -->
      <div 
        class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-blue-600"
        :class="currentAccount?.is_connected ? 'bg-green-500' : 'bg-gray-400'"
        :title="currentAccount?.is_connected ? t('zalo.connected') : t('zalo.disconnected')"
      ></div>
    </div>

    <!-- Dropdown menu -->
    <div 
      v-if="showDropdown"
      v-click-outside="() => showDropdown = false"
      class="absolute left-0 top-12 w-64 bg-white rounded-lg shadow-xl border border-gray-200 z-50 max-h-96 overflow-y-auto"
    >
      <div class="p-2">
        <!-- Header -->
        <div class="px-3 py-2 border-b border-gray-200">
          <h3 class="text-sm font-semibold text-gray-900">{{ t('zalo.accounts') }}</h3>
        </div>

        <!-- Accounts list -->
        <div v-if="loading" class="p-4 text-center text-gray-500 text-sm">
          {{ t('common.loading') }}...
        </div>
        <div v-else-if="accounts.length === 0" class="p-4 text-center text-gray-500 text-sm">
          {{ t('zalo.no_accounts') }}
        </div>
        <div v-else class="divide-y divide-gray-100">
          <button
            v-for="account in accounts"
            :key="account.id"
            @click="selectAccount(account)"
            class="w-full px-3 py-2 text-left hover:bg-gray-50 transition-colors flex items-center gap-3"
            :class="account.id === currentAccount?.id ? 'bg-blue-50' : ''"
          >
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
                <p class="font-medium text-gray-900 truncate text-sm">{{ account.name || account.zalo_id }}</p>
                <div class="flex items-center gap-1 flex-shrink-0">
                  <!-- Primary badge -->
                  <span 
                    v-if="account.is_primary"
                    class="px-1.5 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-800"
                    :title="t('zalo.primary_account')"
                  >
                    {{ t('zalo.primary') }}
                  </span>
                  <!-- Active badge -->
                  <span 
                    v-if="account.is_active && account.id === currentAccount?.id"
                    class="px-1.5 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-800"
                  >
                    {{ t('zalo.active') }}
                  </span>
                </div>
              </div>
              <p class="text-xs text-gray-500 truncate">{{ account.phone || account.zalo_id }}</p>
            </div>
          </button>
        </div>

        <!-- Actions -->
        <template v-if="accounts.filter(acc => !acc.is_primary && (acc.is_connected || acc.is_active)).length > 0">
          <div class="px-3 py-2 border-t border-gray-200 space-y-1">
            <div class="text-xs font-medium text-gray-500 mb-1 px-1">{{ t('zalo.set_primary_account') || 'Set Primary Account' }}</div>
            <button
              v-for="account in accounts.filter(acc => !acc.is_primary && (acc.is_connected || acc.is_active))"
              :key="`primary-${account.id}`"
              @click="setPrimaryAccount(account)"
              class="w-full px-3 py-1.5 text-left text-xs text-gray-700 hover:bg-gray-50 rounded flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
              {{ account.name || account.zalo_id }}
            </button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useZaloAccount } from '../../../composables/useZaloAccount';
import { useSwal } from '../../../composables/useSwal';
import axios from 'axios';

const { t } = useI18n();
const { Swal } = useSwal();
const zaloAccount = useZaloAccount();

const props = defineProps({
  currentAccount: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['account-selected']);

const showDropdown = ref(false);
const accounts = ref([]);
const loading = ref(false);

// Click outside directive
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value();
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  }
};

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

const selectAccount = async (account) => {
  try {
    const success = await zaloAccount.setActiveAccount(account.id);
    if (success) {
      showDropdown.value = false;
      emit('account-selected', account);
      
      // Reload accounts to update active status
      await loadAccounts();
    } else {
      Swal.fire({
        icon: 'error',
        title: t('common.error'),
        text: t('zalo.failed_to_switch_account'),
      });
    }
  } catch (error) {
    console.error('Failed to switch account:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.message || t('zalo.failed_to_switch_account'),
    });
  }
};

const setPrimaryAccount = async (account) => {
  try {
    const response = await axios.post('/api/zalo/accounts/primary', {
      account_id: account.id
    });
    
    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.primary_account_set'),
        timer: 2000,
      });
      
      // Reload accounts to update primary status
      await loadAccounts();
      showDropdown.value = false;
    } else {
      Swal.fire({
        icon: 'error',
        title: t('common.error'),
        text: response.data.message || t('zalo.failed_to_set_primary'),
      });
    }
  } catch (error) {
    console.error('Failed to set primary account:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message || t('zalo.failed_to_set_primary'),
    });
  }
};

// Watch for account changes
watch(() => props.currentAccount, () => {
  if (showDropdown.value) {
    loadAccounts();
  }
});

onMounted(() => {
  loadAccounts();
});
</script>

