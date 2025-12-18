<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="bg-purple-600 text-white px-6 py-4 flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold">Tạo Bài Tập Homework</h2>
          <p class="text-sm text-purple-100 mt-1">Buổi {{ sessionNumber }}: {{ sessionTitle }}</p>
        </div>
        <button @click="$emit('close')" class="text-white hover:bg-purple-700 rounded-lg p-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <!-- Assignment Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tiêu đề bài tập</label>
            <input
              v-model="assignmentData.title"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="Vd: Homework Buổi 1"
            />
          </div>
        </div>

        <!-- Selected Exercises -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Các câu hỏi đã chọn ({{ selectedExercises.length }})</h3>
            <div class="flex space-x-2">
              <button
                @click="showAIGenerator = true"
                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 flex items-center space-x-2 shadow-md"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <span>Tạo bằng AI</span>
              </button>
              <button
                @click="showExerciseBank = true"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center space-x-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>Chọn từ Ngân hàng</span>
              </button>
              <button
                @click="showExerciseForm = true"
                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center space-x-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tạo câu hỏi mới</span>
              </button>
            </div>
          </div>

          <!-- Exercise List (Grouped by Passage) -->
          <div v-if="selectedExercises.length > 0" class="space-y-4">
            <!-- Grouped Exercises -->
            <div
              v-for="group in groupedExercises.groups"
              :key="group.id"
              class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-300 rounded-lg p-4"
            >
              <div class="mb-3 pb-3 border-b border-blue-200">
                <div class="flex items-center space-x-2 mb-2">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                  </svg>
                  <h4 class="font-semibold text-blue-900">{{ group.title }}</h4>
                  <span class="px-2 py-0.5 text-xs bg-blue-200 text-blue-800 rounded">{{ group.exercises.length }} câu hỏi</span>
                </div>
                <div class="text-sm text-gray-700 bg-white/70 rounded p-3 leading-relaxed max-h-32 overflow-y-auto">
                  {{ group.passage }}
                </div>
              </div>
              <div class="space-y-2">
                <div
                  v-for="exercise in group.exercises"
                  :key="exercise.id"
                  class="bg-white border border-blue-200 rounded-lg p-3 hover:border-blue-400 transition"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1 cursor-pointer" @click="previewExercise(exercise)">
                      <div class="flex items-center space-x-2 mb-1">
                        <span class="text-sm font-semibold text-gray-700">Q{{ exercise.settings?.question_number }}</span>
                        <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">{{ exercise.type }}</span>
                      </div>
                      <p class="text-sm text-gray-900 hover:text-blue-600">
                        {{ exercise.content?.question || exercise.title }}
                      </p>
                    </div>
                    <div class="flex items-center space-x-2 ml-4">
                      <input
                        v-model.number="exercise.points"
                        type="number"
                        step="0.5"
                        min="0"
                        class="w-16 px-2 py-1 text-sm border border-gray-300 rounded"
                      />
                      <button
                        @click="removeExercise(exercise.originalIndex)"
                        class="text-red-600 hover:bg-red-50 p-1 rounded"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Ungrouped Exercises -->
            <div
              v-for="exercise in groupedExercises.ungrouped"
              :key="exercise.id"
              class="bg-white border border-gray-200 rounded-lg p-4 hover:border-purple-300 transition cursor-move"
            >
              <div class="flex items-start space-x-3">
                <div class="text-gray-400 pt-1">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center space-x-2 mb-2">
                        <span class="text-sm font-semibold text-gray-700">Câu {{ exercise.originalIndex + 1 }}</span>
                        <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">{{ exercise.type }}</span>
                        <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded">{{ exercise.skill }}</span>
                        <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">{{ exercise.difficulty }}</span>
                      </div>
                      <h4
                        @click="previewExercise(exercise)"
                        class="font-medium text-gray-900 hover:text-purple-600 cursor-pointer"
                      >
                        {{ exercise.title }}
                      </h4>
                    </div>
                    <div class="flex items-center space-x-2 ml-4">
                      <input
                        v-model.number="exercise.points"
                        type="number"
                        step="0.5"
                        min="0"
                        class="w-16 px-2 py-1 text-sm border border-gray-300 rounded"
                      />
                      <button
                        @click="removeExercise(exercise.originalIndex)"
                        class="text-red-600 hover:bg-red-50 p-1 rounded"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mt-2 text-sm text-gray-600">Chưa có câu hỏi nào</p>
            <p class="text-xs text-gray-500">Nhấn "Chọn từ Ngân hàng" hoặc "Tạo câu hỏi mới"</p>
          </div>
        </div>

        <!-- Total Points -->
        <div class="bg-gray-50 rounded-lg p-4">
          <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">Tổng điểm:</span>
            <span class="text-xl font-bold text-purple-600">{{ totalPoints }} điểm</span>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="border-t px-6 py-4 bg-gray-50 flex justify-between items-center">
        <div class="text-sm text-gray-600">
          {{ selectedExercises.length }} câu hỏi • {{ totalPoints }} điểm
        </div>
        <div class="flex space-x-3">
          <button
            @click="$emit('close')"
            class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100"
          >
            Hủy
          </button>
          <button
            @click="saveAssignment"
            :disabled="saving || selectedExercises.length === 0"
            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
          >
            <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ saving ? 'Đang lưu...' : 'Lưu bài tập' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Exercise Bank Modal -->
    <ExerciseBankModal
      v-if="showExerciseBank"
      @close="showExerciseBank = false"
      @select="addExercises"
      :selectedIds="selectedExercises.map(e => e.id)"
      :subjectId="subjectId"
      :subject="subject"
    />

    <!-- Question Editor Modal -->
    <QuestionEditor
      v-if="showExerciseForm"
      @close="showExerciseForm = false"
      @saved="addNewExercise"
      :subjectId="subjectId"
      :subject="subject"
      context="homework"
    />

    <!-- Exercise Preview Modal -->
    <ExercisePreviewModal
      v-if="previewingExercise"
      :exercise="previewingExercise"
      @close="previewingExercise = null"
    />

    <!-- AI Homework Generator Modal -->
    <AIHomeworkGenerator
      v-if="showAIGenerator"
      :session="session"
      :subjectId="subjectId"
      :subject="subject"
      @close="showAIGenerator = false"
      @generated="addGeneratedExercises"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import ExerciseBankModal from './ExerciseBankModal.vue';
import QuestionEditor from '../examination/questions/QuestionEditor.vue';
import ExercisePreviewModal from './ExercisePreviewModal.vue';
import AIHomeworkGenerator from './AIHomeworkGenerator.vue';

const props = defineProps({
  session: {
    type: Object,
    required: true
  },
  classId: {
    type: Number,
    required: true
  },
  subjectId: {
    type: Number,
    required: false
  },
  subject: {
    type: Object,
    required: false
  },
  editingHomework: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const sessionNumber = computed(() => props.session.session_number);
const sessionTitle = computed(() => props.session.lesson_title);

const assignmentData = ref({
  title: `Homework Buổi ${props.session.session_number}`,
  description: null
});

const selectedExercises = ref([]);
const showExerciseBank = ref(false);
const showExerciseForm = ref(false);
const showAIGenerator = ref(false);
const previewingExercise = ref(null);
const saving = ref(false);

const totalPoints = computed(() => {
  return selectedExercises.value.reduce((sum, ex) => sum + (parseFloat(ex.points) || 0), 0);
});

// Group exercises by group_id (for AI-generated grouped exercises)
const groupedExercises = computed(() => {
  const groups = {};
  const ungrouped = [];

  selectedExercises.value.forEach((exercise, index) => {
    const groupId = exercise.settings?.group_id;

    if (groupId) {
      if (!groups[groupId]) {
        groups[groupId] = {
          id: groupId,
          passage: exercise.content?.passage || '',
          title: exercise.title?.split(' - Q')[0] || 'Reading Passage',
          exercises: []
        };
      }
      groups[groupId].exercises.push({ ...exercise, originalIndex: index });
    } else {
      ungrouped.push({ ...exercise, originalIndex: index });
    }
  });

  return { groups: Object.values(groups), ungrouped };
});

// Load editing homework data if exists
onMounted(() => {
  if (props.editingHomework) {
    // Load assignment data
    assignmentData.value = {
      title: props.editingHomework.title,
      description: props.editingHomework.description
    };

    // Load exercises
    if (props.editingHomework.exercises && props.editingHomework.exercises.length > 0) {
      selectedExercises.value = props.editingHomework.exercises.map((ex, index) => ({
        ...ex,
        points: ex.pivot?.points || ex.points || 1,
        originalIndex: index
      }));
    }
  }
});

function addExercises(exercises) {
  // Add exercises from bank
  exercises.forEach(exercise => {
    if (!selectedExercises.value.find(e => e.id === exercise.id)) {
      selectedExercises.value.push({
        ...exercise,
        points: exercise.points || 1
      });
    }
  });
  showExerciseBank.value = false;
}

function addNewExercise(exercise) {
  selectedExercises.value.push({
    ...exercise,
    points: exercise.points || 1
  });
  showExerciseForm.value = false;
}

function addGeneratedExercises(exercises) {
  // Add AI-generated exercises
  exercises.forEach(exercise => {
    selectedExercises.value.push({
      ...exercise,
      points: exercise.points || 1
    });
  });
  showAIGenerator.value = false;
}

function removeExercise(index) {
  selectedExercises.value.splice(index, 1);
}

function previewExercise(exercise) {
  previewingExercise.value = exercise;
}

async function saveAssignment() {
  if (selectedExercises.value.length === 0) {
    await Swal.fire({
      icon: 'warning',
      title: 'Chưa có câu hỏi',
      text: 'Vui lòng thêm ít nhất 1 câu hỏi vào bài tập'
    });
    return;
  }

  if (!assignmentData.value.title) {
    await Swal.fire({
      icon: 'warning',
      title: 'Thiếu tiêu đề',
      text: 'Vui lòng nhập tiêu đề cho bài tập'
    });
    return;
  }

  try {
    saving.value = true;

    // Determine if creating or updating
    const isEditing = !!props.editingHomework;
    const method = isEditing ? 'put' : 'post';
    const url = isEditing
      ? `/api/homework/bank/${props.editingHomework.id}`
      : '/api/homework/bank';

    const payload = {
      lesson_plan_session_id: props.session.id,
      subject_id: props.subjectId || null,
      title: assignmentData.value.title,
      description: assignmentData.value.description,
      exercise_ids: selectedExercises.value.map(e => e.id),
      exercise_data: selectedExercises.value.map((e, index) => ({
        exercise_id: e.id,
        points: e.points,
        sort_order: index,
        is_required: true
      }))
    };

    const response = await axios[method](url, payload, {
      headers: {
        'X-Branch-Id': localStorage.getItem('current_branch_id')
      }
    });

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: isEditing ? 'Đã cập nhật bài tập homework' : 'Đã tạo bài tập homework',
        timer: 2000,
        showConfirmButton: false
      });

      emit('saved', response.data.data);
      emit('close');
    }
  } catch (error) {
    console.error('Error saving assignment:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể lưu bài tập'
    });
  } finally {
    saving.value = false;
  }
}
</script>
