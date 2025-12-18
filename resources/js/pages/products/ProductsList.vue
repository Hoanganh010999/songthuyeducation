<template>
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ t('products.title') }}</h1>
                <p class="text-gray-600 mt-1">{{ t('products.list') }}</p>
            </div>
            <button v-if="can('products.create')" @click="openModal()" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <span class="text-xl">➕</span>
                {{ t('products.create') }}
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input v-model="filters.search" @input="loadProducts" type="text" 
                       :placeholder="t('common.search')"
                       class="px-4 py-2 border border-gray-300 rounded-lg">
                
                <select v-model="filters.type" @change="loadProducts" 
                        class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">{{ t('common.all') }} {{ t('products.type') }}</option>
                    <option value="course">{{ t('products.type_course') }}</option>
                    <option value="package">{{ t('products.type_package') }}</option>
                    <option value="material">{{ t('products.type_material') }}</option>
                    <option value="service">{{ t('products.type_service') }}</option>
                </select>

                <select v-model="filters.category" @change="loadProducts" 
                        class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">{{ t('common.all') }} {{ t('products.category') }}</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>

                <select v-model="filters.is_active" @change="loadProducts" 
                        class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">{{ t('common.all') }}</option>
                    <option :value="1">{{ t('products.active') }}</option>
                    <option :value="0">{{ t('common.inactive') }}</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('products.code') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('products.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('products.type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('products.price') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('products.total_sessions') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ product.code }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ product.name }}</div>
                            <div v-if="product.category" class="text-xs text-gray-500">{{ product.category }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ product.type }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div v-if="product.sale_price" class="space-y-1">
                                <div class="text-sm line-through text-gray-400">{{ formatCurrency(product.price) }}</div>
                                <div class="text-sm font-semibold text-green-600">{{ formatCurrency(product.sale_price) }}</div>
                            </div>
                            <div v-else class="text-sm font-semibold">{{ formatCurrency(product.price) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ product.total_sessions || '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="product.is_active" class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">{{ t('products.active') }}</span>
                            <span v-else class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">{{ t('common.inactive') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button v-if="can('products.edit')" @click="openModal(product)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">{{ t('common.edit') }}</button>
                            <button v-if="can('products.delete')" @click="deleteProduct(product)" 
                                    class="text-red-600 hover:text-red-900">{{ t('common.delete') }}</button>
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
                        <button v-for="page in visiblePages" :key="page" @click="loadProducts(page)"
                                :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                                class="px-3 py-1 border rounded hover:bg-blue-50">
                            {{ page }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Modal -->
        <ProductModal :show="showModal" :product="selectedProduct" @close="closeModal" @saved="handleSaved" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import Swal from 'sweetalert2';
import ProductModal from '../../components/products/ProductModal.vue';

const { t } = useI18n();
const authStore = useAuthStore();
const can = (permission) => authStore.hasPermission(permission);

const products = ref([]);
const categories = ref([]);
const filters = ref({
    search: '',
    type: '',
    category: '',
    is_active: '',
});
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0,
});

const showModal = ref(false);
const selectedProduct = ref(null);

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
    loadProducts();
    loadCategories();
});

const loadProducts = async (page = 1) => {
    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
            ...filters.value,
        };
        
        const res = await api.get('/api/products', { params });
        const responseData = res.data.data; // Pagination object
        products.value = responseData.data; // Array of products
        pagination.value = {
            current_page: responseData.current_page,
            last_page: responseData.last_page,
            per_page: responseData.per_page,
            total: responseData.total,
            from: responseData.from,
            to: responseData.to,
        };
    } catch (error) {
        console.error('Error loading products:', error);
        console.error('Response:', error.response);
    }
};

const loadCategories = async () => {
    try {
        const res = await api.get('/api/products/categories');
        categories.value = res.data.data;
    } catch (error) {
        console.error('Error loading categories:', error);
    }
};

const openModal = (product = null) => {
    selectedProduct.value = product;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedProduct.value = null;
};

const handleSaved = () => {
    closeModal();
    loadProducts(pagination.value.current_page);
};

const deleteProduct = async (product) => {
    const result = await Swal.fire({
        title: t('common.confirm_delete'),
        text: t('products.confirm_delete'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t('common.delete'),
        cancelButtonText: t('common.cancel'),
    });

    if (result.isConfirmed) {
        try {
            await api.delete(`/api/products/${product.id}`);
            Swal.fire(t('common.success'), t('products.deleted_success'), 'success');
            loadProducts(pagination.value.current_page);
        } catch (error) {
            Swal.fire(t('common.error'), error.response?.data?.message || t('common.error_occurred'), 'error');
        }
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};
</script>

