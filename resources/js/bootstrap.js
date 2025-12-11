import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// TEMPORARILY DISABLED PUSHER FOR TESTING
// This will help us identify if Pusher is causing the flash/disappear issue

console.log('ℹ️ Pusher temporarily disabled for testing');
console.log('ℹ️ Components will use auto-refresh instead');

// Uncomment below when you have real Pusher credentials:
/*
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;

if (pusherKey && pusherKey !== 'undefined' && pusherKey !== '' && pusherKey !== 'x') {
    import('laravel-echo').then((EchoModule) => {
        import('pusher-js').then((PusherModule) => {
            window.Pusher = PusherModule.default;

            window.Echo = new EchoModule.default({
                broadcaster: 'pusher',
                key: pusherKey,
                cluster: pusherCluster || 'mt1',
                forceTLS: true
            });
            
            console.log('✓ Pusher initialized successfully');
        }).catch(error => {
            console.warn('Pusher-js not available:', error.message);
        });
    }).catch(error => {
        console.warn('Laravel Echo not available:', error.message);
    });
} else {
    console.log('ℹ Pusher not configured. Real-time updates disabled. Auto-refresh will be used instead.');
}
*/
