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
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0">
        <div class="flex-1 max-w-md">
          <input
            v-model="search"
            @input="handleSearch"
            type="text"
            :placeholder="t('common.search')"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-600">{{ t('common.showing') }}:</span>
          <select
            v-model="perPage"
            @change="loadUsers"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
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

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              User
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Email
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Google Email
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Roles
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Ngày tạo
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
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
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
              <div class="text-sm text-gray-500">{{ user.phone || '-' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div v-if="user.google_email" class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
                <span class="text-sm text-gray-900">{{ user.google_email }}</span>
              </div>
              <div v-else class="flex items-center space-x-2 text-gray-400">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
                <span class="text-sm">Chưa có</span>
              </div>
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
                <span v-if="user.roles.length === 0" class="text-sm text-gray-400">
                  Chưa có role
                </span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(user.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end space-x-2">
                <!-- Google Email Button -->
                <button
                  v-if="authStore.hasPermission('users.edit')"
                  @click.stop.prevent="openGoogleEmailModal(user)"
                  type="button"
                  :class="[
                    'transition',
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
                  class="text-blue-600 hover:text-blue-900 transition"
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
                  class="text-yellow-600 hover:text-yellow-900 transition"
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
                  class="text-red-600 hover:text-red-900 transition"
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

      <!-- Pagination -->
      <div v-if="pagination.total > perPage" class="bg-gray-50 px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Hiển thị <span class="font-medium">{{ pagination.from }}</span> đến 
            <span class="font-medium">{{ pagination.to }}</span> trong tổng số 
            <span class="font-medium">{{ pagination.total }}</span> users
          </div>
          <div class="flex space-x-2">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              Trước
            </button>
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="changePage(page)"
              :class="[
                'px-3 py-1 border rounded-md text-sm font-medium transition',
                page === pagination.current_page
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'border-gray-300 text-gray-700 hover:bg-gray-100'
              ]"
            >
              {{ page }}
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              Sau
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

const authStore = useAuthStore();
const { t } = useI18n();
const swal = useSwal();

const users = ref([]);
const loading = ref(false);
const search = ref('');
const perPage = ref(15);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedUser = ref(null);

const showResetPasswordModal = ref(false);
const selectedUserForReset = ref(null);

const showGoogleEmailModal = ref(false);
const selectedUserForGoogleEmail = ref(null);

const visiblePages = computed(() => {
  const pages = [];
  const current = pagination.value.current_page;
  const last = pagination.value.last_page;
  
  let start = Math.max(1, current - 2);
  let end = Math.min(last, current + 2);
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  return pages;
});

const loadUsers = async (page = 1) => {
  loading.value = true;
  try {
    const response = await usersApi.getAll({
      page,
      per_page: perPage.value,
      search: search.value
    });
    
    if (response.data.success) {
      users.value = response.data.data.data;
      pagination.value = {
        current_page: response.data.data.current_page,
        last_page: response.data.data.last_page,
        from: response.data.data.from,
        to: response.data.data.to,
        total: response.data.data.total
      };
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

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadUsers(page);
  }
};

const getUserInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN');
};

const editUser = (user) => {
  console.log('✏️ editUser called with user:', user);
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
  console.log('🔑 openResetPasswordModal called with user:', user);
  console.log('📋 Before - showResetPasswordModal:', showResetPasswordModal.value);
  selectedUserForReset.value = user;
  showResetPasswordModal.value = true;
  console.log('✅ After - showResetPasswordModal:', showResetPasswordModal.value);
  console.log('👤 selectedUserForReset:', selectedUserForReset.value);
};

const closeResetPasswordModal = () => {
  showResetPasswordModal.value = false;
  selectedUserForReset.value = null;
};

const handleResetSuccess = () => {
  loadUsers(pagination.value.current_page);
};

const openGoogleEmailModal = (user) => {
  console.log('📧 openGoogleEmailModal called with user:', user);
  selectedUserForGoogleEmail.value = user;
  showGoogleEmailModal.value = true;
};

const closeGoogleEmailModal = () => {
  showGoogleEmailModal.value = false;
  selectedUserForGoogleEmail.value = null;
};

const handleGoogleEmailUpdated = (updatedData) => {
  // Update user in the list
  const index = users.value.findIndex(u => u.id === updatedData.user_id);
  if (index !== -1) {
    users.value[index].google_email = updatedData.google_email;
    users.value[index].google_drive_folder_id = updatedData.folder_id;
  }
  // Alternatively, reload the entire list
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

