<template>
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ t('enrollments.title') }}</h1>
            <p class="text-gray-600 mt-1">{{ t('enrollments.list') }}</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-600">{{ t('enrollments.total_orders') }}</div>
                <div class="text-2xl font-bold text-gray-900">{{ stats.total || 0 }}</div>
            </div>
            <div class="bg-yellow-50 rounded-lg shadow p-4">
                <div class="text-sm text-yellow-700">{{ t('enrollments.status_pending') }}</div>
                <div class="text-2xl font-bold text-yellow-900">{{ stats.by_status?.pending || 0 }}</div>
            </div>
            <div class="bg-blue-50 rounded-lg shadow p-4">
                <div class="text-sm text-blue-700">{{ t('enrollments.status_paid') }}</div>
                <div class="text-2xl font-bold text-blue-900">{{ stats.by_status?.paid || 0 }}</div>
            </div>
            <div class="bg-green-50 rounded-lg shadow p-4">
                <div class="text-sm text-green-700">{{ t('enrollments.status_active') }}</div>
                <div class="text-2xl font-bold text-green-900">{{ stats.by_status?.active || 0 }}</div>
            </div>
            <div class="bg-purple-50 rounded-lg shadow p-4">
                <div class="text-sm text-purple-700">{{ t('enrollments.status_completed') }}</div>
                <div class="text-2xl font-bold text-purple-900">{{ stats.by_status?.completed || 0 }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input v-model="filters.search" @input="loadEnrollments" type="text" 
                       :placeholder="t('common.search')"
                       class="px-4 py-2 border border-gray-300 rounded-lg">
                
                <select v-model="filters.status" @change="loadEnrollments" 
                        class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">{{ t('common.all') }} {{ t('enrollments.status') }}</option>
                    <option value="pending">{{ t('enrollments.status_pending') }}</option>
                    <option value="paid">{{ t('enrollments.status_paid') }}</option>
                    <option value="active">{{ t('enrollments.status_active') }}</option>
                    <option value="completed">{{ t('enrollments.status_completed') }}</option>
                    <option value="cancelled">{{ t('enrollments.status_cancelled') }}</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('enrollments.customer') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('enrollments.student') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="min-width: 200px;">{{ t('enrollments.product') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.value') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('enrollments.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="width: 80px;">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="enrollment in enrollments" :key="enrollment.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ enrollment.customer?.name }}</div>
                            <div class="text-xs text-gray-500">{{ enrollment.customer?.phone }}</div>
                            <div class="text-xs text-gray-400 mt-1">{{ enrollment.code }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ enrollment.student?.name }}</div>
                            <div class="text-xs text-gray-500">
                                {{ enrollment.student_type.includes('Child') ? '👶 ' + t('enrollments.student_child') : '👤 ' + t('enrollments.student_self') }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ enrollment.product?.name }}</div>
                            <div v-if="enrollment.voucher" class="text-xs text-green-600">🎫 {{ enrollment.voucher_code }}</div>
                            <div v-else-if="enrollment.campaign" class="text-xs text-blue-600">📢 {{ enrollment.campaign.name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">{{ formatCurrency(enrollment.final_price) }}</div>
                            <div v-if="enrollment.discount_amount > 0" class="text-xs text-green-600">
                                -{{ formatCurrency(enrollment.discount_amount) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getStatusClass(enrollment.status)">
                                {{ getStatusText(enrollment.status) }}
                            </span>
                        </td>
                        <td class="px-2 py-2" style="width: 80px;">
                            <div class="flex flex-col gap-1">
                                <button @click="viewDetail(enrollment)" 
                                        class="text-gray-600 hover:text-gray-900 text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50 w-full"
                                        :title="t('common.view')">
                                    👁️
                                </button>
                                <button v-if="!enrollment.has_approved_income && can('enrollments.edit')"
                                        @click="editEnrollment(enrollment)" 
                                        class="text-indigo-600 hover:text-indigo-900 text-xs px-2 py-1 border border-indigo-300 rounded hover:bg-indigo-50 w-full"
                                        :title="t('common.edit')">
                                    ✏️
                                </button>
                                <button v-if="!enrollment.has_approved_income && can('enrollments.delete')"
                                        @click="deleteEnrollment(enrollment)" 
                                        class="text-red-600 hover:text-red-900 text-xs px-2 py-1 border border-red-300 rounded hover:bg-red-50 w-full"
                                        :title="t('common.delete')">
                                    🗑️
                                </button>
                            </div>
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
                        <button v-for="page in visiblePages" :key="page" @click="loadEnrollments(page)"
                                :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                                class="px-3 py-1 border rounded hover:bg-blue-50">
                            {{ page }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <EnrollmentDetailModal :show="showDetailModal" :enrollment="selectedEnrollment" 
                               @close="closeDetailModal" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import Swal from 'sweetalert2';
import EnrollmentDetailModal from '../../components/enrollments/EnrollmentDetailModal.vue';

const { t } = useI18n();
const authStore = useAuthStore();
const can = (permission) => authStore.hasPermission(permission);

const enrollments = ref([]);
const stats = ref({});
const filters = ref({
    search: '',
    status: '',
});
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0,
});

const showDetailModal = ref(false);
const selectedEnrollment = ref(null);

const visiblePages = computed(() => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;
    const delta = 2;
    for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
        pages.push(i);
    }
    return pages;
});

onMounted(() => {
    loadEnrollments();
    loadStats();
});

const loadEnrollments = async (page = 1) => {
    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
            ...filters.value,
        };
        
        const res = await api.get('/api/enrollments', { params });
        console.log('Enrollments API Response:', res.data);
        console.log('Enrollments data:', res.data.data);
        
        enrollments.value = res.data.data.data;
        pagination.value = {
            current_page: res.data.data.current_page,
            last_page: res.data.data.last_page,
            per_page: res.data.data.per_page,
            total: res.data.data.total,
            from: res.data.data.from,
            to: res.data.data.to,
        };
        
        console.log('Enrollments loaded:', enrollments.value.length);
    } catch (error) {
        console.error('Error loading enrollments:', error);
        console.error('Error response:', error.response);
    }
};

const loadStats = async () => {
    try {
        const res = await api.get('/api/enrollments/statistics');
        stats.value = res.data.data;
    } catch (error) {
        console.error('Error loading stats:', error);
    }
};

const viewDetail = (enrollment) => {
    selectedEnrollment.value = enrollment;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedEnrollment.value = null;
};

const editEnrollment = (enrollment) => {
    // TODO: Implement edit enrollment modal
    console.log('Edit enrollment:', enrollment);
    Swal.fire({
        title: t('common.info'),
        text: 'Chức năng sửa enrollment đang được phát triển',
        icon: 'info',
    });
};

const deleteEnrollment = async (enrollment) => {
    const result = await Swal.fire({
        title: t('common.confirm_delete'),
        text: t('enrollments.confirm_delete_text'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t('common.delete'),
        cancelButtonText: t('common.cancel'),
        confirmButtonColor: '#d33',
    });

    if (result.isConfirmed) {
        try {
            await api.delete(`/api/enrollments/${enrollment.id}`);
            Swal.fire(t('common.success'), t('enrollments.deleted_success'), 'success');
            loadEnrollments(pagination.value.current_page);
            loadStats();
        } catch (error) {
            Swal.fire(t('common.error'), error.response?.data?.message || t('common.error_occurred'), 'error');
        }
    }
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800',
        paid: 'px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800',
        active: 'px-2 py-1 text-xs rounded-full bg-green-100 text-green-800',
        completed: 'px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800',
        cancelled: 'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800',
    };
    return classes[status] || classes.pending;
};

const getStatusText = (status) => {
    return t(`enrollments.status_${status}`);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('vi-VN', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

