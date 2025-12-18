/**
 * Custom TipTap Mark for Missing Words
 * Marks positions where words/phrases should be inserted
 */
import { Mark, mergeAttributes } from '@tiptap/core'

export const MissingMark = Mark.create({
  name: 'missingMark',

  addOptions() {
    return {
      HTMLAttributes: {},
    }
  },

  addAttributes() {
    return {
      suggestion: {
        default: '',
        parseHTML: element => element.getAttribute('data-suggestion'),
        renderHTML: attributes => {
          if (!attributes.suggestion) {
            return {}
          }
          return {
            'data-suggestion': attributes.suggestion,
          }
        },
      },
    }
  },

  parseHTML() {
    return [
      {
        tag: 'span.writing-missing',
      },
    ]
  },

  renderHTML({ HTMLAttributes }) {
    return [
      'span',
      mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, {
        class: 'writing-missing',
      }),
      0,
    ]
  },

  addCommands() {
    return {
      setMissingMark: (suggestion = '') => ({ commands }) => {
        return commands.setMark(this.name, { suggestion })
      },
      unsetMissingMark: () => ({ commands }) => {
        return commands.unsetMark(this.name)
      },
    }
  },
})
