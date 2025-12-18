/**
 * AI Grading Composable
 * Handles AI-powered essay grading with annotation generation
 */

import { ref } from 'vue'
import axios from 'axios'

/**
 * Use AI Grading
 * @param {object} config - Configuration object
 * @returns {object} AI grading state and operations
 */
export function useAIGrading(config = {}) {
  const {
    module = 'quality_management'  // Use quality management AI settings
  } = config

  // =========================================================================
  // STATE
  // =========================================================================

  const aiSettings = ref({
    provider: 'openai',
    model: 'gpt-5.1',
    hasApiKey: false,
    maskedApiKey: '',
    temperature: 0.3,
    maxTokens: 2000,
    azureKey: '',
    azureEndpoint: '',
    azureRegion: 'southeastasia',
    speakingLanguage: 'en-US'
  })

  const loading = ref(false)
  const grading = ref(false)
  const error = ref(null)

  // =========================================================================
  // OPERATIONS
  // =========================================================================

  /**
   * Load AI settings from backend
   * @returns {Promise<void>}
   */
  const loadAISettings = async () => {
    loading.value = true
    error.value = null

    try {
      console.log('[useAIGrading] 🔄 Loading AI settings for module:', module)
      console.log('[useAIGrading] 📍 Branch ID from localStorage:', localStorage.getItem('current_branch_id'))

      const response = await axios.get('/api/quality/ai-settings', {
        params: { module }
      })

      console.log('[useAIGrading] 📡 API Response:', response.data)

      if (response.data.success && response.data.data) {
        const allProviders = response.data.data

        console.log('[useAIGrading] 📦 All providers:', allProviders)

        // FORCE use OpenAI only (GPT)
        const providerSettings = allProviders.openai
        const selectedProvider = 'openai'

        console.log('[useAIGrading] ✅ Force using OpenAI provider:', selectedProvider, providerSettings)

        if (providerSettings && providerSettings.is_active) {
          aiSettings.value.provider = selectedProvider
          aiSettings.value.model = providerSettings.model || 'gpt-4o'
          aiSettings.value.hasApiKey = providerSettings.has_api_key || false
          aiSettings.value.maskedApiKey = providerSettings.masked_api_key || ''

          console.log('[useAIGrading] 🔑 OpenAI API Key Status:', {
            has_api_key: providerSettings.has_api_key,
            hasApiKey: aiSettings.value.hasApiKey,
            masked: aiSettings.value.maskedApiKey
          })

          if (providerSettings.settings) {
            aiSettings.value.temperature = providerSettings.settings.temperature ?? 0.3
            aiSettings.value.maxTokens = providerSettings.settings.max_tokens ?? 2000
          }
        } else {
          console.error('[useAIGrading] ❌ OpenAI (GPT) is not configured or not active')
        }
      } else {
        console.warn('[useAIGrading] ⚠️ API response not successful or no data')
      }
    } catch (err) {
      error.value = err
      console.error('[useAIGrading] ❌ Error loading AI settings:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Grade essay with AI
   * @param {string} essayText - The essay text to grade
   * @param {string} question - The essay question/prompt
   * @param {object} options - Additional options
   * @returns {Promise<object>} Grading result with annotations
   */
  const gradeEssayWithAI = async (essayText, question = '', options = {}) => {
    grading.value = true
    error.value = null

    try {
      console.log('[useAIGrading] 🤖 Grading essay with AI...', {
        essayLength: essayText.length,
        question: question.substring(0, 50) + '...',
        provider: aiSettings.value.provider,
        model: aiSettings.value.model
      })

      // Call backend API endpoint
      const response = await axios.post('/api/course/homework/grade-essay-with-ai', {
        essay_text: essayText,
        question: question,
        provider: aiSettings.value.provider,
        model: aiSettings.value.model,
        ...options
      })

      console.log('[useAIGrading] ✅ AI grading response:', response.data)

      if (response.data.success) {
        return response.data.data
      } else {
        throw new Error(response.data.message || 'AI grading failed')
      }
    } catch (err) {
      error.value = err
      console.error('[useAIGrading] ❌ Error grading essay:', err)
      throw err
    } finally {
      grading.value = false
    }
  }

  // =========================================================================
  // RETURN API
  // =========================================================================

  return {
    // State
    aiSettings,
    loading,
    grading,
    error,

    // Operations
    loadAISettings,
    gradeEssayWithAI
  }
}
