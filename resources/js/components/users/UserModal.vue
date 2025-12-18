<template>
  <Transition name="modal-fade">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
      @click.self="close"
    >
      <div
        class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
          <h3 class="text-xl font-semibold text-gray-800">
            {{ isEdit ? t('users.edit') : t('users.create') }}
          </h3>
          <button
            @click="close"
            class="p-2 rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="saveUser" class="p-6 space-y-6">
          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('users.name') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              id="name"
              :placeholder="t('users.name_placeholder')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('users.email') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.email"
              type="email"
              id="email"
              :placeholder="t('users.email_placeholder')"
              :disabled="isEdit"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'bg-gray-100': isEdit }"
              required
            />
            <p v-if="isEdit" class="text-xs text-gray-500 mt-1">{{ t('users.email_readonly') }}</p>
          </div>

          <!-- Phone -->
          <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
              Số điện thoại <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.phone"
              type="tel"
              id="phone"
              placeholder="Nhập số điện thoại"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>

          <!-- Password -->
          <div v-if="!isEdit">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('users.password') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.password"
              type="password"
              id="password"
              :placeholder="t('users.password_placeholder')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
            <p class="text-xs text-gray-500 mt-1">{{ t('users.password_hint') }}</p>
          </div>

          <!-- Password Confirmation -->
          <div v-if="!isEdit">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('users.password_confirmation') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.password_confirmation"
              type="password"
              id="password_confirmation"
              :placeholder="t('users.password_confirmation_placeholder')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>

          <!-- Roles -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('users.roles') }}
            </label>
            <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
              <label
                v-for="role in availableRoles"
                :key="role.id"
                class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="role.id"
                  v-model="form.role_ids"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <div class="ml-3">
                  <div class="text-sm font-medium text-gray-900">{{ role.display_name || role.name }}</div>
                  <div v-if="role.description" class="text-xs text-gray-500">{{ role.description }}</div>
                </div>
              </label>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ t('users.roles_hint') }}</p>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ error }}</p>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? t('common.saving') : t('common.save') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  user: {
    type: Object,
    default: null,
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();
const swal = useSwal();

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  role_ids: [],
});

const availableRoles = ref([]);
const loading = ref(false);
const error = ref('');

const loadRoles = async () => {
  try {
    const response = await api.get('/api/roles');
    if (response.data.success) {
      availableRoles.value = response.data.data.filter(r => r.is_active);
    }
  } catch (err) {
    console.error('Failed to load roles:', err);
  }
};

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role_ids: [],
  };
  error.value = '';
};

const saveUser = async () => {
  loading.value = true;
  error.value = '';

  try {
    const payload = {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone,
      role_ids: form.value.role_ids,
    };

    if (!props.isEdit) {
      payload.password = form.value.password;
      payload.password_confirmation = form.value.password_confirmation;
    }

    let response;
    if (props.isEdit) {
      response = await api.put(`/api/users/${props.user.id}`, payload);
    } else {
      response = await api.post('/api/users', payload);
    }

    if (response.data.success) {
      swal.success(response.data.message);
      emit('saved');
    } else {
      error.value = response.data.message || 'Failed to save user.';
    }
  } catch (err) {
    console.error('User save error:', err);
    error.value = err.response?.data?.message || 'An unexpected error occurred.';
    if (err.response?.data?.errors) {
      const errors = Object.values(err.response.data.errors).flat();
      error.value += '\n' + errors.join('\n');
    }
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      loadRoles();
      if (props.isEdit && props.user) {
        form.value = {
          name: props.user.name,
          email: props.user.email,
          phone: props.user.phone || '',
          password: '',
          password_confirmation: '',
          role_ids: props.user.roles ? props.user.roles.map(r => r.id) : [],
        };
      } else {
        resetForm();
      }
    }
  },
  { immediate: true }
);

onMounted(() => {
  if (props.show) {
    loadRoles();
  }
});
</script>