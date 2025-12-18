<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="px-6 py-4 border-b border-gray-200 bg-orange-600">
                <h3 class="text-xl font-bold text-white">
                    {{ isEdit ? t('vouchers.edit') : t('vouchers.create') }}
                </h3>
            </div>

            <form @submit.prevent="handleSubmit" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('vouchers.code') }} *</label>
                        <div class="flex gap-2">
                            <input v-model="form.code" type="text" required :disabled="isEdit"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500 disabled:bg-gray-100 uppercase font-mono">
                            <button v-if="!isEdit" type="button" @click="generateCode" 
                                    class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                                🎲
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('vouchers.name') }} *</label>
                        <input v-model="form.name" type="text" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('vouchers.description') }}</label>
                        <textarea v-model="form.description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('vouchers.discount_type') }} *</label>
                        <select v-model="form.type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500">
                            <option value="percentage">{{ t('vouchers.type_percentage') }}</option>
                            <option value="fixed_amount">{{ t('vouchers.type_fixed') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ form.type === 'percentage' ? t('vouchers.discount_percentage') : t('vouchers.discount_amount') }} *
                        </label>
                        <input v-model.number="form.value" type="number" required min="0" 
                               :max="form.type === 'percentage' ? 100 : null"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('vouchers.max_discount') }}</label>
                        <input v-model.number="form.max_discount_amount" type="number" min="0"
                               :disabled="form.type === 'fixed_amount'"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500 disabled:bg-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('vouchers.min_order') }}</label>
                        <input v-model.number="form.min_order_amount" type="number" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('vouchers.usage_limit') }}</label>
                        <input v-model.number="form.usage_limit" type="number" min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500">
                        <p class="text-xs text-gray-500 mt-1">{{ t('vouchers.usage_limit_hint') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('vouchers.user_usage_limit') }}</label>
                        <input v-model.number="form.usage_per_customer" type="number" min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500">
                        <p class="text-xs text-gray-500 mt-1">{{ t('vouchers.user_limit_hint') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('vouchers.start_date') }}</label>
                        <input v-model="form.start_date" type="date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('vouchers.end_date') }}</label>
                        <input v-model="form.end_date" type="date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500">
                        <p class="text-xs text-gray-500 mt-1">{{ t('vouchers.expiry_hint') }}</p>
                    </div>

                    <div class="col-span-2">
                        <label class="flex items-center">
                            <input v-model="form.is_active" type="checkbox" class="mr-2">
                            <span class="text-sm">{{ t('common.active') }}</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="$emit('close')" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" :disabled="submitting"
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50">
                        {{ submitting ? '...' : t('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useI18n } from '../../composables/useI18n';
import api from '../../services/api';
import Swal from 'sweetalert2';

const { t } = useI18n();

const props = defineProps({
    show: Boolean,
    voucher: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    code: '',
    name: '',
    description: '',
    type: 'percentage',
    value: 0,
    max_discount_amount: null,
    min_order_amount: null,
    usage_limit: null,
    usage_per_customer: 1,
    start_date: null,
    end_date: null,
    is_active: true,
});

const submitting = ref(false);

const isEdit = computed(() => !!props.voucher);

watch(() => props.voucher, (newVoucher) => {
    if (newVoucher) {
        form.value = {
            ...newVoucher,
            start_date: newVoucher.start_date ? newVoucher.start_date.split(' ')[0] : null,
            end_date: newVoucher.end_date ? newVoucher.end_date.split(' ')[0] : null,
        };
    } else {
        resetForm();
    }
});

const resetForm = () => {
    form.value = {
        code: '',
        name: '',
        description: '',
        type: 'percentage',
        value: 0,
        max_discount_amount: null,
        min_order_amount: null,
        usage_limit: null,
        usage_per_customer: 1,
        start_date: null,
        end_date: null,
        is_active: true,
    };
};

const generateCode = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    form.value.code = code;
};

const handleSubmit = async () => {
    submitting.value = true;
    try {
        if (isEdit.value) {
            await api.put(`/api/vouchers/${props.voucher.id}`, form.value);
            Swal.fire(t('common.success'), t('vouchers.updated_success'), 'success');
        } else {
            await api.post('/api/vouchers', form.value);
            Swal.fire(t('common.success'), t('vouchers.created_success'), 'success');
        }
        emit('saved');
    } catch (error) {
        Swal.fire(t('common.error'), error.response?.data?.message || t('common.error_occurred'), 'error');
    } finally {
        submitting.value = false;
    }
};
</script>

