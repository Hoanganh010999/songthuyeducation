<template>
  <div>
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('users.title') }}</h1>
        <p class="text-gray-600 mt-1">{{ t('users.list') }}</p>
      </div>
      <button
        v-if="authStore.hasPermission('users.create')"
        @click="showCreateModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium flex items-center space-x-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>{{ t('users.create') }}</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.search') }}</label>
          <div class="relative">
            <input
              v-model="filters.search"
              @input="handleSearch"
              type="text"
              :placeholder="t('users.search_placeholder') || 'Tìm theo tên, email, SĐT...'"
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <!-- Role Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('users.role') || 'Role' }}</label>
          <select
            v-model="filters.roleId"
            @change="loadUsers(1)"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{{ t('common.all') || 'Tất cả' }}</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">
              {{ role.display_name || role.name }}
            </option>
          </select>
        </div>

        <!-- Per Page -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.showing') }}</label>
          <select
            v-model="perPage"
            @change="loadUsers(1)"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option :value="10">10 / trang</option>
            <option :value="25">25 / trang</option>
            <option :value="50">50 / trang</option>
            <option :value="100">100 / trang</option>
          </select>
        </div>
      </div>

      <!-- Active Filters & Clear -->
      <div v-if="hasActiveFilters" class="mt-3 flex items-center justify-between">
        <div class="flex flex-wrap gap-2">
          <span v-if="filters.search" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            Tìm: "{{ filters.search }}"
            <button @click="filters.search = ''; loadUsers(1)" class="ml-1 hover:text-blue-600">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </span>
          <span v-if="filters.roleId" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
            Role: {{ getRoleName(filters.roleId) }}
            <button @click="filters.roleId = ''; loadUsers(1)" class="ml-1 hover:text-purple-600">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </span>
        </div>
        <button @click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Xóa bộ lọc
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">Đang tải...</p>
      </div>

      <div v-else-if="users.length === 0" class="p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <p class="mt-2 text-gray-600">Không có users nào</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                @click="sortBy('name')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
              >
                <div class="flex items-center space-x-1">
                  <span>User</span>
                  <SortIcon :active="sort.field === 'name'" :direction="sort.direction" />
                </div>
              </th>
              <th
                @click="sortBy('email')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
              >
                <div class="flex items-center space-x-1">
                  <span>Email</span>
                  <SortIcon :active="sort.field === 'email'" :direction="sort.direction" />
                </div>
              </th>
              <th
                @click="sortBy('phone')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
              >
                <div class="flex items-center space-x-1">
                  <span>SĐT</span>
                  <SortIcon :active="sort.field === 'phone'" :direction="sort.direction" />
                </div>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Google Email
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Roles
              </th>
              <th
                @click="sortBy('created_at')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
              >
                <div class="flex items-center space-x-1">
                  <span>Ngày tạo</span>
                  <SortIcon :active="sort.field === 'created_at'" :direction="sort.direction" />
                </div>
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Thao tác
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-semibold text-sm">
                      {{ getUserInitials(user.name) }}
                    </span>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ user.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ user.phone || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="user.google_email" class="flex items-center space-x-2">
                  <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                  </svg>
                  <span class="text-sm text-gray-900 truncate max-w-[150px]" :title="user.google_email">{{ user.google_email }}</span>
                </div>
                <span v-else class="text-sm text-gray-400">-</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="role in user.roles"
                    :key="role.id"
                    class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800"
                  >
                    {{ role.display_name || role.name }}
                  </span>
                  <span v-if="!user.roles || user.roles.length === 0" class="text-sm text-gray-400">
                    -
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(user.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-1">
                  <!-- Google Email Button -->
                  <button
                    v-if="authStore.hasPermission('users.edit')"
                    @click.stop.prevent="openGoogleEmailModal(user)"
                    type="button"
                    :class="[
                      'transition p-1.5 rounded hover:bg-gray-100',
                      user.google_email
                        ? 'text-green-600 hover:text-green-900'
                        : 'text-gray-400 hover:text-gray-600'
                    ]"
                    :title="user.google_email ? t('users.update_google_email') : t('users.assign_google_email')"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                      <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                  </button>

                  <button
                    v-if="authStore.hasPermission('users.edit')"
                    @click.stop.prevent="editUser(user)"
                    type="button"
                    class="text-blue-600 hover:text-blue-900 transition p-1.5 rounded hover:bg-gray-100"
                    :title="t('common.edit')"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    v-if="authStore.hasPermission('users.edit')"
                    @click.stop.prevent="openResetPasswordModal(user)"
                    type="button"
                    class="text-yellow-600 hover:text-yellow-900 transition p-1.5 rounded hover:bg-gray-100"
                    :title="t('users.reset_password')"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                  </button>
                  <button
                    v-if="authStore.hasPermission('users.delete')"
                    @click.stop.prevent="confirmDelete(user)"
                    type="button"
                    class="text-red-600 hover:text-red-900 transition p-1.5 rounded hover:bg-gray-100"
                    :title="t('common.delete')"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="bg-gray-50 px-6 py-4 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="text-sm text-gray-700">
            Hiển thị <span class="font-medium">{{ pagination.from }}</span> đến
            <span class="font-medium">{{ pagination.to }}</span> trong tổng số
            <span class="font-medium">{{ pagination.total }}</span> users
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="changePage(1)"
              :disabled="pagination.current_page === 1"
              class="px-2 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition"
              title="Trang đầu"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
              </svg>
            </button>
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              Trước
            </button>
            <span class="px-3 py-1 text-sm text-gray-700">
              {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              Sau
            </button>
            <button
              @click="changePage(pagination.last_page)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-2 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition"
              title="Trang cuối"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- User Modal -->
    <UserModal
      :show="showCreateModal || showEditModal"
      :user="selectedUser"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- Reset Password Modal -->
    <ResetPasswordModal
      :show="showResetPasswordModal"
      :user="selectedUserForReset"
      @close="closeResetPasswordModal"
      @success="handleResetSuccess"
    />

    <!-- Google Email Modal -->
    <AssignGoogleEmailModal
      :show="showGoogleEmailModal"
      :user="selectedUserForGoogleEmail"
      @close="closeGoogleEmailModal"
      @updated="handleGoogleEmailUpdated"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import { usersApi } from '../../services/api';
import UserModal from '../../components/users/UserModal.vue';
import ResetPasswordModal from '../../components/users/ResetPasswordModal.vue';
import AssignGoogleEmailModal from '../../components/users/AssignGoogleEmailModal.vue';

// Sort Icon Component
const SortIcon = {
  props: {
    active: Boolean,
    direction: String
  },
  template: `
    <span class="inline-flex flex-col">
      <svg class="w-3 h-3 -mb-1" :class="[active && direction === 'asc' ? 'text-blue-600' : 'text-gray-300']" fill="currentColor" viewBox="0 0 20 20">
        <path d="M5 12l5-5 5 5H5z"/>
      </svg>
      <svg class="w-3 h-3" :class="[active && direction === 'desc' ? 'text-blue-600' : 'text-gray-300']" fill="currentColor" viewBox="0 0 20 20">
        <path d="M5 8l5 5 5-5H5z"/>
      </svg>
    </span>
  `
};

const authStore = useAuthStore();
const { t } = useI18n();
const swal = useSwal();

const users = ref([]);
const roles = ref([]);
const loading = ref(false);
const perPage = ref(15);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
});

const filters = ref({
  search: '',
  roleId: ''
});

const sort = ref({
  field: 'created_at',
  direction: 'desc'
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedUser = ref(null);

const showResetPasswordModal = ref(false);
const selectedUserForReset = ref(null);

const showGoogleEmailModal = ref(false);
const selectedUserForGoogleEmail = ref(null);

const hasActiveFilters = computed(() => {
  return filters.value.search || filters.value.roleId;
});

const loadUsers = async (page = 1) => {
  loading.value = true;
  try {
    const response = await usersApi.getAll({
      page,
      per_page: perPage.value,
      search: filters.value.search,
      role_id: filters.value.roleId,
      sort_by: sort.value.field,
      sort_dir: sort.value.direction
    });

    if (response.data.success) {
      users.value = response.data.data.data;
      pagination.value = {
        current_page: response.data.data.current_page,
        last_page: response.data.data.last_page,
        from: response.data.data.from || 0,
        to: response.data.data.to || 0,
        total: response.data.data.total
      };
      // Load roles from response if available
      if (response.data.roles) {
        roles.value = response.data.roles;
      }
    }
  } catch (error) {
    console.error('Load users error:', error);
  } finally {
    loading.value = false;
  }
};

let searchTimeout;
const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadUsers(1);
  }, 500);
};

const sortBy = (field) => {
  if (sort.value.field === field) {
    // Toggle direction
    sort.value.direction = sort.value.direction === 'asc' ? 'desc' : 'asc';
  } else {
    sort.value.field = field;
    sort.value.direction = 'asc';
  }
  loadUsers(1);
};

const clearFilters = () => {
  filters.value.search = '';
  filters.value.roleId = '';
  sort.value.field = 'created_at';
  sort.value.direction = 'desc';
  loadUsers(1);
};

const getRoleName = (roleId) => {
  const role = roles.value.find(r => r.id == roleId);
  return role ? (role.display_name || role.name) : '';
};

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadUsers(page);
  }
};

const getUserInitials = (name) => {
  if (!name) return '??';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('vi-VN');
};

const editUser = (user) => {
  selectedUser.value = user;
  showEditModal.value = true;
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedUser.value = null;
};

const handleSaved = () => {
  closeModal();
  loadUsers(pagination.value.current_page);
};

const openResetPasswordModal = (user) => {
  selectedUserForReset.value = user;
  showResetPasswordModal.value = true;
};

const closeResetPasswordModal = () => {
  showResetPasswordModal.value = false;
  selectedUserForReset.value = null;
};

const handleResetSuccess = () => {
  loadUsers(pagination.value.current_page);
};

const openGoogleEmailModal = (user) => {
  selectedUserForGoogleEmail.value = user;
  showGoogleEmailModal.value = true;
};

const closeGoogleEmailModal = () => {
  showGoogleEmailModal.value = false;
  selectedUserForGoogleEmail.value = null;
};

const handleGoogleEmailUpdated = (updatedData) => {
  const index = users.value.findIndex(u => u.id === updatedData.user_id);
  if (index !== -1) {
    users.value[index].google_email = updatedData.google_email;
    users.value[index].google_drive_folder_id = updatedData.folder_id;
  }
  loadUsers(pagination.value.current_page);
};

const confirmDelete = async (user) => {
  const result = await swal.confirmDelete(
    `Bạn có chắc chắn muốn xóa user "${user.name}"? Hành động này không thể hoàn tác.`
  );

  if (!result.isConfirmed) return;

  try {
    await usersApi.delete(user.id);
    swal.success('Xóa user thành công!');
    loadUsers(pagination.value.current_page);
  } catch (error) {
    console.error('Delete user error:', error);
    swal.error('Có lỗi xảy ra khi xóa user');
  }
};

onMounted(() => {
  loadUsers();
});
</script>
