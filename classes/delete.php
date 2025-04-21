<?php
/**
 * Delete Class
 * Handles class deletion with validation
 */

// Include configuration and database connection
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Check if class ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid class ID';
    header('Location: index.php');
    exit;
}

$class_id = intval($_GET['id']);

// Check if class exists
try {
    $check_stmt = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $check_stmt->execute([$class_id]);
    
    if ($check_stmt->rowCount() === 0) {
        $_SESSION['error_message'] = 'Class not found';
        header('Location: index.php');
        exit;
    }
    
    $class_name = $check_stmt->fetch(PDO::FETCH_ASSOC)['name'];
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Check if class has students
try {
    $student_check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ? AND status = 'active'");
    $student_check_stmt->execute([$class_id]);
    $student_count = $student_check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($student_count > 0) {
        $_SESSION['error_message'] = 'Cannot delete class "' . $class_name . '" because it has ' . $student_count . ' active students. Please reassign or remove these students first.';
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Delete class
try {
    $delete_stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
    $delete_stmt->execute([$class_id]);
    
    $_SESSION['success_message'] = 'Class "' . $class_name . '" has been deleted successfully';
    header('Location: index.php');
    exit;
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}