<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
    @click.self="close"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl h-[80vh] flex flex-col">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-3">
          <!-- Avatar - Show Zalo avatar if found, otherwise default icon -->
          <div v-if="zaloAccountStatus?.avatar" class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0">
            <img :src="zaloAccountStatus.avatar" :alt="zaloAccountStatus.display_name || customer.name" class="w-full h-full object-cover" />
          </div>
          <div v-else class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div>
            <!-- Name - Show Zalo display name if found, otherwise customer name -->
            <h3
              class="font-semibold text-gray-900 cursor-pointer hover:text-blue-600 transition-colors"
              @click="showProfileModal = true"
            >
              {{ zaloAccountStatus?.display_name || customer.name }}
            </h3>
            <p class="text-sm text-gray-500">{{ customer.phone || t('common.no_phone') }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <!-- Session Expired Warning -->
          <div v-if="sessionExpired" class="flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Session hết hạn
          </div>
          <!-- Account Status -->
          <div v-else-if="checkingAccount" class="text-sm text-gray-500">
            {{ t('zalo.checking_account') }}...
          </div>
          <div v-else-if="zaloAccountStatus">
            <span
              v-if="zaloAccountStatus.hasAccount && zaloAccountStatus.isFriend"
              class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700"
            >
              {{ t('zalo.friend') }}
            </span>
            <button
              v-else-if="zaloAccountStatus.hasAccount && !zaloAccountStatus.isFriend"
              @click="sendFriendRequest"
              :disabled="sendingFriendRequest"
              class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 disabled:opacity-50"
            >
              {{ sendingFriendRequest ? t('zalo.sending') : t('zalo.send_friend_request') }}
            </button>
            <span
              v-else
              class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700"
            >
              {{ t('zalo.no_zalo_account') }}
            </span>
          </div>
          <button @click="close" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Messages area -->
      <div ref="messagesContainer" class="flex-1 overflow-y-auto bg-gray-50 px-6 py-4 space-y-4 min-h-0">
        <div v-if="loadingMessages" class="text-center py-8 text-gray-500">
          {{ t('common.loading') }}...
        </div>
        <div v-else-if="messages.length === 0" class="text-center py-8 text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <p>{{ t('zalo.no_messages') }}</p>
          <p class="text-xs text-gray-400 mt-2">{{ t('zalo.start_conversation_hint') }}</p>
        </div>
        <template v-else>
          <div
            v-for="message in messages"
            :key="message.id"
            class="flex gap-2"
            :class="message.type === 'sent' ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-md relative flex flex-col"
              :class="message.type === 'sent' ? 'items-end' : 'items-start'"
            >
              <!-- Text message -->
              <div
                v-if="!message.content_type || message.content_type === 'text'"
                class="px-4 py-2 rounded-lg"
                :class="message.type === 'sent'
                  ? 'bg-blue-600 text-white'
                  : 'bg-white text-gray-900 border border-gray-200'"
              >
                <div
                  v-if="isRichText(message.content)"
                  class="text-sm zalo-rich-text"
                  v-html="formatMessageContent(message.content)"
                ></div>
                <div v-else class="text-sm whitespace-pre-wrap">{{ message.content }}</div>
              </div>

              <!-- Sticker message -->
              <div
                v-else-if="message.content_type === 'sticker'"
                class="inline-block"
              >
                <div v-if="message.metadata?.sticker?.stickerUrl || message.metadata?.sticker?.stickerWebpUrl || message.media_url" class="sticker-container">
                  <img
                    :src="message.metadata?.sticker?.stickerUrl || message.metadata?.sticker?.stickerWebpUrl || message.media_url"
                    :alt="message.metadata?.sticker?.text || message.content || 'Sticker'"
                    class="max-w-[150px] max-h-[150px] object-contain"
                  />
                </div>
                <div v-else class="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg text-gray-600 text-sm">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ message.content || 'Sticker' }}</span>
                </div>
              </div>

              <!-- Image message -->
              <div
                v-else-if="message.content_type === 'image' && message.media_url"
                class="inline-block"
              >
                <img
                  :src="message.media_url"
                  :alt="message.content || 'Image'"
                  class="max-w-sm rounded-lg"
                />
              </div>

              <!-- File message -->
              <div
                v-else-if="message.content_type === 'file' && (message.media_url || message.metadata?.file?.href)"
                class="mt-2"
              >
                <a
                  :href="message.media_url || message.metadata?.file?.href"
                  :download="message.metadata?.file?.title || message.content || 'file'"
                  target="_blank"
                  rel="noreferrer noopener"
                  class="flex items-center gap-3 p-3 rounded-lg border transition-all hover:bg-gray-50"
                  :class="message.type === 'sent' ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200'"
                >
                  <!-- File icon -->
                  <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded bg-blue-500 text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                  </div>

                  <!-- File info -->
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate" :class="message.type === 'sent' ? 'text-blue-900' : 'text-gray-900'">
                      {{ message.metadata?.file?.title || message.content || 'Document' }}
                    </p>
                    <p class="text-xs" :class="message.type === 'sent' ? 'text-blue-600' : 'text-gray-500'">
                      {{ t('zalo.click_to_download') }}
                    </p>
                  </div>

                  <!-- Download icon -->
                  <div class="flex-shrink-0">
                    <svg class="w-5 h-5" :class="message.type === 'sent' ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                  </div>
                </a>
              </div>

              <!-- Other content types -->
              <div
                v-else
                class="px-4 py-2 rounded-lg"
                :class="message.type === 'sent'
                  ? 'bg-blue-600 text-white'
                  : 'bg-white text-gray-900 border border-gray-200'"
              >
                <div
                  v-if="isRichText(message.content)"
                  class="text-sm zalo-rich-text"
                  v-html="formatMessageContent(message.content)"
                ></div>
                <div v-else class="text-sm whitespace-pre-wrap">{{ message.content }}</div>
              </div>

              <p class="text-xs text-gray-500 mt-1">
                {{ formatTime(message.sent_at || message.created_at) }}
              </p>
            </div>
          </div>
        </template>
      </div>

      <!-- Input area -->
      <div class="px-6 py-4 border-t border-gray-200 bg-white flex-shrink-0">
        <!-- Session expired warning -->
        <div
          v-if="sessionExpired"
          class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800"
        >
          <div class="flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
              <p class="font-medium">Phiên đăng nhập Zalo đã hết hạn</p>
              <p class="text-xs mt-1">Vui lòng vào <strong>Quản lý Zalo</strong> và đăng nhập lại tài khoản để tiếp tục sử dụng.</p>
            </div>
          </div>
        </div>

        <!-- Warning message if no account or no friend -->
        <div
          v-else-if="!zaloAccountStatus?.hasAccount"
          class="mb-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800"
        >
          <div class="flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
              <p class="font-medium">{{ t('zalo.no_zalo_account') }}</p>
              <p class="text-xs mt-1">{{ t('zalo.customer_no_zalo_hint') }}</p>
            </div>
          </div>
        </div>

        <div
          v-else-if="zaloAccountStatus?.hasAccount && !zaloAccountStatus?.isFriend"
          class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800"
        >
          <div class="flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <p class="font-medium">{{ t('zalo.not_friend_yet') }}</p>
              <p class="text-xs mt-1">{{ t('zalo.send_friend_request_hint') }}</p>
            </div>
          </div>
        </div>

        <!-- Message Input -->
        <div class="flex items-end gap-2">
          <!-- File upload button -->
          <label class="p-2 text-gray-600 hover:text-gray-900 cursor-pointer">
            <input type="file" ref="fileInput" @change="handleFileSelect" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
          </label>
          <!-- Image upload button -->
          <label class="p-2 text-gray-600 hover:text-gray-900 cursor-pointer">
            <input type="file" ref="imageInput" @change="handleImageSelect" class="hidden" accept="image/*">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </label>
          <div class="flex-1">
            <textarea
              v-model="messageText"
              @keydown.enter.exact.prevent="sendMessage"
              @keydown.enter.shift.exact="messageText += '\n'"
              :placeholder="zaloAccountStatus?.isFriend ? t('zalo.type_message') : t('zalo.will_create_conversation')"
              :disabled="!selectedAccountId || !customer.phone"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none disabled:bg-gray-100 disabled:cursor-not-allowed"
              style="min-height: 48px; max-height: 120px;"
            ></textarea>
          </div>
          <button
            @click="sendMessage"
            :disabled="(!messageText.trim() && !selectedFile && !selectedImage) || sending || uploading || !selectedAccountId || !customer.phone"
            class="p-3 rounded-lg transition-colors"
            :class="(messageText.trim() || selectedFile || selectedImage) && selectedAccountId && customer.phone && !sending && !uploading
              ? 'bg-blue-600 text-white hover:bg-blue-700'
              : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </div>
        <!-- Preview uploaded file/image -->
        <div v-if="uploading || selectedFile || selectedImage" class="mt-2 flex items-center gap-2">
          <div v-if="selectedFile" class="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span class="text-sm text-gray-700">{{ selectedFile.name }}</span>
            <button @click="clearFile" class="text-gray-500 hover:text-gray-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div v-if="selectedImage" class="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg">
            <img :src="selectedImagePreview" class="w-10 h-10 object-cover rounded" alt="Preview">
            <span class="text-sm text-gray-700">{{ selectedImage.name }}</span>
            <button @click="clearImage" class="text-gray-500 hover:text-gray-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div v-if="uploading" class="text-sm text-gray-500">
            {{ t('common.uploading') }}...
          </div>
        </div>
        <p v-if="!customer.phone" class="text-xs text-red-500 mt-2">
          {{ t('zalo.customer_no_phone') }}
        </p>
      </div>
    </div>

    <!-- Customer Profile Modal -->
    <CustomerProfileModal
      :show="showProfileModal"
      :customer="customer"
      :zalo-avatar="zaloAccountStatus?.avatar"
      :zalo-user-id="zaloAccountStatus?.zalo_user_id"
      :account-id="selectedAccountId"
      @close="showProfileModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import { useAuthStore } from '../../stores/auth';
import { useZaloSocket } from '../../composables/useZaloSocket';
import axios from 'axios';
import CustomerProfileModal from './CustomerProfileModal.vue';
import { isRichTextFormat, getHtmlContent, getPlainText } from '../../utils/zaloRichTextParser';

const { t } = useI18n();
const swal = useSwal();
const authStore = useAuthStore();
const zaloSocket = useZaloSocket();

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  customer: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close', 'interaction-saved']);

const messageText = ref('');
const messages = ref([]);
const loadingMessages = ref(false);
const sending = ref(false);
const uploading = ref(false);
const messagesContainer = ref(null);
const fileInput = ref(null);
const imageInput = ref(null);
const selectedFile = ref(null);
const selectedImage = ref(null);
const selectedImagePreview = ref(null);

// Zalo account management
const zaloAccounts = ref([]);
const selectedAccountId = ref(null);
const checkingAccount = ref(false);
const zaloAccountStatus = ref(null);
const sendingFriendRequest = ref(false);

// Customer profile modal
const showProfileModal = ref(false);

// Load primary Zalo account only
const loadZaloAccounts = async () => {
  try {
    const response = await axios.get('/api/zalo/accounts', {
      params: {
        branch_id: localStorage.getItem('selected_branch_id'),
        status: 'active',
        is_primary: true, // Only load primary account
      },
    });

    if (response.data.success) {
      zaloAccounts.value = response.data.data || [];
      if (zaloAccounts.value.length > 0) {
        selectedAccountId.value = zaloAccounts.value[0].id;
      }
    }
  } catch (error) {
    console.error('Failed to load Zalo accounts:', error);
  }
};

// Session expired state
const sessionExpired = ref(false);
const sessionExpiredMessage = ref('');

// Check if customer phone has Zalo account
const checkZaloAccount = async () => {
  if (!props.customer.phone || !selectedAccountId.value) return;

  checkingAccount.value = true;
  sessionExpired.value = false;
  try {
    console.log('🔍 [CustomerZaloChat] Checking phone:', props.customer.phone);

    const response = await axios.post('/api/zalo/check-phone', {
      account_id: selectedAccountId.value,
      phone: props.customer.phone,
    });

    console.log('📞 [CustomerZaloChat] Check phone response:', response.data);

    if (response.data.success) {
      zaloAccountStatus.value = response.data.data;
      console.log('✅ [CustomerZaloChat] Zalo account status:', zaloAccountStatus.value);
    }
  } catch (error) {
    console.error('❌ [CustomerZaloChat] Failed to check Zalo account:', error);

    // Check for session expired error
    if (error.response?.status === 401 && error.response?.data?.error_code === 'SESSION_EXPIRED') {
      sessionExpired.value = true;
      sessionExpiredMessage.value = error.response.data.message || 'Phiên đăng nhập Zalo đã hết hạn';
      swal.fire({
        icon: 'warning',
        title: 'Phiên Zalo hết hạn',
        html: `<div class="text-left">
          <p class="mb-2">${sessionExpiredMessage.value}</p>
          <p class="text-sm text-gray-600">Để khắc phục:</p>
          <ol class="text-sm text-gray-600 list-decimal ml-4 mt-1">
            <li>Vào menu <strong>Quản lý Zalo</strong></li>
            <li>Chọn tài khoản bị hết hạn</li>
            <li>Đăng nhập lại bằng QR Code</li>
          </ol>
        </div>`,
        confirmButtonText: 'Đã hiểu',
      });
    }

    zaloAccountStatus.value = {
      hasAccount: false,
      isFriend: false,
    };
  } finally {
    checkingAccount.value = false;
  }
};

// Send friend request
const sendFriendRequest = async () => {
  if (!zaloAccountStatus.value?.zalo_user_id || !selectedAccountId.value) return;

  sendingFriendRequest.value = true;
  try {
    const response = await axios.post('/api/zalo/friends/send-request', {
      account_id: selectedAccountId.value,
      user_id: zaloAccountStatus.value.zalo_user_id,
    });

    if (response.data.success) {
      swal.success(t('zalo.friend_request_sent'));
      // Recheck account status
      await checkZaloAccount();
    } else {
      throw new Error(response.data.message);
    }
  } catch (error) {
    console.error('Failed to send friend request:', error);
    swal.error(error.response?.data?.message || t('common.error_occurred'));
  } finally {
    sendingFriendRequest.value = false;
  }
};

// Load messages (if conversation exists)
const loadMessages = async () => {
  if (!props.customer.phone || !selectedAccountId.value) return;

  loadingMessages.value = true;
  try {
    // First, check if conversation exists for this phone number
    if (!zaloAccountStatus.value?.zalo_user_id) {
      console.log('⚠️ [CustomerZaloChat] No zalo_user_id yet, skipping message load');
      messages.value = [];
      loadingMessages.value = false;
      return;
    }

    // Load messages by recipient_id (zalo_user_id)
    const response = await axios.get('/api/zalo/messages', {
      params: {
        account_id: selectedAccountId.value,
        recipient_id: zaloAccountStatus.value.zalo_user_id,
        recipient_type: 'user',
      },
    });

    if (response.data.success) {
      messages.value = response.data.data || [];

      console.log('📩 [CustomerZaloChat] Loaded messages:', messages.value.length);

      // Ensure scroll to bottom after messages are rendered
      await nextTick();
      setTimeout(() => {
        scrollToBottom();
      }, 100);
    }
  } catch (error) {
    console.error('Failed to load messages:', error);
    // Not showing error - conversation might not exist yet
    messages.value = [];
  } finally {
    loadingMessages.value = false;
  }
};

// Mark conversation as read
const markAsRead = async () => {
  if (!selectedAccountId.value || !zaloAccountStatus.value?.zalo_user_id) return;

  try {
    await axios.post('/api/zalo/mark-as-read', {
      account_id: selectedAccountId.value,
      recipient_id: zaloAccountStatus.value.zalo_user_id
    });

    console.log('✅ [CustomerZaloChatModal] Marked conversation as read');
  } catch (error) {
    console.error('❌ [CustomerZaloChatModal] Error marking as read:', error);
  }
};

// Save customer interaction to work history
const saveCustomerInteraction = async (messageContent, messageType = 'text') => {
  if (!props.customer?.id) return;

  try {
    const notes = messageType === 'text'
      ? messageContent
      : messageType === 'image'
        ? `[Hình ảnh] ${messageContent || ''}`
        : `[File] ${messageContent || ''}`;

    await axios.post(`/api/customers/${props.customer.id}/interactions`, {
      interaction_type_id: 5, // Zalo
      interaction_result_id: 1, // Thành công
      notes: notes.substring(0, 500), // Limit to 500 chars
      interaction_date: new Date().toISOString(),
      metadata: {
        zalo_account_id: selectedAccountId.value,
        zalo_user_id: zaloAccountStatus.value?.zalo_user_id,
        message_type: messageType,
        sent_from: 'customer_zalo_chat_modal'
      }
    });

    console.log('✅ [CustomerZaloChatModal] Saved customer interaction');

    // Emit event to notify parent component to refresh customer data
    emit('interaction-saved');
  } catch (error) {
    console.error('❌ [CustomerZaloChatModal] Error saving interaction:', error);
    // Don't show error to user - this is a background operation
  }
};

// Send message (simple approach like ZaloChatView)
const sendMessage = async () => {
  if ((!messageText.value.trim() && !selectedFile.value && !selectedImage.value) || sending.value || uploading.value || !selectedAccountId.value || !props.customer.phone) return;

  // Make sure we have zalo_user_id
  if (!zaloAccountStatus.value?.zalo_user_id) {
    console.log('⚠️ [CustomerZaloChat] No zalo_user_id, checking account...');
    await checkZaloAccount();

    if (!zaloAccountStatus.value?.zalo_user_id) {
      console.error('❌ [CustomerZaloChat] Still no zalo_user_id after check');
      swal.error(t('zalo.customer_no_zalo_hint'));
      return;
    }
  }

  // If there's a selected image, upload and send it with optional text
  if (selectedImage.value) {
    const text = messageText.value.trim();
    messageText.value = '';
    await uploadImage(selectedImage.value, text);
    return;
  }

  // If there's a selected file, upload and send it with optional text
  if (selectedFile.value) {
    const text = messageText.value.trim();
    messageText.value = '';
    await uploadFile(selectedFile.value, text);
    return;
  }

  // Send text message only
  const text = messageText.value.trim();
  messageText.value = '';
  sending.value = true;

  try {
    console.log('📤 [CustomerZaloChat] Preparing to send message');
    console.log('   Current zaloAccountStatus:', zaloAccountStatus.value);
    console.log('✅ [CustomerZaloChat] Has zalo_user_id:', zaloAccountStatus.value.zalo_user_id);

    // Send message using simple endpoint (like ZaloChatView)
    const response = await axios.post('/api/zalo/messages/send', {
      account_id: selectedAccountId.value,
      recipient_id: zaloAccountStatus.value.zalo_user_id,
      recipient_type: 'user',
      message: text,
    });

    if (response.data.success) {
      // WebSocket will automatically add the message via handleNewMessage()
      // No need to reload - this prevents the "blink" effect

      // Save to customer interaction history
      saveCustomerInteraction(text, 'text');

      swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.message_sent'),
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
      });
    } else {
      throw new Error(response.data.message);
    }
  } catch (error) {
    console.error('Failed to send message:', error);
    messageText.value = text; // Restore message
    swal.error(error.response?.data?.message || t('common.error_occurred'));
  } finally {
    sending.value = false;
  }
};

// Scroll to bottom
const scrollToBottom = (smooth = false) => {
  if (messagesContainer.value) {
    if (smooth) {
      messagesContainer.value.scrollTo({
        top: messagesContainer.value.scrollHeight,
        behavior: 'smooth'
      });
    } else {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  }
};

// Handle file select
const handleFileSelect = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  selectedFile.value = file;
  selectedImage.value = null;
  selectedImagePreview.value = null;
};

// Handle image select
const handleImageSelect = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    selectedImagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);

  selectedImage.value = file;
  selectedFile.value = null;
};

// Upload file
const uploadFile = async (file, textMessage = '') => {
  if (!props.customer.phone || !selectedAccountId.value) return;

  uploading.value = true;
  try {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('account_id', selectedAccountId.value);
    formData.append('recipient_id', zaloAccountStatus.value.zalo_user_id);
    formData.append('recipient_type', 'user');

    const uploadResponse = await axios.post('/api/zalo/messages/upload-file', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (uploadResponse.data.success) {
      const response = await axios.post('/api/zalo/messages/send', {
        account_id: selectedAccountId.value,
        recipient_id: zaloAccountStatus.value.zalo_user_id,
        recipient_type: 'user',
        message: textMessage || file.name,
        media_url: uploadResponse.data.data.url,
        media_path: uploadResponse.data.data.absolute_path,
        content_type: 'file',
      });

      if (response.data.success) {
        // WebSocket will automatically add the message via handleNewMessage()
        clearFile();

        // Save to customer interaction history
        saveCustomerInteraction(file.name, 'file');

        swal.fire({
          icon: 'success',
          title: t('common.success'),
          text: t('zalo.file_sent') || 'File sent successfully',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 2000,
        });
      }
    }
  } catch (error) {
    console.error('Failed to upload file:', error);
    swal.error(error.response?.data?.message || t('common.error_occurred'));
    clearFile();
  } finally {
    uploading.value = false;
  }
};

// Upload image
const uploadImage = async (file, textMessage = '') => {
  if (!props.customer.phone || !selectedAccountId.value) return;

  uploading.value = true;
  try {
    const formData = new FormData();
    formData.append('image', file, file.name);
    formData.append('account_id', selectedAccountId.value);
    formData.append('recipient_id', zaloAccountStatus.value.zalo_user_id);
    formData.append('recipient_type', 'user');

    const uploadResponse = await axios.post('/api/zalo/messages/upload-image', formData, {
      headers: { 'Accept': 'application/json' },
      maxContentLength: Infinity,
      maxBodyLength: Infinity,
      timeout: 120000,
    });

    if (uploadResponse.data.success && uploadResponse.data.data?.url) {
      const response = await axios.post('/api/zalo/messages/send', {
        account_id: selectedAccountId.value,
        recipient_id: zaloAccountStatus.value.zalo_user_id,
        recipient_type: 'user',
        message: textMessage || '',
        media_url: uploadResponse.data.data.url,
        media_path: uploadResponse.data.data.absolute_path,
        content_type: 'image',
      });

      if (response.data.success) {
        // WebSocket will automatically add the message via handleNewMessage()
        clearImage();

        // Save to customer interaction history
        saveCustomerInteraction(textMessage || image.name, 'image');

        swal.fire({
          icon: 'success',
          title: t('common.success'),
          text: t('zalo.image_sent') || 'Image sent successfully',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 2000,
        });
      }
    }
  } catch (error) {
    console.error('Failed to upload image:', error);
    swal.error(error.response?.data?.message || t('common.error_occurred'));
    clearImage();
  } finally {
    uploading.value = false;
  }
};

// Clear file
const clearFile = () => {
  selectedFile.value = null;
  if (fileInput.value) fileInput.value.value = '';
};

// Clear image
const clearImage = () => {
  selectedImage.value = null;
  selectedImagePreview.value = null;
  if (imageInput.value) imageInput.value.value = '';
};

// Format message content (handle rich text)
const formatMessageContent = (content) => {
  if (!content) return '';

  // Check if content is rich text format
  if (isRichTextFormat(content)) {
    return getHtmlContent(content);
  }

  return content;
};

// Check if message is rich text
const isRichText = (content) => {
  return content && isRichTextFormat(content);
};

// Format time
const formatTime = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();

  if (date.toDateString() === now.toDateString()) {
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
  }
  return date.toLocaleString('vi-VN');
};

// Close modal
const close = () => {
  emit('close');
};

// Watch for account selection change
watch(() => selectedAccountId.value, async () => {
  if (selectedAccountId.value && props.customer.phone) {
    await checkZaloAccount();
    await loadMessages();
  }
});

// Watch for modal show
watch(() => props.show, async (newVal) => {
  if (newVal) {
    await loadZaloAccounts();
    if (props.customer.phone && selectedAccountId.value) {
      await nextTick();
      await checkZaloAccount();
      await loadMessages();

      // Mark conversation as read after loading messages
      if (messages.value.length > 0) {
        await markAsRead();
      }

      // Setup WebSocket listener for new messages
      setupWebSocketListener();

      // Ensure scroll to bottom after everything is loaded
      await nextTick();
      setTimeout(() => {
        scrollToBottom();
      }, 300);
    }
  } else {
    // Reset state when modal closes
    messages.value = [];
    messageText.value = '';
    zaloAccountStatus.value = null;
    sessionExpired.value = false;
    sessionExpiredMessage.value = '';

    // Remove WebSocket listener
    cleanupWebSocketListener();
  }
});

// WebSocket message handler
const handleNewMessage = (data) => {
  console.log('[CustomerZaloChatModal] 📩 New message received:', data);
  
  // Check if message is for this conversation
  if (data.account_id === selectedAccountId.value && 
      data.recipient_id === zaloAccountStatus.value?.zalo_user_id) {
    
    // Get the full message object from data.message (NOT data itself)
    const newMessage = data.message;
    
    if (!newMessage) {
      console.warn('[CustomerZaloChatModal] ⚠️ No message object in data:', data);
      return;
    }
    
    // Add message to list if not already exists
    const exists = messages.value.some(m => 
      m.id === newMessage.id || m.message_id === newMessage.message_id
    );
    
    if (!exists) {
      console.log('[CustomerZaloChatModal] ✅ Adding new message to UI:', newMessage.id);
      messages.value.push(newMessage);
      
      // Scroll to bottom
      nextTick(() => scrollToBottom(true));
    } else {
      console.log('[CustomerZaloChatModal] ⚠️ Message already exists, skipping');
    }
  }
};

// Handle conversation updates (contains full message with all fields)
const handleConversationUpdate = (data) => {
  console.log('[CustomerZaloChatModal] 📬 Conversation updated:', data);
  
  // Check if update is for this conversation
  if (data.account_id === selectedAccountId.value && 
      data.recipient_id === zaloAccountStatus.value?.zalo_user_id) {
    
    // Don't reload messages - handleNewMessage already added it
    // This prevents the "blink" effect
    console.log('[CustomerZaloChatModal] ℹ️ Conversation updated, but message already added via handleNewMessage');
  }
};

const setupWebSocketListener = () => {
  if (!selectedAccountId.value || !zaloAccountStatus.value?.zalo_user_id) return;
  
  console.log('[CustomerZaloChatModal] 🔌 Setting up WebSocket listener for account:', selectedAccountId.value);
  console.log('[CustomerZaloChatModal] 🎯 Recipient ID:', zaloAccountStatus.value.zalo_user_id);
  
  // Connect to WebSocket first (if not already connected)
  zaloSocket.connect();
  
  // Wait for connection before joining rooms
  const checkConnectionAndJoin = () => {
    if (zaloSocket.isConnected.value) {
      console.log('[CustomerZaloChatModal] ✅ WebSocket connected, joining rooms...');
      
      // Join account room
      zaloSocket.joinAccount(selectedAccountId.value);
      
      // Join conversation room for this specific recipient
      zaloSocket.joinConversation(selectedAccountId.value, zaloAccountStatus.value.zalo_user_id);
    } else {
      console.log('[CustomerZaloChatModal] ⏳ Waiting for WebSocket connection...');
      setTimeout(checkConnectionAndJoin, 100);
    }
  };
  
  checkConnectionAndJoin();
  
  // Listen for new messages (using useZaloSocket helper)
  zaloSocket.onMessage(handleNewMessage);
  
  // Listen for conversation updates
  zaloSocket.onConversationUpdate(handleConversationUpdate);
};

const cleanupWebSocketListener = () => {
  console.log('[CustomerZaloChatModal] 🔌 Cleaning up WebSocket listener');
  
  // Leave rooms if needed
  if (selectedAccountId.value) {
    zaloSocket.leaveAccount(selectedAccountId.value);
    
    if (zaloAccountStatus.value?.zalo_user_id) {
      zaloSocket.leaveConversation(selectedAccountId.value, zaloAccountStatus.value.zalo_user_id);
    }
  }
  
  // Note: We don't need to manually off() listeners because useZaloSocket.onMessage() 
  // and onConversationUpdate() return cleanup functions that are automatically called
};

onMounted(() => {
  if (props.show) {
    loadZaloAccounts();
    if (props.customer.phone) {
      checkZaloAccount();
      loadMessages();
    }
  }
});

onUnmounted(() => {
  cleanupWebSocketListener();
});
</script>
