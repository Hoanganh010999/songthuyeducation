<template>
  <div class="journal-editor-wrapper">
    <div v-if="!disabled" class="editor-toolbar bg-gray-50 border border-b-0 border-gray-300 rounded-t-lg p-2 flex flex-wrap gap-1">
      <button
        type="button"
        @click="editor?.chain().focus().toggleBold().run()"
        :class="{ 'bg-indigo-100 text-indigo-700': editor?.isActive('bold') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Bold"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M11 5H7v2h1.5c.28 0 .5.22.5.5s-.22.5-.5.5H7v2h1.5c.28 0 .5.22.5.5s-.22.5-.5.5H7v2h4c1.1 0 2-.9 2-2v-1c0-.55-.22-1.05-.59-1.41C12.78 7.71 13 7.11 13 6.5 13 5.67 12.33 5 11.5 5z"/>
        </svg>
      </button>

      <button
        type="button"
        @click="editor?.chain().focus().toggleItalic().run()"
        :class="{ 'bg-indigo-100 text-indigo-700': editor?.isActive('italic') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Italic"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M10 4h3l-4 12H6l4-12z"/>
        </svg>
      </button>

      <button
        type="button"
        @click="editor?.chain().focus().toggleUnderline().run()"
        :class="{ 'bg-indigo-100 text-indigo-700': editor?.isActive('underline') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Underline"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M10 16c-3.31 0-6-2.69-6-6V4h2v6c0 2.21 1.79 4 4 4s4-1.79 4-4V4h2v6c0 3.31-2.69 6-6 6zM4 18h12v2H4z"/>
        </svg>
      </button>

      <div class="relative">
        <button
          type="button"
          @click="showColorPicker = !showColorPicker"
          class="p-2 rounded hover:bg-gray-200 transition-colors flex items-center gap-1"
          title="Text Color"
        >
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
          </svg>
          <div class="w-4 h-1 rounded" :style="{ backgroundColor: currentColor }"></div>
        </button>

        <!-- Color Picker Dropdown -->
        <div v-if="showColorPicker" class="absolute top-full left-0 mt-1 p-2 bg-white border border-gray-300 rounded-lg shadow-lg z-10 grid grid-cols-5 gap-1">
          <button
            v-for="color in ['#000000', '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF', '#FFA500', '#800080', '#008000', '#FFC0CB', '#A52A2A', '#808080', '#C0C0C0', '#FFFFFF']"
            :key="color"
            type="button"
            @click="setColor(color)"
            class="w-6 h-6 rounded border border-gray-300 hover:scale-110 transition-transform"
            :style="{ backgroundColor: color }"
            :title="color"
          ></button>
        </div>
      </div>

      <div class="w-px h-6 bg-gray-300 mx-1"></div>

      <button
        type="button"
        @click="editor?.chain().focus().toggleBulletList().run()"
        :class="{ 'bg-indigo-100 text-indigo-700': editor?.isActive('bulletList') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Bullet List"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M4 6h2v2H4V6zm4 0h10v2H8V6zM4 10h2v2H4v-2zm4 0h10v2H8v-2zm-4 4h2v2H4v-2zm4 0h10v2H8v-2z"/>
        </svg>
      </button>

      <button
        type="button"
        @click="editor?.chain().focus().toggleOrderedList().run()"
        :class="{ 'bg-indigo-100 text-indigo-700': editor?.isActive('orderedList') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Numbered List"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M5 6h1V5H5v1zm0 2h1V7H5v1zm0 2h1V9H5v1zm0 2h1v-1H5v1zm3-6h10v2H8V6zm0 4h10v2H8v-2zm0 4h10v2H8v-2z"/>
        </svg>
      </button>

      <div class="w-px h-6 bg-gray-300 mx-1"></div>

      <button
        type="button"
        @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
        :class="{ 'bg-indigo-100 text-indigo-700': editor?.isActive('heading', { level: 2 }) }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Heading 2"
      >
        <span class="font-bold">H2</span>
      </button>

      <button
        type="button"
        @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
        :class="{ 'bg-indigo-100 text-indigo-700': editor?.isActive('heading', { level: 3 }) }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Heading 3"
      >
        <span class="font-bold">H3</span>
      </button>

      <div class="w-px h-6 bg-gray-300 mx-1"></div>

      <button
        type="button"
        @click="editor?.chain().focus().toggleBlockquote().run()"
        :class="{ 'bg-indigo-100 text-indigo-700': editor?.isActive('blockquote') }"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Quote"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M6 7H4c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h2c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm8 0h-2c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h2c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2z"/>
        </svg>
      </button>

      <button
        type="button"
        @click="editor?.chain().focus().setHorizontalRule().run()"
        class="p-2 rounded hover:bg-gray-200 transition-colors"
        title="Horizontal Line"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M2 10h16v2H2v-2z"/>
        </svg>
      </button>
    </div>

    <editor-content
      :editor="editor"
      class="border border-gray-300 rounded-lg min-h-[400px] prose max-w-none"
      :class="{
        'rounded-t-none': !disabled,
        'bg-gray-100': disabled
      }"
    />
  </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { Underline } from '@tiptap/extension-underline'
import { TextStyle } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import { watch, onBeforeUnmount, ref } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  placeholder: {
    type: String,
    default: 'Viết journal của bạn ở đây...'
  }
})

const emit = defineEmits(['update:modelValue'])

const showColorPicker = ref(false)
const currentColor = ref('#000000')

const editor = useEditor({
  extensions: [
    StarterKit,
    Underline,
    TextStyle,
    Color
  ],
  content: props.modelValue,
  editable: !props.disabled,
  editorProps: {
    attributes: {
      class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-xl focus:outline-none p-4'
    }
  },
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  }
})

const setColor = (color) => {
  editor.value?.chain().focus().setColor(color).run()
  currentColor.value = color
  showColorPicker.value = false
}

// Watch for content changes from parent
watch(() => props.modelValue, (newValue) => {
  console.log('[JournalEditor] modelValue changed:', {
    newValueLength: newValue?.length,
    newValuePreview: newValue?.substring(0, 100),
    editorExists: !!editor.value,
    editorHTML: editor.value?.getHTML()?.substring(0, 100)
  })
  const isSame = editor.value?.getHTML() === newValue
  console.log('[JournalEditor] isSame:', isSame)
  if (!isSame && editor.value) {
    console.log('[JournalEditor] Setting content to editor')
    editor.value?.commands?.setContent(newValue, false)
  }
})

// Watch for disabled changes
watch(() => props.disabled, (newDisabled) => {
  if (editor.value) {
    editor.value?.setEditable(!newDisabled)
  }
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})
</script>

<style scoped>
:deep(.ProseMirror) {
  min-height: 400px;
  outline: none;
}

:deep(.ProseMirror:focus) {
  outline: none;
}

:deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: #9CA3AF;
  pointer-events: none;
  height: 0;
}

/* Make bullet lists visible */
:deep(.ProseMirror ul) {
  list-style-type: disc;
  padding-left: 1.5rem;
}

/* Make numbered lists visible */
:deep(.ProseMirror ol) {
  list-style-type: decimal;
  padding-left: 1.5rem;
}

/* Add spacing to list items */
:deep(.ProseMirror ul li),
:deep(.ProseMirror ol li) {
  margin-bottom: 0.25rem;
}
</style>
