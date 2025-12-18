/**
 * Custom TipTap Mark for Writing Errors
 * Marks text with specific error types and colors
 */
import { Mark, mergeAttributes } from '@tiptap/core'

export const ErrorMark = Mark.create({
  name: 'errorMark',

  addOptions() {
    return {
      HTMLAttributes: {},
    }
  },

  addAttributes() {
    return {
      errorCode: {
        default: null,
        parseHTML: element => element.getAttribute('data-error-code'),
        renderHTML: attributes => {
          if (!attributes.errorCode) {
            return {}
          }
          return {
            'data-error-code': attributes.errorCode,
          }
        },
      },
      errorColor: {
        default: '#e74c3c',
        parseHTML: element => element.getAttribute('data-error-color'),
        renderHTML: attributes => {
          if (!attributes.errorColor) {
            return {}
          }
          return {
            'data-error-color': attributes.errorColor,
          }
        },
      },
      comment: {
        default: '',
        parseHTML: element => element.getAttribute('data-comment'),
        renderHTML: attributes => {
          if (!attributes.comment) {
            return {}
          }
          return {
            'data-comment': attributes.comment,
          }
        },
      },
    }
  },

  parseHTML() {
    return [
      {
        tag: 'span[data-error-code]',
      },
    ]
  },

  renderHTML({ HTMLAttributes }) {
    const color = HTMLAttributes['data-error-color'] || '#e74c3c'

    return [
      'span',
      mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, {
        class: 'writing-error',
        style: `border-bottom: 2px solid ${color}; cursor: pointer; position: relative; --error-color: ${color};`,
      }),
      0,
    ]
  },

  addCommands() {
    return {
      setErrorMark: (errorCode, errorColor, comment = '') => ({ commands }) => {
        return commands.setMark(this.name, { errorCode, errorColor, comment })
      },
      unsetErrorMark: () => ({ commands }) => {
        return commands.unsetMark(this.name)
      },
    }
  },
})
