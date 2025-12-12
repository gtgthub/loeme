<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-xl font-bold text-gray-900 dark:text-white">Wallet Balances</h3>
      <button 
        @click="fetchBalances"
        :disabled="loading"
        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
        title="Refresh balances"
      >
        <svg 
          class="w-5 h-5" 
          :class="{ 'animate-spin': loading }"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </button>
    </div>
    
    <div v-if="loading && !balances" class="py-8">
      <div class="flex flex-col items-center justify-center">
        <svg class="animate-spin h-10 w-10 text-blue-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Loading balances...</p>
      </div>
    </div>
    
    <div v-else-if="error" class="py-8">
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto mb-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-red-600 dark:text-red-400 mb-3">{{ error }}</p>
        <button 
          @click="fetchBalances"
          class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm"
        >
          Retry
        </button>
      </div>
    </div>
    
    <div v-else-if="balances" class="space-y-3">
      <!-- USD Balance -->
      <div 
        v-if="balances.USD"
        class="p-4 bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 rounded-lg border border-green-200 dark:border-green-800"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
              <span class="text-white font-bold text-sm">$</span>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">USD</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ formatNumber(balances.USD.amount, 2) }}
              </p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-xs text-gray-500 dark:text-gray-400">Available</p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">
              {{ formatNumber(balances.USD.available, 2) }}
            </p>
          </div>
        </div>
      </div>
      
      <!-- BTC Balance -->
      <div 
        v-if="balances.BTC"
        class="p-4 bg-gradient-to-r from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20 rounded-lg border border-orange-200 dark:border-orange-800"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center">
              <span class="text-white font-bold text-xs">₿</span>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bitcoin</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ formatNumber(balances.BTC.amount, 3) }}
              </p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-xs text-gray-500 dark:text-gray-400">Available</p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">
              {{ formatNumber(balances.BTC.available, 3) }}
            </p>
            <p v-if="balances.BTC.locked_amount > 0" class="text-xs text-orange-600 dark:text-orange-400 mt-1">
              Locked: {{ formatNumber(balances.BTC.locked_amount, 3) }}
            </p>
          </div>
        </div>
      </div>
      
      <!-- ETH Balance -->
      <div 
        v-if="balances.ETH"
        class="p-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg border border-purple-200 dark:border-purple-800"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
              <span class="text-white font-bold text-xs">Ξ</span>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ethereum</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ formatNumber(balances.ETH.amount, 3) }}
              </p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-xs text-gray-500 dark:text-gray-400">Available</p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">
              {{ formatNumber(balances.ETH.available, 3) }}
            </p>
            <p v-if="balances.ETH.locked_amount > 0" class="text-xs text-purple-600 dark:text-purple-400 mt-1">
              Locked: {{ formatNumber(balances.ETH.locked_amount, 3) }}
            </p>
          </div>
        </div>
      </div>
      
      <!-- No Balances -->
      <div v-if="Object.keys(balances).length === 0" class="py-8 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400">No balances available</p>
      </div>
    </div>
    
    <!-- Last Updated -->
    <div v-if="lastUpdated" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
      <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
        Last updated: {{ formatRelativeTime(lastUpdated) }}
      </p>
    </div>
  </div>
</template>

<script>
import { formatNumber, formatRelativeTime } from '../utils/formatters';

export default {
  name: 'WalletBalances',
  props: {
    autoRefresh: {
      type: Boolean,
      default: false
    },
    refreshInterval: {
      type: Number,
      default: 10000 // 10 seconds
    }
  },
  data() {
    return {
      balances: null,
      loading: false,
      error: null,
      lastUpdated: null,
      interval: null
    };
  },
  mounted() {
    this.fetchBalances();
    
    if (this.autoRefresh) {
      this.startAutoRefresh();
    }
  },
  beforeDestroy() {
    this.stopAutoRefresh();
  },
  methods: {
    formatNumber,
    formatRelativeTime,
    
    async fetchBalances() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await fetch('/api/profile', {
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
          }
        });
        
        if (!response.ok) {
          throw new Error('Failed to fetch balances');
        }
        
        const data = await response.json();
        this.balances = data.balances;
        this.lastUpdated = new Date();
        
        // Emit event with balances for parent component
        this.$emit('balances-updated', this.balances);
        
      } catch (error) {
        console.error('Error fetching balances:', error);
        this.error = 'Failed to load wallet balances';
      } finally {
        this.loading = false;
      }
    },
    
    startAutoRefresh() {
      this.interval = setInterval(() => {
        this.fetchBalances();
      }, this.refreshInterval);
    },
    
    stopAutoRefresh() {
      if (this.interval) {
        clearInterval(this.interval);
        this.interval = null;
      }
    }
  }
};
</script>
