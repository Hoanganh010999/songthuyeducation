/**
 * Scoring System Abstraction
 * Supports multiple scoring types: percentage, band, criteria
 */

import { ref, computed } from 'vue'

/**
 * IELTS Band Scores (0-9 in 0.5 increments)
 */
export const BAND_SCORES = [
  0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5,
  5, 5.5, 6, 6.5, 7, 7.5, 8, 8.5, 9
]

/**
 * IELTS Reading/Listening Score Conversion Table
 * Maps raw score (correct answers) to band score
 */
export const IELTS_CONVERSION_TABLE = {
  40: [
    { raw: '39-40', band: 9.0 },
    { raw: '37-38', band: 8.5 },
    { raw: '35-36', band: 8.0 },
    { raw: '33-34', band: 7.5 },
    { raw: '30-32', band: 7.0 },
    { raw: '27-29', band: 6.5 },
    { raw: '23-26', band: 6.0 },
    { raw: '19-22', band: 5.5 },
    { raw: '15-18', band: 5.0 },
    { raw: '13-14', band: 4.5 },
    { raw: '10-12', band: 4.0 },
    { raw: '8-9', band: 3.5 },
    { raw: '6-7', band: 3.0 },
    { raw: '4-5', band: 2.5 },
    { raw: '3', band: 2.0 },
    { raw: '2', band: 1.5 },
    { raw: '1', band: 1.0 },
    { raw: '0', band: 0.0 }
  ]
}

/**
 * Convert raw score to IELTS band score
 * @param {number} correct - Number of correct answers
 * @param {number} total - Total number of questions (default: 40)
 * @returns {number} IELTS band score (0-9)
 */
export function rawToBandScore(correct, total = 40) {
  // Scale to 40 if different total
  const scaled = total !== 40 ? Math.round((correct / total) * 40) : correct

  // Find band score from conversion table
  const table = IELTS_CONVERSION_TABLE[40]

  for (const entry of table) {
    if (entry.raw.includes('-')) {
      const [min, max] = entry.raw.split('-').map(Number)
      if (scaled >= min && scaled <= max) {
        return entry.band
      }
    } else {
      const value = Number(entry.raw)
      if (scaled === value) {
        return entry.band
      }
    }
  }

  return 0.0
}

/**
 * Calculate IELTS criteria-based band score
 * @param {object} criteria - Criteria scores object
 * @returns {number} Average band score
 */
export function calculateCriteriaBand(criteria) {
  const scores = Object.values(criteria)
    .filter(c => c && typeof c.score === 'number')
    .map(c => c.score)

  if (scores.length === 0) return null

  const average = scores.reduce((sum, score) => sum + score, 0) / scores.length

  // Round to nearest 0.5
  return Math.round(average * 2) / 2
}

/**
 * Round to nearest 0.5
 * @param {number} value
 * @returns {number}
 */
export function roundToHalf(value) {
  return Math.round(value * 2) / 2
}

/**
 * Validate IELTS criteria object
 * @param {object} criteria
 * @returns {boolean}
 */
export function validateCriteria(criteria) {
  if (!criteria || typeof criteria !== 'object') return false

  for (const key in criteria) {
    const item = criteria[key]
    if (!item || typeof item.score !== 'number') return false
    if (item.score < 0 || item.score > 9) return false
    if (!BAND_SCORES.includes(item.score)) return false
  }

  return true
}

/**
 * Use Scoring Composable
 * @param {object} config - Configuration object
 * @returns {object} Scoring methods and state
 */
export function useScoring(config = {}) {
  const {
    type = 'percentage', // 'percentage' | 'band' | 'criteria'
    min = 0,
    max = 100,
    step = 1
  } = config

  // Scoring strategies
  const scoringStrategies = {
    /**
     * Percentage Scoring (0-100)
     */
    percentage: {
      calculate(correct, total) {
        if (total === 0) return 0
        return Math.round((correct / total) * 100)
      },

      validate(value) {
        return typeof value === 'number' && value >= 0 && value <= 100
      },

      display(value) {
        return value !== null && value !== undefined ? `${value}%` : '-'
      },

      parse(input) {
        const num = typeof input === 'string' ? parseFloat(input.replace('%', '')) : input
        return isNaN(num) ? null : Math.max(0, Math.min(100, num))
      }
    },

    /**
     * Band Score Scoring (0-9 in 0.5 increments)
     */
    band: {
      calculate(correct, total) {
        return rawToBandScore(correct, total)
      },

      validate(value) {
        return typeof value === 'number' && BAND_SCORES.includes(value)
      },

      display(value) {
        return value !== null && value !== undefined ? `Band ${value}` : '-'
      },

      parse(input) {
        const num = typeof input === 'string' ? parseFloat(input.replace(/[^\d.]/g, '')) : input
        if (isNaN(num)) return null

        // Round to nearest 0.5
        const rounded = roundToHalf(num)
        return BAND_SCORES.includes(rounded) ? rounded : null
      }
    },

    /**
     * Criteria-based Scoring (IELTS Writing/Speaking)
     */
    criteria: {
      calculate(criteria) {
        return calculateCriteriaBand(criteria)
      },

      validate(value) {
        if (typeof value === 'number') {
          return BAND_SCORES.includes(value)
        }
        return validateCriteria(value)
      },

      display(value) {
        if (typeof value === 'number') {
          return `Band ${value}`
        }
        if (typeof value === 'object') {
          const avgBand = calculateCriteriaBand(value)
          return avgBand !== null ? `Band ${avgBand}` : '-'
        }
        return '-'
      },

      parse(input) {
        if (typeof input === 'object') {
          return validateCriteria(input) ? input : null
        }
        // Treat as band score
        const num = typeof input === 'string' ? parseFloat(input.replace(/[^\d.]/g, '')) : input
        if (isNaN(num)) return null

        const rounded = roundToHalf(num)
        return BAND_SCORES.includes(rounded) ? rounded : null
      }
    }
  }

  const currentStrategy = computed(() => scoringStrategies[type] || scoringStrategies.percentage)

  /**
   * Calculate score based on correct/total
   * @param {number} correct
   * @param {number} total
   * @returns {number}
   */
  const calculateScore = (correct, total) => {
    return currentStrategy.value.calculate(correct, total)
  }

  /**
   * Validate a score value
   * @param {any} value
   * @returns {boolean}
   */
  const validateScore = (value) => {
    return currentStrategy.value.validate(value)
  }

  /**
   * Display formatted score
   * @param {any} value
   * @returns {string}
   */
  const displayScore = (value) => {
    return currentStrategy.value.display(value)
  }

  /**
   * Parse input to score value
   * @param {any} input
   * @returns {number|object|null}
   */
  const parseScore = (input) => {
    return currentStrategy.value.parse(input)
  }

  return {
    type,
    min,
    max,
    step,
    calculateScore,
    validateScore,
    displayScore,
    parseScore,
    bandScores: BAND_SCORES,
    conversionTable: IELTS_CONVERSION_TABLE,
    rawToBandScore,
    calculateCriteriaBand,
    roundToHalf
  }
}
