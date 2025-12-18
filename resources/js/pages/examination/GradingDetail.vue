<template>
  <div class="grading-detail">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <router-link :to="{ name: 'examination.grading' }" class="text-blue-600 hover:underline text-sm mb-2 inline-block">
          &larr; Quay lại danh sách
        </router-link>
        <h1 class="text-2xl font-bold text-gray-800">Chi tiết bài làm</h1>
        <p class="text-gray-600">Xem và chấm điểm bài làm</p>
      </div>
      <div class="flex items-center space-x-3">
        <!-- AI Model Indicator - only show for Writing/Speaking -->
        <div v-if="!isReadingOrListening" class="flex items-center space-x-2">
          <!-- Current AI Model Badge -->
          <div class="flex items-center px-3 py-1.5 bg-gray-100 rounded-lg text-sm">
            <span class="text-gray-500 mr-2">AI:</span>
            <span class="font-medium" :class="getProviderClass(aiSettings.provider)">
              {{ getModelDisplayName() }}
            </span>
            <span v-if="!aiSettings.hasApiKey && aiSettings.provider !== 'azure'" class="ml-2 text-xs text-red-500">
              <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </span>
            <span v-else-if="aiSettings.hasApiKey && aiSettings.provider !== 'azure'" class="ml-2 text-xs text-green-600">
              <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </span>
            <span v-else-if="!aiSettings.azureKey && aiSettings.provider === 'azure'" class="ml-2 text-xs text-red-500">
              <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
          <!-- AI Settings button -->
          <button
            @click="showAISettings = true"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Thiết lập AI
          </button>
        </div>
        <span class="px-3 py-1 rounded-full text-sm" :class="getStatusClass(submission?.status)">
          {{ getStatusName(submission?.status) }}
        </span>
      </div>
    </div>

    <!-- Full Test Tabs -->
    <div v-if="isFullTest && !loading" class="mb-6">
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="flex border-b">
          <button
            v-for="skill in ['listening', 'reading', 'writing', 'speaking']"
            :key="skill"
            @click="switchToTab(skill)"
            class="flex-1 px-6 py-4 text-center font-medium transition-colors relative"
            :class="{
              'bg-blue-50 text-blue-700 border-b-2 border-blue-600': activeTab === skill,
              'text-gray-600 hover:bg-gray-50': activeTab !== skill
            }"
          >
            <div class="flex items-center justify-center space-x-2">
              <span>{{ getTabBadge(skill) }}</span>
              <span
                v-if="getTabStatus(skill) === 'graded'"
                class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700"
              >
                ✓
              </span>
              <span
                v-else-if="getTabStatus(skill) === 'grading'"
                class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700"
              >
                ...
              </span>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    </div>

    <template v-else-if="submission">
      <!-- Student & Test Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold text-gray-800 mb-4">Thông tin học viên</h3>
          <div class="flex items-center mb-4">
            <img
              :src="submission.user?.avatar || '/images/default-avatar.png'"
              class="w-12 h-12 rounded-full mr-4"
              :alt="submission.user?.name"
            />
            <div>
              <div class="font-medium text-gray-900">{{ submission.user?.name }}</div>
              <div class="text-sm text-gray-500">{{ submission.user?.email }}</div>
            </div>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Thời gian nộp:</span>
              <span>{{ formatDate(submission.submitted_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Thời gian làm bài:</span>
              <span>{{ formatDuration(submission.started_at, submission.submitted_at) }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold text-gray-800 mb-4">Thông tin bài test</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Tên bài:</span>
              <span class="font-medium">{{ submission.assignment?.test?.title }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Loại:</span>
              <span>{{ getTypeName(submission.assignment?.test?.type) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Kỹ năng:</span>
              <span class="px-2 py-0.5 rounded-full text-xs" :class="getSkillClass(submission.assignment?.test?.subtype)">
                {{ getSkillName(submission.assignment?.test?.subtype) }}
              </span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t">
              <span class="text-gray-500">Band Score:</span>
              <span class="font-bold text-2xl text-blue-600">
                <template v-if="submission.band_score !== null && submission.band_score !== undefined">
                  {{ submission.band_score }}
                </template>
                <template v-else>-</template>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Auto-grading Panel for Reading/Listening -->
      <div v-if="isReadingOrListening" class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg mb-6 text-white">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-xl font-bold mb-2">Chấm điểm tự động ({{ getSkillName(submission?.assignment?.test?.subtype) }})</h3>
              <p class="text-blue-100 text-sm">Điểm được tính dựa trên số câu trả lời đúng theo thang điểm IELTS chuẩn</p>
            </div>
            <div class="text-right">
              <div class="text-5xl font-bold">{{ calculatedBandScore }}</div>
              <div class="text-blue-200 text-sm">Band Score</div>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-3 gap-4">
            <div class="bg-white/20 rounded-lg p-4 text-center">
              <div class="text-3xl font-bold">{{ correctAnswerCount }}</div>
              <div class="text-blue-100 text-sm">Số câu đúng</div>
            </div>
            <div class="bg-white/20 rounded-lg p-4 text-center">
              <div class="text-3xl font-bold">{{ totalQuestionCount - correctAnswerCount }}</div>
              <div class="text-blue-100 text-sm">Số câu sai</div>
            </div>
            <div class="bg-white/20 rounded-lg p-4 text-center">
              <div class="text-3xl font-bold">{{ totalQuestionCount }}</div>
              <div class="text-blue-100 text-sm">Tổng số câu</div>
            </div>
          </div>

          <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-blue-100">
              <span class="font-medium">Tỷ lệ đúng:</span> {{ totalQuestionCount > 0 ? ((correctAnswerCount / totalQuestionCount) * 100).toFixed(1) : '0.0' }}%
            </div>
            <button
              @click="applyCalculatedBandScore"
              class="px-6 py-2 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50 transition"
            >
              Áp dụng Band Score này
            </button>
          </div>
        </div>

        <!-- Score Conversion Reference Table -->
        <div class="bg-white/10 px-6 py-4 border-t border-white/20">
          <details>
            <summary class="cursor-pointer text-sm font-medium text-blue-100 hover:text-white">
              Xem bảng quy đổi điểm IELTS
            </summary>
            <div class="mt-3 grid grid-cols-6 gap-2 text-xs">
              <div v-for="item in scoreTable" :key="item.raw" class="bg-white/20 rounded p-2 text-center" :class="{ 'ring-2 ring-yellow-300': isCurrentScore(item) }">
                <div class="font-bold">{{ item.band }}</div>
                <div class="text-blue-200">{{ item.raw }}</div>
              </div>
            </div>
          </details>
        </div>
      </div>

      <!-- Answers Section -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
          <h3 class="font-semibold text-gray-800">Câu trả lời</h3>
        </div>

        <div class="divide-y divide-gray-200">
          <div v-for="(answer, index) in submission.answers" :key="answer.id" class="p-6">
            <!-- Question Header -->
            <div class="flex items-start justify-between mb-6">
              <div class="flex items-start flex-1 min-w-0">
                <span class="w-10 h-10 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-sm font-bold mr-4 flex-shrink-0">
                  {{ index + 1 }}
                </span>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-gray-800 text-lg mb-1 line-clamp-2" v-html="getQuestionText(answer.question)"></div>
                  <div class="text-sm text-gray-500">Loại: {{ getQuestionTypeName(answer.question?.type) }}</div>
                </div>
              </div>
              <div class="flex items-center space-x-2 flex-shrink-0 ml-4">
                <span v-if="answer.is_correct === true" class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  Đúng
                </span>
                <span v-else-if="answer.is_correct === false" class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                  Sai
                </span>
                <span v-else class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">
                  Chưa chấm
                </span>
              </div>
            </div>

            <!-- Answer Content -->
            <div class="ml-14 space-y-4">
              <!-- Task Question/Prompt (for Writing with image) -->
              <div v-if="answer.question?.imageUrl || answer.question?.content" class="mb-3 p-3 bg-gray-50 rounded">
                <div class="text-sm text-gray-600 mb-1">Đề bài:</div>
                
                <!-- Image (for Writing Task 1 - charts/diagrams) -->
                <div v-if="answer.question?.imageUrl" class="mb-3">
                  <img 
                    :src="answer.question.imageUrl" 
                    :alt="answer.question.visualType || 'Task Visual'" 
                    class="max-w-full h-auto rounded-lg shadow-md border border-gray-200"
                  />
                  <p v-if="answer.question?.imageSource" class="text-xs text-gray-500 mt-2 italic">
                    Source: {{ answer.question.imageSource }}
                  </p>
                </div>
                
                <!-- Prompt Text -->
                <div class="text-gray-800" v-html="answer.question?.content || answer.question?.text"></div>
              </div>

              <!-- Student Answer -->
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500 mb-2">Câu trả lời của học viên:</div>

                <!-- Audio Answer (Speaking Test) -->
                <div v-if="answer.audio_file_path" class="space-y-3">
                  <div class="flex items-center space-x-3 bg-white rounded-lg p-3">
                    <span class="text-blue-600 font-medium">
                      🎤 Part {{ answer.answer?.part || 'N/A' }} - Question {{ answer.answer?.question_index || 'N/A' }}
                    </span>
                    <span v-if="answer.audio_duration" class="text-sm text-gray-500">
                      ({{ Math.floor(answer.audio_duration / 60) }}:{{ String(answer.audio_duration % 60).padStart(2, '0') }})
                    </span>
                  </div>
                  <audio controls class="w-full">
                    <source :src="`/storage/${answer.audio_file_path}`" type="audio/wav" />
                    <source :src="`/storage/${answer.audio_file_path}`" type="audio/webm" />
                    <source :src="`/storage/${answer.audio_file_path}`" type="audio/mp3" />
                    Trình duyệt không hỗ trợ phát audio.
                  </audio>
                  <a
                    :href="`/storage/${answer.audio_file_path}`"
                    download
                    class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800"
                  >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Tải xuống file ghi âm
                  </a>
                </div>

                <!-- Text Answer -->
                <div v-else class="text-gray-800 whitespace-pre-wrap">
                  <!-- For Writing/Speaking with text_answer, show that instead of JSON answer -->
                  <template v-if="answer.text_answer">
                    {{ answer.text_answer }}
                  </template>
                  <template v-else>
                    {{ formatAnswer(answer.answer) }}
                  </template>
                </div>
              </div>

              <!-- Correct Answer (for objective questions) -->
              <div v-if="answer.question?.correct_answer && !isSubjectiveQuestion(answer.question?.type, answer.question?.correct_answer)" class="bg-green-50 rounded-lg p-4">
                <div class="text-sm font-medium text-green-700 mb-2">Đáp án đúng:</div>
                <div class="text-green-800 font-medium">{{ answer.question.correct_answer }}</div>
              </div>

              <!-- Auto-Compare Result & Manual Override (for objective questions) -->
              <div v-if="answer.question?.correct_answer && !isSubjectiveQuestion(answer.question?.type, answer.question?.correct_answer)" class="border-2 rounded-lg p-4" :class="getCompareClass(answer)">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center">
                    <svg v-if="isAnswerCorrect(answer)" class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span class="font-medium" :class="isAnswerCorrect(answer) ? 'text-green-700' : 'text-red-700'">
                      {{ getCompareMessage(answer) }}
                    </span>
                  </div>
                  <div class="flex space-x-2">
                    <button
                      @click="markAnswer(answer, true)"
                      :disabled="markingAnswerId === answer.id"
                      class="px-3 py-1 text-sm rounded-lg border transition"
                      :class="answer.is_correct === true 
                        ? 'bg-green-600 text-white border-green-600' 
                        : 'bg-white text-green-600 border-green-300 hover:bg-green-50'"
                    >
                      ✓ Đúng
                    </button>
                    <button
                      @click="markAnswer(answer, false)"
                      :disabled="markingAnswerId === answer.id"
                      class="px-3 py-1 text-sm rounded-lg border transition"
                      :class="answer.is_correct === false 
                        ? 'bg-red-600 text-white border-red-600' 
                        : 'bg-white text-red-600 border-red-300 hover:bg-red-50'"
                    >
                      ✗ Sai
                    </button>
                  </div>
                </div>
                <div class="text-xs text-gray-500">
                  💡 So sánh tự động. Bạn có thể thay đổi quyết định bằng các nút bên trên.
                </div>
              </div>

              <!-- Manual Grading for Subjective Questions (Writing/Speaking only) -->
              <div v-if="(isSubjectiveQuestion(answer.question?.type, answer.question?.correct_answer) || answer.audio_file_path) && !isReadingOrListening" class="bg-blue-50 rounded-lg p-5 mt-4">
                <h4 class="font-medium text-blue-800 mb-4">Chấm điểm</h4>

                <!-- IELTS Band Score -->
                <div class="mb-5">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Band Score (0-9)</label>
                  <div class="flex items-center space-x-3">
                    <select
                      v-model.number="answer.band_score"
                      class="w-32 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-lg font-bold"
                    >
                      <option :value="null">-</option>
                      <option v-for="band in bandScores" :key="band" :value="band">{{ band }}</option>
                    </select>
                    <div class="flex space-x-1">
                      <button
                        v-for="band in [5, 5.5, 6, 6.5, 7, 7.5, 8]"
                        :key="band"
                        @click="answer.band_score = band"
                        class="px-3 py-1 text-sm rounded-lg border hover:bg-blue-100"
                        :class="answer.band_score === band ? 'bg-blue-600 text-white border-blue-600' : 'bg-white'"
                      >
                        {{ band }}
                      </button>
                    </div>
                  </div>
                </div>

                <!-- IELTS Grading Criteria (if exists) -->
                <div v-if="answer.grading_criteria" class="mb-5 space-y-3">
                  <h5 class="text-sm font-medium text-gray-700">Điểm chi tiết theo tiêu chí IELTS</h5>

                  <!-- Speaking Criteria -->
                  <template v-if="answer.audio_file_path">
                    <div v-if="answer.grading_criteria.fluency_coherence" class="bg-white rounded-lg p-4 border-l-4 border-purple-500">
                      <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-800">Fluency & Coherence</span>
                        <input
                          v-model.number="answer.grading_criteria.fluency_coherence.score"
                          type="number"
                          step="0.5"
                          min="0"
                          max="9"
                          class="w-20 px-2 py-1 text-center border rounded bg-purple-50 font-bold"
                        />
                      </div>
                      <textarea
                        v-model="answer.grading_criteria.fluency_coherence.feedback"
                        rows="3"
                        class="w-full text-sm text-gray-600 border rounded px-2 py-1 mt-2"
                        placeholder="Nhận xét Fluency & Coherence..."
                      ></textarea>
                    </div>
                    <div v-if="answer.grading_criteria.lexical_resource" class="bg-white rounded-lg p-4 border-l-4 border-blue-500">
                      <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-800">Lexical Resource</span>
                        <input
                          v-model.number="answer.grading_criteria.lexical_resource.score"
                          type="number"
                          step="0.5"
                          min="0"
                          max="9"
                          class="w-20 px-2 py-1 text-center border rounded bg-blue-50 font-bold"
                        />
                      </div>
                      <textarea
                        v-model="answer.grading_criteria.lexical_resource.feedback"
                        rows="3"
                        class="w-full text-sm text-gray-600 border rounded px-2 py-1 mt-2"
                        placeholder="Nhận xét Lexical Resource..."
                      ></textarea>
                    </div>
                    <div v-if="answer.grading_criteria.grammatical_range" class="bg-white rounded-lg p-4 border-l-4 border-green-500">
                      <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-800">Grammatical Range & Accuracy</span>
                        <input
                          v-model.number="answer.grading_criteria.grammatical_range.score"
                          type="number"
                          step="0.5"
                          min="0"
                          max="9"
                          class="w-20 px-2 py-1 text-center border rounded bg-green-50 font-bold"
                        />
                      </div>
                      <textarea
                        v-model="answer.grading_criteria.grammatical_range.feedback"
                        rows="3"
                        class="w-full text-sm text-gray-600 border rounded px-2 py-1 mt-2"
                        placeholder="Nhận xét Grammatical Range & Accuracy..."
                      ></textarea>
                    </div>
                    <div v-if="answer.grading_criteria.pronunciation" class="bg-white rounded-lg p-4 border-l-4 border-orange-500">
                      <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-800">Pronunciation</span>
                        <input
                          v-model.number="answer.grading_criteria.pronunciation.score"
                          type="number"
                          step="0.5"
                          min="0"
                          max="9"
                          class="w-20 px-2 py-1 text-center border rounded bg-orange-50 font-bold"
                        />
                      </div>
                      <textarea
                        v-model="answer.grading_criteria.pronunciation.feedback"
                        rows="3"
                        class="w-full text-sm text-gray-600 border rounded px-2 py-1 mt-2"
                        placeholder="Nhận xét Pronunciation..."
                      ></textarea>
                    </div>
                  </template>
                  
                  <!-- Writing Criteria -->
                  <template v-else>
                    <div v-if="answer.grading_criteria.task_achievement" class="bg-white rounded-lg p-4 border-l-4 border-purple-500">
                      <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-800">Task Achievement</span>
                        <input
                          v-model.number="answer.grading_criteria.task_achievement.score"
                          type="number"
                          step="0.5"
                          min="0"
                          max="9"
                          class="w-20 px-2 py-1 text-center border rounded bg-purple-50 font-bold"
                        />
                      </div>
                      <textarea
                        v-model="answer.grading_criteria.task_achievement.feedback"
                        rows="3"
                        class="w-full text-sm text-gray-600 border rounded px-2 py-1 mt-2"
                        placeholder="Nhận xét Task Achievement..."
                      ></textarea>
                    </div>
                    <div v-if="answer.grading_criteria.coherence_cohesion" class="bg-white rounded-lg p-4 border-l-4 border-blue-500">
                      <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-800">Coherence & Cohesion</span>
                        <input
                          v-model.number="answer.grading_criteria.coherence_cohesion.score"
                          type="number"
                          step="0.5"
                          min="0"
                          max="9"
                          class="w-20 px-2 py-1 text-center border rounded bg-blue-50 font-bold"
                        />
                      </div>
                      <textarea
                        v-model="answer.grading_criteria.coherence_cohesion.feedback"
                        rows="3"
                        class="w-full text-sm text-gray-600 border rounded px-2 py-1 mt-2"
                        placeholder="Nhận xét Coherence & Cohesion..."
                      ></textarea>
                    </div>
                    <div v-if="answer.grading_criteria.lexical_resource" class="bg-white rounded-lg p-4 border-l-4 border-green-500">
                      <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-800">Lexical Resource</span>
                        <input
                          v-model.number="answer.grading_criteria.lexical_resource.score"
                          type="number"
                          step="0.5"
                          min="0"
                          max="9"
                          class="w-20 px-2 py-1 text-center border rounded bg-green-50 font-bold"
                        />
                      </div>
                      <textarea
                        v-model="answer.grading_criteria.lexical_resource.feedback"
                        rows="3"
                        class="w-full text-sm text-gray-600 border rounded px-2 py-1 mt-2"
                        placeholder="Nhận xét Lexical Resource..."
                      ></textarea>
                    </div>
                    <div v-if="answer.grading_criteria.grammatical_range" class="bg-white rounded-lg p-4 border-l-4 border-orange-500">
                      <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-800">Grammatical Range & Accuracy</span>
                        <input
                          v-model.number="answer.grading_criteria.grammatical_range.score"
                          type="number"
                          step="0.5"
                          min="0"
                          max="9"
                          class="w-20 px-2 py-1 text-center border rounded bg-orange-50 font-bold"
                        />
                      </div>
                      <textarea
                        v-model="answer.grading_criteria.grammatical_range.feedback"
                        rows="3"
                        class="w-full text-sm text-gray-600 border rounded px-2 py-1 mt-2"
                        placeholder="Nhận xét Grammatical Range & Accuracy..."
                      ></textarea>
                    </div>
                  </template>
                </div>

                <!-- AI Feedback (Overall) -->
                <div v-if="answer.ai_feedback" class="mb-4 bg-purple-50 border border-purple-200 rounded-lg p-4">
                  <div class="flex items-center mb-2">
                    <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <label class="text-sm font-medium text-purple-900">Nhận xét tổng quát từ AI</label>
                  </div>
                  <div class="text-sm text-gray-700 whitespace-pre-line">{{ answer.ai_feedback }}</div>
                </div>

                <!-- Feedback -->
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Nhận xét tổng quát của giáo viên</label>
                  <textarea
                    v-model="answer.feedback"
                    rows="4"
                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Nhập nhận xét bổ sung của giáo viên (nếu có)..."
                  ></textarea>
                  <p class="text-xs text-gray-500 mt-1">💡 Nhận xét từ AI sẽ được hiển thị ở trên. Bạn có thể thêm nhận xét bổ sung tại đây.</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                  <button
                    @click="gradeAnswerWithAI(answer)"
                    :disabled="aiGradingAnswerId === answer.id"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center disabled:opacity-50"
                  >
                    <svg v-if="aiGradingAnswerId === answer.id" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    {{ aiGradingAnswerId === answer.id ? 'AI đang chấm...' : 'Chấm bằng AI' }}
                  </button>
                  <button
                    @click="gradeAnswer(answer)"
                    :disabled="gradingAnswerId === answer.id"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                  >
                    {{ gradingAnswerId === answer.id ? 'Đang lưu...' : 'Lưu điểm' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Overall Feedback & Band Score (hidden for Speaking - AI score is final) -->
      <div v-if="!isSpeaking" class="bg-white rounded-lg shadow mt-6 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-gray-800">
            {{ isReadingOrListening ? 'Hoàn thành chấm điểm' : 'Nhận xét tổng thể & Band Score' }}
          </h3>
          <div class="flex items-center space-x-3">
            <label class="text-sm font-medium text-gray-700">Overall Band:</label>
            <template v-if="isReadingOrListening || isWriting">
              <!-- Display calculated band score (read-only for Reading/Listening/Writing) -->
              <div class="px-4 py-2 rounded-lg text-lg font-bold" 
                   :class="isWriting ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'">
                <span v-if="overallBandScore !== null">{{ overallBandScore }}</span>
                <span v-else-if="isReadingOrListening">{{ calculatedBandScore }}</span>
                <span v-else class="text-sm font-normal text-gray-500">Chấm 2 tasks để tính</span>
              </div>
              <div v-if="isWriting" class="text-xs text-gray-500 italic">
                (Tự động: T1 + 2×T2 ÷ 3)
              </div>
            </template>
            <template v-else>
              <!-- Manual input for Speaking -->
              <select
                v-model.number="overallBandScore"
                class="w-24 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-lg font-bold"
              >
                <option :value="null">-</option>
                <option v-for="band in bandScores" :key="band" :value="band">{{ band }}</option>
              </select>
            </template>
          </div>
        </div>

        <!-- For Reading/Listening: Simple summary -->
        <div v-if="isReadingOrListening" class="bg-gray-50 rounded-lg p-4 mb-4">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Số câu đúng:</span>
            <span class="font-bold text-gray-800">{{ correctAnswerCount }} / {{ totalQuestionCount }}</span>
          </div>
          <div class="flex items-center justify-between text-sm mt-2">
            <span class="text-gray-600">Tỷ lệ:</span>
            <span class="font-bold text-gray-800">{{ totalQuestionCount > 0 ? ((correctAnswerCount / totalQuestionCount) * 100).toFixed(1) : '0.0' }}%</span>
          </div>
        </div>

        <!-- Feedback textarea -->
        <textarea
          v-model="overallFeedback"
          rows="4"
          class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
          :placeholder="isReadingOrListening ? 'Nhận xét (tùy chọn)...' : 'Nhập nhận xét tổng thể cho bài làm...'"
        ></textarea>
        <div class="mt-4 flex justify-end space-x-3">
          <!-- Only show "Tính Band trung bình" for Speaking (Writing auto-calculates) -->
          <button
            v-if="!isReadingOrListening && !isWriting"
            @click="calculateOverallBand"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Tính Band trung bình
          </button>
          <button
            @click="saveFeedbackWithAutoScore"
            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
            :disabled="savingFeedback"
          >
            {{ savingFeedback ? 'Đang lưu...' : 'Hoàn thành chấm điểm' }}
          </button>
        </div>
      </div>
    </template>

    <!-- AI Settings Modal -->
    <div v-if="showAISettings" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex items-center justify-between">
          <h2 class="text-xl font-bold text-gray-800">Thiết lập AI Chấm bài</h2>
          <button @click="showAISettings = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-6">
          <!-- API Settings -->
          <div>
            <h3 class="font-medium text-gray-800 mb-3">Cấu hình API</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">AI Provider</label>
                <div class="flex flex-wrap gap-3">
                  <label class="flex items-center">
                    <input type="radio" v-model="aiSettings.provider" value="openai" class="mr-2" />
                    <span>OpenAI (GPT)</span>
                  </label>
                  <label class="flex items-center">
                    <input type="radio" v-model="aiSettings.provider" value="anthropic" class="mr-2" />
                    <span>Anthropic (Claude)</span>
                  </label>
                  <label class="flex items-center">
                    <input type="radio" v-model="aiSettings.provider" value="azure" class="mr-2" />
                    <span class="text-blue-600">Azure (TTS & Grading)</span>
                  </label>
                </div>
              </div>

              <!-- API Key for OpenAI/Anthropic -->
              <div v-if="aiSettings.provider !== 'azure'">
                <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                <input
                  v-model="aiSettings.apiKey"
                  type="password"
                  class="w-full px-4 py-2 border rounded-lg"
                  placeholder="sk-..."
                />
              </div>

              <!-- Azure Settings -->
              <div v-if="aiSettings.provider === 'azure'" class="space-y-4 p-4 bg-blue-50 rounded-lg">
                <h4 class="font-medium text-blue-800">Azure Configuration</h4>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Subscription Key</label>
                  <input
                    v-model="aiSettings.azureKey"
                    type="password"
                    class="w-full px-4 py-2 border rounded-lg"
                    placeholder="Azure subscription key..."
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Azure Endpoint URL</label>
                  <input
                    v-model="aiSettings.azureEndpoint"
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
                  <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                  <select v-model="aiSettings.azureRegion" class="w-full px-4 py-2 border rounded-lg">
                    <option value="eastasia">East Asia (Hong Kong)</option>
                    <option value="southeastasia">Southeast Asia (Singapore)</option>
                    <option value="eastus">East US</option>
                    <option value="westus">West US</option>
                    <option value="westeurope">West Europe</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                  <select v-model="aiSettings.speakingLanguage" class="w-full px-4 py-2 border rounded-lg">
                    <option value="en-US">English (US)</option>
                    <option value="en-GB">English (UK)</option>
                    <option value="en-AU">English (Australia)</option>
                  </select>
                </div>
                <div class="text-xs text-gray-600">
                  <p><strong>Note:</strong> Azure can be used for both TTS (Speaking) and AI Grading</p>
                </div>
              </div>

              <!-- Model Selection (not for Azure) -->
              <div v-if="aiSettings.provider !== 'azure'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                <select v-model="aiSettings.model" class="w-full px-4 py-2 border rounded-lg">
                  <template v-if="aiSettings.provider === 'openai'">
                    <optgroup label="GPT-5 Series (Latest - 400K context)">
                      <option value="gpt-5.2">GPT-5.2 (Latest - Best Quality)</option>
                      <option value="gpt-5.1">GPT-5.1 (Recommended)</option>
                    </optgroup>
                  </template>
                  <template v-else>
                    <optgroup label="Claude 4.5 Series (Latest)">
                      <option value="claude-opus-4-5-20251124">Claude Opus 4.5</option>
                      <option value="claude-sonnet-4-5-20250929">Claude Sonnet 4.5</option>
                      <option value="claude-haiku-4-5-20251015">Claude Haiku 4.5</option>
                    </optgroup>
                    <optgroup label="Claude 4 Series">
                      <option value="claude-opus-4-20250514">Claude Opus 4</option>
                      <option value="claude-sonnet-4-20250514">Claude Sonnet 4</option>
                    </optgroup>
                  </template>
                </select>
              </div>

              <!-- Temperature & Max Tokens (not for Azure) -->
              <div v-if="aiSettings.provider !== 'azure'" class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Temperature</label>
                  <input
                    v-model.number="aiSettings.temperature"
                    type="number"
                    min="0"
                    max="2"
                    step="0.1"
                    class="w-full px-4 py-2 border rounded-lg"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Max Tokens</label>
                  <input
                    v-model.number="aiSettings.maxTokens"
                    type="number"
                    min="100"
                    max="4000"
                    class="w-full px-4 py-2 border rounded-lg"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Grading Prompt (not for Azure) -->
          <div v-if="aiSettings.provider !== 'azure'">
            <h3 class="font-medium text-gray-800 mb-3">Prompt Chấm bài IELTS Writing</h3>
            <textarea
              v-model="aiSettings.gradingPrompt"
              rows="12"
              class="w-full px-4 py-3 border rounded-lg font-mono text-sm"
              placeholder="Nhập prompt chấm bài..."
            ></textarea>
            <button
              @click="resetPrompt"
              class="mt-2 text-sm text-blue-600 hover:underline"
            >
              Khôi phục prompt mặc định
            </button>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-end space-x-3">
          <button
            @click="showAISettings = false"
            class="px-4 py-2 border rounded-lg hover:bg-gray-100"
          >
            Hủy
          </button>
          <button
            @click="saveAISettings"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Lưu thiết lập
          </button>
        </div>
      </div>
    </div>

    <!-- AI Grading Confirmation Modal -->
    <div v-if="showAIConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6 border-b">
          <h2 class="text-lg font-bold text-gray-800">Xác nhận chấm bằng AI</h2>
        </div>

        <div class="p-6 space-y-4">
          <!-- Current Provider -->
          <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
            <span class="text-gray-600">Provider:</span>
            <span class="font-medium" :class="getProviderClass(aiSettings.provider)">
              {{ aiSettings.provider === 'openai' ? 'OpenAI' : 'Anthropic' }}
            </span>
          </div>

          <!-- Model Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Chọn Model để chấm</label>
            <select
              v-model="confirmAIModel"
              class="w-full px-4 py-3 border-2 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-lg"
            >
              <template v-if="aiSettings.provider === 'openai'">
                <optgroup label="💡 GPT-5 Series (Khuyến nghị)">
                  <option value="gpt-5.2">GPT-5.2 - Mới nhất (Tốt nhất)</option>
                  <option value="gpt-5.1">GPT-5.1 - Khuyến nghị</option>
                  <option value="gpt-5-mini">GPT-5 Mini - Nhanh ($0.25/1M)</option>
                  <option value="gpt-5-nano">GPT-5 Nano - Rẻ nhất ($0.05/1M)</option>
                </optgroup>
              </template>
              <template v-else>
                <optgroup label="Claude 4.5 (Mới nhất)">
                  <option value="claude-sonnet-4-5-20250929">Claude Sonnet 4.5</option>
                  <option value="claude-haiku-4-5-20251015">Claude Haiku 4.5</option>
                  <option value="claude-opus-4-5-20251124">Claude Opus 4.5</option>
                </optgroup>
                <optgroup label="Claude 4">
                  <option value="claude-sonnet-4-20250514">Claude Sonnet 4</option>
                  <option value="claude-opus-4-20250514">Claude Opus 4</option>
                </optgroup>
              </template>
            </select>
          </div>

          <!-- Cost Info -->
          <div class="text-sm text-gray-500 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
            <span class="font-medium text-yellow-800">💰 Lưu ý:</span>
            Model được chọn sẽ ảnh hưởng đến chi phí API. GPT-5 Nano là lựa chọn tiết kiệm nhất, GPT-5.1 cân bằng giữa chất lượng và chi phí.
          </div>

          <!-- Question Preview -->
          <div v-if="pendingAIGradingAnswer" class="bg-blue-50 rounded-lg p-3">
            <div class="text-sm text-blue-600 font-medium mb-1">Câu hỏi sẽ được chấm:</div>
            <div class="text-gray-800 text-sm line-clamp-3">{{ getQuestionText(pendingAIGradingAnswer.question) }}</div>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-end space-x-3">
          <button
            @click="cancelAIGrading"
            class="px-4 py-2 border rounded-lg hover:bg-gray-100"
          >
            Hủy
          </button>
          <button
            @click="confirmAIGrading"
            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Chấm bằng AI
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api'
import Swal from 'sweetalert2'

const route = useRoute()

const submission = ref(null)
const loading = ref(false)
const gradingAnswerId = ref(null)
const markingAnswerId = ref(null)
const aiGradingAnswerId = ref(null)
const aiGrading = ref(false)
const overallFeedback = ref('')
const overallBandScore = ref(null)
const savingFeedback = ref(false)
const showAISettings = ref(false)

// Full test support
const practiceTestId = computed(() => route.query.practiceTestId)
const isFullTest = computed(() => !!practiceTestId.value)
const allSubmissions = ref([])
const activeTab = ref(null) // 'listening', 'reading', 'writing', 'speaking'

// IELTS Band scores
const bandScores = [0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7, 7.5, 8, 8.5, 9]

// Check if this is Reading or Listening test (auto-grading based on correct count)
const isReadingOrListening = computed(() => {
  const skill = submission.value?.assignment?.test?.subtype
  return ['reading', 'listening'].includes(skill)
})

// Check if this is Writing test
const isWriting = computed(() => {
  const skill = submission.value?.assignment?.test?.subtype
  return skill === 'writing'
})

// Check if this is Speaking test
const isSpeaking = computed(() => {
  const skill = submission.value?.assignment?.test?.subtype
  return skill === 'speaking'
})

// Count correct answers for Reading/Listening
const correctAnswerCount = computed(() => {
  if (!submission.value?.answers) return 0
  return submission.value.answers.filter(a => a.is_correct === true).length
})

// Total questions count - get from test settings, not from submitted answers
const totalQuestionCount = computed(() => {
  // Use total_questions from test if available (includes all questions, not just answered ones)
  if (submission.value?.assignment?.test?.total_questions) {
    return submission.value.assignment.test.total_questions
  }
  // Fallback to answer count (may be wrong if student skipped questions)
  return submission.value?.answers?.length || 0
})

// Convert raw score (correct answers) to IELTS band score
// Based on official IELTS Reading/Listening score conversion table (40 questions)
function rawToBandScore(correct, total = 40) {
  // Scale to 40 if different number of questions
  const scaled = total !== 40 ? Math.round((correct / total) * 40) : correct

  if (scaled >= 39) return 9.0
  if (scaled >= 37) return 8.5
  if (scaled >= 35) return 8.0
  if (scaled >= 33) return 7.5
  if (scaled >= 30) return 7.0
  if (scaled >= 27) return 6.5
  if (scaled >= 23) return 6.0
  if (scaled >= 19) return 5.5
  if (scaled >= 15) return 5.0
  if (scaled >= 13) return 4.5
  if (scaled >= 10) return 4.0
  if (scaled >= 8) return 3.5
  if (scaled >= 6) return 3.0
  if (scaled >= 4) return 2.5
  if (scaled >= 3) return 2.0
  if (scaled >= 2) return 1.5
  if (scaled >= 1) return 1.0
  return 0.0
}

// Calculated band score for Reading/Listening
const calculatedBandScore = computed(() => {
  return rawToBandScore(correctAnswerCount.value, totalQuestionCount.value)
})

// Score conversion table for reference
const scoreTable = [
  { raw: '39-40', band: 9.0 },
  { raw: '37-38', band: 8.5 },
  { raw: '35-36', band: 8.0 },
  { raw: '33-34', band: 7.5 },
  { raw: '30-32', band: 7.0 },
  { raw: '27-29', band: 6.5 },
  { raw: '23-26', band: 6.0 },
  { raw: '19-22', band: 5.5 },
  { raw: '15-18', band: 5.0 },
  { raw: '13-14', band: 4.5 },
  { raw: '10-12', band: 4.0 },
  { raw: '8-9', band: 3.5 },
  { raw: '6-7', band: 3.0 },
  { raw: '4-5', band: 2.5 },
  { raw: '3', band: 2.0 },
  { raw: '2', band: 1.5 },
  { raw: '1', band: 1.0 },
  { raw: '0', band: 0.0 }
]

// Check if current score matches table item
function isCurrentScore(item) {
  return item.band === calculatedBandScore.value
}

// Apply calculated band score to overall
function applyCalculatedBandScore() {
  overallBandScore.value = calculatedBandScore.value
}

// Default AI grading prompt for IELTS Writing
const defaultGradingPrompt = `You are an experienced IELTS examiner. Grade the following IELTS Writing response using the official IELTS Writing Band Descriptors.

Evaluate based on these 4 criteria (each worth 25%):
1. Task Achievement/Response (TA/TR)
2. Coherence and Cohesion (CC)
3. Lexical Resource (LR)
4. Grammatical Range and Accuracy (GRA)

TASK:
{task}

STUDENT RESPONSE:
{response}

Provide your assessment in the following JSON format:
{
  "band_score": <overall band score 0-9, can use .5>,
  "task_achievement": <band 0-9>,
  "coherence_cohesion": <band 0-9>,
  "lexical_resource": <band 0-9>,
  "grammatical_accuracy": <band 0-9>,
  "feedback": "<detailed feedback in Vietnamese, explain strengths and areas for improvement>"
}

Be strict but fair. Provide constructive feedback to help the student improve.`

// AI Settings
const aiSettings = ref({
  provider: 'openai',
  apiKey: '',
  model: 'gpt-5.1',
  temperature: 0.3,
  maxTokens: 2000,
  gradingPrompt: defaultGradingPrompt,
  // Azure settings
  azureKey: '',
  azureEndpoint: '',
  azureRegion: 'southeastasia',
  speakingLanguage: 'en-US',
  // Database status
  hasApiKey: false,
  maskedApiKey: ''
})

// AI Grading Confirmation Modal
const showAIConfirmModal = ref(false)
const pendingAIGradingAnswer = ref(null)
const confirmAIModel = ref('gpt-5.1')

// Load saved settings from localStorage
onMounted(() => {
  fetchSubmission()
  loadAISettings()
})

async function loadAISettings() {
  try {
    const response = await api.get('/examination/ai-settings', {
      params: { module: 'examination_grading' }
    })
    if (response.data.success && response.data.data) {
      const allProviders = response.data.data

      // Get first available provider with API key, prioritize OpenAI > Anthropic > Azure
      let providerSettings = null
      let selectedProvider = 'openai'

      if (allProviders.openai?.has_api_key) {
        providerSettings = allProviders.openai
        selectedProvider = 'openai'
      } else if (allProviders.anthropic?.has_api_key) {
        providerSettings = allProviders.anthropic
        selectedProvider = 'anthropic'
      } else if (allProviders.azure?.has_api_key) {
        providerSettings = allProviders.azure
        selectedProvider = 'azure'
      } else {
        // No API key found, use first available settings or default to openai
        providerSettings = allProviders.openai || allProviders.anthropic || allProviders.azure || null
        selectedProvider = providerSettings?.provider || 'openai'
      }

      if (providerSettings) {
        aiSettings.value.provider = selectedProvider
        aiSettings.value.model = providerSettings.model || 'gpt-5.1'
        aiSettings.value.hasApiKey = providerSettings.has_api_key || false
        aiSettings.value.maskedApiKey = providerSettings.masked_api_key || ''
        if (providerSettings.settings) {
          aiSettings.value.temperature = providerSettings.settings.temperature ?? 0.3
          aiSettings.value.maxTokens = providerSettings.settings.max_tokens ?? 2000
          aiSettings.value.gradingPrompt = providerSettings.settings.grading_prompt || defaultGradingPrompt
          // Azure settings
          aiSettings.value.azureKey = providerSettings.settings.azure_key || ''
          aiSettings.value.azureEndpoint = providerSettings.settings.azure_endpoint || ''
          aiSettings.value.azureRegion = providerSettings.settings.azure_region || 'southeastasia'
          aiSettings.value.speakingLanguage = providerSettings.settings.speaking_language || 'en-US'
        }
      }
    }
  } catch (e) {
    console.error('Error loading AI settings:', e)
  }
}

async function saveAISettings() {
  try {
    await api.post('/examination/ai-settings', {
      module: 'examination_grading',
      provider: aiSettings.value.provider,
      api_key: aiSettings.value.apiKey || undefined,
      model: aiSettings.value.model,
      settings: {
        temperature: aiSettings.value.temperature,
        max_tokens: aiSettings.value.maxTokens,
        grading_prompt: aiSettings.value.gradingPrompt,
        azure_key: aiSettings.value.azureKey,
        azure_endpoint: aiSettings.value.azureEndpoint,
        azure_region: aiSettings.value.azureRegion,
        speaking_language: aiSettings.value.speakingLanguage
      }
    })
    showAISettings.value = false
    Swal.fire('Thành công', 'Đã lưu thiết lập AI thành công!', 'success')
    // Reload to get updated masked key
    await loadAISettings()
    // Clear the apiKey field after saving
    aiSettings.value.apiKey = ''
  } catch (e) {
    console.error('Error saving AI settings:', e)
    Swal.fire('Lỗi', 'Lỗi khi lưu thiết lập: ' + (e.response?.data?.message || e.message), 'error')
  }
}

function resetPrompt() {
  aiSettings.value.gradingPrompt = defaultGradingPrompt
}

const hasSubjectiveQuestions = computed(() => {
  return submission.value?.answers?.some(a => isSubjectiveQuestion(a.question?.type, a.question?.correct_answer))
})

async function fetchSubmission() {
  loading.value = true
  try {
    if (isFullTest.value) {
      // Full test mode: Load all 4 skill submissions
      const response = await api.get('/examination/submissions', {
        params: {
          practice_test_id: practiceTestId.value
        }
      })
      allSubmissions.value = response.data.data || []

      // Set active tab to first available submission
      if (allSubmissions.value.length > 0) {
        const currentSkill = allSubmissions.value.find(s => s.id == route.params.id)?.assignment?.test?.subtype
        activeTab.value = currentSkill || allSubmissions.value[0].assignment?.test?.subtype
        switchToTab(activeTab.value)
      }
    } else {
      // Single submission mode
      const response = await api.get(`/examination/submissions/${route.params.id}`)
      submission.value = response.data.data
      overallFeedback.value = submission.value.feedback || ''
      overallBandScore.value = submission.value.band_score
    }
  } catch (error) {
    console.error('Error fetching submission:', error)
  } finally {
    loading.value = false
  }
}

function switchToTab(skill) {
  const targetSubmission = allSubmissions.value.find(s => s.assignment?.test?.subtype === skill)
  if (targetSubmission) {
    submission.value = targetSubmission
    overallFeedback.value = targetSubmission.feedback || ''
    overallBandScore.value = targetSubmission.band_score
    activeTab.value = skill
  }
}

function getTabBadge(skill) {
  const badges = {
    listening: '🎧 Listening',
    reading: '📖 Reading',
    writing: '✍️ Writing',
    speaking: '🗣️ Speaking'
  }
  return badges[skill] || skill
}

function getTabStatus(skill) {
  const sub = allSubmissions.value.find(s => s.assignment?.test?.subtype === skill)
  return sub?.status || 'not_started'
}

async function gradeAnswer(answer) {
  gradingAnswerId.value = answer.id
  
  // ✅ DEBUG: Log grading_criteria structure before sending
  console.log('🔍 [GradeAnswer] Payload to send:', {
    band_score: answer.band_score,
    feedback_length: (answer.feedback || '').length,
    ai_feedback_length: (answer.ai_feedback || '').length,
    grading_criteria: answer.grading_criteria,
    grading_criteria_type: typeof answer.grading_criteria,
    grading_criteria_keys: answer.grading_criteria ? Object.keys(answer.grading_criteria) : []
  })
  
  try {
    const response = await api.post(`/examination/submissions/${route.params.id}/answers/${answer.id}/grade`, {
      band_score: answer.band_score,
      points: answer.band_score, // Use band score as points
      feedback: answer.feedback,
      ai_feedback: answer.ai_feedback || null,  // ✅ Send AI overall feedback
      grading_criteria: answer.grading_criteria || null,  // Send criteria for IELTS grading
      is_correct: answer.band_score >= 5 // Consider 5+ as passing
    })

    // Update overall band score if returned (for Writing auto-calculation)
    if (response.data.data?.overall_band !== undefined) {
      overallBandScore.value = response.data.data.overall_band
      console.log('✅ Updated overall band:', response.data.data.overall_band)
    }

    // Show success toast
    Swal.fire({
      icon: 'success',
      title: 'Đã lưu điểm',
      text: `Band Score: ${answer.band_score}`,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    })
  } catch (error) {
    console.error('Error grading answer:', error)
    Swal.fire({
      icon: 'error',
      title: 'Lỗi lưu điểm',
      text: error.response?.data?.message || 'Không thể lưu điểm',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    })
  } finally {
    gradingAnswerId.value = null
  }
}

// Show AI confirmation modal before grading
function gradeAnswerWithAI(answer) {
  // For Speaking (audio), directly grade with Azure - no modal
  if (answer.audio_file_path) {
    pendingAIGradingAnswer.value = answer
    confirmAIModel.value = aiSettings.value.model
    confirmAIGrading()
    return
  }

  // For Writing, show model selection modal
  if (!aiSettings.value.hasApiKey) {
    showAISettings.value = true
    return
  }

  // Show confirmation modal with current model
  pendingAIGradingAnswer.value = answer
  confirmAIModel.value = aiSettings.value.model
  showAIConfirmModal.value = true
}

// Confirm and execute AI grading
async function confirmAIGrading() {
  const answer = pendingAIGradingAnswer.value
  if (!answer) return

  // Apply selected model
  aiSettings.value.model = confirmAIModel.value

  showAIConfirmModal.value = false
  aiGradingAnswerId.value = answer.id

  try {
    let result

    // Check if it's a Speaking question (has audio file)
    if (answer.audio_file_path) {
      // Speaking: Use audio grading
      result = await callAISpeaking(answer.question, answer.audio_file_path)

      if (result) {
        answer.band_score = result.band_score

        // ✅ NEW: Check if backend already returns nested format
        const isNestedFormat = typeof result.fluency_coherence === 'object' && result.fluency_coherence !== null
        
        if (isNestedFormat) {
          // Backend returns nested format - use directly!
          answer.grading_criteria = {
            fluency_coherence: result.fluency_coherence || { score: 5.0, feedback: null },
            lexical_resource: result.lexical_resource || { score: 5.0, feedback: null },
            grammatical_range: result.grammatical_range || { score: 5.0, feedback: null },
            pronunciation: result.pronunciation || { score: 5.0, feedback: null }
          }
          
          // Use overall_feedback if available, fallback to feedback
          answer.ai_feedback = result.overall_feedback || result.feedback || null
          answer.feedback = result.overall_feedback || result.feedback || null
        } else {
          // Backward compatibility: flat format from old AI responses
          const criteriaFeedback = parseCriteriaFeedback(result.feedback || '', 'speaking')
          
          answer.grading_criteria = {
            fluency_coherence: {
              score: result.fluency_coherence || 5.0,
              feedback: criteriaFeedback.fluency_coherence || null
            },
            lexical_resource: {
              score: result.lexical_resource || 5.0,
              feedback: criteriaFeedback.lexical_resource || null
            },
            grammatical_range: {
              score: result.grammatical_range || 5.0,
              feedback: criteriaFeedback.grammatical_range || null
            },
            pronunciation: {
              score: result.pronunciation || 5.0,
              feedback: criteriaFeedback.pronunciation || null
            }
          }

          answer.ai_feedback = result.feedback || null
          const summaryFeedback = extractSummaryFeedback(result.feedback || '', criteriaFeedback)
          answer.feedback = summaryFeedback || result.feedback || null
        }
      }
    } else {
      // Writing: Use text grading
      const task = getQuestionText(answer.question)
      const studentResponse = answer.text_answer || answer.answer || ''
      const imageUrl = answer.question?.imageUrl || null

      result = await callAI(task, studentResponse, imageUrl)

      if (result) {
        answer.band_score = result.band_score

        // ✅ NEW: Check if backend already returns nested format
        const isNestedFormat = typeof result.task_achievement === 'object' && result.task_achievement !== null
        
        if (isNestedFormat) {
          // Backend returns nested format - use directly!
          answer.grading_criteria = {
            task_achievement: result.task_achievement || { score: 5.0, feedback: null },
            coherence_cohesion: result.coherence_cohesion || { score: 5.0, feedback: null },
            lexical_resource: result.lexical_resource || { score: 5.0, feedback: null },
            grammatical_range: result.grammatical_range || { score: 5.0, feedback: null }
          }
          
          // Use overall_feedback if available, fallback to feedback
          answer.ai_feedback = result.overall_feedback || result.feedback || null
          answer.feedback = result.overall_feedback || result.feedback || null
        } else {
          // Backward compatibility: flat format from old AI responses
          const criteriaFeedback = parseCriteriaFeedback(result.feedback || '', 'writing')
          
          answer.grading_criteria = {
            task_achievement: {
              score: result.task_achievement || 5.0,
              feedback: criteriaFeedback.task_achievement || null
            },
            coherence_cohesion: {
              score: result.coherence_cohesion || 5.0,
              feedback: criteriaFeedback.coherence_cohesion || null
            },
            lexical_resource: {
              score: result.lexical_resource || 5.0,
              feedback: criteriaFeedback.lexical_resource || null
            },
            grammatical_range: {
              score: result.grammatical_range || 5.0,
              feedback: criteriaFeedback.grammatical_range || null
            }
          }

          answer.ai_feedback = result.feedback || null
          const summaryFeedback = extractSummaryFeedback(result.feedback || '', criteriaFeedback)
          answer.feedback = summaryFeedback || result.feedback || null
        }
      }
    }

    // Auto save after AI grading
    if (result) {
      await gradeAnswer(answer)
    }
  } catch (error) {
    console.error('Error with AI grading:', error)
    handleAIError(error)
  } finally {
    aiGradingAnswerId.value = null
    pendingAIGradingAnswer.value = null
  }
}

// Cancel AI grading
function cancelAIGrading() {
  showAIConfirmModal.value = false
  pendingAIGradingAnswer.value = null
}

// Handle AI errors with specific messages
function handleAIError(error) {
  const message = error.message || ''

  if (message.includes('400')) {
    Swal.fire({
      title: 'Lỗi Bad Request (400)',
      html: `<p>${message}</p>
<p><strong>Nguyên nhân có thể:</strong></p>
<ol style="text-align: left;">
<li>Tên model không đúng</li>
<li>API key không có quyền dùng model này</li>
<li>Nội dung request không hợp lệ</li>
</ol>
<p>Hãy thử đổi sang model khác (gpt-5.2, gpt-5.1)</p>`,
      icon: 'error'
    })
    showAISettings.value = true
  } else if (message.includes('429')) {
    Swal.fire({
      title: 'Lỗi Rate Limit (429)',
      html: `<p>Bạn đã gửi quá nhiều request đến API.</p>
<p><strong>Giải pháp:</strong></p>
<ol style="text-align: left;">
<li>Đợi 1-2 phút rồi thử lại</li>
<li>Sử dụng model rẻ hơn</li>
<li>Kiểm tra quota tại dashboard.openai.com</li>
</ol>`,
      icon: 'warning'
    })
  } else if (message.includes('401')) {
    Swal.fire({
      title: 'Lỗi xác thực (401)',
      text: 'API Key không hợp lệ. Vui lòng kiểm tra lại API Key trong Thiết lập AI.',
      icon: 'error'
    })
    showAISettings.value = true
  } else if (message.includes('403')) {
    Swal.fire({
      title: 'Lỗi quyền truy cập (403)',
      text: 'API Key không có quyền sử dụng model này hoặc đã bị vô hiệu hóa.',
      icon: 'error'
    })
  } else if (message.includes('500') || message.includes('502') || message.includes('503')) {
    Swal.fire({
      title: 'Lỗi server (5xx)',
      text: 'Dịch vụ AI đang tạm thời không khả dụng. Vui lòng thử lại sau.',
      icon: 'warning'
    })
  } else {
    Swal.fire('Lỗi', 'Lỗi khi chấm bằng AI: ' + message, 'error')
  }
}

async function gradeAllWithAI() {
  if (!aiSettings.value.hasApiKey) {
    showAISettings.value = true
    return
  }

  aiGrading.value = true
  try {
    for (const answer of submission.value.answers) {
      if (isSubjectiveQuestion(answer.question?.type, answer.question?.correct_answer)) {
        await gradeAnswerWithAI(answer)
      }
    }
    calculateOverallBand()
  } catch (error) {
    console.error('Error with batch AI grading:', error)
  } finally {
    aiGrading.value = false
  }
}

async function callAI(task, studentResponse, imageUrl = null) {
  // Use backend API endpoint (API key is stored in database)
  const response = await api.post('/examination/grade-with-ai', {
    task: task,
    response: studentResponse,
    image_url: imageUrl,  // Pass image URL for Task 1 charts/diagrams
    provider: aiSettings.value.provider,  // Send provider to backend
    model: aiSettings.value.model,
    grading_prompt: aiSettings.value.gradingPrompt
  })

  if (response.data.success) {
    return response.data.data
  } else {
    throw new Error(response.data.message || 'AI grading failed')
  }
}

async function callAISpeaking(question, audioFilePath) {
  // Send audio file path directly (file already exists on server)
  const response = await api.post('/examination/grade-speaking-with-ai', {
    audio_file_path: audioFilePath,  // Send path instead of blob
    question: getQuestionText(question),
    provider: aiSettings.value.provider,
    model: aiSettings.value.model
    // Note: Do NOT send grading_prompt - Speaking uses its own default prompt on backend
  })

  if (response.data.success) {
    return response.data.data
  } else {
    throw new Error(response.data.message || 'AI speaking grading failed')
  }
}

function calculateOverallBand() {
  const subjectiveAnswers = submission.value.answers.filter(a =>
    isSubjectiveQuestion(a.question?.type, a.question?.correct_answer) && a.band_score != null
  )

  if (subjectiveAnswers.length === 0) return

  const sum = subjectiveAnswers.reduce((acc, a) => acc + a.band_score, 0)
  const avg = sum / subjectiveAnswers.length

  // Round to nearest 0.5
  overallBandScore.value = Math.round(avg * 2) / 2
}

async function saveFeedback() {
  savingFeedback.value = true
  try {
    await api.post(`/examination/submissions/${route.params.id}/feedback`, {
      feedback: overallFeedback.value,
      band_score: overallBandScore.value,
      status: 'graded'
    })
    await fetchSubmission()
  } catch (error) {
    console.error('Error saving feedback:', error)
  } finally {
    savingFeedback.value = false
  }
}

// Save feedback with auto-calculated score for Reading/Listening
async function saveFeedbackWithAutoScore() {
  // For Reading/Listening, auto-apply calculated band score if not set
  if (isReadingOrListening.value && !overallBandScore.value) {
    overallBandScore.value = calculatedBandScore.value
  }
  await saveFeedback()
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('vi-VN')
}

function formatDuration(start, end) {
  if (!start || !end) return '-'
  const diff = new Date(end) - new Date(start)
  const minutes = Math.floor(diff / 60000)
  const seconds = Math.floor((diff % 60000) / 1000)
  return `${minutes} phút ${seconds} giây`
}

function formatAnswer(answer) {
  if (!answer) return '-'
  if (typeof answer === 'object') return JSON.stringify(answer, null, 2)
  return answer
}

function getQuestionText(question) {
  console.log('🔍 getQuestionText called', { 
    question,
    hasQuestion: !!question,
    content: question?.content,
    contentType: typeof question?.content
  })
  
  if (!question) {
    console.log('❌ No question object')
    return ''
  }
  
  // Direct access to content
  const content = question.content
  console.log('📄 Content value:', content)
  
  // If content is already a string, return it
  if (typeof content === 'string' && content.trim()) {
    console.log('✅ Content is non-empty string:', content.substring(0, 50))
    // Try to parse as JSON first
    try {
      const parsed = JSON.parse(content)
      const result = parsed.text || parsed.question || content
      console.log('✅ Parsed JSON, returning:', result.substring(0, 50))
      return result
    } catch {
      // Not JSON, return as is
      console.log('✅ Not JSON, returning as is')
      return content
    }
  }
  
  // If content is object
  if (typeof content === 'object' && content !== null) {
    const result = content.text || content.question || ''
    console.log('✅ Content is object, returning:', result)
    return result
  }
  
  console.log('❌ No content found, returning empty')
  return ''
}

function isSubjectiveQuestion(type, correctAnswer = null) {
  // If has a correct answer (TRUE/FALSE/NOT GIVEN, multiple choice, etc), it's objective
  if (correctAnswer) return false
  
  // Otherwise check type
  return ['writing', 'speaking', 'essay', 'audio_response'].includes(type)
}

function getQuestionTypeName(type) {
  const names = {
    multiple_choice: 'Trắc nghiệm',
    true_false_ng: 'True/False/Not Given',
    fill_blank: 'Điền từ',
    matching: 'Nối',
    essay: 'Viết luận',
    writing: 'Viết',
    speaking: 'Nói',
    audio_response: 'Nói (Audio)',
    short_answer: 'Trả lời ngắn'
  }
  return names[type] || type
}

function getTypeName(type) {
  const names = { ielts: 'IELTS', cambridge: 'Cambridge', toeic: 'TOEIC', custom: 'Tự tạo' }
  return names[type] || type
}

function getSkillName(skill) {
  const names = { listening: 'Listening', reading: 'Reading', writing: 'Writing', speaking: 'Speaking' }
  return names[skill] || skill || 'N/A'
}

function getSkillClass(skill) {
  const classes = {
    listening: 'bg-purple-100 text-purple-800',
    reading: 'bg-blue-100 text-blue-800',
    writing: 'bg-green-100 text-green-800',
    speaking: 'bg-orange-100 text-orange-800'
  }
  return classes[skill] || 'bg-gray-100 text-gray-800'
}

function getStatusName(status) {
  const names = { submitted: 'Chờ chấm', grading: 'Đang chấm', graded: 'Đã chấm', late: 'Nộp muộn' }
  return names[status] || status
}

function getStatusClass(status) {
  const classes = {
    submitted: 'bg-orange-100 text-orange-800',
    grading: 'bg-blue-100 text-blue-800',
    graded: 'bg-green-100 text-green-800',
    late: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

// Get display name for current AI model
function getModelDisplayName() {
  const provider = aiSettings.value.provider
  const model = aiSettings.value.model

  if (provider === 'azure') {
    return 'Azure TTS & AI'
  }

  // Model display names mapping
  const modelNames = {
    // OpenAI GPT-5 Series
    'gpt-5.2': 'GPT-5.2',
    'gpt-5.1': 'GPT-5.1',
    // Claude 4.5
    'claude-opus-4-5-20251124': 'Claude Opus 4.5',
    'claude-sonnet-4-5-20250929': 'Claude Sonnet 4.5',
    'claude-haiku-4-5-20251015': 'Claude Haiku 4.5',
    // Claude 4
    'claude-opus-4-20250514': 'Claude Opus 4',
    'claude-sonnet-4-20250514': 'Claude Sonnet 4'
  }

  return modelNames[model] || model
}

// Get CSS class for provider badge
function getProviderClass(provider) {
  const classes = {
    'openai': 'text-green-600',
    'anthropic': 'text-orange-600',
    'azure': 'text-blue-600'
  }
  return classes[provider] || 'text-gray-600'
}

// Auto-compare student answer with correct answer
function isAnswerCorrect(answer) {
  if (!answer.question?.correct_answer) return null
  
  const studentAnswer = String(formatAnswer(answer.answer)).trim().toLowerCase()
  const correctAnswer = String(answer.question.correct_answer).trim().toLowerCase()
  
  return studentAnswer === correctAnswer
}

// Get comparison message
function getCompareMessage(answer) {
  const isCorrect = isAnswerCorrect(answer)
  if (isCorrect === null) return 'Không có đáp án để so sánh'
  
  return isCorrect 
    ? '✓ Trả lời đúng khớp với đáp án' 
    : '✗ Trả lời không khớp với đáp án'
}

// Get border color class based on comparison
function getCompareClass(answer) {
  const isCorrect = isAnswerCorrect(answer)
  if (isCorrect === null) return 'border-gray-300 bg-gray-50'
  
  return isCorrect 
    ? 'border-green-300 bg-green-50' 
    : 'border-red-300 bg-red-50'
}

// Mark answer as correct/incorrect
async function markAnswer(answer, isCorrect) {
  markingAnswerId.value = answer.id
  try {
    await api.post(`/examination/submissions/${route.params.id}/answers/${answer.id}/grade`, {
      is_correct: isCorrect,
      points: isCorrect ? (answer.question?.points || 1) : 0,
    })
    
    // Update local state
    answer.is_correct = isCorrect
    
    Swal.fire({
      icon: 'success',
      title: isCorrect ? 'Đã đánh dấu đúng' : 'Đã đánh dấu sai',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true
    })
  } catch (error) {
    console.error('Error marking answer:', error)
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể lưu đánh giá',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    })
  } finally {
    markingAnswerId.value = null
  }
}

// Parse feedback to extract criteria-specific sections
function parseCriteriaFeedback(feedback, testType) {
  if (!feedback) return {}

  const criteriaFeedback = {}
  const isSpeaking = testType === 'speaking'

  // Patterns for each criterion (more flexible to match variations)
  const patterns = {
    fluency_coherence: {
      header: /về\s+fluency\s*[&và]\s*coherence[:\s]*/i,
      name: 'Fluency & Coherence'
    },
    lexical_resource: {
      header: /về\s+lexical\s+resource[:\s]*/i,
      name: 'Lexical Resource'
    },
    grammatical_range: {
      header: /về\s+gramm(ar|atical(\s+range)?(\s*[&và]\s*accuracy)?)[:\s]*/i,
      name: 'Grammatical Range & Accuracy'
    },
    pronunciation: {
      header: /về\s+pronunciation[:\s]*/i,
      name: 'Pronunciation'
    },
    task_achievement: {
      header: /về\s+task\s+(achievement|response)[:\s]*/i,
      name: 'Task Achievement'
    },
    coherence_cohesion: {
      header: /về\s+coherence\s*[&và]\s*cohesion[:\s]*/i,
      name: 'Coherence & Cohesion'
    }
  }

  // Split by single OR double newlines to handle both formats
  const lines = feedback.split('\n')

  // Build an array of criterion headers found in the text
  const headerPositions = []
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i].trim()
    const lineLower = line.toLowerCase()

    for (const [criterion, pattern] of Object.entries(patterns)) {
      // Skip if not relevant
      if (isSpeaking && ['task_achievement', 'coherence_cohesion'].includes(criterion)) continue
      if (!isSpeaking && ['fluency_coherence', 'pronunciation'].includes(criterion)) continue

      if (pattern.header.test(lineLower)) {
        headerPositions.push({
          criterion,
          lineIndex: i,
          pattern
        })
        break
      }
    }
  }

  // Extract text between each header
  for (let idx = 0; idx < headerPositions.length; idx++) {
    const current = headerPositions[idx]
    const next = headerPositions[idx + 1]

    const startLine = current.lineIndex
    const endLine = next ? next.lineIndex : lines.length

    // Get the content for this criterion
    const criterionLines = []

    for (let i = startLine; i < endLine; i++) {
      let line = lines[i].trim()

      // Skip the header line itself
      if (i === startLine) {
        // Remove the header prefix from this line
        line = line.replace(current.pattern.header, '').trim()
      }

      // Skip empty lines and introduction lines
      if (!line) continue
      if (i === startLine && /^dựa trên tiêu chuẩn/i.test(line)) continue

      criterionLines.push(line)
    }

    if (criterionLines.length > 0) {
      criteriaFeedback[current.criterion] = criterionLines.join(' ').trim()
    }
  }

  return criteriaFeedback
}

// Extract summary feedback (short, not concatenated criteria)
function extractSummaryFeedback(feedback, criteriaFeedback) {
  if (!feedback) return null
  
  // If feedback is mostly concatenated criteria, return null
  const allCriteriaText = Object.values(criteriaFeedback).join(' ')
  if (allCriteriaText.length > 0) {
    const similarity = calculateSimilarity(feedback.toLowerCase(), allCriteriaText.toLowerCase())
    if (similarity > 80) return null // It's just concatenated criteria
  }
  
  // Look for summary section (usually at end, starts with "Để cải thiện")
  const summaryMatch = feedback.match(/(để cải thiện|để nâng cao|tổng kết|kết luận|tóm lại)[\s\S]*$/i)
  if (summaryMatch) {
    const summary = summaryMatch[0].trim()
    if (summary.length < 500) return summary // Real summary should be short
  }
  
  return null
}

// Simple similarity calculation
function calculateSimilarity(str1, str2) {
  const longer = str1.length > str2.length ? str1 : str2
  const shorter = str1.length > str2.length ? str2 : str1
  if (longer.length === 0) return 100
  const editDistance = levenshteinDistance(longer, shorter)
  return ((longer.length - editDistance) / longer.length) * 100
}

function levenshteinDistance(str1, str2) {
  const matrix = []
  for (let i = 0; i <= str2.length; i++) {
    matrix[i] = [i]
  }
  for (let j = 0; j <= str1.length; j++) {
    matrix[0][j] = j
  }
  for (let i = 1; i <= str2.length; i++) {
    for (let j = 1; j <= str1.length; j++) {
      if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
        matrix[i][j] = matrix[i - 1][j - 1]
      } else {
        matrix[i][j] = Math.min(
          matrix[i - 1][j - 1] + 1,
          matrix[i][j - 1] + 1,
          matrix[i - 1][j] + 1
        )
      }
    }
  }
  return matrix[str2.length][str1.length]
}
</script>
