<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
      <div class="fixed inset-0 bg-black opacity-50" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 rounded-t-lg">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <h3 class="text-xl font-bold text-white">
                {{ t('accounting.income_report_details') }}
              </h3>
              <p class="text-green-100 text-sm mt-1">{{ report?.code }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-semibold" :class="getStatusBadgeClass(report?.status)">
              {{ getStatusText(report?.status) }}
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
                  {{ t('accounting.title') }}
                </label>
                <p class="mt-1 text-lg font-medium text-gray-900">{{ report?.title }}</p>
              </div>
              
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.financial_plan') }}
                </label>
                <p class="mt-1 text-gray-900">
                  <span v-if="report?.financial_plan">
                    {{ report.financial_plan.name }}
                    <span class="text-gray-500 text-sm">
                      ({{ formatPeriod(report.financial_plan) }})
                    </span>
                  </span>
                  <span v-else class="text-gray-500 italic">{{ t('accounting.unplanned') }}</span>
                </p>
              </div>

              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.account_item') }}
                </label>
                <p class="mt-1 text-gray-900">
                  {{ report?.account_item?.name }}
                  <span class="text-gray-500 text-sm">({{ report?.account_item?.code }})</span>
                </p>
              </div>

              <!-- Payer Info -->
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.payer_name') }}
                </label>
                <p class="mt-1 text-gray-900">{{ report?.payer_name || '-' }}</p>
                <p v-if="report?.payer_phone" class="text-sm text-gray-500">{{ report.payer_phone }}</p>
              </div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.amount') }}
                </label>
                <p class="mt-1 text-2xl font-bold text-green-600">
                  {{ formatCurrency(report?.amount) }}
                </p>
              </div>

              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.received_date') }}
                </label>
                <p class="mt-1 text-gray-900">{{ formatDate(report?.received_date) }}</p>
              </div>

              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.reported_by') }}
                </label>
                <p class="mt-1 text-gray-900">{{ report?.reported_by_user?.name }}</p>
              </div>

              <!-- Cash Account (if approved) -->
              <div v-if="report?.cash_account">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.cash_account') }}
                </label>
                <p class="mt-1 text-gray-900">
                  {{ report.cash_account.name }}
                  <span class="text-gray-500 text-sm">
                    ({{ report.cash_account.type === 'cash' ? t('accounting.cash') : t('accounting.bank') }})
                  </span>
                </p>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div v-if="report?.description" class="mb-6">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.description') }}
            </label>
            <div class="mt-2 bg-gray-50 rounded-lg p-4">
              <p class="text-gray-700 whitespace-pre-wrap">{{ report?.description }}</p>
            </div>
          </div>

          <!-- Approval Info (Accountant) -->
          <div v-if="report?.status === 'approved' || report?.status === 'verified'" 
               class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200">
            <h4 class="text-sm font-semibold text-green-900 mb-3">
              {{ t('accounting.approved_info') }}
            </h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-medium text-green-700 uppercase tracking-wider">
                  {{ t('accounting.approved_by') }}
                </label>
                <p class="mt-1 font-medium text-green-900">
                  {{ report?.approved_by_user?.name }}
                </p>
              </div>
              <div>
                <label class="text-xs font-medium text-green-700 uppercase tracking-wider">
                  {{ t('accounting.approved_at') }}
                </label>
                <p class="mt-1 font-medium text-green-900">
                  {{ formatDate(report?.approved_at) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Verification Info (Cashier) -->
          <div v-if="report?.status === 'verified'" 
               class="mb-6 p-4 rounded-lg bg-blue-50 border border-blue-200">
            <h4 class="text-sm font-semibold text-blue-900 mb-3">
              {{ t('accounting.verification_info') }}
            </h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-medium text-blue-700 uppercase tracking-wider">
                  {{ t('accounting.verified_by') }}
                </label>
                <p class="mt-1 font-medium text-blue-900">
                  {{ report?.verified_by_user?.name }}
                </p>
              </div>
              <div>
                <label class="text-xs font-medium text-blue-700 uppercase tracking-wider">
                  {{ t('accounting.verified_at') }}
                </label>
                <p class="mt-1 font-medium text-blue-900">
                  {{ formatDate(report?.verified_at) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Rejection Info -->
          <div v-if="report?.status === 'rejected'" 
               class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-medium text-red-700 uppercase tracking-wider">
                  {{ t('accounting.rejected_by') }}
                </label>
                <p class="mt-1 font-medium text-red-900">
                  {{ report?.approved_by_user?.name }}
                </p>
              </div>
              <div>
                <label class="text-xs font-medium text-red-700 uppercase tracking-wider">
                  {{ t('accounting.rejected_at') }}
                </label>
                <p class="mt-1 font-medium text-red-900">
                  {{ formatDate(report?.approved_at) }}
                </p>
              </div>
            </div>
            <div v-if="report?.rejected_reason" class="mt-3">
              <label class="text-xs font-medium text-red-700 uppercase tracking-wider">
                {{ t('accounting.reject_reason') }}
              </label>
              <p class="mt-1 text-red-900">{{ report.rejected_reason }}</p>
            </div>
          </div>

          <!-- Payment Info -->
          <div v-if="report?.payment_method || report?.payment_ref" class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <h4 class="text-sm font-semibold text-gray-900 mb-3">
              {{ t('accounting.payment_info') }}
            </h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-medium text-gray-700 uppercase tracking-wider">
                  {{ t('accounting.payment_method') }}
                </label>
                <p class="mt-1 text-gray-900">{{ report?.payment_method || '-' }}</p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-700 uppercase tracking-wider">
                  {{ t('accounting.payment_ref') }}
                </label>
                <p class="mt-1 text-gray-900">{{ report?.payment_ref || '-' }}</p>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-between items-center pt-6 border-t border-gray-200">
            <div class="flex gap-2">
              <button
                v-if="report?.status === 'pending' && canApprove"
                @click="$emit('approve', report)"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ t('accounting.approve') }}
              </button>
              
              <button
                v-if="report?.status === 'pending' && canApprove"
                @click="$emit('reject', report)"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                {{ t('accounting.reject') }}
              </button>

              <!-- Verify moved to Transactions Module -->
            </div>

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
import { computed } from 'vue';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();

const props = defineProps({
  isOpen: Boolean,
  report: Object,
  canApprove: {
    type: Boolean,
    default: false
  }
});

defineEmits(['close', 'approve', 'reject']);

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
};

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString('vi-VN') : '-';
};

const formatPeriod = (plan) => {
  if (!plan) return '';
  if (plan.plan_type === 'quarterly') {
    return `Q${plan.quarter}/${plan.year}`;
  }
  return `${plan.month}/${plan.year}`;
};

const getStatusText = (status) => {
  const texts = {
    draft: t('accounting.draft'),
    pending: t('accounting.pending'),
    approved: t('accounting.approved'),
    verified: t('accounting.verified'),
    rejected: t('accounting.rejected')
  };
  return texts[status] || status;
};

const getStatusBadgeClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800 border border-gray-300',
    pending: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
    approved: 'bg-green-100 text-green-800 border border-green-300',
    verified: 'bg-blue-100 text-blue-800 border border-blue-300',
    rejected: 'bg-red-100 text-red-800 border border-red-300'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

