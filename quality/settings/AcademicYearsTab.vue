<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">{{ t('academic_years.title') }}</h3>
      <button
        v-if="authStore.hasPermission('classes.manage_settings')"
        @click="openModal()"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        {{ t('academic_years.create') }}
      </button>
    </div>

    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
    </div>

    <div v-else>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('academic_years.name') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('academic_years.code') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('academic_years.start_date') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('academic_years.end_date') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="year in years" :key="year.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ year.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ year.code }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(year.start_date) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(year.end_date) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span v-if="year.is_current" class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                {{ t('academic_years.is_current') }}
              </span>
              <span v-else class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                {{ t('common.inactive') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
              <button @click="openModal(year)" class="text-blue-600 hover:text-blue-900 mr-3">{{ t('common.edit') }}</button>
              <button @click="deleteYear(year)" class="text-red-600 hover:text-red-900">{{ t('common.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold mb-4">{{ editingYear ? t('academic_years.edit') : t('academic_years.create') }}</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">{{ t('academic_years.name') }}</label>
            <input v-model="form.name" type="text" class="w-full px-3 py-2 border rounded-lg" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">{{ t('academic_years.code') }}</label>
            <input v-model="form.code" type="text" class="w-full px-3 py-2 border rounded-lg" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">{{ t('academic_years.start_date') }}</label>
              <input v-model="form.start_date" type="date" class="w-full px-3 py-2 border rounded-lg" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">{{ t('academic_years.end_date') }}</label>
              <input v-model="form.end_date" type="date" class="w-full px-3 py-2 border rounded-lg" />
            </div>
          </div>
          <div class="flex items-center">
            <input v-model="form.is_current" type="checkbox" class="mr-2" />
            <label class="text-sm">{{ t('academic_years.is_current') }}</label>
          </div>
        </div>
        <div class="flex justify-end space-x-3 mt-6">
          <button @click="showModal = false" class="px-4 py-2 border rounded-lg">{{ t('common.cancel') }}</button>
          <button @click="saveYear" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">{{ t('common.save') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useI18n } from '../../../composables/useI18n';
import { useAuthStore } from '../../../stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();
const loading = ref(false);
const years = ref([]);
const showModal = ref(false);
const editingYear = ref(null);
const form = ref({ name: '', code: '', start_date: '', end_date: '', is_current: false });

const loadYears = async () => {
  loading.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.get('/api/class-settings/academic-years', { params: { branch_id: branchId } });
    years.value = response.data.data;
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const openModal = (year = null) => {
  editingYear.value = year;
  if (year) {
    form.value = { ...year };
  } else {
    form.value = { name: '', code: '', start_date: '', end_date: '', is_current: false };
  }
  showModal.value = true;
};

const saveYear = async () => {
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const data = { ...form.value, branch_id: branchId };
    
    if (editingYear.value) {
      await axios.put(`/api/class-settings/academic-years/${editingYear.value.id}`, data);
    } else {
      await axios.post('/api/class-settings/academic-years', data);
    }
    
    await Swal.fire({ icon: 'success', title: 'Thành công!', timer: 1500 });
    showModal.value = false;
    loadYears();
  } catch (error) {
    Swal.fire({ icon: 'error', title: 'Lỗi!', text: error.response?.data?.message });
  }
};

const deleteYear = async (year) => {
  const result = await Swal.fire({
    title: 'Xác nhận xóa?',
    text: `Xóa năm học ${year.name}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF4444',
  });
  
  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/class-settings/academic-years/${year.id}`);
      await Swal.fire({ icon: 'success', title: 'Đã xóa!', timer: 1500 });
      loadYears();
    } catch (error) {
      Swal.fire({ icon: 'error', title: 'Lỗi!', text: error.response?.data?.message });
    }
  }
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('vi-VN');
};

onMounted(() => {
  loadYears();
});
</script>

