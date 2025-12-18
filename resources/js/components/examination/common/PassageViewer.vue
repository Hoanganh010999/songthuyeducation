<template>
  <div class="passage-viewer bg-white rounded-lg border shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
      <h3 class="font-medium text-gray-800">{{ passage.title }}</h3>
      <div class="flex items-center space-x-2">
        <!-- Word count -->
        <span class="text-sm text-gray-500">{{ passage.word_count }} từ</span>

        <!-- Font size controls -->
        <button @click="decreaseFontSize" class="p-1 hover:bg-gray-200 rounded" title="Giảm cỡ chữ">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
          </svg>
        </button>
        <span class="text-sm text-gray-500">{{ fontSize }}px</span>
        <button @click="increaseFontSize" class="p-1 hover:bg-gray-200 rounded" title="Tăng cỡ chữ">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
        </button>

        <!-- Toggle expand -->
        <button
          @click="isExpanded = !isExpanded"
          class="p-1 hover:bg-gray-200 rounded"
          :title="isExpanded ? 'Thu gọn' : 'Mở rộng'"
        >
          <svg v-if="!isExpanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Content -->
    <div
      class="p-4 overflow-y-auto transition-all duration-300"
      :class="isExpanded ? 'max-h-screen' : 'max-h-96'"
      :style="{ fontSize: fontSize + 'px', lineHeight: lineHeight }"
    >
      <div class="prose max-w-none" v-html="formattedContent"></div>
    </div>

    <!-- Source -->
    <div v-if="passage.source" class="px-4 py-2 border-t text-xs text-gray-500 italic">
      Nguồn: {{ passage.source }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  passage: {
    type: Object,
    required: true
  }
})

const fontSize = ref(16)
const isExpanded = ref(false)

const lineHeight = computed(() => {
  return fontSize.value <= 14 ? '1.6' : '1.8'
})

const formattedContent = computed(() => {
  let content = props.passage.content || ''

  // Add paragraph numbers if they exist in format [1], [2], etc.
  content = content.replace(/\[(\d+)\]/g, '<span class="text-gray-400 text-sm font-medium">[$1]</span>')

  // Highlight important terms (marked with *term*)
  content = content.replace(/\*([^*]+)\*/g, '<strong class="text-blue-700">$1</strong>')

  return content
})

function increaseFontSize() {
  if (fontSize.value < 24) {
    fontSize.value += 2
  }
}

function decreaseFontSize() {
  if (fontSize.value > 12) {
    fontSize.value -= 2
  }
}
</script>

<style scoped>
.prose {
  white-space: pre-wrap;
  word-wrap: break-word;
}

.prose p {
  margin-bottom: 1em;
  text-indent: 2em;
}

.prose p:first-child {
  text-indent: 0;
}
</style>
