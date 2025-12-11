<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
      <svg v-if="icon === 'wallet'" class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
      </svg>
      <svg v-if="icon === 'chart'" class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      <svg v-if="icon === 'orders'" class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
    </div>
    
    <div class="mb-2">
      <div class="text-3xl font-bold text-gray-900 dark:text-white" :class="valueColorClass">
        {{ formattedBalance }}
      </div>
      <div v-if="subtitle" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        {{ subtitle }}
      </div>
    </div>
    
    <div v-if="change !== null" class="flex items-center text-sm">
      <span :class="changeClass" class="flex items-center">
        <svg v-if="change > 0" class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
        <svg v-if="change < 0" class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
        {{ Math.abs(change) }}%
      </span>
      <span class="text-gray-600 dark:text-gray-400 ml-2">{{ changeLabel }}</span>
    </div>
  </div>
</template>

<script>
import { formatCurrency } from '../utils/formatters';

export default {
  name: 'BalanceCard',
  props: {
    title: {
      type: String,
      default: 'Balance'
    },
    balance: {
      type: [Number, String],
      required: true
    },
    decimals: {
      type: Number,
      default: 2
    },
    currency: {
      type: String,
      default: 'USD'
    },
    subtitle: {
      type: String,
      default: ''
    },
    change: {
      type: Number,
      default: null
    },
    changeLabel: {
      type: String,
      default: 'vs last month'
    },
    icon: {
      type: String,
      default: 'wallet',
      validator: value => ['wallet', 'chart', 'orders'].includes(value)
    },
    color: {
      type: String,
      default: 'default',
      validator: value => ['default', 'green', 'red'].includes(value)
    }
  },
  computed: {
    formattedBalance() {
      const formatted = formatCurrency(this.balance, this.decimals);
      return this.currency ? `${this.currency} ${formatted}` : formatted;
    },
    changeClass() {
      if (this.change > 0) {
        return 'text-green-600 dark:text-green-400';
      } else if (this.change < 0) {
        return 'text-red-600 dark:text-red-400';
      }
      return 'text-gray-600 dark:text-gray-400';
    },
    valueColorClass() {
      const colors = {
        default: '',
        green: 'text-green-600 dark:text-green-400',
        red: 'text-red-600 dark:text-red-400'
      };
      return colors[this.color] || '';
    }
  }
};
</script>
