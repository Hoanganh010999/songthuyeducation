<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ t('campaigns.title') }}</h1>
                <p class="text-gray-600 mt-1">{{ t('campaigns.description') }}</p>
            </div>
            <button v-if="can('campaigns.create')" @click="openModal()" 
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center gap-2">
                <span class="text-xl">➕</span>
                {{ t('campaigns.create') }}
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input v-model="filters.search" @input="loadCampaigns" type="text" 
                       :placeholder="t('common.search')"
                       class="px-4 py-2 border border-gray-300 rounded-lg">
                
                <select v-model="filters.discount_type" @change="loadCampaigns" 
                        class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">{{ t('common.all') }} {{ t('campaigns.discount_type') }}</option>
                    <option value="percentage">{{ t('campaigns.type_percentage') }}</option>
                    <option value="fixed">{{ t('campaigns.type_fixed') }}</option>
                </select>

                <select v-model="filters.is_active" @change="loadCampaigns" 
                        class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">{{ t('common.all') }}</option>
                    <option :value="1">{{ t('common.active') }}</option>
                    <option :value="0">{{ t('common.inactive') }}</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('campaigns.code') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('campaigns.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('campaigns.discount') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('campaigns.period') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono font-bold text-purple-600">{{ campaign.code }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ campaign.name }}</div>
                            <div v-if="campaign.description" class="text-xs text-gray-500">{{ campaign.description }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="campaign.discount_type === 'percentage' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'">
                                {{ campaign.discount_type === 'percentage' 
                                    ? `${campaign.discount_value}%` 
                                    : formatCurrency(campaign.discount_value) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ formatDate(campaign.start_date) }}</div>
                            <div>→ {{ formatDate(campaign.end_date) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="campaign.is_active" class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                {{ t('common.active') }}
                            </span>
                            <span v-else class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                {{ t('common.inactive') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button v-if="can('campaigns.edit')" @click="openModal(campaign)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">{{ t('common.edit') }}</button>
                            <button v-if="can('campaigns.delete')" @click="deleteCampaign(campaign)" 
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
                        <button v-for="page in visiblePages" :key="page" @click="loadCampaigns(page)"
                                :class="page === pagination.current_page ? 'bg-purple-600 text-white' : 'bg-white text-gray-700'"
                                class="px-3 py-1 border rounded hover:bg-purple-50">
                            {{ page }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaign Modal -->
        <CampaignModal :show="showModal" :campaign="selectedCampaign" @close="closeModal" @saved="handleSaved" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import Swal from 'sweetalert2';
import CampaignModal from '../../components/campaigns/CampaignModal.vue';

const { t } = useI18n();
const authStore = useAuthStore();
const can = (permission) => authStore.hasPermission(permission);

const campaigns = ref([]);
const filters = ref({
    search: '',
    discount_type: '',
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
const selectedCampaign = ref(null);

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
    loadCampaigns();
});

const loadCampaigns = async (page = 1) => {
    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
            ...filters.value,
        };
        
        const res = await api.get('/api/campaigns', { params });
        const responseData = res.data.data;
        campaigns.value = responseData.data;
        pagination.value = {
            current_page: responseData.current_page,
            last_page: responseData.last_page,
            per_page: responseData.per_page,
            total: responseData.total,
            from: responseData.from,
            to: responseData.to,
        };
    } catch (error) {
        console.error('Error loading campaigns:', error);
    }
};

const openModal = (campaign = null) => {
    selectedCampaign.value = campaign;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedCampaign.value = null;
};

const handleSaved = () => {
    closeModal();
    loadCampaigns(pagination.value.current_page);
};

const deleteCampaign = async (campaign) => {
    const result = await Swal.fire({
        title: t('common.confirm_delete'),
        text: t('campaigns.confirm_delete'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t('common.delete'),
        cancelButtonText: t('common.cancel'),
    });

    if (result.isConfirmed) {
        try {
            await api.delete(`/api/campaigns/${campaign.id}`);
            Swal.fire(t('common.success'), t('campaigns.deleted_success'), 'success');
            loadCampaigns(pagination.value.current_page);
        } catch (error) {
            Swal.fire(t('common.error'), error.response?.data?.message || t('common.error_occurred'), 'error');
        }
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('vi-VN', { year: 'numeric', month: '2-digit', day: '2-digit' });
};
</script>

