<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('zalo.groups') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ t('zalo.groups_subtitle') }}</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="loadGroups(false)"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ t('common.refresh') }}
        </button>
        <button
          @click="loadGroups(true)"
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

      <div v-else-if="groups.length > 0" class="space-y-3">
        <div v-for="group in groups" :key="group.id" 
             class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
          <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden bg-purple-100">
            <img 
              v-if="group.avatar_url" 
              :src="group.avatar_url" 
              :alt="group.name"
              class="w-full h-full object-cover"
              @error="handleImageError"
            />
            <svg v-else class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <div class="flex-1">
            <p class="font-medium text-gray-900">{{ group.name }}</p>
            <p class="text-sm text-gray-600">{{ group.members_count || 0 }} {{ t('zalo.members') }}</p>
          </div>
          <button
            @click="sendGroupMessage(group)"
            class="px-4 py-2 text-sm text-purple-600 border border-purple-600 rounded-lg hover:bg-purple-50"
          >
            {{ t('zalo.send_message') }}
          </button>
        </div>
      </div>

      <div v-else class="text-center py-12 text-gray-500">
        <p>{{ t('zalo.no_groups_found') }}</p>
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
const groups = ref([]);

const loadGroups = async (forceSync = false) => {
  console.log('📋 [ZaloGroups] Loading groups...', { forceSync });
  loading.value = true;
  try {
    const accountId = zaloAccount?.getAccountId?.();
    console.log('📋 [ZaloGroups] Account ID:', accountId);
    
    // Load from cache first (fast), then sync if needed
    console.log('📡 [ZaloGroups] Calling /api/zalo/groups...');
    const response = await axios.get('/api/zalo/groups', {
      params: {
        sync: forceSync,
        account_id: accountId
      }
    });
    
    console.log('📥 [ZaloGroups] Response received:', {
      success: response.data.success,
      cached: response.data.cached,
      count: response.data.data?.length || 0,
    });
    
    groups.value = response.data.data || [];
    
    // If no cached data, automatically sync in background
    if (response.data.cached === false && !forceSync && accountId) {
      console.log('🔄 [ZaloGroups] No cached data, syncing in background...');
      // Silently sync in background
      setTimeout(() => {
        loadGroups(true);
      }, 1000);
    }
  } catch (error) {
    console.error('❌ [ZaloGroups] Failed to load groups:', error);
    console.error('   Response:', error.response?.data);
    console.error('   Status:', error.response?.status);
    groups.value = [];
  } finally {
    loading.value = false;
    console.log('🏁 [ZaloGroups] Load completed');
  }
};

// Watch for account changes
if (zaloAccount) {
  watch(() => zaloAccount.activeAccountId?.value, () => {
    loadGroups();
  });
}

// Listen for reload events
onMounted(() => {
  loadGroups();
  
  window.addEventListener('zalo-reload-data', () => {
    loadGroups();
  });
});

const handleImageError = (event) => {
  // Hide broken image, show placeholder
  event.target.style.display = 'none';
};

const sendGroupMessage = (group) => {
  console.log('Send message to group:', group);
};

onMounted(() => {
  loadGroups();
});
</script>

