<?php
/**
 * Authentication Check
 * 
 * This file verifies that a user is logged in before accessing protected pages
 */

// Include configuration
require_once $_SERVER['DOCUMENT_ROOT'] . '/school_management/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/school_management/includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Store the requested URL for redirection after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Set flash message
    setFlashMessage('login_message', 'Please log in to access this page.', 'warning');
    
    // Redirect to login page
    redirect(SITE_URL . '/auth/login.php');
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    // Session expired
    session_unset();
    session_destroy();
    
    // Start new session for flash message
    session_start();
    
    // Set flash message
    setFlashMessage('login_message', 'Your session has expired. Please log in again.', 'info');
    
    // Redirect to login page
    redirect(SITE_URL . '/auth/login.php');
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>