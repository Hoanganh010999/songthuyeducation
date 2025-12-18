<template>
  <div>
    <div 
      class="px-6 py-4 hover:bg-gray-50 flex items-center justify-between group"
      :style="{ paddingLeft: `${(level * 2) + 1.5}rem` }"
    >
      <div class="flex items-center space-x-3 flex-1">
        <button
          v-if="category.children && category.children.length > 0"
          @click="toggleExpand"
          class="text-gray-400 hover:text-gray-600"
        >
          <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-90': isExpanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
        <div v-else class="w-5"></div>
        
        <div class="flex-1">
          <div class="flex items-center space-x-2">
            <span class="font-mono text-sm font-semibold text-gray-900 bg-gray-100 px-2 py-1 rounded">{{ category.code }}</span>
            <span class="text-gray-900 font-medium">{{ category.name }}</span>
            <span
              class="px-2 py-1 text-xs font-medium rounded-full"
              :class="category.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
            >
              {{ t(`accounting.${category.type}`) }}
            </span>
            <span
              v-if="category.cost_type"
              class="px-2 py-1 text-xs font-medium rounded-full"
              :class="getCostTypeClass(category.cost_type)"
            >
              {{ t(`accounting.${category.cost_type}`) }}
            </span>
            <span
              v-if="!category.is_active"
              class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800"
            >
              {{ t('accounting.inactive') }}
            </span>
          </div>
          <p v-if="category.description" class="text-xs text-gray-500 mt-1">{{ category.description }}</p>
        </div>
      </div>

      <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition">
        <button
          @click="$emit('edit', category)"
          class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>
        <button
          @click="$emit('delete', category)"
          class="p-2 text-red-600 hover:bg-red-50 rounded-lg"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Children -->
    <div v-if="isExpanded && category.children && category.children.length > 0">
      <CategoryTreeNode
        v-for="child in category.children"
        :key="child.id"
        :category="child"
        :level="level + 1"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();

const props = defineProps({
  category: {
    type: Object,
    required: true
  },
  level: {
    type: Number,
    default: 0
  }
});

defineEmits(['edit', 'delete']);

const isExpanded = ref(true);

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value;
};

const getCostTypeClass = (costType) => {
  const classes = {
    fixed: 'bg-blue-100 text-blue-800',
    variable: 'bg-purple-100 text-purple-800',
    infrastructure: 'bg-orange-100 text-orange-800'
  };
  return classes[costType] || 'bg-gray-100 text-gray-800';
};
</script>

