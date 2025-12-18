<template>
  <div class="ielts-practice-index p-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ t('examination.ielts.title') }}</h1>
          <p class="text-gray-600">{{ t('examination.ielts.description') }}</p>
        </div>
        <button
          v-if="canManagePracticeTests"
          @click="goToManagement"
          class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition-all flex items-center gap-2 shadow-lg"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          {{ t('examination.practiceTest.management') }}
        </button>
      </div>
    </div>

    <!-- Practice Tests List -->
    <div class="space-y-6">
      <div
        v-for="(practiceSet, index) in practiceSets"
        :key="index"
        class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6"
      >
        <!-- Test Header -->
        <div class="mb-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold text-gray-800">{{ practiceSet.title }}</h2>
              <p v-if="practiceSet.description" class="text-gray-600 text-sm mt-1">{{ practiceSet.description }}</p>
            </div>
            <span
              v-if="practiceSet.difficulty"
              :class="{
                'bg-green-100 text-green-700': practiceSet.difficulty === 'beginner',
                'bg-yellow-100 text-yellow-700': practiceSet.difficulty === 'intermediate',
                'bg-red-100 text-red-700': practiceSet.difficulty === 'advanced'
              }"
              class="px-3 py-1 rounded-full text-xs font-semibold"
            >
              {{ practiceSet.difficulty }}
            </span>
          </div>
        </div>

        <!-- 4 Skills Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <!-- Listening -->
          <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 rounded-xl p-5 border border-cyan-200 hover:shadow-lg transition-all">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-12 h-12 bg-cyan-500 rounded-lg flex items-center justify-center shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="font-semibold text-gray-800">Listening</h3>
                <p class="text-xs text-cyan-600">4 parts • 30 min</p>
              </div>
            </div>
            <button
              v-if="practiceSet.listening"
              @click="startTest(practiceSet.listening.id, 'listening')"
              class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" />
              </svg>
              Làm bài
            </button>
            <div v-else class="w-full py-2.5 text-center text-gray-400 text-sm bg-white/50 rounded-lg border border-gray-200">Chưa có bài</div>
          </div>

          <!-- Reading -->
          <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200 hover:shadow-lg transition-all">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="font-semibold text-gray-800">Reading</h3>
                <p class="text-xs text-green-600">3 passages • 60 min</p>
              </div>
            </div>
            <button
              v-if="practiceSet.reading"
              @click="startTest(practiceSet.reading.id, 'reading')"
              class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" />
              </svg>
              Làm bài
            </button>
            <div v-else class="w-full py-2.5 text-center text-gray-400 text-sm bg-white/50 rounded-lg border border-gray-200">Chưa có bài</div>
          </div>

          <!-- Writing -->
          <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200 hover:shadow-lg transition-all">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="font-semibold text-gray-800">Writing</h3>
                <p class="text-xs text-orange-600">2 tasks • 60 min</p>
              </div>
            </div>
            <button
              v-if="practiceSet.writing"
              @click="startTest(practiceSet.writing.id, 'writing')"
              class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" />
              </svg>
              Làm bài
            </button>
            <div v-else class="w-full py-2.5 text-center text-gray-400 text-sm bg-white/50 rounded-lg border border-gray-200">Chưa có bài</div>
          </div>

          <!-- Speaking -->
          <div class="bg-gradient-to-br from-rose-50 to-rose-100 rounded-xl p-5 border border-rose-200 hover:shadow-lg transition-all">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-12 h-12 bg-rose-500 rounded-lg flex items-center justify-center shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="font-semibold text-gray-800">Speaking</h3>
                <p class="text-xs text-rose-600">3 parts • 15 min</p>
              </div>
            </div>
            <button
              v-if="practiceSet.speaking"
              @click="startTest(practiceSet.speaking.id, 'speaking')"
              class="w-full bg-rose-500 hover:bg-rose-600 text-white font-medium py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" />
              </svg>
              Làm bài
            </button>
            <div v-else class="w-full py-2.5 text-center text-gray-400 text-sm bg-white/50 rounded-lg border border-gray-200">Chưa có bài</div>
          </div>
        </div>

        <!-- Full Test Section -->
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-xl p-6 text-white relative overflow-hidden shadow-xl">
          <div class="absolute top-0 right-0 opacity-10 pointer-events-none">
            <span class="text-8xl font-bold">IELTS</span>
          </div>
          <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -mb-24 -ml-24"></div>
          <div class="relative z-10">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl shadow-lg">
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-2xl font-bold mb-1">Full Test<sup class="text-xs ml-1">®</sup></h3>
                  <div class="flex items-center gap-4 text-sm opacity-90">
                    <span class="flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      ~165 minutes
                    </span>
                    <span class="flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Listening + Reading + Writing
                    </span>
                  </div>
                </div>
              </div>
              <button
                @click="startFullTest(practiceSet.id)"
                :disabled="!hasFullTest(practiceSet)"
                :class="hasFullTest(practiceSet) ? 'bg-white hover:bg-gray-50 text-blue-600' : 'bg-white/20 text-white/50 cursor-not-allowed'"
                class="font-semibold py-3 px-8 rounded-xl transition-all flex items-center gap-2 shadow-lg"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" />
                </svg>
                {{ hasFullTest(practiceSet) ? 'Start' : 'Coming soon' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from '@/composables/useI18n'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()
const practiceSets = ref([])
const loading = ref(false)

// Check if user can manage practice tests
const canManagePracticeTests = computed(() => {
  return authStore.isSuperAdmin ||
         authStore.hasPermission('manage-practice-tests') ||
         authStore.hasPermission('examination-manage') ||
         authStore.hasRole('admin') ||
         authStore.hasRole('teacher')
})

onMounted(async () => {
  await loadPracticeTests()
})

async function loadPracticeTests() {
  loading.value = true
  try {
    // Load practice tests from the new API
    const response = await axios.get('/api/examination/practice-tests', {
      params: {
        is_active: 1,
        per_page: 100 // Load up to 100 practice tests
      }
    })

    if (response.data.success) {
      // Transform API response to match template structure
      practiceSets.value = response.data.data.data.map(practiceTest => ({
        id: practiceTest.id,
        title: practiceTest.title,
        description: practiceTest.description,
        difficulty: practiceTest.difficulty,
        listening: practiceTest.listening_test || null,
        reading: practiceTest.reading_test || null,
        writing: practiceTest.writing_test || null,
        speaking: practiceTest.speaking_test || null,
        progress: 0 // Calculate based on completed tests
      }))

      console.log('📊 Practice tests loaded:', practiceSets.value)
    } else {
      console.error('Failed to load practice tests:', response.data.message)
    }
  } catch (error) {
    console.error('Failed to load practice tests:', error)
    practiceSets.value = []
  } finally {
    loading.value = false
  }
}

function startTest(testId, skill) {
  console.log('🚀 Starting test:', { testId, skill })
  
  if (!testId) {
    console.error('❌ testId is undefined!')
    alert('Test ID not found. Please try again.')
    return
  }
  
  // Route to specific test page based on skill
  const routeNames = {
    listening: 'examination.ielts-practice.listening',
    reading: 'examination.ielts-practice.reading',
    writing: 'examination.ielts-practice.writing',
    speaking: 'examination.ielts-practice.speaking'
  }
  
  router.push({
    name: routeNames[skill] || 'examination.ielts-practice.listening',
    params: { testId: String(testId) } // Ensure it's a string
  })
}

function hasFullTest(practiceSet) {
  return practiceSet.listening && practiceSet.reading && practiceSet.writing
}

function startFullTest(practiceTestId) {
  router.push({
    name: 'examination.ielts-practice.full',
    params: { setNumber: practiceTestId } // Keep param name for compatibility
  })
}

function goToManagement() {
  router.push({ name: 'examination.ielts-practice.management' })
}
</script>

<style scoped>
.ielts-practice-index {
  max-width: 1400px;
  margin: 0 auto;
}
</style>

