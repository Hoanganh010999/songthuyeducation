<template>
  <div class="feedback-form">
    <!-- Label -->
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Textarea -->
    <textarea
      v-model="localValue"
      :rows="rows"
      :maxlength="maxLength"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
      :class="{ 'border-red-300': error, 'bg-gray-50': disabled }"
      @input="handleInput"
      @blur="handleBlur"
    ></textarea>

    <!-- Character Count -->
    <div class="flex items-center justify-between mt-1">
      <div>
        <span v-if="error" class="text-xs text-red-500">{{ error }}</span>
      </div>
      <div v-if="showCharCount" class="text-xs text-gray-500">
        <span :class="{ 'text-red-500': isOverLimit }">
          {{ characterCount }}
        </span>
        <span v-if="maxLength"> / {{ maxLength }}</span>
      </div>
    </div>

    <!-- Helper Text -->
    <p v-if="helperText" class="mt-1 text-xs text-gray-500">
      {{ helperText }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Nhập nhận xét...'
  },
  rows: {
    type: Number,
    default: 4
  },
  maxLength: {
    type: Number,
    default: null
  },
  minLength: {
    type: Number,
    default: null
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  showCharCount: {
    type: Boolean,
    default: true
  },
  helperText: {
    type: String,
    default: ''
  },
  validateOnBlur: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'blur', 'input'])

const localValue = ref(props.modelValue)
const error = ref('')

// Watch for external changes
watch(() => props.modelValue, (newVal) => {
  localValue.value = newVal
})

// Character count
const characterCount = computed(() => {
  return localValue.value?.length || 0
})

// Is over limit
const isOverLimit = computed(() => {
  return props.maxLength && characterCount.value > props.maxLength
})

// Handle input
const handleInput = () => {
  error.value = ''
  emit('update:modelValue', localValue.value)
  emit('input', localValue.value)
}

// Handle blur
const handleBlur = () => {
  if (props.validateOnBlur) {
    validateInput()
  }
  emit('blur', localValue.value)
}

// Validate input
const validateInput = () => {
  error.value = ''

  if (props.required && !localValue.value.trim()) {
    error.value = 'Trường này là bắt buộc'
    return false
  }

  if (props.minLength && characterCount.value < props.minLength) {
    error.value = `Tối thiểu ${props.minLength} ký tự`
    return false
  }

  if (props.maxLength && characterCount.value > props.maxLength) {
    error.value = `Tối đa ${props.maxLength} ký tự`
    return false
  }

  return true
}

// Expose validate method
defineExpose({
  validate: validateInput,
  reset: () => {
    localValue.value = ''
    error.value = ''
  }
})
</script>

<style scoped>
textarea:focus {
  outline: none;
}
</style>
