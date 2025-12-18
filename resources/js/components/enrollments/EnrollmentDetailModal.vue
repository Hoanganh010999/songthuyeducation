<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">Chi tiết đơn hàng</h3>
                    <button @click="$emit('close')" class="text-white hover:bg-white/20 rounded-full p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-6" v-if="enrollment">
                <!-- Status Badge -->
                <div class="flex items-center justify-between">
                    <span :class="getStatusClass(enrollment.status)" class="text-lg">
                        {{ getStatusText(enrollment.status) }}
                    </span>
                    <span class="text-sm text-gray-500">{{ enrollment.code }}</span>
                </div>

                <!-- Customer & Student Info -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3">👤 Khách hàng</h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Tên:</strong> {{ enrollment.customer?.name }}</div>
                            <div><strong>SĐT:</strong> {{ enrollment.customer?.phone }}</div>
                            <div v-if="enrollment.customer?.email"><strong>Email:</strong> {{ enrollment.customer?.email }}</div>
                        </div>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3">
                            {{ enrollment.student_type.includes('Child') ? '👶 Học viên (Con)' : '👤 Học viên (Chính họ)' }}
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Tên:</strong> {{ enrollment.student?.name }}</div>
                            <div v-if="enrollment.student?.age"><strong>Tuổi:</strong> {{ enrollment.student.age }}</div>
                        </div>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="bg-white border rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">📦 Sản phẩm</h4>
                    <div class="space-y-2">
                        <div class="text-lg font-medium">{{ enrollment.product?.name }}</div>
                        <div v-if="enrollment.product?.total_sessions" class="text-sm text-gray-600">
                            Tổng số buổi: {{ enrollment.product.total_sessions }}
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                    <h4 class="font-semibold text-gray-900 mb-3">💰 Thông tin giá</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Giá gốc:</span>
                            <span class="font-medium">{{ formatCurrency(enrollment.original_price) }}</span>
                        </div>
                        <div v-if="enrollment.discount_amount > 0" class="flex justify-between text-green-600">
                            <span>Giảm giá:</span>
                            <span class="font-medium">-{{ formatCurrency(enrollment.discount_amount) }}</span>
                        </div>
                        <div v-if="enrollment.voucher" class="text-xs text-green-700 bg-green-100 px-2 py-1 rounded">
                            🎫 Voucher: {{ enrollment.voucher_code }}
                        </div>
                        <div v-if="enrollment.campaign" class="text-xs text-blue-700 bg-blue-100 px-2 py-1 rounded">
                            📢 Campaign: {{ enrollment.campaign.name }}
                        </div>
                        <div class="flex justify-between pt-3 border-t text-lg font-bold">
                            <span>Thành tiền:</span>
                            <span class="text-blue-600">{{ formatCurrency(enrollment.final_price) }}</span>
                        </div>
                        <div v-if="enrollment.paid_amount > 0" class="flex justify-between text-sm">
                            <span class="text-gray-600">Đã thanh toán:</span>
                            <span class="text-green-600 font-medium">{{ formatCurrency(enrollment.paid_amount) }}</span>
                        </div>
                        <div v-if="enrollment.remaining_amount > 0" class="flex justify-between text-sm">
                            <span class="text-gray-600">Còn lại:</span>
                            <span class="text-red-600 font-medium">{{ formatCurrency(enrollment.remaining_amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Sessions Info -->
                <div v-if="enrollment.total_sessions" class="bg-white border rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">📚 Tiến độ học</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Tổng số buổi:</span>
                            <span class="font-medium">{{ enrollment.total_sessions }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Đã học:</span>
                            <span class="font-medium text-green-600">{{ enrollment.attended_sessions }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Còn lại:</span>
                            <span class="font-medium text-blue-600">{{ enrollment.remaining_sessions }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                            <div class="bg-blue-600 h-2 rounded-full" 
                                 :style="{ width: `${(enrollment.attended_sessions / enrollment.total_sessions) * 100}%` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="enrollment.notes" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">📝 Ghi chú</h4>
                    <p class="text-sm text-gray-700">{{ enrollment.notes }}</p>
                </div>

                <!-- Dates -->
                <div class="bg-white border rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">📅 Thời gian</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Ngày tạo:</span>
                            <span>{{ formatDate(enrollment.created_at) }}</span>
                        </div>
                        <div v-if="enrollment.start_date" class="flex justify-between">
                            <span class="text-gray-600">Ngày bắt đầu:</span>
                            <span>{{ formatDate(enrollment.start_date) }}</span>
                        </div>
                        <div v-if="enrollment.end_date" class="flex justify-between">
                            <span class="text-gray-600">Dự kiến kết thúc:</span>
                            <span>{{ formatDate(enrollment.end_date) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
                <button @click="$emit('close')" 
                        class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    Đóng
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

defineProps({
    show: Boolean,
    enrollment: Object,
});

defineEmits(['close']);

const getStatusClass = (status) => {
    const classes = {
        pending: 'px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800',
        paid: 'px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800',
        active: 'px-3 py-1 text-sm rounded-full bg-green-100 text-green-800',
        completed: 'px-3 py-1 text-sm rounded-full bg-purple-100 text-purple-800',
        cancelled: 'px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-800',
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
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('vi-VN', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit' 
    });
};
</script>

