<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">{{ t('semesters.title') }}</h3>
      <button
        v-if="authStore.hasPermission('classes.manage_settings')"
        @click="openModal()"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        {{ t('semesters.create') }}
      </button>
    </div>

    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
    </div>

    <table v-else class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('semesters.name') }}</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Năm học</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày bắt đầu</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày kết thúc</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('semesters.total_weeks') }}</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('common.actions') }}</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="semester in semesters" :key="semester.id" class="hover:bg-gray-50">
          <td class="px-6 py-4 font-medium">{{ semester.name }}</td>
          <td class="px-6 py-4 text-gray-600">{{ semester.academic_year?.name }}</td>
          <td class="px-6 py-4">{{ formatDate(semester.start_date) }}</td>
          <td class="px-6 py-4">{{ formatDate(semester.end_date) }}</td>
          <td class="px-6 py-4">{{ semester.total_weeks }} tuần</td>
          <td class="px-6 py-4 text-right">
            <button @click="openModal(semester)" class="text-blue-600 hover:text-blue-900 mr-3">Sửa</button>
            <button @click="deleteSemester(semester)" class="text-red-600 hover:text-red-900">Xóa</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold mb-4">{{ editingSemester ? 'Sửa học kỳ' : 'Tạo học kỳ' }}</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Năm học</label>
            <select v-model="form.academic_year_id" class="w-full px-3 py-2 border rounded-lg">
              <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Tên học kỳ</label>
            <input v-model="form.name" type="text" class="w-full px-3 py-2 border rounded-lg" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Mã</label>
            <input v-model="form.code" type="text" class="w-full px-3 py-2 border rounded-lg" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Ngày bắt đầu</label>
              <input v-model="form.start_date" type="date" class="w-full px-3 py-2 border rounded-lg" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Ngày kết thúc</label>
              <input v-model="form.end_date" type="date" class="w-full px-3 py-2 border rounded-lg" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Số tuần</label>
            <input v-model.number="form.total_weeks" type="number" class="w-full px-3 py-2 border rounded-lg" />
          </div>
        </div>
        <div class="flex justify-end space-x-3 mt-6">
          <button @click="showModal = false" class="px-4 py-2 border rounded-lg">Hủy</button>
          <button @click="saveSemester" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Lưu</button>
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
const semesters = ref([]);
const academicYears = ref([]);
const showModal = ref(false);
const editingSemester = ref(null);
const form = ref({ academic_year_id: null, name: '', code: '', start_date: '', end_date: '', total_weeks: 18 });

const loadSemesters = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/class-settings/semesters');
    semesters.value = response.data.data;
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const loadAcademicYears = async () => {
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.get('/api/class-settings/academic-years', { params: { branch_id: branchId } });
    academicYears.value = response.data.data;
  } catch (error) {
    console.error(error);
  }
};

const openModal = (semester = null) => {
  editingSemester.value = semester;
  form.value = semester ? { ...semester } : { academic_year_id: null, name: '', code: '', start_date: '', end_date: '', total_weeks: 18 };
  showModal.value = true;
};

const saveSemester = async () => {
  try {
    if (editingSemester.value) {
      await axios.put(`/api/class-settings/semesters/${editingSemester.value.id}`, form.value);
    } else {
      await axios.post('/api/class-settings/semesters', form.value);
    }
    await Swal.fire({ icon: 'success', title: 'Thành công!', timer: 1500 });
    showModal.value = false;
    loadSemesters();
  } catch (error) {
    Swal.fire({ icon: 'error', title: 'Lỗi!', text: error.response?.data?.message });
  }
};

const deleteSemester = async (semester) => {
  const result = await Swal.fire({ title: 'Xác nhận xóa?', icon: 'warning', showCancelButton: true });
  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/class-settings/semesters/${semester.id}`);
      await Swal.fire({ icon: 'success', title: 'Đã xóa!', timer: 1500 });
      loadSemesters();
    } catch (error) {
      Swal.fire({ icon: 'error', title: 'Lỗi!', text: error.response?.data?.message });
    }
  }
};

const formatDate = (date) => date ? new Date(date).toLocaleDateString('vi-VN') : '-';

onMounted(() => {
  loadSemesters();
  loadAcademicYears();
});
</script>

