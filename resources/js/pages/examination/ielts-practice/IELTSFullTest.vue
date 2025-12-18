<template>
  <div class="ielts-full-test min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b sticky top-0 z-50 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <!-- Progress -->
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
              <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold">{{ currentSkillIndex + 1 }}</span>
              </div>
              <div>
                <h1 class="font-semibold text-gray-800">{{ currentSkillInfo.title }}</h1>
                <p class="text-xs text-gray-500">{{ currentSkillInfo.subtitle }}</p>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="hidden md:flex items-center gap-2">
              <div
                v-for="(skill, idx) in activeSkills"
                :key="idx"
                :class="[
                  'flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition-all',
                  idx === currentSkillIndex ? skill.color + ' font-semibold shadow-sm' : 'bg-gray-100 text-gray-400',
                  idx < currentSkillIndex ? 'bg-green-100 text-green-700' : ''
                ]"
              >
                <svg v-if="idx < currentSkillIndex" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <component :is="skill.icon" v-else class="w-4 h-4" />
                <span>{{ skill.shortName }}</span>
              </div>
            </div>
          </div>

          <!-- Timer -->
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 text-red-600 font-semibold" v-if="timeRemaining < 300">
              <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ formattedTime }}</span>
            </div>
            <div class="flex items-center gap-2 text-blue-600 font-semibold" v-else>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ formattedTime }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Skill Component -->
    <div class="max-w-7xl mx-auto p-6">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Loading test...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="flex items-center justify-center py-20">
        <div class="text-center">
          <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-gray-600 mb-4">{{ error }}</p>
          <button @click="$router.back()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            Go Back
          </button>
        </div>
      </div>

      <!-- Test Component -->
      <component
        v-else-if="currentTestId"
        :is="currentSkillComponent"
        :test-id="currentTestId"
        :auto-submit="true"
        @submit="handleSkillComplete"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAntiCheat } from '@/composables/useAntiCheat'
import IELTSListeningTest from './IELTSListeningTest.vue'
import IELTSReadingTest from './IELTSReadingTest.vue'
import IELTSWritingTest from './IELTSWritingTest.vue'
import IELTSSpeakingTest from './IELTSSpeakingTest.vue'

const route = useRoute()
const router = useRouter()

// All possible skills configuration
const allSkills = {
  listening: {
    name: 'listening',
    shortName: 'Listening',
    title: 'IELTS Listening Test',
    subtitle: '4 parts • 30 minutes',
    color: 'bg-cyan-100 text-cyan-700',
    timeLimit: 30 * 60,
    component: IELTSListeningTest,
    icon: () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3' })
    ])
  },
  reading: {
    name: 'reading',
    shortName: 'Reading',
    title: 'IELTS Reading Test',
    subtitle: '3 passages • 60 minutes',
    color: 'bg-green-100 text-green-700',
    timeLimit: 60 * 60,
    component: IELTSReadingTest,
    icon: () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' })
    ])
  },
  writing: {
    name: 'writing',
    shortName: 'Writing',
    title: 'IELTS Writing Test',
    subtitle: '2 tasks • 60 minutes',
    color: 'bg-orange-100 text-orange-700',
    timeLimit: 60 * 60,
    component: IELTSWritingTest,
    icon: () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z' })
    ])
  },
  speaking: {
    name: 'speaking',
    shortName: 'Speaking',
    title: 'IELTS Speaking Test',
    subtitle: '3 parts • 15 minutes',
    color: 'bg-rose-100 text-rose-700',
    timeLimit: 15 * 60,
    component: IELTSSpeakingTest,
    icon: () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z' })
    ])
  }
}

const loading = ref(true)
const error = ref(null)
const activeSkills = ref([])
const currentSkillIndex = ref(0)
const testIds = ref({})
const timeRemaining = ref(0)
const timerInterval = ref(null)
const answers = ref({})
const practiceTest = ref(null)

const currentSkillInfo = computed(() => activeSkills.value[currentSkillIndex.value])
const currentTestId = computed(() => {
  if (!currentSkillInfo.value) return null
  return testIds.value[currentSkillInfo.value.name]
})
const currentSkillComponent = computed(() => {
  if (!currentSkillInfo.value) return null
  return currentSkillInfo.value.component
})

const formattedTime = computed(() => {
  const minutes = Math.floor(timeRemaining.value / 60)
  const seconds = timeRemaining.value % 60
  return `${minutes}:${seconds.toString().padStart(2, '0')}`
})

// Anti-cheat system
const antiCheat = useAntiCheat({
  onSubmit: submitAllTests,
  maxViolations: 3,
  enableFullscreen: true,
  enableCopyPaste: true,
  enableTabSwitch: true,
  logEndpoint: null, // TODO: Add endpoint if needed
})

onMounted(async () => {
  await loadFullTest()
  if (!error.value && activeSkills.value.length > 0) {
    // Initialize anti-cheat AFTER test is loaded
    // Only start timer if user confirms
    const confirmed = await antiCheat.initialize()
    if (confirmed) {
      startTimer()
    } else {
      // User cancelled - go back
      router.push({ name: 'examination.ielts-practice' })
    }
  }
})

onUnmounted(() => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
  }
  antiCheat.cleanup()
})

async function loadFullTest() {
  loading.value = true
  error.value = null

  try {
    const practiceTestId = parseInt(route.params.setNumber)

    console.log('🔍 Loading practice test:', practiceTestId)

    // Load practice test from API
    const response = await axios.get(`/api/examination/practice-tests/${practiceTestId}`)

    if (!response.data.success) {
      throw new Error(response.data.message || 'Failed to load practice test')
    }

    practiceTest.value = response.data.data
    console.log('✅ Practice test loaded:', practiceTest.value)

    // Build active skills list and test IDs based on available tests
    const skillOrder = ['listening', 'reading', 'writing', 'speaking']
    const skills = []
    const ids = {}

    for (const skillName of skillOrder) {
      const testKey = `${skillName}_test`
      const testIdKey = `${skillName}_test_id`

      if (practiceTest.value[testKey] && practiceTest.value[testIdKey]) {
        skills.push(allSkills[skillName])
        ids[skillName] = practiceTest.value[testIdKey]
        console.log(`✅ ${skillName}: test ID ${practiceTest.value[testIdKey]}`)
      } else {
        console.log(`⏭️  ${skillName}: skipped (no test)`)
      }
    }

    if (skills.length === 0) {
      throw new Error('This practice test has no active skills. Please configure at least one test.')
    }

    activeSkills.value = skills
    testIds.value = ids

    console.log('📊 Active skills:', activeSkills.value.map(s => s.name))
    console.log('🆔 Test IDs:', testIds.value)

    // Set initial timer
    timeRemaining.value = currentSkillInfo.value.timeLimit

  } catch (err) {
    console.error('❌ Failed to load full test:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load test. Please try again.'

    Swal.fire({
      icon: 'error',
      title: 'Cannot load test',
      text: error.value,
      confirmButtonColor: '#0891b2'
    })
  } finally {
    loading.value = false
  }
}

function startTimer() {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
  }

  timerInterval.value = setInterval(() => {
    if (timeRemaining.value > 0) {
      timeRemaining.value--
    } else {
      // Auto-submit current skill
      autoSubmitSkill()
    }
  }, 1000)
}

async function autoSubmitSkill() {
  clearInterval(timerInterval.value)

  Swal.fire({
    icon: 'info',
    title: 'Time is up!',
    text: `${currentSkillInfo.value.title} has ended. Moving to next section...`,
    timer: 2000,
    showConfirmButton: false
  })

  await new Promise(resolve => setTimeout(resolve, 2000))

  moveToNextSkill()
}

function handleSkillComplete(skillAnswers) {
  // Save answers
  answers.value[currentSkillInfo.value.name] = skillAnswers

  Swal.fire({
    icon: 'success',
    title: 'Section completed!',
    text: 'Moving to next section...',
    timer: 1500,
    showConfirmButton: false
  })

  setTimeout(() => {
    moveToNextSkill()
  }, 1500)
}

function moveToNextSkill() {
  if (currentSkillIndex.value < activeSkills.value.length - 1) {
    currentSkillIndex.value++
    timeRemaining.value = currentSkillInfo.value.timeLimit
    startTimer()
  } else {
    // All skills completed
    completeFullTest()
  }
}

async function submitAllTests() {
  clearInterval(timerInterval.value)
  
  // Get anti-cheat summary
  const antiCheatSummary = antiCheat.getSummary()
  
  console.log('🚨 Anti-Cheat Summary:', antiCheatSummary)
  
  // TODO: Send antiCheatSummary to backend if needed
  // Example: Include in submission data
  // await axios.post('/api/examination/submissions/log-activities', {
  //   submission_id: currentSubmissionId,
  //   activities: antiCheatSummary
  // })
  
  await Swal.fire({
    icon: 'warning',
    title: 'Bài thi đã được nộp',
    html: `
      <p>Do vi phạm quy định, bài thi của bạn đã được nộp tự động.</p>
      ${antiCheatSummary.totalViolations > 0 ? `<p class="text-red-600 mt-2">Tổng vi phạm: <strong>${antiCheatSummary.totalViolations}</strong></p>` : ''}
    `,
    confirmButtonText: 'Đồng ý',
  })
  
  // Cleanup and exit
  antiCheat.cleanup()
  router.push({ name: 'examination.ielts-practice' })
}

async function completeFullTest() {
  clearInterval(timerInterval.value)
  
  // Get anti-cheat summary
  const antiCheatSummary = antiCheat.getSummary()
  
  console.log('✅ Test completed. Anti-Cheat Summary:', antiCheatSummary)

  await Swal.fire({
    icon: 'success',
    title: 'Full Test Completed!',
    html: `
      <p>Congratulations! You have completed all sections.</p>
      ${antiCheatSummary.totalViolations > 0 ? `<p class="text-yellow-600 text-sm mt-2">⚠️ Ghi nhận ${antiCheatSummary.totalViolations} vi phạm trong quá trình làm bài</p>` : ''}
    `,
    confirmButtonColor: '#0891b2'
  })
  
  // Cleanup anti-cheat
  antiCheat.cleanup()

  // Submit all answers and go to results
  router.push({
    name: 'examination.ielts-practice'
  })
}
</script>

<style scoped>
/* Add any custom styles here */
</style>
