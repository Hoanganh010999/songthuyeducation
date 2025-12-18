<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="close">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[80vh] flex flex-col">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
          <p v-if="subtitle" class="text-sm text-gray-500 mt-1">{{ subtitle }}</p>
        </div>
        <button
          @click="close"
          class="text-gray-400 hover:text-gray-600 transition"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="text-center">
            <svg class="animate-spin h-10 w-10 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-600">Loading files...</p>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="files.length === 0" class="flex items-center justify-center py-12">
          <div class="text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-500 text-lg">No files found</p>
            <p class="text-gray-400 text-sm mt-1">This folder is empty</p>
          </div>
        </div>

        <!-- Files Grid -->
        <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="file in files"
            :key="file.id"
            @click="openFile(file)"
            class="group cursor-pointer"
          >
            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-400 hover:shadow-md transition">
              <!-- File Icon -->
              <div class="flex items-center justify-center mb-3">
                <span class="text-5xl">{{ getFileIcon(file.mime_type) }}</span>
              </div>
              
              <!-- File Info -->
              <div class="text-center">
                <p class="text-sm font-medium text-gray-900 truncate mb-1" :title="file.name">
                  {{ file.name }}
                </p>
                <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                <p v-if="file.google_modified_at" class="text-xs text-gray-400 mt-1">
                  {{ formatDate(file.google_modified_at) }}
                </p>
              </div>

              <!-- Hover Actions -->
              <div class="mt-3 flex items-center justify-center space-x-2 opacity-0 group-hover:opacity-100 transition">
                <button
                  @click.stop="openFile(file)"
                  class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                >
                  Open
                </button>
                <a
                  v-if="file.web_content_link"
                  :href="file.web_content_link"
                  @click.stop
                  download
                  class="px-3 py-1 text-xs bg-gray-600 text-white rounded hover:bg-gray-700 transition"
                >
                  Download
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-600">
          {{ files.length }} file{{ files.length !== 1 ? 's' : '' }}
        </p>
        <div class="flex items-center space-x-2">
          <a
            v-if="folderId"
            :href="`https://drive.google.com/drive/folders/${folderId}`"
            target="_blank"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Open in Google Drive
          </a>
          <button
            @click="close"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  folderId: {
    type: String,
    default: null
  },
  title: {
    type: String,
    default: 'Files'
  },
  subtitle: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['close']);

const loading = ref(false);
const files = ref([]);

const loadFiles = async () => {
  if (!props.folderId) {
    files.value = [];
    return;
  }

  loading.value = true;

  try {
    const response = await axios.post('/api/google-drive/folder-files', {
      folder_id: props.folderId,
      branch_id: localStorage.getItem('current_branch_id')
    });

    if (response.data.success) {
      files.value = response.data.data;
    }
  } catch (error) {
    console.error('[GoogleDriveFilesModal] Error loading files:', error);
    files.value = [];
  } finally {
    loading.value = false;
  }
};

const getFileIcon = (mimeType) => {
  if (!mimeType) return '📎';
  if (mimeType.includes('pdf')) return '📄';
  if (mimeType.includes('word') || mimeType.includes('document')) return '📝';
  if (mimeType.includes('sheet') || mimeType.includes('excel')) return '📊';
  if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return '📊';
  if (mimeType.includes('image')) return '🖼️';
  if (mimeType.includes('video')) return '🎥';
  if (mimeType.includes('audio')) return '🎵';
  if (mimeType.includes('zip') || mimeType.includes('rar')) return '📦';
  return '📎';
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const openFile = (file) => {
  if (file.web_view_link) {
    window.open(file.web_view_link, '_blank');
  }
};

const close = () => {
  emit('close');
};

// Watch for modal open and folder ID changes
watch(() => [props.isOpen, props.folderId], ([isOpen, folderId]) => {
  if (isOpen && folderId) {
    loadFiles();
  }
}, { immediate: true });
</script>

