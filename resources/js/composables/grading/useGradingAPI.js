/**
 * API Adapter for Grading System
 * Abstracts API differences between homework and examination grading
 */

import axios from 'axios'

/**
 * Create API adapter based on grading type
 * @param {string} type - 'homework' or 'examination'
 * @returns {object} API adapter with unified interface
 */
export function createGradingAPI(type) {
  const adapters = {
    /**
     * Homework API Adapter
     * Uses /api/course/homework/* endpoints
     */
    homework: {
      /**
       * Fetch submission with answers
       * @param {number|string} submissionId
       * @returns {Promise<{submission: object, answers: array}>}
       */
      async fetchSubmission(submissionId) {
        try {
          console.log('[useGradingAPI] 📥 Fetching homework submission:', submissionId)
          const url = `/api/course/homework/submissions/${submissionId}/answers`
          console.log('[useGradingAPI] 📡 API URL:', url)

          const response = await axios.get(url)

          console.log('[useGradingAPI] ✅ Response:', response.data)
          console.log('[useGradingAPI] 📊 Submission:', response.data.submission)
          console.log('[useGradingAPI] 📊 Answers count:', response.data.data?.length || 0)

          return {
            submission: response.data.submission || null,
            answers: response.data.data || []
          }
        } catch (error) {
          console.error('[useGradingAPI] ❌ Error fetching homework submission:', error)
          console.error('[useGradingAPI] ❌ Error response:', error.response?.data)
          throw error
        }
      },

      /**
       * Grade individual answer
       * @param {number|string} submissionId
       * @param {number|string} answerId
       * @param {object} data - Grading data
       * @returns {Promise<object>}
       */
      async gradeAnswer(submissionId, answerId, data) {
        try {
          const payload = {
            is_correct: data.is_correct
          }

          // Include annotations if provided (for essay questions)
          if (data.annotations !== undefined) {
            payload.annotations = data.annotations
          }

          // Include grading notes if provided
          if (data.grading_notes !== undefined) {
            payload.grading_notes = data.grading_notes
          }

          // Include AI feedback if provided
          if (data.auto_feedback !== undefined) {
            payload.auto_feedback = data.auto_feedback
          }

          console.log('[useGradingAPI] 📤 Sending grading request:', {
            submissionId,
            answerId,
            payload,
            hasAnnotations: !!payload.annotations,
            hasAutoFeedback: !!payload.auto_feedback
          })

          const response = await axios.post(
            `/api/course/homework/submissions/${submissionId}/answers/${answerId}/grade`,
            payload
          )

          console.log('[useGradingAPI] ✅ Grading response:', response.data)

          return response.data
        } catch (error) {
          console.error('Error grading homework answer:', error)
          throw error
        }
      },

      /**
       * Save overall grade and feedback
       * @param {number|string} submissionId
       * @param {object} data - Overall grading data
       * @returns {Promise<object>}
       */
      async saveOverallGrade(submissionId, data) {
        try {
          const response = await axios.post(
            `/api/course/homework/submissions/${submissionId}/grade`,
            {
              grade: data.score,
              teacher_feedback: data.feedback
            }
          )

          return response.data
        } catch (error) {
          console.error('Error saving homework grade:', error)
          throw error
        }
      }
    },

    /**
     * Examination API Adapter
     * Uses /examination/submissions/* endpoints
     */
    examination: {
      /**
       * Fetch submission with answers
       * @param {number|string} submissionId
       * @returns {Promise<{submission: object, answers: array}>}
       */
      async fetchSubmission(submissionId) {
        try {
          // Import api client (lazily to avoid circular dependencies)
          const api = (await import('@/api')).default

          const response = await api.get(`/examination/submissions/${submissionId}`)

          const submissionData = response.data.data || {}

          return {
            submission: submissionData,
            answers: submissionData.answers || []
          }
        } catch (error) {
          console.error('Error fetching examination submission:', error)
          throw error
        }
      },

      /**
       * Grade individual answer
       * @param {number|string} submissionId
       * @param {number|string} answerId
       * @param {object} data - Grading data
       * @returns {Promise<object>}
       */
      async gradeAnswer(submissionId, answerId, data) {
        try {
          const api = (await import('@/api')).default

          const payload = {
            is_correct: data.is_correct
          }

          // Add band score if provided
          if (data.band_score !== undefined && data.band_score !== null) {
            payload.band_score = data.band_score
            payload.points = data.band_score
          }

          // Add feedback if provided
          if (data.feedback) {
            payload.feedback = data.feedback
          }

          // Add grading criteria if provided
          if (data.criteria) {
            payload.grading_criteria = data.criteria
          }

          const response = await api.post(
            `/examination/submissions/${submissionId}/answers/${answerId}/grade`,
            payload
          )

          return response.data
        } catch (error) {
          console.error('Error grading examination answer:', error)
          throw error
        }
      },

      /**
       * Save overall grade and feedback
       * @param {number|string} submissionId
       * @param {object} data - Overall grading data
       * @returns {Promise<object>}
       */
      async saveOverallGrade(submissionId, data) {
        try {
          const api = (await import('@/api')).default

          const response = await api.post(
            `/examination/submissions/${submissionId}/feedback`,
            {
              feedback: data.feedback,
              band_score: data.score,
              status: 'graded'
            }
          )

          return response.data
        } catch (error) {
          console.error('Error saving examination grade:', error)
          throw error
        }
      }
    }
  }

  // Validate type
  if (!adapters[type]) {
    throw new Error(`Invalid grading type: ${type}. Must be 'homework' or 'examination'`)
  }

  return adapters[type]
}

/**
 * Get available grading types
 * @returns {array} List of supported grading types
 */
export function getGradingTypes() {
  return ['homework', 'examination']
}
