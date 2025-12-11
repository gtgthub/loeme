/**
 * Validate email format
 * @param {string} email - The email to validate
 * @returns {boolean} True if valid, false otherwise
 */
export function validateEmail(email) {
    if (!email) return false;
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate password strength
 * @param {string} password - The password to validate
 * @param {number} minLength - Minimum length (default: 8)
 * @returns {object} Object with isValid and message properties
 */
export function validatePassword(password, minLength = 8) {
    if (!password) {
        return { isValid: false, message: 'Password is required' };
    }
    
    if (password.length < minLength) {
        return { isValid: false, message: `Password must be at least ${minLength} characters` };
    }
    
    return { isValid: true, message: '' };
}

/**
 * Validate number value
 * @param {any} value - The value to validate
 * @param {number} min - Minimum value
 * @param {number} max - Maximum value (optional)
 * @returns {boolean} True if valid, false otherwise
 */
export function validateNumber(value, min = 0, max = null) {
    if (value === null || value === undefined || value === '') {
        return false;
    }
    
    const num = parseFloat(value);
    
    if (isNaN(num)) return false;
    if (num < min) return false;
    if (max !== null && num > max) return false;
    
    return true;
}

/**
 * Validate that value is a positive number
 * @param {any} value - The value to validate
 * @returns {boolean} True if valid positive number, false otherwise
 */
export function validatePositiveNumber(value) {
    return validateNumber(value, 0.00000001);
}

/**
 * Validate required field
 * @param {any} value - The value to validate
 * @returns {boolean} True if not empty, false otherwise
 */
export function validateRequired(value) {
    if (value === null || value === undefined) return false;
    if (typeof value === 'string') return value.trim().length > 0;
    if (typeof value === 'number') return !isNaN(value);
    return Boolean(value);
}

/**
 * Validate that two values match (for password confirmation)
 * @param {any} value1 - First value
 * @param {any} value2 - Second value
 * @returns {boolean} True if values match, false otherwise
 */
export function validateMatch(value1, value2) {
    return value1 === value2;
}

/**
 * Validate order price and amount
 * @param {number} price - Order price
 * @param {number} amount - Order amount
 * @returns {object} Object with isValid, errors object
 */
export function validateOrderData(price, amount) {
    const errors = {};
    let isValid = true;
    
    if (!validatePositiveNumber(price)) {
        errors.price = 'Price must be a positive number';
        isValid = false;
    }
    
    if (!validatePositiveNumber(amount)) {
        errors.amount = 'Amount must be a positive number';
        isValid = false;
    }
    
    return { isValid, errors };
}

/**
 * Validate balance is sufficient
 * @param {number} required - Required amount
 * @param {number} available - Available balance
 * @returns {boolean} True if sufficient, false otherwise
 */
export function validateSufficientBalance(required, available) {
    if (!validateNumber(required, 0) || !validateNumber(available, 0)) {
        return false;
    }
    return available >= required;
}
