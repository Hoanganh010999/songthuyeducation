<template>
  <div>
    <div class="flex justify-between mb-4">
      <h3 class="text-lg font-semibold">{{ t('study_periods.title') }}</h3>
      <button v-if="authStore.hasPermission('classes.manage_settings')" @click="openModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg">{{ t('study_periods.create') }}</button>
    </div>
    <div v-if="loading" class="text-center py-8"><div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div></div>
    <table v-else class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên ca học</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời lượng ca (phút)</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời lượng tiết (phút)</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nghỉ giữa tiết (phút)</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="period in periods" :key="period.id" class="hover:bg-gray-50">
          <td class="px-6 py-4 font-medium">{{ period.name }}</td>
          <td class="px-6 py-4">{{ period.duration_minutes }} phút</td>
          <td class="px-6 py-4">{{ period.lesson_duration }} phút</td>
          <td class="px-6 py-4">{{ period.break_duration }} phút</td>
          <td class="px-6 py-4 text-right">
            <button @click="openModal(period)" class="text-blue-600 mr-3">Sửa</button>
            <button @click="deletePeriod(period)" class="text-red-600">Xóa</button>
          </td>
        </tr>
      </tbody>
    </table>
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold mb-4">{{ editing ? 'Sửa ca học' : 'Tạo ca học' }}</h3>
        <div class="space-y-4">
          <div><label class="block text-sm mb-1">Tên ca học</label><input v-model="form.name" type="text" class="w-full px-3 py-2 border rounded-lg" placeholder="VD: Ca sáng, Ca chiều" /></div>
          <div><label class="block text-sm mb-1">Mã</label><input v-model="form.code" type="text" class="w-full px-3 py-2 border rounded-lg" placeholder="VD: MORNING, AFTERNOON" /></div>
          <div><label class="block text-sm mb-1">Thời lượng ca học (phút)</label><input v-model.number="form.duration_minutes" type="number" class="w-full px-3 py-2 border rounded-lg" placeholder="VD: 240 (4 tiếng)" /></div>
          <div><label class="block text-sm mb-1">Thời lượng mỗi tiết (phút)</label><input v-model.number="form.lesson_duration" type="number" class="w-full px-3 py-2 border rounded-lg" placeholder="VD: 45" /></div>
          <div><label class="block text-sm mb-1">Thời gian nghỉ giữa các tiết (phút)</label><input v-model.number="form.break_duration" type="number" class="w-full px-3 py-2 border rounded-lg" placeholder="VD: 10" /></div>
          <div class="p-3 bg-blue-50 rounded text-sm text-blue-800">
            <strong>Lưu ý:</strong> Khi tạo lớp học, bạn chỉ cần chọn ca học và giờ bắt đầu, hệ thống sẽ tự động tính giờ kết thúc dựa trên thời lượng ca.
          </div>
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
const periods = ref([]);
const showModal = ref(false);
const editing = ref(null);
const form = ref({ name: '', code: '', duration_minutes: 240, lesson_duration: 45, break_duration: 10 });

const load = async () => {
  loading.value = true;
  const branchId = localStorage.getItem('current_branch_id');
  const res = await axios.get('/api/class-settings/study-periods', { params: { branch_id: branchId } });
  periods.value = res.data.data;
  loading.value = false;
};

const openModal = (item = null) => {
  editing.value = item;
  form.value = item ? { ...item } : { name: '', code: '', duration_minutes: 240, lesson_duration: 45, break_duration: 10 };
  showModal.value = true;
};

const save = async () => {
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const data = { ...form.value, branch_id: branchId };
    if (editing.value) await axios.put(`/api/class-settings/study-periods/${editing.value.id}`, data);
    else await axios.post('/api/class-settings/study-periods', data);
    await Swal.fire({ icon: 'success', timer: 1500 });
    showModal.value = false;
    load();
  } catch (error) {
    Swal.fire({ icon: 'error', text: error.response?.data?.message });
  }
};

const deletePeriod = async (item) => {
  if ((await Swal.fire({ title: 'Xóa?', showCancelButton: true })).isConfirmed) {
    await axios.delete(`/api/class-settings/study-periods/${item.id}`);
    Swal.fire({ icon: 'success', timer: 1500 });
    load();
  }
};

onMounted(load);
</script>

