<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
      <div class="fixed inset-0 bg-black opacity-50" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
        <!-- Header -->
        <div class="mb-6">
          <h3 class="text-xl font-bold text-gray-900">
            {{ t('accounting.approve_income') }}
          </h3>
          <p class="text-gray-600 mt-1">{{ report?.code }}</p>
        </div>

        <!-- Report Summary -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase">
                {{ t('accounting.title') }}
              </label>
              <p class="mt-1 font-medium text-gray-900">{{ report?.title }}</p>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase">
                {{ t('accounting.amount') }}
              </label>
              <p class="mt-1 text-lg font-bold text-green-600">{{ formatCurrency(report?.amount) }}</p>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase">
                {{ t('accounting.payer_name') }}
              </label>
              <p class="mt-1 text-gray-900">{{ report?.payer_name || '-' }}</p>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase">
                {{ t('accounting.received_date') }}
              </label>
              <p class="mt-1 text-gray-900">{{ formatDate(report?.received_date) }}</p>
            </div>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit">
          <div class="space-y-4">
            <!-- Cash Account (Required) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.select_receiving_account') }} <span class="text-red-500">*</span>
              </label>
              <select
                v-model="formData.cash_account_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">{{ t('accounting.select_cash_account') }}</option>
                <option v-for="account in cashAccounts" :key="account.id" :value="account.id">
                  {{ account.name }} ({{ account.type === 'cash' ? t('accounting.cash') : t('accounting.bank') }}) - {{ formatCurrency(account.balance) }}
                </option>
              </select>
              <p v-if="cashAccounts.length === 0" class="text-xs text-red-600 mt-1">
                {{ t('accounting.no_cash_accounts') }}
              </p>
              <p v-else class="text-xs text-gray-500 mt-1">
                {{ t('accounting.cash_account_hint') }}
              </p>
            </div>

            <!-- Payment Method -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.payment_method') }}
              </label>
              <input
                v-model="formData.payment_method"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.payment_method')"
              />
            </div>

            <!-- Payment Reference -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.payment_ref') }}
              </label>
              <input
                v-model="formData.payment_ref"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.payment_ref')"
              />
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-2 mt-6 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ t('accounting.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="saving || !formData.cash_account_id"
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="!saving" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ saving ? t('accounting.saving') : t('accounting.approve') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import { useAccountingSwal } from './useAccountingSwal';

const { t } = useI18n();
const { showSuccess, showError } = useAccountingSwal();

const props = defineProps({
  isOpen: Boolean,
  report: Object
});

const emit = defineEmits(['close', 'approved']);

const saving = ref(false);
const cashAccounts = ref([]);
const formData = ref({
  cash_account_id: '',
  payment_method: '',
  payment_ref: ''
});

const fetchCashAccounts = async () => {
  try {
    const response = await axios.get('/api/accounting/cash-accounts', {
      params: { is_active: 1 }
    });
    cashAccounts.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching cash accounts:', error);
  }
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
};

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString('vi-VN') : '-';
};

const handleSubmit = async () => {
  if (!formData.value.cash_account_id) {
    await showError(t('accounting.receiving_account_required'));
    return;
  }

  saving.value = true;
  try {
    await axios.post(`/api/accounting/income-reports/${props.report.id}/approve`, formData.value);
    await showSuccess(t('accounting.income_approved'));
    emit('approved');
  } catch (error) {
    console.error('Error approving report:', error);
    await showError(error.response?.data?.message || t('accounting.approve_error'));
  } finally {
    saving.value = false;
  }
};

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    fetchCashAccounts();
    // Reset form
    formData.value = {
      cash_account_id: '',
      payment_method: props.report?.payment_method || '',
      payment_ref: props.report?.payment_ref || ''
    };
  }
});

onMounted(() => {
  if (props.isOpen) {
    fetchCashAccounts();
  }
});
</script>

