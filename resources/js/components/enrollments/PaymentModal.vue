<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="px-6 py-4 border-b border-gray-200 bg-green-600">
                <h3 class="text-xl font-bold text-white">💳 {{ t('enrollments.confirm_payment') }}</h3>
            </div>

            <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                <!-- Enrollment Info -->
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ t('enrollments.code') }}:</span>
                        <span class="font-medium">{{ enrollment?.code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ t('enrollments.customer') }}:</span>
                        <span class="font-medium">{{ enrollment?.customer?.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ t('enrollments.product') }}:</span>
                        <span class="font-medium">{{ enrollment?.product?.name }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2">
                        <span>{{ t('common.total') }}:</span>
                        <span class="text-blue-600">{{ formatCurrency(enrollment?.final_price) }}</span>
                    </div>
                    <div v-if="enrollment?.paid_amount > 0" class="flex justify-between text-sm text-gray-600">
                        <span>{{ t('enrollments.paid_amount') }}:</span>
                        <span>{{ formatCurrency(enrollment?.paid_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-red-600">
                        <span>{{ t('enrollments.remaining_amount') }}:</span>
                        <span>{{ formatCurrency(enrollment?.remaining_amount) }}</span>
                    </div>
                </div>

                <!-- Payment Form -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ t('enrollments.payment_method') }} *
                    </label>
                    <select v-model="form.payment_method" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="cash">{{ t('enrollments.payment_cash') }}</option>
                        <option value="bank_transfer">{{ t('enrollments.payment_bank') }}</option>
                        <option value="card">{{ t('enrollments.payment_card') }}</option>
                        <option value="wallet">{{ t('enrollments.payment_wallet') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ t('enrollments.payment_amount') }} *
                    </label>
                    <input v-model.number="form.amount" type="number" required min="1" 
                           :max="enrollment?.remaining_amount"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <button type="button" @click="form.amount = enrollment?.remaining_amount" 
                            class="mt-2 text-sm text-blue-600 hover:underline">
                        {{ t('enrollments.pay_full') }} ({{ formatCurrency(enrollment?.remaining_amount) }})
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ t('common.notes') }}
                    </label>
                    <textarea v-model="form.notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <!-- Preview -->
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <div class="text-sm text-blue-900 space-y-1">
                        <div>✓ {{ t('enrollments.wallet_deposit_notice') }}</div>
                        <div>✓ {{ t('enrollments.wallet_balance_after') }}: {{ formatCurrency((enrollment?.student?.wallet?.balance || 0) + form.amount) }}</div>
                        <div v-if="form.amount >= enrollment?.remaining_amount">
                            ✓ {{ t('enrollments.status_change_notice') }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="$emit('close')" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" :disabled="submitting"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2">
                        <span v-if="!submitting">💳</span>
                        {{ submitting ? t('common.processing') : t('enrollments.confirm_payment') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import api from '../../api';
import Swal from 'sweetalert2';

const { t } = useI18n();

const props = defineProps({
    show: Boolean,
    enrollment: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    payment_method: 'cash',
    amount: 0,
    notes: '',
});

const submitting = ref(false);

watch(() => props.enrollment, (newEnrollment) => {
    if (newEnrollment) {
        form.value.amount = newEnrollment.remaining_amount;
    }
});

const handleSubmit = async () => {
    submitting.value = true;
    try {
        await api.post(`/enrollments/${props.enrollment.id}/confirm-payment`, form.value);
        
        Swal.fire({
            icon: 'success',
            title: t('common.success'),
            text: t('enrollments.payment_success'),
            confirmButtonText: t('common.ok'),
        });
        
        emit('saved');
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: t('common.error'),
            text: error.response?.data?.message || t('common.error_occurred'),
        });
    } finally {
        submitting.value = false;
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};
</script>

