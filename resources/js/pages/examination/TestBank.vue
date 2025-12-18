<template>
  <div class="test-bank">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <!-- Type Logo -->
        <div v-if="currentType === 'ielts'" class="w-16 h-16 flex-shrink-0">
          <img src="/images/logos/ielts-logo.svg" alt="IELTS" class="w-full h-full object-contain" />
        </div>
        <div v-else-if="currentType === 'cambridge'" class="w-16 h-16 flex-shrink-0">
          <img src="/images/logos/cambridge-logo.svg" alt="Cambridge" class="w-full h-full object-contain" />
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-800">{{ pageTitle }}</h1>
          <p class="text-gray-600">{{ pageSubtitle }}</p>
        </div>
      </div>
      <div class="flex items-center space-x-3">
        <button
          v-if="currentType === 'ielts'"
          @click="showIELTSPromptModal = true"
          class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          {{ t('examination.test_bank.ai_prompt_setup') }}
        </button>

        <router-link
          :to="createTestRoute"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          {{ t('examination.test_bank.create') }}
        </router-link>
      </div>
    </div>

    <!-- Filters -->
    <div v-if="!hasRouteFilters" class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input
          v-model="filters.search"
          type="text"
          :placeholder="t('examination.test_bank.search_placeholder')"
          class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          @input="debouncedSearch"
        />

        <select v-model="filters.type" @change="fetchTests" class="px-4 py-2 border rounded-lg">
          <option value="">{{ t('examination.test_bank.all_types') }}</option>
          <option value="ielts">IELTS</option>
          <option value="cambridge">Cambridge</option>
          <option value="toeic">TOEIC</option>
          <option value="custom">{{ t('examination.test_bank.type_custom') }}</option>
          <option value="quiz">Quiz</option>
        </select>

        <select v-model="filters.status" @change="fetchTests" class="px-4 py-2 border rounded-lg">
          <option value="">{{ t('examination.test_bank.all_status') }}</option>
          <option value="draft">{{ t('examination.status.draft') }}</option>
          <option value="active">{{ t('examination.status.active') }}</option>
          <option value="archived">{{ t('examination.status.archived') }}</option>
        </select>

        <button @click="resetFilters" class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-50">
          {{ t('examination.common.clear_filters') }}
        </button>
      </div>
    </div>

    <!-- Simplified search for specific category pages (IELTS Reading, etc.) -->
    <div v-else class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="flex gap-4">
        <input
          v-model="filters.search"
          type="text"
          :placeholder="t('examination.test_bank.search_placeholder')"
          class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          @input="debouncedSearch"
        />
      </div>
    </div>

    <!-- Tests Grid -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    </div>

    <div v-else-if="tests.length === 0" class="text-center py-12 bg-white rounded-lg">
      <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="mt-4 text-gray-600">{{ t('examination.test_bank.no_tests') }}</p>
      <router-link :to="{ name: 'examination.tests.create' }" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg">
        {{ t('examination.test_bank.create_first') }}
      </router-link>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="test in tests"
        :key="test.id"
        class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-shadow"
      >
        <!-- Card header with type badge -->
        <div class="p-4 border-b bg-gray-50">
          <div class="flex items-start justify-between">
            <span class="px-2 py-1 text-xs font-medium rounded" :class="getTypeClass(test.type)">
              {{ getTypeName(test.type) }}
            </span>
            <span
              class="px-2 py-1 text-xs rounded"
              :class="test.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
            >
              {{ test.status === 'active' ? t('examination.status.active') : test.status === 'draft' ? t('examination.status.draft') : t('examination.status.archived') }}
            </span>
          </div>
          <h3 class="mt-2 font-semibold text-gray-800 line-clamp-1">{{ test.title }}</h3>
        </div>

        <!-- Card body -->
        <div class="p-4">
          <p v-if="test.description" class="text-sm text-gray-600 line-clamp-2 mb-3">
            {{ test.description }}
          </p>

          <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="flex items-center text-gray-600">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ test.test_questions_count || 0 }} {{ t('examination.common.questions') }}
            </div>
            <div v-if="test.time_limit" class="flex items-center text-gray-600">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ test.time_limit }} {{ t('examination.test_bank.minutes') }}
            </div>
            <div v-if="test.passing_score" class="flex items-center text-gray-600">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ t('examination.test_bank.passing_score') }}: {{ test.passing_score }}%
            </div>
            <div class="flex items-center text-gray-600">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              {{ test.max_attempts || 1 }} {{ t('examination.test_bank.attempts') }}
            </div>
          </div>
        </div>

        <!-- Card footer -->
        <div class="px-4 py-3 bg-gray-50 border-t flex justify-between items-center">
          <span class="text-xs text-gray-500">
            {{ formatDate(test.created_at) }}
          </span>
          <div class="flex space-x-2">
            <button @click="openPreview(test)" class="p-1.5 text-green-600 hover:bg-green-50 rounded" :title="t('examination.common.preview')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <router-link
              :to="getEditRoute(test)"
              class="p-1.5 text-blue-600 hover:bg-blue-50 rounded"
              :title="t('examination.common.edit')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </router-link>
            <button @click="duplicateTest(test)" class="p-1.5 text-gray-600 hover:bg-gray-100 rounded" :title="t('examination.common.duplicate')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
            <button @click="deleteTest(test)" class="p-1.5 text-red-600 hover:bg-red-50 rounded" :title="t('examination.common.delete')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.per_page" class="mt-6 flex justify-center">
      <nav class="flex space-x-2">
        <button
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="px-3 py-1 border rounded disabled:opacity-50"
        >
          {{ t('examination.common.previous') }}
        </button>
        <span class="px-3 py-1 text-gray-600">
          {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <button
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-3 py-1 border rounded disabled:opacity-50"
        >
          {{ t('examination.common.next') }}
        </button>
      </nav>
    </div>

    <!-- Preview Modal -->
    <div v-if="showPreview" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-gray-100 w-full h-full overflow-hidden flex flex-col">
        <!-- Preview Header -->
        <div class="bg-white border-b sticky top-0 z-50 shadow-sm">
          <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="bg-gradient-to-br from-green-500 to-emerald-500 p-2 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
              <div>
                <h1 class="font-semibold text-gray-800">{{ previewTest?.title }}</h1>
                <span class="text-xs text-orange-500 font-medium">{{ t('examination.test_bank.preview_mode') }}</span>
              </div>
            </div>

            <div class="flex items-center gap-4">
              <div class="flex items-center gap-2 text-orange-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-semibold">60:00 minutes remaining</span>
              </div>

              <button
                @click="closePreview"
                class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-6 rounded-lg transition-colors flex items-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                {{ t('examination.test_bank.close_preview') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Preview Content -->
        <div class="flex-1 overflow-y-auto">
          <div class="max-w-7xl mx-auto p-6">
            <!-- Loading -->
            <div v-if="previewLoading" class="flex items-center justify-center py-20">
              <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto mb-4"></div>
                <p class="text-gray-600">{{ t('examination.test_bank.loading_preview') }}</p>
              </div>
            </div>

            <!-- Reading Preview -->
            <template v-else-if="previewTest?.subtype === 'reading' && previewPassages.length > 0">
              <!-- Instructions -->
              <div class="bg-cyan-50 border-l-4 border-cyan-500 p-4 mb-6 rounded-r-lg">
                <p class="text-sm text-gray-700">
                  You should spend about <strong>20 minutes</strong> on <strong>Questions {{ getPassageQuestionRange(previewActivePassage) }}</strong>, which is based on
                  <strong>{{ previewPassages[previewActivePassage]?.title || 'Reading Passage' }}</strong> below.
                </p>
              </div>

              <!-- Split Layout -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left: Reading Passage -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                  <!-- Passage Navigation -->
                  <div class="p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                    <div class="flex gap-2">
                      <button
                        v-for="(passage, idx) in previewPassages"
                        :key="idx"
                        @click="previewActivePassage = idx"
                        class="px-4 py-2 rounded-lg font-medium transition-all shadow-sm flex-1 text-sm"
                        :class="previewActivePassage === idx
                          ? 'bg-green-600 text-white'
                          : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                      >
                        Passage {{ idx + 1 }}
                        <span class="ml-1 text-xs opacity-75">({{ getPassageQuestionCount(passage) }} {{ t('examination.common.questions') }})</span>
                      </button>
                    </div>
                  </div>

                  <!-- Passage Header -->
                  <div class="p-4 border-b border-gray-200">
                    <div class="bg-gradient-to-r from-green-50 to-cyan-50 p-4 rounded-lg flex items-center gap-4">
                      <div class="flex-shrink-0">
                        <svg class="w-12 h-12 text-green-600" viewBox="0 0 200 200" fill="none">
                          <rect x="40" y="20" width="120" height="160" rx="8" fill="currentColor" opacity="0.1"/>
                          <rect x="60" y="40" width="80" height="4" rx="2" fill="currentColor" opacity="0.3"/>
                          <rect x="60" y="60" width="60" height="4" rx="2" fill="currentColor" opacity="0.3"/>
                          <circle cx="100" cy="120" r="30" fill="currentColor" opacity="0.2"/>
                        </svg>
                      </div>
                      <div>
                        <h2 class="text-lg font-bold text-gray-800">{{ previewPassages[previewActivePassage]?.title }}</h2>
                      </div>
                    </div>
                  </div>

                  <!-- Passage Text -->
                  <div class="p-6 overflow-y-auto" style="max-height: 60vh;">
                    <div class="prose max-w-none text-gray-700 leading-relaxed" v-html="previewPassages[previewActivePassage]?.content || '<p>No content available</p>'"></div>
                  </div>
                </div>

                <!-- Right: Questions -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                  <div class="p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                    <div class="flex items-center justify-between mb-2">
                      <h3 class="font-semibold text-gray-800">{{ t('examination.test_bank.questions') }}</h3>
                      <span class="text-sm text-gray-500">
                        {{ getPassageQuestionCount(previewPassages[previewActivePassage]) }} {{ t('examination.common.questions') }}
                      </span>
                    </div>

                    <!-- Question Numbers -->
                    <div class="flex flex-wrap gap-1">
                      <template v-if="previewPassages[previewActivePassage]?.questionGroups?.length">
                        <template v-for="group in previewPassages[previewActivePassage].questionGroups" :key="group.startNumber">
                          <span
                            v-for="q in group.questions"
                            :key="q.number"
                            class="w-7 h-7 flex items-center justify-center rounded text-xs font-medium bg-gray-100 text-gray-600"
                          >
                            {{ q.number }}
                          </span>
                        </template>
                      </template>
                      <template v-else>
                        <span
                          v-for="(q, idx) in (previewPassages[previewActivePassage]?.questions || [])"
                          :key="idx"
                          class="w-7 h-7 flex items-center justify-center rounded text-xs font-medium bg-gray-100 text-gray-600"
                        >
                          {{ q.number || idx + 1 }}
                        </span>
                      </template>
                    </div>
                  </div>

                  <!-- Questions List -->
                  <div class="p-6 overflow-y-auto" style="max-height: 60vh;">
                    <!-- Question Groups Display -->
                    <div v-if="previewPassages[previewActivePassage]?.questionGroups?.length" class="space-y-8">
                      <div
                        v-for="(group, gIdx) in previewPassages[previewActivePassage].questionGroups"
                        :key="gIdx"
                        class="border rounded-lg overflow-hidden"
                      >
                        <!-- Group Instruction -->
                        <div class="bg-blue-50 border-b border-blue-100 p-4">
                          <div class="prose prose-sm max-w-none text-gray-800" v-html="generateGroupInstructionHtml(group, previewPassages[previewActivePassage])"></div>
                        </div>

                        <!-- Questions in Group -->
                        <div class="p-4 space-y-4">
                          <div
                            v-for="q in group.questions"
                            :key="q.number"
                            class="border-b pb-4 last:border-b-0"
                          >
                            <div class="flex items-start gap-3">
                              <span class="font-bold text-blue-600 min-w-[30px]">{{ q.number }}.</span>
                              <div class="flex-1">
                                <!-- Question content based on type -->
                                <p v-if="group.type === 'true_false_ng' || group.type === 'yes_no_ng'" class="mb-3 text-gray-700">
                                  {{ q.statement }}
                                </p>
                                <p v-else-if="group.type === 'sentence_completion' || group.type === 'note_completion'" class="mb-3 text-gray-700">
                                  {{ q.sentence || q.note }} <span class="inline-block w-32 border-b-2 border-gray-400"></span>
                                </p>
                                <p v-else-if="group.type === 'matching_headings'" class="mb-3 text-gray-700">
                                  Paragraph <strong>{{ q.paragraphRef }}</strong>
                                </p>
                                <p v-else-if="group.type === 'matching_features' || group.type === 'matching_information'" class="mb-3 text-gray-700">
                                  {{ q.statement || q.information }}
                                </p>
                                <p v-else-if="group.type === 'multiple_choice'" class="mb-3 text-gray-700">
                                  {{ q.question }}
                                </p>
                                <p v-else class="mb-3 text-gray-700">
                                  {{ q.question || q.statement || q.sentence || '' }}
                                </p>

                                <!-- Multiple Choice Options -->
                                <div v-if="group.type === 'multiple_choice' && q.options" class="space-y-2 mb-3">
                                  <div
                                    v-for="option in q.options"
                                    :key="option.label"
                                    class="flex items-start gap-2 p-2 rounded"
                                    :class="q.answer === option.label ? 'bg-green-50 border border-green-200' : ''"
                                  >
                                    <span class="font-semibold text-gray-700">{{ option.label }}</span>
                                    <span class="text-gray-700">{{ option.content }}</span>
                                  </div>
                                </div>

                                <!-- True/False/NG Buttons -->
                                <div v-else-if="group.type === 'true_false_ng' || group.type === 'yes_no_ng'" class="flex gap-2 mb-3">
                                  <span
                                    class="px-3 py-1.5 border rounded-lg text-sm"
                                    :class="q.answer === (group.type === 'yes_no_ng' ? 'YES' : 'TRUE') ? 'bg-green-100 border-green-400 text-green-700' : 'bg-gray-50'"
                                  >{{ group.type === 'yes_no_ng' ? 'YES' : 'TRUE' }}</span>
                                  <span
                                    class="px-3 py-1.5 border rounded-lg text-sm"
                                    :class="q.answer === (group.type === 'yes_no_ng' ? 'NO' : 'FALSE') ? 'bg-green-100 border-green-400 text-green-700' : 'bg-gray-50'"
                                  >{{ group.type === 'yes_no_ng' ? 'NO' : 'FALSE' }}</span>
                                  <span
                                    class="px-3 py-1.5 border rounded-lg text-sm"
                                    :class="q.answer === 'NOT GIVEN' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-gray-50'"
                                  >NOT GIVEN</span>
                                </div>

                                <!-- Answer Input for Completion/Short Answer -->
                                <div v-else-if="['sentence_completion', 'note_completion', 'summary_completion', 'short_answer'].includes(group.type)" class="mb-3">
                                  <input
                                    type="text"
                                    disabled
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                    placeholder="Enter your answer..."
                                  />
                                </div>

                                <!-- Correct Answer -->
                                <div class="text-sm text-green-600 bg-green-50 px-3 py-1.5 rounded-lg inline-block">
                                  <span class="font-medium">{{ t('examination.test_bank.answer') }}:</span> {{ q.answer || 'N/A' }}
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Fallback: Legacy Individual Questions Display -->
                    <div v-else class="space-y-6">
                      <div
                        v-for="(question, qIdx) in (previewPassages[previewActivePassage]?.questions || [])"
                        :key="qIdx"
                        class="border-b pb-4 last:border-b-0"
                      >
                        <div class="flex items-start gap-3">
                          <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number || qIdx + 1 }}.</span>
                          <div class="flex-1">
                            <p class="mb-3 text-gray-700">{{ question.question || question.content || question.statement || '' }}</p>

                            <!-- Multiple Choice -->
                            <div v-if="question.type === 'multiple_choice' && question.options" class="space-y-2">
                              <label
                                v-for="option in question.options"
                                :key="option.label"
                                class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"
                                :class="question.answer === option.label || question.correctAnswer === option.label ? 'bg-green-50 border-green-300' : ''"
                              >
                                <input type="radio" disabled class="mt-1 text-green-600" />
                                <div>
                                  <span class="font-semibold text-gray-700">{{ option.label }}</span>
                                  <span class="text-gray-700 ml-2">{{ option.content }}</span>
                                </div>
                              </label>
                            </div>

                            <!-- True/False/NG -->
                            <div v-else-if="question.type === 'true_false_ng' || question.type === 'yes_no_ng'" class="flex gap-2">
                              <span
                                class="px-3 py-1.5 border rounded-lg text-sm"
                                :class="(question.answer || question.correctAnswer) === (question.type === 'yes_no_ng' ? 'YES' : 'TRUE') ? 'bg-green-100 border-green-400' : 'bg-gray-50'"
                              >{{ question.type === 'yes_no_ng' ? 'YES' : 'TRUE' }}</span>
                              <span
                                class="px-3 py-1.5 border rounded-lg text-sm"
                                :class="(question.answer || question.correctAnswer) === (question.type === 'yes_no_ng' ? 'NO' : 'FALSE') ? 'bg-green-100 border-green-400' : 'bg-gray-50'"
                              >{{ question.type === 'yes_no_ng' ? 'NO' : 'FALSE' }}</span>
                              <span
                                class="px-3 py-1.5 border rounded-lg text-sm"
                                :class="(question.answer || question.correctAnswer) === 'NOT GIVEN' ? 'bg-green-100 border-green-400' : 'bg-gray-50'"
                              >NOT GIVEN</span>
                            </div>

                            <!-- Completion / Short Answer -->
                            <div v-else class="mt-2">
                              <input
                                type="text"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                placeholder="Enter your answer..."
                              />
                            </div>

                            <!-- Correct Answer (Preview mode) -->
                            <div class="mt-2 text-sm text-green-600 bg-green-50 px-3 py-1.5 rounded-lg inline-block">
                              <span class="font-medium">{{ t('examination.test_bank.answer') }}:</span> {{ question.answer || question.correctAnswer || 'N/A' }}
                            </div>
                          </div>
                        </div>
                      </div>

                      <div v-if="(previewPassages[previewActivePassage]?.questions || []).length === 0 && !(previewPassages[previewActivePassage]?.questionGroups?.length)" class="text-center py-8 text-gray-400">
                        <p>{{ t('examination.test_bank.no_questions') }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Navigation -->
                  <div class="p-4 border-t border-gray-200 flex justify-center gap-4">
                    <button
                      v-if="previewActivePassage > 0"
                      @click="previewActivePassage--"
                      class="p-2 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors"
                    >
                      <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                      </svg>
                    </button>
                    <button
                      v-if="previewActivePassage < previewPassages.length - 1"
                      @click="previewActivePassage++"
                      class="p-2 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors"
                    >
                      <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </template>

            <!-- Listening Preview -->
            <template v-else-if="previewTest?.subtype === 'listening' && previewParts.length > 0">
              <!-- Instructions -->
              <div class="bg-cyan-50 border-l-4 border-cyan-500 p-4 mb-6 rounded-r-lg">
                <p class="text-sm text-gray-700">
                  You will hear <strong>{{ previewParts.length }} recordings</strong>. Listen carefully and answer
                  <strong>Questions {{ getPartQuestionRange(previewActivePart) }}</strong> based on <strong>{{ previewParts[previewActivePart]?.title || 'Part ' + (previewActivePart + 1) }}</strong>.
                </p>
              </div>

              <!-- Split Layout -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left: Audio & Transcript -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                  <!-- Part Navigation -->
                  <div class="p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                    <div class="flex gap-2">
                      <button
                        v-for="(part, idx) in previewParts"
                        :key="idx"
                        @click="previewActivePart = idx"
                        class="px-4 py-2 rounded-lg font-medium transition-all shadow-sm flex-1 text-sm"
                        :class="previewActivePart === idx
                          ? 'bg-purple-600 text-white'
                          : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                      >
                        Part {{ idx + 1 }}
                        <span class="ml-1 text-xs opacity-75">({{ getPartQuestionCount(part) }} {{ t('examination.common.questions') }})</span>
                      </button>
                    </div>
                  </div>

                  <!-- Part Header -->
                  <div class="p-4 border-b border-gray-200">
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-4 rounded-lg flex items-center gap-4">
                      <div class="flex-shrink-0">
                        <svg class="w-12 h-12 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                        </svg>
                      </div>
                      <div>
                        <h2 class="text-lg font-bold text-gray-800">{{ previewParts[previewActivePart]?.title || 'Part ' + (previewActivePart + 1) }}</h2>
                        <p class="text-sm text-gray-500">Questions {{ getPartQuestionRange(previewActivePart) }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Audio Player -->
                  <div class="p-4 border-b border-gray-200">
                    <div class="bg-gray-50 rounded-lg p-4">
                      <div class="flex items-center gap-4">
                        <button class="w-12 h-12 bg-purple-600 hover:bg-purple-700 text-white rounded-full flex items-center justify-center transition-colors">
                          <svg class="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                          </svg>
                        </button>
                        <div class="flex-1">
                          <div class="h-2 bg-gray-200 rounded-full">
                            <div class="h-2 bg-purple-600 rounded-full" style="width: 0%"></div>
                          </div>
                          <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>0:00</span>
                            <span>{{ previewParts[previewActivePart]?.audio ? 'Audio available' : 'No audio' }}</span>
                          </div>
                        </div>
                      </div>
                      <p v-if="!previewParts[previewActivePart]?.audio" class="text-sm text-gray-400 text-center mt-2">
                        {{ t('examination.test_bank.no_audio') }}
                      </p>
                    </div>
                  </div>

                  <!-- Transcript Section (Collapsible) -->
                  <div class="border-b border-gray-200">
                    <button
                      @click="showTranscript = !showTranscript"
                      class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors"
                    >
                      <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="font-medium text-gray-700">Transcript</span>
                        <span v-if="previewParts[previewActivePart]?.transcript" class="text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded">
                          Available
                        </span>
                        <span v-else class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">
                          Not available
                        </span>
                      </div>
                      <svg
                        class="w-5 h-5 text-gray-400 transform transition-transform"
                        :class="{ 'rotate-180': showTranscript }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>

                    <!-- Transcript Content -->
                    <div
                      v-if="showTranscript"
                      class="p-4 bg-amber-50 border-t border-amber-100 max-h-96 overflow-y-auto"
                    >
                      <div v-if="previewParts[previewActivePart]?.transcript" class="prose prose-sm max-w-none">
                        <div v-html="previewParts[previewActivePart].transcript"></div>
                      </div>
                      <div v-else class="text-center py-8 text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p>{{ t('examination.test_bank.no_transcript') }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Tip Box -->
                  <div class="p-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                      <p class="text-sm text-blue-700">
                        <strong>Tip:</strong> In the real test, you will hear each recording ONCE only. Read the questions before listening.
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Right: Questions -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                  <div class="p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                    <div class="flex items-center justify-between mb-2">
                      <h3 class="font-semibold text-gray-800">{{ t('examination.test_bank.questions') }}</h3>
                      <span class="text-sm text-gray-500">
                        {{ getPartQuestionCount(previewParts[previewActivePart]) }} {{ t('examination.common.questions') }}
                      </span>
                    </div>

                    <!-- Question Numbers -->
                    <div class="flex flex-wrap gap-1">
                      <template v-if="previewParts[previewActivePart]?.questionGroups?.length">
                        <template v-for="group in previewParts[previewActivePart].questionGroups" :key="group.startNumber">
                          <span
                            v-for="q in group.questions"
                            :key="q.number"
                            class="w-7 h-7 flex items-center justify-center rounded text-xs font-medium bg-gray-100 text-gray-600"
                          >
                            {{ q.number }}
                          </span>
                        </template>
                      </template>
                      <template v-else>
                        <span
                          v-for="(q, idx) in (previewParts[previewActivePart]?.questions || [])"
                          :key="idx"
                          class="w-7 h-7 flex items-center justify-center rounded text-xs font-medium bg-gray-100 text-gray-600"
                        >
                          {{ q.number || idx + 1 }}
                        </span>
                      </template>
                    </div>
                  </div>

                  <!-- Questions List -->
                  <div class="p-6 overflow-y-auto" style="max-height: 60vh;">
                    <!-- Question Groups Display -->
                    <div v-if="previewParts[previewActivePart]?.questionGroups?.length" class="space-y-8">
                      <div
                        v-for="(group, gIdx) in previewParts[previewActivePart].questionGroups"
                        :key="gIdx"
                        class="border rounded-lg overflow-hidden"
                      >
                        <!-- Group Instruction -->
                        <div class="bg-purple-50 border-b border-purple-100 p-4">
                          <div class="prose prose-sm max-w-none text-gray-800" v-html="generateListeningGroupInstructionHtml(group)"></div>
                        </div>

                        <!-- Questions in Group -->
                        <div class="p-4 space-y-4">
                          <div
                            v-for="q in group.questions"
                            :key="q.number"
                            class="border-b pb-4 last:border-b-0"
                          >
                            <div class="flex items-start gap-3">
                              <span class="font-bold text-purple-600 min-w-[30px]">{{ q.number }}.</span>
                              <div class="flex-1">
                                <!-- Question content based on type -->
                                <p v-if="group.type === 'multiple_choice'" class="mb-3 text-gray-700">
                                  {{ q.content || q.question }}
                                </p>
                                <p v-else-if="['fill_blanks', 'sentence_completion', 'note_completion', 'table_completion'].includes(group.type)" class="mb-3 text-gray-700">
                                  {{ q.content }} <span class="inline-block w-32 border-b-2 border-gray-400"></span>
                                </p>
                                <p v-else class="mb-3 text-gray-700">
                                  {{ q.content || q.question || '' }}
                                </p>

                                <!-- Multiple Choice Options -->
                                <div v-if="group.type === 'multiple_choice' && q.options" class="space-y-2 mb-3">
                                  <div
                                    v-for="option in q.options"
                                    :key="option.label"
                                    class="flex items-start gap-2 p-2 rounded"
                                    :class="q.answer === option.label ? 'bg-green-50 border border-green-200' : ''"
                                  >
                                    <span class="font-semibold text-gray-700">{{ option.label }}</span>
                                    <span class="text-gray-700">{{ option.content }}</span>
                                  </div>
                                </div>

                                <!-- Answer Input for Completion/Short Answer -->
                                <div v-else-if="['fill_blanks', 'sentence_completion', 'note_completion', 'table_completion', 'short_answer'].includes(group.type)" class="mb-3">
                                  <input
                                    type="text"
                                    disabled
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                    placeholder="Enter your answer..."
                                  />
                                </div>

                                <!-- Correct Answer -->
                                <div class="text-sm text-green-600 bg-green-50 px-3 py-1.5 rounded-lg inline-block">
                                  <span class="font-medium">{{ t('examination.test_bank.answer') }}:</span> {{ q.answer || 'N/A' }}
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Fallback: Legacy Individual Questions Display -->
                    <div v-else class="space-y-6">
                      <div
                        v-for="(question, qIdx) in (previewParts[previewActivePart]?.questions || [])"
                        :key="qIdx"
                        class="border-b pb-4 last:border-b-0"
                      >
                        <div class="flex items-start gap-3">
                          <span class="font-semibold text-gray-700 min-w-[30px]">{{ question.number || qIdx + 1 }}.</span>
                          <div class="flex-1">
                            <p class="mb-3 text-gray-700">{{ question.question || question.content || '' }}</p>

                            <!-- Multiple Choice -->
                            <div v-if="question.type === 'multiple_choice' && question.options" class="space-y-2">
                              <label
                                v-for="option in question.options"
                                :key="option.label"
                                class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"
                                :class="question.answer === option.label || question.correctAnswer === option.label ? 'bg-green-50 border-green-300' : ''"
                              >
                                <input type="radio" disabled class="mt-1 text-purple-600" />
                                <div>
                                  <span class="font-semibold text-gray-700">{{ option.label }}</span>
                                  <span class="text-gray-700 ml-2">{{ option.content }}</span>
                                </div>
                              </label>
                            </div>

                            <!-- Completion / Short Answer -->
                            <div v-else class="mt-2">
                              <input
                                type="text"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                placeholder="Enter your answer..."
                              />
                            </div>

                            <!-- Correct Answer (Preview mode) -->
                            <div class="mt-2 text-sm text-green-600 bg-green-50 px-3 py-1.5 rounded-lg inline-block">
                              <span class="font-medium">{{ t('examination.test_bank.answer') }}:</span> {{ question.answer || question.correctAnswer || 'N/A' }}
                            </div>
                          </div>
                        </div>
                      </div>

                      <div v-if="(previewParts[previewActivePart]?.questions || []).length === 0 && !(previewParts[previewActivePart]?.questionGroups?.length)" class="text-center py-8 text-gray-400">
                        <p>{{ t('examination.test_bank.no_questions') }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Navigation -->
                  <div class="p-4 border-t border-gray-200 flex justify-center gap-4">
                    <button
                      v-if="previewActivePart > 0"
                      @click="previewActivePart--"
                      class="p-2 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors"
                    >
                      <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                      </svg>
                    </button>
                    <button
                      v-if="previewActivePart < previewParts.length - 1"
                      @click="previewActivePart++"
                      class="p-2 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors"
                    >
                      <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </template>

            <!-- Writing Preview -->
            <template v-else-if="previewTest?.subtype === 'writing' && previewTasks.length > 0">
              <!-- Instructions -->
              <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded-r-lg">
                <p class="text-sm text-gray-700">
                  <strong>IELTS Academic Writing</strong> - 60 minutes for both tasks.
                  Task 1: Describe visual information (150+ words, 20 mins).
                  Task 2: Essay writing (250+ words, 40 mins).
                </p>
              </div>

              <!-- Task Tabs -->
              <div class="flex gap-2 mb-6">
                <button
                  v-for="task in previewTasks"
                  :key="task.id"
                  @click="previewActiveTask = task.id"
                  class="px-6 py-3 rounded-lg font-medium transition-all shadow-sm"
                  :class="previewActiveTask === task.id
                    ? 'bg-orange-600 text-white'
                    : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                >
                  Task {{ task.id }}
                  <span class="ml-2 text-xs opacity-75">
                    ({{ task.id === 1 ? '150+ words' : '250+ words' }})
                  </span>
                </button>
              </div>

              <!-- Active Task Content -->
              <div
                v-for="task in previewTasks"
                :key="task.id"
                v-show="previewActiveTask === task.id"
                class="bg-white rounded-xl shadow-lg overflow-hidden"
              >
                <!-- Task Header -->
                <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-amber-50">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-500 text-white rounded-lg flex items-center justify-center font-bold text-xl">
                      {{ task.id }}
                    </div>
                    <div>
                      <h2 class="text-lg font-bold text-gray-800">{{ task.title || 'Task ' + task.id }}</h2>
                      <div class="flex items-center gap-3 text-sm text-gray-600">
                        <span class="flex items-center gap-1">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          {{ task.timeLimit || (task.id === 1 ? 20 : 40) }} {{ t('examination.test_bank.time_limit') }}
                        </span>
                        <span class="flex items-center gap-1">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                          {{ t('examination.test_bank.min_words', { count: task.minWords || (task.id === 1 ? 150 : 250) }) }}
                        </span>
                        <span v-if="task.visualType" class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs">
                          {{ getVisualTypeLabel(task.visualType) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Task Content -->
                <div class="p-6">
                  <!-- Task 1: Image Display -->
                  <div v-if="task.id === 1 && task.imageUrl" class="mb-6">
                    <div class="border rounded-lg overflow-hidden bg-gray-50 p-4">
                      <img
                        :src="task.imageUrl"
                        :alt="'Task 1 - ' + (task.visualType || 'Visual')"
                        class="max-w-full max-h-96 mx-auto rounded-lg shadow-sm"
                      />
                      <p v-if="task.imageSource" class="text-xs text-gray-400 text-center mt-2">
                        Source: {{ task.imageSource === 'ai_generated' ? 'AI Generated' : task.imageSource }}
                      </p>
                    </div>
                  </div>

                  <!-- Task Prompt -->
                  <div class="mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                      <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      {{ t('examination.test_bank.task') }}
                    </h3>
                    <div class="prose prose-sm max-w-none bg-gray-50 p-4 rounded-lg border" v-html="task.prompt || `<p class='text-gray-400'>${t('examination.test_bank.no_prompt')}</p>`"></div>
                  </div>

                  <!-- Answer Area (Preview Mode - Disabled) -->
                  <div class="mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                      <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                      {{ t('examination.test_bank.student_work') }}
                    </h3>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 text-center">
                      <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                      <p class="text-gray-400">{{ t('examination.test_bank.work_area') }}</p>
                      <p class="text-xs text-gray-400 mt-1">Word count: 0 / {{ task.minWords || (task.id === 1 ? 150 : 250) }}</p>
                    </div>
                  </div>

                  <!-- Scoring Criteria -->
                  <div v-if="task.criteria?.length" class="mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                      <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      {{ t('examination.test_bank.grading_criteria') }}
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                      <div
                        v-for="criterion in task.criteria"
                        :key="criterion"
                        class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg"
                      >
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-sm text-gray-700">{{ getCriterionLabel(criterion) }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Sample Answer (if available) -->
                  <div v-if="task.sampleAnswer" class="border-t pt-6">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                      <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                      </svg>
                      {{ t('examination.test_bank.sample_essay') }}
                    </h3>
                    <div class="prose prose-sm max-w-none bg-purple-50 p-4 rounded-lg border border-purple-200" v-html="task.sampleAnswer"></div>
                  </div>
                </div>

                <!-- Task Navigation -->
                <div class="p-4 border-t border-gray-200 flex justify-center gap-4">
                  <button
                    v-if="previewActiveTask > 1"
                    @click="previewActiveTask--"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center gap-2"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Task {{ previewActiveTask - 1 }}
                  </button>
                  <button
                    v-if="previewActiveTask < previewTasks.length"
                    @click="previewActiveTask++"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center gap-2"
                  >
                    Task {{ previewActiveTask + 1 }}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>
                </div>
              </div>
            </template>

            <!-- Speaking Preview (placeholder) -->
            <template v-else-if="previewTest">
              <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ previewTest.title }}</h3>
                <p class="text-gray-600 mb-4">{{ t('examination.test_bank.test_type') }}: {{ getTypeName(previewTest.type) }} - {{ previewTest.subtype?.toUpperCase() }}</p>
                <p class="text-sm text-gray-500">{{ t('examination.test_bank.preview_developing') }}</p>
              </div>
            </template>

            <!-- No Data -->
            <template v-else>
              <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                <p class="text-gray-500">{{ t('examination.test_bank.no_data') }}</p>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- IELTS Prompt Modal -->
    <div v-if="showIELTSPromptModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-purple-600 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="bg-white/20 p-2 rounded-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-white">{{ t('examination.test_bank.ai_prompt_setup') }} - IELTS Generation</h3>
              <p class="text-sm text-purple-100">{{ t('examination.test_bank.ai_prompt_desc') }}</p>
            </div>
          </div>
          <button @click="showIELTSPromptModal = false" class="text-white hover:bg-white/10 p-2 rounded-lg transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
          <!-- Tab Navigation -->
          <div class="flex border-b border-gray-200 mb-6">
            <button
              v-for="skill in ['listening', 'reading', 'writing', 'speaking']"
              :key="skill"
              @click="activeIELTSTab = skill"
              :class="[
                'px-6 py-3 font-medium text-sm focus:outline-none transition-colors',
                activeIELTSTab === skill
                  ? 'border-b-2 border-purple-600 text-purple-600'
                  : 'text-gray-500 hover:text-gray-700'
              ]"
            >
              {{ skill.charAt(0).toUpperCase() + skill.slice(1) }}
            </button>
          </div>

          <!-- Tab Content -->
          <div class="space-y-4">
            <div v-show="activeIELTSTab === 'listening'">
              <label class="block text-sm font-medium text-gray-700 mb-2">Prompt AI - Listening</label>
              <textarea
                v-model="ieltsListeningPrompt"
                rows="15"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono text-sm"
                :placeholder="t('examination.test_bank.enter_prompt_listening')"
              ></textarea>
              <p class="mt-2 text-xs text-gray-500">
                {{ t('examination.test_bank.prompt_help_listening') }}
              </p>
            </div>

            <div v-show="activeIELTSTab === 'reading'">
              <label class="block text-sm font-medium text-gray-700 mb-2">Prompt AI - Reading</label>
              <textarea
                v-model="ieltsReadingPrompt"
                rows="15"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono text-sm"
                :placeholder="t('examination.test_bank.enter_prompt_reading')"
              ></textarea>
              <p class="mt-2 text-xs text-gray-500">
                {{ t('examination.test_bank.prompt_help_reading') }}
              </p>
            </div>

            <div v-show="activeIELTSTab === 'writing'">
              <label class="block text-sm font-medium text-gray-700 mb-2">Prompt AI - Writing</label>
              <textarea
                v-model="ieltsWritingPrompt"
                rows="15"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono text-sm"
                :placeholder="t('examination.test_bank.enter_prompt_writing')"
              ></textarea>
              <p class="mt-2 text-xs text-gray-500">
                {{ t('examination.test_bank.prompt_help_writing') }}
              </p>
            </div>

            <div v-show="activeIELTSTab === 'speaking'">
              <label class="block text-sm font-medium text-gray-700 mb-2">Prompt AI - Speaking</label>
              <textarea
                v-model="ieltsSpeakingPrompt"
                rows="15"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono text-sm"
                :placeholder="t('examination.test_bank.enter_prompt_speaking')"
              ></textarea>
              <p class="mt-2 text-xs text-gray-500">
                {{ t('examination.test_bank.prompt_help_speaking') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
          <button
            @click="restoreDefaultIELTSPrompt(activeIELTSTab)"
            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ t('examination.test_bank.restore_default') }} ({{ activeIELTSTab.charAt(0).toUpperCase() + activeIELTSTab.slice(1) }})
          </button>

          <div class="flex gap-3">
            <button
              @click="showIELTSPromptModal = false"
              class="px-6 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ t('examination.common.cancel') }}
            </button>
            <button
              @click="saveIELTSPrompt(activeIELTSTab)"
              :disabled="savingIELTSPrompt"
              class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <svg v-if="savingIELTSPrompt" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ savingIELTSPrompt ? t('examination.common.saving') : t('examination.common.save') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useTestStore } from '@/stores/examination'
import { useI18n } from '@/composables/useI18n'
import api from '@/api'
import Swal from 'sweetalert2'

const route = useRoute()
const store = useTestStore()
const { t } = useI18n()

const filters = ref({
  search: '',
  type: '',
  status: '',
  skill: '',
  level: ''
})

// Preview State
const showPreview = ref(false)
const previewTest = ref(null)
const previewLoading = ref(false)
const previewPassages = ref([])
const previewActivePassage = ref(0)
// Listening preview state
const previewParts = ref([])
const previewActivePart = ref(0)
const showTranscript = ref(false)
// Writing preview state
const previewTasks = ref([])
const previewActiveTask = ref(1)

// IELTS Prompt Modal State
const showIELTSPromptModal = ref(false)
const activeIELTSTab = ref('listening')
const ieltsListeningPrompt = ref('')
const ieltsReadingPrompt = ref('')
const ieltsWritingPrompt = ref('')
const ieltsSpeakingPrompt = ref('')
const savingIELTSPrompt = ref(false)

// Default Prompts - SHORT INSTRUCTIONS ONLY (JSON formats handled by backend automatically)
const DEFAULT_IELTS_LISTENING_PROMPT = `You are an expert IELTS Listening test creator. Generate authentic IELTS Listening test content following official Cambridge IELTS standards.

GUIDELINES:
1. Follow IELTS Listening format (4 parts, 40 questions total)
2. Include diverse question types: form completion, multiple choice, matching, labeling, sentence completion
3. Answers must be directly from the transcript
4. Create natural, realistic conversations and monologues
5. Use proper British English spelling and expressions`

const DEFAULT_IELTS_READING_PROMPT = `You are an expert IELTS Reading test creator. Generate authentic IELTS Reading passage with questions following official Cambridge IELTS standards.

GUIDELINES:
1. Passage should be 800-1000 words, academic style
2. Include diverse question types: True/False/NG, Multiple Choice, Matching Headings, Sentence Completion, etc.
3. For matching_headings: provide MORE headings than questions (distractors)
4. Total 13-14 questions per passage
5. Use proper British English spelling and academic vocabulary`

const DEFAULT_IELTS_WRITING_PROMPT = `You are an expert IELTS Writing test creator. Generate authentic IELTS Writing tasks following official Cambridge IELTS standards.

GUIDELINES:
1. Task 1: 150 words minimum (graph, chart, table, diagram, or process)
2. Task 2: 250 words minimum (opinion, discussion, problem-solution, or two-part question)
3. Include band descriptors for assessment criteria
4. Use proper British English spelling and academic style`

const DEFAULT_IELTS_SPEAKING_PROMPT = `You are an expert IELTS Speaking test creator. Generate authentic IELTS Speaking test with all 3 parts following official Cambridge IELTS standards.

GUIDELINES:
1. Part 1: 4-5 familiar topics with 4-5 questions each (Introduction and Interview)
2. Part 2: Clear cue card with 1 minute prep, 1-2 minutes speaking (Individual Long Turn)
3. Part 3: Abstract discussion questions related to Part 2 topic (Two-way Discussion)
4. Include examiner name for personalization
5. Use proper British English and natural, realistic IELTS-style questions`

let searchTimeout = null

const tests = computed(() => store.tests)
const loading = computed(() => store.loading)
const pagination = computed(() => store.pagination)

// Current type and skill/level from route query
const currentType = computed(() => route.query.type || filters.value.type || '')
const currentSkill = computed(() => route.query.skill || '')
const currentLevel = computed(() => route.query.level || '')

// Check if we're in a specific category page (has type from route)
// When type is set via route, show simplified search only
const hasRouteFilters = computed(() => {
  return !!route.query.type
})

// Route for creating a new test based on type
const createTestRoute = computed(() => {
  const type = currentType.value
  const skill = currentSkill.value
  const level = currentLevel.value

  // IELTS uses dedicated IELTS Test Builder
  if (type === 'ielts') {
    return {
      name: 'examination.ielts.create',
      query: skill ? { skill } : {}
    }
  }

  // Other types use generic test builder
  const query = {}
  if (type) query.type = type
  if (type === 'cambridge' && level) query.level = level

  return {
    name: 'examination.tests.create',
    query
  }
})

// Route for editing a test - IELTS vs Generic
function getEditRoute(test) {
  if (test.type === 'ielts') {
    return {
      name: 'examination.ielts.edit',
      params: { id: test.id }
    }
  }
  return {
    name: 'examination.tests.edit',
    params: { id: test.id }
  }
}

// Skill/Level labels
const skillLabels = {
  listening: 'Listening',
  reading: 'Reading',
  writing: 'Writing',
  speaking: 'Speaking'
}

const levelLabels = {
  starters: 'Starters',
  movers: 'Movers',
  flyers: 'Flyers'
}

// Dynamic page title based on type and skill/level
const pageTitle = computed(() => {
  const type = currentType.value
  const skill = currentSkill.value
  const level = currentLevel.value

  if (type === 'ielts' && skill) {
    return `IELTS ${skillLabels[skill] || skill}`
  }
  if (type === 'cambridge' && level) {
    return `Cambridge ${levelLabels[level] || level}`
  }

  const titles = {
    ielts: 'Ngân hàng đề IELTS',
    cambridge: 'Ngân hàng đề Cambridge',
    toeic: 'Ngân hàng đề TOEIC',
    custom: 'Bài test tự tạo',
    quiz: 'Ngân hàng Quiz',
    practice: 'Bài luyện tập'
  }
  return titles[type] || 'Ngân hàng bài test'
})

const pageSubtitle = computed(() => {
  const type = currentType.value
  const skill = currentSkill.value
  const level = currentLevel.value

  if (type === 'ielts' && skill) {
    const skillDescriptions = {
      listening: 'Luyện nghe với các bài thi IELTS Listening thực tế',
      reading: 'Luyện đọc hiểu với các passage IELTS Academic & General',
      writing: 'Luyện viết Task 1 & Task 2 theo chuẩn IELTS',
      speaking: 'Luyện nói với các topic Speaking Part 1, 2, 3'
    }
    return skillDescriptions[skill] || `Đề thi IELTS ${skillLabels[skill]}`
  }
  if (type === 'cambridge' && level) {
    const levelDescriptions = {
      starters: 'Đề thi Cambridge cho trẻ em mới bắt đầu học tiếng Anh',
      movers: 'Đề thi Cambridge cho trẻ em trình độ trung bình',
      flyers: 'Đề thi Cambridge cho trẻ em trình độ nâng cao'
    }
    return levelDescriptions[level] || `Đề thi Cambridge ${levelLabels[level]}`
  }

  const subtitles = {
    ielts: 'Quản lý đề thi IELTS - Listening, Reading, Writing, Speaking',
    cambridge: 'Quản lý đề thi Cambridge - Starters, Movers, Flyers',
    toeic: 'Quản lý đề thi TOEIC',
    custom: 'Các bài test do giáo viên tạo',
    quiz: 'Các bài quiz kiểm tra nhanh',
    practice: 'Bài tập luyện tập'
  }
  return subtitles[type] || 'Quản lý các bài kiểm tra và đề thi'
})

// Watch route query changes
watch(() => route.query, (newQuery) => {
  filters.value.type = newQuery.type || ''
  filters.value.skill = newQuery.skill || ''
  filters.value.level = newQuery.level || ''
  fetchTests()
}, { immediate: false })

onMounted(() => {
  // Initialize filters from route query
  filters.value.type = route.query.type || ''
  filters.value.skill = route.query.skill || ''
  filters.value.level = route.query.level || ''
  fetchTests()
})

// Preview functions
async function openPreview(test) {
  showPreview.value = true
  previewLoading.value = true
  previewTest.value = test
  previewActivePassage.value = 0
  previewPassages.value = []
  previewActivePart.value = 0
  previewParts.value = []
  showTranscript.value = false
  previewTasks.value = []
  previewActiveTask.value = 1

  try {
    // Fetch full test data using the store's fetchTest method
    const response = await store.fetchTest(test.id)
    if (response) {
      previewTest.value = response

      // Extract passages from settings (Reading)
      if (response.settings?.passages) {
        previewPassages.value = response.settings.passages
      } else if (response.passages) {
        previewPassages.value = response.passages
      }

      // Extract parts from settings (Listening)
      if (response.settings?.parts) {
        previewParts.value = response.settings.parts
      } else if (response.parts) {
        previewParts.value = response.parts
      }

      // Extract tasks from settings (Writing)
      if (response.settings?.tasks) {
        previewTasks.value = response.settings.tasks
      } else if (response.tasks) {
        previewTasks.value = response.tasks
      }
    }
  } catch (error) {
    console.error('Error loading preview:', error)
  } finally {
    previewLoading.value = false
  }
}

function closePreview() {
  showPreview.value = false
  previewTest.value = null
  previewPassages.value = []
  previewActivePassage.value = 0
  previewParts.value = []
  previewActivePart.value = 0
  showTranscript.value = false
  previewTasks.value = []
  previewActiveTask.value = 1
}

function getPassageQuestionRange(passageIndex) {
  if (!previewPassages.value || previewPassages.value.length === 0) return '1-13'

  let startNum = 1
  for (let i = 0; i < passageIndex; i++) {
    const p = previewPassages.value[i]
    if (p?.questionGroups?.length) {
      for (const g of p.questionGroups) {
        startNum += (g.questions || []).length
      }
    } else {
      startNum += (p?.questions || []).length
    }
  }

  const passage = previewPassages.value[passageIndex]
  let passageQuestionCount = 0
  if (passage?.questionGroups?.length) {
    for (const g of passage.questionGroups) {
      passageQuestionCount += (g.questions || []).length
    }
  } else {
    passageQuestionCount = (passage?.questions || []).length
  }

  const endNum = startNum + passageQuestionCount - 1
  return passageQuestionCount > 0 ? `${startNum}-${endNum}` : 'N/A'
}

// IELTS Question Group Instruction Templates
const questionGroupTemplates = {
  matching_headings: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>The reading passage has {{paragraphCount}} paragraphs, <strong>{{paragraphLabels}}</strong>.</p>
<p>Choose the correct heading for each paragraph from the list of headings below.</p>
<p>Write the correct number, <strong>i-{{headingCount}}</strong>, in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`,
    listHeader: '<p class="mt-3 font-semibold">List of Headings</p>'
  },
  true_false_ng: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Do the following statements agree with the information given in the reading passage?</p>
<p>In boxes {{startNum}}-{{endNum}} on your answer sheet, write</p>
<p class="ml-4"><strong>TRUE</strong> if the statement agrees with the information</p>
<p class="ml-4"><strong>FALSE</strong> if the statement contradicts the information</p>
<p class="ml-4"><strong>NOT GIVEN</strong> if there is no information on this</p>`
  },
  yes_no_ng: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Do the following statements agree with the views/claims of the writer in the reading passage?</p>
<p>In boxes {{startNum}}-{{endNum}} on your answer sheet, write</p>
<p class="ml-4"><strong>YES</strong> if the statement agrees with the views/claims of the writer</p>
<p class="ml-4"><strong>NO</strong> if the statement contradicts the views/claims of the writer</p>
<p class="ml-4"><strong>NOT GIVEN</strong> if it is impossible to say what the writer thinks about this</p>`
  },
  multiple_choice: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Choose the correct letter, <strong>A</strong>, <strong>B</strong>, <strong>C</strong> or <strong>D</strong>.</p>
<p>Write the correct letter in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`
  },
  sentence_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the sentences below.</p>
<p>Choose <strong>NO MORE THAN {{wordLimit}} WORDS</strong> from the passage for each answer.</p>
<p>Write your answers in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`
  },
  summary_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the summary below.</p>
<p>Choose <strong>NO MORE THAN {{wordLimit}} WORDS</strong> from the passage for each answer.</p>
<p>Write your answers in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`
  },
  note_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the notes below.</p>
<p>Choose <strong>NO MORE THAN {{wordLimit}} WORDS</strong> from the passage for each answer.</p>
<p>Write your answers in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`
  },
  matching_features: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Look at the following statements (Questions {{startNum}}-{{endNum}}) and the list of {{featureType}} below.</p>
<p>Match each statement with the correct {{featureTypeSingular}}, <strong>{{optionLabels}}</strong>.</p>
<p>Write the correct letter in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>
<p><em>NB You may use any letter more than once.</em></p>`,
    listHeader: '<p class="mt-3 font-semibold">List of {{featureType}}</p>'
  },
  matching_information: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>The reading passage has {{paragraphCount}} paragraphs, <strong>{{paragraphLabels}}</strong>.</p>
<p>Which paragraph contains the following information?</p>
<p>Write the correct letter, <strong>{{paragraphLabels}}</strong>, in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>
<p><em>NB You may use any letter more than once.</em></p>`
  },
  short_answer: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Answer the questions below.</p>
<p>Choose <strong>NO MORE THAN {{wordLimit}} WORDS</strong> from the passage for each answer.</p>
<p>Write your answers in boxes {{startNum}}-{{endNum}} on your answer sheet.</p>`
  }
}

// Generate formatted instruction HTML for a question group
function generateGroupInstructionHtml(group, passage) {
  // If custom instruction is saved from user editing, use it directly
  if (group.instruction) {
    return group.instruction
  }

  const template = questionGroupTemplates[group.type]
  if (!template) {
    return `<p><strong>Questions ${group.startNumber}-${group.endNumber}</strong></p>`
  }

  // Count paragraphs in passage
  const paragraphMatches = passage?.content?.match(/<p><strong>[A-Z]<\/strong>/g) || []
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

  // Add list of headings if applicable
  if (group.headings?.length && template.listHeader) {
    instruction += template.listHeader
    for (const heading of group.headings) {
      instruction += `<p class="ml-4"><strong>${heading.numeral}</strong> ${heading.text}</p>`
    }
  }

  // Add list of features if applicable
  if (group.features?.length && template.listHeader) {
    instruction += template.listHeader.replace('{{featureType}}', group.featureType || 'Items')
    for (const feature of group.features) {
      instruction += `<p class="ml-4"><strong>${feature.label}</strong> ${feature.text}</p>`
    }
  }

  return instruction
}

// Convert number to roman numeral
function toRoman(num) {
  const romans = ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x', 'xi', 'xii']
  return romans[num - 1] || num
}

// Get option labels like "A-E" or "i-vi"
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

// Get total question count for a passage (supports both questionGroups and questions)
function getPassageQuestionCount(passage) {
  if (!passage) return 0
  if (passage.questionGroups?.length) {
    let count = 0
    for (const g of passage.questionGroups) {
      count += (g.questions || []).length
    }
    return count
  }
  return (passage.questions || []).length
}

// Listening Preview Helper Functions
function getPartQuestionRange(partIndex) {
  if (!previewParts.value || previewParts.value.length === 0) return '1-10'

  let startNum = 1
  for (let i = 0; i < partIndex; i++) {
    const p = previewParts.value[i]
    if (p?.questionGroups?.length) {
      for (const g of p.questionGroups) {
        startNum += (g.questions || []).length
      }
    } else {
      startNum += (p?.questions || []).length
    }
  }

  const part = previewParts.value[partIndex]
  let partQuestionCount = 0
  if (part?.questionGroups?.length) {
    for (const g of part.questionGroups) {
      partQuestionCount += (g.questions || []).length
    }
  } else {
    partQuestionCount = (part?.questions || []).length
  }

  const endNum = startNum + partQuestionCount - 1
  return partQuestionCount > 0 ? `${startNum}-${endNum}` : 'N/A'
}

function getPartQuestionCount(part) {
  if (!part) return 0
  if (part.questionGroups?.length) {
    let count = 0
    for (const g of part.questionGroups) {
      count += (g.questions || []).length
    }
    return count
  }
  return (part.questions || []).length
}

// Listening Question Group Instruction Templates
const listeningQuestionGroupTemplates = {
  multiple_choice: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Choose the correct letter, <strong>A</strong>, <strong>B</strong> or <strong>C</strong>.</p>`
  },
  fill_blanks: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the notes below.</p>
<p>Write <strong>NO MORE THAN {{wordLimit}} WORDS AND/OR A NUMBER</strong> for each answer.</p>`
  },
  sentence_completion: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Complete the sentences below.</p>
<p>Write <strong>NO MORE THAN {{wordLimit}} WORDS AND/OR A NUMBER</strong> for each answer.</p>`
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
<p>What does the speaker say about each of the following?</p>
<p>Choose <strong>{{optionCount}}</strong> answers from the box and write the correct letter, <strong>{{optionLabels}}</strong>, next to Questions {{startNum}}-{{endNum}}.</p>`
  },
  labeling: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Label the {{diagramType}} below.</p>
<p>Write the correct letter, <strong>A-{{lastLabel}}</strong>, next to Questions {{startNum}}-{{endNum}}.</p>`
  },
  short_answer: {
    instruction: `<p><strong>Questions {{startNum}}-{{endNum}}</strong></p>
<p>Answer the questions below.</p>
<p>Write <strong>NO MORE THAN {{wordLimit}} WORDS AND/OR A NUMBER</strong> for each answer.</p>`
  }
}

// Generate formatted instruction HTML for a listening question group
function generateListeningGroupInstructionHtml(group) {
  // If custom instruction is saved from user editing, use it directly
  if (group.instruction) {
    return group.instruction
  }

  const template = listeningQuestionGroupTemplates[group.type]
  if (!template) {
    return `<p><strong>Questions ${group.startNumber}-${group.endNumber}</strong></p>`
  }

  let instruction = template.instruction
    .replace(/\{\{startNum\}\}/g, group.startNumber)
    .replace(/\{\{endNum\}\}/g, group.endNumber)
    .replace(/\{\{wordLimit\}\}/g, group.wordLimit || 3)
    .replace(/\{\{optionCount\}\}/g, group.options?.length || 5)
    .replace(/\{\{optionLabels\}\}/g, getListeningOptionLabels(group))
    .replace(/\{\{diagramType\}\}/g, group.diagramType || 'diagram')
    .replace(/\{\{lastLabel\}\}/g, String.fromCharCode(64 + (group.labels?.length || 5)))

  return instruction
}

function getListeningOptionLabels(group) {
  if (group.options?.length) {
    const count = group.options.length
    return count <= 3 ? group.options.map((_, i) => String.fromCharCode(65 + i)).join(', ') : `A-${String.fromCharCode(64 + count)}`
  }
  return 'A-E'
}

// Writing Preview Helper Functions
function getVisualTypeLabel(visualType) {
  const labels = {
    bar_chart: 'Bar Chart',
    line_graph: 'Line Graph',
    pie_chart: 'Pie Chart',
    table: 'Table',
    map: 'Map',
    process: 'Process Diagram'
  }
  return labels[visualType] || visualType
}

function getCriterionLabel(criterion) {
  const labels = {
    task_achievement: 'Task Achievement',
    coherence_cohesion: 'Coherence & Cohesion',
    lexical_resource: 'Lexical Resource',
    grammar_accuracy: 'Grammar Range & Accuracy'
  }
  return labels[criterion] || criterion
}

async function fetchTests(page = 1) {
  const params = {
    search: filters.value.search,
    type: filters.value.type,
    status: filters.value.status,
    skill: filters.value.skill,
    level: filters.value.level,
    page
  }
  await store.fetchTests(params)
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchTests(), 300)
}

function resetFilters() {
  filters.value = { search: '', type: '', status: '' }
  fetchTests()
}

function changePage(page) {
  fetchTests(page)
}

async function duplicateTest(test) {
  if (confirm(t('examination.messages.duplicate_test_confirm'))) {
    // Implement duplicate
    fetchTests()
  }
}

async function deleteTest(test) {
  if (confirm(t('examination.messages.delete_test_confirm'))) {
    try {
      const response = await store.deleteTest(test.id)

      if (response.success) {
        // Success notification
        alert(response.message || 'Đề thi đã được xóa thành công')
        fetchTests()
      } else {
        // Error notification
        alert(response.message || 'Có lỗi xảy ra khi xóa đề thi')
      }
    } catch (error) {
      console.error('Error deleting test:', error)
      alert(error.response?.data?.message || 'Có lỗi xảy ra khi xóa đề thi')
    }
  }
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('vi-VN')
}

function getTypeName(type) {
  const names = {
    ielts: 'IELTS',
    cambridge: 'Cambridge',
    toeic: 'TOEIC',
    custom: 'Tự tạo',
    quiz: 'Quiz',
    practice: 'Luyện tập'
  }
  return names[type] || type
}

function getTypeClass(type) {
  const classes = {
    ielts: 'bg-purple-100 text-purple-800',
    cambridge: 'bg-blue-100 text-blue-800',
    toeic: 'bg-green-100 text-green-800',
    custom: 'bg-gray-100 text-gray-800',
    quiz: 'bg-yellow-100 text-yellow-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

// IELTS Prompt Functions
// IELTS Prompt Functions - Xử lý 4 kỹ năng riêng biệt
const skillModuleMap = {
  listening: 'prompt_ielts_listening',
  reading: 'prompt_ielts_reading',
  writing: 'prompt_ielts_writing',
  speaking: 'prompt_ielts_speaking'
}

const skillDefaultMap = {
  listening: DEFAULT_IELTS_LISTENING_PROMPT,
  reading: DEFAULT_IELTS_READING_PROMPT,
  writing: DEFAULT_IELTS_WRITING_PROMPT,
  speaking: DEFAULT_IELTS_SPEAKING_PROMPT
}

const skillPromptRefs = {
  listening: ieltsListeningPrompt,
  reading: ieltsReadingPrompt,
  writing: ieltsWritingPrompt,
  speaking: ieltsSpeakingPrompt
}

async function loadIELTSPrompt() {
  // Load tất cả 4 prompts
  for (const skill of ['listening', 'reading', 'writing', 'speaking']) {
    try {
      const response = await api.get('/examination/ai-prompts', {
        params: { module: skillModuleMap[skill] }
      })
      if (response.data.success && response.data.data) {
        skillPromptRefs[skill].value = response.data.data.prompt || skillDefaultMap[skill]
      } else {
        skillPromptRefs[skill].value = skillDefaultMap[skill]
      }
    } catch (error) {
      console.error(`Error loading ${skill} prompt:`, error)
      skillPromptRefs[skill].value = skillDefaultMap[skill]
    }
  }
}

async function saveIELTSPrompt(skill) {
  savingIELTSPrompt.value = true
  try {
    const response = await api.post('/examination/ai-prompts', {
      module: skillModuleMap[skill],
      prompt: skillPromptRefs[skill].value
    })

    if (response.data.success) {
      const skillName = skill.charAt(0).toUpperCase() + skill.slice(1)
      await Swal.fire('Thành công', `Prompt ${skillName} đã được lưu!`, 'success')
    }
  } catch (error) {
    console.error(`Error saving ${skill} prompt:`, error)
    Swal.fire('Lỗi', 'Có lỗi xảy ra khi lưu prompt!', 'error')
  } finally {
    savingIELTSPrompt.value = false
  }
}

async function restoreDefaultIELTSPrompt(skill) {
  const skillName = skill.charAt(0).toUpperCase() + skill.slice(1)
  const result = await Swal.fire({
    title: `Khôi phục prompt mặc định cho ${skillName}?`,
    text: 'Prompt hiện tại sẽ bị thay thế bằng prompt mặc định.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#9333ea',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Khôi phục',
    cancelButtonText: 'Hủy'
  })

  if (result.isConfirmed) {
    skillPromptRefs[skill].value = skillDefaultMap[skill]
    await Swal.fire('Đã khôi phục', 'Prompt mặc định đã được khôi phục. Nhớ bấm Lưu để lưu thay đổi.', 'success')
  }
}

onMounted(() => {
  loadIELTSPrompt()
})
</script>
