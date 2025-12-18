<template>
  <div class="ielts-writing-test min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b sticky top-0 z-50 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <div class="bg-gradient-to-br from-orange-500 to-red-500 p-2 rounded-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
          </div>
          <div>
            <h1 class="font-semibold text-gray-800">{{ testData.title }}</h1>
          </div>
        </div>

        <!-- Timer & Controls -->
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2" :class="timeRemaining < 600 ? 'text-red-600' : 'text-orange-500'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-semibold">{{ formattedTime }} minutes remaining</span>
          </div>

          <button @click="toggleFullscreen" class="p-2 hover:bg-gray-100 rounded-lg">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
          </button>

          <button
            @click="showNotes = !showNotes"
            class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>{{ showNotes ? 'Ẩn' : 'Hiện' }} Notes ({{ notes.length }})</span>
          </button>

          <button
            @click="submitTest"
            class="bg-orange-600 hover:bg-orange-700 text-white font-medium py-2 px-6 rounded-lg transition-colors"
          >
            Submit ▶
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto p-6">
      <!-- Instructions -->
      <div class="bg-orange-50 border-l-4 border-orange-500 p-4 mb-6 rounded-r-lg">
        <p class="text-sm text-gray-700">
          <strong>IELTS Writing Test</strong> - You have <strong>60 minutes</strong> to complete both tasks. Write your answers in the text boxes provided.
        </p>
      </div>

      <!-- Task Tabs -->
      <div class="flex gap-2 mb-6">
        <button
          v-for="(task, idx) in tasks"
          :key="idx"
          @click="currentTaskIndex = idx"
          class="px-6 py-3 rounded-lg font-medium transition-colors flex-1"
          :class="currentTaskIndex === idx 
            ? 'bg-orange-600 text-white' 
            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
        >
          {{ task.title }}
          <span class="ml-2 text-sm opacity-75">({{ getWordCount(idx) }} words)</span>
        </button>
      </div>

      <!-- Layout: Task + Editor + Notes (optional) -->
      <div class="grid gap-6" :class="showNotes ? 'grid-cols-1 lg:grid-cols-3' : 'grid-cols-1 lg:grid-cols-2'">
        <!-- Left: Task Instructions -->
        <div class="bg-white rounded-xl shadow-lg p-6 overflow-y-auto" style="max-height: 70vh;">
          <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-xl font-bold text-gray-800">{{ currentTask.title }}</h3>
              <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm font-medium">
                {{ currentTask.timeRecommendation }} minutes
              </span>
            </div>
            <p class="text-sm text-gray-600">{{ currentTask.instruction }}</p>
          </div>

          <!-- Task Image (if exists) -->
          <div v-if="currentTask.imageUrl" class="mb-6">
            <img
              :src="currentTask.imageUrl"
              :alt="currentTask.visualType || 'Task Visual'"
              class="max-w-full h-auto rounded-lg shadow-md border border-gray-200"
            />
            <p v-if="currentTask.imageSource" class="text-xs text-gray-500 mt-2 italic">
              Source: {{ currentTask.imageSource }}
            </p>
          </div>

          <!-- Task Content -->
          <div
            ref="taskContentRef"
            class="prose max-w-none select-text relative"
            @mouseup="handleTextSelection"
          >
            <div v-html="currentTask.content"></div>
          </div>

          <!-- Task Requirements -->
          <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h4 class="font-semibold text-gray-800 mb-2">Requirements:</h4>
            <ul class="text-sm text-gray-700 space-y-1">
              <li>✓ Write at least <strong>{{ currentTask.minWords }} words</strong></li>
              <li>✓ Spend about <strong>{{ currentTask.timeRecommendation }} minutes</strong> on this task</li>
              <li v-if="currentTaskIndex === 0">✓ Include all key features from the data</li>
              <li v-if="currentTaskIndex === 1">✓ Give reasons for your answer and include relevant examples</li>
            </ul>
          </div>
        </div>

        <!-- Right: Writing Editor -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <!-- Editor Header -->
          <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-red-50">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-gray-800">Your Answer</h3>
              <div class="flex items-center gap-4">
                <span 
                  class="text-sm font-medium"
                  :class="getWordCount(currentTaskIndex) >= currentTask.minWords ? 'text-green-600' : 'text-orange-600'"
                >
                  {{ getWordCount(currentTaskIndex) }} / {{ currentTask.minWords }} words
                </span>
                <button
                  @click="clearAnswer"
                  class="px-3 py-1 text-sm bg-red-50 text-red-600 hover:bg-red-100 rounded transition-colors"
                >
                  Clear
                </button>
              </div>
            </div>
          </div>

          <!-- Text Editor -->
          <div class="p-6">
            <textarea
              v-model="answers[currentTaskIndex]"
              :placeholder="`Write your answer for ${currentTask.title} here...`"
              class="w-full h-[600px] p-4 border border-gray-300 rounded-lg text-gray-800 leading-relaxed focus:outline-none focus:ring-2 focus:ring-orange-500 resize-none font-mono text-sm"
              @input="updateWordCount"
            ></textarea>
          </div>

          <!-- Word Count Warning -->
          <div v-if="getWordCount(currentTaskIndex) > 0 && getWordCount(currentTaskIndex) < currentTask.minWords" class="px-6 pb-6">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded-r">
              <p class="text-sm text-yellow-800">
                ⚠️ You need {{ currentTask.minWords - getWordCount(currentTaskIndex) }} more words to meet the minimum requirement.
              </p>
            </div>
          </div>
        </div>

        <!-- Right: Notes Sidebar (optional) -->
        <div v-if="showNotes" class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">My Notes</h3>
            <button
              @click="showNotes = false"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Search Notes -->
          <div class="mb-4">
            <input
              type="text"
              v-model="noteSearch"
              placeholder="Search notes..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Notes List -->
          <div class="space-y-3 max-h-[600px] overflow-y-auto">
            <div
              v-for="(note, idx) in filteredNotes"
              :key="idx"
              class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 cursor-pointer"
              @click="scrollToNote(note)"
            >
              <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium text-blue-600">Note {{ idx + 1 }}</span>
                <button
                  @click.stop="deleteNote(idx)"
                  class="text-gray-400 hover:text-red-500"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
              <p class="text-xs text-gray-600 mb-2 italic">"{{ note.selectedText.substring(0, 50) }}..."</p>
              <p class="text-sm text-gray-800">{{ note.content }}</p>
            </div>

            <div v-if="filteredNotes.length === 0" class="text-center py-8 text-gray-400">
              <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-sm">Chưa có note nào</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Selection Popup (appears when text is selected) -->
    <div
      v-if="showSelectionPopup"
      :style="{ top: `${popupPosition.y}px`, left: `${popupPosition.x}px` }"
      class="fixed z-[100] bg-white rounded-lg shadow-xl border border-gray-200 p-2 flex gap-2 selection-popup"
    >
      <button
        @click="addNote"
        class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm font-medium transition-colors flex items-center gap-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        Note
      </button>
      <button
        @click="highlightSelected"
        class="px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-yellow-900 rounded text-sm font-medium transition-colors flex items-center gap-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
        </svg>
        Highlight
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

// Props for Full Test mode
const props = defineProps({
  testId: {
    type: [String, Number],
    default: null
  },
  autoSubmit: {
    type: Boolean,
    default: false
  }
})

const route = useRoute()
const router = useRouter()

// Use prop testId if available, otherwise get from route
const activeTestId = computed(() => {
  const id = props.testId || route.params.testId
  console.log('🔍 Active testId:', id, { prop: props.testId, route: route.params.testId })
  return id
})

const testData = ref({
  title: 'IELTS Writing Practice Test',
  timeLimit: 60
})
const currentTaskIndex = ref(0)
const answers = ref(['', ''])
const timeRemaining = ref(60 * 60) // 60 minutes in seconds
const taskContentRef = ref(null)
const isSubmitting = ref(false) // Prevent double submit

// Notes & Highlights
const notes = ref([])
const showNotes = ref(false)
const noteSearch = ref('')
const selectedText = ref('')
const selectionRange = ref(null)
const showSelectionPopup = ref(false)
const popupPosition = ref({ x: 0, y: 0 })

// Tasks data
const tasks = ref([
  {
    title: 'Task 1',
    timeRecommendation: 20,
    minWords: 150,
    instruction: 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant.',
    content: '<p>You should spend about 20 minutes on this task.</p><p><strong>The graph below shows the number of tourists visiting a particular Caribbean island between 2010 and 2017.</strong></p><p>Summarize the information by selecting and reporting the main features, and make comparisons where relevant.</p><p>Write at least 150 words.</p>'
  },
  {
    title: 'Task 2',
    timeRecommendation: 40,
    minWords: 250,
    instruction: 'Give reasons for your answer and include any relevant examples from your own knowledge or experience.',
    content: '<p>You should spend about 40 minutes on this task.</p><p><strong>Some people think that hosting international sports events is good for the country, while some people think it is bad. Discuss both views and state your opinion.</strong></p><p>Give reasons for your answer and include any relevant examples from your own knowledge or experience.</p><p>Write at least 250 words.</p>'
  }
])

const currentTask = computed(() => tasks.value[currentTaskIndex.value])

const formattedTime = computed(() => {
  const minutes = Math.floor(timeRemaining.value / 60)
  return minutes
})

const filteredNotes = computed(() => {
  if (!noteSearch.value) return notes.value
  return notes.value.filter(note => 
    note.content.toLowerCase().includes(noteSearch.value.toLowerCase()) ||
    note.selectedText.toLowerCase().includes(noteSearch.value.toLowerCase())
  )
})

function getWordCount(taskIndex) {
  if (!answers.value[taskIndex]) return 0
  return answers.value[taskIndex].trim().split(/\s+/).filter(word => word.length > 0).length
}

function updateWordCount() {
  // Force reactivity
  answers.value = [...answers.value]
}

async function clearAnswer() {
  const result = await Swal.fire({
    title: 'Clear Answer?',
    text: 'This will delete all your work for this task. This cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Clear',
    cancelButtonText: 'Cancel'
  })
  
  if (result.isConfirmed) {
    answers.value[currentTaskIndex.value] = ''
    Swal.fire({
      icon: 'success',
      title: 'Cleared',
      timer: 1000,
      showConfirmButton: false
    })
  }
}

function handleTextSelection(event) {
  setTimeout(() => {
    const selection = window.getSelection()
    const text = selection.toString().trim()
    
    if (text.length > 0) {
      showSelectionPopup.value = true
      selectedText.value = text
      selectionRange.value = selection.getRangeAt(0).cloneRange()
      
      const rect = selection.getRangeAt(0).getBoundingClientRect()
      popupPosition.value = {
        x: rect.left + (rect.width / 2) - 100,
        y: rect.top + window.scrollY - 60
      }
    } else {
      showSelectionPopup.value = false
    }
  }, 10)
}

function highlightSelected() {
  if (!selectionRange.value) return
  
  try {
    const span = document.createElement('span')
    span.className = 'bg-yellow-200 cursor-pointer transition-colors hover:bg-yellow-300'
    span.setAttribute('data-highlight', 'true')
    span.setAttribute('title', 'Highlighted text')
    
    selectionRange.value.surroundContents(span)
    
    showSelectionPopup.value = false
    window.getSelection().removeAllRanges()
  } catch (error) {
    console.error('Failed to highlight:', error)
    Swal.fire({
      icon: 'error',
      title: 'Cannot highlight',
      text: 'Please select simpler text',
      timer: 2000
    })
  }
}

async function addNote() {
  if (!selectedText.value || !selectionRange.value) return
  
  const { value: noteContent } = await Swal.fire({
    title: 'Add Note',
    input: 'textarea',
    inputLabel: 'Your note',
    inputPlaceholder: 'Type your note here...',
    inputAttributes: {
      'aria-label': 'Type your note here'
    },
    showCancelButton: true,
    confirmButtonText: 'Save',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#3b82f6'
  })
  
  if (!noteContent) {
    showSelectionPopup.value = false
    return
  }
  
  try {
    const span = document.createElement('span')
    span.className = 'border-b-2 border-blue-400 cursor-help transition-colors hover:bg-blue-50'
    span.setAttribute('data-note', 'true')
    span.setAttribute('data-note-id', notes.value.length)
    span.setAttribute('title', noteContent)
    
    selectionRange.value.surroundContents(span)
    
    notes.value.push({
      id: notes.value.length,
      selectedText: selectedText.value,
      content: noteContent,
      timestamp: new Date().toISOString(),
      element: span
    })
    
    span.addEventListener('click', () => {
      showNotes.value = true
    })
    
    showSelectionPopup.value = false
    showNotes.value = true
    window.getSelection().removeAllRanges()
  } catch (error) {
    console.error('Failed to add note:', error)
    Swal.fire({
      icon: 'error',
      title: 'Cannot add note',
      text: 'Please select simpler text',
      timer: 2000
    })
  }
}

function scrollToNote(note) {
  if (note.element) {
    note.element.scrollIntoView({ behavior: 'smooth', block: 'center' })
    note.element.classList.add('bg-blue-100')
    setTimeout(() => {
      note.element.classList.remove('bg-blue-100')
    }, 1000)
  }
}

async function deleteNote(index) {
  const result = await Swal.fire({
    title: 'Delete this note?',
    text: 'This action cannot be undone',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel'
  })
  
  if (result.isConfirmed) {
    const note = notes.value[index]
    if (note.element) {
      const parent = note.element.parentNode
      if (parent) {
        parent.replaceChild(document.createTextNode(note.element.textContent), note.element)
        parent.normalize()
      }
    }
    notes.value.splice(index, 1)
  }
}

onMounted(async () => {
  await loadTestData()
  startTimer()
})

let timerInterval = null

function startTimer() {
  timerInterval = setInterval(() => {
    if (timeRemaining.value > 0) {
      timeRemaining.value--
    } else {
      submitTest()
    }
  }, 1000)
}

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})

async function loadTestData() {
  try {
    const response = await axios.get(`/api/examination/tests/${activeTestId.value}`)
    testData.value = response.data.data
    
    if (testData.value.settings) {
      const settings = typeof testData.value.settings === 'string' 
        ? JSON.parse(testData.value.settings) 
        : testData.value.settings
      
      // Read from new namespaced structure with backward compatibility fallback
      const tasksData = settings.writing?.tasks || settings.tasks

      if (tasksData && tasksData.length > 0) {
        tasks.value = tasksData.map((task, idx) => ({
          title: task.title || `Task ${idx + 1}`,
          // Support both timeRecommendation and timeLimit
          timeRecommendation: task.timeRecommendation || task.timeLimit || (idx === 0 ? 20 : 40),
          minWords: task.minWords || (idx === 0 ? 150 : 250),
          instruction: task.instruction || '',
          // Support both content and prompt fields
          content: task.content || task.prompt || '',
          // Include all image-related fields
          imageUrl: task.imageUrl || null,
          imagePath: task.imagePath || null,
          imageSource: task.imageSource || null,
          visualType: task.visualType || 'bar_chart',
          chartData: task.chartData || null,
          imagePrompt: task.imagePrompt || null
        }))
        console.log('✅ Writing tasks loaded:', tasks.value)
      }
    }
  } catch (error) {
    console.error('❌ Failed to load test:', error)
  }
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen()
  } else {
    document.exitFullscreen()
  }
}

async function submitTest() {
  // Prevent double submit
  if (isSubmitting.value) {
    console.log('⚠️ Already submitting, skipping...')
    return
  }
  
  // Clear timer immediately to prevent multiple calls
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
  
  // Check if answers meet minimum word count
  const insufficientTasks = tasks.value.filter((task, idx) => 
    getWordCount(idx) < task.minWords
  )
  
  if (insufficientTasks.length > 0) {
    const result = await Swal.fire({
      title: 'Word Count Warning',
      html: `
        <div class="text-left">
          <p class="mb-3">Some tasks have not met the minimum word count:</p>
          <ul class="list-disc pl-5 space-y-1">
            ${insufficientTasks.map(task => {
              const idx = tasks.value.indexOf(task)
              return `<li><strong>${task.title}</strong>: ${getWordCount(idx)}/${task.minWords} words</li>`
            }).join('')}
          </ul>
          <p class="mt-3 text-sm text-gray-600">Do you want to submit anyway?</p>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ea580c',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Submit Anyway',
      cancelButtonText: 'Continue Writing'
    })
    
    if (!result.isConfirmed) {
      // Restart timer if user cancels
      startTimer()
      return
    }
  } else {
    const result = await Swal.fire({
      title: 'Submit Test?',
      html: `
        <p>Task 1: <strong>${getWordCount(0)} words</strong></p>
        <p>Task 2: <strong>${getWordCount(1)} words</strong></p>
        <p class="text-sm text-gray-600 mt-2">Are you sure you want to submit?</p>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#ea580c',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Submit',
      cancelButtonText: 'Continue'
    })
    
    if (!result.isConfirmed) {
      // Restart timer if user cancels
      startTimer()
      return
  }
  }

  isSubmitting.value = true

  try {
    console.log('📤 Submitting Writing test...', {
      test_id: route.params.testId,
      task1_words: getWordCount(0),
      task2_words: getWordCount(1)
    })
    
    const response = await axios.post(`/api/examination/submissions`, {
      test_id: route.params.testId,
      answers: {
        task1: answers.value[0],
        task2: answers.value[1]
      },
      time_taken: (testData.value.timeLimit * 60) - timeRemaining.value
    })

    const submissionId = response.data.data.submission_id
    
    console.log('✅ Writing test submitted successfully:', submissionId)

    Swal.fire({
      icon: 'success',
      title: 'Submitted!',
      text: 'Your answers have been submitted successfully.',
      timer: 1500,
      showConfirmButton: false
    })

    setTimeout(() => {
      router.push({
        name: 'examination.ielts-practice.result',
        params: { submissionId: submissionId }
      })
    }, 1500)
  } catch (error) {
    console.error('❌ Failed to submit test:', error)
    isSubmitting.value = false // Reset flag on error
    Swal.fire({
      icon: 'error',
      title: 'Submission Failed',
      text: 'Please try again later',
      confirmButtonColor: '#ea580c'
    })
  }
}

onMounted(() => {
  document.addEventListener('click', (e) => {
    if (showSelectionPopup.value && !e.target.closest('.selection-popup')) {
      const selection = window.getSelection()
      if (selection.toString().trim() === '') {
        showSelectionPopup.value = false
      }
    }
  })
  
  document.addEventListener('mousedown', (e) => {
    if (showSelectionPopup.value && !e.target.closest('.selection-popup')) {
      setTimeout(() => {
        const selection = window.getSelection()
        if (selection.toString().trim() === '') {
          showSelectionPopup.value = false
        }
      }, 50)
    }
  })
})
</script>

<style scoped>
textarea {
  font-family: 'Georgia', serif;
  line-height: 1.8;
}
</style>
