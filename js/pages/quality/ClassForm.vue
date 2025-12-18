<template>
  <form @submit.prevent="save" class="space-y-6">
    <!-- Basic Info -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.name') }} *</label>
        <input v-model="form.name" type="text" required class="w-full px-3 py-2 border rounded-lg" :placeholder="t('classes.name')"/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.code') }}</label>
        <input v-model="form.code" type="text" class="w-full px-3 py-2 border rounded-lg" :placeholder="t('classes.code')"/>
      </div>
    </div>

    <!-- Subject & Homeroom Teacher -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.subject') }} *</label>
        <select v-model="form.subject_id" required class="w-full px-3 py-2 border rounded-lg">
          <option value="">-- {{ t('common.select') }} --</option>
          <option v-for="subject in subjects" :key="subject?.id || subject" :value="subject.id">{{ subject?.name || 'N/A' }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.homeroom_teacher') }} *</label>
        <select v-model="form.homeroom_teacher_id" required class="w-full px-3 py-2 border rounded-lg" :disabled="!form.subject_id">
          <option value="">-- {{ t('common.select') }} --</option>
          <option v-for="teacher in subjectTeachers" :key="teacher?.id || teacher" :value="teacher.id">{{ teacher?.name || 'N/A' }}</option>
        </select>
        <p v-if="!form.subject_id" class="text-xs text-gray-500 mt-1">{{ t('classes.select_subject_first') }}</p>
      </div>
    </div>

    <!-- Semester & Start Date -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.semester') }}</label>
        <select v-model="form.semester_id" class="w-full px-3 py-2 border rounded-lg">
          <option value="">-- {{ t('common.none') }} --</option>
          <option v-for="semester in semesters" :key="semester?.id || semester" :value="semester.id">{{ semester?.name || 'N/A' }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.start_date') }} *</label>
        <input v-model="form.start_date" type="date" required class="w-full px-3 py-2 border rounded-lg" />
      </div>
    </div>

    <!-- Hourly Rate -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.hourly_rate') }}</label>
        <input v-model.number="form.hourly_rate" type="number" min="0" step="1000" class="w-full px-3 py-2 border rounded-lg" placeholder="100000" />
      </div>
      <div></div>
    </div>

    <!-- Lesson Plan & Total Sessions -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.lesson_plan') }}</label>
        <select v-model="form.lesson_plan_id" class="w-full px-3 py-2 border rounded-lg" @change="onSyllabusChange">
          <option value="">-- {{ t('common.none') }} --</option>
          <option v-for="syllabus in syllabuses" :key="syllabus.id" :value="syllabus.id">
            {{ syllabus.name }} ({{ syllabus.total_sessions }} {{ t('classes.sessions') || 'sessions' }})
          </option>
        </select>
        <p v-if="form.lesson_plan_id" class="text-xs text-green-600 mt-1">✓ {{ t('classes.syllabus_will_apply') || 'Syllabus will be applied' }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.total_sessions') }} *</label>
        <input 
          v-model.number="form.total_sessions" 
          type="number" 
          min="1" 
          required 
          class="w-full px-3 py-2 border rounded-lg" 
          placeholder="30"
          :disabled="!!form.lesson_plan_id"
        />
        <p v-if="form.lesson_plan_id" class="text-xs text-gray-500 mt-1">{{ t('classes.sessions_from_syllabus') || 'Sessions from syllabus' }}</p>
      </div>
    </div>

    <!-- Room & Status -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.room') }}</label>
        <select v-model="form.room_id" class="w-full px-3 py-2 border rounded-lg">
          <option value="">-- {{ t('common.none') }} --</option>
          <option v-for="room in rooms" :key="room?.id || room" :value="room.id">{{ room?.name || 'N/A' }} ({{ room?.capacity || 0 }})</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('classes.status') }}</label>
        <select v-model="form.status" class="w-full px-3 py-2 border rounded-lg">
          <option value="draft">{{ t('classes.status_draft') }}</option>
          <option value="active">{{ t('classes.status_active') }}</option>
          <option value="paused">{{ t('classes.status_paused') }}</option>
          <option value="completed">{{ t('classes.status_completed') }}</option>
          <option value="cancelled">{{ t('classes.status_cancelled') }}</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">
          <span v-if="form.status === 'draft'">📝 {{ t('classes.status_draft_hint') }}</span>
          <span v-if="form.status === 'active'">✅ {{ t('classes.status_active_hint') }}</span>
          <span v-if="form.status === 'paused'">⏸️ {{ t('classes.status_paused_hint') }}</span>
          <span v-if="form.status === 'completed'">🎓 {{ t('classes.status_completed_hint') }}</span>
          <span v-if="form.status === 'cancelled'">❌ {{ t('classes.status_cancelled_hint') }}</span>
        </p>
      </div>
    </div>

    <!-- Weekly Schedule Section -->
    <div class="border-t pt-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ t('classes.schedule') }}</h3>
        </div>
        <button type="button" @click="addSchedule" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700" :disabled="!form.subject_id">
          + {{ t('classes.add_schedule') }}
        </button>
      </div>
      
      <div v-if="!form.subject_id" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-4">
        <p class="text-sm text-yellow-800">⚠️ {{ t('classes.select_subject_first') }}</p>
      </div>
      
      <div v-if="form.schedules.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
        <p class="text-gray-500">{{ t('classes.no_schedules') }}</p>
      </div>

      <div v-else class="space-y-3">
        <div v-for="(schedule, index) in form.schedules" :key="index" class="p-4 border rounded-lg bg-gray-50">
          <div class="grid grid-cols-5 gap-3">
            <div>
              <label class="block text-xs text-gray-600 mb-1">{{ t('classes.day_of_week') }}</label>
              <select v-model="schedule.day_of_week" required class="w-full px-2 py-1 text-sm border rounded">
                <option value="2">{{ t('class_detail.monday') }}</option>
                <option value="3">{{ t('class_detail.tuesday') }}</option>
                <option value="4">{{ t('class_detail.wednesday') }}</option>
                <option value="5">{{ t('class_detail.thursday') }}</option>
                <option value="6">{{ t('class_detail.friday') }}</option>
                <option value="7">{{ t('class_detail.saturday') }}</option>
                <option value="8">{{ t('class_detail.sunday') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">{{ t('classes.study_period') }}</label>
              <select v-model="schedule.study_period_id" required class="w-full px-2 py-1 text-sm border rounded">
                <option value="">-- {{ t('common.select') }} --</option>
                <option v-for="period in studyPeriods" :key="period?.id || period" :value="period.id">{{ period?.name || 'N/A' }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">{{ t('class_detail.start_time') }}</label>
              <input v-model="schedule.start_time" type="time" required class="w-full px-2 py-1 text-sm border rounded"/>
              <p v-if="schedule.end_time" class="text-xs text-gray-500 mt-1">{{ t('class_detail.end_time') }}: {{ schedule.end_time }}</p>
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">{{ t('classes.teacher') }}</label>
              <select v-model="schedule.teacher_id" required class="w-full px-2 py-1 text-sm border rounded" @change="checkConflict(schedule)" :disabled="!form.subject_id">
                <option value="">-- {{ t('common.select') }} --</option>
                <option v-for="teacher in subjectTeachers" :key="teacher?.id || teacher" :value="teacher.id">{{ teacher?.name || 'N/A' }}</option>
              </select>
              <p v-if="schedule.conflict" class="text-xs text-red-600 mt-1">⚠️ {{ t('classes.teacher_conflict') }}</p>
            </div>
            <div></div>
            <div class="flex items-end">
              <button type="button" @click="removeSchedule(index)" class="px-2 py-1 text-sm text-red-600 hover:bg-red-50 rounded">{{ t('common.delete') }}</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end space-x-3 pt-4 border-t">
      <button type="button" @click="$emit('cancel')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">{{ t('common.cancel') }}</button>
      <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
        {{ saving ? t('common.saving') : t('common.save') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();

const props = defineProps({
  classData: Object
});

const emit = defineEmits(['saved', 'cancel']);

const form = ref({
  name: '',
  code: '',
  subject_id: '',
  homeroom_teacher_id: '',
  semester_id: '',
  start_date: '',
  hourly_rate: null,
  lesson_plan_id: null,
  total_sessions: 30,
  room_id: '',
  status: 'draft',
  schedules: []
});

const saving = ref(false);
const allTeachers = ref([]); // All teachers for GVCN selection
const semesters = ref([]);
const subjects = ref([]);
const syllabuses = ref([]);
const rooms = ref([]);
const studyPeriods = ref([]);

// Computed: Teachers filtered by selected subject
const subjectTeachers = computed(() => {
  if (!form.value.subject_id || !Array.isArray(subjects.value)) return [];
  
  const selectedSubject = subjects.value.find(s => s && s.id === parseInt(form.value.subject_id));
  if (!selectedSubject) return [];
  
  // Return teachers from the subject (head teacher + assigned teachers)
  const teacherIds = [
    ...(selectedSubject.head_teacher_id ? [selectedSubject.head_teacher_id] : []),
    ...(Array.isArray(selectedSubject.teachers) ? selectedSubject.teachers.filter(t => t && t.id).map(t => t.id) : [])
  ];
  
  return Array.isArray(allTeachers.value) 
    ? allTeachers.value.filter(t => t && t.id && teacherIds.includes(t.id)) 
    : [];
});

const loadData = async () => {
  const branchId = localStorage.getItem('current_branch_id');
  console.log('📊 Loading class form data for branch:', branchId);
  
  try {
    const [usersRes, semestersRes, subjectsRes, syllabusesRes, roomsRes, periodsRes] = await Promise.all([
      axios.get('/api/users/branch-employees', { params: { branch_id: branchId } }), // Load branch users for GVCN dropdown
      axios.get('/api/class-settings/semesters', { params: { branch_id: branchId } }),
      axios.get('/api/quality/subjects', { params: { branch_id: branchId } }),
      axios.get('/api/lesson-plans', { params: { branch_id: branchId, status: 'in_use' } }),
      axios.get('/api/class-settings/rooms', { params: { branch_id: branchId } }),
      axios.get('/api/class-settings/study-periods', { params: { branch_id: branchId } })
    ]);
    
    console.log('👥 Users response:', usersRes.data);
    console.log('📚 Subjects response:', subjectsRes.data);
    console.log('📝 Syllabuses response:', syllabusesRes.data);
    
    // branch-employees returns: { success: true, data: [...] }
    allTeachers.value = Array.isArray(usersRes.data.data) 
      ? usersRes.data.data.filter(t => t && t.id) 
      : [];
    semesters.value = Array.isArray(semestersRes.data.data) ? semestersRes.data.data : [];
    subjects.value = Array.isArray(subjectsRes.data.data) ? subjectsRes.data.data : [];
    syllabuses.value = Array.isArray(syllabusesRes.data.data) ? syllabusesRes.data.data : [];
    rooms.value = Array.isArray(roomsRes.data.data) ? roomsRes.data.data : [];
    studyPeriods.value = Array.isArray(periodsRes.data.data) ? periodsRes.data.data : [];
    
    console.log('✅ Loaded:', {
      teachers: allTeachers.value.length,
      subjects: subjects.value.length,
      semesters: semesters.value.length,
      rooms: rooms.value.length,
      periods: studyPeriods.value.length
    });
  } catch (error) {
    console.error('❌ Load data error:', error);
    // Set defaults on error
    allTeachers.value = [];
    semesters.value = [];
    subjects.value = [];
    rooms.value = [];
    studyPeriods.value = [];
  }
};

const onSyllabusChange = () => {
  if (form.value.lesson_plan_id) {
    const selectedSyllabus = syllabuses.value.find(s => s.id === parseInt(form.value.lesson_plan_id));
    if (selectedSyllabus) {
      form.value.total_sessions = selectedSyllabus.total_sessions;
    }
  }
};

const addSchedule = () => {
  form.value.schedules.push({
    day_of_week: '2',
    study_period_id: '',
    start_time: '07:00',
    end_time: '',
    teacher_id: '',
    room_id: form.value.room_id || '',
    conflict: false
  });
};

const removeSchedule = (index) => {
  form.value.schedules.splice(index, 1);
};

const checkConflict = async (schedule) => {
  // Simple conflict check (GVCN has no restrictions)
  if (schedule.teacher_id === form.value.homeroom_teacher_id) {
    schedule.conflict = false;
    return;
  }

  // Check if teacher has another class at the same time
  const conflicts = form.value.schedules.filter(s => 
    s !== schedule &&
    s.teacher_id === schedule.teacher_id &&
    s.day_of_week === schedule.day_of_week &&
    s.start_time === schedule.start_time
  );

  schedule.conflict = conflicts.length > 0;
};

// Auto-calculate end_time when study_period or start_time changes
watch(() => form.value.schedules, (schedules) => {
  schedules.forEach(schedule => {
    if (schedule.study_period_id && schedule.start_time) {
      const period = studyPeriods.value.find(p => p.id === parseInt(schedule.study_period_id));
      if (period) {
        const [hours, minutes] = schedule.start_time.split(':').map(Number);
        const totalMinutes = hours * 60 + minutes + period.duration_minutes;
        const endHours = Math.floor(totalMinutes / 60);
        const endMinutes = totalMinutes % 60;
        schedule.end_time = `${String(endHours).padStart(2, '0')}:${String(endMinutes).padStart(2, '0')}`;
      }
    }
  });
}, { deep: true });

// Check if schedules have changed
const checkScheduleChanges = () => {
  if (!props.classData || !originalSchedules.value.length) {
    return false; // New class, no changes to check
  }
  
  const current = form.value.schedules || [];
  const original = originalSchedules.value || [];
  
  // Check if count changed
  if (current.length !== original.length) return true;
  
  // Check if any schedule details changed
  for (let i = 0; i < current.length; i++) {
    const curr = current[i];
    const orig = original[i];
    
    if (curr.day_of_week !== orig.day_of_week ||
        curr.start_time !== orig.start_time ||
        curr.study_period_id !== orig.study_period_id) {
      return true;
    }
  }
  
  return false;
};

const save = async () => {
  saving.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const data = { 
      ...form.value, 
      branch_id: branchId,
      semester_id: form.value.semester_id || null // Convert empty string to null
    };
    
    console.log('💾 Saving class data:', data);

    // Check if editing and schedules changed
    if (props.classData && checkScheduleChanges()) {
      const result = await Swal.fire({
        icon: 'warning',
        title: t('common.warning'),
        html: `
          <p class="text-gray-700">${t('classes.schedule_change_warning')}</p>
          <p class="text-gray-700 mt-2">${t('classes.schedule_recalculate_note')}</p>
          <p class="text-red-600 mt-2 font-semibold">⚠️ ${t('classes.completed_sessions_safe')}</p>
        `,
        showCancelButton: true,
        confirmButtonText: t('common.continue'),
        cancelButtonText: t('common.cancel'),
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
      });
      
      if (!result.isConfirmed) {
        saving.value = false;
        return;
      }
    }

    if (props.classData) {
      const response = await axios.put(`/api/classes/${props.classData.id}`, data);
      
      // Check folder copy status
      if (response.data.folder_copy?.error) {
        await Swal.fire({
          icon: 'warning',
          title: t('common.success'),
          html: `
            <p>${t('classes.class_updated')}</p>
            <hr class="my-3">
            <p class="text-sm text-orange-600">${t('classes.folder_copy_warning')}</p>
            <p class="text-xs text-gray-600 mt-2">${response.data.folder_copy.message}</p>
          `,
          confirmButtonText: t('common.ok'),
        });
      } else {
        await Swal.fire(t('common.success'), t('classes.class_updated'), 'success');
      }
    } else {
      const response = await axios.post('/api/classes', data);
      
      // Check folder copy status
      if (response.data.folder_copy?.error) {
        await Swal.fire({
          icon: 'warning',
          title: t('common.success'),
          html: `
            <p>${t('classes.class_created')}</p>
            <hr class="my-3">
            <p class="text-sm text-orange-600">${t('classes.folder_copy_warning')}</p>
            <p class="text-xs text-gray-600 mt-2">${response.data.folder_copy.message}</p>
          `,
          confirmButtonText: t('common.ok'),
        });
      } else {
        await Swal.fire(t('common.success'), t('classes.class_created'), 'success');
      }
    }

    emit('saved');
  } catch (error) {
    console.error('❌ Save class error:', error);
    console.error('❌ Error response:', error.response?.data);
    
    // Show validation errors if available
    let errorMessage = t('common.error_occurred');
    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat();
      errorMessage = errors.join('\n');
    } else if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    }
    
    Swal.fire(t('common.error'), errorMessage, 'error');
  } finally {
    saving.value = false;
  }
};

// Map day_of_week from string to number
const dayMap = {
  'monday': '2',
  'tuesday': '3',
  'wednesday': '4',
  'thursday': '5',
  'friday': '6',
  'saturday': '7',
  'sunday': '8'
};

const reverseDayMap = {
  '2': 'monday',
  '3': 'tuesday',
  '4': 'wednesday',
  '5': 'thursday',
  '6': 'friday',
  '7': 'saturday',
  '8': 'sunday'
};

// Track original schedules for comparison
const originalSchedules = ref([]);

onMounted(() => {
  loadData();
  if (props.classData) {
    const classData = { ...props.classData };
    
    // Map schedules day_of_week from string to number for editing
    if (classData.schedules && Array.isArray(classData.schedules)) {
      classData.schedules = classData.schedules.map(schedule => ({
        ...schedule,
        day_of_week: dayMap[schedule.day_of_week] || schedule.day_of_week,
        start_time: schedule.start_time?.substring(0, 5) || schedule.start_time,
        end_time: schedule.end_time?.substring(0, 5) || schedule.end_time
      }));
      
      // Save original schedules for comparison
      originalSchedules.value = JSON.parse(JSON.stringify(classData.schedules));
    }
    
    form.value = classData;
  }
});
</script>

