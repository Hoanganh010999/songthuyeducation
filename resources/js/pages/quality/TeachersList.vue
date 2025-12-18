<template>
  <div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-900">{{ t('teachers.title') }}</h2>
          <p class="text-sm text-gray-600 mt-1">
            {{ t('teachers.description') }}
          </p>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex items-center space-x-3">
          <button
            @click="showSettingsModal = true"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{{ t('teachers.settings_button') }}</span>
          </button>
          
          <button
            @click="loadTeachers"
            :disabled="loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 flex items-center space-x-2"
          >
            <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>{{ t('teachers.refresh') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6">
      <!-- Settings Required Warning -->
      <div v-if="!hasSettings" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
        <svg class="w-12 h-12 text-yellow-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p class="text-yellow-900 font-medium mb-2">{{ t('teachers.no_settings') }}</p>
        <p class="text-yellow-700 text-sm mb-4">
          {{ t('teachers.no_settings_description') }}
        </p>
        <button
          @click="showSettingsModal = true"
          class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition"
        >
          {{ t('teachers.setup_now') }}
        </button>
      </div>

      <!-- Loading State -->
      <div v-else-if="loading" class="text-center py-12">
        <svg class="animate-spin h-10 w-10 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600 mt-3">{{ t('teachers.loading_list') }}</p>
      </div>

      <!-- Teachers List -->
      <div v-else>
        <!-- Filter Info -->
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <div>
              <p class="text-sm font-medium text-blue-900">
                Lọc theo phòng ban: <span class="font-bold">{{ selectedDepartments.join(', ') }}</span>
              </p>
              <p class="text-xs text-blue-700 mt-1">
                {{ t('teachers.found') }} {{ teachers.length }} {{ t('teachers.teachers_count') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Teachers Table -->
        <div v-if="teachers.length > 0">
          <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('teachers.teacher') }}
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                  Bộ môn
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('teachers.department') }}
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                  Liên hệ
                </th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                  Ngày vào
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="teacher in teachers"
                :key="`${teacher.id}-${teacher.department_id}`"
                class="hover:bg-gray-50 transition"
              >
                <!-- Teacher Info -->
                <td class="px-3 py-3">
                  <div class="flex items-center">
                    <div class="w-9 h-9 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                      <span class="text-green-700 font-semibold text-sm">
                        {{ teacher.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div class="ml-3">
                      <div class="font-medium text-gray-900 text-sm">{{ teacher.name }}</div>
                      <div class="text-xs text-gray-500">ID: {{ teacher.id }}</div>
                    </div>
                  </div>
                </td>

                <!-- Subjects -->
                <td class="px-3 py-3">
                  <div v-if="teacher.subjects && teacher.subjects.length > 0">
                    <div v-for="(subject, index) in teacher.subjects" :key="subject.id" :class="{ 'mt-2': index > 0 }">
                      <div class="text-sm font-medium text-gray-900">{{ subject.name }}</div>
                      <div class="text-xs mt-0.5">
                        <span v-if="subject.is_head" class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                          Trưởng bộ môn
                        </span>
                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-600">
                          Giáo viên
                        </span>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-xs text-gray-400">Chưa phân môn</div>
                </td>

                <!-- Department + Role -->
                <td class="px-3 py-3">
                  <div class="text-sm text-gray-900">{{ teacher.department_name }}</div>
                  <div class="mt-1">
                    <span
                      v-if="teacher.is_head"
                      class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-red-100 text-red-800"
                    >
                      {{ t('teachers.head') }}
                    </span>
                    <span
                      v-else-if="teacher.is_deputy"
                      class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-orange-100 text-orange-800"
                    >
                      {{ t('teachers.deputy') }}
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600"
                    >
                      {{ t('teachers.employee') }}
                    </span>
                  </div>
                </td>

                <!-- Contact -->
                <td class="px-3 py-3">
                  <div class="text-sm text-gray-900">{{ teacher.email }}</div>
                  <div class="flex items-center space-x-1 mt-1">
                    <span class="text-xs text-gray-500">{{ teacher.phone || '-' }}</span>
                    <button
                      @click="openPhoneModal(teacher, 'Teacher')"
                      class="p-0.5 hover:bg-green-50 rounded transition"
                      :title="'Cập nhật SĐT'"
                    >
                      <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                      </svg>
                    </button>
                  </div>
                  <div v-if="teacher.google_email" class="flex items-center space-x-1 mt-1">
                    <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                      <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                    <span class="text-xs text-gray-600">{{ teacher.google_email }}</span>
                    <button
                      @click="openGoogleEmailModal(teacher, 'Teacher')"
                      class="p-0.5 hover:bg-blue-50 rounded transition"
                      :title="'Cập nhật Google Email'"
                    >
                      <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                    </button>
                  </div>
                  <div v-else class="flex items-center space-x-1 mt-1">
                    <span class="text-xs text-gray-400">Chưa có Google Email</span>
                    <button
                      @click="openGoogleEmailModal(teacher, 'Teacher')"
                      class="p-0.5 hover:bg-blue-50 rounded transition"
                      :title="'Thêm Google Email'"
                    >
                      <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                    </button>
                  </div>
                </td>

                <!-- Start Date -->
                <td class="px-3 py-3 text-sm text-gray-500">
                  {{ formatDate(teacher.start_date) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12 border border-gray-200 border-dashed rounded-lg">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="text-gray-600 font-medium mb-2">{{ t('teachers.no_teachers') }}</p>
          <p class="text-sm text-gray-500">
            Không có giáo viên trong phòng ban: {{ selectedDepartments.join(', ') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Settings Modal -->
    <div
      v-if="showSettingsModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showSettingsModal = false"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <TeacherSettingsModal
          @close="showSettingsModal = false"
          @saved="handleSettingsSaved"
        />
      </div>
    </div>

    <!-- Google Email Update Modal -->
    <UserGoogleEmailModal
      :show="showGoogleEmailModal"
      :user="selectedUser"
      :user-type="selectedUserType"
      @close="closeGoogleEmailModal"
      @updated="handleUpdated"
    />

    <!-- Phone Update Modal -->
    <UserPhoneUpdateModal
      :show="showPhoneModal"
      :user="selectedUser"
      :user-type="selectedUserType"
      @close="closePhoneModal"
      @updated="handleUpdated"
      @show-zalo-profile="handleShowZaloProfile"
    />

    <!-- Zalo User Profile Modal -->
    <ZaloUserProfileModal
      :show="showZaloProfileModal"
      :zalo-user="zaloUserData"
      @close="closeZaloProfileModal"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import TeacherSettingsModal from './TeacherSettingsModal.vue';
import UserGoogleEmailModal from '../../components/quality/UserGoogleEmailModal.vue';
import UserPhoneUpdateModal from '../../components/quality/UserPhoneUpdateModal.vue';
import ZaloUserProfileModal from '../../components/quality/ZaloUserProfileModal.vue';

const { t } = useI18n();
const authStore = useAuthStore();
const loading = ref(false);
const teachers = ref([]);
const selectedDepartments = ref([]);
const selectedDepartmentIds = ref([]);
const showSettingsModal = ref(false);

// Modal state
const showGoogleEmailModal = ref(false);
const showPhoneModal = ref(false);
const showZaloProfileModal = ref(false);
const selectedUser = ref(null);
const selectedUserType = ref('Teacher');
const zaloUserData = ref(null);

const hasSettings = computed(() => selectedDepartmentIds.value.length > 0);

const loadSettings = async () => {
  const branchId = localStorage.getItem('current_branch_id');
  if (!branchId) {
    selectedDepartmentIds.value = [];
    selectedDepartments.value = [];
    return;
  }

  try {
    const response = await axios.get('/api/quality/teachers/settings', {
      params: { branch_id: branchId }
    });
    selectedDepartmentIds.value = response.data.data.department_ids || [];

    // Load department names if we have IDs
    if (selectedDepartmentIds.value.length > 0) {
      const deptResponse = await axios.get('/api/quality/departments', {
        params: { branch_id: branchId }
      });
      const allDepartments = deptResponse.data.data || [];
      selectedDepartments.value = allDepartments
        .filter(dept => selectedDepartmentIds.value.includes(dept.id))
        .map(dept => dept.name);
    } else {
      selectedDepartments.value = [];
    }
  } catch (error) {
    console.error('Load settings error:', error);
    selectedDepartmentIds.value = [];
    selectedDepartments.value = [];
  }
};

const loadTeachers = async () => {
  // Load settings first
  await loadSettings();

  if (selectedDepartmentIds.value.length === 0) {
    teachers.value = [];
    return;
  }

  loading.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.get('/api/quality/teachers', {
      params: {
        department_ids: selectedDepartmentIds.value,
        branch_id: branchId
      }
    });

    teachers.value = response.data.data || [];
  } catch (error) {
    console.error('Load teachers error:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: error.response?.data?.message || 'Không thể tải danh sách giáo viên',
      confirmButtonText: 'OK'
    });
  } finally {
    loading.value = false;
  }
};

const handleSettingsSaved = async () => {
  showSettingsModal.value = false;
  await loadTeachers();
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN');
};

// Modal functions
const openGoogleEmailModal = (user, userType) => {
  selectedUser.value = user;
  selectedUserType.value = userType;
  showGoogleEmailModal.value = true;
};

const closeGoogleEmailModal = () => {
  showGoogleEmailModal.value = false;
  selectedUser.value = null;
};

const openPhoneModal = (user, userType) => {
  selectedUser.value = user;
  selectedUserType.value = userType;
  showPhoneModal.value = true;
};

const closePhoneModal = () => {
  showPhoneModal.value = false;
  selectedUser.value = null;
};

const handleShowZaloProfile = (zaloUser) => {
  zaloUserData.value = zaloUser;
  showZaloProfileModal.value = true;
};

const closeZaloProfileModal = () => {
  showZaloProfileModal.value = false;
  zaloUserData.value = null;
};

const handleUpdated = (updatedUser) => {
  // Refresh the teachers list to show updated info
  loadTeachers();
};

onMounted(() => {
  loadTeachers();
});
</script>

