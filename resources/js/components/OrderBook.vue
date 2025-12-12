<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Order Book</h3>
    </div>
    
    <div v-if="loading" class="p-6">
      <loading-spinner :visible="true" message="Loading orders..." />
    </div>
    
    <div v-else class="grid grid-cols-2 divide-x divide-gray-200 dark:divide-gray-700">
      <!-- Buy Orders (Bids) -->
      <div class="p-4">
        <div class="text-sm font-semibold text-green-600 dark:text-green-400 mb-3">Buy Orders</div>
        
        <!-- Column Headers -->
        <div class="flex justify-between items-center px-2 pb-2 mb-2 border-b border-gray-200 dark:border-gray-700">
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Price (USD)</span>
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Qty ({{ baseSymbol }})</span>
        </div>
        
        <div class="space-y-1">
          <div v-if="buyOrders.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8 text-sm">
            No buy orders
          </div>
          <div
            v-for="order in buyOrders"
            :key="order.id"
            class="flex justify-between items-center p-2 hover:bg-green-50 dark:hover:bg-green-900/20 rounded cursor-pointer transition-colors"
            @click="$emit('order-clicked', order)"
          >
            <div class="flex-1 relative">
              <div class="absolute inset-0 bg-green-100 dark:bg-green-900/30 rounded" :style="{ width: getDepthWidth(order, buyOrders) }"></div>
              <span class="relative text-sm font-medium text-green-600 dark:text-green-400">${{ formatNumber(order.price, 3) }}</span>
            </div>
            <span class="text-sm text-gray-700 dark:text-gray-300 ml-4">{{ formatNumber(order.remaining_amount, 3) }}</span>
          </div>
        </div>
      </div>
      
      <!-- Sell Orders (Asks) -->
      <div class="p-4">
        <div class="text-sm font-semibold text-red-600 dark:text-red-400 mb-3">Sell Orders</div>
        
        <!-- Column Headers -->
        <div class="flex justify-between items-center px-2 pb-2 mb-2 border-b border-gray-200 dark:border-gray-700">
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Price (USD)</span>
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Qty ({{ baseSymbol }})</span>
        </div>
        
        <div class="space-y-1">
          <div v-if="sellOrders.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8 text-sm">
            No sell orders
          </div>
          <div
            v-for="order in sellOrders"
            :key="order.id"
            class="flex justify-between items-center p-2 hover:bg-red-50 dark:hover:bg-red-900/20 rounded cursor-pointer transition-colors"
            @click="$emit('order-clicked', order)"
          >
            <div class="flex-1 relative">
              <div class="absolute inset-0 bg-red-100 dark:bg-red-900/30 rounded" :style="{ width: getDepthWidth(order, sellOrders) }"></div>
              <span class="relative text-sm font-medium text-red-600 dark:text-red-400">${{ formatNumber(order.price, 3) }}</span>
            </div>
            <span class="text-sm text-gray-700 dark:text-gray-300 ml-4">{{ formatNumber(order.remaining_amount, 3) }}</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Spread -->
    <div v-if="!loading && spread !== null" class="p-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-700">
      <div class="flex justify-between items-center text-sm">
        <span class="text-gray-600 dark:text-gray-400">Spread:</span>
        <span class="font-semibold text-gray-900 dark:text-white">${{ formatNumber(spread, 3) }} USD</span>
      </div>
    </div>
  </div>
</template>

<script>
import { formatNumber } from '../utils/formatters';
import LoadingSpinner from './LoadingSpinner.vue';

export default {
  name: 'OrderBook',
  components: {
    LoadingSpinner
  },
  props: {
    orders: {
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
    }
  },
  data() {
    return {
      loading: false,
      interval: null
    };
  },
  computed: {
    baseSymbol() {
      // Extract base symbol from symbol (e.g., "BTC" from "BTC-USD")
      const parts = this.symbol.split('-');
      return parts[0] || 'ASSET';
    },
    buyOrders() {
      return this.orders
        .filter(order => order.side === 'buy' && order.status === 1)
        .sort((a, b) => parseFloat(b.price) - parseFloat(a.price))
        .slice(0, 15);
    },
    sellOrders() {
      return this.orders
        .filter(order => order.side === 'sell' && order.status === 1)
        .sort((a, b) => parseFloat(a.price) - parseFloat(b.price))
        .slice(0, 15);
    },
    spread() {
      if (this.buyOrders.length === 0 || this.sellOrders.length === 0) return null;
      const highestBid = parseFloat(this.buyOrders[0].price);
      const lowestAsk = parseFloat(this.sellOrders[0].price);
      return lowestAsk - highestBid;
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
    getDepthWidth(order, orderList) {
      if (orderList.length === 0) return '0%';
      const maxAmount = Math.max(...orderList.map(o => parseFloat(o.remaining_amount)));
      const percentage = (parseFloat(order.remaining_amount) / maxAmount) * 100;
      return `${percentage}%`;
    },
    async fetchOrders() {
      if (this.loading) return;
      
      this.loading = true;
      
      try {
        // Convert symbol format: BTC-USD -> BTCUSD
        const normalizedSymbol = this.symbol.replace('-', '');
        const response = await fetch(`/api/orderbook?symbol=${normalizedSymbol}`);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.orders) {
          this.$emit('update:orders', data.orders);
        }
      } catch (error) {
        console.error('Error fetching orders:', error);
      } finally {
        this.loading = false;
      }
    },
    startAutoRefresh() {
      this.interval = setInterval(() => {
        this.fetchOrders();
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
