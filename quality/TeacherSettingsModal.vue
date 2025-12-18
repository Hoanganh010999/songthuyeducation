<template>
  <div>
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
      <div>
        <h3 class="text-xl font-bold text-gray-900">{{ t('teachers.settings_title') }}</h3>
        <p class="text-sm text-gray-600 mt-1">
          Chọn các phòng ban để lọc giáo viên
        </p>
      </div>
      <button
        @click="$emit('close')"
        class="text-gray-400 hover:text-gray-600 transition"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Modal Content -->
    <div class="p-6">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600 mt-2">{{ t('teachers.loading') }}</p>
      </div>

      <!-- Department Selection -->
      <div v-else>
        <!-- Info Box -->
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800">
              <p class="font-medium mb-1">Hướng dẫn:</p>
              <ul class="list-disc list-inside space-y-1 text-blue-700">
                <li>Chọn các phòng ban có giáo viên</li>
                <li>Tất cả nhân viên thuộc phòng ban đã chọn sẽ được lấy ra</li>
                <li>Có thể chọn nhiều phòng ban cùng lúc</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Departments Grid -->
        <div v-if="departments.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
          <div
            v-for="department in departments"
            :key="department.id"
            @click="toggleDepartment(department.id)"
            class="relative p-4 border rounded-lg cursor-pointer transition-all hover:shadow-md"
            :class="{
              'border-blue-500 bg-blue-50': selectedDepartmentIds.includes(department.id),
              'border-gray-300 bg-white hover:border-gray-400': !selectedDepartmentIds.includes(department.id)
            }"
          >
            <!-- Checkbox -->
            <div class="flex items-start space-x-3">
              <div class="flex items-center h-5 mt-0.5">
                <input
                  type="checkbox"
                  :checked="selectedDepartmentIds.includes(department.id)"
                  @click.stop="toggleDepartment(department.id)"
                  class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-900">{{ department.name }}</div>
                <div v-if="department.code" class="flex items-center space-x-2 mt-1">
                  <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                    {{ department.code }}
                  </span>
                </div>
                <div v-if="department.description" class="text-xs text-gray-500 mt-1">
                  {{ department.description }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8 border border-gray-200 border-dashed rounded-lg">
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <p class="text-gray-600">Không có phòng ban nào</p>
          <p class="text-sm text-gray-500 mt-1">Vui lòng tạo phòng ban trước</p>
        </div>

        <!-- Selected Summary -->
        <div v-if="selectedDepartmentIds.length > 0" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-green-900">
                Đã chọn {{ selectedDepartmentIds.length }} phòng ban
              </p>
              <p class="text-xs text-green-700 mt-1">
                {{ getSelectedDepartmentNames() }}
              </p>
            </div>
            <button
              @click="selectedDepartmentIds = []"
              class="text-sm text-green-700 hover:text-green-900 underline"
            >
              Bỏ chọn tất cả
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end space-x-3">
      <button
        @click="$emit('close')"
        type="button"
        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
      >
        Hủy
      </button>
      <button
        @click="saveSettings"
        :disabled="selectedDepartmentIds.length === 0 || saving"
        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
      >
        <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>{{ saving ? 'Đang lưu...' : 'Lưu thiết lập' }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();
const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const saving = ref(false);
const departments = ref([]);
const selectedDepartmentIds = ref([]);

const loadDepartments = async () => {
  loading.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    if (!branchId) {
      throw new Error('Không xác định được chi nhánh');
    }

    const response = await axios.get('/api/quality/departments', {
      params: { branch_id: branchId }
    });
    departments.value = response.data.data || [];
  } catch (error) {
    console.error('Load departments error:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: error.response?.data?.message || 'Không thể tải danh sách phòng ban',
      confirmButtonText: 'OK'
    });
  } finally {
    loading.value = false;
  }
};

const loadCurrentSettings = async () => {
  const branchId = localStorage.getItem('current_branch_id');
  if (!branchId) return;

  try {
    const response = await axios.get('/api/quality/teachers/settings', {
      params: { branch_id: branchId }
    });

    selectedDepartmentIds.value = response.data.data.department_ids || [];
  } catch (error) {
    console.error('Load settings error:', error);
  }
};

const toggleDepartment = (departmentId) => {
  const index = selectedDepartmentIds.value.indexOf(departmentId);
  if (index > -1) {
    selectedDepartmentIds.value.splice(index, 1);
  } else {
    selectedDepartmentIds.value.push(departmentId);
  }
};

const getSelectedDepartmentNames = () => {
  return departments.value
    .filter(dept => selectedDepartmentIds.value.includes(dept.id))
    .map(dept => dept.name)
    .join(', ');
};

const saveSettings = async () => {
  if (selectedDepartmentIds.value.length === 0) {
    await Swal.fire({
      icon: 'warning',
      title: 'Chưa chọn phòng ban',
      text: 'Vui lòng chọn ít nhất một phòng ban',
      confirmButtonText: 'OK'
    });
    return;
  }

  const branchId = localStorage.getItem('current_branch_id');
  if (!branchId) {
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: 'Không xác định được chi nhánh hiện tại',
      confirmButtonText: 'OK'
    });
    return;
  }

  saving.value = true;
  try {
    await axios.post('/api/quality/teachers/settings', {
      branch_id: branchId,
      department_ids: selectedDepartmentIds.value
    });

    await Swal.fire({
      icon: 'success',
      title: 'Thành công!',
      text: 'Đã lưu thiết lập phòng ban giáo viên cho chi nhánh này',
      confirmButtonText: 'OK'
    });

    emit('saved');
  } catch (error) {
    console.error('Save settings error:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: error.response?.data?.message || 'Không thể lưu thiết lập',
      confirmButtonText: 'OK'
    });
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadDepartments();
  await loadCurrentSettings();
});
</script>
