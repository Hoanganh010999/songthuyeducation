<template>
  <div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('accounting.transactions') }}</h2>
        <p class="text-gray-600 mt-1">{{ t('accounting.transactions_subtitle') }}</p>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('accounting.monthly_trend') }}</h3>
        <canvas ref="monthlyChart"></canvas>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">{{ t('accounting.category_breakdown') }}</h3>
          <select
            v-model="filters.category_breakdown_type"
            @change="renderCategoryChart"
            class="px-3 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="income">{{ t('accounting.income') }}</option>
            <option value="expense">{{ t('accounting.expense') }}</option>
          </select>
        </div>
        <canvas ref="categoryChart"></canvas>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ t('accounting.total_income') }}</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ formatCurrency(summary.total_income) }}</p>
          </div>
          <div class="p-3 bg-green-100 rounded-full">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
            </svg>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ t('accounting.total_expense') }}</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ formatCurrency(summary.total_expense) }}</p>
          </div>
          <div class="p-3 bg-red-100 rounded-full">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
            </svg>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ t('accounting.balance') }}</p>
            <p 
              class="text-2xl font-bold mt-1" 
              :class="summary.balance >= 0 ? 'text-blue-600' : 'text-orange-600'"
            >
              {{ formatCurrency(summary.balance) }}
            </p>
          </div>
          <div class="p-3 bg-blue-100 rounded-full">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.transaction_type') }}</label>
          <select
            v-model="filters.transaction_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">{{ t('accounting.all_types') }}</option>
            <option value="income">{{ t('accounting.income') }}</option>
            <option value="expense">{{ t('accounting.expense') }}</option>
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
        <div class="flex items-end">
          <button
            @click="exportTransactions"
            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center justify-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>{{ t('accounting.export') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.code') }}
            </th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.date') }}
            </th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.type') }} / {{ t('accounting.account_item') }}
            </th>
            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.amount') }}
            </th>
            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.status') }}
            </th>
            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading">
            <td colspan="6" class="px-3 py-3 text-center text-sm text-gray-500">
              {{ t('accounting.loading') }}
            </td>
          </tr>
          <tr v-else-if="transactions.length === 0">
            <td colspan="6" class="px-3 py-3 text-center text-sm text-gray-500">
              {{ t('accounting.no_transactions') }}
            </td>
          </tr>
          <tr v-else v-for="transaction in transactions" :key="transaction.id" class="hover:bg-gray-50">
            <td class="px-3 py-3 text-sm font-medium text-gray-900">
              {{ transaction.code }}
            </td>
            <td class="px-3 py-3 text-sm text-gray-500 whitespace-nowrap">
              {{ formatDate(transaction.transaction_date) }}
            </td>
            <td class="px-3 py-3 text-sm">
              <span
                class="inline-block px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                :class="transaction.transaction_type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ t(`accounting.${transaction.transaction_type}`) }}
              </span>
              <div class="text-xs text-gray-600 mt-1">{{ transaction.account_item?.name || '-' }}</div>
              <div v-if="transaction.payment_method" class="text-xs text-gray-400 mt-0.5">{{ transaction.payment_method }}</div>
            </td>
            <td class="px-3 py-3 text-sm text-right font-medium whitespace-nowrap"
              :class="transaction.transaction_type === 'income' ? 'text-green-600' : 'text-red-600'"
            >
              {{ formatCurrency(transaction.amount) }}
            </td>
            <td class="px-3 py-3 text-center">
              <span
                class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                :class="getStatusClass(transaction.status)"
              >
                {{ t(`accounting.${transaction.status}`) }}
              </span>
            </td>
            <td class="px-3 py-3">
              <div class="flex flex-col gap-1">
                <button
                  @click="viewTransaction(transaction)"
                  class="text-xs text-blue-600 hover:text-blue-900 hover:underline text-left"
                >
                  {{ t('accounting.view') }}
                </button>
                <!-- Approve button for pending expense transactions -->
                <button
                  v-if="transaction.status === 'pending' && transaction.transaction_type === 'expense'"
                  @click="approveTransaction(transaction)"
                  class="text-xs text-green-600 hover:text-green-900 hover:underline text-left font-medium"
                >
                  {{ t('accounting.approve') }}
                </button>
                <!-- Verify button for approved income transactions -->
                <button
                  v-if="transaction.status === 'approved' && transaction.transaction_type === 'income'"
                  @click="verifyTransaction(transaction)"
                  class="text-xs text-purple-600 hover:text-purple-900 hover:underline text-left font-medium"
                >
                  {{ t('accounting.verify') }}
                </button>
                <!-- Reject button -->
                <button
                  v-if="transaction.status === 'pending' || (transaction.status === 'approved' && transaction.transaction_type === 'income')"
                  @click="rejectTransaction(transaction)"
                  class="text-xs text-red-600 hover:text-red-900 hover:underline text-left"
                >
                  {{ t('accounting.reject') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Transaction View Modal -->
    <TransactionViewModal
      :isOpen="showViewModal"
      :transaction="viewTransactionData"
      @close="closeViewModal"
    />

    <!-- Transaction Approve Modal (for Expense) -->
    <TransactionApproveModal
      v-if="showApproveModal"
      :transaction="approveTransactionData"
      @close="closeApproveModal"
      @approved="handleApproved"
    />
  </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';
import Swal from 'sweetalert2';
import { useAccountingSwal } from './useAccountingSwal';
import TransactionViewModal from './TransactionViewModal.vue';
import TransactionApproveModal from './TransactionApproveModal.vue';

Chart.register(...registerables);

const { t } = useI18n();
const { showSuccess, showError, showConfirm } = useAccountingSwal();

const monthlyChart = ref(null);
const categoryChart = ref(null);
let monthlyChartInstance = null;
let categoryChartInstance = null;

const loading = ref(false);
const transactions = ref([]);
const summary = ref({
  total_income: 0,
  total_expense: 0,
  balance: 0
});
const filters = ref({
  search: '',
  transaction_type: '',
  from_date: '',
  to_date: '',
  category_breakdown_type: 'income' // For category breakdown chart (income/expense)
});

// Modal refs
const showViewModal = ref(false);
const viewTransactionData = ref(null);
const showApproveModal = ref(false);
const approveTransactionData = ref(null);

const fetchSummary = async () => {
  try {
    const response = await axios.get('/api/accounting/transactions/summary', {
      params: filters.value
    });
    summary.value = response.data;
  } catch (error) {
    console.error('Error fetching summary:', error);
  }
};

const fetchTransactions = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/accounting/transactions', {
      params: filters.value
    });
    transactions.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching transactions:', error);
  } finally {
    loading.value = false;
  }
};

const viewTransaction = (transaction) => {
  viewTransactionData.value = transaction;
  showViewModal.value = true;
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewTransactionData.value = null;
};

const approveTransaction = (transaction) => {
  // Check if transaction is still pending
  if (transaction.status !== 'pending') {
    showError(t('accounting.can_only_approve_pending'));
    return;
  }
  
  // Open approve modal for expense transactions
  approveTransactionData.value = transaction;
  showApproveModal.value = true;
};

const verifyTransaction = async (transaction) => {
  const result = await showConfirm(
    t('accounting.verify'),
    `${t('accounting.verify')} transaction "${transaction.code}"?`
  );
  if (!result.isConfirmed) return;
  
  try {
    await axios.post(`/api/accounting/transactions/${transaction.id}/verify`);
    await showSuccess(t('accounting.income_verified'));
    await fetchTransactions();
    await fetchSummary();
  } catch (error) {
    console.error('Error verifying transaction:', error);
    await showError(error.response?.data?.message || t('accounting.verify_error'));
  }
};

const rejectTransaction = async (transaction) => {
  const { value: reason } = await Swal.fire({
    title: t('accounting.reject'),
    text: `Transaction "${transaction.code}"`,
    input: 'textarea',
    inputLabel: t('accounting.reject_reason'),
    inputPlaceholder: t('accounting.reject_reason_placeholder'),
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: t('accounting.reject'),
    cancelButtonText: t('accounting.cancel'),
    inputValidator: (value) => {
      if (!value) {
        return t('accounting.reject_reason_required');
      }
    }
  });

  if (!reason) return;

  try {
    await axios.post(`/api/accounting/transactions/${transaction.id}/reject`, {
      rejected_reason: reason
    });
    await showSuccess(t('accounting.transaction_rejected'));
    await fetchTransactions();
    await fetchSummary();
  } catch (error) {
    console.error('Error rejecting transaction:', error);
    await showError(error.response?.data?.message || t('accounting.reject_error'));
  }
};

const closeApproveModal = () => {
  showApproveModal.value = false;
  approveTransactionData.value = null;
};

const handleApproved = async () => {
  closeApproveModal();
  await fetchTransactions();
  await fetchSummary();
};

const getStatusClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-blue-100 text-blue-800',
    'verified': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const exportTransactions = async () => {
  try {
    const params = new URLSearchParams(filters.value).toString();
    window.open(`/api/accounting/transactions/export?${params}`, '_blank');
  } catch (error) {
    console.error('Error exporting transactions:', error);
    alert(t('accounting.export_error'));
  }
};

const fetchCashFlow = async () => {
  try {
    const response = await axios.get('/api/accounting/transactions/cash-flow', {
      params: filters.value
    });
    // Ensure we return an array
    const data = response.data;
    return Array.isArray(data) ? data : (data.data || []);
  } catch (error) {
    console.error('Error fetching cash flow:', error);
    return [];
  }
};

const fetchCategoryBreakdown = async () => {
  try {
    const response = await axios.get('/api/accounting/transactions/category-breakdown', {
      params: {
        year: filters.value.year || new Date().getFullYear(),
        type: filters.value.category_breakdown_type || 'income',
        branch_id: filters.value.branch_id
      }
    });
    // Ensure we return an array
    const data = response.data;
    return Array.isArray(data) ? data : (data.data || []);
  } catch (error) {
    console.error('Error fetching category breakdown:', error);
    return [];
  }
};

const renderMonthlyChart = async () => {
  const cashFlow = await fetchCashFlow();
  
  console.log('📊 Monthly cash flow data:', cashFlow);
  
  if (!Array.isArray(cashFlow) || cashFlow.length === 0) {
    console.warn('⚠️ No cash flow data available for monthly chart');
    return;
  }
  
  if (monthlyChartInstance) {
    monthlyChartInstance.destroy();
  }
  
  if (!monthlyChart.value) return;
  
  const ctx = monthlyChart.value.getContext('2d');
  monthlyChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: cashFlow.map(item => item.date || item.period),
      datasets: [
        {
          label: t('accounting.income'),
          data: cashFlow.map(item => item.income || 0),
          borderColor: 'rgb(34, 197, 94)',
          backgroundColor: 'rgba(34, 197, 94, 0.1)',
          tension: 0.4
        },
        {
          label: t('accounting.expense'),
          data: cashFlow.map(item => item.expense || 0),
          borderColor: 'rgb(239, 68, 68)',
          backgroundColor: 'rgba(239, 68, 68, 0.1)',
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return new Intl.NumberFormat('vi-VN', { 
                style: 'currency', 
                currency: 'VND',
                notation: 'compact'
              }).format(value);
            }
          }
        }
      }
    }
  });
};

const renderCategoryChart = async () => {
  const breakdown = await fetchCategoryBreakdown();
  
  console.log('📊 Category breakdown data:', breakdown);
  console.log('📊 Is array?', Array.isArray(breakdown));
  console.log('📊 Type:', typeof breakdown);
  
  if (!Array.isArray(breakdown) || breakdown.length === 0) {
    console.warn('⚠️ No category breakdown data available');
    return;
  }
  
  if (categoryChartInstance) {
    categoryChartInstance.destroy();
  }
  
  if (!categoryChart.value) return;
  
  const ctx = categoryChart.value.getContext('2d');
  categoryChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: breakdown.map(item => item.category_name || t('accounting.uncategorized')),
      datasets: [{
        data: breakdown.map(item => Math.abs(item.total)),
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(34, 197, 94, 0.8)',
          'rgba(251, 191, 36, 0.8)',
          'rgba(168, 85, 247, 0.8)',
          'rgba(236, 72, 153, 0.8)',
          'rgba(20, 184, 166, 0.8)',
          'rgba(249, 115, 22, 0.8)'
        ],
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: 'right',
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.parsed || 0;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = ((value / total) * 100).toFixed(1);
              return label + ': ' + formatCurrency(value) + ' (' + percentage + '%)';
            }
          }
        }
      }
    }
  });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
};

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString('vi-VN') : '-';
};

watch(filters, async () => {
  await Promise.all([
    fetchSummary(),
    fetchTransactions()
  ]);
  
  await nextTick();
  await Promise.all([
    renderMonthlyChart(),
    renderCategoryChart()
  ]);
}, { deep: true });

onMounted(async () => {
  await Promise.all([
    fetchSummary(),
    fetchTransactions()
  ]);
  
  await nextTick();
  await Promise.all([
    renderMonthlyChart(),
    renderCategoryChart()
  ]);
});
</script>

