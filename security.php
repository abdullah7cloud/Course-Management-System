<?php
/**
 * ENHANCED SECURITY.PHP
 * Comprehensive security functions for input validation and sanitization
 */

/**
 * Sanitize input data for HTML output
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic international format)
 */
function validate_phone($phone) {
    return preg_match('/^\+?[\d\s\-\(\)]{10,}$/', $phone);
}

/**
 * Log security events
 */
function log_security_event($event, $user_id = null) {
    $log_message = date('Y-m-d H:i:s') . " | User: " . ($user_id ?? ($_SESSION['user_id'] ?? 'Unknown')) . " | Event: " . $event;
    error_log($log_message, 3, __DIR__ . "/../logs/security.log");
}

/**
 * Rate limiting check
 */
function check_rate_limit($action, $limit = 5, $timeframe = 60) {
    $key = "rate_limit_{$action}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    $rate_data = $_SESSION[$key];
    
    if (time() - $rate_data['time'] > $timeframe) {
        $_SESSION[$key] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    if ($rate_data['count'] >= $limit) {
        log_security_event("Rate limit exceeded for: $action");
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}
?>