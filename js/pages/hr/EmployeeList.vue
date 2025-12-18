<template>
  <div class="space-y-4">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold text-gray-900">Danh sách nhân viên</h2>
      <button
        v-if="authStore.hasPermission('employees.invite')"
        @click="showInviteModal = true"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition"
      >
        Mời nhân viên
      </button>
    </div>

    <!-- Search and Filter -->
    <div class="flex space-x-4">
      <div class="flex-1">
        <input
          v-model="searchQuery"
          @input="handleSearch"
          type="text"
          placeholder="Tìm kiếm theo tên, email, số điện thoại..."
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <select
        v-model="filterDepartment"
        @change="loadEmployees"
        class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <option value="">Tất cả phòng ban</option>
        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
          {{ dept.name }}
        </option>
      </select>
    </div>

    <!-- Employee List -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Nhân viên
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Phòng ban
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Chức vụ
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Liên hệ
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Trạng thái
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Thao tác
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading">
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
              Đang tải...
            </td>
          </tr>
          <tr v-else-if="employees.length === 0">
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
              Không có nhân viên nào
            </td>
          </tr>
          <tr v-else v-for="employee in employees" :key="employee?.id || Math.random()" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                  <img
                    v-if="employee?.avatar"
                    :src="employee.avatar"
                    :alt="employee?.name || 'User'"
                    class="h-10 w-10 rounded-full object-cover"
                  />
                  <div
                    v-else
                    class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold"
                  >
                    {{ getInitials(employee?.name) }}
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ employee?.name || '-' }}</div>
                  <div class="text-sm text-gray-500">{{ employee?.employee_code || '-' }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                {{ (employee?.departments && Array.isArray(employee.departments)) ? employee.departments.map(d => d?.name).filter(Boolean).join(', ') : '-' }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                {{ (employee?.positions && Array.isArray(employee.positions)) ? employee.positions.map(p => p?.name).filter(Boolean).join(', ') : '-' }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ employee?.email || '-' }}</div>
              <div class="text-sm text-gray-500">{{ employee?.phone || '-' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="{
                  'bg-green-100 text-green-800': employee?.employment_status === 'active',
                  'bg-gray-100 text-gray-800': employee?.employment_status !== 'active'
                }"
              >
                {{ employee?.employment_status === 'active' ? 'Đang làm việc' : 'Nghỉ việc' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                @click="viewEmployee(employee)"
                class="text-blue-600 hover:text-blue-900 mr-3"
              >
                Chi tiết
              </button>
              <button
                v-if="authStore.hasPermission('employees.manage')"
                @click="editEmployee(employee)"
                class="text-indigo-600 hover:text-indigo-900"
              >
                Sửa
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Invite Modal -->
    <div
      v-if="showInviteModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="showInviteModal = false"
    >
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Mời nhân viên</h3>
          <button @click="showInviteModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <!-- Search by Phone -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Số điện thoại *
            </label>
            <input
              v-model="inviteForm.phone"
              @input="searchUserByPhone"
              type="text"
              placeholder="Nhập số điện thoại"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p class="text-xs text-gray-500 mt-1">Tìm kiếm user theo số điện thoại</p>
          </div>

          <!-- Found User Info -->
          <div v-if="inviteForm.foundUser" class="p-3 bg-green-50 border border-green-200 rounded-md">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">
                {{ getInitials(inviteForm.foundUser.name) }}
              </div>
              <div>
                <div class="font-medium">{{ inviteForm.foundUser.name }}</div>
                <div class="text-sm text-gray-600">{{ inviteForm.foundUser.email }}</div>
                <div class="text-sm text-gray-600">{{ inviteForm.foundUser.phone }}</div>
              </div>
            </div>
          </div>

          <!-- Invitation Message -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Nội dung thư mời *
            </label>
            <textarea
              v-model="inviteForm.message"
              rows="4"
              placeholder="Viết nội dung thư mời..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-2 pt-4">
            <button
              @click="showInviteModal = false"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition"
            >
              Hủy
            </button>
            <button
              @click="sendInvitation"
              :disabled="!canSendInvite"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Gửi lời mời
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';

const authStore = useAuthStore();
const employees = ref([]);
const departments = ref([]);
const positions = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const filterDepartment = ref('');
const showInviteModal = ref(false);
const searchResults = ref([]);
const inviteForm = ref({
  phone: '',
  foundUser: null,
  message: ''
});

const canSendInvite = computed(() => {
  return inviteForm.value.foundUser && inviteForm.value.message.trim().length > 0;
});

const loadEmployees = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/users/branch-employees', {
      params: {
        search: searchQuery.value,
        department_id: filterDepartment.value
      }
    });
    employees.value = (response.data.data || []).filter(e => e && e.name);
  } catch (error) {
    console.error('Error loading employees:', error);
  } finally {
    loading.value = false;
  }
};

const loadDepartments = async () => {
  try {
    const response = await axios.get('/api/hr/departments');
    departments.value = response.data;
  } catch (error) {
    console.error('Error loading departments:', error);
  }
};

const loadPositions = async () => {
  try {
    // TODO: Create positions endpoint
    positions.value = [
      { id: 1, name: 'Giám đốc' },
      { id: 2, name: 'Phó giám đốc' },
      { id: 3, name: 'Trưởng phòng' },
      { id: 4, name: 'Nhân viên' }
    ];
  } catch (error) {
    console.error('Error loading positions:', error);
  }
};

const handleSearch = () => {
  loadEmployees();
};

let searchTimeout = null;

const searchUserByPhone = async () => {
  clearTimeout(searchTimeout);
  
  if (inviteForm.value.phone.length < 9) {
    inviteForm.value.foundUser = null;
    return;
  }
  
  searchTimeout = setTimeout(async () => {
    try {
      const response = await axios.get('/api/users/search-by-phone', {
        params: { phone: inviteForm.value.phone }
      });
      inviteForm.value.foundUser = response.data.data;
    } catch (error) {
      inviteForm.value.foundUser = null;
      if (error.response?.status !== 404) {
        console.error('Error searching user:', error);
      }
    }
  }, 500);
};

const sendInvitation = async () => {
  if (!canSendInvite.value) return;
  
  // Get current branch from localStorage
  const branchId = localStorage.getItem('current_branch_id');
  if (!branchId) {
    alert('Vui lòng chọn chi nhánh trước khi gửi lời mời');
    return;
  }
  
  try {
    await axios.post('/api/hr/employee-invitations', {
      user_id: inviteForm.value.foundUser.id,
      message: inviteForm.value.message,
      branch_id: parseInt(branchId)
    });
    
    showInviteModal.value = false;
    inviteForm.value = {
      phone: '',
      foundUser: null,
      message: ''
    };
    
    alert('Đã gửi lời mời thành công! User sẽ nhận được thông báo.');
    
    // Reload employees
    loadEmployees();
  } catch (error) {
    console.error('Error sending invitation:', error);
    alert(error.response?.data?.message || 'Có lỗi xảy ra khi gửi lời mời');
  }
};

const getInitials = (name) => {
  if (!name) return '??';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const viewEmployee = (employee) => {
  // TODO: Implement view employee details
  console.log('View employee:', employee);
};

const editEmployee = (employee) => {
  // TODO: Implement edit employee
  console.log('Edit employee:', employee);
};

onMounted(() => {
  loadEmployees();
  loadDepartments();
  loadPositions();
});
</script>

