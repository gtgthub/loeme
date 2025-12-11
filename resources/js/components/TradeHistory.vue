<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Trades</h3>
    </div>
    
    <div v-if="loading" class="p-6">
      <loading-spinner :visible="true" message="Loading trades..." />
    </div>
    
    <div v-else-if="trades.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <p>No recent trades</p>
    </div>
    
    <div v-else class="overflow-auto max-h-96">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Time</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Price</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr
            v-for="trade in displayedTrades"
            :key="trade.id"
            class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
              {{ formatRelativeTime(trade.created_at) }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium"
                :class="getTradeSideClass(trade)">
              {{ formatNumber(trade.price, 8) }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 text-right">
              {{ formatNumber(trade.amount, 8) }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 text-right font-medium">
              {{ formatNumber(parseFloat(trade.price) * parseFloat(trade.amount), 8) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div v-if="trades.length > visibleCount" class="p-4 border-t border-gray-200 dark:border-gray-700 text-center">
      <button
        @click="showAll = !showAll"
        class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium text-sm"
      >
        {{ showAll ? 'Show Less' : `View All (${trades.length})` }}
      </button>
    </div>
  </div>
</template>

<script>
import { formatNumber, formatRelativeTime } from '../utils/formatters';
import LoadingSpinner from './LoadingSpinner.vue';

export default {
  name: 'TradeHistory',
  components: {
    LoadingSpinner
  },
  props: {
    trades: {
      type: Array,
      default: () => []
    },
    symbol: {
      type: String,
      required: true
    },
    autoRefresh: {
      type: Boolean,
      default: true
    },
    refreshInterval: {
      type: Number,
      default: 5000
    },
    visibleCount: {
      type: Number,
      default: 20
    }
  },
  data() {
    return {
      loading: false,
      interval: null,
      showAll: false
    };
  },
  computed: {
    displayedTrades() {
      if (this.showAll) {
        return this.trades;
      }
      return this.trades.slice(0, this.visibleCount);
    }
  },
  mounted() {
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
    getTradeSideClass(trade) {
      // Determine side based on buy/sell order IDs
      // If you have access to which order was the taker, use that
      // For now, alternating colors for visual variety
      return trade.id % 2 === 0
        ? 'text-green-600 dark:text-green-400'
        : 'text-red-600 dark:text-red-400';
    },
    async fetchTrades() {
      if (this.loading) return;
      
      this.loading = true;
      
      try {
        // Convert symbol format: BTC-USD -> BTC-USD (keep as is, backend accepts it)
        const response = await fetch(`/api/trades/${this.symbol}`);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.trades) {
          this.$emit('update:trades', data.trades);
        }
      } catch (error) {
        console.error('Error fetching trades:', error);
      } finally {
        this.loading = false;
      }
    },
    startAutoRefresh() {
      this.interval = setInterval(() => {
        this.fetchTrades();
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
