<template>
  <div class="true-false-question">
    <!-- Question Title -->
    <div v-if="question.title" class="mb-2">
      <p class="text-gray-800 font-medium" v-html="question.title"></p>
    </div>

    <!-- Question Content -->
    <div v-if="question.content" class="mb-4">
      <p class="text-gray-700 whitespace-pre-line" v-html="question.content"></p>
    </div>

    <!-- Options - All in one row -->
    <div class="flex flex-wrap gap-4">
      <label
        v-for="option in options"
        :key="option.value"
        class="flex items-center px-6 py-3 rounded-lg border cursor-pointer transition-colors"
        :class="getOptionClass(option.value)"
      >
        <input
          type="radio"
          :name="`question_${question.id}`"
          :value="option.value"
          v-model="selectedAnswer"
          :disabled="disabled || showResult"
          class="h-4 w-4 text-blue-600 focus:ring-blue-500"
        />
        <span class="ml-2 font-medium">{{ option.label }}</span>

        <!-- Result indicator -->
        <span v-if="showResult && option.value === correctAnswer" class="ml-2 text-green-500">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </span>
      </label>

      <!-- Not Given option for IELTS - in same row -->
      <label
        v-if="hasNotGiven"
        class="flex items-center px-6 py-3 rounded-lg border cursor-pointer transition-colors"
        :class="getOptionClass('not_given')"
      >
        <input
          type="radio"
          :name="`question_${question.id}`"
          value="not_given"
          v-model="selectedAnswer"
          :disabled="disabled || showResult"
          class="h-4 w-4 text-blue-600 focus:ring-blue-500"
        />
        <span class="ml-2 font-medium">Not Given</span>

        <span v-if="showResult && correctAnswer === 'not_given'" class="ml-2 text-green-500">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </span>
      </label>
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
import { ref, watch, computed } from 'vue'

const props = defineProps({
  question: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Boolean],
    default: null
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

const selectedAnswer = ref(props.modelValue)

// Check if this is True/False/Not Given (IELTS style)
const hasNotGiven = computed(() => {
  return props.question.type === 'true_false_ng'
})

const options = [
  { value: true, label: 'True' },
  { value: false, label: 'False' }
]

const correctAnswer = computed(() => props.question.correct_answer)

watch(selectedAnswer, (newVal) => {
  emit('update:modelValue', newVal)
  emit('answer', {
    question_id: props.question.id,
    answer: newVal
  })
})

watch(() => props.modelValue, (newVal) => {
  selectedAnswer.value = newVal
})

function getOptionClass(value) {
  if (!props.showResult) {
    return selectedAnswer.value === value
      ? 'border-blue-500 bg-blue-50'
      : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
  }

  if (value === correctAnswer.value) {
    return 'border-green-500 bg-green-50'
  }

  if (selectedAnswer.value === value && value !== correctAnswer.value) {
    return 'border-red-500 bg-red-50'
  }

  return 'border-gray-200 bg-gray-50'
}
</script>
