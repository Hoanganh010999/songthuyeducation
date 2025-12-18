<template>
  <div class="ielts-test-builder">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <router-link :to="{ name: 'examination.tests', query: { type: 'ielts' } }" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </router-link>
        <div>
          <h1 class="text-2xl font-bold text-gray-800">{{ isEditing ? 'Chỉnh sửa đề IELTS' : 'Tạo đề IELTS mới' }}</h1>
          <p class="text-gray-600">Tạo bài thi IELTS hoàn chỉnh với các phần Listening, Reading, Writing, Speaking</p>
        </div>
      </div>
      <div class="flex space-x-3">
        <button @click="saveDraft" :disabled="saving" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
          Lưu nháp
        </button>
        <button @click="saveAndPublish" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
          {{ saving ? 'Đang lưu...' : 'Lưu & Xuất bản' }}
        </button>
      </div>
    </div>

    <!-- Test Info -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
      <h2 class="text-lg font-semibold mb-4">Thông tin đề thi</h2>
      <div class="grid grid-cols-1 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tên đề thi *</label>
          <input
            v-model="testData.title"
            type="text"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="VD: IELTS Academic Test 1"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
          <textarea
            v-model="testData.description"
            rows="2"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Mô tả ngắn về đề thi..."
          ></textarea>
        </div>
      </div>
    </div>

    <!-- Skill Tabs (only show when creating new, not when editing) -->
    <div class="bg-white rounded-lg shadow-sm border">
      <div class="border-b" v-if="!isEditing">
        <nav class="flex -mb-px">
          <button
            v-for="skill in skills"
            :key="skill.id"
            @click="activeSkill = skill.id"
            class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
            :class="activeSkill === skill.id
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
          >
            <div class="flex items-center space-x-2">
              <component :is="skill.icon" class="w-5 h-5" />
              <span>{{ skill.name }}</span>
              <span v-if="getSkillQuestionCount(skill.id) > 0" class="ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-600 rounded-full">
                {{ getSkillQuestionCount(skill.id) }}
              </span>
            </div>
          </button>
        </nav>
      </div>
      
      <!-- When editing, show skill badge instead of tabs -->
      <div v-else class="px-6 py-4 bg-gray-50 border-b flex items-center space-x-3">
        <span class="text-sm font-medium text-gray-700">Kỹ năng:</span>
        <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-semibold rounded-full uppercase">
          {{ testData.subtype }}
        </span>
      </div>

      <div class="p-6">
        <!-- When editing: only show the current skill's section -->
        <!-- When creating: show based on active tab -->
        
        <!-- Listening Section -->
        <IELTSListeningSection
          v-if="(isEditing && testData.subtype === 'listening') || (!isEditing && activeSkill === 'listening')"
          v-model="testData.sections.listening"
          @update:modelValue="updateSection('listening', $event)"
        />

        <!-- Reading Section -->
        <IELTSReadingSection
          v-if="(isEditing && testData.subtype === 'reading') || (!isEditing && activeSkill === 'reading')"
          v-model="testData.sections.reading"
          :subtype="testData.subtype"
          @update:modelValue="updateSection('reading', $event)"
        />

        <!-- Writing Section -->
        <IELTSWritingSection
          v-if="(isEditing && testData.subtype === 'writing') || (!isEditing && activeSkill === 'writing')"
          v-model="testData.sections.writing"
          :subtype="testData.subtype"
          @update:modelValue="updateSection('writing', $event)"
        />

        <!-- Speaking Section -->
        <IELTSSpeakingSection
          v-if="(isEditing && testData.subtype === 'speaking') || (!isEditing && activeSkill === 'speaking')"
          v-model="testData.sections.speaking"
          @update:modelValue="updateSection('speaking', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api'
import IELTSListeningSection from './sections/IELTSListeningSection.vue'
import IELTSReadingSection from './sections/IELTSReadingSection.vue'
import IELTSWritingSection from './sections/IELTSWritingSection.vue'
import IELTSSpeakingSection from './sections/IELTSSpeakingSection.vue'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()

const isEditing = computed(() => !!route.params.id)
const saving = ref(false)
const activeSkill = ref('listening')

// Icons as render functions
const HeadphonesIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3' })
    ])
  }
}

const BookIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' })
    ])
  }
}

const PencilIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z' })
    ])
  }
}

const MicrophoneIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z' })
    ])
  }
}

const skills = [
  { id: 'listening', name: 'Listening', icon: HeadphonesIcon },
  { id: 'reading', name: 'Reading', icon: BookIcon },
  { id: 'writing', name: 'Writing', icon: PencilIcon },
  { id: 'speaking', name: 'Speaking', icon: MicrophoneIcon },
]

const testData = reactive({
  title: '',
  description: '',
  type: 'ielts',
  subtype: '',  // Will be: listening, reading, writing, or speaking
  time_limit: null, // Will be calculated
  sections: {
    listening: {
      parts: [
        { id: 1, title: 'Part 1', audio: null, questions: [] },
        { id: 2, title: 'Part 2', audio: null, questions: [] },
        { id: 3, title: 'Part 3', audio: null, questions: [] },
        { id: 4, title: 'Part 4', audio: null, questions: [] },
      ]
    },
    reading: {
      passages: [
        { id: 1, title: 'Passage 1', content: '', questions: [] },
        { id: 2, title: 'Passage 2', content: '', questions: [] },
        { id: 3, title: 'Passage 3', content: '', questions: [] },
      ]
    },
    writing: {
      tasks: [] // Start empty - user adds tasks manually
    },
    speaking: {
      parts: [
        { id: 1, title: 'Part 1', questions: [], criteria: [], examinerNotes: '' },
        { id: 2, title: 'Part 2', cueCard: '', prepTime: 1, speakTime: 2, questions: [], criteria: [], examinerNotes: '' },
        { id: 3, title: 'Part 3', questions: [], criteria: [], examinerNotes: '' },
      ]
    }
  }
})

function getSkillQuestionCount(skillId) {
  const section = testData.sections[skillId]
  if (!section) return 0

  switch (skillId) {
    case 'listening':
      return section.parts.reduce((sum, p) => sum + (p.questions?.length || 0), 0)
    case 'reading':
      return section.passages.reduce((sum, p) => sum + (p.questions?.length || 0), 0)
    case 'writing':
      return section.tasks.length
    case 'speaking':
      return section.parts.reduce((sum, p) => {
        if (p.questions) return sum + p.questions.length
        if (p.cueCard) return sum + 1
        return sum
      }, 0)
    default:
      return 0
  }
}

function updateSection(skill, data) {
  testData.sections[skill] = data
}

async function saveDraft() {
  await saveTest('draft')
}

async function saveAndPublish() {
  await saveTest('active')
}

async function saveTest(status) {
  if (!testData.title) {
    Swal.fire({
      icon: 'warning',
      title: 'Thiếu thông tin',
      text: 'Vui lòng nhập tên đề thi',
    })
    return
  }

  // Auto-determine which sections have content
  const activeSkills = []
  if (getSkillQuestionCount('listening') > 0) activeSkills.push('listening')
  if (getSkillQuestionCount('reading') > 0) activeSkills.push('reading')
  if (getSkillQuestionCount('writing') > 0) activeSkills.push('writing')
  if (getSkillQuestionCount('speaking') > 0) activeSkills.push('speaking')

  if (activeSkills.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Thiếu nội dung',
      text: 'Vui lòng thêm nội dung vào ít nhất một kỹ năng',
    })
    return
  }

  console.log('💾 Saving test with:', {
    title: testData.title,
    activeSkills: activeSkills,
    sections: testData.sections
  })
  
  // Debug: Check labeling groups for features and images
  if (testData.sections.listening?.parts) {
    testData.sections.listening.parts.forEach((part, pIdx) => {
      part.questionGroups?.forEach((group, gIdx) => {
        if (group.type === 'labeling') {
          console.log(`🔍 Part ${pIdx+1} Group ${gIdx+1} (labeling):`, {
            hasImage: !!group.diagramImage,
            imageUrl: group.diagramImage,
            hasFeatures: !!group.features,
            featuresCount: group.features?.length || 0,
            features: group.features
          })
        }
      })
    })
  }

  saving.value = true
  try {
    if (isEditing.value) {
      // When editing, update single test as before
      testData.subtype = activeSkills.length === 1 ? activeSkills[0] : 'full'
      const payload = {
        ...testData,
        status,
        sections: JSON.stringify(testData.sections)
      }
      await api.put(`/examination/tests/${route.params.id}`, payload)
    } else {
      // When creating new: create separate tests for each skill
      const createdTests = []

      for (const skill of activeSkills) {
        // Create isolated sections object with only this skill's data
        const skillSections = {
          listening: skill === 'listening' ? testData.sections.listening : { parts: [] },
          reading: skill === 'reading' ? testData.sections.reading : { passages: [] },
          writing: skill === 'writing' ? testData.sections.writing : { tasks: [] },
          speaking: skill === 'speaking' ? testData.sections.speaking : { parts: [] }
        }

        const skillTitle = activeSkills.length === 1
          ? testData.title
          : `${testData.title} - ${skill.charAt(0).toUpperCase() + skill.slice(1)}`

        const payload = {
          title: skillTitle,
          description: testData.description,
          type: 'ielts',
          subtype: skill,
          status: status,
          sections: JSON.stringify(skillSections)
        }

        console.log(`Creating ${skill} test:`, skillTitle)
        const response = await api.post('/examination/tests', payload)
        createdTests.push(response.data.data)
      }

      console.log(`✅ Created ${createdTests.length} tests`)
    }

    router.push({ name: 'examination.tests', query: { type: 'ielts' } })
  } catch (error) {
    console.error('Error saving test:', error)
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Có lỗi xảy ra khi lưu đề thi: ' + (error.response?.data?.message || error.message),
    })
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  if (isEditing.value) {
    try {
      const response = await api.get(`/examination/tests/${route.params.id}`)
      const test = response.data.data
      testData.title = test.title
      testData.description = test.description
      testData.subtype = test.subtype || ''  // listening, reading, writing, speaking
      
      // Load content from settings field
      if (test.settings) {
        const settings = typeof test.settings === 'string' ? JSON.parse(test.settings) : test.settings

        // Load all sections from namespaced structure (settings.reading, settings.listening, etc.)
        // This supports both single-skill and multi-skill tests

        // Load Listening section
        if (settings.listening && settings.listening.parts) {
          testData.sections.listening.parts = settings.listening.parts
        }

        // Load Reading section
        if (settings.reading && settings.reading.passages) {
          testData.sections.reading.passages = settings.reading.passages.map((p, index) => ({
            id: index + 1,
            title: p.title || `Passage ${index + 1}`,
            content: p.content || '',
            questionGroups: p.questionGroups || [],
            questions: (p.questions || []).map(q => ({
              id: q.number || (Date.now() + Math.random()),
              number: q.number,
              type: q.type,
              content: q.question || q.statement || q.sentence || '',
              correctAnswer: q.answer,
              options: q.options || []
            }))
          }))
        }

        // Load Writing section
        if (settings.writing && settings.writing.tasks) {
          testData.sections.writing.tasks = settings.writing.tasks.map(t => ({
            id: t.number || t.id,
            title: t.title,
            prompt: t.prompt || '',
            imageUrl: t.image_url || t.imageUrl || null,
            imageSource: t.image_source || t.imageSource || null,
            imagePath: t.image_path || t.imagePath || null,
            visualType: t.visual_type || t.visualType || 'bar_chart',
            chartData: t.chart_data || t.chartData || null,
            imagePrompt: t.image_prompt || t.imagePrompt || '',
            minWords: t.min_words || t.minWords || 150,
            timeLimit: t.time_limit || t.timeLimit || 20,
            sampleAnswer: t.sample_answer || t.sampleAnswer || '',
            showSampleAnswer: false
          }))
        }

        // Load Speaking section
        if (settings.speaking && settings.speaking.parts) {
          testData.sections.speaking.parts = settings.speaking.parts
        }
        if (settings.speaking && settings.speaking.script) {
          testData.sections.speaking.script = settings.speaking.script
        }
      }
    } catch (error) {
      console.error('Error loading test:', error)
    }
  } else {
    // Pre-fill skill from query param when creating new
    if (route.query.skill) {
      testData.subtype = route.query.skill
    }
  }
})
</script>
