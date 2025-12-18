<template>
  <div class="matching-question">
    <!-- Question Title -->
    <div class="mb-4">
      <p class="text-gray-800 font-medium" v-html="question.title"></p>
    </div>

    <!-- Matching interface -->
    <div class="grid grid-cols-2 gap-6">
      <!-- Left column (items to match) -->
      <div class="space-y-3">
        <h4 class="text-sm font-medium text-gray-600 mb-2">{{ leftColumnTitle }}</h4>
        <div
          v-for="(item, index) in leftItems"
          :key="'left-' + index"
          class="flex items-center p-3 bg-gray-50 rounded-lg border"
        >
          <span class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-700 rounded-full font-medium mr-3">
            {{ index + 1 }}
          </span>
          <span class="flex-1" v-html="item.content || item"></span>

          <!-- Dropdown to select match -->
          <select
            v-model="answers[index]"
            @change="handleChange"
            :disabled="disabled || showResult"
            class="ml-3 px-3 py-1 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            :class="getSelectClass(index)"
          >
            <option value="">-- Chọn --</option>
            <option v-for="(opt, optIndex) in rightItems" :key="optIndex" :value="opt.id || opt.label || optIndex">
              {{ opt.label || String.fromCharCode(65 + optIndex) }}
            </option>
          </select>
        </div>
      </div>

      <!-- Right column (options) -->
      <div class="space-y-3">
        <h4 class="text-sm font-medium text-gray-600 mb-2">{{ rightColumnTitle }}</h4>
        <div
          v-for="(item, index) in rightItems"
          :key="'right-' + index"
          class="flex items-start p-3 bg-white rounded-lg border"
          :class="{ 'border-green-500 bg-green-50': showResult && isRightItemCorrect(index) }"
        >
          <span class="w-8 h-8 flex items-center justify-center bg-gray-100 text-gray-700 rounded-full font-medium mr-3">
            {{ item.label || String.fromCharCode(65 + index) }}
          </span>
          <span class="flex-1" v-html="item.content || item"></span>
        </div>
      </div>
    </div>

    <!-- Show correct answers -->
    <div v-if="showResult && correctAnswers" class="mt-4 p-3 bg-green-50 rounded-lg">
      <p class="text-sm font-medium text-green-800 mb-2">Đáp án đúng:</p>
      <div class="flex flex-wrap gap-2">
        <span v-for="(answer, index) in correctAnswers" :key="index" class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">
          {{ index + 1 }} → {{ answer }}
        </span>
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

const answers = ref([...props.modelValue])

const leftColumnTitle = computed(() => props.question.content?.left_title || 'Câu hỏi')
const rightColumnTitle = computed(() => props.question.content?.right_title || 'Đáp án')

const leftItems = computed(() => {
  return props.question.content?.left || props.question.content?.items || []
})

const rightItems = computed(() => {
  return props.question.content?.right || props.question.content?.options || props.question.options || []
})

const correctAnswers = computed(() => props.question.correct_answer || [])

onMounted(() => {
  if (answers.value.length < leftItems.value.length) {
    answers.value = Array(leftItems.value.length).fill('')
  }
})

watch(() => props.modelValue, (newVal) => {
  answers.value = [...newVal]
}, { deep: true })

function handleChange() {
  emit('update:modelValue', [...answers.value])
  emit('answer', {
    question_id: props.question.id,
    answer: [...answers.value]
  })
}

function isCorrect(index) {
  if (!correctAnswers.value[index]) return false
  return answers.value[index] === correctAnswers.value[index]
}

function isRightItemCorrect(index) {
  const label = rightItems.value[index]?.label || String.fromCharCode(65 + index)
  return correctAnswers.value.includes(label)
}

function getSelectClass(index) {
  if (!props.showResult) {
    return answers.value[index] ? 'border-blue-500' : 'border-gray-300'
  }
  return isCorrect(index) ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50'
}
</script>
