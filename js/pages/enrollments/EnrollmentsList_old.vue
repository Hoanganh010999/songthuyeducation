<template>
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ t('enrollments.title') }}</h1>
            <p class="text-gray-600 mt-1">{{ t('enrollments.list') }}</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-600">Tổng đơn</div>
                <div class="text-2xl font-bold text-gray-900">{{ stats.total || 0 }}</div>
            </div>
            <div class="bg-yellow-50 rounded-lg shadow p-4">
                <div class="text-sm text-yellow-700">Chờ thanh toán</div>
                <div class="text-2xl font-bold text-yellow-900">{{ stats.by_status?.pending || 0 }}</div>
            </div>
            <div class="bg-blue-50 rounded-lg shadow p-4">
                <div class="text-sm text-blue-700">Đã thanh toán</div>
                <div class="text-2xl font-bold text-blue-900">{{ stats.by_status?.paid || 0 }}</div>
            </div>
            <div class="bg-green-50 rounded-lg shadow p-4">
                <div class="text-sm text-green-700">Đang học</div>
                <div class="text-2xl font-bold text-green-900">{{ stats.by_status?.active || 0 }}</div>
            </div>
            <div class="bg-purple-50 rounded-lg shadow p-4">
                <div class="text-sm text-purple-700">Hoàn thành</div>
                <div class="text-2xl font-bold text-purple-900">{{ stats.by_status?.completed || 0 }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input v-model="filters.search" @input="loadEnrollments" type="text" 
                       placeholder="Tìm mã đơn, tên khách hàng..."
                       class="px-4 py-2 border border-gray-300 rounded-lg">
                
                <select v-model="filters.status" @change="loadEnrollments" 
                        class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending">Chờ thanh toán</option>
                    <option value="paid">Đã thanh toán</option>
                    <option value="active">Đang học</option>
                    <option value="completed">Hoàn thành</option>
                    <option value="cancelled">Đã hủy</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Học viên</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giá trị</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="enrollment in enrollments" :key="enrollment.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ enrollment.code }}</div>
                            <div class="text-xs text-gray-500">{{ formatDate(enrollment.created_at) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ enrollment.customer?.name }}</div>
                            <div class="text-xs text-gray-500">{{ enrollment.customer?.phone }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ enrollment.student?.name }}</div>
                            <div class="text-xs text-gray-500">
                                {{ enrollment.student_type.includes('Child') ? '👶 Con' : '👤 Chính họ' }}
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
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button v-if="enrollment.status === 'pending' && can('enrollments.edit')" 
                                    @click="openPaymentModal(enrollment)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                💳 Thanh toán
                            </button>
                            <button @click="viewDetail(enrollment)" 
                                    class="text-gray-600 hover:text-gray-900">
                                👁️ Xem
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="pagination.total > 0" class="px-6 py-4 border-t">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Hiển thị {{ pagination.from }} - {{ pagination.to }} / {{ pagination.total }}
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

        <!-- Payment Modal -->
        <PaymentModal :show="showPaymentModal" :enrollment="selectedEnrollment" 
                      @close="closePaymentModal" @saved="handlePaymentSaved" />
        
        <!-- Detail Modal -->
        <EnrollmentDetailModal :show="showDetailModal" :enrollment="selectedEnrollment" 
                               @close="closeDetailModal" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import api from '../../api';
import PaymentModal from '../../components/enrollments/PaymentModal.vue';
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

const showPaymentModal = ref(false);
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
        
        const res = await api.get('/enrollments', { params });
        enrollments.value = res.data.data.data;
        pagination.value = {
            current_page: res.data.data.current_page,
            last_page: res.data.data.last_page,
            per_page: res.data.data.per_page,
            total: res.data.data.total,
            from: res.data.data.from,
            to: res.data.data.to,
        };
    } catch (error) {
        console.error('Error loading enrollments:', error);
    }
};

const loadStats = async () => {
    try {
        const res = await api.get('/enrollments/statistics');
        stats.value = res.data.data;
    } catch (error) {
        console.error('Error loading stats:', error);
    }
};

const openPaymentModal = (enrollment) => {
    selectedEnrollment.value = enrollment;
    showPaymentModal.value = true;
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
    selectedEnrollment.value = null;
};

const handlePaymentSaved = () => {
    closePaymentModal();
    loadEnrollments(pagination.value.current_page);
    loadStats();
};

const viewDetail = (enrollment) => {
    selectedEnrollment.value = enrollment;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedEnrollment.value = null;
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
    const texts = {
        pending: 'Chờ thanh toán',
        paid: 'Đã thanh toán',
        active: 'Đang học',
        completed: 'Hoàn thành',
        cancelled: 'Đã hủy',
    };
    return texts[status] || status;
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

