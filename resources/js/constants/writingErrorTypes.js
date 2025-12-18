/**
 * Writing Error Types for Essay Grading
 * Support for both Vietnamese and English
 */

export const ERROR_CATEGORIES = {
  VOCABULARY: 'vocabulary',
  GRAMMAR: 'grammar',
  MECHANICS: 'mechanics',
  SENTENCE: 'sentence',
  COHERENCE: 'coherence'
}

export const ERROR_TYPES = {
  // Vocabulary (Từ vựng)
  WW: {
    code: 'WW',
    category: ERROR_CATEGORIES.VOCABULARY,
    name: {
      en: 'Wrong word',
      vi: 'Dùng sai từ'
    },
    color: '#e74c3c' // Red
  },
  WF: {
    code: 'WF',
    category: ERROR_CATEGORIES.VOCABULARY,
    name: {
      en: 'Word form',
      vi: 'Sai dạng từ'
    },
    color: '#e74c3c'
  },
  WC: {
    code: 'WC',
    category: ERROR_CATEGORIES.VOCABULARY,
    name: {
      en: 'Word choice',
      vi: 'Chọn từ chưa phù hợp'
    },
    color: '#e74c3c'
  },
  REP: {
    code: 'REP',
    category: ERROR_CATEGORIES.VOCABULARY,
    name: {
      en: 'Repetition',
      vi: 'Lặp từ'
    },
    color: '#e67e22' // Orange
  },
  COL: {
    code: 'COL',
    category: ERROR_CATEGORIES.VOCABULARY,
    name: {
      en: 'Collocation',
      vi: 'Kết hợp từ không tự nhiên'
    },
    color: '#e74c3c'
  },
  IDM: {
    code: 'IDM',
    category: ERROR_CATEGORIES.VOCABULARY,
    name: {
      en: 'Idiom',
      vi: 'Dùng idiom sai / chưa tự nhiên'
    },
    color: '#e74c3c'
  },

  // Grammar (Ngữ pháp)
  WO: {
    code: 'WO',
    category: ERROR_CATEGORIES.GRAMMAR,
    name: {
      en: 'Word order',
      vi: 'Sai trật tự từ'
    },
    color: '#3498db' // Blue
  },
  T: {
    code: 'T',
    category: ERROR_CATEGORIES.GRAMMAR,
    name: {
      en: 'Tense',
      vi: 'Sai thì'
    },
    color: '#3498db'
  },
  SV: {
    code: 'SV',
    category: ERROR_CATEGORIES.GRAMMAR,
    name: {
      en: 'Subject-Verb agreement',
      vi: 'Sự hòa hợp chủ ngữ - động từ'
    },
    color: '#3498db'
  },
  ART: {
    code: 'ART',
    category: ERROR_CATEGORIES.GRAMMAR,
    name: {
      en: 'Article (a/an/the)',
      vi: 'Mạo từ (a/an/the)'
    },
    color: '#3498db'
  },
  PREP: {
    code: 'PREP',
    category: ERROR_CATEGORIES.GRAMMAR,
    name: {
      en: 'Preposition',
      vi: 'Giới từ'
    },
    color: '#3498db'
  },
  PL: {
    code: 'PL',
    category: ERROR_CATEGORIES.GRAMMAR,
    name: {
      en: 'Plural',
      vi: 'Số ít / số nhiều'
    },
    color: '#3498db'
  },
  PASS: {
    code: 'PASS',
    category: ERROR_CATEGORIES.GRAMMAR,
    name: {
      en: 'Passive voice',
      vi: 'Câu bị động'
    },
    color: '#3498db'
  },
  MOD: {
    code: 'MOD',
    category: ERROR_CATEGORIES.GRAMMAR,
    name: {
      en: 'Modal verb',
      vi: 'Động từ khuyết thiếu'
    },
    color: '#3498db'
  },
  PR: {
    code: 'PR',
    category: ERROR_CATEGORIES.GRAMMAR,
    name: {
      en: 'Pronoun',
      vi: 'Đại từ'
    },
    color: '#3498db'
  },
  IF: {
    code: 'IF',
    category: ERROR_CATEGORIES.GRAMMAR,
    name: {
      en: 'Conditional',
      vi: 'Câu điều kiện'
    },
    color: '#3498db'
  },

  // Mechanics (Chính tả & hình thức)
  SP: {
    code: 'SP',
    category: ERROR_CATEGORIES.MECHANICS,
    name: {
      en: 'Spelling',
      vi: 'Chính tả'
    },
    color: '#9b59b6' // Purple
  },
  CAP: {
    code: 'CAP',
    category: ERROR_CATEGORIES.MECHANICS,
    name: {
      en: 'Capitalization',
      vi: 'Viết hoa'
    },
    color: '#9b59b6'
  },
  PUNC: {
    code: 'PUNC',
    category: ERROR_CATEGORIES.MECHANICS,
    name: {
      en: 'Punctuation',
      vi: 'Dấu câu'
    },
    color: '#9b59b6'
  },
  FORM: {
    code: 'FORM',
    category: ERROR_CATEGORIES.MECHANICS,
    name: {
      en: 'Informal language',
      vi: 'Không phù hợp văn phong học thuật'
    },
    color: '#9b59b6'
  },

  // Sentence & Expression (Câu & diễn đạt)
  RO: {
    code: 'RO',
    category: ERROR_CATEGORIES.SENTENCE,
    name: {
      en: 'Run-on sentence',
      vi: 'Câu quá dài, nối sai'
    },
    color: '#f39c12' // Yellow
  },
  FS: {
    code: 'FS',
    category: ERROR_CATEGORIES.SENTENCE,
    name: {
      en: 'Fragment sentence',
      vi: 'Câu thiếu thành phần'
    },
    color: '#f39c12'
  },
  AWK: {
    code: 'AWK',
    category: ERROR_CATEGORIES.SENTENCE,
    name: {
      en: 'Awkward',
      vi: 'Diễn đạt gượng, không tự nhiên'
    },
    color: '#f39c12'
  },
  CL: {
    code: 'CL',
    category: ERROR_CATEGORIES.SENTENCE,
    name: {
      en: 'Clarity',
      vi: 'Chưa rõ ý'
    },
    color: '#f39c12'
  },
  PAR: {
    code: 'PAR',
    category: ERROR_CATEGORIES.SENTENCE,
    name: {
      en: 'Paraphrase',
      vi: 'Diễn đạt lại chưa tốt'
    },
    color: '#f39c12'
  },

  // Coherence & Task Response (Mạch lạc & phát triển ý)
  TR: {
    code: 'TR',
    category: ERROR_CATEGORIES.COHERENCE,
    name: {
      en: 'Task response',
      vi: 'Chưa trả lời đúng yêu cầu đề'
    },
    color: '#1abc9c' // Teal
  },
  DEV: {
    code: 'DEV',
    category: ERROR_CATEGORIES.COHERENCE,
    name: {
      en: 'Development',
      vi: 'Ý chưa được phát triển'
    },
    color: '#1abc9c'
  },
  COH: {
    code: 'COH',
    category: ERROR_CATEGORIES.COHERENCE,
    name: {
      en: 'Coherence',
      vi: 'Thiếu mạch lạc'
    },
    color: '#1abc9c'
  },
  CC: {
    code: 'CC',
    category: ERROR_CATEGORIES.COHERENCE,
    name: {
      en: 'Cohesive device',
      vi: 'Từ nối'
    },
    color: '#1abc9c'
  },
  EX: {
    code: 'EX',
    category: ERROR_CATEGORIES.COHERENCE,
    name: {
      en: 'Example needed',
      vi: 'Thiếu ví dụ'
    },
    color: '#1abc9c'
  }
}

export const ANNOTATION_TYPES = {
  ERROR: 'error',        // Underline with error type
  DELETE: 'delete',      // Strikethrough
  MISSING: 'missing'     // Insert missing word/phrase
}

/**
 * Get error type by code
 */
export function getErrorType(code) {
  return ERROR_TYPES[code] || null
}

/**
 * Get all error types grouped by category
 */
export function getErrorTypesByCategory() {
  const grouped = {}

  Object.values(ERROR_TYPES).forEach(errorType => {
    const category = errorType.category
    if (!grouped[category]) {
      grouped[category] = []
    }
    grouped[category].push(errorType)
  })

  return grouped
}

/**
 * Get category display name
 */
export function getCategoryName(category, lang = 'vi') {
  const names = {
    [ERROR_CATEGORIES.VOCABULARY]: {
      en: 'Vocabulary',
      vi: 'Từ vựng'
    },
    [ERROR_CATEGORIES.GRAMMAR]: {
      en: 'Grammar',
      vi: 'Ngữ pháp'
    },
    [ERROR_CATEGORIES.MECHANICS]: {
      en: 'Mechanics',
      vi: 'Chính tả & hình thức'
    },
    [ERROR_CATEGORIES.SENTENCE]: {
      en: 'Sentence & Expression',
      vi: 'Câu & diễn đạt'
    },
    [ERROR_CATEGORIES.COHERENCE]: {
      en: 'Coherence & Task Response',
      vi: 'Mạch lạc & phát triển ý'
    }
  }

  return names[category]?.[lang] || category
}

/**
 * Get localized text from bilingual object
 * Integrates with the project's useI18n() system
 */
export function getLocalizedText(bilingualObj, currentLang = 'vi') {
  if (typeof bilingualObj === 'string') return bilingualObj
  if (!bilingualObj) return ''

  // Support both 'vi'/'en' and 'vie'/'eng' codes
  const lang = currentLang.startsWith('vi') ? 'vi' : 'en'
  return bilingualObj[lang] || bilingualObj.vi || bilingualObj.en || ''
}

/**
 * Get error type name in current language
 */
export function getErrorTypeName(errorCode, currentLang = 'vi') {
  const errorType = ERROR_TYPES[errorCode]
  if (!errorType) return errorCode
  return getLocalizedText(errorType.name, currentLang)
}
