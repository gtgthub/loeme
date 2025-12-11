<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Orders & Wallet Overview</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your orders and view wallet balances</p>
        </div>
        <button 
          @click="refreshAll"
          :disabled="refreshing"
          class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50"
        >
          <span class="flex items-center space-x-2">
            <svg 
              class="w-5 h-5" 
              :class="{ 'animate-spin': refreshing }"
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Refresh All</span>
          </span>
        </button>
      </div>
    </div>

    <!-- Wallet Balances Section -->
    <wallet-balances 
      ref="walletBalances"
      :auto-refresh="autoRefresh"
      @balances-updated="handleBalancesUpdated"
    />

    <!-- Two Column Layout for Orders and Orderbook -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Orders List (Takes 2/3 width on large screens) -->
      <div class="lg:col-span-2">
        <orders-table 
          ref="ordersTable"
          :auto-refresh="autoRefresh"
          :per-page="20"
          @orders-updated="handleOrdersUpdated"
          @order-cancelled="handleOrderCancelled"
        />
      </div>

      <!-- Orderbook Panel (Takes 1/3 width on large screens) -->
      <div class="lg:col-span-1">
        <orderbook-panel 
          ref="orderbookPanel"
          :symbol="selectedSymbol"
          :auto-refresh="autoRefresh"
          :refresh-interval="5000"
          @symbol-changed="handleSymbolChanged"
          @price-selected="handlePriceSelected"
          @orderbook-updated="handleOrderbookUpdated"
        />
      </div>
    </div>

    <!-- Toast Notification -->
    <div 
      v-if="toast.show"
      class="fixed bottom-4 right-4 z-50 animate-slide-up"
    >
      <div 
        class="px-6 py-4 rounded-lg shadow-lg border-l-4 max-w-md"
        :class="toast.type === 'success' ? 'bg-green-50 border-green-500 dark:bg-green-900/30' : 'bg-blue-50 border-blue-500 dark:bg-blue-900/30'"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg 
              v-if="toast.type === 'success'"
              class="w-6 h-6 text-green-600" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg 
              v-else
              class="w-6 h-6 text-blue-600" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-3 flex-1">
            <p 
              class="text-sm font-semibold"
              :class="toast.type === 'success' ? 'text-green-800 dark:text-green-300' : 'text-blue-800 dark:text-blue-300'"
            >
              {{ toast.message }}
            </p>
            <p 
              v-if="toast.detail"
              class="text-xs mt-1"
              :class="toast.type === 'success' ? 'text-green-700 dark:text-green-400' : 'text-blue-700 dark:text-blue-400'"
            >
              {{ toast.detail }}
            </p>
          </div>
          <button 
            @click="toast.show = false"
            class="ml-4 flex-shrink-0 text-gray-400 hover:text-gray-600"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import WalletBalances from './WalletBalances.vue';
import OrdersTable from './OrdersTable.vue';
import OrderbookPanel from './OrderbookPanel.vue';

export default {
  name: 'OrdersOverview',
  components: {
    WalletBalances,
    OrdersTable,
    OrderbookPanel
  },
  props: {
    pusherKey: {
      type: String,
      default: ''
    },
    pusherCluster: {
      type: String,
      default: 'mt1'
    },
    autoRefresh: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      selectedSymbol: 'BTCUSD',
      refreshing: false,
      toast: {
        show: false,
        type: 'success',
        message: '',
        detail: ''
      },
      pusherChannel: null
    };
  },
  mounted() {
    this.initializePusher();
  },
  beforeDestroy() {
    this.cleanupPusher();
  },
  methods: {
    initializePusher() {
      // Initialize Pusher if key is provided
      if (this.pusherKey && window.Pusher) {
        try {
          const pusher = new Pusher(this.pusherKey, {
            cluster: this.pusherCluster,
            encrypted: true
          });
          
          // Subscribe to user's private channel
          const userId = document.querySelector('meta[name="user-id"]')?.content;
          if (userId) {
            this.pusherChannel = pusher.subscribe(`private-user.${userId}`);
            
            // Listen for OrderMatched event
            this.pusherChannel.bind('OrderMatched', this.handleOrderMatched);
          }
        } catch (error) {
          console.error('Error initializing Pusher:', error);
        }
      }
    },
    
    cleanupPusher() {
      if (this.pusherChannel) {
        this.pusherChannel.unbind('OrderMatched');
      }
    },
    
    handleOrderMatched(data) {
      console.log('Order matched:', data);
      
      // Show toast notification
      this.showToast(
        'success',
        'Order Matched!',
        `Your ${data.side} order for ${data.amount} ${data.symbol} filled at $${data.price}`
      );
      
      // Refresh all components
      this.refreshAll();
    },
    
    handleBalancesUpdated(balances) {
      // Emit event if parent component needs it
      this.$emit('balances-updated', balances);
    },
    
    handleOrdersUpdated(orders) {
      // Emit event if parent component needs it
      this.$emit('orders-updated', orders);
    },
    
    handleOrderCancelled(orderId) {
      this.showToast('success', 'Order Cancelled', `Order #${orderId} has been cancelled successfully`);
      // Refresh balances as cancelling may free up locked funds
      if (this.$refs.walletBalances) {
        this.$refs.walletBalances.fetchBalances();
      }
    },
    
    handleSymbolChanged(symbol) {
      this.selectedSymbol = symbol;
    },
    
    handlePriceSelected(price) {
      // Emit event so parent can update order form if needed
      this.$emit('price-selected', price);
    },
    
    handleOrderbookUpdated(orderbook) {
      // Emit event if parent component needs it
      this.$emit('orderbook-updated', orderbook);
    },
    
    async refreshAll() {
      this.refreshing = true;
      
      try {
        const promises = [];
        
        if (this.$refs.walletBalances) {
          promises.push(this.$refs.walletBalances.fetchBalances());
        }
        
        if (this.$refs.ordersTable) {
          promises.push(this.$refs.ordersTable.fetchOrders());
        }
        
        if (this.$refs.orderbookPanel) {
          promises.push(this.$refs.orderbookPanel.fetchOrderbook());
        }
        
        await Promise.all(promises);
        
        this.showToast('success', 'Refreshed', 'All data has been refreshed successfully');
      } catch (error) {
        console.error('Error refreshing:', error);
      } finally {
        this.refreshing = false;
      }
    },
    
    showToast(type, message, detail = '') {
      this.toast = {
        show: true,
        type,
        message,
        detail
      };
      
      // Auto-hide after 5 seconds
      setTimeout(() => {
        this.toast.show = false;
      }, 5000);
    }
  }
};
</script>

<style scoped>
@keyframes slide-up {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.animate-slide-up {
  animation: slide-up 0.3s ease-out;
}
</style>
