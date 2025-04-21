<?php
/**
 * Logout Page
 * 
 * This file handles user logout
 */

// Include configuration
require_once '../config/config.php';
require_once '../includes/functions.php';

// Destroy session
session_unset();
session_destroy();

// Start new session for flash message
session_start();

// Set flash message
setFlashMessage('login_message', 'You have been successfully logged out.', 'success');

// Redirect to login page
redirect(SITE_URL . '/auth/login.php');
?>