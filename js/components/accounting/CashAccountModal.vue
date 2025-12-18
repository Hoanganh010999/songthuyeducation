<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-black opacity-50" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
        <h3 class="text-xl font-semibold mb-4">
          {{ isEditMode ? t('accounting.edit_cash_account') : t('accounting.create_cash_account') }}
        </h3>

        <form @submit.prevent="handleSubmit">
          <div class="space-y-4">
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.name') }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.name_placeholder')"
              />
            </div>

            <!-- Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.type') }} <span class="text-red-500">*</span>
              </label>
              <select
                v-model="formData.type"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="cash">{{ t('accounting.cash') }}</option>
                <option value="bank">{{ t('accounting.bank') }}</option>
              </select>
            </div>

            <!-- Account Number (for bank) -->
            <div v-if="formData.type === 'bank'">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.account_number') }}
              </label>
              <input
                v-model="formData.account_number"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.account_number_placeholder')"
              />
            </div>

            <!-- Bank Name (for bank) -->
            <div v-if="formData.type === 'bank'">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.bank_name') }}
              </label>
              <input
                v-model="formData.bank_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.bank_name_placeholder')"
              />
            </div>

            <!-- Initial Balance -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.initial_balance') }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model.number="formData.balance"
                type="number"
                step="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.amount_placeholder')"
              />
            </div>

            <!-- Branch (Optional) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('common.branch') }}
              </label>
              <select
                v-model="formData.branch_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option :value="null">{{ t('common.all') }} {{ t('common.branch') }}</option>
                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                  {{ branch.name }}
                </option>
              </select>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.description') }}
              </label>
              <textarea
                v-model="formData.description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.description_placeholder')"
              ></textarea>
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
              <input
                v-model="formData.is_active"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label class="ml-2 text-sm text-gray-700">
                {{ t('accounting.active') }}
              </label>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-2 mt-6">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ t('accounting.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50"
            >
              {{ saving ? t('accounting.saving') : t('accounting.save') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import { useAccountingSwal } from './useAccountingSwal';

const { t } = useI18n();
const { showSuccess, showError } = useAccountingSwal();

const branches = ref([]);

const props = defineProps({
  isOpen: Boolean,
  account: Object
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const isEditMode = ref(false);

const formData = ref({
  name: '',
  type: 'cash',
  account_number: '',
  bank_name: '',
  balance: 0,
  branch_id: null,
  description: '',
  is_active: true
});

watch(() => props.account, (newAccount) => {
  if (newAccount) {
    isEditMode.value = true;
    formData.value = {
      name: newAccount.name || '',
      type: newAccount.type || 'cash',
      account_number: newAccount.account_number || '',
      bank_name: newAccount.bank_name || '',
      balance: newAccount.balance || 0,
      branch_id: newAccount.branch_id || null,
      description: newAccount.description || '',
      is_active: newAccount.is_active !== undefined ? newAccount.is_active : true
    };
  } else {
    isEditMode.value = false;
    formData.value = {
      name: '',
      type: 'cash',
      account_number: '',
      bank_name: '',
      balance: 0,
      branch_id: null,
      description: '',
      is_active: true
    };
  }
}, { immediate: true });

const handleSubmit = async () => {
  saving.value = true;
  try {
    let response;
    if (isEditMode.value) {
      response = await axios.put(`/api/accounting/cash-accounts/${props.account.id}`, formData.value);
      console.log('✅ Cash account updated:', response.data);
    } else {
      response = await axios.post('/api/accounting/cash-accounts', formData.value);
      console.log('✅ Cash account created:', response.data);
    }
    await showSuccess(t('accounting.cash_account_saved'));
    emit('saved', response.data.data);
    emit('close');
  } catch (error) {
    console.error('❌ Error saving cash account:', error);
    await showError(error.response?.data?.message || t('accounting.save_error'));
  } finally {
    saving.value = false;
  }
};

const loadBranches = async () => {
  try {
    const response = await axios.get('/api/branches/list');
    branches.value = response.data.data.data || response.data.data || [];
  } catch (error) {
    console.error('Error loading branches:', error);
  }
};

onMounted(() => {
  loadBranches();
});
</script>

