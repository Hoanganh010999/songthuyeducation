import api from '@/api'

const examinationApi = {
  // Question Types
  getQuestionTypes() {
    return api.get('/examination/question-types')
  },

  getTestTypes() {
    return api.get('/examination/test-types')
  },

  // Questions
  questions: {
    list(params = {}) {
      return api.get('/examination/questions', { params })
    },
    get(id) {
      return api.get(`/examination/questions/${id}`)
    },
    create(data) {
      return api.post('/examination/questions', data)
    },
    update(id, data) {
      return api.put(`/examination/questions/${id}`, data)
    },
    delete(id) {
      return api.delete(`/examination/questions/${id}`)
    },
    duplicate(id) {
      return api.post(`/examination/questions/${id}/duplicate`)
    },
    import(data) {
      return api.post('/examination/questions/import', data)
    }
  },

  // Tests
  tests: {
    list(params = {}) {
      return api.get('/examination/tests', { params })
    },
    get(id) {
      return api.get(`/examination/tests/${id}`)
    },
    create(data) {
      return api.post('/examination/tests', data)
    },
    update(id, data) {
      return api.put(`/examination/tests/${id}`, data)
    },
    delete(id) {
      return api.delete(`/examination/tests/${id}`)
    },
    duplicate(id) {
      return api.post(`/examination/tests/${id}/duplicate`)
    },
    preview(id) {
      return api.get(`/examination/tests/${id}/preview`)
    },
    addSection(testId, data) {
      return api.post(`/examination/tests/${testId}/sections`, data)
    },
    addQuestions(testId, questions, sectionId = null) {
      return api.post(`/examination/tests/${testId}/questions`, { questions, section_id: sectionId })
    },
    removeQuestion(testId, questionId) {
      return api.delete(`/examination/tests/${testId}/questions/${questionId}`)
    },
    reorderQuestions(testId, questions) {
      return api.put(`/examination/tests/${testId}/questions/reorder`, { questions })
    }
  },

  // Assignments
  assignments: {
    list(params = {}) {
      return api.get('/examination/assignments', { params })
    },
    my(params = {}) {
      return api.get('/examination/assignments/my', { params })
    },
    get(id) {
      return api.get(`/examination/assignments/${id}`)
    },
    create(data) {
      return api.post('/examination/assignments', data)
    },
    update(id, data) {
      return api.put(`/examination/assignments/${id}`, data)
    },
    delete(id) {
      return api.delete(`/examination/assignments/${id}`)
    },
    assign(id, userIds) {
      return api.post(`/examination/assignments/${id}/assign`, { user_ids: userIds })
    },
    submissions(id, params = {}) {
      return api.get(`/examination/assignments/${id}/submissions`, { params })
    },
    statistics(id) {
      return api.get(`/examination/assignments/${id}/statistics`)
    }
  },

  // Submissions (Test Taking)
  submissions: {
    start(assignmentId) {
      return api.post('/examination/submissions/start', { assignment_id: assignmentId })
    },
    saveAnswer(submissionId, questionId, answer) {
      return api.post(`/examination/submissions/${submissionId}/answer`, {
        question_id: questionId,
        answer: answer
      })
    },
    saveTextAnswer(submissionId, questionId, textAnswer) {
      return api.post(`/examination/submissions/${submissionId}/answer`, {
        question_id: questionId,
        text_answer: textAnswer
      })
    },
    saveAudioResponse(submissionId, questionId, audioFile, duration) {
      const formData = new FormData()
      formData.append('question_id', questionId)
      formData.append('audio', audioFile)
      formData.append('duration', duration)
      return api.post(`/examination/submissions/${submissionId}/audio-response`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    },
    submit(submissionId) {
      return api.post(`/examination/submissions/${submissionId}/submit`)
    },
    result(submissionId) {
      return api.get(`/examination/submissions/${submissionId}/result`)
    },
    remainingTime(submissionId) {
      return api.get(`/examination/submissions/${submissionId}/remaining-time`)
    },
    logActivity(submissionId, action, data = {}) {
      return api.post(`/examination/submissions/${submissionId}/activity`, { action, data })
    },
    gradeAnswer(submissionId, answerId, points, feedback = null, criteria = null) {
      return api.post(`/examination/submissions/${submissionId}/answers/${answerId}/grade`, {
        points,
        feedback,
        grading_criteria: criteria
      })
    },
    addFeedback(submissionId, feedback) {
      return api.post(`/examination/submissions/${submissionId}/feedback`, { feedback })
    }
  }
}

export default examinationApi
