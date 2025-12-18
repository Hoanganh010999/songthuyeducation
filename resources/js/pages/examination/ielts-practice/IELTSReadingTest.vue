<template>
  <div class="ielts-reading-test min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b sticky top-0 z-50 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <div class="bg-gradient-to-br from-green-500 to-emerald-500 p-2 rounded-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <div>
            <h1 class="font-semibold text-gray-800">{{ testData.title }}</h1>
          </div>
        </div>

        <!-- Timer & Controls -->
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2" :class="timeRemaining < 600 ? 'text-red-600' : 'text-orange-500'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-semibold">{{ formattedTime }} minutes remaining</span>
          </div>

          <button @click="toggleFullscreen" class="p-2 hover:bg-gray-100 rounded-lg">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
          </button>

          <button
            @click="showNotesSidebar = !showNotesSidebar"
            class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>{{ showNotesSidebar ? 'Ẩn' : 'Hiện' }} Notes ({{ notes.length }})</span>
          </button>

          <button
            @click="submitTest"
            class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition-colors"
          >
            Submit ▶
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto p-6">
      <!-- Instructions -->
      <div class="bg-cyan-50 border-l-4 border-cyan-500 p-4 mb-6 rounded-r-lg">
        <p class="text-sm text-gray-700">
          You should spend about <strong>20 minutes</strong> on <strong>{{ currentPassage.range }}</strong>, which is based on 
          <strong>{{ currentPassage.title }}</strong> below.
        </p>
      </div>

      <!-- Layout: Passage + Questions + Notes (optional) -->
      <div class="grid gap-6" :class="showNotesSidebar ? 'grid-cols-1 lg:grid-cols-3' : 'grid-cols-1 lg:grid-cols-2'">
        <!-- Left: Reading Passage -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <!-- Passage Navigation -->
          <div class="p-6 border-b border-gray-200 sticky top-0 bg-white z-10">
            <div class="flex gap-2">
              <button
                v-for="(passage, idx) in passages"
                :key="idx"
                @click="switchPassage(idx)"
                class="px-4 py-2 rounded-lg font-medium transition-all shadow-sm flex-1"
                :class="currentPassageIndex === idx 
                  ? 'bg-green-600 text-white transform scale-105' 
                  : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
              >
                Passage {{ idx + 1 }}
                <span v-if="getPassageProgress(idx) > 0" class="ml-2 text-xs">
                  ({{ getPassageProgress(idx) }}/{{ getPassageQuestionCount(idx) }})
                </span>
              </button>
            </div>
          </div>

          <!-- Passage Header with Illustration -->
          <div class="p-6 border-b border-gray-200">
            <div class="bg-gradient-to-r from-green-50 to-cyan-50 p-4 rounded-lg flex items-center gap-4">
              <div class="flex-shrink-0">
                <svg class="w-16 h-16 text-green-600" viewBox="0 0 200 200" fill="none">
                  <!-- Decorative icon -->
                  <rect x="40" y="20" width="120" height="160" rx="8" fill="currentColor" opacity="0.1"/>
                  <rect x="60" y="40" width="80" height="4" rx="2" fill="currentColor" opacity="0.3"/>
                  <rect x="60" y="60" width="60" height="4" rx="2" fill="currentColor" opacity="0.3"/>
                  <circle cx="100" cy="120" r="30" fill="currentColor" opacity="0.2"/>
                  <path d="M100 100 L100 140 M80 120 L120 120" stroke="currentColor" stroke-width="4" opacity="0.4"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-gray-800 mb-1">{{ currentPassage.title }}</h2>
                <p class="text-sm text-gray-600">{{ currentPassage.subtitle }}</p>
              </div>
            </div>
          </div>

          <!-- Passage Text with separate scroll -->
          <div class="passage-scroll overflow-y-auto p-6" style="max-height: 60vh;">
            <div 
              ref="passageTextRef"
              class="prose max-w-none text-gray-700 leading-relaxed select-text relative"
              v-html="currentPassage.content"
              @mouseup="handleTextSelection"
            ></div>
          </div>
        </div>

        <!-- Right: Questions -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <!-- Sticky Part Progress Header -->
          <div class="p-6 border-b border-gray-200 sticky top-0 bg-white z-10">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold text-gray-800">{{ currentPassage.questionsTitle }}</h3>
              <span class="text-sm text-gray-500">
                Passage {{ currentPassageIndex + 1 }}: {{ answeredCount }} of {{ totalQuestions }} questions
              </span>
            </div>

            <!-- Question Numbers Navigation (Sticky) -->
            <div class="flex flex-wrap gap-1">
              <button
                v-for="num in questionNumbers"
                :key="num"
                @click="scrollToQuestion(num)"
                class="w-8 h-8 rounded-lg text-sm font-medium transition-colors"
                :class="answers[num] 
                  ? 'bg-green-600 text-white' 
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
              >
                {{ num }}
              </button>
            </div>
          </div>

          <!-- Questions List with separate scroll -->
          <div class="questions-scroll overflow-y-auto p-6" style="max-height: 60vh;">
            <div class="space-y-6">
            <!-- Section: Questions 1-4 -->
            <div 
              v-for="(section, sIdx) in currentPassage.questionSections"
              :key="sIdx"
              class="question-section"
            >
              <h4 class="font-semibold text-gray-800 mb-3">{{ section.title }}</h4>
              <div v-if="section.instruction" class="text-sm text-gray-600 mb-4 italic prose prose-sm max-w-none" v-html="section.instruction"></div>

              <!-- Matching Type Questions -->
              <div v-if="section.type === 'matching'">
                <!-- List of Headings/Options (displayed first) -->
                <div v-if="section.options && section.options.length > 0" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                  <h5 class="font-semibold text-gray-800 mb-3">List of Headings</h5>
                  <div class="grid grid-cols-1 gap-2">
                    <div
                      v-for="option in section.options"
                      :key="option.value"
                      class="flex gap-3 text-sm"
                    >
                      <span class="font-bold text-gray-700 min-w-[30px]">{{ option.value }}</span>
                      <span class="text-gray-700">{{ option.text }}</span>
                    </div>
                  </div>
                </div>

                <!-- Questions (Paragraphs) -->
                <div class="space-y-3">
                  <div
                    v-for="(question, qIdx) in section.questions"
                    :key="qIdx"
                    :id="`question-${question.number}`"
                    class="question-item"
                  >
                    <div class="flex items-start gap-3">
                      <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number }}.</span>
                      <div class="flex-1">
                        <p class="mb-2 text-gray-700 font-medium">{{ question.text }}</p>
                        <select
                          v-model="answers[question.number]"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        >
                          <option value="">-- Select heading --</option>
                          <option
                            v-for="option in section.options"
                            :key="option.value"
                            :value="option.value"
                          >
                            {{ option.value }}
                          </option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Sentence Completion -->
              <div v-else-if="section.type === 'completion'" class="space-y-3">
                <div 
                  v-for="(question, qIdx) in section.questions"
                  :key="qIdx"
                  :id="`question-${question.number}`"
                  class="question-item"
                >
                  <div class="flex items-start gap-3">
                    <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number }}.</span>
                    <div class="flex-1">
                      <p class="text-gray-700 mb-2">
                        {{ question.prefix }}
                        <input
                          v-model="answers[question.number]"
                          type="text"
                          class="inline-block w-48 px-3 py-1 border-b-2 border-gray-300 focus:border-green-500 focus:outline-none bg-green-50"
                          :placeholder="`Answer ${question.number}`"
                        />
                        {{ question.suffix }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Multiple Choice -->
              <div v-else-if="section.type === 'mcq'" class="space-y-4">
                <div
                  v-for="(question, qIdx) in section.questions"
                  :key="qIdx"
                  :id="`question-${question.number}`"
                  class="question-item"
                >
                  <div class="flex items-start gap-3">
                    <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number }}.</span>
                    <div class="flex-1">
                      <p class="mb-3 text-gray-700 font-medium">{{ question.text }}</p>
                      <div class="space-y-2">
                        <label
                          v-for="option in question.options"
                          :key="option.value"
                          class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                          :class="answers[question.number] === option.value ? 'border-green-500 bg-green-50' : 'border-gray-200'"
                        >
                          <input
                            v-model="answers[question.number]"
                            type="radio"
                            :name="`q${question.number}`"
                            :value="option.value"
                            class="mt-1 text-green-600"
                          />
                          <div>
                            <span class="font-semibold text-gray-700">{{ option.value }}</span>
                            <span class="text-gray-700 ml-2">{{ option.text }}</span>
                          </div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- True/False/Not Given or Yes/No/Not Given -->
              <div v-else-if="section.type === 'true_false_ng' || section.type === 'yes_no_ng'" class="space-y-3">
                <div
                  v-for="(question, qIdx) in section.questions"
                  :key="qIdx"
                  :id="`question-${question.number}`"
                  class="question-item"
                >
                  <div class="flex items-start gap-3">
                    <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number }}.</span>
                    <div class="flex-1">
                      <p class="mb-2 text-gray-700">{{ question.text }}</p>
                      <select
                        v-model="answers[question.number]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                      >
                        <option value="">-- Select answer --</option>
                        <option
                          v-for="option in section.options"
                          :key="option.value"
                          :value="option.value"
                        >
                          {{ option.value }}
                        </option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Matching Features / Information -->
              <div v-else-if="section.type === 'matching_features' || section.type === 'matching_information'">
                <div v-if="section.options && section.options.length > 0" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                  <h5 class="font-semibold text-gray-800 mb-3">Options</h5>
                  <div class="grid grid-cols-1 gap-2">
                    <div v-for="option in section.options" :key="option.value" class="flex gap-3 text-sm">
                      <span class="font-bold text-gray-700 min-w-[30px]">{{ option.value }}</span>
                      <span class="text-gray-700">{{ option.text }}</span>
                    </div>
                  </div>
                </div>

                <div class="space-y-3">
                  <div v-for="(question, qIdx) in section.questions" :key="qIdx" :id="`question-${question.number}`" class="question-item">
                    <div class="flex items-start gap-3">
                      <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number }}.</span>
                      <div class="flex-1">
                        <p class="mb-2 text-gray-700 font-medium">{{ question.text }}</p>
                        <select v-model="answers[question.number]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                          <option value="">-- Select option --</option>
                          <option v-for="option in section.options" :key="option.value" :value="option.value">{{ option.value }}</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Matching Sentence Endings -->
              <div v-else-if="section.type === 'matching_endings'">
                <div v-if="section.options && section.options.length > 0" class="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                  <h5 class="font-semibold text-gray-800 mb-3">Sentence Endings</h5>
                  <div class="grid grid-cols-1 gap-2">
                    <div v-for="option in section.options" :key="option.value" class="flex gap-3 text-sm">
                      <span class="font-bold text-gray-700 min-w-[30px]">{{ option.value }}</span>
                      <span class="text-gray-700">{{ option.text }}</span>
                    </div>
                  </div>
                </div>

                <div class="space-y-3">
                  <div v-for="(question, qIdx) in section.questions" :key="qIdx" :id="`question-${question.number}`" class="question-item">
                    <div class="flex items-start gap-3">
                      <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number }}.</span>
                      <div class="flex-1">
                        <p class="mb-2 text-gray-700 font-medium">{{ question.text }}</p>
                        <select v-model="answers[question.number]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                          <option value="">-- Select ending --</option>
                          <option v-for="option in section.options" :key="option.value" :value="option.value">{{ option.value }}</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Short Answer -->
              <div v-else-if="section.type === 'short_answer'" class="space-y-3">
                <div v-for="(question, qIdx) in section.questions" :key="qIdx" :id="`question-${question.number}`" class="question-item">
                  <div class="flex items-start gap-3">
                    <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number }}.</span>
                    <div class="flex-1">
                      <p class="mb-2 text-gray-700">{{ question.text }}</p>
                      <input v-model="answers[question.number]" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" :placeholder="`Answer (${question.wordLimit})`" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Diagram Labelling -->
              <div v-else-if="section.type === 'diagram'" class="space-y-3">
                <div v-for="(question, qIdx) in section.questions" :key="qIdx" :id="`question-${question.number}`" class="question-item">
                  <div class="flex items-start gap-3">
                    <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number }}.</span>
                    <div class="flex-1">
                      <p class="mb-2 text-gray-700">
                        <span v-if="question.label" class="font-semibold">[{{ question.label }}]</span>
                        {{ question.text }}
                      </p>
                      <input v-model="answers[question.number]" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" :placeholder="`Answer (max ${question.wordLimit} words)`" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Table Completion -->
              <div v-else-if="section.type === 'table'" class="space-y-3">
                <div v-for="(question, qIdx) in section.questions" :key="qIdx" :id="`question-${question.number}`" class="question-item">
                  <div class="flex items-start gap-3">
                    <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number }}.</span>
                    <div class="flex-1">
                      <p class="mb-2 text-gray-700">
                        <span v-if="question.row" class="font-semibold">[{{ question.row }}]</span>
                        {{ question.text }}
                      </p>
                      <input v-model="answers[question.number]" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" :placeholder="`Answer (max ${question.wordLimit} words)`" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Navigation (outside scroll area) -->
          <div class="p-6 border-t border-gray-200 flex justify-center gap-4">
            <button
              v-if="currentPassageIndex > 0"
              @click="currentPassageIndex--"
              class="p-3 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors"
            >
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <button
              v-if="currentPassageIndex < passages.length - 1"
              @click="currentPassageIndex++"
              class="p-3 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors"
            >
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>

        <!-- Right: Notes Sidebar (optional) -->
        <div v-if="showNotesSidebar" class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">My Notes</h3>
            <button
              @click="showNotesSidebar = false"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Search Notes -->
          <div class="mb-4">
            <input
              type="text"
              v-model="noteSearch"
              placeholder="Search notes..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Notes List -->
          <div class="space-y-3 max-h-[600px] overflow-y-auto">
            <div
              v-for="(note, idx) in filteredNotes"
              :key="idx"
              class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 cursor-pointer"
              @click="scrollToNote(note)"
            >
              <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium text-blue-600">Note {{ idx + 1 }}</span>
                <button
                  @click.stop="deleteNote(idx)"
                  class="text-gray-400 hover:text-red-500"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
              <p class="text-xs text-gray-600 mb-2 italic">"{{ note.selectedText.substring(0, 50) }}..."</p>
              <p class="text-sm text-gray-800">{{ note.content }}</p>
            </div>

            <div v-if="filteredNotes.length === 0" class="text-center py-8 text-gray-400">
              <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-sm">Chưa có note nào</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Selection Popup (appears when text is selected) -->
    <div
      v-if="showSelectionPopup"
      :style="{ top: `${popupPosition.y}px`, left: `${popupPosition.x}px` }"
      class="fixed z-[100] bg-white rounded-lg shadow-xl border border-gray-200 p-2 flex gap-2 selection-popup"
    >
      <button
        @click="addNote"
        class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm font-medium transition-colors flex items-center gap-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        Note
      </button>
      <button
        @click="highlightSelected"
        class="px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-yellow-900 rounded text-sm font-medium transition-colors flex items-center gap-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
        </svg>
        Highlight
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

// Props for Full Test mode
const props = defineProps({
  testId: {
    type: [String, Number],
    default: null
  },
  autoSubmit: {
    type: Boolean,
    default: false
  }
})

const route = useRoute()
const router = useRouter()

// Use prop testId if available, otherwise get from route
const activeTestId = computed(() => {
  const id = props.testId || route.params.testId
  console.log('🔍 Active testId:', id, { prop: props.testId, route: route.params.testId })
  return id
})

const testData = ref({
  title: 'Loading...',
  timeLimit: 60
})
const currentPassageIndex = ref(0)
const answers = ref({})
const timeRemaining = ref(60 * 60) // 60 minutes in seconds
const passageTextRef = ref(null)
const isSubmitting = ref(false) // Prevent double submit

// Notes & Highlights
const notes = ref([])
const showNotesSidebar = ref(false)
const selectedText = ref('')
const selectionRange = ref(null)
const showSelectionPopup = ref(false)
const popupPosition = ref({ x: 0, y: 0 })

// Passages data - will be loaded from API
const passages = ref([
  {
    title: 'Reading Passage 1',
    subtitle: 'Loading...',
    range: 'Questions 1 - 13',
    questionsTitle: 'Questions 1-4',
    content: '<p>Loading passage content...</p>',
    questionSections: [
      {
        title: 'Questions 1-4',
        instruction: 'The text has 5 paragraphs (A - E). Which paragraph contains each of the following pieces of information?',
        type: 'matching',
        options: [
          { value: 'A', text: 'A possible security problem' },
          { value: 'B', text: 'The cost of M-Pesa' },
          { value: 'C', text: 'An international service similar to M-Pesa' },
          { value: 'D', text: 'The fact that most Kenyans do not have a bank account' }
        ],
        questions: [
          { number: 1, text: 'A possible security problem' },
          { number: 2, text: 'The cost of M-Pesa' },
          { number: 3, text: 'An international service similar to M-Pesa' },
          { number: 4, text: 'The fact that most Kenyans do not have a bank account' }
        ]
      },
      {
        title: 'Questions 5-8',
        instruction: 'Complete the following sentences using NO MORE THAN THREE WORDS from the text for each answer.',
        type: 'completion',
        questions: [
          { number: 5, prefix: 'Safaricom is the', suffix: 'mobile phone company in Kenya.' },
          { number: 6, prefix: 'An M-Pesa account needs to be credited by', suffix: '' },
          { number: 7, prefix: 'The money can then be transferred', suffix: 'to anyone with a mobile phone.' },
          { number: 8, prefix: 'Recipients visit', suffix: 'with their cellphone.' }
        ]
      },
      {
        title: 'Questions 9-13',
        instruction: 'Choose the correct letter, A, B or C.',
        type: 'mcq',
        questions: [
          {
            number: 9,
            text: 'What is the main purpose of M-Pesa?',
            options: [
              { value: 'A', text: 'To provide banking services to rural areas' },
              { value: 'B', text: 'To send money by text message' },
              { value: 'C', text: 'To replace traditional banks' }
            ]
          },
          {
            number: 10,
            text: 'How many people signed up in the first two weeks?',
            options: [
              { value: 'A', text: 'More than 10,000' },
              { value: 'B', text: '8,000' },
              { value: 'C', text: '280' }
            ]
          }
        ]
      }
    ]
  }
])

const currentPassage = computed(() => passages.value[currentPassageIndex.value])

const questionNumbers = computed(() => {
  const numbers = []
  currentPassage.value.questionSections.forEach(section => {
    section.questions.forEach(q => {
      numbers.push(q.number)
    })
  })
  return numbers
})

const totalQuestions = computed(() => questionNumbers.value.length)

const answeredCount = computed(() => {
  return questionNumbers.value.filter(num => answers.value[num]).length
})

const formattedTime = computed(() => {
  const minutes = Math.floor(timeRemaining.value / 60)
  return minutes
})

onMounted(async () => {
  await loadTestData()
  startTimer()
})

let timerInterval = null

function startTimer() {
  timerInterval = setInterval(() => {
    if (timeRemaining.value > 0) {
      timeRemaining.value--
    } else {
      submitTest()
    }
  }, 1000)
}

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})

async function loadTestData() {
  try {
    const response = await axios.get(`/api/examination/tests/${activeTestId.value}`)
    testData.value = response.data.data
    
    console.log('📝 Reading Test Data:', testData.value)
    
    if (testData.value.settings) {
      const settings = typeof testData.value.settings === 'string' 
        ? JSON.parse(testData.value.settings) 
        : testData.value.settings
      
      console.log('⚙️ Settings:', settings)
      
      // Read from new namespaced structure with backward compatibility fallback
      const passagesData = settings.reading?.passages || settings.passages

      if (passagesData && passagesData.length > 0) {
        // Calculate questionStart for each passage if not provided
        let cumulativeQuestionNumber = 1
        
        // Map passages with their questions
        passages.value = passagesData.map((passage, idx) => {
          // Check if passage uses questionGroups structure (new) or flat questions (old)
          const useQuestionGroups = passage.questionGroups && passage.questionGroups.length > 0

          console.log(`📖 Passage ${idx + 1}: Using ${useQuestionGroups ? 'questionGroups' : 'flat questions'} structure`)

          // Calculate question count for this passage
          let questionCount = 0
          if (useQuestionGroups && passage.questionGroups) {
            questionCount = passage.questionGroups.reduce((sum, group) => {
              return sum + (group.questions ? group.questions.length : 0)
            }, 0)
          } else if (passage.questions) {
            questionCount = passage.questions.length
          }

          // Use provided questionStart or calculate from cumulative count
          const questionStart = passage.questionStart || cumulativeQuestionNumber
          const questionEnd = passage.questionEnd || (questionStart + questionCount - 1)
          
          // Use questionStart as offset for continuous numbering across passages
          const questionOffset = questionStart - 1

          const passageData = {
            title: passage.title || `Reading Passage ${idx + 1}`,
            subtitle: passage.subtitle || '',
            range: `Questions ${questionStart} - ${questionEnd}`,
            questionsTitle: `Questions ${questionStart}-${questionEnd}`,
            content: passage.content || '<p>No content available</p>',
            questionSections: useQuestionGroups
              ? transformQuestionGroups(passage.questionGroups, questionOffset)
              : transformQuestions(passage.questions || [], questionOffset)
          }

          // Update cumulative count for next passage
          cumulativeQuestionNumber = questionEnd + 1

          return passageData
        })

        console.log('📖 Loaded passages:', passages.value.length)
        passages.value.forEach((p, idx) => {
          console.log(`  Passage ${idx + 1}: ${p.questionSections.length} sections, ${p.questionSections.reduce((sum, s) => sum + s.questions.length, 0)} questions total`)
          p.questionSections.forEach((s, sIdx) => {
            console.log(`    Section ${sIdx + 1}: ${s.type}, ${s.questions.length} questions, instruction source: ${s.instructionSource}`)
          })
        })
      } else {
        console.warn('⚠️ No passages data found')
      }
    } else {
      console.warn('⚠️ No settings found in test data')
    }
  } catch (error) {
    console.error('❌ Failed to load test:', error)
  }
}

// Normalize question type names from database to template format
function normalizeQuestionType(dbType) {
  const typeMap = {
    'multiple_choice': 'mcq',
    'sentence_completion': 'completion',
    'note_completion': 'completion',  // Use same template as sentence_completion
    'summary_completion': 'completion',  // Use same template
    'matching_headings': 'matching',
    'matching_features': 'matching_features',  // Keep as-is
    'matching_information': 'matching_information',
    'matching_sentence_endings': 'matching_endings',
    'true_false_ng': 'true_false_ng',
    'yes_no_ng': 'yes_no_ng',
    'short_answer': 'short_answer',
    'diagram_labelling': 'diagram',
    'table_completion': 'table'
  }
  return typeMap[dbType] || dbType
}

// Transform questionGroups structure (new format) to display sections
function transformQuestionGroups(questionGroups, questionOffset = 0) {
  if (!questionGroups || questionGroups.length === 0) {
    return [{
      title: 'Questions 1-10',
      instruction: 'Answer the questions below.',
      type: 'mcq',
      questions: [],
      instructionSource: 'default'
    }]
  }

  return questionGroups.map(group => {
    const normalizedType = normalizeQuestionType(group.type)

    // Use group instruction if available, otherwise use default for type
    const hasCustomInstruction = group.instruction && group.instruction.trim() !== ''
    const instruction = hasCustomInstruction ? group.instruction : getInstructionForType(group.type)
    const instructionSource = hasCustomInstruction ? 'database' : 'default'

    // Get question range from startNumber and endNumber
    const startNum = group.startNumber || group.questions[0]?.number || 1
    const endNum = group.endNumber || group.questions[group.questions.length - 1]?.number || 1

    // Transform questions based on type
    let questions = []
    let options = []

    if (group.type === 'true_false_ng' || group.type === 'yes_no_ng') {
      // TRUE/FALSE/NOT GIVEN questions
      questions = group.questions.map(q => ({
        number: (q.number || 0) + questionOffset,
        text: q.statement || q.question || q.content || q.sentence
      }))

      // Set options for T/F/NG
      options = group.type === 'true_false_ng'
        ? [{ value: 'TRUE' }, { value: 'FALSE' }, { value: 'NOT GIVEN' }]
        : [{ value: 'YES' }, { value: 'NO' }, { value: 'NOT GIVEN' }]

    } else if (group.type === 'multiple_choice') {
      // Multiple choice questions
      questions = group.questions.map(q => {
        // Handle different option formats
        let questionOptions = []

        if (q.options && Array.isArray(q.options) && q.options.length > 0) {
          if (q.options[0].label && q.options[0].content) {
            // AI-generated format: [{ label: 'A', content: '...' }]
            questionOptions = q.options.map(opt => ({
              value: opt.label,
              text: opt.content
            }))
          } else if (q.options[0].value && q.options[0].text) {
            questionOptions = q.options
          } else if (typeof q.options[0] === 'string') {
            questionOptions = q.options.map((opt, idx) => ({
              value: String.fromCharCode(65 + idx),
              text: opt
            }))
          }
        }

        return {
          number: (q.number || 0) + questionOffset,
          text: q.question || q.content || q.statement,
          options: questionOptions
        }
      })

    } else if (group.type === 'matching_headings') {
      // Matching headings
      questions = group.questions.map(q => ({
        number: (q.number || 0) + questionOffset,
        text: q.paragraphRef || q.paragraph || `Paragraph ${(q.number || 0) + questionOffset}`
      }))

      // Extract headings from group
      if (group.headings && Array.isArray(group.headings) && group.headings.length > 0) {
        options = group.headings.map((heading, idx) => ({
          value: heading.numeral || String.fromCharCode(105 + idx), // i, ii, iii, ...
          text: heading.text || heading.content || heading
        }))
      } else {
        console.warn(`⚠️ Matching group ${startNum}-${endNum} has no headings!`, group)
      }

    } else if (group.type === 'sentence_completion' || group.type === 'summary_completion' || group.type === 'note_completion') {
      // Sentence/Summary/Note completion - template expects prefix/suffix
      questions = group.questions.map(q => {
        const fullText = q.sentence || q.note || q.question || q.content || ''
        // Split on ___ or ... for blank
        const blankMatch = fullText.match(/^(.*?)(?:___|\.\.\.|\[blank\])(.*)$/)

        if (blankMatch) {
          return {
            number: (q.number || 0) + questionOffset,
            prefix: blankMatch[1].trim(),
            suffix: blankMatch[2].trim(),
            wordLimit: group.wordLimit || 3
          }
        } else {
          // No blank found, put entire text as prefix
          return {
            number: (q.number || 0) + questionOffset,
            prefix: fullText,
            suffix: '',
            wordLimit: group.wordLimit || 3
          }
        }
      })

    } else if (group.type === 'matching_features' || group.type === 'matching_information') {
      // Matching features/information - similar to matching headings
      questions = group.questions.map(q => ({
        number: (q.number || 0) + questionOffset,
        text: q.statement || q.information || q.question
      }))

      // Extract features/options from group
      if (group.features && Array.isArray(group.features) && group.features.length > 0) {
        options = group.features.map((feature, idx) => ({
          value: feature.label || String.fromCharCode(65 + idx), // A, B, C, ...
          text: feature.text || feature.content || feature
        }))
      }

    } else if (group.type === 'matching_sentence_endings') {
      // Matching sentence endings
      questions = group.questions.map(q => ({
        number: (q.number || 0) + questionOffset,
        text: q.stem || q.sentenceStart || q.question
      }))

      // Extract endings from group
      if (group.endings && Array.isArray(group.endings) && group.endings.length > 0) {
        options = group.endings.map((ending, idx) => ({
          value: ending.label || String.fromCharCode(65 + idx), // A, B, C, ...
          text: ending.text || ending.content || ending
        }))
      }

    } else if (group.type === 'short_answer') {
      // Short answer questions
      questions = group.questions.map(q => ({
        number: (q.number || 0) + questionOffset,
        text: q.question || q.content || q.statement,
        wordLimit: group.wordLimit || 'ONE WORD AND/OR A NUMBER'
      }))

    } else if (group.type === 'diagram_labelling') {
      // Diagram labelling
      questions = group.questions.map(q => ({
        number: (q.number || 0) + questionOffset,
        text: q.question || q.content,
        label: q.label,
        wordLimit: group.wordLimit || 3
      }))

    } else if (group.type === 'table_completion') {
      // Table completion
      questions = group.questions.map(q => ({
        number: (q.number || 0) + questionOffset,
        text: q.prompt || q.question || q.content,
        row: q.row,
        wordLimit: group.wordLimit || 3
      }))

    } else {
      // Generic fallback
      questions = group.questions.map(q => ({
        number: (q.number || 0) + questionOffset,
        text: q.question || q.content || q.statement || q.sentence
      }))
    }

    const section = {
      title: `Questions ${startNum}-${endNum}`,
      instruction,
      instructionSource,
      type: normalizedType,
      questions,
      options
    }

    // Log section details
    console.log(`📦 Created section Q${startNum}-${endNum} (${normalizedType}):`, {
      questionsCount: questions.length,
      optionsCount: options.length,
      hasInstruction: !!instruction,
      firstQuestion: questions[0],
      firstOption: options[0]
    })

    return section
  })
}

// Transform questions from settings to display structure (old flat format)
function transformQuestions(questions, questionOffset = 0) {
  if (!questions || questions.length === 0) {
    return [{
      title: 'Questions 1-10',
      instruction: 'Answer the questions below.',
      type: 'mcq',
      questions: []
    }]
  }

  // Group questions by type
  const sections = []
  let currentSection = null

  questions.forEach((q, idx) => {
    const normalizedType = normalizeQuestionType(q.type)

    // Create new section if type changes or first question
    if (!currentSection || currentSection.type !== normalizedType) {
      // Use custom instruction if available, otherwise use default for type
      const customInstruction = q.instruction || q.sectionInstruction
      const finalInstruction = customInstruction || getInstructionForType(q.type)

      currentSection = {
        title: `Questions ${q.number}`,
        instruction: finalInstruction,
        instructionSource: customInstruction ? 'question-level' : 'default',
        type: normalizedType,
        questions: [],
        options: []
      }
      sections.push(currentSection)
    }
    
    // Update section title range
    const firstQ = currentSection.questions[0]?.number || q.number
    currentSection.title = `Questions ${firstQ}-${q.number}`
    
    // Add question to section
    if (q.type === 'multiple_choice' || q.type === 'mcq') {
      // Handle different option formats
      let questionOptions = []

      if (q.options && Array.isArray(q.options) && q.options.length > 0) {
        if (q.options[0].label && q.options[0].content) {
          // Format: [{ label: 'A', content: '...' }] - AI generated format
          questionOptions = q.options.map(opt => ({
            value: opt.label,
            text: opt.content
          }))
        } else if (q.options[0].value && q.options[0].text) {
          // Format: [{ value: 'A', text: '...' }]
          questionOptions = q.options
        } else if (q.options[0].text) {
          // Format: [{ text: '...', is_correct: true }] - need to add values
          questionOptions = q.options.map((opt, idx) => ({
            value: String.fromCharCode(65 + idx), // A, B, C, D, ...
            text: opt.text || opt
          }))
        } else if (typeof q.options[0] === 'string') {
          // Format: ['option1', 'option2', ...]
          questionOptions = q.options.map((opt, idx) => ({
            value: String.fromCharCode(65 + idx),
            text: opt
          }))
        }
      } else if (q.options && typeof q.options === 'object' && !Array.isArray(q.options)) {
        // Options come as object - convert to array
        const optionKeys = Object.keys(q.options)

        questionOptions = optionKeys.map((key, idx) => {
          const optValue = q.options[key]

          // If key is already A/B/C/D, use it; otherwise generate from index
          const value = /^[A-Z]$/.test(key) ? key : String.fromCharCode(65 + idx)

          // If optValue is string, use it as text; if object, extract text field
          const text = typeof optValue === 'string' ? optValue : (optValue?.text || optValue)

          return { value, text }
        }).filter(opt => opt.text && opt.text.trim() !== '')
      } else {
        // Fallback to optionA, optionB, optionC, optionD fields
        questionOptions = [
          { value: 'A', text: q.optionA || '' },
          { value: 'B', text: q.optionB || '' },
          { value: 'C', text: q.optionC || '' },
          { value: 'D', text: q.optionD || '' }
        ].filter(opt => opt.text && opt.text.trim() !== '')
      }

      currentSection.questions.push({
        number: (q.number || 0) + questionOffset,
        text: q.content || q.question || q.statement,
        options: questionOptions
      })
    } else if (q.type === 'sentence_completion' || q.type === 'completion') {
      currentSection.questions.push({
        number: (q.number || 0) + questionOffset,
        prefix: q.prefix || q.content || '',
        suffix: q.suffix || ''
      })
    } else if (q.type === 'true_false_ng' || q.type === 'yes_no_ng') {
      const questionText = q.content || q.statement || q.question || q.text

      currentSection.questions.push({
        number: (q.number || 0) + questionOffset,
        text: questionText
      })
      currentSection.options = [
        { value: 'TRUE', text: 'TRUE' },
        { value: 'FALSE', text: 'FALSE' },
        { value: 'NOT GIVEN', text: 'NOT GIVEN' }
      ]
    } else {
      // Default format
      currentSection.questions.push({
        number: (q.number || 0) + questionOffset,
        text: q.content || q.question || q.statement
      })
    }
  })
  
  return sections
}

function getInstructionForType(type) {
  const instructions = {
    'multiple_choice': 'Choose the correct letter, A, B, C or D.',
    'mcq': 'Choose the correct letter, A, B, C or D.',
    'true_false_ng': 'Do the following statements agree with the information in the passage? Write TRUE, FALSE or NOT GIVEN.',
    'yes_no_ng': 'Do the following statements agree with the views of the writer? Write YES, NO or NOT GIVEN.',
    'sentence_completion': 'Complete the sentences below using NO MORE THAN THREE WORDS from the passage.',
    'completion': 'Complete the sentences below using NO MORE THAN THREE WORDS from the passage.',
    'matching': 'Match the information to the correct paragraph.'
  }
  return instructions[type] || 'Answer the questions below.'
}

const noteSearch = ref('')

const filteredNotes = computed(() => {
  if (!noteSearch.value) return notes.value
  return notes.value.filter(note => 
    note.content.toLowerCase().includes(noteSearch.value.toLowerCase()) ||
    note.selectedText.toLowerCase().includes(noteSearch.value.toLowerCase())
  )
})

function scrollToQuestion(num) {
  const element = document.getElementById(`question-${num}`)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

function handleTextSelection(event) {
  // Small delay to ensure selection is complete
  setTimeout(() => {
    const selection = window.getSelection()
    const text = selection.toString().trim()
    
    if (text.length > 0) {
      // Show popup at cursor position
      showSelectionPopup.value = true
      selectedText.value = text
      selectionRange.value = selection.getRangeAt(0).cloneRange()
      
      // Position popup near cursor
      const rect = selection.getRangeAt(0).getBoundingClientRect()
      popupPosition.value = {
        x: rect.left + (rect.width / 2) - 100, // Center popup above selection
        y: rect.top + window.scrollY - 60 // 60px above selection
      }
    } else {
      showSelectionPopup.value = false
    }
  }, 10)
}

function highlightSelected() {
  if (!selectionRange.value) return
  
  try {
    const span = document.createElement('span')
    span.className = 'bg-yellow-200 cursor-pointer transition-colors hover:bg-yellow-300'
    span.setAttribute('data-highlight', 'true')
    span.setAttribute('title', 'Highlighted text')
    
    selectionRange.value.surroundContents(span)
    
    showSelectionPopup.value = false
    window.getSelection().removeAllRanges()
  } catch (error) {
    console.error('Failed to highlight:', error)
  }
}

function addNote() {
  if (!selectedText.value || !selectionRange.value) return
  
  const noteContent = prompt('Nhập ghi chú của bạn:', '')
  if (!noteContent) {
    showSelectionPopup.value = false
    return
  }
  
  try {
    // Create note span with underline
    const span = document.createElement('span')
    span.className = 'border-b-2 border-blue-400 cursor-help transition-colors hover:bg-blue-50'
    span.setAttribute('data-note', 'true')
    span.setAttribute('data-note-id', notes.value.length)
    span.setAttribute('title', noteContent)
    
    selectionRange.value.surroundContents(span)
    
    // Save note
    notes.value.push({
      id: notes.value.length,
      selectedText: selectedText.value,
      content: noteContent,
      timestamp: new Date().toISOString(),
      element: span
    })
    
    // Add click event to scroll to note
    span.addEventListener('click', () => {
      showNotesSidebar.value = true
      // Highlight this note in sidebar
    })
    
    showSelectionPopup.value = false
    showNotesSidebar.value = true
    window.getSelection().removeAllRanges()
  } catch (error) {
    console.error('Failed to add note:', error)
    Swal.fire({
      icon: 'warning',
      title: 'Không thể thêm note',
      text: 'Vui lòng chọn text đơn giản hơn (không chọn qua nhiều elements).',
    })
  }
}

function scrollToNote(note) {
  if (note.element) {
    note.element.scrollIntoView({ behavior: 'smooth', block: 'center' })
    // Flash effect
    note.element.classList.add('bg-blue-100')
    setTimeout(() => {
      note.element.classList.remove('bg-blue-100')
    }, 1000)
  }
}

function deleteNote(index) {
  if (confirm('Xóa note này?')) {
    const note = notes.value[index]
    if (note.element) {
      // Remove underline styling
      const parent = note.element.parentNode
      if (parent) {
        parent.replaceChild(document.createTextNode(note.element.textContent), note.element)
        parent.normalize()
      }
    }
    notes.value.splice(index, 1)
  }
}

// Close popup when clicking outside
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (showSelectionPopup.value && !e.target.closest('.selection-popup')) {
      const selection = window.getSelection()
      if (selection.toString().trim() === '') {
        showSelectionPopup.value = false
      }
    }
  })
  
  // Also close popup when user clicks elsewhere
  document.addEventListener('mousedown', (e) => {
    if (showSelectionPopup.value && !e.target.closest('.selection-popup')) {
      // Check if user is starting a new selection
      setTimeout(() => {
        const selection = window.getSelection()
        if (selection.toString().trim() === '') {
          showSelectionPopup.value = false
        }
      }, 50)
    }
  })
})

function switchPassage(idx) {
  currentPassageIndex.value = idx
}

function getPassageQuestionCount(passageIdx) {
  const passage = passages.value[passageIdx]
  if (!passage || !passage.questionSections) return 0
  
  let count = 0
  passage.questionSections.forEach(section => {
    count += section.questions?.length || 0
  })
  return count
}

function getPassageProgress(passageIdx) {
  const passage = passages.value[passageIdx]
  if (!passage || !passage.questionSections) return 0
  
  let answered = 0
  passage.questionSections.forEach(section => {
    section.questions?.forEach(q => {
      if (answers.value[q.number]) answered++
    })
  })
  return answered
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen()
  } else {
    document.exitFullscreen()
  }
}

async function submitTest() {
  // Prevent double submit
  if (isSubmitting.value) {
    console.log('⚠️ Already submitting, skipping...')
    return
  }
  
  isSubmitting.value = true
  
  // Clear timer immediately to prevent multiple calls
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
  
  try {
    console.log('📤 Submitting Reading test...', {
      test_id: route.params.testId,
      answers_count: Object.keys(answers.value).length
    })
    
    const response = await axios.post(`/api/examination/submissions`, {
      test_id: route.params.testId,
      answers: answers.value,
      time_taken: (testData.value.timeLimit * 60) - timeRemaining.value
    })

    const submissionId = response.data.data.submission_id
    
    console.log('✅ Reading test submitted successfully:', submissionId)

    router.push({
      name: 'examination.ielts-practice.result',
      params: { submissionId: submissionId }
    })
  } catch (error) {
    console.error('❌ Failed to submit test:', error)
    isSubmitting.value = false // Reset flag on error
    Swal.fire('Error', 'Failed to submit test. Please try again.', 'error')
  }
}
</script>

<style scoped>
.question-item {
  scroll-margin-top: 100px;
}

.passage-content {
  user-select: text;
}

.prose p {
  margin-bottom: 1rem;
  line-height: 1.8;
}
</style>

