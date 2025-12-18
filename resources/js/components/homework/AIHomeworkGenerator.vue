<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-4 flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold flex items-center space-x-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <span>Tạo Homework bằng AI</span>
          </h2>
          <p class="text-sm text-blue-100 mt-1">Buổi {{ sessionNumber }}: {{ sessionTitle }}</p>
        </div>
        <button @click="$emit('close')" class="text-white hover:bg-white/20 rounded-lg p-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
              <h4 class="font-medium text-blue-900">Hướng dẫn</h4>
              <p class="text-sm text-blue-700 mt-1">Chọn materials của buổi học này. AI sẽ tạo câu hỏi homework dựa trên nội dung đã học.</p>
            </div>
          </div>
        </div>

        <!-- Loading Materials -->
        <div v-if="loadingMaterials" class="text-center py-12">
          <svg class="animate-spin h-8 w-8 mx-auto text-purple-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-gray-600 mt-3">Đang tải materials...</p>
        </div>

        <!-- Materials List -->
        <div v-else-if="materials.length > 0">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Chọn Materials ({{ selectedMaterials.length }}/{{ materials.length }})</h3>

          <div class="space-y-3">
            <div
              v-for="material in materials"
              :key="material.id"
              @click="toggleMaterial(material.id)"
              :class="[
                'border rounded-lg p-4 cursor-pointer transition',
                selectedMaterials.includes(material.id)
                  ? 'border-purple-500 bg-purple-50'
                  : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50'
              ]"
            >
              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 mt-1">
                  <div :class="[
                    'w-5 h-5 rounded border-2 flex items-center justify-center',
                    selectedMaterials.includes(material.id)
                      ? 'border-purple-500 bg-purple-500'
                      : 'border-gray-300'
                  ]">
                    <svg v-if="selectedMaterials.includes(material.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                  </div>
                </div>
                <div class="flex-1">
                  <h4 class="font-medium text-gray-900">{{ material.title }}</h4>
                  <p v-if="material.description" class="text-sm text-gray-600 mt-1">{{ material.description }}</p>
                  <div class="flex items-center space-x-3 mt-2 text-xs text-gray-500">
                    <span>{{ formatDate(material.created_at) }}</span>
                    <span>{{ getContentLength(material.content) }} từ</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button
            v-if="materials.length > 0"
            @click="toggleAllMaterials"
            class="mt-3 text-sm text-purple-600 hover:text-purple-700 font-medium"
          >
            {{ selectedMaterials.length === materials.length ? 'Bỏ chọn tất cả' : 'Chọn tất cả' }}
          </button>
        </div>

        <!-- No Materials -->
        <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p class="mt-2 text-sm text-gray-600">Chưa có materials cho buổi học này</p>
          <p class="text-xs text-gray-500 mt-1">Tạo materials trước khi generate homework</p>
        </div>

        <!-- Generation Settings -->
        <div v-if="materials.length > 0" class="mt-6 bg-gray-50 rounded-lg p-4">
          <h4 class="font-medium text-gray-900 mb-3">Cài đặt</h4>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Độ khó</label>
            <select
              v-model="settings.difficulty"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option value="easy">Dễ</option>
              <option value="medium">Trung bình</option>
              <option value="hard">Khó</option>
              <option value="mixed">Hỗn hợp</option>
            </select>
          </div>
        </div>

        <!-- Subject Selection -->
        <div v-if="materials.length > 0" class="mt-6 border rounded-lg p-4 bg-gradient-to-br from-blue-50 to-indigo-50">
          <label class="text-sm font-medium text-gray-900 flex items-center gap-2 mb-3">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            Chọn môn học *
          </label>
          <select
            v-model="selectedSubject"
            class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :disabled="loadingSubjects"
          >
            <option :value="null">-- Chọn môn học --</option>
            <option v-for="subject in examSubjects" :key="subject.id" :value="subject.id">
              {{ subject.icon }} {{ subject.name }}
            </option>
          </select>
        </div>

        <!-- Exercise Categories Selection -->
        <div v-if="materials.length > 0 && selectedSubject" class="mt-6 border rounded-lg p-4 bg-gradient-to-br from-indigo-50 to-purple-50">
          <div class="flex items-center justify-between mb-3">
            <div>
              <label class="text-sm font-medium text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
                Phân loại bài tập *
              </label>
              <p class="text-xs text-gray-600 mt-1">Chọn các kỹ năng cần luyện tập</p>
            </div>
            <button
              v-if="categories.length > 0"
              type="button"
              @click="toggleAllCategories"
              class="text-sm text-purple-600 hover:text-purple-800"
            >
              {{ selectedCategories.length === categories.length ? 'Bỏ chọn tất cả' : 'Chọn tất cả' }}
            </button>
          </div>

          <div v-if="loadingCategories" class="text-center py-4 text-gray-500 text-sm">
            <svg class="animate-spin h-5 w-5 mx-auto text-purple-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2">Đang tải phân loại...</p>
          </div>

          <div v-else-if="categories.length === 0" class="text-center py-8 text-gray-500 text-sm">
            <p>Chưa có phân loại bài tập</p>
            <p class="text-xs mt-1">Vui lòng cấu hình trong Examination Settings</p>
          </div>

          <div v-else class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <div
              v-for="category in categories"
              :key="category.id"
              @click="toggleCategory(category.id)"
              :class="[
                'p-3 border-2 rounded-lg cursor-pointer transition-all duration-200',
                selectedCategories.includes(category.id)
                  ? 'border-purple-500 bg-purple-100 shadow-md'
                  : 'border-gray-300 hover:border-purple-400 hover:bg-white'
              ]"
            >
              <div class="flex items-center space-x-2">
                <div :class="[
                  'w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0',
                  selectedCategories.includes(category.id)
                    ? 'border-purple-500 bg-purple-500'
                    : 'border-gray-400'
                ]">
                  <svg v-if="selectedCategories.includes(category.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <span class="text-lg">{{ category.icon }}</span>
                <span class="text-sm font-medium text-gray-900">{{ category.name }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Question Types Selection -->
        <div v-if="materials.length > 0" class="mt-6 border rounded-lg p-4 bg-gray-50">
          <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-medium text-gray-900">Loại câu hỏi cần tạo *</label>
            <button
              type="button"
              @click="addQuestionType"
              class="text-sm text-purple-600 hover:text-purple-800 flex items-center gap-1"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Thêm loại câu hỏi
            </button>
          </div>

          <div v-if="questionTypes.length === 0" class="text-center py-8 text-gray-500 text-sm">
            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p>Chưa có loại câu hỏi nào được chọn</p>
            <p class="text-xs mt-1">Nhấn "Thêm loại câu hỏi" để bắt đầu</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(qt, index) in questionTypes"
              :key="index"
              class="flex items-center gap-3 p-3 bg-white border rounded-lg"
            >
              <div class="flex-1">
                <select
                  v-model="qt.type"
                  required
                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
                >
                  <option value="">Chọn loại câu hỏi</option>
                  <optgroup label="Basic Types">
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True/False</option>
                    <option value="true_false_ng">True/False/Not Given</option>
                    <option value="short_answer">Short Answer</option>
                  </optgroup>
                  <optgroup label="Fill & Complete">
                    <option value="fill_blanks">Fill in the Blanks</option>
                    <option value="sentence_completion">Sentence Completion</option>
                    <option value="summary_completion">Summary Completion</option>
                    <option value="table_completion">Table Completion</option>
                  </optgroup>
                  <optgroup label="Matching & Ordering">
                    <option value="matching">Matching</option>
                    <option value="matching_headings">Matching Headings</option>
                    <option value="matching_sentence_endings">Matching Sentence Endings</option>
                  </optgroup>
                </select>
              </div>
              <div class="w-32">
                <input
                  v-model.number="qt.count"
                  type="number"
                  min="1"
                  max="10"
                  required
                  placeholder="Số lượng"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
                />
              </div>
              <button
                type="button"
                @click="removeQuestionType(index)"
                class="p-2 text-red-600 hover:bg-red-50 rounded-lg"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
            <div class="text-right text-sm text-gray-600">
              Tổng: <strong>{{ totalQuestionCount }}</strong> câu hỏi
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="border-t px-6 py-4 bg-gray-50 flex justify-between items-center">
        <div class="text-sm text-gray-600">
          {{ selectedMaterials.length }} materials đã chọn
        </div>
        <div class="flex space-x-3">
          <button
            @click="$emit('close')"
            class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100"
            :disabled="generating"
          >
            Hủy
          </button>
          <button
            @click="generateHomework"
            :disabled="generating || selectedMaterials.length === 0"
            class="px-6 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
          >
            <svg v-if="generating" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ generating ? 'Đang tạo...' : 'Tạo Homework' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
  session: {
    type: Object,
    required: true
  },
  subjectId: {
    type: Number,
    required: false
  },
  subject: {
    type: Object,
    required: false
  }
});

const emit = defineEmits(['close', 'generated']);

const sessionNumber = computed(() => props.session.session_number);
const sessionTitle = computed(() => props.session.lesson_title);

const materials = ref([]);
const selectedMaterials = ref([]);
const loadingMaterials = ref(false);
const generating = ref(false);

const settings = ref({
  questionCount: 10,
  difficulty: 'mixed'
});

const examSubjects = ref([]);
const selectedSubject = ref(null);
const loadingSubjects = ref(false);

const categories = ref([]);
const selectedCategories = ref([]);
const loadingCategories = ref(false);

const questionTypes = ref([]);

const totalQuestionCount = computed(() => {
  return questionTypes.value.reduce((sum, qt) => sum + (qt.count || 0), 0);
});

onMounted(async () => {
  await fetchMaterials();
  await fetchExamSubjects();
});

watch(selectedSubject, async (newSubject) => {
  if (newSubject) {
    await fetchCategories();
  } else {
    categories.value = [];
    selectedCategories.value = [];
  }
});

async function fetchMaterials() {
  loadingMaterials.value = true;
  try {
    const response = await axios.get(`/api/quality/sessions/${props.session.id}/materials`, {
      headers: {
        'X-Branch-Id': localStorage.getItem('current_branch_id')
      }
    });

    materials.value = response.data.data || [];

    // Auto-select all materials
    if (materials.value.length > 0) {
      selectedMaterials.value = materials.value.map(m => m.id);
    }
  } catch (error) {
    console.error('Error fetching materials:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể tải materials'
    });
  } finally {
    loadingMaterials.value = false;
  }
}

function toggleMaterial(materialId) {
  const index = selectedMaterials.value.indexOf(materialId);
  if (index > -1) {
    selectedMaterials.value.splice(index, 1);
  } else {
    selectedMaterials.value.push(materialId);
  }
}

function toggleAllMaterials() {
  if (selectedMaterials.value.length === materials.value.length) {
    selectedMaterials.value = [];
  } else {
    selectedMaterials.value = materials.value.map(m => m.id);
  }
}

async function fetchExamSubjects() {
  loadingSubjects.value = true;
  try {
    const response = await axios.get('/api/examination/subjects', {
      headers: {
        'X-Branch-Id': localStorage.getItem('current_branch_id')
      }
    });

    examSubjects.value = response.data.data || [];
    console.log('✅ [AIHomeworkGenerator] Exam subjects loaded:', examSubjects.value.length);

    // Auto-select first subject if available
    if (examSubjects.value.length > 0 && !selectedSubject.value) {
      selectedSubject.value = examSubjects.value[0].id;
    }
  } catch (error) {
    console.error('❌ [AIHomeworkGenerator] Error fetching exam subjects:', error);
  } finally {
    loadingSubjects.value = false;
  }
}

async function fetchCategories() {
  console.log('🔍 [AIHomeworkGenerator] fetchCategories called, selectedSubject:', selectedSubject.value);

  if (!selectedSubject.value) {
    console.warn('⚠️ [AIHomeworkGenerator] No subject selected, skipping categories fetch');
    return;
  }

  loadingCategories.value = true;
  try {
    console.log('📡 [AIHomeworkGenerator] Fetching categories for subject:', selectedSubject.value);
    const response = await axios.get(`/api/examination/subjects/${selectedSubject.value}/categories`, {
      headers: {
        'X-Branch-Id': localStorage.getItem('current_branch_id')
      }
    });

    categories.value = response.data.data || [];
    console.log('✅ [AIHomeworkGenerator] Categories loaded:', categories.value.length, categories.value);

    // Auto-select all categories
    if (categories.value.length > 0) {
      selectedCategories.value = categories.value.map(c => c.id);
      console.log('✅ [AIHomeworkGenerator] Auto-selected categories:', selectedCategories.value);
    }
  } catch (error) {
    console.error('❌ [AIHomeworkGenerator] Error fetching categories:', error);
  } finally {
    loadingCategories.value = false;
  }
}

function toggleCategory(categoryId) {
  const index = selectedCategories.value.indexOf(categoryId);
  if (index > -1) {
    selectedCategories.value.splice(index, 1);
  } else {
    selectedCategories.value.push(categoryId);
  }
}

function toggleAllCategories() {
  if (selectedCategories.value.length === categories.value.length) {
    selectedCategories.value = [];
  } else {
    selectedCategories.value = categories.value.map(c => c.id);
  }
}

function getContentLength(content) {
  if (!content) return 0;
  const text = content.replace(/<[^>]*>/g, '');
  return text.split(/\s+/).length;
}

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN');
}

function addQuestionType() {
  questionTypes.value.push({ type: '', count: 3 });
}

function removeQuestionType(index) {
  questionTypes.value.splice(index, 1);
}

async function generateHomework() {
  if (selectedMaterials.value.length === 0) {
    await Swal.fire({
      icon: 'warning',
      title: 'Chưa chọn materials',
      text: 'Vui lòng chọn ít nhất 1 material'
    });
    return;
  }

  if (!selectedSubject.value) {
    await Swal.fire({
      icon: 'warning',
      title: 'Chưa chọn môn học',
      text: 'Vui lòng chọn môn học trước'
    });
    return;
  }

  if (selectedCategories.value.length === 0) {
    await Swal.fire({
      icon: 'warning',
      title: 'Chưa chọn phân loại',
      text: 'Vui lòng chọn ít nhất 1 phân loại bài tập (Reading, Grammar, Vocabulary...)'
    });
    return;
  }

  // Validate question types if provided
  if (questionTypes.value.length > 0) {
    for (const qt of questionTypes.value) {
      if (!qt.type || !qt.count || qt.count < 1) {
        await Swal.fire({
          icon: 'warning',
          title: 'Thiếu thông tin',
          text: 'Vui lòng điền đầy đủ loại câu hỏi và số lượng'
        });
        return;
      }
    }
  }

  try {
    generating.value = true;

    const response = await axios.post('/api/homework/generate-with-ai', {
      session_id: props.session.id,
      material_ids: selectedMaterials.value,
      subject_id: selectedSubject.value,
      category_ids: selectedCategories.value,
      question_count: settings.value.questionCount,
      difficulty: settings.value.difficulty,
      question_types: questionTypes.value.length > 0 ? questionTypes.value : null
    }, {
      headers: {
        'X-Branch-Id': localStorage.getItem('current_branch_id')
      }
    });

    if (response.data.success) {
      const exercises = response.data.data.exercises || [];

      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: `Đã tạo ${exercises.length} câu hỏi`,
        timer: 2000,
        showConfirmButton: false
      });

      emit('generated', exercises);
      emit('close');
    }
  } catch (error) {
    console.error('Error generating homework:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể tạo homework. Vui lòng thử lại.'
    });
  } finally {
    generating.value = false;
  }
}
</script>
