<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
    @click.self="close"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[85vh] overflow-y-auto">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
        <div>
          <h2 class="text-xl font-bold text-gray-900">
            {{ t('quality.add_zalo_group') || 'Thêm Zalo Group' }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            {{ classData?.name }}
          </p>
        </div>
        <button
          @click="close"
          class="p-2 hover:bg-gray-100 rounded-full transition-colors"
        >
          <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Tabs -->
      <div class="px-6 py-3 border-b border-gray-200 bg-gray-50">
        <div class="flex gap-2">
          <button
            @click="activeTab = 'search'"
            :class="activeTab === 'search' ? 'bg-white text-blue-600 border-blue-600' : 'text-gray-600 hover:bg-white'"
            class="px-4 py-2 rounded-lg border transition-colors font-medium"
          >
            {{ t('quality.search_group') || 'Tìm kiếm Group' }}
          </button>
          <button
            @click="activeTab = 'create'"
            :class="activeTab === 'create' ? 'bg-white text-blue-600 border-blue-600' : 'text-gray-600 hover:bg-white'"
            class="px-4 py-2 rounded-lg border transition-colors font-medium"
          >
            {{ t('quality.create_group') || 'Tạo Group Mới' }}
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="p-6">
        <!-- Search Tab -->
        <div v-if="activeTab === 'search'">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('quality.group_name') || 'Tên Group' }}
            </label>
            <div class="flex gap-2">
              <input
                v-model="searchForm.name"
                type="text"
                :placeholder="t('quality.enter_exact_group_name') || 'Nhập tên chính xác của group'"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                @keydown.enter="searchGroup"
              />
              <button
                @click="searchGroup"
                :disabled="!searchForm.name || searching"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
              >
                <svg v-if="searching" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ t('common.search') || 'Tìm kiếm' }}
              </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              {{ t('quality.exact_search_note') || 'Lưu ý: Tìm kiếm chính xác theo tên, không hỗ trợ tìm kiếm tương đối' }}
            </p>
          </div>

          <!-- Search Results -->
          <div v-if="searchResults.length > 0" class="space-y-3">
            <h3 class="text-sm font-medium text-gray-700">{{ t('quality.search_results') || 'Kết quả tìm kiếm' }}:</h3>
            <div
              v-for="group in searchResults"
              :key="group.id"
              class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
            >
              <div class="flex items-center gap-3">
                <!-- Group Avatar -->
                <div class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0">
                  <img
                    v-if="group.avatar_url"
                    :src="group.avatar_url"
                    :alt="group.name"
                    class="w-full h-full object-cover"
                    @error="$event.target.parentElement.innerHTML = `<div class='w-full h-full bg-purple-100 flex items-center justify-center'><svg class='w-6 h-6 text-purple-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' /></svg></div>`"
                  />
                  <div v-else class="w-full h-full bg-purple-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                  </div>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ group.name }}</p>
                  <p class="text-sm text-gray-600">{{ group.members_count || 0 }} thành viên</p>
                </div>
              </div>
              <button
                @click="selectGroup(group)"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                {{ t('common.select') || 'Chọn' }}
              </button>
            </div>
          </div>

          <div v-else-if="searched && !searching" class="text-center py-8 text-gray-500">
            {{ t('quality.no_groups_found') || 'Không tìm thấy group nào' }}
          </div>
        </div>

        <!-- Create Tab -->
        <div v-if="activeTab === 'create'">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('quality.group_name') || 'Tên Group' }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model="createForm.name"
                type="text"
                :placeholder="t('quality.enter_group_name') || 'Nhập tên group'"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('quality.select_members') || 'Chọn thành viên' }} <span class="text-red-500">*</span>
              </label>
              <p class="text-sm text-gray-600 mb-3">
                {{ t('quality.create_group_note') || 'Chọn học sinh từ danh sách lớp để thêm vào group' }}
              </p>

              <!-- Member selection -->
              <div class="border border-gray-300 rounded-lg max-h-96 overflow-y-auto">
                <!-- Loading state -->
                <div v-if="loadingContacts" class="p-8 text-center">
                  <svg class="animate-spin w-8 h-8 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <p class="text-sm text-gray-600 mt-2">Đang tải danh sách...</p>
                </div>

                <!-- Empty state -->
                <div v-else-if="!loadingContacts && contacts.length === 0" class="p-8 text-center text-gray-500">
                  <p>Không tìm thấy học viên/phụ huynh nào trong lớp</p>
                </div>

                <!-- Contacts list -->
                <div v-else class="divide-y divide-gray-200">
                  <div
                    v-for="contact in contacts"
                    :key="contact.phone"
                    class="p-3 hover:bg-gray-50 cursor-pointer flex items-center gap-3"
                    @click="toggleMember(contact)"
                  >
                    <input
                      type="checkbox"
                      :checked="isMemberSelected(contact)"
                      class="w-4 h-4 text-blue-600 rounded"
                      @click.stop="toggleMember(contact)"
                    />
                    <!-- Avatar -->
                    <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0">
                      <img
                        v-if="contact.avatar_url"
                        :src="contact.avatar_url"
                        :alt="contact.name"
                        class="w-full h-full object-cover"
                        @error="$event.target.parentElement.innerHTML = `<div class='w-full h-full bg-gray-100 flex items-center justify-center'><svg class='w-5 h-5 text-gray-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' /></svg></div>`"
                      />
                      <div v-else class="w-full h-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-medium text-gray-900 truncate">{{ contact.name }}</p>
                        <span
                          :class="contact.type === 'student' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                          class="px-2 py-0.5 text-xs font-medium rounded"
                        >
                          {{ contact.relationship }}
                        </span>
                        <span
                          v-if="contact.needs_friend_request"
                          class="px-2 py-0.5 text-xs font-medium rounded bg-orange-100 text-orange-800"
                        >
                          Cần gửi lời mời
                        </span>
                      </div>
                      <p class="text-sm text-gray-600">
                        <span v-if="contact.zalo_name">{{ contact.zalo_name }} - </span>
                        {{ contact.phone }}
                      </p>
                      <p v-if="contact.type === 'parent'" class="text-xs text-gray-500">
                        Phụ huynh của {{ contact.student_name }} ({{ contact.student_code }})
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Selected count -->
              <p v-if="selectedMembers.length > 0" class="text-sm text-gray-600 mt-2">
                Đã chọn: <span class="font-medium">{{ selectedMembers.length }}</span> người
              </p>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
              <button
                @click="close"
                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100"
              >
                {{ t('common.cancel') || 'Hủy' }}
              </button>
              <button
                @click="createGroup"
                :disabled="!createForm.name || creating"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
              >
                <svg v-if="creating" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ t('quality.create_group') || 'Tạo Group' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';

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
  accountId: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['close', 'group-selected']);

const activeTab = ref('search');
const searching = ref(false);
const searched = ref(false);
const searchForm = ref({
  name: '',
});
const searchResults = ref([]);

const creating = ref(false);
const createForm = ref({
  name: '',
  members: [],
});

// Contacts state
const loadingContacts = ref(false);
const contacts = ref([]);
const selectedMembers = ref([]);

// Search for existing groups by exact name
const searchGroup = async () => {
  if (!searchForm.value.name) return;

  searching.value = true;
  searched.value = false;

  try {
    // Load all groups with sync to get latest data from Zalo
    console.log('[AddZaloGroupModal] Searching groups with sync=true', {
      account_id: props.accountId,
      search_name: searchForm.value.name,
    });

    const response = await axios.get('/api/zalo/groups', {
      params: {
        account_id: props.accountId,
        sync: true, // Sync from Zalo to get latest members_count
      },
    });

    console.log('[AddZaloGroupModal] API Response:', {
      success: response.data.success,
      total_groups: response.data.data?.length,
      cached: response.data.cached,
      sample_group: response.data.data?.[0],
    });

    if (response.data.success) {
      const allGroups = response.data.data || [];
      // Filter by exact name match
      searchResults.value = allGroups.filter(group =>
        group.name.toLowerCase() === searchForm.value.name.toLowerCase()
      );

      console.log('[AddZaloGroupModal] Search results:', {
        found_count: searchResults.value.length,
        results: searchResults.value,
      });

      // Debug: Log each result's avatar info
      searchResults.value.forEach((group, index) => {
        console.log(`[AddZaloGroupModal] Group ${index}:`, {
          name: group.name,
          avatar_url: group.avatar_url,
          has_avatar: !!group.avatar_url,
        });
      });

      searched.value = true;
    }
  } catch (error) {
    console.error('Error searching groups:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || 'Failed to search groups',
    });
  } finally {
    searching.value = false;
  }
};

// Select a group from search results
const selectGroup = async (group) => {
  try {
    // Update class with selected group
    const response = await axios.patch(`/api/classes/${props.classData.id}/zalo-group`, {
      zalo_account_id: props.accountId,
      zalo_group_id: group.id,
      zalo_group_name: group.name,
    });

    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('quality.group_added_successfully') || 'Group đã được thêm vào lớp học',
        timer: 2000,
        showConfirmButton: false,
      });

      emit('group-selected', response.data.data);
      close();
    }
  } catch (error) {
    console.error('Error selecting group:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || 'Failed to add group to class',
    });
  }
};

// Create new group
const createGroup = async () => {
  if (!createForm.value.name) {
    Swal.fire({
      icon: 'warning',
      title: t('common.validation_error'),
      text: t('quality.please_enter_group_name') || 'Vui lòng nhập tên group',
    });
    return;
  }

  if (selectedMembers.value.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: t('common.validation_error'),
      text: 'Vui lòng chọn ít nhất 1 thành viên để tạo nhóm',
    });
    return;
  }

  creating.value = true;

  try {
    // Prepare members data with all necessary info for auto-friend feature
    const membersData = selectedMembers.value.map(member => ({
      name: member.name,
      phone: member.phone,
      zalo_user_id: member.zalo_user_id || null,
    }));

    console.log('[CreateGroup] Creating group with auto-friend requests:', {
      name: createForm.value.name,
      membersData,
      selectedMembers: selectedMembers.value,
    });

    const response = await axios.post('/api/zalo/groups/create-with-auto-friend', {
      account_id: props.accountId,
      name: createForm.value.name,
      members: membersData,
    });

    if (response.data.success) {
      const newGroup = response.data.data;
      const friendRequests = response.data.friendRequests;

      console.log('[CreateGroup] Group created successfully:', {
        newGroup,
        friendRequests,
      });

      // Update class with new group
      const updateResponse = await axios.patch(`/api/classes/${props.classData.id}/zalo-group`, {
        zalo_account_id: props.accountId,
        zalo_group_id: newGroup.groupId,
        zalo_group_name: createForm.value.name,
      });

      if (updateResponse.data.success) {
        // Build success message with friend request summary
        let successMessage = 'Group đã được tạo thành công!';
        successMessage += '\n\nTất cả thành viên đã được thêm vào group.';

        if (friendRequests.sentCount > 0 || friendRequests.failedCount > 0) {
          successMessage += `\n\nLời mời kết bạn:`;
          if (friendRequests.sentCount > 0) {
            successMessage += `\n- Đã gửi: ${friendRequests.sentCount} người`;
          }
          if (friendRequests.failedCount > 0) {
            successMessage += `\n- Thất bại: ${friendRequests.failedCount} người`;
            successMessage += `\n\n⚠️ Lưu ý: Lời mời kết bạn có thể bị từ chối do giới hạn của Zalo (giới hạn số lượng/ngày, cài đặt riêng tư, v.v.). Thành viên vẫn đã được thêm vào group và có thể chat ngay.`;
          }
        }

        Swal.fire({
          icon: 'success',
          title: t('common.success'),
          html: successMessage.replace(/\n/g, '<br>'),
          showConfirmButton: true,
          confirmButtonText: 'OK',
        });

        emit('group-selected', updateResponse.data.data);
        close();
      }
    }
  } catch (error) {
    console.error('Error creating group:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || 'Failed to create group',
    });
  } finally {
    creating.value = false;
  }
};

// Load Zalo contacts for class (students + parents)
const loadContacts = async () => {
  if (!props.classData?.id || !props.accountId) return;

  loadingContacts.value = true;

  try {
    const response = await axios.get(`/api/classes/${props.classData.id}/zalo-contacts`, {
      params: {
        zalo_account_id: props.accountId,
      },
    });

    if (response.data.success) {
      contacts.value = response.data.data;
      console.log('[AddZaloGroupModal] Loaded contacts:', response.data);
    }
  } catch (error) {
    console.error('Error loading contacts:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || 'Failed to load contacts',
    });
  } finally {
    loadingContacts.value = false;
  }
};

// Toggle member selection
const toggleMember = (contact) => {
  const index = selectedMembers.value.findIndex(m => m.phone === contact.phone);
  if (index > -1) {
    selectedMembers.value.splice(index, 1);
  } else {
    selectedMembers.value.push(contact);
  }
};

// Check if member is selected
const isMemberSelected = (contact) => {
  return selectedMembers.value.some(m => m.phone === contact.phone);
};

const close = () => {
  // Reset forms
  searchForm.value = { name: '' };
  createForm.value = { name: '', members: [] };
  searchResults.value = [];
  searched.value = false;
  activeTab.value = 'search';
  contacts.value = [];
  selectedMembers.value = [];

  emit('close');
};

// Load contacts when create tab is activated
watch(() => activeTab.value, (newTab) => {
  if (newTab === 'create' && contacts.value.length === 0) {
    loadContacts();
  }
});
</script>
