<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Order Book</h3>
        <div class="flex items-center space-x-2">
          <select
            v-model="selectedSymbol"
            @change="handleSymbolChange"
            class="text-sm px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="BTCUSD">BTC/USD</option>
            <option value="ETHUSD">ETH/USD</option>
          </select>
          <button 
            @click="fetchOrderbook"
            :disabled="loading"
            class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
            title="Refresh"
          >
            <svg 
              class="w-4 h-4" 
              :class="{ 'animate-spin': loading }"
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>
      </div>
    </div>
    
    <div v-if="loading && !orderbook" class="p-6">
      <div class="flex justify-center">
        <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>
    
    <div v-else-if="error" class="p-6 text-center">
      <p class="text-red-600 dark:text-red-400 text-sm">{{ error }}</p>
    </div>
    
    <div v-else class="overflow-hidden">
      <!-- Header Row -->
      <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-3 gap-4 text-xs font-semibold text-gray-600 dark:text-gray-400">
          <div class="text-left">Price (USD)</div>
          <div class="text-right">Amount</div>
          <div class="text-right">Total</div>
        </div>
      </div>
      
      <!-- Asks (Sell Orders) - Red -->
      <div class="max-h-64 overflow-y-auto">
        <div v-if="asks.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">
          No sell orders
        </div>
        <div
          v-for="(ask, index) in asks"
          :key="'ask-' + index"
          class="px-4 py-2 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors cursor-pointer relative"
          @click="$emit('price-selected', ask.price)"
        >
          <div 
            class="absolute inset-y-0 right-0 bg-red-100 dark:bg-red-900/30"
            :style="{ width: getDepthPercentage(ask.amount, asks) + '%' }"
          ></div>
          <div class="grid grid-cols-3 gap-4 text-sm relative z-10">
            <div class="text-left font-medium text-red-600 dark:text-red-400">
              {{ formatNumber(ask.price, 2) }}
            </div>
            <div class="text-right text-gray-700 dark:text-gray-300">
              {{ formatNumber(ask.amount, 8) }}
            </div>
            <div class="text-right text-gray-600 dark:text-gray-400 text-xs">
              {{ formatNumber(ask.total, 8) }}
            </div>
          </div>
        </div>
      </div>
      
      <!-- Spread -->
      <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 border-y border-gray-200 dark:border-gray-600">
        <div class="flex justify-between items-center">
          <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Spread:</span>
          <span class="text-sm font-bold text-gray-900 dark:text-white">
            {{ spread !== null ? formatNumber(spread, 2) : '--' }}
          </span>
        </div>
      </div>
      
      <!-- Bids (Buy Orders) - Green -->
      <div class="max-h-64 overflow-y-auto">
        <div v-if="bids.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">
          No buy orders
        </div>
        <div
          v-for="(bid, index) in bids"
          :key="'bid-' + index"
          class="px-4 py-2 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors cursor-pointer relative"
          @click="$emit('price-selected', bid.price)"
        >
          <div 
            class="absolute inset-y-0 right-0 bg-green-100 dark:bg-green-900/30"
            :style="{ width: getDepthPercentage(bid.amount, bids) + '%' }"
          ></div>
          <div class="grid grid-cols-3 gap-4 text-sm relative z-10">
            <div class="text-left font-medium text-green-600 dark:text-green-400">
              {{ formatNumber(bid.price, 2) }}
            </div>
            <div class="text-right text-gray-700 dark:text-gray-300">
              {{ formatNumber(bid.amount, 8) }}
            </div>
            <div class="text-right text-gray-600 dark:text-gray-400 text-xs">
              {{ formatNumber(bid.total, 8) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { formatNumber } from '../utils/formatters';

export default {
  name: 'OrderbookPanel',
  props: {
    symbol: {
      type: String,
      default: 'BTCUSD'
    },
    autoRefresh: {
      type: Boolean,
      default: true
    },
    refreshInterval: {
      type: Number,
      default: 5000 // 5 seconds
    },
    maxRows: {
      type: Number,
      default: 15
    }
  },
  data() {
    return {
      selectedSymbol: this.symbol,
      orderbook: null,
      loading: false,
      error: null,
      interval: null
    };
  },
  computed: {
    bids() {
      if (!this.orderbook || !this.orderbook.bids) return [];
      return this.orderbook.bids.slice(0, this.maxRows);
    },
    asks() {
      if (!this.orderbook || !this.orderbook.asks) return [];
      return this.orderbook.asks.slice(0, this.maxRows);
    },
    spread() {
      if (this.bids.length === 0 || this.asks.length === 0) return null;
      const highestBid = parseFloat(this.bids[0].price);
      const lowestAsk = parseFloat(this.asks[0].price);
      return lowestAsk - highestBid;
    }
  },
  mounted() {
    this.fetchOrderbook();
    
    if (this.autoRefresh) {
      this.startAutoRefresh();
    }
  },
  beforeDestroy() {
    this.stopAutoRefresh();
  },
  watch: {
    symbol(newSymbol) {
      this.selectedSymbol = newSymbol;
      this.fetchOrderbook();
    }
  },
  methods: {
    formatNumber,
    
    handleSymbolChange() {
      this.$emit('symbol-changed', this.selectedSymbol);
      this.fetchOrderbook();
    },
    
    async fetchOrderbook() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await fetch(`/api/orderbook?symbol=${this.selectedSymbol}`, {
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
          }
        });
        
        if (!response.ok) {
          throw new Error('Failed to fetch orderbook');
        }
        
        const data = await response.json();
        this.orderbook = data;
        
        this.$emit('orderbook-updated', data);
        
      } catch (error) {
        console.error('Error fetching orderbook:', error);
        this.error = 'Failed to load orderbook';
      } finally {
        this.loading = false;
      }
    },
    
    getDepthPercentage(amount, orders) {
      if (orders.length === 0) return 0;
      const maxAmount = Math.max(...orders.map(o => parseFloat(o.amount)));
      return (parseFloat(amount) / maxAmount) * 100;
    },
    
    startAutoRefresh() {
      this.interval = setInterval(() => {
        this.fetchOrderbook();
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
