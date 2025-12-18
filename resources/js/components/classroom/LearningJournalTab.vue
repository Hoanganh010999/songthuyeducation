<template>
  <div class="learning-journal-container">
    <!-- Student Panel (only for students) -->
    <div v-if="!isTeacher" class="student-panel mb-4">
      <div class="bg-blue-50 p-4 rounded-lg">
        <label class="block text-sm font-medium text-gray-700 mb-2">Chọn lớp</label>
        <select
          v-model="selectedClassId"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="loadJournals"
        >
          <option value="">-- Chọn lớp --</option>
          <option
            v-for="cls in availableClasses"
            :key="cls.id"
            :value="cls.id"
          >
            {{ cls.code }} - {{ cls.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Teacher Panel (only for teachers/admins) -->
    <div v-if="isTeacher" class="teacher-panel mb-4">
      <div class="bg-blue-50 p-4 rounded-lg">
        <div class="flex items-center gap-4">
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Chọn lớp</label>
            <select
              v-model="selectedClassId"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              @change="loadStudentsForClass"
            >
              <option value="">-- Chọn lớp --</option>
              <option
                v-for="cls in availableClasses"
                :key="cls.id"
                :value="cls.id"
              >
                {{ cls.code }} - {{ cls.name }}
              </option>
            </select>
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Chọn học viên</label>
            <select
              v-model="selectedStudentId"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              :disabled="!selectedClassId"
              @change="loadStudentJournals"
            >
              <option value="">-- Chọn học viên --</option>
              <option
                v-for="student in classStudents"
                :key="student.id"
                :value="student.user_id"
              >
                {{ student.user?.name || student.name }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="journal-content flex gap-4">
      <!-- Date Sidebar -->
      <div class="date-sidebar w-72 bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-semibold text-gray-900">Danh sách Journal</h3>
            <span class="text-xs text-gray-500">{{ journals.length }} bài</span>
          </div>

          <!-- Add New Journal Button (only for students) -->
          <button
            v-if="!isTeacher"
            @click="addNewDate"
            class="w-full mt-2 flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Viết Journal Mới
          </button>
        </div>

        <div class="p-4 space-y-2 max-h-[600px] overflow-y-auto">
          <!-- Journal List -->
          <button
            v-for="date in journalDates"
            :key="date"
            class="w-full text-left px-4 py-3 rounded-lg transition-colors border"
            :class="{
              'bg-indigo-50 border-indigo-300 text-indigo-900': selectedDate === date,
              'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300': selectedDate !== date
            }"
            @click="selectDate(date)"
          >
            <div class="font-medium">{{ formatDate(date) }}</div>
            <div class="text-xs text-gray-500 mt-1">
              {{ getJournalStatus(date) }}
            </div>
          </button>

          <!-- Empty State -->
          <div v-if="journalDates.length === 0" class="text-center py-8 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <p class="text-sm">Chưa có journal nào</p>
            <p class="text-xs mt-1" v-if="!isTeacher">Nhấn nút "Viết Journal Mới" để bắt đầu</p>
          </div>
        </div>
      </div>

      <!-- Journal Editor/Viewer -->
      <div class="journal-editor flex-1 bg-white rounded-lg shadow p-6">
        <div v-if="selectedDate">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold">{{ formatDate(selectedDate) }}</h3>

            <!-- Teacher Actions -->
            <div v-if="isTeacher && currentJournal && currentJournal.status === 'submitted'" class="flex gap-2">
              <button
                @click="openGradingModal"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors font-semibold shadow-sm"
              >
                Chấm bài
              </button>
            </div>

            <!-- Student Actions -->
            <div v-if="!isTeacher" class="flex gap-2">
              <button
                v-if="canEdit"
                @click="saveJournal"
                :disabled="saving"
                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors disabled:opacity-50"
              >
                {{ saving ? 'Đang lưu...' : 'Lưu' }}
              </button>
              <button
                v-if="canEdit && currentJournal?.status === 'draft'"
                @click="submitJournal"
                :disabled="!journalContent || journalContent.trim() === ''"
                class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition-colors disabled:opacity-50"
              >
                Nộp bài
              </button>
              <button
                v-if="canEdit && currentJournal?.id"
                @click="deleteJournal"
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
              >
                Xóa
              </button>
            </div>
          </div>

          <!-- Status Badge -->
          <div v-if="currentJournal" class="mb-4">
            <span
              class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
              :class="{
                'bg-gray-100 text-gray-800': currentJournal.status === 'draft',
                'bg-blue-100 text-blue-800': currentJournal.status === 'submitted',
                'bg-green-100 text-green-800': currentJournal.status === 'graded'
              }"
            >
              {{ getStatusLabel(currentJournal.status) }}
            </span>
            <span v-if="currentJournal.score !== null" class="ml-2 text-lg font-semibold text-green-600">
              Điểm: {{ Math.round(currentJournal.score) }}/100
            </span>
          </div>

          <!-- Journal Content -->
          <div class="journal-editor-content">
            <!-- Show WritingGradingEditor for graded journals (with annotations) -->
            <WritingGradingEditor
              v-if="currentJournal?.status === 'graded'"
              :content="currentJournal.content"
              :initial-annotations="currentJournal.annotations || []"
              :editable="false"
              :show-feedback="false"
            />

            <!-- Show JournalEditor for editing (draft/submitted) -->
            <JournalEditor
              v-else
              v-model="journalContent"
              :disabled="!canEdit"
              :placeholder="canEdit ? 'Viết journal của bạn ở đây...' : ''"
            />
          </div>

          <!-- AI Feedback (if graded) -->
          <div v-if="currentJournal?.ai_feedback" class="mt-4 p-4 bg-purple-50 border border-purple-200 rounded-lg">
            <h4 class="font-semibold text-purple-900 mb-2">Nhận xét</h4>
            <p class="text-purple-800 whitespace-pre-wrap">{{ currentJournal.ai_feedback }}</p>
          </div>
        </div>

        <div v-else class="text-center py-16 text-gray-500">
          <p class="text-lg">{{ !isTeacher ? 'Chọn ngày để viết journal' : 'Chọn lớp và học viên để xem journal' }}</p>
        </div>
      </div>
    </div>

    <!-- Grading Modal -->
    <JournalGradingModal
      v-if="showGradingModal"
      :show="showGradingModal"
      :journal="currentJournal"
      :mode="'grading'"
      @close="closeGradingModal"
      @graded="handleJournalGraded"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import JournalGradingModal from './JournalGradingModal.vue'
import JournalEditor from './JournalEditor.vue'
import WritingGradingEditor from '@/components/grading/writing/WritingGradingEditor.vue'
import { format, parseISO } from 'date-fns'
import { vi } from 'date-fns/locale'

const props = defineProps({
  userRole: {
    type: String,
    default: 'student'
  }
})

const emit = defineEmits(['journal-updated'])

// State
const journals = ref([])
const selectedDate = ref(null)
const journalContent = ref('')
const currentJournal = ref(null)
const saving = ref(false)
const loading = ref(false)

// Common state
const isTeacher = computed(() => ['admin', 'super-admin', 'teacher'].includes(props.userRole))
const availableClasses = ref([])
const selectedClassId = ref('')

// Teacher-specific state
const selectedStudentId = ref('')
const classStudents = ref([])

// Grading modal
const showGradingModal = ref(false)

// Computed
const journalDates = computed(() => {
  return journals.value.map(j => j.journal_date).sort((a, b) => new Date(b) - new Date(a))
})

const canEdit = computed(() => {
  if (isTeacher.value) return false
  if (!currentJournal.value) return true
  return currentJournal.value.status !== 'graded'
})

// Methods
const formatDate = (dateStr) => {
  try {
    return format(parseISO(dateStr), 'dd/MM/yyyy (EEEE)', { locale: vi })
  } catch (e) {
    return dateStr
  }
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Bản nháp',
    submitted: 'Đã nộp',
    graded: 'Đã chấm'
  }
  return labels[status] || status
}

const getJournalStatus = (date) => {
  const journal = journals.value.find(j => j.journal_date === date)
  if (!journal) return 'Chưa viết'

  if (journal.status === 'graded') {
    return `Đã chấm - ${Math.round(journal.score)}/100`
  }
  return getStatusLabel(journal.status)
}

const loadAvailableClasses = async () => {
  try {
    const response = await axios.get('/api/course/my-classes')
    if (response.data.success) {
      availableClasses.value = response.data.data

      // Auto-select first class if available
      if (availableClasses.value.length > 0 && !selectedClassId.value) {
        selectedClassId.value = availableClasses.value[0].id

        // Load journals for student, or load students for teacher
        if (isTeacher.value) {
          await loadStudentsForClass()
        } else {
          await loadJournals()
        }
      }
    }
  } catch (error) {
    console.error('[LearningJournal] Error loading classes:', error)
  }
}

const loadJournals = async () => {
  if (!selectedClassId.value) return

  try {
    loading.value = true
    const response = await axios.get(`/api/course/learning-journals/class/${selectedClassId.value}/my-journals`)

    if (response.data.success) {
      journals.value = response.data.data

      // Auto-select today's date or most recent
      if (journals.value.length > 0) {
        const today = format(new Date(), 'yyyy-MM-dd')
        const todayJournal = journals.value.find(j => j.journal_date === today)
        selectedDate.value = todayJournal ? today : journals.value[0].journal_date
        await loadJournalForDate(selectedDate.value)
      }
    }
  } catch (error) {
    console.error('[LearningJournal] Error loading journals:', error)
  } finally {
    loading.value = false
  }
}

const loadStudentJournals = async () => {
  console.log('[LearningJournal] loadStudentJournals called', {
    classId: selectedClassId.value,
    studentId: selectedStudentId.value
  })

  if (!selectedClassId.value || !selectedStudentId.value) {
    console.log('[LearningJournal] Missing classId or studentId, returning')
    return
  }

  try {
    loading.value = true
    console.log('[LearningJournal] Calling getStudentJournals API:', {
      url: `/api/course/learning-journals/class/${selectedClassId.value}/student/${selectedStudentId.value}`
    })

    const response = await axios.get(
      `/api/course/learning-journals/class/${selectedClassId.value}/student/${selectedStudentId.value}`
    )

    console.log('[LearningJournal] getStudentJournals response:', response.data)

    if (response.data.success) {
      journals.value = response.data.data
      console.log('[LearningJournal] Loaded student journals:', {
        count: journals.value.length,
        journals: journals.value
      })

      if (journals.value.length > 0) {
        selectedDate.value = journals.value[0].journal_date
        await loadJournalForDate(selectedDate.value)
      } else {
        // Reset if no journals
        selectedDate.value = null
        journalContent.value = ''
        currentJournal.value = null
      }
    }
  } catch (error) {
    console.error('[LearningJournal] Error loading student journals:', error)
  } finally {
    loading.value = false
  }
}

const loadJournalForDate = async (date) => {
  if (!selectedClassId.value) return

  try {
    console.log('[LearningJournal] Loading journal for date:', date, 'class:', selectedClassId.value)

    // Normalize date to YYYY-MM-DD format for comparison
    const normalizeDate = (d) => {
      if (!d) return null
      // Extract YYYY-MM-DD from both "2025-12-13" and "2025-12-13T17:00:00.000000Z"
      return d.substring(0, 10)
    }

    // If teacher is viewing student journals, find from already loaded list
    if (isTeacher.value && selectedStudentId.value) {
      const targetDate = normalizeDate(date)
      const journal = journals.value.find(j => normalizeDate(j.journal_date) === targetDate)
      currentJournal.value = journal || null
      journalContent.value = currentJournal.value?.content || ''
      console.log('[LearningJournal] [Teacher View] Found journal from list:', {
        id: currentJournal.value?.id,
        date: currentJournal.value?.journal_date,
        status: currentJournal.value?.status,
        hasContent: !!currentJournal.value?.content,
        contentLength: currentJournal.value?.content?.length,
        searchDate: targetDate,
        allDates: journals.value.map(j => ({ date: j.journal_date, normalized: normalizeDate(j.journal_date) }))
      })
      console.log('[LearningJournal] [Teacher View] AFTER ASSIGNMENT:', {
        journalContentLength: journalContent.value?.length,
        journalContentPreview: journalContent.value?.substring(0, 100),
        currentJournalContent: currentJournal.value?.content?.substring(0, 100)
      })
      return
    }

    // Student view - call API
    const response = await axios.get(
      `/api/course/learning-journals/class/${selectedClassId.value}/date/${date}`
    )

    if (response.data.success) {
      currentJournal.value = response.data.data
      journalContent.value = currentJournal.value?.content || ''
      console.log('[LearningJournal] Loaded journal:', {
        id: currentJournal.value?.id,
        date: currentJournal.value?.journal_date,
        status: currentJournal.value?.status,
        hasContent: !!currentJournal.value?.content,
        contentLength: currentJournal.value?.content?.length
      })
    }
  } catch (error) {
    console.error('[LearningJournal] Error loading journal for date:', error)
  }
}

const selectDate = (date) => {
  console.log('[LearningJournal] Selecting date:', date)
  selectedDate.value = date
  loadJournalForDate(date)
}

const addNewDate = () => {
  const today = format(new Date(), 'yyyy-MM-dd')

  // Check if today already exists
  if (journalDates.value.includes(today)) {
    selectedDate.value = today
    loadJournalForDate(today)
  } else {
    selectedDate.value = today
    currentJournal.value = null
    journalContent.value = ''
  }
}

const saveJournal = async () => {
  if (!selectedDate.value || !selectedClassId.value) return

  try {
    saving.value = true
    console.log('[LearningJournal] Saving journal:', {
      date: selectedDate.value,
      classId: selectedClassId.value,
      contentLength: journalContent.value?.length
    })

    const response = await axios.post(
      `/api/course/learning-journals/class/${selectedClassId.value}/save`,
      {
        journal_date: selectedDate.value,
        content: journalContent.value
      }
    )

    if (response.data.success) {
      console.log('[LearningJournal] Saved successfully:', response.data.data)

      // Save the date we want to stay on
      const savedDate = response.data.data.journal_date

      // Set currentJournal from response (has ID)
      currentJournal.value = response.data.data

      // Reload journals list WITHOUT auto-selecting
      const currentLoading = loading.value
      loading.value = true
      const journalsResponse = await axios.get(`/api/course/learning-journals/class/${selectedClassId.value}/my-journals`)
      if (journalsResponse.data.success) {
        journals.value = journalsResponse.data.data
      }
      loading.value = currentLoading

      // Keep the selected date and reload that specific journal
      selectedDate.value = savedDate
      await loadJournalForDate(savedDate)

      emit('journal-updated', currentJournal.value)
    }
  } catch (error) {
    console.error('[LearningJournal] Error saving journal:', error)
    if (error.response?.status === 403) {
      alert('Không thể chỉnh sửa journal đã được chấm điểm')
    } else {
      alert('Lỗi khi lưu journal: ' + (error.response?.data?.message || error.message))
    }
  } finally {
    saving.value = false
  }
}

const submitJournal = async () => {
  await saveJournal()
  // The status will be 'submitted' after save
}

const deleteJournal = async () => {
  if (!currentJournal.value?.id) return

  if (!confirm('Bạn có chắc muốn xóa journal này?')) {
    return
  }

  try {
    const response = await axios.delete(
      `/api/course/learning-journals/${currentJournal.value.id}`
    )

    if (response.data.success) {
      // Reload journals list
      await loadJournals()

      // Clear current journal
      selectedDate.value = null
      journalContent.value = ''
      currentJournal.value = null

      alert('Đã xóa journal thành công')
    }
  } catch (error) {
    console.error('[LearningJournal] Error deleting journal:', error)
    alert('Lỗi khi xóa journal: ' + (error.response?.data?.message || error.message))
  }
}

// Teacher methods
const loadStudentsForClass = async () => {
  if (!selectedClassId.value) return

  try {
    // Reset student selection and journals
    selectedStudentId.value = ''
    journals.value = []
    selectedDate.value = null
    currentJournal.value = null
    journalContent.value = ''

    // Load students in class
    const response = await axios.get(`/api/quality/classes/${selectedClassId.value}/students`)
    if (response.data.success) {
      classStudents.value = response.data.data
    }
  } catch (error) {
    console.error('[LearningJournal] Error loading students:', error)
  }
}

const openGradingModal = () => {
  showGradingModal.value = true
}

const closeGradingModal = () => {
  showGradingModal.value = false
}

const handleJournalGraded = (gradedJournal) => {
  currentJournal.value = gradedJournal

  // Update in journals list
  const existingIndex = journals.value.findIndex(j => j.id === gradedJournal.id)
  if (existingIndex >= 0) {
    journals.value[existingIndex] = gradedJournal
  }

  closeGradingModal()
  emit('journal-updated', gradedJournal)
}

// Lifecycle
onMounted(() => {
  loadAvailableClasses()
})
</script>

<style scoped>
.learning-journal-container {
  @apply w-full;
}

.date-sidebar {
  min-height: 600px;
  max-height: 800px;
  overflow-y: auto;
}

.journal-editor-content {
  min-height: 400px;
}
</style>
