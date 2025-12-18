<template>
  <div class="vocabulary-book p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">📚 {{ t('vocabulary.title') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ t('vocabulary.subtitle') }}</p>
      </div>
      <div class="flex space-x-3">
        <button
          @click="showStats = true"
          class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition flex items-center space-x-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          <span>{{ t('vocabulary.statistics') }}</span>
        </button>
        <button
          @click="startFlashcardReview"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center space-x-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
          </svg>
          <span>{{ t('vocabulary.review_flashcards') }}</span>
        </button>
      </div>
    </div>

    <!-- Search & Filter -->
    <div class="mb-4 flex space-x-3">
      <div class="flex-1">
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          type="text"
          :placeholder="t('vocabulary.search_placeholder')"
          class="w-full px-4 py-2 border rounded-lg"
        />
      </div>
      <select v-model="filterMastery" @change="loadVocabulary" class="px-4 py-2 border rounded-lg">
        <option value="">{{ t('vocabulary.all_levels') }}</option>
        <option value="0">{{ t('vocabulary.level_new') }}</option>
        <option value="1">{{ t('vocabulary.level_beginner') }}</option>
        <option value="2">{{ t('vocabulary.level_learning') }}</option>
        <option value="3">{{ t('vocabulary.level_practicing') }}</option>
        <option value="4">{{ t('vocabulary.level_good') }}</option>
        <option value="5">{{ t('vocabulary.level_mastered') }}</option>
      </select>
    </div>

    <!-- Vocabulary Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-56">
              {{ t('vocabulary.word_wordform') }}
              <button @click="addNewEntry" class="ml-2 text-green-600 hover:text-green-800">
                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
              </button>
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">{{ t('vocabulary.synonym_antonym') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-48">{{ t('vocabulary.definition') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('vocabulary.example') }}</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">{{ t('vocabulary.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <!-- Loading -->
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
              <div class="flex justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-else-if="entries.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
              <p class="text-lg mb-2">📖 No vocabulary entries yet</p>
              <p class="text-sm">Click the + button to add your first word!</p>
            </td>
          </tr>

          <!-- Entries -->
          <tr v-for="entry in entries" :key="entry.id" class="hover:bg-gray-50">
            <!-- Column 1: Word + Word Form (2 rows) -->
            <td class="px-4 py-3">
              <div v-if="editingId === entry.id" class="space-y-2">
                <div class="flex items-center space-x-2">
                  <input
                    v-model="editForm.word"
                    @keyup.enter="saveEntry(entry.id)"
                    @keyup.esc="cancelEdit"
                    @blur="checkSpelling"
                    class="flex-1 px-2 py-1 border rounded font-semibold"
                    :class="spellingError ? 'border-red-500' : ''"
                    :placeholder="t('vocabulary.placeholder_word')"
                  />
                  <button
                    v-if="editForm.word"
                    @click="speakWord(editForm.word)"
                    class="text-blue-600 hover:text-blue-800 flex-shrink-0"
                    title="Pronunciation"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                </div>
                <!-- Spelling suggestions -->
                <div v-if="spellingError && spellingSuggestions.length > 0" class="text-xs">
                  <p class="text-red-600 mb-1">❌ {{ t('vocabulary.spelling_error') }}</p>
                  <div class="flex flex-wrap gap-1">
                    <button
                      v-for="suggestion in spellingSuggestions"
                      :key="suggestion"
                      @click="applySuggestion(suggestion)"
                      class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition"
                    >
                      {{ suggestion }}
                    </button>
                  </div>
                </div>
                <input
                  v-model="editForm.word_form"
                  @keyup.enter="saveEntry(entry.id)"
                  class="w-full px-2 py-1 border rounded text-sm"
                  :placeholder="t('vocabulary.placeholder_wordform')"
                />
              </div>
              <div v-else>
                <div class="flex items-center space-x-2 mb-1">
                  <button
                    @click="speakWord(entry.word)"
                    class="text-blue-600 hover:text-blue-800 flex-shrink-0"
                    title="Pronunciation"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                  <span class="font-semibold text-gray-900">{{ entry.word }}</span>
                  <span
                    v-if="entry.mastery_level > 0"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                    :class="masteryColor(entry.mastery_level)"
                  >
                    L{{ entry.mastery_level }}
                  </span>
                </div>
                <div class="text-xs text-gray-500 italic">{{ entry.word_form || '-' }}</div>
              </div>
            </td>

            <!-- Column 2: Synonym + Antonym (2 rows) -->
            <td class="px-4 py-3">
              <div v-if="editingId === entry.id" class="space-y-2">
                <input
                  v-model="editForm.synonym"
                  @keyup.enter="saveEntry(entry.id)"
                  class="w-full px-2 py-1 border rounded text-sm"
                  :placeholder="t('vocabulary.placeholder_synonym')"
                />
                <input
                  v-model="editForm.antonym"
                  @keyup.enter="saveEntry(entry.id)"
                  class="w-full px-2 py-1 border rounded text-sm"
                  :placeholder="t('vocabulary.placeholder_antonym')"
                />
              </div>
              <div v-else class="space-y-1">
                <div class="text-sm text-gray-600">
                  <span class="text-green-600 font-medium">+</span> {{ entry.synonym || '-' }}
                </div>
                <div class="text-sm text-gray-600">
                  <span class="text-red-600 font-medium">−</span> {{ entry.antonym || '-' }}
                </div>
              </div>
            </td>

            <!-- Column 3: Definition -->
            <td class="px-4 py-3">
              <textarea
                v-if="editingId === entry.id"
                v-model="editForm.definition"
                rows="3"
                class="w-full px-2 py-1 border rounded text-sm"
                :placeholder="t('vocabulary.placeholder_definition')"
              ></textarea>
              <span v-else class="text-sm text-gray-700">{{ entry.definition || '-' }}</span>
            </td>

            <!-- Column 4: Example -->
            <td class="px-4 py-3">
              <textarea
                v-if="editingId === entry.id"
                v-model="editForm.example"
                rows="3"
                class="w-full px-2 py-1 border rounded text-sm"
                :placeholder="t('vocabulary.placeholder_example')"
              ></textarea>
              <span v-else class="text-sm text-gray-600 italic">{{ entry.example || '-' }}</span>
            </td>

            <!-- Column 5: Actions -->
            <td class="px-4 py-3 text-center">
              <!-- Edit Mode: Save & Cancel -->
              <div v-if="editingId === entry.id" class="flex flex-col space-y-2">
                <button
                  @click="saveEntry(entry.id)"
                  class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm font-medium"
                >
                  ✓ {{ t('vocabulary.save') }}
                </button>
                <button
                  @click="cancelEdit"
                  class="px-3 py-1 bg-gray-400 text-white rounded hover:bg-gray-500 transition text-sm"
                >
                  ✕ {{ t('vocabulary.cancel') }}
                </button>
              </div>
              <!-- View Mode: Edit & Delete -->
              <div v-else class="flex justify-center space-x-2">
                <button
                  @click="editEntry(entry)"
                  class="text-blue-600 hover:text-blue-800"
                  :title="t('vocabulary.edit')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <button
                  @click="deleteEntry(entry.id)"
                  class="text-red-600 hover:text-red-800"
                  title="Delete"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.per_page" class="mt-4 flex justify-center">
      <nav class="flex space-x-2">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="loadPage(page)"
          :class="[
            'px-3 py-1 rounded',
            page === pagination.current_page
              ? 'bg-blue-600 text-white'
              : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
          ]"
        >
          {{ page }}
        </button>
      </nav>
    </div>

    <!-- Statistics Modal -->
    <div v-if="showStats" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showStats = false">
      <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
        <h3 class="text-xl font-bold mb-4">📊 {{ t('vocabulary.stats_title') }}</h3>
        <div class="space-y-3">
          <div class="flex justify-between py-2 border-b">
            <span class="font-medium">{{ t('vocabulary.total_words') }}</span>
            <span class="text-lg font-bold text-blue-600">{{ stats.total }}</span>
          </div>
          <div class="flex justify-between py-2 border-b">
            <span class="font-medium">{{ t('vocabulary.reviewed') }}</span>
            <span class="text-lg font-bold text-green-600">{{ stats.reviewed }}</span>
          </div>
          <div class="flex justify-between py-2 border-b">
            <span class="font-medium">{{ t('vocabulary.mastered_count') }}</span>
            <span class="text-lg font-bold text-purple-600">{{ stats.mastered }}</span>
          </div>
          
          <div class="mt-4">
            <h4 class="font-semibold mb-2">{{ t('vocabulary.mastery_distribution') }}</h4>
            <div class="space-y-2">
              <div v-for="level in [0, 1, 2, 3, 4, 5]" :key="level" class="flex items-center">
                <span class="w-20 text-sm">{{ t('vocabulary.level') }} {{ level }}:</span>
                <div class="flex-1 bg-gray-200 rounded-full h-4 ml-2">
                  <div
                    class="h-4 rounded-full transition-all"
                    :class="masteryBarColor(level)"
                    :style="{ width: masteryPercentage(level) + '%' }"
                  ></div>
                </div>
                <span class="ml-2 text-sm font-medium w-12 text-right">{{ stats.mastery_distribution?.[level] || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
        <button
          @click="showStats = false"
          class="mt-6 w-full px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300"
        >
          Close
        </button>
      </div>
    </div>

    <!-- Flashcard Review Modal -->
    <FlashcardReview
      v-if="showFlashcards"
      @close="showFlashcards = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import FlashcardReview from './FlashcardReview.vue'
import { useI18n } from '../../composables/useI18n'

const { t } = useI18n()

const loading = ref(false)
const entries = ref([])
const searchQuery = ref('')
const filterMastery = ref('')
const editingId = ref(null)
const editForm = ref({})
const showStats = ref(false)
const showFlashcards = ref(false)
const spellingError = ref(false)
const spellingSuggestions = ref([])
const stats = ref({
  total: 0,
  reviewed: 0,
  mastered: 0,
  mastery_distribution: {}
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 50,
  total: 0
})

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }
  return pages
})

const loadVocabulary = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: 50
    }
    
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    
    if (filterMastery.value !== '') {
      params.mastery_level = filterMastery.value
    }
    
    const response = await axios.get('/api/course/vocabulary', { params })
    
    entries.value = response.data.data.data
    pagination.value = {
      current_page: response.data.data.current_page,
      last_page: response.data.data.last_page,
      per_page: response.data.data.per_page,
      total: response.data.data.total
    }
  } catch (error) {
    console.error('Failed to load vocabulary:', error)
    Swal.fire('Error', 'Failed to load vocabulary entries', 'error')
  } finally {
    loading.value = false
  }
}

const loadPage = (page) => {
  loadVocabulary(page)
}

let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadVocabulary(1)
  }, 500)
}

const addNewEntry = () => {
  const newEntry = {
    id: 'new-' + Date.now(),
    word: '',
    word_form: '',
    definition: '',
    synonym: '',
    antonym: '',
    example: '',
    mastery_level: 0
  }
  entries.value.unshift(newEntry)
  editEntry(newEntry)
}

const editEntry = (entry) => {
  editingId.value = entry.id
  editForm.value = { ...entry }
}

const cancelEdit = () => {
  // Remove new entry if cancelled
  if (typeof editingId.value === 'string' && editingId.value.startsWith('new-')) {
    entries.value = entries.value.filter(e => e.id !== editingId.value)
  }
  editingId.value = null
  editForm.value = {}
  spellingError.value = false
  spellingSuggestions.value = []
}

const checkSpelling = async () => {
  const word = editForm.value.word?.trim().toLowerCase()
  if (!word) {
    spellingError.value = false
    spellingSuggestions.value = []
    return
  }
  
  try {
    // Use Datamuse API to check if word exists and get suggestions
    // Note: Using fetch instead of axios to avoid branch_id interceptor
    const response = await fetch(`https://api.datamuse.com/words?sp=${word}&max=5`)
    const data = await response.json()
    
    if (data && data.length > 0) {
      const exactMatch = data.find(item => item.word.toLowerCase() === word)
      
      if (exactMatch) {
        // Word is spelled correctly
        spellingError.value = false
        spellingSuggestions.value = []
      } else {
        // Word might be misspelled, show suggestions
        spellingError.value = true
        spellingSuggestions.value = data.slice(0, 5).map(item => item.word)
      }
    } else {
      // No matches found - might be misspelled
      spellingError.value = true
      // Try to get similar sounding words
      const soundsLikeResponse = await fetch(`https://api.datamuse.com/words?sl=${word}&max=5`)
      const soundsLikeData = await soundsLikeResponse.json()
      spellingSuggestions.value = soundsLikeData.slice(0, 5).map(item => item.word)
    }
  } catch (error) {
    console.error('Spelling check failed:', error)
    spellingError.value = false
    spellingSuggestions.value = []
  }
}

const applySuggestion = (suggestion) => {
  editForm.value.word = suggestion
  spellingError.value = false
  spellingSuggestions.value = []
}

const saveEntry = async (id) => {
  // Validate word is not empty
  if (!editForm.value.word || !editForm.value.word.trim()) {
    cancelEdit()
    return
  }
  
  try {
    let response
    
    if (typeof id === 'string' && id.startsWith('new-')) {
      // Create new entry
      response = await axios.post('/api/course/vocabulary', editForm.value)
      // Replace temp entry with real one
      const index = entries.value.findIndex(e => e.id === id)
      if (index !== -1) {
        entries.value[index] = response.data.data
      }
    } else {
      // Update existing entry
      response = await axios.put(`/api/course/vocabulary/${id}`, editForm.value)
      const index = entries.value.findIndex(e => e.id === id)
      if (index !== -1) {
        entries.value[index] = response.data.data
      }
    }
    
    editingId.value = null
    editForm.value = {}
    spellingError.value = false
    spellingSuggestions.value = []
  } catch (error) {
    console.error('Failed to save entry:', error)
    Swal.fire('Error', error.response?.data?.message || 'Failed to save entry', 'error')
  }
}

const deleteEntry = async (id) => {
  const result = await Swal.fire({
    title: 'Delete this word?',
    text: 'This action cannot be undone',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel'
  })
  
  if (!result.isConfirmed) return
  
  try {
    await axios.delete(`/api/course/vocabulary/${id}`)
    entries.value = entries.value.filter(e => e.id !== id)
    Swal.fire('Deleted!', 'Word has been removed from your vocabulary book', 'success')
  } catch (error) {
    console.error('Failed to delete entry:', error)
    Swal.fire('Error', 'Failed to delete entry', 'error')
  }
}

const speakWord = (word) => {
  if ('speechSynthesis' in window) {
    const utterance = new SpeechSynthesisUtterance(word)
    utterance.lang = 'en-US'
    utterance.rate = 0.8 // Slower for clarity
    window.speechSynthesis.speak(utterance)
  } else {
    console.warn('Text-to-speech not supported')
  }
}

const loadStatistics = async () => {
  try {
    const response = await axios.get('/api/course/vocabulary/statistics')
    stats.value = response.data.data
  } catch (error) {
    console.error('Failed to load statistics:', error)
  }
}

const startFlashcardReview = () => {
  if (entries.value.length === 0) {
    Swal.fire('No words yet', 'Add some words to your vocabulary book first!', 'info')
    return
  }
  showFlashcards.value = true
}

const masteryColor = (level) => {
  const colors = {
    0: 'bg-gray-200 text-gray-700',
    1: 'bg-red-100 text-red-700',
    2: 'bg-orange-100 text-orange-700',
    3: 'bg-yellow-100 text-yellow-700',
    4: 'bg-green-100 text-green-700',
    5: 'bg-purple-100 text-purple-700'
  }
  return colors[level] || colors[0]
}

const masteryBarColor = (level) => {
  const colors = {
    0: 'bg-gray-400',
    1: 'bg-red-400',
    2: 'bg-orange-400',
    3: 'bg-yellow-400',
    4: 'bg-green-400',
    5: 'bg-purple-400'
  }
  return colors[level] || colors[0]
}

const masteryPercentage = (level) => {
  const count = stats.value.mastery_distribution?.[level] || 0
  const total = stats.value.total || 1
  return (count / total) * 100
}

onMounted(() => {
  loadVocabulary()
  loadStatistics()
})
</script>

<style scoped>
.vocabulary-book {
  max-width: 1400px;
  margin: 0 auto;
}

table {
  table-layout: fixed;
}
</style>

