// Examination Module Routes
// Import these routes in index.js and spread into the DashboardLayout children

const ExaminationIndex = () => import('@/pages/examination/ExaminationIndex.vue')
const QuestionBank = () => import('@/pages/examination/QuestionBank.vue')
const TestBank = () => import('@/pages/examination/TestBank.vue')
const TestBuilder = () => import('@/pages/examination/TestBuilder.vue')
const IELTSTestBuilder = () => import('@/pages/examination/ielts/IELTSTestBuilder.vue')
const Assignments = () => import('@/pages/examination/Assignments.vue')
const MyAssignments = () => import('@/pages/examination/MyAssignments.vue')
const TakeTest = () => import('@/pages/examination/TakeTest.vue')
const Result = () => import('@/pages/examination/Result.vue')
const ExaminationSettings = () => import('@/pages/examination/ExaminationSettings.vue')
const GradingList = () => import('@/pages/examination/GradingList.vue')
const GradingDetail = () => import('@/pages/examination/GradingDetail.vue')

// IELTS Practice (Student-facing)
const IELTSPracticeIndex = () => import('@/pages/examination/ielts-practice/IELTSPracticeIndex.vue')
const PracticeTestManagement = () => import('@/pages/examination/ielts-practice/PracticeTestManagement.vue')
const IELTSListeningTest = () => import('@/pages/examination/ielts-practice/IELTSListeningTest.vue')
const IELTSReadingTest = () => import('@/pages/examination/ielts-practice/IELTSReadingTest.vue')
const IELTSWritingTest = () => import('@/pages/examination/ielts-practice/IELTSWritingTest.vue')
const IELTSSpeakingTest = () => import('@/pages/examination/ielts-practice/IELTSSpeakingTest.vue')
const IELTSFullTest = () => import('@/pages/examination/ielts-practice/IELTSFullTest.vue')

export const examinationRoutes = [
  {
    path: 'examination',
    component: ExaminationIndex,
    meta: { permission: 'examination.view' },
    children: [
      {
        path: '',
        redirect: '/examination/my-assignments'
      },
      // Question Bank
      {
        path: 'questions',
        name: 'examination.questions',
        component: QuestionBank,
        meta: { permission: 'examination.questions.view' }
      },
      // Test Bank
      {
        path: 'tests',
        name: 'examination.tests',
        component: TestBank,
        meta: { 
          permission: [
            'examination.tests.view',
            'examination.ielts.tests.view',      // IELTS Test Bank
            'examination.cambridge.view',
            'examination.tests.create',
            'examination.tests.edit'
          ]
        }
      },
      {
        path: 'tests/create',
        name: 'examination.tests.create',
        component: TestBuilder,
        meta: { permission: 'examination.tests.create' }
      },
      {
        path: 'tests/:id/edit',
        name: 'examination.tests.edit',
        component: TestBuilder,
        meta: { permission: 'examination.tests.edit' }
      },
      // IELTS Test Builder (separate from generic TestBuilder)
      {
        path: 'ielts/create',
        name: 'examination.ielts.create',
        component: IELTSTestBuilder,
        meta: { 
          permission: [
            'examination.ielts.tests.create',
            'examination.tests.create'
          ]
        }
      },
      {
        path: 'ielts/:id/edit',
        name: 'examination.ielts.edit',
        component: IELTSTestBuilder,
        meta: { 
          permission: [
            'examination.ielts.tests.edit',
            'examination.tests.edit'
          ]
        }
      },
      // Assignments (Admin/Teacher view)
      {
        path: 'assignments',
        name: 'examination.assignments',
        component: Assignments,
        meta: { permission: 'examination.assignments.view' }
      },
      // My Assignments (Student view)
      {
        path: 'my-assignments',
        name: 'examination.my-assignments',
        component: MyAssignments
      },
      // IELTS Practice
      {
        path: 'ielts-practice',
        name: 'examination.ielts-practice',
        component: IELTSPracticeIndex
      },
      // Practice Test Management (Admin/Teacher)
      {
        path: 'ielts-practice/management',
        name: 'examination.ielts-practice.management',
        component: PracticeTestManagement,
        meta: { permission: 'examination.tests.create' }
      },
      // Settings
      {
        path: 'settings',
        name: 'examination.settings',
        component: ExaminationSettings,
        meta: { permission: 'examination.settings.manage' }
      },
      // Grading (Teacher view)
      {
        path: 'grading',
        name: 'examination.grading',
        component: GradingList,
        meta: { 
          permission: [
            'examination.grading.view',
            'examination.submissions.view',
            'examination.submissions.grade'
          ]
        }
      },
      {
        path: 'grading/:id',
        name: 'examination.grading.detail',
        component: GradingDetail,
        meta: { 
          permission: [
            'examination.grading.view',
            'examination.submissions.view',
            'examination.submissions.grade'
          ]
        }
      }
    ]
  },
  // Take Test (Full screen, outside of ExaminationIndex layout)
  {
    path: 'examination/take/:assignmentId',
    name: 'examination.take-test',
    component: TakeTest,
    meta: { fullScreen: true }
  },
  // View Result
  {
    path: 'examination/result/:submissionId',
    name: 'examination.result',
    component: Result
  },
  // IELTS Practice Tests (Full screen)
  {
    path: 'examination/ielts-practice/listening/:testId',
    name: 'examination.ielts-practice.listening',
    component: IELTSListeningTest,
    meta: { fullScreen: true }
  },
  {
    path: 'examination/ielts-practice/reading/:testId',
    name: 'examination.ielts-practice.reading',
    component: IELTSReadingTest,
    meta: { fullScreen: true }
  },
  {
    path: 'examination/ielts-practice/writing/:testId',
    name: 'examination.ielts-practice.writing',
    component: IELTSWritingTest,
    meta: { fullScreen: true }
  },
  {
    path: 'examination/ielts-practice/speaking/:testId',
    name: 'examination.ielts-practice.speaking',
    component: IELTSSpeakingTest,
    meta: { fullScreen: true }
  },
  {
    path: 'examination/ielts-practice/result/:submissionId',
    name: 'examination.ielts-practice.result',
    component: Result
  },
  // IELTS Full Test (All 3 skills)
  {
    path: 'examination/ielts-practice/full/:setNumber',
    name: 'examination.ielts-practice.full',
    component: IELTSFullTest,
    meta: { fullScreen: true }
  }
]

export default examinationRoutes
