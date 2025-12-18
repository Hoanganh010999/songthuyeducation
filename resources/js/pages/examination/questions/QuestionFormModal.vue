<template>
  <div class="modal modal-open">
    <div class="modal-box max-w-4xl max-h-[90vh] overflow-y-auto">
      <h3 class="font-bold text-lg mb-4">
        {{ isEditing ? 'Sß╗¡a c├óu hß╗Åi' : 'Tß║ío c├óu hß╗Åi mß╗¢i' }}
      </h3>

      <form @submit.prevent="handleSubmit">
        <!-- Basic Info -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <!-- Question Type -->
          <div class="form-control">
            <label class="label">
              <span class="label-text">Loß║íi c├óu hß╗Åi <span class="text-error">*</span></span>
            </label>
            <select v-model="form.type" class="select select-bordered w-full" required>
              <option value="">Chß╗ìn loß║íi c├óu hß╗Åi</option>
              <optgroup label="Trß║»c nghiß╗çm">
                <option value="multiple_choice">Trß║»c nghiß╗çm 1 ─æ├íp ├ín</option>
                <option value="multiple_response">Trß║»c nghiß╗çm nhiß╗üu ─æ├íp ├ín</option>
                <option value="true_false">─É├║ng/Sai</option>
                <option value="true_false_not_given">True/False/Not Given</option>
              </optgroup>
              <optgroup label="─Éiß╗ün v├áo">
                <option value="fill_blanks">─Éiß╗ün v├áo chß╗ù trß╗æng</option>
                <option value="fill_blanks_drag">K├⌐o thß║ú ─æiß╗ün chß╗ù trß╗æng</option>
                <option value="short_answer">Trß║ú lß╗¥i ngß║»n</option>
              </optgroup>
              <optgroup label="Nß╗æi/Sß║»p xß║┐p">
                <option value="matching">Nß╗æi cß╗Öt</option>
                <option value="matching_headings">Nß╗æi ti├¬u ─æß╗ü</option>
                <option value="ordering">Sß║»p xß║┐p thß╗⌐ tß╗▒</option>
              </optgroup>
              <optgroup label="T╞░╞íng t├íc">
                <option value="drag_drop">K├⌐o thß║ú</option>
                <option value="hotspot">─Éiß╗âm n├│ng (click v├╣ng)</option>
                <option value="labeling">Gß║»n nh├ún</option>
              </optgroup>
              <optgroup label="Ho├án th├ánh">
                <option value="sentence_completion">Ho├án th├ánh c├óu</option>
                <option value="summary_completion">Ho├án th├ánh t├│m tß║»t</option>
                <option value="note_completion">Ho├án th├ánh ghi ch├║</option>
                <option value="table_completion">Ho├án th├ánh bß║úng</option>
                <option value="flow_chart">Ho├án th├ánh s╞í ─æß╗ô</option>
              </optgroup>
              <optgroup label="Tß╗▒ luß║¡n">
                <option value="essay">Tß╗▒ luß║¡n (Writing)</option>
                <option value="audio_response">Ghi ├óm (Speaking)</option>
              </optgroup>
            </select>
          </div>

          <!-- Category -->
          <div class="form-control">
            <label class="label">
              <span class="label-text">Danh mß╗Ñc</span>
            </label>
            <select v-model="form.category_id" class="select select-bordered w-full">
              <option value="">Kh├┤ng c├│ danh mß╗Ñc</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Title -->
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Ti├¬u ─æß╗ü c├óu hß╗Åi <span class="text-error">*</span></span>
          </label>
          <input
            v-model="form.title"
            type="text"
            class="input input-bordered w-full"
            placeholder="Nhß║¡p ti├¬u ─æß╗ü c├óu hß╗Åi"
            required
          />
        </div>

        <!-- Content/Question Text -->
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Nß╗Öi dung c├óu hß╗Åi</span>
          </label>

          <!-- Rich Editor for Fill Blanks Types -->
          <div v-if="hasFillBlanks" class="border rounded-lg overflow-hidden">
            <div class="bg-gray-100 px-3 py-2 border-b flex items-center justify-between">
              <div class="flex gap-1">
                <button type="button" @click="formatContent('bold')" class="btn btn-xs btn-ghost" title="Bold">
                  <strong>B</strong>
                </button>
                <button type="button" @click="formatContent('italic')" class="btn btn-xs btn-ghost" title="Italic">
                  <em>I</em>
                </button>
                <button type="button" @click="formatContent('underline')" class="btn btn-xs btn-ghost" title="Underline">
                  <u>U</u>
                </button>
              </div>
              <button type="button" @click="insertBlank" class="btn btn-xs btn-primary">
                + Th├¬m chß╗ù trß╗æng
              </button>
            </div>
            <div
              ref="contentEditor"
              contenteditable="true"
              @input="updateContentFromEditor"
              @blur="updateContentFromEditor"
              class="min-h-[120px] p-3 focus:outline-none focus:ring-2 focus:ring-primary"
              v-html="form.content"
            ></div>
            <div class="bg-blue-50 px-3 py-2 text-xs text-gray-600">
              ≡ƒÆí Sß╗¡ dß╗Ñng <strong>[blank]</strong> ─æß╗â ─æ├ính dß║Ñu vß╗ï tr├¡ cß║ºn ─æiß╗ün. V├¡ dß╗Ñ: "The cat [blank] on the mat."
            </div>
          </div>

          <!-- Regular Textarea for Other Types -->
          <textarea
            v-else
            v-model="form.content"
            class="textarea textarea-bordered h-32"
            placeholder="Nhß║¡p nß╗Öi dung chi tiß║┐t cß╗ºa c├óu hß╗Åi..."
          ></textarea>
        </div>

        <!-- Options for Multiple Choice -->
        <div v-if="hasOptions" class="mb-4">
          <label class="label">
            <span class="label-text">─É├íp ├ín <span class="text-error">*</span></span>
            <button type="button" @click="addOption" class="btn btn-xs btn-ghost">
              + Th├¬m ─æ├íp ├ín
            </button>
          </label>
          <div class="space-y-2">
            <div v-for="(option, index) in form.options" :key="index" class="flex items-center gap-2">
              <input
                v-if="form.type === 'multiple_choice' || form.type === 'true_false'"
                type="radio"
                :name="'correct_option'"
                :checked="option.is_correct"
                @change="setCorrectOption(index)"
                class="radio radio-primary"
              />
              <input
                v-else-if="form.type === 'multiple_response'"
                type="checkbox"
                v-model="option.is_correct"
                class="checkbox checkbox-primary"
              />
              <span class="badge badge-ghost">{{ getOptionLabel(index) }}</span>
              <input
                v-model="option.content"
                type="text"
                class="input input-bordered flex-1"
                :placeholder="'─É├íp ├ín ' + getOptionLabel(index)"
              />
              <button
                type="button"
                @click="removeOption(index)"
                class="btn btn-ghost btn-sm btn-circle text-error"
                :disabled="form.options.length <= 2"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Correct Answer for Fill Blanks / Short Answer -->
        <div v-if="hasTextAnswer" class="form-control mb-4">
          <label class="label">
            <span class="label-text">─É├íp ├ín ─æ├║ng <span class="text-error">*</span></span>
          </label>

          <!-- Multiple Blanks - Individual Inputs -->
          <div v-if="hasFillBlanks && blankCount > 0" class="space-y-2">
            <div class="text-sm text-gray-600 mb-2">
              T├¼m thß║Ñy {{ blankCount }} chß╗ù trß╗æng trong nß╗Öi dung
            </div>
            <div v-for="(blank, index) in blankAnswers" :key="index" class="flex items-center gap-2">
              <span class="badge badge-primary">Blank {{ index + 1 }}</span>
              <input
                v-model="blankAnswers[index]"
                type="text"
                class="input input-bordered flex-1"
                :placeholder="'─É├íp ├ín ' + (index + 1)"
                @input="updateCorrectAnswer"
              />
            </div>
          </div>

          <!-- Single Answer Input (for short_answer, etc.) -->
          <div v-else>
            <input
              v-model="form.correct_answer"
              type="text"
              class="input input-bordered w-full"
              placeholder="Nhß║¡p ─æ├íp ├ín ─æ├║ng (ph├ón c├ích bß║▒ng | nß║┐u c├│ nhiß╗üu ─æ├íp ├ín)"
            />
            <label class="label">
              <span class="label-text-alt">V├¡ dß╗Ñ: answer1|answer2|answer3</span>
            </label>
          </div>
        </div>

        <!-- Essay/Audio Settings -->
        <div v-if="isProductionType" class="mb-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="form-control">
              <label class="label">
                <span class="label-text">Sß╗æ tß╗½ tß╗æi thiß╗âu</span>
              </label>
              <input
                v-model.number="form.settings.min_words"
                type="number"
                class="input input-bordered w-full"
                min="0"
              />
            </div>
            <div class="form-control">
              <label class="label">
                <span class="label-text">Sß╗æ tß╗½ tß╗æi ─æa</span>
              </label>
              <input
                v-model.number="form.settings.max_words"
                type="number"
                class="input input-bordered w-full"
                min="0"
              />
            </div>
          </div>
        </div>

        <!-- Scoring & Difficulty -->
        <div class="grid grid-cols-3 gap-4 mb-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">─Éiß╗âm</span>
            </label>
            <input
              v-model.number="form.points"
              type="number"
              class="input input-bordered w-full"
              min="0"
              step="0.5"
            />
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">─Éß╗Ö kh├│</span>
            </label>
            <select v-model="form.difficulty" class="select select-bordered w-full">
              <option value="easy">Dß╗à</option>
              <option value="medium">Trung b├¼nh</option>
              <option value="hard">Kh├│</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Thß╗¥i gian (gi├óy)</span>
            </label>
            <input
              v-model.number="form.time_limit"
              type="number"
              class="input input-bordered w-full"
              min="0"
              placeholder="Kh├┤ng giß╗¢i hß║ín"
            />
          </div>
        </div>

        <!-- Explanation -->
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Giß║úi th├¡ch ─æ├íp ├ín</span>
          </label>
          <textarea
            v-model="form.explanation"
            class="textarea textarea-bordered h-20"
            placeholder="Nhß║¡p giß║úi th├¡ch cho ─æ├íp ├ín ─æ├║ng..."
          ></textarea>
        </div>

        <!-- Tags -->
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Tags</span>
          </label>
          <input
            v-model="form.tags_input"
            type="text"
            class="input input-bordered w-full"
            placeholder="Nhß║¡p tags, ph├ón c├ích bß║▒ng dß║Ñu phß║⌐y"
          />
        </div>

        <!-- Actions -->
        <div class="modal-action">
          <button type="button" @click="showPreview = !showPreview" class="btn btn-ghost">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            {{ showPreview ? 'ß║¿n' : 'Xem' }} Preview
          </button>
          <div class="flex-1"></div>
          <button type="button" @click="$emit('close')" class="btn">Hß╗ºy</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <span v-if="saving" class="loading loading-spinner loading-sm mr-2"></span>
            {{ isEditing ? 'Cß║¡p nhß║¡t' : 'Tß║ío mß╗¢i' }}
          </button>
        </div>
      </form>

      <!-- Preview Section -->
      <div v-if="showPreview" class="mt-6 border-t pt-6">
        <h4 class="font-bold text-lg mb-4 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
          Preview c├óu hß╗Åi
        </h4>
        <div class="bg-gray-50 rounded-lg p-4 border">
          <QuestionRenderer
            :question="previewQuestion"
            :show-answer="false"
            @answer="() => {}"
          />
        </div>
      </div>
    </div>
    <div class="modal-backdrop" @click="$emit('close')"></div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import examinationApi from '@/services/examinationApi'
import QuestionRenderer from '@/components/examination/questions/QuestionRenderer.vue'

const props = defineProps({
  question: Object,
  categories: Array,
  questionTypes: Array,
})

const emit = defineEmits(['close', 'saved'])

const saving = ref(false)
const showPreview = ref(false)
const contentEditor = ref(null)
const blankAnswers = ref([])

const form = reactive({
  type: '',
  category_id: '',
  title: '',
  content: '',
  options: [
    { content: '', is_correct: true, label: 'A' },
    { content: '', is_correct: false, label: 'B' },
    { content: '', is_correct: false, label: 'C' },
    { content: '', is_correct: false, label: 'D' },
  ],
  correct_answer: '',
  points: 1,
  difficulty: 'medium',
  time_limit: null,
  explanation: '',
  tags_input: '',
  settings: {
    min_words: null,
    max_words: null,
  },
})

const isEditing = computed(() => !!props.question)

const hasOptions = computed(() => {
  return ['multiple_choice', 'multiple_response', 'true_false', 'true_false_not_given'].includes(form.type)
})

const hasTextAnswer = computed(() => {
  return ['fill_blanks', 'fill_blanks_drag', 'short_answer', 'sentence_completion', 'summary_completion', 'note_completion'].includes(form.type)
})

const hasFillBlanks = computed(() => {
  const result = ['fill_blanks', 'fill_blanks_drag', 'sentence_completion', 'summary_completion', 'note_completion'].includes(form.type)
  console.log('🔍 hasFillBlanks:', result, 'type:', form.type)
  return result
})

const isProductionType = computed(() => {
  return ['essay', 'audio_response'].includes(form.type)
})

const blankCount = computed(() => {
  const matches = form.content.match(/\[blank\]/g)
  const count = matches ? matches.length : 0
  console.log('🔢 blankCount:', count, 'content:', form.content.substring(0, 50))
  return count
})

const previewQuestion = computed(() => {
  return {
    id: 'preview',
    type: form.type,
    title: form.title,
    content: form.content,
    options: form.options,
    correct_answer: form.correct_answer,
    points: form.points,
    settings: form.settings,
  }
})

onMounted(() => {
  if (props.question) {
    // Populate form with existing question data
    Object.assign(form, {
      type: props.question.type,
      category_id: props.question.category_id || '',
      title: props.question.title,
      content: props.question.content || '',
      correct_answer: props.question.correct_answer || '',
      points: props.question.points || 1,
      difficulty: props.question.difficulty || 'medium',
      time_limit: props.question.time_limit,
      explanation: props.question.explanation || '',
      tags_input: props.question.tags?.join(', ') || '',
      settings: props.question.settings || { min_words: null, max_words: null },
    })

    if (props.question.options?.length) {
      form.options = props.question.options.map(opt => ({
        content: opt.content,
        is_correct: opt.is_correct,
        label: opt.label,
      }))
    }
  }
})

watch(() => form.type, (newType) => {
  // Reset options for true/false type
  if (newType === 'true_false') {
    form.options = [
      { content: 'True', is_correct: true, label: 'A' },
      { content: 'False', is_correct: false, label: 'B' },
    ]
  } else if (newType === 'true_false_not_given') {
    form.options = [
      { content: 'True', is_correct: false, label: 'A' },
      { content: 'False', is_correct: false, label: 'B' },
      { content: 'Not Given', is_correct: false, label: 'C' },
    ]
  }
})

// Watch blank count and sync blank answers
watch(blankCount, (newCount, oldCount) => {
  if (newCount > blankAnswers.value.length) {
    // Add new blank answers
    for (let i = blankAnswers.value.length; i < newCount; i++) {
      blankAnswers.value.push('')
    }
  } else if (newCount < blankAnswers.value.length) {
    // Remove excess blank answers
    blankAnswers.value = blankAnswers.value.slice(0, newCount)
  }
  updateCorrectAnswer()
})

// Initialize blank answers on mount
watch(() => form.content, () => {
  if (hasFillBlanks.value) {
    const count = blankCount.value
    if (blankAnswers.value.length === 0 && count > 0) {
      // Initialize from existing correct_answer
      const existingAnswers = form.correct_answer.split('|').map(a => a.trim())
      blankAnswers.value = Array(count).fill('').map((_, i) => existingAnswers[i] || '')
    }
  }
}, { immediate: true })

function getOptionLabel(index) {
  return String.fromCharCode(65 + index) // A, B, C, D, ...
}

function addOption() {
  form.options.push({
    content: '',
    is_correct: false,
    label: getOptionLabel(form.options.length),
  })
}

function removeOption(index) {
  if (form.options.length > 2) {
    form.options.splice(index, 1)
    // Re-label options
    form.options.forEach((opt, i) => {
      opt.label = getOptionLabel(i)
    })
  }
}

function setCorrectOption(index) {
  form.options.forEach((opt, i) => {
    opt.is_correct = i === index
  })
}

// Rich Editor Functions
function insertBlank() {
  console.log('✨ insertBlank called')
  const editor = contentEditor.value
  if (!editor) {
    console.error('❌ contentEditor ref is null!')
    return
  }

  const selection = window.getSelection()
  const range = selection.getRangeAt(0)

  // Insert [blank] at cursor position
  const blankNode = document.createTextNode('[blank]')
  range.deleteContents()
  range.insertNode(blankNode)

  // Move cursor after [blank]
  range.setStartAfter(blankNode)
  range.setEndAfter(blankNode)
  selection.removeAllRanges()
  selection.addRange(range)

  // Update form content
  form.content = editor.innerHTML
  console.log('✅ Blank inserted. New content:', form.content)

  // Focus back to editor
  editor.focus()
}

function formatContent(command) {
  document.execCommand(command, false, null)
  if (contentEditor.value) {
    form.content = contentEditor.value.innerHTML
  }
}

function updateContentFromEditor(event) {
  form.content = event.target.innerHTML
}

function updateCorrectAnswer() {
  if (hasFillBlanks.value && blankAnswers.value.length > 0) {
    form.correct_answer = blankAnswers.value.join('|')
  }
}

async function handleSubmit() {
  saving.value = true

  try {
    const payload = {
      type: form.type,
      category_id: form.category_id || null,
      title: form.title,
      content: form.content,
      points: form.points,
      difficulty: form.difficulty,
      time_limit: form.time_limit,
      explanation: form.explanation,
      tags: form.tags_input ? form.tags_input.split(',').map(t => t.trim()) : [],
      settings: form.settings,
    }

    if (hasOptions.value) {
      payload.options = form.options.map((opt, index) => ({
        content: opt.content,
        is_correct: opt.is_correct,
        label: getOptionLabel(index),
        sort_order: index,
      }))
    }

    if (hasTextAnswer.value) {
      payload.correct_answer = form.correct_answer
    }

    if (isEditing.value) {
      await examinationApi.questions.update(props.question.id, payload)
    } else {
      await examinationApi.questions.create(payload)
    }

    emit('saved')
  } catch (error) {
    console.error('Failed to save question:', error)
    alert('Kh├┤ng thß╗â l╞░u c├óu hß╗Åi: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}
</script>
