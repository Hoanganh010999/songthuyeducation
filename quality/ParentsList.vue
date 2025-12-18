<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">{{ t('quality.parents') }}</h1>
      <p class="text-gray-600 mt-1">{{ t('quality.parents_description') }}</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input v-model="filters.search" @input="loadParents" type="text" 
               :placeholder="t('common.search')"
               class="px-4 py-2 border border-gray-300 rounded-lg">

        <select v-model="filters.is_active" @change="loadParents" 
                class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">{{ t('common.all_status') }}</option>
            <option :value="1">{{ t('common.active') }}</option>
            <option :value="0">{{ t('common.inactive') }}</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th @click="handleSort('name')" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
              <div class="flex items-center gap-1">
                {{ t('common.name') }}
                <span v-if="sortColumn === 'name'" class="text-blue-600">
                  <svg v-if="sortDirection === 'asc'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                  <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </span>
                <svg v-else class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
              </div>
            </th>
            <th @click="handleSort('email')" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
              <div class="flex items-center gap-1">
                Liên hệ
                <span v-if="sortColumn === 'email'" class="text-blue-600">
                  <svg v-if="sortDirection === 'asc'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                  <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </span>
                <svg v-else class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
              </div>
            </th>
            <th @click="handleSort('google_email')" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
              <div class="flex items-center gap-1">
                Google Email
                <span v-if="sortColumn === 'google_email'" class="text-blue-600">
                  <svg v-if="sortDirection === 'asc'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                  <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </span>
                <svg v-else class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
              </div>
            </th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Con cái</th>
            <th @click="handleSort('is_active')" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
              <div class="flex items-center justify-center gap-1">
                {{ t('common.status') }}
                <span v-if="sortColumn === 'is_active'" class="text-blue-600">
                  <svg v-if="sortDirection === 'asc'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                  <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </span>
                <svg v-else class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="parent in parents" :key="parent.id" class="hover:bg-gray-50">
            <!-- Name -->
            <td class="px-3 py-3">
                <div class="text-sm font-medium text-gray-900">{{ parent.user?.name || 'N/A' }}</div>
            </td>

            <!-- Contact: Email + Phone -->
            <td class="px-3 py-3">
                <div class="text-sm text-gray-900">{{ parent.user?.email || 'N/A' }}</div>
                <div class="flex items-center space-x-1 mt-1">
                    <span class="text-xs text-gray-500">{{ parent.user?.phone || '-' }}</span>
                    <button
                        v-if="parent.user"
                        @click="openPhoneModal(parent.user, 'Parent')"
                        class="p-0.5 hover:bg-green-50 rounded transition"
                        title="Cập nhật SĐT"
                    >
                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </button>
                </div>
            </td>

            <!-- Google Email -->
            <td class="px-3 py-3">
                <div class="flex items-center space-x-1">
                    <div v-if="parent.user?.google_email" class="flex items-center space-x-1">
                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        <span class="text-xs text-gray-900">{{ parent.user.google_email }}</span>
                    </div>
                    <span v-else class="text-xs text-gray-400">-</span>
                    <button
                        v-if="parent.user"
                        @click="openGoogleEmailModal(parent.user, 'Parent')"
                        class="p-0.5 hover:bg-blue-50 rounded transition"
                        title="Cập nhật Google Email"
                    >
                        <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                    </button>
                </div>
            </td>

            <!-- Children -->
            <td class="px-3 py-3">
                <div v-if="parent.students && parent.students.length > 0" class="space-y-2">
                    <div v-for="(student, index) in parent.students" :key="student.id" :class="index > 0 ? 'pt-2 border-t border-gray-100' : ''">
                        <div class="flex items-center space-x-1">
                            <svg class="w-3 h-3 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                            </svg>
                            <span class="text-xs font-medium text-gray-900">{{ student.user?.name || 'N/A' }}</span>
                        </div>
                        <!-- Classes -->
                        <div v-if="student.classes && student.classes.length > 0" class="ml-4 mt-1 flex flex-wrap gap-1">
                            <span v-for="cls in student.classes" :key="cls.id" class="inline-flex items-center px-1.5 py-0.5 text-xs rounded bg-blue-100 text-blue-800">
                                {{ cls.name }}
                            </span>
                        </div>
                        <div v-else class="ml-4 mt-1 text-xs text-orange-600">Chưa có lớp</div>
                        <!-- Balance -->
                        <div class="ml-4 mt-1 text-xs font-semibold">
                            <span v-if="student.effective_balance !== undefined" :class="student.effective_balance > 0 ? 'text-green-600' : 'text-gray-500'">
                                💰 {{ formatCurrency(student.effective_balance) }}
                            </span>
                            <span v-else-if="student.wallet" :class="student.wallet.balance > 0 ? 'text-green-600' : 'text-gray-500'">
                                💰 {{ formatCurrency(student.wallet.balance) }}
                            </span>
                            <span v-else class="text-gray-400">💰 -</span>
                        </div>
                    </div>
                </div>
                <div v-else class="flex items-center gap-1 text-red-600">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs font-medium">Chưa có con</span>
                </div>
            </td>

            <!-- Status -->
            <td class="px-3 py-3 text-center">
                <button
                    @click="toggleParentStatus(parent)"
                    :class="parent.is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'"
                    class="px-1.5 py-0.5 text-xs rounded-full transition-colors cursor-pointer"
                    :title="parent.is_active ? 'Click để chuyển sang Inactive' : 'Click để chuyển sang Active'"
                >
                    {{ parent.is_active ? t('common.active') : t('common.inactive') }}
                </button>
            </td>
          </tr>
          <tr v-if="parents.length === 0">
            <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                {{ t('common.no_data') }}
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="px-6 py-4 border-t">
          <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700">
                  {{ t('common.showing') }} {{ pagination.from }} - {{ pagination.to }} {{ t('common.of') }} {{ pagination.total }}
              </div>
              <div class="flex gap-2">
                  <button v-for="page in visiblePages" :key="page" @click="loadParents(page)"
                          :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                          class="px-3 py-1 border rounded hover:bg-blue-50">
                      {{ page }}
                  </button>
              </div>
          </div>
      </div>
    </div>

    <!-- Google Email Update Modal -->
    <UserGoogleEmailModal
      :show="showGoogleEmailModal"
      :user="selectedUser"
      :user-type="selectedUserType"
      @close="closeGoogleEmailModal"
      @updated="handleUpdated"
    />

    <!-- Phone Update Modal -->
    <UserPhoneUpdateModal
      :show="showPhoneModal"
      :user="selectedUser"
      :user-type="selectedUserType"
      @close="closePhoneModal"
      @updated="handleUpdated"
      @show-zalo-profile="handleShowZaloProfile"
    />

    <!-- Zalo User Profile Modal -->
    <ZaloUserProfileModal
      :show="showZaloProfileModal"
      :zalo-user="zaloUserData"
      @close="closeZaloProfileModal"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import api from '../../services/api';
import Swal from 'sweetalert2';
import UserGoogleEmailModal from '../../components/quality/UserGoogleEmailModal.vue';
import UserPhoneUpdateModal from '../../components/quality/UserPhoneUpdateModal.vue';
import ZaloUserProfileModal from '../../components/quality/ZaloUserProfileModal.vue';

const { t } = useI18n();

const parents = ref([]);
const filters = ref({
    search: '',
    is_active: '',
});
const sortColumn = ref('name');
const sortDirection = ref('asc');

// Sort handler
const handleSort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
    loadParents(1);
};

const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: 15,
});

// Modal state
const showGoogleEmailModal = ref(false);
const showPhoneModal = ref(false);
const showZaloProfileModal = ref(false);
const selectedUser = ref(null);
const selectedUserType = ref('Parent');
const zaloUserData = ref(null);

const visiblePages = computed(() => {
    const pages = [];
    const maxVisible = 5;
    let startPage = Math.max(1, pagination.value.current_page - Math.floor(maxVisible / 2));
    let endPage = Math.min(pagination.value.last_page, startPage + maxVisible - 1);

    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }
    return pages;
});

onMounted(() => {
    loadParents();
});

const loadParents = async (page = 1) => {
    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
            search: filters.value.search,
            status: filters.value.is_active ? 'active' : (filters.value.is_active === false ? 'inactive' : ''),
            sort_by: sortColumn.value,
            sort_dir: sortDirection.value,
        };

        const res = await api.get('/api/quality/parents', { params });
        const responseData = res.data.data;
        parents.value = responseData.data;
        pagination.value = {
            current_page: responseData.current_page,
            last_page: responseData.last_page,
            per_page: responseData.per_page,
            total: responseData.total,
            from: responseData.from,
            to: responseData.to,
        };
    } catch (error) {
        console.error('Error loading parents:', error);
    }
};


// Modal functions
const openGoogleEmailModal = (user, userType) => {
    selectedUser.value = user;
    selectedUserType.value = userType;
    showGoogleEmailModal.value = true;
};

const closeGoogleEmailModal = () => {
    showGoogleEmailModal.value = false;
    selectedUser.value = null;
};

const openPhoneModal = (user, userType) => {
    selectedUser.value = user;
    selectedUserType.value = userType;
    showPhoneModal.value = true;
};

const closePhoneModal = () => {
    showPhoneModal.value = false;
    selectedUser.value = null;
};

const handleShowZaloProfile = (zaloUser) => {
    zaloUserData.value = zaloUser;
    showZaloProfileModal.value = true;
};

const closeZaloProfileModal = () => {
    showZaloProfileModal.value = false;
    zaloUserData.value = null;
};

const handleUpdated = (updatedUser) => {
    // Refresh the parent list to show updated info
    loadParents(pagination.value.current_page);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};

const toggleParentStatus = async (parent) => {
    try {
        const newStatus = !parent.is_active;
        const statusText = newStatus ? t('common.active') : t('common.inactive');
        
        const result = await Swal.fire({
            title: 'Xác nhận thay đổi trạng thái',
            html: `
                <div class="text-left">
                    <p class="text-sm text-gray-600 mb-2">Phụ huynh: <strong>${parent.user?.name}</strong></p>
                    <p class="text-sm text-gray-600">Trạng thái mới: <strong>${statusText}</strong></p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Xác nhận',
            cancelButtonText: t('common.cancel'),
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        });
        
        if (result.isConfirmed) {
            await api.patch(`/api/quality/parents/${parent.id}/status`, {
                is_active: newStatus
            });
            
            await Swal.fire({
                icon: 'success',
                title: t('common.success'),
                text: 'Trạng thái phụ huynh đã được cập nhật',
                timer: 1500,
                showConfirmButton: false
            });
            
            // Refresh parent list
            loadParents(pagination.value.current_page);
        }
    } catch (error) {
        console.error('Error toggling parent status:', error);
        await Swal.fire({
            icon: 'error',
            title: t('common.error'),
            text: error.response?.data?.message || 'Không thể cập nhật trạng thái phụ huynh'
        });
    }
};
</script>

