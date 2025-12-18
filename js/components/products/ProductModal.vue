<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl">
            <div class="px-6 py-4 border-b border-gray-200 bg-blue-600">
                <h3 class="text-xl font-bold text-white">
                    {{ isEdit ? t('products.edit') : t('products.create') }}
                </h3>
            </div>

            <form @submit.prevent="handleSubmit" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('products.name') }} *</label>
                        <input v-model="form.name" type="text" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('products.type') }} *</label>
                        <select v-model="form.type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="course">{{ t('products.type_course') }}</option>
                            <option value="package">{{ t('products.type_package') }}</option>
                            <option value="material">{{ t('products.type_material') }}</option>
                            <option value="service">{{ t('products.type_service') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('products.category') }}</label>
                        <input v-model="form.category" type="text"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('products.price') }} *</label>
                        <input v-model.number="form.price" type="number" required min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('products.sale_price') }}</label>
                        <input v-model.number="form.sale_price" type="number" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('products.duration') }} ({{ t('common.months') }})</label>
                        <input v-model.number="form.duration_months" type="number" min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('products.total_sessions') }}</label>
                        <input v-model.number="form.total_sessions" type="number" min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('products.description') }}</label>
                        <textarea v-model="form.description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="flex items-center">
                            <input v-model="form.is_active" type="checkbox" class="mr-2">
                            <span class="text-sm">{{ t('products.active') }}</span>
                        </label>
                        <label class="flex items-center">
                            <input v-model="form.is_featured" type="checkbox" class="mr-2">
                            <span class="text-sm">{{ t('products.featured') }}</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="$emit('close')" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" :disabled="submitting"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
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
    product: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    name: '',
    type: 'course',
    category: '',
    price: 0,
    sale_price: null,
    duration_months: null,
    total_sessions: null,
    description: '',
    is_active: true,
    is_featured: false,
});

const submitting = ref(false);

const isEdit = computed(() => !!props.product);

watch(() => props.product, (newProduct) => {
    if (newProduct) {
        form.value = { ...newProduct };
    } else {
        resetForm();
    }
});

const resetForm = () => {
    form.value = {
        name: '',
        type: 'course',
        category: '',
        price: 0,
        sale_price: null,
        duration_months: null,
        total_sessions: null,
        description: '',
        is_active: true,
        is_featured: false,
    };
};

const handleSubmit = async () => {
    submitting.value = true;
    try {
        if (isEdit.value) {
            await api.put(`/api/products/${props.product.id}`, form.value);
            Swal.fire(t('common.success'), t('products.updated_success'), 'success');
        } else {
            await api.post('/api/products', form.value);
            Swal.fire(t('common.success'), t('products.created_success'), 'success');
        }
        emit('saved');
    } catch (error) {
        Swal.fire(t('common.error'), error.response?.data?.message || t('common.error_occurred'), 'error');
    } finally {
        submitting.value = false;
    }
};
</script>

