<template>
  <div class="multiple-choice-question">
    <!-- Question Title -->
    <div v-if="question.title" class="mb-2">
      <p class="text-gray-800 font-medium" v-html="question.title"></p>
    </div>

    <!-- Question Content -->
    <div v-if="question.content" class="mb-4">
      <p class="text-gray-700 whitespace-pre-line" v-html="question.content"></p>
      <img v-if="question.image_url" :src="question.image_url" class="mt-2 max-w-md rounded-lg" />
    </div>

    <!-- Options -->
    <div class="space-y-2">
      <label
        v-for="option in question.options"
        :key="option.id"
        class="flex items-start p-3 rounded-lg border cursor-pointer transition-colors"
        :class="getOptionClass(option)"
      >
        <input
          type="radio"
          :name="`question_${question.id}`"
          :value="option.id"
          v-model="selectedAnswer"
          :disabled="disabled || showResult"
          class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
        />
        <span class="ml-3 flex-1" v-html="option.content"></span>

        <!-- Result indicators -->
        <span v-if="showResult && option.is_correct" class="ml-2 text-green-500">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </span>
        <span v-if="showResult && selectedAnswer === option.id && !option.is_correct" class="ml-2 text-red-500">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </span>
      </label>
    </div>

    <!-- Feedback/Explanation -->
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
    type: [String, Number],
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

function getOptionClass(option) {
  if (!props.showResult) {
    return selectedAnswer.value === option.id
      ? 'border-blue-500 bg-blue-50'
      : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
  }

  if (option.is_correct) {
    return 'border-green-500 bg-green-50'
  }

  if (selectedAnswer.value === option.id && !option.is_correct) {
    return 'border-red-500 bg-red-50'
  }

  return 'border-gray-200 bg-gray-50'
}
</script>
