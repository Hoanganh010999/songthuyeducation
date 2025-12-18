<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[85vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="bg-green-600 text-white px-6 py-4 flex items-center justify-between">
        <h2 class="text-xl font-semibold">Ngân hàng câu hỏi</h2>
        <button @click="$emit('close')" class="text-white hover:bg-green-700 rounded-lg p-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Filters -->
      <div class="p-4 bg-gray-50 border-b">
        <div class="grid grid-cols-3 gap-3 mb-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Môn học</label>
            <select v-model="filters.subject_id" @change="onSubjectFilterChange" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
              <option :value="null">Tất cả</option>
              <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Phân loại</label>
            <select v-model="filters.subject_category_id" :disabled="!filters.subject_id || categories.length === 0" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg disabled:bg-gray-100">
              <option :value="null">Tất cả</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Tìm kiếm</label>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Tìm theo tiêu đề..."
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"
            />
          </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Kỹ năng</label>
            <select v-model="filters.skill" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
              <option value="">Tất cả</option>
              <option value="reading">Reading</option>
              <option value="writing">Writing</option>
              <option value="listening">Listening</option>
              <option value="speaking">Speaking</option>
              <option value="grammar">Grammar</option>
              <option value="vocabulary">Vocabulary</option>
              <option value="math">Math</option>
              <option value="science">Science</option>
              <option value="general">General</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Độ khó</label>
            <select v-model="filters.difficulty" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
              <option value="">Tất cả</option>
              <option value="easy">Easy</option>
              <option value="medium">Medium</option>
              <option value="hard">Hard</option>
              <option value="expert">Expert</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Loại câu hỏi</label>
            <select v-model="filters.type" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
              <option value="">Tất cả</option>
              <optgroup label="Basic Types">
                <option value="multiple_choice">Multiple Choice</option>
                <option value="multiple_response">Multiple Response</option>
                <option value="true_false">True/False</option>
                <option value="true_false_ng">True/False/Not Given</option>
                <option value="short_answer">Short Answer</option>
                <option value="essay">Essay</option>
              </optgroup>
              <optgroup label="Fill & Complete">
                <option value="fill_blanks">Fill in the Blanks</option>
                <option value="fill_blanks_drag">Fill Blanks (Drag & Drop)</option>
                <option value="sentence_completion">Sentence Completion</option>
                <option value="summary_completion">Summary Completion</option>
                <option value="note_completion">Note Completion</option>
                <option value="table_completion">Table Completion</option>
              </optgroup>
              <optgroup label="Matching & Ordering">
                <option value="matching">Matching</option>
                <option value="matching_headings">Matching Headings</option>
                <option value="matching_features">Matching Features</option>
                <option value="matching_sentence_endings">Matching Sentence Endings</option>
                <option value="ordering">Ordering</option>
              </optgroup>
              <optgroup label="Interactive">
                <option value="drag_drop">Drag & Drop</option>
                <option value="hotspot">Hotspot</option>
                <option value="labeling">Labeling</option>
                <option value="flow_chart">Flow Chart</option>
              </optgroup>
              <optgroup label="Media Response">
                <option value="audio_response">Audio Response</option>
              </optgroup>
            </select>
          </div>
        </div>
      </div>

      <!-- Exercise List -->
      <div class="flex-1 overflow-y-auto p-4">
        <div v-if="loading" class="text-center py-12">
          <svg class="animate-spin h-8 w-8 text-green-600 mx-auto" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-gray-600 mt-2">Đang tải...</p>
        </div>

        <div v-else-if="exercises.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p class="mt-2 text-sm text-gray-600">Không tìm thấy câu hỏi nào</p>
        </div>

        <div v-else class="space-y-2">
          <div
            v-for="exercise in exercises"
            :key="exercise.id"
            @click="toggleSelect(exercise)"
            class="border rounded-lg p-4 cursor-pointer transition"
            :class="isSelected(exercise.id) ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-green-300 hover:bg-gray-50'"
          >
            <div class="flex items-start space-x-3">
              <!-- Checkbox -->
              <div class="pt-1">
                <div
                  class="w-5 h-5 rounded border-2 flex items-center justify-center"
                  :class="isSelected(exercise.id) ? 'bg-green-600 border-green-600' : 'border-gray-300'"
                >
                  <svg v-if="isSelected(exercise.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2 mb-2">
                  <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">{{ exercise.type }}</span>
                  <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded">{{ exercise.skill }}</span>
                  <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">{{ exercise.difficulty }}</span>
                  <span class="text-xs text-gray-500">{{ exercise.points }} điểm</span>
                </div>
                <h4 class="font-medium text-gray-900">{{ exercise.title }}</h4>
                <p v-if="exercise.instructions" class="text-sm text-gray-600 mt-1 line-clamp-2">{{ exercise.instructions }}</p>

                <!-- Show options preview for MCQ -->
                <div v-if="exercise.options && exercise.options.length > 0" class="mt-2 text-xs text-gray-500">
                  {{ exercise.options.length }} lựa chọn
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="border-t px-6 py-4 bg-gray-50 flex justify-between items-center">
        <div class="text-sm text-gray-600">
          Đã chọn: <span class="font-semibold">{{ selectedCount }}</span> câu hỏi
        </div>
        <div class="flex space-x-3">
          <button
            @click="$emit('close')"
            class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100"
          >
            Hủy
          </button>
          <button
            @click="confirmSelection"
            :disabled="selectedCount === 0"
            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Chọn ({{ selectedCount }})
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  selectedIds: {
    type: Array,
    default: () => []
  },
  subjectId: {
    type: Number,
    default: null
  },
  subject: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'select']);

const subjects = ref([]);
const categories = ref([]);

const filters = ref({
  subject_id: null,
  subject_category_id: null,
  skill: '',
  difficulty: '',
  type: '',
  search: ''
});

const exercises = ref([]);
const loading = ref(false);
const selected = ref(new Set(props.selectedIds));

const selectedCount = computed(() => selected.value.size);

function isSelected(id) {
  return selected.value.has(id);
}

function toggleSelect(exercise) {
  if (selected.value.has(exercise.id)) {
    selected.value.delete(exercise.id);
  } else {
    selected.value.add(exercise.id);
  }
}

function confirmSelection() {
  const selectedExercises = exercises.value.filter(ex => selected.value.has(ex.id));
  emit('select', selectedExercises);
}

async function loadSubjects() {
  try {
    const response = await axios.get('/api/examination/subjects', {
      headers: {
        'X-Branch-Id': localStorage.getItem('current_branch_id')
      }
    });
    if (response.data.success) {
      subjects.value = response.data.data;
    }
  } catch (error) {
    console.error('Error loading subjects:', error);
  }
}

async function loadCategories(subjectId) {
  try {
    const response = await axios.get(`/api/examination/subjects/${subjectId}/categories`, {
      headers: {
        'X-Branch-Id': localStorage.getItem('current_branch_id')
      }
    });
    if (response.data.success) {
      categories.value = response.data.data;
    }
  } catch (error) {
    console.error('Error loading categories:', error);
    categories.value = [];
  }
}

async function onSubjectFilterChange() {
  filters.value.subject_category_id = null;
  categories.value = [];

  if (filters.value.subject_id) {
    await loadCategories(filters.value.subject_id);
  }
}

async function loadExercises() {
  try {
    loading.value = true;

    // Debug: Check localStorage
    const branchId = localStorage.getItem('current_branch_id');
    console.log('[ExerciseBankModal] Loading exercises with branch_id:', branchId);
    console.log('[ExerciseBankModal] All localStorage keys:', Object.keys(localStorage));

    if (!branchId) {
      console.warn('[ExerciseBankModal] No current_branch_id in localStorage!');
      // Try to get from user if logged in
      // For now, just log the warning
    }

    const params = {
      active_only: true,
      per_page: 100
    };

    if (filters.value.subject_id) params.subject_id = filters.value.subject_id;
    if (filters.value.subject_category_id) params.subject_category_id = filters.value.subject_category_id;
    if (filters.value.skill) params.skill = filters.value.skill;
    if (filters.value.difficulty) params.difficulty = filters.value.difficulty;
    if (filters.value.type) params.type = filters.value.type;
    if (filters.value.search) params.search = filters.value.search;

    console.log('[ExerciseBankModal] Request params:', params);

    const response = await axios.get('/api/examination/questions', {
      params,
      headers: {
        'X-Branch-Id': branchId
      }
    });

    console.log('[ExerciseBankModal] Response:', response.data);

    if (response.data.success) {
      exercises.value = response.data.data.data || response.data.data;
      console.log('[ExerciseBankModal] Loaded exercises:', exercises.value.length);
    }
  } catch (error) {
    console.error('[ExerciseBankModal] Error loading exercises:', error);
    if (error.response) {
      console.error('[ExerciseBankModal] Response data:', error.response.data);
      console.error('[ExerciseBankModal] Response status:', error.response.status);

      // Show user-friendly error
      if (error.response.status === 400) {
        alert('Lỗi: ' + (error.response.data.message || 'Không thể tải danh sách câu hỏi. Vui lòng đăng nhập lại.'));
      } else if (error.response.status === 401) {
        alert('Lỗi: Vui lòng đăng nhập lại.');
      }
    }
  } finally {
    loading.value = false;
  }
}

// Watch filters and reload
watch(filters, () => {
  loadExercises();
}, { deep: true });

onMounted(async () => {
  loadSubjects();

  // Auto-select subject if provided from props
  if (props.subjectId) {
    filters.value.subject_id = props.subjectId;
    await loadCategories(props.subjectId);
  }

  loadExercises();
});
</script>
