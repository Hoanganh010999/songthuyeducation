/**
 * Core Grading Composable
 * Central state management and operations for grading
 */

import { ref, computed, watch } from 'vue'
import { useScoring } from './useScoring'

/**
 * Use Grading Core
 * @param {object} config - Configuration object
 * @returns {object} Grading state and operations
 */
export function useGradingCore(config) {
  const {
    submissionId: initialSubmissionId,
    gradingType = 'homework',    // 'homework' | 'examination'
    scoringType = 'percentage',  // 'percentage' | 'band' | 'criteria'
    apiAdapter                   // API adapter instance
  } = config

  if (!apiAdapter) {
    throw new Error('apiAdapter is required for useGradingCore')
  }

  // Make submissionId reactive
  const submissionId = ref(initialSubmissionId)

  // =========================================================================
  // STATE
  // =========================================================================

  const submission = ref(null)
  const answers = ref([])
  const loading = ref(false)
  const saving = ref(false)
  const currentAnswerId = ref(null)
  const error = ref(null)

  // =========================================================================
  // SCORING SYSTEM
  // =========================================================================

  const {
    calculateScore,
    validateScore,
    displayScore,
    parseScore,
    bandScores,
    conversionTable
  } = useScoring({ type: scoringType })

  // =========================================================================
  // OVERALL GRADING
  // =========================================================================

  const overallGrade = ref({
    score: null,      // Overall score (percentage or band)
    feedback: '',     // Teacher feedback
    gradedAt: null,
    gradedBy: null
  })

  // =========================================================================
  // COMPUTED PROPERTIES
  // =========================================================================

  /**
   * Count of correctly graded answers
   */
  const correctCount = computed(() => {
    return answers.value.filter(a => a.is_correct === true).length
  })

  /**
   * Count of incorrectly graded answers
   */
  const incorrectCount = computed(() => {
    return answers.value.filter(a => a.is_correct === false).length
  })

  /**
   * Count of ungraded answers
   */
  const ungradedCount = computed(() => {
    return answers.value.filter(a => a.is_correct === null).length
  })

  /**
   * Total answer count
   */
  const totalCount = computed(() => {
    return answers.value.length
  })

  /**
   * Auto-calculated score based on correct/total
   */
  const autoScore = computed(() => {
    if (totalCount.value === 0) return null
    return calculateScore(correctCount.value, totalCount.value)
  })

  /**
   * Score percentage (0-100)
   */
  const scorePercentage = computed(() => {
    if (totalCount.value === 0) return 0
    return Math.round((correctCount.value / totalCount.value) * 100)
  })

  /**
   * Is grading complete (all answers graded)
   */
  const isGradingComplete = computed(() => {
    return ungradedCount.value === 0 && totalCount.value > 0
  })

  /**
   * Has any grades been assigned
   */
  const hasGrades = computed(() => {
    return correctCount.value > 0 || incorrectCount.value > 0
  })

  // =========================================================================
  // OPERATIONS
  // =========================================================================

  /**
   * Load submission and answers
   * @param {number|string} id - Optional submission ID to load (updates reactive submissionId)
   * @returns {Promise<void>}
   */
  const loadSubmission = async (id = null) => {
    console.log('[useGradingCore] 🔄 loadSubmission called with id:', id)
    console.log('[useGradingCore] 📊 Current submissionId.value:', submissionId.value)

    // Update submissionId if provided
    if (id !== null && id !== undefined) {
      submissionId.value = id
      console.log('[useGradingCore] ✅ Updated submissionId.value to:', submissionId.value)
    }

    loading.value = true
    error.value = null

    console.log('[useGradingCore] 📡 Fetching submission with ID:', submissionId.value)

    try {
      const data = await apiAdapter.fetchSubmission(submissionId.value)

      console.log('[useGradingCore] ✅ Data received:', data)
      console.log('[useGradingCore] 📊 Submission:', data.submission)
      console.log('[useGradingCore] 📊 Answers:', data.answers)
      console.log('[useGradingCore] 📊 Answers length:', data.answers?.length)

      submission.value = data.submission
      answers.value = data.answers

      console.log('[useGradingCore] ✅ State updated - answers.value.length:', answers.value.length)

      // Pre-fill overall grade if already graded
      if (data.submission) {
        overallGrade.value = {
          score: data.submission.grade || data.submission.band_score || null,
          feedback: data.submission.teacher_feedback || data.submission.feedback || '',
          gradedAt: data.submission.graded_at || null,
          gradedBy: data.submission.graded_by || null
        }
        console.log('[useGradingCore] 📝 Overall grade pre-filled:', overallGrade.value)
      }
    } catch (err) {
      error.value = err
      console.error('[useGradingCore] ❌ Error loading submission:', err)
      console.error('[useGradingCore] ❌ Error details:', err.response?.data || err.message)
      throw err
    } finally {
      loading.value = false
      console.log('[useGradingCore] 🏁 loadSubmission completed, loading:', loading.value)
    }
  }

  /**
   * Grade individual answer
   * @param {number|string} answerId - Answer ID
   * @param {boolean|number|object} value - Grade value (boolean for binary, number for band, object for criteria)
   * @param {object} options - Additional options
   * @returns {Promise<void>}
   */
  const gradeAnswer = async (answerId, value, options = {}) => {
    currentAnswerId.value = answerId
    error.value = null

    try {
      // Prepare grading data
      const gradingData = {
        is_correct: null
      }

      // Handle different value types
      if (typeof value === 'boolean') {
        // Binary grading (correct/incorrect)
        gradingData.is_correct = value
      } else if (typeof value === 'number') {
        // Band score grading
        gradingData.band_score = value
        gradingData.is_correct = value >= (options.passingBand || 5)
      } else if (typeof value === 'object') {
        // Criteria grading
        gradingData.criteria = value
        gradingData.is_correct = true // Subjective questions are considered "correct" when graded
      }

      // Add optional fields
      if (options.feedback) {
        gradingData.feedback = options.feedback
      }

      if (options.bandScore !== undefined) {
        gradingData.band_score = options.bandScore
      }

      if (options.criteria) {
        gradingData.criteria = options.criteria
      }

      if (options.annotations !== undefined) {
        gradingData.annotations = options.annotations
      }

      if (options.grading_notes !== undefined) {
        gradingData.grading_notes = options.grading_notes
      }

      // Call API
      const response = await apiAdapter.gradeAnswer(submissionId.value, answerId, gradingData)

      // Update local state with response data (includes annotations)
      const answerIndex = answers.value.findIndex(a => a.id === answerId)
      if (answerIndex !== -1) {
        // Use the response data which includes all fields including annotations
        if (response && response.data) {
          answers.value[answerIndex] = {
            ...answers.value[answerIndex],
            ...response.data
          }
        } else {
          // Fallback to manual update if no response data
          answers.value[answerIndex] = {
            ...answers.value[answerIndex],
            is_correct: gradingData.is_correct,
            band_score: gradingData.band_score,
            grading_criteria: gradingData.criteria,
            feedback: gradingData.feedback,
            annotations: gradingData.annotations
          }
        }
      }

      // Auto-update overall grade if not manually set
      if (!overallGrade.value.score || options.autoUpdate) {
        overallGrade.value.score = autoScore.value
      }
    } catch (err) {
      error.value = err
      console.error('Error grading answer:', err)
      throw err
    } finally {
      currentAnswerId.value = null
    }
  }

  /**
   * Save overall grade and feedback
   * @param {object} customGrade - Optional custom grade to save (default: uses overallGrade)
   * @returns {Promise<void>}
   */
  const saveOverallGrade = async (customGrade = null) => {
    saving.value = true
    error.value = null

    try {
      const gradeData = customGrade || overallGrade.value

      await apiAdapter.saveOverallGrade(submissionId.value, {
        score: gradeData.score,
        feedback: gradeData.feedback
      })

      // Update local state
      if (!customGrade) {
        overallGrade.value.gradedAt = new Date().toISOString()
      }
    } catch (err) {
      error.value = err
      console.error('Error saving overall grade:', err)
      throw err
    } finally {
      saving.value = false
    }
  }

  /**
   * Reset grading for an answer
   * @param {number|string} answerId
   * @returns {Promise<void>}
   */
  const resetAnswer = async (answerId) => {
    return gradeAnswer(answerId, null, { reset: true })
  }

  /**
   * Apply auto-calculated score to overall grade
   */
  const applyAutoScore = () => {
    if (autoScore.value !== null) {
      overallGrade.value.score = autoScore.value
    }
  }

  /**
   * Clear all local state
   */
  const reset = () => {
    submission.value = null
    answers.value = []
    overallGrade.value = {
      score: null,
      feedback: '',
      gradedAt: null,
      gradedBy: null
    }
    error.value = null
  }

  // =========================================================================
  // WATCHERS
  // =========================================================================

  /**
   * Watch for auto score changes and suggest update
   */
  watch(autoScore, (newScore) => {
    // Only auto-fill if no manual score set yet
    if (overallGrade.value.score === null && newScore !== null) {
      overallGrade.value.score = newScore
    }
  })

  // =========================================================================
  // RETURN API
  // =========================================================================

  return {
    // State
    submission,
    answers,
    loading,
    saving,
    currentAnswerId,
    error,
    overallGrade,

    // Computed
    correctCount,
    incorrectCount,
    ungradedCount,
    totalCount,
    autoScore,
    scorePercentage,
    isGradingComplete,
    hasGrades,

    // Operations
    loadSubmission,
    gradeAnswer,
    saveOverallGrade,
    resetAnswer,
    applyAutoScore,
    reset,

    // Scoring utilities
    displayScore,
    validateScore,
    parseScore,
    bandScores,
    conversionTable,

    // Config
    gradingType,
    scoringType
  }
}
