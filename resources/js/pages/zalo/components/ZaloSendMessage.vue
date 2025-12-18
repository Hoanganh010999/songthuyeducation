<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">{{ t('zalo.send_message') }}</h1>
      <p class="mt-1 text-sm text-gray-600">{{ t('zalo.send_message_subtitle') }}</p>
    </div>

    <!-- Send Message Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <form @submit.prevent="sendMessage" class="space-y-6">
        <!-- Recipient Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('zalo.recipient_type') }}
          </label>
          <div class="flex gap-4">
            <label class="flex items-center gap-2">
              <input
                v-model="form.type"
                type="radio"
                value="user"
                class="w-4 h-4 text-blue-600"
              />
              <span>{{ t('zalo.user') }}</span>
            </label>
            <label class="flex items-center gap-2">
              <input
                v-model="form.type"
                type="radio"
                value="group"
                class="w-4 h-4 text-blue-600"
              />
              <span>{{ t('zalo.group') }}</span>
            </label>
          </div>
        </div>

        <!-- Recipient -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ form.type === 'user' ? t('zalo.phone_number') : t('zalo.group_id') }}
            <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.to"
            type="text"
            :placeholder="form.type === 'user' ? '0987654321' : 'group_id_here'"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <p class="mt-1 text-xs text-gray-500">
            {{ form.type === 'user' ? t('zalo.phone_hint') : t('zalo.group_hint') }}
          </p>
        </div>

        <!-- Message -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('zalo.message') }}
            <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.message"
            rows="6"
            required
            :placeholder="t('zalo.message_placeholder')"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          ></textarea>
          <div class="mt-1 flex items-center justify-between text-xs">
            <span class="text-gray-500">{{ form.message.length }} {{ t('zalo.characters') }}</span>
            <span :class="form.message.length > 1000 ? 'text-red-500' : 'text-gray-500'">
              {{ t('zalo.max_characters', { max: 1000 }) }}
            </span>
          </div>
        </div>

        <!-- Message Templates -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('zalo.quick_templates') }}
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
              v-for="template in templates"
              :key="template.key"
              type="button"
              @click="form.message = template.content"
              class="px-3 py-2 text-sm text-left border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ template.name }}
            </button>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3">
          <button
            type="submit"
            :disabled="sending || !form.to || !form.message"
            class="inline-flex items-center gap-2 px-6 py-2.5 text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="!sending" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ sending ? t('zalo.sending') : t('zalo.send_now') }}
          </button>

          <button
            type="button"
            @click="resetForm"
            class="px-6 py-2.5 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            {{ t('common.clear') }}
          </button>
        </div>

        <!-- Result Message -->
        <div v-if="result" class="p-4 rounded-lg" :class="result.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
          <div class="flex items-start gap-2">
            <svg v-if="result.success" class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg v-else class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="flex-1">
              <p class="font-medium">{{ result.message }}</p>
              <p v-if="result.details" class="text-sm mt-1 opacity-90">{{ result.details }}</p>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Recent Sent Messages -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('zalo.recent_sent') }}</h2>
      <div v-if="recentMessages.length > 0" class="space-y-3">
        <div v-for="msg in recentMessages" :key="msg.id" 
             class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg">
          <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900">{{ msg.to }}</p>
            <p class="text-sm text-gray-600 line-clamp-2">{{ msg.message }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ formatDate(msg.created_at) }}</p>
          </div>
          <span :class="msg.status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                class="px-2 py-1 text-xs font-medium rounded-full">
            {{ msg.status }}
          </span>
        </div>
      </div>
      <div v-else class="text-center py-8 text-gray-500">
        <p>{{ t('zalo.no_messages_yet') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useSwal } from '../../../composables/useSwal';
import axios from 'axios';

const { t } = useI18n();
const { Swal } = useSwal();

const form = ref({
  type: 'user',
  to: '',
  message: '',
});

const sending = ref(false);
const result = ref(null);
const recentMessages = ref([]);

const templates = ref([
  { key: 'homework', name: t('zalo.template_homework'), content: '📚 Bài tập mới đã được giao. Vui lòng kiểm tra hệ thống và nộp bài đúng hạn.' },
  { key: 'reminder', name: t('zalo.template_reminder'), content: '⏰ Nhắc nhở: Bài tập sắp hết hạn. Vui lòng hoàn thành và nộp bài sớm nhất.' },
  { key: 'class_cancel', name: t('zalo.template_class_cancel'), content: '⚠️ Thông báo: Lớp học hôm nay tạm nghỉ. Lịch học bù sẽ được thông báo sau.' },
  { key: 'congratulations', name: t('zalo.template_congratulations'), content: '🎉 Chúc mừng! Bạn đã hoàn thành xuất sắc bài kiểm tra.' },
]);

const sendMessage = async () => {
  sending.value = true;
  result.value = null;

  try {
    const response = await axios.post('/api/zalo/send', {
      to: form.value.to,
      message: form.value.message,
      type: form.value.type,
    });

    if (response.data.success) {
      result.value = {
        success: true,
        message: t('zalo.message_sent_success'),
      };

      Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.message_sent_success'),
        timer: 2000,
      });

      // Reload recent messages
      loadRecentMessages();
      
      // Clear form
      form.value.to = '';
      form.value.message = '';
    } else {
      throw new Error(response.data.message || 'Unknown error');
    }
  } catch (error) {
    console.error('Send message error:', error);
    result.value = {
      success: false,
      message: t('zalo.message_sent_failed'),
      details: error.response?.data?.message || error.message,
    };

    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message,
    });
  } finally {
    sending.value = false;
  }
};

const resetForm = () => {
  form.value = {
    type: 'user',
    to: '',
    message: '',
  };
  result.value = null;
};

const loadRecentMessages = async () => {
  try {
    const response = await axios.get('/api/zalo/history?limit=5');
    recentMessages.value = response.data.data || [];
  } catch (error) {
    console.error('Failed to load recent messages:', error);
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString();
};

onMounted(() => {
  loadRecentMessages();
});
</script>

