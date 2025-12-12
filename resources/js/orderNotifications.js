/**
 * Listen for OrderMatched events and show notifications
 */

export function initializeOrderNotifications() {
    // Wait for Echo to be available
    const checkEcho = setInterval(() => {
        if (window.Echo && window.userId) {
            clearInterval(checkEcho);
            setupOrderMatchedListener();
        }
    }, 100);

    // Stop checking after 5 seconds
    setTimeout(() => clearInterval(checkEcho), 5000);
}

function setupOrderMatchedListener() {
    console.log('📡 Setting up OrderMatched listener for user:', window.userId);

    window.Echo.private(`user.${window.userId}`)
        .listen('.OrderMatched', (event) => {
            console.log('🎉 OrderMatched event received:', event);
            
            showMatchNotification(event);
            
            // Trigger a custom event that components can listen to
            window.dispatchEvent(new CustomEvent('order-matched', { 
                detail: event 
            }));
            
            // Optionally refresh balances
            refreshUserData();
        });
}

function showMatchNotification(event) {
    const { symbol, price, amount, side, trade_id } = event;
    
    // Determine notification content based on side
    const sideText = side === 'buy' ? 'bought' : 'sold';
    const bgColor = side === 'buy' ? '#10b981' : '#ef4444'; // green-500 / red-500
    
    const message = `You ${sideText} ${amount} ${symbol.split('-')[0]} at $${price}`;
    
    // Create notification element with inline styles for better visibility
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: ${bgColor};
        color: white;
        padding: 20px 24px;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        z-index: 99999;
        min-width: 300px;
        max-width: 400px;
        font-family: system-ui, -apple-system, sans-serif;
        animation: slideInRight 0.3s ease-out;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                <svg style="width: 24px; height: 24px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <div style="font-weight: bold; font-size: 16px; margin-bottom: 4px;">🎉 Order Matched!</div>
                    <div style="font-size: 14px; opacity: 0.95;">${message}</div>
                    <div style="font-size: 12px; opacity: 0.8; margin-top: 4px;">Trade ID: #${trade_id}</div>
                </div>
            </div>
            <button 
                onclick="this.parentElement.parentElement.remove()" 
                style="background: none; border: none; color: white; cursor: pointer; padding: 4px; opacity: 0.8; flex-shrink: 0;"
                onmouseover="this.style.opacity='1'"
                onmouseout="this.style.opacity='0.8'"
            >
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Add countdown timer
    let secondsLeft = 30;
    const countdownEl = document.createElement('div');
    countdownEl.style.cssText = `
        font-size: 11px;
        opacity: 0.7;
        margin-top: 8px;
        text-align: right;
    `;
    countdownEl.textContent = `Dismissing in ${secondsLeft}s`;
    notification.querySelector('div').appendChild(countdownEl);
    
    const countdownInterval = setInterval(() => {
        secondsLeft--;
        if (secondsLeft > 0) {
            countdownEl.textContent = `Dismissing in ${secondsLeft}s`;
        } else {
            clearInterval(countdownInterval);
        }
    }, 1000);
    
    // Auto-remove after 30 seconds
    const removeTimeout = setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            notification.remove();
            clearInterval(countdownInterval);
        }, 300);
    }, 30000);
    
    // Clear timeout if manually closed
    notification.querySelector('button').addEventListener('click', () => {
        clearTimeout(removeTimeout);
        clearInterval(countdownInterval);
    });
    
    // Play notification sound if available
    playNotificationSound();
}

function playNotificationSound() {
    try {
        // Create a simple beep sound
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2);
    } catch (error) {
        console.warn('Could not play notification sound:', error);
    }
}

function refreshUserData() {
    // Trigger refresh on any component listening for balance updates
    window.dispatchEvent(new Event('refresh-balances'));
    
    // If there's a method to reload orders
    window.dispatchEvent(new Event('refresh-orders'));
}

// CSS animations - ensure they're added once
if (!document.getElementById('order-notification-styles')) {
    const style = document.createElement('style');
    style.id = 'order-notification-styles';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}
