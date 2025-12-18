<template>
  <div class="writing-grading-editor">
    <!-- Editor Container -->
    <div class="border border-gray-300 rounded-lg overflow-hidden bg-white">
      <!-- Toolbar for clearing annotations and AI grading -->
      <div class="bg-gray-50 border-b border-gray-200 px-4 py-2 flex items-center justify-between">
        <div class="text-sm font-medium text-gray-700">
          {{ editable ? t('course.writing_grading') : 'Bài làm của học sinh' }}
        </div>
        <div v-if="editable" class="flex items-center gap-3">
          <!-- AI Grading Button -->
          <button
            @click="gradeWithAI"
            :disabled="aiGrading || !aiSettings.hasApiKey"
            class="text-sm px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 transition-colors"
          >
            <svg v-if="aiGrading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            {{ aiGrading ? 'Đang chấm...' : 'Chấm bằng AI' }}
          </button>

          <!-- Clear Button -->
          <button
            @click="clearAllAnnotations"
            class="text-sm text-gray-600 hover:text-gray-800 flex items-center gap-1"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            {{ t('course.clear_all_annotations') }}
          </button>
        </div>
      </div>

      <!-- TipTap Editor -->
      <div class="p-4">
        <editor-content :editor="editor" class="prose max-w-none" />
      </div>
    </div>

    <!-- Floating Menu for Selection Actions -->
    <div
      v-if="showFloatingMenu && editor"
      :style="floatingMenuStyle"
      class="fixed z-50 bg-white rounded-lg shadow-lg border border-gray-200 p-2 flex gap-2"
    >
      <!-- Delete Button -->
      <button
        @click="markAsDelete"
        class="px-3 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded flex items-center gap-1"
        :title="t('course.mark_as_delete')"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        {{ t('course.delete') }}
      </button>

      <!-- Error Button -->
      <button
        @click="showErrorSelector = true"
        class="px-3 py-2 text-sm font-medium text-white bg-orange-500 hover:bg-orange-600 rounded flex items-center gap-1"
        :title="t('course.mark_error')"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        {{ t('course.error') }}
      </button>

      <!-- Missing Button (only for exactly 2 words) -->
      <button
        v-if="isTwoWordsSelected"
        @click="showMissingInput = true"
        class="px-3 py-2 text-sm font-medium text-white bg-yellow-500 hover:bg-yellow-600 rounded flex items-center gap-1"
        :title="t('course.insert_missing')"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('course.missing') }}
      </button>
    </div>

    <!-- Error Type Selector Modal -->
    <ErrorTypeSelector
      v-if="showErrorSelector"
      :position="floatingMenuPosition"
      @select="applyErrorMark"
      @close="showErrorSelector = false"
    />

    <!-- Missing Word Input Modal -->
    <MissingWordInput
      v-if="showMissingInput"
      :position="floatingMenuPosition"
      @submit="applyMissingMark"
      @close="showMissingInput = false"
    />

    <!-- Delete Comment Input Modal -->
    <DeleteCommentInput
      v-if="showDeleteComment"
      :position="floatingMenuPosition"
      @submit="applyDeleteMark"
      @close="showDeleteComment = false"
    />

    <!-- Annotations Summary (Optional) -->
    <div v-if="annotations.length > 0" class="mt-4 bg-blue-50 rounded-lg p-4">
      <h4 class="font-semibold text-blue-900 mb-3">
        {{ t('course.annotations_summary') }} ({{ annotations.length }})
      </h4>
      <div class="space-y-3 text-sm">
        <div v-for="(annotation, index) in annotations" :key="index" class="bg-white rounded-lg p-3 border border-blue-200">
          <div class="flex items-start gap-2 mb-1">
            <span
              class="w-3 h-3 rounded-full mt-1 flex-shrink-0"
              :style="{ backgroundColor: annotation.color || (annotation.type === 'delete' ? '#e74c3c' : '#999') }"
            ></span>
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-semibold text-gray-900">{{ annotation.errorCode || annotation.type }}</span>
                <span class="text-gray-500">•</span>
                <span class="text-gray-700 italic">"{{ annotation.text }}"</span>
              </div>
              <div v-if="annotation.comment" class="text-gray-600 text-xs mt-1 pl-5">
                💬 {{ annotation.comment }}
              </div>
              <div v-if="annotation.suggestion" class="text-green-600 text-xs mt-1 pl-5">
                ➜ Suggestion: {{ annotation.suggestion }}
              </div>
            </div>
            <button
              @click="removeAnnotation(annotation)"
              class="text-red-500 hover:text-red-700 hover:bg-red-50 rounded p-1 transition-colors"
              :title="'Xóa'"
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
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { Underline } from '@tiptap/extension-underline'
import { TextStyle } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import { ErrorMark } from './ErrorMark.js'
import { DeleteMark } from './DeleteMark.js'
import { MissingMark } from './MissingMark.js'
import { useI18n } from '../../../composables/useI18n'
import { useAIGrading } from '../../../composables/grading/useAIGrading'
import ErrorTypeSelector from './ErrorTypeSelector.vue'
import MissingWordInput from './MissingWordInput.vue'
import DeleteCommentInput from './DeleteCommentInput.vue'
import Swal from 'sweetalert2'

const props = defineProps({
  content: {
    type: String,
    default: ''
  },
  editable: {
    type: Boolean,
    default: true
  },
  initialAnnotations: {
    type: Array,
    default: () => []
  },
  question: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:annotations', 'update:feedback', 'update:score'])

const { t, currentLanguageCode } = useI18n()

// Floating menu state
const showFloatingMenu = ref(false)
const floatingMenuPosition = ref({ x: 0, y: 0 })
const showErrorSelector = ref(false)
const showMissingInput = ref(false)
const showDeleteComment = ref(false)
const isTwoWordsSelected = ref(false)

// Annotations data
const annotations = ref([])

// AI Grading setup
const { aiSettings, grading: aiGrading, loadAISettings, gradeEssayWithAI } = useAIGrading()

// Initialize editor - useEditor must be called at setup level
const editor = useEditor({
  content: props.content,
  editable: props.editable,
  extensions: [
    StarterKit,
    Underline,
    TextStyle,
    Color,
    ErrorMark,
    DeleteMark,
    MissingMark,
  ],
  editorProps: {
    // Prevent text input while allowing selection for marking
    handleKeyDown: (view, event) => {
      // Allow navigation keys (arrows, home, end, etc.)
      const allowedKeys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'PageUp', 'PageDown']
      if (allowedKeys.includes(event.key)) {
        return false
      }
      // Prevent any text modification keys
      if (event.key.length === 1 || event.key === 'Backspace' || event.key === 'Delete' || event.key === 'Enter') {
        event.preventDefault()
        return true
      }
      return false
    },
    handlePaste: () => {
      return true // Prevent paste
    },
    handleDrop: () => {
      return true // Prevent drop
    },
  },
  onSelectionUpdate: ({ editor }) => {
    handleSelectionChange(editor)
  },
  onUpdate: ({ editor }) => {
    extractAnnotations(editor)
  },
})

onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy()
  }
})

// Handle text selection
const handleSelectionChange = (editorInstance) => {
  const { from, to } = editorInstance.state.selection
  const selectedText = editorInstance.state.doc.textBetween(from, to, ' ')

  if (selectedText.trim().length > 0) {
    // Count words in selection
    const words = selectedText.trim().split(/\s+/)
    isTwoWordsSelected.value = words.length === 2

    // Get cursor position for floating menu
    const coords = editorInstance.view.coordsAtPos(from)
    floatingMenuPosition.value = { x: coords.left, y: coords.top }
    showFloatingMenu.value = true
  } else {
    showFloatingMenu.value = false
    showErrorSelector.value = false
    showMissingInput.value = false
  }
}

// Floating menu style
const floatingMenuStyle = computed(() => {
  return {
    left: `${floatingMenuPosition.value.x}px`,
    top: `${floatingMenuPosition.value.y - 60}px`,
  }
})

// Show delete comment modal
const markAsDelete = () => {
  showDeleteComment.value = true
}

// Apply delete mark with comment
const applyDeleteMark = (comment) => {
  if (editor.value && editor.value.chain && comment) {
    editor.value.chain().focus().setDeleteMark(comment).run()
    showDeleteComment.value = false
    showFloatingMenu.value = false
    extractAnnotations(editor.value)
  }
}

// Apply error mark
const applyErrorMark = (errorType) => {
  if (editor.value && editor.value.chain && errorType) {
    editor.value
      .chain()
      .focus()
      .setErrorMark(errorType.code, errorType.color, errorType.comment || '')
      .run()

    showErrorSelector.value = false
    showFloatingMenu.value = false
    extractAnnotations(editor.value)
  }
}

// Apply missing word mark
const applyMissingMark = (suggestion) => {
  if (editor.value && editor.value.chain) {
    editor.value.chain().focus().setMissingMark(suggestion).run()
    showMissingInput.value = false
    showFloatingMenu.value = false
    extractAnnotations(editor.value)
  }
}

// Extract annotations from editor content
const extractAnnotations = (editorInstance) => {
  const annotationsData = []
  const doc = editorInstance.state.doc

  doc.descendants((node, pos) => {
    if (node.marks) {
      node.marks.forEach(mark => {
        const text = node.text || ''

        if (mark.type.name === 'errorMark') {
          annotationsData.push({
            type: 'error',
            errorCode: mark.attrs.errorCode,
            color: mark.attrs.errorColor,
            comment: mark.attrs.comment || '',
            text: text,
            position: pos,
          })
        } else if (mark.type.name === 'deleteMark') {
          annotationsData.push({
            type: 'delete',
            comment: mark.attrs.comment || '',
            text: text,
            position: pos,
          })
        } else if (mark.type.name === 'missingMark') {
          annotationsData.push({
            type: 'missing',
            suggestion: mark.attrs.suggestion,
            text: text,
            position: pos,
          })
        }
      })
    }
  })

  annotations.value = annotationsData
  emit('update:annotations', annotationsData)
}

// Remove individual annotation
const removeAnnotation = (annotation) => {
  if (!editor.value) return

  const { state } = editor.value
  const { doc, tr } = state

  // Find and remove the mark at the annotation position
  doc.nodesBetween(annotation.position, annotation.position + annotation.text.length, (node, pos) => {
    if (node.marks) {
      node.marks.forEach(mark => {
        // Match by mark type and position
        const matchesError = mark.type.name === 'errorMark' && annotation.type === 'error' &&
                            mark.attrs.errorCode === annotation.errorCode
        const matchesDelete = mark.type.name === 'deleteMark' && annotation.type === 'delete'
        const matchesMissing = mark.type.name === 'missingMark' && annotation.type === 'missing'

        if (matchesError || matchesDelete || matchesMissing) {
          const from = pos
          const to = pos + node.nodeSize
          editor.value.chain().focus().setTextSelection({ from, to }).unsetMark(mark.type.name).run()
        }
      })
    }
  })

  // Re-extract annotations to update the list
  extractAnnotations(editor.value)
}

// Apply initial annotations to editor
const applyInitialAnnotations = () => {
  if (!editor.value || !props.initialAnnotations || props.initialAnnotations.length === 0) {
    return
  }

  console.log('[WritingGradingEditor] 📥 Applying initial annotations:', props.initialAnnotations)

  // Apply each annotation
  props.initialAnnotations.forEach(annotation => {
    const from = annotation.position
    const to = annotation.position + annotation.text.length

    if (annotation.type === 'error') {
      editor.value
        .chain()
        .focus()
        .setTextSelection({ from, to })
        .setErrorMark(annotation.errorCode, annotation.errorColor, annotation.comment || '')
        .run()
    } else if (annotation.type === 'delete') {
      editor.value
        .chain()
        .focus()
        .setTextSelection({ from, to })
        .setDeleteMark(annotation.comment || '')
        .run()
    } else if (annotation.type === 'missing') {
      editor.value
        .chain()
        .focus()
        .setTextSelection({ from, to })
        .setMissingMark(annotation.suggestion || '')
        .run()
    }
  })

  // Clear selection after applying all marks
  editor.value.commands.setTextSelection(0)

  console.log('[WritingGradingEditor] ✅ Applied', props.initialAnnotations.length, 'annotations')

  // Extract and emit the annotations to update parent component
  setTimeout(() => {
    extractAnnotations(editor.value)
  }, 200)
}

// Apply initial annotations when editor is ready
watch(() => editor.value, (newEditor) => {
  if (newEditor && props.initialAnnotations && props.initialAnnotations.length > 0) {
    // Wait for editor to be fully initialized
    setTimeout(() => {
      applyInitialAnnotations()
    }, 100)
  }
}, { immediate: true })

// Clear all annotations
const clearAllAnnotations = () => {
  if (editor.value && editor.value.chain) {
    editor.value
      .chain()
      .focus()
      .unsetErrorMark()
      .unsetDeleteMark()
      .unsetMissingMark()
      .selectAll()
      .unsetErrorMark()
      .unsetDeleteMark()
      .unsetMissingMark()
      .run()

    annotations.value = []
    emit('update:annotations', [])
  }
}

// Grade with AI
const gradeWithAI = async () => {
  if (!aiSettings.value.hasApiKey) {
    Swal.fire({
      icon: 'warning',
      title: 'Thiếu cấu hình AI',
      text: 'Vui lòng cấu hình AI trong Quality Management → Settings → AI Configuration'
    })
    return
  }

  try {
    // Get essay text from editor
    const essayText = editor.value.getText()

    if (!essayText || essayText.trim().length < 10) {
      Swal.fire({
        icon: 'warning',
        title: 'Bài làm quá ngắn',
        text: 'Bài làm cần có ít nhất 10 ký tự để chấm bằng AI'
      })
      return
    }

    console.log('[WritingGradingEditor] 🤖 Starting AI grading...', {
      essayLength: essayText.length,
      provider: aiSettings.value.provider,
      model: aiSettings.value.model
    })

    // Call AI grading API
    const result = await gradeEssayWithAI(essayText, props.question || '')

    console.log('[WritingGradingEditor] ✅ AI grading result:', result)

    // Clear existing annotations first
    clearAllAnnotations()

    // Apply AI-generated annotations
    if (result.annotations && result.annotations.length > 0) {
      // Wait a bit for clear to complete
      await new Promise(resolve => setTimeout(resolve, 100))

      // Apply each annotation
      for (const annotation of result.annotations) {
        const from = annotation.position
        const to = from + annotation.text.length

        if (annotation.type === 'error') {
          editor.value
            .chain()
            .focus()
            .setTextSelection({ from, to })
            .setErrorMark(
              annotation.errorCode || 'AI',
              annotation.color || '#e74c3c',
              annotation.comment || ''
            )
            .run()
        } else if (annotation.type === 'delete') {
          editor.value
            .chain()
            .focus()
            .setTextSelection({ from, to })
            .setDeleteMark(annotation.comment || '')
            .run()
        } else if (annotation.type === 'missing') {
          editor.value
            .chain()
            .focus()
            .setTextSelection({ from, to })
            .setMissingMark(annotation.suggestion || '')
            .run()
        }
      }

      // Clear selection
      editor.value.commands.setTextSelection(0)

      // Extract annotations to update state
      setTimeout(() => {
        extractAnnotations(editor.value)
      }, 200)

      // Emit feedback and score to parent
      if (result.feedback) {
        emit('update:feedback', result.feedback)
        console.log('[WritingGradingEditor] 💬 Emitted feedback to parent:', result.feedback.substring(0, 100) + '...')
      }
      if (result.score !== undefined && result.score !== null) {
        emit('update:score', result.score)
        console.log('[WritingGradingEditor] 🎯 Emitted score to parent:', result.score)
      }

      Swal.fire({
        icon: 'success',
        title: 'Chấm bài thành công!',
        html: `
          <div class="text-left">
            <p class="mb-2"><strong>Số lỗi tìm thấy:</strong> ${result.annotations.length}</p>
            ${result.feedback ? `<p class="mb-2"><strong>Nhận xét:</strong> ${result.feedback}</p>` : ''}
            ${result.score ? `<p><strong>Điểm đề xuất:</strong> ${result.score}/100</p>` : ''}
          </div>
        `,
        confirmButtonText: 'OK'
      })
    } else {
      Swal.fire({
        icon: 'info',
        title: 'Không có lỗi',
        text: result.feedback || 'AI không tìm thấy lỗi nào trong bài làm này.',
        confirmButtonText: 'OK'
      })
    }
  } catch (error) {
    console.error('[WritingGradingEditor] ❌ Error grading with AI:', error)
    Swal.fire({
      icon: 'error',
      title: 'Lỗi khi chấm bằng AI',
      text: error.message || 'Đã xảy ra lỗi khi gọi AI. Vui lòng thử lại sau.'
    })
  }
}

// Load AI settings on mount
onMounted(() => {
  loadAISettings().catch(err => {
    console.error('[WritingGradingEditor] Failed to load AI settings:', err)
  })
})

// Expose methods
defineExpose({
  getAnnotations: () => annotations.value,
  setContent: (content) => {
    if (editor.value && editor.value.commands) {
      editor.value.commands.setContent(content)
    }
  },
  clearAnnotations: clearAllAnnotations,
})
</script>

<style scoped>
.writing-grading-editor :deep(.ProseMirror) {
  min-height: 200px;
  outline: none;
  line-height: 2.5;
  padding: 1rem;
}

.writing-grading-editor :deep(.ProseMirror p) {
  margin-bottom: 1.5em;
  min-height: 2.5em;
}

.writing-grading-editor :deep(.writing-error) {
  position: relative;
  display: inline-block;
}

/* Error code badge displayed below the underlined text */
.writing-grading-editor :deep(.writing-error::before) {
  content: attr(data-error-code);
  position: absolute;
  top: calc(100% - 1px);
  left: 50%;
  transform: translateX(-50%);
  font-size: 9px;
  font-weight: 600;
  color: white;
  background-color: var(--error-color, #e74c3c);
  padding: 1px 4px;
  border-radius: 3px;
  line-height: 1.2;
  z-index: 10;
  white-space: nowrap;
}

/* Hover tooltip for full comment */
.writing-grading-editor :deep(.writing-error:hover::after) {
  content: attr(data-error-code) " - " attr(data-comment);
  position: absolute;
  bottom: 100%;
  left: 0;
  background: rgba(0, 0, 0, 0.9);
  color: white;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 12px;
  white-space: pre-wrap;
  max-width: 300px;
  z-index: 100;
  margin-bottom: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.writing-grading-editor :deep(.writing-delete) {
  position: relative;
  display: inline-block;
}

.writing-grading-editor :deep(.writing-delete:hover::after) {
  content: "Xóa: " attr(data-comment);
  position: absolute;
  bottom: 100%;
  left: 0;
  background: rgba(231, 76, 60, 0.95);
  color: white;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 12px;
  white-space: pre-wrap;
  max-width: 300px;
  z-index: 100;
  margin-bottom: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Missing word indicator - suggestion box above with arrow below it */
.writing-grading-editor :deep(.writing-missing) {
  position: relative;
  display: inline;
  background-color: rgba(255, 193, 7, 0.2);
  border-bottom: 2px dashed #ffc107;
  padding: 0 2px;
}

/* Suggestion box above the text */
.writing-grading-editor :deep(.writing-missing::before) {
  content: attr(data-suggestion);
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  bottom: 100%;
  margin-bottom: 16px;
  background: #ffc107;
  color: #000;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  white-space: nowrap;
  z-index: 10;
  font-weight: 600;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  pointer-events: none;
}

/* Arrow pointing down below the suggestion box */
.writing-grading-editor :deep(.writing-missing::after) {
  content: '▼';
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  bottom: 100%;
  margin-bottom: -2px;
  color: #ffc107;
  font-size: 18px;
  line-height: 1;
  z-index: 11;
  pointer-events: none;
}

/* Bullet lists styling */
.writing-grading-editor :deep(.ProseMirror ul) {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin: 1rem 0;
}

/* Numbered lists styling */
.writing-grading-editor :deep(.ProseMirror ol) {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin: 1rem 0;
}

/* List items spacing */
.writing-grading-editor :deep(.ProseMirror ul li),
.writing-grading-editor :deep(.ProseMirror ol li) {
  margin-bottom: 0.5rem;
}
</style>
