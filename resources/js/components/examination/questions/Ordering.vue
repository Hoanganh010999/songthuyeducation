<template>
  <div class="ordering-question">
    <!-- Question Title -->
    <div class="mb-4">
      <p class="text-gray-800 font-medium" v-html="question.title"></p>
      <p class="text-sm text-gray-500 mt-1">Kéo thả để sắp xếp theo thứ tự đúng</p>
    </div>

    <!-- Sortable items -->
    <div class="space-y-2">
      <div
        v-for="(item, index) in orderedItems"
        :key="item.id || index"
        class="flex items-center p-3 bg-white rounded-lg border-2 cursor-move transition-colors"
        :class="getItemClass(index)"
        draggable="true"
        @dragstart="onDragStart(index)"
        @dragover.prevent
        @drop="onDrop(index)"
      >
        <!-- Order number -->
        <span class="w-8 h-8 flex items-center justify-center bg-gray-100 text-gray-700 rounded-full font-medium mr-3">
          {{ index + 1 }}
        </span>

        <!-- Drag handle -->
        <span class="mr-3 text-gray-400">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
          </svg>
        </span>

        <!-- Content -->
        <span class="flex-1" v-html="item.content || item"></span>

        <!-- Result indicator -->
        <span v-if="showResult" class="ml-3">
          <svg v-if="isItemCorrect(index)" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
          <svg v-else class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </span>
      </div>
    </div>

    <!-- Show correct order -->
    <div v-if="showResult && !allCorrect" class="mt-4 p-3 bg-green-50 rounded-lg">
      <p class="text-sm font-medium text-green-800 mb-2">Thứ tự đúng:</p>
      <ol class="list-decimal list-inside text-sm text-green-700 space-y-1">
        <li v-for="(itemId, index) in correctOrder" :key="index">
          {{ getItemContent(itemId) }}
        </li>
      </ol>
    </div>

    <!-- Explanation -->
    <div v-if="showResult && question.explanation" class="mt-4 p-3 bg-blue-50 rounded-lg">
      <p class="text-sm text-blue-800">
        <strong>Giải thích:</strong> {{ question.explanation }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'

const props = defineProps({
  question: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Array,
    default: () => []
  },
  disabled: {
    type: Boolean,
    default: false
  },
  showResult: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'answer'])

const dragIndex = ref(null)
const orderedItems = ref([])

const items = computed(() => {
  return props.question.content?.items || props.question.options || []
})

const correctOrder = computed(() => {
  return props.question.correct_answer || []
})

const allCorrect = computed(() => {
  if (!correctOrder.value.length) return false
  return orderedItems.value.every((item, index) => {
    const itemId = item.id || index
    return itemId === correctOrder.value[index]
  })
})

onMounted(() => {
  initializeOrder()
})

watch(() => props.modelValue, (newVal) => {
  if (newVal && newVal.length) {
    // Reorder items based on modelValue
    const newOrder = []
    newVal.forEach(id => {
      const item = items.value.find((it, idx) => (it.id || idx) === id)
      if (item) newOrder.push(item)
    })
    if (newOrder.length === items.value.length) {
      orderedItems.value = newOrder
    }
  }
}, { deep: true })

function initializeOrder() {
  if (props.modelValue && props.modelValue.length) {
    // Use saved order
    const newOrder = []
    props.modelValue.forEach(id => {
      const item = items.value.find((it, idx) => (it.id || idx) === id)
      if (item) newOrder.push(item)
    })
    orderedItems.value = newOrder.length ? newOrder : [...items.value]
  } else {
    // Shuffle for initial display
    orderedItems.value = [...items.value].sort(() => Math.random() - 0.5)
  }
}

function onDragStart(index) {
  if (props.disabled || props.showResult) return
  dragIndex.value = index
}

function onDrop(index) {
  if (props.disabled || props.showResult || dragIndex.value === null) return

  const newOrder = [...orderedItems.value]
  const draggedItem = newOrder.splice(dragIndex.value, 1)[0]
  newOrder.splice(index, 0, draggedItem)
  orderedItems.value = newOrder
  dragIndex.value = null

  emitAnswer()
}

function emitAnswer() {
  const order = orderedItems.value.map((item, index) => item.id || index)
  emit('update:modelValue', order)
  emit('answer', {
    question_id: props.question.id,
    answer: order
  })
}

function isItemCorrect(index) {
  if (!correctOrder.value.length) return false
  const itemId = orderedItems.value[index]?.id || index
  return itemId === correctOrder.value[index]
}

function getItemClass(index) {
  if (props.disabled || props.showResult) {
    if (props.showResult) {
      return isItemCorrect(index) ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50'
    }
    return 'border-gray-200 bg-gray-50 cursor-not-allowed'
  }
  return 'border-gray-200 hover:border-blue-300 hover:shadow-sm'
}

function getItemContent(itemId) {
  const item = items.value.find((it, idx) => (it.id || idx) === itemId)
  return item?.content || item || ''
}
</script>
