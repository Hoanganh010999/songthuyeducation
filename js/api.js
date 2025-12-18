import axios from 'axios';

// Configure axios defaults
const apiBaseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';
axios.defaults.baseURL = apiBaseURL;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
// DON'T set Content-Type globally - let axios auto-detect based on data type
// For JSON: axios sets 'application/json'
// For FormData: axios sets 'multipart/form-data' with boundary
// axios.defaults.headers.common['Content-Type'] = 'application/json';

// API wrapper
const api = {
  // Users
  users: {
    getAll(params = {}) {
      return axios.get('/api/users', { params });
    },
    getById(id) {
      return axios.get(`/api/users/${id}`);
    },
    getBranchEmployees() {
      return axios.get('/api/users/branch-employees');
    },
    create(data) {
      return axios.post('/api/users', data);
    },
    update(id, data) {
      return axios.put(`/api/users/${id}`, data);
    },
    delete(id) {
      return axios.delete(`/api/users/${id}`);
    }
  },

  // Classes
  classes: {
    getAll(params = {}) {
      return axios.get('/api/classes', { params });
    },
    getById(id) {
      return axios.get(`/api/classes/${id}`);
    },
    getDetail(id) {
      return axios.get(`/api/classes/${id}/detail`);
    },
    create(data) {
      return axios.post('/api/classes', data);
    },
    update(id, data) {
      return axios.put(`/api/classes/${id}`, data);
    },
    delete(id) {
      return axios.delete(`/api/classes/${id}`);
    },
    
    // Schedules
    getSchedules(id) {
      return axios.get(`/api/classes/${id}/schedules`);
    },
    getWeeklySchedule(id, params = {}) {
      return axios.get(`/api/classes/${id}/weekly-schedule`, { params });
    },
    createSchedule(id, data) {
      return axios.post(`/api/classes/${id}/schedules`, data);
    },
    updateSchedule(id, scheduleId, data) {
      return axios.put(`/api/classes/${id}/schedules/${scheduleId}`, data);
    },
    deleteSchedule(id, scheduleId) {
      return axios.delete(`/api/classes/${id}/schedules/${scheduleId}`);
    },
    
    // Lesson Sessions
    getLessonSessions(id, params = {}) {
      return axios.get(`/api/classes/${id}/lesson-sessions`, { params });
    },
    getSessionDetail(sessionId) {
      return axios.get(`/api/classes/sessions/${sessionId}/detail`);
    },
    generateSessions(id, data) {
      return axios.post(`/api/classes/${id}/generate-sessions`, data);
    },
    updateSession(id, sessionId, data) {
      return axios.put(`/api/classes/${id}/sessions/${sessionId}`, data);
    },
    syncFromSyllabus(id) {
      return axios.post(`/api/classes/${id}/sync-from-syllabus`);
    },
    syncFolderIds(id) {
      return axios.post(`/api/classes/${id}/sync-folder-ids`);
    },
    
    // Students
    getStudents(id) {
      return axios.get(`/api/classes/${id}/students`);
    },
    addStudent(id, data) {
      return axios.post(`/api/classes/${id}/students`, data);
    },
    updateStudent(id, studentId, data) {
      return axios.put(`/api/classes/${id}/students/${studentId}`, data);
    },
    removeStudent(id, studentId) {
      return axios.delete(`/api/classes/${id}/students/${studentId}`);
    },
    
    // Attendance
    markAttendance(sessionId, data) {
      return axios.post(`/api/classes/sessions/${sessionId}/attendance`, data);
    },
    generateEvaluationPdf(attendanceId) {
      return axios.post(`/api/classes/evaluations/generate-pdf`, { attendance_id: attendanceId });
    },
    viewEvaluationPdf(attendanceId) {
      return axios.get(`/api/classes/evaluations/${attendanceId}/pdf`);
    },
    
    // Homework
    submitHomework(sessionId, data) {
      return axios.post(`/api/classes/sessions/${sessionId}/homework`, data);
    },
    gradeHomework(homeworkId, data) {
      return axios.put(`/api/classes/homework/${homeworkId}/grade`, data);
    },
    
    // Comments
    addSessionComment(sessionId, data) {
      return axios.post(`/api/classes/sessions/${sessionId}/comments`, data);
    },
    
    // Overview
    getOverview(id) {
      return axios.get(`/api/classes/${id}/overview`);
    }
  },

  // Subjects
  subjects: {
    getAll(params = {}) {
      return axios.get('/api/subjects', { params });
    },
    getById(id) {
      return axios.get(`/api/subjects/${id}`);
    },
    create(data) {
      return axios.post('/api/subjects', data);
    },
    update(id, data) {
      return axios.put(`/api/subjects/${id}`, data);
    },
    delete(id) {
      return axios.delete(`/api/subjects/${id}`);
    }
  },

  // Quality Management
  quality: {
    getTeachers(params = {}) {
      return axios.get('/api/quality/teachers', { params });
    },
    getTeacherSettings() {
      return axios.get('/api/quality/teachers/settings');
    },
    saveTeacherSettings(data) {
      return axios.post('/api/quality/teachers/settings', data);
    }
  }
};

export default api;

