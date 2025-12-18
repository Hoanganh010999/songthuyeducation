<template>
  <div class="practice-test-management p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <div class="flex items-center gap-3">
          <button
            @click="$router.back()"
            class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <h1 class="text-3xl font-bold text-gray-800">{{ t('examination.practiceTest.management') }}</h1>
        </div>
        <p class="text-gray-600 mt-1 ml-14">{{ t('examination.practiceTest.managementDescription') }}</p>
      </div>
      <button
        @click="openCreateModal"
        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors flex items-center gap-2 shadow-lg"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        {{ t('examination.practiceTest.createNew') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="t('common.search')"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <select
            v-model="filters.difficulty"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ t('examination.practiceTest.allDifficulties') }}</option>
            <option value="beginner">{{ t('examination.practiceTest.difficulty.beginner') }}</option>
            <option value="intermediate">{{ t('examination.practiceTest.difficulty.intermediate') }}</option>
            <option value="advanced">{{ t('examination.practiceTest.difficulty.advanced') }}</option>
          </select>
        </div>
        <div>
          <select
            v-model="filters.isActive"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ t('examination.practiceTest.allStatus') }}</option>
            <option value="true">{{ t('common.active') }}</option>
            <option value="false">{{ t('common.inactive') }}</option>
          </select>
        </div>
        <div>
          <button
            @click="loadPracticeTests"
            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors"
          >
            {{ t('common.filter') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Practice Tests Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-gray-600">{{ t('common.loading') }}...</p>
      </div>

      <div v-else-if="practiceTests.length === 0" class="p-12 text-center">
        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="mt-4 text-gray-600">{{ t('examination.practiceTest.noPracticeTests') }}</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('examination.practiceTest.title') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('examination.practiceTest.tests') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('examination.practiceTest.difficulty.label') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('common.status') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('common.order') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('common.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="practiceTest in practiceTests" :key="practiceTest.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ practiceTest.title }}</div>
              <div class="text-sm text-gray-500">{{ practiceTest.description }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="flex flex-wrap gap-1">
                <span
                  v-if="practiceTest.reading_test"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
                  :title="practiceTest.reading_test?.title"
                >
                  {{ t('examination.practiceTest.skills.reading') }}
                </span>
                <span
                  v-if="practiceTest.writing_test"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800"
                  :title="practiceTest.writing_test?.title"
                >
                  {{ t('examination.practiceTest.skills.writing') }}
                </span>
                <span
                  v-if="practiceTest.listening_test"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-100 text-cyan-800"
                  :title="practiceTest.listening_test?.title"
                >
                  {{ t('examination.practiceTest.skills.listening') }}
                </span>
                <span
                  v-if="practiceTest.speaking_test"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800"
                  :title="practiceTest.speaking_test?.title"
                >
                  {{ t('examination.practiceTest.skills.speaking') }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="{
                  'bg-green-100 text-green-800': practiceTest.difficulty === 'beginner',
                  'bg-yellow-100 text-yellow-800': practiceTest.difficulty === 'intermediate',
                  'bg-red-100 text-red-800': practiceTest.difficulty === 'advanced'
                }"
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
              >
                {{ t(`examination.practiceTest.difficulty.${practiceTest.difficulty}`) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="practiceTest.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
              >
                {{ practiceTest.is_active ? t('common.active') : t('common.inactive') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ practiceTest.order }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="openEditModal(practiceTest)"
                  class="text-blue-600 hover:text-blue-900"
                  :title="t('common.edit')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  @click="duplicatePracticeTest(practiceTest.id)"
                  class="text-green-600 hover:text-green-900"
                  :title="t('common.duplicate')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                </button>
                <button
                  @click="confirmDelete(practiceTest)"
                  class="text-red-600 hover:text-red-900"
                  :title="t('common.delete')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-800">
              {{ editingPracticeTest ? t('examination.practiceTest.edit') : t('examination.practiceTest.createNew') }}
            </h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="savePracticeTest" class="p-6">
            <div class="space-y-6">
              <!-- Title -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('examination.practiceTest.title') }} <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.title"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :placeholder="t('examination.practiceTest.titlePlaceholder')"
                />
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('examination.practiceTest.description') }}
                </label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :placeholder="t('examination.practiceTest.descriptionPlaceholder')"
                ></textarea>
              </div>

              <!-- Test Selection -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Reading Test -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ t('examination.practiceTest.skills.reading') }}
                  </label>
                  <select
                    v-model="form.reading_test_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option :value="null">{{ t('examination.practiceTest.selectTest') }}</option>
                    <option v-for="test in availableTests.reading" :key="test.id" :value="test.id">
                      {{ test.title }}
                    </option>
                  </select>
                </div>

                <!-- Writing Test -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ t('examination.practiceTest.skills.writing') }}
                  </label>
                  <select
                    v-model="form.writing_test_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option :value="null">{{ t('examination.practiceTest.selectTest') }}</option>
                    <option v-for="test in availableTests.writing" :key="test.id" :value="test.id">
                      {{ test.title }}
                    </option>
                  </select>
                </div>

                <!-- Listening Test -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ t('examination.practiceTest.skills.listening') }}
                  </label>
                  <select
                    v-model="form.listening_test_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option :value="null">{{ t('examination.practiceTest.selectTest') }}</option>
                    <option v-for="test in availableTests.listening" :key="test.id" :value="test.id">
                      {{ test.title }}
                    </option>
                  </select>
                </div>

                <!-- Speaking Test -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ t('examination.practiceTest.skills.speaking') }}
                  </label>
                  <select
                    v-model="form.speaking_test_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option :value="null">{{ t('examination.practiceTest.selectTest') }}</option>
                    <option v-for="test in availableTests.speaking" :key="test.id" :value="test.id">
                      {{ test.title }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Difficulty & Status -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ t('examination.practiceTest.difficulty.label') }} <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.difficulty"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="beginner">{{ t('examination.practiceTest.difficulty.beginner') }}</option>
                    <option value="intermediate">{{ t('examination.practiceTest.difficulty.intermediate') }}</option>
                    <option value="advanced">{{ t('examination.practiceTest.difficulty.advanced') }}</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ t('common.order') }}
                  </label>
                  <input
                    v-model.number="form.order"
                    type="number"
                    min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <div class="flex items-end">
                  <label class="flex items-center space-x-2 cursor-pointer">
                    <input
                      v-model="form.is_active"
                      type="checkbox"
                      class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <span class="text-sm font-medium text-gray-700">{{ t('common.active') }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
              <button
                type="button"
                @click="closeModal"
                class="px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-semibold rounded-lg transition-colors flex items-center gap-2"
              >
                <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ saving ? t('common.saving') : t('common.save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()
const router = useRouter()

const loading = ref(false)
const saving = ref(false)
const showModal = ref(false)
const practiceTests = ref([])
const editingPracticeTest = ref(null)

const filters = reactive({
  search: '',
  difficulty: '',
  isActive: ''
})

const form = reactive({
  title: '',
  description: '',
  reading_test_id: null,
  writing_test_id: null,
  listening_test_id: null,
  speaking_test_id: null,
  difficulty: 'intermediate',
  is_active: true,
  order: 0
})

const availableTests = reactive({
  reading: [],
  writing: [],
  listening: [],
  speaking: []
})

onMounted(async () => {
  await Promise.all([
    loadPracticeTests(),
    loadAvailableTests()
  ])
})

async function loadPracticeTests() {
  loading.value = true
  try {
    const response = await axios.get('/api/examination/practice-tests', {
      params: {
        search: filters.search || undefined,
        difficulty: filters.difficulty || undefined,
        is_active: filters.isActive || undefined,
        paginate: false
      }
    })
    practiceTests.value = response.data.data
  } catch (error) {
    console.error('Failed to load practice tests:', error)
    Swal.fire('Lỗi', t('examination.practiceTest.errors.loadFailed'), 'error')
  } finally {
    loading.value = false
  }
}

async function loadAvailableTests() {
  try {
    const response = await axios.get('/api/examination/practice-tests/available-tests')
    const data = response.data.data

    availableTests.reading = data.reading || []
    availableTests.writing = data.writing || []
    availableTests.listening = data.listening || []
    availableTests.speaking = data.speaking || []
  } catch (error) {
    console.error('Failed to load available tests:', error)
  }
}

function openCreateModal() {
  editingPracticeTest.value = null
  Object.assign(form, {
    title: '',
    description: '',
    reading_test_id: null,
    writing_test_id: null,
    listening_test_id: null,
    speaking_test_id: null,
    difficulty: 'intermediate',
    is_active: true,
    order: 0
  })
  showModal.value = true
}

function openEditModal(practiceTest) {
  editingPracticeTest.value = practiceTest
  Object.assign(form, {
    title: practiceTest.title,
    description: practiceTest.description || '',
    reading_test_id: practiceTest.reading_test_id,
    writing_test_id: practiceTest.writing_test_id,
    listening_test_id: practiceTest.listening_test_id,
    speaking_test_id: practiceTest.speaking_test_id,
    difficulty: practiceTest.difficulty,
    is_active: practiceTest.is_active,
    order: practiceTest.order || 0
  })
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingPracticeTest.value = null
}

async function savePracticeTest() {
  saving.value = true
  try {
    if (editingPracticeTest.value) {
      await axios.put(`/api/examination/practice-tests/${editingPracticeTest.value.id}`, form)
    } else {
      await axios.post('/api/examination/practice-tests', form)
    }

    await loadPracticeTests()
    closeModal()
  } catch (error) {
    console.error('Failed to save practice test:', error)
    Swal.fire('Lỗi', t('examination.practiceTest.errors.saveFailed'), 'error')
  } finally {
    saving.value = false
  }
}

async function duplicatePracticeTest(id) {
  if (!confirm(t('examination.practiceTest.confirmDuplicate'))) return

  try {
    await axios.post(`/api/examination/practice-tests/${id}/duplicate`)
    await loadPracticeTests()
  } catch (error) {
    console.error('Failed to duplicate practice test:', error)
    Swal.fire('Lỗi', t('examination.practiceTest.errors.duplicateFailed'), 'error')
  }
}

function confirmDelete(practiceTest) {
  if (!confirm(t('examination.practiceTest.confirmDelete', { title: practiceTest.title }))) return
  deletePracticeTest(practiceTest.id)
}

async function deletePracticeTest(id) {
  try {
    await axios.delete(`/api/examination/practice-tests/${id}`)
    await loadPracticeTests()
  } catch (error) {
    console.error('Failed to delete practice test:', error)
    Swal.fire('Lỗi', t('examination.practiceTest.errors.deleteFailed'), 'error')
  }
}
</script>

<style scoped>
.practice-test-management {
  max-width: 1600px;
  margin: 0 auto;
}
</style>
