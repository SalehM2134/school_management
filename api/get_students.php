<?php
/**
 * API to get students by class ID
 * Used by the attendance report page for dynamic student selection
 */

// Include configuration and database connection
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if class ID is provided
if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    echo json_encode([]);
    exit;
}

$class_id = intval($_GET['class_id']);

// Get students for the selected class
try {
    $stmt = $conn->prepare("
        SELECT id, admission_no, name
        FROM students
        WHERE class_id = ? AND status = 'active'
        ORDER BY name
    ");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($students);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}