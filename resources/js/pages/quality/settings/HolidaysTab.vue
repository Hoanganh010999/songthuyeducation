<template>
  <div>
    <div class="flex justify-between mb-4">
      <h3 class="text-lg font-semibold">{{ t('holidays.title') }}</h3>
      <button v-if="authStore.hasPermission('classes.manage_settings')" @click="openModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Tạo lịch nghỉ</button>
    </div>
    <div v-if="loading" class="text-center py-8"><div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div></div>
    <table v-else class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên ngày nghỉ</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày bắt đầu</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày kết thúc</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số ngày</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="holiday in holidays" :key="holiday.id" class="hover:bg-gray-50">
          <td class="px-6 py-4 font-medium">{{ holiday.name }}</td>
          <td class="px-6 py-4">{{ formatDate(holiday.start_date) }}</td>
          <td class="px-6 py-4">{{ formatDate(holiday.end_date) }}</td>
          <td class="px-6 py-4">{{ holiday.total_days }} ngày</td>
          <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ holidayTypes[holiday.type] }}</span></td>
          <td class="px-6 py-4 text-right">
            <button @click="openModal(holiday)" class="text-blue-600 mr-3">Sửa</button>
            <button @click="deleteHoliday(holiday)" class="text-red-600">Xóa</button>
          </td>
        </tr>
      </tbody>
    </table>
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold mb-4">{{ editing ? 'Sửa lịch nghỉ' : 'Tạo lịch nghỉ' }}</h3>
        <div class="space-y-3">
          <div><label class="block text-sm mb-1">Tên ngày nghỉ</label><input v-model="form.name" class="w-full px-3 py-2 border rounded-lg" /></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-sm mb-1">Ngày bắt đầu</label><input v-model="form.start_date" type="date" class="w-full px-3 py-2 border rounded-lg" /></div>
            <div><label class="block text-sm mb-1">Ngày kết thúc</label><input v-model="form.end_date" type="date" class="w-full px-3 py-2 border rounded-lg" /></div>
          </div>
          <div><label class="block text-sm mb-1">Loại</label><select v-model="form.type" class="w-full px-3 py-2 border rounded-lg"><option v-for="(label, value) in holidayTypes" :key="value" :value="value">{{ label }}</option></select></div>
        </div>
        <div class="flex justify-end space-x-3 mt-6">
          <button @click="showModal = false" class="px-4 py-2 border rounded-lg">Hủy</button>
          <button @click="save" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Lưu</button>
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
const holidays = ref([]);
const showModal = ref(false);
const editing = ref(null);
const form = ref({ name: '', start_date: '', end_date: '', type: 'national', affects_schedule: true });
const holidayTypes = { national: 'Ngày lễ quốc gia', school: 'Nghỉ trường', semester_break: 'Nghỉ học kỳ', other: 'Khác' };

const load = async () => {
  loading.value = true;
  const branchId = localStorage.getItem('current_branch_id');
  const res = await axios.get('/api/class-settings/holidays', { params: { branch_id: branchId } });
  holidays.value = res.data.data;
  loading.value = false;
};

const openModal = (item = null) => {
  editing.value = item;
  form.value = item ? { ...item } : { name: '', start_date: '', end_date: '', type: 'national', affects_schedule: true };
  showModal.value = true;
};

const save = async () => {
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const data = { ...form.value, branch_id: branchId };
    if (editing.value) await axios.put(`/api/class-settings/holidays/${editing.value.id}`, data);
    else await axios.post('/api/class-settings/holidays', data);
    Swal.fire({ icon: 'success', timer: 1500 });
    showModal.value = false;
    load();
  } catch (error) {
    Swal.fire({ icon: 'error', text: error.response?.data?.message });
  }
};

const deleteHoliday = async (item) => {
  if ((await Swal.fire({ title: 'Xóa?', showCancelButton: true })).isConfirmed) {
    await axios.delete(`/api/class-settings/holidays/${item.id}`);
    Swal.fire({ icon: 'success', timer: 1500 });
    load();
  }
};

const formatDate = (date) => date ? new Date(date).toLocaleDateString('vi-VN') : '-';

onMounted(load);
</script>

