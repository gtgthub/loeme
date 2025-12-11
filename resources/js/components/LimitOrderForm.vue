<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Place Limit Order</h3>
    
    <form @submit.prevent="handleSubmit">
      <!-- Symbol Dropdown -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Symbol
        </label>
        <select
          v-model="form.symbol"
          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
          :class="errors.symbol ? 'border-red-500' : 'border-gray-300'"
          required
        >
          <option value="">Select Symbol</option>
          <option value="BTCUSD">BTC/USD</option>
          <option value="ETHUSD">ETH/USD</option>
        </select>
        <p v-if="errors.symbol" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.symbol }}</p>
      </div>
      
      <!-- Buy/Sell Toggle -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Side
        </label>
        <div class="flex border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
          <button
            type="button"
            @click="form.side = 'buy'"
            :class="form.side === 'buy' ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
            class="flex-1 py-3 px-4 font-semibold transition-colors focus:outline-none"
          >
            BUY
          </button>
          <button
            type="button"
            @click="form.side = 'sell'"
            :class="form.side === 'sell' ? 'bg-red-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
            class="flex-1 py-3 px-4 font-semibold transition-colors focus:outline-none"
          >
            SELL
          </button>
        </div>
      </div>
      
      <!-- Price Input -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Price
        </label>
        <div class="relative">
          <input
            v-model="form.price"
            type="number"
            step="0.01"
            min="0.00000001"
            placeholder="0.00"
            class="w-full px-4 py-2 pr-16 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            :class="errors.price ? 'border-red-500' : 'border-gray-300'"
            required
            @input="validatePrice"
          />
          <span class="absolute right-3 top-2.5 text-gray-500 dark:text-gray-400 text-sm font-medium">USD</span>
        </div>
        <p v-if="errors.price" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.price }}</p>
      </div>
      
      <!-- Amount Input -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Amount
        </label>
        <div class="relative">
          <input
            v-model="form.amount"
            type="number"
            step="0.00000001"
            min="0.00000001"
            placeholder="0.00"
            class="w-full px-4 py-2 pr-16 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            :class="errors.amount ? 'border-red-500' : 'border-gray-300'"
            required
            @input="validateAmount"
          />
          <span class="absolute right-3 top-2.5 text-gray-500 dark:text-gray-400 text-sm font-medium">{{ baseSymbol }}</span>
        </div>
        <p v-if="errors.amount" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.amount }}</p>
      </div>
      
      <!-- Estimated Cost Preview -->
      <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
        <div class="space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">Estimated Cost:</span>
            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(estimatedCost, 2) }} USD</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">Available Balance:</span>
            <span class="font-medium" :class="hasInsufficientBalance ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
              {{ formatNumber(availableBalance, 8) }} {{ form.side === 'buy' ? 'USD' : baseSymbol }}
            </span>
          </div>
        </div>
      </div>
      
      <!-- Error Message -->
      <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <p class="text-sm text-red-600 dark:text-red-400">{{ errorMessage }}</p>
      </div>
      
      <!-- Success Message -->
      <div v-if="successMessage" class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <p class="text-sm text-green-600 dark:text-green-400">{{ successMessage }}</p>
      </div>
      
      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="submitting || !isFormValid || hasInsufficientBalance"
        class="w-full py-3 px-4 rounded-lg font-semibold text-white transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
        :class="form.side === 'buy' ? 'bg-green-500 hover:bg-green-600 focus:ring-green-500' : 'bg-red-500 hover:bg-red-600 focus:ring-red-500'"
      >
        <span v-if="submitting" class="flex items-center justify-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Processing...
        </span>
        <span v-else>
          Place {{ form.side === 'buy' ? 'Buy' : 'Sell' }} Order
        </span>
      </button>
    </form>
  </div>
</template>

<script>
import { formatNumber } from '../utils/formatters';

export default {
  name: 'LimitOrderForm',
  props: {
    balances: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      form: {
        symbol: 'BTCUSD',
        side: 'buy',
        price: '',
        amount: '',
        type: 'limit'
      },
      errors: {},
      errorMessage: '',
      successMessage: '',
      submitting: false
    };
  },
  computed: {
    baseSymbol() {
      if (!this.form.symbol) return '';
      return this.form.symbol.replace('USD', '');
    },
    estimatedCost() {
      const price = parseFloat(this.form.price) || 0;
      const amount = parseFloat(this.form.amount) || 0;
      return price * amount;
    },
    availableBalance() {
      if (!this.balances) return 0;
      
      const targetSymbol = this.form.side === 'buy' ? 'USD' : this.baseSymbol;
      const balance = this.balances[targetSymbol];
      
      if (!balance) return 0;
      return parseFloat(balance.available || balance.amount || 0);
    },
    hasInsufficientBalance() {
      if (this.form.side === 'buy') {
        return this.estimatedCost > this.availableBalance;
      } else {
        const amount = parseFloat(this.form.amount) || 0;
        return amount > this.availableBalance;
      }
    },
    isFormValid() {
      return this.form.symbol && 
             this.form.side && 
             this.form.price && 
             parseFloat(this.form.price) > 0 &&
             this.form.amount && 
             parseFloat(this.form.amount) > 0;
    }
  },
  methods: {
    formatNumber,
    
    validatePrice() {
      this.errors.price = '';
      const price = parseFloat(this.form.price);
      
      if (isNaN(price) || price <= 0) {
        this.errors.price = 'Price must be a positive number';
      }
    },
    
    validateAmount() {
      this.errors.amount = '';
      const amount = parseFloat(this.form.amount);
      
      if (isNaN(amount) || amount <= 0) {
        this.errors.amount = 'Amount must be a positive number';
      }
    },
    
    async handleSubmit() {
      this.errors = {};
      this.errorMessage = '';
      this.successMessage = '';
      
      // Validate form
      this.validatePrice();
      this.validateAmount();
      
      if (this.errors.price || this.errors.amount) {
        return;
      }
      
      if (!this.isFormValid) {
        this.errorMessage = 'Please fill in all required fields';
        return;
      }
      
      if (this.hasInsufficientBalance) {
        this.errorMessage = 'Insufficient balance';
        return;
      }
      
      this.submitting = true;
      
      try {
        const response = await fetch('/api/order', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify(this.form)
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
          this.successMessage = 'Order placed successfully!';
          
          // Clear form
          this.form.price = '';
          this.form.amount = '';
          
          // Emit event to parent to refresh data
          this.$emit('order-placed', data.order);
          
          // Clear success message after 3 seconds
          setTimeout(() => {
            this.successMessage = '';
          }, 3000);
          
        } else {
          this.errorMessage = data.message || 'Failed to place order';
        }
      } catch (error) {
        console.error('Order submission error:', error);
        this.errorMessage = 'An error occurred while placing the order';
      } finally {
        this.submitting = false;
      }
    }
  }
};
</script>
