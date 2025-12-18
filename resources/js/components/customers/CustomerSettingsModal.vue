<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
      @click.self="close"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between rounded-t-lg">
          <h2 class="text-xl font-bold text-gray-900">
            {{ t('customers.settings') }}
          </h2>
          <button @click="close" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Tabs -->
        <div class="border-b">
          <nav class="flex space-x-8 px-6" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm transition',
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
          <!-- Interaction Types Tab -->
          <div v-if="activeTab === 'interaction-types'">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-900">{{ t('customers.interaction_types') }}</h3>
              <button
                @click="openAddModal('interaction-type')"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ t('common.add') }}
              </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="item in interactionTypes"
                :key="item.id"
                class="border rounded-lg p-4 hover:shadow-md transition"
              >
                <div class="flex items-start justify-between">
                  <div class="flex items-center gap-3 flex-1">
                    <div
                      :style="{ backgroundColor: item.color || '#3B82F6' }"
                      class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                    >
                      <span class="text-lg">{{ getIconEmoji(item.icon) }}</span>
                    </div>
                    <div class="flex-1">
                      <h4 class="font-semibold text-gray-900">{{ item.name }}</h4>
                      <p class="text-sm text-gray-500">{{ item.code }}</p>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <button
                      @click="openEditModal('interaction-type', item)"
                      class="text-blue-600 hover:text-blue-800"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      @click="deleteItem('interaction-type', item)"
                      class="text-red-600 hover:text-red-800"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
                <p v-if="item.description" class="text-sm text-gray-600 mt-2">{{ item.description }}</p>
              </div>
            </div>
          </div>

          <!-- Interaction Results Tab -->
          <div v-if="activeTab === 'interaction-results'">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-900">{{ t('customers.interaction_results') }}</h3>
              <button
                @click="openAddModal('interaction-result')"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ t('common.add') }}
              </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="item in interactionResults"
                :key="item.id"
                class="border rounded-lg p-4 hover:shadow-md transition"
              >
                <div class="flex items-start justify-between">
                  <div class="flex items-center gap-3 flex-1">
                    <div
                      :style="{ backgroundColor: item.color || '#10B981' }"
                      class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                    >
                      <span class="text-lg">{{ getIconEmoji(item.icon) }}</span>
                    </div>
                    <div class="flex-1">
                      <h4 class="font-semibold text-gray-900">{{ item.name }}</h4>
                      <p class="text-sm text-gray-500">{{ item.code }}</p>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <button
                      @click="openEditModal('interaction-result', item)"
                      class="text-blue-600 hover:text-blue-800"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      @click="deleteItem('interaction-result', item)"
                      class="text-red-600 hover:text-red-800"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
                <p v-if="item.description" class="text-sm text-gray-600 mt-2">{{ item.description }}</p>
              </div>
            </div>
          </div>

          <!-- Customer Sources Tab -->
          <div v-if="activeTab === 'sources'">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-900">{{ t('customers.sources') }}</h3>
              <button
                @click="openAddModal('source')"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ t('common.add') }}
              </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="item in sources"
                :key="item.id"
                class="border rounded-lg p-4 hover:shadow-md transition"
              >
                <div class="flex items-start justify-between">
                  <div class="flex items-center gap-3 flex-1">
                    <div
                      :style="{ backgroundColor: item.color || '#F59E0B' }"
                      class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                    >
                      <span class="text-lg">{{ getIconEmoji(item.icon) }}</span>
                    </div>
                    <div class="flex-1">
                      <h4 class="font-semibold text-gray-900">{{ item.name }}</h4>
                      <p class="text-sm text-gray-500">{{ item.code }}</p>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <button
                      @click="openEditModal('source', item)"
                      class="text-blue-600 hover:text-blue-800"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      @click="deleteItem('source', item)"
                      class="text-red-600 hover:text-red-800"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
                <p v-if="item.description" class="text-sm text-gray-600 mt-2">{{ item.description }}</p>
              </div>
            </div>
          </div>

          <!-- Department Settings Tab -->
          <div v-if="activeTab === 'department'">
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Thiết lập phòng ban phụ trách</h3>
              <p class="text-sm text-gray-600">
                Chọn phòng ban chịu trách nhiệm quản lý module Khách hàng.
                Trưởng/Phó phòng sẽ thấy tất cả khách hàng, nhân viên chỉ thấy khách hàng được gán cho mình.
              </p>
            </div>

            <!-- Branch selector (for super admin) -->
            <div v-if="branches.length > 1" class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Chi nhánh</label>
              <select
                v-model="selectedBranchId"
                @change="loadDepartmentSettings"
                class="w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                  {{ branch.name }}
                </option>
              </select>
            </div>

            <!-- Department Selection -->
            <div class="bg-gray-50 rounded-lg p-6">
              <div class="flex items-start gap-4">
                <div class="flex-1">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Phòng ban phụ trách</label>
                  <select
                    v-model="selectedDepartmentId"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option :value="null">-- Chọn phòng ban --</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                      {{ dept.name }} {{ dept.code ? `(${dept.code})` : '' }}
                    </option>
                  </select>
                </div>
                <div class="pt-7">
                  <button
                    @click="saveDepartmentSettings"
                    :disabled="!selectedDepartmentId || savingDepartment"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {{ savingDepartment ? 'Đang lưu...' : 'Lưu thiết lập' }}
                  </button>
                </div>
              </div>

              <!-- Current Setting Display -->
              <div v-if="currentDepartmentSetting" class="mt-6 p-4 bg-white border border-green-200 rounded-lg">
                <div class="flex items-center gap-3">
                  <div
                    :style="{ backgroundColor: currentDepartmentSetting.department?.color || '#3B82F6' }"
                    class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium text-gray-900">
                      Phòng ban hiện tại: <span class="text-blue-600">{{ currentDepartmentSetting.department?.name }}</span>
                    </p>
                    <p class="text-sm text-gray-500">
                      Trưởng/Phó phòng này sẽ thấy tất cả khách hàng của chi nhánh
                    </p>
                  </div>
                  <button
                    @click="removeDepartmentSettings"
                    class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                  >
                    Xóa thiết lập
                  </button>
                </div>
              </div>

              <!-- No Setting Info -->
              <div v-else class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center gap-3">
                  <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <p class="text-yellow-700">
                    Chưa có phòng ban phụ trách. Tất cả người có quyền xem customers đều thấy khách hàng theo phân quyền mặc định.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t px-6 py-4 flex justify-end">
          <button
            @click="close"
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
          >
            {{ t('common.close') }}
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Add/Edit Item Modal -->
  <CustomerSettingItemModal
    :show="showItemModal"
    :type="currentType"
    :item="currentItem"
    :is-edit="isEditMode"
    @close="closeItemModal"
    @saved="handleItemSaved"
  />
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';
import CustomerSettingItemModal from './CustomerSettingItemModal.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

const { t } = useI18n();
const swal = useSwal();

const activeTab = ref('interaction-types');
const interactionTypes = ref([]);
const interactionResults = ref([]);
const sources = ref([]);

const showItemModal = ref(false);
const currentType = ref('');
const currentItem = ref(null);
const isEditMode = ref(false);

// Department settings state
const branches = ref([]);
const departments = ref([]);
const selectedBranchId = ref(null);
const selectedDepartmentId = ref(null);
const currentDepartmentSetting = ref(null);
const savingDepartment = ref(false);
const hasDepartmentPermission = ref(false);

const tabs = computed(() => {
  const baseTabs = [
    { id: 'interaction-types', label: t('customers.interaction_types') },
    { id: 'interaction-results', label: t('customers.interaction_results') },
    { id: 'sources', label: t('customers.sources') },
  ];

  // Only show department tab if user has permission
  if (hasDepartmentPermission.value) {
    baseTabs.push({ id: 'department', label: 'Phòng ban phụ trách' });
  }

  return baseTabs;
});

// Icon emoji mapping
const iconEmojiMap = {
  phone: '📞',
  envelope: '✉️',
  message: '💬',
  users: '👥',
  comment: '💬',
  facebook: '📘',
  store: '🏪',
  'check-circle': '✅',
  'phone-slash': '📵',
  calendar: '📅',
  'times-circle': '❌',
  clock: '⏰',
  'info-circle': 'ℹ️',
  ban: '🚫',
  google: '🔍',
  'user-friends': '👫',
  walking: '🚶',
  globe: '🌐',
  'calendar-star': '🎉',
  'ellipsis-h': '⋯',
};

const getIconEmoji = (icon) => {
  return iconEmojiMap[icon] || '📌';
};

const loadInteractionTypes = async () => {
  try {
    const response = await api.get('/api/customers/settings/interaction-types');
    if (response.data.success) {
      interactionTypes.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load interaction types:', error);
    swal.error('Có lỗi xảy ra khi tải loại tương tác');
  }
};

const loadInteractionResults = async () => {
  try {
    const response = await api.get('/api/customers/settings/interaction-results');
    if (response.data.success) {
      interactionResults.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load interaction results:', error);
    swal.error('Có lỗi xảy ra khi tải kết quả tương tác');
  }
};

const loadSources = async () => {
  try {
    const response = await api.get('/api/customers/settings/sources');
    if (response.data.success) {
      sources.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load sources:', error);
    swal.error('Có lỗi xảy ra khi tải nguồn khách hàng');
  }
};

const openAddModal = (type) => {
  currentType.value = type;
  currentItem.value = null;
  isEditMode.value = false;
  showItemModal.value = true;
};

const openEditModal = (type, item) => {
  currentType.value = type;
  currentItem.value = item;
  isEditMode.value = true;
  showItemModal.value = true;
};

const closeItemModal = () => {
  showItemModal.value = false;
  currentType.value = '';
  currentItem.value = null;
  isEditMode.value = false;
};

const handleItemSaved = () => {
  closeItemModal();
  // Reload data based on current tab
  if (activeTab.value === 'interaction-types') {
    loadInteractionTypes();
  } else if (activeTab.value === 'interaction-results') {
    loadInteractionResults();
  } else if (activeTab.value === 'sources') {
    loadSources();
  }
};

const deleteItem = async (type, item) => {
  const result = await swal.confirmDelete(
    `Bạn có chắc chắn muốn xóa "${item.name}"?`
  );
  
  if (!result.isConfirmed) return;

  try {
    let endpoint = '';
    if (type === 'interaction-type') {
      endpoint = `/api/customers/settings/interaction-types/${item.id}`;
    } else if (type === 'interaction-result') {
      endpoint = `/api/customers/settings/interaction-results/${item.id}`;
    } else if (type === 'source') {
      endpoint = `/api/customers/settings/sources/${item.id}`;
    }

    const response = await api.delete(endpoint);
    if (response.data.success) {
      swal.success(response.data.message);
      handleItemSaved(); // Reload data
    }
  } catch (error) {
    console.error('Failed to delete item:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi xóa');
  }
};

const close = () => {
  emit('close');
};

// Department settings functions
const loadBranches = async () => {
  try {
    const response = await api.get('/api/branches');
    if (response.data.success) {
      branches.value = response.data.data || [];
      if (branches.value.length > 0 && !selectedBranchId.value) {
        selectedBranchId.value = branches.value[0].id;
      }
    }
  } catch (error) {
    console.error('Failed to load branches:', error);
  }
};

const loadDepartmentSettings = async () => {
  if (!selectedBranchId.value) return;

  try {
    const response = await api.get('/api/customers/settings/department', {
      params: { branch_id: selectedBranchId.value }
    });
    if (response.data.success) {
      hasDepartmentPermission.value = true;
      currentDepartmentSetting.value = response.data.data.current_setting;
      departments.value = response.data.data.departments || [];
      if (currentDepartmentSetting.value) {
        selectedDepartmentId.value = currentDepartmentSetting.value.department_id;
      } else {
        selectedDepartmentId.value = null;
      }
    }
  } catch (error) {
    if (error.response?.status === 403) {
      hasDepartmentPermission.value = false;
    } else {
      console.error('Failed to load department settings:', error);
    }
  }
};

const saveDepartmentSettings = async () => {
  if (!selectedBranchId.value || !selectedDepartmentId.value) return;

  savingDepartment.value = true;
  try {
    const response = await api.post('/api/customers/settings/department', {
      branch_id: selectedBranchId.value,
      department_id: selectedDepartmentId.value
    });
    if (response.data.success) {
      swal.success(response.data.message);
      currentDepartmentSetting.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to save department settings:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi lưu thiết lập');
  } finally {
    savingDepartment.value = false;
  }
};

const removeDepartmentSettings = async () => {
  const result = await swal.confirmDelete('Bạn có chắc chắn muốn xóa thiết lập phòng ban phụ trách?');
  if (!result.isConfirmed) return;

  try {
    const response = await api.delete('/api/customers/settings/department', {
      data: { branch_id: selectedBranchId.value }
    });
    if (response.data.success) {
      swal.success(response.data.message);
      currentDepartmentSetting.value = null;
      selectedDepartmentId.value = null;
    }
  } catch (error) {
    console.error('Failed to remove department settings:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi xóa thiết lập');
  }
};

// Load data khi modal được mở
watch(() => props.show, async (newVal) => {
  if (newVal) {
    loadInteractionTypes();
    loadInteractionResults();
    loadSources();
    await loadBranches();
    loadDepartmentSettings();
  }
}, { immediate: true });
</script>