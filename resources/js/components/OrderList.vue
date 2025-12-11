<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My Orders</h3>
        
        <div class="flex flex-col sm:flex-row gap-3">
          <!-- Status Filter -->
          <select
            v-model="filterStatus"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-sm"
          >
            <option value="all">All Status</option>
            <option value="1">Open</option>
            <option value="2">Filled</option>
            <option value="3">Cancelled</option>
          </select>
          
          <!-- Symbol Filter -->
          <input
            v-model="filterSymbol"
            type="text"
            placeholder="Filter by symbol..."
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-sm"
          />
        </div>
      </div>
    </div>
    
    <div v-if="loading" class="p-6">
      <loading-spinner :visible="true" message="Loading orders..." />
    </div>
    
    <div v-else-if="filteredOrders.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
      </svg>
      <p>{{ filterStatus !== 'all' || filterSymbol ? 'No orders match your filters' : 'No orders yet' }}</p>
    </div>
    
    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
      <order-item
        v-for="order in filteredOrders"
        :key="order.id"
        :order="order"
        @cancel="handleCancel"
      />
    </div>
    
    <!-- Pagination could be added here if needed -->
  </div>
</template>

<script>
import LoadingSpinner from './LoadingSpinner.vue';
import OrderItem from './OrderItem.vue';

export default {
  name: 'OrderList',
  components: {
    LoadingSpinner,
    OrderItem
  },
  props: {
    orders: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      filterStatus: 'all',
      filterSymbol: ''
    };
  },
  computed: {
    filteredOrders() {
      let filtered = this.orders;
      
      // Filter by status
      if (this.filterStatus !== 'all') {
        filtered = filtered.filter(order => order.status === parseInt(this.filterStatus));
      }
      
      // Filter by symbol
      if (this.filterSymbol) {
        const query = this.filterSymbol.toLowerCase();
        filtered = filtered.filter(order => order.symbol.toLowerCase().includes(query));
      }
      
      // Sort by created_at descending
      filtered = [...filtered].sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return dateB - dateA;
      });
      
      return filtered;
    }
  },
  methods: {
    handleCancel(orderId) {
      this.$emit('cancel-order', orderId);
    }
  }
};
</script>
