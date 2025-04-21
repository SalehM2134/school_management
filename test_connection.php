<?php
// Include configuration
require_once 'config/config.php';

// Test database connection
try {
    // Try to get version info
    $stmt = $conn->query('SELECT VERSION() as version');
    $version = $stmt->fetch();
    
    echo '<h1>Database Connection Test</h1>';
    echo '<p style="color: green;">Connection successful!</p>';
    echo '<p>MySQL Version: ' . $version['version'] . '</p>';
    
    // Test query to count users
    $stmt = $conn->query('SELECT COUNT(*) as user_count FROM users');
    $result = $stmt->fetch();
    
    echo '<p>Number of users in database: ' . $result['user_count'] . '</p>';
    
} catch(PDOException $e) {
    echo '<h1>Database Connection Test</h1>';
    echo '<p style="color: red;">Connection failed: ' . $e->getMessage() . '</p>';
}
?>