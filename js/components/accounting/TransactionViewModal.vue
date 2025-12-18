<template>
  <div v-if="isOpen && transaction" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
      <div class="fixed inset-0 bg-black opacity-50" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full">
        <!-- Header -->
        <div 
          class="px-6 py-4 rounded-t-lg"
          :class="transaction.transaction_type === 'income' ? 'bg-gradient-to-r from-green-600 to-green-700' : 'bg-gradient-to-r from-blue-600 to-blue-700'"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <h3 class="text-xl font-bold text-white">
                {{ t('accounting.transaction_details') }}
              </h3>
              <p class="text-white text-opacity-90 text-sm mt-1">{{ transaction.code }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-semibold" :class="getStatusBadgeClass(transaction.status)">
              {{ t(`accounting.${transaction.status}`) }}
            </span>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Basic Info -->
          <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="space-y-4">
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.transaction_type') }}
                </label>
                <p class="mt-1 text-lg font-medium" :class="transaction.transaction_type === 'income' ? 'text-green-600' : 'text-blue-600'">
                  {{ t(`accounting.${transaction.transaction_type}`) }}
                </p>
              </div>
              
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.amount') }}
                </label>
                <p class="mt-1 text-2xl font-bold" :class="transaction.transaction_type === 'income' ? 'text-green-600' : 'text-blue-600'">
                  {{ formatCurrency(transaction.amount) }}
                </p>
              </div>

              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.account_item') }}
                </label>
                <p class="mt-1 text-gray-900">
                  {{ transaction.account_item?.name || '-' }}
                </p>
              </div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.transaction_date') }}
                </label>
                <p class="mt-1 text-gray-900">{{ formatDate(transaction.transaction_date) }}</p>
              </div>

              <div v-if="transaction.financial_plan">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.financial_plan') }}
                </label>
                <p class="mt-1 text-gray-900">
                  {{ transaction.financial_plan.name }}
                </p>
              </div>

              <div v-if="transaction.cash_account">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.cash_account') }}
                </label>
                <p class="mt-1 text-gray-900">
                  {{ transaction.cash_account.name }}
                </p>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div class="mb-6">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.description') }}
            </label>
            <p class="mt-2 text-gray-900 bg-gray-50 rounded-lg p-4">
              {{ transaction.description || '-' }}
            </p>
          </div>

          <!-- Payment Info (if available) -->
          <div v-if="transaction.payment_method || transaction.payment_ref" class="mb-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">
              {{ t('accounting.payment_info') }}
            </h4>
            <div class="bg-gray-50 rounded-lg p-4 grid grid-cols-2 gap-4">
              <div v-if="transaction.payment_method">
                <label class="text-xs font-medium text-gray-500 uppercase">
                  {{ t('accounting.payment_method') }}
                </label>
                <p class="mt-1 text-gray-900">{{ t(`accounting.${transaction.payment_method}`) }}</p>
              </div>
              <div v-if="transaction.payment_ref">
                <label class="text-xs font-medium text-gray-500 uppercase">
                  {{ t('accounting.payment_ref') }}
                </label>
                <p class="mt-1 text-gray-900">{{ transaction.payment_ref }}</p>
              </div>
            </div>
          </div>

          <!-- Approval Info (if approved) -->
          <div v-if="transaction.status !== 'pending' && transaction.approved_by" class="mb-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">
              {{ t('accounting.approval_info') }}
            </h4>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-xs font-medium text-blue-700 uppercase">
                    {{ t('accounting.approved_by') }}
                  </label>
                  <p class="mt-1 text-blue-900 font-medium">{{ transaction.approved_by?.name || '-' }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-blue-700 uppercase">
                    {{ t('accounting.approved_at') }}
                  </label>
                  <p class="mt-1 text-blue-900">{{ formatDate(transaction.approved_at) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Verification Info (if verified) -->
          <div v-if="transaction.status === 'verified' && transaction.verified_by" class="mb-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">
              {{ t('accounting.verification_info') }}
            </h4>
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-xs font-medium text-green-700 uppercase">
                    {{ t('accounting.verified_by') }}
                  </label>
                  <p class="mt-1 text-green-900 font-medium">{{ transaction.verified_by?.name || '-' }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-green-700 uppercase">
                    {{ t('accounting.verified_at') }}
                  </label>
                  <p class="mt-1 text-green-900">{{ formatDate(transaction.verified_at) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Rejection Info (if rejected) -->
          <div v-if="transaction.status === 'rejected'" class="mb-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">
              {{ t('accounting.rejection_info') }}
            </h4>
            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
              <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                  <label class="text-xs font-medium text-red-700 uppercase">
                    {{ t('accounting.rejected_by') }}
                  </label>
                  <p class="mt-1 text-red-900 font-medium">{{ transaction.rejected_by?.name || '-' }}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-red-700 uppercase">
                    {{ t('accounting.rejected_at') }}
                  </label>
                  <p class="mt-1 text-red-900">{{ formatDate(transaction.rejected_at) }}</p>
                </div>
              </div>
              <div>
                <label class="text-xs font-medium text-red-700 uppercase">
                  {{ t('accounting.reject_reason') }}
                </label>
                <p class="mt-1 text-red-900">{{ transaction.rejected_reason || '-' }}</p>
              </div>
            </div>
          </div>

          <!-- Recorded Info -->
          <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <label class="text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.recorded_by') }}
            </label>
            <p class="mt-1 text-gray-900">
              {{ transaction.recorded_by?.name || '-' }} • {{ formatDate(transaction.created_at) }}
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end items-center pt-6 border-t border-gray-200 mt-6">
            <button
              @click="$emit('close')"
              class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
            >
              {{ t('accounting.close') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();

const props = defineProps({
  isOpen: Boolean,
  transaction: Object
});

defineEmits(['close']);

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('vi-VN', { 
    year: 'numeric', 
    month: '2-digit', 
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getStatusBadgeClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-blue-100 text-blue-800',
    'verified': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

