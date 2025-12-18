<template>
  <div 
    v-if="show" 
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
    @click.self="closeModal"
  >
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-900">Biểu cảm</h3>
        <button 
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 focus:outline-none"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Tabs -->
      <div class="flex border-b overflow-x-auto">
        <button
          @click="activeTab = 'all'"
          :class="[
            'px-4 py-3 text-sm font-medium focus:outline-none whitespace-nowrap flex items-center gap-2',
            activeTab === 'all' 
              ? 'text-blue-600 border-b-2 border-blue-600' 
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          <span>Tất cả</span>
          <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">{{ totalCount }}</span>
        </button>
        
        <button
          v-for="reaction in reactions"
          :key="reaction.reaction"
          @click="activeTab = reaction.reaction"
          :class="[
            'px-4 py-3 text-sm font-medium focus:outline-none whitespace-nowrap flex items-center gap-2',
            activeTab === reaction.reaction 
              ? 'text-blue-600 border-b-2 border-blue-600' 
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          <span class="text-xl">{{ getReactionEmoji(reaction.reaction) }}</span>
          <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">{{ reaction.count }}</span>
        </button>
      </div>

      <!-- User List -->
      <div class="flex-1 overflow-y-auto p-4">
        <div v-if="filteredUsers.length === 0" class="text-center py-8 text-gray-500">
          Không có biểu cảm nào
        </div>
        
        <div v-else class="space-y-3">
          <div 
            v-for="user in filteredUsers" 
            :key="`${user.zalo_user_id}-${user.reaction}`"
            class="flex items-center gap-3"
          >
            <!-- Avatar -->
            <div class="flex-shrink-0">
              <div 
                v-if="user.avatar"
                class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden"
              >
                <img :src="user.avatar" :alt="user.name" class="w-full h-full object-cover" />
              </div>
              <div 
                v-else
                class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-sm"
              >
                {{ getInitials(user.name) }}
              </div>
            </div>

            <!-- Name and Reaction -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</p>
              <p class="text-xs text-gray-500">{{ formatTime(user.reacted_at) }}</p>
            </div>

            <!-- Reaction Icon -->
            <div class="text-2xl">
              {{ getReactionEmoji(user.reaction) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { format, formatDistanceToNow } from 'date-fns';
import { vi } from 'date-fns/locale';

const props = defineProps({
  show: {
    type: Boolean,
    required: true,
  },
  reactions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close']);

const activeTab = ref('all');

// Total count across all reactions
const totalCount = computed(() => {
  return props.reactions.reduce((sum, r) => sum + r.count, 0);
});

// Filtered users based on active tab
const filteredUsers = computed(() => {
  if (activeTab.value === 'all') {
    // Show all users from all reactions
    return props.reactions.flatMap(r => 
      r.users.map(u => ({ ...u, reaction: r.reaction }))
    ).sort((a, b) => new Date(b.reacted_at) - new Date(a.reacted_at));
  } else {
    // Show users for specific reaction
    const reaction = props.reactions.find(r => r.reaction === activeTab.value);
    return reaction ? reaction.users.map(u => ({ ...u, reaction: reaction.reaction })) : [];
  }
});

// Map reaction icon to emoji
const getReactionEmoji = (icon) => {
  if (!icon) return '👍';
  
  // Zalo API returns HTML-like strings for reactions
  const emojiMap = {
    // Standard emoji names
    'HEART': '❤️',
    'LIKE': '👍',
    'HAHA': '😂',
    'WOW': '😮',
    'SAD': '😢',
    'ANGRY': '😠',
    'LOVE': '❤️',
    
    // Zalo HTML-like formats (case insensitive)
    '/-heart': '❤️',
    '/-strong': '👍',
    '/-haha': '😂',
    '/-wow': '😮',
    '/-sad': '😢',
    '/-angry': '😠',
    '/emoticon': '😊',
  };
  
  // Try exact match first
  const iconLower = icon.toLowerCase();
  if (emojiMap[iconLower]) {
    return emojiMap[iconLower];
  }
  
  // Try to extract from HTML-like string
  if (icon.includes('strong')) return '👍';
  if (icon.includes('heart') || icon.includes('love')) return '❤️';
  if (icon.includes('haha') || icon.includes('laugh')) return '😂';
  if (icon.includes('wow')) return '😮';
  if (icon.includes('sad') || icon.includes('cry')) return '😢';
  if (icon.includes('angry') || icon.includes('mad')) return '😠';
  
  // If it's already an emoji, return as is
  if (/\p{Emoji}/u.test(icon)) {
    return icon;
  }
  
  // Default fallback
  return '👍';
};

// Get initials from name
const getInitials = (name) => {
  if (!name) return '?';
  const words = name.trim().split(' ');
  if (words.length === 1) {
    return words[0].charAt(0).toUpperCase();
  }
  return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
};

// Format timestamp
const formatTime = (timestamp) => {
  if (!timestamp) return '';
  try {
    const date = new Date(timestamp);
    const now = new Date();
    const diffInHours = (now - date) / 1000 / 60 / 60;

    if (diffInHours < 24) {
      return formatDistanceToNow(date, { addSuffix: true, locale: vi });
    } else {
      return format(date, 'dd/MM/yyyy HH:mm', { locale: vi });
    }
  } catch (e) {
    return '';
  }
};

const closeModal = () => {
  emit('close');
};
</script>

<style scoped>
/* Custom scrollbar for user list */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>

