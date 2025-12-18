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

      <!-- Tab: Thông tin cơ bản -->
      <div v-show="activeTab === 'basic'" class="space-y-6">
        <div class="grid grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
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
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('syllabus.session_order_placeholder')"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <span class="flex items-center space-x-1">
                <span>{{ t('syllabus.duration') }}</span>
              </span>
            </label>
            <input
              v-model.number="formData.duration_minutes"
              type="number"
              min="1"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('syllabus.duration_placeholder')"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <span class="flex items-center space-x-1">
              <span>{{ t('syllabus.lesson_title') }}</span>
              <span class="text-red-500">*</span>
            </span>
          </label>
          <input
            v-model="formData.lesson_title"
            type="text"
            required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :placeholder="t('syllabus.lesson_title_placeholder')"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('syllabus.lesson_objectives') }}</label>
          <QuillEditor
            v-model:content="formData.lesson_objectives"
            contentType="html"
            theme="snow"
            :toolbar="toolbarOptions"
            :placeholder="t('syllabus.lesson_objectives_placeholder')"
            class="bg-white"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('syllabus.lesson_content') }}</label>
          <QuillEditor
            v-model:content="formData.lesson_content"
            contentType="html"
            theme="snow"
            :toolbar="toolbarOptions"
            :placeholder="t('syllabus.lesson_content_placeholder')"
            class="bg-white"
          />
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

      <!-- Tab: Tài liệu -->
      <div v-show="activeTab === 'materials'" class="space-y-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-800">
              <p class="font-medium">{{ t('syllabus.materials_guide_title') }}</p>
              <ul class="list-disc list-inside mt-1 space-y-1">
                <li>Upload files directly to Google Drive</li>
                <li>Files will be organized in session folders</li>
                <li>Maximum file size: 50MB</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Lesson Plan Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <div class="flex items-center space-x-2">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span>{{ t('syllabus.lesson_plan_link') }}</span>
            </div>
          </label>
          <div class="flex items-center space-x-3 flex-wrap">
          <input
              ref="lessonPlanFileInput"
              type="file"
              @change="handleFileSelect($event, 'lesson_plan')"
              class="hidden"
              accept=".pdf,.doc,.docx,.ppt,.pptx"
            />
            <button
              type="button"
              @click="$refs.lessonPlanFileInput.click()"
              :disabled="uploadingFiles.lesson_plan"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center space-x-2"
            >
              <svg v-if="!uploadingFiles.lesson_plan" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
              </svg>
              <svg v-else class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ uploadingFiles.lesson_plan ? 'Uploading...' : 'Upload Lesson Plan' }}</span>
            </button>
            
            <!-- Uploaded Lesson Plan Files (compact card layout) -->
            <div v-if="uploadedFiles.lesson_plan && uploadedFiles.lesson_plan.length > 0" class="flex flex-wrap gap-2">
              <div
                v-for="file in uploadedFiles.lesson_plan"
                :key="file.id"
                @click="openFile(file)"
                class="flex flex-col items-center justify-center w-16 h-16 p-1.5 bg-blue-50 rounded-lg border border-blue-200 hover:border-blue-400 hover:shadow-md cursor-pointer transition group relative"
                :title="`${file.name}\n${formatFileSize(file.size)}\nClick to view on Google Drive`"
              >
                <span class="text-2xl mb-0.5">{{ getFileIcon(file.mimeType || file.mime_type) }}</span>
                <p class="text-xs font-medium text-gray-700 text-center truncate w-full px-0.5 leading-tight">{{ file.name }}</p>
                <!-- External link icon on hover -->
                <svg class="absolute top-0.5 right-0.5 w-2.5 h-2.5 text-blue-400 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
              </div>
            </div>
            <span v-else-if="loadingFiles.lesson_plan" class="text-sm text-gray-500">Loading...</span>
            <span v-else-if="selectedFiles.lesson_plan" class="text-sm text-gray-600">{{ selectedFiles.lesson_plan.name }}</span>
          </div>
          <p class="text-xs text-gray-500 mt-2">{{ t('syllabus.lesson_plan_description') }}</p>
        </div>

        <!-- Teaching Materials Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <div class="flex items-center space-x-2">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
              </svg>
              <span>{{ t('syllabus.materials_link') }}</span>
            </div>
          </label>
          <div class="flex items-center space-x-3 flex-wrap gap-y-2">
          <input
              ref="materialsFileInput"
              type="file"
              @change="handleFileSelect($event, 'materials')"
              class="hidden"
              accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip"
              multiple
            />
            <button
              type="button"
              @click="$refs.materialsFileInput.click()"
              :disabled="uploadingFiles.materials"
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center space-x-2"
            >
              <svg v-if="!uploadingFiles.materials" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
              </svg>
              <svg v-else class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ uploadingFiles.materials ? 'Uploading...' : 'Upload Materials' }}</span>
            </button>
            
            <!-- Uploaded Materials Files (compact card layout) -->
            <div v-if="uploadedFiles.materials.length > 0" class="flex flex-wrap gap-2">
              <div
                v-for="file in uploadedFiles.materials"
                :key="file.id"
                @click="openFile(file)"
                class="flex flex-col items-center justify-center w-16 h-16 p-1.5 bg-green-50 rounded-lg border border-green-200 hover:border-green-400 hover:shadow-md cursor-pointer transition group relative"
                :title="`${file.name}\n${formatFileSize(file.size)}\nClick to view on Google Drive`"
              >
                <span class="text-2xl mb-0.5">{{ getFileIcon(file.mimeType || file.mime_type) }}</span>
                <p class="text-xs font-medium text-gray-700 text-center truncate w-full px-0.5 leading-tight">{{ file.name }}</p>
                <!-- External link icon on hover -->
                <svg class="absolute top-0.5 right-0.5 w-2.5 h-2.5 text-green-400 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
              </div>
            </div>
            <span v-else-if="loadingFiles.materials" class="text-sm text-gray-500">Loading...</span>
            <span v-else-if="selectedFiles.materials" class="text-sm text-gray-600">{{ selectedFiles.materials.length }} file(s) selected</span>
          </div>
          
          <p class="text-xs text-gray-500 mt-2">{{ t('syllabus.materials_description') }}</p>
        </div>

        <!-- Homework Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <div class="flex items-center space-x-2">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
              <span>{{ t('syllabus.homework_link') }}</span>
            </div>
          </label>
          <div class="flex items-center space-x-3 flex-wrap gap-y-2">
          <input
              ref="homeworkFileInput"
              type="file"
              @change="handleFileSelect($event, 'homework')"
              class="hidden"
              accept=".pdf,.doc,.docx,.ppt,.pptx"
              multiple
            />
            <button
              type="button"
              @click="$refs.homeworkFileInput.click()"
              :disabled="uploadingFiles.homework"
              class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center space-x-2"
            >
              <svg v-if="!uploadingFiles.homework" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
              </svg>
              <svg v-else class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ uploadingFiles.homework ? 'Uploading...' : 'Upload Homework' }}</span>
            </button>
            
            <!-- Uploaded Homework Files (compact card layout) -->
            <div v-if="uploadedFiles.homework.length > 0" class="flex flex-wrap gap-2">
              <div
                v-for="file in uploadedFiles.homework"
                :key="file.id"
                @click="openFile(file)"
                class="flex flex-col items-center justify-center w-16 h-16 p-1.5 bg-purple-50 rounded-lg border border-purple-200 hover:border-purple-400 hover:shadow-md cursor-pointer transition group relative"
                :title="`${file.name}\n${formatFileSize(file.size)}\nClick to view on Google Drive`"
              >
                <span class="text-2xl mb-0.5">{{ getFileIcon(file.mimeType || file.mime_type) }}</span>
                <p class="text-xs font-medium text-gray-700 text-center truncate w-full px-0.5 leading-tight">{{ file.name }}</p>
                <!-- External link icon on hover -->
                <svg class="absolute top-0.5 right-0.5 w-2.5 h-2.5 text-purple-400 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
              </div>
            </div>
            <span v-else-if="loadingFiles.homework" class="text-sm text-gray-500">Loading...</span>
            <span v-else-if="selectedFiles.homework" class="text-sm text-gray-600">{{ selectedFiles.homework.length }} file(s) selected</span>
          </div>
          
          <p class="text-xs text-gray-500 mt-2">{{ t('syllabus.homework_description') }}</p>
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
</template>

<script setup>
import { ref, reactive, h, onMounted, watch } from 'vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';

const { t } = useI18n();

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
  notes: props.session.notes || ''
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

const tabs = [
  {
    id: 'basic',
    label: t('syllabus.tab_basic_info'),
    icon: h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
    ])
  },
  {
    id: 'materials',
    label: t('syllabus.tab_materials'),
    icon: h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z' })
    ])
  }
];

const toolbarOptions = [
  ['bold', 'italic', 'underline', 'strike'],
  ['blockquote', 'code-block'],
  [{ 'header': 1 }, { 'header': 2 }],
  [{ 'list': 'ordered'}, { 'list': 'bullet' }],
  [{ 'indent': '-1'}, { 'indent': '+1' }],
  [{ 'size': ['small', false, 'large', 'huge'] }],
  [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
  [{ 'color': [] }, { 'background': [] }],
  [{ 'align': [] }],
  ['clean'],
  ['link']
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
      notes: newSession.notes || ''
    });
    
    // Load files if we have syllabusId
    if (props.syllabusId && formData.session_number) {
      loadUploadedFiles('lesson_plan');
      loadUploadedFiles('materials');
      loadUploadedFiles('homework');
    }
  }
}, { immediate: true, deep: true });

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
