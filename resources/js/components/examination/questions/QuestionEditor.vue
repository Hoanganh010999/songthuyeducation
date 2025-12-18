<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
      <!-- Overlay -->
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="sticky top-0 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 px-6 py-5 flex items-center justify-between z-10">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div>
              <h2 class="text-xl font-bold text-white">
                {{ isEditing ? 'Chỉnh sửa câu hỏi' : 'Tạo câu hỏi mới' }}
              </h2>
              <p class="text-xs text-white/80 mt-0.5">Tạo câu hỏi chất lượng cao cho kỳ thi</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <button @click="showAIGenerator = true" class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-lg hover:bg-white/30 transition-all flex items-center gap-2 border border-white/30">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
              <span class="font-medium">AI Generate</span>
            </button>
            <button @click="$emit('close')" class="text-white/90 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="saveQuestion" class="flex-1 overflow-y-auto p-6 space-y-5 bg-gray-50">
          <!-- Subject & Category Section -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-4">
              <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
              <h3 class="text-base font-semibold text-gray-800">Môn học & Phân loại</h3>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Môn học *</label>
                <select v-model="form.subject_id" @change="onSubjectChange" :disabled="!!props.subjectId" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed transition-all">
                  <option value="">Chọn môn học</option>
                  <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                </select>
                <p v-if="props.subjectId" class="mt-1 text-xs text-green-600">✓ Đã chọn từ syllabus</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phân loại</label>
                <select v-model="form.subject_category_id" :disabled="!form.subject_id || subjectCategories.length === 0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent disabled:bg-gray-100 transition-all">
                  <option value="">{{ form.subject_id ? (subjectCategories.length ? 'Chọn môn học trước' : 'Không có phân loại') : 'Chọn môn học trước' }}</option>
                  <option v-for="cat in subjectCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Basic Info Section -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-4">
              <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 class="text-base font-semibold text-gray-800">Thông tin cơ bản</h3>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <!-- Question Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Loại câu hỏi *</label>
                <select v-model="form.type" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                  <option value="">Chọn loại</option>
                  <option v-for="type in questionTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </div>

              <!-- Difficulty -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Độ khó *</label>
                <select v-model="form.difficulty" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                  <option value="">Trung bình</option>
                  <option value="easy">Dễ</option>
                  <option value="medium">Trung bình</option>
                  <option value="hard">Khó</option>
                  <option value="expert">Chuyên gia</option>
                </select>
              </div>

              <!-- Points -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Điểm *</label>
                <input v-model.number="form.points" type="number" min="1" required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" />
              </div>

              <!-- Status -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                <select v-model="form.status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                  <option value="draft">Nháp</option>
                  <option value="active">Hoạt động</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Question Content Section -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-4">
              <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </div>
              <h3 class="text-base font-semibold text-gray-800">Nội dung câu hỏi *</h3>
            </div>

            <!-- Rich Editor for Fill Blanks Types -->
            <div v-if="isFillBlanks" class="border border-gray-300 rounded-xl overflow-hidden">
              <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 border-b flex items-center justify-between">
                <div class="flex gap-1">
                  <button type="button" @click="formatContent('bold')" class="px-3 py-1.5 text-sm hover:bg-white rounded-lg transition-all shadow-sm" title="Bold">
                    <strong>B</strong>
                  </button>
                  <button type="button" @click="formatContent('italic')" class="px-3 py-1.5 text-sm hover:bg-white rounded-lg transition-all shadow-sm" title="Italic">
                    <em>I</em>
                  </button>
                  <button type="button" @click="formatContent('underline')" class="px-3 py-1.5 text-sm hover:bg-white rounded-lg transition-all shadow-sm" title="Underline">
                    <u>U</u>
                  </button>
                  <button type="button" @click="formatContent('insertUnorderedList')" class="px-3 py-1.5 text-sm hover:bg-white rounded-lg transition-all shadow-sm" title="Bullet List">
                    • List
                  </button>
                  <button type="button" @click="formatContent('insertOrderedList')" class="px-3 py-1.5 text-sm hover:bg-white rounded-lg transition-all shadow-sm" title="Numbered List">
                    1. List
                  </button>
                </div>
                <button type="button" @click="insertBlank" class="px-4 py-1.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm rounded-lg hover:shadow-md transition-all">
                  + Thêm chỗ trống
                </button>
              </div>
              <div
                ref="contentEditor"
                contenteditable="true"
                @input="updateContentFromEditor"
                class="min-h-[120px] p-4 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white"
              ></div>
              <div class="bg-gradient-to-r from-blue-50 to-purple-50 px-4 py-3 text-sm text-gray-700 border-t">
                <span class="inline-flex items-center gap-2">
                  <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Sử dụng <strong>[blank]</strong> để đánh dấu vị trí cần điền. Ví dụ: "The cat [blank] on the mat."</span>
                </span>
              </div>
            </div>

            <!-- Regular Textarea for Other Types -->
            <textarea v-else v-model="form.title" rows="3" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
              placeholder="Nhập nội dung câu hỏi..."></textarea>
          </div>

          <!-- Question Content (Extended) -->
          <div v-if="needsExtendedContent">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung mở rộng</label>
            <textarea v-model="form.content" rows="4"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Đoạn văn, nội dung chi tiết..."></textarea>
          </div>

          <!-- Audio URL (for listening questions) -->
          <div v-if="form.skill === 'listening'">
            <label class="block text-sm font-medium text-gray-700 mb-1">URL Audio</label>
            <input v-model="form.audio_url" type="url"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="https://..." />
          </div>

          <!-- Options Section (Multiple Choice, True/False) -->
          <div v-if="hasOptions" class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-medium text-gray-800">Các lựa chọn</h3>
              <button type="button" @click="addOption" class="text-sm text-blue-600 hover:text-blue-800">
                + Thêm lựa chọn
              </button>
            </div>

            <div class="space-y-3">
              <div v-for="(option, index) in form.options" :key="index" class="flex items-start gap-3">
                <input
                  type="checkbox"
                  v-model="option.is_correct"
                  class="mt-2 rounded border-gray-300"
                  :class="{ 'text-green-600': option.is_correct }"
                />
                <div class="flex-1">
                  <input v-model="option.text" type="text" required
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    :placeholder="`Lựa chọn ${index + 1}`" />
                </div>
                <button v-if="form.options.length > 2" type="button" @click="removeOption(index)"
                  class="p-2 text-red-600 hover:bg-red-50 rounded">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
            <p class="mt-2 text-sm text-gray-500">Đánh dấu vào ô để chọn đáp án đúng</p>
          </div>

          <!-- Matching Items -->
          <div v-if="isMatching" class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-medium text-gray-800">Các cặp nối</h3>
              <button type="button" @click="addMatchingPair" class="text-sm text-blue-600 hover:text-blue-800">
                + Thêm cặp
              </button>
            </div>

            <div class="space-y-3">
              <div v-for="(pair, index) in form.matching_pairs" :key="index" class="grid grid-cols-2 gap-3">
                <input v-model="pair.left" type="text" required
                  class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  :placeholder="`Vế trái ${index + 1}`" />
                <div class="flex gap-2">
                  <input v-model="pair.right" type="text" required
                    class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    :placeholder="`Vế phải ${index + 1}`" />
                  <button v-if="form.matching_pairs.length > 2" type="button" @click="removeMatchingPair(index)"
                    class="p-2 text-red-600 hover:bg-red-50 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Fill Blanks -->
          <div v-if="isFillBlanks" class="border rounded-lg p-4">
            <h3 class="font-medium text-gray-800 mb-2">Đáp án cho các chỗ trống</h3>
            <p class="text-sm text-gray-500 mb-3">
              Tìm thấy {{ blankCount }} chỗ trống trong nội dung câu hỏi
            </p>
            <div v-if="blankCount > 0" class="space-y-3">
              <div v-for="(answer, index) in form.blank_answers" :key="index" class="flex items-center gap-3">
                <span class="badge badge-primary">Blank {{ index + 1 }}</span>
                <input v-model="form.blank_answers[index]" type="text" required
                  class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  :placeholder="'Đáp án ' + (index + 1)" />
              </div>
            </div>
            <p v-else class="text-sm text-gray-400 italic">
              Chưa có chỗ trống nào. Sử dụng nút "+ Thêm chỗ trống" ở phần nội dung câu hỏi.
            </p>
          </div>

          <!-- Drag & Drop Editor -->
          <div v-if="isDragDrop" class="border rounded-lg p-4 space-y-4">
            <h3 class="font-medium text-gray-800">Thiết kế câu hỏi kéo thả</h3>

            <div class="bg-blue-50 p-3 rounded text-sm text-blue-800">
              <strong>Cách hoạt động:</strong>
              <ul class="list-disc list-inside mt-1 space-y-1">
                <li><strong>Vùng thả (Zone)</strong> - Nơi học sinh sẽ thả phần tử vào (màu xanh dương)</li>
                <li><strong>Phần tử (Item)</strong> - Text/hình mà học sinh sẽ kéo (hiện ở danh sách bên dưới)</li>
                <li>Dùng dropdown để gán đáp án: Item nào đúng cho Zone nào</li>
              </ul>
            </div>

            <!-- Container Height -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Chiều cao canvas (px)</label>
              <input v-model.number="dragDropSettings.containerHeight" type="number" min="300" max="800"
                class="px-3 py-2 border rounded-lg w-32" placeholder="400" />
            </div>

            <!-- Background Content Editor -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Nội dung nền (Background Layer)</label>
              <div class="border rounded-lg overflow-hidden">
                <!-- Toolbar -->
                <div class="bg-gray-100 px-3 py-2 border-b flex items-center gap-2">
                  <button type="button" @click="formatBackground('bold')" class="btn btn-xs btn-ghost" title="Bold">
                    <strong>B</strong>
                  </button>
                  <button type="button" @click="formatBackground('italic')" class="btn btn-xs btn-ghost" title="Italic">
                    <em>I</em>
                  </button>
                  <button type="button" @click="formatBackground('underline')" class="btn btn-xs btn-ghost" title="Underline">
                    <u>U</u>
                  </button>
                  <div class="h-4 w-px bg-gray-300 mx-1"></div>
                  <button type="button" @click="formatBackground('insertUnorderedList')" class="btn btn-xs btn-ghost" title="Bullet List">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                  </button>
                  <button type="button" @click="formatBackground('insertOrderedList')" class="btn btn-xs btn-ghost" title="Numbered List">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                    </svg>
                  </button>
                  <div class="h-4 w-px bg-gray-300 mx-1"></div>
                  <button type="button" @click="insertBackgroundImage" class="btn btn-xs btn-ghost" title="Thêm hình">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </button>
                </div>
                <!-- Editor -->
                <div
                  ref="backgroundEditor"
                  contenteditable="true"
                  @input="updateBackgroundContent"
                  class="min-h-[100px] p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                ></div>
                <input type="file" ref="backgroundImageInput" @change="onBackgroundImageInsert" accept="image/*" class="hidden" />
              </div>
              <p class="text-xs text-gray-500">Soạn thảo văn bản, thêm hình ảnh cho background. Các vùng thả sẽ hiển thị trên layer này.</p>
            </div>

            <!-- Canvas Preview -->
            <div class="border-2 rounded-lg overflow-hidden relative"
              ref="dragDropCanvas"
              :style="{ height: (dragDropSettings.containerHeight || 400) + 'px' }"
              @click="onCanvasClick">

              <!-- Background Content Layer (Read-only preview) -->
              <div class="absolute inset-0 bg-white overflow-auto pointer-events-none p-3" v-html="dragDropSettings.backgroundContent"></div>

              <!-- Drop Zones Layer -->
              <div
                v-for="(zone, index) in dragDropSettings.dropZones"
                :key="`zone-${index}`"
                class="absolute border-2 border-blue-500 bg-blue-100 bg-opacity-30 cursor-move group"
                :style="{
                  left: zone.x + '%',
                  top: zone.y + '%',
                  width: zone.width + '%',
                  height: zone.height + '%'
                }"
                @mousedown="startDragZone(index, $event)"
              >
                <span class="text-xs font-medium text-blue-700 p-1">Zone {{ index + 1 }}</span>
                <button type="button" @click.stop="removeDropZone(index)"
                  class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <!-- Resize Handle -->
                <div class="absolute bottom-0 right-0 w-4 h-4 bg-blue-600 cursor-se-resize"
                  @mousedown.stop="startResizeZone(index, $event)"></div>
              </div>
            </div>

            <!-- Add Item Buttons -->
            <div class="flex gap-2 flex-wrap">
              <button type="button" @click="addTextItem" class="btn btn-sm btn-outline">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Thêm Text
              </button>
              <button type="button" @click="triggerImageUpload" class="btn btn-sm btn-outline">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Thêm Hình
              </button>
              <button type="button" @click="addDropZone" class="btn btn-sm btn-primary">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Thêm Vùng Thả
              </button>
            </div>
            <input type="file" ref="itemImageInput" @change="onItemImageUpload" accept="image/*" class="hidden" />

            <!-- Items List -->
            <div v-if="dragDropSettings.items.length > 0" class="border rounded-lg p-4 bg-gray-50">
              <h4 class="font-medium text-gray-700 mb-3">Danh sách phần tử ({{ dragDropSettings.items.length }})</h4>
              <div class="space-y-2">
                <div v-for="(item, index) in dragDropSettings.items" :key="`item-list-${index}`"
                  class="flex items-center justify-between p-2 bg-white rounded border">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-500">{{ index + 1 }}.</span>
                    <img v-if="item.type === 'image'" :src="item.content" class="w-24 h-24 object-cover border rounded" alt="Item" />
                    <span v-else class="text-sm" v-html="item.content"></span>
                  </div>
                  <button type="button" @click="removeItem(index)"
                    class="text-red-500 hover:text-red-700 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Assign Answers -->
            <div v-if="dragDropSettings.dropZones.length > 0" class="border-t pt-4">
              <h4 class="font-medium text-gray-700 mb-3">Gán đáp án cho các vùng</h4>
              <div class="space-y-2">
                <div v-for="(zone, index) in dragDropSettings.dropZones" :key="`answer-${index}`"
                  class="flex items-center gap-3">
                  <span class="text-sm font-medium w-20">Vùng {{ index + 1 }}:</span>
                  <select v-model="dragDropSettings.correctAnswers[index]"
                    class="select select-bordered select-sm flex-1">
                    <option :value="null">-- Chọn item đúng --</option>
                    <option v-for="(item, itemIdx) in dragDropSettings.items" :key="itemIdx" :value="itemIdx">
                      {{ getItemLabel(item, itemIdx) }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 p-3 rounded text-sm text-blue-800">
              <strong>Hướng dẫn:</strong>
              <ul class="list-disc list-inside mt-1 space-y-1">
                <li>Thêm các phần tử (text/hình) mà học sinh sẽ kéo thả</li>
                <li>Thêm các "Vùng Thả" trên canvas - nơi học sinh thả phần tử vào</li>
                <li>Kéo các vùng để định vị, kéo góc để thay đổi kích thước</li>
                <li>Dùng dropdown "Gán đáp án" để chọn item nào đúng cho vùng nào</li>
                <li>Khi học sinh làm bài, các phần tử sẽ hiển thị ngẫu nhiên ở dưới để kéo lên</li>
              </ul>
            </div>
          </div>

          <!-- Table Completion Editor -->
          <div v-if="isTableCompletion" class="border rounded-lg p-4 space-y-4">
            <h3 class="font-medium text-gray-800">Thiết kế bảng Table Completion</h3>

            <div class="bg-blue-50 p-3 rounded text-sm text-blue-800">
              <strong>Cách hoạt động:</strong>
              <ul class="list-disc list-inside mt-1 space-y-1">
                <li>Tạo bảng với các cột (headers) và hàng (rows)</li>
                <li>Đánh dấu ô nào là "chỗ trống" để học sinh điền vào</li>
                <li>Nhập đáp án đúng cho từng chỗ trống</li>
              </ul>
            </div>

            <!-- Headers (Columns) -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">Cột (Headers)</label>
                <div class="flex gap-2">
                  <button type="button" @click="addTableColumn" class="btn btn-sm btn-outline">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Thêm cột
                  </button>
                </div>
              </div>
              <div class="flex gap-2 flex-wrap">
                <div v-for="(header, index) in tableCompletionData.headers" :key="`header-${index}`"
                  class="flex items-center gap-2 border rounded-lg px-3 py-2 bg-white">
                  <input v-model="tableCompletionData.headers[index]" type="text"
                    class="w-32 px-2 py-1 border rounded focus:ring-2 focus:ring-blue-500"
                    :placeholder="`Column ${index + 1}`" />
                  <button v-if="tableCompletionData.headers.length > 1" type="button" @click="removeTableColumn(index)"
                    class="text-red-500 hover:text-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Table Preview -->
            <div class="overflow-x-auto border rounded-lg">
              <table class="table table-bordered border-collapse w-full">
                <thead>
                  <tr class="bg-gray-100">
                    <th v-for="(header, index) in tableCompletionData.headers" :key="`th-${index}`"
                      class="border border-gray-300 px-3 py-2 text-left font-medium">
                      {{ header || `Column ${index + 1}` }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, rowIndex) in tableCompletionData.rows" :key="`row-${rowIndex}`">
                    <td v-for="(cell, colIndex) in row" :key="`cell-${rowIndex}-${colIndex}`"
                      class="border border-gray-300 px-2 py-2">
                      <div class="flex items-center gap-2">
                        <!-- Checkbox to mark as blank -->
                        <input type="checkbox" v-model="cell.isBlank" @change="updateBlankIndexes"
                          class="checkbox checkbox-sm" title="Đánh dấu là chỗ trống" />
                        <!-- Cell content input -->
                        <input v-model="cell.content" type="text"
                          :disabled="cell.isBlank"
                          class="flex-1 px-2 py-1 border rounded text-sm"
                          :class="cell.isBlank ? 'bg-yellow-50 border-yellow-300' : 'bg-white'"
                          :placeholder="cell.isBlank ? `Blank ${cell.blankIndex || ''}` : 'Nội dung ô'" />
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Row Controls -->
            <div class="flex gap-2">
              <button type="button" @click="addTableRow" class="btn btn-sm btn-outline">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Thêm hàng
              </button>
              <button v-if="tableCompletionData.rows.length > 1" type="button" @click="removeTableRow"
                class="btn btn-sm btn-outline btn-error">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                </svg>
                Xóa hàng cuối
              </button>
            </div>

            <!-- Correct Answers -->
            <div class="border-t pt-4">
              <h4 class="font-medium text-gray-700 mb-3">Đáp án cho các chỗ trống</h4>
              <div class="space-y-2">
                <div v-for="(blank, index) in getTableBlanks()" :key="`blank-${index}`"
                  class="flex items-center gap-3">
                  <span class="badge badge-primary">Blank {{ index + 1 }}</span>
                  <input v-model="blank.correctAnswer" type="text"
                    class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    :placeholder="`Đáp án cho blank ${index + 1}`" />
                </div>
                <p v-if="getTableBlanks().length === 0" class="text-sm text-gray-500 italic">
                  Chưa có chỗ trống nào. Tích vào checkbox ở các ô trong bảng để đánh dấu là chỗ trống.
                </p>
              </div>
            </div>
          </div>

          <!-- Short Answer / Essay -->
          <div v-if="isShortAnswer || isEssay" class="border rounded-lg p-4">
            <h3 class="font-medium text-gray-800 mb-2">
              {{ isEssay ? 'Gợi ý chấm điểm' : 'Đáp án mẫu' }}
            </h3>
            <textarea v-model="form.sample_answer" rows="3"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              :placeholder="isEssay ? 'Các tiêu chí chấm điểm...' : 'Đáp án mẫu...'"></textarea>
          </div>

          <!-- Explanation -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Giải thích đáp án</label>
            <textarea v-model="form.explanation" rows="2"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Giải thích tại sao đáp án đúng..."></textarea>
          </div>

          <!-- Tags -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium text-gray-700">Tags</label>
              <button type="button" @click="showAddTagModal = true" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tạo tag mới
              </button>
            </div>

            <!-- Selected Tags -->
            <div class="flex flex-wrap gap-2 p-3 border rounded-lg bg-gray-50 min-h-[42px]">
              <div v-for="tagId in form.tag_ids" :key="tagId" class="flex items-center gap-1 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                <span>{{ getTagName(tagId) }}</span>
                <button type="button" @click="removeTag(tagId)" class="hover:text-blue-900">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <span v-if="form.tag_ids.length === 0" class="text-gray-400 text-sm">Chưa có tag nào</span>
            </div>

            <!-- Available Tags -->
            <div class="mt-2">
              <p class="text-xs text-gray-500 mb-2">Click để thêm tag:</p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="tag in availableTags"
                  :key="tag.id"
                  type="button"
                  @click="addTag(tag.id)"
                  :disabled="form.tag_ids.includes(tag.id)"
                  class="px-3 py-1 text-sm border rounded-full hover:bg-blue-50 hover:border-blue-300 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                >
                  {{ tag.name }}
                </button>
                <span v-if="availableTags.length === 0" class="text-gray-400 text-sm">Không có tag nào. Hãy tạo tag mới!</span>
              </div>
            </div>
          </div>

          <!-- Preview Section -->
          <div v-if="showPreview" class="mt-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
              <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center shadow-md">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </div>
                <div>
                  <h4 class="font-bold text-lg text-gray-800">Preview câu hỏi</h4>
                  <p class="text-sm text-gray-600">Xem trước giao diện câu hỏi cho học sinh</p>
                </div>
              </div>
              <div class="bg-gray-50 rounded-xl p-6 border-2 border-gray-200">
                <QuestionRenderer
                  :question="previewQuestion"
                  :modelValue="previewAnswer"
                  :show-answer="false"
                  @update:modelValue="previewAnswer = $event"
                  @answer="() => {}"
                />
              </div>
            </div>
          </div>

        </form>

        <!-- Footer Actions -->
        <div class="sticky bottom-0 bg-white border-t px-6 py-4 flex justify-between items-center shadow-lg">
          <button type="button" @click="showPreview = !showPreview" class="px-5 py-2.5 border-2 border-indigo-300 text-indigo-700 rounded-lg hover:bg-indigo-50 transition-all flex items-center gap-2 font-medium shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            {{ showPreview ? 'Ẩn' : 'Xem' }} Preview
          </button>
          <div class="flex gap-3">
            <button type="button" @click="$emit('close')"
              class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-all font-medium shadow-sm">
              Hủy
            </button>
            <button @click="saveQuestion" type="button" :disabled="saving"
              class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 text-white rounded-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed font-medium shadow-md flex items-center gap-2">
              <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              {{ saving ? 'Đang lưu...' : (isEditing ? 'Cập nhật câu hỏi' : 'Tạo câu hỏi') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Tag Modal -->
    <div v-if="showAddTagModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showAddTagModal = false"></div>
      <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Quản lý Tags</h3>

        <!-- My Tags Section -->
        <div class="mb-6">
          <h4 class="text-sm font-medium text-gray-700 mb-2">Tags của tôi</h4>
          <div v-if="myTags.length > 0" class="space-y-2 max-h-48 overflow-y-auto border rounded-lg p-3 bg-gray-50">
            <div v-for="tag in myTags" :key="tag.id"
              class="flex items-center justify-between p-2 bg-white rounded border hover:border-blue-300">
              <span class="text-sm text-gray-800">{{ tag.name }}</span>
              <button type="button" @click="deleteTag(tag.id)" :disabled="deletingTagId === tag.id"
                class="text-red-600 hover:text-red-800 disabled:opacity-50 p-1">
                <svg v-if="deletingTagId === tag.id" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
          <p v-else class="text-sm text-gray-500 italic">Bạn chưa tạo tag nào.</p>
        </div>

        <!-- Create New Tag Form -->
        <div class="border-t pt-4">
          <h4 class="text-sm font-medium text-gray-700 mb-2">Tạo tag mới</h4>
          <form @submit.prevent="createTag">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Tên tag *</label>
              <input v-model="newTagName" type="text" required
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="Nhập tên tag..." />
            </div>
            <div class="flex justify-end gap-3">
              <button type="button" @click="showAddTagModal = false"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Đóng
              </button>
              <button type="submit" :disabled="creatingTag"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                {{ creatingTag ? 'Đang tạo...' : 'Tạo tag' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- AI Question Generator Modal -->
    <AIQuestionGenerator
      v-if="showAIGenerator"
      @close="showAIGenerator = false"
      @saved="handleAISaved"
      :subjectId="props.subjectId"
      :subject="props.subject"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useQuestionStore } from '@/stores/examination'
import QuestionRenderer from '@/components/examination/questions/QuestionRenderer.vue'
import AIQuestionGenerator from '@/components/examination/AIQuestionGenerator.vue'
import api from '@/api'
import Swal from 'sweetalert2'

const props = defineProps({
  question: {
    type: Object,
    default: null
  },
  subjectId: {
    type: Number,
    default: null
  },
  subject: {
    type: Object,
    default: null
  },
  context: {
    type: String,
    default: 'examination', // 'examination' or 'homework'
    validator: (value) => ['examination', 'homework'].includes(value)
  }
})

const emit = defineEmits(['close', 'saved'])

const store = useQuestionStore()
const saving = ref(false)
const categories = ref([])
const subjects = ref([])
const subjectCategories = ref([])
const tags = ref([])
const showAddTagModal = ref(false)
const showAIGenerator = ref(false)
const newTagName = ref('')
const creatingTag = ref(false)
const contentEditor = ref(null)
const showPreview = ref(false)
const previewAnswer = ref([])

// Drag & Drop refs
const dragDropCanvas = ref(null)
const itemImageInput = ref(null)
const backgroundEditor = ref(null)
const backgroundImageInput = ref(null)
const dragDropSettings = ref({
  backgroundContent: '', // Rich text content for background layer
  containerHeight: 400,
  items: [],
  dropZones: [],
  correctAnswers: []
})
const draggingZone = ref(null)
const resizingZone = ref(null)
const dragStartPos = ref({ x: 0, y: 0 })

// Table Completion Settings
const tableCompletionData = ref({
  headers: ['Column 1', 'Column 2'],
  rows: [
    [
      { content: '', isBlank: false },
      { content: '', isBlank: false }
    ]
  ]
})

const isEditing = computed(() => !!props.question)

const questionTypes = [
  { value: 'multiple_choice', label: 'Trắc nghiệm (1 đáp án)' },
  { value: 'multiple_response', label: 'Trắc nghiệm (nhiều đáp án)' },
  { value: 'true_false', label: 'Đúng/Sai' },
  { value: 'true_false_ng', label: 'True/False/Not Given' },
  { value: 'fill_blanks', label: 'Điền vào chỗ trống' },
  { value: 'drag_drop', label: 'Kéo và thả' },
  { value: 'matching', label: 'Nối cột' },
  { value: 'matching_headings', label: 'Matching Headings' },
  { value: 'matching_features', label: 'Matching Features' },
  { value: 'matching_sentence_endings', label: 'Matching Sentence Endings' },
  { value: 'short_answer', label: 'Trả lời ngắn' },
  { value: 'sentence_completion', label: 'Sentence Completion' },
  { value: 'summary_completion', label: 'Summary Completion' },
  { value: 'note_completion', label: 'Note Completion' },
  { value: 'table_completion', label: 'Table Completion' },
  { value: 'ordering', label: 'Sắp xếp thứ tự' },
  { value: 'essay', label: 'Viết luận' },
  { value: 'audio_response', label: 'Trả lời bằng audio' }
]

const defaultForm = {
  type: '',
  skill: 'general',
  difficulty: 'medium',
  points: 1,
  title: '',
  content: '',
  audio_url: '',
  options: [
    { text: '', is_correct: false },
    { text: '', is_correct: false },
    { text: '', is_correct: false },
    { text: '', is_correct: false }
  ],
  matching_pairs: [
    { left: '', right: '' },
    { left: '', right: '' },
    { left: '', right: '' }
  ],
  blank_answers: [''],
  sample_answer: '',
  explanation: '',
  category_id: '',
  subject_id: '',
  subject_category_id: '',
  tag_ids: [],
  status: 'draft'
}

// Fetch subjects and tags on mount
onMounted(async () => {
  await fetchSubjects()
  await fetchTags()

  // When subjectId is from props (syllabus context), don't fetch categories
  // because subject_id will be set to null when saving (to avoid FK constraint)
  // Categories are only needed when manually selecting from exam_subjects
  if (props.subjectId && !form.subject_id) {
    form.subject_id = props.subjectId
    // Skip fetching categories - they're not compatible with syllabus subjects
  }
})

async function fetchSubjects() {
  try {
    const response = await api.get('/examination/subjects')
    subjects.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching subjects:', error)
  }
}

async function fetchSubjectCategories(subjectId) {
  if (!subjectId) {
    subjectCategories.value = []
    return
  }
  try {
    const response = await api.get(`/examination/subjects/${subjectId}/categories`)
    subjectCategories.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching subject categories:', error)
    subjectCategories.value = []
  }
}

function onSubjectChange() {
  form.value.subject_category_id = ''
  fetchSubjectCategories(form.value.subject_id)
}

async function fetchTags() {
  try {
    const response = await api.get('/examination/question-tags')
    tags.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching tags:', error)
  }
}

const availableTags = computed(() => {
  return tags.value.filter(tag => !form.value.tag_ids.includes(tag.id))
})

function getTagName(tagId) {
  const tag = tags.value.find(t => t.id === tagId)
  return tag ? tag.name : ''
}

function addTag(tagId) {
  if (!form.value.tag_ids.includes(tagId)) {
    form.value.tag_ids.push(tagId)
  }
}

function removeTag(tagId) {
  const index = form.value.tag_ids.indexOf(tagId)
  if (index > -1) {
    form.value.tag_ids.splice(index, 1)
  }
}

async function createTag() {
  if (!newTagName.value.trim()) return

  creatingTag.value = true
  try {
    const response = await api.post('/examination/question-tags', {
      name: newTagName.value.trim()
    })

    const newTag = response.data.data
    tags.value.push(newTag)
    form.value.tag_ids.push(newTag.id)

    newTagName.value = ''
    showAddTagModal.value = false
  } catch (error) {
    console.error('Error creating tag:', error)
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể tạo tag: ' + (error.response?.data?.message || error.message),
    })
  } finally {
    creatingTag.value = false
  }
}

const myTags = computed(() => {
  return tags.value.filter(tag => tag.can_delete === true)
})

const deletingTagId = ref(null)

async function deleteTag(tagId) {
  if (!confirm('Bạn có chắc chắn muốn xóa tag này?')) return

  deletingTagId.value = tagId
  try {
    await api.delete(`/examination/question-tags/${tagId}`)

    // Remove from tags list
    const index = tags.value.findIndex(t => t.id === tagId)
    if (index > -1) {
      tags.value.splice(index, 1)
    }

    // Remove from selected tags if it was selected
    removeTag(tagId)
  } catch (error) {
    console.error('Error deleting tag:', error)
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể xóa tag: ' + (error.response?.data?.message || error.message),
    })
  } finally {
    deletingTagId.value = null
  }
}

function handleAISaved() {
  showAIGenerator.value = false
  emit('saved')
}

const form = ref({ ...defaultForm })

const hasOptions = computed(() =>
  ['multiple_choice', 'multiple_response', 'true_false', 'true_false_ng'].includes(form.value.type)
)

const isMatching = computed(() =>
  ['matching', 'matching_headings', 'matching_features', 'matching_sentence_endings'].includes(form.value.type)
)

const isFillBlanks = computed(() =>
  ['fill_blanks', 'sentence_completion', 'summary_completion', 'note_completion'].includes(form.value.type)
)

const isShortAnswer = computed(() => form.value.type === 'short_answer')
const isEssay = computed(() => form.value.type === 'essay')
const isDragDrop = computed(() => ['drag_drop', 'labeling', 'hotspot'].includes(form.value.type))

const isTableCompletion = computed(() => form.value.type === 'table_completion')

const needsExtendedContent = computed(() => isEssay.value)

const blankCount = computed(() => {
  const matches = form.value.title.match(/\[blank\]/g)
  return matches ? matches.length : 0
})

const previewQuestion = computed(() => {
  // Convert [blank] to ___ format for preview
  let previewTitle = form.value.title
  let blankCounter = 1
  previewTitle = previewTitle.replace(/\[blank\]/g, () => {
    return `___${blankCounter++}___`
  })

  const preview = {
    id: 'preview',
    type: form.value.type,
    title: previewTitle,
    content: {
      text: previewTitle
    },
    options: form.value.options.map(opt => ({
      content: opt.text,
      is_correct: opt.is_correct
    })),
    correct_answer: form.value.blank_answers, // Array format
    points: form.value.points,
    settings: {},
  }

  // Add drag-drop settings for preview
  if (isDragDrop.value) {
    preview.settings = {
      ...dragDropSettings.value
    }
  }

  // Add table completion settings for preview
  if (isTableCompletion.value) {
    // Collect correct answers from blank cells
    const correctAnswers = []
    tableCompletionData.value.rows.forEach(row => {
      row.forEach(cell => {
        if (cell.isBlank) {
          correctAnswers.push(cell.correctAnswer || '')
        }
      })
    })

    preview.settings = {
      tableData: {
        headers: tableCompletionData.value.headers,
        rows: tableCompletionData.value.rows
      }
    }
    preview.correct_answer = correctAnswers
  }

  return preview
})

watch(() => props.question, async (newQuestion) => {
  if (newQuestion) {
    form.value = {
      ...defaultForm,
      ...newQuestion,
      subject_id: newQuestion.subject_id || '',
      subject_category_id: newQuestion.subject_category_id || '',
      tag_ids: newQuestion.tag_ids || newQuestion.tags?.map(t => t.id) || [],
      options: newQuestion.options?.length
        ? newQuestion.options.map(o => ({
            text: o.content || o.text,
            is_correct: o.is_correct,
            feedback: o.feedback
          }))
        : defaultForm.options,
      matching_pairs: newQuestion.matching_pairs?.length ? newQuestion.matching_pairs : defaultForm.matching_pairs,
      blank_answers: newQuestion.blank_answers?.length ? newQuestion.blank_answers : defaultForm.blank_answers
    }
    // Load subject categories if editing and has subject
    if (newQuestion.subject_id) {
      await fetchSubjectCategories(newQuestion.subject_id)
    }

    // Load drag-drop settings if exists
    if (newQuestion.settings && ['drag_drop', 'labeling', 'hotspot'].includes(newQuestion.type)) {
      dragDropSettings.value = {
        backgroundContent: newQuestion.settings.backgroundContent || '',
        containerHeight: newQuestion.settings.containerHeight || 400,
        items: newQuestion.settings.items || [],
        dropZones: newQuestion.settings.dropZones || [],
        correctAnswers: newQuestion.settings.correctAnswers || []
      }

      // Update background editor content (use nextTick to ensure ref is ready)
      nextTick(() => {
        if (backgroundEditor.value) {
          backgroundEditor.value.innerHTML = dragDropSettings.value.backgroundContent
        }
      })
    }

    // Load table completion settings if exists
    if (newQuestion.settings && newQuestion.type === 'table_completion') {
      tableCompletionData.value = {
        headers: newQuestion.settings.tableData?.headers || ['Column 1', 'Column 2'],
        rows: newQuestion.settings.tableData?.rows || [
          [
            { content: '', isBlank: false },
            { content: '', isBlank: false }
          ]
        ]
      }
    }
  } else {
    // Reset form with subject_id from prop if provided
    form.value = {
      ...defaultForm,
      subject_id: props.subjectId || ''
    }

    // Don't fetch categories when subjectId is from props (syllabus context)
    // because it's from subjects table, not exam_subjects table
    subjectCategories.value = []

    dragDropSettings.value = {
      backgroundImage: null,
      containerHeight: 400,
      items: [],
      dropZones: [],
      correctAnswers: []
    }
    tableCompletionData.value = {
      headers: ['Column 1', 'Column 2'],
      rows: [
        [
          { content: '', isBlank: false },
          { content: '', isBlank: false }
        ]
      ]
    }
  }
}, { immediate: true })

// Watch blank count and sync blank answers
watch(blankCount, (newCount) => {
  if (newCount > form.value.blank_answers.length) {
    // Add new blank answers
    for (let i = form.value.blank_answers.length; i < newCount; i++) {
      form.value.blank_answers.push('')
    }
  } else if (newCount < form.value.blank_answers.length) {
    // Remove excess blank answers
    form.value.blank_answers = form.value.blank_answers.slice(0, newCount)
  }

  // Also update preview answer array
  previewAnswer.value = Array(newCount).fill('')
})

// Sync contentEditor innerHTML when form.title changes (only when not typing)
let isTyping = false
watch(() => form.value.title, (newTitle) => {
  if (!isTyping && contentEditor.value && isFillBlanks.value) {
    if (contentEditor.value.innerHTML !== newTitle) {
      contentEditor.value.innerHTML = newTitle || ''
    }
  }
})

// Watch for question type change to initialize editor
watch(() => form.value.type, () => {
  if (isFillBlanks.value && contentEditor.value) {
    contentEditor.value.innerHTML = form.value.title || ''
  }
})

function addOption() {
  form.value.options.push({ text: '', is_correct: false })
}

function removeOption(index) {
  form.value.options.splice(index, 1)
}

function addMatchingPair() {
  form.value.matching_pairs.push({ left: '', right: '' })
}

function removeMatchingPair(index) {
  form.value.matching_pairs.splice(index, 1)
}

function addBlankAnswer() {
  form.value.blank_answers.push('')
}

// Rich Editor Functions
function insertBlank() {
  const editor = contentEditor.value
  if (!editor) return

  const selection = window.getSelection()
  const range = selection.getRangeAt(0)

  // Insert [blank] at cursor position
  const blankNode = document.createTextNode('[blank]')
  range.deleteContents()
  range.insertNode(blankNode)

  // Move cursor after [blank]
  range.setStartAfter(blankNode)
  range.setEndAfter(blankNode)
  selection.removeAllRanges()
  selection.addRange(range)

  // Update form content
  form.value.title = editor.innerHTML

  // Focus back to editor
  editor.focus()
}

function formatContent(command) {
  document.execCommand(command, false, null)
  if (contentEditor.value) {
    form.value.title = contentEditor.value.innerHTML
  }
}

function updateContentFromEditor(event) {
  isTyping = true
  form.value.title = event.target.innerHTML
  // Reset isTyping after a short delay
  setTimeout(() => {
    isTyping = false
  }, 100)
}

// ===== Drag & Drop Functions =====

// Background Image
// Background Editor Functions
function formatBackground(command) {
  const editor = backgroundEditor.value
  if (!editor) return

  // Focus editor first to ensure cursor position is correct
  editor.focus()

  // Execute command
  document.execCommand(command, false, null)

  // Update content
  dragDropSettings.value.backgroundContent = editor.innerHTML
}

function updateBackgroundContent(event) {
  dragDropSettings.value.backgroundContent = event.target.innerHTML
}

function insertBackgroundImage() {
  backgroundImageInput.value?.click()
}

function onBackgroundImageInsert(event) {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      const editor = backgroundEditor.value
      if (!editor) return

      // Insert image at cursor or end
      const img = document.createElement('img')
      img.src = e.target.result
      img.style.maxWidth = '200px'
      img.style.height = 'auto'
      img.style.display = 'inline-block'
      img.style.margin = '4px'

      const selection = window.getSelection()
      if (selection.rangeCount > 0) {
        const range = selection.getRangeAt(0)
        range.insertNode(img)
        range.setStartAfter(img)
        range.setEndAfter(img)
        selection.removeAllRanges()
        selection.addRange(range)
      } else {
        editor.appendChild(img)
      }

      // Update content
      dragDropSettings.value.backgroundContent = editor.innerHTML
      editor.focus()
    }
    reader.readAsDataURL(file)
  }
  // Reset input
  event.target.value = ''
}

// Add Items
function addTextItem() {
  const text = prompt('Nhập nội dung text:')
  if (text) {
    dragDropSettings.value.items.push({
      type: 'text',
      content: text
    })
  }
}

function triggerImageUpload() {
  itemImageInput.value?.click()
}

function onItemImageUpload(event) {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      // Create img element to crop to square
      const img = new Image()
      img.onload = () => {
        // Fixed square size (30% of original 120px = 36px, rounded to 40px)
        const size = 40

        // Create canvas for cropping
        const canvas = document.createElement('canvas')
        canvas.width = size
        canvas.height = size
        const ctx = canvas.getContext('2d')

        // Calculate crop dimensions (center crop to square)
        const sourceSize = Math.min(img.width, img.height)
        const sourceX = (img.width - sourceSize) / 2
        const sourceY = (img.height - sourceSize) / 2

        // Draw cropped and resized image
        ctx.drawImage(
          img,
          sourceX, sourceY, sourceSize, sourceSize, // Source (crop to square)
          0, 0, size, size // Destination (resize to fixed size)
        )

        // Get cropped image as base64
        const croppedImage = canvas.toDataURL('image/png')

        dragDropSettings.value.items.push({
          type: 'image',
          content: croppedImage,
          width: size,
          height: size
        })
      }
      img.src = e.target.result
    }
    reader.readAsDataURL(file)
  }
  // Reset input
  event.target.value = ''
}

function removeItem(index) {
  dragDropSettings.value.items.splice(index, 1)
  // Update correct answers
  dragDropSettings.value.correctAnswers = dragDropSettings.value.correctAnswers.map(ans => {
    if (ans === index) return null
    if (ans > index) return ans - 1
    return ans
  })
}

// Add Drop Zones
function addDropZone() {
  dragDropSettings.value.dropZones.push({
    x: 20,
    y: 20,
    width: 15,
    height: 15
  })
  dragDropSettings.value.correctAnswers.push(null)
}

function removeDropZone(index) {
  dragDropSettings.value.dropZones.splice(index, 1)
  dragDropSettings.value.correctAnswers.splice(index, 1)
}

// Drag Zone
function startDragZone(index, event) {
  if (event.button !== 0) return
  draggingZone.value = index
  const canvas = dragDropCanvas.value
  const rect = canvas.getBoundingClientRect()
  dragStartPos.value = {
    x: event.clientX,
    y: event.clientY,
    zoneX: dragDropSettings.value.dropZones[index].x,
    zoneY: dragDropSettings.value.dropZones[index].y
  }

  document.addEventListener('mousemove', onDragZoneMove)
  document.addEventListener('mouseup', onDragZoneEnd)
  event.preventDefault()
}

function onDragZoneMove(event) {
  if (draggingZone.value === null) return

  const canvas = dragDropCanvas.value
  const rect = canvas.getBoundingClientRect()
  const deltaX = event.clientX - dragStartPos.value.x
  const deltaY = event.clientY - dragStartPos.value.y

  const newX = dragStartPos.value.zoneX + (deltaX / rect.width * 100)
  const newY = dragStartPos.value.zoneY + (deltaY / rect.height * 100)

  dragDropSettings.value.dropZones[draggingZone.value].x = Math.max(0, Math.min(95, newX))
  dragDropSettings.value.dropZones[draggingZone.value].y = Math.max(0, Math.min(95, newY))
}

function onDragZoneEnd() {
  draggingZone.value = null
  document.removeEventListener('mousemove', onDragZoneMove)
  document.removeEventListener('mouseup', onDragZoneEnd)
}

// Resize Zone
function startResizeZone(index, event) {
  if (event.button !== 0) return
  resizingZone.value = index
  const canvas = dragDropCanvas.value
  const rect = canvas.getBoundingClientRect()
  const zone = dragDropSettings.value.dropZones[index]
  dragStartPos.value = {
    x: event.clientX,
    y: event.clientY,
    width: zone.width,
    height: zone.height
  }

  document.addEventListener('mousemove', onResizeZoneMove)
  document.addEventListener('mouseup', onResizeZoneEnd)
  event.preventDefault()
}

function onResizeZoneMove(event) {
  if (resizingZone.value === null) return

  const canvas = dragDropCanvas.value
  const rect = canvas.getBoundingClientRect()
  const deltaX = event.clientX - dragStartPos.value.x
  const deltaY = event.clientY - dragStartPos.value.y

  const newWidth = Math.max(5, dragStartPos.value.width + (deltaX / rect.width * 100))
  const newHeight = Math.max(5, dragStartPos.value.height + (deltaY / rect.height * 100))

  dragDropSettings.value.dropZones[resizingZone.value].width = Math.min(50, newWidth)
  dragDropSettings.value.dropZones[resizingZone.value].height = Math.min(50, newHeight)
}

function onResizeZoneEnd() {
  resizingZone.value = null
  document.removeEventListener('mousemove', onResizeZoneMove)
  document.removeEventListener('mouseup', onResizeZoneEnd)
}

function getItemLabel(item, index) {
  if (item.type === 'text') {
    const text = item.content.replace(/<[^>]*>/g, '')
    return text.substring(0, 30) + (text.length > 30 ? '...' : '')
  }
  return `Hình ${index + 1}`
}

// ===== End Drag & Drop Functions =====

// ===== Table Completion Functions =====

function addTableColumn() {
  tableCompletionData.value.headers.push(`Column ${tableCompletionData.value.headers.length + 1}`)
  // Add a cell to each existing row
  tableCompletionData.value.rows.forEach(row => {
    row.push({ content: '', isBlank: false })
  })
}

function removeTableColumn(index) {
  if (tableCompletionData.value.headers.length <= 1) {
    Swal.fire({
      icon: 'warning',
      title: 'Không thể xóa',
      text: 'Bảng phải có ít nhất 1 cột',
    })
    return
  }
  tableCompletionData.value.headers.splice(index, 1)
  // Remove the cell from each row
  tableCompletionData.value.rows.forEach(row => {
    row.splice(index, 1)
  })
  updateBlankIndexes()
}

function addTableRow() {
  const newRow = []
  for (let i = 0; i < tableCompletionData.value.headers.length; i++) {
    newRow.push({ content: '', isBlank: false })
  }
  tableCompletionData.value.rows.push(newRow)
}

function removeTableRow() {
  if (tableCompletionData.value.rows.length <= 1) {
    Swal.fire({
      icon: 'warning',
      title: 'Không thể xóa',
      text: 'Bảng phải có ít nhất 1 hàng',
    })
    return
  }
  tableCompletionData.value.rows.pop()
  updateBlankIndexes()
}

function updateBlankIndexes() {
  let blankIndex = 1
  tableCompletionData.value.rows.forEach(row => {
    row.forEach(cell => {
      if (cell.isBlank) {
        cell.blankIndex = blankIndex++
      } else {
        delete cell.blankIndex
      }
    })
  })
}

function getTableBlanks() {
  const blanks = []
  tableCompletionData.value.rows.forEach(row => {
    row.forEach(cell => {
      if (cell.isBlank) {
        blanks.push({
          blankIndex: cell.blankIndex,
          correctAnswer: cell.correctAnswer || ''
        })
      }
    })
  })
  return blanks
}

// ===== End Table Completion Functions =====

async function saveQuestion() {
  saving.value = true
  try {
    // Validate drag-drop questions
    if (isDragDrop.value) {
      if (dragDropSettings.value.items.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Thiếu dữ liệu',
          text: 'Vui lòng thêm ít nhất một phần tử kéo thả',
        })
        saving.value = false
        return
      }
      if (dragDropSettings.value.dropZones.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Thiếu dữ liệu',
          text: 'Vui lòng thêm ít nhất một vùng thả',
        })
        saving.value = false
        return
      }
      // Check if all correct answers are set (not null)
      const hasUnsetAnswers = dragDropSettings.value.correctAnswers.some(ans => ans === null || ans === undefined)
      if (hasUnsetAnswers) {
        Swal.fire({
          icon: 'warning',
          title: 'Thiếu đáp án',
          text: 'Vui lòng thiết lập đáp án đúng cho TẤT CẢ các vùng thả',
        })
        saving.value = false
        return
      }
    }

    // Validate table completion questions
    if (isTableCompletion.value) {
      // Check if there's at least one blank
      const hasBlank = tableCompletionData.value.rows.some(row =>
        row.some(cell => cell.isBlank)
      )
      if (!hasBlank) {
        alert('Vui lòng đánh dấu ít nhất một ô là chỗ trống')
        saving.value = false
        return
      }

      // Check if all blanks have correct answers
      const hasEmptyAnswer = tableCompletionData.value.rows.some(row =>
        row.some(cell => cell.isBlank && (!cell.correctAnswer || cell.correctAnswer.trim() === ''))
      )
      if (hasEmptyAnswer) {
        alert('Vui lòng điền đáp án đúng cho TẤT CẢ các chỗ trống')
        saving.value = false
        return
      }
    }

    const data = prepareFormData()
    console.log('📤 Sending data to backend:', data)

    let savedQuestion

    // Use different API based on context
    if (props.context === 'homework') {
      // Save to homework_exercises table
      // homework_exercises has different schema than questions table
      // Map the fields to match homework_exercises structure
      const homeworkData = {
        skill: data.skill,
        difficulty: data.difficulty,
        type: data.type,
        title: data.title,
        content: data.content,
        explanation: data.explanation,
        correct_answer: data.correct_answer,
        points: data.points,
        time_limit: data.time_limit,
        tags: data.tag_ids,
        settings: data.settings || {},
        subject_id: data.subject_id,
        subject_category_id: data.subject_category_id,
        status: data.status || 'active'
      }

      const branchId = localStorage.getItem('current_branch_id')
      const endpoint = isEditing.value
        ? `/api/homework/exercises/${props.question.id}`
        : '/api/homework/exercises'
      const method = isEditing.value ? 'put' : 'post'

      const response = await axios[method](endpoint, homeworkData, {
        headers: {
          'X-Branch-Id': branchId
        }
      })
      savedQuestion = response.data
    } else {
      // Save to questions table (examination)
      if (isEditing.value) {
        savedQuestion = await store.updateQuestion(props.question.id, data)
      } else {
        savedQuestion = await store.createQuestion(data)
      }
    }

    // Emit the saved question data (contains id, points, etc.)
    emit('saved', savedQuestion?.data || savedQuestion)
  } catch (error) {
    console.error('❌ Error saving question:', error)

    // Log validation errors if available
    if (error.response?.data?.errors) {
      console.error('📋 Validation errors:', error.response.data.errors)
      const errorMessages = Object.entries(error.response.data.errors)
        .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
        .join('\n')
      alert('Lỗi validation:\n' + errorMessages)
    } else if (error.response?.data?.message) {
      alert('Lỗi: ' + error.response.data.message)
    } else {
      alert('Có lỗi xảy ra khi lưu câu hỏi')
    }
  } finally {
    saving.value = false
  }
}

function prepareFormData() {
  const data = {
    type: form.value.type,
    skill: form.value.skill || 'general',
    difficulty: form.value.difficulty,
    points: form.value.points,
    title: form.value.title,
    explanation: form.value.explanation,
    category_id: form.value.category_id || null,
    // Don't send subject_id from syllabus context (use null to avoid FK constraint with exam_subjects)
    subject_id: props.subjectId ? null : (form.value.subject_id || null),
    subject_category_id: form.value.subject_category_id || null,
    tag_ids: form.value.tag_ids || [],
    status: form.value.status
  }

  // Only send content if it's an array (backend expects array, not string)
  if (Array.isArray(form.value.content) && form.value.content.length > 0) {
    data.content = form.value.content
  }

  if (form.value.audio_url) {
    data.audio_url = form.value.audio_url
  }

  // Add type-specific data
  if (hasOptions.value) {
    data.options = form.value.options
      .filter(o => o.text.trim())
      .map(o => ({
        content: o.text,
        is_correct: o.is_correct,
        feedback: o.feedback
      }))
  }

  if (isMatching.value) {
    data.matching_pairs = form.value.matching_pairs.filter(p => p.left.trim() && p.right.trim())
  }

  if (isFillBlanks.value) {
    data.blank_answers = form.value.blank_answers.filter(a => a.trim())
  }

  if (isShortAnswer.value || isEssay.value) {
    data.sample_answer = form.value.sample_answer
  }

  if (isDragDrop.value) {
    data.settings = {
      ...dragDropSettings.value
    }
  }

  if (isTableCompletion.value) {
    // Collect correct answers from blank cells
    const correctAnswers = []
    tableCompletionData.value.rows.forEach(row => {
      row.forEach(cell => {
        if (cell.isBlank) {
          correctAnswers.push(cell.correctAnswer || '')
        }
      })
    })

    data.settings = {
      tableData: {
        headers: tableCompletionData.value.headers,
        rows: tableCompletionData.value.rows
      }
    }
    data.correct_answer = correctAnswers
  }

  return data
}
</script>
