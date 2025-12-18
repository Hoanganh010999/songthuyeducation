<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('zalo.friends') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ t('zalo.friends_subtitle') }}</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="loadFriends(false)"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ t('common.refresh') }}
        </button>
        <button
          @click="loadFriends(true)"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          :title="t('zalo.resync_from_zalo') || 'Resync from Zalo'"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ t('zalo.resync') || 'Resync' }}
        </button>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div v-if="loading" class="text-center py-12">
        <svg class="animate-spin h-8 w-8 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-gray-600">{{ t('common.loading') }}</p>
      </div>

      <div v-else-if="friends.length > 0" class="space-y-3">
        <div v-for="friend in friends" :key="friend.id" 
             class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
          <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden bg-blue-100">
            <img 
              v-if="friend.avatar_url" 
              :src="friend.avatar_url" 
              :alt="friend.name"
              class="w-full h-full object-cover"
              @error="handleImageError"
            />
            <svg v-else class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div class="flex-1">
            <p class="font-medium text-gray-900">{{ friend.name }}</p>
            <p class="text-sm text-gray-600">{{ friend.phone || 'N/A' }}</p>
          </div>
          <button
            @click="sendQuickMessage(friend)"
            class="px-4 py-2 text-sm text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50"
          >
            {{ t('zalo.send_message') }}
          </button>
        </div>
      </div>

      <div v-else class="text-center py-12 text-gray-500">
        <p>{{ t('zalo.no_friends_found') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject, watch } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import axios from 'axios';

const { t } = useI18n();
const zaloAccount = inject('zaloAccount', null);
const loading = ref(false);
const friends = ref([]);

const loadFriends = async (forceSync = false) => {
  console.log('📋 [ZaloFriends] Loading friends...', { forceSync });
  loading.value = true;
  try {
    const accountId = zaloAccount?.getAccountId?.();
    console.log('📋 [ZaloFriends] Account ID:', accountId);
    
    // Load from cache first (fast), then sync if needed
    console.log('📡 [ZaloFriends] Calling /api/zalo/friends...');
    const response = await axios.get('/api/zalo/friends', {
      params: {
        sync: forceSync,
        account_id: accountId
      }
    });
    
    console.log('📥 [ZaloFriends] Response received:', {
      success: response.data.success,
      cached: response.data.cached,
      count: response.data.data?.length || 0,
    });
    
    friends.value = response.data.data || [];
    
    // If no cached data, automatically sync in background
    if (response.data.cached === false && !forceSync && accountId) {
      console.log('🔄 [ZaloFriends] No cached data, syncing in background...');
      // Silently sync in background
      setTimeout(() => {
        loadFriends(true);
      }, 1000);
    }
  } catch (error) {
    console.error('❌ [ZaloFriends] Failed to load friends:', error);
    console.error('   Response:', error.response?.data);
    console.error('   Status:', error.response?.status);
    friends.value = [];
  } finally {
    loading.value = false;
    console.log('🏁 [ZaloFriends] Load completed');
  }
};

// Watch for account changes
if (zaloAccount) {
  watch(() => zaloAccount.activeAccountId?.value, () => {
    loadFriends();
  });
}

// Listen for reload events
onMounted(() => {
  loadFriends();
  
  window.addEventListener('zalo-reload-data', () => {
    loadFriends();
  });
});

const handleImageError = (event) => {
  // Hide broken image, show placeholder
  event.target.style.display = 'none';
};

const sendQuickMessage = (friend) => {
  // Implementation for quick message
  console.log('Send message to:', friend);
};

onMounted(() => {
  loadFriends();
});
</script>

