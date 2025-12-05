<?php
/**
 * REQUIRE_LOGIN.PHP
 * 
 * Authentication Security Function
 * This file contains a reusable function to protect pages that require user authentication.
 * It checks if a user is logged in by verifying the session status.
 */

/**
 * require_login() Function
 * 
 * Purpose:
 * - To provide a security checkpoint for pages that require user authentication
 * - To prevent unauthorized access to protected pages
 * - To centralize authentication logic for easy maintenance
 * 
 * How it works:
 * 1. Checks if the 'logged_in' session variable exists and is set
 * 2. If user is not logged in, redirects to login page immediately
 * 3. If user is logged in, allows the script to continue execution
 * 
 * Security Benefits:
 * - Prevents access to sensitive pages without proper authentication
 * - Provides consistent security across all protected pages
 * - Easy to implement and maintain
 * 
 * Usage Example:
 * <?php
 * session_start();
 * require_once 'require_login.php';
 * require_login(); // This will check authentication
 * // Rest of your protected page code here...
 * ?>
 */

function require_login() {
    // Check if the user authentication session variable exists
    // $_SESSION['logged_in'] is typically set to true during successful login
    if (!isset($_SESSION['logged_in'])) {
        // User is NOT logged in - redirect to login page for authentication
        // Using relative path to login.php (one directory up from current location)
        header("Location: ../login.php");
        
        // Terminate script execution immediately after redirect
        // This prevents any protected content from being displayed or executed
        exit; // Critical security measure - stops further code execution
    }
    
    // If this point is reached, user IS authenticated
    // The protected page will continue to load normally
    // No return value needed as function serves as a security gate
}

// Note: This function assumes session_start() has been called previously
// For proper usage, always call session_start() before including this file
?>