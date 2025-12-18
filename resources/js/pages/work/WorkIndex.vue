<template>
  <div class="work-management">
    <div class="work-header mb-6">
      <h1 class="text-3xl font-bold text-gray-800">{{ t('work.title') }}</h1>
      <p class="text-gray-600 mt-2">{{ t('work.description') }}</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="-mb-px flex space-x-8">
        <router-link
          v-for="tab in tabs"
          :key="tab.name"
          :to="tab.route"
          class="border-b-2 py-4 px-1 text-sm font-medium transition-colors"
          :class="isActiveTab(tab.route)
            ? 'border-blue-500 text-blue-600'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
        >
          <i :class="tab.icon" class="mr-2"></i>
          {{ t(tab.label) }}
        </router-link>
      </nav>
    </div>

    <!-- Router View for Sub-pages -->
    <router-view />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from '../../composables/useI18n';

const route = useRoute();
const { t } = useI18n();

const tabs = [
  {
    name: 'dashboard',
    route: { name: 'work.dashboard' },
    icon: 'fas fa-chart-line',
    label: 'work.tabs_dashboard'
  },
  {
    name: 'items',
    route: { name: 'work.items.list' },
    icon: 'fas fa-tasks',
    label: 'work.tabs_items'
  }
];

const isActiveTab = (tabRoute) => {
  return route.name === tabRoute.name || route.matched.some(r => r.name === tabRoute.name);
};
</script>

<style scoped>
.work-management {
  padding: 1.5rem;
}
</style>
