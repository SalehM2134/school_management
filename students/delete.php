<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
// Your authentication check function should be here

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Student ID is required";
    header("Location: index.php");
    exit;
}

$student_id = intval($_GET['id']);

// Check if student exists
$check_query = "SELECT id FROM students WHERE id = $student_id";
$result = $conn->query($check_query);

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Student not found";
    header("Location: index.php");
    exit;
}

// Delete student
$query = "DELETE FROM students WHERE id = $student_id";

if ($conn->query($query)) {
    // Also delete related records (attendance, etc.)
    $conn->query("DELETE FROM attendance WHERE student_id = $student_id");
    
    $_SESSION['success_message'] = "Student deleted successfully";
} else {
    $_SESSION['error_message'] = "Error deleting student: " . $conn->error;
}

header("Location: index.php");
exit;