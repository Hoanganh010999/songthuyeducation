<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('zalo.history') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ t('zalo.history_subtitle') }}</p>
      </div>
      <button
        @click="loadHistory"
        :disabled="loading"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        {{ t('common.refresh') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('zalo.filter_status') }}
          </label>
          <select
            v-model="filters.status"
            @change="loadHistory"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
          >
            <option value="">{{ t('common.all') }}</option>
            <option value="success">{{ t('zalo.success') }}</option>
            <option value="failed">{{ t('zalo.failed') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('zalo.filter_type') }}
          </label>
          <select
            v-model="filters.type"
            @change="loadHistory"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
          >
            <option value="">{{ t('common.all') }}</option>
            <option value="single">{{ t('zalo.single_message') }}</option>
            <option value="bulk">{{ t('zalo.bulk_message') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('zalo.filter_date_from') }}
          </label>
          <input
            v-model="filters.dateFrom"
            type="date"
            @change="loadHistory"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('zalo.filter_date_to') }}
          </label>
          <input
            v-model="filters.dateTo"
            type="date"
            @change="loadHistory"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
        </div>
      </div>
    </div>

    <!-- History List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div v-if="loading" class="text-center py-12">
        <svg class="animate-spin h-8 w-8 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-gray-600">{{ t('common.loading') }}</p>
      </div>

      <div v-else-if="history.length > 0">
        <!-- Table Header -->
        <div class="grid grid-cols-12 gap-4 p-4 bg-gray-50 border-b border-gray-200 font-medium text-sm text-gray-700">
          <div class="col-span-2">{{ t('zalo.recipient') }}</div>
          <div class="col-span-4">{{ t('zalo.message') }}</div>
          <div class="col-span-2">{{ t('zalo.sent_at') }}</div>
          <div class="col-span-2">{{ t('zalo.sent_by') }}</div>
          <div class="col-span-1">{{ t('zalo.type') }}</div>
          <div class="col-span-1 text-center">{{ t('zalo.status') }}</div>
        </div>

        <!-- Table Body -->
        <div class="divide-y divide-gray-200">
          <div v-for="item in history" :key="item.id" 
               class="grid grid-cols-12 gap-4 p-4 hover:bg-gray-50 transition-colors">
            <div class="col-span-2">
              <p class="text-sm font-medium text-gray-900">{{ item.recipient }}</p>
              <p class="text-xs text-gray-500">{{ item.recipient_type }}</p>
            </div>
            <div class="col-span-4">
              <p class="text-sm text-gray-600 line-clamp-2">{{ item.message }}</p>
            </div>
            <div class="col-span-2">
              <p class="text-sm text-gray-900">{{ formatDate(item.sent_at) }}</p>
            </div>
            <div class="col-span-2">
              <p class="text-sm text-gray-900">{{ item.sent_by_name }}</p>
            </div>
            <div class="col-span-1">
              <span class="inline-flex px-2 py-1 text-xs rounded-full"
                    :class="item.is_bulk ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'">
                {{ item.is_bulk ? t('zalo.bulk') : t('zalo.single') }}
              </span>
            </div>
            <div class="col-span-1 text-center">
              <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                    :class="item.status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                {{ item.status === 'success' ? '✓' : '✗' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-200 flex items-center justify-between">
          <p class="text-sm text-gray-600">
            {{ t('zalo.showing_results', { from: pagination.from, to: pagination.to, total: pagination.total }) }}
          </p>
          <div class="flex items-center gap-2">
            <button
              @click="prevPage"
              :disabled="pagination.currentPage === 1"
              class="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 hover:bg-gray-50"
            >
              {{ t('common.previous') }}
            </button>
            <span class="px-3 py-1 text-sm">
              {{ pagination.currentPage }} / {{ pagination.lastPage }}
            </span>
            <button
              @click="nextPage"
              :disabled="pagination.currentPage === pagination.lastPage"
              class="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 hover:bg-gray-50"
            >
              {{ t('common.next') }}
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12 text-gray-500">
        <p>{{ t('zalo.no_history_found') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import axios from 'axios';

const { t } = useI18n();

const loading = ref(false);
const history = ref([]);
const filters = ref({
  status: '',
  type: '',
  dateFrom: '',
  dateTo: '',
});
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  from: 0,
  to: 0,
  total: 0,
  perPage: 20,
});

const loadHistory = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: pagination.value.perPage,
      ...filters.value,
    };

    const response = await axios.get('/api/zalo/history', { params });
    history.value = response.data.data || [];
    
    if (response.data.meta) {
      pagination.value = {
        currentPage: response.data.meta.current_page,
        lastPage: response.data.meta.last_page,
        from: response.data.meta.from || 0,
        to: response.data.meta.to || 0,
        total: response.data.meta.total || 0,
        perPage: response.data.meta.per_page,
      };
    }
  } catch (error) {
    console.error('Failed to load history:', error);
  } finally {
    loading.value = false;
  }
};

const nextPage = () => {
  if (pagination.value.currentPage < pagination.value.lastPage) {
    loadHistory(pagination.value.currentPage + 1);
  }
};

const prevPage = () => {
  if (pagination.value.currentPage > 1) {
    loadHistory(pagination.value.currentPage - 1);
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString();
};

onMounted(() => {
  loadHistory();
});
</script>

