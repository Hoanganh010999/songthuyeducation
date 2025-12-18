<template>
  <div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('accounting.cash_accounts') }}</h2>
        <p class="text-gray-600 mt-1">{{ t('accounting.cash_accounts_subtitle') }}</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>{{ t('accounting.add_account') }}</span>
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <p class="text-sm font-medium text-gray-600">{{ t('accounting.total_cash') }}</p>
        <p class="text-2xl font-bold text-green-600 mt-2">{{ formatCurrency(summary.total_cash) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <p class="text-sm font-medium text-gray-600">{{ t('accounting.total_bank') }}</p>
        <p class="text-2xl font-bold text-blue-600 mt-2">{{ formatCurrency(summary.total_bank) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <p class="text-sm font-medium text-gray-600">{{ t('accounting.total_balance') }}</p>
        <p class="text-2xl font-bold text-gray-900 mt-2">{{ formatCurrency(summary.total_balance) }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('common.search') }}</label>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="t('accounting.search_account_placeholder')"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.account_type') }}</label>
          <select
            v-model="filters.type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">{{ t('common.all') }}</option>
            <option value="cash">{{ t('accounting.cash') }}</option>
            <option value="bank">{{ t('accounting.bank') }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('common.status') }}</label>
          <select
            v-model="filters.is_active"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">{{ t('common.all') }}</option>
            <option value="1">{{ t('common.active') }}</option>
            <option value="0">{{ t('accounting.inactive') }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('accounting.account_code') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.name') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('accounting.type') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('accounting.account_number') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('accounting.bank_name') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('accounting.balance') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading">
            <td colspan="8" class="px-6 py-4 text-center text-gray-500">{{ t('accounting.loading') }}...</td>
          </tr>
          <tr v-else-if="accounts.length === 0">
            <td colspan="8" class="px-6 py-4 text-center text-gray-500">{{ t('accounting.no_accounts') }}</td>
          </tr>
          <tr v-else v-for="account in accounts" :key="account.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ account.code }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ account.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs font-medium rounded-full" :class="account.type === 'cash' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                {{ account.type === 'cash' ? t('accounting.cash') : t('accounting.bank') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ account.account_number || '-' }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ account.bank_name || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">{{ formatCurrency(account.balance) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs font-medium rounded-full" :class="account.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                {{ account.is_active ? t('common.active') : t('accounting.inactive') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button @click="openEditModal(account)" class="text-blue-600 hover:text-blue-900 mr-3">{{ t('common.edit') }}</button>
              <button @click="confirmDelete(account)" class="text-red-600 hover:text-red-900">{{ t('common.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <CashAccountModal
      :isOpen="showModal"
      :account="selectedAccount"
      @close="closeModal"
      @saved="onSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useI18n } from '../../composables/useI18n';
import CashAccountModal from './CashAccountModal.vue';
import { useAccountingSwal } from './useAccountingSwal';

const { t } = useI18n();
const { showSuccess, showError, showDeleteConfirm } = useAccountingSwal();

const loading = ref(false);
const accounts = ref([]);
const summary = ref({ total_cash: 0, total_bank: 0, total_balance: 0 });
const filters = ref({ search: '', type: '', is_active: '' });

// Log filters changes
watch(filters, (newFilters) => {
  console.log('🔄 Filters changed:', newFilters);
}, { deep: true });
const showModal = ref(false);
const selectedAccount = ref(null);

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
};

const fetchAccounts = async () => {
  loading.value = true;
  try {
    console.log('🔍 Fetching cash accounts with filters:', filters.value);
    console.log('🔍 Filters object:', JSON.stringify(filters.value));
    
    // Don't send empty string filters
    const params = {};
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.type) params.type = filters.value.type;
    if (filters.value.is_active !== '') params.is_active = filters.value.is_active;
    
    console.log('📤 Sending params:', params);
    
    const response = await axios.get('/api/accounting/cash-accounts', { params });
    console.log('✅ Raw response:', response);
    console.log('📦 response.data:', response.data);
    console.log('📦 response.data.data:', response.data.data);
    console.log('📦 Array.isArray:', Array.isArray(response.data.data));
    
    if (response.data && response.data.data) {
      accounts.value = response.data.data;
    } else if (Array.isArray(response.data)) {
      accounts.value = response.data;
    } else {
      accounts.value = [];
    }
    
    console.log('💾 Accounts set to:', accounts.value);
    console.log('📊 Total accounts:', accounts.value?.length || 0);
    console.log('📊 First account:', accounts.value[0]);
  } catch (error) {
    console.error('❌ Error fetching cash accounts:', error);
    console.error('❌ Error response:', error.response);
    accounts.value = [];
  } finally {
    loading.value = false;
  }
};

const fetchSummary = async () => {
  try {
    const response = await axios.get('/api/accounting/cash-accounts/summary');
    summary.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching summary:', error);
  }
};

const openCreateModal = () => {
  selectedAccount.value = null;
  showModal.value = true;
};

const openEditModal = (account) => {
  selectedAccount.value = account;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedAccount.value = null;
};

const onSaved = async () => {
  await fetchAccounts();
  await fetchSummary();
};

const confirmDelete = async (account) => {
  const result = await showDeleteConfirm(account.name);
  if (!result.isConfirmed) return;
  
  try {
    await axios.delete(`/api/accounting/cash-accounts/${account.id}`);
    await showSuccess('Xóa tài khoản thành công');
    await fetchAccounts();
    await fetchSummary();
  } catch (error) {
    console.error('Error deleting account:', error);
    await showError(error.response?.data?.message || 'Lỗi xóa tài khoản');
  }
};

watch(filters, () => { fetchAccounts(); }, { deep: true });

onMounted(() => {
  fetchAccounts();
  fetchSummary();
});
</script>

