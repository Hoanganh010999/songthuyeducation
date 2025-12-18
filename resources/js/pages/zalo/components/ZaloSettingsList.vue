<template>
  <div class="h-full flex flex-col">
    <div class="p-4 border-b border-gray-200">
      <h2 class="text-lg font-semibold text-gray-900">{{ t('zalo.settings') }}</h2>
    </div>

    <div class="flex-1 overflow-y-auto">
      <button
        v-for="item in filteredSettingsItems"
        :key="item.key"
        @click="selectItem(item.key)"
        class="w-full px-4 py-3 text-left hover:bg-gray-50 transition-colors flex items-center gap-3 border-b border-gray-100"
        :class="selectedKey === item.key ? 'bg-blue-50 border-l-4 border-blue-600' : ''"
      >
        <component :is="item.icon" class="w-5 h-5 text-gray-600" />
        <span class="font-medium text-gray-900">{{ item.label }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, h, computed } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useAuthStore } from '../../../stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();

const props = defineProps({
  selectedKey: {
    type: String,
    default: 'connection'
  }
});

const emit = defineEmits(['select']);

const selectItem = (key) => {
  emit('select', key);
};

// Filter settings items based on permissions
const filteredSettingsItems = computed(() => {
  return settingsItems.filter(item => {
    // Branch access management requires special permission
    if (item.key === 'branch_access') {
      return authStore.hasPermission('zalo.manage_branch_access');
    }
    return true;
  });
});

// Icons
const ConnectionIcon = () => h('svg', {
  class: 'w-5 h-5',
  fill: 'none',
  stroke: 'currentColor',
  viewBox: '0 0 24 24'
}, [
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0'
  })
]);

const NotificationIcon = () => h('svg', {
  class: 'w-5 h-5',
  fill: 'none',
  stroke: 'currentColor',
  viewBox: '0 0 24 24'
}, [
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'
  })
]);

const DocumentIcon = () => h('svg', {
  class: 'w-5 h-5',
  fill: 'none',
  stroke: 'currentColor',
  viewBox: '0 0 24 24'
}, [
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
  })
]);

const BranchIcon = () => h('svg', {
  class: 'w-5 h-5',
  fill: 'none',
  stroke: 'currentColor',
  viewBox: '0 0 24 24'
}, [
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
  })
]);

const settingsItems = [
  { key: 'connection', label: t('zalo.service_connection'), icon: ConnectionIcon },
  { key: 'branch_access', label: t('zalo.branch_access_management'), icon: BranchIcon },
  { key: 'notifications', label: t('zalo.auto_notifications'), icon: NotificationIcon },
  { key: 'documentation', label: t('zalo.setup_guide'), icon: DocumentIcon },
];
</script>

