<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">{{ t('quality.students') }}</h1>
      <p class="text-gray-600 mt-1">{{ t('quality.students_description') }}</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadStudents" type="text" 
               :placeholder="t('common.search')"
               class="px-4 py-2 border border-gray-300 rounded-lg">
        
        <select v-model="filters.branch_id" @change="loadStudents" 
                class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">{{ t('common.all') }} {{ t('common.branch') }}</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
            </option>
        </select>

        <select v-model="filters.is_active" @change="loadStudents" 
                class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">{{ t('common.all_status') }}</option>
            <option :value="1">{{ t('common.active') }}</option>
            <option :value="0">{{ t('common.inactive') }}</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.name') }}</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.email') }}</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.phone') }}</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Google Email</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('quality.classes') }}</th>
            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('wallets.balance') }}</th>
            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ t('common.status') }}</th>
            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50">
            <td class="px-3 py-2 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ student.user?.name || 'N/A' }}</div>
                <div class="text-xs text-gray-500">{{ student.student_code }}</div>
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                {{ student.user?.email || 'N/A' }}
            </td>
            <td class="px-3 py-2 whitespace-nowrap">
                <div class="flex items-center space-x-1">
                    <span class="text-xs text-gray-500">{{ student.user?.phone || '-' }}</span>
                    <button
                        v-if="student.user"
                        @click="openPhoneModal(student.user, 'Student')"
                        class="p-1 hover:bg-green-50 rounded transition"
                        :title="t('quality.update_phone') || 'Cập nhật số điện thoại'"
                    >
                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-3 py-2 whitespace-nowrap">
                <div class="flex items-center space-x-1">
                    <div v-if="student.user?.google_email" class="flex items-center space-x-1">
                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        <span class="text-xs text-gray-900">{{ student.user.google_email }}</span>
                    </div>
                    <span v-else class="text-xs text-gray-400">-</span>
                    <button
                        v-if="student.user"
                        @click="openGoogleEmailModal(student.user, 'Student')"
                        class="p-1 hover:bg-blue-50 rounded transition"
                        :title="t('quality.update_google_email') || 'Cập nhật Google Email'"
                    >
                        <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-3 py-2 text-xs">
                <div v-if="student.classes && student.classes.length > 0" class="flex flex-col gap-1">
                    <span v-for="cls in student.classes" :key="cls.id" class="inline-flex items-center px-1.5 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800">
                        {{ cls.name }}
                    </span>
                </div>
                <div v-else class="flex items-center gap-1 text-orange-600">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs font-medium">{{ t('quality.no_class') }}</span>
                </div>
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-xs text-right font-semibold">
                <span v-if="student.effective_balance !== undefined" :class="student.effective_balance > 0 ? 'text-green-600' : 'text-gray-500'">
                    {{ formatCurrency(student.effective_balance) }}
                </span>
                <span v-else-if="student.wallet" :class="student.wallet.balance > 0 ? 'text-green-600' : 'text-gray-500'">
                    {{ formatCurrency(student.wallet.balance) }}
                </span>
                <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-3 py-2 whitespace-nowrap">
                <span v-if="student.is_active" class="px-1.5 py-0.5 text-xs rounded-full bg-green-100 text-green-800">
                    {{ t('common.active') }}
                </span>
                <span v-else class="px-1.5 py-0.5 text-xs rounded-full bg-gray-100 text-gray-800">
                    {{ t('common.inactive') }}
                </span>
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-center">
                <button
                    @click="showAddToClassModal(student)"
                    class="text-blue-600 hover:text-blue-900 text-xs font-medium"
                    :title="t('quality.add_to_class')"
                >
                    + {{ t('quality.add_to_class') }}
                </button>
            </td>
          </tr>
          <tr v-if="students.length === 0">
            <td colspan="8" class="px-3 py-6 text-center text-gray-500">
                {{ t('common.no_data') }}
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="px-6 py-4 border-t">
          <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700">
                  {{ t('common.showing') }} {{ pagination.from }} - {{ pagination.to }} {{ t('common.of') }} {{ pagination.total }}
              </div>
              <div class="flex gap-2">
                  <button v-for="page in visiblePages" :key="page" @click="loadStudents(page)"
                          :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                          class="px-3 py-1 border rounded hover:bg-blue-50">
                      {{ page }}
                  </button>
              </div>
          </div>
      </div>
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
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useRouter } from 'vue-router';
import api from '../../services/api';
import Swal from 'sweetalert2';
import UserGoogleEmailModal from '../../components/quality/UserGoogleEmailModal.vue';
import UserPhoneUpdateModal from '../../components/quality/UserPhoneUpdateModal.vue';
import ZaloUserProfileModal from '../../components/quality/ZaloUserProfileModal.vue';

const { t } = useI18n();
const router = useRouter();

const students = ref([]);
const branches = ref([]);
const filters = ref({
    search: '',
    branch_id: '',
    is_active: '',
});
const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: 15,
});

// Modal state
const showGoogleEmailModal = ref(false);
const showPhoneModal = ref(false);
const showZaloProfileModal = ref(false);
const selectedUser = ref(null);
const selectedUserType = ref('Student');
const zaloUserData = ref(null);

const visiblePages = computed(() => {
    const pages = [];
    const maxVisible = 5;
    let startPage = Math.max(1, pagination.value.current_page - Math.floor(maxVisible / 2));
    let endPage = Math.min(pagination.value.last_page, startPage + maxVisible - 1);

    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }
    return pages;
});

onMounted(() => {
    loadStudents();
    loadBranches();
});

const loadStudents = async (page = 1) => {
    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
            search: filters.value.search,
            status: filters.value.is_active ? 'active' : (filters.value.is_active === false ? 'inactive' : ''),
            branch_id: filters.value.branch_id || '',
        };
        
        const res = await api.get('/api/quality/students', { params });
        const responseData = res.data.data;
        students.value = responseData.data;
        
        pagination.value = {
            current_page: responseData.current_page,
            last_page: responseData.last_page,
            per_page: responseData.per_page,
            total: responseData.total,
            from: responseData.from,
            to: responseData.to,
        };
    } catch (error) {
        console.error('Error loading students:', error);
    }
};

const loadBranches = async () => {
    try {
        const res = await api.get('/api/branches/list');
        branches.value = res.data.data || res.data;
    } catch (error) {
        console.error('Error loading branches:', error);
    }
};

const showAddToClassModal = async (student) => {
    try {
        // Load available classes
        const classesRes = await api.get('/api/quality/classes', {
            params: { branch_id: student.branch_id }
        });
        
        const availableClasses = classesRes.data.data || [];
        
        if (availableClasses.length === 0) {
            await Swal.fire({
                icon: 'warning',
                title: t('quality.no_classes_available'),
                text: t('quality.create_class_first'),
                confirmButtonText: t('common.ok')
            });
            return;
        }
        
        const classOptions = {};
        availableClasses.forEach(cls => {
            classOptions[cls.id] = `${cls.name} (${cls.code})`;
        });
        
        const result = await Swal.fire({
            title: t('quality.add_student_to_class'),
            html: `
                <div class="text-left mb-4">
                    <p class="text-sm text-gray-600 mb-2">${t('quality.student')}: <strong>${student.user?.name}</strong></p>
                    <p class="text-sm text-gray-600">${t('quality.student_code')}: <strong>${student.student_code}</strong></p>
                </div>
            `,
            input: 'select',
            inputOptions: classOptions,
            inputPlaceholder: t('quality.select_class'),
            showCancelButton: true,
            confirmButtonText: t('common.add'),
            cancelButtonText: t('common.cancel'),
            inputValidator: (value) => {
                if (!value) {
                    return t('quality.please_select_class');
                }
            }
        });
        
        if (result.isConfirmed) {
            await api.post(`/api/quality/classes/${result.value}/students`, {
                student_id: student.user_id,
                enrollment_date: new Date().toISOString().split('T')[0],
                status: 'active'
            });
            
            await Swal.fire({
                icon: 'success',
                title: t('common.success'),
                text: t('quality.student_added_to_class'),
                timer: 1500,
                showConfirmButton: false
            });
            
            loadStudents(pagination.value.current_page);
        }
    } catch (error) {
        console.error('Error adding student to class:', error);
        await Swal.fire({
            icon: 'error',
            title: t('common.error'),
            text: error.response?.data?.message || t('common.error_occurred')
        });
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
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
    // Refresh the student list to show updated info
    loadStudents(pagination.value.current_page);
};
</script>

