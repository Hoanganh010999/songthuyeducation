<template>
  <div v-if="hasAnyFeedback" class="feedback-accordion space-y-3">
    <!-- Band Score Summary (if available) -->
    <div v-if="bandScore !== null && bandScore !== undefined" class="mb-4 p-4 bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg border border-purple-200">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-gray-600 mb-1">Điểm tổng</div>
          <div class="text-3xl font-bold text-purple-700">{{ formatBandScore(bandScore) }}</div>
          <div class="text-xs text-gray-500 mt-1">/ 9.0</div>
        </div>
        <div class="text-4xl">🎯</div>
      </div>
    </div>

    <!-- Grading Criteria Accordions -->
    <div v-if="criteriaList && Object.keys(criteriaList).length > 0" class="space-y-2">
      <div
        v-for="(score, criterion) in criteriaList"
        :key="criterion"
        class="border rounded-lg overflow-hidden transition-all"
        :class="expandedCriteria[criterion] ? 'border-blue-300 shadow-md' : 'border-gray-200'"
      >
        <!-- Criteria Header (Always Visible) -->
        <button
          @click="toggleCriterion(criterion)"
          class="w-full px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors flex items-center justify-between text-left"
        >
          <div class="flex items-center space-x-3 flex-1">
            <div class="flex-shrink-0">
              <div
                class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg"
                :class="getScoreColorClass(score)"
              >
                {{ formatBandScore(score) }}
              </div>
            </div>
            <div class="flex-1">
              <div class="font-semibold text-gray-800">{{ getCriterionLabel(criterion) }}</div>
              <div class="text-xs text-gray-500 mt-0.5">{{ getCriterionDescription(criterion) }}</div>
            </div>
          </div>
          <div class="flex-shrink-0 ml-4">
            <svg
              class="w-5 h-5 text-gray-500 transition-transform"
              :class="{ 'rotate-180': expandedCriteria[criterion] }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </button>

        <!-- Criteria Content (Expandable) -->
        <div
          v-show="expandedCriteria[criterion]"
          class="px-4 py-3 bg-white border-t border-gray-100"
        >
          <div class="text-sm text-gray-700 leading-relaxed">
            <div v-if="getCriterionFeedback(criterion)" class="whitespace-pre-wrap">
              {{ getCriterionFeedback(criterion) }}
            </div>
            <div v-else class="text-gray-400 italic">
              Chưa có nhận xét chi tiết cho tiêu chí này
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- General Feedback (if no grading criteria or additional feedback) -->
    <div v-if="generalFeedback" class="border rounded-lg overflow-hidden border-yellow-200">
      <button
        @click="expandedGeneral = !expandedGeneral"
        class="w-full px-4 py-3 bg-yellow-50 hover:bg-yellow-100 transition-colors flex items-center justify-between text-left"
      >
        <div class="flex items-center space-x-3">
          <div class="text-2xl">📝</div>
          <div>
            <div class="font-semibold text-yellow-800">Nhận xét tổng quan</div>
            <div class="text-xs text-yellow-600 mt-0.5">Nhấn để xem chi tiết</div>
          </div>
        </div>
        <svg
          class="w-5 h-5 text-yellow-600 transition-transform"
          :class="{ 'rotate-180': expandedGeneral }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <div
        v-show="expandedGeneral"
        class="px-4 py-3 bg-white border-t border-yellow-100"
      >
        <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
          {{ generalFeedback }}
        </div>
      </div>
    </div>

    <!-- Fallback: Show raw feedback if no criteria feedback found or if criteria list is empty -->
    <div v-if="shouldShowRawFeedback" class="border rounded-lg border-blue-200">
      <button
        @click="expandedRaw = !expandedRaw"
        class="w-full px-4 py-3 bg-blue-50 hover:bg-blue-100 transition-colors flex items-center justify-between text-left"
      >
        <div class="flex items-center space-x-3">
          <div class="text-2xl">💬</div>
          <div>
            <div class="font-semibold text-blue-800">Nhận xét</div>
            <div class="text-xs text-blue-600 mt-0.5">Nhấn để xem chi tiết</div>
          </div>
        </div>
        <svg
          class="w-5 h-5 text-blue-600 transition-transform"
          :class="{ 'rotate-180': expandedRaw }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <div
        v-show="expandedRaw"
        class="px-4 py-3 bg-white border-t border-blue-100"
      >
        <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
          {{ rawFeedback }}
        </div>
      </div>
    </div>
    
    <!-- If no feedback at all -->
    <div v-if="!props.feedback && (!criteriaList || Object.keys(criteriaList).length === 0)" class="text-center py-4 text-gray-500 text-sm">
      Chưa có nhận xét
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  feedback: {
    type: String,
    default: null
  },
  gradingCriteria: {
    type: Object,
    default: null
  },
  bandScore: {
    type: [Number, String],
    default: null
  },
  testType: {
    type: String,
    default: null // 'speaking' or 'writing'
  }
})

const expandedCriteria = ref({})
const expandedGeneral = ref(false)
const expandedRaw = ref(false)

// Criteria labels and descriptions
const criteriaLabels = {
  // Speaking
  fluency_coherence: {
    label: 'Fluency & Coherence (FC)',
    description: 'Độ trôi chảy và mạch lạc'
  },
  lexical_resource: {
    label: 'Lexical Resource (LR)',
    description: 'Vốn từ vựng'
  },
  grammatical_range: {
    label: 'Grammatical Range & Accuracy (GRA)',
    description: 'Phạm vi và độ chính xác ngữ pháp'
  },
  pronunciation: {
    label: 'Pronunciation (P)',
    description: 'Phát âm'
  },
  // Writing
  task_achievement: {
    label: 'Task Achievement (TA)',
    description: 'Hoàn thành yêu cầu đề bài'
  },
  coherence_cohesion: {
    label: 'Coherence & Cohesion (CC)',
    description: 'Mạch lạc và liên kết'
  }
}

// Get criteria list from grading_criteria or parse from feedback
const criteriaList = computed(() => {
  // Priority 1: Use grading_criteria from API
  if (props.gradingCriteria && Object.keys(props.gradingCriteria).length > 0) {
    // Convert to flat structure if needed: { criterion: { score: X, feedback: Y } } -> { criterion: X }
    const flat = {}
    for (const [key, value] of Object.entries(props.gradingCriteria)) {
      if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
        // If it's an object with score property, use score
        flat[key] = value.score !== undefined ? value.score : (typeof value === 'number' ? value : null)
      } else if (typeof value === 'number') {
        // If it's just a number, use it directly
        flat[key] = value
      }
    }
    // Only return if we have at least one valid score
    if (Object.keys(flat).length > 0) {
      return flat
    }
  }
  
  // Priority 2: Try to parse from feedback string
  if (props.feedback) {
    const parsed = parseFeedbackCriteria(props.feedback)
    if (parsed && Object.keys(parsed).length > 0) {
      return parsed
    }
  }
  
  return {}
})

// Check if we have structured feedback
const hasStructuredFeedback = computed(() => {
  const hasCriteria = props.gradingCriteria && Object.keys(props.gradingCriteria).length > 0
  
  // Try to parse from feedback
  let hasParsedCriteria = false
  if (props.feedback) {
    const parsed = parseFeedbackCriteria(props.feedback)
    hasParsedCriteria = parsed && Object.keys(parsed).length > 0
  }
  
  return hasCriteria || hasParsedCriteria
})

// Check if any criterion has actual feedback text
const hasAnyCriteriaFeedback = computed(() => {
  if (Object.keys(criteriaList.value).length === 0) return false
  
  return Object.keys(criteriaList.value).some(criterion => {
    const feedback = getCriterionFeedback(criterion)
    return feedback && feedback.trim().length > 0
  })
})

// Determine if we should show raw feedback
const shouldShowRawFeedback = computed(() => {
  if (!rawFeedback.value) return false
  
  // Show raw feedback if:
  // 1. No structured feedback at all, OR
  // 2. Criteria list is empty, OR
  // 3. No criteria have actual feedback text
  return !hasStructuredFeedback.value || 
         Object.keys(criteriaList.value).length === 0 || 
         !hasAnyCriteriaFeedback.value
})

// Check if we have any feedback to show at all
const hasAnyFeedback = computed(() => {
  // Check if we have criteria with feedback
  if (hasAnyCriteriaFeedback.value) return true
  
  // Check if we have general feedback
  if (generalFeedback.value) return true
  
  // Check if we have raw feedback
  if (rawFeedback.value && shouldShowRawFeedback.value) return true
  
  return false
})

// General feedback (extracted from feedback string)
const generalFeedback = computed(() => {
  if (!props.feedback) return null
  
  // If we have structured criteria, check if feedback is just concatenated criteria
  if (hasStructuredFeedback.value) {
    // Get all criteria feedback text
    const allCriteriaText = Object.values(criteriaList.value)
      .map(criterion => {
        const feedback = getCriterionFeedback(Object.keys(criteriaList.value).find(k => criteriaList.value[k] === criterion))
        return feedback || ''
      })
      .join(' ')
      .toLowerCase()
    
    // If feedback is mostly the same as concatenated criteria, it's not a real summary
    if (allCriteriaText.length > 0) {
      const feedbackLower = props.feedback.toLowerCase()
      const similarity = calculateTextSimilarity(feedbackLower, allCriteriaText)
      
      // If >70% similar, it's just concatenated criteria - don't show as general feedback
      if (similarity > 70) {
        return null
      }
    }
    
    // Look for summary section (usually at end, starts with "Để cải thiện" or similar)
    const summaryMatch = props.feedback.match(/(để cải thiện|để nâng cao|tổng kết|kết luận|tóm lại)[\s\S]*$/i)
    if (summaryMatch) {
      const summary = summaryMatch[0].trim()
      // Real summary should be short (<500 chars) and not contain detailed criteria descriptions
      if (summary.length < 500 && !summary.match(/về\s+(fluency|lexical|grammar|pronunciation|task|coherence)/i)) {
        return summary
      }
    }
  }
  
  return null
})

// Simple text similarity calculation
function calculateTextSimilarity(str1, str2) {
  if (!str1 || !str2) return 0
  const longer = str1.length > str2.length ? str1 : str2
  const shorter = str1.length > str2.length ? str2 : str1
  if (longer.length === 0) return 100
  
  // Count common words
  const words1 = longer.split(/\s+/).filter(w => w.length > 3)
  const words2 = shorter.split(/\s+/).filter(w => w.length > 3)
  
  if (words1.length === 0) return 0
  
  let matches = 0
  for (const word of words2) {
    if (words1.includes(word)) {
      matches++
    }
  }
  
  return (matches / words1.length) * 100
}

// Raw feedback (fallback) - Always show if no criteria feedback found
const rawFeedback = computed(() => {
  // Always return feedback if it exists - we'll decide whether to show it in shouldShowRawFeedback
  return props.feedback || null
})

// Parse criteria scores from feedback string
function parseFeedbackCriteria(feedback) {
  if (!feedback) return null
  
  const criteria = {}
  const lines = feedback.split('\n')
  
  // Check if it's Speaking or Writing format
  const isSpeaking = feedback.includes('SPEAKING') || 
                     feedback.includes('Fluency & Coherence') ||
                     feedback.includes('fluency_coherence')
  const isWriting = feedback.includes('WRITING') ||
                     feedback.includes('Task Achievement') ||
                     feedback.includes('task_achievement')
  
  for (const line of lines) {
    // Match patterns like "• Fluency & Coherence (FC): 6.5" or "• Task Achievement (TA): 7.0"
    const match = line.match(/[•\-\*]\s*(.+?)\s*\(([A-Z]+)\)\s*:\s*([\d.]+)/i)
    if (match) {
      const fullName = match[1].trim()
      const code = match[2].trim()
      const score = parseFloat(match[3])
      
      // Map to criteria keys
      if (isSpeaking) {
        if (code === 'FC' || fullName.includes('Fluency')) {
          criteria.fluency_coherence = score
        } else if (code === 'LR' || fullName.includes('Lexical')) {
          criteria.lexical_resource = score
        } else if (code === 'GRA' || fullName.includes('Grammatical')) {
          criteria.grammatical_range = score
        } else if (code === 'P' || fullName.includes('Pronunciation')) {
          criteria.pronunciation = score
        }
      } else if (isWriting) {
        if (code === 'TA' || fullName.includes('Task Achievement')) {
          criteria.task_achievement = score
        } else if (code === 'CC' || fullName.includes('Coherence & Cohesion')) {
          criteria.coherence_cohesion = score
        } else if (code === 'LR' || fullName.includes('Lexical')) {
          criteria.lexical_resource = score
        } else if (code === 'GRA' || fullName.includes('Grammatical')) {
          criteria.grammatical_range = score
        }
      }
    }
  }
  
  return Object.keys(criteria).length > 0 ? criteria : null
}

function toggleCriterion(criterion) {
  expandedCriteria.value[criterion] = !expandedCriteria.value[criterion]
}

function formatBandScore(score) {
  if (score === null || score === undefined) return '-'
  const num = parseFloat(score)
  return num % 1 === 0 ? num.toString() : num.toFixed(1)
}

function getScoreColorClass(score) {
  const num = parseFloat(score)
  if (num >= 7) return 'bg-green-500'
  if (num >= 6) return 'bg-blue-500'
  if (num >= 5) return 'bg-yellow-500'
  return 'bg-red-500'
}

function getCriterionLabel(criterion) {
  return criteriaLabels[criterion]?.label || criterion.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function getCriterionDescription(criterion) {
  return criteriaLabels[criterion]?.description || ''
}

function getCriterionFeedback(criterion) {
  // Priority 1: Check if grading_criteria has feedback for this criterion
  if (props.gradingCriteria && props.gradingCriteria[criterion]) {
    const criterionData = props.gradingCriteria[criterion]
    
    // If it's an object with feedback property
    if (typeof criterionData === 'object' && criterionData.feedback) {
      return criterionData.feedback
    }
  }
  
  // Priority 2: Try to extract from feedback string
  if (!props.feedback) return null
  
  const feedback = props.feedback
  const lines = feedback.split('\n')
  
  // Map criterion keys to Vietnamese keywords
  const keywordMap = {
    fluency_coherence: ['fluency', 'coherence', 'mạch lạc', 'trôi chảy', 'ngắt câu', 'ý tưởng', 'chuỗi'],
    lexical_resource: ['lexical', 'resource', 'vốn từ', 'từ vựng', 'từ khó', 'diễn đạt', 'chọn từ'],
    grammatical_range: ['grammar', 'grammatical', 'ngữ pháp', 'cấu trúc', 'động từ', 'chủ ngữ', 'câu'],
    pronunciation: ['pronunciation', 'phát âm', 'azure'],
    task_achievement: ['task achievement', 'hoàn thành', 'yêu cầu', 'đề bài'],
    coherence_cohesion: ['coherence', 'cohesion', 'mạch lạc', 'liên kết', 'paragraphing']
  }
  
  const keywords = keywordMap[criterion] || []
  if (keywords.length === 0) return null
  
  // Find the section for this criterion
  let startIndex = -1
  let endIndex = -1
  let foundSection = false
  
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i].toLowerCase()
    
    // Check if this line starts a new section for our criterion
    const matchesCriterion = keywords.some(keyword => 
      line.includes(keyword.toLowerCase())
    )
    
    // Check if this line starts a different criterion section
    const matchesOtherCriterion = Object.keys(keywordMap).some(otherCriterion => {
      if (otherCriterion === criterion) return false
      return keywordMap[otherCriterion].some(keyword => 
        line.includes(keyword.toLowerCase()) && 
        (line.includes('về') || line.includes('about'))
      )
    })
    
    if (matchesCriterion && (line.includes('về') || line.includes('about') || line.includes('📊'))) {
      startIndex = i
      foundSection = true
    } else if (foundSection && matchesOtherCriterion) {
      endIndex = i
      break
    }
  }
  
  // If we found a section, extract it
  if (startIndex >= 0) {
    const end = endIndex > startIndex ? endIndex : lines.length
    const sectionLines = lines.slice(startIndex, end)
    
    // Remove the header line (e.g., "Về Fluency & Coherence,")
    const contentLines = sectionLines.slice(1)
    const content = contentLines.join('\n').trim()
    
    // Clean up: remove empty lines at start/end and separator lines
    const cleaned = content.replace(/^[━─═\s]+$/gm, '').trim()
    return cleaned || null
  }
  
  // Fallback: try to find paragraphs that mention the criterion
  const paragraphs = feedback.split(/\n\s*\n/)
  for (const paragraph of paragraphs) {
    const lowerParagraph = paragraph.toLowerCase()
    if (keywords.some(keyword => lowerParagraph.includes(keyword.toLowerCase()))) {
      // Check if it's specifically about this criterion
      const isAboutCriterion = lowerParagraph.includes('về') || 
                               lowerParagraph.includes('about') ||
                               (criterion === 'fluency_coherence' && lowerParagraph.includes('fluency') && lowerParagraph.includes('coherence')) ||
                               (criterion === 'lexical_resource' && lowerParagraph.includes('lexical') && lowerParagraph.includes('resource')) ||
                               (criterion === 'grammatical_range' && (lowerParagraph.includes('grammar') || lowerParagraph.includes('grammatical'))) ||
                               (criterion === 'pronunciation' && lowerParagraph.includes('pronunciation'))
      
      if (isAboutCriterion) {
        return paragraph.trim()
      }
    }
  }
  
  return null
}

// Initialize: expand first criterion by default
onMounted(() => {
  if (criteriaList.value && Object.keys(criteriaList.value).length > 0) {
    const firstCriterion = Object.keys(criteriaList.value)[0]
    expandedCriteria.value[firstCriterion] = true
  }
})
</script>

<style scoped>
.feedback-accordion {
  font-family: inherit;
}
</style>

