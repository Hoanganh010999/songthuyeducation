import { ref, onMounted, watch } from 'vue';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

export function useQuillEditor(options = {}) {
  const editorRef = ref(null);
  const quillInstance = ref(null);
  const content = ref('');

  // Allow custom toolbar, or use default
  const toolbar = options.toolbar || [
    ['bold', 'italic', 'underline', 'strike'],
    ['blockquote', 'code-block'],
    [{ 'list': 'bullet' }, { 'list': 'ordered' }],  // Separate buttons for clarity
    [{ 'header': [1, 2, 3, false] }],
    ['link'],
    ['clean']
  ];

  const defaultOptions = {
    theme: 'snow',
    placeholder: options.placeholder || 'Nhập nội dung...',
    modules: {
      toolbar: toolbar
    },
    ...(options.quillOptions || {})
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

  /**
   * Extract styles from Quill Delta format for Zalo API
   * Returns array of styles in format: [{ start: number, len: number, st: string }]
   */
  const extractStyles = () => {
    if (!quillInstance.value) return null;

    const quill = quillInstance.value;
    const delta = quill.getContents();
    const text = quill.getText();
    const plainText = text.endsWith('\n') ? text.slice(0, -1) : text;

    const zaloApiStyles = [];
    let currentPosition = 0;

    // Helper to convert color to Zalo format
    const colorToZaloFormat = (color) => {
      const colorMap = {
        '#db342e': 'c_db342e',  // red
        'rgb(219, 52, 46)': 'c_db342e',
        '#f27806': 'c_f27806',  // orange
        'rgb(242, 120, 6)': 'c_f27806',
        '#f7b503': 'c_f7b503',  // yellow
        'rgb(247, 181, 3)': 'c_f7b503',
        '#15a85f': 'c_15a85f',  // green
        'rgb(21, 168, 95)': 'c_15a85f'
      };
      return colorMap[color] || null;
    };

    // Track line positions for list styles
    let lineStartPosition = 0;

    delta.ops.forEach((op, index) => {
      if (typeof op.insert === 'string') {
        const length = op.insert.length;
        const attributes = op.attributes || {};
        const isNewline = op.insert === '\n';

        // Handle newlines with list attributes
        if (isNewline && attributes.list) {
          // Apply list style to the PREVIOUS text (current line)
          const lineLength = currentPosition - lineStartPosition;
          if (lineLength > 0) {
            const listStyle = attributes.list === 'bullet' ? 'lst_1' : 'lst_2';
            zaloApiStyles.push({ start: lineStartPosition, len: lineLength, st: listStyle });
          }

          currentPosition += length;
          lineStartPosition = currentPosition;
        } else if (isNewline) {
          // Regular newline without list
          currentPosition += length;
          lineStartPosition = currentPosition;
        } else {
          // Regular text - apply text formatting styles (not list)
          if (Object.keys(attributes).length > 0 && length > 0) {
            if (attributes.bold) {
              zaloApiStyles.push({ start: currentPosition, len: length, st: 'b' });
            }
            if (attributes.italic) {
              zaloApiStyles.push({ start: currentPosition, len: length, st: 'i' });
            }
            if (attributes.underline) {
              zaloApiStyles.push({ start: currentPosition, len: length, st: 'u' });
            }
            if (attributes.strike) {
              zaloApiStyles.push({ start: currentPosition, len: length, st: 's' });
            }
            if (attributes.color) {
              const zaloColor = colorToZaloFormat(attributes.color);
              if (zaloColor) {
                zaloApiStyles.push({ start: currentPosition, len: length, st: zaloColor });
              }
            }
          }
          currentPosition += length;
        }
      }
    });

    return zaloApiStyles.length > 0 ? zaloApiStyles : null;
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
    focus,
    extractStyles
  };
}

