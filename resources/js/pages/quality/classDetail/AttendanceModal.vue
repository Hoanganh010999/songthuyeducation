<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ t('class_detail.mark_attendance') }}</h3>
          <p class="text-sm text-gray-500 mt-1">
            {{ session?.lesson_plan_session?.title || 'Session' }} - {{ formatDate(session?.scheduled_date) }}
          </p>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 140px);">
        <div class="space-y-4">
          <div
            v-for="(student, index) in attendanceData"
            :key="student.student_id"
            class="bg-gray-50 rounded-lg p-4"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                  {{ index + 1 }}
                </div>
                <div>
                  <div class="flex items-center gap-2">
                  <h4 class="font-medium text-gray-900">{{ student.student_name }}</h4>
                    <!-- Homework Submission Status -->
                    <div v-if="student.homework_submission_status === 'submitted' || student.homework_submission_status === 'graded'"
                      class="flex items-center gap-1 text-xs font-medium text-green-600 bg-green-100 px-2 py-0.5 rounded-full">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      <span>Đã nộp</span>
                    </div>
                    <!-- Homework Submission Links -->
                    <div v-if="student.homework_submission && (student.homework_submission.unit_folder_link || student.homework_submission.submission_link)"
                      class="flex items-center gap-1">
                      <!-- Unit Folder Link -->
                      <a v-if="student.homework_submission.unit_folder_link"
                        :href="student.homework_submission.unit_folder_link"
                        target="_blank"
                        class="flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-2 py-0.5 rounded-full transition"
                        title="Xem folder bài tập">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <span>Folder</span>
                      </a>
                      <!-- Submission Document Link -->
                      <a v-if="student.homework_submission.submission_link"
                        :href="student.homework_submission.submission_link"
                        target="_blank"
                        class="flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2 py-0.5 rounded-full transition"
                        title="Xem bài nộp">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Bài nộp</span>
                      </a>
                    </div>
                    <div v-else-if="student.homework_submission_status"
                      class="flex items-center gap-1 text-xs font-medium text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span>Chưa nộp</span>
                    </div>
                  </div>
                  <p class="text-sm text-gray-500">{{ student.student_code }}</p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Status -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('class_detail.attendance_status') }}
                </label>
                <div class="flex space-x-2">
                  <button
                    v-for="status in ['present', 'absent', 'late', 'excused']"
                    :key="status"
                    @click="student.status = status"
                    :class="[
                      'flex-1 px-3 py-2 text-sm font-medium rounded-md border-2 transition-colors',
                      student.status === status
                        ? getStatusActiveClass(status)
                        : 'border-gray-200 text-gray-700 hover:border-gray-300'
                    ]"
                  >
                    {{ t(`class_detail.${status}`) }}
                  </button>
                </div>
              </div>

              <!-- Check-in Time -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('class_detail.check_in_time') }}
                </label>
                <input
                  v-model="student.check_in_time"
                  type="time"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
              </div>

              <!-- Homework Score -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                  {{ t('class_detail.homework_score') }} (1-10)
                  <span v-if="!student.homework_submission_status || (student.homework_submission_status !== 'submitted' && student.homework_submission_status !== 'graded')" 
                    class="text-xs text-red-500 font-normal">
                    (Cần nộp bài trước)
                  </span>
                </label>
                <input
                  v-model.number="student.homework_score"
                  type="number"
                  min="1"
                  max="10"
                  :disabled="!student.homework_submission_status || (student.homework_submission_status !== 'submitted' && student.homework_submission_status !== 'graded')"
                  :class="[
                    'w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500',
                    (!student.homework_submission_status || (student.homework_submission_status !== 'submitted' && student.homework_submission_status !== 'graded'))
                      ? 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed'
                      : 'border-gray-300'
                  ]"
                  placeholder="1-10"
                />
              </div>

              <!-- Participation Score -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('class_detail.participation_level') }}
                </label>
                <select
                  v-model="student.participation_score"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">{{ t('class_detail.select_level') }}</option>
                  <option value="5">{{ t('class_detail.excellent') }}</option>
                  <option value="4">{{ t('class_detail.good') }}</option>
                  <option value="3">{{ t('class_detail.average') }}</option>
                  <option value="2">{{ t('class_detail.needs_attention') }}</option>
                  <option value="1">{{ t('class_detail.poor') }}</option>
                </select>
              </div>

              <!-- Comment/Notes -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('class_detail.comment_text') || 'Nhận xét' }}
                </label>
                <textarea
                  v-model="student.comment"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                  :placeholder="t('class_detail.comment_placeholder') || 'Nhận xét về học viên trong buổi học này...'"
                ></textarea>
              </div>
            </div>

            <!-- Evaluation Button (if form exists and student is present) -->
            <div v-if="evaluationForm && student.status === 'present'" class="mt-4 border-t pt-4">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">
                  📋 {{ t('class_detail.evaluation_form') || 'Form đánh giá' }}
                </span>
                <div class="flex space-x-2">
                  <button
                    v-if="student.evaluation_pdf_url"
                    @click="viewEvaluationPDF(student.evaluation_pdf_url)"
                    class="px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100"
                  >
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {{ t('class_detail.view_pdf') || 'Xem PDF' }}
                  </button>
                  <button
                    @click="openEvaluationModal(student)"
                    class="px-3 py-1.5 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700"
                  >
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ student.evaluation_data && Object.keys(student.evaluation_data).length > 0 
                      ? (t('class_detail.edit_evaluation') || 'Sửa đánh giá') 
                      : (t('class_detail.evaluate') || 'Đánh giá') 
                    }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          @click="saveAttendance"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {{ saving ? t('common.saving') : t('class_detail.save_attendance') }}
        </button>
      </div>
    </div>

    <!-- Student Evaluation Modal -->
    <StudentEvaluationModal
      v-if="showEvaluationModal"
      :evaluation-form="evaluationForm"
      :student-id="selectedStudent.student_id"
      :student-name="selectedStudent.student_name"
      :session-id="session.id"
      :session-title="session.lesson_title || 'Session'"
      :existing-data="selectedStudent.evaluation_data || {}"
      @close="showEvaluationModal = false"
      @saved="handleEvaluationSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import api from '../../../api';
import axios from 'axios';
import dayjs from 'dayjs';
import Swal from 'sweetalert2';
import StudentEvaluationModal from './StudentEvaluationModal.vue';

const { t } = useI18n();

const props = defineProps({
  session: {
    type: Object,
    required: true
  },
  classData: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'saved']);

const attendanceData = ref([]);
const evaluationForm = ref(null);
const saving = ref(false);
const showEvaluationModal = ref(false);
const selectedStudent = ref(null);

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY');
};

const getStatusActiveClass = (status) => {
  if (status === 'present') return 'border-green-500 bg-green-50 text-green-700';
  if (status === 'absent') return 'border-red-500 bg-red-50 text-red-700';
  if (status === 'late') return 'border-yellow-500 bg-yellow-50 text-yellow-700';
  if (status === 'excused') return 'border-blue-500 bg-blue-50 text-blue-700';
  return 'border-gray-500 bg-gray-50 text-gray-700';
};

const loadAttendance = async () => {
  try {
    // Get session detail with attendance
    const response = await api.classes.getSessionDetail(props.session.id);
    const sessionData = response.data.data;
    
    // Load evaluation form if exists
    if (sessionData.valuation_form) {
      evaluationForm.value = sessionData.valuation_form;
    }
    
    // Get class students - API returns flat data with student_name, student_code already
    const studentsResponse = await api.classes.getStudents(props.classData.id);
    const activeStudents = studentsResponse.data.data.filter(s => s.status === 'active');
    
    // Build attendance data
    attendanceData.value = activeStudents.map(student => {
      const existingAttendance = sessionData.attendances?.find(a => a.student_id === student.student_id);
      
      // Get evaluation_data (already parsed by Laravel)
      const evaluationData = existingAttendance?.evaluation_data || {};
      
      return {
        student_id: student.student_id,
        user_id: student.user_id, // Add user_id for homework submission matching
        student_name: student.student_name || 'N/A',
        student_code: student.student_code || 'N/A',
        status: existingAttendance?.status || 'present',
        check_in_time: existingAttendance?.check_in_time || null,
        homework_score: existingAttendance?.homework_score || null,
        participation_score: existingAttendance?.participation_score || '',
        comment: existingAttendance?.notes || '',
        evaluation_data: evaluationData,
        evaluation_pdf_url: existingAttendance?.evaluation_pdf_url || null,
        homework_submission_status: null // Will be loaded separately
      };
    });
    
    // Load homework submissions status
    await loadHomeworkSubmissions();
  } catch (error) {
    console.error('Error loading attendance:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to load attendance data'
    });
  }
};

const loadHomeworkSubmissions = async () => {
  try {
    console.log('🔍 [AttendanceModal] Loading homework submissions...', {
      classId: props.classData.id,
      sessionId: props.session.id
    });

    // Get homework submissions for this session
    const response = await axios.get(`/api/quality/classes/${props.classData.id}/sessions/${props.session.id}/homework-submissions`);

    console.log('📊 [AttendanceModal] API Response:', response.data);

    if (response.data.success) {
      const submissions = response.data.data || [];

      console.log('📝 [AttendanceModal] Submissions:', submissions);
      console.log('👥 [AttendanceModal] Attendance Data before matching:', attendanceData.value.map(s => ({
        student_name: s.student_name,
        student_id: s.student_id,
        user_id: s.user_id
      })));

      // Update each student's homework submission status and data
      // NOTE: homework_submissions.student_id stores user_id, not student_id
      attendanceData.value.forEach(student => {
        const submission = submissions.find(s => s.student_id === student.user_id);
        console.log(`🔎 [AttendanceModal] Matching ${student.student_name}:`, {
          student_user_id: student.user_id,
          found_submission: !!submission,
          submission_data: submission
        });
        student.homework_submission_status = submission ? submission.status : 'not_submitted';
        student.homework_submission = submission || null; // Store full submission object for links
      });

      console.log('✅ [AttendanceModal] Final attendance data:', attendanceData.value.map(s => ({
        student_name: s.student_name,
        homework_submission_status: s.homework_submission_status,
        has_homework_submission: !!s.homework_submission
      })));
    }
  } catch (error) {
    console.error('❌ [AttendanceModal] Error loading homework submissions:', error);
    // Fail silently - homework submission is optional
  }
};

const saveAttendance = async () => {
  try {
    saving.value = true;
    
    const response = await api.classes.markAttendance(props.session.id, {
      attendances: attendanceData.value.map(a => ({
        student_id: a.student_id,
        status: a.status,
        check_in_time: a.check_in_time,
        homework_score: a.homework_score || null,
        participation_score: a.participation_score || null,
        notes: a.comment || '',
        evaluation_data: a.evaluation_data || null
      }))
    });
    
    // Generate PDFs for students with evaluation data
    console.log('🔍 Response data:', response.data);
    const attendancesWithEvaluation = (response.data.data || []).filter(att => {
      console.log(`🔍 Checking attendance ID ${att.id}:`, {
        has_evaluation_data: !!att.evaluation_data,
        evaluation_data: att.evaluation_data
      });
      if (!att.evaluation_data) return false;
      
      // evaluation_data is already an object from Laravel cast
      const data = att.evaluation_data;
      return data && typeof data === 'object' && Object.keys(data).length > 0;
    });
    
    console.log(`✅ Found ${attendancesWithEvaluation.length} attendances with evaluation data`);
    
    if (attendancesWithEvaluation.length > 0) {
      // Show generating PDF message
      Swal.fire({
        title: t('class_detail.generating_pdfs') || 'Đang tạo PDF...',
        html: t('class_detail.generating_pdfs_text') || 'Đang tạo phiếu đánh giá PDF cho học viên...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      // Generate PDFs in parallel
      const pdfPromises = attendancesWithEvaluation.map(att => {
        console.log(`📄 Generating PDF for attendance ID: ${att.id}`);
        return api.classes.generateEvaluationPdf(att.id)
          .then(res => {
            console.log(`✅ PDF generated for attendance ${att.id}:`, res.data);
            return res;
          })
          .catch(err => {
            console.error(`❌ Error generating PDF for attendance ${att.id}:`, err);
            return null;
          });
      });
      
      const pdfResults = await Promise.all(pdfPromises);
      
      // Update PDF URLs in attendanceData
      pdfResults.forEach((result, index) => {
        if (result && result.data.success) {
          const att = attendancesWithEvaluation[index];
          const student = attendanceData.value.find(s => s.student_id === att.student_id);
          if (student) {
            student.evaluation_pdf_url = result.data.data.pdf_url;
            console.log(`✅ Updated PDF URL for student ${student.student_name}:`, student.evaluation_pdf_url);
          }
        }
      });
      
      Swal.close();
    }
    
    await Swal.fire({
      icon: 'success',
      title: t('class_detail.attendance_saved') || 'Đã lưu điểm danh',
      text: attendancesWithEvaluation.length > 0 
        ? `Đã tạo ${attendancesWithEvaluation.length} phiếu đánh giá PDF`
        : undefined,
      timer: 2000,
      showConfirmButton: false
    });
    
    emit('saved');
  } catch (error) {
    console.error('Error saving attendance:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Failed to save attendance'
    });
  } finally {
    saving.value = false;
  }
};

const openEvaluationModal = async (student) => {
  // Check if student already has PDF
  if (student.evaluation_pdf_url) {
    const result = await Swal.fire({
      icon: 'warning',
      title: t('class_detail.pdf_exists_title') || 'Đã có phiếu đánh giá',
      html: t('class_detail.pdf_exists_message') || 
        'Học viên này đã có phiếu đánh giá PDF.<br><br>' +
        '<strong>Lưu ý:</strong> Nếu bạn điền lại và lưu điểm danh,<br>' +
        'file PDF cũ sẽ bị xóa và thay thế bằng file mới.',
      showCancelButton: true,
      confirmButtonText: t('class_detail.continue_edit') || 'Tiếp tục chỉnh sửa',
      cancelButtonText: t('common.cancel') || 'Hủy',
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
    });
    
    if (!result.isConfirmed) {
      return;
    }
  }
  
  selectedStudent.value = student;
  showEvaluationModal.value = true;
};

const handleEvaluationSaved = (data) => {
  // Find student and update evaluation_data
  const student = attendanceData.value.find(s => s.student_id === data.studentId);
  if (student) {
    student.evaluation_data = data.evaluationData;
  }
  showEvaluationModal.value = false;
  
  Swal.fire({
    icon: 'success',
    title: t('class_detail.evaluation_saved') || 'Đã lưu đánh giá',
    text: t('class_detail.evaluation_saved_text') || 'Đánh giá sẽ được lưu khi bạn click "Lưu điểm danh"',
    timer: 3000,
    showConfirmButton: false
  });
};

const viewEvaluationPDF = (url) => {
  window.open(url, '_blank');
};

onMounted(() => {
  loadAttendance();
});
</script>

