<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">{{ t('quality.parents') }}</h1>
      <p class="text-gray-600 mt-1">{{ t('quality.parents_description') }}</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadParents" type="text" 
               :placeholder="t('common.search')"
               class="px-4 py-2 border border-gray-300 rounded-lg">
        
        <select v-model="filters.branch_id" @change="loadParents" 
                class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">{{ t('common.all') }} {{ t('common.branch') }}</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
            </option>
        </select>

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
      <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.name') }}</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.email') }}</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.phone') }}</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Google Email</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.status') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="parent in parents" :key="parent.id" class="hover:bg-gray-50">
            <td class="px-3 py-2 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ parent.user?.name || 'N/A' }}</div>
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                {{ parent.user?.email || 'N/A' }}
            </td>
            <td class="px-3 py-2 whitespace-nowrap">
                <div class="flex items-center space-x-1">
                    <span class="text-xs text-gray-500">{{ parent.user?.phone || '-' }}</span>
                    <button
                        v-if="parent.user"
                        @click="openPhoneModal(parent.user, 'Parent')"
                        class="p-1 hover:bg-green-50 rounded transition"
                        :title="t('quality.update_phone') || 'Cập nhật số điện thoại'"
                    >
                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-3 py-2 whitespace-nowrap">
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
                        class="p-1 hover:bg-blue-50 rounded transition"
                        :title="t('quality.update_google_email') || 'Cập nhật Google Email'"
                    >
                        <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-3 py-2 whitespace-nowrap">
                <span v-if="parent.is_active" class="px-1.5 py-0.5 text-xs rounded-full bg-green-100 text-green-800">
                    {{ t('common.active') }}
                </span>
                <span v-else class="px-1.5 py-0.5 text-xs rounded-full bg-gray-100 text-gray-800">
                    {{ t('common.inactive') }}
                </span>
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
import UserGoogleEmailModal from '../../components/quality/UserGoogleEmailModal.vue';
import UserPhoneUpdateModal from '../../components/quality/UserPhoneUpdateModal.vue';
import ZaloUserProfileModal from '../../components/quality/ZaloUserProfileModal.vue';

const { t } = useI18n();

const parents = ref([]);
const branches = ref([]);
const filters = ref({
    search: '',
    branch_id: '',
    is_active: '',
});
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
    loadBranches();
});

const loadParents = async (page = 1) => {
    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
            search: filters.value.search,
            status: filters.value.is_active ? 'active' : (filters.value.is_active === false ? 'inactive' : ''),
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

const loadBranches = async () => {
    try {
        const res = await api.get('/api/branches/list');
        branches.value = res.data.data || res.data;
    } catch (error) {
        console.error('Error loading branches:', error);
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
</script>

