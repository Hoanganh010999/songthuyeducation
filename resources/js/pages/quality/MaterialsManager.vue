<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <button
              @click="goBack"
              class="text-gray-600 hover:text-gray-900"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
            </button>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">
                {{ isEditMode ? 'Edit Material' : 'New Material' }}
              </h1>
              <p class="text-sm text-gray-500 mt-1">{{ lessonInfo.lesson_title }}</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <button
              v-if="!isEditMode"
              @click="generateWithAI"
              :disabled="generating"
              class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center space-x-2"
            >
              <svg v-if="generating" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
              <span>{{ generating ? 'Generating...' : 'Generate with AI' }}</span>
            </button>
            <button
              @click="saveMaterial"
              :disabled="saving"
              class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center space-x-2"
            >
              <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ saving ? 'Saving...' : 'Save Material' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      </div>

      <div v-else class="bg-white rounded-lg shadow-lg p-6">
        <!-- Lesson Info (Read-only) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 pb-6 border-b">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Lesson Focus</label>
            <div class="px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700">
              {{ lessonInfo.lesson_focus || 'N/A' }}
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
            <div class="px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700">
              {{ lessonInfo.level || 'N/A' }}
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Duration (Mins)</label>
            <div class="px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700">
              {{ lessonInfo.duration || 'N/A' }}
            </div>
          </div>
        </div>

        <!-- Material Form -->
        <div class="space-y-6">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Material Title <span class="text-red-500">*</span>
            </label>
            <input
              v-model="materialData.title"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
              placeholder="Enter material title"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Description
            </label>
            <textarea
              v-model="materialData.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
              placeholder="Brief description of the material"
            ></textarea>
          </div>

          <!-- Content Editor -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Content <span class="text-red-500">*</span>
              <span class="text-gray-500 font-normal ml-2">(Use the editor below to create your teaching materials)</span>
            </label>
            <div
              v-if="materialData.content && !isEditingContent"
              class="border rounded-lg p-4 prose max-w-none"
              style="min-height: 400px; background: white;"
            >
              <div class="flex justify-between items-center mb-4 pb-2 border-b">
                <h4 class="text-sm font-medium text-gray-700">Preview</h4>
                <div class="flex items-center space-x-2">
                  <button
                    @click="printMaterial"
                    class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 flex items-center space-x-1"
                    title="Print Material"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Print</span>
                  </button>
                  <button
                    @click="downloadWord"
                    class="text-sm px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 flex items-center space-x-1"
                    title="Download as Word"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Word</span>
                  </button>
                  <button
                    @click="showAIEditModal = true"
                    class="text-sm px-3 py-1 bg-purple-100 text-purple-700 rounded hover:bg-purple-200 flex items-center space-x-1"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <span>Edit with AI</span>
                  </button>
                  <button
                    @click="isEditingContent = true"
                    class="text-sm text-purple-600 hover:text-purple-700"
                  >
                    Edit
                  </button>
                </div>
              </div>
              <div
                v-html="materialData.content"
                class="material-content"
                @mouseup="handleTextSelection"
                ref="contentPreview"
              ></div>
            </div>
            <div v-else class="border rounded-lg" style="background: white;">
              <!-- QuillEditor Header with AI Button and Done Button -->
              <div class="flex justify-between items-center px-4 py-2 border-b bg-gray-50">
                <h4 class="text-sm font-medium text-gray-700">Content Editor</h4>
                <div class="flex items-center space-x-2">
                  <button
                    @click="showAIEditModal = true"
                    class="text-sm px-3 py-1 bg-purple-100 text-purple-700 rounded hover:bg-purple-200 flex items-center space-x-1"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <span>Edit with AI</span>
                  </button>
                  <button
                    v-if="materialData.content"
                    @click="isEditingContent = false"
                    class="text-sm px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200"
                  >
                    Done Editing
                  </button>
                </div>
              </div>
              <!-- TipTap Toolbar -->
              <div v-if="editor" class="border-b bg-gray-50 p-2 flex flex-wrap gap-1">
                <!-- Headings -->
                <button
                  @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                  :class="{ 'bg-gray-300': editor.isActive('heading', { level: 1 }) }"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm font-bold"
                >
                  H1
                </button>
                <button
                  @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                  :class="{ 'bg-gray-300': editor.isActive('heading', { level: 2 }) }"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm font-bold"
                >
                  H2
                </button>
                <button
                  @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                  :class="{ 'bg-gray-300': editor.isActive('heading', { level: 3 }) }"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm font-bold"
                >
                  H3
                </button>

                <div class="border-r mx-1"></div>

                <!-- Format -->
                <button
                  @click="editor.chain().focus().toggleBold().run()"
                  :class="{ 'bg-gray-300': editor.isActive('bold') }"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm font-bold"
                >
                  B
                </button>
                <button
                  @click="editor.chain().focus().toggleItalic().run()"
                  :class="{ 'bg-gray-300': editor.isActive('italic') }"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm italic"
                >
                  I
                </button>
                <button
                  @click="editor.chain().focus().toggleStrike().run()"
                  :class="{ 'bg-gray-300': editor.isActive('strike') }"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm line-through"
                >
                  S
                </button>

                <div class="border-r mx-1"></div>

                <!-- Lists -->
                <button
                  @click="editor.chain().focus().toggleBulletList().run()"
                  :class="{ 'bg-gray-300': editor.isActive('bulletList') }"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm"
                >
                  • List
                </button>
                <button
                  @click="editor.chain().focus().toggleOrderedList().run()"
                  :class="{ 'bg-gray-300': editor.isActive('orderedList') }"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm"
                >
                  1. List
                </button>

                <div class="border-r mx-1"></div>

                <!-- Table Controls -->
                <button
                  @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm bg-green-50"
                >
                  📊 Insert Table
                </button>
                <button
                  v-if="editor.isActive('table')"
                  @click="editor.chain().focus().addColumnAfter().run()"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm"
                >
                  + Col
                </button>
                <button
                  v-if="editor.isActive('table')"
                  @click="editor.chain().focus().addRowAfter().run()"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm"
                >
                  + Row
                </button>
                <button
                  v-if="editor.isActive('table')"
                  @click="editor.chain().focus().deleteTable().run()"
                  class="px-2 py-1 border rounded hover:bg-red-200 text-sm text-red-600"
                >
                  🗑️ Delete Table
                </button>

                <div class="border-r mx-1"></div>

                <!-- Other -->
                <button
                  @click="editor.chain().focus().toggleBlockquote().run()"
                  :class="{ 'bg-gray-300': editor.isActive('blockquote') }"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm"
                >
                  " Quote
                </button>
                <button
                  @click="editor.chain().focus().setHorizontalRule().run()"
                  class="px-2 py-1 border rounded hover:bg-gray-200 text-sm"
                >
                  ─ HR
                </button>
              </div>

              <!-- TipTap Editor -->
              <EditorContent
                :editor="editor"
                class="tiptap-editor"
              />
            </div>
          </div>
        </div>

        <!-- Helper Text -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <h4 class="text-sm font-medium text-blue-900 mb-2">Tips for Creating Materials:</h4>
          <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
            <li>Include learning objectives and key vocabulary</li>
            <li>Add worksheets, exercises, and activities</li>
            <li>Embed images, videos, or external links</li>
            <li>Organize content with clear headings and sections</li>
            <li>Save frequently to avoid losing your work</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- AI Provider Selection Modal -->
    <div v-if="showAIModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Select AI Provider</h3>
            <button @click="showAIModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6 space-y-4">
          <!-- Provider Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">AI Provider</label>
            <select
              v-model="aiConfig.provider"
              @change="updateModelOptions"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
            >
              <option value="openai">OpenAI</option>
              <option value="anthropic">Anthropic (Claude)</option>
            </select>
          </div>

          <!-- Model Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
            <select
              v-model="aiConfig.model"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
            >
              <option v-for="model in availableModels" :key="model.value" :value="model.value">
                {{ model.label }}
              </option>
            </select>
          </div>

          <!-- Additional Instructions -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Additional Instructions (Optional)
            </label>
            <textarea
              v-model="aiConfig.additionalContext"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 resize-none"
              placeholder="Add specific instructions or requirements for this material...&#10;&#10;Example:&#10;- Focus on beginner level activities&#10;- Include visual aids&#10;- Add pronunciation practice"
            ></textarea>
            <p class="mt-1 text-xs text-gray-500">
              These instructions will be added to the AI prompt to customize the generated material.
            </p>
          </div>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <p class="text-xs text-blue-700">
              Đảm bảo đã thiết lập API key cho provider này trong Quality Settings.
            </p>
          </div>
        </div>

        <div class="p-6 border-t flex justify-end gap-3">
          <button
            @click="showAIModal = false"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="confirmGenerate"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
          >
            Generate
          </button>
        </div>
      </div>
    </div>

    <!-- AI Edit Modal (Edit entire content) -->
    <div v-if="showAIEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Edit Content with AI</h3>
            <button @click="closeAIEditModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              What would you like AI to do with this content?
            </label>
            <textarea
              v-model="aiEditPrompt"
              rows="6"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 resize-none"
              placeholder="Example prompts:&#10;- Simplify the language for beginners&#10;- Add more examples and exercises&#10;- Make it more engaging with activities&#10;- Correct grammar and improve clarity&#10;- Translate to Vietnamese"
              @keydown.ctrl.enter="confirmAIEdit"
            ></textarea>
            <p class="mt-1 text-xs text-gray-500">
              AI will edit the entire content based on your instructions. Press Ctrl+Enter to submit.
            </p>
          </div>

          <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
            <p class="text-xs text-purple-700">
              Using GPT-5.1 to edit content. This may take a few moments.
            </p>
          </div>
        </div>

        <div class="p-6 border-t flex justify-end gap-3">
          <button
            @click="closeAIEditModal"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="confirmAIEdit"
            :disabled="!aiEditPrompt.trim() || processingAIEdit"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center space-x-2"
          >
            <svg v-if="processingAIEdit" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ processingAIEdit ? 'Processing...' : 'Edit with AI' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Text Selection Popup -->
    <div
      v-if="showSelectionPopup"
      :style="{
        position: 'fixed',
        top: selectionPopupPosition.y + 'px',
        left: selectionPopupPosition.x + 'px',
        zIndex: 60
      }"
      class="bg-white rounded-lg shadow-xl border border-gray-200"
    >
      <button
        @click="openTextSelectionEdit"
        class="px-4 py-2 text-sm text-purple-700 hover:bg-purple-50 flex items-center space-x-2 rounded-lg"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
        </svg>
        <span>Edit with AI</span>
      </button>
    </div>

    <!-- Text Selection AI Edit Modal -->
    <div v-if="showTextSelectionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-70">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b sticky top-0 bg-white">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Edit Selected Text with AI</h3>
            <button @click="closeTextSelectionModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6 space-y-4">
          <!-- Selected Text -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Selected Text:</label>
            <div class="px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700 max-h-32 overflow-y-auto">
              {{ selectedText }}
            </div>
          </div>

          <!-- Edit Prompt -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              How should AI edit this text?
            </label>
            <textarea
              v-model="textSelectionPrompt"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 resize-none"
              placeholder="Example: Simplify this, add more detail, fix grammar, etc."
              @keydown.ctrl.enter="confirmTextSelectionEdit"
            ></textarea>
          </div>

          <!-- AI Suggestion (after processing) -->
          <div v-if="aiSuggestion" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">AI Suggestion:</label>
            <div class="px-3 py-2 bg-green-50 border border-green-300 rounded-lg text-gray-700 max-h-48 overflow-y-auto">
              {{ aiSuggestion }}
            </div>
            <div class="flex justify-end space-x-2">
              <button
                @click="rejectAISuggestion"
                class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
              >
                Reject
              </button>
              <button
                @click="acceptAISuggestion"
                class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700"
              >
                Replace Selected Text
              </button>
            </div>
          </div>
        </div>

        <div class="p-6 border-t flex justify-end gap-3 sticky bottom-0 bg-white">
          <button
            @click="closeTextSelectionModal"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            v-if="!aiSuggestion"
            @click="confirmTextSelectionEdit"
            :disabled="!textSelectionPrompt.trim() || processingTextSelection"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center space-x-2"
          >
            <svg v-if="processingTextSelection" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ processingTextSelection ? 'Processing...' : 'Get AI Suggestion' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import api from '@/api';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

const sessionId = ref(route.params.sessionId);
const materialId = ref(route.params.materialId);
const isEditMode = computed(() => materialId.value && materialId.value !== 'new');

const loading = ref(true);
const saving = ref(false);
const generating = ref(false);
const showAIModal = ref(false);
const isEditingContent = ref(false);

// AI Edit states
const showAIEditModal = ref(false);
const aiEditPrompt = ref('');
const processingAIEdit = ref(false);

// Text selection states
const showSelectionPopup = ref(false);
const showTextSelectionModal = ref(false);
const selectedText = ref('');
const selectionRange = ref(null);
const selectionPopupPosition = ref({ x: 0, y: 0 });
const textSelectionPrompt = ref('');
const processingTextSelection = ref(false);
const aiSuggestion = ref('');
const contentPreview = ref(null);
const quillEditor = ref(null);
const quillSelectionIndex = ref(null);
const quillSelectionLength = ref(null);

const lessonInfo = ref({
  lesson_title: route.query.title || '',
  lesson_focus: route.query.focus || '',
  level: route.query.level || '',
  duration: route.query.duration || ''
});

const materialData = ref({
  title: '',
  description: '',
  content: ''
});

// TipTap Editor instance
const editor = useEditor({
  extensions: [
    StarterKit,
    Table.configure({
      resizable: true,
      HTMLAttributes: {
        class: 'tiptap-table',
      },
    }),
    TableRow,
    TableHeader,
    TableCell,
    TextStyle,
    Color,
  ],
  content: '',
  editorProps: {
    attributes: {
      class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-2xl mx-auto focus:outline-none min-h-[400px] p-4',
    },
  },
  onUpdate: ({ editor }) => {
    materialData.value.content = editor.getHTML();
  },
});

// Watch materialData.content changes and update editor
watch(() => materialData.value.content, (newContent) => {
  if (editor.value && editor.value.getHTML() !== newContent) {
    editor.value.commands.setContent(newContent, false);
  }
});

// Cleanup editor on unmount
onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy();
  }
});

// AI Configuration
const aiConfig = ref({
  provider: 'openai',
  model: 'gpt-5.1',
  additionalContext: ''
});

const openaiModels = [
  { value: 'gpt-5.2', label: 'GPT-5.2 (Latest)' },
  { value: 'gpt-5.1', label: 'GPT-5.1 (Recommended)' }
];

const anthropicModels = [
  { value: 'claude-sonnet-4-5-20250929', label: 'Claude Sonnet 4.5' },
  { value: 'claude-3-7-sonnet-20250219', label: 'Claude 3.7 Sonnet' },
  { value: 'claude-3-5-sonnet-20241022', label: 'Claude 3.5 Sonnet' },
  { value: 'claude-3-opus-20240229', label: 'Claude 3 Opus' }
];

const availableModels = computed(() => {
  return aiConfig.value.provider === 'openai' ? openaiModels : anthropicModels;
});

const updateModelOptions = () => {
  if (aiConfig.value.provider === 'openai') {
    aiConfig.value.model = 'gpt-5.1';
  } else {
    aiConfig.value.model = 'claude-sonnet-4-5-20250929';
  }
};

// Load session and material data
onMounted(async () => {
  try {
    loading.value = true;

    // Load session data if not passed via query
    if (!lessonInfo.value.lesson_title) {
      const sessionResponse = await api.get(`/quality/sessions/${sessionId.value}`);
      if (sessionResponse.data.success) {
        const session = sessionResponse.data.data;
        lessonInfo.value = {
          lesson_title: session.lesson_title,
          lesson_focus: session.lesson_focus,
          level: session.level,
          duration: session.duration_minutes
        };
      }
    }

    // Load existing material if editing
    if (isEditMode.value) {
      const materialResponse = await api.get(`/quality/sessions/${sessionId.value}/materials/${materialId.value}`);
      if (materialResponse.data.success && materialResponse.data.data) {
        const material = materialResponse.data.data;
        materialData.value = {
          title: material.title || '',
          description: material.description || '',
          content: material.content || ''
        };
      }
    }
  } catch (error) {
    console.error('Error loading data:', error);
    await Swal.fire('Error', 'Failed to load data', 'error');
  } finally {
    loading.value = false;
  }
});

const saveMaterial = async () => {
  // Validate
  if (!materialData.value.title || !materialData.value.title.trim()) {
    await Swal.fire('Validation Error', 'Please enter a material title', 'warning');
    return;
  }

  if (!materialData.value.content || !materialData.value.content.trim()) {
    await Swal.fire('Validation Error', 'Please enter material content', 'warning');
    return;
  }

  try {
    saving.value = true;

    const payload = {
      title: materialData.value.title,
      description: materialData.value.description,
      content: materialData.value.content
    };

    let response;
    if (isEditMode.value) {
      // Update existing material
      response = await api.put(`/quality/sessions/${sessionId.value}/materials/${materialId.value}`, payload);
    } else {
      // Create new material
      response = await api.post(`/quality/sessions/${sessionId.value}/materials`, payload);
    }

    if (response.data.success) {
      await Swal.fire('Success', `Material ${isEditMode.value ? 'updated' : 'created'} successfully!`, 'success');

      // Navigate back to list
      router.push({
        name: 'quality.materials-list',
        params: { sessionId: sessionId.value }
      });
    }
  } catch (error) {
    console.error('Error saving material:', error);
    await Swal.fire('Error', error.response?.data?.message || 'Failed to save material', 'error');
  } finally {
    saving.value = false;
  }
};

const printMaterial = () => {
  const printWindow = window.open('', '', 'width=800,height=600');
  const content = materialData.value.content;
  const title = materialData.value.title || 'Material';

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>${title}</title>
      <style>
        body {
          font-family: Arial, sans-serif;
          line-height: 1.6;
          padding: 20px;
          max-width: 800px;
          margin: 0 auto;
        }
        h1, h2, h3, h4, h5, h6 {
          margin-top: 1.5em;
          margin-bottom: 0.5em;
        }
        table {
          border-collapse: collapse;
          width: 100%;
          margin: 1em 0;
        }
        td, th {
          border: 1px solid #333;
          padding: 8px;
          text-align: left;
        }
        th {
          background-color: #f0f0f0;
          font-weight: bold;
        }
        p {
          margin: 0.5em 0;
        }
        @media print {
          body {
            padding: 0;
          }
        }
      </style>
    </head>
    <body>
      ${content}
    </body>
    </html>
  `);

  printWindow.document.close();
  printWindow.focus();

  // Wait for content to load before printing
  setTimeout(() => {
    printWindow.print();
    printWindow.close();
  }, 250);
};

const downloadWord = () => {
  try {
    const content = materialData.value.content;
    const title = materialData.value.title || 'Material';

    // Create a complete HTML document
    const htmlContent = `
      <!DOCTYPE html>
      <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
      <head>
        <meta charset='utf-8'>
        <title>${title}</title>
        <style>
          body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.5;
          }
          table {
            border-collapse: collapse;
            width: 100%;
            margin: 1em 0;
          }
          td, th {
            border: 1px solid #000;
            padding: 8px;
          }
          th {
            background-color: #f0f0f0;
            font-weight: bold;
          }
          h1 { font-size: 18pt; }
          h2 { font-size: 16pt; }
          h3 { font-size: 14pt; }
          p { margin: 0.5em 0; }
        </style>
      </head>
      <body>
        ${content}
      </body>
      </html>
    `;

    // Convert to blob
    const blob = new Blob(['\ufeff', htmlContent], {
      type: 'application/msword'
    });

    // Create download link
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${title.replace(/[^a-z0-9]/gi, '_')}.doc`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    Swal.fire({
      icon: 'success',
      title: 'Downloaded!',
      text: 'Material downloaded as Word document',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
  } catch (error) {
    console.error('Error downloading Word:', error);
    Swal.fire('Error', 'Failed to download as Word document', 'error');
  }
};

const generateWithAI = () => {
  showAIModal.value = true;
};

const confirmGenerate = async () => {
  try {
    showAIModal.value = false;
    generating.value = true;

    const response = await api.post(`/quality/sessions/${sessionId.value}/generate-material`, {
      provider: aiConfig.value.provider,
      model: aiConfig.value.model,
      additional_context: aiConfig.value.additionalContext
    });

    if (response.data.success && response.data.data) {
      const generatedData = response.data.data;

      // Fill form with AI-generated data
      materialData.value.title = generatedData.title;
      materialData.value.description = generatedData.description;
      materialData.value.content = generatedData.content;

      await Swal.fire({
        icon: 'success',
        title: 'Material Generated!',
        text: 'AI has generated the material. Review and edit as needed, then save.',
        confirmButtonColor: '#16a34a'
      });
    }
  } catch (error) {
    console.error('Error generating material:', error);
    await Swal.fire('Error', error.response?.data?.message || 'Failed to generate material with AI', 'error');
  } finally {
    generating.value = false;
  }
};

const goBack = () => {
  router.push({
    name: 'quality.materials-list',
    params: { sessionId: sessionId.value }
  });
};

// AI Edit functions
const closeAIEditModal = () => {
  showAIEditModal.value = false;
  aiEditPrompt.value = '';
};

const confirmAIEdit = async () => {
  if (!aiEditPrompt.value.trim()) return;

  try {
    processingAIEdit.value = true;

    const response = await api.post(`/quality/sessions/${sessionId.value}/edit-material-with-ai`, {
      content: materialData.value.content,
      prompt: aiEditPrompt.value,
      model: 'gpt-5.1' // Always use GPT-5.1 as requested
    });

    if (response.data.success && response.data.data) {
      materialData.value.content = response.data.data.edited_content;

      await Swal.fire({
        icon: 'success',
        title: 'Content Edited!',
        text: 'AI has edited the content based on your instructions.',
        confirmButtonColor: '#16a34a'
      });

      closeAIEditModal();
    }
  } catch (error) {
    console.error('Error editing with AI:', error);
    await Swal.fire('Error', error.response?.data?.message || 'Failed to edit content with AI', 'error');
  } finally {
    processingAIEdit.value = false;
  }
};

// Text selection functions
const handleTextSelection = (event) => {
  const selection = window.getSelection();
  const text = selection.toString().trim();

  if (text.length > 0) {
    selectedText.value = text;
    selectionRange.value = selection.getRangeAt(0);

    // Calculate popup position
    const rect = selection.getRangeAt(0).getBoundingClientRect();
    selectionPopupPosition.value = {
      x: rect.left + (rect.width / 2) - 80, // Center the popup
      y: rect.bottom + window.scrollY + 5
    };

    // Reset Quill selection info since this is from preview
    quillSelectionIndex.value = null;
    quillSelectionLength.value = null;

    showSelectionPopup.value = true;
  } else {
    showSelectionPopup.value = false;
  }
};

// Note: Removed onQuillReady - now using TipTap editor instead of Quill

const openTextSelectionEdit = () => {
  showSelectionPopup.value = false;
  showTextSelectionModal.value = true;
  textSelectionPrompt.value = '';
  aiSuggestion.value = '';
};

const closeTextSelectionModal = () => {
  showTextSelectionModal.value = false;
  textSelectionPrompt.value = '';
  aiSuggestion.value = '';
  selectedText.value = '';
  selectionRange.value = null;
};

const confirmTextSelectionEdit = async () => {
  if (!textSelectionPrompt.value.trim()) return;

  try {
    processingTextSelection.value = true;

    const response = await api.post(`/quality/sessions/${sessionId.value}/edit-text-with-ai`, {
      text: selectedText.value,
      prompt: textSelectionPrompt.value,
      model: 'gpt-5.1' // Always use GPT-5.1 as requested
    });

    if (response.data.success && response.data.data) {
      aiSuggestion.value = response.data.data.edited_text;
    }
  } catch (error) {
    console.error('Error editing text with AI:', error);
    await Swal.fire('Error', error.response?.data?.message || 'Failed to edit text with AI', 'error');
  } finally {
    processingTextSelection.value = false;
  }
};

const acceptAISuggestion = () => {
  if (!aiSuggestion.value) return;

  try {
    // Check if this is from Quill Editor or preview
    if (quillSelectionIndex.value !== null && quillSelectionLength.value !== null) {
      // Replace in Quill Editor
      if (!quillEditor.value) {
        throw new Error('Quill editor not found');
      }

      const quill = quillEditor.value.getQuill();
      if (!quill) {
        throw new Error('Quill instance not found');
      }

      // Delete the selected text and insert AI suggestion
      quill.deleteText(quillSelectionIndex.value, quillSelectionLength.value);
      quill.insertText(quillSelectionIndex.value, aiSuggestion.value);

      // Set cursor after the inserted text
      quill.setSelection(quillSelectionIndex.value + aiSuggestion.value.length);

    } else if (selectionRange.value) {
      // Replace in preview mode (HTML content)
      const parser = new DOMParser();
      const doc = parser.parseFromString(materialData.value.content, 'text/html');

      // Find and replace the selected text in the HTML
      const bodyContent = doc.body.innerHTML;
      const replacedContent = bodyContent.replace(selectedText.value, aiSuggestion.value);

      // Update the material content
      materialData.value.content = replacedContent;
    } else {
      throw new Error('No selection found');
    }

    Swal.fire({
      icon: 'success',
      title: 'Text Replaced!',
      text: 'The selected text has been replaced with the AI suggestion.',
      timer: 2000,
      showConfirmButton: false
    });

    closeTextSelectionModal();
  } catch (error) {
    console.error('Error replacing text:', error);
    Swal.fire('Error', 'Failed to replace text: ' + error.message, 'error');
  }
};

const rejectAISuggestion = () => {
  aiSuggestion.value = '';
  textSelectionPrompt.value = '';
};
</script>

<style scoped>
/* TipTap Editor Styles */
.tiptap-editor :deep(.ProseMirror) {
  min-height: 400px;
  padding: 1rem;
  outline: none;
}

.tiptap-editor :deep(.ProseMirror):focus {
  outline: none;
}

/* TipTap Table Styles */
.tiptap-editor :deep(table) {
  border-collapse: collapse;
  table-layout: fixed;
  width: 100%;
  margin: 1rem 0;
  overflow: hidden;
}

.tiptap-editor :deep(td),
.tiptap-editor :deep(th) {
  min-width: 1em;
  border: 2px solid #cbd5e1;
  padding: 0.5rem;
  vertical-align: top;
  box-sizing: border-box;
  position: relative;
}

.tiptap-editor :deep(th) {
  font-weight: bold;
  text-align: left;
  background-color: #f1f5f9;
}

.tiptap-editor :deep(.selectedCell):after {
  z-index: 2;
  position: absolute;
  content: "";
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background: rgba(59, 130, 246, 0.1);
  pointer-events: none;
}

.tiptap-editor :deep(.column-resize-handle) {
  position: absolute;
  right: -2px;
  top: 0;
  bottom: -2px;
  width: 4px;
  background-color: #3b82f6;
  pointer-events: none;
}

/* Preserve inline styles from AI-generated content */
.tiptap-editor :deep([style]) {
  all: revert;
}

/* Material content preview styles */
.material-content {
  line-height: 1.6;
}

.material-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
  border: 1px solid #e5e7eb;
}

.material-content :deep(th),
.material-content :deep(td) {
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  text-align: left;
}

.material-content :deep(th) {
  background-color: #f9fafb;
  font-weight: 600;
  color: #374151;
}

.material-content :deep(tr:nth-child(even)) {
  background-color: #fafafa;
}

.material-content :deep(h1) {
  font-size: 1.875rem;
  font-weight: 700;
  margin-top: 1.5rem;
  margin-bottom: 1rem;
}

.material-content :deep(h2) {
  font-size: 1.5rem;
  font-weight: 600;
  margin-top: 1.25rem;
  margin-bottom: 0.75rem;
}

.material-content :deep(h3) {
  font-size: 1.25rem;
  font-weight: 600;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

.material-content :deep(p) {
  margin-bottom: 0.75rem;
}

.material-content :deep(ul),
.material-content :deep(ol) {
  margin-left: 1.5rem;
  margin-bottom: 0.75rem;
}

.material-content :deep(hr) {
  margin: 1.5rem 0;
  border: 0;
  border-top: 2px solid #e5e7eb;
}
</style>
