<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
    @click.self="close"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl h-[85vh] flex flex-col">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
        <div class="flex-1">
          <h2 class="text-xl font-bold text-gray-900">
            {{ t('quality.zalo_group_chat') || 'Hộp chat Zalo Group' }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            {{ classData?.name }} - {{ classData?.zalo_group_name || 'Chưa có group' }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <!-- Add Member Button -->
          <button
            v-if="classData?.zalo_group_id && groupExists"
            @click="showAddMemberForm = true"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors inline-flex items-center gap-2"
            :title="'Thêm thành viên'"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span>Thêm thành viên</span>
          </button>
          <button
            @click="close"
            class="p-2 hover:bg-gray-100 rounded-full transition-colors"
          >
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-hidden">
        <!-- Validating -->
        <div v-if="validating" class="h-full flex items-center justify-center">
          <div class="text-center">
            <svg class="animate-spin w-12 h-12 mx-auto text-blue-600 mb-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-600">Đang kiểm tra group...</p>
          </div>
        </div>

        <!-- No Group Assigned or Group Doesn't Exist -->
        <div v-else-if="!classData?.zalo_group_id || !groupExists" class="h-full flex items-center justify-center p-6">
          <div class="text-center max-w-md">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              {{ t('quality.no_zalo_group') || 'Chưa có Zalo Group' }}
            </h3>
            <p class="text-gray-600 mb-6">
              {{ t('quality.no_zalo_group_desc') || 'Lớp học này chưa được liên kết với Zalo Group. Hãy thêm group để bắt đầu chat.' }}
            </p>
            <button
              @click="openAddGroupModal"
              class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              {{ t('quality.add_group') || 'Thêm Group' }}
            </button>
          </div>
        </div>

        <!-- Has Group - Show Chat -->
        <div v-else class="h-full">
          <ZaloChatView
            v-if="groupItem"
            :item="groupItem"
            :account-id="classData.zalo_account_id"
            item-type="groups"
          />
        </div>
      </div>
    </div>

    <!-- Add Member by Phone Modal -->
    <div v-if="showAddMemberForm" class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Thêm thành viên bằng số điện thoại</h3>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Số điện thoại <span class="text-red-500">*</span>
          </label>
          <input
            v-model="phoneNumber"
            type="tel"
            placeholder="Nhập số điện thoại (VD: 0397617861)"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            @keydown.enter="addMemberByPhone"
          />
          <p class="text-xs text-gray-500 mt-1">Hệ thống sẽ tự động gửi lời mời kết bạn nếu chưa là bạn</p>
        </div>

        <div class="flex justify-end gap-3">
          <button
            @click="showAddMemberForm = false; phoneNumber = ''"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
            :disabled="addingMember"
          >
            Hủy
          </button>
          <button
            @click="addMemberByPhone"
            :disabled="!phoneNumber.trim() || addingMember"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
          >
            <svg v-if="addingMember" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ addingMember ? 'Đang thêm...' : 'Thêm thành viên' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import ZaloChatView from '../../pages/zalo/components/ZaloChatView.vue';

const { t } = useI18n();

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  classData: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close', 'add-group', 'group-removed']);

const validating = ref(false);
const groupExists = ref(true);
const groupData = ref(null);
const showAddMemberForm = ref(false);
const phoneNumber = ref('');
const addingMember = ref(false);

// Validate if group still exists in Zalo (Optimistic UI - runs in background)
const validateGroup = async () => {
  if (!props.classData?.zalo_group_id || !props.classData?.zalo_account_id) {
    return;
  }

  // 🚀 OPTIMISTIC UI: Don't set validating = true, let UI show immediately
  // validating.value = true;

  try {
    console.log('[ClassZaloGroupChatModal] Validating group in background:', {
      zalo_group_id: props.classData.zalo_group_id,
      zalo_account_id: props.classData.zalo_account_id,
    });

    // 🚀 NEW: Use optimized validate endpoint (checks only one group)
    const response = await axios.post('/api/zalo/groups/validate', {
      account_id: props.classData.zalo_account_id,
      group_id: props.classData.zalo_group_id,
    });

    if (response.data.success) {
      if (response.data.exists) {
        // Group exists - update data
        groupExists.value = true;
        groupData.value = response.data.data;

        console.log('[ClassZaloGroupChatModal] Group validated:', {
          id: response.data.data.id,
          name: response.data.data.name,
          members_count: response.data.data.members_count,
          cached: response.data.cached,
        });
      } else {
        // Group doesn't exist - clear it
        console.warn('[ClassZaloGroupChatModal] Group not found in Zalo, clearing class data');

        await clearGroupFromClass();
        groupExists.value = false;
        groupData.value = null;

        Swal.fire({
          icon: 'warning',
          title: 'Group không tồn tại',
          text: 'Zalo Group đã bị xóa hoặc không còn tồn tại. Dữ liệu đã được xóa khỏi lớp học.',
        });
      }
    }
  } catch (error) {
    console.error('[ClassZaloGroupChatModal] Error validating group:', error);
    // Don't show error to user - group might still work
  }
  // 🚀 OPTIMISTIC UI: Don't set validating = false in finally
};

// Clear group data from class
const clearGroupFromClass = async () => {
  try {
    const response = await axios.patch(`/api/classes/${props.classData.id}/zalo-group`, {
      zalo_account_id: null,
      zalo_group_id: null,
      zalo_group_name: null,
    });

    if (response.data.success) {
      // Notify parent to update class data
      emit('group-removed', response.data.data);
    }
  } catch (error) {
    console.error('[ClassZaloGroupChatModal] Error clearing group from class:', error);
  }
};

// Create group item compatible with ZaloChatView
const groupItem = computed(() => {
  if (!props.classData?.zalo_group_id || !groupExists.value) return null;

  // Use group data from API if available, otherwise use class data
  return {
    id: props.classData.zalo_group_id,
    groupId: props.classData.zalo_group_id,
    name: groupData.value?.name || groupData.value?.displayName || props.classData.zalo_group_name || 'Nhóm lớp học',
    displayName: groupData.value?.displayName || groupData.value?.name || props.classData.zalo_group_name || 'Nhóm lớp học',
    avatar_url: groupData.value?.avatar_url || null,
    members_count: groupData.value?.members_count || 0,
  };
});

const close = () => {
  emit('close');
};

const openAddGroupModal = () => {
  emit('add-group');
};

// Add member by phone number
const addMemberByPhone = async () => {
  if (!phoneNumber.value.trim()) return;

  addingMember.value = true;

  try {
    console.log('[ClassZaloGroupChatModal] Adding member by phone:', {
      phone: phoneNumber.value,
      group_id: props.classData.zalo_group_id,
      account_id: props.classData.zalo_account_id,
    });

    const response = await axios.post('/api/zalo/groups/add-member-by-phone', {
      account_id: props.classData.zalo_account_id,
      group_id: props.classData.zalo_group_id,
      phone: phoneNumber.value,
    });

    if (response.data.success) {
      const friendRequestSent = response.data.data?.friend_request_sent;

      Swal.fire({
        icon: 'success',
        title: 'Thành công',
        html: response.data.message,
        showConfirmButton: true,
        confirmButtonText: 'OK',
      });

      // Reset form
      showAddMemberForm.value = false;
      phoneNumber.value = '';
    }
  } catch (error) {
    console.error('[ClassZaloGroupChatModal] Error adding member:', error);

    let errorMessage = 'Không thể thêm thành viên';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.message) {
      errorMessage = error.message;
    }

    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: errorMessage,
    });
  } finally {
    addingMember.value = false;
  }
};

// Validate group when modal is shown
watch(() => props.show, (newValue) => {
  if (newValue && props.classData?.zalo_group_id) {
    validateGroup();
  } else {
    // Reset validation state when modal is closed
    groupExists.value = true;
    groupData.value = null;
  }
});
</script>
