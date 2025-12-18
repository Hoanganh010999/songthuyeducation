<template>
  <div class="zalo-image-uploader">
    <!-- Upload Button Trigger -->
    <button
      v-if="!showUploader"
      @click="showUploader = true"
      type="button"
      class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
      title="Upload Images"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
    </button>

    <!-- Upload Modal -->
    <div
      v-if="showUploader"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeUploader"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">📷 Upload Images</h3>
          <button
            @click="closeUploader"
            class="text-gray-400 hover:text-gray-600 transition"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
          <!-- Dropzone Area -->
          <div
            ref="dropzoneRef"
            @drop.prevent="handleDrop"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @click="triggerFileInput"
            :class="[
              'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition',
              isDragging 
                ? 'border-blue-500 bg-blue-50' 
                : selectedImages.length > 0
                ? 'border-gray-300 bg-gray-50'
                : 'border-gray-300 hover:border-blue-400 hover:bg-blue-50'
            ]"
          >
            <input
              ref="fileInputRef"
              type="file"
              multiple
              accept="image/jpeg,image/png,image/gif,image/webp"
              @change="handleFileSelect"
              class="hidden"
            />

            <div v-if="selectedImages.length === 0">
              <div class="w-16 h-16 mx-auto mb-4 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
              </div>
              <p class="text-lg font-medium text-gray-700 mb-2">
                Click to browse or drag images here
              </p>
              <p class="text-sm text-gray-500 mb-3">
                Supports: JPG, PNG, GIF, WebP
              </p>
              <p class="text-xs text-gray-400">
                Max 5MB per image • Up to 10 images • Or use Ctrl+V to paste
              </p>
            </div>

            <div v-else class="text-sm text-gray-600">
              Click to add more images or drag here
            </div>
          </div>

          <!-- Image Previews Grid -->
          <div v-if="selectedImages.length > 0" class="mt-6">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm font-medium text-gray-700">
                Selected Images ({{ selectedImages.length }}/10)
              </p>
              <button
                @click="clearAll"
                class="text-xs text-red-600 hover:text-red-700 font-medium"
              >
                Clear All
              </button>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-4 gap-4">
              <div
                v-for="(image, index) in selectedImages"
                :key="index"
                class="relative group"
              >
                <img
                  :src="image.preview"
                  :alt="`Preview ${index + 1}`"
                  class="w-full h-32 object-cover rounded-lg border-2 border-gray-200"
                />
                <!-- Remove button -->
                <button
                  @click="removeImage(index)"
                  class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
                <!-- File info -->
                <div class="mt-1 text-xs text-gray-500 truncate">
                  {{ formatFileSize(image.file.size) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            {{ errorMessage }}
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3">
          <button
            @click="closeUploader"
            type="button"
            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Cancel
          </button>
          <button
            @click="sendImages"
            :disabled="selectedImages.length === 0 || uploading"
            type="button"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center gap-2"
          >
            <svg v-if="uploading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ uploading ? 'Uploading...' : `Send ${selectedImages.length} Image${selectedImages.length > 1 ? 's' : ''}` }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  accountId: {
    type: [String, Number],
    required: true
  },
  recipientId: {
    type: [String, Number], 
    required: true
  },
  recipientType: {
    type: String,
    required: true,
    validator: (value) => ['user', 'group'].includes(value)
  }
})

const emit = defineEmits(['images-sent', 'close'])

// State
const showUploader = ref(false)
const selectedImages = ref([])
const isDragging = ref(false)
const uploading = ref(false)
const errorMessage = ref('')

// Refs
const dropzoneRef = ref(null)
const fileInputRef = ref(null)

// Constants
const MAX_FILE_SIZE = 5 * 1024 * 1024 // 5MB
const MAX_IMAGES = 10
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']

// Trigger file input
const triggerFileInput = () => {
  fileInputRef.value?.click()
}

// Handle file selection from input
const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  addFiles(files)
  // Reset input
  event.target.value = ''
}

// Handle drag and drop
const handleDrop = (event) => {
  isDragging.value = false
  const files = Array.from(event.dataTransfer.files)
  addFiles(files)
}

// Handle paste from clipboard
const handlePaste = (event) => {
  if (!showUploader.value) {
    // Auto-open uploader when pasting images
    const items = event.clipboardData?.items
    if (items) {
      for (let item of items) {
        if (item.type.indexOf('image') !== -1) {
          showUploader.value = true
          break
        }
      }
    }
  }

  const items = event.clipboardData?.items
  if (!items) return

  const files = []
  for (let item of items) {
    if (item.type.indexOf('image') !== -1) {
      const file = item.getAsFile()
      if (file) files.push(file)
    }
  }

  if (files.length > 0) {
    event.preventDefault()
    addFiles(files)
  }
}

// Add files to selection
const addFiles = (files) => {
  errorMessage.value = ''

  for (let file of files) {
    // Check limit
    if (selectedImages.value.length >= MAX_IMAGES) {
      errorMessage.value = `Maximum ${MAX_IMAGES} images allowed`
      break
    }

    // Validate type
    if (!ALLOWED_TYPES.includes(file.type)) {
      errorMessage.value = `Invalid file type: ${file.name}. Only JPG, PNG, GIF, WebP allowed.`
      continue
    }

    // Validate size
    if (file.size > MAX_FILE_SIZE) {
      errorMessage.value = `File too large: ${file.name}. Max ${formatFileSize(MAX_FILE_SIZE)}`
      continue
    }

    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      selectedImages.value.push({
        file: file,
        preview: e.target.result
      })
    }
    reader.readAsDataURL(file)
  }
}

// Remove image
const removeImage = (index) => {
  selectedImages.value.splice(index, 1)
  errorMessage.value = ''
}

// Clear all
const clearAll = () => {
  selectedImages.value = []
  errorMessage.value = ''
}

// Close uploader
const closeUploader = () => {
  if (!uploading.value) {
    showUploader.value = false
    selectedImages.value = []
    errorMessage.value = ''
    emit('close')
  }
}

// Send images
const sendImages = async () => {
  if (selectedImages.value.length === 0 || uploading.value) return

  uploading.value = true
  errorMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('account_id', props.accountId)
    formData.append('recipient_id', props.recipientId)
    formData.append('recipient_type', props.recipientType)

    // Use 'images' field name (Laravel will receive as 'images' array)
    selectedImages.value.forEach((image) => {
      formData.append('images[]', image.file)
    })

    // Use upgraded /upload-image endpoint (not /send-images)
    const response = await axios.post('/api/zalo/messages/upload-image', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    if (response.data.success) {
      // Handle response - backend returns different format for multiple
      const uploadedImages = response.data.data.images || [response.data.data]
      const errors = response.data.data.errors || []
      
      // Show errors if any
      if (errors.length > 0) {
        const errorNames = errors.map(e => e.filename).join(', ')
        console.warn(`Failed to upload: ${errorNames}`)
      }
      
      emit('images-sent', {
        uploaded: uploadedImages,
        errors: errors,
        totalCount: selectedImages.value.length,
        successCount: uploadedImages.length
      })
      
      closeUploader()
    } else {
      throw new Error(response.data.message || 'Failed to send images')
    }
  } catch (error) {
    console.error('Error sending images:', error)
    errorMessage.value = error.response?.data?.message || error.message || 'Failed to send images'
  } finally {
    uploading.value = false
  }
}

// Format file size
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

// Lifecycle
onMounted(() => {
  // Listen for paste events globally when component is active
  document.addEventListener('paste', handlePaste)
})

onUnmounted(() => {
  // Cleanup
  document.removeEventListener('paste', handlePaste)
})
</script>

<style scoped>
.zalo-image-uploader {
  display: inline-block;
}
</style>
