<template>
  <div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ t('course.learning_history') }}</h1>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6" v-if="!isStudent">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('course.select_student') }}</label>
          <select v-model="selectedStudent" @change="onStudentChange" class="w-full border-gray-300 rounded-lg">
            <option value="">{{ t('course.select_student') }}</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.full_name }} ({{ student.student_code }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('course.select_class') }}</label>
          <select v-model="selectedClass" @change="onClassChange" class="w-full border-gray-300 rounded-lg">
            <option value="">{{ t('course.all_classes') }}</option>
            <option v-for="cls in classes" :key="cls.id" :value="cls.id">
              {{ cls.name }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Class Filter for Students -->
    <div class="bg-white rounded-lg shadow p-4 mb-6" v-else>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('course.select_class') }}</label>
        <select v-model="selectedClass" @change="loadHistory" class="w-full border-gray-300 rounded-lg">
          <option value="">{{ t('course.all_classes') }}</option>
          <option v-for="cls in classes" :key="cls.id" :value="cls.id">
            {{ cls.name }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="!selectedStudent && !selectedClass" class="bg-white rounded-lg shadow p-12 text-center">
      <p class="text-gray-600">{{ isSuperAdmin ? t('course.select_student_or_class') : t('course.select_student') }}</p>
    </div>

    <div v-else>
      <!-- Statistics Cards -->
      <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 mb-1">
            {{ !selectedStudent ? t('course.total_students') : t('course.total_sessions') }}
          </div>
          <div class="text-2xl font-bold text-gray-900">
            {{ !selectedStudent ? (history.stats?.total_students || 0) : (history.stats?.total_sessions || 0) }}
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 mb-1">{{ t('course.present') }}</div>
          <div class="text-2xl font-bold text-green-600">{{ history.stats?.present_count || 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 mb-1">{{ t('course.absent') }}</div>
          <div class="text-2xl font-bold text-red-600">{{ history.stats?.absent_count || 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 mb-1">{{ t('common.average') }} {{ t('course.score') }}</div>
          <div class="text-2xl font-bold text-blue-600">{{ history.stats?.average_score?.toFixed(1) || 'N/A' }}</div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-white rounded-lg shadow">
        <div class="border-b border-gray-200">
          <nav class="flex space-x-8 px-6">
            <button
              @click="activeTab = 'attendance'"
              :class="activeTab === 'attendance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              class="py-4 px-1 border-b-2 font-medium text-sm"
            >
              {{ t('course.attendance_history') }}
            </button>
            <button
              @click="activeTab = 'assignments'"
              :class="activeTab === 'assignments' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              class="py-4 px-1 border-b-2 font-medium text-sm"
            >
              {{ t('course.assignments_history') }}
            </button>
            <button
              @click="activeTab = 'evaluations'"
              :class="activeTab === 'evaluations' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              class="py-4 px-1 border-b-2 font-medium text-sm"
            >
              {{ t('course.evaluations') }}
            </button>
          </nav>
        </div>

        <div class="p-6">
          <!-- Attendance Tab -->
          <div v-if="activeTab === 'attendance'">
            <div v-if="history.attendances?.length === 0" class="text-center py-8 text-gray-500">
              {{ t('course.no_data') }}
            </div>
            <div v-else class="space-y-4">
              <div v-for="att in history.attendances" :key="att.id" class="bg-gray-50 rounded-lg p-4">
                <!-- Header: Student Name (for class view), Class and Date -->
                <div class="flex items-center justify-between mb-3">
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">
                      <span v-if="!selectedStudent && att.student">{{ att.student.name }} - </span>
                      {{ att.session?.class?.name || 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-600">
                      {{ t('class_detail.session') }} {{ att.session?.session_number || '' }} - 
                      {{ formatDate(att.session?.scheduled_date) }}
                    </div>
                  </div>
                  <span
                    :class="{
                      'bg-green-100 text-green-800': att.status === 'present',
                      'bg-red-100 text-red-800': att.status === 'absent',
                      'bg-yellow-100 text-yellow-800': att.status === 'late',
                      'bg-blue-100 text-blue-800': att.status === 'excused'
                    }"
                    class="px-3 py-1 rounded-full text-xs font-medium"
                  >
                    {{ getAttendanceStatus(att) }}
                  </span>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-3 gap-4 mt-3 pt-3 border-t border-gray-200">
                  <!-- Check-in Time -->
                  <div>
                    <div class="text-xs text-gray-500 mb-1">{{ t('class_detail.check_in_time') }}</div>
                    <div class="font-medium text-gray-900">
                      {{ formatTime(att.check_in_time) }}
                    </div>
                  </div>

                  <!-- Homework Score -->
                  <div>
                    <div class="text-xs text-gray-500 mb-1">{{ t('class_detail.homework_score') }}</div>
                    <div class="font-medium text-gray-900">
                      <span v-if="att.homework_score" class="text-blue-600">{{ att.homework_score }}/10</span>
                      <span v-else class="text-gray-400">N/A</span>
                    </div>
                  </div>

                  <!-- Participation Score -->
                  <div>
                    <div class="text-xs text-gray-500 mb-1">{{ t('class_detail.participation_level') }}</div>
                    <div class="font-medium text-gray-900">
                      <span v-if="att.participation_score">
                        <span v-if="att.participation_score === 5" class="text-green-600">{{ t('class_detail.excellent') }}</span>
                        <span v-else-if="att.participation_score === 4" class="text-blue-600">{{ t('class_detail.good') }}</span>
                        <span v-else-if="att.participation_score === 3" class="text-yellow-600">{{ t('class_detail.average') }}</span>
                        <span v-else-if="att.participation_score === 2" class="text-orange-600">{{ t('class_detail.below_average') }}</span>
                        <span v-else class="text-red-600">{{ t('class_detail.poor') }}</span>
                      </span>
                      <span v-else class="text-gray-400">N/A</span>
                    </div>
                  </div>
                </div>

                <!-- Notes -->
                <div v-if="att.notes" class="mt-3 pt-3 border-t border-gray-200">
                  <div class="text-xs text-gray-500 mb-1">{{ t('common.notes') }}</div>
                  <div class="text-sm text-gray-700">{{ att.notes }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Assignments Tab -->
          <div v-if="activeTab === 'assignments'">
            <div v-if="history.submissions?.length === 0" class="text-center py-8 text-gray-500">
              {{ t('course.no_data') }}
            </div>
            <div v-else class="space-y-3">
              <div v-for="sub in history.submissions" :key="sub.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex-1">
                  <div class="font-medium text-gray-900">
                    <span v-if="!selectedStudent && sub.student?.user">{{ sub.student.user.name }} - </span>
                    {{ sub.assignment?.title }}
                  </div>
                  <div class="text-sm text-gray-600">{{ formatDate(sub.submitted_at) }}</div>
                </div>
                <div class="flex items-center space-x-3">
                  <span
                    :class="{
                      'bg-green-100 text-green-800': sub.status === 'graded',
                      'bg-blue-100 text-blue-800': sub.status === 'submitted',
                      'bg-gray-100 text-gray-800': sub.status === 'draft'
                    }"
                    class="px-3 py-1 rounded-full text-xs font-medium"
                  >
                    {{ t(`course.${sub.status}`) }}
                  </span>
                  <span v-if="sub.score !== null" class="font-bold text-blue-600">{{ sub.score }}/{{ sub.assignment?.max_score }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Evaluations Tab -->
          <div v-if="activeTab === 'evaluations'">
            <div v-if="history.evaluations?.length === 0" class="text-center py-8 text-gray-500">
              {{ t('course.no_data') }}
            </div>
            <div v-else class="space-y-3">
              <div v-for="evaluation in history.evaluations" :key="evaluation.id" class="p-4 bg-gray-50 rounded-lg">
                <div class="font-medium text-gray-900 mb-2">
                  <span v-if="!selectedStudent && evaluation.student">{{ evaluation.student.name }} - </span>
                  {{ evaluation.session?.class?.name || 'N/A' }}
                </div>
                <div class="text-sm text-gray-600">
                  {{ t('class_detail.session') }} {{ evaluation.session?.session_number || '' }} - 
                  {{ formatDate(evaluation.created_at) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';

const { t } = useI18n();
const authStore = useAuthStore();

const loading = ref(false);
const students = ref([]);
const classes = ref([]);
const selectedStudent = ref('');
const selectedClass = ref('');
const history = ref({});
const activeTab = ref('attendance');
const initialized = ref(false);

// Check if current user is a student
const isStudent = computed(() => {
  if (!authStore.currentUser) return false;
  return authStore.currentUser.roles?.some(role => role.name === 'student') || false;
});

// Check if current user is a parent
const isParent = computed(() => {
  if (!authStore.currentUser) return false;
  return authStore.currentUser.roles?.some(role => role.name === 'parent') || false;
});

// Check if current user is super admin
const isSuperAdmin = computed(() => {
  if (!authStore.currentUser) return false;
  return authStore.currentUser.roles?.some(role => role.name === 'super-admin') || false;
});

// Get current user's student ID and classes
const getCurrentStudentInfo = async () => {
  try {
    // Get student record for current user using course module endpoint
    const response = await axios.get('/api/course/my-student-info');
    if (response.data.success && response.data.data) {
      const studentData = response.data.data;
      // Also load classes from the response
      classes.value = studentData.classes || [];
      return studentData.id;
    }
    return null;
  } catch (error) {
    console.error('Error getting student info:', error);
    return null;
  }
};

const loadStudents = async () => {
  try {
    // If user is a parent, load only their children
    if (isParent.value) {
      const response = await axios.get('/api/course/my-children');
      console.log('[LearningHistory] Parent children response:', response.data);
      students.value = response.data.data || [];
      console.log('[LearningHistory] Loaded parent\'s children:', students.value.length);
    } else {
      // For teachers/admin, load all students
      const response = await axios.get('/api/quality/students', {
        params: { per_page: 1000 }
      });
      console.log('[LearningHistory] All students response:', response.data);
      
      // Check if response has paginated data (Laravel pagination format)
      if (response.data.success && response.data.data && Array.isArray(response.data.data.data)) {
        students.value = response.data.data.data; // Paginated response
      } else if (Array.isArray(response.data.data)) {
        students.value = response.data.data;
      } else if (Array.isArray(response.data)) {
        students.value = response.data;
      } else {
        students.value = [];
        console.warn('[LearningHistory] Unexpected response format:', response.data);
      }
      
      console.log('[LearningHistory] Loaded all students:', students.value.length);
    }
  } catch (error) {
    console.error('[LearningHistory] Error loading students:', error);
    students.value = [];
  }
};

const loadAllClasses = async () => {
  try {
    const response = await axios.get('/api/quality/classes', {
      params: { per_page: 1000 }
    });
    console.log('[LearningHistory] All classes response:', response.data);
    
    // Check if response has data array directly or nested
    if (Array.isArray(response.data.data)) {
      classes.value = response.data.data;
    } else if (Array.isArray(response.data)) {
      classes.value = response.data;
    } else {
      classes.value = [];
      console.warn('[LearningHistory] Unexpected classes response format:', response.data);
    }
    
    console.log('[LearningHistory] Loaded all classes:', classes.value.length);
  } catch (error) {
    console.error('[LearningHistory] Error loading classes:', error);
    classes.value = [];
  }
};

const loadClasses = async (studentId) => {
  try {
    // Use course module API which handles permission checks internally
    const response = await axios.get(`/api/course/students/${studentId}/classes`);
    console.log('[LearningHistory] Student classes response:', response.data);
    
    // Check if response has data array directly or nested
    if (Array.isArray(response.data.data)) {
      classes.value = response.data.data;
    } else if (Array.isArray(response.data)) {
      classes.value = response.data;
    } else {
      classes.value = [];
      console.warn('[LearningHistory] Unexpected student classes response format:', response.data);
    }
    
    console.log('[LearningHistory] Loaded classes for student:', studentId, classes.value.length);
  } catch (error) {
    console.error('[LearningHistory] Error loading classes:', error);
    classes.value = [];
  }
};

const onStudentChange = async () => {
  // Don't reset class for super admin to allow switching between student and class view
  if (!isSuperAdmin.value) {
    selectedClass.value = '';
    classes.value = [];
  }
  history.value = {};
  
  // Load new student's data
  if (selectedStudent.value) {
    await loadHistory();
  } else if (selectedClass.value) {
    // If student is cleared but class is still selected, load class history
    await loadClassHistory();
  }
};

const onClassChange = async () => {
  // If class is selected
  if (selectedClass.value) {
    if (selectedStudent.value) {
      // If both class and student are selected, load student history
      await loadHistory();
    } else {
      // If only class is selected (no student), load entire class history
      await loadClassHistory();
    }
  } else {
    // If no class is selected, reset history
    history.value = {};
  }
};

const loadHistory = async () => {
  if (!selectedStudent.value) return;

  // Load classes for the selected student if not already loaded
  if (classes.value.length === 0 && !isSuperAdmin.value) {
    await loadClasses(selectedStudent.value);
  }

  loading.value = true;
  try {
    const response = await axios.get(`/api/course/learning-history/students/${selectedStudent.value}`, {
      params: { class_id: selectedClass.value || undefined }
    });
    history.value = response.data;
  } catch (error) {
    console.error('Error loading history:', error);
  } finally {
    loading.value = false;
  }
};

const loadClassHistory = async () => {
  if (!selectedClass.value) return;

  loading.value = true;
  try {
    const response = await axios.get(`/api/course/learning-history/classes/${selectedClass.value}`);
    history.value = response.data;
  } catch (error) {
    console.error('Error loading class history:', error);
  } finally {
    loading.value = false;
  }
};

const getAttendanceStatus = (att) => {
  if (att.status === 'excused') return t('course.excused');
  if (att.status === 'present') return t('course.present');
  if (att.status === 'absent') return t('course.absent');
  if (att.status === 'late') return t('course.late');
  return att.status;
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  try {
    return new Date(date).toLocaleDateString('vi-VN');
  } catch (e) {
    return 'N/A';
  }
};

const formatTime = (time) => {
  if (!time) return 'N/A';
  try {
    // If time is already in HH:MM format
    if (typeof time === 'string' && time.match(/^\d{2}:\d{2}/)) {
      return time.substring(0, 5);
    }
    // If it's a datetime
    const date = new Date(time);
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
  } catch (e) {
    return 'N/A';
  }
};

const initializeData = async () => {
  if (initialized.value || !authStore.currentUser) {
    console.log('[LearningHistory] Skipping initialization:', { 
      initialized: initialized.value, 
      hasUser: !!authStore.currentUser 
    });
    return;
  }
  
  console.log('[LearningHistory] Starting initialization...', {
    isStudent: isStudent.value,
    isParent: isParent.value,
    isSuperAdmin: isSuperAdmin.value
  });
  
  initialized.value = true;
  
  if (isStudent.value) {
    // If user is a student, auto-load their own data
    const studentId = await getCurrentStudentInfo();
    if (studentId) {
      selectedStudent.value = studentId;
      // Classes already loaded in getCurrentStudentInfo
      await loadHistory();
    } else {
      console.warn('[LearningHistory] Current user is a student but no student record found');
    }
  } else {
    // If user is not a student (parent/teacher/admin), load students list
    await loadStudents();
    
    // If super admin, also load all classes
    if (isSuperAdmin.value) {
      await loadAllClasses();
    }
  }
  
  console.log('[LearningHistory] Initialization complete');
};

// Watch for currentUser to be loaded
watch(() => authStore.currentUser, (newUser) => {
  if (newUser && !initialized.value) {
    initializeData();
  }
}, { immediate: true });

onMounted(() => {
  // Try to initialize immediately if user is already loaded
  if (authStore.currentUser) {
    initializeData();
  }
});
</script>

