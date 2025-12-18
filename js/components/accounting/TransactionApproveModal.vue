<template>
  <div v-if="transaction" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
      <div class="fixed inset-0 bg-black opacity-50" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 rounded-t-lg">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <h3 class="text-xl font-bold text-white">
                {{ t('accounting.approve_transaction') }}
              </h3>
              <p class="text-green-100 text-sm mt-1">{{ transaction.code }}</p>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Transaction Info -->
          <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase">
                  {{ t('accounting.type') }}
                </label>
                <p class="text-sm font-medium text-gray-900">
                  {{ t(`accounting.${transaction.transaction_type}`) }}
                </p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase">
                  {{ t('accounting.amount') }}
                </label>
                <p class="text-sm font-medium text-red-600">
                  {{ formatCurrency(transaction.amount) }}
                </p>
              </div>
              <div class="col-span-2">
                <label class="text-xs font-medium text-gray-500 uppercase">
                  {{ t('accounting.description') }}
                </label>
                <p class="text-sm text-gray-900">
                  {{ transaction.description || '-' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Approval Form -->
          <form @submit.prevent="handleSubmit">
            <div class="space-y-4">
              <!-- Cash Account Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('accounting.select_payment_account') }} *
                </label>
                <!-- If disabled, use readonly select for display + hidden input for value -->
                <template v-if="hasExistingCashAccount">
                  <div class="relative">
                    <select
                      :value="formData.cash_account_id"
                      disabled
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                    >
                      <option 
                        v-for="account in cashAccounts" 
                        :key="account.id" 
                        :value="account.id"
                      >
                        {{ account.name }} - {{ formatCurrency(account.balance) }}
                      </option>
                    </select>
                    <!-- Hidden input to ensure value is sent even when select is disabled -->
                    <input type="hidden" v-model="formData.cash_account_id">
                  </div>
                  <p class="text-xs text-blue-600 mt-1 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    {{ t('accounting.cash_account_already_selected') }}
                  </p>
                </template>
                <!-- Normal select if no existing cash account -->
                <template v-else>
                  <select
                    v-model="formData.cash_account_id"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                  >
                    <option value="">{{ t('accounting.select_account') }}</option>
                    <option 
                      v-for="account in cashAccounts" 
                      :key="account.id" 
                      :value="account.id"
                    >
                      {{ account.name }} - {{ formatCurrency(account.balance) }}
                    </option>
                  </select>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ t('accounting.receiving_account_required') }}
                  </p>
                </template>
              </div>

              <!-- Payment Method (optional) -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('accounting.payment_method') }}
                </label>
                <select
                  v-model="formData.payment_method"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                >
                  <option value="">{{ t('accounting.select_payment_method') }}</option>
                  <option value="cash">{{ t('accounting.cash') }}</option>
                  <option value="bank_transfer">{{ t('accounting.bank_transfer') }}</option>
                  <option value="check">{{ t('accounting.check') }}</option>
                  <option value="credit_card">{{ t('accounting.credit_card') }}</option>
                  <option value="other">{{ t('accounting.other') }}</option>
                </select>
              </div>

              <!-- Payment Reference (optional) -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('accounting.payment_ref') }}
                </label>
                <input
                  v-model="formData.payment_ref"
                  type="text"
                  :placeholder="t('accounting.payment_ref_placeholder')"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                />
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
              <button
                type="button"
                @click="$emit('close')"
                class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
              >
                {{ t('accounting.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="!formData.cash_account_id || submitting"
                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <svg v-if="submitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ t('accounting.approve') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import { useAccountingSwal } from './useAccountingSwal';

const { t } = useI18n();
const { showSuccess, showError } = useAccountingSwal();

const props = defineProps({
  transaction: Object
});

const emit = defineEmits(['close', 'approved']);

const submitting = ref(false);
const cashAccounts = ref([]);

// Debug: Log transaction data
console.log('🔍 Transaction data:', props.transaction);
console.log('🔍 Cash account ID from transaction:', props.transaction?.cash_account_id);

const formData = ref({
  cash_account_id: props.transaction?.cash_account_id || '',
  payment_method: props.transaction?.payment_method || '',
  payment_ref: props.transaction?.payment_ref || ''
});

// Check if cash account was already selected
const hasExistingCashAccount = computed(() => {
  const hasAccount = !!props.transaction?.cash_account_id;
  console.log('🔍 Has existing cash account:', hasAccount);
  return hasAccount;
});

const fetchCashAccounts = async () => {
  try {
    const response = await axios.get('/api/accounting/cash-accounts', {
      params: { status: 'active' }
    });
    cashAccounts.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching cash accounts:', error);
  }
};

const handleSubmit = async () => {
  if (submitting.value) return;
  
  submitting.value = true;
  try {
    console.log('🚀 Approving transaction:', props.transaction.id);
    console.log('📤 Transaction status:', props.transaction.status);
    console.log('📤 Transaction data:', props.transaction);
    console.log('📤 Sending data:', formData.value);
    console.log('📤 Has cash_account_id?', !!formData.value.cash_account_id);
    console.log('📤 Cash account value:', formData.value.cash_account_id);
    
    const response = await axios.post(`/api/accounting/transactions/${props.transaction.id}/approve`, formData.value);
    
    console.log('✅ Approval response:', response.data);
    await showSuccess(t('accounting.transaction_approved'));
    emit('approved');
  } catch (error) {
    console.error('❌ Error approving transaction:', error);
    console.error('❌ Error response:', error.response);
    console.error('❌ Error response data:', error.response?.data);
    console.error('❌ Error message:', error.response?.data?.message);
    console.error('❌ Validation errors:', error.response?.data?.errors);
    
    const errorMsg = error.response?.data?.message || t('accounting.approve_error');
    const validationErrors = error.response?.data?.errors;
    
    if (validationErrors) {
      const errorList = Object.values(validationErrors).flat().join('\n');
      await showError(`${errorMsg}\n${errorList}`);
    } else {
      await showError(errorMsg);
    }
  } finally {
    submitting.value = false;
  }
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
};

onMounted(() => {
  fetchCashAccounts();
});
</script>

