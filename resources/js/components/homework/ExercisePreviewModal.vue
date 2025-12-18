<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[70]" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="bg-blue-600 text-white px-6 py-4 flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold">Xem trước câu hỏi</h2>
          <div class="flex items-center space-x-2 mt-1">
            <span class="px-2 py-0.5 text-xs bg-blue-500 rounded">{{ exercise.type }}</span>
            <span class="px-2 py-0.5 text-xs bg-blue-500 rounded">{{ exercise.skill }}</span>
            <span class="px-2 py-0.5 text-xs bg-blue-500 rounded">{{ exercise.difficulty }}</span>
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
        <!-- Title -->
        <div class="mb-4">
          <h3 class="text-lg font-semibold text-gray-900">{{ exercise.title }}</h3>
          <div class="flex items-center space-x-4 mt-2 text-sm text-gray-600">
            <span>Điểm: <span class="font-semibold">{{ exercise.points }}</span></span>
            <span v-if="exercise.time_limit">Thời gian: <span class="font-semibold">{{ exercise.time_limit }}s</span></span>
          </div>
        </div>

        <!-- Instructions -->
        <div v-if="exerciseContent.instructions" class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
          <p class="text-sm font-medium text-gray-700" v-html="formatText(exerciseContent.instructions)"></p>
        </div>

        <!-- Question with short instruction -->
        <div v-if="exerciseContent.question && !exerciseContent.passage" class="mb-4">
          <div class="flex items-start space-x-2 mb-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="font-medium text-gray-900 text-base" v-html="formatText(exerciseContent.question)"></p>
          </div>
        </div>

        <!-- Main Question (Passage or Question content) -->
        <div v-if="exerciseContent.passage || (exerciseContent.question && exerciseContent.passage)" class="mb-6 p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
          <div class="flex items-center space-x-2 mb-3">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h4 class="font-semibold text-gray-900">Câu hỏi:</h4>
          </div>
          <div class="text-gray-900 leading-relaxed" v-html="formatText(exerciseContent.passage)"></div>
        </div>

        <!-- Options (for MCQ) -->
        <div v-if="exercise.options && exercise.options.length > 0" class="space-y-2 mb-4">
          <div
            v-for="option in exercise.options"
            :key="option.id"
            class="flex items-start space-x-3 p-3 rounded-lg border"
            :class="option.is_correct ? 'bg-green-50 border-green-300' : 'bg-gray-50 border-gray-200'"
          >
            <span class="font-semibold text-gray-700">{{ option.label }}.</span>
            <span class="flex-1" v-html="formatText(option.content)"></span>
            <svg v-if="option.is_correct" class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
        </div>

        <!-- Correct Answer -->
        <div v-if="exercise.correct_answer" class="mb-4 p-3 bg-green-50 rounded-lg border border-green-200">
          <p class="text-sm font-medium text-gray-700 mb-1">Đáp án đúng:</p>
          <p class="text-gray-900 font-semibold">{{ formatAnswer(exercise.correct_answer) }}</p>
        </div>

        <!-- Sample Answer -->
        <div v-else-if="exerciseContent.sample_answer" class="mb-4 p-3 bg-green-50 rounded-lg border border-green-200">
          <p class="text-sm font-medium text-gray-700 mb-1">Câu trả lời mẫu:</p>
          <p class="text-gray-900" v-html="formatText(exerciseContent.sample_answer)"></p>
        </div>

        <!-- Explanation -->
        <div v-if="exercise.explanation" class="mb-4 p-3 bg-purple-50 rounded-lg border border-purple-200">
          <p class="text-sm font-medium text-gray-700 mb-1">Giải thích:</p>
          <p class="text-sm text-gray-700" v-html="formatText(exercise.explanation)"></p>
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

const props = defineProps({
  exercise: {
    type: Object,
    required: true
  }
});

defineEmits(['close']);

// Parse content JSON to get passage, question, instructions
const exerciseContent = computed(() => {
  let content;
  if (typeof props.exercise.content === 'string') {
    try {
      content = JSON.parse(props.exercise.content);
    } catch (e) {
      content = {
        passage: null,
        question: props.exercise.content,
        instructions: null
      };
    }
  } else {
    content = props.exercise.content || {};
  }

  // Debug: log the exercise structure
  console.log('Exercise:', props.exercise);
  console.log('Exercise Content:', content);

  return content;
});

function formatText(text) {
  if (!text) return '';

  // Convert to string if it's not already
  let str = String(text);

  // Convert **bold** to <strong>bold</strong>
  str = str.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

  // Convert *italic* to <em>italic</em> (but not ** which is already handled)
  str = str.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');

  // Convert line breaks to <br>
  str = str.replace(/\n/g, '<br>');

  return str;
}

function formatAnswer(answer) {
  if (Array.isArray(answer)) {
    return answer.join(', ');
  }
  if (typeof answer === 'object' && answer !== null) {
    return JSON.stringify(answer, null, 2);
  }
  return answer || 'N/A';
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
