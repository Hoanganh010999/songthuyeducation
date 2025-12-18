<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
      <div class="fixed inset-0 bg-black opacity-50" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-lg">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <h3 class="text-xl font-bold text-white">
                {{ t('accounting.expense_proposal_details') }}
              </h3>
              <p class="text-blue-100 text-sm mt-1">{{ proposal?.code }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-semibold" :class="getStatusBadgeClass(proposal?.status)">
              {{ getStatusText(proposal?.status) }}
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
                <p class="mt-1 text-lg font-medium text-gray-900">{{ proposal?.title }}</p>
              </div>
              
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.financial_plan') }}
                </label>
                <p class="mt-1 text-gray-900">
                  {{ proposal?.financial_plan?.name }}
                  <span class="text-gray-500 text-sm">
                    ({{ formatPeriod(proposal?.financial_plan) }})
                  </span>
                </p>
              </div>

              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.account_item') }}
                </label>
                <p class="mt-1 text-gray-900">
                  {{ proposal?.account_item?.name }}
                  <span class="text-gray-500 text-sm">({{ proposal?.account_item?.code }})</span>
                </p>
              </div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.amount') }}
                </label>
                <p class="mt-1 text-2xl font-bold text-blue-600">
                  {{ formatCurrency(proposal?.amount) }}
                </p>
              </div>

              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.requested_date') }}
                </label>
                <p class="mt-1 text-gray-900">{{ formatDate(proposal?.requested_date) }}</p>
              </div>

              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('accounting.requested_by') }}
                </label>
                <p class="mt-1 text-gray-900">{{ proposal?.requested_by_user?.name }}</p>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div v-if="proposal?.description" class="mb-6">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.description') }}
            </label>
            <div class="mt-2 bg-gray-50 rounded-lg p-4">
              <p class="text-gray-700 whitespace-pre-wrap">{{ proposal?.description }}</p>
            </div>
          </div>

          <!-- Approval/Rejection Info -->
          <div v-if="proposal?.status === 'approved' || proposal?.status === 'rejected'" 
               class="mb-6 p-4 rounded-lg"
               :class="proposal?.status === 'approved' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-medium uppercase tracking-wider"
                       :class="proposal?.status === 'approved' ? 'text-green-700' : 'text-red-700'">
                  {{ proposal?.status === 'approved' ? t('accounting.approved_by') : t('accounting.rejected_by') }}
                </label>
                <p class="mt-1 font-medium"
                   :class="proposal?.status === 'approved' ? 'text-green-900' : 'text-red-900'">
                  {{ proposal?.approved_by_user?.name }}
                </p>
              </div>
              <div>
                <label class="text-xs font-medium uppercase tracking-wider"
                       :class="proposal?.status === 'approved' ? 'text-green-700' : 'text-red-700'">
                  {{ proposal?.status === 'approved' ? t('accounting.approved_at') : t('accounting.rejected_at') }}
                </label>
                <p class="mt-1 font-medium"
                   :class="proposal?.status === 'approved' ? 'text-green-900' : 'text-red-900'">
                  {{ formatDate(proposal?.approved_at) }}
                </p>
              </div>
            </div>
            <div v-if="proposal?.status === 'rejected' && proposal?.rejected_reason" class="mt-3">
              <label class="text-xs font-medium text-red-700 uppercase tracking-wider">
                {{ t('accounting.reject_reason') }}
              </label>
              <p class="mt-1 text-red-900">{{ proposal?.rejected_reason }}</p>
            </div>
          </div>

          <!-- Payment Info -->
          <div v-if="proposal?.status === 'paid'" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="text-sm font-semibold text-blue-900 mb-3">
              {{ t('accounting.payment_info') }}
            </h4>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="text-xs font-medium text-blue-700 uppercase tracking-wider">
                  {{ t('accounting.payment_date') }}
                </label>
                <p class="mt-1 text-blue-900">{{ formatDate(proposal?.payment_date) }}</p>
              </div>
              <div>
                <label class="text-xs font-medium text-blue-700 uppercase tracking-wider">
                  {{ t('accounting.payment_method') }}
                </label>
                <p class="mt-1 text-blue-900">{{ proposal?.payment_method || '-' }}</p>
              </div>
              <div>
                <label class="text-xs font-medium text-blue-700 uppercase tracking-wider">
                  {{ t('accounting.payment_ref') }}
                </label>
                <p class="mt-1 text-blue-900">{{ proposal?.payment_ref || '-' }}</p>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end items-center pt-6 border-t border-gray-200">
            <!-- Approve/Reject moved to Transactions Module -->
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
  proposal: Object
});

defineEmits(['close']);

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
    rejected: t('accounting.rejected'),
    paid: t('accounting.paid')
  };
  return texts[status] || status;
};

const getStatusBadgeClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800 border border-gray-300',
    pending: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
    approved: 'bg-green-100 text-green-800 border border-green-300',
    rejected: 'bg-red-100 text-red-800 border border-red-300',
    paid: 'bg-blue-100 text-blue-800 border border-blue-300'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

