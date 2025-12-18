<template>
  <div
    class="grading-answer-card bg-gray-50 rounded-lg p-5 border-2 transition-colors"
    :class="borderClass"
  >
    <!-- Question Header -->
    <div class="flex items-center justify-between mb-3">
      <slot name="header" :answer="answer" :index="index">
        <div class="flex items-center space-x-2">
          <span class="text-sm font-semibold text-gray-700">Câu {{ index + 1 }}</span>
          <span
            v-if="questionTypeName"
            class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded"
          >
            {{ questionTypeName }}
          </span>
        </div>
      </slot>

      <!-- Correctness Badge -->
      <div v-if="showBadge">
        <slot name="badge" :answer="answer">
          <span
            v-if="answer.is_correct === true"
            class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm flex items-center"
          >
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            Đúng
          </span>
          <span
            v-else-if="answer.is_correct === false"
            class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm flex items-center"
          >
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            Sai
          </span>
        </slot>
      </div>
    </div>

    <!-- Question Text -->
    <div class="mb-3">
      <slot name="question" :answer="answer">
        <div class="text-sm font-medium text-gray-700 mb-2">Câu hỏi:</div>
        <div class="text-gray-900" v-html="questionText"></div>
      </slot>
    </div>

    <!-- Student Answer -->
    <div class="mb-3 bg-white rounded p-3">
      <slot name="student-answer" :answer="answer">
        <div class="text-sm font-medium text-gray-700 mb-2">Câu trả lời của học sinh:</div>
        <div class="text-gray-900">{{ studentAnswerText }}</div>
      </slot>
    </div>

    <!-- Correct Answer (if applicable) -->
    <div v-if="showCorrectAnswer && correctAnswer" class="mb-3 bg-green-50 rounded p-3">
      <slot name="correct-answer" :answer="answer">
        <div class="text-sm font-medium text-green-700 mb-2">Đáp án đúng:</div>
        <div class="text-green-900 font-medium">{{ correctAnswer }}</div>
      </slot>
    </div>

    <!-- Grading Section -->
    <div v-if="!hideGrading" class="pt-3 border-t">
      <slot name="grading" :answer="answer"></slot>
    </div>

    <!-- Footer Slot -->
    <div v-if="$slots.footer">
      <slot name="footer" :answer="answer"></slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  answer: {
    type: Object,
    required: true
  },
  index: {
    type: Number,
    default: 0
  },
  questionTypeName: {
    type: String,
    default: ''
  },
  showBadge: {
    type: Boolean,
    default: true
  },
  showCorrectAnswer: {
    type: Boolean,
    default: true
  },
  hideGrading: {
    type: Boolean,
    default: false
  }
})

// Border color based on correctness
const borderClass = computed(() => {
  if (props.answer.is_correct === true) return 'border-green-300'
  if (props.answer.is_correct === false) return 'border-red-300'
  return 'border-gray-200'
})

// Extract question text
const questionText = computed(() => {
  const question = props.answer.exercise || props.answer.question
  if (!question) return 'Không có câu hỏi'

  // If content is a JSON object with 'question' field, extract it
  if (question.content) {
    try {
      // Parse if it's a string
      const content = typeof question.content === 'string'
        ? JSON.parse(question.content)
        : question.content

      if (content.question) {
        return content.question
      }
    } catch (e) {
      // If parsing fails, use as is
      if (typeof question.content === 'string') {
        return question.content
      }
    }
  }

  // Fallback to title or other fields
  return question.title ||
         question.question_text ||
         question.text ||
         'Không có nội dung câu hỏi'
})

// Extract student answer text
const studentAnswerText = computed(() => {
  const answer = props.answer.answer_text ||
                props.answer.answer ||
                props.answer.text_answer

  if (!answer) return 'Không có câu trả lời'

  // Get exercise to check for options
  const question = props.answer.exercise || props.answer.question

  // For multiple choice questions, convert option ID to label
  if (question && question.options && Array.isArray(question.options)) {
    // If answer is a number (option ID), find the corresponding option label
    const answerId = parseInt(answer)
    if (!isNaN(answerId)) {
      const selectedOption = question.options.find(opt => opt.id === answerId)
      if (selectedOption && selectedOption.label) {
        return selectedOption.label
      }
    }
  }

  // Handle JSON answers
  if (typeof answer === 'object') {
    return JSON.stringify(answer, null, 2)
  }

  return String(answer)
})

// Extract correct answer
const correctAnswer = computed(() => {
  const question = props.answer.exercise || props.answer.question
  if (!question) return null

  return question.correct_answer || null
})
</script>

<style scoped>
.grading-answer-card {
  transition: all 0.2s;
}

.grading-answer-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
