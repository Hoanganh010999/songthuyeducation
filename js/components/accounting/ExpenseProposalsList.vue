<template>
  <div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('accounting.expense_proposals') }}</h2>
        <p class="text-gray-600 mt-1">{{ t('accounting.expense_proposals_subtitle') }}</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>{{ t('accounting.add_expense') }}</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.search') }}</label>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="t('accounting.search_placeholder')"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.status') }}</label>
          <select
            v-model="filters.status"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">{{ t('accounting.all_status') }}</option>
            <option value="pending">{{ t('accounting.pending') }}</option>
            <option value="approved">{{ t('accounting.approved') }}</option>
            <option value="rejected">{{ t('accounting.rejected') }}</option>
            <option value="paid">{{ t('accounting.paid') }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.from_date') }}</label>
          <input
            v-model="filters.from_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.to_date') }}</label>
          <input
            v-model="filters.to_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.code') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.title') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.account_item') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.amount') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.requested_date') }}
            </th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.status') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading">
            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
              {{ t('accounting.loading') }}
            </td>
          </tr>
          <tr v-else-if="proposals.length === 0">
            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
              {{ t('accounting.no_proposals') }}
            </td>
          </tr>
          <tr v-else v-for="proposal in proposals" :key="proposal.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ proposal.code }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
              {{ proposal.title }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ proposal.account_item?.name || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
              {{ formatCurrency(proposal.amount) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(proposal.requested_date) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <span
                class="px-2 py-1 text-xs font-medium rounded-full"
                :class="getStatusClass(proposal.status)"
              >
                {{ t(`accounting.${proposal.status}`) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
              <button
                @click="viewProposal(proposal)"
                class="text-blue-600 hover:text-blue-900"
              >
                {{ t('accounting.view') }}
              </button>
              <!-- Approve/Reject moved to Transactions Module -->
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Expense Proposal Modal (Create/Edit) -->
    <ExpenseProposalModal
      v-if="showModal"
      :proposal="selectedProposal"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- Expense Proposal View Modal -->
    <ExpenseProposalViewModal
      :isOpen="showViewModal"
      :proposal="viewProposalData"
      @close="closeViewModal"
    />
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import ExpenseProposalModal from './ExpenseProposalModal.vue';
import ExpenseProposalViewModal from './ExpenseProposalViewModal.vue';
import { useAccountingSwal } from './useAccountingSwal';

const { t } = useI18n();
const { showSuccess, showError, showConfirm } = useAccountingSwal();

const loading = ref(false);
const proposals = ref([]);
const showModal = ref(false);
const selectedProposal = ref(null);
const showViewModal = ref(false);
const viewProposalData = ref(null);
const filters = ref({
  search: '',
  status: '',
  from_date: '',
  to_date: ''
});

const fetchProposals = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/accounting/expense-proposals', {
      params: filters.value
    });
    proposals.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching proposals:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  selectedProposal.value = null;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedProposal.value = null;
};

const handleSaved = () => {
  closeModal();
  fetchProposals();
};

const viewProposal = (proposal) => {
  viewProposalData.value = proposal;
  showViewModal.value = true;
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewProposalData.value = null;
};

// Approve/Reject moved to Transactions Module

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
};

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString('vi-VN') : '-';
};

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-blue-100 text-blue-800',
    rejected: 'bg-red-100 text-red-800',
    paid: 'bg-green-100 text-green-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

watch(filters, () => {
  fetchProposals();
}, { deep: true });

onMounted(() => {
  fetchProposals();
});
</script>

