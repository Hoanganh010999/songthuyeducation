<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-600 to-green-700">
                <h3 class="text-xl font-bold text-white">📝 {{ t('enrollments.create') }}</h3>
            </div>

            <form @submit.prevent="handleSubmit" class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Customer Info (Read-only) -->
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <h4 class="font-semibold text-blue-900 mb-3">👤 {{ t('enrollments.customer_info') }}</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <strong>{{ t('common.name') }}:</strong> {{ customer?.name }}
                        </div>
                        <div>
                            <strong>{{ t('common.phone') }}:</strong> {{ customer?.phone }}
                        </div>
                        <div v-if="customer?.email">
                            <strong>{{ t('common.email') }}:</strong> {{ customer?.email }}
                        </div>
                        <div v-if="customer?.branch">
                            <strong>{{ t('common.branch') }}:</strong> {{ customer.branch.name }}
                        </div>
                    </div>
                </div>

                <!-- Select Student -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ t('enrollments.select_student') }} *
                    </label>
                    <select v-model="form.student_selection" required @change="handleStudentChange"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">-- {{ t('enrollments.select_student') }} --</option>
                        <option value="customer">{{ customer?.name }} ({{ t('enrollments.for_self') }})</option>
                        <option v-for="child in customerChildren" :key="child.id" :value="`child-${child.id}`">
                            {{ child.name }} ({{ t('enrollments.for_child') }} - {{ child.age }} {{ t('common.years_old') }})
                        </option>
                    </select>
                </div>

                <!-- Select Product -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ t('enrollments.select_product') }} *
                    </label>
                    <select v-model="form.product_id" required @change="calculatePrice"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">-- {{ t('enrollments.select_product') }} --</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.name }} - {{ formatCurrency(product.sale_price || product.price) }}
                        </option>
                    </select>
                    <div v-if="selectedProduct" class="mt-2 p-3 bg-gray-50 rounded text-sm">
                        <div><strong>{{ t('products.type') }}:</strong> {{ t(`products.type_${selectedProduct.type}`) }}</div>
                        <div v-if="selectedProduct.total_sessions">
                            <strong>{{ t('products.total_sessions') }}:</strong> {{ selectedProduct.total_sessions }}
                        </div>
                        <div v-if="selectedProduct.duration_months">
                            <strong>{{ t('products.duration') }}:</strong> {{ selectedProduct.duration_months }} {{ t('common.months') }}
                        </div>
                    </div>
                </div>

                <!-- Apply Voucher -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ t('vouchers.title') }} ({{ t('common.optional') }})
                    </label>
                    <div class="flex gap-2">
                        <input v-model="form.voucher_code" type="text" 
                               :placeholder="t('vouchers.enter_code')"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <button type="button" @click="applyVoucher" :disabled="!form.voucher_code || checkingVoucher"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            {{ checkingVoucher ? '...' : t('vouchers.apply') }}
                        </button>
                    </div>
                    <div v-if="voucherError" class="mt-2 text-sm text-red-600">{{ voucherError }}</div>
                    <div v-if="appliedVoucher" class="mt-2 p-3 bg-green-50 border border-green-200 rounded text-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <strong class="text-green-700">✓ {{ appliedVoucher.name }}</strong>
                                <div class="text-gray-600">
                                    {{ t('enrollments.discount') }}: {{ appliedVoucher._calculatedDiscount 
                                        ? formatCurrency(appliedVoucher._calculatedDiscount)
                                        : (appliedVoucher.type === 'percentage' 
                                            ? `${appliedVoucher.value}%` 
                                            : formatCurrency(appliedVoucher.value)) }}
                                </div>
                            </div>
                            <button type="button" @click="removeVoucher" class="text-red-600 hover:text-red-800">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Apply Campaign -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ t('campaigns.title') }} ({{ t('common.optional') }})
                    </label>
                    <select v-model="form.campaign_id" @change="handleCampaignChange"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">-- {{ t('common.no_apply') }} --</option>
                        <option v-for="campaign in activeCampaigns" :key="campaign.id" :value="campaign.id">
                            {{ campaign.name }} ({{ campaign.discount_type === 'percentage' 
                                ? `${campaign.discount_value}%` 
                                : formatCurrency(campaign.discount_value) }} OFF)
                        </option>
                    </select>
                </div>

                <!-- Price Summary -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg p-4 border border-blue-200">
                    <h4 class="font-semibold text-blue-900 mb-3">💰 {{ t('enrollments.price_details') }}</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>{{ t('enrollments.original_price') }}:</span>
                            <span class="font-medium">{{ formatCurrency(pricing.original_price) }}</span>
                        </div>
                        <div v-if="pricing.discount_amount > 0" class="flex justify-between text-green-600">
                            <span>{{ t('enrollments.discount') }}:</span>
                            <span class="font-medium">-{{ formatCurrency(pricing.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t text-lg font-bold text-blue-600">
                            <span>{{ t('enrollments.final_price') }}:</span>
                            <span>{{ formatCurrency(pricing.final_price) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ t('common.notes') }}
                    </label>
                    <textarea v-model="form.notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="$emit('close')" 
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" :disabled="submitting || !canSubmit"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2">
                        <span v-if="!submitting">📝</span>
                        {{ submitting ? 'Đang tạo...' : 'Tạo đơn đăng ký' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import api from '../../services/api';
import Swal from 'sweetalert2';

const { t } = useI18n();

const props = defineProps({
    show: Boolean,
    customer: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    student_selection: '',
    student_type: '',
    student_id: null,
    product_id: '',
    voucher_code: '',
    voucher_id: null,
    campaign_id: '',
    notes: '',
});

const products = ref([]);
const customerChildren = ref([]);
const activeCampaigns = ref([]);
const appliedVoucher = ref(null);
const voucherError = ref('');
const checkingVoucher = ref(false);
const submitting = ref(false);

const pricing = ref({
    original_price: 0,
    discount_amount: 0,
    final_price: 0,
});

const selectedProduct = computed(() => {
    return products.value.find(p => p.id == form.value.product_id);
});

const canSubmit = computed(() => {
    return form.value.student_selection && form.value.product_id;
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        loadData();
        resetForm();
    }
});

onMounted(() => {
    if (props.show) {
        loadData();
    }
});

const loadData = async () => {
    try {
        const [productsRes, campaignsRes, childrenRes] = await Promise.all([
            api.get('/api/products', { params: { is_active: 1 } }),
            api.get('/api/campaigns', { params: { is_active: 1 } }),
            api.get(`/api/customers/${props.customer.id}/children`),
        ]);
        
        products.value = productsRes.data.data.data || productsRes.data.data;
        activeCampaigns.value = campaignsRes.data.data.data || campaignsRes.data.data;
        customerChildren.value = childrenRes.data.data || [];
    } catch (error) {
        console.error('Error loading data:', error);
    }
};

const handleStudentChange = () => {
    const selection = form.value.student_selection;
    if (selection === 'customer') {
        form.value.student_type = 'App\\Models\\Customer';
        form.value.student_id = props.customer.id;
    } else if (selection.startsWith('child-')) {
        form.value.student_type = 'App\\Models\\CustomerChild';
        form.value.student_id = parseInt(selection.replace('child-', ''));
    }
};

const handleCampaignChange = async () => {
    // If voucher is applied, re-validate it with new campaign
    if (appliedVoucher.value && form.value.voucher_code) {
        await applyVoucher();
    } else {
        calculatePrice();
    }
};

const calculatePrice = () => {
    if (!selectedProduct.value) {
        pricing.value = { original_price: 0, discount_amount: 0, final_price: 0 };
        return;
    }

    const originalPrice = parseFloat(selectedProduct.value.sale_price || selectedProduct.value.price);
    let discountAmount = 0;

    // Apply campaign discount first
    if (form.value.campaign_id) {
        const campaign = activeCampaigns.value.find(c => c.id == form.value.campaign_id);
        if (campaign) {
            if (campaign.discount_type === 'percentage') {
                let campaignDiscount = (originalPrice * campaign.discount_value) / 100;
                if (campaign.max_discount_amount) {
                    campaignDiscount = Math.min(campaignDiscount, parseFloat(campaign.max_discount_amount));
                }
                discountAmount += campaignDiscount;
            } else {
                discountAmount += parseFloat(campaign.discount_value);
            }
        }
    }

    // Apply voucher discount (use backend-calculated value for accuracy)
    if (appliedVoucher.value && appliedVoucher.value._calculatedDiscount) {
        const voucherDiscount = parseFloat(appliedVoucher.value._calculatedDiscount);
        discountAmount += voucherDiscount;
    }

    const finalPrice = Math.max(0, originalPrice - discountAmount);

    pricing.value = {
        original_price: originalPrice,
        discount_amount: discountAmount,
        final_price: finalPrice,
    };
};

const applyVoucher = async () => {
    if (!form.value.voucher_code) return;
    
    if (!form.value.product_id) {
        voucherError.value = t('enrollments.select_product_first');
        return;
    }

    checkingVoucher.value = true;
    voucherError.value = '';

    try {
        // Calculate base amount (after campaign discount if any)
        let amount = selectedProduct.value 
            ? parseFloat(selectedProduct.value.sale_price || selectedProduct.value.price)
            : 0;
        
        // If campaign is applied, deduct campaign discount first
        if (form.value.campaign_id) {
            const campaign = activeCampaigns.value.find(c => c.id == form.value.campaign_id);
            if (campaign) {
                let campaignDiscount = 0;
                if (campaign.discount_type === 'percentage') {
                    campaignDiscount = (amount * campaign.discount_value) / 100;
                    if (campaign.max_discount_amount) {
                        campaignDiscount = Math.min(campaignDiscount, parseFloat(campaign.max_discount_amount));
                    }
                } else {
                    campaignDiscount = parseFloat(campaign.discount_value);
                }
                amount = Math.max(0, amount - campaignDiscount);
            }
        }

        const res = await api.get('/api/vouchers/check', { 
            params: {
                code: form.value.voucher_code,
                customer_id: props.customer.id,
                product_id: form.value.product_id,
                amount: amount, // Amount after campaign discount
            }
        });

        // Backend returns: { voucher: {...}, discount_amount: xxx, final_amount: xxx }
        const responseData = res.data.data;
        
        appliedVoucher.value = responseData.voucher;
        form.value.voucher_id = appliedVoucher.value.id;
        
        // Store the calculated discount from backend
        appliedVoucher.value._calculatedDiscount = responseData.discount_amount;
        
        calculatePrice();
    } catch (error) {
        voucherError.value = error.response?.data?.message || 'Mã voucher không hợp lệ';
        appliedVoucher.value = null;
        form.value.voucher_id = null;
    } finally {
        checkingVoucher.value = false;
    }
};

const removeVoucher = () => {
    appliedVoucher.value = null;
    form.value.voucher_code = '';
    form.value.voucher_id = null;
    voucherError.value = '';
    calculatePrice();
};

const handleSubmit = async () => {
    if (!canSubmit.value) return;

    submitting.value = true;
    try {
        const payload = {
            customer_id: props.customer.id,
            student_type: form.value.student_type,
            student_id: form.value.student_id,
            product_id: form.value.product_id,
            voucher_id: form.value.voucher_id,
            campaign_id: form.value.campaign_id || null,
            notes: form.value.notes,
        };

        await api.post('/api/enrollments', payload);
        
        Swal.fire({
            icon: 'success',
            title: t('common.success'),
            text: t('enrollments.created_success'),
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

const resetForm = () => {
    form.value = {
        student_selection: '',
        student_type: '',
        student_id: null,
        product_id: '',
        voucher_code: '',
        voucher_id: null,
        campaign_id: '',
        notes: '',
    };
    appliedVoucher.value = null;
    voucherError.value = '';
    pricing.value = { original_price: 0, discount_amount: 0, final_price: 0 };
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};
</script>
