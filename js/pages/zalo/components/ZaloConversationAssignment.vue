<template>
  <div class="space-y-4">
    <!-- Assignment Section Header -->
    <div class="border-t border-gray-200 pt-4">
      <h5 class="font-medium text-gray-900 mb-3">{{ t('zalo.assign_conversation') }}</h5>
    </div>

    <!-- Current Department Assignment -->
    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600">{{ t('zalo.department') }}:</span>
        <button
          @click="showDepartmentModal = true"
          class="text-sm text-blue-600 hover:text-blue-700"
        >
          {{ conversation.department ? t('zalo.change') : t('zalo.assign') }}
        </button>
      </div>
      <div v-if="conversation.department" class="flex items-center gap-2 p-2 bg-blue-50 rounded">
        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <span class="text-sm font-medium text-gray-900">{{ conversation.department?.name }}</span>
      </div>
      <div v-else class="text-sm text-gray-400 italic">{{ t('zalo.not_assigned_department') }}</div>
    </div>

    <!-- Current User Assignments -->
    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600">{{ t('zalo.assigned_employees') }}:</span>
        <button
          @click="showUserModal = true"
          class="text-sm text-blue-600 hover:text-blue-700"
        >
          {{ t('zalo.assign_employee') }}
        </button>
      </div>
      <div v-if="conversation.assigned_users && conversation.assigned_users.length > 0" class="space-y-1">
        <div
          v-for="user in conversation.assigned_users"
          :key="user?.id"
          class="flex items-center justify-between p-2 bg-green-50 rounded"
        >
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="text-sm font-medium text-gray-900">{{ user?.name }}</span>
          </div>
          <button
            @click="removeUserAssignment(user?.id)"
            class="text-red-600 hover:text-red-700"
            :title="t('zalo.remove_assignment')"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
      <div v-else class="text-sm text-gray-400 italic">{{ t('zalo.not_assigned_employee') }}</div>
    </div>

    <!-- Department Assignment Modal -->
    <Teleport to="body">
      <div v-if="showDepartmentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
          <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">{{ t('zalo.assign_department') }}</h3>
              <button @click="showDepartmentModal = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="p-4">
            <div v-if="loadingDepartments" class="text-center py-4">
              <svg class="inline w-6 h-6 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <p class="mt-2 text-sm text-gray-500">{{ t('common.loading') }}...</p>
            </div>
            <div v-else-if="departments.length === 0" class="text-center py-4 text-gray-500">
              {{ t('zalo.no_departments') }}
            </div>
            <div v-else class="space-y-2 max-h-96 overflow-y-auto">
              <button
                v-for="dept in departments"
                :key="dept.id"
                @click="assignDepartment(dept.id)"
                class="w-full text-left p-3 rounded-lg hover:bg-gray-50 border border-gray-200"
                :class="conversation.department?.id === dept.id ? 'bg-blue-50 border-blue-300' : ''"
              >
                <div class="font-medium text-gray-900">{{ dept.name }}</div>
                <div v-if="dept.branch" class="text-xs text-gray-500 mt-1">{{ t('zalo.branch') }}: {{ dept.branch.name }}</div>
              </button>
            </div>
          </div>

          <div class="p-4 border-t bg-gray-50">
            <button
              @click="showDepartmentModal = false"
              class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ t('zalo.close') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- User Assignment Modal -->
    <Teleport to="body">
      <div v-if="showUserModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
          <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">{{ t('zalo.assign_employee') }}</h3>
              <button @click="showUserModal = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="p-4">
            <!-- Search -->
            <div class="mb-3">
              <input
                v-model="userSearchQuery"
                type="text"
                :placeholder="t('zalo.search_employees')"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div v-if="loadingUsers" class="text-center py-4">
              <svg class="inline w-6 h-6 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <p class="mt-2 text-sm text-gray-500">{{ t('common.loading') }}...</p>
            </div>
            <div v-else-if="filteredUsers.length === 0" class="text-center py-4 text-gray-500">
              {{ t('zalo.no_employees') }}
            </div>
            <div v-else class="space-y-2 max-h-96 overflow-y-auto">
              <button
                v-for="user in filteredUsers"
                :key="user?.id"
                @click="assignUser(user?.id)"
                :disabled="assigningUser === user?.id"
                class="w-full text-left p-3 rounded-lg hover:bg-gray-50 border border-gray-200 disabled:opacity-50"
                :class="isUserAssigned(user?.id) ? 'bg-green-50 border-green-300' : ''"
              >
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ user?.name }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ user?.email }}</div>
                  </div>
                  <div v-if="isUserAssigned(user?.id)" class="text-green-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <div v-else-if="assigningUser === user?.id" class="text-blue-600">
                    <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                  </div>
                </div>
              </button>
            </div>
          </div>

          <div class="p-4 border-t bg-gray-50">
            <button
              @click="showUserModal = false"
              class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ t('zalo.close') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useSwal } from '../../../composables/useSwal';
import axios from 'axios';

const { t } = useI18n();

const props = defineProps({
  conversation: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['updated']);

const showDepartmentModal = ref(false);
const showUserModal = ref(false);
const departments = ref([]);
const users = ref([]);
const loadingDepartments = ref(false);
const loadingUsers = ref(false);
const assigningUser = ref(null);
const userSearchQuery = ref('');

const filteredUsers = computed(() => {
  if (!userSearchQuery.value) return users.value;
  const query = userSearchQuery.value.toLowerCase();
  return users.value.filter(user =>
    user?.name?.toLowerCase().includes(query) ||
    user?.email?.toLowerCase().includes(query)
  );
});

const isUserAssigned = (userId) => {
  if (!props.conversation || !props.conversation.assigned_users) return false;
  return props.conversation.assigned_users.some(u => u?.id === userId);
};

const loadDepartments = async () => {
  loadingDepartments.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = {};
    if (branchId) params.branch_id = branchId;

    const response = await axios.get('/api/hr/departments', { params });
    // DepartmentController returns array directly, not wrapped
    departments.value = Array.isArray(response.data) ? response.data : (response.data.data || []);
    console.log('✅ [ZaloConversationAssignment] Loaded departments:', departments.value.length);
  } catch (error) {
    console.error('❌ [ZaloConversationAssignment] Failed to load departments:', error);
    useSwal().fire({
      icon: 'error',
      title: t('common.error'),
      text: t('zalo.cannot_load_departments'),
    });
  } finally {
    loadingDepartments.value = false;
  }
};

const loadUsers = async () => {
  loadingUsers.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = {};
    if (branchId) params.branch_id = branchId;

    // Use branch-employees endpoint to get only employees (not students/parents)
    const response = await axios.get('/api/users/branch-employees', { params });

    // This endpoint returns data directly in response.data.data array
    const data = response.data.data || [];
    users.value = Array.isArray(data) ? data.filter(u => u && u.name) : [];

    console.log('✅ [ZaloConversationAssignment] Loaded employees:', users.value.length);
  } catch (error) {
    console.error('❌ [ZaloConversationAssignment] Failed to load employees:', error);
    users.value = []; // Ensure it's an array even on error
    useSwal().fire({
      icon: 'error',
      title: t('common.error'),
      text: t('zalo.cannot_load_employees'),
    });
  } finally {
    loadingUsers.value = false;
  }
};

const assignDepartment = async (departmentId) => {
  try {
    // Use conversation_id (database ID) instead of id (recipient_id from Zalo)
    const conversationDbId = props.conversation.conversation_id || props.conversation.id;
    const response = await axios.post(`/api/zalo/conversations/${conversationDbId}/assign-department`, {
      department_id: departmentId,
    });

    if (response.data.success) {
      useSwal().fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.department_assigned_success'),
        timer: 2000,
      });

      // Update local conversation object
      const dept = departments.value.find(d => d.id === departmentId);
      if (dept) {
        props.conversation.department = dept;
      }

      showDepartmentModal.value = false;
      emit('updated');
    }
  } catch (error) {
    console.error('Failed to assign department:', error);
    useSwal().fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('zalo.cannot_assign_department'),
    });
  }
};

const assignUser = async (userId) => {
  if (isUserAssigned(userId)) {
    useSwal().fire({
      icon: 'info',
      title: t('common.info'),
      text: t('zalo.employee_already_assigned'),
      timer: 2000,
    });
    return;
  }

  assigningUser.value = userId;
  try {
    // Use conversation_id (database ID) instead of id (recipient_id from Zalo)
    const conversationDbId = props.conversation.conversation_id || props.conversation.id;
    const response = await axios.post(`/api/zalo/conversations/${conversationDbId}/assign-user`, {
      user_id: userId,
      can_view: true,
      can_reply: true,
    });

    if (response.data.success) {
      useSwal().fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.employee_assigned_success'),
        timer: 2000,
      });

      // Update local conversation object
      const user = users.value.find(u => u.id === userId);
      if (user) {
        if (!props.conversation.assigned_users) {
          props.conversation.assigned_users = [];
        }
        props.conversation.assigned_users.push(user);
      }

      emit('updated');
    }
  } catch (error) {
    console.error('Failed to assign user:', error);
    useSwal().fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('zalo.cannot_assign_employee'),
    });
  } finally {
    assigningUser.value = null;
  }
};

const removeUserAssignment = async (userId) => {
  const result = await useSwal().fire({
    icon: 'question',
    title: t('common.confirm'),
    text: t('zalo.remove_assignment_confirm'),
    showCancelButton: true,
    confirmButtonText: t('common.delete'),
    cancelButtonText: t('common.cancel'),
  });

  if (!result.isConfirmed) return;

  try {
    // Use conversation_id (database ID) instead of id (recipient_id from Zalo)
    const conversationDbId = props.conversation.conversation_id || props.conversation.id;
    const response = await axios.delete(`/api/zalo/conversations/${conversationDbId}/assign-user/${userId}`);

    if (response.data.success) {
      useSwal().fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.assignment_removed_success'),
        timer: 2000,
      });

      // Update local conversation object
      if (props.conversation.assigned_users) {
        props.conversation.assigned_users = props.conversation.assigned_users.filter(u => u.id !== userId);
      }

      emit('updated');
    }
  } catch (error) {
    console.error('Failed to remove user assignment:', error);
    useSwal().fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('zalo.cannot_remove_assignment'),
    });
  }
};

// Watch modal open events to load data when modals are opened
watch(showDepartmentModal, (newValue) => {
  if (newValue && departments.value.length === 0) {
    loadDepartments();
  }
});

watch(showUserModal, (newValue) => {
  if (newValue && users.value.length === 0) {
    loadUsers();
  }
});
</script>
