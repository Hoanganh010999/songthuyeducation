<template>
  <div>
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Quản Lý Chi Nhánh</h1>
        <p class="text-gray-600 mt-1">Quản lý thông tin các chi nhánh</p>
      </div>
      <button
        v-if="authStore.hasPermission('branches.create')"
        @click="showCreateModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium flex items-center space-x-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tạo Chi Nhánh</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            placeholder="Tìm kiếm theo tên, mã, thành phố..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <select
            v-model="filters.isActive"
            @change="loadBranches(1)"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Tất cả trạng thái</option>
            <option value="1">Hoạt động</option>
            <option value="0">Ngừng hoạt động</option>
          </select>
        </div>
        <div>
          <button
            @click="resetFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Đặt lại bộ lọc
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Branches Table -->
    <div v-else-if="branches.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã & Tên</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Địa chỉ</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quản lý</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nhân sự</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="branch in branches" :key="branch.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div>
                  <div class="flex items-center space-x-2">
                    <code class="text-sm font-mono font-medium text-gray-900 bg-gray-100 px-2 py-1 rounded">
                      {{ branch.code }}
                    </code>
                    <span
                      v-if="branch.is_headquarters"
                      class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full"
                    >
                      TRỤ SỞ CHÍNH
                    </span>
                  </div>
                  <div class="text-sm font-medium text-gray-900 mt-1">{{ branch.name }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900">{{ branch.city }}</div>
              <div class="text-sm text-gray-500">{{ branch.district }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div v-if="branch.manager" class="text-sm">
                <div class="font-medium text-gray-900">{{ branch.manager.name }}</div>
                <div class="text-gray-500">{{ branch.manager.email }}</div>
              </div>
              <span v-else class="text-sm text-gray-400">Chưa có</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 rounded-full">
                {{ branch.users_count }} người
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <span
                :class="[
                  'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                  branch.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]"
              >
                {{ branch.is_active ? 'Hoạt động' : 'Ngừng' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end space-x-2">
                <button
                  @click="viewBranch(branch)"
                  class="text-blue-600 hover:text-blue-900 transition"
                  title="Xem chi tiết"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button
                  v-if="authStore.hasPermission('branches.edit')"
                  @click="editBranch(branch)"
                  class="text-green-600 hover:text-green-900 transition"
                  title="Chỉnh sửa"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  v-if="authStore.hasPermission('branches.delete') && !branch.is_headquarters"
                  @click="deleteBranch(branch)"
                  class="text-red-600 hover:text-red-900 transition"
                  title="Xóa"
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
      <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
        <div class="text-sm text-gray-700">
          Hiển thị {{ pagination.from }} - {{ pagination.to }} trong tổng số {{ pagination.total }} chi nhánh
        </div>
        <div class="flex space-x-2">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="loadBranches(page)"
            :class="[
              'px-4 py-2 text-sm rounded-lg transition',
              page === pagination.current_page
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-100'
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Không có chi nhánh</h3>
      <p class="mt-1 text-sm text-gray-500">Bắt đầu bằng cách tạo chi nhánh mới</p>
    </div>

    <!-- Branch Modal (Create/Edit) -->
    <BranchModal
      :show="showCreateModal || showEditModal"
      :branch="selectedBranch"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- Branch Detail Modal -->
    <BranchDetailModal
      :show="showDetailModal"
      :branch="selectedBranch"
      @close="closeDetailModal"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';
import BranchModal from '../../components/branches/BranchModal.vue';
import BranchDetailModal from '../../components/branches/BranchDetailModal.vue';

const authStore = useAuthStore();
const swal = useSwal();

const branches = ref([]);
const loading = ref(false);
const filters = ref({
  search: '',
  isActive: '',
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDetailModal = ref(false);
const selectedBranch = ref(null);

const visiblePages = computed(() => {
  const pages = [];
  const current = pagination.value.current_page;
  const last = pagination.value.last_page;
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i);
  }
  
  return pages;
});

let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadBranches(1);
  }, 500);
};

const loadBranches = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: 15,
      search: filters.value.search || undefined,
      is_active: filters.value.isActive !== '' ? filters.value.isActive : undefined,
    };

    const response = await api.get('/api/branches', { params });

    if (response.data.success) {
      branches.value = response.data.data.data;
      pagination.value = {
        current_page: response.data.data.current_page,
        last_page: response.data.data.last_page,
        from: response.data.data.from,
        to: response.data.data.to,
        total: response.data.data.total,
      };
    }
  } catch (error) {
    console.error('Failed to load branches:', error);
    swal.error('Không thể tải danh sách chi nhánh');
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.value = {
    search: '',
    isActive: '',
  };
  loadBranches(1);
};

const viewBranch = (branch) => {
  selectedBranch.value = branch;
  showDetailModal.value = true;
};

const editBranch = (branch) => {
  selectedBranch.value = branch;
  showEditModal.value = true;
};

const deleteBranch = async (branch) => {
  const result = await swal.confirmDelete(
    `Bạn có chắc chắn muốn xóa chi nhánh "${branch.name}"?`
  );
  
  if (!result.isConfirmed) return;

  try {
    const response = await api.delete(`/api/branches/${branch.id}`);
    if (response.data.success) {
      swal.success(response.data.message);
      loadBranches(pagination.value.current_page);
    } else {
      swal.error(response.data.message || 'Không thể xóa chi nhánh');
    }
  } catch (error) {
    console.error('Delete error:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi xóa chi nhánh');
  }
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedBranch.value = null;
};

const closeDetailModal = () => {
  showDetailModal.value = false;
  selectedBranch.value = null;
};

const handleSaved = () => {
  closeModal();
  loadBranches(pagination.value.current_page);
};

onMounted(() => {
  loadBranches();
});
</script>

