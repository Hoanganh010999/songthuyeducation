<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[70]" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="bg-blue-600 text-white px-6 py-4 flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold">Xem trước câu hỏi</h2>
          <div class="flex items-center space-x-2 mt-1">
            <span class="px-2 py-0.5 text-xs bg-blue-500 rounded">{{ exercise.type }}</span>
            <span v-if="exercise.skill" class="px-2 py-0.5 text-xs bg-blue-500 rounded">{{ exercise.skill }}</span>
            <span v-if="exercise.difficulty" class="px-2 py-0.5 text-xs bg-blue-500 rounded">{{ exercise.difficulty }}</span>
          </div>
        </div>
        <button @click="$emit('close')" class="text-white hover:bg-blue-700 rounded-lg p-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <!-- Points -->
        <div class="mb-4 flex items-center justify-between">
          <span class="text-sm text-gray-600">Điểm: <span class="font-semibold text-gray-900">{{ exercise.points || exercise.pivot?.points || 0 }}</span></span>
          <span v-if="exercise.time_limit" class="text-sm text-gray-600">Thời gian: <span class="font-semibold text-gray-900">{{ exercise.time_limit }}s</span></span>
        </div>

        <!-- Instructions -->
        <div v-if="exerciseContent.instructions" class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
          <p class="text-sm font-medium text-gray-700" v-html="exerciseContent.instructions"></p>
        </div>

        <!-- Reading Passage -->
        <div v-if="exerciseContent.passage" class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-300">
          <div class="flex items-center space-x-2 mb-3">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <h4 class="font-semibold text-gray-900">Đoạn văn:</h4>
          </div>
          <div class="text-gray-800 leading-relaxed whitespace-pre-wrap text-justify" v-html="formatText(exerciseContent.passage)"></div>
        </div>

        <!-- Use QuestionRenderer for the actual question -->
        <QuestionRenderer
          :question="questionData"
          :questionNumber="1"
          :showResult="true"
          disabled
        />

        <!-- Explanation -->
        <div v-if="exercise.explanation" class="mt-4 p-3 bg-purple-50 rounded-lg border border-purple-200">
          <p class="text-sm font-medium text-gray-700 mb-1">Giải thích:</p>
          <p class="text-sm text-gray-700" v-html="exercise.explanation"></p>
        </div>

        <!-- Metadata -->
        <div class="mt-6 pt-4 border-t text-xs text-gray-500 space-y-1">
          <p>Được tạo bởi: <span class="font-medium">{{ exercise.creator?.name || 'N/A' }}</span></p>
          <p>Đã sử dụng: <span class="font-medium">{{ exercise.usage_count || 0 }}</span> lần</p>
          <p v-if="exercise.created_at">Ngày tạo: <span class="font-medium">{{ formatDate(exercise.created_at) }}</span></p>
        </div>
      </div>

      <!-- Footer -->
      <div class="border-t px-6 py-4 bg-gray-50 flex justify-end">
        <button
          @click="$emit('close')"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Đóng
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import QuestionRenderer from '../examination/questions/QuestionRenderer.vue';

const props = defineProps({
  exercise: {
    type: Object,
    required: true
  }
});

defineEmits(['close']);

// Parse content JSON to get passage, question, instructions
const exerciseContent = computed(() => {
  if (typeof props.exercise.content === 'string') {
    try {
      return JSON.parse(props.exercise.content);
    } catch (e) {
      return {
        passage: null,
        question: props.exercise.content,
        instructions: null
      };
    }
  }
  return props.exercise.content || {};
});

// Convert exercise to question format for QuestionRenderer
const questionData = computed(() => {
  return {
    id: props.exercise.id,
    title: exerciseContent.value.question || props.exercise.title,
    type: props.exercise.type,
    points: props.exercise.points || props.exercise.pivot?.points || 0,
    options: props.exercise.options || [],
    explanation: props.exercise.explanation,
    image_url: exerciseContent.value.image_url || null,
    correct_answer: props.exercise.correct_answer
  };
});

// Format text with markdown-style bold and italic to HTML
function formatText(text) {
  if (!text) return '';

  // Convert **bold** to <strong>bold</strong>
  text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

  // Convert *italic* to <em>italic</em>
  text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');

  // Convert line breaks to <br>
  text = text.replace(/\n/g, '<br>');

  return text;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
}
</script>
