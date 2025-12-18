<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="px-6 py-4 border-b border-gray-200 bg-purple-600">
                <h3 class="text-xl font-bold text-white">
                    {{ isEdit ? t('campaigns.edit') : t('campaigns.create') }}
                </h3>
            </div>

            <form @submit.prevent="handleSubmit" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('campaigns.code') }} *</label>
                        <div class="flex gap-2">
                            <input v-model="form.code" type="text" required :disabled="isEdit"
                                   placeholder="BLACKFRIDAY2025"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 disabled:bg-gray-100 uppercase font-mono">
                            <button v-if="!isEdit" type="button" @click="generateCode" 
                                    class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200" title="Generate Code">
                                🎲
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('campaigns.name') }} *</label>
                        <input v-model="form.name" type="text" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('campaigns.description') }}</label>
                        <textarea v-model="form.description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('campaigns.discount_type') }} *</label>
                        <select v-model="form.discount_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                            <option value="percentage">{{ t('campaigns.type_percentage') }}</option>
                            <option value="fixed">{{ t('campaigns.type_fixed') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ form.discount_type === 'percentage' ? t('campaigns.discount_percentage') : t('campaigns.discount_amount') }} *
                        </label>
                        <input v-model.number="form.discount_value" type="number" required min="0" 
                               :max="form.discount_type === 'percentage' ? 100 : null"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('campaigns.start_date') }} *</label>
                        <input v-model="form.start_date" type="date" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('campaigns.end_date') }} *</label>
                        <input v-model="form.end_date" type="date" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('campaigns.max_discount') }}</label>
                        <input v-model.number="form.max_discount_amount" type="number" min="0"
                               :disabled="form.discount_type === 'fixed'"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 disabled:bg-gray-100">
                        <p class="text-xs text-gray-500 mt-1">{{ t('campaigns.max_discount_hint') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('campaigns.min_order') }}</label>
                        <input v-model.number="form.min_order_amount" type="number" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                        <p class="text-xs text-gray-500 mt-1">{{ t('campaigns.min_order_hint') }}</p>
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
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50">
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
    campaign: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    code: '',
    name: '',
    description: '',
    discount_type: 'percentage',
    discount_value: 0,
    max_discount_amount: null,
    min_order_amount: null,
    start_date: '',
    end_date: '',
    is_active: true,
});

const submitting = ref(false);

const isEdit = computed(() => !!props.campaign);

watch(() => props.campaign, (newCampaign) => {
    if (newCampaign) {
        form.value = {
            ...newCampaign,
            start_date: newCampaign.start_date ? newCampaign.start_date.split(' ')[0] : '',
            end_date: newCampaign.end_date ? newCampaign.end_date.split(' ')[0] : '',
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
        discount_type: 'percentage',
        discount_value: 0,
        max_discount_amount: null,
        min_order_amount: null,
        start_date: '',
        end_date: '',
        is_active: true,
    };
};

const generateCode = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = 'CAMP';
    for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    form.value.code = code;
};

const handleSubmit = async () => {
    submitting.value = true;
    try {
        if (isEdit.value) {
            await api.put(`/api/campaigns/${props.campaign.id}`, form.value);
            Swal.fire(t('common.success'), t('campaigns.updated_success'), 'success');
        } else {
            await api.post('/api/campaigns', form.value);
            Swal.fire(t('common.success'), t('campaigns.created_success'), 'success');
        }
        emit('saved');
    } catch (error) {
        Swal.fire(t('common.error'), error.response?.data?.message || t('common.error_occurred'), 'error');
    } finally {
        submitting.value = false;
    }
};
</script>

