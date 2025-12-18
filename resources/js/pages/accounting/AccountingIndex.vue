<template>
  <div class="flex h-full">
    <!-- Accounting Menu (Left Panel) -->
    <div class="w-80 bg-white border-r border-gray-200 overflow-y-auto">
      <div class="p-6 border-b border-gray-200">
        <h1 class="text-2xl font-bold text-gray-900">{{ t('accounting.title') }}</h1>
        <p class="text-sm text-gray-600 mt-1">{{ t('accounting.description') }}</p>
      </div>

      <!-- Accounting Categories -->
      <div class="p-4">
        <!-- Dashboard -->
        <div class="mb-2">
          <button
            @click="selectItem('dashboard')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'dashboard' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('accounting.dashboard') }}</div>
                <div class="text-xs text-gray-500">{{ t('accounting.overview') }}</div>
              </div>
            </div>
          </button>
        </div>

        <!-- Kế hoạch Thu Chi -->
        <div class="mb-2">
          <button
            @click="selectItem('financial-plans')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'financial-plans' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('accounting.financial_plans') }}</div>
                <div class="text-xs text-gray-500">{{ t('accounting.budget_planning') }}</div>
              </div>
            </div>
          </button>
        </div>

        <!-- Đề Xuất Chi & Báo Thu -->
        <div class="mb-2">
          <button
            @click="toggleCategory('proposals-reports')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-gray-50': expandedCategories.includes('proposals-reports') }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('accounting.proposals_reports') }}</div>
                <div class="text-xs text-gray-500">{{ t('accounting.expense_income') }}</div>
              </div>
            </div>
            <svg
              class="w-5 h-5 text-gray-400 transition-transform"
              :class="{ 'rotate-90': expandedCategories.includes('proposals-reports') }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Sub-items -->
          <div v-if="expandedCategories.includes('proposals-reports')" class="ml-4 mt-2 space-y-1">
            <button
              @click="selectItem('expense-proposals')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'expense-proposals' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span class="text-sm font-medium">{{ t('accounting.expense_proposals') }}</span>
            </button>
            <button
              @click="selectItem('income-reports')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'income-reports' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-sm font-medium">{{ t('accounting.income_reports') }}</span>
            </button>
          </div>
        </div>

        <!-- Duyệt Thu Chi -->
        <div class="mb-2">
          <button
            @click="selectItem('transactions')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'transactions' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('accounting.approve_transactions') }}</div>
                <div class="text-xs text-gray-500">{{ t('accounting.official_records') }}</div>
              </div>
            </div>
          </button>
        </div>

        <!-- Reports -->
        <div class="mb-2">
          <button
            @click="toggleCategory('reports')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-gray-50': expandedCategories.includes('reports') }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('accounting.reports') }}</div>
                <div class="text-xs text-gray-500">{{ t('accounting.analysis_reports') }}</div>
              </div>
            </div>
            <svg
              class="w-5 h-5 text-gray-400 transition-transform"
              :class="{ 'rotate-90': expandedCategories.includes('reports') }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Sub-items -->
          <div v-if="expandedCategories.includes('reports')" class="ml-4 mt-2 space-y-1">
            <button
              @click="selectItem('financial-report')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'financial-report' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
              </svg>
              <span class="text-sm font-medium">{{ t('accounting.financial_report') }}</span>
            </button>
            <button
              @click="selectItem('cash-flow')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'cash-flow' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              <span class="text-sm font-medium">{{ t('accounting.cash_flow') }}</span>
            </button>
          </div>
        </div>

        <!-- Định Khoản -->
        <div class="mb-2">
          <button
            @click="toggleCategory('account-setup')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-gray-50': expandedCategories.includes('account-setup') }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('accounting.account_setup') }}</div>
                <div class="text-xs text-gray-500">{{ t('accounting.manage_categories') }}</div>
              </div>
            </div>
            <svg
              class="w-5 h-5 text-gray-400 transition-transform"
              :class="{ 'rotate-90': expandedCategories.includes('account-setup') }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Sub-items -->
          <div v-if="expandedCategories.includes('account-setup')" class="ml-4 mt-2 space-y-1">
            <button
              @click="selectItem('account-categories')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'account-categories' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
              </svg>
              <span class="text-sm font-medium">{{ t('accounting.categories') }}</span>
            </button>
            <button
              @click="selectItem('account-items')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'account-items' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
              </svg>
              <span class="text-sm font-medium">{{ t('accounting.account_items') }}</span>
            </button>
            <button
              @click="selectItem('cash-accounts')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'cash-accounts' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              <span class="text-sm font-medium">{{ t('accounting.cash_accounts') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Area (Right Panel) -->
    <div class="flex-1 bg-gray-50 overflow-y-auto">
      <!-- Welcome State / Dashboard -->
      <div v-if="selectedItem === 'dashboard'" class="p-6">
        <AccountingDashboard @navigate="selectItem" />
      </div>

      <!-- Account Categories -->
      <div v-else-if="selectedItem === 'account-categories'" class="p-6">
        <AccountCategoriesList />
      </div>

      <!-- Account Items -->
      <div v-else-if="selectedItem === 'account-items'" class="p-6">
        <AccountItemsList />
      </div>

      <!-- Cash Accounts -->
      <div v-else-if="selectedItem === 'cash-accounts'" class="p-6">
        <CashAccountsList />
      </div>

      <!-- Financial Plans -->
      <div v-else-if="selectedItem === 'financial-plans'" class="p-6">
        <FinancialPlansList />
      </div>

      <!-- Expense Proposals -->
      <div v-else-if="selectedItem === 'expense-proposals'" class="p-6">
        <ExpenseProposalsList />
      </div>

      <!-- Income Reports -->
      <div v-else-if="selectedItem === 'income-reports'" class="p-6">
        <IncomeReportsList />
      </div>

      <!-- Transactions -->
      <div v-else-if="selectedItem === 'transactions'" class="p-6">
        <TransactionsList />
      </div>

      <!-- Financial Report -->
      <div v-else-if="selectedItem === 'financial-report'" class="p-6">
        <div class="max-w-7xl mx-auto">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ t('accounting.financial_report') }}</h2>
          <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600">{{ t('accounting.report_description') }}</p>
            <div class="mt-4 text-sm text-gray-500">
              Coming soon...
            </div>
          </div>
        </div>
      </div>

      <!-- Cash Flow -->
      <div v-else-if="selectedItem === 'cash-flow'" class="p-6">
        <div class="max-w-7xl mx-auto">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ t('accounting.cash_flow') }}</h2>
          <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600">{{ t('accounting.cashflow_description') }}</p>
            <div class="mt-4 text-sm text-gray-500">
              Coming soon...
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '../../composables/useI18n';
import AccountingDashboard from '../../components/accounting/AccountingDashboard.vue';
import AccountCategoriesList from '../../components/accounting/AccountCategoriesList.vue';
import AccountItemsList from '../../components/accounting/AccountItemsList.vue';
import CashAccountsList from '../../components/accounting/CashAccountsList.vue';
import FinancialPlansList from '../../components/accounting/FinancialPlansList.vue';
import ExpenseProposalsList from '../../components/accounting/ExpenseProposalsList.vue';
import IncomeReportsList from '../../components/accounting/IncomeReportsList.vue';
import TransactionsList from '../../components/accounting/TransactionsList.vue';

const { t } = useI18n();

const expandedCategories = ref(['account-setup']); // Default expanded
const selectedItem = ref('dashboard'); // Default selected

const toggleCategory = (category) => {
  const index = expandedCategories.value.indexOf(category);
  if (index > -1) {
    expandedCategories.value.splice(index, 1);
  } else {
    expandedCategories.value.push(category);
  }
};

const selectItem = (item) => {
  selectedItem.value = item;
};
</script>
