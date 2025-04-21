<?php
/**
 * Database Connection
 * 
 * This file establishes a connection to the MySQL database
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_management_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
try {
    // Create PDO connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set character set
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<?php
