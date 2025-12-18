import { ref, onMounted, watch } from 'vue';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

export function useQuillEditor(options = {}) {
  const editorRef = ref(null);
  const quillInstance = ref(null);
  const content = ref('');

  const defaultOptions = {
    theme: 'snow',
    placeholder: options.placeholder || 'Nhập nội dung...',
    modules: {
      toolbar: [
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ 'list': 'bullet' }, { 'list': 'ordered' }],  // Separate buttons for clarity
        [{ 'header': [1, 2, 3, false] }],
        ['link'],
        ['clean']
      ]
    },
    ...options
  };

  const initEditor = () => {
    // Always reinitialize if element exists
    if (editorRef.value) {
      // Destroy existing instance if any
      if (quillInstance.value) {
        quillInstance.value = null;
      }
      
      // Clear the element first
      editorRef.value.innerHTML = '';
      
      // Create new instance
      quillInstance.value = new Quill(editorRef.value, defaultOptions);
      
      // Listen for text changes
      quillInstance.value.on('text-change', () => {
        const html = quillInstance.value.root.innerHTML;
        const text = quillInstance.value.getText().trim();
        content.value = text ? html : '';
      });

      // Set initial content if provided
      if (options.initialContent) {
        quillInstance.value.root.innerHTML = options.initialContent;
      }
    }
  };

  const setContent = (newContent) => {
    if (quillInstance.value) {
      quillInstance.value.root.innerHTML = newContent || '';
      content.value = newContent || '';
    }
  };

  const getContent = () => {
    return content.value;
  };

  const getText = () => {
    return quillInstance.value ? quillInstance.value.getText().trim() : '';
  };

  const clear = () => {
    if (quillInstance.value) {
      try {
        // Remove all event listeners temporarily
        quillInstance.value.off('text-change');
        
        // Clear content directly via DOM manipulation (safest way)
        quillInstance.value.root.innerHTML = '<p><br></p>';
        
        // Re-attach event listener
        quillInstance.value.on('text-change', () => {
          const html = quillInstance.value.root.innerHTML;
          const text = quillInstance.value.getText().trim();
          content.value = text ? html : '';
        });
        
        content.value = '';
      } catch (error) {
        console.error('Error clearing editor:', error);
        // Fallback: reinitialize editor
        initEditor();
      }
    }
  };

  const focus = () => {
    if (quillInstance.value) {
      quillInstance.value.focus();
    }
  };

  return {
    editorRef,
    quillInstance,
    content,
    initEditor,
    setContent,
    getContent,
    getText,
    clear,
    focus
  };
}

