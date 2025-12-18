<template>
  <div class="fixed bottom-4 right-4 z-[9999] space-y-2">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'rounded-lg shadow-lg cursor-pointer transform transition-all duration-300',
          'hover:shadow-xl border',
          toast.type === 'zalo' ? 'bg-white border-gray-200 w-80' : getToastClasses(toast.type)
        ]"
        @click="handleClick(toast)"
      >
        <!-- Zalo Toast - Clean white card design -->
        <div v-if="toast.type === 'zalo'" class="p-3">
          <div class="flex items-start gap-3">
            <!-- Avatar -->
            <div class="flex-shrink-0">
              <img
                v-if="toast.avatar"
                :src="toast.avatar"
                class="w-12 h-12 rounded-full object-cover border border-gray-200"
                @error="handleAvatarError($event)"
              />
              <div v-else class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-lg">
                {{ getInitials(toast.title) }}
              </div>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-gray-900 truncate">{{ toast.title }}</p>
              <p class="text-sm text-gray-600 mt-0.5 line-clamp-2">{{ getDisplayMessage(toast.message) }}</p>
            </div>

            <!-- Close button -->
            <button
              @click.stop="removeToast(toast.id)"
              class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors p-1"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Other Toast Types -->
        <div v-else class="p-3">
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
              <div :class="['w-8 h-8 rounded-full flex items-center justify-center', getIconBgClass(toast.type)]">
                <component :is="getIcon(toast.type)" class="w-4 h-4" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p v-if="toast.title" class="font-semibold text-sm truncate">{{ toast.title }}</p>
              <p class="text-sm opacity-90 line-clamp-2">{{ getDisplayMessage(toast.message) }}</p>
            </div>
            <button
              @click.stop="removeToast(toast.id)"
              class="flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { h } from 'vue';
import { useToast } from '../composables/useToast';

const { toasts, removeToast } = useToast();

// Get initials from name
const getInitials = (name) => {
  if (!name) return '?';
  const parts = name.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

// Handle avatar load error - hide the img and show initials
const handleAvatarError = (event) => {
  event.target.style.display = 'none';
  // The v-else div will show automatically since img is hidden
};

const getToastClasses = (type) => {
  const classes = {
    success: 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white max-w-sm',
    error: 'bg-gradient-to-r from-red-500 to-red-600 text-white max-w-sm',
    warning: 'bg-gradient-to-r from-amber-500 to-amber-600 text-white max-w-sm',
    info: 'bg-gradient-to-r from-gray-600 to-gray-700 text-white max-w-sm',
  };
  return classes[type] || classes.info;
};

const getIconBgClass = (type) => {
  const classes = {
    success: 'bg-white/20',
    error: 'bg-white/20',
    warning: 'bg-white/20',
    info: 'bg-white/20',
  };
  return classes[type] || 'bg-white/20';
};

const getIcon = (type) => {
  const icons = {
    success: {
      render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'text-white' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 13l4 4L19 7' })
        ]);
      }
    },
    error: {
      render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'text-white' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M6 18L18 6M6 6l12 12' })
        ]);
      }
    },
    warning: {
      render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'text-white' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' })
        ]);
      }
    },
    info: {
      render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'text-white' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
        ]);
      }
    },
  };
  return icons[type] || icons.info;
};

const getDisplayMessage = (message) => {
  if (!message) return '';
  if (message.length > 100) {
    return message.substring(0, 100) + '...';
  }
  return message;
};

const handleClick = (toast) => {
  if (toast.onClick) {
    toast.onClick();
  }
  removeToast(toast.id);
};
</script>

<style scoped>
.toast-enter-active {
  animation: toast-in 0.3s ease-out;
}

.toast-leave-active {
  animation: toast-out 0.3s ease-in;
}

@keyframes toast-in {
  0% {
    opacity: 0;
    transform: translateX(100%);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes toast-out {
  0% {
    opacity: 1;
    transform: translateX(0);
  }
  100% {
    opacity: 0;
    transform: translateX(100%);
  }
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
