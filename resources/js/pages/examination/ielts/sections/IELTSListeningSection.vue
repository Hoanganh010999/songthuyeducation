<template>
  <div class="ielts-listening-section">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold">IELTS Listening</h3>
        <p class="text-sm text-gray-600">4 phần, khoảng 40 câu hỏi, thời gian nghe: 30 phút</p>
      </div>
      <button
        @click="showAIModal = true"
        class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 flex items-center shadow-md"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        Tạo đề bằng AI
      </button>
    </div>

    <!-- Parts Tabs -->
    <div class="flex space-x-2 mb-4">
      <button
        v-for="part in modelValue.parts"
        :key="part.id"
        @click="activePart = part.id"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        :class="activePart === part.id
          ? 'bg-blue-100 text-blue-700'
          : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
      >
        {{ part.title }}
        <span v-if="getPartQuestionCount(part) > 0" class="ml-1 text-xs">({{ getPartQuestionCount(part) }})</span>
      </button>
    </div>

    <!-- Active Part Content -->
    <div v-for="part in modelValue.parts" :key="part.id" v-show="activePart === part.id">
      <div class="border rounded-lg p-4 space-y-4">
        <!-- Audio Upload -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
            </svg>
            File audio {{ part.title }}
          </label>

          <div v-if="part.audio" class="flex items-center space-x-3 bg-white p-3 rounded border">
            <audio :src="part.audio.url" controls class="flex-1 h-10"></audio>
            <button @click="removeAudio(part)" class="text-red-500 hover:text-red-700">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>

          <div v-else class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
            <input
              type="file"
              accept="audio/*"
              @change="handleAudioUpload($event, part)"
              :id="`audio-${part.id}`"
              class="hidden"
            />
            <label :for="`audio-${part.id}`" class="cursor-pointer">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <p class="mt-2 text-sm text-gray-600">
                <span class="text-blue-600 font-medium">Chọn file</span> hoặc kéo thả vào đây
              </p>
              <p class="text-xs text-gray-500 mt-1">MP3, WAV tối đa 50MB</p>
            </label>
            <div v-if="uploading[part.id]" class="mt-3">
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all" :style="{ width: uploadProgress[part.id] + '%' }"></div>
              </div>
              <p class="text-xs text-gray-500 mt-1">Đang upload... {{ uploadProgress[part.id] }}%</p>
            </div>
          </div>
        </div>

        <!-- Transcript Section -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg overflow-hidden">
          <div class="bg-yellow-100 px-4 py-2 flex items-center justify-between">
            <label class="text-sm font-medium text-yellow-800">
              <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Transcript (Bản ghi âm)
            </label>
            <button
              @click="toggleTranscript(part)"
              class="text-xs text-yellow-700 hover:text-yellow-900"
            >
              {{ showTranscript[part.id] ? 'Thu gọn' : 'Mở rộng' }}
            </button>
          </div>
          <div v-show="showTranscript[part.id]" class="border-t border-yellow-200">
            <!-- Transcript Toolbar -->
            <div class="bg-yellow-100/50 border-b border-yellow-200 px-2 py-1 flex space-x-1">
              <button @click="formatTranscript(part, 'bold')" class="p-1.5 hover:bg-yellow-200 rounded" title="Bold">
                <span class="font-bold text-sm">B</span>
              </button>
              <button @click="formatTranscript(part, 'italic')" class="p-1.5 hover:bg-yellow-200 rounded" title="Italic">
                <span class="italic text-sm">I</span>
              </button>
              <button @click="formatTranscript(part, 'underline')" class="p-1.5 hover:bg-yellow-200 rounded" title="Underline">
                <span class="underline text-sm">U</span>
              </button>
              <div class="border-l border-yellow-300 mx-1"></div>
              <button @click="insertSpeaker(part)" class="p-1.5 hover:bg-yellow-200 rounded text-xs font-medium">
                👤 Speaker
              </button>
            </div>
            <!-- Transcript Content -->
            <div
              :ref="el => transcriptRefs[part.id] = el"
              contenteditable="true"
              @input="e => updateTranscript(part, e)"
              @blur="e => updateTranscript(part, e)"
              class="p-4 min-h-[150px] prose prose-sm max-w-none focus:outline-none bg-white"
              :innerHTML="part.transcript || ''"
              placeholder="Nhập transcript của audio..."
            ></div>
          </div>
        </div>

        <!-- Question Groups Section (New format with instructions) -->
        <div v-if="part.questionGroups && part.questionGroups.length > 0">
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-medium">
              <svg class="w-5 h-5 inline mr-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              Nhóm câu hỏi ({{ part.questionGroups.length }} nhóm)
            </h4>
            <button
              @click="addQuestionGroup(part)"
              class="px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100"
            >
              + Thêm nhóm câu hỏi
            </button>
          </div>

          <div class="space-y-6">
            <div
              v-for="(group, gIndex) in part.questionGroups"
              :key="group.id"
              class="border-2 border-blue-200 rounded-xl overflow-hidden bg-white"
            >
              <!-- Group Header -->
              <div class="bg-blue-50 px-4 py-3 border-b border-blue-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <span class="px-2.5 py-1 bg-blue-600 text-white text-xs font-semibold rounded">
                      Câu {{ group.startNumber }}-{{ group.endNumber }}
                    </span>
                    <select
                      v-model="group.type"
                      @change="onGroupTypeChange(group, part)"
                      class="text-sm border border-blue-300 rounded px-2 py-1 bg-white"
                    >
                      <option v-for="qt in listeningQuestionTypes" :key="qt.value" :value="qt.value">
                        {{ qt.label }}
                      </option>
                    </select>
                    <span v-if="group.type.includes('completion')" class="text-sm text-gray-600">
                      Word limit:
                      <input
                        type="number"
                        v-model.number="group.wordLimit"
                        class="w-12 px-1 py-0.5 border rounded text-center"
                        min="1"
                        max="5"
                      />
                    </span>
                  </div>
                  <button
                    @click="removeQuestionGroup(part, gIndex)"
                    class="text-red-500 hover:text-red-700 p-1"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Group Instruction (Editable with Rich Text) -->
              <div class="border-b">
                <div class="bg-gray-50 px-4 py-2 flex items-center justify-between">
                  <label class="text-sm font-medium text-gray-700">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Hướng dẫn làm bài (Instruction)
                  </label>
                  <button
                    @click="regenerateInstruction(group, part)"
                    class="text-xs text-blue-600 hover:text-blue-800"
                  >
                    ↻ Tạo lại từ template
                  </button>
                </div>
                <!-- Instruction Editor Toolbar -->
                <div class="bg-gray-100 border-b px-2 py-1 flex space-x-1">
                  <button @click="formatGroupInstruction(group, 'bold')" class="p-1.5 hover:bg-gray-200 rounded" title="Bold">
                    <span class="font-bold text-sm">B</span>
                  </button>
                  <button @click="formatGroupInstruction(group, 'italic')" class="p-1.5 hover:bg-gray-200 rounded" title="Italic">
                    <span class="italic text-sm">I</span>
                  </button>
                  <button @click="formatGroupInstruction(group, 'underline')" class="p-1.5 hover:bg-gray-200 rounded" title="Underline">
                    <span class="underline text-sm">U</span>
                  </button>
                </div>
                <!-- Instruction Content -->
                <div
                  :ref="el => groupInstructionRefs[group.id] = el"
                  contenteditable="true"
                  @input="e => updateGroupInstruction(group, e)"
                  @blur="e => updateGroupInstruction(group, e)"
                  class="p-4 min-h-[80px] prose prose-sm max-w-none focus:outline-none focus:ring-2 focus:ring-blue-300 bg-blue-50/30"
                  v-html="getGroupInstructionHtml(group)"
                ></div>
              </div>

              <!-- Labeling Type: Image Upload + Features -->
              <div v-if="group.type === 'labeling'" class="border-b px-4 py-4 bg-purple-50 space-y-3">
                <!-- Diagram Description -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Diagram/Map Description
                  </label>
                  <textarea
                    v-model="group.diagramDescription"
                    @input="emitUpdate"
                    rows="2"
                    class="w-full px-3 py-2 border rounded text-sm"
                    placeholder="Describe the diagram/map (e.g., 'A map showing the cycle route with labeled positions A-I')"
                  ></textarea>
                </div>

                <!-- Image Upload -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Upload Diagram/Map Image
                  </label>
                  <div class="flex items-center space-x-3">
                    <input
                      type="file"
                      accept="image/*"
                      @change="e => uploadDiagramImage(e, group)"
                      class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100"
                    />
                  </div>
                  <!-- Image Preview -->
                  <div v-if="group.diagramImage" class="mt-3">
                    <img :src="group.diagramImage" alt="Diagram" class="max-w-full h-auto border rounded shadow-sm" />
                    <button
                      @click="group.diagramImage = null; emitUpdate()"
                      class="mt-2 text-xs text-red-600 hover:text-red-800"
                    >
                      ✕ Remove image
                    </button>
                  </div>
                </div>

                <!-- Features (Positions A-I) -->
                <div>
                  <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-gray-700">
                      Position Labels (e.g., A-I on map)
                    </label>
                    <button
                      @click="addLabelingFeature(group)"
                      class="text-xs text-purple-600 hover:text-purple-800"
                    >
                      + Add Position
                    </button>
                  </div>
                  <div class="space-y-2 max-h-60 overflow-y-auto">
                    <div v-for="(feature, fIdx) in group.features || []" :key="fIdx" class="flex items-center space-x-2">
                      <span class="w-8 text-center font-bold text-purple-700 bg-white px-2 py-1 rounded border">{{ feature.label }}</span>
                      <input
                        v-model="feature.description"
                        @input="emitUpdate"
                        type="text"
                        class="flex-1 px-2 py-1 border rounded text-sm"
                        placeholder="Position description (e.g., 'Near entrance gate')"
                      />
                      <button
                        @click="removeLabelingFeature(group, fIdx)"
                        class="text-red-400 hover:text-red-600"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Matching Type: Features -->
              <div v-if="group.type === 'matching'" class="border-b px-4 py-4 bg-green-50 space-y-3">
                <div class="flex items-center justify-between mb-2">
                  <label class="text-sm font-medium text-gray-700">
                    Features List (A, B, C, ...)
                  </label>
                  <button
                    @click="addMatchingFeature(group)"
                    class="text-xs text-green-600 hover:text-green-800"
                  >
                    + Add Feature
                  </button>
                </div>
                <div class="space-y-2">
                  <div v-for="(feature, fIdx) in group.features || []" :key="fIdx" class="flex items-center space-x-2">
                    <span class="w-8 text-center font-bold text-green-700 bg-white px-2 py-1 rounded border">{{ feature.label }}</span>
                    <input
                      v-model="feature.text"
                      @input="emitUpdate"
                      type="text"
                      class="flex-1 px-2 py-1 border rounded text-sm"
                      placeholder="Feature text"
                    />
                    <button
                      @click="removeMatchingFeature(group, fIdx)"
                      class="text-red-400 hover:text-red-600"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Questions in Group -->
              <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                  <label class="text-sm font-medium text-gray-700">Câu hỏi trong nhóm</label>
                  <button
                    @click="addQuestionToGroup(group, part)"
                    class="text-xs text-green-600 hover:text-green-800"
                  >
                    + Thêm câu
                  </button>
                </div>
                <div class="space-y-3">
                  <div
                    v-for="(question, qIndex) in group.questions"
                    :key="question.id"
                    class="border rounded-lg p-3 bg-gray-50"
                  >
                    <div class="flex items-start space-x-3">
                      <span class="font-bold text-gray-600 pt-1">{{ question.number }}.</span>
                      <div class="flex-1">
                        <!-- Question Content -->
                        <input
                          v-model="question.content"
                          type="text"
                          class="w-full px-2 py-1 border rounded text-sm mb-2"
                          placeholder="Nội dung câu hỏi..."
                        />

                        <!-- Multiple Choice Options -->
                        <div v-if="group.type === 'multiple_choice'" class="space-y-1">
                          <div v-for="(opt, oIdx) in (question.options || [])" :key="oIdx" class="flex items-center space-x-2">
                            <input
                              type="radio"
                              :name="`q-${question.id}-mc`"
                              :value="opt.label"
                              v-model="question.answer"
                              class="text-blue-600"
                            />
                            <span class="font-bold text-sm">{{ opt.label }}</span>
                            <input
                              v-model="opt.content"
                              type="text"
                              class="flex-1 px-2 py-1 border rounded text-sm"
                              placeholder="Option text"
                            />
                          </div>
                        </div>

                        <!-- Answer for other types -->
                        <div v-else class="flex items-center space-x-2">
                          <span class="text-sm text-gray-500">Đáp án:</span>
                          <input
                            v-model="question.answer"
                            type="text"
                            class="flex-1 px-2 py-1 border rounded text-sm"
                            placeholder="Answer"
                          />
                        </div>
                      </div>
                      <button
                        @click="removeQuestionFromGroup(group, qIndex, part)"
                        class="text-red-400 hover:text-red-600"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Legacy Questions Section (for backward compatibility) -->
        <div v-else>
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-medium">Câu hỏi {{ part.title }}</h4>
            <button
              @click="addQuestion(part)"
              class="px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100"
            >
              + Thêm câu hỏi
            </button>
          </div>

          <div v-if="!part.questions || part.questions.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
            Chưa có câu hỏi nào. Sử dụng "Tạo đề bằng AI" hoặc thêm câu hỏi thủ công.
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
                    <div class="flex items-center space-x-3 mb-3">
                      <span class="font-medium text-gray-700">Câu {{ question.number }}</span>
                      <select v-model="question.type" class="text-sm border rounded px-2 py-1">
                        <option value="multiple_choice">Trắc nghiệm</option>
                        <option value="fill_blanks">Điền vào chỗ trống</option>
                        <option value="matching">Nối đáp án</option>
                        <option value="short_answer">Trả lời ngắn</option>
                        <option value="sentence_completion">Hoàn thành câu</option>
                        <option value="note_completion">Hoàn thành ghi chú</option>
                        <option value="table_completion">Hoàn thành bảng</option>
                        <option value="labeling">Gán nhãn</option>
                      </select>
                    </div>

                    <!-- Question Content -->
                    <div class="mb-3">
                      <label class="block text-sm text-gray-600 mb-1">Nội dung câu hỏi</label>
                      <textarea
                        v-model="question.content"
                        rows="2"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                        placeholder="Nhập nội dung câu hỏi..."
                      ></textarea>
                    </div>

                    <!-- Options for Multiple Choice -->
                    <div v-if="question.type === 'multiple_choice'" class="space-y-2">
                      <label class="block text-sm text-gray-600">Đáp án</label>
                      <div v-for="(option, optIdx) in question.options" :key="optIdx" class="flex items-center space-x-2">
                        <input
                          type="radio"
                          :name="`q${question.id}-correct`"
                          :checked="question.correctAnswer === option.label"
                          @change="question.correctAnswer = option.label"
                          class="text-blue-600"
                        />
                        <span class="w-6 text-center font-medium">{{ option.label }}.</span>
                        <input
                          v-model="option.content"
                          type="text"
                          class="flex-1 px-2 py-1 border rounded text-sm"
                          placeholder="Nội dung đáp án"
                        />
                        <button v-if="question.options.length > 2" @click="removeOption(question, optIdx)" class="text-red-500">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </div>
                      <button @click="addOption(question)" class="text-sm text-blue-600 hover:text-blue-700">
                        + Thêm đáp án
                      </button>
                    </div>

                    <!-- Correct Answer for other types -->
                    <div v-else class="space-y-2">
                      <label class="block text-sm text-gray-600">Đáp án đúng</label>
                      <input
                        v-model="question.correctAnswer"
                        type="text"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                        placeholder="Nhập đáp án đúng"
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
      </div>
    </div>

    <!-- AI Generate Modal -->
    <div v-if="showAIModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b bg-gradient-to-r from-purple-600 to-indigo-600">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <div>
                <h3 class="text-xl font-bold text-white">Tạo đề IELTS Listening bằng AI</h3>
                <p class="text-purple-100 text-sm">Chọn phương thức tạo đề phù hợp</p>
              </div>
            </div>
            <button @click="showAIModal = false" class="text-white/70 hover:text-white">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6">
          <!-- Method Selection -->
          <div v-if="!aiGenerateMethod" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Option 1: Generate from scratch -->
            <div
              @click="selectAIMethod('generate')"
              class="border-2 border-gray-200 rounded-xl p-6 cursor-pointer hover:border-purple-500 hover:bg-purple-50 transition-all group"
            >
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-purple-200 transition-all">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <h4 class="font-bold text-lg text-gray-900 mb-2">Tạo 100% bằng AI</h4>
              <p class="text-sm text-gray-600">AI sẽ tự động tạo transcript đầy đủ và câu hỏi theo yêu cầu của bạn</p>
              <ul class="mt-3 text-xs text-gray-500 space-y-1">
                <li>✓ Transcript hoàn chỉnh</li>
                <li>✓ Đa dạng loại câu hỏi</li>
                <li>✓ Phù hợp mọi cấp độ</li>
              </ul>
            </div>

            <!-- Option 2: Upload existing questions -->
            <div
              @click="selectAIMethod('upload')"
              class="border-2 border-gray-200 rounded-xl p-6 cursor-pointer hover:border-indigo-500 hover:bg-indigo-50 transition-all group"
            >
              <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-indigo-200 transition-all">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
              </div>
              <h4 class="font-bold text-lg text-gray-900 mb-2">Upload Câu Hỏi Có Sẵn</h4>
              <p class="text-sm text-gray-600">Upload file chứa câu hỏi và AI sẽ chuyển đổi sang định dạng hệ thống</p>
              <ul class="mt-3 text-xs text-gray-500 space-y-1">
                <li>✓ Hỗ trợ .pdf, .docx, .txt</li>
                <li>✓ AI convert sang JSON format</li>
                <li>✓ Nhanh chóng, chính xác</li>
              </ul>
            </div>
          </div>

          <!-- Generate from Scratch Form -->
          <div v-if="aiGenerateMethod === 'generate'" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Chủ đề</label>
              <select v-model="aiGenerateOptions.topic" class="w-full px-3 py-2 border rounded-lg">
                <option value="">Chọn chủ đề</option>
                <option value="accommodation">Accommodation (Nhà ở)</option>
                <option value="education">Education (Giáo dục)</option>
                <option value="travel">Travel & Tourism</option>
                <option value="health">Health & Medicine</option>
                <option value="work">Work & Career</option>
                <option value="shopping">Shopping & Services</option>
                <option value="social">Social Activities</option>
                <option value="environment">Environment</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Độ khó (Band Score)</label>
              <select v-model="aiGenerateOptions.difficulty" class="w-full px-3 py-2 border rounded-lg">
                <option value="5-6">Band 5-6 (Cơ bản)</option>
                <option value="6-7">Band 6-7 (Trung bình)</option>
                <option value="7-8">Band 7-8 (Nâng cao)</option>
                <option value="8-9">Band 8-9 (Chuyên gia)</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Số câu hỏi mỗi part</label>
              <select v-model="aiGenerateOptions.questionsPerPart" class="w-full px-3 py-2 border rounded-lg">
                <option :value="8">8 câu</option>
                <option :value="10">10 câu (chuẩn IELTS)</option>
                <option :value="12">12 câu</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Part cần tạo</label>
              <select v-model="aiGenerateOptions.targetPart" class="w-full px-3 py-2 border rounded-lg">
                <option value="current">Part hiện tại ({{ activePart }})</option>
                <option value="all">Tất cả 4 parts</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Loại câu hỏi</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
              <label v-for="qType in listeningQuestionTypes" :key="qType.value" class="flex items-center space-x-2 p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" v-model="aiGenerateOptions.questionTypes" :value="qType.value" class="text-purple-600" />
                <span class="text-sm">{{ qType.label }}</span>
              </label>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Yêu cầu thêm (tùy chọn)</label>
            <textarea
              v-model="aiGenerateOptions.customPrompt"
              rows="3"
              class="w-full px-3 py-2 border rounded-lg text-sm"
              placeholder="VD: Cuộc hội thoại về đặt phòng khách sạn, có 2 người nói..."
            ></textarea>
          </div>

          <div class="flex justify-end space-x-3">
            <button @click="showAIModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Hủy
            </button>
            <button
              @click="generateWithAI"
              :disabled="aiGenerating"
              class="px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 flex items-center disabled:opacity-50"
            >
              <svg v-if="aiGenerating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ aiGenerating ? 'Đang tạo...' : 'Tạo đề' }}
            </button>
          </div>

          <!-- AI Generation Progress -->
          <div v-if="aiGenerating" class="mt-6 p-4 bg-purple-50 rounded-lg">
            <div class="flex items-center space-x-3">
              <div class="animate-pulse">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-purple-800">{{ aiProgressMessage }}</p>
                <p class="text-sm text-purple-600">Vui lòng đợi trong giây lát...</p>
              </div>
            </div>
          </div>
        </div>

          <!-- Upload Questions Form -->
          <div v-if="aiGenerateMethod === 'upload'" class="space-y-6">
            <button
              @click="aiGenerateMethod = null"
              class="flex items-center text-gray-600 hover:text-gray-800 mb-4"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Quay lại
            </button>

            <!-- File Upload Area -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Upload File Câu Hỏi</label>
              <div
                v-if="!uploadedFile"
                @drop.prevent="handleFileDrop"
                @dragover.prevent="isDragging = true"
                @dragleave="isDragging = false"
                :class="['border-2 border-dashed rounded-xl p-8 text-center transition-all', isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300']"
              >
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-gray-600 mb-2">Kéo thả file vào đây hoặc</p>
                <label class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg cursor-pointer hover:bg-indigo-700">
                  Chọn file
                  <input type="file" class="hidden" accept=".doc,.docx,.pdf,.txt" @change="handleFileSelect" />
                </label>
                <p class="text-xs text-gray-500 mt-2">Hỗ trợ: .pdf, .docx, .doc, .txt (tối đa 10MB)</p>
      </div>

              <div v-else class="flex items-center justify-center space-x-4">
                <div class="flex items-center space-x-3 bg-indigo-50 px-4 py-3 rounded-lg flex-1">
                  <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <div class="flex-1">
                    <p class="font-medium text-gray-900">{{ uploadedFile.name }}</p>
                    <p class="text-sm text-gray-500">{{ formatFileSize(uploadedFile.size) }}</p>
                  </div>
                  <button @click="uploadedFile = null" class="text-red-500 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Info Note -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 text-sm text-blue-800">
                  <p class="font-medium mb-1">AI sẽ tự động:</p>
                  <ul class="list-disc list-inside space-y-1 text-blue-700">
                    <li>Nhận diện loại câu hỏi (trắc nghiệm, điền chỗ trống, nối câu...)</li>
                    <li>Phát hiện transcript (nếu có trong file)</li>
                    <li>Convert sang định dạng JSON chuẩn của hệ thống</li>
                    <li>Tạo instructions theo format IELTS</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="flex justify-end space-x-3">
              <button @click="showAIModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                Hủy
              </button>
              <button
                @click="generateFromFile"
                :disabled="!uploadedFile || aiGenerating"
                class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg v-if="aiGenerating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ aiGenerating ? 'Đang xử lý...' : 'Tạo câu hỏi' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import draggable from 'vuedraggable'
import api from '@/api'
import Swal from 'sweetalert2'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:modelValue'])

const activePart = ref(1)
const uploading = ref({})
const uploadProgress = ref({})
const showTranscript = ref({})

// Refs for contenteditable editors
const transcriptRefs = ref({})
const groupInstructionRefs = ref({})

let questionIdCounter = 0

// AI Generation State
const showAIModal = ref(false)
const aiGenerateMethod = ref(null) // 'generate' or 'upload'
const aiGenerating = ref(false)
const aiProgressMessage = ref('')
const isDragging = ref(false)
const uploadedFile = ref(null)

const aiGenerateOptions = reactive({
  topic: '',
  difficulty: '6-7',
  questionsPerPart: 10,
  targetPart: 'current',
  questionTypes: ['multiple_choice', 'fill_blanks', 'matching'],
  customPrompt: ''
})

const listeningQuestionTypes = [
  { value: 'multiple_choice', label: 'Trắc nghiệm' },
  { value: 'fill_blanks', label: 'Điền vào chỗ trống' },
  { value: 'form_completion', label: 'Hoàn thành biểu mẫu' },
  { value: 'matching', label: 'Nối đáp án' },
  { value: 'sentence_completion', label: 'Hoàn thành câu' },
  { value: 'note_completion', label: 'Hoàn thành ghi chú' },
  { value: 'table_completion', label: 'Hoàn thành bảng' },
  { value: 'labeling', label: 'Gán nhãn' },
  { value: 'short_answer', label: 'Trả lời ngắn' }
]

// Listening Question Group Templates
const questionGroupTemplates = {
  multiple_choice: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Choose the correct letter, <strong>A</strong>, <strong>B</strong> or <strong>C</strong>.</p>`
  },
  fill_blanks: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the notes below.</p>
<p>Write <strong>NO MORE THAN {{wordLimit}} WORDS AND/OR A NUMBER</strong> for each answer.</p>`
  },
  form_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the form below.</p>
<p>Write <strong>NO MORE THAN {{wordLimit}} WORDS AND/OR A NUMBER</strong> for each answer.</p>`
  },
  sentence_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the sentences below.</p>
<p>Write <strong>NO MORE THAN {{wordLimit}} WORDS</strong> for each answer.</p>`
  },
  note_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the notes below.</p>
<p>Write <strong>NO MORE THAN {{wordLimit}} WORDS AND/OR A NUMBER</strong> for each answer.</p>`
  },
  table_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the table below.</p>
<p>Write <strong>NO MORE THAN {{wordLimit}} WORDS AND/OR A NUMBER</strong> for each answer.</p>`
  },
  matching: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>What does the speaker say about each item?</p>
<p>Choose <strong>FIVE</strong> answers from the box and write the correct letter, <strong>A-G</strong>, next to questions {{startNum}}-{{endNum}}.</p>`
  },
  labeling: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Label the diagram below.</p>
<p>Write <strong>NO MORE THAN {{wordLimit}} WORDS</strong> for each answer.</p>`
  },
  short_answer: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Answer the questions below.</p>
<p>Write <strong>NO MORE THAN {{wordLimit}} WORDS AND/OR A NUMBER</strong> for each answer.</p>`
  }
}

function getNextQuestionId() {
  return ++questionIdCounter
}

function getPartQuestionCount(part) {
  if (part.questionGroups?.length) {
    let count = 0
    for (const g of part.questionGroups) {
      count += (g.questions || []).length
    }
    return count
  }
  return (part.questions || []).length
}

function toggleTranscript(part) {
  showTranscript.value[part.id] = !showTranscript.value[part.id]
}

function updateTranscript(part, event) {
  part.transcript = event.target.innerHTML
  emitUpdate()
}

function formatTranscript(part, command) {
  const editor = transcriptRefs.value[part.id]
  if (editor) {
    editor.focus()
    document.execCommand(command, false, null)
    part.transcript = editor.innerHTML
    emitUpdate()
  }
}

function insertSpeaker(part) {
  const editor = transcriptRefs.value[part.id]
  if (editor) {
    editor.focus()
    document.execCommand('insertHTML', false, '<p><strong>Speaker:</strong> </p>')
    part.transcript = editor.innerHTML
    emitUpdate()
  }
}

// Question Group Functions
function getGroupInstructionHtml(group) {
  if (group.instruction) {
    return group.instruction
  }
  return generateGroupInstruction(group)
}

function generateGroupInstruction(group) {
  const template = questionGroupTemplates[group.type]
  if (!template) {
    return `<p><strong>Questions ${group.startNumber}-${group.endNumber}</strong></p>`
  }

  return template.instruction
    .replace(/\{\{startNum\}\}/g, group.startNumber)
    .replace(/\{\{endNum\}\}/g, group.endNumber)
    .replace(/\{\{wordLimit\}\}/g, group.wordLimit || 2)
}

function updateGroupInstruction(group, event) {
  group.instruction = event.target.innerHTML
  emitUpdate()
}

function formatGroupInstruction(group, command) {
  const editor = groupInstructionRefs.value[group.id]
  if (editor) {
    editor.focus()
    document.execCommand(command, false, null)
    group.instruction = editor.innerHTML
    emitUpdate()
  }
}

function regenerateInstruction(group, part) {
  group.instruction = generateGroupInstruction(group)
  emitUpdate()
}

function onGroupTypeChange(group, part) {
  regenerateInstruction(group, part)

  // Initialize options for multiple choice
  if (group.type === 'multiple_choice') {
    for (const q of group.questions) {
      if (!q.options || q.options.length === 0) {
        q.options = [
          { label: 'A', content: '' },
          { label: 'B', content: '' },
          { label: 'C', content: '' },
        ]
      }
    }
  }

  emitUpdate()
}

function addQuestionGroup(part) {
  if (!part.questionGroups) {
    part.questionGroups = []
  }

  let startNum = 1
  for (const g of part.questionGroups) {
    startNum = Math.max(startNum, g.endNumber + 1)
  }

  const newGroup = {
    id: getNextQuestionId(),
    type: 'multiple_choice',
    startNumber: startNum,
    endNumber: startNum + 4,
    wordLimit: 2,
    instruction: null,
    questions: Array.from({ length: 5 }, (_, i) => ({
      id: getNextQuestionId(),
      number: startNum + i,
      content: '',
      options: [
        { label: 'A', content: '' },
        { label: 'B', content: '' },
        { label: 'C', content: '' },
      ],
      answer: ''
    }))
  }

  part.questionGroups.push(newGroup)
  updateAllQuestionNumbers()
  emitUpdate()
}

function removeQuestionGroup(part, index) {
  part.questionGroups.splice(index, 1)
  updateAllQuestionNumbers()
  emitUpdate()
}

function addQuestionToGroup(group, part) {
  const nextNum = group.endNumber + 1
  const newQuestion = {
    id: getNextQuestionId(),
    number: nextNum,
    content: '',
    options: group.type === 'multiple_choice' ? [
      { label: 'A', content: '' },
      { label: 'B', content: '' },
      { label: 'C', content: '' },
    ] : [],
    answer: ''
  }

  group.questions.push(newQuestion)
  group.endNumber = nextNum
  regenerateInstruction(group, part)
  updateAllQuestionNumbers()
  emitUpdate()
}

function removeQuestionFromGroup(group, index, part) {
  group.questions.splice(index, 1)

  if (group.questions.length > 0) {
    group.startNumber = group.questions[0].number
    group.endNumber = group.questions[group.questions.length - 1].number
  }

  updateAllQuestionNumbers()
  emitUpdate()
}

function updateAllQuestionNumbers() {
  for (const part of props.modelValue.parts) {
    if (part.questionGroups && part.questionGroups.length > 0) {
      let num = 1
      // Add offset based on previous parts
      for (const p of props.modelValue.parts) {
        if (p.id === part.id) break
        if (p.questionGroups?.length) {
          for (const g of p.questionGroups) {
            num += (g.questions || []).length
          }
        } else {
          num += (p.questions || []).length
        }
      }

      for (const group of part.questionGroups) {
        group.startNumber = num
        for (const q of group.questions) {
          q.number = num++
        }
        group.endNumber = num - 1
      }
    }
  }
}

// Labeling type functions
async function uploadDiagramImage(event, group) {
  const file = event.target.files[0]
  if (!file) return

  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', 'diagrams')

    const response = await api.post('/examination/upload/image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    // Backend returns: { success: true, url: "...", path: "...", filename: "..." }
    if (response.data.success && response.data.url) {
      group.diagramImage = response.data.url
      emitUpdate()
      
      Swal.fire({
        icon: 'success',
        title: 'Uploaded!',
        text: 'Diagram image uploaded successfully',
        timer: 1500
      })
    } else {
      throw new Error('Upload response missing URL')
    }
  } catch (error) {
    console.error('Failed to upload diagram image:', error)
    Swal.fire('Error', `Failed to upload image: ${error.message || 'Unknown error'}`, 'error')
  }
}

function addLabelingFeature(group) {
  if (!group.features) group.features = []
  const labels = 'ABCDEFGHIJ'
  const nextLabel = labels[group.features.length] || String.fromCharCode(65 + group.features.length)
  group.features.push({ label: nextLabel, description: '' })
  emitUpdate()
}

function removeLabelingFeature(group, index) {
  group.features.splice(index, 1)
  // Re-label remaining features
  const labels = 'ABCDEFGHIJ'
  group.features.forEach((f, i) => {
    f.label = labels[i] || String.fromCharCode(65 + i)
  })
  emitUpdate()
}

function addMatchingFeature(group) {
  if (!group.features) group.features = []
  const labels = 'ABCDEFGHIJ'
  const nextLabel = labels[group.features.length] || String.fromCharCode(65 + group.features.length)
  group.features.push({ label: nextLabel, text: '' })
  emitUpdate()
}

function removeMatchingFeature(group, index) {
  group.features.splice(index, 1)
  // Re-label remaining features
  const labels = 'ABCDEFGHIJ'
  group.features.forEach((f, i) => {
    f.label = labels[i] || String.fromCharCode(65 + i)
  })
  emitUpdate()
}

// Legacy question functions
function getTotalQuestionsBefore(partId) {
  let total = 0
  for (const part of props.modelValue.parts) {
    if (part.id === partId) break
    total += (part.questions || []).length
  }
  return total
}

function updateQuestionNumbers() {
  let num = 1
  for (const part of props.modelValue.parts) {
    for (const question of (part.questions || [])) {
      question.number = num++
    }
  }
  emitUpdate()
}

function addQuestion(part) {
  if (!part.questions) part.questions = []
  const totalBefore = getTotalQuestionsBefore(part.id) + part.questions.length + 1
  part.questions.push({
    id: getNextQuestionId(),
    number: totalBefore,
    type: 'multiple_choice',
    content: '',
    options: [
      { label: 'A', content: '' },
      { label: 'B', content: '' },
      { label: 'C', content: '' },
      { label: 'D', content: '' },
    ],
    correctAnswer: null
  })
  updateQuestionNumbers()
}

function removeQuestion(part, index) {
  part.questions.splice(index, 1)
  updateQuestionNumbers()
}

function addOption(question) {
  const labels = 'ABCDEFGHIJ'
  const nextLabel = labels[question.options.length] || question.options.length + 1
  question.options.push({ label: nextLabel, content: '' })
  emitUpdate()
}

function removeOption(question, index) {
  question.options.splice(index, 1)
  const labels = 'ABCDEFGHIJ'
  question.options.forEach((opt, i) => {
    opt.label = labels[i] || i + 1
  })
  emitUpdate()
}

// Audio upload
async function handleAudioUpload(event, part) {
  const file = event.target.files[0]
  if (!file) return

  uploading.value[part.id] = true
  uploadProgress.value[part.id] = 0

  try {
    const formData = new FormData()
    formData.append('file', file)

    const response = await api.post('/examination/upload/audio', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        uploadProgress.value[part.id] = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      }
    })

    part.audio = {
      name: file.name,
      url: response.data.data.url,
      path: response.data.data.path,
      size: response.data.data.size
    }
    emitUpdate()
    
    Swal.fire({
      icon: 'success',
      title: 'Thành công',
      text: 'Upload audio thành công!',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (error) {
    console.error('Error uploading audio:', error)
    Swal.fire('Lỗi', error.response?.data?.message || 'Có lỗi khi upload file audio', 'error')
  } finally {
    uploading.value[part.id] = false
    uploadProgress.value[part.id] = 0
    event.target.value = ''
  }
}

function removeAudio(part) {
  part.audio = null
  emitUpdate()
}

// AI Generation - check if API key is configured in database
const hasAISettings = ref(false)

async function checkAISettings() {
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
    console.error('Error checking AI settings:', error)
    hasAISettings.value = false
  }
}

// Load settings on mount
onMounted(() => {
  checkAISettings()
})

async function generateWithAI() {
  if (!hasAISettings.value) {
    Swal.fire('Cảnh báo', 'Vui lòng cấu hình API key tại trang Ngân hàng đề IELTS trước khi sử dụng tính năng này.', 'warning')
    return
  }

  if (!aiGenerateOptions.topic) {
    Swal.fire('Cảnh báo', 'Vui lòng chọn chủ đề', 'warning')
    return
  }

  aiGenerating.value = true
  aiProgressMessage.value = 'Đang tạo transcript và câu hỏi...'

  try {
    const prompt = buildGeneratePrompt()
    const result = await callAI(prompt)

    if (result) {
      applyAIResult(result)
      showAIModal.value = false
    }
  } catch (error) {
    console.error('Error generating with AI:', error)
    Swal.fire('Lỗi', 'Lỗi khi tạo đề: ' + error.message, 'error')
  } finally {
    aiGenerating.value = false
  }
}

function buildGeneratePrompt() {
  const topicNames = {
    accommodation: 'Accommodation & Housing',
    education: 'Education & Learning',
    travel: 'Travel & Tourism',
    health: 'Health & Medicine',
    work: 'Work & Career',
    shopping: 'Shopping & Services',
    social: 'Social Activities',
    environment: 'Environment'
  }

  const partNum = activePart.value
  const partTypes = {
    1: 'a conversation between two people in an everyday social context (e.g., booking a hotel, making arrangements)',
    2: 'a monologue in an everyday social context (e.g., a speech about facilities, a tour guide)',
    3: 'a conversation between up to four people in an educational context (e.g., a tutorial, a group discussion)',
    4: 'a monologue on an academic subject (e.g., a lecture)'
  }

  return `You are an expert IELTS Listening test creator. Create Part ${partNum} of an IELTS Listening test.

REQUIREMENTS:
- Topic: ${topicNames[aiGenerateOptions.topic] || aiGenerateOptions.topic}
- Part ${partNum}: ${partTypes[partNum]}
- Difficulty: Band ${aiGenerateOptions.difficulty}
- Total questions: ${aiGenerateOptions.questionsPerPart}
- Question types: ${aiGenerateOptions.questionTypes.join(', ')}
${aiGenerateOptions.customPrompt ? `- Additional: ${aiGenerateOptions.customPrompt}` : ''}

IMPORTANT:
1. Create a realistic, natural TRANSCRIPT (dialogue or monologue) that would last 3-4 minutes if spoken
2. Use actual speaker names (not "Man/Woman") - e.g., "John:", "Sarah:", "Receptionist:", "Student:"
3. The transcript MUST contain all information needed to answer the questions
4. Include enough detail so answers are clear and unambiguous
5. Use natural conversational English with some informal language
6. Group questions by type with proper IELTS instructions (use <strong> tags)

RESPOND IN JSON FORMAT ONLY (no markdown, no explanation):
{
  "transcript": "<p><strong>Receptionist:</strong> Good morning, City Hotel. How can I help you?</p><p><strong>Customer:</strong> Hi, I'd like to book a room for next weekend, please...</p>",
  "questionGroups": [
    {
      "type": "fill_blanks",
      "startNumber": 1,
      "endNumber": 5,
      "wordLimit": 2,
      "instruction": "<p>Complete the notes below. Write <strong>NO MORE THAN TWO WORDS AND/OR A NUMBER</strong> for each answer.</p>",
      "questions": [
        {"number": 1, "content": "Customer name: __________", "answer": "Sarah Johnson"},
        {"number": 2, "content": "Number of nights: __________", "answer": "three"},
        {"number": 3, "content": "Room type: __________", "answer": "double room"},
        {"number": 4, "content": "Price per night: £__________", "answer": "85"},
        {"number": 5, "content": "Breakfast included: __________", "answer": "Yes"}
      ]
    },
    {
      "type": "multiple_choice",
      "startNumber": 6,
      "endNumber": 8,
      "instruction": "<p>Choose the <strong>correct letter</strong>, <strong>A, B or C</strong>.</p>",
      "questions": [
        {
          "number": 6,
          "content": "When does the customer want to check in?",
          "options": [
            {"label": "A", "content": "Friday morning"},
            {"label": "B", "content": "Friday evening"},
            {"label": "C", "content": "Saturday morning"}
          ],
          "answer": "B"
        },
        {
          "number": 7,
          "content": "What view does the room have?",
          "options": [
            {"label": "A", "content": "City view"},
            {"label": "B", "content": "Sea view"},
            {"label": "C", "content": "Garden view"}
          ],
          "answer": "C"
    }
  ]
    },
    {
      "type": "matching",
      "startNumber": 26,
      "endNumber": 30,
      "instruction": "<p>Complete the flow chart below. Choose <strong>FIVE</strong> answers from the box and write the correct letter, <strong>A–I</strong>, next to Questions 26–30.</p>",
      "features": [
        {"label": "A", "text": "some soil samples"},
        {"label": "B", "text": "some photographs"},
        {"label": "C", "text": "objects to team leader"},
        {"label": "D", "text": "labels on objects"},
        {"label": "E", "text": "a record sheet"},
        {"label": "F", "text": "a unit number"},
        {"label": "G", "text": "further analysis of objects"},
        {"label": "H", "text": "tools from the site hut"},
        {"label": "I", "text": "a comprehensive plan"}
      ],
      "questions": [
        {"number": 26, "content": "Take __________ and clean the area", "answer": "H"},
        {"number": 27, "content": "Ask your team leader for __________", "answer": "F"},
        {"number": 28, "content": "Make __________ of the site", "answer": "I"},
        {"number": 29, "content": "Take __________", "answer": "A"},
        {"number": 30, "content": "Register and put __________", "answer": "D"}
      ]
    }

IMPORTANT: Use "text" (not "content") for matching features!
  ]
}

QUESTION TYPES TO USE:
- "fill_blanks": Form/notes/table/sentence completion (must include wordLimit: 1, 2, or 3)
- "multiple_choice": MCQ with 3-4 options (A, B, C, D)
- "matching": Match people/items/features (must include features array with labels A-E)
- "labeling": Diagram/map labeling (must include features array with labels A-I + diagramDescription)

EXAMPLE FOR LABELING TYPE (Map/Diagram Labeling):
{
  "type": "labeling",
  "startNumber": 11,
  "endNumber": 14,
  "instruction": "<p>Label the map below. Write the correct letter, <strong>A–I</strong>, next to Questions 11–14.</p>",
  "diagramDescription": "A map showing the cycle route with labeled positions A-I",
  "features": [
    {"label": "A"},
    {"label": "B"},
    {"label": "C"},
    {"label": "D"},
    {"label": "E"},
    {"label": "F"},
    {"label": "G"},
    {"label": "H"},
    {"label": "I"}
  ],
  "questions": [
    {"number": 11, "content": "Rocks", "answer": "B"},
    {"number": 12, "content": "Colleen Nature Reserve", "answer": "E"},
    {"number": 13, "content": "Ashington China Factory", "answer": "F"},
    {"number": 14, "content": "Langton Forest", "answer": "I"}
  ]
}

CRITICAL FOR LABELING TYPE:
- "features" array MUST contain ONLY labels (A-I) WITHOUT descriptions/text
- The labels are positions on the map/diagram that students will match
- "questions" contain the items/places to be labeled (e.g., "Rocks", "Visitor Center")
- Students select a letter (A-I) from dropdown to match each item to its position
- DO NOT put location names in features - only letters A-I

CRITICAL RULES:
1. "transcript": HTML format with <p> and <strong> tags for speakers
2. "instruction": MUST be in HTML with <strong> tags for emphasis (word limits, question types)
3. "content": Clear question text (for fill_blanks, use __________ for blanks)
4. "answer": For MCQ use letter (A/B/C/D); for fill_blanks/labeling/matching use letter/text from options
5. "wordLimit": REQUIRED for fill_blanks (1, 2, or 3 - must match instruction)
6. "features": REQUIRED for matching AND labeling types:
   - For LABELING: array of {label} ONLY (A-I) - NO text/description/content
   - For MATCHING: array of {label, text} with full answer options (A-I) - use "text" field, NOT "content"
7. "diagramDescription": REQUIRED for labeling type - describe the map/diagram
8. startNumber/endNumber: Must be continuous (no gaps between groups)
9. Group same question types together - don't mix types
10. Answers MUST be findable in the transcript
11. For LABELING questions: "content" should be the place/item name (e.g., "Rocks", "Visitor Center")
12. For MATCHING questions: "content" should include blanks __________ if it's a completion task
13. For MCQ options: use {label, content} format
14. For MATCHING features: use {label, text} format - "text" is the answer option text
15. Return ONLY valid JSON. NO markdown code blocks, NO explanation text`
}

async function callAI(prompt) {
  // Use backend API endpoint (API key is stored in database)
  const response = await api.post('/examination/generate-ielts-content', {
    skill: 'listening',
    prompt: prompt
  }, {
    timeout: 180000  // 3 minutes timeout for AI generation
  })

  if (response.data.success) {
    return response.data.data
  } else {
    throw new Error(response.data.message || 'AI generation failed')
  }
}

function applyAIResult(result) {
  // Check if result is an array (multiple parts)
  if (Array.isArray(result)) {
    // Multi-part response - apply to all parts by index
    result.forEach((partData, index) => {
      // Match by index: result[0] → parts[0], result[1] → parts[1], etc.
      const part = props.modelValue.parts[index]
      
      if (part) {
        // Apply transcript
        if (partData.transcript) {
          part.transcript = partData.transcript
          showTranscript.value[part.id] = true
        }
        
        // Apply title if provided (but keep original if not)
        if (partData.title && !partData.title.includes('Part')) {
          // If custom title provided, use it
          part.title = `Part ${index + 1}: ${partData.title}`
        }
        
        // Apply question groups
        if (partData.questionGroups && Array.isArray(partData.questionGroups)) {
          part.questionGroups = partData.questionGroups.map(group => mapQuestionGroup(group))
        }
      }
    })
    
    updatePartsQuestionsArrays()
    updateAllQuestionNumbers()
    emitUpdate()
    
    Swal.fire('Thành công', `Đã nhập ${result.length} parts từ file!`, 'success')
    return
  }
  
  // Single part response - apply to active part only
  const part = props.modelValue.parts.find(p => p.id === activePart.value)
  if (!part) return

  // Apply transcript
  if (result.transcript) {
    part.transcript = result.transcript
    showTranscript.value[part.id] = true
  }

  // Apply question groups
  if (result.questionGroups && Array.isArray(result.questionGroups)) {
    part.questionGroups = result.questionGroups.map(group => mapQuestionGroup(group))
  }
  
  updatePartsQuestionsArrays()
  updateAllQuestionNumbers()
  emitUpdate()
  
  Swal.fire('Thành công', 'Đã nhập câu hỏi từ file!', 'success')
}

function mapQuestionGroup(group) {
  // Fix: Normalize AI response - convert "content" to "text" for features
  const features = (group.features || []).map(feature => {
    if (feature.content && !feature.text && !feature.description) {
      // AI returned "content" instead of "text" - normalize it
      return {
        label: feature.label,
        text: feature.content
      }
    }
    return feature
  })
  
  return {
      id: getNextQuestionId(),
      type: group.type,
      startNumber: group.startNumber,
      endNumber: group.endNumber,
      wordLimit: group.wordLimit || 2,
      instruction: group.instruction || null,
      // For matching types
      headings: group.headings || [],
      features: features,
      featureType: group.featureType || '',
      endings: group.endings || [],
      // For completion/labelling types
      diagramDescription: group.diagramDescription || '',
    diagramImage: group.diagramImage || null,
      tableDescription: group.tableDescription || '',
      summaryText: group.summaryText || '',
      notesText: group.notesText || '',
      formTitle: group.formTitle || '',
      columns: group.columns || [],
      questions: (group.questions || []).map(q => ({
        id: getNextQuestionId(),
        number: q.number,
        content: q.content || '',
        statement: q.statement || '',
        sentence: q.sentence || '',
        question: q.question || '',
        stem: q.stem || '',
        note: q.note || '',
        label: q.label || '',
        row: q.row || '',
        prompt: q.prompt || '',
        fieldName: q.fieldName || '',
        options: q.options || [],
        answer: q.answer || ''
      }))
  }
}

function updatePartsQuestionsArrays() {
  // Update legacy questions array for all parts
  props.modelValue.parts.forEach(part => {
    part.questions = []
    for (const group of part.questionGroups || []) {
      for (const q of group.questions || []) {
        part.questions.push({
          id: q.id,
          number: q.number,
          type: group.type,
          content: q.content,
          options: q.options,
          correctAnswer: q.answer
        })
      }
    }
  })
}

function selectAIMethod(method) {
  aiGenerateMethod.value = method
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 10 * 1024 * 1024) {
      Swal.fire('Lỗi', 'File quá lớn. Vui lòng chọn file nhỏ hơn 10MB.', 'error')
      return
    }
    uploadedFile.value = file
  }
}

function handleFileDrop(event) {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file) {
    const ext = file.name.split('.').pop().toLowerCase()
    if (!['doc', 'docx', 'pdf', 'txt'].includes(ext)) {
      Swal.fire('Lỗi', 'Chỉ hỗ trợ file .pdf, .docx, .doc, .txt', 'error')
      return
    }
    if (file.size > 10 * 1024 * 1024) {
      Swal.fire('Lỗi', 'File quá lớn. Vui lòng chọn file nhỏ hơn 10MB.', 'error')
      return
    }
    uploadedFile.value = file
  }
}

function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

async function generateFromFile() {
  if (!hasAISettings.value) {
    Swal.fire('Cảnh báo', 'Vui lòng cấu hình API key tại trang Ngân hàng đề IELTS trước khi sử dụng tính năng này.', 'warning')
    return
  }

  if (!uploadedFile.value) {
    Swal.fire('Cảnh báo', 'Vui lòng chọn file', 'warning')
    return
  }

  aiGenerating.value = true
  aiProgressMessage.value = 'Đang đọc nội dung file...'

  try {
    // Read file content
    const fileContent = await readFileContent(uploadedFile.value)

    aiProgressMessage.value = 'Đang tạo câu hỏi từ transcript...'

    const prompt = buildUploadPrompt(fileContent)
    const result = await callAI(prompt)

    if (result) {
      applyAIResult(result)
      showAIModal.value = false
      aiGenerateMethod.value = null
      uploadedFile.value = null
    }
  } catch (error) {
    console.error('Error generating from file:', error)
    Swal.fire('Lỗi', 'Lỗi khi xử lý file: ' + error.message, 'error')
  } finally {
    aiGenerating.value = false
  }
}

async function readFileContent(file) {
  const ext = file.name.split('.').pop().toLowerCase()
  
  try {
    if (ext === 'pdf') {
      return await readPdfContent(file)
    } else if (ext === 'docx' || ext === 'doc') {
      return await readWordContent(file)
    } else if (ext === 'txt') {
      return await readTextContent(file)
    } else {
      throw new Error('Định dạng file không được hỗ trợ. Vui lòng chọn file .pdf, .docx hoặc .txt')
    }
  } catch (error) {
    throw new Error(`Lỗi đọc file: ${error.message}`)
  }
}

async function readTextContent(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = (e) => resolve(e.target.result)
    reader.onerror = reject
    reader.readAsText(file)
  })
}

async function readPdfContent(file) {
  try {
    const pdfjsLib = await import('pdfjs-dist')
    pdfjsLib.GlobalWorkerOptions.workerSrc = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjsLib.version}/pdf.worker.min.js`
    
    const arrayBuffer = await file.arrayBuffer()
    const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise
    
    let fullText = ''
    for (let i = 1; i <= pdf.numPages; i++) {
      const page = await pdf.getPage(i)
      const textContent = await page.getTextContent()
      const pageText = textContent.items.map(item => item.str).join(' ')
      fullText += pageText + '\n\n'
    }
    
    return fullText.trim()
  } catch (error) {
    throw new Error(`Lỗi đọc PDF: ${error.message}`)
  }
}

async function readWordContent(file) {
  try {
    const mammoth = await import('mammoth')
    
    const arrayBuffer = await file.arrayBuffer()
    const result = await mammoth.extractRawText({ arrayBuffer })
    
    if (result.messages && result.messages.length > 0) {
      console.warn('Word parsing warnings:', result.messages)
    }
    
    return result.value
  } catch (error) {
    throw new Error(`Lỗi đọc Word: ${error.message}`)
  }
}

function buildUploadPrompt(fileContent) {
  return `You are an IELTS data converter. I have uploaded a file containing IELTS Listening questions. Your job is to CONVERT them into the correct JSON structure.

CONTENT FROM FILE:
${fileContent.substring(0, 12000)}

CRITICAL INSTRUCTIONS: 
- If file has 4 PARTS → return ARRAY of 4 objects IN ORDER [Part1, Part2, Part3, Part4]
- If file has only 1 part → return SINGLE object
- Part 1: Questions 1-10 (dialogue, everyday conversation)
- Part 2: Questions 11-20 (monologue, social situation)
- Part 3: Questions 21-30 (dialogue, academic/training)
- Part 4: Questions 31-40 (monologue, academic lecture)

YOUR TASK:
1. DETECT how many parts in file (look for "Part 1/2/3/4", "Section 1/2/3/4", or question numbers 1-10, 11-20, 21-30, 31-40)
2. EXTRACT questions for EACH part separately
3. PRESERVE original question numbers (don't renumber)
4. EXTRACT transcript for each part (if missing, create short placeholder)
5. IDENTIFY question types and create proper instructions
6. PRESERVE all original answers

FORMAT FOR MULTIPLE PARTS (if file has 4 parts) - return ARRAY IN ORDER:
[
  {
    "title": "A conversation about accommodation",
    "transcript": "<p><strong>Receptionist:</strong> Good morning. Housing Office.</p><p><strong>Student:</strong> Hello, I'm looking for student accommodation...</p>",
    "questionGroups": [
      {
        "type": "fill_blanks",
        "startNumber": 1,
        "endNumber": 10,
        "wordLimit": 2,
        "instruction": "<p>Complete the form. Write <strong>NO MORE THAN TWO WORDS AND/OR A NUMBER</strong> for each answer.</p>",
        "questions": [
          {"number": 1, "content": "Name: __________", "answer": "John Williams"},
          {"number": 2, "content": "Phone: __________", "answer": "0777123456"},
          ...continue to question 10
        ]
      }
    ]
  },
  {
    "title": "Information about the City Museum",
    "transcript": "<p><strong>Guide:</strong> Welcome everyone to the City Museum. Today I'll be talking about our new exhibitions...</p>",
    "questionGroups": [
      {
        "type": "fill_blanks",
        "startNumber": 11,
        "endNumber": 15,
        "wordLimit": 3,
        "instruction": "<p>Complete the notes. Write <strong>NO MORE THAN THREE WORDS</strong> for each answer.</p>",
        "questions": [
          {"number": 11, "content": "Museum opened in: __________", "answer": "1875"}
          ...continue to question 20
        ]
      }
    ]
  },
  {
    "title": "Discussion about a research project",
    "transcript": "<p><strong>Tutor:</strong> So, how's your research project going?</p><p><strong>Student:</strong> Well, I've made good progress...</p>",
    "questionGroups": [questions 21-30]
  },
  {
    "title": "Lecture on renewable energy",
    "transcript": "<p><strong>Lecturer:</strong> Today's lecture focuses on renewable energy sources...</p>",
    "questionGroups": [questions 31-40]
  }
]

FORMAT FOR SINGLE PART (if file has only 1 part) - return OBJECT (not array):
{
  "transcript": "<p><strong>Receptionist:</strong> Good morning...</p><p><strong>Customer:</strong> Hi, I'd like to inquire about...</p>",
  "questionGroups": [
    {
      "type": "fill_blanks",
      "startNumber": 1,
      "endNumber": 10,
      "wordLimit": 2,
      "instruction": "<p>Complete the form. Write <strong>NO MORE THAN TWO WORDS AND/OR A NUMBER</strong>.</p>",
      "questions": [
        {"number": 1, "content": "Full name: __________", "answer": "David Brown"},
        {"number": 2, "content": "Contact number: __________", "answer": "0412345678"}
      ]
    }
  ]
}

IMPORTANT RULES:
1. For 4 parts → return [part1, part2, part3, part4] array with ALL 4 parts
2. For 1 part → return {transcript, questionGroups} single object
3. Keep original question numbers (1-10 for Part 1, 11-20 for Part 2, etc.)
4. Each part must have transcript (even if placeholder)
5. Group same question types together within each part

QUESTION TYPES YOU MAY ENCOUNTER:
- Fill in the blanks / Form completion / Note completion → use "fill_blanks" (must include wordLimit: 1, 2, or 3)
- Multiple choice questions → use "multiple_choice" (must include options array)
- Matching (people/items/features) → use "matching" (must include features array)
- Diagram/Map labeling → use "labeling" (must include features array + diagramDescription)

CONVERSION RULES:
1. Extract ALL questions from the file exactly as written
2. Identify and assign the correct "type" for each question group
3. Create proper IELTS "instruction" in HTML format with <strong> tags for key words
4. For fill_blanks: include "wordLimit" (analyze from instruction like "NO MORE THAN TWO WORDS" = wordLimit: 2)
5. For multiple_choice: create "options" array with label (A,B,C,D) and content
6. For matching: create "features" array with label and text
7. For labeling: create "features" array with label and description (e.g., {"label": "A", "description": "Position near entrance"})
8. PRESERVE the original "answer" from the file
9. "transcript": If file contains transcript, format it. If not, create a simple placeholder
10. Number questions sequentially starting from 1
11. Return ONLY the raw JSON object. NO markdown code blocks, NO explanation text`
}

function emitUpdate() {
  emit('update:modelValue', props.modelValue)
}

// Initialize
updateQuestionNumbers()
</script>
