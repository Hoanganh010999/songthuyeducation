<template>
  <div class="fill-blanks-question">
    <!-- Question Title -->
    <div class="mb-4">
      <p class="text-gray-800 font-medium" v-html="question.title"></p>
    </div>

    <!-- Content with blanks -->
    <div class="text-gray-700 leading-relaxed">
      <template v-for="(part, index) in parsedContent" :key="index">
        <span v-if="part.type === 'text'" v-html="part.content"></span>
        <span v-else class="inline-flex items-center mx-1">
          <span class="text-sm text-gray-500 mr-1">({{ part.index }})</span>
          <input
            type="text"
            v-model="answers[part.index - 1]"
            :disabled="disabled || showResult"
            class="w-32 px-2 py-1 border-b-2 focus:outline-none focus:border-blue-500 text-center"
            :class="getInputClass(part.index - 1)"
            @input="handleInput"
            :placeholder="`Câu ${part.index}`"
          />
          <!-- Result indicator -->
          <span v-if="showResult" class="ml-1">
            <svg v-if="isCorrect(part.index - 1)" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <svg v-else class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </span>
        </span>
      </template>
    </div>

    <!-- Show correct answers -->
    <div v-if="showResult && correctAnswers.length" class="mt-4 p-3 bg-green-50 rounded-lg">
      <p class="text-sm font-medium text-green-800 mb-1">Đáp án đúng:</p>
      <div class="flex flex-wrap gap-2">
        <span v-for="(answer, index) in correctAnswers" :key="index" class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">
          ({{ index + 1 }}) {{ answer }}
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

// Parse content to find blanks (marked as ___1___, ___2___, etc. or just ___)
const parsedContent = computed(() => {
  const content = props.question.content?.text || props.question.title || ''
  const parts = []
  let blankIndex = 1

  // Pattern to match blanks: ___n___ or ___
  const regex = /___(\d+)?___/g
  let lastIndex = 0
  let match

  while ((match = regex.exec(content)) !== null) {
    // Add text before blank
    if (match.index > lastIndex) {
      parts.push({
        type: 'text',
        content: content.slice(lastIndex, match.index)
      })
    }

    // Add blank
    const index = match[1] ? parseInt(match[1]) : blankIndex
    parts.push({
      type: 'blank',
      index: index
    })
    blankIndex++
    lastIndex = regex.lastIndex
  }

  // Add remaining text
  if (lastIndex < content.length) {
    parts.push({
      type: 'text',
      content: content.slice(lastIndex)
    })
  }

  return parts
})

const blanksCount = computed(() => {
  return parsedContent.value.filter(p => p.type === 'blank').length
})

const correctAnswers = computed(() => {
  return props.question.correct_answer || []
})

onMounted(() => {
  // Initialize answers array with empty strings
  if (answers.value.length < blanksCount.value) {
    answers.value = Array(blanksCount.value).fill('')
  }
})

watch(() => props.modelValue, (newVal) => {
  answers.value = [...newVal]
}, { deep: true })

function handleInput() {
  emit('update:modelValue', [...answers.value])
  emit('answer', {
    question_id: props.question.id,
    answer: [...answers.value]
  })
}

function isCorrect(index) {
  if (!correctAnswers.value[index]) return false
  const userAnswer = (answers.value[index] || '').toLowerCase().trim()
  const correct = Array.isArray(correctAnswers.value[index])
    ? correctAnswers.value[index].map(a => a.toLowerCase().trim())
    : [correctAnswers.value[index].toLowerCase().trim()]
  return correct.includes(userAnswer)
}

function getInputClass(index) {
  if (!props.showResult) {
    return answers.value[index]
      ? 'border-blue-500 bg-blue-50'
      : 'border-gray-300'
  }

  return isCorrect(index)
    ? 'border-green-500 bg-green-50'
    : 'border-red-500 bg-red-50'
}
</script>
