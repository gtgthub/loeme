<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Orders</h3>
        
        <!-- Filters -->
        <div class="flex flex-wrap gap-2">
          <!-- Symbol Filter -->
          <select
            v-model="filters.symbol"
            @change="applyFilters"
            class="text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">All Symbols</option>
            <option value="BTCUSD">BTC/USD</option>
            <option value="ETHUSD">ETH/USD</option>
          </select>
          
          <!-- Side Filter -->
          <select
            v-model="filters.side"
            @change="applyFilters"
            class="text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">All Sides</option>
            <option value="buy">Buy</option>
            <option value="sell">Sell</option>
          </select>
          
          <!-- Status Filter -->
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">All Status</option>
            <option value="1">Open</option>
            <option value="2">Filled</option>
            <option value="3">Cancelled</option>
          </select>
          
          <!-- Search -->
          <input
            v-model="filters.search"
            @input="applyFilters"
            type="text"
            placeholder="Search orders..."
            class="text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          />
          
          <!-- Refresh Button -->
          <button 
            @click="fetchOrders"
            :disabled="loading"
            class="px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm disabled:opacity-50"
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
    
    <div v-if="loading && !orders.length" class="p-6">
      <div class="flex justify-center">
        <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>
    
    <div v-else-if="error" class="p-6 text-center">
      <p class="text-red-600 dark:text-red-400">{{ error }}</p>
      <button 
        @click="fetchOrders"
        class="mt-3 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm"
      >
        Retry
      </button>
    </div>
    
    <div v-else-if="filteredOrders.length === 0" class="p-8 text-center">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      <p class="text-gray-500 dark:text-gray-400">No orders found</p>
    </div>
    
    <div v-else class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('id')">
              ID
              <span v-if="sortField === 'id'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('symbol')">
              Symbol
              <span v-if="sortField === 'symbol'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('side')">
              Side
              <span v-if="sortField === 'side'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('price')">
              Price
              <span v-if="sortField === 'price'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('amount')">
              Amount
              <span v-if="sortField === 'amount'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
              Filled
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('status')">
              Status
              <span v-if="sortField === 'status'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('created_at')">
              Time
              <span v-if="sortField === 'created_at'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr 
            v-for="order in paginatedOrders"
            :key="order.id"
            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
            :class="{ 'bg-blue-50 dark:bg-blue-900/20': order.status === 1 }"
          >
            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
              #{{ order.id }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
              <span class="font-medium">{{ order.symbol }}</span>
            </td>
            <td class="px-4 py-3 text-sm">
              <span 
                class="inline-flex px-2 py-1 rounded-full text-xs font-semibold"
                :class="order.side === 'buy' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
              >
                {{ order.side.toUpperCase() }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">
              ${{ formatNumber(order.price, 2) }}
            </td>
            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">
              {{ formatNumber(order.amount, 8) }}
            </td>
            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">
              {{ formatNumber(order.filled_amount, 8) }}
              <span v-if="order.amount > 0" class="text-xs text-gray-500 dark:text-gray-400">
                ({{ ((order.filled_amount / order.amount) * 100).toFixed(0) }}%)
              </span>
            </td>
            <td class="px-4 py-3 text-sm">
              <span 
                class="inline-flex px-2 py-1 rounded-full text-xs font-semibold"
                :class="getStatusClass(order.status)"
              >
                {{ order.status_label }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
              {{ formatDate(order.created_at) }}
            </td>
            <td class="px-4 py-3 text-sm text-center">
              <button 
                v-if="order.status === 1"
                @click="cancelOrder(order.id)"
                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium text-xs"
              >
                Cancel
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <div v-if="totalPages > 1" class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Showing {{ startIndex + 1 }} to {{ endIndex }} of {{ filteredOrders.length }} orders
        </div>
        <div class="flex space-x-2">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            Previous
          </button>
          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { formatNumber, formatDate } from '../utils/formatters';

export default {
  name: 'OrdersTable',
  props: {
    autoRefresh: {
      type: Boolean,
      default: false
    },
    refreshInterval: {
      type: Number,
      default: 10000
    },
    perPage: {
      type: Number,
      default: 20
    }
  },
  data() {
    return {
      orders: [],
      loading: false,
      error: null,
      filters: {
        symbol: '',
        side: '',
        status: '',
        search: ''
      },
      sortField: 'created_at',
      sortOrder: 'desc',
      currentPage: 1,
      interval: null
    };
  },
  computed: {
    filteredOrders() {
      let filtered = [...this.orders];
      
      // Apply filters
      if (this.filters.symbol) {
        filtered = filtered.filter(order => order.symbol === this.filters.symbol);
      }
      
      if (this.filters.side) {
        filtered = filtered.filter(order => order.side === this.filters.side);
      }
      
      if (this.filters.status) {
        filtered = filtered.filter(order => order.status === parseInt(this.filters.status));
      }
      
      if (this.filters.search) {
        const query = this.filters.search.toLowerCase();
        filtered = filtered.filter(order => 
          order.id.toString().includes(query) ||
          order.symbol.toLowerCase().includes(query) ||
          order.status_label.toLowerCase().includes(query)
        );
      }
      
      // Apply sorting
      filtered.sort((a, b) => {
        let aVal = a[this.sortField];
        let bVal = b[this.sortField];
        
        if (this.sortField === 'created_at') {
          aVal = new Date(aVal);
          bVal = new Date(bVal);
        } else if (typeof aVal === 'string') {
          aVal = aVal.toLowerCase();
          bVal = bVal.toLowerCase();
        }
        
        if (this.sortOrder === 'asc') {
          return aVal > bVal ? 1 : -1;
        } else {
          return aVal < bVal ? 1 : -1;
        }
      });
      
      return filtered;
    },
    paginatedOrders() {
      const start = (this.currentPage - 1) * this.perPage;
      const end = start + this.perPage;
      return this.filteredOrders.slice(start, end);
    },
    totalPages() {
      return Math.ceil(this.filteredOrders.length / this.perPage);
    },
    startIndex() {
      return (this.currentPage - 1) * this.perPage;
    },
    endIndex() {
      return Math.min(this.startIndex + this.perPage, this.filteredOrders.length);
    }
  },
  mounted() {
    this.fetchOrders();
    
    if (this.autoRefresh) {
      this.startAutoRefresh();
    }
  },
  beforeDestroy() {
    this.stopAutoRefresh();
  },
  methods: {
    formatNumber,
    formatDate,
    
    async fetchOrders() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await fetch('/api/orders', {
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
          }
        });
        
        if (!response.ok) {
          throw new Error('Failed to fetch orders');
        }
        
        const data = await response.json();
        this.orders = data.orders || [];
        
        this.$emit('orders-updated', this.orders);
        
      } catch (error) {
        console.error('Error fetching orders:', error);
        this.error = 'Failed to load orders';
      } finally {
        this.loading = false;
      }
    },
    
    async cancelOrder(orderId) {
      if (!confirm('Are you sure you want to cancel this order?')) {
        return;
      }
      
      try {
        const response = await fetch(`/api/orders/${orderId}/cancel`, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
          }
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
          // Refresh orders
          await this.fetchOrders();
          this.$emit('order-cancelled', orderId);
        } else {
          alert(data.message || 'Failed to cancel order');
        }
      } catch (error) {
        console.error('Error cancelling order:', error);
        alert('An error occurred while cancelling the order');
      }
    },
    
    getStatusClass(status) {
      switch (status) {
        case 1: // Open
          return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 2: // Filled
          return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 3: // Cancelled
          return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400';
        default:
          return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400';
      }
    },
    
    sortBy(field) {
      if (this.sortField === field) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortOrder = 'desc';
      }
    },
    
    applyFilters() {
      this.currentPage = 1; // Reset to first page when filters change
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
