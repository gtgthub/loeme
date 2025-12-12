<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Place Order</h3>
    
    <!-- Buy/Sell Toggle -->
    <div class="flex mb-4 border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
      <button
        @click="side = 'buy'"
        :class="side === 'buy' ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
        class="flex-1 py-2 px-4 font-semibold transition-colors focus:outline-none"
      >
        Buy
      </button>
      <button
        @click="side = 'sell'"
        :class="side === 'sell' ? 'bg-red-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
        class="flex-1 py-2 px-4 font-semibold transition-colors focus:outline-none"
      >
        Sell
      </button>
    </div>
    
    <form @submit.prevent="handleSubmit">
      <!-- Price Input -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Price
        </label>
        <div class="relative">
          <input
            v-model="form.price"
            type="number"
            step="0.00000001"
            min="0"
            placeholder="0.00"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            :class="errors.price ? 'border-red-500' : 'border-gray-300'"
            @input="onPriceInput"
            required
          />
          <span class="absolute right-3 top-2.5 text-gray-500 dark:text-gray-400 text-sm">{{ currency }}</span>
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
            min="0"
            placeholder="0.00"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            :class="errors.amount ? 'border-red-500' : 'border-gray-300'"
            @input="onAmountInput"
            required
          />
          <span class="absolute right-3 top-2.5 text-gray-500 dark:text-gray-400 text-sm">{{ baseSymbol }}</span>
        </div>
        <p v-if="errors.amount" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.amount }}</p>
      </div>
      
      <!-- Total Display -->
      <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
        <div class="flex justify-between text-sm">
          <span class="text-gray-600 dark:text-gray-400">Total:</span>
          <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(total, 3) }} {{ currency }}</span>
        </div>
      </div>
      
      <!-- Available Balance -->
      <div v-if="userBalance !== null" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        <div class="flex justify-between">
          <span>Available:</span>
          <span class="font-medium">{{ formatNumber(userBalance, 3) }} {{ side === 'buy' ? currency : baseSymbol }}</span>
        </div>
      </div>
      
      <!-- Debug Info (temporary) -->
      <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-xs">
        <div><strong>Debug:</strong></div>
        <div>Price: {{ form.price }} (valid: {{ form.price && parseFloat(form.price) > 0 }})</div>
        <div>Amount: {{ form.amount }} (valid: {{ form.amount && parseFloat(form.amount) > 0 }})</div>
        <div>isValid: {{ isValid }}</div>
        <div>submitting: {{ submitting }}</div>
        <div>Button disabled: {{ submitting || !isValid }}</div>
      </div>
      
      <!-- Error Message -->
      <error-message
        v-if="errorMessage"
        :message="errorMessage"
        type="danger"
        :dismissible="true"
        @dismissed="errorMessage = ''"
        class="mb-4"
      />
      
      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="submitting || !isValid"
        class="w-full py-3 px-4 rounded-lg font-semibold text-white transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
        :class="side === 'buy' ? 'bg-green-500 hover:bg-green-600 focus:ring-green-500' : 'bg-red-500 hover:bg-red-600 focus:ring-red-500'"
      >
        <span v-if="submitting">
          <loading-spinner :visible="true" size="sm" color="white" container-class="inline-flex" />
          Processing...
        </span>
        <span v-else>
          {{ side === 'buy' ? 'Buy' : 'Sell' }} {{ baseSymbol }}
        </span>
      </button>
    </form>
  </div>
</template>

<script>
import { formatNumber } from '../utils/formatters';
import { validateOrderData, validateSufficientBalance } from '../utils/validators';
import LoadingSpinner from './LoadingSpinner.vue';
import ErrorMessage from './ErrorMessage.vue';

export default {
  name: 'OrderForm',
  components: {
    LoadingSpinner,
    ErrorMessage
  },
  props: {
    symbol: {
      type: String,
      required: true
    },
    userAssets: {
      type: Array,
      default: () => []
    },
    initialSide: {
      type: String,
      default: 'buy',
      validator: value => ['buy', 'sell'].includes(value)
    }
  },
  data() {
    return {
      side: this.initialSide,
      form: {
        price: '',
        amount: ''
      },
      errors: {},
      errorMessage: '',
      submitting: false
    };
  },
  mounted() {
    // Ensure submitting is false on mount (in case of browser back button or cached state)
    this.submitting = false;
    console.log('OrderForm mounted - submitting:', this.submitting, 'isValid:', this.isValid);
  },
  watch: {
    submitting(newVal) {
      console.log('Submitting changed to:', newVal);
    },
    isValid(newVal) {
      console.log('isValid changed to:', newVal, 'price:', this.form.price, 'amount:', this.form.amount);
    }
  },
  computed: {
    // Normalize symbol to use slash separator for display
    displaySymbol() {
      return this.symbol.replace('-', '/');
    },
    currency() {
      return this.displaySymbol.split('/')[1] || 'USD';
    },
    baseSymbol() {
      return this.displaySymbol.split('/')[0];
    },
    total() {
      const price = parseFloat(this.form.price) || 0;
      const amount = parseFloat(this.form.amount) || 0;
      return price * amount;
    },
    userBalance() {
      if (!this.userAssets || this.userAssets.length === 0) return null;
      
      const assetSymbol = this.side === 'buy' ? this.currency : this.baseSymbol;
      const asset = this.userAssets.find(a => a.symbol === assetSymbol);
      
      if (!asset) return 0;
      return parseFloat(asset.amount) - parseFloat(asset.locked_amount || 0);
    },
    isValid() {
      const priceValid = this.form.price && parseFloat(this.form.price) > 0;
      const amountValid = this.form.amount && parseFloat(this.form.amount) > 0;
      const valid = priceValid && amountValid;
      return valid;
    }
  },
  methods: {
    formatNumber,
    onPriceInput() {
      console.log('Price changed:', this.form.price, 'isValid:', this.isValid);
      this.$forceUpdate();
    },
    onAmountInput() {
      console.log('Amount changed:', this.form.amount, 'isValid:', this.isValid);
      this.$forceUpdate();
    },
    async handleSubmit() {
      this.errors = {};
      this.errorMessage = '';
      
      // Validate order data
      const validation = validateOrderData(this.form.price, this.form.amount);
      if (!validation.isValid) {
        this.errors = validation.errors;
        return;
      }
      
      // Check balance
      const requiredAmount = this.side === 'buy' ? this.total : parseFloat(this.form.amount);
      if (this.userBalance !== null && !validateSufficientBalance(requiredAmount, this.userBalance)) {
        this.errorMessage = 'Insufficient balance';
        return;
      }
      
      // Get CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      if (!csrfToken) {
        this.errorMessage = 'CSRF token not found';
        return;
      }
      
      this.submitting = true;
      
      try {
        // Submit order via fetch
        const response = await fetch('/trading/orders', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            symbol: this.symbol,
            side: this.side,
            price: this.form.price,
            amount: this.form.amount
          })
        });
        
        // Parse response
        const data = await response.json();
        
        // Check if request was successful
        if (response.ok && (data.success || data.order)) {
          // Reset form
          this.form = { price: '', amount: '' };
          this.$emit('order-placed', data.order);
          
          // Reload page to show updated data
          window.location.reload();
        } else {
          // Handle error response
          this.errorMessage = data.message || 'Failed to place order';
          this.submitting = false;
        }
      } catch (error) {
        console.error('Order submission error:', error);
        this.errorMessage = 'An error occurred while placing the order. Please try again.';
        this.submitting = false;
      }
    }
  }
};
</script>
