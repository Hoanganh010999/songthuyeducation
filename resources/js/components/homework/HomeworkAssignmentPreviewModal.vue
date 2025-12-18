<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[70]" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="bg-orange-600 text-white px-6 py-4 flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold">{{ homework.title }}</h2>
          <div class="flex items-center space-x-3 mt-2 text-sm">
            <span v-if="homework.deadline" class="flex items-center space-x-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span>Hạn: {{ formatDate(homework.deadline) }}</span>
            </span>
            <span v-if="homework.exercises" class="flex items-center space-x-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span>{{ homework.exercises.length }} câu hỏi</span>
            </span>
            <span v-if="totalPoints > 0" class="flex items-center space-x-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
              </svg>
              <span>Tổng: {{ totalPoints }} điểm</span>
            </span>
          </div>
        </div>
        <button @click="$emit('close')" class="text-white hover:bg-orange-700 rounded-lg p-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <!-- Description -->
        <div v-if="homework.description" class="mb-6 p-4 bg-orange-50 rounded-lg border border-orange-200">
          <h3 class="text-sm font-semibold text-orange-900 mb-2">Mô tả:</h3>
          <div class="text-sm text-gray-700" v-html="homework.description"></div>
        </div>

        <!-- Session Info -->
        <div v-if="homework.session" class="mb-6 p-3 bg-blue-50 rounded-lg border border-blue-200">
          <div class="flex items-center space-x-2 text-sm text-blue-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <span class="font-medium">Buổi {{ homework.session.session_number }}: {{ homework.session.lesson_title }}</span>
          </div>
        </div>

        <!-- Exercises List -->
        <div v-if="homework.exercises && homework.exercises.length > 0" class="space-y-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-3">Danh sách câu hỏi:</h3>

          <div
            v-for="(exercise, index) in homework.exercises"
            :key="exercise.id"
            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
          >
            <!-- Exercise Header -->
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-start space-x-3 flex-1">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 text-orange-600 font-semibold text-sm flex-shrink-0">
                  {{ index + 1 }}
                </span>
                <div class="flex-1">
                  <h4 class="font-semibold text-gray-900 mb-1">{{ exercise.title }}</h4>
                  <div class="flex items-center space-x-3 text-xs text-gray-500">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded">{{ exercise.type }}</span>
                    <span v-if="exercise.skill" class="px-2 py-0.5 bg-green-100 text-green-700 rounded">{{ exercise.skill }}</span>
                    <span v-if="exercise.difficulty" class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded">{{ exercise.difficulty }}</span>
                  </div>
                </div>
              </div>
              <div class="text-right">
                <span class="text-sm font-semibold text-orange-600">{{ exercise.pivot?.points || exercise.points || 0 }} điểm</span>
              </div>
            </div>

            <!-- Exercise Preview Button -->
            <button
              @click="previewExercise(exercise)"
              class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center space-x-1"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <span>Xem chi tiết</span>
            </button>
          </div>
        </div>

        <!-- No exercises message -->
        <div v-else class="text-center py-8 text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p>Bài tập này chưa có câu hỏi</p>
        </div>

        <!-- Metadata -->
        <div class="mt-6 pt-4 border-t text-xs text-gray-500 space-y-1">
          <p>Được tạo bởi: <span class="font-medium">{{ homework.creator?.name || 'N/A' }}</span></p>
          <p v-if="homework.created_at">Ngày tạo: <span class="font-medium">{{ formatDate(homework.created_at) }}</span></p>
        </div>
      </div>

      <!-- Footer -->
      <div class="border-t px-6 py-4 bg-gray-50 flex justify-end">
        <button
          @click="$emit('close')"
          class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700"
        >
          Đóng
        </button>
      </div>
    </div>
  </div>

  <!-- Exercise Preview Modal -->
  <ExercisePreviewModalV2
    v-if="selectedExercise"
    :exercise="selectedExercise"
    @close="selectedExercise = null"
  />
</template>

<script setup>
import { ref, computed } from 'vue';
import ExercisePreviewModalV2 from './ExercisePreviewModalV2.vue';

const props = defineProps({
  homework: {
    type: Object,
    required: true
  }
});

defineEmits(['close']);

const selectedExercise = ref(null);

// Calculate total points
const totalPoints = computed(() => {
  if (!props.homework.exercises || props.homework.exercises.length === 0) return 0;

  return props.homework.exercises.reduce((sum, exercise) => {
    return sum + (exercise.pivot?.points || exercise.points || 0);
  }, 0);
});

function previewExercise(exercise) {
  selectedExercise.value = exercise;
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
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
