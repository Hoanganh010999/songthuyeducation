<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-semibold text-gray-900">Job Title Settings</h2>
        <p class="text-sm text-gray-600 mt-1">Quản lý các chức danh công việc và quyền hạn</p>
      </div>
      <button
        v-if="authStore.hasPermission('hr.manage')"
        @click="openModal()"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tạo Job Title
      </button>
    </div>

    <!-- Job Titles List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roles (Quyền)</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading">
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Đang tải...</td>
          </tr>
          <tr v-else-if="positions.length === 0">
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Chưa có Job Title nào</td>
          </tr>
          <tr v-else v-for="position in positions" :key="position.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ position.name }}</div>
              <div class="text-sm text-gray-500">{{ position.description || '-' }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ position.code }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ position.level || 0 }}</td>
            <td class="px-6 py-4">
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="role in position.roles"
                  :key="role.id"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                  {{ role.name }}
                </span>
                <span v-if="!position.roles || position.roles.length === 0" class="text-sm text-gray-400">
                  Chưa gán role
                </span>
              </div>
            </td>
            <td class="px-6 py-4">
              <span
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="position.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              >
                {{ position.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
              <button
                @click="openModal(position)"
                class="text-blue-600 hover:text-blue-900"
              >
                Sửa
              </button>
              <button
                v-if="authStore.hasPermission('hr.manage')"
                @click="deletePosition(position)"
                class="text-red-600 hover:text-red-900"
              >
                Xóa
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="showModal = false"
    >
      <div class="relative top-20 mx-auto p-6 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            {{ editingPosition ? 'Chỉnh sửa Job Title' : 'Tạo Job Title mới' }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="savePosition" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tên Job Title *</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
              <input
                v-model="form.code"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
              <input
                v-model.number="form.level"
                type="number"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
              <input
                v-model.number="form.sort_order"
                type="number"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Roles (Quyền hạn thừa kế)
            </label>
            <div class="border border-gray-300 rounded-md p-3 max-h-48 overflow-y-auto space-y-2">
              <label
                v-for="role in availableRoles"
                :key="role.id"
                class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="role.id"
                  v-model="form.role_ids"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded"
                />
                <span class="ml-3">
                  <span class="text-sm font-medium text-gray-900">{{ role.name }}</span>
                  <span class="block text-xs text-gray-500">{{ role.description }}</span>
                </span>
              </label>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              Nhân viên có Job Title này sẽ tự động thừa kế các quyền từ Roles được chọn
            </p>
          </div>

          <div class="flex items-center">
            <input
              type="checkbox"
              v-model="form.is_active"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded"
            />
            <label class="ml-2 text-sm text-gray-700">Active</label>
          </div>

          <div class="flex justify-end space-x-2 pt-4">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
            >
              Hủy
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
            >
              {{ editingPosition ? 'Cập nhật' : 'Tạo mới' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';
import Swal from 'sweetalert2';

const authStore = useAuthStore();
const positions = ref([]);
const availableRoles = ref([]);
const loading = ref(false);
const showModal = ref(false);
const editingPosition = ref(null);

const form = ref({
  name: '',
  code: '',
  description: '',
  level: 0,
  sort_order: 0,
  is_active: true,
  role_ids: []
});

const loadPositions = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/hr/positions');
    positions.value = response.data;
  } catch (error) {
    console.error('Error loading positions:', error);
    Swal.fire('Lỗi!', 'Không thể tải danh sách Job Titles', 'error');
  } finally {
    loading.value = false;
  }
};

const loadRoles = async () => {
  try {
    const response = await axios.get('/api/roles');
    availableRoles.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error loading roles:', error);
  }
};

const openModal = (position = null) => {
  editingPosition.value = position;
  if (position) {
    form.value = {
      name: position.name,
      code: position.code,
      description: position.description || '',
      level: position.level || 0,
      sort_order: position.sort_order || 0,
      is_active: position.is_active,
      role_ids: position.roles ? position.roles.map(r => r.id) : []
    };
  } else {
    form.value = {
      name: '',
      code: '',
      description: '',
      level: 0,
      sort_order: 0,
      is_active: true,
      role_ids: []
    };
  }
  showModal.value = true;
};

const savePosition = async () => {
  try {
    // Get branch_id from localStorage
    const branchId = localStorage.getItem('current_branch_id');
    if (!branchId && !editingPosition.value) {
      Swal.fire('Lỗi!', 'Vui lòng chọn chi nhánh trước', 'error');
      return;
    }
    
    const payload = { ...form.value };
    if (!editingPosition.value) {
      payload.branch_id = parseInt(branchId);
    }
    
    if (editingPosition.value) {
      await axios.put(`/api/hr/positions/${editingPosition.value.id}`, payload);
      Swal.fire('Thành công!', 'Đã cập nhật Job Title', 'success');
    } else {
      await axios.post('/api/hr/positions', payload);
      Swal.fire('Thành công!', 'Đã tạo Job Title mới', 'success');
    }
    showModal.value = false;
    loadPositions();
  } catch (error) {
    console.error('Error saving position:', error);
    Swal.fire('Lỗi!', error.response?.data?.message || 'Có lỗi xảy ra', 'error');
  }
};

const deletePosition = async (position) => {
  const result = await Swal.fire({
    title: 'Xác nhận xóa?',
    text: `Bạn có chắc muốn xóa Job Title "${position.name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
    confirmButtonColor: '#dc2626'
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/hr/positions/${position.id}`);
      Swal.fire('Thành công!', 'Đã xóa Job Title', 'success');
      loadPositions();
    } catch (error) {
      console.error('Error deleting position:', error);
      Swal.fire('Lỗi!', error.response?.data?.message || 'Có lỗi xảy ra', 'error');
    }
  }
};

onMounted(() => {
  loadPositions();
  loadRoles();
});
</script>

