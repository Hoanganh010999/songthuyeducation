<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">{{ t('quality.settings') }}</h1>
      <p class="text-gray-600 mt-1">{{ t('quality.settings_description') }}</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'attendance_fee'"
          :class="[
            activeTab === 'attendance_fee'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          {{ t('attendance_fee.title') }}
        </button>
        <button
          @click="activeTab = 'ai_config'"
          :class="[
            activeTab === 'ai_config'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          AI Configuration
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div v-if="activeTab === 'attendance_fee'">
      <AttendanceFeeSettings />
    </div>

    <!-- AI Configuration Tab -->
    <div v-if="activeTab === 'ai_config'" class="space-y-6">
      <!-- AI Lesson Plan Generation -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">AI Lesson Plan Generation</h3>

        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Thiết lập AI để tự động tạo lesson plans với cấu trúc TECP (Blocks → Stages → Procedures).
          </p>

          <button
            @click="showAISettings = true"
            class="w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Thiết lập AI
          </button>
        </div>
      </div>

      <!-- AI Prompt Management -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">AI Prompt Management</h3>

        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Quản lý custom prompts cho từng lesson shape (A-H) với JSON format được định nghĩa sẵn.
          </p>

          <button
            @click="showPromptSettings = true"
            class="w-full px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Quản lý Prompts
          </button>
        </div>
      </div>

      <!-- AI Material Generation -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">AI Material Generation</h3>

        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Thiết lập AI để tự động tạo teaching materials từ lesson plan với cấu trúc JSON được định nghĩa sẵn.
          </p>

          <button
            @click="showMaterialPromptSettings = true"
            class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Thiết lập Material Generation
          </button>
        </div>
      </div>
    </div>

    <!-- AI Settings Modal -->
    <div v-if="showAISettings" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Thiết lập AI Lesson Plan Generation</h3>
            <button @click="showAISettings = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6 space-y-6">
          <!-- Provider Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">AI Provider</label>
            <div class="flex flex-wrap gap-4">
              <label class="flex items-center">
                <input type="radio" v-model="aiSettings.provider" value="openai" class="mr-2" />
                <span>OpenAI (GPT)</span>
              </label>
              <label class="flex items-center">
                <input type="radio" v-model="aiSettings.provider" value="anthropic" class="mr-2" />
                <span>Anthropic (Claude)</span>
              </label>
            </div>
          </div>

          <!-- API Key -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
            <!-- Show current masked key if exists -->
            <div v-if="aiSettings.hasApiKey && !aiSettings.apiKey" class="mb-2 p-3 bg-green-50 border border-green-200 rounded-lg">
              <div class="flex items-center gap-2 text-green-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">Đã cấu hình: {{ aiSettings.maskedApiKey }}</span>
              </div>
              <p class="text-xs text-green-600 mt-1">Nhập key mới bên dưới nếu muốn thay đổi</p>
            </div>
            <input
              v-model="aiSettings.apiKey"
              type="password"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
              :placeholder="aiSettings.hasApiKey ? 'Nhập key mới để thay đổi...' : (aiSettings.provider === 'openai' ? 'sk-...' : 'sk-ant-...')"
            />
            <p class="text-xs text-gray-500 mt-1">API key được mã hóa và lưu trữ an toàn trên server</p>
          </div>

          <!-- Model Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
            <select v-model="aiSettings.model" class="w-full px-4 py-2 border rounded-lg">
              <template v-if="aiSettings.provider === 'openai'">
                <optgroup label="GPT-5 Series (Latest - 400K context)">
                  <option value="gpt-5-pro">GPT-5 Pro (Best Quality)</option>
                  <option value="gpt-5.1">GPT-5.1 (Recommended)</option>
                  <option value="gpt-5-mini">GPT-5 Mini (Fast)</option>
                </optgroup>
                <optgroup label="GPT-4.1 Series">
                  <option value="gpt-4.1">GPT-4.1</option>
                  <option value="gpt-4.1-mini">GPT-4.1 Mini</option>
                </optgroup>
                <optgroup label="Legacy Models">
                  <option value="gpt-4o">GPT-4o</option>
                  <option value="gpt-4o-mini">GPT-4o Mini</option>
                </optgroup>
              </template>
              <template v-else-if="aiSettings.provider === 'anthropic'">
                <optgroup label="Claude 4.5 Series (Latest)">
                  <option value="claude-sonnet-4-5-20250929">Claude Sonnet 4.5 (Recommended)</option>
                  <option value="claude-opus-4-5-20251124">Claude Opus 4.5 (Best)</option>
                  <option value="claude-haiku-4-5-20251015">Claude Haiku 4.5 (Fastest)</option>
                </optgroup>
                <optgroup label="Claude 4 Series">
                  <option value="claude-sonnet-4-20250514">Claude Sonnet 4</option>
                  <option value="claude-opus-4-20250514">Claude Opus 4</option>
                </optgroup>
              </template>
            </select>
          </div>

          <!-- Temperature -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Temperature: {{ aiSettings.temperature }}
            </label>
            <input
              v-model.number="aiSettings.temperature"
              type="range"
              min="0"
              max="1"
              step="0.1"
              class="w-full"
            />
            <div class="flex justify-between text-xs text-gray-500">
              <span>Chính xác (0)</span>
              <span>Sáng tạo (1)</span>
            </div>
          </div>

          <!-- Max Tokens -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Max Tokens</label>
            <input
              v-model.number="aiSettings.maxTokens"
              type="number"
              min="2000"
              max="16000"
              class="w-full px-4 py-2 border rounded-lg"
            />
            <p class="text-xs text-gray-500 mt-1">Số token tối đa cho mỗi lần tạo lesson plan (khuyến nghị: 4000-8000)</p>
          </div>

          <!-- Info box -->
          <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <h4 class="font-medium text-purple-800 mb-2">Hướng dẫn sử dụng AI tạo Lesson Plan</h4>
            <ul class="text-sm text-purple-700 space-y-1 list-disc list-inside">
              <li>Vào trang Syllabus và chọn session muốn tạo lesson plan</li>
              <li>Click nút "Generate with AI" để bắt đầu</li>
              <li>Chọn lesson shape (A-H) phù hợp với mục tiêu bài học</li>
              <li>AI sẽ tự động tạo cấu trúc TECP đầy đủ (Blocks, Stages, Procedures)</li>
            </ul>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-end space-x-4">
          <button
            @click="showAISettings = false"
            :disabled="savingAISettings"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50"
          >
            Hủy
          </button>
          <button
            @click="saveAISettings"
            :disabled="savingAISettings"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center"
          >
            <svg v-if="savingAISettings" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ savingAISettings ? 'Đang lưu...' : 'Lưu thiết lập' }}
          </button>
        </div>
      </div>
    </div>

    <!-- AI Prompt Settings Modal (Placeholder) -->
    <div v-if="showPromptSettings" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">AI Prompt Management</h3>
            <button @click="showPromptSettings = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6">
          <AIPromptManager module="quality_management" />
        </div>
      </div>
    </div>

    <!-- Material Prompt Settings Modal -->
    <div v-if="showMaterialPromptSettings" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl mx-4 max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="p-6 border-b flex items-center justify-between">
          <div>
            <h3 class="text-xl font-bold text-gray-800">AI Material Generation Prompt</h3>
            <p class="text-sm text-gray-500 mt-1">Customize how AI generates teaching materials from lesson plans</p>
          </div>
          <button @click="showMaterialPromptSettings = false" class="text-gray-400 hover:text-gray-600">
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
                  v-model="materialPromptData.description"
                  rows="20"
                  class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-green-500 font-mono text-sm"
                  placeholder="Enter instructions for AI to generate teaching materials..."
                ></textarea>
                <p class="text-xs text-gray-500 mt-2">
                  Mô tả chi tiết cách AI nên tạo teaching materials từ lesson plan.
                </p>
              </div>

              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-2">Thông tin AI nhận được:</h4>
                <ul class="text-xs text-blue-700 space-y-1 list-disc list-inside">
                  <li>Thông tin cơ bản: lesson title, focus, level, duration</li>
                  <li>Toàn bộ stages với stage name, aim, timing</li>
                  <li>Toàn bộ procedures với instructions, ICQs, feedback</li>
                  <li>AI sẽ tạo materials phù hợp với lesson plan</li>
                </ul>
              </div>
            </div>

            <!-- Right: JSON Format Template -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  JSON Response Format (Read-only)
                </label>
                <textarea
                  :value="materialPromptData.json_format"
                  rows="20"
                  readonly
                  class="w-full px-4 py-3 border rounded-lg bg-gray-50 font-mono text-sm"
                ></textarea>
                <p class="text-xs text-gray-500 mt-2">
                  AI sẽ trả về response theo format này.
                </p>
              </div>

              <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-yellow-800 mb-2">Lưu ý:</h4>
                <ul class="text-xs text-yellow-700 space-y-1 list-disc list-inside">
                  <li>AI tạo title, description và content cho material</li>
                  <li>Content được format dạng HTML</li>
                  <li>Có thể bao gồm images, lists, tables</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t flex justify-end gap-3">
          <button
            @click="showMaterialPromptSettings = false"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Hủy
          </button>
          <button
            @click="saveMaterialPrompt"
            :disabled="savingMaterialPrompt"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
          >
            {{ savingMaterialPrompt ? 'Đang lưu...' : 'Lưu Prompt' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import AttendanceFeeSettings from './settings/AttendanceFeeSettings.vue';
import AIPromptManager from '../examination/components/AIPromptManager.vue';
import api from '@/api';
import Swal from 'sweetalert2';

const { t } = useI18n();
const activeTab = ref('attendance_fee');

// Modal visibility
const showAISettings = ref(false);
const showPromptSettings = ref(false);
const showMaterialPromptSettings = ref(false);
const savingAISettings = ref(false);
const savingMaterialPrompt = ref(false);

// Store all provider settings
const allAISettings = ref({});

// AI Settings
const aiSettings = ref({
  provider: 'openai',
  apiKey: '',
  hasApiKey: false,
  maskedApiKey: '',
  model: 'gpt-5.1',
  temperature: 0.7,
  maxTokens: 4000
});

// Material Prompt Data
const materialPromptData = ref({
  description: '',
  json_format: JSON.stringify({
    "title": "string - Material title",
    "description": "string - Brief description of the material",
    "content": "HTML string - Main content with formatting (can include <h1>, <p>, <ul>, <ol>, <table>, <img>, etc.)"
  }, null, 2)
});

onMounted(async () => {
  await loadAISettings();
  await loadMaterialPrompt();
});

// Load AI Settings
async function loadAISettings() {
  try {
    const response = await api.get('/quality/ai-settings');

    if (response.data.success && response.data.data) {
      // Store all provider settings
      allAISettings.value = response.data.data;

      // Update form with current provider's data
      updateAISettingsFromProvider();
    }
  } catch (error) {
    console.error('Error loading AI settings:', error);
  }
}

// Update AI form data based on current provider
function updateAISettingsFromProvider() {
  const currentProvider = aiSettings.value.provider || 'openai';
  const providerData = allAISettings.value[currentProvider] || {};

  aiSettings.value = {
    provider: currentProvider,
    apiKey: '',
    hasApiKey: providerData.has_api_key || false,
    maskedApiKey: providerData.masked_api_key || '',
    model: providerData.model || 'gpt-5.1',
    temperature: providerData.settings?.temperature || 0.7,
    maxTokens: providerData.settings?.max_tokens || 4000
  };
}

// Watch for provider changes
watch(() => aiSettings.value.provider, () => {
  if (Object.keys(allAISettings.value).length > 0) {
    updateAISettingsFromProvider();
  }
});

// Save AI Settings
async function saveAISettings() {
  savingAISettings.value = true;
  try {
    const payload = {
      provider: aiSettings.value.provider,
      api_key: aiSettings.value.apiKey || undefined,
      model: aiSettings.value.model,
      settings: {
        temperature: aiSettings.value.temperature,
        max_tokens: aiSettings.value.maxTokens
      }
    };

    const response = await api.post('/quality/ai-settings', payload);

    if (response.data.success) {
      await Swal.fire('Thành công', 'Thiết lập AI đã được lưu!', 'success');
      showAISettings.value = false;
      await loadAISettings();
    }
  } catch (error) {
    console.error('Error saving AI settings:', error);
    Swal.fire('Lỗi', error.response?.data?.message || 'Có lỗi xảy ra khi lưu thiết lập!', 'error');
  } finally {
    savingAISettings.value = false;
  }
}

// Load Material Prompt
async function loadMaterialPrompt() {
  try {
    const response = await api.get('/quality/material-generation-prompt');

    if (response.data.success && response.data.data) {
      materialPromptData.value.description = response.data.data.description || '';
      if (response.data.data.json_format) {
        materialPromptData.value.json_format = JSON.stringify(
          JSON.parse(response.data.data.json_format), null, 2
        );
      }
    }
  } catch (error) {
    console.error('Error loading material prompt:', error);
    // It's okay if it doesn't exist yet
  }
}

// Save Material Prompt
async function saveMaterialPrompt() {
  savingMaterialPrompt.value = true;
  try {
    const payload = {
      prompt_type: 'material_generation',
      description: materialPromptData.value.description,
      json_format: materialPromptData.value.json_format
    };

    const response = await api.post('/quality/material-generation-prompt', payload);

    if (response.data.success) {
      await Swal.fire('Thành công', 'Material generation prompt đã được lưu!', 'success');
      showMaterialPromptSettings.value = false;
      await loadMaterialPrompt();
    }
  } catch (error) {
    console.error('Error saving material prompt:', error);
    Swal.fire('Lỗi', error.response?.data?.message || 'Có lỗi xảy ra khi lưu prompt!', 'error');
  } finally {
    savingMaterialPrompt.value = false;
  }
}
</script>

