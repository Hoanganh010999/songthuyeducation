/**
 * Parse Zalo Rich Text Format
 *
 * Zalo rich text format:
 * {
 *   "title": "Text content with formatting",
 *   "params": "{\"styles\":[{\"start\":0,\"len\":4,\"st\":\"b\"},{\"start\":4,\"len\":16,\"st\":\"b\"},{\"start\":167,\"len\":31,\"st\":\"b,c_db342e\"}],\"ver\":0}"
 * }
 *
 * Styles:
 * - "b" = bold
 * - "i" = italic
 * - "u" = underline
 * - "s" = strikethrough
 * - "c_XXXXXX" = color (hex code without #)
 */

/**
 * Check if message content is rich text format
 */
export function isRichTextFormat(content) {
  if (typeof content !== 'string') return false;

  try {
    const parsed = JSON.parse(content);
    return parsed && typeof parsed.title === 'string' && typeof parsed.params === 'string';
  } catch {
    return false;
  }
}

/**
 * Parse rich text content and convert to HTML
 */
export function parseRichText(content) {
  try {
    const richText = JSON.parse(content);

    if (!richText || typeof richText.title !== 'string') {
      return { html: content, plainText: content };
    }

    const text = richText.title;
    const plainText = text;

    // Parse styles if available
    let styles = [];
    if (richText.params) {
      try {
        const params = typeof richText.params === 'string'
          ? JSON.parse(richText.params)
          : richText.params;
        styles = params.styles || [];
      } catch {
        // Params parsing failed, continue without styles
      }
    }

    if (styles.length === 0) {
      // No styles, return plain text
      return {
        html: escapeHtml(text),
        plainText: text
      };
    }

    // Create character array with style information
    const chars = text.split('').map((char, index) => ({
      char,
      styles: []
    }));

    // Apply styles to characters
    styles.forEach(style => {
      const start = style.start || 0;
      const len = style.len || 0;
      const st = style.st || '';

      for (let i = start; i < start + len && i < chars.length; i++) {
        if (chars[i]) {
          chars[i].styles.push(st);
        }
      }
    });

    // Convert to HTML
    let html = '';
    let currentStyles = [];
    let openTags = [];

    for (let i = 0; i < chars.length; i++) {
      const char = chars[i];
      const newStyles = char.styles;

      // Check if styles changed
      const stylesChanged = !arraysEqual(currentStyles, newStyles);

      if (stylesChanged) {
        // Close all open tags
        while (openTags.length > 0) {
          html += openTags.pop();
        }

        // Open new tags
        if (newStyles.length > 0) {
          const { tags, closeTags } = stylesToHtml(newStyles);
          html += tags;
          openTags = closeTags;
        }

        currentStyles = [...newStyles];
      }

      // Add character (escape HTML)
      html += escapeHtml(char.char);
    }

    // Close remaining tags
    while (openTags.length > 0) {
      html += openTags.pop();
    }

    return {
      html,
      plainText
    };
  } catch (error) {
    console.error('[zaloRichTextParser] Failed to parse rich text:', error);
    return {
      html: escapeHtml(content),
      plainText: content
    };
  }
}

/**
 * Convert style codes to HTML tags
 */
function stylesToHtml(styles) {
  const tags = [];
  const closeTags = [];

  styles.forEach(styleStr => {
    const parts = styleStr.split(',');

    parts.forEach(part => {
      if (part === 'b') {
        tags.push('<strong>');
        closeTags.unshift('</strong>');
      } else if (part === 'i') {
        tags.push('<em>');
        closeTags.unshift('</em>');
      } else if (part === 'u') {
        tags.push('<u>');
        closeTags.unshift('</u>');
      } else if (part === 's') {
        tags.push('<s>');
        closeTags.unshift('</s>');
      } else if (part.startsWith('c_')) {
        const color = part.substring(2);
        tags.push(`<span style="color: #${color}">`);
        closeTags.unshift('</span>');
      }
    });
  });

  return {
    tags: tags.join(''),
    closeTags
  };
}

/**
 * Escape HTML special characters
 */
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Check if two arrays are equal
 */
function arraysEqual(arr1, arr2) {
  if (arr1.length !== arr2.length) return false;

  for (let i = 0; i < arr1.length; i++) {
    if (arr1[i] !== arr2[i]) return false;
  }

  return true;
}

/**
 * Get plain text from message content (handles both rich text and plain text)
 */
export function getPlainText(content) {
  if (!content) return '';

  if (isRichTextFormat(content)) {
    return parseRichText(content).plainText;
  }

  return content;
}

/**
 * Get HTML from message content (handles both rich text and plain text)
 */
export function getHtmlContent(content) {
  if (!content) return '';

  if (isRichTextFormat(content)) {
    return parseRichText(content).html;
  }

  return escapeHtml(content);
}
