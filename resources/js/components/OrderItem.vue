<template>
  <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0">
      <!-- Order Info -->
      <div class="flex-1">
        <div class="flex items-center space-x-3 mb-2">
          <span class="font-semibold text-gray-900 dark:text-white">{{ order.symbol }}</span>
          <span
            class="px-2 py-1 text-xs font-semibold rounded"
            :class="sideClass"
          >
            {{ order.side.toUpperCase() }}
          </span>
          <span
            class="px-2 py-1 text-xs font-semibold rounded"
            :class="statusClass"
          >
            {{ statusText }}
          </span>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
          <div>
            <span class="text-gray-500 dark:text-gray-400">Price:</span>
            <span class="ml-1 font-medium text-gray-900 dark:text-white">${{ formatNumber(order.price, 3) }}</span>
          </div>
          <div>
            <span class="text-gray-500 dark:text-gray-400">Amount:</span>
            <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ formatNumber(order.amount, 3) }}</span>
          </div>
          <div>
            <span class="text-gray-500 dark:text-gray-400">{{ remainingLabel }}:</span>
            <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ formatNumber(remainingOrFilled, 3) }}</span>
            <span v-if="order.status === 1 && filledPercentage > 0" class="text-xs text-gray-500 dark:text-gray-400">
              ({{ (100 - filledPercentage).toFixed(0) }}% unfilled)
            </span>
          </div>
          <div>
            <span class="text-gray-500 dark:text-gray-400">Total Price:</span>
            <span class="ml-1 font-medium text-gray-900 dark:text-white">${{ formatNumber(totalPrice, 3) }}</span>
          </div>
        </div>
        
        <!-- Progress Bar for Filled Amount -->
        <div v-if="order.status === 1 && filledPercentage > 0" class="mt-2">
          <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
            <span>Filled: {{ filledPercentage.toFixed(2) }}%</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
            <div
              class="h-2 rounded-full transition-all duration-300"
              :class="order.side === 'buy' ? 'bg-green-500' : 'bg-red-500'"
              :style="{ width: filledPercentage + '%' }"
            ></div>
          </div>
        </div>
        
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
          {{ formatDate(order.created_at) }}
        </div>
      </div>
      
      <!-- Actions -->
      <div class="flex items-center space-x-2">
        <button
          v-if="order.status === 1"
          @click="confirmCancel"
          :disabled="cancelling"
          class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="cancelling">Cancelling...</span>
          <span v-else>Cancel Order</span>
        </button>
      </div>
    </div>
    
    <!-- Cancel Confirmation (optional inline confirmation) -->
    <div v-if="showConfirmation" class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
      <p class="text-sm text-yellow-800 dark:text-yellow-200 mb-2">Are you sure you want to cancel this order?</p>
      <div class="flex space-x-2">
        <button
          @click="handleCancel"
          class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm font-medium"
        >
          Yes, Cancel
        </button>
        <button
          @click="showConfirmation = false"
          class="px-3 py-1 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded text-sm font-medium"
        >
          No, Keep It
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { formatNumber, formatDate } from '../utils/formatters';

export default {
  name: 'OrderItem',
  props: {
    order: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      cancelling: false,
      showConfirmation: false
    };
  },
  computed: {
    statusText() {
      const statuses = {
        1: 'Open',
        2: 'Filled',
        3: 'Cancelled'
      };
      return statuses[this.order.status] || 'Unknown';
    },
    statusClass() {
      const classes = {
        1: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        2: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        3: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
      };
      return classes[this.order.status] || 'bg-gray-100 text-gray-800';
    },
    sideClass() {
      return this.order.side === 'buy'
        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    },
    filledPercentage() {
      const filled = parseFloat(this.order.amount) - parseFloat(this.order.remaining_amount || 0);
      return (filled / parseFloat(this.order.amount)) * 100;
    },
    remainingLabel() {
      if (this.order.status === 1) {
        // Open order - show what's left to fill
        return 'Unfilled';
      } else if (this.order.status === 2) {
        // Filled order
        return 'Filled';
      } else {
        // Cancelled order
        return 'Cancelled at';
      }
    },
    remainingOrFilled() {
      // For open orders, show remaining/unfilled amount
      // For filled/cancelled orders, show filled amount
      if (this.order.status === 1) {
        return parseFloat(this.order.remaining_amount || this.order.amount);
      } else {
        // Filled amount = original amount - remaining amount
        return parseFloat(this.order.amount) - parseFloat(this.order.remaining_amount || 0);
      }
    },
    totalPrice() {
      return parseFloat(this.order.price) * parseFloat(this.order.amount);
    }
  },
  methods: {
    formatNumber,
    formatDate,
    confirmCancel() {
      this.showConfirmation = true;
    },
    async handleCancel() {
      this.cancelling = true;
      
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      
      try {
        const response = await fetch(`/orders/${this.order.id}/cancel`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          }
        });
        
        const data = await response.json();
        
        if (data.success || data.order) {
          this.$emit('cancel', this.order.id);
          // Reload to show updated order list
          window.location.reload();
        } else {
          alert(data.message || 'Failed to cancel order');
        }
      } catch (error) {
        console.error('Cancel order error:', error);
        alert('An error occurred while cancelling the order');
      } finally {
        this.cancelling = false;
        this.showConfirmation = false;
      }
    }
  }
};
</script>
