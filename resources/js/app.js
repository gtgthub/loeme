import './bootstrap';
import Vue from 'vue'; // Will use full build via vite.config.js alias
import { initializeOrderNotifications } from './orderNotifications';

// Import Vue components
import BalanceCard from './components/BalanceCard.vue';
import AssetList from './components/AssetList.vue';
import OrderForm from './components/OrderForm.vue';
import OrderBook from './components/OrderBook.vue';
import TradeHistory from './components/TradeHistory.vue';
import OrderList from './components/OrderList.vue';
import OrderItem from './components/OrderItem.vue';
import LoadingSpinner from './components/LoadingSpinner.vue';
import ErrorMessage from './components/ErrorMessage.vue';

// Import new components for limit orders and overview
import LimitOrderForm from './components/LimitOrderForm.vue';
import WalletBalances from './components/WalletBalances.vue';
import OrderbookPanel from './components/OrderbookPanel.vue';
import OrdersTable from './components/OrdersTable.vue';
import OrdersOverview from './components/OrdersOverview.vue';

// Register components globally
Vue.component('balance-card', BalanceCard);
Vue.component('asset-list', AssetList);
Vue.component('order-form', OrderForm);
Vue.component('order-book', OrderBook);
Vue.component('trade-history', TradeHistory);
Vue.component('order-list', OrderList);
Vue.component('order-item', OrderItem);
Vue.component('loading-spinner', LoadingSpinner);
Vue.component('error-message', ErrorMessage);

// Register new components
Vue.component('limit-order-form', LimitOrderForm);
Vue.component('wallet-balances', WalletBalances);
Vue.component('orderbook-panel', OrderbookPanel);
Vue.component('orders-table', OrdersTable);
Vue.component('orders-overview', OrdersOverview);

// Make Vue available globally
window.Vue = Vue;

// Configure Vue to suppress production tip and add error handler
Vue.config.productionTip = false;
Vue.config.errorHandler = function (err, vm, info) {
    console.error('Vue Error:', err);
    console.error('Component:', vm);
    console.error('Info:', info);
};

// Initialize Vue instances on pages
document.addEventListener('DOMContentLoaded', () => {
    const appElements = document.querySelectorAll('[data-vue-app]');
    
    if (appElements.length === 0) {
        console.warn('No Vue app elements found with [data-vue-app]');
        return;
    }
    
    appElements.forEach((el, index) => {
        try {
            new Vue({
                el: el,
                mounted() {
                    console.log(`Vue app ${index + 1} mounted successfully`);
                }
            });
        } catch (error) {
            console.error(`Failed to mount Vue app ${index + 1}:`, error);
        }
    });
    
    console.log(`✓ Initialized ${appElements.length} Vue instance(s)`);
    
    // Initialize order match notifications if user is authenticated
    if (window.userId) {
        initializeOrderNotifications();
    }
});
