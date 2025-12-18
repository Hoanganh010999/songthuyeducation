import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

// Question Store
export const useQuestionStore = defineStore('questions', () => {
  const questions = ref([])
  const currentQuestion = ref(null)
  const categories = ref([])
  const loading = ref(false)
  const pagination = ref({
    current_page: 1,
    per_page: 20,
    total: 0,
    last_page: 1
  })
  const filters = ref({
    search: '',
    category_id: null,
    subject_id: null,
    skill: null,
    difficulty: null,
    type: null,
    tag_id: null,
    status: ''
  })

  // Actions
  async function fetchQuestions(params = {}) {
    loading.value = true
    try {
      const response = await api.get('/examination/questions', {
        params: { ...filters.value, ...params }
      })
      questions.value = response.data.data.data
      pagination.value = {
        current_page: response.data.data.current_page,
        per_page: response.data.data.per_page,
        total: response.data.data.total,
        last_page: response.data.data.last_page
      }
      return response.data
    } catch (error) {
      console.error('Error fetching questions:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchQuestion(id) {
    loading.value = true
    try {
      const response = await api.get(`/examination/questions/${id}`)
      currentQuestion.value = response.data.data
      return response.data.data
    } catch (error) {
      console.error('Error fetching question:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function createQuestion(data) {
    loading.value = true
    try {
      const response = await api.post('/examination/questions', data)
      return response.data
    } catch (error) {
      console.error('Error creating question:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function updateQuestion(id, data) {
    loading.value = true
    try {
      const response = await api.put(`/examination/questions/${id}`, data)
      return response.data
    } catch (error) {
      console.error('Error updating question:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function deleteQuestion(id) {
    try {
      const response = await api.delete(`/examination/questions/${id}`)
      questions.value = questions.value.filter(q => q.id !== id)
      return response.data
    } catch (error) {
      console.error('Error deleting question:', error)
      throw error
    }
  }

  async function duplicateQuestion(id) {
    try {
      const response = await api.post(`/examination/questions/${id}/duplicate`)
      return response.data
    } catch (error) {
      console.error('Error duplicating question:', error)
      throw error
    }
  }

  function setFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  function resetFilters() {
    filters.value = {
      search: '',
      category_id: null,
      subject_id: null,
      skill: null,
      difficulty: null,
      type: null,
      tag_id: null,
      status: ''
    }
  }

  return {
    questions,
    currentQuestion,
    categories,
    loading,
    pagination,
    filters,
    fetchQuestions,
    fetchQuestion,
    createQuestion,
    updateQuestion,
    deleteQuestion,
    duplicateQuestion,
    setFilters,
    resetFilters
  }
})

// Test Store
export const useTestStore = defineStore('tests', () => {
  const tests = ref([])
  const currentTest = ref(null)
  const loading = ref(false)
  const pagination = ref({
    current_page: 1,
    per_page: 20,
    total: 0,
    last_page: 1
  })

  async function fetchTests(params = {}) {
    loading.value = true
    try {
      console.log('[TestStore] Fetching tests with params:', params)
      const response = await api.get('/examination/tests', { params })
      console.log('[TestStore] Full response:', response)
      console.log('[TestStore] response.data:', response.data)
      console.log('[TestStore] response.data.data:', response.data?.data)
      console.log('[TestStore] response.data.data.data:', response.data?.data?.data)
      tests.value = response.data?.data?.data || []
      pagination.value = {
        current_page: response.data.data.current_page,
        per_page: response.data.data.per_page,
        total: response.data.data.total,
        last_page: response.data.data.last_page
      }
      console.log('[TestStore] Tests loaded:', tests.value.length, 'items')
      return response.data
    } catch (error) {
      console.error('[TestStore] Error fetching tests:', error)
      tests.value = []
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchTest(id) {
    loading.value = true
    try {
      const response = await api.get(`/examination/tests/${id}`)
      currentTest.value = response.data.data
      return response.data.data
    } catch (error) {
      console.error('Error fetching test:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function createTest(data) {
    loading.value = true
    try {
      const response = await api.post('/examination/tests', data)
      return response.data
    } catch (error) {
      console.error('Error creating test:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function updateTest(id, data) {
    loading.value = true
    try {
      const response = await api.put(`/examination/tests/${id}`, data)
      return response.data
    } catch (error) {
      console.error('Error updating test:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function addQuestionsToTest(testId, questions, sectionId = null) {
    try {
      const response = await api.post(`/examination/tests/${testId}/questions`, {
        questions,
        section_id: sectionId
      })
      return response.data
    } catch (error) {
      console.error('Error adding questions:', error)
      throw error
    }
  }

  async function removeQuestionFromTest(testId, questionId) {
    try {
      const response = await api.delete(`/examination/tests/${testId}/questions/${questionId}`)
      return response.data
    } catch (error) {
      console.error('Error removing question:', error)
      throw error
    }
  }

  async function deleteTest(id) {
    try {
      const response = await api.delete(`/examination/tests/${id}`)
      // Remove from local state if successful
      tests.value = tests.value.filter(t => t.id !== id)
      return response.data
    } catch (error) {
      console.error('Error deleting test:', error)
      throw error
    }
  }

  return {
    tests,
    currentTest,
    loading,
    pagination,
    fetchTests,
    fetchTest,
    createTest,
    updateTest,
    deleteTest,
    addQuestionsToTest,
    removeQuestionFromTest
  }
})

// Assignment Store
export const useAssignmentStore = defineStore('assignments', () => {
  const assignments = ref([])
  const myAssignments = ref([])
  const currentAssignment = ref(null)
  const loading = ref(false)

  async function fetchAssignments(params = {}) {
    loading.value = true
    try {
      const response = await api.get('/examination/assignments', { params })
      assignments.value = response.data.data.data
      return response.data
    } catch (error) {
      console.error('Error fetching assignments:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchMyAssignments(params = {}) {
    loading.value = true
    try {
      const response = await api.get('/examination/assignments/my', { params })
      myAssignments.value = response.data.data
      return response.data.data
    } catch (error) {
      console.error('Error fetching my assignments:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function createAssignment(data) {
    loading.value = true
    try {
      const response = await api.post('/examination/assignments', data)
      return response.data
    } catch (error) {
      console.error('Error creating assignment:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function assignToUsers(assignmentId, userIds) {
    try {
      const response = await api.post(`/examination/assignments/${assignmentId}/assign`, {
        user_ids: userIds
      })
      return response.data
    } catch (error) {
      console.error('Error assigning users:', error)
      throw error
    }
  }

  return {
    assignments,
    myAssignments,
    currentAssignment,
    loading,
    fetchAssignments,
    fetchMyAssignments,
    createAssignment,
    assignToUsers
  }
})

// Submission Store (Test Taking)
export const useSubmissionStore = defineStore('submission', () => {
  const currentSubmission = ref(null)
  const answers = ref({})
  const loading = ref(false)
  const saving = ref(false)
  const remainingTime = ref(null)
  const timerInterval = ref(null)

  async function startTest(assignmentId) {
    loading.value = true
    try {
      const response = await api.post('/examination/submissions/start', {
        assignment_id: assignmentId
      })
      currentSubmission.value = response.data.data

      // Initialize answers from saved data
      if (currentSubmission.value.questions) {
        currentSubmission.value.questions.forEach(q => {
          if (q.saved_answer) {
            answers.value[q.question_id] = q.saved_answer.answer || q.saved_answer.text_answer
          }
        })
      }

      // Start timer
      remainingTime.value = currentSubmission.value.remaining_time
      startTimer()

      return response.data.data
    } catch (error) {
      console.error('Error starting test:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function saveAnswer(questionId, answer) {
    saving.value = true
    try {
      const response = await api.post(`/examination/submissions/${currentSubmission.value.submission_id}/answer`, {
        question_id: questionId,
        answer: answer
      })
      answers.value[questionId] = answer
      return response.data
    } catch (error) {
      console.error('Error saving answer:', error)
      throw error
    } finally {
      saving.value = false
    }
  }

  async function saveTextAnswer(questionId, textAnswer) {
    saving.value = true
    try {
      const response = await api.post(`/examination/submissions/${currentSubmission.value.submission_id}/answer`, {
        question_id: questionId,
        text_answer: textAnswer
      })
      answers.value[questionId] = textAnswer
      return response.data
    } catch (error) {
      console.error('Error saving text answer:', error)
      throw error
    } finally {
      saving.value = false
    }
  }

  async function submitTest() {
    loading.value = true
    try {
      stopTimer()
      const response = await api.post(`/examination/submissions/${currentSubmission.value.submission_id}/submit`)
      return response.data
    } catch (error) {
      console.error('Error submitting test:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function getResult(submissionId) {
    loading.value = true
    try {
      const response = await api.get(`/examination/submissions/${submissionId}/result`)
      return response.data.data
    } catch (error) {
      console.error('Error getting result:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  function startTimer() {
    if (timerInterval.value) clearInterval(timerInterval.value)

    timerInterval.value = setInterval(() => {
      if (remainingTime.value > 0) {
        remainingTime.value--
      } else {
        // Auto submit when time runs out
        submitTest()
      }
    }, 1000)
  }

  function stopTimer() {
    if (timerInterval.value) {
      clearInterval(timerInterval.value)
      timerInterval.value = null
    }
  }

  function formatTime(seconds) {
    if (!seconds) return '00:00'
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  }

  const formattedRemainingTime = computed(() => formatTime(remainingTime.value))

  const answeredCount = computed(() => Object.keys(answers.value).length)

  function reset() {
    stopTimer()
    currentSubmission.value = null
    answers.value = {}
    remainingTime.value = null
  }

  return {
    currentSubmission,
    answers,
    loading,
    saving,
    remainingTime,
    formattedRemainingTime,
    answeredCount,
    startTest,
    saveAnswer,
    saveTextAnswer,
    submitTest,
    getResult,
    startTimer,
    stopTimer,
    reset
  }
})
