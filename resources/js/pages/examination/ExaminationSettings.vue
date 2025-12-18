<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Cài đặt Examination</h2>
        <p class="text-sm text-gray-500 mt-1">Thiết lập các thông số cho module kiểm tra đánh giá</p>
      </div>
    </div>

    <!-- Settings Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- General Settings -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Cài đặt chung</h3>

        <div class="space-y-4">
          <!-- Default Time Limit -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Thời gian làm bài mặc định (phút)
            </label>
            <input
              v-model.number="settings.default_time_limit"
              type="number"
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Pass Score -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Điểm đạt mặc định (%)
            </label>
            <input
              v-model.number="settings.default_pass_score"
              type="number"
              min="0"
              max="100"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Max Attempts -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Số lần làm bài tối đa
            </label>
            <input
              v-model.number="settings.default_max_attempts"
              type="number"
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
            <p class="text-xs text-gray-500 mt-1">Để trống nếu không giới hạn</p>
          </div>

          <!-- Shuffle Questions -->
          <div class="flex items-center justify-between">
            <div>
              <label class="text-sm font-medium text-gray-700">Xáo trộn câu hỏi</label>
              <p class="text-xs text-gray-500">Tự động xáo trộn thứ tự câu hỏi khi làm bài</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.shuffle_questions" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            </label>
          </div>

          <!-- Shuffle Options -->
          <div class="flex items-center justify-between">
            <div>
              <label class="text-sm font-medium text-gray-700">Xáo trộn đáp án</label>
              <p class="text-xs text-gray-500">Tự động xáo trộn thứ tự đáp án</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.shuffle_options" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            </label>
          </div>
        </div>
      </div>

      <!-- Display Settings -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Hiển thị kết quả</h3>

        <div class="space-y-4">
          <!-- Show Score Immediately -->
          <div class="flex items-center justify-between">
            <div>
              <label class="text-sm font-medium text-gray-700">Hiển thị điểm ngay</label>
              <p class="text-xs text-gray-500">Hiển thị điểm ngay sau khi nộp bài</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.show_score_immediately" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            </label>
          </div>

          <!-- Show Answers After Submit -->
          <div class="flex items-center justify-between">
            <div>
              <label class="text-sm font-medium text-gray-700">Hiển thị đáp án</label>
              <p class="text-xs text-gray-500">Cho phép xem đáp án sau khi nộp bài</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.show_answers_after_submit" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            </label>
          </div>

          <!-- Show Correct/Incorrect Marks -->
          <div class="flex items-center justify-between">
            <div>
              <label class="text-sm font-medium text-gray-700">Đánh dấu đúng/sai</label>
              <p class="text-xs text-gray-500">Hiển thị đánh dấu đúng/sai cho từng câu</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.show_correct_marks" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            </label>
          </div>

          <!-- Allow Review -->
          <div class="flex items-center justify-between">
            <div>
              <label class="text-sm font-medium text-gray-700">Cho phép xem lại</label>
              <p class="text-xs text-gray-500">Học viên có thể xem lại bài đã làm</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.allow_review" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            </label>
          </div>
        </div>
      </div>

      <!-- IELTS Settings -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Cài đặt IELTS</h3>

        <div class="space-y-4">
          <!-- IELTS Band Score Calculation -->
          <div class="flex items-center justify-between">
            <div>
              <label class="text-sm font-medium text-gray-700">Tự động tính Band Score</label>
              <p class="text-xs text-gray-500">Tính toán band score IELTS tự động</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.ielts_auto_band_score" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            </label>
          </div>

          <!-- IELTS Listening Time -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Thời gian IELTS Listening (phút)
            </label>
            <input
              v-model.number="settings.ielts_listening_time"
              type="number"
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- IELTS Reading Time -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Thời gian IELTS Reading (phút)
            </label>
            <input
              v-model.number="settings.ielts_reading_time"
              type="number"
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- IELTS Writing Time -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Thời gian IELTS Writing (phút)
            </label>
            <input
              v-model.number="settings.ielts_writing_time"
              type="number"
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <!-- Cambridge Settings -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Cài đặt Cambridge</h3>

        <div class="space-y-4">
          <!-- Cambridge Shield Calculation -->
          <div class="flex items-center justify-between">
            <div>
              <label class="text-sm font-medium text-gray-700">Tự động tính Shield</label>
              <p class="text-xs text-gray-500">Tính toán số shield Cambridge tự động</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.cambridge_auto_shield" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            </label>
          </div>

          <!-- Starters Pass Score -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Điểm đạt Starters (shield)
            </label>
            <input
              v-model.number="settings.cambridge_starters_pass"
              type="number"
              min="1"
              max="5"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Movers Pass Score -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Điểm đạt Movers (shield)
            </label>
            <input
              v-model.number="settings.cambridge_movers_pass"
              type="number"
              min="1"
              max="5"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Flyers Pass Score -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Điểm đạt Flyers (shield)
            </label>
            <input
              v-model.number="settings.cambridge_flyers_pass"
              type="number"
              min="1"
              max="5"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <!-- AI Tạo Đề IELTS -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">AI Tạo Đề IELTS</h3>

        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Thiết lập AI để tự động tạo đề thi IELTS Reading với nội dung và câu hỏi theo chuẩn quốc tế.
          </p>

          <button
            @click="showGenerationAISettings = true"
            class="w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Thiết lập AI Tạo Đề
          </button>
        </div>
      </div>

      <!-- AI Chấm Bài -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">AI Chấm Bài</h3>

        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Thiết lập AI để tự động chấm bài thi IELTS Writing và Speaking với tiêu chí đánh giá chi tiết.
          </p>

          <button
            @click="showGradingAISettings = true"
            class="w-full px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Thiết lập AI Chấm Bài
          </button>
        </div>
      </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
      <button
        @click="saveSettings"
        :disabled="saving"
        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
      >
        <svg v-if="saving" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ saving ? 'Đang lưu...' : 'Lưu cài đặt' }}
      </button>
    </div>

    <!-- AI Tạo Đề Settings Modal -->
    <div v-if="showGenerationAISettings" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Thiết lập AI Tạo Đề IELTS</h3>
            <button @click="showGenerationAISettings = false" class="text-gray-400 hover:text-gray-600">
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
                <input type="radio" v-model="generationAI.provider" value="openai" class="mr-2" />
                <span>OpenAI (GPT)</span>
              </label>
              <label class="flex items-center">
                <input type="radio" v-model="generationAI.provider" value="anthropic" class="mr-2" />
                <span>Anthropic (Claude)</span>
              </label>
            </div>
          </div>

          <!-- API Key -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
            <!-- Show current masked key if exists -->
            <div v-if="generationAI.hasApiKey && !generationAI.apiKey" class="mb-2 p-3 bg-green-50 border border-green-200 rounded-lg">
              <div class="flex items-center gap-2 text-green-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">Đã cấu hình: {{ generationAI.maskedApiKey }}</span>
              </div>
              <p class="text-xs text-green-600 mt-1">Nhập key mới bên dưới nếu muốn thay đổi</p>
            </div>
            <input
              v-model="generationAI.apiKey"
              type="password"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
              :placeholder="generationAI.hasApiKey ? 'Nhập key mới để thay đổi...' : (generationAI.provider === 'openai' ? 'sk-...' : 'sk-ant-...')"
            />
            <p class="text-xs text-gray-500 mt-1">API key được mã hóa và lưu trữ an toàn trên server</p>
          </div>

          <!-- Model Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
            <select v-model="generationAI.model" class="w-full px-4 py-2 border rounded-lg">
              <template v-if="generationAI.provider === 'openai'">
                <optgroup label="GPT-5 Series (Latest - 400K context)">
                  <option value="gpt-5.2">GPT-5.2 (Latest - Best Quality)</option>
                  <option value="gpt-5.1">GPT-5.1 (Recommended)</option>
                </optgroup>
              </template>
              <template v-else-if="generationAI.provider === 'anthropic'">
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
              Temperature: {{ generationAI.temperature }}
            </label>
            <input
              v-model.number="generationAI.temperature"
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
              v-model.number="generationAI.maxTokens"
              type="number"
              min="1000"
              max="16000"
              class="w-full px-4 py-2 border rounded-lg"
            />
            <p class="text-xs text-gray-500 mt-1">Số token tối đa cho mỗi lần tạo đề (khuyến nghị: 8000-16000)</p>
          </div>

          <!-- Info box -->
          <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <h4 class="font-medium text-purple-800 mb-2">Hướng dẫn sử dụng AI tạo đề</h4>
            <ul class="text-sm text-purple-700 space-y-1 list-disc list-inside">
              <li>Vào trang tạo đề IELTS và chọn kỹ năng Reading</li>
              <li>Click nút "Tạo đề bằng AI" để bắt đầu</li>
              <li>Chọn tự tạo hoàn toàn hoặc upload file Word/PDF</li>
              <li>AI sẽ tự động tạo bài đọc và câu hỏi theo chuẩn IELTS</li>
            </ul>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-end space-x-4">
          <button
            @click="showGenerationAISettings = false"
            :disabled="savingGenerationAI"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50"
          >
            Hủy
          </button>
          <button
            @click="saveGenerationAISettings"
            :disabled="savingGenerationAI"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center"
          >
            <svg v-if="savingGenerationAI" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ savingGenerationAI ? 'Đang lưu...' : 'Lưu thiết lập' }}
          </button>
        </div>
      </div>
    </div>

    <!-- AI Chấm Bài Settings Modal -->
    <div v-if="showGradingAISettings" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Thiết lập AI Chấm bài</h3>
            <button @click="showGradingAISettings = false" class="text-gray-400 hover:text-gray-600">
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
                <input type="radio" v-model="gradingAI.provider" value="openai" class="mr-2" />
                <span>OpenAI (GPT)</span>
              </label>
              <label class="flex items-center">
                <input type="radio" v-model="gradingAI.provider" value="anthropic" class="mr-2" />
                <span>Anthropic (Claude)</span>
              </label>
              <label class="flex items-center">
                <input type="radio" v-model="gradingAI.provider" value="azure" class="mr-2" />
                <span class="flex items-center">
                  <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                  </svg>
                  Azure (TTS & Grading)
                </span>
              </label>
            </div>
            <p v-if="gradingAI.provider === 'azure'" class="text-xs text-blue-600 mt-2">
              Azure TTS & Grading - Cho chấm Speaking IELTS và TTS
            </p>
          </div>

          <!-- API Key (OpenAI/Anthropic) -->
          <div v-if="gradingAI.provider !== 'azure'">
            <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
            <!-- Show current masked key if exists -->
            <div v-if="gradingAI.hasApiKey && !gradingAI.apiKey" class="mb-2 p-3 bg-green-50 border border-green-200 rounded-lg">
              <div class="flex items-center gap-2 text-green-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">Đã cấu hình: {{ gradingAI.maskedApiKey }}</span>
              </div>
              <p class="text-xs text-green-600 mt-1">Nhập key mới bên dưới nếu muốn thay đổi</p>
            </div>
            <input
              v-model="gradingAI.apiKey"
              type="password"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
              :placeholder="gradingAI.hasApiKey ? 'Nhập key mới để thay đổi...' : 'sk-...'"
            />
            <p class="text-xs text-gray-500 mt-1">API key được mã hóa và lưu trữ an toàn trên server</p>
          </div>

          <!-- Azure Settings -->
          <div v-if="gradingAI.provider === 'azure'" class="space-y-4 p-4 bg-blue-50 rounded-lg">
            <h4 class="font-medium text-blue-800">Azure Configuration</h4>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Azure API Key</label>
              <!-- Show current masked key if exists -->
              <div v-if="gradingAI.hasApiKey && !gradingAI.azureKey" class="mb-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center gap-2 text-green-700">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span class="text-sm font-medium">Đã cấu hình: {{ gradingAI.maskedApiKey }}</span>
                </div>
                <p class="text-xs text-green-600 mt-1">Nhập key mới bên dưới nếu muốn thay đổi</p>
              </div>
              <input
                v-model="gradingAI.azureKey"
                type="password"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                :placeholder="gradingAI.hasApiKey ? 'Nhập key mới để thay đổi...' : 'Azure API key...'"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Azure Endpoint URL</label>
              <input
                v-model="gradingAI.azureEndpoint"
                type="text"
                class="w-full px-4 py-2 border rounded-lg"
                placeholder="https://eastasia.api.cognitive.microsoft.com/"
              />
              <div class="text-xs text-gray-600 mt-1">
                <p><strong>Cognitive Services:</strong> https://&lt;region&gt;.api.cognitive.microsoft.com/</p>
                <p><strong>Speech Service:</strong> https://&lt;region&gt;.tts.speech.microsoft.com/</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Region</label>
              <select v-model="gradingAI.azureRegion" class="w-full px-4 py-2 border rounded-lg">
                <option value="eastasia">East Asia (Hong Kong)</option>
                <option value="southeastasia">Southeast Asia (Singapore)</option>
                <option value="eastus">East US</option>
                <option value="westus">West US</option>
                <option value="westeurope">West Europe</option>
                <option value="northeurope">North Europe</option>
                <option value="japaneast">Japan East</option>
                <option value="koreacentral">Korea Central</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Speaking Language</label>
              <select v-model="gradingAI.speakingLanguage" class="w-full px-4 py-2 border rounded-lg">
                <option value="en-US">English (US)</option>
                <option value="en-GB">English (UK)</option>
                <option value="en-AU">English (Australia)</option>
              </select>
            </div>

            <div class="text-xs text-gray-600 space-y-1">
              <p><strong>Azure TTS & Grading bao gồm:</strong></p>
              <ul class="list-disc list-inside ml-2">
                <li>Text-to-Speech cho IELTS Speaking tests</li>
                <li>AI Grading cho bài thi Writing</li>
                <li>Pronunciation Assessment (nếu có)</li>
              </ul>
            </div>
          </div>

          <!-- Model Selection (not for Azure) -->
          <div v-if="gradingAI.provider !== 'azure'">
            <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
            <select v-model="gradingAI.model" class="w-full px-4 py-2 border rounded-lg">
              <template v-if="gradingAI.provider === 'openai'">
                <optgroup label="GPT-5 Series (Latest - 400K context)">
                  <option value="gpt-5.2">GPT-5.2 (Latest - Best Quality)</option>
                  <option value="gpt-5.1">GPT-5.1 (Recommended)</option>
                </optgroup>
              </template>
              <template v-else-if="gradingAI.provider === 'anthropic'">
                <optgroup label="Claude 4.5 Series (Latest)">
                  <option value="claude-opus-4-5-20251124">Claude Opus 4.5 (Best)</option>
                  <option value="claude-sonnet-4-5-20250929">Claude Sonnet 4.5 (Recommended)</option>
                  <option value="claude-haiku-4-5-20251015">Claude Haiku 4.5 (Fastest)</option>
                </optgroup>
                <optgroup label="Claude 4 Series">
                  <option value="claude-opus-4-20250514">Claude Opus 4</option>
                  <option value="claude-sonnet-4-20250514">Claude Sonnet 4</option>
                </optgroup>
              </template>
            </select>
          </div>

          <!-- Temperature (not for Azure) -->
          <div v-if="gradingAI.provider !== 'azure'">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Temperature: {{ gradingAI.temperature }}
            </label>
            <input
              v-model.number="gradingAI.temperature"
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

          <!-- Max Tokens (not for Azure) -->
          <div v-if="gradingAI.provider !== 'azure'">
            <label class="block text-sm font-medium text-gray-700 mb-2">Max Tokens</label>
            <input
              v-model.number="gradingAI.maxTokens"
              type="number"
              min="100"
              max="4000"
              class="w-full px-4 py-2 border rounded-lg"
            />
            <p class="text-xs text-gray-500 mt-1">Số token tối đa cho mỗi lần chấm bài (khuyến nghị: 2000)</p>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-end space-x-4">
          <button
            @click="showGradingAISettings = false"
            :disabled="savingGradingAI"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50"
          >
            Hủy
          </button>
          <button
            @click="saveGradingAISettings"
            :disabled="savingGradingAI"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center"
          >
            <svg v-if="savingGradingAI" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ savingGradingAI ? 'Đang lưu...' : 'Lưu thiết lập' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '@/api'
import Swal from 'sweetalert2'

const saving = ref(false)
const savingGenerationAI = ref(false)
const savingGradingAI = ref(false)

// Modal visibility
const showGenerationAISettings = ref(false)
const showGradingAISettings = ref(false)

// Store all provider settings
const allGenerationSettings = ref({})
const allGradingSettings = ref({})

const settings = ref({
  // General
  default_time_limit: 60,
  default_pass_score: 70,
  default_max_attempts: 3,
  shuffle_questions: false,
  shuffle_options: false,

  // Display
  show_score_immediately: true,
  show_answers_after_submit: false,
  show_correct_marks: true,
  allow_review: true,

  // IELTS
  ielts_auto_band_score: true,
  ielts_listening_time: 30,
  ielts_reading_time: 60,
  ielts_writing_time: 60,

  // Cambridge
  cambridge_auto_shield: true,
  cambridge_starters_pass: 3,
  cambridge_movers_pass: 3,
  cambridge_flyers_pass: 3
})

// AI Generation Settings (for examination_generation module)
const generationAI = ref({
  provider: 'openai',
  apiKey: '',
  hasApiKey: false,
  maskedApiKey: '',
  model: 'gpt-5.1',
  temperature: 0.7,
  maxTokens: 8000
})

// AI Grading Settings (for examination_grading module)
const gradingAI = ref({
  provider: 'openai',
  apiKey: '',
  hasApiKey: false,
  maskedApiKey: '',
  model: 'gpt-5.1',
  temperature: 0.3,
  maxTokens: 2000,
  // Azure specific
  azureKey: '',
  azureEndpoint: '',
  azureRegion: 'eastasia',
  speakingLanguage: 'en-US'
})

onMounted(async () => {
  await fetchSettings()
  await loadGenerationAISettings()
  await loadGradingAISettings()
})

async function fetchSettings() {
  try {
    const response = await api.get('/examination/settings')
    if (response.data.data) {
      settings.value = { ...settings.value, ...response.data.data }
    }
  } catch (error) {
    console.error('Error fetching settings:', error)
  }
}

async function saveSettings() {
  saving.value = true
  try {
    await api.post('/examination/settings', settings.value)
    Swal.fire('Thành công', 'Cài đặt đã được lưu!', 'success')
  } catch (error) {
    console.error('Error saving settings:', error)
    Swal.fire('Lỗi', 'Có lỗi xảy ra khi lưu cài đặt!', 'error')
  } finally {
    saving.value = false
  }
}

// AI Generation Settings Functions
async function loadGenerationAISettings() {
  try {
    const response = await api.get('/examination/ai-settings', {
      params: { module: 'examination_generation' }
    })

    if (response.data.success && response.data.data) {
      // Store all provider settings
      allGenerationSettings.value = response.data.data

      // Update form with current provider's data
      updateGenerationAIFromProvider()
    }
  } catch (error) {
    console.error('Error loading generation AI settings:', error)
  }
}

// Update generation AI form data based on current provider
function updateGenerationAIFromProvider() {
  const currentProvider = generationAI.value.provider || 'openai'
  const providerData = allGenerationSettings.value[currentProvider] || {}

  generationAI.value = {
    provider: currentProvider,
    apiKey: '',
    hasApiKey: providerData.has_api_key || false,
    maskedApiKey: providerData.masked_api_key || '',
    model: providerData.model || 'gpt-5.1',
    temperature: providerData.settings?.temperature || 0.7,
    maxTokens: providerData.settings?.max_tokens || 8000
  }
}

// Watch for provider changes
watch(() => generationAI.value.provider, () => {
  if (Object.keys(allGenerationSettings.value).length > 0) {
    updateGenerationAIFromProvider()
  }
})

async function saveGenerationAISettings() {
  savingGenerationAI.value = true
  try {
    const payload = {
      module: 'examination_generation',
      provider: generationAI.value.provider,
      api_key: generationAI.value.apiKey || undefined,
      model: generationAI.value.model,
      settings: {
        temperature: generationAI.value.temperature,
        max_tokens: generationAI.value.maxTokens
      }
    }

    const response = await api.post('/examination/ai-settings', payload)

    if (response.data.success) {
      await Swal.fire('Thành công', 'Thiết lập AI Tạo Đề đã được lưu!', 'success')
      showGenerationAISettings.value = false
      await loadGenerationAISettings()
    }
  } catch (error) {
    console.error('Error saving generation AI settings:', error)
    Swal.fire('Lỗi', 'Có lỗi xảy ra khi lưu thiết lập!', 'error')
  } finally {
    savingGenerationAI.value = false
  }
}

// AI Grading Settings Functions
async function loadGradingAISettings() {
  try {
    const response = await api.get('/examination/ai-settings', {
      params: { module: 'examination_grading' }
    })

    if (response.data.success && response.data.data) {
      // Store all provider settings
      allGradingSettings.value = response.data.data

      // Update form with current provider's data
      updateGradingAIFromProvider()
    }
  } catch (error) {
    console.error('Error loading grading AI settings:', error)
  }
}

// Update grading AI form data based on current provider
function updateGradingAIFromProvider() {
  const currentProvider = gradingAI.value.provider || 'openai'
  const providerData = allGradingSettings.value[currentProvider] || {}

  gradingAI.value = {
    provider: currentProvider,
    apiKey: '',
    hasApiKey: providerData.has_api_key || false,
    maskedApiKey: providerData.masked_api_key || '',
    model: providerData.model || 'gpt-5.1',
    temperature: providerData.settings?.temperature || 0.3,
    maxTokens: providerData.settings?.max_tokens || 2000,
    // Azure specific
    azureKey: '',
    azureEndpoint: providerData.settings?.azure_endpoint || '',
    azureRegion: providerData.settings?.azure_region || 'eastasia',
    speakingLanguage: providerData.settings?.speaking_language || 'en-US'
  }
}

// Watch for provider changes
watch(() => gradingAI.value.provider, () => {
  if (Object.keys(allGradingSettings.value).length > 0) {
    updateGradingAIFromProvider()
  }
})

async function saveGradingAISettings() {
  savingGradingAI.value = true
  try {
    const payload = {
      module: 'examination_grading',
      provider: gradingAI.value.provider,
      api_key: gradingAI.value.apiKey || undefined,
      model: gradingAI.value.model,
      settings: {}
    }

    // Add settings based on provider
    if (gradingAI.value.provider === 'azure') {
      payload.settings = {
        azure_key: gradingAI.value.azureKey || undefined,
        azure_endpoint: gradingAI.value.azureEndpoint,
        azure_region: gradingAI.value.azureRegion,
        speaking_language: gradingAI.value.speakingLanguage
      }
    } else {
      payload.settings = {
        temperature: gradingAI.value.temperature,
        max_tokens: gradingAI.value.maxTokens
      }
    }

    const response = await api.post('/examination/ai-settings', payload)

    if (response.data.success) {
      await Swal.fire('Thành công', 'Thiết lập AI Chấm Bài đã được lưu!', 'success')
      showGradingAISettings.value = false
      await loadGradingAISettings()
    }
  } catch (error) {
    console.error('Error saving grading AI settings:', error)
    Swal.fire('Lỗi', 'Có lỗi xảy ra khi lưu thiết lập!', 'error')
  } finally {
    savingGradingAI.value = false
  }
}
</script>
