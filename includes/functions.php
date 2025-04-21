<?php
/**
 * Utility Functions
 * 
 * This file contains helper functions used throughout the application
 */

/**
 * Sanitize input data
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has admin role
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

/**
 * Format date to readable format
 * 
 * @param string $date Date string
 * @param string $format Format string
 * @return string Formatted date
 */
function formatDate($date, $format = 'd M, Y') {
    return date($format, strtotime($date));
}

/**
 * Display flash message
 * 
 * @param string $name Message name
 * @return string HTML for flash message
 */
function flashMessage($name) {
    if (isset($_SESSION[$name])) {
        $message = $_SESSION[$name];
        unset($_SESSION[$name]);
        return $message;
    }
    return '';
}

/**
 * Set flash message
 * 
 * @param string $name Message name
 * @param string $message Message content
 * @param string $type Message type (success, danger, warning, info)
 * @return void
 */
function setFlashMessage($name, $message, $type = 'success') {
    $_SESSION[$name] = '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                            ' . $message . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
}

/**
 * Generate random string
 * 
 * @param int $length Length of string
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Upload file
 * 
 * @param array $file File from $_FILES
 * @param string $destination Destination folder
 * @param array $allowedTypes Allowed file types
 * @param int $maxSize Maximum file size in bytes
 * @return string|bool Filename if successful, false otherwise
 */
function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png'], $maxSize = MAX_UPLOAD_SIZE) {
    // Check if file was uploaded without errors
    if ($file['error'] == 0) {
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];
        
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Check file extension
        if (!in_array($fileExt, $allowedTypes)) {
            return false;
        }
        
        // Check file size
        if ($fileSize > $maxSize) {
            return false;
        }
        
        // Create unique filename
        $newFileName = generateRandomString() . '.' . $fileExt;
        $uploadPath = $destination . $newFileName;
        
        // Upload file
        if (move_uploaded_file($fileTmp, $uploadPath)) {
            return $newFileName;
        }
    }
    
    return false;
}

/**
 * Get active page for navigation highlighting
 * 
 * @return string Current page name
 */
function getActivePage() {
    $currentFile = basename($_SERVER['PHP_SELF']);
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    
    if ($currentFile == 'index.php' && $currentDir != 'school_management') {
        return $currentDir;
    } elseif ($currentFile == 'dashboard.php') {
        return 'dashboard';
    } else {
        return str_replace('.php', '', $currentFile);
    }
}
?>