<template>
  <div class="p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6 flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">{{ t('class_detail.lesson_list') }}</h3>
        <p class="text-sm text-gray-500 mt-1">
          {{ sessions.length }} {{ t('class_detail.total_sessions') || 'sessions' }}
        </p>
      </div>
      <div class="flex items-center space-x-2">
        <button
          v-if="classData?.lesson_plan_id"
          @click="syncFromSyllabus"
          :disabled="syncing || syncingFolders"
          class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 disabled:opacity-50 flex items-center space-x-2"
          title="Đồng bộ nội dung và folder IDs từ giáo án (bỏ qua các buổi đã điểm danh)"
        >
          <svg v-if="syncing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>{{ syncing ? t('common.syncing') || 'Đang cập nhật...' : t('class_detail.sync_from_syllabus') || 'Đồng bộ từ Giáo án' }}</span>
        </button>
        <button
          v-if="classData?.lesson_plan_id && sessions.length > 0"
          @click="syncFolderIds"
          :disabled="syncing || syncingFolders"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 flex items-center space-x-2"
          title="Chỉ đồng bộ folder IDs từ giáo án (không thay đổi nội dung bài học)"
        >
          <svg v-if="syncingFolders" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          <span>{{ syncingFolders ? 'Đang đồng bộ...' : 'Đồng bộ Folder IDs' }}</span>
        </button>
      </div>
    </div>

    <!-- Generate Sessions Button (if no sessions yet) -->
    <div v-if="sessions.length === 0 && classData" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
      <div class="flex items-start space-x-3">
        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="flex-1">
          <p class="text-sm font-medium text-blue-900">{{ t('class_detail.no_sessions_generated') || 'Chưa có buổi học nào được tạo' }}</p>
          <p class="text-sm text-blue-700 mt-1">{{ t('class_detail.sessions_preview_below') || 'Dưới đây là danh sách ngày học dự kiến dựa trên lịch học của lớp' }}</p>
        </div>
      </div>
    </div>

    <!-- Sessions Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12"></th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                {{ t('class_detail.session') || 'Session' }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                {{ t('class_detail.date') || 'Date' }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">
                {{ t('class_detail.teacher') || 'Teacher' }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('class_detail.lesson_name') || 'Lesson' }}
              </th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                {{ t('class_detail.lesson_plan') || 'Plan' }}
              </th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                {{ t('class_detail.materials') || 'Materials' }}
              </th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                {{ t('class_detail.homework') || 'Homework' }}
              </th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-40">
                {{ t('common.actions') || 'Actions' }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <template v-for="(session, index) in displaySessions" :key="session.id || index">
              <tr class="hover:bg-gray-50 transition">
                <!-- Expand Button -->
                <td class="px-4 py-4 whitespace-nowrap">
                  <button
                    v-if="session.id && session.status === 'completed'"
                    @click="toggleExpand(session.id)"
                    class="text-gray-400 hover:text-gray-600 transition"
                  >
                    <svg
                      class="w-5 h-5 transition-transform"
                      :class="{ 'transform rotate-90': expandedSessions.has(session.id) }"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                  </button>
                </td>
                
              <!-- Session Number -->
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <span class="inline-flex items-center justify-center w-8 h-8 rounded-full font-semibold text-sm" :class="sessionStatusClass(session.status)">
                    {{ getSessionNumber(session) }}
                  </span>
                </div>
              </td>
              
              <!-- Date -->
              <td class="px-4 py-4 whitespace-nowrap">
                <button
                  @click="openEvaluationModal(session)"
                  class="text-left hover:bg-purple-50 rounded px-2 py-1 transition w-full"
                  :disabled="!session.id"
                  :class="{ 'cursor-not-allowed opacity-50': !session.id }"
                  title="Click để đánh giá chi tiết"
                >
                  <div class="text-sm font-medium text-purple-600 hover:text-purple-800">{{ formatDate(session.scheduled_date) }}</div>
                  <div class="text-xs text-gray-500" v-if="session.class_schedule">
                    {{ session.class_schedule.start_time?.substring(0, 5) || '' }} - {{ session.class_schedule.end_time?.substring(0, 5) || '' }}
                  </div>
                </button>
              </td>

              <!-- Teacher -->
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="flex items-center gap-1">
                  <span
                    v-if="getEffectiveTeacher(session)"
                    class="text-sm text-gray-900"
                    :class="{ 'font-semibold text-indigo-700': session.teacher_id }"
                    :title="session.teacher_id ? 'Giáo viên riêng cho buổi này' : getTeacherSource(session)"
                  >
                    {{ getEffectiveTeacher(session) }}
                  </span>
                  <span v-else class="text-sm text-gray-400 italic">
                    Chưa có
                  </span>
                  <svg
                    v-if="session.teacher_id"
                    class="w-3 h-3 text-indigo-600"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    title="Đã thay đổi giáo viên"
                  >
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </td>

              <!-- Lesson Name -->
              <td class="px-4 py-4">
                <div class="flex items-center gap-2">
                  <div>
                    <div v-if="session.lesson_title" class="text-sm font-medium text-gray-900">
                      {{ session.lesson_title }}
                    </div>
                    <div v-else class="text-sm text-gray-400 italic">
                      {{ t('class_detail.no_lesson_plan') || 'No lesson plan' }}
                    </div>
                    <div v-if="session.lesson_objectives" class="text-xs text-gray-500 mt-1 line-clamp-2" v-html="session.lesson_objectives">
                    </div>
                  </div>
                  <span v-if="session.trial_students_count > 0" class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full whitespace-nowrap flex-shrink-0" title="Học viên học thử">
                    👤 {{ session.trial_students_count }}
                  </span>
                </div>
              </td>
              
              <!-- Lesson Plan Link -->
              <td class="px-4 py-4 text-center">
                <div class="flex flex-wrap justify-center gap-1">
                  <!-- Lesson Plans Badge -->
                  <button
                    v-if="session.lesson_plans_folder_id"
                    @click="openFilesModal('lesson_plans', session)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition cursor-pointer"
                    :title="t('syllabus.open_lesson_plans') || 'Open lesson plans'"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    {{ t('syllabus.lesson_plans_badge') || 'Lesson Plans' }}
                  </button>
                  <!-- Fallback to old lesson_plan_file_id or lesson_plan_url -->
                  <button
                    v-else-if="session.lesson_plan_file_id"
                    @click="openFilesModal('lesson_plan', session)"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 transition"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ t('common.view') || 'View' }}
                  </button>
                  <a
                    v-else-if="session.lesson_plan_url"
                    :href="session.lesson_plan_url"
                    target="_blank"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 transition"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ t('common.view') || 'View' }}
                  </a>
                  <span v-else class="text-xs text-gray-400">-</span>
                </div>
              </td>
              
              <!-- Materials Link -->
              <td class="px-4 py-4 text-center">
                <div class="flex flex-wrap justify-center gap-1">
                  <!-- Materials Badge -->
                  <button
                    v-if="session.materials_folder_id"
                    @click="openFilesModal('materials', session)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 transition cursor-pointer"
                    :title="t('syllabus.open_materials') || 'Open materials'"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    {{ t('syllabus.materials_badge') || 'Materials' }}
                    <span v-if="session.materials_count" class="ml-1 bg-green-600 text-white px-1 rounded-full text-[10px]">
                      {{ session.materials_count }}
                    </span>
                  </button>
                  <!-- Fallback to old materials_url -->
                  <a
                    v-else-if="session.materials_url"
                    :href="session.materials_url"
                    target="_blank"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-50 rounded-md hover:bg-green-100 transition"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    {{ t('common.view') || 'View' }}
                  </a>
                  <span v-else class="text-xs text-gray-400">-</span>
                </div>
              </td>
              
              <!-- Homework Link -->
              <td class="px-4 py-4 text-center">
                <div class="flex flex-wrap justify-center gap-1">
                  <!-- Homework Badge -->
                  <button
                    v-if="session.homework_folder_id"
                    @click="openFilesModal('homework', session)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition cursor-pointer"
                    :title="t('syllabus.open_homework') || 'Open homework'"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    {{ t('syllabus.homework_badge') || 'Homework' }}
                    <span v-if="session.homework_count" class="ml-1 bg-purple-600 text-white px-1 rounded-full text-[10px]">
                      {{ session.homework_count }}
                    </span>
                  </button>
                  <!-- Fallback to old homework_url -->
                  <a
                    v-else-if="session.homework_url"
                    :href="session.homework_url"
                    target="_blank"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-purple-700 bg-purple-50 rounded-md hover:bg-purple-100 transition"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    {{ t('common.view') || 'View' }}
                  </a>
                  <span v-else class="text-xs text-gray-400">-</span>
                </div>
              </td>
              
              <!-- Actions -->
              <td class="px-4 py-4 text-center whitespace-nowrap">
                <div class="flex items-center justify-center space-x-1">
                  <!-- Edit Teacher Button -->
                  <button
                    v-if="session.id && session.status === 'scheduled'"
                    @click="openEditTeacherModal(session)"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 rounded hover:bg-indigo-100"
                    title="Thay đổi giáo viên"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <!-- Cancel Session Button -->
                  <button
                    v-if="session.id && session.status === 'scheduled'"
                    @click="openCancelSessionModal(session)"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-50 rounded hover:bg-red-100"
                    title="Báo nghỉ"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                  <!-- Quick Attendance Button -->
                  <button
                    v-if="(session.status === 'completed' || session.status === 'scheduled') && authStore.hasPermission('attendance.quick_mark')"
                    @click="openQuickAttendanceModal(session)"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 rounded hover:bg-blue-100"
                    :title="'Điểm danh nhanh'"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                  </button>
                  <!-- Comments Button -->
                  <button
                    v-if="session.status === 'completed' && authStore.hasPermission('class_comments.manage')"
                    @click="openCommentsModal(session)"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-purple-700 bg-purple-50 rounded hover:bg-purple-100"
                    :title="t('class_detail.comments') || 'Comments'"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                  </button>
                  <span v-if="!session.id" class="text-xs text-gray-400">-</span>
                </div>
              </td>
            </tr>

            <!-- Expanded Homework Panel Row -->
            <tr v-if="expandedSessions.has(session.id)" :key="`expanded-${session.id}`">
              <td colspan="9" class="px-0 py-0 bg-gray-50">
                <div class="p-4">
                  <SessionHomeworkPanel
                    :session="session"
                    :class-data="classData"
                    @reload="$emit('refresh')"
                  />
                </div>
              </td>
            </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="sessions.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
      <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
      </svg>
      <p class="text-gray-500">{{ t('class_detail.no_sessions') || 'No sessions yet' }}</p>
    </div>

    <!-- Quick Attendance Modal -->
    <QuickAttendanceModal
      :is-open="showQuickAttendanceModal"
      :session="selectedSession"
      :class-data="classData"
      @close="showQuickAttendanceModal = false"
      @saved="handleQuickAttendanceSaved"
    />

    <!-- Evaluation Modal -->
    <EvaluationModal
      :is-open="showEvaluationModal"
      :session="selectedSession"
      :class-data="classData"
      @close="showEvaluationModal = false"
      @saved="handleEvaluationSaved"
    />

    <!-- Comments Modal -->
    <CommentsModal
      v-if="showCommentsModal"
      :session="selectedSession"
      :class-data="classData"
      @close="showCommentsModal = false"
      @saved="handleCommentSaved"
    />

    <!-- Google Drive Files Modal -->
    <GoogleDriveFilesModal
      :is-open="showFilesModal"
      :folder-id="selectedFolderId"
      :title="filesModalTitle"
      :subtitle="filesModalSubtitle"
      @close="closeFilesModal"
    />

    <!-- Edit Session Teacher Modal -->
    <EditSessionTeacherModal
      v-if="showEditTeacherModal"
      :show="showEditTeacherModal"
      :session="selectedSession"
      :class-id="parseInt(classId)"
      @close="showEditTeacherModal = false"
      @changed="handleTeacherChanged"
    />

    <!-- Cancel Session Modal -->
    <CancelSessionModal
      v-if="showCancelSessionModal"
      :show="showCancelSessionModal"
      :session="selectedSession"
      :class-id="parseInt(classId)"
      :class-name="classData?.name || ''"
      @close="showCancelSessionModal = false"
      @cancelled="handleSessionCancelled"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useAuthStore } from '../../../stores/auth';
import api from '../../../services/api';
import dayjs from 'dayjs';
import isoWeek from 'dayjs/plugin/isoWeek';
import Swal from 'sweetalert2';
import QuickAttendanceModal from '../../../components/quality/QuickAttendanceModal.vue';
import EvaluationModal from '../../../components/quality/EvaluationModal.vue';
import CommentsModal from './CommentsModal.vue';
import GoogleDriveFilesModal from '../../../components/GoogleDriveFilesModal.vue';
import EditSessionTeacherModal from '../../../components/quality/EditSessionTeacherModal.vue';
import CancelSessionModal from '../../../components/quality/CancelSessionModal.vue';
import SessionHomeworkPanel from './SessionHomeworkPanel.vue';

dayjs.extend(isoWeek);

const { t } = useI18n();
const authStore = useAuthStore();

const props = defineProps({
  classId: {
    type: [String, Number],
    required: true
  },
  classData: {
    type: Object,
    default: null
  }
});

const sessions = ref([]);
const showQuickAttendanceModal = ref(false);
const showEvaluationModal = ref(false);
const showCommentsModal = ref(false);
const showEditTeacherModal = ref(false);
const showCancelSessionModal = ref(false);
const selectedSession = ref(null);
const syncing = ref(false);
const syncingFolders = ref(false);
const expandedSessions = ref(new Set());

// Google Drive Files Modal
const showFilesModal = ref(false);
const selectedFolderId = ref(null);
const filesModalTitle = ref('');
const filesModalSubtitle = ref('');

// Compute display sessions - use actual sessions or generate preview
const displaySessions = computed(() => {
  if (sessions.value.length > 0) {
    return sessions.value;
  }
  
  // If no sessions yet, generate preview based on class schedule
  if (!props.classData || !props.classData.start_date || !props.classData.total_sessions) {
    return [];
  }
  
  return generatePreviewSessions();
});

const generatePreviewSessions = () => {
  const startDate = dayjs(props.classData.start_date);
  const totalSessions = props.classData.total_sessions || 0;
  const schedules = props.classData.schedules || [];
  
  if (!schedules.length) return [];
  
  // Map day names to dayjs day numbers (0 = Sunday, 1 = Monday, etc.)
  const dayMap = {
    'sunday': 0,
    'monday': 1,
    'tuesday': 2,
    'wednesday': 3,
    'thursday': 4,
    'friday': 5,
    'saturday': 6
  };
  
  // Get scheduled days as numbers
  const scheduledDays = schedules.map(s => dayMap[s.day_of_week]).filter(d => d !== undefined);
  
  if (!scheduledDays.length) return [];
  
  const previewSessions = [];
  let currentDate = startDate;
  let sessionCount = 0;
  
  // Generate sessions up to total_sessions
  while (sessionCount < totalSessions) {
    const currentDay = currentDate.day();
    
    if (scheduledDays.includes(currentDay)) {
      // Find schedule for this day
      const schedule = schedules.find(s => dayMap[s.day_of_week] === currentDay);
      
      previewSessions.push({
        id: null, // No ID for preview
        scheduled_date: currentDate.format('YYYY-MM-DD'),
        status: 'preview', // Custom status for preview
        lesson_plan_session: null,
        class_schedule: schedule
      });
      
      sessionCount++;
    }
    
    currentDate = currentDate.add(1, 'day');
    
    // Safety limit to prevent infinite loop
    if (currentDate.diff(startDate, 'day') > 365) break;
  }
  
  return previewSessions;
};

const sessionStatusClass = (status) => {
  if (status === 'completed') return 'bg-green-100 text-green-800';
  if (status === 'scheduled') return 'bg-blue-100 text-blue-800';
  if (status === 'cancelled') return 'bg-red-100 text-red-800';
  if (status === 'rescheduled') return 'bg-yellow-100 text-yellow-800';
  if (status === 'preview') return 'bg-gray-100 text-gray-600'; // Preview status
  return 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY');
};

const toggleExpand = (sessionId) => {
  if (expandedSessions.value.has(sessionId)) {
    expandedSessions.value.delete(sessionId);
  } else {
    expandedSessions.value.add(sessionId);
  }
  // Trigger reactivity
  expandedSessions.value = new Set(expandedSessions.value);
};

const getEffectiveTeacher = (session) => {
  // Priority: session.teacher > classSchedule.teacher > homeroomTeacher
  if (session.teacher) {
    return session.teacher.name;
  }
  if (session.class_schedule?.teacher) {
    return session.class_schedule.teacher.name;
  }
  if (props.classData?.homeroom_teacher) {
    return props.classData.homeroom_teacher.name;
  }
  return null;
};

const getTeacherSource = (session) => {
  if (session.class_schedule?.teacher) {
    return 'Giáo viên theo lịch';
  }
  if (props.classData?.homeroom_teacher) {
    return 'Giáo viên chủ nhiệm';
  }
  return '';
};

const getSessionNumber = (session) => {
  // If session has session_number from backend, use it
  if (session.session_number) {
    return session.session_number;
  }
  
  // For preview sessions, calculate index
  const index = displaySessions.value.findIndex(s => 
    (s.id && s.id === session.id) || 
    (s.scheduled_date === session.scheduled_date && !s.id && !session.id)
  );
  return index >= 0 ? index + 1 : '?';
};

const loadSessions = async () => {
  try {
    const response = await api.get(`/api/classes/${props.classId}/sessions`);
    sessions.value = response.data.data;
  } catch (error) {
    console.error('Error loading sessions:', error);
  }
};

const openQuickAttendanceModal = (session) => {
  if (!session.id) {
    Swal.fire({
      icon: 'info',
      title: 'Buổi học chưa được tạo',
      text: 'Đây là buổi học dự kiến. Vui lòng đợi đến ngày học để điểm danh.',
    });
    return;
  }

  selectedSession.value = session;
  showQuickAttendanceModal.value = true;
};

const openEvaluationModal = (session) => {
  if (!session.id) {
    Swal.fire({
      icon: 'info',
      title: 'Buổi học chưa được tạo',
      text: 'Đây là buổi học dự kiến. Vui lòng đợi đến ngày học để đánh giá.',
    });
    return;
  }

  selectedSession.value = session;
  showEvaluationModal.value = true;
};

const openCommentsModal = (session) => {
  selectedSession.value = session;
  showCommentsModal.value = true;
};

const handleQuickAttendanceSaved = () => {
  // Don't auto-close modal - let user send Zalo notification
  // showQuickAttendanceModal.value = false;
  loadSessions();
};

const handleEvaluationSaved = () => {
  // Don't auto-close modal - let user send Zalo notification
  // showEvaluationModal.value = false;
  loadSessions();
};

const handleCommentSaved = () => {
  showCommentsModal.value = false;
  Swal.fire({
    icon: 'success',
    title: t('class_detail.comment_saved'),
    timer: 2000,
    showConfirmButton: false
  });
  loadSessions();
};

const openEditTeacherModal = async (session) => {
  console.log('🎯 [LessonSessionsTab] openEditTeacherModal called with session:', session);
  try {
    // Load full session data with relationships from API
    console.log('🔄 [LessonSessionsTab] Loading session from API:', `/api/classes/${props.classId}/sessions/${session.id}`);
    const response = await api.get(`/api/classes/${props.classId}/sessions/${session.id}`);

    if (response.data.success) {
      console.log('✅ [LessonSessionsTab] Session loaded successfully:', response.data.data);
      console.log('📋 [LessonSessionsTab] Session.class.subject:', response.data.data?.class?.subject);
      console.log('📋 [LessonSessionsTab] Session.class_schedule.subject:', response.data.data?.class_schedule?.subject);
      selectedSession.value = response.data.data;
      showEditTeacherModal.value = true;
      console.log('✅ [LessonSessionsTab] Modal opened, showEditTeacherModal =', showEditTeacherModal.value);
    }
  } catch (error) {
    console.error('❌ [LessonSessionsTab] Error loading session:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể tải thông tin buổi học',
      timer: 3000,
      showConfirmButton: false
    });
  }
};

const handleTeacherChanged = (updatedSession) => {
  showEditTeacherModal.value = false;
  Swal.fire({
    icon: 'success',
    title: 'Đã thay đổi giáo viên thành công',
    text: 'Giáo viên đã nhận thông báo qua Zalo',
    timer: 3000,
    showConfirmButton: false
  });
  loadSessions();
};

const openCancelSessionModal = async (session) => {
  try {
    // Load full session data with relationships from API
    const response = await api.get(`/api/classes/${props.classId}/sessions/${session.id}`);

    if (response.data.success) {
      selectedSession.value = response.data.data;
      showCancelSessionModal.value = true;
    }
  } catch (error) {
    console.error('Error loading session:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể tải thông tin buổi học',
      timer: 3000,
      showConfirmButton: false
    });
  }
};

const handleSessionCancelled = (result) => {
  showCancelSessionModal.value = false;
  const rescheduledCount = result.rescheduled_sessions || 0;

  let message = 'Đã báo nghỉ buổi học thành công!';
  if (rescheduledCount > 0) {
    message += `\nĐã điều chỉnh ${rescheduledCount} buổi học tiếp theo.`;
  }

  Swal.fire({
    icon: 'success',
    title: 'Báo nghỉ thành công',
    text: message,
    timer: 3000,
    showConfirmButton: false
  });
  loadSessions();
};

const openFilesModal = (fileType, session) => {
  let folderId = null;
  let title = '';
  let subtitle = '';

  if (fileType === 'lesson_plans' || fileType === 'lesson_plan') {
    folderId = session.lesson_plans_folder_id || session.google_drive_folder_id;
    title = 'Lesson Plans';
    subtitle = `Session ${session.session_number}: ${session.lesson_title}`;
  } else if (fileType === 'materials') {
    folderId = session.materials_folder_id;
    title = 'Teaching Materials';
    subtitle = `Session ${session.session_number}: ${session.lesson_title}`;
  } else if (fileType === 'homework') {
    folderId = session.homework_folder_id;
    title = 'Homework';
    subtitle = `Session ${session.session_number}: ${session.lesson_title}`;
  }

  if (folderId) {
    selectedFolderId.value = folderId;
    filesModalTitle.value = title;
    filesModalSubtitle.value = subtitle;
    showFilesModal.value = true;
  }
};

const closeFilesModal = () => {
  showFilesModal.value = false;
  selectedFolderId.value = null;
  filesModalTitle.value = '';
  filesModalSubtitle.value = '';
};

const syncFromSyllabus = async () => {
  const result = await Swal.fire({
    icon: 'warning',
    title: t('class_detail.confirm_sync_title') || 'Đồng bộ từ Giáo án?',
    html: t('class_detail.confirm_sync_message') || 
      'Hệ thống sẽ đồng bộ nội dung bài học và folder IDs từ giáo án.<br><br>' +
      '<strong>Lưu ý:</strong><br>' +
      '• Chỉ cập nhật những buổi học chưa điểm danh<br>' +
      '• Những buổi đã điểm danh sẽ không bị thay đổi<br>' +
      '• Folder IDs sẽ được cập nhật tự động',
    showCancelButton: true,
    confirmButtonText: t('common.confirm') || 'Xác nhận',
    cancelButtonText: t('common.cancel') || 'Hủy',
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#6b7280',
  });

  if (!result.isConfirmed) return;

  try {
    syncing.value = true;

    // Sync content from syllabus (includes folder IDs)
    const response = await api.post(`/api/classes/${props.classId}/sync-from-syllabus`);

    if (response.data.success) {
      // Also sync folder IDs to ensure all sessions have folder IDs
      try {
        await api.post(`/api/classes/${props.classId}/sync-folder-ids`);
      } catch (folderError) {
        // Log but don't fail the whole operation
      }
      
      await Swal.fire({
        icon: 'success',
        title: t('class_detail.sync_success') || 'Đồng bộ thành công!',
        html: `
          ${response.data.message}<br><br>
          <div class="text-left text-sm">
            ${response.data.data.created > 0 ? `<div>✨ Đã tạo mới: <strong>${response.data.data.created}</strong> buổi</div>` : ''}
            ${response.data.data.updated > 0 ? `<div>✅ Đã cập nhật: <strong>${response.data.data.updated}</strong> buổi</div>` : ''}
            ${response.data.data.skipped > 0 ? `<div>⏭️ Bỏ qua (đã điểm danh): <strong>${response.data.data.skipped}</strong> buổi</div>` : ''}
            <div>📊 Tổng: <strong>${response.data.data.total}</strong> buổi</div>
            <div class="mt-2 text-xs text-gray-600">📁 Folder IDs đã được đồng bộ</div>
          </div>
        `,
        timer: 5000,
        showConfirmButton: true
      });
      
      // Reload sessions
      loadSessions();
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error') || 'Lỗi',
      text: error.response?.data?.message || t('class_detail.sync_error') || 'Không thể đồng bộ từ giáo án',
    });
  } finally {
    syncing.value = false;
  }
};

const syncFolderIds = async () => {
  const result = await Swal.fire({
    icon: 'question',
    title: 'Đồng bộ Folder IDs?',
    html: 'Hệ thống sẽ đồng bộ folder IDs từ giáo án vào các buổi học của lớp.<br><br>' +
      '<strong>Lưu ý:</strong> Chỉ cập nhật folder IDs, không thay đổi nội dung bài học.',
    showCancelButton: true,
    confirmButtonText: t('common.confirm') || 'Xác nhận',
    cancelButtonText: t('common.cancel') || 'Hủy',
    confirmButtonColor: '#3b82f6',
    cancelButtonColor: '#6b7280',
  });

  if (!result.isConfirmed) return;

  try {
    syncingFolders.value = true;
    const response = await api.post(`/api/classes/${props.classId}/sync-folder-ids`);
    
    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Đồng bộ thành công!',
        html: `
          ${response.data.message}<br><br>
          <div class="text-left text-sm">
            <div>✅ Đã đồng bộ: <strong>${response.data.data.synced}</strong> buổi</div>
            <div>📊 Tổng: <strong>${response.data.data.total}</strong> buổi</div>
          </div>
        `,
        timer: 3000,
        showConfirmButton: true
      });
      
      // Reload sessions
      loadSessions();
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error') || 'Lỗi',
      text: error.response?.data?.message || 'Không thể đồng bộ folder IDs',
    });
  } finally {
    syncingFolders.value = false;
  }
};

onMounted(() => {
  loadSessions();
});
</script>

