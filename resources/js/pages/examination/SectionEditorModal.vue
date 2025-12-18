<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>

      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ isEditing ? 'Chỉnh sửa phần' : 'Thêm phần mới' }}
          </h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="saveSection" class="p-6 space-y-6">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tên phần *</label>
            <input v-model="form.title" type="text" required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="VD: Part 1 - Listening" />
          </div>

          <!-- Instructions -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hướng dẫn</label>
            <textarea v-model="form.instructions" rows="3"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Hướng dẫn cho học viên về phần này"></textarea>
          </div>

          <!-- Time limit for section -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian (phút)</label>
            <input v-model.number="form.time_limit" type="number" min="0"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Để trống nếu không giới hạn riêng" />
            <p class="text-xs text-gray-500 mt-1">Thời gian giới hạn riêng cho phần này (tùy chọn)</p>
          </div>

          <!-- Audio Track (for listening sections) -->
          <div class="border rounded-lg p-4">
            <h3 class="font-medium text-gray-800 mb-3">Audio (Listening)</h3>

            <div v-if="form.audio_track" class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg mb-3">
              <span class="text-2xl">🎧</span>
              <div class="flex-1">
                <p class="font-medium">{{ form.audio_track.title }}</p>
                <p class="text-sm text-gray-500">{{ formatDuration(form.audio_track.duration) }}</p>
              </div>
              <button type="button" @click="removeAudioTrack" class="text-red-600 hover:text-red-800">
                Xóa
              </button>
            </div>

            <div v-else class="space-y-3">
              <div>
                <label class="block text-sm text-gray-600 mb-1">Chọn từ thư viện</label>
                <select v-model="form.audio_track_id" class="w-full px-3 py-2 border rounded-lg">
                  <option value="">Không có audio</option>
                  <option v-for="track in audioTracks" :key="track.id" :value="track.id">
                    {{ track.title }} ({{ formatDuration(track.duration) }})
                  </option>
                </select>
              </div>

              <div class="text-center text-gray-400 text-sm">hoặc</div>

              <div>
                <label class="block text-sm text-gray-600 mb-1">URL Audio</label>
                <input v-model="form.audio_url" type="url"
                  class="w-full px-3 py-2 border rounded-lg"
                  placeholder="https://..." />
              </div>
            </div>
          </div>

          <!-- Reading Passage -->
          <div class="border rounded-lg p-4">
            <h3 class="font-medium text-gray-800 mb-3">Đoạn văn (Reading)</h3>

            <div v-if="form.reading_passage" class="p-3 bg-green-50 rounded-lg mb-3">
              <div class="flex items-start justify-between mb-2">
                <p class="font-medium">{{ form.reading_passage.title }}</p>
                <button type="button" @click="removePassage" class="text-red-600 hover:text-red-800 text-sm">
                  Xóa
                </button>
              </div>
              <p class="text-sm text-gray-600 line-clamp-3">{{ stripHtml(form.reading_passage.content) }}</p>
            </div>

            <div v-else class="space-y-3">
              <div>
                <label class="block text-sm text-gray-600 mb-1">Chọn từ thư viện</label>
                <select v-model="form.reading_passage_id" class="w-full px-3 py-2 border rounded-lg">
                  <option value="">Không có đoạn văn</option>
                  <option v-for="passage in passages" :key="passage.id" :value="passage.id">
                    {{ passage.title }}
                  </option>
                </select>
              </div>

              <div class="text-center text-gray-400 text-sm">hoặc</div>

              <div>
                <label class="block text-sm text-gray-600 mb-1">Nhập đoạn văn mới</label>
                <input v-model="newPassage.title" type="text"
                  class="w-full px-3 py-2 border rounded-lg mb-2"
                  placeholder="Tiêu đề đoạn văn" />
                <textarea v-model="newPassage.content" rows="4"
                  class="w-full px-3 py-2 border rounded-lg"
                  placeholder="Nội dung đoạn văn..."></textarea>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <button type="button" @click="$emit('close')"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
              Hủy
            </button>
            <button type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              {{ isEditing ? 'Cập nhật' : 'Thêm phần' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/api'

const props = defineProps({
  section: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'save'])

const audioTracks = ref([])
const passages = ref([])

const isEditing = computed(() => !!props.section?.id)

const defaultForm = {
  title: '',
  instructions: '',
  time_limit: null,
  audio_track_id: '',
  audio_track: null,
  audio_url: '',
  reading_passage_id: '',
  reading_passage: null
}

const form = ref({ ...defaultForm })

const newPassage = ref({
  title: '',
  content: ''
})

watch(() => props.section, (section) => {
  if (section) {
    form.value = {
      ...defaultForm,
      ...section
    }
  } else {
    form.value = { ...defaultForm }
  }
}, { immediate: true })

onMounted(async () => {
  await Promise.all([
    fetchAudioTracks(),
    fetchPassages()
  ])
})

async function fetchAudioTracks() {
  try {
    const response = await api.get('/examination/audio-tracks', { params: { per_page: 100 } })
    audioTracks.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching audio tracks:', error)
  }
}

async function fetchPassages() {
  try {
    const response = await api.get('/examination/reading-passages', { params: { per_page: 100 } })
    passages.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching passages:', error)
  }
}

function removeAudioTrack() {
  form.value.audio_track = null
  form.value.audio_track_id = ''
  form.value.audio_url = ''
}

function removePassage() {
  form.value.reading_passage = null
  form.value.reading_passage_id = ''
}

function saveSection() {
  const data = {
    title: form.value.title,
    instructions: form.value.instructions,
    time_limit: form.value.time_limit || null,
    audio_track_id: form.value.audio_track_id || null,
    audio_url: form.value.audio_url || null,
    reading_passage_id: form.value.reading_passage_id || null
  }

  // If new passage content is provided
  if (newPassage.value.title && newPassage.value.content) {
    data.new_passage = {
      title: newPassage.value.title,
      content: newPassage.value.content
    }
  }

  emit('save', data)
}

function formatDuration(seconds) {
  if (!seconds) return '-'
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

function stripHtml(html) {
  return html?.replace(/<[^>]*>/g, '') || ''
}
</script>
