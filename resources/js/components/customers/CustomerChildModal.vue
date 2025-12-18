<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
      @click.self="close"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
          <h3 class="text-lg font-bold text-gray-900">
            {{ child ? t('customers.edit_child') : t('customers.add_child') }}
          </h3>
          <button @click="close" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="save" class="p-6 space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.child_name') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('customers.child_name_placeholder')"
            />
          </div>

          <!-- Date of Birth & Gender -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('customers.date_of_birth') }}
              </label>
              <input
                v-model="form.date_of_birth"
                type="date"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('customers.gender') }}
              </label>
              <select
                v-model="form.gender"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="">{{ t('common.select') }}</option>
                <option value="male">{{ t('customers.male') }}</option>
                <option value="female">{{ t('customers.female') }}</option>
                <option value="other">{{ t('customers.other') }}</option>
              </select>
            </div>
          </div>

          <!-- School & Grade -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('customers.school') }}
              </label>
              <input
                v-model="form.school"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('customers.school_placeholder')"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('customers.grade') }}
              </label>
              <input
                v-model="form.grade"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('customers.grade_placeholder')"
              />
            </div>
          </div>

          <!-- Interests -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.interests') }}
            </label>
            <input
              v-model="form.interests"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('customers.interests_placeholder')"
            />
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.notes') }}
            </label>
            <textarea
              v-model="form.notes"
              rows="3"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('customers.child_notes_placeholder')"
            ></textarea>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
            >
              {{ loading ? t('common.saving') : t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  customer: {
    type: Object,
    default: null,
  },
  child: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();
const swal = useSwal();

const loading = ref(false);
const form = ref({
  name: '',
  date_of_birth: '',
  gender: '',
  school: '',
  grade: '',
  interests: '',
  notes: '',
});

const resetForm = () => {
  if (props.child) {
    form.value = {
      name: props.child.name || '',
      date_of_birth: props.child.date_of_birth || '',
      gender: props.child.gender || '',
      school: props.child.school || '',
      grade: props.child.grade || '',
      interests: props.child.interests || '',
      notes: props.child.notes || '',
    };
  } else {
    form.value = {
      name: '',
      date_of_birth: '',
      gender: '',
      school: '',
      grade: '',
      interests: '',
      notes: '',
    };
  }
};

const save = async () => {
  if (!props.customer?.id) return;

  loading.value = true;
  try {
    let response;
    if (props.child) {
      response = await api.put(
        `/api/customers/${props.customer.id}/children/${props.child.id}`,
        form.value
      );
    } else {
      response = await api.post(
        `/api/customers/${props.customer.id}/children`,
        form.value
      );
    }

    if (response.data.success) {
      swal.success(response.data.message);
      emit('saved');
    }
  } catch (error) {
    console.error('Failed to save child:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi lưu');
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm();
  }
});
</script>











