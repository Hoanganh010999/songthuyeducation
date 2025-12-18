<template>
  <div class="h-full flex flex-col overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
      <h3 class="font-semibold text-gray-900">{{ t('zalo.info') }}</h3>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-6 min-h-0">
      <!-- Avatar & Name -->
      <div class="text-center">
        <div class="relative inline-block">
          <div class="w-24 h-24 rounded-full mx-auto mb-3 overflow-hidden bg-gray-200">
            <img 
              v-if="item.avatar_url" 
              :src="item.avatar_url" 
              :alt="item.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center bg-blue-100">
              <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>
          <!-- Change Avatar Button (only for groups) -->
          <button
            v-if="itemType === 'groups'"
            @click="triggerAvatarUpload"
            class="absolute bottom-3 right-1/2 translate-x-1/2 translate-y-1/2 w-8 h-8 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 flex items-center justify-center"
            :title="t('zalo.change_group_avatar')"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>
          <!-- Hidden file input -->
          <input
            ref="avatarInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleAvatarChange"
          />
        </div>
        <h4 class="font-semibold text-gray-900">{{ item.name || item.displayName }}</h4>
        <p v-if="itemType === 'groups'" class="text-sm text-gray-500 mt-1">
          {{ groupMembers.length || item.members_count || 0 }} {{ t('zalo.members') }}
        </p>
      </div>

      <!-- Actions -->
      <div class="space-y-2">
        <button class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 rounded-lg">
          {{ t('zalo.turn_off_notifications') }}
        </button>
        <button v-if="itemType === 'groups'" class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 rounded-lg">
          {{ t('zalo.manage_group') }}
        </button>
      </div>

      <!-- Group Members (only for groups) -->
      <div v-if="itemType === 'groups'">
        <div class="flex items-center justify-between mb-3">
          <h5 class="font-medium text-gray-900">{{ t('zalo.group_members') }}</h5>
          <button
            @click="showAddMembersModal = true"
            class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700"
            :title="t('zalo.add_members')"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ t('zalo.add') }}
          </button>
        </div>
        <div v-if="loadingMembers" class="text-sm text-gray-500">
          {{ t('common.loading') }}...
        </div>
        <div v-else-if="groupMembers.length === 0" class="text-sm text-gray-500">
          {{ t('zalo.no_members') || 'No members' }}
        </div>
        <div v-else class="space-y-2">
          <div
            v-for="member in groupMembers"
            :key="member.id"
            class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50"
          >
            <div 
              class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 flex-shrink-0 cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
              @dblclick="openChatWithMember(member)"
              :title="t('zalo.double_click_to_chat') || 'Double-click to open chat'"
            >
              <img
                v-if="member.avatar_url"
                :src="member.avatar_url"
                :alt="member.display_name"
                class="w-full h-full object-cover"
              />
              <div
                v-else
                class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-600 font-semibold"
              >
                {{ (member.display_name || 'U').charAt(0).toUpperCase() }}
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ member.display_name }}</p>
              <p v-if="member.is_admin" class="text-xs text-blue-600">{{ t('zalo.admin') || 'Admin' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Group Assignment (for groups only) -->
      <ZaloGroupAssignment
        v-if="itemType === 'groups' && item.id"
        :group="item"
        @updated="handleAssignmentUpdated"
      />

      <!-- Conversation Assignment (Department + User for both, Branch only for conversations) -->
      <ZaloConversationAssignment
        v-if="item.id"
        :conversation="item"
        :item-type="itemType"
        @updated="handleAssignmentUpdated"
      />

      <!-- Media sections -->
      <div class="space-y-4">
        <!-- Photos/Videos -->
        <div>
          <h5 class="font-medium text-gray-900 mb-3">{{ t('zalo.photos_videos') }}</h5>
          <div v-if="loadingMedia" class="text-sm text-gray-500">
            {{ t('common.loading') }}...
          </div>
          <div v-else-if="mediaItems.photos.length === 0" class="text-sm text-gray-500">
            {{ t('zalo.no_photos_videos') }}
          </div>
          <div v-else class="grid grid-cols-3 gap-2">
            <img
              v-for="(photo, index) in mediaItems.photos.slice(0, 9)"
              :key="index"
              :src="photo.media_url"
              alt="Photo"
              class="w-full h-20 object-cover rounded-lg cursor-pointer hover:opacity-75"
              @click="openMedia(photo.media_url)"
            />
          </div>
        </div>

        <!-- Files -->
        <div>
          <h5 class="font-medium text-gray-900 mb-3">{{ t('zalo.files') }}</h5>
          <div v-if="loadingMedia" class="text-sm text-gray-500">
            {{ t('common.loading') }}...
          </div>
          <div v-else-if="mediaItems.files.length === 0" class="text-sm text-gray-500">
            {{ t('zalo.no_files') }}
          </div>
          <div v-else class="space-y-2">
            <a
              v-for="(file, index) in mediaItems.files"
              :key="index"
              :href="file.media_url"
              target="_blank"
              class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50"
            >
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="text-sm text-gray-700 truncate flex-1">{{ file.content || t('zalo.file') }}</span>
            </a>
          </div>
        </div>

        <!-- Links -->
        <div>
          <h5 class="font-medium text-gray-900 mb-3">{{ t('zalo.links') }}</h5>
          <div v-if="loadingMedia" class="text-sm text-gray-500">
            {{ t('common.loading') }}...
          </div>
          <div v-else-if="mediaItems.links.length === 0" class="text-sm text-gray-500">
            {{ t('zalo.no_links') }}
          </div>
          <div v-else class="space-y-2">
            <a
              v-for="(link, index) in mediaItems.links"
              :key="index"
              :href="link.media_url"
              target="_blank"
              class="block p-2 rounded-lg hover:bg-gray-50"
            >
              <p class="text-sm text-blue-600 truncate">{{ link.media_url }}</p>
              <p v-if="link.content" class="text-xs text-gray-500 mt-1 line-clamp-2">{{ link.content }}</p>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Members Modal -->
    <Teleport to="body">
      <div v-if="showAddMembersModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
          <div class="p-6 border-b">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-gray-900">{{ t('zalo.add_members_to_group') }}</h3>
              <button @click="closeAddMembersModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          
          <form @submit.prevent="handleAddMembers" class="flex-1 overflow-y-auto">
            <div class="p-6 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('zalo.select_members_to_add') }} <span class="text-red-500">*</span>
                  <span class="text-xs text-gray-500 ml-2">({{ addMembersForm.selectedMembers.length }} {{ t('zalo.selected') }})</span>
                </label>
                
                <!-- Search members -->
                <div class="relative mb-3">
                  <input
                    v-model="addMembersForm.searchQuery"
                    type="text"
                    :placeholder="t('zalo.search_friends')"
                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                  <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                
                <!-- Members list -->
                <div class="border border-gray-300 rounded-lg max-h-96 overflow-y-auto">
                  <div v-if="!addMembersForm.loadingFriends && addMembersForm.friends.length === 0" class="p-4 text-center text-gray-500">
                    {{ t('zalo.no_friends_to_add') }}
                  </div>
                  <div v-else-if="addMembersForm.loadingFriends" class="p-4 text-center text-gray-500">
                    <svg class="inline w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ t('zalo.loading_friends') }}
                  </div>
                  <label
                    v-for="friend in filteredFriendsForAddMembers"
                    :key="friend.id"
                    class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                  >
                    <input
                      type="checkbox"
                      :value="friend.id"
                      v-model="addMembersForm.selectedMembers"
                      :disabled="friend.isInGroup"
                      class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:opacity-50"
                    />
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 flex-shrink-0">
                      <img v-if="friend.avatar" :src="friend.avatar" :alt="friend.name" class="w-full h-full object-cover" />
                      <div v-else class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-600 font-semibold">
                        {{ (friend.name || 'U').charAt(0).toUpperCase() }}
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 truncate">
                        {{ friend.name || friend.displayName }}
                        <span v-if="friend.isInGroup" class="ml-2 text-xs text-gray-500">({{ t('zalo.already_in_group') }})</span>
                      </p>
                      <p class="text-xs text-gray-500 truncate">{{ friend.phone || friend.id }}</p>
                    </div>
                  </label>
                </div>
              </div>
            </div>
            
            <div class="p-6 border-t bg-gray-50">
              <div class="flex justify-end gap-3">
                <button
                  type="button"
                  @click="closeAddMembersModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                  {{ t('zalo.cancel') }}
                </button>
                <button
                  type="submit"
                  :disabled="addMembersForm.selectedMembers.length === 0 || addMembersForm.submitting"
                  class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 inline-flex items-center gap-2"
                >
                  <svg v-if="addMembersForm.submitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  {{ addMembersForm.submitting ? t('zalo.adding_members') : t('zalo.add_to_group') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, inject, computed } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useSwal } from '../../../composables/useSwal';
import axios from 'axios';
import ZaloConversationAssignment from './ZaloConversationAssignment.vue';
import ZaloGroupAssignment from './ZaloGroupAssignment.vue';

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  itemType: {
    type: String,
    required: true, // 'friends' or 'groups'
  },
});

const { t } = useI18n();
const zaloAccount = inject('zaloAccount', null);
const selectItem = inject('selectItem', null);

const emit = defineEmits(['assignment-updated']);

const loadingMedia = ref(false);
const loadingMembers = ref(false);
const groupMembers = ref([]);
const mediaItems = ref({
  photos: [],
  files: [],
  links: [],
});

// Add Members Modal state
const showAddMembersModal = ref(false);
const addMembersForm = ref({
  selectedMembers: [],
  searchQuery: '',
  friends: [],
  loadingFriends: false,
  submitting: false,
});

// Filtered friends for Add Members modal (exclude existing members)
const filteredFriendsForAddMembers = computed(() => {
  let friends = addMembersForm.value.friends;
  
  // Filter by search query
  if (addMembersForm.value.searchQuery) {
    const query = addMembersForm.value.searchQuery.toLowerCase();
    friends = friends.filter(friend =>
      (friend.name || friend.displayName || '').toLowerCase().includes(query)
    );
  }
  
  return friends;
});

// Load group members
const loadGroupMembers = async () => {
  if (props.itemType !== 'groups' || !props.item?.id) return;
  
  loadingMembers.value = true;
  try {
    const accountId = zaloAccount?.activeAccountId.value;
    if (!accountId) return;

    const response = await axios.get(`/api/zalo/groups/${props.item.id}/members`, {
      params: {
        account_id: accountId,
        sync: false, // Use cached data first
      },
    });

    if (response.data.success) {
      groupMembers.value = response.data.data || [];
      console.log('✅ [ZaloChatInfo] Group members loaded:', groupMembers.value.length);
    }
  } catch (error) {
    console.error('❌ [ZaloChatInfo] Failed to load group members:', error);
    groupMembers.value = [];
  } finally {
    loadingMembers.value = false;
  }
};

// Load media
const loadMedia = async () => {
  if (!props.item?.id) return;
  
  loadingMedia.value = true;
  try {
    const accountId = zaloAccount?.activeAccountId.value;
    if (!accountId) return;

    const response = await axios.get('/api/zalo/messages/media', {
      params: {
        account_id: accountId,
        recipient_id: props.item.id,
        recipient_type: props.itemType === 'groups' ? 'group' : 'user',
      },
    });

    if (response.data.success) {
      const data = response.data.data || {};
      mediaItems.value = {
        photos: (data.images || []).concat(data.videos || []),
        files: data.files || [],
        links: data.links || [],
      };
    }
  } catch (error) {
    console.error('Failed to load media:', error);
  } finally {
    loadingMedia.value = false;
  }
};

// Open media
const openMedia = (url) => {
  window.open(url, '_blank');
};

// Load friends for Add Members modal
const loadFriendsForAddMembers = async () => {
  const accountId = zaloAccount?.activeAccountId.value;
  if (!accountId) return;
  
  addMembersForm.value.loadingFriends = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = { account_id: accountId };
    if (branchId) params.branch_id = branchId;
    
    const response = await axios.get('/api/zalo/friends', { params });
    const allFriends = (response.data.data || []).map(friend => ({
      id: friend.id,
      name: friend.name || friend.display_name,
      displayName: friend.display_name || friend.name,
      avatar: friend.avatar_url || friend.avatar,
      phone: friend.phone || null,
      isInGroup: false,
    }));
    
    // Mark friends who are already in the group
    const existingMemberIds = groupMembers.value.map(m => m.id);
    addMembersForm.value.friends = allFriends.map(friend => ({
      ...friend,
      isInGroup: existingMemberIds.includes(friend.id),
    }));
  } catch (error) {
    console.error('Failed to load friends:', error);
    addMembersForm.value.friends = [];
  } finally {
    addMembersForm.value.loadingFriends = false;
  }
};

// Handle Add Members
const handleAddMembers = async () => {
  const accountId = zaloAccount?.activeAccountId.value;
  if (!accountId) {
    useSwal().fire({
      icon: 'warning',
      title: t('zalo.no_active_account'),
      text: t('zalo.please_select_account'),
    });
    return;
  }

  if (addMembersForm.value.selectedMembers.length === 0) {
    useSwal().fire({
      icon: 'warning',
      title: t('zalo.no_members_selected'),
      text: t('zalo.select_at_least_one_member_to_add'),
    });
    return;
  }
  
  addMembersForm.value.submitting = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = branchId ? { branch_id: branchId } : {};
    
    const response = await axios.post(`/api/zalo/groups/${props.item.id}/add-members`, {
      account_id: accountId,
      members: addMembersForm.value.selectedMembers,
      ...params
    });
    
    if (response.data.success) {
      const data = response.data.data;
      const successCount = data.successMembers?.length || 0;
      const errorCount = data.errorMembers?.length || 0;
      
      useSwal().fire({
        icon: 'success',
        title: t('zalo.members_added'),
        html: `${t('zalo.successfully_added_members', { count: successCount })}${errorCount > 0 ? `<br>${t('zalo.failed_members', { count: errorCount })}` : ''}`,
        timer: 3000,
      });
      
      // Reload group members
      await loadGroupMembers();
      
      // Close modal and reset form
      closeAddMembersModal();
    } else {
      throw new Error(response.data.message || 'Failed to add members');
    }
  } catch (error) {
    console.error('Failed to add members:', error);
    useSwal().fire({
      icon: 'error',
      title: t('zalo.failed'),
      text: error.response?.data?.message || error.message || t('zalo.add_members_failed'),
    });
  } finally {
    addMembersForm.value.submitting = false;
  }
};

// Close Add Members Modal
const closeAddMembersModal = () => {
  showAddMembersModal.value = false;
  addMembersForm.value.selectedMembers = [];
  addMembersForm.value.searchQuery = '';
  addMembersForm.value.friends = [];
};

// Avatar upload functionality
const avatarInput = ref(null);

// Trigger file input click
const triggerAvatarUpload = () => {
  avatarInput.value?.click();
};

// Handle avatar change
const handleAvatarChange = async (event) => {
  const file = event.target.files?.[0];
  if (!file) return;
  
  // Validate file type
  if (!file.type.startsWith('image/')) {
    useSwal().fire({
      icon: 'error',
      title: t('zalo.invalid_file'),
      text: t('zalo.please_select_image_file'),
    });
    return;
  }

  // Validate file size (max 5MB)
  if (file.size > 5 * 1024 * 1024) {
    useSwal().fire({
      icon: 'error',
      title: t('zalo.file_too_large'),
      text: t('zalo.image_size_limit'),
    });
    return;
  }
  
  // Confirm before uploading
  const result = await useSwal().fire({
    icon: 'question',
    title: t('zalo.change_group_avatar_confirm'),
    text: t('zalo.change_avatar_question'),
    showCancelButton: true,
    confirmButtonText: t('zalo.yes_change_it'),
    cancelButtonText: t('zalo.cancel'),
  });
  
  if (!result.isConfirmed) {
    // Reset file input
    event.target.value = '';
    return;
  }
  
  // Show loading
  useSwal().loading(t('zalo.uploading_avatar'));
  
  try {
    const accountId = zaloAccount?.activeAccountId.value;
    if (!accountId) {
      throw new Error('No active Zalo account');
    }
    
    // Create FormData
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('account_id', accountId);
    
    // Upload avatar
    const response = await axios.post(`/api/zalo/groups/${props.item.id}/change-avatar`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    
    if (response.data.success) {
      const newAvatarUrl = response.data.data?.avatar_url;
      
      useSwal().fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.avatar_changed_successfully'),
        timer: 2000,
      });
      
      console.log('✅ [ZaloChatInfo] New avatar URL:', newAvatarUrl);
      
      // Update avatar in current item (immediate UI update)
      if (props.item && newAvatarUrl) {
        props.item.avatar_url = newAvatarUrl;
      }
      
      // Emit event to parent to refresh group list
      const refreshEvent = new CustomEvent('refresh-group-list', {
        detail: {
          groupId: props.item.id,
          newAvatarUrl: newAvatarUrl,
        },
      });
      window.dispatchEvent(refreshEvent);
    } else {
      throw new Error(response.data.message || 'Failed to change avatar');
    }
  } catch (error) {
    console.error('❌ [ZaloChatInfo] Failed to change group avatar:', error);
    useSwal().fire({
      icon: 'error',
      title: t('zalo.failed'),
      text: error.response?.data?.message || error.message || t('zalo.change_avatar_failed'),
    });
  } finally {
    // Reset file input
    event.target.value = '';
  }
};

// Handle assignment updates
const handleAssignmentUpdated = (updatedData) => {
  console.log('[ZaloChatInfo] Assignment updated:', updatedData);

  // Emit event to parent (ZaloIndex) to update list items
  emit('assignment-updated', {
    itemId: props.item.id,
    updatedData: updatedData,
  });
};

// Open chat with member (double-click on avatar)
const openChatWithMember = async (member) => {
  console.log('🔵 [ZaloChatInfo] Opening chat with member:', member);
  
  if (!selectItem) {
    console.warn('⚠️ [ZaloChatInfo] selectItem function not available');
    return;
  }

  try {
    const accountId = zaloAccount?.activeAccountId.value;
    if (!accountId) {
      useSwal().fire({
        icon: 'warning',
        title: t('zalo.no_active_account'),
        text: t('zalo.please_select_account'),
      });
      return;
    }

    // Show loading indicator
    useSwal().loading(t('zalo.opening_chat') || 'Opening chat...');

    // Find or create conversation with this member
    const response = await axios.get('/api/zalo/history', {
      params: {
        account_id: accountId,
      },
    });

    if (response.data.success) {
      const conversations = response.data.data || [];
      
      // Find existing conversation with this user
      let conversation = conversations.find(
        conv => conv.id === member.id && conv.type === 'user'
      );

      // If no conversation exists, create a conversation object for this user
      if (!conversation) {
        conversation = {
          id: member.id,
          name: member.display_name || member.name,
          displayName: member.display_name || member.name,
          avatar_url: member.avatar_url,
          type: 'user',
          itemType: 'friends', // Important: set itemType to 'friends' for user conversations
          unread_count: 0,
          last_message: null,
          last_message_time: null,
        };
      } else {
        // Ensure itemType is set
        conversation.itemType = 'friends';
      }

      // Close the loading modal
      useSwal().close();

      // Switch to history tab to show conversation
      window.dispatchEvent(new CustomEvent('zalo-switch-to-history'));

      // Select the conversation
      selectItem(conversation);

      console.log('✅ [ZaloChatInfo] Chat opened with member:', member.display_name);
    } else {
      throw new Error(response.data.message || 'Failed to load conversations');
    }
  } catch (error) {
    console.error('❌ [ZaloChatInfo] Failed to open chat with member:', error);
    useSwal().fire({
      icon: 'error',
      title: t('zalo.failed'),
      text: error.response?.data?.message || error.message || t('zalo.failed_to_open_chat'),
    });
  }
};

// Watch showAddMembersModal to load friends when modal opens
watch(showAddMembersModal, (newValue) => {
  if (newValue) {
    loadFriendsForAddMembers();
  }
});

// Watch item changes
watch(() => props.item, () => {
  loadMedia();
  loadGroupMembers();
}, { immediate: true });

onMounted(() => {
  loadMedia();
  loadGroupMembers();
});
</script>

