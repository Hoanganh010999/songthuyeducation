<template>
  <div class="ielts-writing-section">
    <div class="mb-4">
      <h3 class="text-lg font-semibold">IELTS Writing</h3>
      <p class="text-sm text-gray-600">
        {{ subtype === 'academic' ? 'Academic Writing' : 'General Training Writing' }}, 2 tasks, 60 phút
      </p>
    </div>

    <!-- Task Tabs -->
    <div class="flex space-x-2 mb-4">
      <button
        v-for="task in modelValue.tasks"
        :key="task.id"
        @click="activeTask = task.id"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        :class="activeTask === task.id
          ? 'bg-orange-100 text-orange-700'
          : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
      >
        {{ task.title }}
      </button>
      
      <!-- Add Task Button -->
      <button
        v-if="modelValue.tasks.length < 2"
        @click="addTask"
        class="px-4 py-2 rounded-lg text-sm font-medium bg-green-50 text-green-600 hover:bg-green-100 transition-colors flex items-center gap-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Task
      </button>
    </div>

    <!-- Empty State -->
    <div v-if="modelValue.tasks.length === 0" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-gray-600 font-medium mb-2">Chưa có task nào</p>
      <p class="text-sm text-gray-500 mb-4">IELTS Writing thường có 2 tasks</p>
      <button
        @click="addTask"
        class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors inline-flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Thêm Task
      </button>
    </div>

    <!-- Active Task Content -->
    <div v-for="task in modelValue.tasks" :key="task.id" v-show="activeTask === task.id">
      <div class="border rounded-lg p-4 space-y-4">
        <!-- Task Info -->
        <div class="bg-gradient-to-r from-orange-50 to-amber-50 p-4 rounded-lg">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-orange-500 text-white rounded-lg flex items-center justify-center font-bold">
                {{ task.id }}
              </div>
              <div>
                <span class="font-semibold text-gray-800">{{ task.title }}</span>
                <p class="text-sm text-gray-600">
                  {{ task.id === 1 ? '20 phút - tối thiểu 150 từ' : '40 phút - tối thiểu 250 từ' }}
                </p>
              </div>
            </div>
            <span class="px-3 py-1 bg-orange-100 text-orange-700 text-sm rounded-lg font-medium">
              {{ task.id === 1 ? getTask1Type() : 'Essay' }}
            </span>
          </div>
        </div>

        <!-- Task 1 specific: Visual/Image section -->
        <div v-if="task.id === 1" class="space-y-4">
          <!-- Visual Type Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Loại hình ảnh/biểu đồ *
            </label>
            <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
              <button
                v-for="vType in visualTypes"
                :key="vType.value"
                @click="task.visualType = vType.value; emitUpdate()"
                class="p-3 border rounded-lg text-center transition-all hover:shadow-md"
                :class="task.visualType === vType.value
                  ? 'border-orange-500 bg-orange-50 text-orange-700'
                  : 'border-gray-200 hover:border-orange-300'"
              >
                <div class="text-2xl mb-1">{{ vType.icon }}</div>
                <div class="text-xs">{{ vType.label }}</div>
              </button>
            </div>
          </div>

          <!-- Image Section -->
          <div class="border rounded-lg overflow-hidden">
            <!-- Image Header with Tabs -->
            <div class="bg-gray-50 border-b px-4 py-2 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="font-medium text-gray-700">Hình ảnh biểu đồ</span>
                <!-- Warning for map/process types -->
                <span v-if="isMapOrProcess(task)" class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded">
                  Chỉ hỗ trợ Upload
                </span>
              </div>
              <div class="flex gap-1">
                <button
                  @click="imageMode = 'upload'"
                  class="px-3 py-1 text-sm rounded-lg transition-colors"
                  :class="imageMode === 'upload' ? 'bg-orange-500 text-white' : 'text-gray-600 hover:bg-gray-200'"
                >
                  Upload
                </button>
                <!-- AI Generate only for chart types (not map/process) -->
                <button
                  v-if="!isMapOrProcess(task)"
                  @click="imageMode = 'generate'"
                  class="px-3 py-1 text-sm rounded-lg transition-colors flex items-center gap-1"
                  :class="imageMode === 'generate' ? 'bg-orange-500 text-white' : 'text-gray-600 hover:bg-gray-200'"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  AI Tạo Chart
                </button>
                <button
                  @click="imageMode = 'url'"
                  class="px-3 py-1 text-sm rounded-lg transition-colors"
                  :class="imageMode === 'url' ? 'bg-orange-500 text-white' : 'text-gray-600 hover:bg-gray-200'"
                >
                  URL
                </button>
              </div>
            </div>

            <div class="p-4">
              <!-- Image Preview (shown when image exists) -->
              <div v-if="task.imageUrl" class="mb-4">
                <div class="relative inline-block">
                  <img
                    :src="task.imageUrl"
                    alt="Task 1 Visual"
                    class="max-w-full max-h-80 rounded-lg border shadow-sm"
                  />
                  <button
                    @click="removeImage(task)"
                    class="absolute top-2 right-2 bg-red-500 text-white p-1.5 rounded-full hover:bg-red-600 shadow-lg"
                    title="Xóa ảnh"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                <p v-if="task.imageSource" class="text-xs text-gray-500 mt-2">
                  Nguồn: {{ task.imageSource }}
                </p>
              </div>

              <!-- Upload Mode -->
              <div v-if="imageMode === 'upload' && !task.imageUrl">
                <div
                  @click="triggerImageUpload(task)"
                  @dragover.prevent="dragOver = true"
                  @dragleave="dragOver = false"
                  @drop.prevent="handleImageDrop($event, task)"
                  class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors"
                  :class="dragOver ? 'border-orange-400 bg-orange-50' : 'border-gray-300 hover:border-orange-400'"
                >
                  <input
                    type="file"
                    :ref="el => imageInputRefs[task.id] = el"
                    @change="handleImageSelect($event, task)"
                    accept="image/*"
                    class="hidden"
                  />
                  <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <p class="text-gray-600 font-medium">Click hoặc kéo thả hình ảnh vào đây</p>
                  <p class="text-xs text-gray-400 mt-1">PNG, JPG, GIF - tối đa 10MB</p>
                </div>
              </div>

              <!-- AI Generate Mode - Using QuickChart for charts -->
              <div v-else-if="imageMode === 'generate' && !task.imageUrl && !isMapOrProcess(task)" class="space-y-4">
                <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg p-4">
                  <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-purple-500 text-white rounded-lg flex items-center justify-center flex-shrink-0">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                      </svg>
                    </div>
                    <div class="flex-1">
                      <h4 class="font-semibold text-gray-800">AI Tạo Biểu đồ IELTS</h4>
                      <p class="text-sm text-gray-600">Mô tả chủ đề dữ liệu, AI sẽ tạo số liệu và vẽ biểu đồ chuẩn IELTS (đơn giản, grayscale).</p>
                    </div>
                  </div>
                </div>

                <!-- Chart Topic Description -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Mô tả chủ đề dữ liệu *
                  </label>
                  <textarea
                    v-model="generatePrompt"
                    rows="3"
                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="VD: So sánh tỷ lệ sử dụng phương tiện giao thông (xe hơi, xe buýt, xe đạp, đi bộ) ở 4 thành phố London, Paris, Tokyo, Beijing năm 2023"
                  ></textarea>
                  <p class="text-xs text-gray-500 mt-1">
                    Chỉ cần mô tả chủ đề và loại dữ liệu. AI sẽ tự tạo số liệu hợp lý và biểu đồ chuẩn IELTS.
                  </p>
                </div>

                <!-- Quick Topic Templates -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Mẫu chủ đề:</label>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="template in getChartTopicTemplates(task.visualType)"
                      :key="template.label"
                      @click="generatePrompt = template.topic"
                      class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition-colors"
                    >
                      {{ template.label }}
                    </button>
                  </div>
                </div>

                <!-- Two-Step Generation -->
                <div class="border rounded-lg overflow-hidden">
                  <!-- Step 1: Generate Data -->
                  <div class="p-4 bg-gray-50">
                    <div class="flex items-center gap-3 mb-3">
                      <div class="w-7 h-7 bg-purple-500 text-white rounded-full flex items-center justify-center text-sm font-bold">1</div>
                      <span class="font-medium text-gray-700">AI Tạo Số liệu</span>
                      <span v-if="chartData" class="text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded">Đã có dữ liệu</span>
                    </div>

                    <button
                      @click="generateChartData(task)"
                      :disabled="!generatePrompt.trim() || generatingChartData"
                      class="w-full px-4 py-2.5 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2"
                    >
                      <template v-if="generatingChartData">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Đang tạo dữ liệu...</span>
                      </template>
                      <template v-else>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>{{ chartData ? 'Tạo lại dữ liệu' : 'Tạo dữ liệu' }}</span>
                      </template>
                    </button>
                  </div>

                  <!-- Data Preview -->
                  <div v-if="chartData" class="p-4 border-t">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm font-medium text-gray-700">Dữ liệu đã tạo:</span>
                      <button @click="showChartDataEditor = !showChartDataEditor" class="text-xs text-purple-600 hover:underline">
                        {{ showChartDataEditor ? 'Ẩn' : 'Chỉnh sửa' }}
                      </button>
                    </div>
                    <div v-if="!showChartDataEditor" class="text-sm text-gray-600 bg-gray-50 rounded p-3">
                      <p><strong>{{ chartData.title }}</strong></p>
                      <p v-if="chartData.subtitle" class="text-xs text-gray-500">{{ chartData.subtitle }}</p>
                    </div>
                    <textarea
                      v-else
                      v-model="chartDataJson"
                      rows="8"
                      class="w-full px-3 py-2 text-xs font-mono border rounded-lg focus:ring-2 focus:ring-purple-500"
                      @blur="parseChartDataJson"
                    ></textarea>
                  </div>

                  <!-- Step 2: Generate Chart Image -->
                  <div class="p-4 border-t" :class="chartData ? '' : 'opacity-50 pointer-events-none'">
                    <div class="flex items-center gap-3 mb-3">
                      <div class="w-7 h-7 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">2</div>
                      <span class="font-medium text-gray-700">Vẽ Biểu đồ</span>
                    </div>

                    <button
                      @click="generateChartImage(task)"
                      :disabled="!chartData || generatingChart"
                      class="w-full px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2"
                    >
                      <template v-if="generatingChart">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Đang vẽ biểu đồ...</span>
                      </template>
                      <template v-else>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Vẽ biểu đồ</span>
                      </template>
                    </button>
                  </div>
                </div>

                <!-- Generation Progress -->
                <div v-if="generatingChartData || generatingChart" class="bg-gray-50 rounded-lg p-4">
                  <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 border-4 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-sm text-gray-600">{{ generationStatus }}</span>
                  </div>
                  <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-purple-500 to-blue-500 transition-all duration-500" :style="{ width: generationProgress + '%' }"></div>
                  </div>
                </div>

                <!-- API Settings Warning -->
                <div v-if="!hasAISettings" class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                  <div class="flex items-center gap-2 text-amber-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-sm">Chưa cấu hình API key. Vào <strong>Thiết lập AI</strong> để cấu hình.</span>
                  </div>
                </div>
              </div>

              <!-- Map/Process Notice -->
              <div v-else-if="imageMode === 'generate' && !task.imageUrl && isMapOrProcess(task)" class="p-6 text-center">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </div>
                <h4 class="font-semibold text-gray-800 mb-2">Không hỗ trợ tạo tự động</h4>
                <p class="text-sm text-gray-600 mb-4">
                  Map và Process diagram cần được thiết kế thủ công.<br>
                  Vui lòng <strong>Upload</strong> hình ảnh có sẵn.
                </p>
                <button
                  @click="imageMode = 'upload'"
                  class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors"
                >
                  Chuyển sang Upload
                </button>
              </div>

              <!-- URL Mode -->
              <div v-else-if="imageMode === 'url' && !task.imageUrl" class="space-y-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">URL hình ảnh</label>
                  <div class="flex gap-2">
                    <input
                      v-model="imageUrlInput"
                      type="url"
                      class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"
                      placeholder="https://example.com/chart.png"
                    />
                    <button
                      @click="loadImageFromUrl(task)"
                      :disabled="!imageUrlInput.trim()"
                      class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:opacity-50"
                    >
                      Tải ảnh
                    </button>
                  </div>
                </div>
              </div>

              <!-- Upload Progress -->
              <div v-if="uploadingImage" class="mt-3">
                <div class="flex items-center gap-3 mb-1">
                  <svg class="w-5 h-5 text-orange-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span class="text-sm text-gray-600">Đang upload... {{ uploadProgress }}%</span>
                </div>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full bg-orange-500 transition-all" :style="{ width: uploadProgress + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Task Prompt with Rich Text Editor -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Đề bài / Hướng dẫn *
          </label>
          <div class="border rounded-lg overflow-hidden">
            <!-- Toolbar -->
            <div class="bg-gray-50 border-b px-2 py-1.5 flex items-center gap-1">
              <button @click="formatText('bold')" class="p-1.5 hover:bg-gray-200 rounded" title="Bold (Ctrl+B)">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6V4zm0 8h9a4 4 0 014 4 4 4 0 01-4 4H6v-8z" />
                </svg>
              </button>
              <button @click="formatText('italic')" class="p-1.5 hover:bg-gray-200 rounded" title="Italic (Ctrl+I)">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M10 4v3h2.21l-3.42 10H6v3h8v-3h-2.21l3.42-10H18V4h-8z" />
                </svg>
              </button>
              <button @click="formatText('underline')" class="p-1.5 hover:bg-gray-200 rounded" title="Underline (Ctrl+U)">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M6 3v7a6 6 0 006 6 6 6 0 006-6V3h-2.5v7a3.5 3.5 0 01-3.5 3.5A3.5 3.5 0 018.5 10V3H6zM5 19h14v2H5v-2z" />
                </svg>
              </button>
              <div class="w-px h-5 bg-gray-300 mx-1"></div>
              <button @click="formatText('insertUnorderedList')" class="p-1.5 hover:bg-gray-200 rounded" title="Bullet List">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </button>
              <button @click="formatText('insertOrderedList')" class="p-1.5 hover:bg-gray-200 rounded" title="Numbered List">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z" />
                </svg>
              </button>
              <div class="w-px h-5 bg-gray-300 mx-1"></div>
              <button
                @click="task.id === 1 ? generateTaskPrompt(task) : generateTask2Prompt(task)"
                :disabled="generatingPrompt"
                class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded hover:bg-purple-200 flex items-center gap-1"
                title="AI tạo đề bài"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                AI Đề bài
              </button>
            </div>
            <div
              :ref="el => promptEditorRefs[task.id] = el"
              contenteditable="true"
              @input="updateTaskPrompt($event, task)"
              @blur="updateTaskPrompt($event, task)"
              class="p-4 min-h-[180px] prose prose-sm max-w-none focus:outline-none focus:bg-orange-50/30"
              v-html="task.prompt"
              :data-placeholder="getPromptPlaceholder(task.id)"
            ></div>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            {{ task.id === 1
              ? 'Mô tả yêu cầu phân tích biểu đồ/sơ đồ/bảng số liệu'
              : 'Nêu chủ đề và yêu cầu bài luận (agree/disagree, discuss both views, advantages/disadvantages...)' }}
          </p>
        </div>

        <!-- Word Count & Time Requirements -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Số từ tối thiểu</label>
            <input
              v-model.number="task.minWords"
              type="number"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"
              :placeholder="task.id === 1 ? '150' : '250'"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian (phút)</label>
            <input
              v-model.number="task.timeLimit"
              type="number"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"
              :placeholder="task.id === 1 ? '20' : '40'"
            />
          </div>
        </div>

        <!-- Scoring Criteria -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tiêu chí chấm điểm IELTS</label>
          <div class="grid grid-cols-2 gap-3">
            <label
              v-for="criterion in scoringCriteria"
              :key="criterion.key"
              class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
              :class="task.criteria?.includes(criterion.key) ? 'border-orange-500 bg-orange-50' : 'hover:bg-gray-50'"
            >
              <input
                type="checkbox"
                :checked="task.criteria?.includes(criterion.key)"
                @change="toggleCriterion(task, criterion.key)"
                class="w-4 h-4 rounded text-orange-600 focus:ring-orange-500"
              />
              <div>
                <span class="text-sm font-medium">{{ criterion.label }}</span>
                <p class="text-xs text-gray-500">{{ criterion.description }}</p>
              </div>
            </label>
          </div>
        </div>

        <!-- Sample Answer (Optional) -->
        <div class="border rounded-lg overflow-hidden">
          <button
            @click="task.showSampleAnswer = !task.showSampleAnswer"
            class="w-full px-4 py-3 bg-gray-50 flex items-center justify-between hover:bg-gray-100 transition-colors"
          >
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="font-medium text-gray-700">Bài mẫu (tùy chọn)</span>
              <span v-if="task.sampleAnswer" class="text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded">Có bài mẫu</span>
            </div>
            <svg
              class="w-5 h-5 text-gray-400 transform transition-transform"
              :class="{ 'rotate-180': task.showSampleAnswer }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="task.showSampleAnswer" class="p-4 border-t">
            <div class="flex justify-end mb-2">
              <button
                @click="generateSampleAnswer(task)"
                :disabled="generatingSample"
                class="px-3 py-1.5 text-xs bg-green-100 text-green-700 rounded-lg hover:bg-green-200 flex items-center gap-1"
              >
                <svg v-if="generatingSample" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                AI tạo bài mẫu
              </button>
            </div>
            <div
              contenteditable="true"
              @input="e => { task.sampleAnswer = e.target.innerHTML; emitUpdate() }"
              class="p-4 min-h-[200px] prose prose-sm max-w-none focus:outline-none border rounded-lg bg-green-50/30"
              v-html="task.sampleAnswer"
              data-placeholder="Nhập bài mẫu hoặc sử dụng AI để tạo..."
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Task 2 Essay Prompt Modal -->
    <div v-if="showTask2Modal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showTask2Modal = false"></div>

        <div class="relative bg-white rounded-lg shadow-xl transform transition-all sm:max-w-lg sm:w-full mx-auto">
          <div class="px-6 py-4 border-b">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">AI Tạo đề Task 2 - Essay</h3>
              <button @click="showTask2Modal = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="px-6 py-4 space-y-4">
            <!-- Essay Type Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Loại bài Essay *</label>
              <div class="space-y-2">
                <label
                  v-for="et in essayTypes"
                  :key="et.value"
                  class="flex items-start p-3 border rounded-lg cursor-pointer transition-colors"
                  :class="task2EssayType === et.value ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-purple-300'"
                >
                  <input
                    type="radio"
                    v-model="task2EssayType"
                    :value="et.value"
                    class="mt-0.5 text-purple-600 focus:ring-purple-500"
                  />
                  <div class="ml-3">
                    <span class="text-sm font-medium text-gray-900">{{ et.label }}</span>
                    <p class="text-xs text-gray-500">{{ et.description }}</p>
                  </div>
                </label>
              </div>
            </div>

            <!-- Topic Input -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Chủ đề bài luận *</label>
              <input
                v-model="task2Topic"
                type="text"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="Ví dụ: Tác động của mạng xã hội đến giới trẻ..."
              />
              <!-- Topic Templates -->
              <div class="mt-2 flex flex-wrap gap-1">
                <button
                  v-for="tpl in task2TopicTemplates"
                  :key="tpl.label"
                  @click="task2Topic = tpl.topic"
                  class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded hover:bg-purple-100 hover:text-purple-700"
                >
                  {{ tpl.label }}
                </button>
              </div>
            </div>
          </div>

          <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
            <button
              @click="showTask2Modal = false"
              class="px-4 py-2 text-sm text-gray-700 bg-white border rounded-lg hover:bg-gray-50"
            >
              Hủy
            </button>
            <button
              @click="confirmGenerateTask2Prompt"
              :disabled="generatingTask2Prompt || !task2Topic.trim()"
              class="px-4 py-2 text-sm text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <svg v-if="generatingTask2Prompt" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              {{ generatingTask2Prompt ? 'Đang tạo...' : 'Tạo đề bài' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/api'
import Swal from 'sweetalert2'

const props = defineProps({
  modelValue: { type: Object, required: true },
  subtype: { type: String, default: 'academic' }
})

const emit = defineEmits(['update:modelValue'])

// Create a computed property for easier access in template
const modelValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const activeTask = ref(1)
const dragOver = ref(false)
const uploadingImage = ref(false)
const uploadProgress = ref(0)
const imageInputRefs = reactive({})
const promptEditorRefs = reactive({})
const imageMode = ref('upload')
const imageUrlInput = ref('')

// AI Generation states
const generatePrompt = ref('')
const generatingImage = ref(false)
const generationStatus = ref('')
const generationProgress = ref(0)
const generatingPrompt = ref(false)
const generatingSample = ref(false)

// QuickChart generation states
const chartData = ref(null)
const chartDataJson = ref('')
const showChartDataEditor = ref(false)
const generatingChartData = ref(false)
const generatingChart = ref(false)

// Visual types for Task 1
const visualTypes = [
  { value: 'bar_chart', label: 'Bar Chart', icon: '📊' },
  { value: 'line_graph', label: 'Line Graph', icon: '📈' },
  { value: 'pie_chart', label: 'Pie Chart', icon: '🥧' },
  { value: 'table', label: 'Table', icon: '📋' },
  { value: 'map', label: 'Map', icon: '🗺️' },
  { value: 'process', label: 'Process', icon: '🔄' }
]

// Prompt templates for quick generation
// IELTS-authentic prompt templates - simple, minimalist, black & white style
const promptTemplates = [
  {
    label: 'Bar Chart - So sánh',
    prompt: 'Simple bar chart showing percentage of people using transport types (car, bus, bicycle, walking) in 4 cities: London, Paris, Tokyo, Beijing. Year 2023. Y-axis 0-60%. Simple grayscale bars, black axis lines, clear labels.'
  },
  {
    label: 'Line Graph - Xu hướng',
    prompt: 'Simple line graph showing internet usage trends for 3 age groups (18-34, 35-54, 55+) from 2010-2023. Y-axis 0-100%. Use solid line, dashed line, and dotted line to differentiate. Simple black/gray style.'
  },
  {
    label: 'Pie Chart - Phân bổ',
    prompt: 'Two simple pie charts side by side: household expenditure in 1980 vs 2020. Categories: Housing, Food, Transport, Entertainment, Others. Use grayscale shading or simple patterns. Percentage labels beside each segment.'
  },
  {
    label: 'Table - Số liệu',
    prompt: 'Simple data table: visitors to 5 attractions in 3 countries, years 2019-2023. Simple black borders, white background, header row in light gray. Numbers in millions.'
  },
  {
    label: 'Map - Thay đổi',
    prompt: 'Two simple maps side by side showing a town in 1990 vs 2020. Black and white only. Simple rectangles for buildings, lines for roads. Labels: residential, shops, park, school, hospital. Compass indicator.'
  },
  {
    label: 'Process - Quy trình',
    prompt: 'Simple flowchart showing plastic bottle recycling process in 8 steps. Black outlines on white. Simple shapes (rectangles, arrows). Numbered stages. No colors or decorations.'
  }
]

const scoringCriteria = [
  { key: 'task_achievement', label: 'Task Achievement', description: 'Hoàn thành yêu cầu đề bài' },
  { key: 'coherence_cohesion', label: 'Coherence & Cohesion', description: 'Mạch lạc và liên kết' },
  { key: 'lexical_resource', label: 'Lexical Resource', description: 'Vốn từ vựng' },
  { key: 'grammar_accuracy', label: 'Grammar Range & Accuracy', description: 'Ngữ pháp' }
]

// Check if AI settings are configured (loaded from API)
const aiSettingsLoaded = ref(false)
const hasAISettings = ref(false)

// Task 2 Essay Prompt Generation
const showTask2Modal = ref(false)
const task2Topic = ref('')
const task2EssayType = ref('opinion')
const generatingTask2Prompt = ref(false)
const currentTask2 = ref(null)

const essayTypes = [
  { value: 'opinion', label: 'Opinion (Agree/Disagree)', description: 'Đưa ra quan điểm đồng ý hoặc không đồng ý' },
  { value: 'discussion', label: 'Discussion (Both Views)', description: 'Thảo luận cả hai quan điểm' },
  { value: 'advantages', label: 'Advantages & Disadvantages', description: 'Phân tích ưu và nhược điểm' },
  { value: 'problem', label: 'Problem & Solution', description: 'Nêu vấn đề và đề xuất giải pháp' },
  { value: 'twoPart', label: 'Two-Part Question', description: 'Trả lời hai câu hỏi liên quan' }
]

const task2TopicTemplates = [
  { label: 'Công nghệ', topic: 'Tác động của mạng xã hội đến giới trẻ' },
  { label: 'Giáo dục', topic: 'Học trực tuyến vs học truyền thống' },
  { label: 'Môi trường', topic: 'Biến đổi khí hậu và trách nhiệm cá nhân' },
  { label: 'Xã hội', topic: 'Khoảng cách giàu nghèo ngày càng tăng' },
  { label: 'Sức khỏe', topic: 'Thực phẩm nhanh và béo phì ở trẻ em' },
  { label: 'Việc làm', topic: 'Làm việc từ xa - xu hướng tương lai' }
]

async function loadAISettings() {
  try {
    const response = await api.get('/examination/ai-settings', {
      params: { module: 'examination_generation' }
    })
    if (response.data.success && response.data.data) {
      // Check if any provider (openai, anthropic, or azure) has API key
      const settings = response.data.data
      hasAISettings.value = (settings.openai?.has_api_key || settings.anthropic?.has_api_key || settings.azure?.has_api_key) || false
    }
  } catch (error) {
    console.error('Error loading AI settings:', error)
  } finally {
    aiSettingsLoaded.value = true
  }
}

onMounted(() => {
  loadAISettings()
})

function getTask1Type() {
  if (props.subtype === 'academic') {
    const task = props.modelValue.tasks?.find(t => t.id === 1)
    const typeLabels = {
      bar_chart: 'Bar Chart',
      line_graph: 'Line Graph',
      pie_chart: 'Pie Chart',
      table: 'Table',
      map: 'Map',
      process: 'Process Diagram'
    }
    return typeLabels[task?.visualType] || 'Graph/Chart/Diagram'
  }
  return 'Letter'
}

// Check if visual type is map or process (requires upload, no AI generation)
function isMapOrProcess(task) {
  return task?.visualType === 'map' || task?.visualType === 'process'
}

// Get topic templates based on chart type
function getChartTopicTemplates(visualType) {
  const templates = {
    bar_chart: [
      { label: 'Giao thông', topic: 'Tỷ lệ sử dụng phương tiện giao thông (car, bus, bicycle, walking) ở 4 thành phố London, Paris, Tokyo, Beijing năm 2023' },
      { label: 'Học vấn', topic: 'Tỷ lệ nam và nữ tốt nghiệp đại học ở 5 quốc gia: Vietnam, Japan, USA, UK, Australia năm 2022' },
      { label: 'Tiêu dùng', topic: 'Chi tiêu trung bình hàng tháng cho các danh mục (food, housing, transport, entertainment) của 3 nhóm tuổi: 20-30, 31-45, 46-60' },
      { label: 'Năng lượng', topic: 'Tỷ lệ sử dụng các nguồn năng lượng (coal, oil, gas, nuclear, renewable) ở 4 quốc gia năm 2020' }
    ],
    line_graph: [
      { label: 'Internet', topic: 'Tỷ lệ sử dụng internet theo 3 nhóm tuổi (18-34, 35-54, 55+) từ 2010 đến 2023' },
      { label: 'Du lịch', topic: 'Số lượng khách du lịch quốc tế đến 3 quốc gia Vietnam, Thailand, Malaysia từ 2015 đến 2023' },
      { label: 'Dân số', topic: 'Dân số của 3 thành phố lớn nhất Vietnam từ 2000 đến 2020' },
      { label: 'Giá cả', topic: 'Biến động giá nhà ở 3 thành phố New York, London, Tokyo từ 2010 đến 2023 (chỉ số 100 = năm 2010)' }
    ],
    pie_chart: [
      { label: 'Chi tiêu hộ gia đình', topic: 'Phân bổ chi tiêu hộ gia đình năm 1980 và 2020: Housing, Food, Transport, Entertainment, Others' },
      { label: 'Năng lượng', topic: 'Nguồn điện năng ở Vietnam năm 2000 và 2023: Thủy điện, Nhiệt điện than, Khí đốt, Năng lượng tái tạo' },
      { label: 'Thời gian rảnh', topic: 'Cách người trẻ (18-30) sử dụng thời gian rảnh năm 2010 vs 2023: Social media, Sports, Reading, Gaming, Others' },
      { label: 'Xuất khẩu', topic: 'Cơ cấu hàng xuất khẩu của Vietnam năm 2010 và 2022: Electronics, Textiles, Agriculture, Machinery, Others' }
    ],
    table: [
      { label: 'Du lịch', topic: 'Số lượng khách tham quan 5 địa điểm du lịch nổi tiếng (Eiffel Tower, Great Wall, Colosseum, Machu Picchu, Taj Mahal) từ 2019-2023' },
      { label: 'Giáo dục', topic: 'Số sinh viên quốc tế ở 5 quốc gia (USA, UK, Australia, Canada, Germany) theo ngành học: Business, Engineering, IT, Medicine, Arts' },
      { label: 'Việc làm', topic: 'Tỷ lệ thất nghiệp theo nhóm tuổi (16-24, 25-34, 35-54, 55+) ở 4 quốc gia từ 2019-2023' },
      { label: 'Giao thông', topic: 'Thời gian di chuyển trung bình đến nơi làm việc (phút) ở 6 thành phố lớn theo phương tiện: Car, Public transport, Bicycle' }
    ]
  }
  return templates[visualType] || templates['bar_chart']
}

function getPromptPlaceholder(taskId) {
  if (taskId === 1) {
    return props.subtype === 'academic'
      ? 'VD: The chart below shows the percentage of households with internet access in different countries from 2000 to 2020.\n\nSummarise the information by selecting and reporting the main features, and make comparisons where relevant.\n\nWrite at least 150 words.'
      : 'VD: You recently bought a product online but it was damaged when it arrived. Write a letter to the company...'
  }
  return 'VD: Some people believe that technology has made our lives more complicated. To what extent do you agree or disagree?\n\nGive reasons for your answer and include any relevant examples from your own knowledge or experience.\n\nWrite at least 250 words.'
}

function triggerImageUpload(task) {
  if (imageInputRefs[task.id]) {
    imageInputRefs[task.id].click()
  }
}

function handleImageSelect(event, task) {
  const file = event.target.files?.[0]
  if (file) {
    uploadImage(file, task)
  }
}

function handleImageDrop(event, task) {
  dragOver.value = false
  const file = event.dataTransfer.files?.[0]
  if (file && file.type.startsWith('image/')) {
    uploadImage(file, task)
  }
}

async function uploadImage(file, task) {
  if (file.size > 10 * 1024 * 1024) {
    Swal.fire('Lỗi', 'File quá lớn. Vui lòng chọn file nhỏ hơn 10MB', 'error')
    return
  }

  uploadingImage.value = true
  uploadProgress.value = 0

  try {
    // Create FormData for upload
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', 'ielts_writing')

    // Upload to server
    const response = await api.post('/upload/image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.round((progressEvent.loaded / progressEvent.total) * 100)
      }
    })

    if (response.data.success) {
      task.imageUrl = response.data.url
      task.imageSource = 'upload'
      task.imagePath = response.data.path
      emitUpdate()
    } else {
      throw new Error(response.data.message || 'Upload failed')
    }
  } catch (error) {
    console.error('Upload error:', error)
    // Fallback to local preview if server upload fails
    const reader = new FileReader()
    reader.onload = (e) => {
      task.imageUrl = e.target.result
      task.imageSource = 'local'
      emitUpdate()
    }
    reader.readAsDataURL(file)
  } finally {
    uploadingImage.value = false
    uploadProgress.value = 0
  }
}

function removeImage(task) {
  task.imageUrl = null
  task.imageSource = null
  task.imagePath = null
  emitUpdate()
}

async function loadImageFromUrl(task) {
  if (!imageUrlInput.value.trim()) return

  try {
    task.imageUrl = imageUrlInput.value
    task.imageSource = 'url'
    imageUrlInput.value = ''
    emitUpdate()
  } catch (error) {
    Swal.fire('Lỗi', 'Không thể tải ảnh từ URL này', 'error')
  }
}

// Legacy DALL-E image generation (kept for compatibility but not used in UI anymore)
async function generateImage(task) {
  if (!generatePrompt.value.trim()) return

  generatingImage.value = true
  generationStatus.value = 'Đang khởi tạo...'
  generationProgress.value = 10

  try {
    if (!hasAISettings.value) {
      throw new Error('Chưa cấu hình API key. Vui lòng vào Thiết lập AI để cấu hình.')
    }

    generationStatus.value = 'Đang gửi yêu cầu tạo ảnh...'
    generationProgress.value = 30

    const visualTypeDesc = visualTypes.find(v => v.value === task.visualType)?.label || 'chart'
    const imagePrompt = `IELTS Writing Task 1 ${visualTypeDesc}.\n\n${generatePrompt.value}`

    const response = await api.post('/examination/generate-image', {
      prompt: imagePrompt,
      visual_type: task.visualType
    })

    generationStatus.value = 'Đang xử lý kết quả...'
    generationProgress.value = 80

    if (response.data.success && response.data.image_url) {
      task.imageUrl = response.data.image_url
      task.imageSource = 'ai_generated'
      task.imagePath = response.data.image_path
      task.imagePrompt = generatePrompt.value
      generationProgress.value = 100
      generationStatus.value = 'Hoàn thành!'
      emitUpdate()
    } else {
      throw new Error(response.data.message || 'Không thể tạo ảnh')
    }
  } catch (error) {
    console.error('Image generation error:', error)
    Swal.fire('Lỗi', 'Lỗi khi tạo ảnh: ' + (error.response?.data?.message || error.message), 'error')
  } finally {
    setTimeout(() => {
      generatingImage.value = false
      generationProgress.value = 0
    }, 1000)
  }
}

// Step 1: Generate chart data using AI
async function generateChartData(task) {
  if (!generatePrompt.value.trim()) return

  generatingChartData.value = true
  generationStatus.value = 'Đang tạo số liệu...'
  generationProgress.value = 20

  try {
    if (!hasAISettings.value) {
      throw new Error('Chưa cấu hình API key. Vui lòng vào Thiết lập AI để cấu hình.')
    }

    generationStatus.value = 'AI đang phân tích và tạo dữ liệu...'
    generationProgress.value = 50

    const response = await api.post('/examination/generate-chart-data', {
      visual_type: task.visualType,
      topic: generatePrompt.value
    })

    generationProgress.value = 90

    if (response.data.success && response.data.chart_data) {
      chartData.value = response.data.chart_data
      chartDataJson.value = JSON.stringify(response.data.chart_data, null, 2)
      generationProgress.value = 100
      generationStatus.value = 'Đã tạo dữ liệu thành công!'
    } else {
      throw new Error(response.data.message || 'Không thể tạo dữ liệu')
    }
  } catch (error) {
    console.error('Chart data generation error:', error)
    Swal.fire('Lỗi', 'Lỗi khi tạo dữ liệu: ' + (error.response?.data?.message || error.message), 'error')
  } finally {
    setTimeout(() => {
      generatingChartData.value = false
      generationProgress.value = 0
    }, 500)
  }
}

// Step 2: Generate chart image using QuickChart
async function generateChartImage(task) {
  if (!chartData.value) {
    Swal.fire('Cảnh báo', 'Vui lòng tạo dữ liệu trước', 'warning')
    return
  }

  generatingChart.value = true
  generationStatus.value = 'Đang vẽ biểu đồ...'
  generationProgress.value = 30

  try {
    generationStatus.value = 'Đang render biểu đồ với QuickChart...'
    generationProgress.value = 60

    const response = await api.post('/examination/generate-chart', {
      visual_type: task.visualType,
      chart_data: chartData.value
    })

    generationProgress.value = 90

    if (response.data.success && response.data.image_url) {
      task.imageUrl = response.data.image_url
      task.imageSource = 'quickchart'
      task.imagePath = response.data.image_path
      task.chartData = chartData.value
      task.imagePrompt = generatePrompt.value
      generationProgress.value = 100
      generationStatus.value = 'Hoàn thành!'
      emitUpdate()

      // Reset chart data for next generation
      // chartData.value = null
      // chartDataJson.value = ''
    } else {
      throw new Error(response.data.message || 'Không thể vẽ biểu đồ')
    }
  } catch (error) {
    console.error('Chart generation error:', error)
    Swal.fire('Lỗi', 'Lỗi khi vẽ biểu đồ: ' + (error.response?.data?.message || error.message), 'error')
  } finally {
    setTimeout(() => {
      generatingChart.value = false
      generationProgress.value = 0
    }, 1000)
  }
}

// Parse edited chart data JSON
function parseChartDataJson() {
  try {
    chartData.value = JSON.parse(chartDataJson.value)
  } catch (e) {
    Swal.fire('Lỗi', 'JSON không hợp lệ. Vui lòng kiểm tra lại cú pháp.', 'error')
  }
}

async function generateTaskPrompt(task) {
  if (!task.imageUrl && !generatePrompt.value) {
    Swal.fire('Cảnh báo', 'Vui lòng upload ảnh hoặc nhập mô tả biểu đồ trước', 'warning')
    return
  }

  generatingPrompt.value = true

  try {
    if (!hasAISettings.value) {
      throw new Error('Chưa cấu hình API key. Vui lòng vào Thiết lập AI để cấu hình.')
    }

    const visualType = visualTypes.find(v => v.value === task.visualType)?.label || 'chart'
    const description = generatePrompt.value || `A ${visualType} showing data`

    const response = await api.post('/examination/generate-prompt', {
      type: 'writing_task1',
      visual_type: task.visualType,
      description: description
    })

    if (response.data.success && response.data.prompt) {
      task.prompt = response.data.prompt
      if (promptEditorRefs[task.id]) {
        promptEditorRefs[task.id].innerHTML = response.data.prompt
      }
      emitUpdate()
    }
  } catch (error) {
    console.error('Generate prompt error:', error)
    Swal.fire('Lỗi', error.response?.data?.message || error.message, 'error')
  } finally {
    generatingPrompt.value = false
  }
}

// Task 2: Open modal to configure essay prompt
function generateTask2Prompt(task) {
  if (!hasAISettings.value) {
    Swal.fire('Cảnh báo', 'Chưa cấu hình API key. Vui lòng vào Thiết lập AI để cấu hình.', 'warning')
    return
  }
  currentTask2.value = task
  task2Topic.value = ''
  task2EssayType.value = 'opinion'
  showTask2Modal.value = true
}

// Task 2: Generate essay prompt with AI
async function confirmGenerateTask2Prompt() {
  if (!task2Topic.value.trim()) {
    Swal.fire('Cảnh báo', 'Vui lòng nhập chủ đề bài luận', 'warning')
    return
  }

  generatingTask2Prompt.value = true

  try {
    const essayTypeLabel = essayTypes.find(t => t.value === task2EssayType.value)?.label || 'Essay'
    const description = `${essayTypeLabel} essay about: ${task2Topic.value}`

    const response = await api.post('/examination/generate-prompt', {
      type: 'writing_task2',
      essay_type: task2EssayType.value,
      description: description
    })

    if (response.data.success && response.data.prompt) {
      currentTask2.value.prompt = response.data.prompt
      if (promptEditorRefs[currentTask2.value.id]) {
        promptEditorRefs[currentTask2.value.id].innerHTML = response.data.prompt
      }
      emitUpdate()
      showTask2Modal.value = false
    }
  } catch (error) {
    console.error('Generate Task 2 prompt error:', error)
    Swal.fire('Lỗi', error.response?.data?.message || error.message, 'error')
  } finally {
    generatingTask2Prompt.value = false
  }
}

async function generateSampleAnswer(task) {
  if (!task.prompt) {
    Swal.fire('Cảnh báo', 'Vui lòng nhập đề bài trước', 'warning')
    return
  }

  generatingSample.value = true

  try {
    if (!hasAISettings.value) {
      throw new Error('Chưa cấu hình API key. Vui lòng vào Thiết lập AI để cấu hình.')
    }

    const response = await api.post('/examination/generate-sample', {
      type: task.id === 1 ? 'writing_task1' : 'writing_task2',
      prompt: task.prompt,
      visual_type: task.visualType,
      min_words: task.minWords || (task.id === 1 ? 150 : 250)
    })

    if (response.data.success && response.data.sample) {
      task.sampleAnswer = response.data.sample
      emitUpdate()
    }
  } catch (error) {
    console.error('Generate sample error:', error)
    Swal.fire('Lỗi', error.response?.data?.message || error.message, 'error')
  } finally {
    generatingSample.value = false
  }
}

function updateTaskPrompt(event, task) {
  task.prompt = event.target.innerHTML
  emitUpdate()
}

function toggleCriterion(task, criterionKey) {
  if (!task.criteria) task.criteria = []

  const index = task.criteria.indexOf(criterionKey)
  if (index === -1) {
    task.criteria.push(criterionKey)
  } else {
    task.criteria.splice(index, 1)
  }
  emitUpdate()
}

function formatText(command) {
  document.execCommand(command, false, null)
}

function emitUpdate() {
  emit('update:modelValue', props.modelValue)
}

function addTask() {
  const nextId = props.modelValue.tasks.length + 1
  const newTask = {
    id: nextId,
    title: `Task ${nextId}`,
    prompt: '',
    imageUrl: null,
    imageFile: null,
    minWords: nextId === 1 ? 150 : 250,
    timeLimit: nextId === 1 ? 20 : 40,
    criteria: ['task_achievement', 'coherence_cohesion', 'lexical_resource', 'grammar_accuracy'],
    sampleAnswer: '',
    showSampleAnswer: false
  }
  
  if (nextId === 1) {
    newTask.visualType = 'bar_chart'
  }
  
  props.modelValue.tasks.push(newTask)
  activeTask.value = nextId
  emitUpdate()
}

onMounted(() => {
  // Initialize criteria if not set
  props.modelValue.tasks?.forEach(task => {
    if (!task.criteria) {
      task.criteria = ['task_achievement', 'coherence_cohesion', 'lexical_resource', 'grammar_accuracy']
    }
    if (task.id === 1 && !task.visualType) {
      task.visualType = 'bar_chart'
    }
  })
})
</script>

<style scoped>
[contenteditable]:empty:before {
  content: attr(data-placeholder);
  color: #9ca3af;
  pointer-events: none;
}

[contenteditable]:focus:empty:before {
  content: '';
}
</style>
