<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[70]" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[85vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="bg-purple-600 text-white px-6 py-4 flex items-center justify-between">
        <h2 class="text-xl font-semibold">{{ editMode ? 'Chỉnh sửa câu hỏi' : 'Tạo câu hỏi mới' }}</h2>
        <button @click="$emit('close')" class="text-white hover:bg-purple-700 rounded-lg p-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Form -->
      <div class="flex-1 overflow-y-auto p-6">
        <div class="space-y-4">
          <!-- Subject and Category -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Môn học</label>
              <select v-model="form.subject_id" @change="onSubjectChange" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option :value="null">-- Chọn môn học --</option>
                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Phân loại</label>
              <select v-model="form.subject_category_id" :disabled="!form.subject_id || categories.length === 0" class="w-full px-3 py-2 border border-gray-300 rounded-lg disabled:bg-gray-100">
                <option :value="null">-- Chọn phân loại --</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
            </div>
          </div>

          <!-- Type, Skill, Difficulty -->
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Loại câu hỏi *</label>
              <select v-model="form.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
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
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Kỹ năng *</label>
              <select v-model="form.skill" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
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
              <label class="block text-sm font-medium text-gray-700 mb-2">Độ khó *</label>
              <select v-model="form.difficulty" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
                <option value="expert">Expert</option>
              </select>
            </div>
          </div>

          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tiêu đề câu hỏi *</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              placeholder="Vd: Choose the correct answer"
            />
          </div>

          <!-- Instructions -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hướng dẫn</label>
            <textarea
              v-model="form.instructions"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              placeholder="Hướng dẫn làm bài cho học sinh..."
            ></textarea>
          </div>

          <!-- Options (for multiple choice types) -->
          <div v-if="hasOptions">
            <label class="block text-sm font-medium text-gray-700 mb-2">Đáp án *</label>
            <div class="space-y-2">
              <div
                v-for="(option, index) in form.options"
                :key="index"
                class="flex items-center space-x-2"
              >
                <span class="text-sm font-medium text-gray-600 w-8">{{ String.fromCharCode(65 + index) }}.</span>
                <input
                  v-model="option.content"
                  type="text"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg"
                  :placeholder="`Đáp án ${String.fromCharCode(65 + index)}`"
                />
                <label class="flex items-center space-x-1 cursor-pointer">
                  <input
                    v-if="form.type === 'multiple_choice' || form.type === 'true_false' || form.type === 'true_false_ng'"
                    type="radio"
                    :name="'correct_option'"
                    :checked="option.is_correct"
                    @change="setCorrectOption(index)"
                    class="w-4 h-4 text-green-600 border-gray-300"
                  />
                  <input
                    v-else
                    type="checkbox"
                    v-model="option.is_correct"
                    class="w-4 h-4 text-green-600 border-gray-300 rounded"
                  />
                  <span class="text-sm text-gray-600">Đúng</span>
                </label>
                <button
                  v-if="form.options.length > 2"
                  @click="removeOption(index)"
                  class="text-red-600 hover:bg-red-50 p-1 rounded"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
            <button
              v-if="form.options.length < 6"
              @click="addOption"
              class="mt-2 text-sm text-purple-600 hover:text-purple-700 flex items-center space-x-1"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
              </svg>
              <span>Thêm đáp án</span>
            </button>
          </div>

          <!-- Text Answer (for fill blanks, short answer, completions) -->
          <div v-if="hasTextAnswer">
            <label class="block text-sm font-medium text-gray-700 mb-2">Đáp án đúng *</label>
            <input
              v-model="form.answer_key_text"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              placeholder="Nhập đáp án (phân cách bằng | nếu có nhiều đáp án). Ví dụ: answer1|answer2|answer3"
            />
            <p class="mt-1 text-xs text-gray-500">Sử dụng dấu | để thêm nhiều đáp án được chấp nhận</p>
          </div>

          <!-- Essay/Audio Response Settings -->
          <div v-if="isProductionType">
            <label class="block text-sm font-medium text-gray-700 mb-2">Yêu cầu số từ</label>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <input
                  v-model.number="form.min_words"
                  type="number"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                  placeholder="Số từ tối thiểu"
                  min="0"
                />
              </div>
              <div>
                <input
                  v-model.number="form.max_words"
                  type="number"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                  placeholder="Số từ tối đa"
                  min="0"
                />
              </div>
            </div>
          </div>

          <!-- Points and Time Limit -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Điểm</label>
              <input
                v-model.number="form.points"
                type="number"
                step="0.5"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Thời gian (giây)</label>
              <input
                v-model.number="form.time_limit"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                placeholder="Không giới hạn"
              />
            </div>
          </div>

          <!-- Hints -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Gợi ý</label>
            <textarea
              v-model="form.hints"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              placeholder="Gợi ý giúp học sinh làm bài..."
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="border-t px-6 py-4 bg-gray-50 flex justify-end space-x-3">
        <button
          @click="$emit('close')"
          class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100"
        >
          Hủy
        </button>
        <button
          @click="save"
          :disabled="saving || !isValid"
          class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>{{ saving ? 'Đang lưu...' : 'Lưu câu hỏi' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
  exercise: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'save']);

const editMode = computed(() => !!props.exercise);

const subjects = ref([]);
const categories = ref([]);

const form = ref({
  subject_id: props.exercise?.subject_id || null,
  subject_category_id: props.exercise?.subject_category_id || null,
  type: props.exercise?.type || 'multiple_choice',
  skill: props.exercise?.skill || 'reading',
  difficulty: props.exercise?.difficulty || 'medium',
  title: props.exercise?.title || '',
  instructions: props.exercise?.instructions || '',
  hints: props.exercise?.hints || '',
  points: props.exercise?.points || 1,
  time_limit: props.exercise?.time_limit || null,
  min_words: props.exercise?.settings?.min_words || null,
  max_words: props.exercise?.settings?.max_words || null,
  options: props.exercise?.options || [
    { content: '', is_correct: false },
    { content: '', is_correct: false },
    { content: '', is_correct: false },
    { content: '', is_correct: false }
  ],
  answer_key_text: props.exercise?.answer_key || ''
});

const saving = ref(false);

// Computed properties for question type categorization
const hasOptions = computed(() => {
  return ['multiple_choice', 'multiple_response', 'true_false', 'true_false_ng',
    'matching', 'matching_headings', 'matching_features', 'matching_sentence_endings',
    'ordering', 'fill_blanks_drag'].includes(form.value.type);
});

const hasTextAnswer = computed(() => {
  return ['fill_blanks', 'short_answer', 'sentence_completion', 'summary_completion',
    'note_completion', 'table_completion', 'flow_chart', 'labeling', 'hotspot'].includes(form.value.type);
});

const isProductionType = computed(() => {
  return ['essay', 'audio_response'].includes(form.value.type);
});

const isValid = computed(() => {
  if (!form.value.title) return false;

  // For question types that use options
  if (hasOptions.value) {
    const filledOptions = form.value.options.filter(o => o.content.trim());
    const correctOptions = form.value.options.filter(o => o.is_correct);
    return filledOptions.length >= 2 && correctOptions.length > 0;
  }

  // For text answer types, require answer_key_text
  if (hasTextAnswer.value) {
    return form.value.answer_key_text.trim().length > 0;
  }

  return true;
});

function setCorrectOption(index) {
  // For single-choice questions (radio buttons), only one can be correct
  form.value.options.forEach((opt, i) => {
    opt.is_correct = (i === index);
  });
}

function addOption() {
  form.value.options.push({ content: '', is_correct: false });
}

function removeOption(index) {
  form.value.options.splice(index, 1);
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

      // If editing and has subject_id, load categories
      if (props.exercise?.subject_id) {
        await loadCategories(props.exercise.subject_id);
      }
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

async function onSubjectChange() {
  form.value.subject_category_id = null;
  categories.value = [];

  if (form.value.subject_id) {
    await loadCategories(form.value.subject_id);
  }
}

async function save() {
  try {
    saving.value = true;

    const data = {
      subject_id: form.value.subject_id,
      subject_category_id: form.value.subject_category_id,
      type: form.value.type,
      skill: form.value.skill,
      difficulty: form.value.difficulty,
      title: form.value.title,
      instructions: form.value.instructions,
      hints: form.value.hints,
      points: form.value.points,
      time_limit: form.value.time_limit,
      settings: {}
    };

    // Add options for question types that use answer choices
    if (hasOptions.value) {
      data.options = form.value.options
        .filter(o => o.content.trim())
        .map(o => ({
          content: o.content,
          is_correct: o.is_correct
        }));
    }

    // Add text answer for fill blanks, short answer, completions, etc.
    if (hasTextAnswer.value && form.value.answer_key_text) {
      // Split by | to support multiple acceptable answers
      data.answer_key = form.value.answer_key_text.split('|').map(a => a.trim()).filter(a => a);
    }

    // Add word count settings for essay/audio response
    if (isProductionType.value) {
      if (form.value.min_words) data.settings.min_words = form.value.min_words;
      if (form.value.max_words) data.settings.max_words = form.value.max_words;
    }

    const url = editMode.value
      ? `/api/homework/exercises/${props.exercise.id}`
      : '/api/homework/exercises';

    const method = editMode.value ? 'put' : 'post';

    const response = await axios[method](url, data, {
      headers: {
        'X-Branch-Id': localStorage.getItem('current_branch_id')
      }
    });

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: editMode.value ? 'Đã cập nhật câu hỏi' : 'Đã tạo câu hỏi',
        timer: 1500,
        showConfirmButton: false
      });

      emit('save', response.data.data);
    }
  } catch (error) {
    console.error('Error saving exercise:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể lưu câu hỏi'
    });
  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  loadSubjects();
});
</script>
