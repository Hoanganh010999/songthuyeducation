<template>
  <div class="drag-drop-question">
    <!-- Question Title -->
    <div class="mb-4">
      <p class="text-gray-800 font-medium" v-html="question.title"></p>
    </div>

    <!-- Drag Drop Area -->
    <div class="relative border rounded-lg overflow-hidden bg-white" :style="containerStyle">
      <!-- Background Content Layer -->
      <div
        v-if="backgroundContent"
        class="absolute inset-0 overflow-auto pointer-events-none p-3"
        v-html="backgroundContent"
      ></div>

      <!-- Drop Zones -->
      <div
        v-for="(zone, index) in dropZones"
        :key="`zone-${index}`"
        class="absolute border-2 border-dashed transition-colors flex items-center justify-center"
        :class="getDropZoneClass(index)"
        :style="getDropZoneStyle(zone)"
        @dragover.prevent="onDragOver(index)"
        @dragleave="onDragLeave(index)"
        @drop="onDrop(index, $event)"
      >
        <span v-if="!showResult" class="absolute top-1 left-1 text-xs text-gray-400">{{ index + 1 }}</span>

        <!-- Dropped Item Preview -->
        <div v-if="answers[index] !== null && items[answers[index]]" class="flex items-center justify-center">
          <img
            v-if="items[answers[index]].type === 'image'"
            :src="items[answers[index]].content"
            class="object-contain"
            :style="{ maxWidth: items[answers[index]].width + 'px', maxHeight: items[answers[index]].height + 'px' }"
            alt="Dropped item"
          />
          <span v-else class="text-sm text-center" v-html="items[answers[index]].content"></span>
        </div>

        <!-- Result Indicator -->
        <div v-if="showResult" class="absolute top-1 right-1">
          <svg v-if="isCorrect(index)" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
          <svg v-else class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Draggable Items Container -->
    <div v-if="!disabled && !showResult" class="mt-4 p-4 border rounded-lg bg-white">
      <h4 class="text-sm font-medium text-gray-700 mb-3">Kéo các phần tử vào vị trí đúng:</h4>
      <div class="flex flex-wrap gap-3">
        <div
          v-for="(item, index) in availableItems"
          :key="`item-${index}`"
          :draggable="!item.used"
          @dragstart="onDragStart(index, $event)"
          @dragend="onDragEnd"
          class="cursor-move border-2 rounded-lg p-2 transition-all hover:shadow-md"
          :class="item.used ? 'opacity-30 cursor-not-allowed' : 'border-blue-300 bg-blue-50'"
          :style="getItemStyle(item)"
        >
          <img
            v-if="item.type === 'image'"
            :src="item.content"
            class="max-w-full object-contain"
            :style="{ maxHeight: item.height + 'px', maxWidth: item.width + 'px' }"
            alt="Draggable item"
          />
          <span v-else class="text-sm" v-html="item.content"></span>
        </div>
      </div>
    </div>

    <!-- Show correct answers -->
    <div v-if="showResult" class="mt-4 p-3 bg-blue-50 rounded-lg">
      <p class="text-sm font-medium text-blue-800 mb-2">Đáp án đúng:</p>
      <div class="space-y-1 text-sm text-blue-700">
        <div v-for="(answer, index) in correctAnswers" :key="index">
          Vùng {{ index + 1 }}: {{ getItemLabel(answer) }}
        </div>
      </div>
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
import { ref, computed, watch, onMounted } from 'vue'

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

// Parse question settings
const settings = computed(() => props.question.settings || {})
const backgroundContent = computed(() => settings.value.backgroundContent || '')
const containerStyle = computed(() => ({
  minHeight: (settings.value.containerHeight || 400) + 'px',
  height: (settings.value.containerHeight || 400) + 'px',
}))

// Draggable items from question
const items = computed(() => settings.value.items || [])

// Drop zones from question
const dropZones = computed(() => settings.value.dropZones || [])

// Correct answers (which item goes to which zone)
const correctAnswers = computed(() => settings.value.correctAnswers || [])

// Student's answers
const answers = ref(props.modelValue && Array.isArray(props.modelValue) ? [...props.modelValue] : [])

// Available items (shuffled on mount)
const availableItems = ref([])

// Drag state
const draggedItemIndex = ref(null)
const dragOverZoneIndex = ref(null)

onMounted(() => {
  // Shuffle items and initialize
  availableItems.value = items.value.map((item, index) => ({
    ...item,
    originalIndex: index,
    used: false
  }))

  // Shuffle items
  availableItems.value = shuffleArray(availableItems.value)

  // Initialize answers array
  if (answers.value.length === 0) {
    answers.value = Array(dropZones.value.length).fill(null)
  }

  // Mark items as used if already in answers
  answers.value.forEach(answer => {
    if (answer !== null) {
      const item = availableItems.value.find(i => i.originalIndex === answer)
      if (item) item.used = true
    }
  })
})

watch(() => props.modelValue, (newVal) => {
  answers.value = [...newVal]
}, { deep: true })

function shuffleArray(array) {
  const shuffled = [...array]
  for (let i = shuffled.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]]
  }
  return shuffled
}

function onDragStart(itemIndex, event) {
  draggedItemIndex.value = itemIndex
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/html', event.target.innerHTML)
}

function onDragEnd() {
  draggedItemIndex.value = null
  dragOverZoneIndex.value = null
}

function onDragOver(zoneIndex) {
  dragOverZoneIndex.value = zoneIndex
}

function onDragLeave(zoneIndex) {
  if (dragOverZoneIndex.value === zoneIndex) {
    dragOverZoneIndex.value = null
  }
}

function onDrop(zoneIndex, event) {
  event.preventDefault()

  if (draggedItemIndex.value === null) return

  const draggedItem = availableItems.value[draggedItemIndex.value]

  // If zone already has an item, mark it as available again
  if (answers.value[zoneIndex] !== null) {
    const previousItemIndex = availableItems.value.findIndex(
      item => item.originalIndex === answers.value[zoneIndex]
    )
    if (previousItemIndex !== -1) {
      availableItems.value[previousItemIndex].used = false
    }
  }

  // Place new item in zone
  answers.value[zoneIndex] = draggedItem.originalIndex
  draggedItem.used = true

  // Emit update
  emit('update:modelValue', [...answers.value])
  emit('answer', {
    question_id: props.question.id,
    answer: [...answers.value]
  })

  dragOverZoneIndex.value = null
}

function getDropZoneStyle(zone) {
  return {
    left: zone.x + '%',
    top: zone.y + '%',
    width: zone.width + '%',
    height: zone.height + '%',
  }
}

function getDropZoneClass(index) {
  if (props.showResult) {
    return isCorrect(index) ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50'
  }
  if (dragOverZoneIndex.value === index) {
    return 'border-blue-500 bg-blue-100'
  }
  if (answers.value[index] !== null) {
    return 'border-blue-400 bg-blue-50'
  }
  return 'border-gray-300 bg-white bg-opacity-50'
}

function getItemStyle(item) {
  if (item.type === 'image') {
    return {
      minWidth: '60px',
      minHeight: '60px',
    }
  }
  return {}
}

function isCorrect(zoneIndex) {
  const studentAnswer = answers.value[zoneIndex]
  const correctAnswer = correctAnswers.value[zoneIndex]

  console.log(`🔍 Zone ${zoneIndex + 1}:`, {
    studentAnswer,
    correctAnswer,
    studentType: typeof studentAnswer,
    correctType: typeof correctAnswer,
    isEqual: studentAnswer === correctAnswer,
    looseEqual: studentAnswer == correctAnswer
  })

  if (correctAnswer === null || correctAnswer === undefined) return false
  // Use loose equality to handle number vs string comparison
  return studentAnswer == correctAnswer
}

function getItemLabel(itemIndex) {
  const item = items.value[itemIndex]
  if (!item) return 'N/A'
  if (item.type === 'text') {
    return item.content.replace(/<[^>]*>/g, '') // Strip HTML
  }
  return `Hình ${itemIndex + 1}`
}
</script>

<style scoped>
.drag-drop-question {
  user-select: none;
}
</style>
