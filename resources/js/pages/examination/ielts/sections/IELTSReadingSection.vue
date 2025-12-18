<template>
  <div class="ielts-reading-section">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold">IELTS Reading</h3>
        <p class="text-sm text-gray-600">
          {{ subtype === 'academic' ? '3 bài đọc học thuật' : '3 phần với nhiều bài đọc ngắn' }}, 40 câu hỏi, 60 phút
        </p>
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

    <!-- Passages Tabs -->
    <div class="flex space-x-2 mb-4">
      <button
        v-for="passage in modelValue.passages"
        :key="passage.id"
        @click="activePassage = passage.id"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        :class="activePassage === passage.id
          ? 'bg-green-100 text-green-700'
          : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
      >
        {{ passage.title }}
        <span v-if="passage.questions.length > 0" class="ml-1 text-xs">({{ passage.questions.length }})</span>
      </button>
    </div>

    <!-- Active Passage Content -->
    <div v-for="passage in modelValue.passages" :key="passage.id" v-show="activePassage === passage.id">
      <div class="border rounded-lg p-4 space-y-4">
        <!-- Passage Title -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề bài đọc</label>
          <input
            v-model="passage.title"
            type="text"
            class="w-full px-3 py-2 border rounded-lg"
            placeholder="VD: The History of Aviation"
          />
        </div>

        <!-- Passage Content with Rich Text Editor -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Nội dung bài đọc *
          </label>
          <div class="border rounded-lg overflow-hidden">
            <!-- Simple toolbar -->
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
              <button @click="formatText('underline')" class="p-1.5 hover:bg-gray-200 rounded" title="Underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v7a5 5 0 0010 0V4M5 20h14" />
                </svg>
              </button>
              <div class="border-l mx-1"></div>
              <button @click="insertParagraph" class="p-1.5 hover:bg-gray-200 rounded text-xs" title="New Paragraph">
                ¶
              </button>
            </div>
            <div
              ref="passageEditor"
              :id="`passage-editor-${passage.id}`"
              contenteditable="true"
              @input="updatePassageContent($event, passage)"
              @blur="updatePassageContent($event, passage)"
              class="p-4 min-h-[300px] prose prose-sm max-w-none focus:outline-none"
              v-html="passage.content"
            ></div>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            Nhập nội dung bài đọc. Có thể định dạng chữ đậm, nghiêng, gạch chân.
          </p>
        </div>

        <!-- Question Groups Section (New format with instructions) -->
        <div v-if="passage.questionGroups && passage.questionGroups.length > 0">
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-medium">
              <svg class="w-5 h-5 inline mr-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              Nhóm câu hỏi ({{ passage.questionGroups.length }} nhóm)
            </h4>
            <button
              @click="addQuestionGroup(passage)"
              class="px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100"
            >
              + Thêm nhóm câu hỏi
            </button>
          </div>

          <div class="space-y-6">
            <div
              v-for="(group, gIndex) in passage.questionGroups"
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
                      @change="onGroupTypeChange(group)"
                      class="text-sm border border-blue-300 rounded px-2 py-1 bg-white"
                    >
                      <option v-for="qt in questionTypes" :key="qt.value" :value="qt.value">
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
                    @click="removeQuestionGroup(passage, gIndex)"
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
                    @click="regenerateInstruction(group, passage)"
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
                  class="p-4 min-h-[100px] prose prose-sm max-w-none focus:outline-none focus:ring-2 focus:ring-blue-300 bg-blue-50/30"
                  v-html="getGroupInstructionHtml(group, passage)"
                ></div>
              </div>

              <!-- Headings/Features List (for matching types) -->
              <div v-if="group.type === 'matching_headings' && group.headings?.length" class="border-b px-4 py-3 bg-yellow-50">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                  </svg>
                  List of Headings
                </label>
                <div class="space-y-2">
                  <div v-for="(heading, hIdx) in group.headings" :key="hIdx" class="flex items-center space-x-2">
                    <span class="w-6 text-center font-bold text-gray-600">{{ heading.numeral }}</span>
                    <input
                      v-model="heading.text"
                      type="text"
                      class="flex-1 px-2 py-1 border rounded text-sm"
                      placeholder="Heading text"
                    />
                    <button
                      @click="group.headings.splice(hIdx, 1)"
                      class="text-red-400 hover:text-red-600"
                    >×</button>
                  </div>
                  <button
                    @click="addHeading(group)"
                    class="text-sm text-yellow-700 hover:text-yellow-800"
                  >
                    + Thêm heading
                  </button>
                </div>
              </div>

              <!-- Features List (for matching features) -->
              <div v-if="group.type === 'matching_features' && group.features?.length" class="border-b px-4 py-3 bg-green-50">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  List of {{ group.featureType || 'Features' }}
                </label>
                <div class="space-y-2">
                  <div v-for="(feature, fIdx) in group.features" :key="fIdx" class="flex items-center space-x-2">
                    <span class="w-6 text-center font-bold text-gray-600">{{ feature.label }}</span>
                    <input
                      v-model="feature.text"
                      type="text"
                      class="flex-1 px-2 py-1 border rounded text-sm"
                      placeholder="Feature text"
                    />
                    <button
                      @click="group.features.splice(fIdx, 1)"
                      class="text-red-400 hover:text-red-600"
                    >×</button>
                  </div>
                  <button
                    @click="addFeature(group)"
                    class="text-sm text-green-700 hover:text-green-800"
                  >
                    + Thêm {{ group.featureType || 'feature' }}
                  </button>
                </div>
              </div>

              <!-- Endings List (for matching sentence endings) -->
              <div v-if="group.type === 'matching_sentence_endings' && group.endings?.length" class="border-b px-4 py-3 bg-purple-50">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  List of Sentence Endings
                </label>
                <div class="space-y-2">
                  <div v-for="(ending, eIdx) in group.endings" :key="eIdx" class="flex items-center space-x-2">
                    <span class="w-6 text-center font-bold text-gray-600">{{ ending.label }}</span>
                    <input
                      v-model="ending.text"
                      type="text"
                      class="flex-1 px-2 py-1 border rounded text-sm"
                      placeholder="Ending text"
                    />
                    <button
                      @click="group.endings.splice(eIdx, 1)"
                      class="text-red-400 hover:text-red-600"
                    >×</button>
                  </div>
                  <button
                    @click="addEnding(group)"
                    class="text-sm text-purple-700 hover:text-purple-800"
                  >
                    + Thêm ending
                  </button>
                </div>
              </div>

              <!-- Summary Text (for summary completion) -->
              <div v-if="group.type === 'summary_completion'" class="border-b px-4 py-3 bg-orange-50">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Summary Text (với _____ để đánh dấu chỗ trống)
                </label>
                <textarea
                  v-model="group.summaryText"
                  rows="4"
                  class="w-full px-3 py-2 border rounded text-sm"
                  placeholder="Enter summary text with _____ for blanks..."
                ></textarea>
              </div>

              <!-- Diagram Description (for diagram labelling) -->
              <div v-if="group.type === 'diagram_labelling'" class="border-b px-4 py-3 bg-indigo-50">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Diagram Description
                </label>
                <textarea
                  :value="group.diagramDescription || ''"
                  @input="e => { group.diagramDescription = e.target.value; emitUpdate(); }"
                  rows="3"
                  class="w-full px-3 py-2 border rounded text-sm mb-3"
                  placeholder="Describe the diagram..."
                ></textarea>

                <!-- Diagram Labels Preview -->
                <div v-if="group.questions && group.questions.length > 0" class="mt-3">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Diagram Labels:</label>
                  <div class="flex flex-wrap gap-2">
                    <div v-for="(q, qIdx) in group.questions" :key="qIdx" class="inline-flex items-center gap-2 px-3 py-2 bg-white border-2 border-indigo-300 rounded-lg">
                      <span class="font-bold text-indigo-700 text-lg">{{ q.label || '?' }}</span>
                      <span class="text-xs text-gray-600">→ Q{{ q.number }}</span>
                    </div>
                  </div>
                  <p class="text-xs text-gray-600 mt-2 italic">
                    💡 Students will see the diagram with these labels and need to fill in the answers
                  </p>
                </div>
              </div>

              <!-- Table Description (for table completion) -->
              <div v-if="group.type === 'table_completion'" class="border-b px-4 py-3 bg-teal-50">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  Table Description
                </label>
                <textarea
                  :value="group.tableDescription || ''"
                  @input="e => { group.tableDescription = e.target.value; emitUpdate(); }"
                  rows="2"
                  class="w-full px-3 py-2 border rounded text-sm mb-3"
                  placeholder="Describe the table structure..."
                ></textarea>

                <!-- Table Preview -->
                <div v-if="group.columns && group.columns.length > 0" class="mt-3">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Table Preview:</label>
                  <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-300 text-sm">
                      <thead>
                        <tr class="bg-teal-100">
                          <th v-for="(col, idx) in group.columns" :key="idx" class="border border-gray-300 px-3 py-2 text-left font-semibold">
                            {{ col }}
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(q, qIdx) in group.questions" :key="qIdx" class="hover:bg-teal-50">
                          <td class="border border-gray-300 px-3 py-2 font-medium">
                            {{ q.row || 'Row ' + (qIdx + 1) }}
                          </td>
                          <td class="border border-gray-300 px-3 py-2">
                            <span v-html="(q.prompt || q.question || '').replace('_____', `<strong class='text-blue-600'>[${q.number}]</strong>`)"></span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Notes Text (for note completion) -->
              <div v-if="group.type === 'note_completion'" class="border-b px-4 py-3 bg-pink-50">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Notes Text (với _____ để đánh dấu chỗ trống)
                </label>
                <textarea
                  v-model="group.notesText"
                  rows="4"
                  class="w-full px-3 py-2 border rounded text-sm"
                  placeholder="Enter notes with _____ for blanks..."
                ></textarea>
              </div>

              <!-- Questions in Group -->
              <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                  <label class="text-sm font-medium text-gray-700">Câu hỏi trong nhóm</label>
                  <button
                    @click="addQuestionToGroup(group)"
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
                        <!-- Different input based on question type -->
                        <template v-if="group.type === 'matching_headings'">
                          <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">Paragraph</span>
                            <input
                              v-model="question.paragraphRef"
                              type="text"
                              class="w-12 px-2 py-1 border rounded text-center font-bold"
                              placeholder="A"
                            />
                            <span class="text-sm text-gray-500">→ Đáp án:</span>
                            <input
                              v-model="question.answer"
                              type="text"
                              class="w-16 px-2 py-1 border rounded text-center"
                              placeholder="iii"
                            />
                          </div>
                        </template>

                        <template v-else-if="group.type === 'true_false_ng' || group.type === 'yes_no_ng'">
                          <input
                            v-model="question.statement"
                            type="text"
                            class="w-full px-2 py-1 border rounded text-sm mb-2"
                            placeholder="Statement..."
                          />
                          <div class="flex space-x-3">
                            <label v-for="opt in (group.type === 'true_false_ng' ? ['TRUE', 'FALSE', 'NOT GIVEN'] : ['YES', 'NO', 'NOT GIVEN'])" :key="opt" class="flex items-center space-x-1">
                              <input
                                type="radio"
                                :name="`q-${question.id}-answer`"
                                :value="opt"
                                v-model="question.answer"
                                class="text-green-600"
                              />
                              <span class="text-xs">{{ opt }}</span>
                            </label>
                          </div>
                        </template>

                        <template v-else-if="group.type === 'sentence_completion' || group.type === 'note_completion'">
                          <input
                            v-model="question.sentence"
                            type="text"
                            class="w-full px-2 py-1 border rounded text-sm mb-2"
                            placeholder="Sentence with __________ blank..."
                          />
                          <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">Đáp án:</span>
                            <input
                              v-model="question.answer"
                              type="text"
                              class="flex-1 px-2 py-1 border rounded text-sm"
                              placeholder="Answer (from passage)"
                            />
                          </div>
                        </template>

                        <template v-else-if="group.type === 'multiple_choice'">
                          <input
                            v-model="question.question"
                            type="text"
                            class="w-full px-2 py-1 border rounded text-sm mb-2"
                            placeholder="Question text..."
                          />
                          <div class="space-y-1">
                            <div v-for="(opt, oIdx) in (question.options || [])" :key="oIdx" class="flex items-center space-x-2">
                              <input
                                type="radio"
                                :name="`q-${question.id}-mc`"
                                :value="opt.label"
                                v-model="question.answer"
                                class="text-green-600"
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
                        </template>

                        <template v-else-if="group.type === 'matching_features' || group.type === 'matching_information'">
                          <input
                            v-model="question.statement"
                            type="text"
                            class="w-full px-2 py-1 border rounded text-sm mb-2"
                            placeholder="Statement..."
                          />
                          <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">Đáp án:</span>
                            <input
                              v-model="question.answer"
                              type="text"
                              class="w-12 px-2 py-1 border rounded text-center"
                              placeholder="A"
                            />
                          </div>
                        </template>

                        <template v-else-if="group.type === 'matching_sentence_endings'">
                          <input
                            v-model="question.stem"
                            type="text"
                            class="w-full px-2 py-1 border rounded text-sm mb-2"
                            placeholder="Sentence start (stem)..."
                          />
                          <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">Đáp án (ending):</span>
                            <input
                              v-model="question.answer"
                              type="text"
                              class="w-12 px-2 py-1 border rounded text-center"
                              placeholder="A"
                            />
                          </div>
                        </template>

                        <template v-else-if="group.type === 'summary_completion'">
                          <div class="text-sm text-gray-500 mb-2">
                            (Câu này sẽ lấy từ Summary Text ở trên với ______)
                          </div>
                          <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">Đáp án:</span>
                            <input
                              v-model="question.answer"
                              type="text"
                              class="flex-1 px-2 py-1 border rounded text-sm"
                              placeholder="Answer (from passage)"
                            />
                          </div>
                        </template>

                        <template v-else-if="group.type === 'diagram_labelling'">
                          <div class="flex items-center space-x-2 mb-2">
                            <span class="text-sm text-gray-600">Label:</span>
                            <input
                              v-model="question.label"
                              type="text"
                              class="w-20 px-2 py-1 border rounded text-sm"
                              placeholder="e.g., A"
                            />
                          </div>
                          <input
                            v-model="question.question"
                            type="text"
                            class="w-full px-2 py-1 border rounded text-sm mb-2"
                            placeholder="Question/Prompt text..."
                          />
                          <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">Đáp án:</span>
                            <input
                              v-model="question.answer"
                              type="text"
                              class="flex-1 px-2 py-1 border rounded text-sm"
                              placeholder="Answer"
                            />
                          </div>
                        </template>

                        <template v-else-if="group.type === 'table_completion'">
                          <div class="flex items-center space-x-2 mb-2">
                            <span class="text-sm text-gray-600">Row:</span>
                            <input
                              v-model="question.row"
                              type="text"
                              class="flex-1 px-2 py-1 border rounded text-sm"
                              placeholder="e.g., Row 1"
                            />
                          </div>
                          <input
                            v-model="question.prompt"
                            type="text"
                            class="w-full px-2 py-1 border rounded text-sm mb-2"
                            placeholder="Prompt/Context for this cell..."
                          />
                          <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">Đáp án:</span>
                            <input
                              v-model="question.answer"
                              type="text"
                              class="flex-1 px-2 py-1 border rounded text-sm"
                              placeholder="Answer"
                            />
                          </div>
                        </template>

                        <template v-else>
                          <!-- Default: short answer / other types -->
                          <input
                            v-model="question.question"
                            type="text"
                            class="w-full px-2 py-1 border rounded text-sm mb-2"
                            placeholder="Question..."
                          />
                          <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">Đáp án:</span>
                            <input
                              v-model="question.answer"
                              type="text"
                              class="flex-1 px-2 py-1 border rounded text-sm"
                              placeholder="Answer"
                            />
                          </div>
                        </template>
                      </div>
                      <button
                        @click="removeQuestionFromGroup(group, qIndex)"
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
            <h4 class="font-medium">Câu hỏi {{ passage.title }}</h4>
            <div class="flex space-x-2">
              <select v-model="newQuestionType" class="text-sm border rounded px-2 py-1">
                <option value="multiple_choice">Trắc nghiệm</option>
                <option value="true_false_ng">True/False/Not Given</option>
                <option value="yes_no_ng">Yes/No/Not Given</option>
                <option value="matching_headings">Matching Headings</option>
                <option value="matching_features">Matching Features</option>
                <option value="matching_sentence_endings">Matching Sentence Endings</option>
                <option value="sentence_completion">Sentence Completion</option>
                <option value="summary_completion">Summary Completion</option>
                <option value="short_answer">Short Answer</option>
              </select>
              <button
                @click="addQuestion(passage)"
                class="px-3 py-1 text-sm bg-green-50 text-green-600 rounded-lg hover:bg-green-100"
              >
                + Thêm câu hỏi
              </button>
            </div>
          </div>

          <div v-if="!passage.questions || passage.questions.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
            Chưa có câu hỏi nào. Sử dụng "Tạo đề bằng AI" hoặc thêm câu hỏi thủ công.
          </div>

          <draggable
            v-else
            v-model="passage.questions"
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
                      <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">
                        {{ getQuestionTypeName(question.type) }}
                      </span>
                    </div>

                    <!-- Question Content -->
                    <div class="mb-3">
                      <label class="block text-sm text-gray-600 mb-1">Nội dung câu hỏi / Hướng dẫn</label>
                      <div
                        contenteditable="true"
                        @input="e => question.content = e.target.innerHTML"
                        class="w-full px-3 py-2 border rounded-lg text-sm min-h-[60px] focus:outline-none focus:ring-2 focus:ring-green-500"
                        v-html="question.content"
                      ></div>
                    </div>

                    <!-- Question type specific inputs -->
                    <component
                      :is="getQuestionComponent(question.type)"
                      :modelValue="question"
                      @update:modelValue="val => updateQuestion(passage, index, val)"
                    />
                  </div>

                  <button @click="removeQuestion(passage, index)" class="text-red-500 hover:text-red-700">
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
                <h3 class="text-xl font-bold text-white">Tạo đề IELTS Reading bằng AI</h3>
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
              <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-purple-200">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-800 mb-2">Tự tạo hoàn toàn</h4>
              <p class="text-sm text-gray-600 mb-4">
                AI sẽ tạo bài đọc và câu hỏi hoàn toàn mới dựa trên chủ đề và độ khó bạn chọn
              </p>
              <ul class="text-xs text-gray-500 space-y-1">
                <li class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Chọn chủ đề (Science, History, Environment...)</li>
                <li class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Chọn độ khó (Band 5-9)</li>
                <li class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Tự động tạo các loại câu hỏi IELTS</li>
              </ul>
            </div>

            <!-- Option 2: Upload file -->
            <div
              @click="selectAIMethod('upload')"
              class="border-2 border-gray-200 rounded-xl p-6 cursor-pointer hover:border-indigo-500 hover:bg-indigo-50 transition-all group"
            >
              <div class="w-14 h-14 bg-indigo-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-indigo-200">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-800 mb-2">Upload Câu Hỏi Có Sẵn</h4>
              <p class="text-sm text-gray-600 mb-4">
                Upload file chứa passage + câu hỏi, AI sẽ chuyển đổi sang định dạng hệ thống
              </p>
              <ul class="text-xs text-gray-500 space-y-1">
                <li class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Hỗ trợ .pdf, .docx, .doc, .txt</li>
                <li class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> AI convert sang JSON format</li>
                <li class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Giữ nguyên nội dung gốc</li>
              </ul>
            </div>
          </div>

          <!-- Generate from scratch form -->
          <div v-if="aiGenerateMethod === 'generate'" class="space-y-6">
            <button @click="aiGenerateMethod = null" class="text-purple-600 hover:text-purple-800 flex items-center text-sm">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              Quay lại
            </button>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Chủ đề bài đọc</label>
                <select v-model="aiGenerateOptions.topic" class="w-full px-3 py-2 border rounded-lg">
                  <option value="">Chọn chủ đề</option>
                  <option value="science">Science & Technology</option>
                  <option value="history">History & Archaeology</option>
                  <option value="environment">Environment & Nature</option>
                  <option value="health">Health & Medicine</option>
                  <option value="education">Education & Learning</option>
                  <option value="business">Business & Economics</option>
                  <option value="psychology">Psychology & Behavior</option>
                  <option value="art">Art & Culture</option>
                  <option value="society">Society & Social Issues</option>
                  <option value="language">Language & Communication</option>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng passage</label>
                <select v-model="aiGenerateOptions.passageCount" class="w-full px-3 py-2 border rounded-lg">
                  <option :value="1">1 passage</option>
                  <option :value="2">2 passages</option>
                  <option :value="3">3 passages (Full test)</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Số câu hỏi mỗi passage</label>
                <select v-model="aiGenerateOptions.questionsPerPassage" class="w-full px-3 py-2 border rounded-lg">
                  <option :value="10">10 câu</option>
                  <option :value="13">13 câu (chuẩn IELTS)</option>
                  <option :value="14">14 câu</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Loại câu hỏi muốn tạo</label>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                <label v-for="qType in questionTypes" :key="qType.value" class="flex items-center space-x-2 p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
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
                placeholder="VD: Bài đọc về lịch sử phát minh ra máy bay, độ dài khoảng 800 từ..."
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
          </div>

          <!-- Upload file form -->
          <div v-if="aiGenerateMethod === 'upload'" class="space-y-6">
            <button @click="aiGenerateMethod = null" class="text-indigo-600 hover:text-indigo-800 flex items-center text-sm">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              Quay lại
            </button>

            <!-- File upload area -->
            <div
              @dragover.prevent="isDragging = true"
              @dragleave="isDragging = false"
              @drop.prevent="handleFileDrop"
              :class="[
                'border-2 border-dashed rounded-xl p-8 text-center transition-colors',
                isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400'
              ]"
            >
              <div v-if="!uploadedFile">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="text-left">
                  <p class="font-medium text-gray-800">{{ uploadedFile.name }}</p>
                  <p class="text-sm text-gray-500">{{ formatFileSize(uploadedFile.size) }}</p>
                </div>
                <button @click="uploadedFile = null" class="text-red-500 hover:text-red-700">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
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
                    <li>Nhận diện passage và câu hỏi từ file</li>
                    <li>Phát hiện loại câu hỏi (TRUE/FALSE, MCQ, Completion...)</li>
                    <li>Convert sang JSON format chuẩn</li>
                    <li>Tạo instructions theo format IELTS</li>
                    <li>Format passage với paragraph labels (A, B, C...)</li>
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, h, defineComponent, onMounted } from 'vue'
import draggable from 'vuedraggable'
import api from '@/api'
import Swal from 'sweetalert2'

const props = defineProps({
  modelValue: { type: Object, required: true },
  subtype: { type: String, default: 'academic' }
})

const emit = defineEmits(['update:modelValue'])

const activePassage = ref(1)
const newQuestionType = ref('multiple_choice')
let questionIdCounter = 0

// Refs for contenteditable instruction editors
const groupInstructionRefs = ref({})

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
  passageCount: 1,
  questionsPerPassage: 13,
  questionTypes: ['multiple_choice', 'true_false_ng', 'sentence_completion'],
  customPrompt: ''
})

const questionTypes = [
  { value: 'multiple_choice', label: 'Trắc nghiệm' },
  { value: 'true_false_ng', label: 'True/False/NG' },
  { value: 'yes_no_ng', label: 'Yes/No/NG' },
  { value: 'matching_headings', label: 'Matching Headings' },
  { value: 'matching_features', label: 'Matching Features' },
  { value: 'matching_information', label: 'Matching Information' },
  { value: 'matching_sentence_endings', label: 'Matching Sentence Endings' },
  { value: 'sentence_completion', label: 'Sentence Completion' },
  { value: 'summary_completion', label: 'Summary Completion' },
  { value: 'note_completion', label: 'Note Completion' },
  { value: 'table_completion', label: 'Table Completion' },
  { value: 'diagram_labelling', label: 'Diagram Labelling' },
  { value: 'short_answer', label: 'Short Answer' }
]

// IELTS Standard Instruction Templates
const questionGroupTemplates = {
  matching_headings: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>The reading passage has {{paragraphCount}} paragraphs, <strong>{{paragraphLabels}}</strong>.</p>
<p>Choose the correct heading for each paragraph from the list of headings below.</p>
<p>Write the correct number, <strong>i-{{headingCount}}</strong>, in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`,
    listHeader: '<p><strong>List of Headings</strong></p>',
    itemFormat: '<p><strong>{{numeral}}</strong> {{text}}</p>',
    questionFormat: '<p><strong>{{num}}</strong> Paragraph <strong>{{paragraphRef}}</strong></p>'
  },
  true_false_ng: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Do the following statements agree with the information given in the reading passage?</p>
<p>In boxes {{startNum}}-{{endNum}} on your answer sheet, write</p>
<p><strong>TRUE</strong> if the statement agrees with the information</p>
<p><strong>FALSE</strong> if the statement contradicts the information</p>
<p><strong>NOT GIVEN</strong> if there is no information on this</p>`,
    questionFormat: '<p><strong>{{num}}</strong> {{statement}}</p>'
  },
  yes_no_ng: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Do the following statements agree with the views/claims of the writer in the reading passage?</p>
<p>In boxes {{startNum}}-{{endNum}} on your answer sheet, write</p>
<p><strong>YES</strong> if the statement agrees with the views/claims of the writer</p>
<p><strong>NO</strong> if the statement contradicts the views/claims of the writer</p>
<p><strong>NOT GIVEN</strong> if it is impossible to say what the writer thinks about this</p>`,
    questionFormat: '<p><strong>{{num}}</strong> {{statement}}</p>'
  },
  multiple_choice: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Choose the correct letter, <strong>A</strong>, <strong>B</strong>, <strong>C</strong> or <strong>D</strong>.</p>
<p>Write the correct letter in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`,
    questionFormat: `<p><strong>{{num}}</strong> {{question}}</p>
<p><strong>A</strong> {{optionA}}</p>
<p><strong>B</strong> {{optionB}}</p>
<p><strong>C</strong> {{optionC}}</p>
<p><strong>D</strong> {{optionD}}</p>`
  },
  sentence_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the sentences below.</p>
<p>Choose <strong>NO MORE THAN {{wordLimit}} WORDS</strong> from the passage for each answer.</p>
<p>Write your answers in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`,
    questionFormat: '<p><strong>{{num}}</strong> {{sentence}} <strong>__________</strong></p>'
  },
  summary_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the summary below.</p>
<p>Choose <strong>NO MORE THAN {{wordLimit}} WORDS</strong> from the passage for each answer.</p>
<p>Write your answers in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`,
    summaryText: '<div class="bg-gray-50 p-4 rounded-lg border my-3">{{summaryContent}}</div>'
  },
  note_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the notes below.</p>
<p>Choose <strong>NO MORE THAN {{wordLimit}} WORDS</strong> from the passage for each answer.</p>
<p>Write your answers in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`,
    questionFormat: '<p>• {{note}} <strong>__________</strong></p>'
  },
  matching_features: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Look at the following statements (Questions {{startNum}}-{{endNum}}) and the list of {{featureType}} below.</p>
<p>Match each statement with the correct {{featureTypeSingular}}, <strong>{{optionLabels}}</strong>.</p>
<p>Write the correct letter, <strong>{{optionLabels}}</strong>, in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>
<p><em>NB You may use any letter more than once.</em></p>`,
    listHeader: '<p><strong>List of {{featureType}}</strong></p>',
    itemFormat: '<p><strong>{{label}}</strong> {{text}}</p>',
    questionFormat: '<p><strong>{{num}}</strong> {{statement}}</p>'
  },
  matching_information: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>The reading passage has {{paragraphCount}} paragraphs, <strong>{{paragraphLabels}}</strong>.</p>
<p>Which paragraph contains the following information?</p>
<p>Write the correct letter, <strong>{{paragraphLabels}}</strong>, in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>
<p><em>NB You may use any letter more than once.</em></p>`,
    questionFormat: '<p><strong>{{num}}</strong> {{information}}</p>'
  },
  matching_sentence_endings: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete each sentence with the correct ending, <strong>{{optionLabels}}</strong>, below.</p>
<p>Write the correct letter, <strong>{{optionLabels}}</strong>, in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`,
    listHeader: '<p><strong>List of Sentence Endings</strong></p>',
    itemFormat: '<p><strong>{{label}}</strong> {{text}}</p>',
    questionFormat: '<p><strong>{{num}}</strong> {{sentenceStart}}</p>'
  },
  short_answer: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Answer the questions below.</p>
<p>Choose <strong>NO MORE THAN {{wordLimit}} WORDS</strong> from the passage for each answer.</p>
<p>Write your answers in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`,
    questionFormat: '<p><strong>{{num}}</strong> {{question}}</p>'
  }
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
    Swal.fire('Cảnh báo', 'Vui lòng chọn chủ đề bài đọc', 'warning')
    return
  }

  aiGenerating.value = true
  aiProgressMessage.value = 'Đang tạo bài đọc và câu hỏi...'

  try {
    const prompt = buildGeneratePrompt()
    const result = await callAI(prompt)

    if (result) {
      applyAIResult(result)
      showAIModal.value = false
      aiGenerateMethod.value = null
    }
  } catch (error) {
    console.error('Error generating with AI:', error)
    Swal.fire('Lỗi', 'Lỗi khi tạo đề: ' + error.message, 'error')
  } finally {
    aiGenerating.value = false
  }
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

    aiProgressMessage.value = 'Đang tạo câu hỏi từ bài đọc...'

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
    // Dynamic import for PDF.js
    const pdfjsLib = await import('pdfjs-dist')
    
    // Set worker path for PDF.js
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
    // Dynamic import for Mammoth
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

function buildGeneratePrompt() {
  const topicNames = {
    science: 'Science & Technology',
    history: 'History & Archaeology',
    environment: 'Environment & Nature',
    health: 'Health & Medicine',
    education: 'Education & Learning',
    business: 'Business & Economics',
    psychology: 'Psychology & Behavior',
    art: 'Art & Culture',
    society: 'Society & Social Issues',
    language: 'Language & Communication'
  }

  return `You are an expert IELTS Reading test creator. Create an IELTS Academic Reading passage with GROUPED questions.

REQUIREMENTS:
- Topic: ${topicNames[aiGenerateOptions.topic] || aiGenerateOptions.topic}
- Difficulty: Band ${aiGenerateOptions.difficulty}
- Total questions: ${aiGenerateOptions.questionsPerPassage}
- Question types: ${aiGenerateOptions.questionTypes.join(', ')}
${aiGenerateOptions.customPrompt ? `- Additional: ${aiGenerateOptions.customPrompt}` : ''}

PASSAGE GUIDELINES:
- Length: 700-900 words with 5-7 paragraphs labeled A, B, C, D, E, F, G
- Academic style, complex vocabulary for Band ${aiGenerateOptions.difficulty}
- Include facts, dates, names for questions
- Each paragraph starts with bold label: <p><strong>A</strong> First paragraph...</p>

CRITICAL: Return QUESTION GROUPS, not individual questions. Each group has:
- Shared instruction (standard IELTS format)
- List of items (headings/options for matching types)
- Individual questions with their answers

RESPOND IN JSON FORMAT ONLY:
{
  "title": "Passage title",
  "content": "HTML with paragraphs labeled A-G",
  "questionGroups": [
    {
      "type": "matching_headings",
      "startNumber": 1,
      "endNumber": 4,
      "headings": [
        {"numeral": "i", "text": "Heading text 1"},
        {"numeral": "ii", "text": "Heading text 2"}
      ],
      "questions": [
        {"number": 1, "paragraphRef": "B", "answer": "v"},
        {"number": 2, "paragraphRef": "C", "answer": "ii"}
      ]
    },
    {
      "type": "true_false_ng",
      "startNumber": 5,
      "endNumber": 8,
      "questions": [
        {"number": 5, "statement": "Statement about the passage.", "answer": "TRUE"},
        {"number": 6, "statement": "Another statement.", "answer": "FALSE"}
      ]
    },
    {
      "type": "sentence_completion",
      "startNumber": 9,
      "endNumber": 11,
      "wordLimit": 2,
      "questions": [
        {"number": 9, "sentence": "The author suggests that early humans used __________ to track time.", "answer": "sundials"},
        {"number": 10, "sentence": "Water clocks were invented in __________.", "answer": "Egypt"}
      ]
    },
    {
      "type": "multiple_choice",
      "startNumber": 12,
      "endNumber": 13,
      "questions": [
        {
          "number": 12,
          "question": "What does the author mainly discuss?",
          "options": [
            {"label": "A", "content": "Option A text"},
            {"label": "B", "content": "Option B text"},
            {"label": "C", "content": "Option C text"},
            {"label": "D", "content": "Option D text"}
          ],
          "answer": "B"
        }
      ]
    },
    {
      "type": "matching_features",
      "startNumber": 14,
      "endNumber": 16,
      "featureType": "People",
      "features": [
        {"label": "A", "text": "Person 1"},
        {"label": "B", "text": "Person 2"},
        {"label": "C", "text": "Person 3"}
      ],
      "questions": [
        {"number": 14, "statement": "developed a new theory", "answer": "A"},
        {"number": 15, "statement": "conducted experiments", "answer": "B"}
      ]
    }
  ]
}

IMPORTANT RULES:
1. Group consecutive questions of same type together
2. Total questions = ${aiGenerateOptions.questionsPerPassage}
3. For matching_headings: provide MORE headings than questions (extra distractors)
4. For true_false_ng: answers must be TRUE, FALSE, or NOT GIVEN
5. For sentence_completion/short_answer: answers are 1-3 words FROM the passage
6. Return ONLY valid JSON. No markdown.`
}

function buildUploadPrompt(fileContent) {
  return `You are an IELTS data converter. I have uploaded a file containing an IELTS Reading passage with questions. Your job is to CONVERT them into the correct JSON structure.

CONTENT FROM FILE:
${fileContent.substring(0, 8000)}

YOUR TASK:
1. EXTRACT the reading passage and format it with paragraph labels (A, B, C, D...) using <p><strong>A</strong> text...</p>
2. EXTRACT all questions exactly as written in the file
3. IDENTIFY the question type for each (TRUE/FALSE/NG, MCQ, completion, matching, etc.)
4. GROUP consecutive questions of the same type together
5. CREATE standard IELTS instructions for each group (in HTML with <strong> tags)
6. PRESERVE all original answers from the file

CONVERT TO THIS EXACT JSON FORMAT (no markdown, no explanation):
{
  "title": "Climate Change and Its Global Impact",
  "content": "<p><strong>A</strong> Climate change refers to significant changes in global temperatures...</p><p><strong>B</strong> The primary cause of recent climate change is human activity...</p><p><strong>C</strong> Rising sea levels threaten coastal communities...</p>",
  "questionGroups": [
    {
      "type": "true_false_ng",
      "startNumber": 1,
      "endNumber": 5,
      "instruction": "<p>Do the following statements agree with the information in the passage? Write <strong>TRUE, FALSE or NOT GIVEN</strong>.</p>",
      "questions": [
        {"number": 1, "statement": "Climate change only affects coastal areas.", "answer": "FALSE"},
        {"number": 2, "statement": "Human activities are the main cause of recent climate change.", "answer": "TRUE"},
        {"number": 3, "statement": "All countries have signed the Paris Agreement.", "answer": "NOT GIVEN"},
        {"number": 4, "statement": "Sea levels are rising globally.", "answer": "TRUE"},
        {"number": 5, "statement": "Climate change affects agriculture.", "answer": "TRUE"}
      ]
    },
    {
      "type": "sentence_completion",
      "startNumber": 6,
      "endNumber": 8,
      "wordLimit": 3,
      "instruction": "<p>Complete the sentences below. Choose <strong>NO MORE THAN THREE WORDS</strong> from the passage for each answer.</p>",
      "questions": [
        {"number": 6, "sentence": "The Paris Agreement aims to limit global warming to __________.", "answer": "below 2 degrees"},
        {"number": 7, "sentence": "Rising temperatures affect __________ patterns.", "answer": "weather"},
        {"number": 8, "sentence": "Vulnerable nations face __________ challenges.", "answer": "disproportionate climate"}
      ]
    },
    {
      "type": "multiple_choice",
      "startNumber": 9,
      "endNumber": 10,
      "instruction": "<p>Choose the <strong>correct letter</strong>, <strong>A, B, C or D</strong>.</p>",
      "questions": [
        {
          "number": 9,
          "question": "What is the main topic of the passage?",
          "options": [
            {"label": "A", "content": "The history of climate science"},
            {"label": "B", "content": "The impact of climate change globally"},
            {"label": "C", "content": "Solutions to climate change"},
            {"label": "D", "content": "Climate change in specific regions"}
          ],
          "answer": "B"
    },
    {
          "number": 10,
          "question": "According to the passage, what affects sea levels?",
          "options": [
            {"label": "A", "content": "Volcanic activity"},
            {"label": "B", "content": "Ocean currents"},
            {"label": "C", "content": "Melting ice caps"},
            {"label": "D", "content": "Tectonic movements"}
      ],
          "answer": "C"
        }
      ]
    }
  ]
}

QUESTION TYPES YOU MAY ENCOUNTER:
- TRUE/FALSE/NOT GIVEN or YES/NO/NOT GIVEN → use "true_false_ng" or "yes_no_ng"
- Sentence completion / Fill in the blanks → use "sentence_completion" (must include wordLimit)
- Multiple choice → use "multiple_choice" (must include options array)
- Matching headings to paragraphs → use "matching_headings" (must include headings array)
- Matching features/people/items → use "matching_features" (must include features array)
- Which paragraph contains information → use "matching_information"
- Short answer questions → use "short_answer" (must include wordLimit)
- Summary/Note completion → use "summary_completion" or "note_completion" (must include wordLimit)

CONVERSION RULES:
1. EXTRACT passage content and format with <p><strong>A</strong>...</p> paragraph labels
2. EXTRACT all questions exactly as written
3. IDENTIFY question type and assign correct "type" field
4. CREATE proper "instruction" in HTML with <strong> tags
5. For completion types: analyze instruction to determine "wordLimit" (e.g., "NO MORE THAN THREE WORDS" = 3)
6. For MCQ: extract options and format as {"label": "A", "content": "text"}
7. For matching_headings: extract headings as {"numeral": "i", "text": "heading text"}
8. PRESERVE original answers from file
9. Number questions sequentially and ensure startNumber/endNumber are correct
10. Return ONLY raw JSON object. NO markdown code blocks, NO explanation`
}

async function callAI(prompt) {
  // Use backend API endpoint (API key is stored in database)
  const response = await api.post('/examination/generate-ielts-content', {
    skill: 'reading',
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
  // Apply to the active passage
  const passage = props.modelValue.passages.find(p => p.id === activePassage.value)
  if (!passage) return

  if (result.title) {
    passage.title = result.title
  }

  if (result.content) {
    passage.content = result.content
  }

  // Handle new questionGroups format
  if (result.questionGroups && Array.isArray(result.questionGroups)) {
    passage.questionGroups = result.questionGroups.map(group => ({
      id: getNextQuestionId(),
      type: group.type,
      startNumber: group.startNumber,
      endNumber: group.endNumber,
      wordLimit: group.wordLimit || 3,
      // For matching types
      headings: group.headings || [],
      features: group.features || [],
      featureType: group.featureType || '',
      endings: group.endings || [],  // matching_sentence_endings
      // For completion/labelling types
      diagramDescription: group.diagramDescription || '',
      tableDescription: group.tableDescription || '',
      summaryText: group.summaryText || '',
      notesText: group.notesText || '',
      columns: group.columns || [],
      // Questions within group
      questions: (group.questions || []).map(q => ({
        id: getNextQuestionId(),
        number: q.number,
        // Different fields based on question type
        statement: q.statement || '',
        sentence: q.sentence || '',
        question: q.question || '',
        paragraphRef: q.paragraphRef || '',
        information: q.information || '',
        sentenceStart: q.sentenceStart || '',
        stem: q.stem || '',  // matching_sentence_endings
        note: q.note || '',
        label: q.label || '',  // diagram_labelling
        row: q.row || '',  // table_completion
        prompt: q.prompt || '',  // table_completion
        options: q.options || [],
        answer: q.answer || ''
      }))
    }))

    // Also flatten to questions array for backward compatibility
    passage.questions = []
    for (const group of passage.questionGroups) {
      for (const q of group.questions) {
        passage.questions.push({
          id: q.id,
          number: q.number,
          type: group.type,
          groupId: group.id,
          // Build display content based on type
          content: buildQuestionContent(group, q),
          statement: q.statement,
          sentence: q.sentence,
          question: q.question,
          paragraphRef: q.paragraphRef,
          options: q.options,
          correctAnswer: q.answer
        })
      }
    }
  }
  // Handle legacy questions format (backward compatibility)
  else if (result.questions && Array.isArray(result.questions)) {
    passage.questions = result.questions.map((q, index) => ({
      id: getNextQuestionId(),
      number: getTotalQuestionsBefore(passage.id) + index + 1,
      type: q.type || 'short_answer',
      content: q.question || q.content || q.statement || '',
      statement: q.statement || '',
      options: q.options || [],
      correctAnswer: q.answer || q.correctAnswer || null
    }))
  }

  updateQuestionNumbers()
  emitUpdate()
}

// Build formatted question content based on type
function buildQuestionContent(group, question) {
  switch (group.type) {
    case 'true_false_ng':
    case 'yes_no_ng':
      return question.statement || ''
    case 'sentence_completion':
    case 'note_completion':
      return question.sentence || question.note || ''
    case 'multiple_choice':
      return question.question || ''
    case 'matching_headings':
      return `Paragraph ${question.paragraphRef}`
    case 'matching_features':
    case 'matching_information':
      return question.statement || question.information || ''
    case 'matching_sentence_endings':
      return question.sentenceStart || ''
    case 'short_answer':
      return question.question || ''
    default:
      return question.question || question.statement || ''
  }
}

// Generate formatted instruction HTML for a question group
function generateGroupInstruction(group, passageContent) {
  const template = questionGroupTemplates[group.type]
  if (!template) return ''

  // Count paragraphs in passage
  const paragraphMatches = passageContent?.match(/<p><strong>[A-Z]<\/strong>/g) || []
  const paragraphCount = paragraphMatches.length || 5
  const paragraphLabels = 'ABCDEFGHIJ'.substring(0, paragraphCount).split('').join('-')

  let instruction = template.instruction
    .replace(/\{\{startNum\}\}/g, group.startNumber)
    .replace(/\{\{endNum\}\}/g, group.endNumber)
    .replace(/\{\{wordLimit\}\}/g, group.wordLimit || 3)
    .replace(/\{\{paragraphCount\}\}/g, paragraphCount)
    .replace(/\{\{paragraphLabels\}\}/g, paragraphLabels)
    .replace(/\{\{headingCount\}\}/g, toRoman(group.headings?.length || 6))
    .replace(/\{\{featureType\}\}/g, group.featureType || 'items')
    .replace(/\{\{featureTypeSingular\}\}/g, (group.featureType || 'item').replace(/s$/, ''))
    .replace(/\{\{optionLabels\}\}/g, getOptionLabels(group))

  // Add list of headings/features if applicable
  if (group.headings?.length && template.listHeader) {
    instruction += template.listHeader
    for (const heading of group.headings) {
      instruction += template.itemFormat
        .replace('{{numeral}}', heading.numeral)
        .replace('{{text}}', heading.text)
    }
  }

  if (group.features?.length && template.listHeader) {
    instruction += template.listHeader.replace('{{featureType}}', group.featureType || 'Items')
    for (const feature of group.features) {
      instruction += template.itemFormat
        .replace('{{label}}', feature.label)
        .replace('{{text}}', feature.text)
    }
  }

  return instruction
}

// Convert number to roman numeral
function toRoman(num) {
  const romans = ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x', 'xi', 'xii']
  return romans[num - 1] || num
}

// Get option labels like "A-E" or "A, B or C"
function getOptionLabels(group) {
  if (group.headings?.length) {
    return `i-${toRoman(group.headings.length)}`
  }
  if (group.features?.length) {
    const labels = group.features.map(f => f.label)
    if (labels.length <= 3) return labels.join(', ')
    return `${labels[0]}-${labels[labels.length - 1]}`
  }
  return 'A-D'
}

// Get formatted instruction HTML for a question group
function getGroupInstructionHtml(group, passage) {
  // If custom instruction is set, use it
  if (group.instruction) {
    return group.instruction
  }
  // Otherwise, generate from template
  return generateGroupInstruction(group, passage?.content || '')
}

// Update group instruction from contenteditable
function updateGroupInstruction(group, event) {
  group.instruction = event.target.innerHTML
  emitUpdate()
}

// Format text in group instruction editor
function formatGroupInstruction(group, command) {
  const editor = groupInstructionRefs.value[group.id]
  if (editor) {
    editor.focus()
    document.execCommand(command, false, null)
    group.instruction = editor.innerHTML
    emitUpdate()
  }
}

// Regenerate instruction from template
function regenerateInstruction(group, passage) {
  group.instruction = generateGroupInstruction(group, passage?.content || '')
  emitUpdate()
}

// When group type changes, regenerate instruction
function onGroupTypeChange(group) {
  const passage = props.modelValue.passages.find(p => p.id === activePassage.value)
  regenerateInstruction(group, passage)

  // Initialize headings/features if needed
  if (group.type === 'matching_headings' && (!group.headings || group.headings.length === 0)) {
    group.headings = [
      { numeral: 'i', text: '' },
      { numeral: 'ii', text: '' },
      { numeral: 'iii', text: '' },
      { numeral: 'iv', text: '' },
    ]
  }
  if (group.type === 'matching_features' && (!group.features || group.features.length === 0)) {
    group.features = [
      { label: 'A', text: '' },
      { label: 'B', text: '' },
      { label: 'C', text: '' },
    ]
    group.featureType = 'People'
  }
  if (group.type === 'matching_sentence_endings' && (!group.endings || group.endings.length === 0)) {
    group.endings = [
      { label: 'A', text: '' },
      { label: 'B', text: '' },
      { label: 'C', text: '' },
    ]
  }

  // Initialize text fields for completion/labelling types
  if (group.type === 'summary_completion' && !group.summaryText) {
    group.summaryText = ''
  }
  if (group.type === 'diagram_labelling' && !group.diagramDescription) {
    group.diagramDescription = ''
  }
  if (group.type === 'table_completion' && !group.tableDescription) {
    group.tableDescription = ''
  }
  if (group.type === 'note_completion' && !group.notesText) {
    group.notesText = ''
  }

  // Initialize options for multiple choice questions
  if (group.type === 'multiple_choice') {
    for (const q of group.questions) {
      if (!q.options || q.options.length === 0) {
        q.options = [
          { label: 'A', content: '' },
          { label: 'B', content: '' },
          { label: 'C', content: '' },
          { label: 'D', content: '' },
        ]
      }
    }
  }

  emitUpdate()
}

// Add new question group
function addQuestionGroup(passage) {
  if (!passage.questionGroups) {
    passage.questionGroups = []
  }

  // Calculate start number based on existing groups
  let startNum = 1
  for (const g of passage.questionGroups) {
    startNum = Math.max(startNum, g.endNumber + 1)
  }

  const newGroup = {
    id: getNextQuestionId(),
    type: newQuestionType.value,
    startNumber: startNum,
    endNumber: startNum + 3,
    wordLimit: 3,
    instruction: null,
    headings: [],
    features: [],
    featureType: '',
    questions: [
      { id: getNextQuestionId(), number: startNum, statement: '', sentence: '', question: '', answer: '' },
      { id: getNextQuestionId(), number: startNum + 1, statement: '', sentence: '', question: '', answer: '' },
      { id: getNextQuestionId(), number: startNum + 2, statement: '', sentence: '', question: '', answer: '' },
      { id: getNextQuestionId(), number: startNum + 3, statement: '', sentence: '', question: '', answer: '' },
    ]
  }

  // Initialize type-specific fields
  if (newGroup.type === 'matching_headings') {
    newGroup.headings = [
      { numeral: 'i', text: '' },
      { numeral: 'ii', text: '' },
      { numeral: 'iii', text: '' },
      { numeral: 'iv', text: '' },
      { numeral: 'v', text: '' },
    ]
    newGroup.questions.forEach(q => { q.paragraphRef = '' })
  }

  if (newGroup.type === 'multiple_choice') {
    newGroup.questions.forEach(q => {
      q.options = [
        { label: 'A', content: '' },
        { label: 'B', content: '' },
        { label: 'C', content: '' },
        { label: 'D', content: '' },
      ]
    })
  }

  passage.questionGroups.push(newGroup)
  updateAllQuestionNumbers()
  emitUpdate()
}

// Remove question group
function removeQuestionGroup(passage, index) {
  passage.questionGroups.splice(index, 1)
  updateAllQuestionNumbers()
  emitUpdate()
}

// Add question to group
function addQuestionToGroup(group) {
  const nextNum = group.endNumber + 1
  const newQuestion = {
    id: getNextQuestionId(),
    number: nextNum,
    statement: '',
    sentence: '',
    question: '',
    paragraphRef: '',
    options: group.type === 'multiple_choice' ? [
      { label: 'A', content: '' },
      { label: 'B', content: '' },
      { label: 'C', content: '' },
      { label: 'D', content: '' },
    ] : [],
    answer: ''
  }

  group.questions.push(newQuestion)
  group.endNumber = nextNum

  // Regenerate instruction with new range
  const passage = props.modelValue.passages.find(p => p.id === activePassage.value)
  regenerateInstruction(group, passage)

  updateAllQuestionNumbers()
  emitUpdate()
}

// Remove question from group
function removeQuestionFromGroup(group, index) {
  group.questions.splice(index, 1)

  // Update question numbers and group range
  if (group.questions.length > 0) {
    group.startNumber = group.questions[0].number
    group.endNumber = group.questions[group.questions.length - 1].number
  }

  updateAllQuestionNumbers()
  emitUpdate()
}

// Add heading to matching_headings group
function addHeading(group) {
  if (!group.headings) group.headings = []
  const romans = ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x', 'xi', 'xii']
  const nextNumeral = romans[group.headings.length] || (group.headings.length + 1)
  group.headings.push({ numeral: nextNumeral, text: '' })

  // Regenerate instruction with new heading count
  const passage = props.modelValue.passages.find(p => p.id === activePassage.value)
  regenerateInstruction(group, passage)
  emitUpdate()
}

// Add feature to matching_features group
function addFeature(group) {
  if (!group.features) group.features = []
  const labels = 'ABCDEFGHIJ'
  const nextLabel = labels[group.features.length] || String.fromCharCode(65 + group.features.length)
  group.features.push({ label: nextLabel, text: '' })

  // Regenerate instruction with new feature count
  const passage = props.modelValue.passages.find(p => p.id === activePassage.value)
  regenerateInstruction(group, passage)
  emitUpdate()
}

// Add ending to matching_sentence_endings group
function addEnding(group) {
  if (!group.endings) group.endings = []
  const labels = 'ABCDEFGHIJ'
  const nextLabel = labels[group.endings.length] || String.fromCharCode(65 + group.endings.length)
  group.endings.push({ label: nextLabel, text: '' })

  // Regenerate instruction with new ending count
  const passage = props.modelValue.passages.find(p => p.id === activePassage.value)
  regenerateInstruction(group, passage)
  emitUpdate()
}

// Update all question numbers across all groups
function updateAllQuestionNumbers() {
  for (const passage of props.modelValue.passages) {
    if (passage.questionGroups && passage.questionGroups.length > 0) {
      let num = 1
      for (const group of passage.questionGroups) {
        group.startNumber = num
        for (const q of group.questions) {
          q.number = num++
        }
        group.endNumber = num - 1
      }
    }
  }
}

function getNextQuestionId() {
  return ++questionIdCounter
}

const questionTypeNames = {
  multiple_choice: 'Trắc nghiệm',
  true_false_ng: 'True/False/Not Given',
  yes_no_ng: 'Yes/No/Not Given',
  matching_headings: 'Matching Headings',
  matching_features: 'Matching Features',
  matching_sentence_endings: 'Matching Sentence Endings',
  sentence_completion: 'Sentence Completion',
  summary_completion: 'Summary Completion',
  short_answer: 'Short Answer'
}

function getQuestionTypeName(type) {
  return questionTypeNames[type] || type
}

// Simple components for different question types
const MultipleChoiceInput = defineComponent({
  props: ['modelValue'],
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const question = props.modelValue

    function addOption() {
      const labels = 'ABCDEFGHIJ'
      const nextLabel = labels[question.options?.length || 0] || (question.options?.length || 0) + 1
      if (!question.options) question.options = []
      question.options.push({ label: nextLabel, content: '' })
      emit('update:modelValue', question)
    }

    function removeOption(idx) {
      question.options.splice(idx, 1)
      const labels = 'ABCDEFGHIJ'
      question.options.forEach((opt, i) => { opt.label = labels[i] || i + 1 })
      emit('update:modelValue', question)
    }

    return () => h('div', { class: 'space-y-2' }, [
      h('label', { class: 'block text-sm text-gray-600' }, 'Đáp án'),
      ...(question.options || []).map((option, idx) =>
        h('div', { class: 'flex items-center space-x-2', key: idx }, [
          h('input', {
            type: 'radio',
            name: `q${question.id}-correct`,
            checked: question.correctAnswer === option.label,
            onChange: () => { question.correctAnswer = option.label; emit('update:modelValue', question) },
            class: 'text-green-600'
          }),
          h('span', { class: 'w-6 text-center font-medium' }, option.label + '.'),
          h('input', {
            value: option.content,
            onInput: (e) => { option.content = e.target.value; emit('update:modelValue', question) },
            type: 'text',
            class: 'flex-1 px-2 py-1 border rounded text-sm',
            placeholder: 'Nội dung đáp án'
          }),
          question.options.length > 2 ? h('button', {
            onClick: () => removeOption(idx),
            class: 'text-red-500'
          }, '×') : null
        ])
      ),
      h('button', {
        onClick: addOption,
        class: 'text-sm text-green-600 hover:text-green-700'
      }, '+ Thêm đáp án')
    ])
  }
})

const TrueFalseInput = defineComponent({
  props: ['modelValue'],
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const question = props.modelValue
    const options = ['TRUE', 'FALSE', 'NOT GIVEN']

    return () => h('div', { class: 'space-y-2' }, [
      h('label', { class: 'block text-sm text-gray-600' }, 'Đáp án đúng'),
      h('div', { class: 'flex space-x-4' },
        options.map(opt =>
          h('label', { class: 'flex items-center space-x-2', key: opt }, [
            h('input', {
              type: 'radio',
              name: `q${question.id}-answer`,
              checked: question.correctAnswer === opt,
              onChange: () => { question.correctAnswer = opt; emit('update:modelValue', question) },
              class: 'text-green-600'
            }),
            h('span', {}, opt)
          ])
        )
      )
    ])
  }
})

const ShortAnswerInput = defineComponent({
  props: ['modelValue'],
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const question = props.modelValue

    return () => h('div', { class: 'space-y-2' }, [
      h('label', { class: 'block text-sm text-gray-600' }, 'Đáp án đúng (có thể có nhiều đáp án, phân cách bằng dấu |)'),
      h('input', {
        value: question.correctAnswer,
        onInput: (e) => { question.correctAnswer = e.target.value; emit('update:modelValue', question) },
        type: 'text',
        class: 'w-full px-3 py-2 border rounded-lg text-sm',
        placeholder: 'VD: answer1|answer2|answer3'
      })
    ])
  }
})

function getQuestionComponent(type) {
  switch (type) {
    case 'multiple_choice':
      return MultipleChoiceInput
    case 'true_false_ng':
    case 'yes_no_ng':
      return TrueFalseInput
    default:
      return ShortAnswerInput
  }
}

function getTotalQuestionsBefore(passageId) {
  let total = 0
  for (const passage of props.modelValue.passages) {
    if (passage.id === passageId) break
    total += passage.questions.length
  }
  return total
}

function updateQuestionNumbers() {
  let num = 1
  for (const passage of props.modelValue.passages) {
    for (const question of passage.questions) {
      question.number = num++
    }
  }
  emitUpdate()
}

function addQuestion(passage) {
  const totalBefore = getTotalQuestionsBefore(passage.id) + passage.questions.length + 1
  const type = newQuestionType.value

  const question = {
    id: getNextQuestionId(),
    number: totalBefore,
    type: type,
    content: '',
    correctAnswer: null
  }

  if (type === 'multiple_choice') {
    question.options = [
      { label: 'A', content: '' },
      { label: 'B', content: '' },
      { label: 'C', content: '' },
      { label: 'D', content: '' },
    ]
  }

  passage.questions.push(question)
  updateQuestionNumbers()
}

function removeQuestion(passage, index) {
  passage.questions.splice(index, 1)
  updateQuestionNumbers()
}

function updateQuestion(passage, index, val) {
  passage.questions[index] = val
  emitUpdate()
}

function updatePassageContent(event, passage) {
  passage.content = event.target.innerHTML
  emitUpdate()
}

function formatText(command) {
  document.execCommand(command, false, null)
}

function insertParagraph() {
  document.execCommand('insertParagraph', false, null)
}

function emitUpdate() {
  emit('update:modelValue', props.modelValue)
}

// Initialize question numbers
updateQuestionNumbers()
</script>
