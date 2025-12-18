<template>
  <div class="space-y-6">
    <!-- Header with Settings Button -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('classes.management_title') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ t('classes.management_description') }}</p>
      </div>
      <div class="flex items-center space-x-3">
        <button
          v-if="authStore.hasPermission('classes.manage_settings')"
          @click="showSettingsModal = true"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center space-x-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
          </svg>
          <span>{{ t('classes.settings') }}</span>
        </button>
        <button
          v-if="authStore.hasPermission('classes.create')"
          @click="createClass"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center space-x-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          <span>{{ t('classes.create_class') }}</span>
        </button>
      </div>
    </div>

    <!-- Status Filter -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
      <div class="flex items-center gap-4">
        <label class="text-sm font-medium text-gray-700">Lọc theo trạng thái:</label>
        <select v-model="statusFilter" @change="loadClasses" 
                class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Tất cả trạng thái</option>
            <option value="draft">{{ t('classes.status_draft') }}</option>
            <option value="active">{{ t('classes.status_active') }}</option>
            <option value="completed">{{ t('classes.status_completed') }}</option>
            <option value="cancelled">{{ t('classes.status_cancelled') }}</option>
        </select>
      </div>
    </div>

    <!-- Classes List -->
    <div class="bg-white rounded-lg shadow">
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
      </div>
      
      <div v-else-if="classes.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        <p class="mt-4 text-gray-500">{{ t('classes.no_classes') }}</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('classes.class_name') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('classes.homeroom_teacher_short') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('classes.semester') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('classes.sessions') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('classes.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="classItem in classes" :key="classItem.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900">{{ classItem.name }}</td>
            <td class="px-6 py-4 text-gray-600">{{ classItem.homeroom_teacher?.name || '-' }}</td>
            <td class="px-6 py-4 text-gray-600">{{ classItem.semester?.name || '-' }}</td>
            <td class="px-6 py-4 text-gray-600">{{ classItem.completed_sessions }}/{{ classItem.total_sessions }}</td>
            <td class="px-6 py-4">
              <span :class="statusClass(classItem.status)" class="px-2 py-1 text-xs rounded-full">
                {{ statusText(classItem.status) }}
              </span>
            </td>
            <td class="px-6 py-4 text-right space-x-2">
              <!-- View Details -->
              <button
                @click="viewClass(classItem)"
                class="text-blue-600 hover:text-blue-800 inline-flex items-center justify-center"
                :title="t('classes.view_details')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>

              <!-- Zalo Group -->
              <button
                @click="openZaloGroupChat(classItem)"
                class="text-purple-600 hover:text-purple-800 inline-flex items-center justify-center"
                :title="t('quality.zalo_group') || 'Zalo Group'"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </button>

              <!-- Edit -->
              <button
                v-if="authStore.hasPermission('classes.edit')"
                @click="editClass(classItem)"
                class="text-green-600 hover:text-green-800 inline-flex items-center justify-center"
                :title="t('common.edit')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>

              <!-- Delete -->
              <button
                v-if="authStore.hasPermission('classes.delete')"
                @click="deleteClass(classItem)"
                class="text-red-600 hover:text-red-800 inline-flex items-center justify-center"
                :title="t('common.delete')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Settings Modal (ClassSettingsIndex as modal) -->
    <div v-if="showSettingsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg w-full max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-xl font-bold text-gray-900">{{ t('classes.settings_title') }}</h3>
          <button @click="showSettingsModal = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
          <ClassSettingsIndex />
        </div>
      </div>
    </div>

    <!-- Class Form Modal (placeholder for now) -->
    <div v-if="showClassForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-gray-900">{{ editingClass ? t('classes.edit_class') : t('classes.create_new_class') }}</h3>
          <button @click="showClassForm = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <ClassForm
          :class-data="editingClass"
          @saved="onClassSaved"
          @cancel="showClassForm = false"
        />
      </div>
    </div>

    <!-- Zalo Group Chat Modal -->
    <ClassZaloGroupChatModal
      :show="showZaloGroupChatModal"
      :class-data="selectedClass"
      @close="closeZaloGroupChat"
      @add-group="openAddGroupModal"
      @group-removed="onGroupRemoved"
    />

    <!-- Add Zalo Group Modal -->
    <AddZaloGroupModal
      :show="showAddGroupModal"
      :class-data="selectedClass"
      :account-id="currentZaloAccountId"
      @close="closeAddGroupModal"
      @group-selected="onGroupSelected"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import ClassSettingsIndex from './ClassSettingsIndex.vue';
import ClassForm from './ClassForm.vue';
import ClassZaloGroupChatModal from '../../components/quality/ClassZaloGroupChatModal.vue';
import AddZaloGroupModal from '../../components/quality/AddZaloGroupModal.vue';

const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();
const loading = ref(false);
const classes = ref([]);
const showSettingsModal = ref(false);
const showClassForm = ref(false);
const editingClass = ref(null);
const showZaloGroupChatModal = ref(false);
const showAddGroupModal = ref(false);
const selectedClass = ref(null);
const currentZaloAccountId = ref(null);

const statusFilter = ref('active'); // Default to active

const loadClasses = async () => {
  loading.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = { branch_id: branchId };
    
    // Add status filter if selected
    if (statusFilter.value) {
      params.status = statusFilter.value;
    }
    
    const response = await axios.get('/api/classes', { params });
    classes.value = response.data.data;
  } catch (error) {
    console.error('Load classes error:', error);
    Swal.fire(t('common.error'), t('classes.load_error'), 'error');
  } finally {
    loading.value = false;
  }
};

const createClass = () => {
  editingClass.value = null;
  showClassForm.value = true;
};

const editClass = (classItem) => {
  editingClass.value = classItem;
  showClassForm.value = true;
};

const viewClass = (classItem) => {
  router.push({ name: 'class.detail', params: { id: classItem.id } });
};

const deleteClass = async (classItem) => {
  const result = await Swal.fire({
    title: t('classes.delete_confirm_title'),
    text: t('classes.delete_confirm_text', { name: classItem.name }),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: t('common.delete'),
    cancelButtonText: t('common.cancel')
  });

  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/api/classes/${classItem.id}`);
    await Swal.fire(t('common.success'), t('classes.class_deleted'), 'success');
    await loadClasses();
  } catch (error) {
    console.error('Delete class error:', error);
    Swal.fire(t('common.error'), t('classes.delete_error'), 'error');
  }
};

const onClassSaved = () => {
  showClassForm.value = false;
  loadClasses();
};

const statusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    active: 'bg-green-100 text-green-800',
    completed: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-red-100 text-red-800'
  };
  return classes[status] || classes.draft;
};

const statusText = (status) => {
  const texts = {
    draft: t('classes.status_draft'),
    active: t('classes.status_active'),
    completed: t('classes.status_completed'),
    cancelled: t('classes.status_cancelled')
  };
  return texts[status] || status;
};

// Zalo Group functions
const openZaloGroupChat = async (classItem) => {
  selectedClass.value = classItem;

  // Get primary Zalo account ID if class doesn't have one
  if (!classItem.zalo_account_id) {
    try {
      const response = await axios.get('/api/zalo/accounts', {
        params: {
          branch_id: localStorage.getItem('current_branch_id'),
        },
      });
      if (response.data.success && response.data.data.length > 0) {
        // Filter for primary account on client side
        const primaryAccount = response.data.data.find(acc => acc.is_primary === true || acc.is_primary === 1);
        if (primaryAccount) {
          currentZaloAccountId.value = primaryAccount.id;
        } else {
          // No primary account found
          Swal.fire({
            icon: 'warning',
            title: 'Không có tài khoản Zalo',
            text: 'Không tìm thấy tài khoản Zalo primary. Vui lòng cấu hình tài khoản Zalo trong module Zalo.',
          });
          return;
        }
      } else {
        // No accounts found
        Swal.fire({
          icon: 'warning',
          title: 'Không có tài khoản Zalo',
          text: 'Không tìm thấy tài khoản Zalo nào. Vui lòng cấu hình tài khoản Zalo trong module Zalo.',
        });
        return;
      }
    } catch (error) {
      console.error('Error loading Zalo accounts:', error);
      Swal.fire({
        icon: 'error',
        title: 'Lỗi',
        text: 'Không thể tải danh sách tài khoản Zalo',
      });
      return;
    }
  } else {
    currentZaloAccountId.value = classItem.zalo_account_id;
  }

  showZaloGroupChatModal.value = true;
};

const closeZaloGroupChat = () => {
  showZaloGroupChatModal.value = false;
  selectedClass.value = null;
};

const openAddGroupModal = () => {
  showAddGroupModal.value = true;
};

const closeAddGroupModal = () => {
  showAddGroupModal.value = false;
};

const onGroupSelected = async (updatedClass) => {
  // Reload classes to get updated data
  await loadClasses();

  // Update selected class with new data
  const updated = classes.value.find(c => c.id === updatedClass.id);
  if (updated) {
    selectedClass.value = updated;
  }

  // Close add group modal
  showAddGroupModal.value = false;
};

const onGroupRemoved = async (updatedClass) => {
  // Reload classes to get updated data
  await loadClasses();

  // Update selected class with new data (now with group removed)
  const updated = classes.value.find(c => c.id === updatedClass.id);
  if (updated) {
    selectedClass.value = updated;
  }
};

onMounted(() => {
  loadClasses();
});
</script>

