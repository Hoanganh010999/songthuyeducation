<template>
  <div v-if="canAssignBranch" class="space-y-4">
    <!-- Assignment Section Header -->
    <div class="border-t border-gray-200 pt-4">
      <h5 class="font-medium text-gray-900 mb-3">{{ t('zalo.group_assignment') }}</h5>
    </div>

    <!-- Current Branch Assignment -->
    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600">{{ t('zalo.branch') }}:</span>
        <button
          @click="showBranchModal = true"
          class="text-sm text-blue-600 hover:text-blue-700"
        >
          {{ group.branch_id ? t('zalo.change') : t('zalo.assign') }}
        </button>
      </div>
      <div v-if="group.branch" class="flex items-center gap-2 p-2 bg-blue-50 rounded">
        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <span class="text-sm font-medium text-gray-900">{{ group.branch?.name }}</span>
      </div>
      <div v-else class="text-sm text-gray-400 italic">{{ t('zalo.unassigned') }}</div>
    </div>

    <!-- Branch Assignment Modal -->
    <Teleport to="body">
      <div v-if="showBranchModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
          <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">{{ t('zalo.assign_to_branch') }}</h3>
              <button @click="showBranchModal = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="p-4">
            <div v-if="loadingBranches" class="text-center py-4">
              <svg class="inline w-6 h-6 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <p class="mt-2 text-sm text-gray-500">{{ t('common.loading') }}...</p>
            </div>
            <div v-else-if="branches.length === 0" class="text-center py-4 text-gray-500">
              {{ t('zalo.no_branches') || 'No branches found' }}
            </div>
            <div v-else class="space-y-2 max-h-96 overflow-y-auto">
              <!-- Unassign option -->
              <button
                @click="assignBranch(null)"
                class="w-full text-left p-3 rounded-lg hover:bg-gray-50 border border-gray-200"
                :class="!group.branch_id ? 'bg-gray-100 border-gray-400' : ''"
              >
                <div class="font-medium text-gray-900">{{ t('zalo.unassigned') }} ({{ t('zalo.global') || 'Global' }})</div>
                <div class="text-xs text-gray-500 mt-1">{{ t('zalo.visible_to_all_branches') || 'Visible to all branches' }}</div>
              </button>

              <!-- Branch options -->
              <button
                v-for="branch in branches"
                :key="branch.id"
                @click="assignBranch(branch.id)"
                class="w-full text-left p-3 rounded-lg hover:bg-gray-50 border border-gray-200"
                :class="group.branch_id === branch.id ? 'bg-blue-50 border-blue-300' : ''"
              >
                <div class="font-medium text-gray-900">{{ branch.name }}</div>
                <div v-if="branch.zalo_account_name" class="text-xs text-gray-500 mt-1">
                  {{ t('zalo.account') }}: {{ branch.zalo_account_name }}
                </div>
              </button>
            </div>
          </div>

          <div class="p-4 border-t bg-gray-50">
            <button
              @click="showBranchModal = false"
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
import { ref, watch, computed } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useSwal } from '../../../composables/useSwal';
import { useAuthStore } from '../../../stores/auth';
import axios from 'axios';

const { t } = useI18n();
const authStore = useAuthStore();

const props = defineProps({
  group: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['updated']);

// Check if user can assign branches (superadmin or has zalo.view_all_branches_groups permission)
const canAssignBranch = computed(() => {
  return authStore.isSuperAdmin || authStore.hasPermission('zalo.view_all_branches_groups');
});

const showBranchModal = ref(false);
const branches = ref([]);
const loadingBranches = ref(false);

const loadBranches = async () => {
  loadingBranches.value = true;
  try {
    // Get branches that share the same Zalo account as this group
    const accountId = localStorage.getItem('active_zalo_account_id');
    const response = await axios.get(`/api/zalo/groups/${props.group.id}/available-branches`, {
      params: {
        account_id: accountId
      }
    });
    branches.value = response.data.data || [];
    console.log('✅ [ZaloGroupAssignment] Loaded branches:', branches.value.length);
  } catch (error) {
    console.error('❌ [ZaloGroupAssignment] Failed to load branches:', error);
    // Fallback to loading all branches if the specific endpoint doesn't exist yet
    try {
      const fallbackResponse = await axios.get('/api/branches');
      branches.value = fallbackResponse.data.data || [];
    } catch (fallbackError) {
      console.error('Failed to load branches (fallback):', fallbackError);
      useSwal().fire({
        icon: 'error',
        title: t('common.error'),
        text: t('zalo.cannot_load_branches') || 'Cannot load branches',
      });
    }
  } finally {
    loadingBranches.value = false;
  }
};

const assignBranch = async (branchId) => {
  try {
    const accountId = localStorage.getItem('active_zalo_account_id');
    const response = await axios.put(`/api/zalo/groups/${props.group.id}/assign`, {
      branch_id: branchId,
      department_id: props.group.department_id, // Keep existing department
      account_id: accountId,
    });

    if (response.data.success) {
      // Update local group object from backend response
      const updatedGroup = response.data.data;
      if (updatedGroup) {
        props.group.branch_id = updatedGroup.branch_id;
        props.group.branch = updatedGroup.branch;
        props.group.department_id = updatedGroup.department_id;
        props.group.department = updatedGroup.department;

        console.log('✅ [ZaloGroupAssignment] Group updated from backend:', {
          branch_id: updatedGroup.branch_id,
          branch: updatedGroup.branch,
        });
      }

      useSwal().fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.group_assigned_success') || 'Group assigned successfully',
        timer: 1500,
      });

      showBranchModal.value = false;

      // Emit updated event with the full updated group data
      emit('updated', updatedGroup);
    }
  } catch (error) {
    console.error('❌ [ZaloGroupAssignment] Failed to assign branch:', error);
    useSwal().fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || (t('zalo.cannot_assign_branch') || 'Cannot assign branch'),
    });
  }
};

// Watch modal open event to load data when modal is opened
watch(showBranchModal, (newValue) => {
  if (newValue && branches.value.length === 0) {
    loadBranches();
  }
});
</script>
