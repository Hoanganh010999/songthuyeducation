<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-xl font-bold text-gray-900">
          {{ isEdit ? t('subjects.edit_subject') : t('subjects.create_subject') }}
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <form @submit.prevent="saveSubject" class="p-6 space-y-6">
        <!-- Subject Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('subjects.subject_name') }} <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            :placeholder="t('subjects.subject_name')"
          />
        </div>

        <!-- Subject Code -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('subjects.subject_code') }}
          </label>
          <input
            v-model="form.code"
            type="text"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            :placeholder="t('subjects.subject_code')"
          />
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('common.description') }}
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            :placeholder="t('common.description')"
          ></textarea>
        </div>

        <!-- Color Picker -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('subjects.subject_color') }}
          </label>
          <div class="flex items-center space-x-4">
            <input
              v-model="form.color"
              type="color"
              class="w-16 h-10 border border-gray-300 rounded cursor-pointer"
            />
            <span class="text-sm text-gray-600">{{ form.color }}</span>
            <div class="flex space-x-2">
              <button
                v-for="color in presetColors"
                :key="color"
                type="button"
                @click="form.color = color"
                class="w-8 h-8 rounded-full border-2 border-gray-300 hover:scale-110 transition"
                :style="{ backgroundColor: color }"
                :class="{ 'ring-2 ring-blue-500': form.color === color }"
              ></button>
            </div>
          </div>
        </div>

        <!-- Sort Order -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('common.sort_order') }}
          </label>
          <input
            v-model.number="form.sort_order"
            type="number"
            min="0"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Active Status -->
        <div class="flex items-center">
          <input
            v-model="form.is_active"
            type="checkbox"
            id="is_active"
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label for="is_active" class="ml-2 text-sm text-gray-700">
            {{ t('subjects.active') }}
          </label>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
          >
            {{ t('common.cancel') }}
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 flex items-center space-x-2"
          >
            <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ saving ? t('common.saving') : t('common.save') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();
const props = defineProps({
  subject: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const isEdit = computed(() => !!props.subject);

const presetColors = [
  '#3B82F6', // Blue
  '#10B981', // Green
  '#F59E0B', // Yellow
  '#EF4444', // Red
  '#8B5CF6', // Purple
  '#EC4899', // Pink
  '#14B8A6', // Teal
  '#F97316', // Orange
];

const form = ref({
  name: '',
  code: '',
  description: '',
  color: '#3B82F6',
  sort_order: 0,
  is_active: true,
  branch_id: null
});

const saveSubject = async () => {
  if (!form.value.name) {
    await Swal.fire({
      icon: 'warning',
      title: t('common.warning'),
      text: t('subjects.required_name'),
      confirmButtonText: 'OK'
    });
    return;
  }

  saving.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const data = { ...form.value, branch_id: branchId };

    if (isEdit.value) {
      await axios.put(`/api/quality/subjects/${props.subject.id}`, data);
      await Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('subjects.update_success'),
        confirmButtonText: 'OK'
      });
    } else {
      await axios.post('/api/quality/subjects', data);
      await Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('subjects.create_success'),
        confirmButtonText: 'OK'
      });
    }

    emit('saved');
  } catch (error) {
    console.error('Save subject error:', error);
    await Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('common.error_occurred'),
      confirmButtonText: 'OK'
    });
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  if (isEdit.value && props.subject) {
    form.value = {
      name: props.subject.name,
      code: props.subject.code || '',
      description: props.subject.description || '',
      color: props.subject.color || '#3B82F6',
      sort_order: props.subject.sort_order || 0,
      is_active: props.subject.is_active !== false,
      branch_id: props.subject.branch_id
    };
  }
});
</script>

