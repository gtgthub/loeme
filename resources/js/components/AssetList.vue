<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search assets..."
            class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-sm"
          />
          <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
      </div>
    </div>
    
    <div v-if="loading" class="p-6">
      <loading-spinner :visible="true" message="Loading assets..." />
    </div>
    
    <div v-else-if="filteredAssets.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
      </svg>
      <p>{{ searchQuery ? 'No assets found' : 'No assets yet' }}</p>
    </div>
    
    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
      <div
        v-for="asset in filteredAssets"
        :key="asset.id"
        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-bold text-sm"
                 :class="getAssetColor(asset.symbol)">
              {{ asset.symbol.substring(0, 2).toUpperCase() }}
            </div>
            <div>
              <div class="font-semibold text-gray-900 dark:text-white">
                {{ asset.symbol }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                Available: {{ formatNumber(availableAmount(asset), 3) }}
              </div>
            </div>
          </div>
          
          <div class="text-right">
            <div class="font-semibold text-gray-900 dark:text-white">
              {{ formatNumber(asset.amount, 3) }}
            </div>
            <div v-if="parseFloat(asset.locked_amount) > 0" class="text-sm text-yellow-600 dark:text-yellow-400">
              Locked: {{ formatNumber(asset.locked_amount, 3) }}
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="showViewAll && filteredAssets.length > visibleCount" class="p-4 border-t border-gray-200 dark:border-gray-700 text-center">
      <button
        @click="toggleViewAll"
        class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium text-sm"
      >
        {{ viewingAll ? 'Show Less' : `View All (${filteredAssets.length})` }}
      </button>
    </div>
  </div>
</template>

<script>
import { formatNumber } from '../utils/formatters';
import LoadingSpinner from './LoadingSpinner.vue';

export default {
  name: 'AssetList',
  components: {
    LoadingSpinner
  },
  props: {
    assets: {
      type: Array,
      default: () => []
    },
    title: {
      type: String,
      default: 'My Assets'
    },
    loading: {
      type: Boolean,
      default: false
    },
    showViewAll: {
      type: Boolean,
      default: true
    },
    initialVisibleCount: {
      type: Number,
      default: 5
    }
  },
  data() {
    return {
      searchQuery: '',
      viewingAll: false
    };
  },
  computed: {
    filteredAssets() {
      let filtered = this.assets;
      
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(asset =>
          asset.symbol.toLowerCase().includes(query)
        );
      }
      
      // Show only non-zero assets first
      filtered = filtered.filter(asset => parseFloat(asset.amount) > 0);
      
      // Sort by amount descending
      filtered.sort((a, b) => parseFloat(b.amount) - parseFloat(a.amount));
      
      if (!this.viewingAll && this.showViewAll) {
        return filtered.slice(0, this.visibleCount);
      }
      
      return filtered;
    },
    visibleCount() {
      return this.initialVisibleCount;
    }
  },
  methods: {
    formatNumber,
    availableAmount(asset) {
      return parseFloat(asset.amount) - parseFloat(asset.locked_amount);
    },
    getAssetColor(symbol) {
      const colors = [
        'from-blue-400 to-blue-600',
        'from-green-400 to-green-600',
        'from-purple-400 to-purple-600',
        'from-pink-400 to-pink-600',
        'from-yellow-400 to-yellow-600',
        'from-indigo-400 to-indigo-600',
        'from-red-400 to-red-600',
        'from-teal-400 to-teal-600'
      ];
      
      // Generate consistent color based on symbol
      const hash = symbol.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
      return colors[hash % colors.length];
    },
    toggleViewAll() {
      this.viewingAll = !this.viewingAll;
    }
  }
};
</script>
