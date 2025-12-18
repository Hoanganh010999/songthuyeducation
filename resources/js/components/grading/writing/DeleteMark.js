/**
 * Custom TipTap Mark for Deleted Text
 * Shows strikethrough for text that should be deleted
 */
import { Mark, mergeAttributes } from '@tiptap/core'

export const DeleteMark = Mark.create({
  name: 'deleteMark',

  addOptions() {
    return {
      HTMLAttributes: {},
    }
  },

  addAttributes() {
    return {
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
        tag: 'span.writing-delete',
      },
      {
        tag: 's',
      },
    ]
  },

  renderHTML({ HTMLAttributes }) {
    return [
      'span',
      mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, {
        class: 'writing-delete',
        style: 'text-decoration: line-through; color: #e74c3c; cursor: pointer;',
      }),
      0,
    ]
  },

  addCommands() {
    return {
      setDeleteMark: (comment = '') => ({ commands }) => {
        return commands.setMark(this.name, { comment })
      },
      unsetDeleteMark: () => ({ commands }) => {
        return commands.unsetMark(this.name)
      },
    }
  },
})
