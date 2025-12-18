<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('calendar.my_calendar') }}</h1>
        <p class="text-sm text-gray-600 mt-1">{{ t('calendar.events') }}</p>
      </div>
      <button
        v-if="authStore.hasPermission('calendar.create')"
        @click="showCreateModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('calendar.add_event') }}
      </button>
    </div>

    <!-- Calendar Container -->
    <div class="bg-white rounded-lg shadow-sm p-4">
      <!-- Calendar Navigation -->
      <div class="flex items-center justify-between mb-4 pb-4 border-b">
        <button @click="moveToPrev" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Trước
        </button>
        
        <div class="flex items-center gap-4">
          <h2 class="text-xl font-bold text-gray-900">{{ currentDateRange }}</h2>
          <button @click="moveToToday" class="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-sm">
            Hôm nay
          </button>
        </div>
        
        <button @click="moveToNext" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition flex items-center gap-2">
          Sau
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
      
      <div ref="calendarContainer" style="height: 800px;"></div>
    </div>

    <!-- Event Modal (Placeholder for now) -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg">
        <h3 class="text-lg font-bold mb-4">{{ t('calendar.add_event') }}</h3>
        <p class="text-gray-600">Event form coming soon...</p>
        <button @click="showCreateModal = false" class="mt-4 px-4 py-2 bg-gray-200 rounded-lg">
          {{ t('common.close') }}
        </button>
      </div>
    </div>

    <!-- Customer Detail Modal -->
    <CustomerDetailModal
      :show="showCustomerModal"
      :customer="selectedCustomer"
      @close="closeCustomerModal"
    />

    <!-- Feedback Modal -->
    <FeedbackModal
      :show="showFeedbackModal"
      :event="selectedEvent"
      :class-teacher="selectedEvent?.customer_info?.teacher_name || null"
      @close="closeFeedbackModal"
      @submit="handleFeedbackSubmit"
    />

    <!-- Assign Teacher Modal -->
    <AssignTeacherModal
      :show="showAssignTeacherModal"
      :event="selectedEvent"
      @close="closeAssignTeacherModal"
      @assigned="handleTeacherAssigned"
    />

    <!-- Edit Session Teacher Modal -->
    <EditSessionTeacherModal
      :show="showEditTeacherModal"
      :session="selectedSession"
      :classId="selectedClassId"
      @close="closeEditTeacherModal"
      @changed="handleSessionTeacherChanged"
    />

    <!-- Cancel Session Modal -->
    <CancelSessionModal
      :show="showCancelSessionModal"
      :session="selectedSession"
      :classId="selectedClassId"
      @close="closeCancelSessionModal"
      @cancelled="handleSessionCancelled"
    />

    <!-- Hover Tooltip -->
    <div
      v-if="tooltip.show"
      :style="{ top: tooltip.y + 'px', left: tooltip.x + 'px' }"
      class="fixed z-[100] bg-white rounded-lg shadow-xl border border-gray-200 p-4 max-w-sm pointer-events-none"
    >
      <div v-if="tooltip.data">
        <div v-if="tooltip.data.type === 'customer'">
          <h4 class="font-semibold text-gray-900 mb-2">{{ tooltip.data.name }}</h4>
          <div class="space-y-1 text-sm text-gray-600 mb-3">
            <p v-if="tooltip.data.phone">📞 {{ tooltip.data.phone }}</p>
            <p v-if="tooltip.data.email">✉️ {{ tooltip.data.email }}</p>
            <p v-if="tooltip.data.source">📍 {{ tooltip.data.source }}</p>
          </div>
          
          <!-- Lịch sử tương tác gần nhất -->
          <div v-if="tooltip.data.interactions && tooltip.data.interactions.length > 0" class="border-t pt-2 mt-2">
            <p class="text-xs font-semibold text-gray-700 mb-2">Lịch sử tương tác gần nhất:</p>
            <div class="space-y-2">
              <div 
                v-for="interaction in tooltip.data.interactions" 
                :key="interaction.id"
                class="text-xs bg-gray-50 rounded p-2"
              >
                <div class="flex items-start gap-1">
                  <span>{{ interaction.interaction_type?.icon || '📌' }}</span>
                  <div class="flex-1">
                    <p class="font-medium text-gray-800">{{ interaction.interaction_type?.name }}</p>
                    <p class="text-gray-600 text-[10px] mt-0.5">
                      {{ formatInteractionDate(interaction.interaction_date) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="tooltip.data.type === 'child'">
          <h4 class="font-semibold text-gray-900 mb-2">{{ tooltip.data.name }}</h4>
          <div class="space-y-1 text-sm text-gray-600">
            <p v-if="tooltip.data.date_of_birth">🎂 {{ tooltip.data.date_of_birth }}</p>
            <p v-if="tooltip.data.school">🏫 {{ tooltip.data.school }}</p>
            <p v-if="tooltip.data.grade">📚 Lớp {{ tooltip.data.grade }}</p>
            <p v-if="tooltip.data.notes" class="text-xs text-gray-500 mt-2 pt-2 border-t italic">{{ tooltip.data.notes }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { useRouter } from 'vue-router';
import Calendar from '@toast-ui/calendar';
import '@toast-ui/calendar/dist/toastui-calendar.min.css';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';
import CustomerDetailModal from '../../components/customers/CustomerDetailModal.vue';
import FeedbackModal from '../../components/calendar/FeedbackModal.vue';
import AssignTeacherModal from '../../components/calendar/AssignTeacherModal.vue';
import EditSessionTeacherModal from '../../components/quality/EditSessionTeacherModal.vue';
import CancelSessionModal from '../../components/quality/CancelSessionModal.vue';
import dayjs from 'dayjs';
import 'dayjs/locale/vi';

const { t } = useI18n();
const authStore = useAuthStore();
const swal = useSwal();
const router = useRouter();

const calendarContainer = ref(null);
let calendarInstance = null;
const showCreateModal = ref(false);
const showCustomerModal = ref(false);
const selectedCustomer = ref(null);
const showFeedbackModal = ref(false);
const showAssignTeacherModal = ref(false);
const showEditTeacherModal = ref(false);
const showCancelSessionModal = ref(false);
const selectedEvent = ref(null);
const selectedSession = ref(null);
const selectedClassId = ref(null);
const currentDate = ref(new Date());
const allEvents = ref([]); // Store all events for easy lookup
const tooltip = ref({
  show: false,
  x: 0,
  y: 0,
  data: null,
});

const currentDateRange = computed(() => {
  return dayjs(currentDate.value).locale('vi').format('MMMM YYYY');
});

// Category colors matching backend
const calendars = [
  { id: 'customer_follow_up', name: 'Liên Hệ Lại KH', backgroundColor: '#F59E0B', borderColor: '#F59E0B' },
  { id: 'placement_test', name: 'Test Đầu Vào', backgroundColor: '#06B6D4', borderColor: '#06B6D4' },
  { id: 'class_session', name: 'Buổi Học', backgroundColor: '#14B8A6', borderColor: '#14B8A6' },
  { id: 'homework', name: 'Bài Tập', backgroundColor: '#F97316', borderColor: '#F97316' },
  { id: 'meeting', name: 'Cuộc Họp', backgroundColor: '#3B82F6', borderColor: '#3B82F6' },
  { id: 'task', name: 'Công Việc', backgroundColor: '#10B981', borderColor: '#10B981' },
  { id: 'deadline', name: 'Deadline', backgroundColor: '#EF4444', borderColor: '#EF4444' },
  { id: 'event', name: 'Sự Kiện', backgroundColor: '#8B5CF6', borderColor: '#8B5CF6' },
  { id: 'reminder', name: 'Nhắc Nhở', backgroundColor: '#EC4899', borderColor: '#EC4899' },
  { id: 'general', name: 'Chung', backgroundColor: '#6B7280', borderColor: '#6B7280' },
];

const initCalendar = () => {
  if (!calendarContainer.value) return;

  calendarInstance = new Calendar(calendarContainer.value, {
    defaultView: 'month',
    useFormPopup: false,
    useDetailPopup: true,
    calendars: calendars,
    month: {
      startDayOfWeek: 1, // Monday
    },
    week: {
      startDayOfWeek: 1,
      dayNames: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
      eventView: true,
      taskView: false,
    },
    template: {
      monthDayName(model) {
        return `<span class="calendar-week-dayname-name">${model.label}</span>`;
      },
      monthGridHeader(model) {
        const date = new Date(model.date);
        const template = `<div class="calendar-grid-header-date">${date.getDate()}</div>`;
        return template;
      },
      popupDetailBody(event) {
        return getCustomPopupDetailBody(event);
      },
      time(event) {
        // Custom template cho time events - thêm badge học thử NGAY ĐẦU
        const trialCount = event.raw?.customer_info?.trial_students_count || 0;
        const trialBadge = trialCount > 0 
          ? `<span style="background: #FFA500; color: white; padding: 2px 5px; border-radius: 8px; font-size: 9px; margin-right: 4px; font-weight: bold; display: inline-block;">👤${trialCount}</span>`
          : '';
        
        return `${trialBadge}<span>${event.title}</span>`;
      },
    },
  });

  // Load events
  loadEvents();

  // Event listeners
  calendarInstance.on('selectDateTime', (dateTimeInfo) => {
    if (authStore.hasPermission('calendar.create')) {
      showCreateModal.value = true;
      // TODO: Set default dates from dateTimeInfo
    }
  });

  // Setup click listeners cho customer names trong popup
  setupPopupClickListeners();
};

const loadEvents = async () => {
  try {
    const date = calendarInstance.getDate();
    const start = calendarInstance.getDateRangeStart();
    const end = calendarInstance.getDateRangeEnd();

    // Convert TZDate to Date object
    const startDate = new Date(start.getTime());
    const endDate = new Date(end.getTime());

    const response = await api.get('/api/calendar/events', {
      params: {
        start_date: startDate.toISOString(),
        end_date: endDate.toISOString(),
      },
    });

    if (response.data.success) {
      calendarInstance.clear();
      calendarInstance.createEvents(response.data.data);
      // Store events for easy lookup
      allEvents.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load events:', error);
    swal.error('Có lỗi xảy ra khi tải danh sách sự kiện');
  }
};

const getCustomPopupDetailBody = (event) => {
  const customerInfo = event.raw?.customer_info;
  const currentUserId = authStore.user?.id;
  const eventStatus = event.raw?.status;
  const isCompleted = eventStatus === 'completed';
  
  // Quyền submit feedback:
  // - Placement test: có quyền calendar.submit_feedback HOẶC là GV được gán
  // - Trial class: có quyền calendar.submit_feedback HOẶC là GV dạy buổi đó
  let canSubmitFeedback = authStore.hasPermission('calendar.submit_feedback');
  
  if (event.calendarId === 'placement_test') {
    canSubmitFeedback = canSubmitFeedback || event.raw?.assigned_teacher_id == currentUserId;
  } else if (event.calendarId === 'class_session') {
    canSubmitFeedback = canSubmitFeedback || customerInfo?.teacher_id == currentUserId;
  }
  
  const canAssignTeacher = authStore.hasPermission('calendar.assign_teacher');
  
  // DEBUG: Log để kiểm tra
  console.log('=== CALENDAR EVENT DEBUG ===');
  console.log('Event:', event);
  console.log('CalendarId:', event.calendarId);
  console.log('Category:', event.category);
  console.log('Status:', eventStatus);
  console.log('Can Submit Feedback:', canSubmitFeedback);
  console.log('Can Assign Teacher:', canAssignTeacher);
  console.log('Is Completed:', isCompleted);
  console.log('Customer Info:', customerInfo);
  console.log('Trial Count:', customerInfo?.trial_students_count);
  
  // Action buttons for placement test and trial class
  let actionButtons = '';
  
  // FIX: Dùng event.calendarId thay vì event.category
  if (event.calendarId === 'placement_test') {
    if (canAssignTeacher) {
      actionButtons += `
        <button 
          class="assign-teacher-btn"
          data-event-id="${event.id}"
          style="padding: 6px 12px; background: #06B6D4; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; margin-right: 8px; font-weight: 500;"
          onmouseover="this.style.background='#0891B2'"
          onmouseout="this.style.background='#06B6D4'">
          👨‍🏫 Phân công GV
        </button>
      `;
    }
    if (canSubmitFeedback && !isCompleted) {
      actionButtons += `
        <button 
          class="submit-feedback-btn"
          data-event-id="${event.id}"
          style="padding: 6px 12px; background: #10B981; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; font-weight: 500;"
          onmouseover="this.style.background='#059669'"
          onmouseout="this.style.background='#10B981'">
          📝 Trả kết quả
        </button>
      `;
    }
    if (isCompleted) {
      actionButtons += `
        <button 
          class="view-feedback-btn"
          data-event-id="${event.id}"
          style="padding: 6px 12px; background: #3B82F6; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; margin-left: 8px; font-weight: 500;"
          onmouseover="this.style.background='#2563EB'"
          onmouseout="this.style.background='#3B82F6'">
          👁️ Xem kết quả
        </button>
      `;
    }
  } else if (event.calendarId === 'class_session') {
    // Buttons for trial class feedback
    if (customerInfo?.trial_students_count > 0) {
      if (canSubmitFeedback && !isCompleted) {
        actionButtons += `
          <button
            class="submit-feedback-btn"
            data-event-id="${event.id}"
            style="padding: 6px 12px; background: #F59E0B; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; font-weight: 500; margin-right: 8px;"
            onmouseover="this.style.background='#D97706'"
            onmouseout="this.style.background='#F59E0B'">
            📝 Đánh giá học thử
          </button>
        `;
      }
      if (isCompleted) {
        actionButtons += `
          <button
            class="view-feedback-btn"
            data-event-id="${event.id}"
            style="padding: 6px 12px; background: #8B5CF6; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; margin-right: 8px; font-weight: 500;"
            onmouseover="this.style.background='#7C3AED'"
            onmouseout="this.style.background='#8B5CF6'">
            👁️ Xem đánh giá
          </button>
        `;
      }
    }

    // Teacher change and cancellation buttons for all class sessions
    const canManageSession = authStore.hasPermission('classes.update_session');

    if (canManageSession && eventStatus !== 'cancelled' && eventStatus !== 'completed') {
      actionButtons += `
        <button
          class="change-teacher-btn"
          data-event-id="${event.id}"
          data-class-id="${customerInfo?.class_id}"
          data-session-id="${event.raw?.metadata?.session_id || event.raw?.eventable_id}"
          style="padding: 6px 12px; background: #06B6D4; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; margin-right: 8px; font-weight: 500;"
          onmouseover="this.style.background='#0891B2'"
          onmouseout="this.style.background='#06B6D4'">
          👨‍🏫 Đổi giáo viên
        </button>
        <button
          class="cancel-session-btn"
          data-event-id="${event.id}"
          data-class-id="${customerInfo?.class_id}"
          data-session-id="${event.raw?.metadata?.session_id || event.raw?.eventable_id}"
          style="padding: 6px 12px; background: #EF4444; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; font-weight: 500;"
          onmouseover="this.style.background='#DC2626'"
          onmouseout="this.style.background='#EF4444'">
          🚫 Báo nghỉ
        </button>
      `;
    }
  }
  
  if (!customerInfo) {
    // Nếu không có customer info, trả về body mặc định + action buttons
    return `
      <div class="toastui-calendar-section-detail">
        ${event.body || 'Không có mô tả'}
      </div>
      ${actionButtons ? `
        <div class="toastui-calendar-section-detail" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
          ${actionButtons}
        </div>
      ` : ''}
    `;
  }
  
  let customerSection = '';
  
  // Nếu là class session
  if (customerInfo.type === 'class_session') {
    const trialBadge = customerInfo.trial_students_count > 0
      ? `<span style="background: #FFA500; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-left: 8px; font-weight: 500;">👤 ${customerInfo.trial_students_count} học thử</span>`
      : '';
    customerSection = `
      <div class="toastui-calendar-section-detail" style="margin-top: 10px;">
        <div style="background: #f0fdfa; border-left: 3px solid #14B8A6; padding: 12px; border-radius: 4px;">
          <div style="font-size: 14px; font-weight: 600; color: #0f766e; margin-bottom: 8px;">
            📚 ${customerInfo.class_name}${trialBadge}
          </div>
          <div style="font-size: 12px; color: #6b7280; line-height: 1.6;">
            <div style="margin-bottom: 4px;">
              <strong>Mã lớp:</strong> ${customerInfo.class_code}
            </div>
            <div style="margin-bottom: 4px;">
              <strong>Buổi học:</strong> ${customerInfo.session_number}/${customerInfo.total_sessions}
            </div>
            <div style="margin-bottom: 4px;">
              <strong>Bài học:</strong> ${customerInfo.lesson_title || 'N/A'}
            </div>
            <div style="margin-bottom: 4px;">
              <strong>Giáo viên:</strong> ${customerInfo.teacher_name}
            </div>
            <div style="margin-bottom: 4px;">
              <strong>Số học viên:</strong> ${customerInfo.student_count} người
            </div>
            ${customerInfo.room_number ? `
              <div style="margin-bottom: 4px;">
                <strong>Phòng:</strong> ${customerInfo.room_number}
              </div>
            ` : ''}
            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #d1fae5;">
              <a href="#" 
                 class="class-detail-link"
                 data-class-id="${customerInfo.class_id}"
                 style="color: #14B8A6; text-decoration: none; font-weight: 500; font-size: 13px; cursor: pointer;"
                 onmouseover="this.style.textDecoration='underline'"
                 onmouseout="this.style.textDecoration='none'">
                📖 Xem chi tiết lớp →
              </a>
            </div>
          </div>
        </div>
      </div>
    `;
  }
  // Nếu là customer
  else if (customerInfo.type === 'customer') {
    customerSection = `
      <div class="toastui-calendar-section-detail" style="margin-top: 10px;">
        <strong>Khách hàng:</strong><br/>
        <a href="#" 
           class="customer-link" 
           data-customer-id="${customerInfo.id}"
           data-customer-type="customer"
           style="color: #3b82f6; text-decoration: none; font-weight: 500; cursor: pointer;"
           onmouseover="this.style.textDecoration='underline'"
           onmouseout="this.style.textDecoration='none'">
          ${customerInfo.name}
        </a>
        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
          ${customerInfo.phone ? '📞 ' + customerInfo.phone : ''}
          ${customerInfo.email ? '<br/>✉️ ' + customerInfo.email : ''}
        </div>
      </div>
    `;
  }
  // Nếu là child
  else if (customerInfo.type === 'child') {
    customerSection = `
      <div class="toastui-calendar-section-detail" style="margin-top: 10px;">
        <strong>Học viên:</strong><br/>
        <a href="#" 
           class="customer-link" 
           data-customer-id="${customerInfo.parent_id}"
           data-customer-type="child"
           data-child-id="${customerInfo.id}"
           style="color: #3b82f6; text-decoration: none; font-weight: 500; cursor: pointer;"
           onmouseover="this.style.textDecoration='underline'"
           onmouseout="this.style.textDecoration='none'">
          ${customerInfo.name}
        </a>
        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
          ${customerInfo.parent_name ? '👨‍👩‍👧 Phụ huynh: ' + customerInfo.parent_name : ''}
          ${customerInfo.parent_phone ? '<br/>📞 ' + customerInfo.parent_phone : ''}
        </div>
      </div>
    `;
  }
  // Nếu là homework
  else if (customerInfo.type === 'homework') {
    const filesHtml = customerInfo.files && customerInfo.files.length > 0 
      ? customerInfo.files.map(file => `
          <a href="${file.url}" 
             style="display: flex; align-items: center; gap: 4px; color: #2563eb; text-decoration: none; font-size: 12px; margin-top: 4px;"
             onmouseover="this.style.textDecoration='underline'"
             onmouseout="this.style.textDecoration='none'">
            📎 ${file.name}
          </a>
        `).join('')
      : '<div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Không có tài liệu đính kèm</div>';
    
    customerSection = `
      <div class="toastui-calendar-section-detail" style="margin-top: 10px;">
        <strong>Lớp:</strong> ${customerInfo.class_code} - ${customerInfo.class_name}<br/>
        ${customerInfo.session_number ? `<strong>Buổi học:</strong> Buổi ${customerInfo.session_number}${customerInfo.session_title ? ': ' + customerInfo.session_title : ''}<br/>` : ''}
        ${customerInfo.deadline ? `<strong>Hạn nộp:</strong> ${new Date(customerInfo.deadline).toLocaleString('vi-VN')}<br/>` : ''}
        <div style="margin-top: 8px;">
          <strong>Tài liệu:</strong>
          ${filesHtml}
        </div>
      </div>
    `;
  }
  
  return `
    <div class="toastui-calendar-section-detail">
      ${event.body || 'Không có mô tả'}
    </div>
    ${customerSection}
    ${event.location && customerInfo.type !== 'class_session' && customerInfo.type !== 'homework' ? `
      <div class="toastui-calendar-section-detail" style="margin-top: 10px;">
        <strong>Địa điểm:</strong> ${event.location}
      </div>
    ` : ''}
    ${actionButtons ? `
      <div class="toastui-calendar-section-detail" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
        ${actionButtons}
      </div>
    ` : ''}
  `;
};

const setupPopupClickListeners = () => {
  const calendarEl = calendarContainer.value;
  if (!calendarEl) return;
  
  // Event delegation cho customer links và action buttons
  calendarEl.addEventListener('click', async (e) => {
    // Handle submit feedback button
    const feedbackBtn = e.target.closest('.submit-feedback-btn');
    if (feedbackBtn) {
      e.preventDefault();
      e.stopPropagation();
      
      const eventId = feedbackBtn.getAttribute('data-event-id');
      if (eventId) {
        // Find event from stored events
        const event = allEvents.value.find(ev => ev.id == eventId);
        if (event) {
          selectedEvent.value = event;
          showFeedbackModal.value = true;
        }
      }
      return;
    }
    
    // Handle view feedback button (readonly mode)
    const viewFeedbackBtn = e.target.closest('.view-feedback-btn');
    if (viewFeedbackBtn) {
      e.preventDefault();
      e.stopPropagation();
      
      const eventId = viewFeedbackBtn.getAttribute('data-event-id');
      if (eventId) {
        // Find event from stored events
        const event = allEvents.value.find(ev => ev.id == eventId);
        if (event) {
          selectedEvent.value = event;
          showFeedbackModal.value = true; // Reuse same modal, it will detect completed status
        }
      }
      return;
    }
    
    // Handle assign teacher button
    const assignTeacherBtn = e.target.closest('.assign-teacher-btn');
    if (assignTeacherBtn) {
      e.preventDefault();
      e.stopPropagation();

      const eventId = assignTeacherBtn.getAttribute('data-event-id');
      if (eventId) {
        // Find event from stored events
        const event = allEvents.value.find(ev => ev.id == eventId);
        if (event) {
          selectedEvent.value = event;
          showAssignTeacherModal.value = true;
        }
      }
      return;
    }

    // Handle change teacher button (for class sessions)
    const changeTeacherBtn = e.target.closest('.change-teacher-btn');
    if (changeTeacherBtn) {
      e.preventDefault();
      e.stopPropagation();

      const sessionId = changeTeacherBtn.getAttribute('data-session-id');
      const classId = changeTeacherBtn.getAttribute('data-class-id');

      if (sessionId && classId) {
        // Load session data from API
        loadSessionDataAndOpenModal(classId, sessionId, 'teacher');
      }
      return;
    }

    // Handle cancel session button (report absence)
    const cancelSessionBtn = e.target.closest('.cancel-session-btn');
    if (cancelSessionBtn) {
      e.preventDefault();
      e.stopPropagation();

      const sessionId = cancelSessionBtn.getAttribute('data-session-id');
      const classId = cancelSessionBtn.getAttribute('data-class-id');

      if (sessionId && classId) {
        // Load session data from API
        loadSessionDataAndOpenModal(classId, sessionId, 'cancel');
      }
      return;
    }
    
    // Handle class detail link
    const classDetailLink = e.target.closest('.class-detail-link');
    if (classDetailLink) {
      e.preventDefault();
      e.stopPropagation();
      
      const classId = classDetailLink.getAttribute('data-class-id');
      if (classId) {
        console.log('Navigating to class detail:', classId);
        
        // Close the popup first
        if (calendarInstance) {
          calendarInstance.clearGridSelections();
        }
        
        // Navigate using Vue Router with named route
        router.push({ 
          name: 'class.detail', 
          params: { id: classId } 
        }).then(() => {
          console.log('Navigation successful');
        }).catch(err => {
          console.error('Navigation error:', err);
        });
      }
      return;
    }
    
    const customerLink = e.target.closest('.customer-link');
    if (!customerLink) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    const customerId = customerLink.getAttribute('data-customer-id');
    if (!customerId) return;
    
    // Ẩn tooltip nếu đang hiển thị
    tooltip.value.show = false;
    
    // Load customer data và hiển thị modal
    try {
      const response = await api.get(`/api/customers/${customerId}`);
      if (response.data.success) {
        selectedCustomer.value = response.data.data;
        showCustomerModal.value = true;
      }
    } catch (error) {
      console.error('Failed to load customer:', error);
      swal.error('Không thể tải thông tin khách hàng');
    }
  });
  
  // Hover tooltip cho customer links
  calendarEl.addEventListener('mouseover', async (e) => {
    const customerLink = e.target.closest('.customer-link');
    if (!customerLink) {
      tooltip.value.show = false;
      return;
    }
    
    const customerId = customerLink.getAttribute('data-customer-id');
    const customerType = customerLink.getAttribute('data-customer-type');
    const childId = customerLink.getAttribute('data-child-id');
    
    const rect = customerLink.getBoundingClientRect();
    
    // Nếu là child (placement test), hiển thị thông tin child
    if (customerType === 'child' && childId) {
      try {
        const response = await api.get(`/api/customers/${customerId}/children`);
        if (response.data.success) {
          const child = response.data.data.find(c => c.id == childId);
          if (child) {
            // Format date of birth
            let dob = null;
            if (child.date_of_birth) {
              const d = new Date(child.date_of_birth);
              dob = d.toLocaleDateString('vi-VN');
            }
            
            tooltip.value = {
              show: true,
              x: rect.right + 10,
              y: rect.top,
              data: {
                type: 'child',
                name: child.name,
                date_of_birth: dob,
                school: child.school,
                grade: child.grade,
                notes: child.notes,
              },
            };
            return;
          }
        }
      } catch (error) {
        console.error('Failed to load child info:', error);
      }
    }
    
    // Nếu là customer, load thông tin customer và lịch sử tương tác
    try {
      const [customerResponse, interactionsResponse] = await Promise.all([
        api.get(`/api/customers/${customerId}`),
        api.get(`/api/customers/${customerId}/interactions`, { params: { per_page: 3 } })
      ]);
      
      if (customerResponse.data.success) {
        const customer = customerResponse.data.data;
        const interactions = interactionsResponse.data.success ? interactionsResponse.data.data.data : [];
        
        tooltip.value = {
          show: true,
          x: rect.right + 10,
          y: rect.top,
          data: {
            type: 'customer',
            name: customer.name,
            phone: customer.phone,
            email: customer.email,
            source: customer.source,
            interactions: interactions.slice(0, 3),
          },
        };
      }
    } catch (error) {
      console.error('Failed to load customer info:', error);
    }
  });
  
  calendarEl.addEventListener('mouseout', (e) => {
    const customerLink = e.target.closest('.customer-link');
    if (!customerLink) {
      tooltip.value.show = false;
    }
  });
};

const closeCustomerModal = () => {
  showCustomerModal.value = false;
  selectedCustomer.value = null;
};

const closeFeedbackModal = () => {
  showFeedbackModal.value = false;
  selectedEvent.value = null;
};

const closeAssignTeacherModal = () => {
  showAssignTeacherModal.value = false;
  selectedEvent.value = null;
};

const handleFeedbackSubmit = async (feedbackData) => {
  try {
    const eventId = selectedEvent.value.id;
    const category = feedbackData.category || selectedEvent.value.calendarId;
    
    let response;
    
    if (category === 'placement_test') {
      // Submit placement test result
      response = await api.post(`/api/calendar/events/${eventId}/submit-test-result`, {
        score: feedbackData.score,
        level: feedbackData.level,
        notes: feedbackData.feedback,
      });
    } else if (category === 'class_session') {
      // Submit trial class feedback
      response = await api.post(`/api/calendar/events/${eventId}/submit-trial-feedback`, {
        feedback: feedbackData.feedback,
        rating: feedbackData.rating,
      });
    }
    
    if (response.data.success) {
      await swal.success(t('calendar.feedback_success'));
      closeFeedbackModal();
      loadEvents(); // Reload to show updated status
    }
  } catch (error) {
    console.error('Error submitting feedback:', error);
    await swal.error(t('calendar.feedback_error'));
  }
};

const handleTeacherAssigned = async (teacherId) => {
  try {
    const eventId = selectedEvent.value.id;
    const response = await api.post(`/api/calendar/events/${eventId}/assign-teacher`, {
      teacher_id: teacherId,
    });

    if (response.data.success) {
      await swal.success(t('calendar.teacher_assigned_success'));
      closeAssignTeacherModal();
      loadEvents(); // Reload to show updated assigned teacher
    }
  } catch (error) {
    console.error('Error assigning teacher:', error);
    await swal.error(t('calendar.teacher_assigned_error'));
  }
};

const loadSessionDataAndOpenModal = async (classId, sessionId, modalType) => {
  try {
    // Load session data
    const response = await api.get(`/api/classes/${classId}/sessions/${sessionId}`);

    if (response.data.success) {
      const session = response.data.data;
      selectedSession.value = session;
      selectedClassId.value = parseInt(classId);

      // Open appropriate modal
      if (modalType === 'teacher') {
        showEditTeacherModal.value = true;
      } else if (modalType === 'cancel') {
        showCancelSessionModal.value = true;
      }
    }
  } catch (error) {
    console.error('Error loading session:', error);
    await swal.error('Không thể tải thông tin buổi học');
  }
};

const closeEditTeacherModal = () => {
  showEditTeacherModal.value = false;
  selectedSession.value = null;
  selectedClassId.value = null;
};

const closeCancelSessionModal = () => {
  showCancelSessionModal.value = false;
  selectedSession.value = null;
  selectedClassId.value = null;
};

const handleSessionTeacherChanged = async (updatedSession) => {
  await swal.success('Đã thay đổi giáo viên thành công!', 'Giáo viên và lớp học đã nhận thông báo qua Zalo');
  closeEditTeacherModal();
  loadEvents(); // Reload calendar to show updated teacher
};

const handleSessionCancelled = async (result) => {
  const message = result.rescheduled_count > 0
    ? `Đã báo nghỉ thành công!\n\nĐã điều chỉnh lịch ${result.rescheduled_count} buổi học tiếp theo.`
    : 'Đã báo nghỉ thành công!';

  await swal.success('Báo nghỉ thành công', message);
  closeCancelSessionModal();
  loadEvents(); // Reload calendar to show updated status
};

const formatInteractionDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  });
};

// Navigation methods
const moveToPrev = () => {
  if (!calendarInstance) return;
  calendarInstance.prev();
  currentDate.value = calendarInstance.getDate().toDate();
  loadEvents();
};

const moveToNext = () => {
  if (!calendarInstance) return;
  calendarInstance.next();
  currentDate.value = calendarInstance.getDate().toDate();
  loadEvents();
};

const moveToToday = () => {
  if (!calendarInstance) return;
  calendarInstance.today();
  currentDate.value = new Date();
  loadEvents();
};

onMounted(() => {
  initCalendar();
});

onBeforeUnmount(() => {
  if (calendarInstance) {
    calendarInstance.destroy();
  }
});
</script>