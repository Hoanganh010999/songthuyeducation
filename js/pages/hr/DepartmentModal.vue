<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
      <h3 class="text-xl font-bold mb-4">
        {{ department ? 'Chỉnh sửa phòng ban' : 'Tạo phòng ban mới' }}
      </h3>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Tên phòng ban *</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
            required
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Job Title mặc định</label>
          <select
            v-model="form.default_position_id"
            class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
          >
            <option :value="null">Không có</option>
            <option v-for="pos in positions" :key="pos.id" :value="pos.id">
              {{ pos.name }}
            </option>
          </select>
          <p class="text-xs text-gray-500 mt-1">Nhân viên trong phòng ban sẽ thừa kế quyền từ Job Title</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Mô tả</label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>
      </div>
      
      <div class="flex gap-2 mt-6">
        <button
          @click="$emit('close')"
          class="flex-1 px-4 py-2 border rounded hover:bg-gray-50"
        >
          Hủy
        </button>
        <button
          @click="save"
          :disabled="!form.name"
          class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
        >
          Lưu
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  department: Object,
  positions: Array,
  parentId: Number
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
  name: '',
  description: '',
  default_position_id: null
});

watch(() => [props.department, props.parentId], ([dept, parentId]) => {
  if (dept) {
    form.value = {
      name: dept.name,
      description: dept.description || '',
      default_position_id: dept.default_position_id || null,
      parent_id: dept.parent_id || null
    };
  } else {
    form.value = {
      name: '',
      description: '',
      default_position_id: null,
      parent_id: parentId || null
    };
  }
}, { immediate: true });

const save = async () => {
  try {
    const payload = { ...form.value };
    
    if (props.department) {
      await axios.put(`/api/hr/departments/${props.department.id}`, payload);
    } else {
      await axios.post('/api/hr/departments', payload);
    }
    
    emit('saved');
  } catch (error) {
    alert(error.response?.data?.message || 'Có lỗi xảy ra');
  }
};
</script>

