<template>
  <div class="short-answer-question">
    <!-- Question Title -->
    <div v-if="question.title" class="mb-2">
      <p class="text-gray-800 font-medium" v-html="question.title"></p>
    </div>

    <!-- Question Content -->
    <div v-if="question.content" class="mb-4">
      <p class="text-gray-700 whitespace-pre-line" v-html="question.content"></p>
      <img v-if="question.image_url" :src="question.image_url" class="mt-2 max-w-md rounded-lg" />
    </div>

    <!-- Input -->
    <div class="relative">
      <input
        type="text"
        v-model="textAnswer"
        :disabled="disabled || showResult"
        class="w-full px-4 py-3 border-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        :class="inputClass"
        placeholder="Nhập câu trả lời..."
        @input="handleInput"
      />

      <!-- Result indicator -->
      <span v-if="showResult" class="absolute right-3 top-1/2 transform -translate-y-1/2">
        <svg v-if="isCorrect" class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <svg v-else class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
      </span>
    </div>

    <!-- Hint -->
    <p v-if="hint && !showResult" class="mt-2 text-sm text-gray-500">
      💡 {{ hint }}
    </p>

    <!-- Show correct answer -->
    <div v-if="showResult && !isCorrect" class="mt-3 p-3 bg-green-50 rounded-lg">
      <p class="text-sm text-green-800">
        <strong>Đáp án đúng:</strong> {{ correctAnswerDisplay }}
      </p>
    </div>

    <!-- Explanation -->
    <div v-if="showResult && question.explanation" class="mt-3 p-3 bg-blue-50 rounded-lg">
      <p class="text-sm text-blue-800">
        <strong>Giải thích:</strong> {{ question.explanation }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  question: {
    type: Object,
    required: true
  },
  modelValue: {
    type: String,
    default: ''
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

const textAnswer = ref(props.modelValue)

const hint = computed(() => props.question.content?.hint)

const correctAnswers = computed(() => {
  const answer = props.question.correct_answer
  if (Array.isArray(answer)) return answer
  return answer ? [answer] : []
})

const correctAnswerDisplay = computed(() => {
  return correctAnswers.value.join(' / ')
})

const isCorrect = computed(() => {
  if (!textAnswer.value) return false
  const userAnswer = textAnswer.value.toLowerCase().trim()
  return correctAnswers.value.some(a => a.toLowerCase().trim() === userAnswer)
})

const inputClass = computed(() => {
  if (!props.showResult) {
    return textAnswer.value ? 'border-blue-500' : 'border-gray-300'
  }
  return isCorrect.value ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50'
})

watch(() => props.modelValue, (newVal) => {
  textAnswer.value = newVal
})

function handleInput() {
  emit('update:modelValue', textAnswer.value)
  emit('answer', {
    question_id: props.question.id,
    answer: textAnswer.value
  })
}
</script>
