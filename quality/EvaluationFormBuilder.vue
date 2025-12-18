<template>
  <div class="flex flex-col max-h-[95vh]">
    <!-- Header -->
    <div class="flex-shrink-0 bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-xl font-bold">Biểu mẫu đánh giá</h3>
          <p class="text-purple-100 text-sm mt-1">{{ unit.lesson_title || `Unit ${unit.session_number}` }}</p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            type="button"
            @click="showPreview = true"
            class="px-4 py-2 bg-white text-purple-600 rounded-lg hover:bg-purple-50 transition flex items-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <span>Xem trước</span>
          </button>
          <button
            type="button"
            @click="$emit('cancel')"
            class="text-white hover:text-purple-100"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Form Content - Scrollable -->
    <div class="flex-1 overflow-y-auto p-6">
      <form @submit.prevent="save" class="space-y-6" id="evaluationForm">
        <!-- Form Name -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
          <label class="block text-sm font-semibold text-gray-700 mb-2">Tên biểu mẫu *</label>
          <input 
            v-model="form.name" 
            type="text" 
            required 
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
            placeholder="Ví dụ: Đánh giá học viên buổi 1"
          />
          <p class="text-xs text-gray-500 mt-1">Tên này sẽ hiển thị khi giáo viên điểm danh</p>
        </div>

        <!-- Fields Builder -->
        <div class="border-t pt-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Các trường đánh giá</h3>
              <p class="text-sm text-gray-600 mt-1">Thêm các trường để đánh giá học viên</p>
            </div>
            <button 
              type="button" 
              @click="addField" 
              class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
              </svg>
              <span>Thêm trường</span>
            </button>
          </div>

          <!-- Empty State -->
          <div v-if="form.fields.length === 0" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <p class="mt-2 text-gray-500">Chưa có trường đánh giá nào</p>
            <p class="text-sm text-gray-400 mt-1">Nhấn "Thêm trường" để bắt đầu</p>
          </div>

          <!-- Fields List -->
          <div v-else class="space-y-4">
            <div 
              v-for="(field, index) in form.fields" 
              :key="index" 
              class="p-5 border-2 rounded-lg bg-white hover:border-purple-300 transition"
            >
              <!-- Field Header -->
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                    <span class="text-purple-600 font-semibold text-sm">{{ index + 1 }}</span>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-gray-500">{{ getFieldTypeLabel(field.field_type) }}</span>
                  </div>
                </div>
                <button 
                  type="button" 
                  @click="removeField(index)" 
                  class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                  title="Xóa trường"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>

              <div class="space-y-4">
                <!-- Field Type -->
                <div class="grid grid-cols-12 gap-4">
                  <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Loại trường</label>
                    <select 
                      v-model="field.field_type" 
                      required 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                    >
                      <option value="text">Văn bản (Editor)</option>
                      <option value="checkbox">Checkbox</option>
                      <option value="dropdown">Dropdown</option>
                    </select>
                  </div>

                  <!-- Required -->
                  <div class="col-span-2 flex items-end">
                    <label class="flex items-center space-x-2 cursor-pointer pb-2">
                      <input 
                        v-model="field.is_required" 
                        type="checkbox" 
                        class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500" 
                      />
                      <span class="text-sm text-gray-600">Bắt buộc</span>
                    </label>
                  </div>
                </div>

                <!-- Field Title -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tiêu đề trường *
                    <span class="text-xs font-normal text-gray-500">(Sẽ được in đậm và cỡ chữ lớn)</span>
                  </label>
                  <input 
                    v-model="field.field_title" 
                    type="text" 
                    required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                    placeholder="Ví dụ: Thái độ học tập"
                  />
                </div>

                <!-- Field Description -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Mô tả
                    <span class="text-xs font-normal text-gray-500">(Tùy chọn - Hướng dẫn cho người điền form)</span>
                  </label>
                  <textarea 
                    v-model="field.field_description" 
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                    placeholder="Ví dụ: Đánh giá thái độ tham gia học tập của học viên"
                  ></textarea>
                </div>
              </div>

              <!-- Field Config: Checkbox Items -->
              <div v-if="field.field_type === 'checkbox'" class="mt-4 p-4 bg-gray-50 rounded-lg border">
                <div class="flex items-center justify-between mb-3">
                  <h4 class="text-sm font-medium text-gray-700">Các checkbox</h4>
                  <button 
                    type="button" 
                    @click="field.field_config.checkboxes.push({ label: '', value: '' })" 
                    class="px-3 py-1 text-xs text-purple-600 bg-purple-50 hover:bg-purple-100 rounded transition"
                  >
                    + Thêm checkbox
                  </button>
                </div>
                <div class="space-y-3">
                  <div 
                    v-for="(checkbox, cbIndex) in field.field_config.checkboxes" 
                    :key="cbIndex" 
                    class="p-3 bg-white rounded-lg border"
                  >
                    <div class="flex items-start space-x-2">
                      <span class="text-sm text-gray-500 mt-2 w-6">{{ cbIndex + 1 }}.</span>
                      <div class="flex-1 space-y-2">
                        <input 
                          v-model="checkbox.label" 
                          type="text" 
                          class="w-full px-3 py-2 text-sm border rounded-lg" 
                          :placeholder="`Nhãn checkbox ${cbIndex + 1}`" 
                        />
                        <input 
                          v-model="checkbox.value" 
                          type="text" 
                          class="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50" 
                          :placeholder="`Giá trị lưu trữ (vd: skill_communication)`" 
                        />
                      </div>
                      <button 
                        type="button" 
                        @click="field.field_config.checkboxes.splice(cbIndex, 1)" 
                        class="p-2 text-red-600 hover:bg-red-50 rounded transition"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Field Config: Dropdown Options -->
              <div v-if="field.field_type === 'dropdown'" class="mt-4 p-4 bg-gray-50 rounded-lg border">
                <div class="flex items-center justify-between mb-3">
                  <h4 class="text-sm font-medium text-gray-700">Các tùy chọn dropdown</h4>
                  <button 
                    type="button" 
                    @click="field.field_config.options.push({ label: '', value: '' })" 
                    class="px-3 py-1 text-xs text-purple-600 bg-purple-50 hover:bg-purple-100 rounded transition"
                  >
                    + Thêm tùy chọn
                  </button>
                </div>
                <div class="space-y-3">
                  <div 
                    v-for="(option, optIndex) in field.field_config.options" 
                    :key="optIndex" 
                    class="p-3 bg-white rounded-lg border"
                  >
                    <div class="flex items-start space-x-2">
                      <span class="text-sm text-gray-500 mt-2 w-6">{{ optIndex + 1 }}.</span>
                      <div class="flex-1 space-y-2">
                        <input 
                          v-model="option.label" 
                          type="text" 
                          class="w-full px-3 py-2 text-sm border rounded-lg" 
                          :placeholder="`Nhãn hiển thị ${optIndex + 1}`" 
                        />
                        <input 
                          v-model="option.value" 
                          type="text" 
                          class="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50" 
                          :placeholder="`Giá trị lưu trữ (vd: excellent, good, average)`" 
                        />
                      </div>
                      <button 
                        type="button" 
                        @click="field.field_config.options.splice(optIndex, 1)" 
                        class="p-2 text-red-600 hover:bg-red-50 rounded transition"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </form>
    </div>

    <!-- Actions - Fixed Footer -->
    <div class="flex-shrink-0 bg-white border-t px-6 py-4">
      <div class="flex justify-end space-x-3">
        <button 
          type="button" 
          @click="$emit('cancel')" 
          class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
        >
          Hủy
        </button>
        <button 
          type="submit" 
          form="evaluationForm"
          :disabled="saving" 
          class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition flex items-center space-x-2"
        >
          <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>{{ saving ? 'Đang lưu...' : 'Lưu biểu mẫu' }}</span>
        </button>
      </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="showPreview" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
          <h3 class="text-xl font-bold text-gray-900">Xem trước biểu mẫu đánh giá</h3>
          <button @click="showPreview = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        
        <div class="p-6 space-y-6">
          <!-- Header -->
          <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-6 border border-purple-200">
            <h2 class="text-2xl font-bold text-gray-900">{{ form.name || 'Chưa có tên' }}</h2>
            <p class="text-sm text-gray-600 mt-1">Biểu mẫu cho: {{ unit.lesson_title }}</p>
          </div>

          <!-- Fields Preview -->
          <div class="bg-white border rounded-lg p-6 space-y-4">
            <div v-if="form.fields.length === 0" class="text-center py-8 text-gray-500">
              Chưa có trường đánh giá nào
            </div>
            <div v-else class="space-y-8">
              <div v-for="(field, index) in form.fields" :key="index" class="space-y-3">
                <!-- Field Title -->
                <h3 class="text-lg font-bold text-gray-900">
                  {{ field.field_title }}
                  <span v-if="field.is_required" class="text-red-500">*</span>
                </h3>

                <!-- Field Description -->
                <p v-if="field.field_description" class="text-sm text-gray-600 italic">
                  {{ field.field_description }}
                </p>

                <!-- Text Field - Editor Area -->
                <div v-if="field.field_type === 'text'" class="border border-gray-300 rounded-lg">
                  <div class="bg-gray-50 px-4 py-2 border-b text-xs text-gray-500">
                    Vùng nhập văn bản (Rich Text Editor)
                  </div>
                  <div class="px-4 py-3 min-h-[120px] bg-white">
                    <p class="text-sm text-gray-400 italic">Giáo viên sẽ sử dụng editor để nhập nội dung đánh giá ở đây...</p>
                  </div>
                </div>

                <!-- Checkbox Field -->
                <div v-if="field.field_type === 'checkbox'" class="space-y-3">
                  <div 
                    v-for="(checkbox, cbIndex) in field.field_config.checkboxes" 
                    :key="cbIndex" 
                    class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
                  >
                    <input type="checkbox" disabled class="mt-0.5 w-5 h-5 text-purple-600 rounded" />
                    <div class="flex-1">
                      <label class="text-gray-900 font-medium">{{ checkbox.label || `Checkbox ${cbIndex + 1}` }}</label>
                      <p v-if="checkbox.value" class="text-xs text-gray-500 mt-1">Giá trị: {{ checkbox.value }}</p>
                    </div>
                  </div>
                  <p v-if="field.field_config.checkboxes.length === 0" class="text-sm text-gray-400 italic p-3">
                    Chưa có checkbox nào. Thêm checkbox trong phần cấu hình.
                  </p>
                </div>

                <!-- Dropdown Field -->
                <div v-if="field.field_type === 'dropdown'">
                  <select disabled class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-gray-700">
                    <option value="">-- Chọn một tùy chọn --</option>
                    <option 
                      v-for="(option, i) in field.field_config.options" 
                      :key="i"
                      :value="option.value"
                    >
                      {{ option.label || `Tùy chọn ${i + 1}` }}
                    </option>
                  </select>
                  <p v-if="field.field_config.options.length === 0" class="text-sm text-gray-400 italic mt-2">
                    Chưa có tùy chọn nào. Thêm tùy chọn trong phần cấu hình.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Info Box -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start space-x-3">
              <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <div class="text-sm text-blue-800">
                <p class="font-medium">Lưu ý:</p>
                <p class="mt-1">Biểu mẫu này sẽ xuất hiện khi giáo viên điểm danh học viên ở buổi học tương ứng trong module Quản lý lớp học.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();
const props = defineProps({
  unit: { type: Object, required: true },
  valuationForm: { type: Object, default: null }
});
const emit = defineEmits(['saved', 'cancel']);

const saving = ref(false);
const showPreview = ref(false);
const form = ref({
  name: '',
  description: `Evaluation form for ${props.unit.lesson_title || 'Unit'}`,
  branch_id: localStorage.getItem('current_branch_id'),
  fields: []
});

const addField = () => {
  form.value.fields.push({
    field_type: 'text',
    field_title: '',
    field_description: '',
    is_required: false,
    field_config: {
      checkboxes: [],
      options: []
    }
  });
};

const removeField = (index) => {
  form.value.fields.splice(index, 1);
};

const getFieldTypeLabel = (type) => {
  const labels = {
    text: 'Văn bản (Editor)',
    checkbox: 'Checkbox',
    dropdown: 'Dropdown'
  };
  return labels[type] || type;
};

const save = async () => {
  saving.value = true;
  try {
    let formId;
    if (props.valuationForm) {
      await axios.put(`/api/valuation-forms/${props.valuationForm.id}`, form.value);
      formId = props.valuationForm.id;
      Swal.fire(t('common.success'), 'Đã cập nhật biểu mẫu đánh giá', 'success');
    } else {
      const response = await axios.post('/api/valuation-forms', form.value);
      formId = response.data.data.id;
      Swal.fire(t('common.success'), 'Đã tạo biểu mẫu đánh giá', 'success');
    }
    emit('saved', formId);
  } catch (error) {
    console.error('Save error:', error);
    Swal.fire(t('common.error'), error.response?.data?.message || 'Không thể lưu biểu mẫu', 'error');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  if (props.valuationForm) {
    form.value = {
      ...props.valuationForm,
      fields: (props.valuationForm.fields || []).map(field => ({
        ...field,
        field_config: {
          checkboxes: field.field_config?.checkboxes || [],
          options: field.field_config?.options || []
        }
      }))
    };
  } else {
    form.value.name = `Đánh giá - ${props.unit.lesson_title || 'Unit ' + props.unit.session_number}`;
  }
});
</script>
