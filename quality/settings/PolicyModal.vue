<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
        <h3 class="text-xl font-semibold">
          {{ policy ? t('attendance_fee.edit_policy') : t('attendance_fee.create_policy') }}
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('attendance_fee.policy_name') }} *
          </label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('attendance_fee.description') }}
          </label>
          <textarea
            v-model="form.description"
            rows="2"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
        </div>

        <!-- Unexcused Absence -->
        <div class="bg-red-50 p-4 rounded-lg space-y-3">
          <h4 class="font-semibold text-red-900">{{ t('attendance_fee.unexcused_absence') }}</h4>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('attendance_fee.absence_unexcused_percent') }} *
            </label>
            <div class="flex items-center gap-2">
              <input
                v-model.number="form.absence_unexcused_percent"
                type="number"
                min="0"
                max="100"
                step="0.01"
                required
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
              <span class="text-gray-600">%</span>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('attendance_fee.absence_consecutive_threshold') }} *
            </label>
            <input
              v-model.number="form.absence_consecutive_threshold"
              type="number"
              min="1"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            />
            <div class="mt-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
              <p class="text-xs text-blue-900 font-medium mb-1">📋 {{ t('attendance_fee.refund_mechanism') }}:</p>
              <ul class="text-xs text-blue-800 space-y-1 ml-4 list-disc">
                <li>{{ t('attendance_fee.refund_rule_1') }}</li>
                <li>{{ t('attendance_fee.refund_rule_2') }}</li>
                <li>{{ t('attendance_fee.refund_rule_3') }}</li>
                <li>{{ t('attendance_fee.refund_rule_4') }}</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Excused Absence -->
        <div class="bg-orange-50 p-4 rounded-lg space-y-3">
          <h4 class="font-semibold text-orange-900">{{ t('attendance_fee.excused_absence') }}</h4>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('attendance_fee.absence_excused_free_limit') }} *
            </label>
            <input
              v-model.number="form.absence_excused_free_limit"
              type="number"
              min="0"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            />
            <p class="text-xs text-gray-500 mt-1">{{ t('attendance_fee.absence_excused_free_limit_hint') }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('attendance_fee.absence_excused_percent') }} *
            </label>
            <div class="flex items-center gap-2">
              <input
                v-model.number="form.absence_excused_percent"
                type="number"
                min="0"
                max="100"
                step="0.01"
                required
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
              <span class="text-gray-600">%</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ t('attendance_fee.absence_excused_percent_hint') }}</p>
          </div>
        </div>

        <!-- Late -->
        <div class="bg-yellow-50 p-4 rounded-lg space-y-3">
          <h4 class="font-semibold text-yellow-900">{{ t('attendance_fee.late') }}</h4>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('attendance_fee.late_deduct_percent') }} *
            </label>
            <div class="flex items-center gap-2">
              <input
                v-model.number="form.late_deduct_percent"
                type="number"
                min="0"
                max="100"
                step="0.01"
                required
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
              <span class="text-gray-600">%</span>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('attendance_fee.late_grace_minutes') }} *
            </label>
            <div class="flex items-center gap-2">
              <input
                v-model.number="form.late_grace_minutes"
                type="number"
                min="0"
                required
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
              <span class="text-gray-600">{{ t('attendance_fee.minutes') }}</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ t('attendance_fee.late_grace_minutes_hint') }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('attendance_fee.late_penalty_threshold') }} *
            </label>
            <input
              v-model.number="form.late_penalty_threshold"
              type="number"
              min="1"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            />
            <p class="text-xs text-gray-500 mt-1">{{ t('attendance_fee.late_penalty_threshold_hint') }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('attendance_fee.late_penalty_amount') }} *
            </label>
            <div class="flex items-center gap-2">
              <input
                v-model.number="form.late_penalty_amount"
                type="number"
                min="0"
                step="1000"
                required
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
              <span class="text-gray-600">đ</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ t('attendance_fee.late_penalty_amount_hint') }}</p>
          </div>
        </div>

        <!-- Active -->
        <div class="flex items-center gap-3">
          <input
            v-model="form.is_active"
            type="checkbox"
            id="is_active"
            class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
          />
          <label for="is_active" class="text-sm font-medium text-gray-700">
            {{ t('attendance_fee.is_active') }}
          </label>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
          >
            {{ t('common.cancel') }}
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ saving ? t('common.saving') : t('common.save') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';

const { t } = useI18n();
const props = defineProps({
  policy: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const form = ref({
  name: '',
  description: '',
  absence_unexcused_percent: 100,
  absence_consecutive_threshold: 1,
  absence_excused_free_limit: 2,
  absence_excused_percent: 50,
  late_deduct_percent: 30,
  late_grace_minutes: 15,
  late_penalty_threshold: 3,
  late_penalty_amount: 50000,
  is_active: false
});

const handleSubmit = async () => {
  saving.value = true;
  try {
    if (props.policy) {
      await axios.put(`/api/quality/attendance-fee-policies/${props.policy.id}`, form.value);
      await Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('attendance_fee.policy_updated'),
        timer: 1500,
        showConfirmButton: false
      });
    } else {
      await axios.post('/api/quality/attendance-fee-policies', form.value);
      await Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('attendance_fee.policy_created'),
        timer: 1500,
        showConfirmButton: false
      });
    }
    emit('saved');
  } catch (error) {
    console.error('Error saving policy:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('common.error_occurred')
    });
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  if (props.policy) {
    Object.assign(form.value, props.policy);
  }
});
</script>

