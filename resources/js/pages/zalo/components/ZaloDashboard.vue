<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">{{ t('zalo.dashboard') }}</h1>
      <p class="mt-1 text-sm text-gray-600">{{ t('zalo.dashboard_subtitle') }}</p>
    </div>

    <!-- Service Status -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ t('zalo.service_status') }}</h2>
        <button
          @click="checkStatus"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
          <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ t('common.refresh') }}
        </button>
      </div>

      <div v-if="status" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Service Online Status -->
        <div class="p-4 rounded-lg" :class="status.isReady ? 'bg-green-50' : 'bg-red-50'">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full flex items-center justify-center" 
                 :class="status.isReady ? 'bg-green-100' : 'bg-red-100'">
              <svg v-if="status.isReady" class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <svg v-else class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">{{ t('zalo.service') }}</p>
              <p class="text-lg font-bold" :class="status.isReady ? 'text-green-600' : 'text-red-600'">
                {{ status.isReady ? t('zalo.online') : t('zalo.offline') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Total Messages Sent (Today) -->
        <div class="p-4 rounded-lg bg-blue-50">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">{{ t('zalo.messages_today') }}</p>
              <p class="text-lg font-bold text-blue-600">{{ stats.messagesToday }}</p>
            </div>
          </div>
        </div>

        <!-- Total Friends -->
        <div class="p-4 rounded-lg bg-purple-50">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">{{ t('zalo.total_friends') }}</p>
              <p class="text-lg font-bold text-purple-600">{{ stats.totalFriends }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-8 text-gray-500">
        <p>{{ t('zalo.loading_status') }}</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('zalo.quick_actions') }}</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <button
          @click="$emit('changeTab', 'send_message')"
          class="p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors text-left group"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 group-hover:bg-blue-200 flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ t('zalo.send_message') }}</p>
              <p class="text-sm text-gray-600">{{ t('zalo.send_single_desc') }}</p>
            </div>
          </div>
        </button>

        <button
          @click="$emit('changeTab', 'send_bulk')"
          class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors text-left group"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-green-100 group-hover:bg-green-200 flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
              </svg>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ t('zalo.send_bulk') }}</p>
              <p class="text-sm text-gray-600">{{ t('zalo.send_bulk_desc') }}</p>
            </div>
          </div>
        </button>

        <button
          @click="$emit('changeTab', 'friends')"
          class="p-4 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-colors text-left group"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-purple-100 group-hover:bg-purple-200 flex items-center justify-center">
              <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ t('zalo.friends') }}</p>
              <p class="text-sm text-gray-600">{{ t('zalo.friends_desc') }}</p>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('zalo.recent_activity') }}</h2>
      <div v-if="recentMessages.length > 0" class="space-y-3">
        <div v-for="msg in recentMessages" :key="msg.id" 
             class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50">
          <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900">{{ msg.recipient }}</p>
            <p class="text-sm text-gray-600 truncate">{{ msg.message }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ msg.sent_at }}</p>
          </div>
          <span :class="msg.status === 'success' ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">
            {{ msg.status === 'success' ? '✓ Sent' : '✗ Failed' }}
          </span>
        </div>
      </div>
      <div v-else class="text-center py-8 text-gray-500">
        <p>{{ t('zalo.no_recent_activity') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import axios from 'axios';

const { t } = useI18n();
const loading = ref(false);
const status = ref(null);
const stats = ref({
  messagesToday: 0,
  totalFriends: 0,
});
const recentMessages = ref([]);

defineEmits(['changeTab']);

const checkStatus = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/zalo/status');
    status.value = response.data;
    
    // Get stats
    const statsResponse = await axios.get('/api/zalo/stats');
    stats.value = statsResponse.data;

    // Get recent messages
    const historyResponse = await axios.get('/api/zalo/history?limit=5');
    recentMessages.value = historyResponse.data.data || [];
  } catch (error) {
    console.error('Failed to check Zalo status:', error);
    status.value = { isReady: false };
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  checkStatus();
});
</script>

