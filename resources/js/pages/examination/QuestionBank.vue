<template>
  <div class="question-bank">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">{{ t('examination.question_bank.title') }}</h1>
        <p class="text-gray-600">{{ t('examination.question_bank.description') }}</p>
      </div>
      <div class="flex items-center space-x-3">
        <button
          @click="showSubjectSettings = true"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          {{ t('examination.question_bank.subject_settings') }}
        </button>
        <button
          @click="openCreateModal"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          {{ t('examination.question_bank.create') }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
          <input
            v-model="filters.search"
            type="text"
            :placeholder="t('examination.question_bank.search_placeholder')"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @input="debouncedSearch"
          />
        </div>

        <!-- Subject filter -->
        <select v-model="filters.subject_id" @change="fetchQuestions" class="px-4 py-2 border rounded-lg">
          <option value="">{{ t('examination.question_bank.all_subjects') }}</option>
          <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
        </select>

        <!-- Skill filter -->
        <select v-model="filters.skill" @change="fetchQuestions" class="px-4 py-2 border rounded-lg">
          <option value="">{{ t('examination.common.all_skills') }}</option>
          <option value="listening">Listening</option>
          <option value="reading">Reading</option>
          <option value="writing">Writing</option>
          <option value="speaking">Speaking</option>
          <option value="grammar">Grammar</option>
          <option value="vocabulary">Vocabulary</option>
        </select>

        <!-- Type filter -->
        <select v-model="filters.type" @change="fetchQuestions" class="px-4 py-2 border rounded-lg">
          <option value="">{{ t('examination.question_bank.all_types') }}</option>
          <option value="multiple_choice">{{ t('examination.question_types.multiple_choice') }}</option>
          <option value="fill_blanks">{{ t('examination.question_types.fill_blanks') }}</option>
          <option value="matching">{{ t('examination.question_types.matching') }}</option>
          <option value="true_false">{{ t('examination.question_types.true_false') }}</option>
          <option value="essay">{{ t('examination.question_types.essay') }}</option>
          <option value="short_answer">{{ t('examination.question_types.short_answer') }}</option>
        </select>

        <!-- Difficulty filter -->
        <select v-model="filters.difficulty" @change="fetchQuestions" class="px-4 py-2 border rounded-lg">
          <option value="">{{ t('examination.question_bank.all_difficulties') }}</option>
          <option value="easy">{{ t('examination.difficulty.easy') }}</option>
          <option value="medium">{{ t('examination.difficulty.medium') }}</option>
          <option value="hard">{{ t('examination.difficulty.hard') }}</option>
          <option value="expert">{{ t('examination.difficulty.expert') }}</option>
        </select>

        <!-- Tag filter -->
        <select v-model="filters.tag_id" @change="fetchQuestions" class="px-4 py-2 border rounded-lg">
          <option value="">{{ t('examination.question_bank.all_tags') }}</option>
          <option v-for="tag in tags" :key="tag.id" :value="tag.id">{{ tag.name }}</option>
        </select>
      </div>
    </div>

    <!-- Questions Table -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
      <table class="w-full table-fixed">
        <thead class="bg-gray-50">
          <tr>
            <th class="w-1/3 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('examination.common.question') }}</th>
            <th class="w-24 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('examination.common.subject') }}</th>
            <th class="w-32 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('examination.question_types.label') }}</th>
            <th class="w-24 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('examination.common.skill') }}</th>
            <th class="w-20 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('examination.difficulty.label') }}</th>
            <th class="w-24 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('examination.common.status') }}</th>
            <th class="w-48 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('examination.common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
              <p class="mt-2">{{ t('examination.common.loading') }}</p>
            </td>
          </tr>
          <tr v-else-if="questions.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
              {{ t('examination.question_bank.no_questions') }}
            </td>
          </tr>
          <tr v-for="question in questions" :key="question.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="overflow-hidden">
                <p class="text-gray-800 font-medium truncate" :title="stripHtml(question.title)">{{ stripHtml(question.title) }}</p>
                <p v-if="question.subject_category" class="text-sm text-gray-500 truncate">{{ question.subject_category.name }}</p>
              </div>
            </td>
            <td class="px-4 py-3">
              <span v-if="question.subject" class="text-sm text-gray-600 truncate block">{{ question.subject.name }}</span>
              <span v-else class="text-sm text-gray-400">-</span>
            </td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 text-xs rounded-full whitespace-nowrap" :class="getTypeClass(question.type)">
                {{ getTypeName(question.type) }}
              </span>
            </td>
            <td class="px-4 py-3 capitalize text-gray-600 text-sm truncate">{{ question.skill || '-' }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 text-xs rounded-full whitespace-nowrap" :class="getDifficultyClass(question.difficulty)">
                {{ getDifficultyName(question.difficulty) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                class="px-2 py-1 text-xs rounded-full whitespace-nowrap"
                :class="question.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              >
                {{ question.status === 'active' ? t('examination.status.active') : t('examination.status.draft') }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end space-x-1">
                <button @click="practiceQuestion(question)" class="p-1.5 text-purple-600 hover:bg-purple-50 rounded" :title="t('examination.common.practice')">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </button>
                <button @click="editQuestion(question)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" :title="t('examination.common.edit')">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button @click="duplicateQuestion(question)" class="p-1.5 text-gray-600 hover:bg-gray-100 rounded" :title="t('examination.common.duplicate')">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                </button>
                <button @click="deleteQuestion(question)" class="p-1.5 text-red-600 hover:bg-red-50 rounded" :title="t('examination.common.delete')">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="px-4 py-3 border-t flex items-center justify-between">
        <p class="text-sm text-gray-600">
          {{ t('examination.common.showing') }} {{ questions.length }} / {{ pagination.total }} {{ t('examination.common.questions') }}
        </p>
        <div class="flex space-x-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            {{ t('examination.common.previous') }}
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            {{ t('examination.common.next') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Question Editor Modal -->
    <QuestionEditor
      v-if="showEditor"
      :question="selectedQuestion"
      @close="closeEditor"
      @saved="onQuestionSaved"
    />

    <!-- Subject Settings Modal -->
    <ExamSubjectSettings
      v-if="showSubjectSettings"
      @close="showSubjectSettings = false"
    />

    <!-- Practice Modal với QuestionRenderer thật -->
    <div v-if="showPracticeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="bg-white border-b px-6 py-4 flex justify-between items-center">
          <div>
            <h3 class="text-xl font-bold text-gray-800">{{ t('examination.common.practice') }}</h3>
            <p class="text-sm text-gray-500 mt-1">Chế độ làm thử - Giao diện giống thực tế</p>
          </div>
          <button @click="closePractice" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Question info -->
        <div v-if="selectedQuestion" class="border-b px-6 py-3 bg-gray-50">
          <div class="flex items-center justify-between text-sm">
            <div class="flex items-center space-x-4">
              <span class="px-2 py-1 rounded-full text-xs" :class="getTypeClass(selectedQuestion.type)">
                {{ getTypeName(selectedQuestion.type) }}
              </span>
              <span class="px-2 py-1 rounded-full text-xs" :class="getDifficultyClass(selectedQuestion.difficulty)">
                {{ getDifficultyName(selectedQuestion.difficulty) }}
              </span>
              <span v-if="selectedQuestion.skill" class="text-gray-600 capitalize">
                {{ selectedQuestion.skill }}
              </span>
            </div>
            <div v-if="selectedQuestion.points" class="font-medium text-blue-600">
              {{ selectedQuestion.points }} điểm
            </div>
          </div>
        </div>

        <!-- Question content - scrollable -->
        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="selectedQuestion" class="max-w-3xl mx-auto">
            <!-- Sử dụng QuestionRenderer component thật -->
            <QuestionRenderer
              :question="selectedQuestion"
              :questionNumber="1"
              v-model="practiceAnswer"
              :disabled="showPracticeResult"
              :showResult="showPracticeResult"
            />
          </div>
        </div>

        <!-- Actions footer -->
        <div class="bg-gray-50 px-6 py-4 border-t flex justify-between items-center">
          <button
            v-if="!showPracticeResult"
            @click="resetPractice"
            class="px-4 py-2 text-gray-600 hover:text-gray-800"
          >
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Làm lại
          </button>
          <div v-else class="text-sm text-gray-600">
            Đây là giao diện làm bài thực tế
          </div>

          <div class="flex space-x-3">
            <button
              v-if="!showPracticeResult"
              @click="checkPracticeAnswer"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Kiểm tra đáp án
            </button>
            <button
              v-else
              @click="resetPractice"
              class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
            >
              Làm lại
            </button>
            <button @click="closePractice" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100">
              {{ t('examination.common.close') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuestionStore } from '@/stores/examination'
import { useI18n } from '@/composables/useI18n'
import QuestionEditor from '@/components/examination/questions/QuestionEditor.vue'
import QuestionRenderer from '@/components/examination/questions/QuestionRenderer.vue'
import ExamSubjectSettings from '@/components/examination/ExamSubjectSettings.vue'
import api from '@/api'

const { t } = useI18n()

const store = useQuestionStore()

const showEditor = ref(false)
const showSubjectSettings = ref(false)
const showPracticeModal = ref(false)
const selectedQuestion = ref(null)
const practiceAnswer = ref(null)
const showPracticeResult = ref(false)
const subjects = ref([])
const tags = ref([])

const questions = computed(() => store.questions)
const loading = computed(() => store.loading)
const pagination = computed(() => store.pagination)
const filters = computed(() => store.filters)

let searchTimeout = null

onMounted(() => {
  fetchSubjects()
  fetchQuestions()
  fetchTags()
})

async function fetchSubjects() {
  try {
    const response = await api.get('/examination/subjects')
    subjects.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching subjects:', error)
  }
}

async function fetchTags() {
  try {
    const response = await api.get('/examination/question-tags')
    tags.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching tags:', error)
  }
}

async function fetchQuestions(page = 1) {
  await store.fetchQuestions({ page })
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchQuestions()
  }, 300)
}

function changePage(page) {
  fetchQuestions(page)
}

function openCreateModal() {
  selectedQuestion.value = null
  showEditor.value = true
}

function editQuestion(question) {
  selectedQuestion.value = question
  showEditor.value = true
}

async function duplicateQuestion(question) {
  if (confirm(t('examination.messages.confirm_duplicate'))) {
    await store.duplicateQuestion(question.id)
    fetchQuestions()
  }
}

async function deleteQuestion(question) {
  if (confirm(t('examination.messages.delete_question_confirm'))) {
    await store.deleteQuestion(question.id)
  }
}

function practiceQuestion(question) {
  selectedQuestion.value = question
  practiceAnswer.value = null
  showPracticeResult.value = false
  showPracticeModal.value = true
}

function checkPracticeAnswer() {
  showPracticeResult.value = true
}

function resetPractice() {
  practiceAnswer.value = null
  showPracticeResult.value = false
}

function closeEditor() {
  showEditor.value = false
  selectedQuestion.value = null
}

function closePractice() {
  showPracticeModal.value = false
  selectedQuestion.value = null
  practiceAnswer.value = null
  showPracticeResult.value = false
}

function onQuestionSaved() {
  closeEditor()
  fetchQuestions()
}

function stripHtml(html) {
  return html?.replace(/<[^>]*>/g, '') || ''
}

function getTypeName(type) {
  const typeKey = `examination.question_types.${type}`
  return t(typeKey) || type
}

function getTypeClass(type) {
  const classes = {
    multiple_choice: 'bg-blue-100 text-blue-800',
    fill_blanks: 'bg-purple-100 text-purple-800',
    matching: 'bg-green-100 text-green-800',
    true_false: 'bg-yellow-100 text-yellow-800',
    essay: 'bg-pink-100 text-pink-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

function getDifficultyName(difficulty) {
  const difficultyKey = `examination.difficulty.${difficulty}`
  const translation = t(difficultyKey)
  // Return first 2 characters for short display (Dễ -> Dễ, Medium -> Me, etc.)
  return translation.substring(0, 2) || difficulty
}

function getDifficultyClass(difficulty) {
  const classes = {
    easy: 'bg-green-100 text-green-800',
    medium: 'bg-yellow-100 text-yellow-800',
    hard: 'bg-orange-100 text-orange-800',
    expert: 'bg-red-100 text-red-800'
  }
  return classes[difficulty] || 'bg-gray-100 text-gray-800'
}
</script>
