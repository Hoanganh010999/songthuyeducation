<template>
  <div class="flex flex-col h-full bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ t('holidays.module_title') }}</h1>
          <p class="text-sm text-gray-600 mt-1">{{ t('holidays.module_description') }}</p>
        </div>
        <button
          v-if="authStore.hasPermission('holidays.create')"
          @click="openModal()"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center space-x-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          <span>{{ t('holidays.create_holiday') }}</span>
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-6">
      <div class="bg-white rounded-lg shadow">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
        </div>

        <!-- No Data -->
        <div v-else-if="holidays.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <p class="mt-4 text-gray-500">{{ t('holidays.no_holidays') }}</p>
        </div>

        <!-- Table -->
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('holidays.name') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('holidays.start_date') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('holidays.end_date') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('holidays.duration_days') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('holidays.description') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="holiday in holidays" :key="holiday.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-medium text-gray-900">{{ holiday.name }}</td>
              <td class="px-6 py-4 text-gray-600">{{ formatDate(holiday.start_date) }}</td>
              <td class="px-6 py-4 text-gray-600">{{ formatDate(holiday.end_date) }}</td>
              <td class="px-6 py-4 text-gray-600 text-center">{{ calculateDuration(holiday) }}</td>
              <td class="px-6 py-4 text-gray-600 text-sm">{{ holiday.description || '-' }}</td>
              <td class="px-6 py-4 text-right space-x-3">
                <button
                  v-if="authStore.hasPermission('holidays.edit')"
                  @click="openModal(holiday)"
                  class="text-blue-600 hover:text-blue-800"
                >
                  {{ t('common.edit') }}
                </button>
                <button
                  v-if="authStore.hasPermission('holidays.delete')"
                  @click="deleteHoliday(holiday)"
                  class="text-red-600 hover:text-red-800"
                >
                  {{ t('common.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 mb-4">
          {{ editing ? t('holidays.edit_holiday') : t('holidays.create_holiday') }}
        </h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('holidays.name') }}</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('holidays.name_placeholder')"
            />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('holidays.start_date') }}</label>
              <input
                v-model="form.start_date"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('holidays.end_date') }}</label>
              <input
                v-model="form.end_date"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('holidays.description') }}</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('holidays.description_placeholder')"
            ></textarea>
          </div>
        </div>
        
        <div class="flex justify-end space-x-3 mt-6">
          <button
            @click="showModal = false"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
          >
            {{ t('common.cancel') }}
          </button>
          <button
            @click="save"
            :disabled="saving"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
          >
            {{ saving ? t('common.saving') : t('common.save') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';
import Swal from 'sweetalert2';

const { t } = useI18n();
const authStore = useAuthStore();

const loading = ref(false);
const holidays = ref([]);
const showModal = ref(false);
const editing = ref(null);
const saving = ref(false);
const form = ref({ name: '', start_date: '', end_date: '', description: '' });

const loadHolidays = async () => {
  loading.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.get('/api/holidays', { params: { branch_id: branchId } });
    holidays.value = response.data.data;
  } catch (error) {
    console.error('Load holidays error:', error);
    Swal.fire('Lỗi!', 'Không thể tải danh sách lịch nghỉ', 'error');
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  editing.value = item;
  form.value = item
    ? { name: item.name, start_date: item.start_date, end_date: item.end_date, description: item.description || '' }
    : { name: '', start_date: '', end_date: '', description: '' };
  showModal.value = true;
};

const save = async () => {
  if (!form.value.name || !form.value.start_date || !form.value.end_date) {
    Swal.fire('Lỗi!', 'Vui lòng điền đầy đủ thông tin', 'error');
    return;
  }

  saving.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const data = { ...form.value, branch_id: branchId };

    if (editing.value) {
      await axios.put(`/api/holidays/${editing.value.id}`, data);
      await Swal.fire('Thành công!', t('holidays.updated_success'), 'success');
    } else {
      await axios.post('/api/holidays', data);
      await Swal.fire('Thành công!', t('holidays.created_success'), 'success');
    }

    showModal.value = false;
    await loadHolidays();
  } catch (error) {
    console.error('Save holiday error:', error);
    Swal.fire('Lỗi!', error.response?.data?.message || 'Không thể lưu lịch nghỉ', 'error');
  } finally {
    saving.value = false;
  }
};

const deleteHoliday = async (holiday) => {
  const result = await Swal.fire({
    title: t('holidays.confirm_delete'),
    text: holiday.name,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: t('common.delete'),
    cancelButtonText: t('common.cancel')
  });

  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/api/holidays/${holiday.id}`);
    await Swal.fire('Thành công!', t('holidays.deleted_success'), 'success');
    await loadHolidays();
  } catch (error) {
    console.error('Delete holiday error:', error);
    Swal.fire('Lỗi!', 'Không thể xóa lịch nghỉ', 'error');
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN', { year: 'numeric', month: '2-digit', day: '2-digit' });
};

const calculateDuration = (holiday) => {
  const start = new Date(holiday.start_date);
  const end = new Date(holiday.end_date);
  const diffTime = Math.abs(end - start);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
  return diffDays;
};

onMounted(() => {
  loadHolidays();
});
</script>

