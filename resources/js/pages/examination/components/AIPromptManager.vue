<template>
  <div class="space-y-6">
    <!-- Info Banner -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-start">
        <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="flex-1">
          <h4 class="text-sm font-medium text-blue-800">Custom AI Prompts</h4>
          <p class="text-sm text-blue-700 mt-1">
            Tùy chỉnh prompts cho từng lesson shape (A-H). AI sẽ sử dụng prompts này để tạo lesson plans với cấu trúc JSON được định nghĩa sẵn.
          </p>
        </div>
      </div>
    </div>

    <!-- Lesson Shapes Grid -->
    <div class="grid grid-cols-2 gap-4">
      <button
        v-for="shape in lessonShapes"
        :key="shape.code"
        @click="editPrompt(shape)"
        :class="[
          'p-4 rounded-lg border-2 text-left transition-all',
          selectedShape?.code === shape.code
            ? 'border-purple-500 bg-purple-50'
            : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50'
        ]"
      >
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <span class="text-lg font-bold text-gray-900">{{ shape.code }}</span>
              <span v-if="hasCustomPrompt(shape.code)" class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded">
                Custom
              </span>
            </div>
            <p class="text-sm text-gray-600 mt-1">{{ shape.name }}</p>
          </div>
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </div>
      </button>
    </div>

    <!-- Edit Prompt Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl mx-4 max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="p-6 border-b flex items-center justify-between">
          <div>
            <h3 class="text-xl font-bold text-gray-800">
              Edit Prompt: Shape {{ selectedShape?.code }} - {{ selectedShape?.name }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">Customize the AI prompt for this lesson shape</p>
          </div>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6">
          <div class="grid grid-cols-2 gap-6">
            <!-- Left: Prompt Editor -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  AI Prompt Instructions
                </label>
                <textarea
                  v-model="editingPrompt"
                  rows="20"
                  class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-purple-500 font-mono text-sm"
                  placeholder="Enter custom prompt for AI..."
                ></textarea>
                <p class="text-xs text-gray-500 mt-2">
                  Mô tả chi tiết cách AI nên tạo lesson plan cho lesson shape này.
                </p>
              </div>

              <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-yellow-800 mb-2">Lưu ý:</h4>
                <ul class="text-xs text-yellow-700 space-y-1 list-disc list-inside">
                  <li>Prompt sẽ được gửi cùng với JSON format template</li>
                  <li>AI sẽ tự động tuân thủ cấu trúc JSON đã định nghĩa</li>
                  <li>Để trống để sử dụng default prompt</li>
                </ul>
              </div>
            </div>

            <!-- Right: JSON Format Template -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  JSON Format Template (Read-only)
                </label>
                <div class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto">
                  <pre class="text-xs font-mono">{{ jsonTemplate }}</pre>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                  AI phải trả về JSON theo định dạng này.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t bg-gray-50 flex justify-between">
          <button
            v-if="hasCustomPrompt(selectedShape?.code)"
            @click="resetToDefault"
            :disabled="saving"
            class="px-4 py-2 text-red-600 hover:text-red-800 disabled:opacity-50"
          >
            Reset to Default
          </button>
          <div class="flex-1"></div>
          <div class="flex space-x-4">
            <button
              @click="closeModal"
              :disabled="saving"
              class="px-4 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50"
            >
              Hủy
            </button>
            <button
              @click="savePrompt"
              :disabled="saving"
              class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center"
            >
              <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ saving ? 'Đang lưu...' : 'Lưu Prompt' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '@/api';
import Swal from 'sweetalert2';

const props = defineProps({
  module: {
    type: String,
    required: true,
    default: 'quality_management'
  }
});

// Lesson Shapes
const lessonShapes = [
  { code: 'A', name: 'Text-Based Presentation of Language' },
  { code: 'B', name: 'Language Practice' },
  { code: 'C', name: 'Test-Teach-Test Presentation of Language' },
  { code: 'D', name: 'Situational Presentation (PPP)' },
  { code: 'E', name: 'Receptive Skills (listening or reading)' },
  { code: 'F', name: 'Productive Skills (speaking or writing)' },
  { code: 'G', name: 'Dogme ELT' },
  { code: 'H', name: 'Task-Based Learning/Teaching (TBL/TBT)' }
];

// State
const customPrompts = ref({});
const selectedShape = ref(null);
const editingPrompt = ref('');
const showEditModal = ref(false);
const saving = ref(false);

// JSON Template
const jsonTemplate = computed(() => {
  return `{
  "lesson_plan": {
    "title": "Lesson title",
    "shape": "${selectedShape.value?.code || 'X'}",
    "blocks": [
      {
        "block_number": 1,
        "block_title": "Optional block title",
        "stages": [
          {
            "stage_number": 1,
            "stage_name": "Stage name (e.g., Warmer, Lead-in)",
            "stage_aim": "What this stage aims to achieve",
            "total_timing": 5,
            "procedure": {
              "stage_content": "Brief description",
              "instructions": "Step-by-step instructions",
              "icqs": "Instruction checking questions",
              "instruction_timing": 1,
              "instruction_interaction": "T-Ss",
              "task_completion": "What students do",
              "monitoring_points": "What teacher monitors",
              "task_timing": 3,
              "task_interaction": "SS-Ss",
              "feedback": "How feedback is given",
              "feedback_timing": 1,
              "feedback_interaction": "T-Ss",
              "learner_problems": [
                {
                  "problem": "Potential problem",
                  "solution": "How to solve it"
                }
              ],
              "task_problems": [
                {
                  "problem": "Task difficulty",
                  "solution": "How to adapt"
                }
              ]
            }
          }
        ]
      }
    ]
  }
}`;
});

onMounted(async () => {
  await loadPrompts();
});

// Load custom prompts
async function loadPrompts() {
  try {
    const response = await api.get('/quality/ai-prompts');

    if (response.data.success && response.data.data) {
      // Convert array to object for easy lookup
      response.data.data.forEach(prompt => {
        customPrompts.value[prompt.identifier] = prompt.prompt;
      });
    }
  } catch (error) {
    console.error('Error loading prompts:', error);
  }
}

// Check if shape has custom prompt
function hasCustomPrompt(shapeCode) {
  return !!customPrompts.value[`lesson_shape_${shapeCode}`];
}

// Edit prompt
function editPrompt(shape) {
  selectedShape.value = shape;
  editingPrompt.value = customPrompts.value[`lesson_shape_${shape.code}`] || '';
  showEditModal.value = true;
}

// Close modal
function closeModal() {
  showEditModal.value = false;
  selectedShape.value = null;
  editingPrompt.value = '';
}

// Save prompt
async function savePrompt() {
  if (!selectedShape.value) return;

  saving.value = true;
  try {
    const payload = {
      module: `prompt_lesson_plan_${selectedShape.value.code.toLowerCase()}`,
      prompt: editingPrompt.value || null,
    };

    const response = await api.post('/quality/ai-prompts', payload);

    if (response.data.success) {
      await Swal.fire('Thành công', 'Prompt đã được lưu!', 'success');

      // Update local cache
      if (editingPrompt.value) {
        customPrompts.value[`lesson_shape_${selectedShape.value.code}`] = editingPrompt.value;
      } else {
        delete customPrompts.value[`lesson_shape_${selectedShape.value.code}`];
      }

      closeModal();
    }
  } catch (error) {
    console.error('Error saving prompt:', error);
    Swal.fire('Lỗi', error.response?.data?.message || 'Có lỗi xảy ra khi lưu prompt!', 'error');
  } finally {
    saving.value = false;
  }
}

// Reset to default
async function resetToDefault() {
  const result = await Swal.fire({
    title: 'Reset to Default?',
    text: 'Custom prompt sẽ bị xóa và AI sẽ sử dụng default prompt.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Reset',
    cancelButtonText: 'Hủy'
  });

  if (result.isConfirmed) {
    editingPrompt.value = '';
    await savePrompt();
  }
}
</script>
