/**
 * Format a number as currency
 * @param {number} value - The value to format
 * @param {number} decimals - Number of decimal places
 * @returns {string} Formatted currency string
 */
export function formatCurrency(value, decimals = 2) {
    if (value === null || value === undefined || isNaN(value)) {
        return '0.00';
    }
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(value);
}

/**
 * Format a number with specified decimal places
 * @param {number} value - The value to format
 * @param {number} decimals - Number of decimal places
 * @returns {string} Formatted number string
 */
export function formatNumber(value, decimals = 2) {
    if (value === null || value === undefined || isNaN(value)) {
        return '0';
    }
    return parseFloat(value).toFixed(decimals);
}

/**
 * Format a timestamp to readable date/time
 * @param {string|Date} timestamp - The timestamp to format
 * @returns {string} Formatted date string
 */
export function formatDate(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Format a timestamp to relative time (e.g., "2 hours ago")
 * @param {string|Date} timestamp - The timestamp to format
 * @returns {string} Relative time string
 */
export function formatRelativeTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffSecs < 60) return 'just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    return formatDate(timestamp);
}

/**
 * Format a percentage value
 * @param {number} value - The value to format (0-100)
 * @param {number} decimals - Number of decimal places
 * @returns {string} Formatted percentage string
 */
export function formatPercentage(value, decimals = 2) {
    if (value === null || value === undefined || isNaN(value)) {
        return '0%';
    }
    return `${formatNumber(value, decimals)}%`;
}

/**
 * Shorten large numbers (e.g., 1000 -> 1K, 1000000 -> 1M)
 * @param {number} value - The value to format
 * @returns {string} Shortened number string
 */
export function formatShortNumber(value) {
    if (value === null || value === undefined || isNaN(value)) {
        return '0';
    }
    
    const absValue = Math.abs(value);
    const sign = value < 0 ? '-' : '';
    
    if (absValue >= 1000000000) {
        return sign + formatNumber(absValue / 1000000000, 2) + 'B';
    }
    if (absValue >= 1000000) {
        return sign + formatNumber(absValue / 1000000, 2) + 'M';
    }
    if (absValue >= 1000) {
        return sign + formatNumber(absValue / 1000, 2) + 'K';
    }
    return sign + formatNumber(absValue, 2);
}
