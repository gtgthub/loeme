import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
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
                forceTLS: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                }
            });
            
            console.log('✓ Pusher initialized successfully');
            console.log('✓ Key:', pusherKey);
            console.log('✓ Cluster:', pusherCluster);
        }).catch(error => {
            console.warn('Pusher-js not available:', error.message);
        });
    }).catch(error => {
        console.warn('Laravel Echo not available:', error.message);
    });
} else {
    console.log('ℹ Pusher not configured. Real-time updates disabled. Auto-refresh will be used instead.');
}
