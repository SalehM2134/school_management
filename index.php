<?php
/**
 * Index Page
 * 
 * This file redirects to the login page or dashboard
 */

// Include configuration
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to dashboard
    redirect(SITE_URL . '/dashboard.php');
} else {
    // Redirect to login page
    redirect(SITE_URL . '/auth/login.php');
}
?>