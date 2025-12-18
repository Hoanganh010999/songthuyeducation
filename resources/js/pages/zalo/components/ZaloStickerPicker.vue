<template>
  <div
    v-if="show"
    class="absolute bottom-full left-0 mb-2 w-[420px] h-[450px] bg-white rounded-lg shadow-xl border border-gray-200 flex flex-col"
  >
    <!-- Header with tabs -->
    <div class="border-b border-gray-200">
      <div class="flex items-center justify-between px-4 py-3">
        <div class="flex gap-2">
          <button
            @click="activeTab = 'sticker'"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
            :class="activeTab === 'sticker' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'"
          >
            STICKER
          </button>
          <button
            @click="activeTab = 'emoji'"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
            :class="activeTab === 'emoji' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'"
          >
            EMOJI
          </button>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Search -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="relative">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="t('zalo.search_stickers')"
          class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>

    <!-- Sticker grid -->
    <div v-if="activeTab === 'sticker'" class="flex-1 overflow-y-auto p-4">
      <div v-if="loadingStickers" class="flex items-center justify-center h-full">
        <svg class="w-8 h-8 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </div>
      <div v-else-if="stickers.length === 0" class="flex flex-col items-center justify-center h-full text-gray-500">
        <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p>{{ t('zalo.no_stickers_found') }}</p>
      </div>
      <div v-else>
        <!-- Category title -->
        <h3 class="text-sm font-medium text-gray-700 mb-3">Cư hành</h3>

        <!-- Sticker grid -->
        <div class="grid grid-cols-4 gap-2">
          <button
            v-for="sticker in stickers"
            :key="sticker.id"
            @click="selectSticker(sticker)"
            class="aspect-square rounded-lg hover:bg-gray-100 p-2 transition-colors"
          >
            <img
              v-if="sticker.stickerUrl || sticker.stickerWebpUrl"
              :src="sticker.stickerUrl || sticker.stickerWebpUrl"
              :alt="sticker.text || 'Sticker'"
              class="w-full h-full object-contain"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Emoji tab (placeholder) -->
    <div v-else class="flex-1 flex items-center justify-center text-gray-500">
      <p>Emoji feature coming soon</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import axios from 'axios';

const { t } = useI18n();

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  accountId: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['close', 'select']);

const activeTab = ref('sticker');
const searchQuery = ref('');
const loadingStickers = ref(false);
const stickers = ref([]);
let searchTimeout = null;

// Sample stickers for testing (will be replaced with API call)
const sampleStickers = [
  { id: 23037, cateId: 10398, type: 7, text: 'Happy', stickerUrl: 'https://zalo-api.zadn.vn/api/emoticon/sticker/webpc?eid=23037&size=130&checksum=3e8b5e5e5e5e5e5e' },
  { id: 23038, cateId: 10398, type: 7, text: 'Cry', stickerUrl: 'https://zalo-api.zadn.vn/api/emoticon/sticker/webpc?eid=23038&size=130&checksum=3e8b5e5e5e5e5e5e' },
  { id: 23039, cateId: 10398, type: 7, text: 'Love', stickerUrl: 'https://zalo-api.zadn.vn/api/emoticon/sticker/webpc?eid=23039&size=130&checksum=3e8b5e5e5e5e5e5e' },
  { id: 23040, cateId: 10398, type: 7, text: 'Angry', stickerUrl: 'https://zalo-api.zadn.vn/api/emoticon/sticker/webpc?eid=23040&size=130&checksum=3e8b5e5e5e5e5e5e' },
];

// Note: Search is now handled server-side via API
// No need for client-side filtering

// Load recent stickers from Laravel API (database)
const loadStickers = async () => {
  loadingStickers.value = true;
  try {
    console.log('🔍 [ZaloStickerPicker] Loading recent stickers for account:', props.accountId);

    // Call Laravel API to get recent stickers from database
    const response = await axios.get('/api/zalo/messages/recent-stickers', {
      params: {
        branch_id: localStorage.getItem('selected_branch_id')
      }
    });

    console.log('✅ [ZaloStickerPicker] Recent stickers loaded:', response.data);

    if (response.data.success && response.data.data) {
      stickers.value = response.data.data;

      // If no recent stickers, show sample stickers
      if (stickers.value.length === 0) {
        console.log('ℹ️ [ZaloStickerPicker] No recent stickers found, showing sample stickers');
        stickers.value = sampleStickers;
      }
    } else {
      console.error('❌ [ZaloStickerPicker] Failed to load stickers:', response.data.message);
      // Fallback to sample stickers if API fails
      stickers.value = sampleStickers;
    }
  } catch (error) {
    console.error('❌ [ZaloStickerPicker] Failed to load stickers:', error);
    // Fallback to sample stickers if API fails
    stickers.value = sampleStickers;
  } finally {
    loadingStickers.value = false;
  }
};

// Search stickers from zalo-service API (live search)
const searchStickers = async (keyword) => {
  loadingStickers.value = true;
  try {
    console.log('🔍 [ZaloStickerPicker] Searching stickers with keyword:', keyword);

    // Get zalo-service config from environment
    const zaloServiceUrl = import.meta.env.VITE_ZALO_SERVICE_URL;
    const apiKey = import.meta.env.VITE_ZALO_SERVICE_API_KEY;

    // Call zalo-service API to search for stickers
    const response = await axios.get(`${zaloServiceUrl}/api/message/stickers`, {
      params: {
        keyword: keyword
      },
      headers: {
        'X-API-Key': apiKey,
        'X-Account-Id': props.accountId
      }
    });

    console.log('✅ [ZaloStickerPicker] Search results:', response.data);

    if (response.data.success && response.data.data?.stickers) {
      stickers.value = response.data.data.stickers;

      if (stickers.value.length === 0) {
        console.log('ℹ️ [ZaloStickerPicker] No stickers found for keyword:', keyword);
      }
    } else {
      console.error('❌ [ZaloStickerPicker] Failed to search stickers:', response.data.message);
      stickers.value = [];
    }
  } catch (error) {
    console.error('❌ [ZaloStickerPicker] Failed to search stickers:', error);
    stickers.value = [];
  } finally {
    loadingStickers.value = false;
  }
};

const selectSticker = (sticker) => {
  emit('select', sticker);
  emit('close');
};

// Debounced search - call searchStickers for search, loadStickers when cleared
watch(searchQuery, (newValue, oldValue) => {
  // Clear previous timeout
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  // If search query is cleared, reload recent stickers from database
  if (newValue.length === 0 && oldValue.length > 0) {
    searchTimeout = setTimeout(() => {
      console.log('🔄 [ZaloStickerPicker] Search cleared, loading recent stickers from database...');
      loadStickers();
    }, 300);
  }
  // If search has at least 2 characters, search from zalo-service
  else if (newValue.length >= 2) {
    searchTimeout = setTimeout(() => {
      console.log('🔍 [ZaloStickerPicker] Searching stickers from zalo-service...');
      searchStickers(newValue);
    }, 800); // Wait 800ms after user stops typing
  }
});

// Load stickers when modal opens
watch(() => props.show, (newValue) => {
  if (newValue && stickers.value.length === 0) {
    loadStickers();
  }
});

onMounted(() => {
  if (props.show) {
    loadStickers();
  }
});
</script>
