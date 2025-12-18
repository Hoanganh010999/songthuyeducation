<template>
  <div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('accounting.income_reports') }}</h2>
        <p class="text-gray-600 mt-1">{{ t('accounting.income_reports_subtitle') }}</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center space-x-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>{{ t('accounting.add_income') }}</span>
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
            <option value="verified">{{ t('accounting.verified') }}</option>
            <option value="rejected">{{ t('accounting.rejected') }}</option>
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
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.code') }}
            </th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.title') }}
            </th>
            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.amount') }}
            </th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('accounting.date') }}
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
          <tr v-else-if="reports.length === 0">
            <td colspan="6" class="px-3 py-3 text-center text-sm text-gray-500">
              {{ t('accounting.no_reports') }}
            </td>
          </tr>
          <tr v-else v-for="report in reports" :key="report.id" class="hover:bg-gray-50">
            <td class="px-3 py-3 text-sm font-medium text-gray-900">
              {{ report.code }}
            </td>
            <td class="px-3 py-3 text-sm text-gray-900">
              <div class="font-medium">{{ report.title }}</div>
              <div class="text-xs text-gray-500">{{ report.payer_name || '-' }}</div>
            </td>
            <td class="px-3 py-3 text-sm text-right text-green-600 font-medium whitespace-nowrap">
              {{ formatCurrency(report.amount) }}
            </td>
            <td class="px-3 py-3 text-sm text-gray-500 whitespace-nowrap">
              {{ formatDate(report.received_date) }}
            </td>
            <td class="px-3 py-3 text-center">
              <span
                class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                :class="getStatusClass(report.status)"
              >
                {{ t(`accounting.${report.status}`) }}
              </span>
            </td>
            <td class="px-3 py-3">
              <div class="flex flex-col gap-1">
                <button
                  @click="viewReport(report)"
                  class="text-xs text-blue-600 hover:text-blue-900 hover:underline text-left"
                >
                  {{ t('accounting.view') }}
                </button>
                <button
                  v-if="report.status === 'pending' || report.status === 'draft'"
                  @click="editReport(report)"
                  class="text-xs text-indigo-600 hover:text-indigo-900 hover:underline text-left"
                >
                  {{ t('accounting.edit') }}
                </button>
                <button
                  v-if="report.status === 'pending'"
                  @click="approveReport(report)"
                  class="text-xs text-green-600 hover:text-green-900 hover:underline text-left font-medium"
                >
                  {{ t('accounting.approve') }}
                </button>
                <button
                  v-if="report.status === 'pending'"
                  @click="rejectReport(report)"
                  class="text-xs text-red-600 hover:text-red-900 hover:underline text-left"
                >
                  {{ t('accounting.reject') }}
                </button>
                <button
                  v-if="report.status === 'draft' || report.status === 'rejected'"
                  @click="deleteReport(report)"
                  class="text-xs text-red-600 hover:text-red-900 hover:underline text-left"
                >
                  {{ t('accounting.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Income Report Modal (Create/Edit) -->
    <IncomeReportModal
      v-if="showModal"
      :report="selectedReport"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- Income Report View Modal -->
    <IncomeReportViewModal
      :isOpen="showViewModal"
      :report="viewReportData"
      :canApprove="true"
      @close="closeViewModal"
      @approve="approveReport"
      @reject="rejectReport"
    />

    <!-- Approve Modal (with Cash Account selection) -->
    <IncomeReportApproveModal
      :isOpen="showApproveModal"
      :report="approveReportData"
      @close="closeApproveModal"
      @approved="handleApproved"
    />
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import IncomeReportModal from './IncomeReportModal.vue';
import IncomeReportViewModal from './IncomeReportViewModal.vue';
import IncomeReportApproveModal from './IncomeReportApproveModal.vue';
import { useAccountingSwal } from './useAccountingSwal';

const { t } = useI18n();
const { showSuccess, showError, showConfirm, showDeleteConfirm } = useAccountingSwal();

const loading = ref(false);
const reports = ref([]);
const showModal = ref(false);
const selectedReport = ref(null);
const showViewModal = ref(false);
const viewReportData = ref(null);
const showApproveModal = ref(false);
const approveReportData = ref(null);
const filters = ref({
  search: '',
  status: '',
  from_date: '',
  to_date: ''
});

const fetchReports = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/accounting/income-reports', {
      params: filters.value
    });
    reports.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching reports:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  selectedReport.value = null;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedReport.value = null;
};

const handleSaved = () => {
  closeModal();
  fetchReports();
};

const viewReport = (report) => {
  viewReportData.value = report;
  showViewModal.value = true;
};

const editReport = (report) => {
  selectedReport.value = report;
  showModal.value = true;
};

const deleteReport = async (report) => {
  const result = await showDeleteConfirm(report.title);
  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/api/accounting/income-reports/${report.id}`);
    await showSuccess(t('accounting.delete_success'));
    await fetchReports();
  } catch (error) {
    console.error('Error deleting report:', error);
    await showError(error.response?.data?.message || t('accounting.delete_error'));
  }
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewReportData.value = null;
};

const approveReport = (report) => {
  // Open approve modal to select cash account
  approveReportData.value = report;
  showApproveModal.value = true;
};

const closeApproveModal = () => {
  showApproveModal.value = false;
  approveReportData.value = null;
};

const handleApproved = async () => {
  closeApproveModal();
  closeViewModal();
  await fetchReports();
};

const rejectReport = async (report) => {
  const { value: reason } = await Swal.fire({
    title: t('accounting.reject_income'),
    text: `"${report.title}"`,
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
    await axios.post(`/api/accounting/income-reports/${report.id}/reject`, { rejected_reason: reason });
    await showSuccess(t('accounting.income_rejected'));
    closeViewModal();
    await fetchReports();
  } catch (error) {
    console.error('Error rejecting report:', error);
    await showError(error.response?.data?.message || t('accounting.reject_error'));
  }
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
};

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString('vi-VN') : '-';
};

const getStatusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    verified: 'bg-blue-100 text-blue-800',
    rejected: 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

watch(filters, () => {
  fetchReports();
}, { deep: true });

onMounted(() => {
  fetchReports();
});
</script>

