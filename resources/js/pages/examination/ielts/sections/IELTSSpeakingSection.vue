<template>
  <div class="ielts-speaking-section">
    <div class="mb-4">
      <h3 class="text-lg font-semibold">IELTS Speaking</h3>
      <p class="text-sm text-gray-600">
        3 parts, 11-14 phút tổng cộng
      </p>
    </div>

    <!-- Main Tabs: Parts / Script Preview -->
    <div class="flex space-x-2 mb-4 border-b">
      <button @click="mainTab = 'parts'" class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px" :class="mainTab === 'parts' ? 'border-orange-500 text-orange-700' : 'border-transparent text-gray-500 hover:text-gray-700'">
        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
        Nội dung Part
      </button>
      <button @click="mainTab = 'script'" class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px" :class="mainTab === 'script' ? 'border-orange-500 text-orange-700' : 'border-transparent text-gray-500 hover:text-gray-700'">
        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
        Script Examiner
        <span v-if="hasScript" class="ml-1 px-1.5 py-0.5 text-xs bg-green-100 text-green-700 rounded">✓</span>
      </button>
    </div>

    <!-- Parts Content Tab -->
    <div v-show="mainTab === 'parts'">
      <!-- AI Generation Banner -->
      <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg p-4 mb-4">
        <div class="flex items-start justify-between">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-purple-500 text-white rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            <div class="flex-1">
              <h4 class="font-semibold text-gray-800">AI Tạo đề Speaking Test tự động</h4>
              <p class="text-sm text-gray-600">Mô tả chủ đề, AI sẽ tạo đầy đủ 3 Parts + câu hỏi + script examiner với tên ngẫu nhiên</p>
            </div>
          </div>
          <button @click="showAIGenerationModal = true" :disabled="!hasAISettings" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            AI Tạo đề
          </button>
        </div>
        <div v-if="!hasAISettings" class="mt-3 flex items-center gap-2 text-xs text-amber-700 bg-amber-50 rounded px-3 py-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
          Chưa cấu hình API key. Vào <strong>Thiết lập AI</strong> để cấu hình.
        </div>
      </div>

      <!-- Part Tabs -->
      <div class="flex space-x-2 mb-4">
        <button v-for="part in modelValue.parts" :key="part.id" @click="activePart = part.id" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors" :class="activePart === part.id ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
          {{ part.title }}
          <span v-if="part.questions.length > 0" class="ml-1 text-xs">({{ part.questions.length }})</span>
        </button>
      </div>

      <!-- Active Part Content -->
    <div v-for="part in modelValue.parts" :key="part.id" v-show="activePart === part.id">
      <div class="border rounded-lg p-4 space-y-4">
        <!-- Part Info -->
        <div class="bg-gray-50 p-3 rounded-lg">
          <div class="flex items-center justify-between">
            <div>
              <span class="font-medium">{{ part.title }}</span>
              <span class="text-sm text-gray-600 ml-2">({{ getPartDuration(part.id) }})</span>
            </div>
            <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs rounded">
              {{ getPartType(part.id) }}
            </span>
          </div>
          <p class="text-sm text-gray-600 mt-2">{{ getPartDescription(part.id) }}</p>
        </div>

        <!-- Part 2 specific: Cue Card -->
        <div v-if="part.id === 2" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Topic (Cue Card) *
            </label>
            <div class="border rounded-lg overflow-hidden">
              <!-- Toolbar -->
              <div class="bg-gray-50 border-b px-2 py-1 flex space-x-1">
                <button @click="formatText('bold')" class="p-1.5 hover:bg-gray-200 rounded" title="Bold">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z" />
                  </svg>
                </button>
                <button @click="formatText('italic')" class="p-1.5 hover:bg-gray-200 rounded" title="Italic">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0v16m-4 0h8" />
                  </svg>
                </button>
                <div class="border-l mx-1"></div>
                <button @click="formatText('insertUnorderedList')" class="p-1.5 hover:bg-gray-200 rounded" title="Bullet List">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                  </svg>
                </button>
              </div>
              <div
                contenteditable="true"
                @input="e => part.cueCard = e.target.innerHTML"
                @blur="emitUpdate"
                class="p-4 min-h-[150px] prose prose-sm max-w-none focus:outline-none"
                v-html="part.cueCard"
                placeholder="VD: Describe a place you have visited that you would recommend..."
              ></div>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              Nhập topic và các bullet points gợi ý cho thí sinh
            </p>
          </div>

          <!-- Preparation Time -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian chuẩn bị (phút)</label>
              <input
                v-model.number="part.prepTime"
                type="number"
                class="w-full px-3 py-2 border rounded-lg"
                placeholder="1"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian nói (phút)</label>
              <input
                v-model.number="part.speakTime"
                type="number"
                class="w-full px-3 py-2 border rounded-lg"
                placeholder="2"
              />
            </div>
          </div>
        </div>

        <!-- Questions Section (Part 1 & 3) -->
        <div v-if="part.id !== 2 || (part.id === 2 && part.questions.length > 0)">
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-medium">
              {{ part.id === 2 ? 'Follow-up Questions' : 'Câu hỏi' }}
            </h4>
            <button
              @click="addQuestion(part)"
              class="px-3 py-1 text-sm bg-orange-50 text-orange-600 rounded-lg hover:bg-orange-100"
            >
              + Thêm câu hỏi
            </button>
          </div>

          <div v-if="part.questions.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
            Chưa có câu hỏi nào. Click "Thêm câu hỏi" để bắt đầu.
          </div>

          <draggable
            v-else
            v-model="part.questions"
            item-key="id"
            handle=".drag-handle"
            class="space-y-3"
            @end="updateQuestionNumbers"
          >
            <template #item="{ element: question, index }">
              <div class="border rounded-lg p-4 bg-white">
                <div class="flex items-start space-x-3">
                  <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600 pt-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                    </svg>
                  </div>

                  <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                      <span class="font-medium text-gray-700">Q{{ question.number }}</span>
                      <select
                        v-if="part.id === 1"
                        v-model="question.topic"
                        class="text-xs border rounded px-2 py-1"
                      >
                        <option value="">-- Chủ đề --</option>
                        <option v-for="topic in commonTopics" :key="topic" :value="topic">
                          {{ topic }}
                        </option>
                      </select>
                    </div>

                    <!-- Question Content -->
                    <div
                      contenteditable="true"
                      @input="e => question.content = e.target.innerHTML"
                      @blur="emitUpdate"
                      class="w-full px-3 py-2 border rounded-lg text-sm min-h-[60px] focus:outline-none focus:ring-2 focus:ring-orange-500"
                      v-html="question.content"
                      :placeholder="getQuestionPlaceholder(part.id)"
                    ></div>

                    <!-- Follow-up prompts for Part 3 -->
                    <div v-if="part.id === 3" class="mt-2">
                      <label class="text-xs text-gray-500">Gợi ý theo dõi (nếu thí sinh trả lời ngắn)</label>
                      <input
                        v-model="question.followUp"
                        type="text"
                        class="w-full px-3 py-1 border rounded text-sm mt-1"
                        placeholder="VD: Can you elaborate on that? / Why do you think so?"
                      />
                    </div>
                  </div>

                  <button @click="removeQuestion(part, index)" class="text-red-500 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </template>
          </draggable>
        </div>

        <!-- Scoring Criteria for Part -->
        <div class="border-t pt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Tiêu chí đánh giá</label>
          <div class="grid grid-cols-2 gap-2">
            <label v-for="criterion in scoringCriteria" :key="criterion.key" class="flex items-center space-x-2">
              <input
                type="checkbox"
                :checked="part.criteria?.includes(criterion.key)"
                @change="toggleCriterion(part, criterion.key)"
                class="rounded text-orange-600"
              />
              <span class="text-sm">{{ criterion.label }}</span>
            </label>
          </div>
        </div>

        <!-- Examiner Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú cho giám khảo</label>
          <textarea
            v-model="part.examinerNotes"
            @blur="emitUpdate"
            rows="2"
            class="w-full px-3 py-2 border rounded-lg text-sm"
            placeholder="Lưu ý về cách đánh giá, điểm cần chú ý..."
          ></textarea>
        </div>
      </div>
    </div>
    </div>

    <!-- Script Preview Tab -->
    <div v-show="mainTab === 'script'" class="space-y-4">
      <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start justify-between">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-blue-500 text-white rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
            </div>
            <div>
              <h4 class="font-semibold text-gray-800">Script Examiner cho Azure Speech</h4>
              <p class="text-sm text-gray-600">Script đầy đủ với timing cho từng câu. Dùng để phát âm thanh tự động và ghi âm câu trả lời học sinh.</p>
            </div>
          </div>
          <button @click="generateFullScript" :disabled="generatingScript" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
            <svg v-if="generatingScript" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            {{ generatingScript ? 'Đang tạo...' : (hasScript ? 'Tạo lại Script' : 'Tạo Script') }}
          </button>
        </div>
      </div>

      <!-- Script Legend -->
      <div class="flex flex-wrap gap-4 text-sm">
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-500"></span><span class="text-gray-600">Hướng dẫn (TTS)</span></div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-500"></span><span class="text-gray-600">Câu hỏi - Chờ trả lời (TTS + Record)</span></div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-orange-500"></span><span class="text-gray-600">Timer (Countdown)</span></div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-gray-400"></span><span class="text-gray-600">Chuyển tiếp (TTS)</span></div>
      </div>

      <!-- Script Timeline -->
      <div v-if="modelValue.script && modelValue.script.length > 0" class="space-y-2">
        <div class="flex items-center justify-between bg-gray-100 rounded-lg px-4 py-2 mb-4">
          <span class="text-sm font-medium text-gray-700">Tổng thời lượng ước tính:</span>
          <span class="text-lg font-bold text-orange-600">{{ formatDuration(getTotalDuration()) }}</span>
        </div>

        <div v-for="(item, index) in modelValue.script" :key="index" class="border rounded-lg overflow-hidden" :class="getScriptItemBorderClass(item.type)">
          <div class="flex items-stretch">
            <div class="w-2 flex-shrink-0" :class="getScriptItemBgClass(item.type)"></div>
            <div class="flex-1 p-3">
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-medium px-2 py-0.5 rounded" :class="getScriptItemLabelClass(item.type)">{{ getScriptItemLabel(item.type) }}</span>
                    <span v-if="item.partId" class="text-xs text-gray-400">Part {{ item.partId }}</span>
                    <span v-if="item.questionNumber" class="text-xs text-gray-400">Q{{ item.questionNumber }}</span>
                  </div>
                  <textarea v-model="item.text" @blur="emitUpdate" rows="2" class="w-full px-3 py-2 text-sm border rounded resize-none focus:ring-2 focus:ring-blue-500" :class="item.type === 'question' ? 'bg-green-50' : (item.type === 'timer' ? 'bg-orange-50' : 'bg-white')"></textarea>
                </div>
                <div class="flex flex-col items-end gap-2">
                  <div class="flex items-center gap-1">
                    <input v-model.number="item.duration" type="number" min="1" class="w-16 px-2 py-1 text-sm border rounded text-center" /><span class="text-xs text-gray-500">s</span>
                  </div>
                  <label v-if="item.type === 'question'" class="flex items-center gap-1 text-xs">
                    <input type="checkbox" v-model="item.waitForResponse" class="rounded text-green-600" /><span class="text-gray-600">Ghi âm</span>
                  </label>
                  <div v-if="item.type === 'question' && item.waitForResponse" class="flex items-center gap-1">
                    <span class="text-xs text-gray-500">Record:</span>
                    <input v-model.number="item.responseDuration" type="number" min="10" class="w-14 px-2 py-1 text-xs border rounded text-center" /><span class="text-xs text-gray-500">s</span>
                  </div>
                  <button v-if="item.type !== 'timer'" @click="playTTS(item, index)" :disabled="playingTTS === index" class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded hover:bg-gray-200 flex items-center gap-1">
                    <svg v-if="playingTTS === index" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                    Test
                  </button>
                </div>
              </div>
            </div>
            <button @click="removeScriptItem(index)" class="px-2 text-gray-400 hover:text-red-500 hover:bg-red-50">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
        </div>

        <div class="flex gap-2 mt-4">
          <button @click="addScriptItem('instruction')" class="px-3 py-1.5 text-xs bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100">+ Hướng dẫn</button>
          <button @click="addScriptItem('question')" class="px-3 py-1.5 text-xs bg-green-50 text-green-600 rounded-lg hover:bg-green-100">+ Câu hỏi</button>
          <button @click="addScriptItem('timer')" class="px-3 py-1.5 text-xs bg-orange-50 text-orange-600 rounded-lg hover:bg-orange-100">+ Timer</button>
          <button @click="addScriptItem('transition')" class="px-3 py-1.5 text-xs bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">+ Chuyển tiếp</button>
        </div>
      </div>

      <!-- Empty Script State -->
      <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
        <p class="text-gray-500 mb-4">Chưa có script. Tạo script tự động từ nội dung Part hoặc thêm thủ công.</p>
        <button @click="generateFullScript" :disabled="generatingScript" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Tạo Script từ nội dung Part</button>
      </div>

      <!-- Export Options -->
      <div v-if="hasScript" class="border-t pt-4 mt-4">
        <h4 class="font-medium text-gray-700 mb-3">Xuất Script</h4>
        <div class="flex gap-2">
          <button @click="exportScript('json')" class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center gap-1">JSON</button>
          <button @click="exportScript('txt')" class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center gap-1">Text</button>
        </div>
      </div>
    </div>

    <!-- AI Generation Modal -->
    <div v-if="showAIGenerationModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeAIModal"></div>

        <div class="relative bg-white rounded-lg shadow-xl transform transition-all sm:max-w-2xl sm:w-full mx-auto">
          <div class="px-6 py-4 border-b bg-gradient-to-r from-purple-50 to-blue-50">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-500 text-white rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">AI Tạo đề Speaking Test</h3>
              </div>
              <button @click="closeAIModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="px-6 py-4 space-y-4">
            <!-- Topic Input -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Chủ đề chung cho bài test *
                <span class="text-xs text-gray-500 font-normal ml-1">(AI sẽ tạo câu hỏi phù hợp cho 3 Parts)</span>
              </label>
              <input
                v-model="aiGenerationTopic"
                type="text"
                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="VD: Technology and modern life, Education in the 21st century, Environmental protection..."
              />
            </div>

            <!-- Topic Templates -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Mẫu chủ đề:</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="template in speakingTopicTemplates"
                  :key="template"
                  @click="aiGenerationTopic = template"
                  class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded-full hover:bg-purple-100 hover:text-purple-700 transition-colors"
                >
                  {{ template }}
                </button>
              </div>
            </div>

            <!-- Examiner Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Tên Examiner
                <span class="text-xs text-gray-500 font-normal ml-1">(để trống, AI sẽ tạo tên ngẫu nhiên)</span>
              </label>
              <input
                v-model="examinerName"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="VD: Sarah Johnson, Michael Smith..."
              />
            </div>

            <!-- Preview Info -->
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-800">
                  <p class="font-medium mb-1">AI sẽ tạo:</p>
                  <ul class="list-disc list-inside space-y-1 text-xs">
                    <li>Part 1: 4-5 câu hỏi về bản thân liên quan đến chủ đề</li>
                    <li>Part 2: Cue card với gợi ý chi tiết + 2 câu follow-up</li>
                    <li>Part 3: 4-5 câu hỏi thảo luận sâu hơn</li>
                    <li>Script examiner đầy đủ với timing cho Azure Speech</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
            <button
              @click="closeAIModal"
              class="px-4 py-2 text-sm text-gray-700 bg-white border rounded-lg hover:bg-gray-50"
            >
              Hủy
            </button>
            <button
              @click="generateWithAI"
              :disabled="generatingWithAI || !aiGenerationTopic.trim()"
              class="px-6 py-2 text-sm text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <svg v-if="generatingWithAI" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              {{ generatingWithAI ? 'Đang tạo...' : 'Tạo đề test' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import draggable from 'vuedraggable'
import api from '@/api'

const props = defineProps({
  modelValue: { type: Object, required: true }
})

const emit = defineEmits(['update:modelValue'])

const mainTab = ref('parts')
const activePart = ref(1)
let questionIdCounter = 0

const generatingScript = ref(false)
const playingTTS = ref(null)
const hasAISettings = ref(false)

// AI Generation
const showAIGenerationModal = ref(false)
const aiGenerationTopic = ref('')
const examinerName = ref('')
const generatingWithAI = ref(false)

const speakingTopicTemplates = [
  'Technology and modern life',
  'Education in the 21st century',
  'Environmental protection',
  'Work-life balance',
  'Social media and communication',
  'Health and fitness',
  'Travel and tourism',
  'Art and culture',
  'Food and cooking',
  'Sports and competition'
]

const hasScript = computed(() => props.modelValue.script && props.modelValue.script.length > 0)

onMounted(() => {
  loadAISettings()
  if (!props.modelValue.script) props.modelValue.script = []
})

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
  }
}

const commonTopics = [
  'Work/Study',
  'Hometown',
  'Family',
  'Hobbies',
  'Weather',
  'Food',
  'Sports',
  'Reading',
  'Music',
  'Technology',
  'Travel',
  'Health'
]

const scoringCriteria = [
  { key: 'fluency_coherence', label: 'Fluency & Coherence' },
  { key: 'lexical_resource', label: 'Lexical Resource' },
  { key: 'grammar_range', label: 'Grammatical Range & Accuracy' },
  { key: 'pronunciation', label: 'Pronunciation' }
]

function getNextQuestionId() {
  return ++questionIdCounter
}

function getPartDuration(partId) {
  const durations = {
    1: '4-5 phút',
    2: '3-4 phút',
    3: '4-5 phút'
  }
  return durations[partId]
}

function getPartType(partId) {
  const types = {
    1: 'Introduction & Interview',
    2: 'Individual Long Turn',
    3: 'Two-way Discussion'
  }
  return types[partId]
}

function getPartDescription(partId) {
  const descriptions = {
    1: 'Giám khảo hỏi các câu hỏi quen thuộc về bản thân, gia đình, công việc, sở thích...',
    2: 'Thí sinh nhận một cue card, có 1 phút chuẩn bị và nói trong 1-2 phút về chủ đề',
    3: 'Giám khảo hỏi các câu hỏi thảo luận sâu hơn liên quan đến chủ đề Part 2'
  }
  return descriptions[partId]
}

function getQuestionPlaceholder(partId) {
  const placeholders = {
    1: 'VD: What do you do - do you work or study?',
    2: 'VD: Can you tell me more about...?',
    3: 'VD: How do you think technology has changed the way people communicate?'
  }
  return placeholders[partId]
}

function getTotalQuestionsBefore(partId) {
  let total = 0
  for (const part of props.modelValue.parts) {
    if (part.id === partId) break
    total += part.questions.length
  }
  return total
}

function updateQuestionNumbers() {
  let num = 1
  for (const part of props.modelValue.parts) {
    for (const question of part.questions) {
      question.number = num++
    }
  }
  emitUpdate()
}

function addQuestion(part) {
  const totalBefore = getTotalQuestionsBefore(part.id) + part.questions.length + 1

  const question = {
    id: getNextQuestionId(),
    number: totalBefore,
    content: '',
    topic: '',
    followUp: ''
  }

  part.questions.push(question)
  updateQuestionNumbers()
}

function removeQuestion(part, index) {
  part.questions.splice(index, 1)
  updateQuestionNumbers()
}

function toggleCriterion(part, criterionKey) {
  if (!part.criteria) part.criteria = []

  const index = part.criteria.indexOf(criterionKey)
  if (index === -1) {
    part.criteria.push(criterionKey)
  } else {
    part.criteria.splice(index, 1)
  }
  emitUpdate()
}

function formatText(command) {
  document.execCommand(command, false, null)
}

function emitUpdate() {
  emit('update:modelValue', props.modelValue)
}

// AI Generation methods
function closeAIModal() {
  showAIGenerationModal.value = false
  aiGenerationTopic.value = ''
  examinerName.value = ''
}

async function generateWithAI() {
  if (!aiGenerationTopic.value.trim()) return

  generatingWithAI.value = true
  try {
    const response = await api.post('/examination/generate-speaking-test', {
      topic: aiGenerationTopic.value,
      examiner_name: examinerName.value || undefined
    })

    if (response.data.success && response.data.data) {
      const data = response.data.data

      // Update parts with generated content
      if (data.parts && Array.isArray(data.parts)) {
        for (let i = 0; i < data.parts.length && i < props.modelValue.parts.length; i++) {
          const generatedPart = data.parts[i]
          const modelPart = props.modelValue.parts[i]

          // Update questions - handle both questions and follow_up_questions
          const questions = generatedPart.questions || generatedPart.follow_up_questions
          if (questions) {
            modelPart.questions = questions.map((q, idx) => ({
              id: getNextQuestionId(),
              number: idx + 1,
              content: q.content || q.text || q,
              topic: generatedPart.topic || '',
              followUp: q.followUp || ''
            }))
          }

          // Update Part 2 cue card - handle both snake_case (from AI) and camelCase (existing data)
          if (modelPart.id === 2) {
            const cueCardData = generatedPart.cueCard || generatedPart.cue_card
            if (cueCardData) {
              // If cue_card has topic and prompts structure (from AI), format it as HTML
              if (cueCardData.topic) {
                const topic = cueCardData.topic
                const prompts = cueCardData.prompts || []
                modelPart.cueCard = `<p><strong>${topic}</strong></p><p>You should say:</p><ul>${prompts.map(p => `<li>${p}</li>`).join('')}</ul>`
              } else {
                // Otherwise use as-is (already formatted HTML)
                modelPart.cueCard = cueCardData
              }
              modelPart.bullets = generatedPart.bullets || cueCardData.prompts || []
            }
          }
        }
      }

      // Store examiner name
      if (!props.modelValue.settings) props.modelValue.settings = {}
      props.modelValue.settings.examinerName = data.examiner_name || examinerName.value || 'the examiner'

      // Generate script automatically
      await generateFullScript()

      emitUpdate()
      closeAIModal()

      // Switch to Parts tab to show results
      mainTab.value = 'parts'
    }
  } catch (error) {
    console.error('AI generation error:', error)
    alert('Lỗi khi tạo đề: ' + (error.response?.data?.message || error.message))
  } finally {
    generatingWithAI.value = false
  }
}

// Transition phrases for natural flow between questions
const transitionPhrases = [
  "Thank you.",
  "I see.",
  "That's interesting.",
  "Good. Let's move on to the next question.",
  "Alright, thank you.",
  "OK, thank you.",
  "Right, I see.",
  "Interesting. Now, let's talk about something else.",
  "Thank you for sharing that.",
  "I understand. Moving on,",
  "Good. Next question,"
]

function getRandomTransition() {
  return transitionPhrases[Math.floor(Math.random() * transitionPhrases.length)]
}

// Generate full examiner script
async function generateFullScript() {
  generatingScript.value = true
  try {
    const script = []
    // Get examiner name from settings or use default
    const examinerName = props.modelValue.settings?.examinerName || 'the examiner'
    const part1 = props.modelValue.parts.find(p => p.id === 1)
    if (part1) {
      script.push({ type: 'instruction', text: `Good morning. My name is ${examinerName}.`, duration: 3, partId: 1, waitForResponse: false })
      script.push({ type: 'question', text: "What should I call you?", duration: 3, partId: 1, waitForResponse: true, responseDuration: 6, questionNumber: null })
      script.push({ type: 'instruction', text: "Thank you. Now, in the first part, I'd like to ask you some questions about yourself.", duration: 6, partId: 1, waitForResponse: false })
      let qNum = 1
      for (const question of part1.questions) {
        // Add transition before question (except first question)
        if (qNum > 1) {
          script.push({ type: 'transition', text: getRandomTransition(), duration: 2, partId: 1, waitForResponse: false })
        }
        script.push({ type: 'question', text: question.content || 'Question ' + qNum, duration: Math.ceil((question.content?.length || 30) / 15) + 2, partId: 1, waitForResponse: true, responseDuration: question.responseDuration || 30, questionNumber: qNum })
        qNum++
      }
      script.push({ type: 'transition', text: "Thank you. Now I'm going to give you a topic and I'd like you to talk about it for one to two minutes.", duration: 6, partId: 1, waitForResponse: false })
    }
    const part2 = props.modelValue.parts.find(p => p.id === 2)
    if (part2) {
      // Extract topic text from cueCard HTML
      const topicText = part2.cueCard ? part2.cueCard.replace(/<[^>]*>/g, '').trim() : 'your topic'
      script.push({ type: 'instruction', text: `Here's your topic: ${topicText}. You have one minute to prepare, and you can make some notes if you wish.`, duration: 10, partId: 2, waitForResponse: false })
      script.push({ type: 'timer', text: "[Preparation Time - 1 minute]", duration: (part2.prepTime || 1) * 60, partId: 2, waitForResponse: false, isPreparation: true })
      script.push({ type: 'instruction', text: "All right? Remember, you have one to two minutes for this. Please start speaking now.", duration: 6, partId: 2, waitForResponse: false })
      script.push({ type: 'question', text: "[Candidate speaks about the topic]", duration: 5, partId: 2, waitForResponse: true, responseDuration: (part2.speakTime || 2) * 60, questionNumber: null, isLongTurn: true })
      let qNum = 1
      for (const question of part2.questions) {
        // Add transition before follow-up question
        if (qNum > 0) {
          script.push({ type: 'transition', text: getRandomTransition(), duration: 2, partId: 2, waitForResponse: false })
        }
        script.push({ type: 'question', text: question.content || 'Follow-up question ' + qNum, duration: Math.ceil((question.content?.length || 20) / 15) + 2, partId: 2, waitForResponse: true, responseDuration: question.responseDuration || 20, questionNumber: qNum })
        qNum++
      }
      script.push({ type: 'transition', text: "Thank you. Now I'd like to discuss one or two more general questions related to this topic.", duration: 5, partId: 2, waitForResponse: false })
    }
    const part3 = props.modelValue.parts.find(p => p.id === 3)
    if (part3) {
      let qNum = 1
      for (const question of part3.questions) {
        // Add transition before question (except first question)
        if (qNum > 1) {
          script.push({ type: 'transition', text: getRandomTransition(), duration: 2, partId: 3, waitForResponse: false })
        }
        script.push({ type: 'question', text: question.content || 'Discussion question ' + qNum, duration: Math.ceil((question.content?.length || 40) / 15) + 2, partId: 3, waitForResponse: true, responseDuration: question.responseDuration || 45, questionNumber: qNum, followUp: question.followUp })
        qNum++
      }
      script.push({ type: 'instruction', text: "Thank you. That is the end of the speaking test.", duration: 4, partId: 3, waitForResponse: false })
    }
    props.modelValue.script = script
    emitUpdate()
  } catch (error) {
    console.error('Error generating script:', error)
    alert('Lỗi khi tạo script: ' + error.message)
  } finally {
    generatingScript.value = false
  }
}

function getScriptItemBgClass(type) {
  return { instruction: 'bg-blue-500', question: 'bg-green-500', timer: 'bg-orange-500', transition: 'bg-gray-400' }[type] || 'bg-gray-400'
}

function getScriptItemBorderClass(type) {
  return { instruction: 'border-blue-200', question: 'border-green-200', timer: 'border-orange-200', transition: 'border-gray-200' }[type] || 'border-gray-200'
}

function getScriptItemLabelClass(type) {
  return { instruction: 'bg-blue-100 text-blue-700', question: 'bg-green-100 text-green-700', timer: 'bg-orange-100 text-orange-700', transition: 'bg-gray-100 text-gray-700' }[type] || 'bg-gray-100 text-gray-700'
}

function getScriptItemLabel(type) {
  return { instruction: 'Hướng dẫn', question: 'Câu hỏi', timer: 'Timer', transition: 'Chuyển tiếp' }[type] || type
}

function addScriptItem(type) {
  const defaults = {
    instruction: { type: 'instruction', text: '', duration: 5, waitForResponse: false },
    question: { type: 'question', text: '', duration: 5, waitForResponse: true, responseDuration: 30 },
    timer: { type: 'timer', text: '[Timer]', duration: 60, waitForResponse: false },
    transition: { type: 'transition', text: '', duration: 5, waitForResponse: false }
  }
  if (!props.modelValue.script) props.modelValue.script = []
  props.modelValue.script.push({ ...defaults[type] })
  emitUpdate()
}

function removeScriptItem(index) {
  props.modelValue.script.splice(index, 1)
  emitUpdate()
}

function getTotalDuration() {
  if (!props.modelValue.script) return 0
  return props.modelValue.script.reduce((total, item) => {
    let duration = item.duration || 0
    if (item.waitForResponse && item.responseDuration) duration += item.responseDuration
    return total + duration
  }, 0)
}

function formatDuration(seconds) {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return mins + ':' + secs.toString().padStart(2, '0')
}

async function playTTS(item, index) {
  if ('speechSynthesis' in window) {
    playingTTS.value = index
    const utterance = new SpeechSynthesisUtterance(item.text)
    utterance.lang = 'en-US'
    utterance.rate = 0.9
    utterance.onend = () => { playingTTS.value = null }
    speechSynthesis.speak(utterance)
  } else {
    alert('Browser không hỗ trợ Text-to-Speech')
  }
}

function exportScript(format) {
  const script = props.modelValue.script
  let content = ''
  let filename = 'ielts-speaking-script.' + format
  if (format === 'json') {
    content = JSON.stringify(script, null, 2)
  } else {
    content = 'IELTS SPEAKING TEST SCRIPT\n' + '='.repeat(50) + '\n\n'
    let currentPart = null
    for (const item of script) {
      if (item.partId !== currentPart) {
        currentPart = item.partId
        content += '\n--- PART ' + currentPart + ' ---\n\n'
      }
      const typeLabel = getScriptItemLabel(item.type).toUpperCase()
      content += '[' + typeLabel + '] (' + item.duration + 's)'
      if (item.waitForResponse) content += ' → Record: ' + item.responseDuration + 's'
      content += '\n' + item.text + '\n\n'
    }
    content += '\nTotal Duration: ' + formatDuration(getTotalDuration()) + '\n'
  }
  const blob = new Blob([content], { type: format === 'json' ? 'application/json' : 'text/plain' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

// Initialize question numbers
updateQuestionNumbers()
</script>
