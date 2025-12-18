<template>
  <div class="org-node">
    <!-- Department Card -->
    <div class="flex flex-col items-center">
      <div class="dept-card group relative">
        <div class="p-4 bg-white border-2 border-blue-500 rounded-lg shadow-md hover:shadow-lg transition-shadow min-w-[200px]">
          <!-- Icon -->
          <div class="flex justify-center mb-2">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
          </div>
          
          <!-- Info -->
          <div class="text-center">
            <div class="font-bold text-gray-900 mb-1">{{ department.name }}</div>
            <div class="text-xs text-gray-500">
              <div v-if="department.default_position_id">
                {{ getPositionName(department.default_position_id) }}
              </div>
              <div v-if="department.users && department.users.length > 0">
                {{ department.users.length }} nhân viên
              </div>
            </div>
          </div>
          
          <!-- Actions -->
          <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
            <button
              @click="$emit('edit', department)"
              class="p-1 text-blue-600 hover:bg-blue-50 rounded"
              title="Chỉnh sửa"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="$emit('add-child', department)"
              class="p-1 text-green-600 hover:bg-green-50 rounded"
              title="Thêm phòng ban con"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
          </div>
          <div class="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
            <button
              @click="$emit('assign', department)"
              class="p-1 text-purple-600 hover:bg-purple-50 rounded"
              title="Gán nhân viên"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
            </button>
            <button
              @click="$emit('delete', department)"
              class="p-1 text-red-600 hover:bg-red-50 rounded"
              title="Xóa"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Vertical Line -->
        <div v-if="department.children && department.children.length > 0" class="h-8 w-0.5 bg-gray-300 mx-auto"></div>
      </div>
    </div>
    
    <!-- Children -->
    <div v-if="department.children && department.children.length > 0" class="flex justify-center">
      <div class="flex gap-8 relative">
        <!-- Horizontal Line -->
        <div class="absolute top-0 left-0 right-0 h-0.5 bg-gray-300" style="width: calc(100% - 4rem); left: 2rem;"></div>
        
        <DepartmentNode
          v-for="child in department.children"
          :key="child.id"
          :department="child"
          :positions="positions"
          @edit="$emit('edit', $event)"
          @add-child="$emit('add-child', $event)"
          @assign="$emit('assign', $event)"
          @delete="$emit('delete', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  department: Object,
  positions: Array
});

defineEmits(['edit', 'add-child', 'assign', 'delete']);

const getPositionName = (positionId) => {
  const pos = props.positions.find(p => p.id === positionId);
  return pos ? pos.name : '';
};
</script>

