<template>
  <div class="flex h-full">
    <!-- Sales Menu (Left Sidebar) -->
    <div class="w-64 bg-white border-r border-gray-200 overflow-y-auto">
      <div class="p-6 border-b border-gray-200">
        <h1 class="text-2xl font-bold text-gray-900">{{ t('sales.title') }}</h1>
        <p class="text-sm text-gray-600 mt-1">{{ t('sales.description') }}</p>
      </div>

      <!-- Sales Menu Items -->
      <div class="p-4">
        <!-- Customers -->
        <div class="mb-2">
          <button
            @click="selectItem('customers')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'customers' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('customers.title') }}</div>
                <div class="text-xs text-gray-500">{{ t('customers.list') }}</div>
              </div>
            </div>
          </button>
        </div>

        <!-- Products -->
        <div class="mb-2">
          <button
            @click="selectItem('products')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'products' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('products.title') }}</div>
                <div class="text-xs text-gray-500">{{ t('products.list') }}</div>
              </div>
            </div>
          </button>
        </div>

        <!-- Enrollments/Registrations -->
        <div class="mb-2">
          <button
            @click="selectItem('enrollments')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'enrollments' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('enrollments.title') }}</div>
                <div class="text-xs text-gray-500">{{ t('enrollments.description') }}</div>
              </div>
            </div>
          </button>
        </div>

        <!-- Sales Campaigns -->
        <div class="mb-2">
          <button
            @click="selectItem('campaigns')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'campaigns' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('campaigns.title') }}</div>
                <div class="text-xs text-gray-500">{{ t('campaigns.description') }}</div>
              </div>
            </div>
          </button>
        </div>

        <!-- Vouchers -->
        <div class="mb-2">
          <button
            @click="selectItem('vouchers')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'vouchers' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('vouchers.title') }}</div>
                <div class="text-xs text-gray-500">{{ t('vouchers.description') }}</div>
              </div>
            </div>
          </button>
        </div>

        <!-- Settings Section -->
        <div class="mt-4 pt-4 border-t border-gray-200">
          <div class="mb-2">
            <button
              @click="selectItem('settings')"
              class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'settings' }"
            >
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <div class="text-left">
                  <div class="font-semibold text-gray-900">{{ t('common.settings') }}</div>
                  <div class="text-xs text-gray-500">{{ t('sales.settings_description') }}</div>
                </div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Area (Right Panel) -->
    <div class="flex-1 bg-gray-50 overflow-y-auto">
      <div class="p-6">
        <!-- Customers -->
        <div v-if="selectedItem === 'customers'">
          <CustomersList />
        </div>

        <!-- Products -->
        <div v-else-if="selectedItem === 'products'">
          <ProductsList />
        </div>

        <!-- Enrollments -->
        <div v-else-if="selectedItem === 'enrollments'">
          <EnrollmentsList />
        </div>

        <!-- Sales Campaigns -->
        <div v-else-if="selectedItem === 'campaigns'">
          <CampaignsList />
        </div>

        <!-- Vouchers -->
        <div v-else-if="selectedItem === 'vouchers'">
          <VouchersListComplete />
        </div>

        <!-- Settings -->
        <div v-else-if="selectedItem === 'settings'">
          <SalesSettings />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '../../composables/useI18n';
import CustomersList from '../customers/CustomersList.vue';
import ProductsList from '../products/ProductsList.vue';
import EnrollmentsList from '../enrollments/EnrollmentsList.vue';
import CampaignsList from './CampaignsList.vue';
import VouchersListComplete from './VouchersListComplete.vue';
import SalesSettings from './SalesSettings.vue';

const { t } = useI18n();
const selectedItem = ref('customers');

const selectItem = (item) => {
  selectedItem.value = item;
};
</script>
