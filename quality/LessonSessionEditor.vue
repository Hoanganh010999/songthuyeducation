<template>
  <div class="bg-white rounded-lg shadow-lg">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-lg">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-xl font-bold">{{ session.id ? t('syllabus.edit_session') : t('syllabus.add_new_session') }}</h3>
          <p class="text-blue-100 text-sm mt-1">{{ t('syllabus.session_number_label') }} {{ session.session_number }}</p>
        </div>
        <button
          @click="$emit('close')"
          class="text-white hover:text-blue-100"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Form Content with Tabs -->
    <div class="p-6">
      <!-- Tabs -->
      <div class="flex border-b mb-6">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'px-6 py-3 font-medium transition-colors relative',
            activeTab === tab.id
              ? 'text-blue-600 border-b-2 border-blue-600'
              : 'text-gray-500 hover:text-gray-700'
          ]"
        >
          <div class="flex items-center space-x-2">
            <component :is="tab.icon" class="w-5 h-5" />
            <span>{{ tab.label }}</span>
          </div>
        </button>
      </div>

      <!-- Single Tab Content -->
      <div v-show="activeTab === 'basic'" class="space-y-6">
        <!-- Basic Info - Compact Layout -->
        <div class="bg-gray-50 p-4 rounded-lg space-y-3">
          <!-- Row 1: Required fields -->
          <div class="grid grid-cols-4 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">
                <span class="flex items-center space-x-1">
                  <span>{{ t('syllabus.session_order') }}</span>
                  <span class="text-red-500">*</span>
                </span>
              </label>
              <input
                v-model.number="formData.session_number"
                type="number"
                min="1"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                :placeholder="t('syllabus.session_order_placeholder')"
              />
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-medium text-gray-700 mb-1">
                <span class="flex items-center space-x-1">
                  <span>{{ t('syllabus.lesson_title') }}</span>
                  <span class="text-red-500">*</span>
                </span>
              </label>
              <input
                v-model="formData.lesson_title"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                :placeholder="t('syllabus.lesson_title_placeholder')"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">
                <span class="flex items-center space-x-1">
                  <span>Duration (Mins)</span>
                  <span class="text-red-500">*</span>
                </span>
              </label>
              <input
                v-model.number="formData.duration_minutes"
                type="number"
                min="1"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="45/50"
              />
            </div>
          </div>

          <!-- Row 2: TECP Header fields -->
          <div class="grid grid-cols-4 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Teacher's Name</label>
              <input
                v-model="formData.teacher_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="Teacher name"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Lesson Focus</label>
              <input
                v-model="formData.lesson_focus"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="e.g., Present Perfect"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Level</label>
              <input
                v-model="formData.level"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="e.g., Intermediate"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">TP#</label>
              <input
                v-model="formData.tp_number"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="TP number"
              />
            </div>
          </div>

          <!-- Row 3: Date -->
          <div class="grid grid-cols-4 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
              <input
                v-model="formData.lesson_date"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
              />
            </div>
          </div>
        </div>

        <!-- LESSON AIMS Section -->
        <div class="border-t-2 border-blue-500 pt-4">
          <h3 class="text-lg font-bold text-blue-700 mb-4">LESSON AIMS</h3>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Communicative outcome:
                <span class="text-xs italic text-gray-500">What real-world communicative function will students be able to perform by the end of the lesson?</span>
              </label>
              <textarea
                v-model="formData.communicative_outcome"
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="(tell a story / debate their opinions / discuss their habits etc.) / understand a specific written/audio text"
              ></textarea>
              <p class="text-xs text-gray-500 mt-1">By the end of the lesson, students will be better able to...</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Linguistic aim:
                <span class="text-xs italic text-gray-500">are you teaching any new language?</span>
              </label>
              <textarea
                v-model="formData.linguistic_aim"
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="By the end of the lesson, students will have a better understanding of/will have practiced..."
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Productive Sub-skills focus:
                  <span class="text-xs italic text-gray-500">Which elements of the communicative skills will you be helping learners to improve?</span>
                </label>
                <textarea
                  v-model="formData.productive_subskills_focus"
                  rows="2"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                  placeholder="(fluency / accuracy / etc.)"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">To develop the sub-skill(s) of...</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Receptive Sub-skills focus:
                  <span class="text-xs italic text-gray-500">Which elements of the communicative skills will you be helping learners to improve?</span>
                </label>
                <textarea
                  v-model="formData.receptive_subskills_focus"
                  rows="2"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                  placeholder="(gist / specific information / detailed comprehension etc.)"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">To develop the sub-skill(s) of...</p>
              </div>
            </div>
          </div>
        </div>

        <!-- PERSONAL AIMS Section -->
        <div class="border-t-2 border-purple-500 pt-4">
          <h3 class="text-lg font-bold text-purple-700 mb-4">PERSONAL AIMS</h3>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Which aspects of your teaching are you going to work on today?
              </label>
              <textarea
                v-model="formData.teaching_aspects_to_improve"
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="e.g., Giving clearer instructions, monitoring, error correction..."
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                How will you improve them?
              </label>
              <textarea
                v-model="formData.improvement_methods"
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="e.g., Use ICQs, walk around more, note errors for delayed correction..."
              ></textarea>
            </div>
          </div>
        </div>

        <!-- LANGUAGE ANALYSIS SHEET Section -->
        <div class="border-t-2 border-green-500 pt-4">
          <h3 class="text-lg font-bold text-green-700 mb-2">LANGUAGE ANALYSIS SHEET - FUNCTIONAL LANGUAGE</h3>
          <p class="text-xs italic text-gray-600 mb-4">Whenever you start out to teach functions, you need to research the language and anticipate any potential difficulties for your students. The below categories will help guide you. Complete it when appropriate.</p>

          <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium bg-yellow-100 p-2 mb-2">
                LANGUAGE AREA
              </label>
              <p class="text-xs text-gray-600 mb-2">Identify your language area by name:</p>
              <input
                v-model="formData.language_area"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                placeholder="e.g., Present Perfect, Giving Advice..."
              />
            </div>

            <div>
              <label class="block text-sm font-medium bg-yellow-100 p-2 mb-2">
                EXAMPLES OF LANGUAGE
              </label>
              <p class="text-xs text-gray-600 mb-2">These are the marker sentences in the lesson:</p>
              <textarea
                v-model="formData.examples_of_language"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                placeholder="1) ___________&#10;2) ___________"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium bg-yellow-100 p-2 mb-2">
                CONTEXT
              </label>
              <p class="text-xs text-gray-600 mb-2">Where do examples come from:</p>
              <textarea
                v-model="formData.context"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                placeholder="e.g., reading text, listening, real-life scenario..."
              ></textarea>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium bg-yellow-100 p-2 mb-2">
                CONCEPT CHECKING
              </label>
              <p class="text-xs text-gray-600 mb-2">How will you check to ensure learners understand it: CCQs, visuals, cards, timelines, clines, etc?</p>
              <textarea
                v-model="formData.concept_checking_methods"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                placeholder="1) ___________"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium bg-yellow-100 p-2 mb-2">
                IN the LESSON
              </label>
              <p class="text-xs text-gray-600 mb-2">How will you deal with the meaning and concept checking?</p>
              <textarea
                v-model="formData.concept_checking_in_lesson"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                placeholder="Describe your approach..."
              ></textarea>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Framework / Shape</label>
          <select
            v-model="formData.framework_shape"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Select lesson shape</option>
            <option value="A">A - Text-Based Presentation of Language</option>
            <option value="B">B - Language Practice</option>
            <option value="C">C - Test-Teach-Test Presentation of Language</option>
            <option value="D">D - Situational Presentation (PPP)</option>
            <option value="E">E - Receptive Skills (listening or reading)</option>
            <option value="F">F - Productive Skills (speaking or writing)</option>
            <option value="G">G - Dogme ELT</option>
            <option value="H">H - Task-Based Learning/Teaching (TBL/TBT)</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('syllabus.notes') }}</label>
          <textarea
            v-model="formData.notes"
            rows="3"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :placeholder="t('syllabus.notes_placeholder')"
          ></textarea>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3 pt-6 border-t mt-6">
        <button
          type="button"
          @click="$emit('close')"
          class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
        >
          {{ t('syllabus.cancel') }}
        </button>
        <button
          type="button"
          @click="save"
          :disabled="saving"
          class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 flex items-center space-x-2"
        >
          <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>{{ saving ? t('syllabus.saving') : t('syllabus.save') }}</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Homework Exercise Builder Modal -->
  <HomeworkExerciseBuilder
    v-if="showHomeworkBuilder"
    :session="props.session"
    :classId="classId"
    :subjectId="props.subjectId"
    :subject="props.subject"
    @close="showHomeworkBuilder = false"
    @saved="handleHomeworkSaved"
  />
</template>

<script setup>
import { ref, reactive, h, onMounted, watch, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import HomeworkExerciseBuilder from '../../components/homework/HomeworkExerciseBuilder.vue';

const { t } = useI18n();
const router = useRouter();

const props = defineProps({
  session: {
    type: Object,
    required: true
  },
  syllabusId: {
    type: Number,
    required: false
  },
  syllabusName: {
    type: String,
    required: false
  },
  syllabusFolderId: {
    type: String,
    required: false
  },
  classId: {
    type: Number,
    required: false
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

const emit = defineEmits(['save', 'close']);

const saving = ref(false);
const activeTab = ref('basic');

const formData = reactive({
  id: props.session.id || null,
  session_number: props.session.session_number || 1,
  lesson_title: props.session.lesson_title || '',
  lesson_objectives: props.session.lesson_objectives || '',
  lesson_content: props.session.lesson_content || '',
  lesson_plan_url: props.session.lesson_plan_url || '',
  materials_url: props.session.materials_url || '',
  homework_url: props.session.homework_url || '',
  duration_minutes: props.session.duration_minutes || 45,
  notes: props.session.notes || '',
  // TECP Lesson Plan fields
  teacher_name: props.session.teacher_name || '',
  lesson_focus: props.session.lesson_focus || '',
  level: props.session.level || '',
  lesson_date: props.session.lesson_date || '',
  tp_number: props.session.tp_number || '',
  communicative_outcome: props.session.communicative_outcome || '',
  linguistic_aim: props.session.linguistic_aim || '',
  productive_subskills_focus: props.session.productive_subskills_focus || '',
  receptive_subskills_focus: props.session.receptive_subskills_focus || '',
  teaching_aspects_to_improve: props.session.teaching_aspects_to_improve || '',
  improvement_methods: props.session.improvement_methods || '',
  framework_shape: props.session.framework_shape || '',
  language_area: props.session.language_area || '',
  examples_of_language: props.session.examples_of_language || '',
  context: props.session.context || '',
  concept_checking_methods: props.session.concept_checking_methods || '',
  concept_checking_in_lesson: props.session.concept_checking_in_lesson || ''
});

// File upload states
const selectedFiles = reactive({
  lesson_plan: null,
  materials: null,
  homework: null
});

const uploadingFiles = reactive({
  lesson_plan: false,
  materials: false,
  homework: false
});

const uploadedFiles = reactive({
  lesson_plan: null,
  materials: [],
  homework: []
});

const loadingFiles = reactive({
  lesson_plan: false,
  materials: false,
  homework: false
});

// Folder IDs for opening folders on Google Drive
const lessonPlansFolderId = ref(null);
const materialsFolderId = ref(null);
const homeworkFolderId = ref(null);

const lessonPlanFileInput = ref(null);
const materialsFileInput = ref(null);
const homeworkFileInput = ref(null);

// Homework Exercise Builder state
const showHomeworkBuilder = ref(false);

const tabs = [
  {
    id: 'basic',
    label: 'Lesson Plan',
    icon: h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })
    ])
  }
];


const handleFileSelect = async (event, fileType) => {
  const files = event.target.files;
  if (!files || files.length === 0) return;

  // Store selected files
  if (fileType === 'lesson_plan') {
    selectedFiles[fileType] = files[0];
  } else {
    selectedFiles[fileType] = Array.from(files);
  }

  // Auto-upload if session already exists
  if (formData.id && props.syllabusFolderId) {
    await uploadFiles(fileType);
  }
};

const uploadFiles = async (fileType) => {
  if (!selectedFiles[fileType]) return;

  const files = fileType === 'lesson_plan' 
    ? [selectedFiles[fileType]] 
    : selectedFiles[fileType];

  uploadingFiles[fileType] = true;

  try {
    for (const file of files) {
      const formDataToSend = new FormData();
      formDataToSend.append('file', file);
      formDataToSend.append('unit_number', formData.session_number); // Session number = Unit number
      formDataToSend.append('folder_type', fileType === 'lesson_plan' ? 'lesson_plans' : fileType);
      formDataToSend.append('branch_id', localStorage.getItem('current_branch_id'));

      // Use new endpoint: /api/lesson-plans/{syllabusId}/upload
      const response = await axios.post(`/api/lesson-plans/${props.syllabusId}/upload`, formDataToSend, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });

      if (response.data.success) {
        // File uploaded successfully
      }
    }

    await Swal.fire({
      icon: 'success',
      title: 'Success',
      text: `${fileType.replace('_', ' ')} uploaded successfully!`,
      timer: 2000
    });

    // Clear selection
    selectedFiles[fileType] = null;
    if (fileType === 'lesson_plan' && lessonPlanFileInput.value) {
      lessonPlanFileInput.value.value = '';
    } else if (fileType === 'materials' && materialsFileInput.value) {
      materialsFileInput.value.value = '';
    } else if (fileType === 'homework' && homeworkFileInput.value) {
      homeworkFileInput.value.value = '';
    }

    // Reload files list
    await loadUploadedFiles(fileType);
  } catch (error) {
    await Swal.fire({
      icon: 'error',
      title: 'Upload Failed',
      text: error.response?.data?.message || `Failed to upload ${fileType.replace('_', ' ')}`
    });
  } finally {
    uploadingFiles[fileType] = false;
  }
};

const loadUploadedFiles = async (fileType) => {
  if (!props.syllabusId || !formData.session_number) return;

  loadingFiles[fileType] = true;

  try {
    // Use new endpoint to get files from Unit folder
    const folderTypeParam = fileType === 'lesson_plan' ? 'lesson_plans' : fileType;
    const response = await axios.get(
      `/api/lesson-plans/${props.syllabusId}/unit/${formData.session_number}/files`,
      {
        params: {
          folder_type: folderTypeParam,
          branch_id: localStorage.getItem('current_branch_id')
        }
      }
    );
    
    if (response.data.success) {
      uploadedFiles[fileType] = response.data.data;
      
      // Store folder IDs for opening folders
      if (response.data.folder_ids) {
        if (fileType === 'lesson_plan') {
          lessonPlansFolderId.value = response.data.folder_ids.lesson_plans_folder_id;
        } else if (fileType === 'materials') {
          materialsFolderId.value = response.data.folder_ids.materials_folder_id;
        } else if (fileType === 'homework') {
          homeworkFolderId.value = response.data.folder_ids.homework_folder_id;
        }
      }
    }
  } catch (error) {
    // Don't show error to user
    uploadedFiles[fileType] = [];
  } finally {
    loadingFiles[fileType] = false;
  }
};

const getFileIcon = (mimeType) => {
  if (!mimeType) return '📎';
  
  // Normalize to lowercase for better matching
  const mime = mimeType.toLowerCase();
  
  // PDF
  if (mime.includes('pdf')) return '📄';
  
  // Word documents
  if (mime.includes('word') || 
      mime.includes('msword') ||
      mime.includes('wordprocessing') ||
      mime.includes('document') ||
      mime.includes('.doc') ||
      mime.includes('officedocument.wordprocessing')) {
    return '📝';
  }
  
  // Excel spreadsheets
  if (mime.includes('sheet') || 
      mime.includes('excel') ||
      mime.includes('spreadsheet') ||
      mime.includes('.xls')) {
    return '📊';
  }
  
  // PowerPoint presentations
  if (mime.includes('presentation') || 
      mime.includes('powerpoint') ||
      mime.includes('.ppt')) {
    return '📽️';
  }
  
  // Images
  if (mime.includes('image')) return '🖼️';
  
  // Videos
  if (mime.includes('video')) return '🎥';
  
  // Audio
  if (mime.includes('audio')) return '🎵';
  
  // Archives
  if (mime.includes('zip') || 
      mime.includes('rar') || 
      mime.includes('archive') ||
      mime.includes('compressed')) {
    return '📦';
  }
  
  // Text files
  if (mime.includes('text')) return '📃';
  
  // Default
  return '📎';
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const openFile = (file) => {
  // Support both camelCase (from Google API) and snake_case (from DB)
  const link = file.webViewLink || file.web_view_link;
  if (link) {
    window.open(link, '_blank');
  } else {
    // Show user-friendly error
    Swal.fire({
      icon: 'error',
      title: 'Cannot Open File',
      text: 'This file does not have a valid link. Please try re-uploading the file.',
      confirmButtonText: 'OK'
    });
  }
};

const save = () => {
  if (!formData.lesson_title) {
    alert(t('syllabus.required_lesson_title'));
    return;
  }

  emit('save', { ...formData });
};

// Navigate to materials list
const goToMaterialsList = async () => {
  try {
    const sessionId = formData.id;
    if (!sessionId) {
      await Swal.fire('Error', 'Please save the session first!', 'error');
      return;
    }

    // Navigate to materials list page
    router.push({
      name: 'quality.materials-list',
      params: { sessionId: sessionId },
      query: {
        title: formData.lesson_title,
        focus: formData.lesson_focus,
        level: formData.level,
        duration: formData.duration_minutes
      }
    });
  } catch (error) {
    console.error('Error:', error);
    await Swal.fire('Error', 'Failed to navigate to materials list', 'error');
  }
};

// Handle homework assignment saved
const handleHomeworkSaved = (assignment) => {
  showHomeworkBuilder.value = false;

  Swal.fire({
    icon: 'success',
    title: 'Thành công!',
    text: 'Đã tạo bài tập homework',
    timer: 2000,
    showConfirmButton: false
  });

  // Optionally refresh session data
  emit('save');
};

// Watch for session prop changes (when modal opens with existing session)
watch(() => props.session, (newSession) => {
  if (newSession && newSession.id) {
    // Update formData with new session data
    Object.assign(formData, {
      id: newSession.id,
      session_number: newSession.session_number || 1,
      lesson_title: newSession.lesson_title || '',
      lesson_objectives: newSession.lesson_objectives || '',
      lesson_content: newSession.lesson_content || '',
      lesson_plan_url: newSession.lesson_plan_url || '',
      materials_url: newSession.materials_url || '',
      homework_url: newSession.homework_url || '',
      duration_minutes: newSession.duration_minutes || 45,
      notes: newSession.notes || '',
      // TECP fields
      teacher_name: newSession.teacher_name || '',
      lesson_focus: newSession.lesson_focus || '',
      level: newSession.level || '',
      lesson_date: newSession.lesson_date || '',
      tp_number: newSession.tp_number || '',
      communicative_outcome: newSession.communicative_outcome || '',
      linguistic_aim: newSession.linguistic_aim || '',
      productive_subskills_focus: newSession.productive_subskills_focus || '',
      receptive_subskills_focus: newSession.receptive_subskills_focus || '',
      teaching_aspects_to_improve: newSession.teaching_aspects_to_improve || '',
      improvement_methods: newSession.improvement_methods || '',
      framework_shape: newSession.framework_shape || '',
      language_area: newSession.language_area || '',
      examples_of_language: newSession.examples_of_language || '',
      context: newSession.context || '',
      concept_checking_methods: newSession.concept_checking_methods || '',
      concept_checking_in_lesson: newSession.concept_checking_in_lesson || ''
    });

    // Load files if we have syllabusId
    if (props.syllabusId && formData.session_number) {
      loadUploadedFiles('lesson_plan');
      loadUploadedFiles('materials');
      loadUploadedFiles('homework');
    }
  }
}, { immediate: true, deep: true });

// Navigate to Lesson Plan Editor
function openLessonPlanEditor() {
  if (formData.id) {
    router.push({
      name: 'lesson-plan.editor',
      params: { sessionId: formData.id }
    });
  }
}

// Load uploaded files when component mounts
onMounted(() => {
  // Load uploaded files if editing existing session and have syllabusId
  if (formData.id && props.syllabusId && formData.session_number) {
    // Always try to load files (folders are created on-demand)
    loadUploadedFiles('lesson_plan');
    loadUploadedFiles('materials');
    loadUploadedFiles('homework');
  }
});
</script>

<style>
.prose {
  max-width: none;
}

.prose h1, .prose h2, .prose h3, .prose h4 {
  margin-top: 1em;
  margin-bottom: 0.5em;
  font-weight: 600;
}

.prose p {
  margin-bottom: 0.75em;
}

.prose ul, .prose ol {
  margin-left: 1.5em;
  margin-bottom: 0.75em;
}

.prose strong {
  font-weight: 600;
}

.ql-editor {
  min-height: 200px;
  font-size: 14px;
}

.ql-container {
  font-family: inherit;
}
</style>
