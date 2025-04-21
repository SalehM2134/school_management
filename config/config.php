<?php
// Application configuration
define('SITE_NAME', 'School Management System');
define('SITE_URL', 'http://localhost/school_management');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/school_management/assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('UTC');

// Include database connection
require_once 'database.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>