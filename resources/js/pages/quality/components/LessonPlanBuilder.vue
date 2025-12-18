<template>
  <div class="space-y-6">
    <!-- Header with AI Generate Button -->
    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg border border-purple-200">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Lesson Plan Structure</h3>
        <p class="text-sm text-gray-600 mt-1">Stages → Procedures (TECP Framework)</p>
      </div>
      <button
        @click="showAIGenerateModal = true"
        class="px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:from-purple-700 hover:to-blue-700 flex items-center gap-2 shadow-md"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        Generate with AI
      </button>
    </div>

    <!-- Blocks List -->
    <div v-if="blocks.length === 0" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="mt-2 text-sm text-gray-600">No lesson plan stages yet</p>
      <button
        @click="addBlock"
        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Add First Stage
      </button>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="(block, blockIndex) in blocks"
        :key="block.id || blockIndex"
        class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden"
      >
        <!-- Block Header -->
        <div
          class="px-4 py-3 bg-blue-50 border-b border-blue-200 flex items-center justify-between cursor-pointer"
          @click="toggleBlock(blockIndex)"
        >
          <div class="flex items-center gap-3 flex-1">
            <button
              @click.stop="toggleBlock(blockIndex)"
              class="text-blue-600 hover:text-blue-800"
            >
              <svg
                class="w-5 h-5 transform transition-transform"
                :class="{ 'rotate-90': block.expanded }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
            <div class="flex-1">
              <span class="font-semibold text-blue-900">Stage {{ block.block_number }}</span>
              <span v-if="block.block_title" class="ml-2 text-blue-700">- {{ block.block_title }}</span>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click.stop="deleteBlock(blockIndex)"
                class="text-red-600 hover:text-red-800"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Block Content -->
        <div v-if="block.expanded" class="p-4 space-y-4">
          <!-- Stage Details -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Stage Title</label>
              <input
                v-model="block.block_title"
                type="text"
                class="w-full px-3 py-2 border rounded-lg"
                placeholder="Optional stage title"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Stage Number</label>
              <input
                v-model.number="block.block_number"
                type="number"
                class="w-full px-3 py-2 border rounded-lg"
                min="1"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stage Description</label>
            <textarea
              v-model="block.block_description"
              @input="autoResize($event.target)"
              rows="2"
              class="w-full px-3 py-2 border rounded-lg resize-none overflow-hidden"
              placeholder="Optional description"
            ></textarea>
          </div>

          <!-- Procedures -->
          <div class="border-t pt-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-semibold text-gray-900">Procedures</h4>
              <button
                @click="addStage(blockIndex)"
                class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700"
              >
                + Add Procedure
              </button>
            </div>

            <div v-if="block.stages && block.stages.length === 0" class="text-center py-6 bg-gray-50 rounded border-2 border-dashed">
              <p class="text-sm text-gray-600">No procedures yet</p>
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="(stage, stageIndex) in block.stages"
                :key="stage.id || stageIndex"
                class="border border-gray-200 rounded-lg overflow-hidden"
              >
                <!-- Stage Header -->
                <div
                  class="px-3 py-2 bg-green-50 flex items-center justify-between cursor-pointer"
                  @click="toggleStage(blockIndex, stageIndex)"
                >
                  <div class="flex items-center gap-2 flex-1">
                    <button
                      @click.stop="toggleStage(blockIndex, stageIndex)"
                      class="text-green-600"
                    >
                      <svg
                        class="w-4 h-4 transform transition-transform"
                        :class="{ 'rotate-90': stage.expanded }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                    <span class="font-medium text-green-900 text-sm">
                      Procedure {{ stage.stage_number }}: {{ stage.stage_name }}
                    </span>
                    <span class="text-xs text-green-700">({{ stage.total_timing }} min)</span>
                  </div>
                  <button
                    @click.stop="deleteStage(blockIndex, stageIndex)"
                    class="text-red-600 hover:text-red-800"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <!-- Procedure Content - 2 Column Layout -->
                <div v-if="stage.expanded" class="p-3 bg-white">
                  <div class="grid grid-cols-3 gap-4">
                    <!-- Left Column (2/3 width) -->
                    <div class="col-span-2 space-y-3">
                      <div class="grid grid-cols-4 gap-3">
                        <div>
                          <label class="block text-xs font-medium text-gray-700 mb-1">Number</label>
                          <input
                            v-model.number="stage.stage_number"
                            type="number"
                            class="w-full px-2 py-1 text-sm border rounded"
                            min="1"
                          />
                        </div>
                        <div>
                          <label class="block text-xs font-medium text-gray-700 mb-1">Procedure Name *</label>
                          <input
                            v-model="stage.stage_name"
                            type="text"
                            class="w-full px-2 py-1 text-sm border rounded"
                            placeholder="e.g., Warmer, Lead-in"
                          />
                        </div>
                        <div>
                          <label class="block text-xs font-medium text-gray-700 mb-1">Timing (min)</label>
                          <input
                            v-model.number="stage.total_timing"
                            type="number"
                            class="w-full px-2 py-1 text-sm border rounded"
                            min="0"
                          />
                        </div>
                        <div>
                          <label class="block text-xs font-medium text-gray-700 mb-1">Interaction</label>
                          <select
                            v-model="stage.interaction_pattern"
                            class="w-full px-2 py-1 text-sm border rounded"
                          >
                            <option value="">Select...</option>
                            <option value="T-Ss">T-Ss</option>
                            <option value="Ss-T">Ss-T</option>
                            <option value="SS-Ss">SS-Ss</option>
                            <option value="Ss-text">Ss-text</option>
                          </select>
                        </div>
                      </div>

                      <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">
                          Procedure Aim
                          <span class="text-gray-500 font-normal">(Describe what this procedure aims to achieve)</span>
                        </label>
                        <QuillEditor
                          v-model:content="stage.stage_aim"
                          contentType="html"
                          theme="snow"
                          :toolbar="[
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['clean']
                          ]"
                          placeholder="Enter the main goal and objectives of this procedure..."
                          class="bg-white rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-blue-500"
                          style="min-height: 150px;"
                        />
                      </div>
                    </div>

                    <!-- Right Column (1/3 width) - Problems & Solutions -->
                    <div class="space-y-3">
                      <!-- Learner Problems -->
                      <div class="bg-red-50 p-3 rounded border border-red-200">
                        <h6 class="font-medium text-red-900 text-xs mb-2">Learner Problems</h6>
                        <div class="space-y-2">
                          <div>
                            <label class="block text-xs text-gray-700 mb-1">Problem:</label>
                            <textarea
                              v-model="stage.learner_problem"
                              @input="autoResize($event.target)"
                              rows="2"
                              class="w-full px-2 py-1 text-xs border rounded resize-none overflow-hidden"
                              placeholder="Describe the problem..."
                            ></textarea>
                          </div>
                          <div>
                            <label class="block text-xs text-gray-700 mb-1">Solution:</label>
                            <textarea
                              v-model="stage.learner_solution"
                              @input="autoResize($event.target)"
                              rows="2"
                              class="w-full px-2 py-1 text-xs border rounded resize-none overflow-hidden"
                              placeholder="Describe the solution..."
                            ></textarea>
                          </div>
                        </div>
                      </div>

                      <!-- Task Problems -->
                      <div class="bg-orange-50 p-3 rounded border border-orange-200">
                        <h6 class="font-medium text-orange-900 text-xs mb-2">Task Problems</h6>
                        <div class="space-y-2">
                          <div>
                            <label class="block text-xs text-gray-700 mb-1">Problem:</label>
                            <textarea
                              v-model="stage.task_problem"
                              @input="autoResize($event.target)"
                              rows="2"
                              class="w-full px-2 py-1 text-xs border rounded resize-none overflow-hidden"
                              placeholder="Describe the problem..."
                            ></textarea>
                          </div>
                          <div>
                            <label class="block text-xs text-gray-700 mb-1">Solution:</label>
                            <textarea
                              v-model="stage.task_solution"
                              @input="autoResize($event.target)"
                              rows="2"
                              class="w-full px-2 py-1 text-xs border rounded resize-none overflow-hidden"
                              placeholder="Describe the solution..."
                            ></textarea>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Block Button -->
      <button
        @click="addBlock"
        class="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-600 transition-colors"
      >
        + Add Another Block
      </button>
    </div>

    <!-- AI Generate Modal -->
    <div v-if="showAIGenerateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b sticky top-0 bg-white z-10">
          <h3 class="text-xl font-bold text-gray-800">Generate Lesson Plan with AI</h3>
          <p class="text-sm text-gray-600 mt-1">Session information will be used automatically</p>
        </div>

        <div class="p-6 space-y-4">
          <!-- Session Information (Read-only) -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-2">
            <h4 class="font-semibold text-blue-900 mb-2">Session Information</h4>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <span class="text-gray-600">Lesson Title:</span>
                <span class="ml-2 font-medium">{{ sessionData.lesson_title || 'N/A' }}</span>
              </div>
              <div>
                <span class="text-gray-600">Level:</span>
                <span class="ml-2 font-medium">{{ sessionData.level || 'N/A' }}</span>
              </div>
              <div>
                <span class="text-gray-600">Duration:</span>
                <span class="ml-2 font-medium">{{ sessionData.duration_minutes || 50 }} minutes</span>
              </div>
              <div>
                <span class="text-gray-600">Lesson Focus:</span>
                <span class="ml-2 font-medium">{{ sessionData.lesson_focus || 'N/A' }}</span>
              </div>
              <div v-if="sessionData.framework_shape" class="col-span-2">
                <span class="text-gray-600">Framework Shape:</span>
                <span class="ml-2 font-medium text-purple-700">{{ sessionData.framework_shape }} - {{ getLessonShapeName(sessionData.framework_shape) }}</span>
              </div>
              <div v-if="sessionData.communicative_outcome" class="col-span-2">
                <span class="text-gray-600">Communicative Outcome:</span>
                <span class="ml-2 font-medium text-sm">{{ sessionData.communicative_outcome }}</span>
              </div>
              <div v-if="sessionData.linguistic_aim" class="col-span-2">
                <span class="text-gray-600">Linguistic Aim:</span>
                <span class="ml-2 font-medium text-sm">{{ sessionData.linguistic_aim }}</span>
              </div>
            </div>
          </div>

          <!-- Student Information -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Tuổi học viên (Age)</label>
              <input
                v-model.number="aiGenerateData.student_age"
                type="number"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
                placeholder="e.g., 25"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Số học viên (Number of Students)</label>
              <input
                v-model.number="aiGenerateData.number_of_students"
                type="number"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
                placeholder="e.g., 15"
              />
            </div>
          </div>

          <!-- Lesson Shape Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Lesson Shape * <span class="text-xs text-gray-500">(Choose the TECP framework shape)</span></label>
            <select
              v-model="aiGenerateData.lesson_shape"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
            >
              <option value="">Select lesson shape...</option>
              <option value="A">A - Text-Based Presentation of Language</option>
              <option value="B">B - Language Practice</option>
              <option value="C">C - Test-Teach-Test Presentation</option>
              <option value="D">D - Situational Presentation (PPP)</option>
              <option value="E">E - Receptive Skills</option>
              <option value="F">F - Productive Skills</option>
              <option value="G">G - Dogme ELT</option>
              <option value="H">H - Task-Based Learning (TBL/TBT)</option>
            </select>
          </div>

          <!-- Additional Context -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Additional Context (Optional)
              <span class="text-xs text-gray-500 block mt-1">Add any specific requirements, student needs, materials to use, or teaching focus</span>
            </label>
            <textarea
              v-model="aiGenerateData.additional_context"
              rows="4"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
              placeholder="Example: Focus on spoken fluency, students have difficulty with pronunciation, use pictures from textbook page 45..."
            ></textarea>
          </div>

          <!-- Advanced AI Settings -->
          <div class="border border-gray-300 rounded-lg">
            <button
              @click="showAdvancedSettings = !showAdvancedSettings"
              type="button"
              class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50"
            >
              <span class="font-medium text-gray-700">⚙️ Advanced AI Settings (Optional)</span>
              <svg
                :class="['w-5 h-5 transition-transform', showAdvancedSettings ? 'rotate-180' : '']"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <div v-show="showAdvancedSettings" class="p-4 space-y-4 border-t border-gray-200">
              <div class="bg-yellow-50 border border-yellow-200 rounded p-3 text-xs text-yellow-800">
                <strong>⚠️ Chú ý:</strong> Nếu không chọn, hệ thống sẽ dùng AI settings mặc định của branch. API key luôn lấy từ database settings.
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Provider</label>
                  <select
                    v-model="aiGenerateData.provider"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
                  >
                    <option value="">Use default from settings</option>
                    <option value="openai">OpenAI</option>
                    <option value="anthropic">Anthropic (Claude)</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                  <select
                    v-model="aiGenerateData.model"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
                  >
                    <option value="">Use default from settings</option>

                    <!-- OpenAI Models -->
                    <optgroup v-if="!aiGenerateData.provider || aiGenerateData.provider === 'openai'" label="OpenAI Models">
                      <option value="gpt-5.2">GPT-5.2 (Latest - Best Quality)</option>
                      <option value="gpt-5.1">GPT-5.1 (Recommended)</option>
                    </optgroup>

                    <!-- Anthropic Models -->
                    <optgroup v-if="!aiGenerateData.provider || aiGenerateData.provider === 'anthropic'" label="Anthropic Claude Models">
                      <option value="claude-sonnet-4-5">Claude Sonnet 4.5 (Latest, Best Balance) ⭐</option>
                      <option value="claude-opus-4-5">Claude Opus 4.5 (Most Powerful)</option>
                      <option value="claude-3-5-sonnet-20241022">Claude 3.5 Sonnet (Stable)</option>
                      <option value="claude-3-opus-20240229">Claude 3 Opus (Legacy)</option>
                      <option value="claude-3-sonnet-20240229">Claude 3 Sonnet (Legacy)</option>
                    </optgroup>
                  </select>
                  <p class="text-xs text-gray-500 mt-1">
                    <template v-if="aiGenerateData.provider === 'anthropic'">
                      💡 Sonnet 4.5 là model mới nhất, cân bằng giữa hiệu năng và chi phí
                    </template>
                    <template v-else-if="aiGenerateData.provider === 'openai'">
                      💡 GPT-4o được khuyên dùng cho hầu hết các tác vụ
                    </template>
                    <template v-else>
                      💡 Chọn provider để xem các models phù hợp
                    </template>
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
            <p class="text-xs text-purple-700">
              AI will generate a complete TECP-structured lesson plan using the session information above.
              The custom prompt for the selected lesson shape and JSON format instructions will be included automatically.
            </p>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-end space-x-4 sticky bottom-0">
          <button
            @click="showAIGenerateModal = false"
            :disabled="generatingAI"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50"
          >
            Cancel
          </button>
          <button
            @click="generateWithAI"
            :disabled="generatingAI || !aiGenerateData.lesson_shape"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center"
          >
            <svg v-if="generatingAI" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ generatingAI ? 'Generating...' : 'Generate' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import api from '@/api';
import Swal from 'sweetalert2';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const props = defineProps({
  sessionId: {
    type: Number,
    required: true
  },
  initialBlocks: {
    type: Array,
    default: () => []
  },
  sessionData: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['blocksUpdated']);

// State
const blocks = ref([]);
const showAIGenerateModal = ref(false);
const showAdvancedSettings = ref(false);
const generatingAI = ref(false);
const aiGenerateData = ref({
  lesson_shape: '',
  student_age: null,
  number_of_students: null,
  additional_context: '',
  provider: '', // openai or anthropic
  model: '' // e.g., gpt-4, gpt-5.1-preview, claude-3-opus-20240229
});

// Load initial blocks ONCE on mount
onMounted(() => {
  if (props.initialBlocks && props.initialBlocks.length > 0) {
    blocks.value = JSON.parse(JSON.stringify(props.initialBlocks));
  }

  // Pre-populate lesson shape from session data if available
  if (props.sessionData && props.sessionData.framework_shape) {
    aiGenerateData.value.lesson_shape = props.sessionData.framework_shape;
  }
});

// Function to emit changes (call this manually when needed)
function emitUpdate() {
  emit('blocksUpdated', JSON.parse(JSON.stringify(blocks.value)));
}

// Block management
function addBlock() {
  blocks.value.push({
    block_number: blocks.value.length + 1,
    block_title: '',
    block_description: '',
    stages: [],
    expanded: true
  });
}

function deleteBlock(blockIndex) {
  blocks.value.splice(blockIndex, 1);
  // Renumber blocks
  blocks.value.forEach((block, index) => {
    block.block_number = index + 1;
  });
  emitUpdate(); // Notify parent component
}

function toggleBlock(blockIndex) {
  blocks.value[blockIndex].expanded = !blocks.value[blockIndex].expanded;
}

// Stage management
function addStage(blockIndex) {
  if (!blocks.value[blockIndex].stages) {
    blocks.value[blockIndex].stages = [];
  }
  blocks.value[blockIndex].stages.push({
    stage_number: blocks.value[blockIndex].stages.length + 1,
    stage_name: '',
    stage_aim: '',
    total_timing: 0,
    interaction_pattern: '',
    learner_problem: '',
    learner_solution: '',
    task_problem: '',
    task_solution: '',
    expanded: true
  });
}

function deleteStage(blockIndex, stageIndex) {
  blocks.value[blockIndex].stages.splice(stageIndex, 1);
  // Renumber stages
  blocks.value[blockIndex].stages.forEach((stage, index) => {
    stage.stage_number = index + 1;
  });
  emitUpdate(); // Notify parent component
}

function toggleStage(blockIndex, stageIndex) {
  blocks.value[blockIndex].stages[stageIndex].expanded =
    !blocks.value[blockIndex].stages[stageIndex].expanded;
}

// Helper function to get lesson shape full name
function getLessonShapeName(shape) {
  const shapes = {
    'A': 'Text-Based Presentation of Language',
    'B': 'Language Practice',
    'C': 'Test-Teach-Test Presentation',
    'D': 'Situational Presentation (PPP)',
    'E': 'Receptive Skills',
    'F': 'Productive Skills',
    'G': 'Dogme ELT',
    'H': 'Task-Based Learning (TBL/TBT)'
  };
  return shapes[shape] || shape;
}

// Auto-resize textarea to fit content
function autoResize(textarea) {
  if (!textarea) return;

  // Reset height to auto to get the correct scrollHeight
  textarea.style.height = 'auto';

  // Set height to scrollHeight (content height)
  const newHeight = Math.max(textarea.scrollHeight, 40); // Minimum 40px
  textarea.style.height = newHeight + 'px';
}

// Resize all textareas after data changes
function resizeAllTextareas() {
  // Wait for DOM update
  nextTick(() => {
    const textareas = document.querySelectorAll('textarea[class*="resize-none"]');
    textareas.forEach(textarea => autoResize(textarea));
  });
}

// Watch blocks for changes and resize textareas
watch(blocks, () => {
  resizeAllTextareas();
}, { deep: true });

// Resize on mount
onMounted(() => {
  resizeAllTextareas();
});

// AI Generation
async function generateWithAI() {
  if (!aiGenerateData.value.lesson_shape) {
    Swal.fire('Error', 'Please select a lesson shape', 'error');
    return;
  }

  generatingAI.value = true;
  try {
    const payload = {
      session_id: props.sessionId,
      lesson_shape: aiGenerateData.value.lesson_shape,
      student_age: aiGenerateData.value.student_age,
      number_of_students: aiGenerateData.value.number_of_students,
      additional_context: aiGenerateData.value.additional_context
    };

    // Add optional provider and model if provided
    if (aiGenerateData.value.provider) {
      payload.provider = aiGenerateData.value.provider;
    }
    if (aiGenerateData.value.model) {
      payload.model = aiGenerateData.value.model;
    }

    const response = await api.post('/quality/generate-lesson-plan', payload);

    if (response.data.success && response.data.raw_response) {
      const lessonPlan = response.data.raw_response.lesson_plan;

      // Process the AI-generated stages (updated terminology)
      const processedStages = (lessonPlan.stages || []).map(stage => ({
        ...stage,
        expanded: false,
        stages: (stage.procedures || []).map(procedure => {
          // Extract first problem/solution from arrays for UI compatibility
          const learnerProblems = procedure.procedure?.learner_problems || [];
          const taskProblems = procedure.procedure?.task_problems || [];

          return {
            ...procedure,
            expanded: false,
            learner_problem: learnerProblems[0]?.problem || '',
            learner_solution: learnerProblems[0]?.solution || '',
            task_problem: taskProblems[0]?.problem || '',
            task_solution: taskProblems[0]?.solution || '',
            procedure: procedure.procedure ? {
              ...procedure.procedure,
              learner_problems_text: JSON.stringify(learnerProblems),
              task_problems_text: JSON.stringify(taskProblems)
            } : null
          };
        })
      }));

      blocks.value = processedStages;
      showAIGenerateModal.value = false;

      // Notify parent component about the updated data
      emitUpdate();

      await Swal.fire('Success', 'Lesson plan generated successfully! You can now edit it.', 'success');
    }
  } catch (error) {
    console.error('Error generating lesson plan:', error);
    Swal.fire('Error', error.response?.data?.message || 'Failed to generate lesson plan', 'error');
  } finally {
    generatingAI.value = false;
  }
}

// Expose blocks data to parent component
defineExpose({
  getBlocks: () => blocks.value,
  blocks
});
</script>
