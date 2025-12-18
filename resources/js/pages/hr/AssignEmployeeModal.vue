<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[80vh] overflow-y-auto">
      <h3 class="text-xl font-bold mb-4">Gán nhân viên vào phòng ban</h3>
      
      <!-- Current Employees -->
      <div v-if="currentEmployees.length > 0" class="mb-6">
        <h4 class="font-semibold mb-2">Nhân viên hiện tại:</h4>
        <div class="space-y-2">
          <div
            v-for="emp in currentEmployees"
            :key="emp.id"
            class="p-3 bg-gray-50 rounded border"
          >
            <!-- View Mode -->
            <div v-if="editingEmployeeId !== emp.id" class="flex items-center justify-between">
              <div>
                <div class="font-medium">{{ emp.name }}</div>
                <div class="text-sm text-gray-500">{{ emp.email }}</div>
                <div class="text-xs text-gray-400 mt-1">
                  <span v-if="emp.is_head" class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Trưởng phòng</span>
                  <span v-if="emp.is_deputy" class="bg-green-100 text-green-800 px-2 py-0.5 rounded ml-1">Phó phòng</span>
                  <span v-if="!emp.is_head && !emp.is_deputy" class="text-gray-400">Nhân viên</span>
                </div>
              </div>
              <div class="flex gap-2">
                <button
                  @click="startEdit(emp)"
                  class="px-3 py-1 text-blue-600 hover:bg-blue-50 rounded"
                >
                  Sửa
                </button>
                <button
                  @click="removeEmployee(emp.id)"
                  class="px-3 py-1 text-red-600 hover:bg-red-50 rounded"
                >
                  Xóa
                </button>
              </div>
            </div>

            <!-- Edit Mode -->
            <div v-else>
              <div class="mb-3">
                <div class="font-medium">{{ emp.name }}</div>
                <div class="text-sm text-gray-500">{{ emp.email }}</div>
              </div>
              <div class="flex gap-4 mb-3">
                <label class="flex items-center">
                  <input v-model="editForm.is_head" type="checkbox" class="mr-2" />
                  Trưởng phòng
                </label>
                <label class="flex items-center">
                  <input v-model="editForm.is_deputy" type="checkbox" class="mr-2" />
                  Phó phòng
                </label>
              </div>
              <div class="flex gap-2">
                <button
                  @click="saveEdit"
                  class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  Lưu
                </button>
                <button
                  @click="cancelEdit"
                  class="px-3 py-1 border rounded hover:bg-gray-100"
                >
                  Hủy
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Add New Employee -->
      <div class="border-t pt-4">
        <h4 class="font-semibold mb-2">Thêm nhân viên mới:</h4>
        
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Chọn nhân viên</label>
          <select
            v-model="selectedEmployeeId"
            class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
          >
            <option value="">-- Chọn nhân viên --</option>
            <option
              v-for="emp in availableEmployees"
              :key="emp.id"
              :value="emp.id"
            >
              {{ emp.name }} ({{ emp.email }})
            </option>
          </select>
        </div>
        
        <div v-if="selectedEmployeeId" class="space-y-3">
          <div class="flex gap-4">
            <label class="flex items-center">
              <input v-model="assignForm.is_head" type="checkbox" class="mr-2" />
              Trưởng phòng
            </label>
            <label class="flex items-center">
              <input v-model="assignForm.is_deputy" type="checkbox" class="mr-2" />
              Phó phòng
            </label>
          </div>
        </div>
      </div>
      
      <div class="flex gap-2 mt-6">
        <button
          @click="$emit('close')"
          class="flex-1 px-4 py-2 border rounded hover:bg-gray-50"
        >
          Đóng
        </button>
        <button
          @click="assign"
          :disabled="!selectedEmployeeId"
          class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
        >
          Gán nhân viên
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
  departmentId: Number,
  positions: Array
});

const emit = defineEmits(['close', 'saved']);

const authStore = useAuthStore();

const employees = ref([]);
const currentEmployees = ref([]);
const selectedEmployeeId = ref('');
const assignForm = ref({
  is_head: false,
  is_deputy: false
});
const editingEmployeeId = ref(null);
const editForm = ref({
  is_head: false,
  is_deputy: false
});

const availableEmployees = computed(() => {
  const currentIds = currentEmployees.value.map(e => e.id);
  return employees.value.filter(e => !currentIds.includes(e.id));
});

const loadEmployees = async () => {
  try {
    const response = await axios.get('/api/users/branch-employees');
    employees.value = (response.data.data || []).filter(u => u && u.name);
  } catch (error) {
    console.error('Error loading employees:', error);
  }
};

const loadCurrentEmployees = async () => {
  try {
    const response = await axios.get(`/api/hr/departments/${props.departmentId}`);
    currentEmployees.value = response.data.data?.users || [];
  } catch (error) {
    console.error('Error loading current employees:', error);
  }
};

const assign = async () => {
  if (!selectedEmployeeId.value) return;
  
  const assignedUserId = selectedEmployeeId.value;
  
  try {
    const response = await axios.post(`/api/hr/departments/${props.departmentId}/assign`, {
      user_id: assignedUserId,
      is_head: assignForm.value.is_head,
      is_deputy: assignForm.value.is_deputy
      // position_id will use department's default_position_id from backend
    });
    
    // Reset form
    selectedEmployeeId.value = '';
    assignForm.value = { is_head: false, is_deputy: false };
    
    // Reload current employees
    await loadCurrentEmployees();
    
    emit('saved');
    
    // Check if assigned user is current logged in user
    if (authStore.user && authStore.user.id === assignedUserId) {
      // Refresh user data to get new permissions
      await authStore.fetchUser();
      
      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: 'Bạn đã được gán vào phòng ban. Quyền của bạn đã được cập nhật.',
        confirmButtonText: 'OK'
      });
    } else {
      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: response.data.message || 'Đã phân công nhân viên thành công',
        confirmButtonText: 'OK'
      });
    }
  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Có lỗi xảy ra';
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: errorMsg,
      confirmButtonText: 'OK'
    });
    console.error('Assign error:', error.response?.data);
  }
};

const removeEmployee = async (userId) => {
  const result = await Swal.fire({
    title: 'Xác nhận xóa?',
    text: 'Bạn có chắc chắn muốn xóa nhân viên khỏi phòng ban này?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
    confirmButtonColor: '#dc2626'
  });

  if (!result.isConfirmed) return;

  try {
    await axios.post(`/api/hr/departments/${props.departmentId}/remove`, {
      user_id: userId
    });

    await loadCurrentEmployees();
    emit('saved');

    // Check if removed user is current logged in user
    if (authStore.user && authStore.user.id === userId) {
      // Refresh user data to update permissions
      await authStore.fetchUser();

      await Swal.fire({
        icon: 'info',
        title: 'Thông báo',
        text: 'Bạn đã được gỡ khỏi phòng ban. Quyền của bạn đã được cập nhật.',
        confirmButtonText: 'OK'
      });
    } else {
      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: 'Đã gỡ nhân viên khỏi phòng ban',
        confirmButtonText: 'OK'
      });
    }
  } catch (error) {
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: error.response?.data?.message || 'Có lỗi xảy ra',
      confirmButtonText: 'OK'
    });
  }
};

const startEdit = (employee) => {
  editingEmployeeId.value = employee.id;
  editForm.value = {
    is_head: employee.is_head || false,
    is_deputy: employee.is_deputy || false
  };
};

const cancelEdit = () => {
  editingEmployeeId.value = null;
  editForm.value = {
    is_head: false,
    is_deputy: false
  };
};

const saveEdit = async () => {
  if (!editingEmployeeId.value) return;

  const userId = editingEmployeeId.value;

  try {
    // Use update-user endpoint for editing existing employees
    const response = await axios.post(`/api/hr/departments/${props.departmentId}/update-user`, {
      user_id: userId,
      is_head: editForm.value.is_head,
      is_deputy: editForm.value.is_deputy
    });

    await loadCurrentEmployees();
    emit('saved');

    // Reset edit mode
    editingEmployeeId.value = null;
    editForm.value = { is_head: false, is_deputy: false };

    // Check if edited user is current logged in user
    if (authStore.user && authStore.user.id === userId) {
      await authStore.fetchUser();

      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: 'Vai trò của bạn đã được cập nhật. Quyền của bạn đã được làm mới.',
        confirmButtonText: 'OK'
      });
    } else {
      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: response.data.message || 'Đã cập nhật vai trò nhân viên',
        confirmButtonText: 'OK'
      });
    }
  } catch (error) {
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: error.response?.data?.message || 'Có lỗi xảy ra',
      confirmButtonText: 'OK'
    });
    console.error('Update error:', error.response?.data);
  }
};

onMounted(() => {
  loadEmployees();
  loadCurrentEmployees();
});
</script>

