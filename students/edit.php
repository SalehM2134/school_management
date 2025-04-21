<?php
/**
 * Edit Student Page
 * Allows updating existing student information
 */

// Include configuration and database connection
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = 'Edit Student';

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid student ID';
    header('Location: index.php');
    exit;
}

$student_id = intval($_GET['id']);

// Get student data
try {
    $student_stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND status = 'active'");
    $student_stmt->execute([$student_id]);
    
    if ($student_stmt->rowCount() === 0) {
        $_SESSION['error_message'] = 'Student not found';
        header('Location: index.php');
        exit;
    }
    
    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Get all classes for dropdown
$classes_query = "SELECT * FROM classes ORDER BY name";
$classes_stmt = $conn->query($classes_query);
$classes = $classes_stmt->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $errors = [];
    
    // Required fields
    $required_fields = ['name', 'admission_no', 'class_id', 'gender', 'dob', 'contact'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Check if admission number already exists (excluding current student)
    if (!empty($_POST['admission_no'])) {
        $check_stmt = $conn->prepare("SELECT id FROM students WHERE admission_no = ? AND id != ? AND status = 'active'");
        $check_stmt->execute([$_POST['admission_no'], $student_id]);
        if ($check_stmt->rowCount() > 0) {
            $errors[] = 'Admission number already exists for another student';
        }
    }
    
    // If no errors, update student data
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE students SET 
                                    name = ?, 
                                    admission_no = ?, 
                                    class_id = ?, 
                                    gender = ?, 
                                    dob = ?, 
                                    contact = ?, 
                                    updated_at = NOW() 
                                    WHERE id = ?");
            
            $stmt->execute([
                $_POST['name'],
                $_POST['admission_no'],
                $_POST['class_id'],
                $_POST['gender'],
                $_POST['dob'],
                $_POST['contact'],
                $student_id
            ]);
            
            $_SESSION['success_message'] = 'Student updated successfully';
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/student.css">
</head>
<body>
    <nav class="nav">
        <a href="<?php echo SITE_URL; ?>/dashboard.php" class="logo">
            <i class="fas fa-school"></i> <?php echo SITE_NAME; ?>
        </a>
        <button class="nav-toggle" id="navToggle">
            <i class="fas fa-bars"></i>
        </button>
        <ul class="nav-list" id="navList">
            <li><a href="<?php echo SITE_URL; ?>/dashboard.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>
            <li><a href="<?php echo SITE_URL; ?>/students/index.php" class="nav-item active">
                <i class="fas fa-user-graduate"></i> Students
            </a></li>
            <li><a href="<?php echo SITE_URL; ?>/classes/index.php" class="nav-item">
                <i class="fas fa-chalkboard"></i> Classes
            </a></li>
            <li><a href="<?php echo SITE_URL; ?>/attendance/index.php" class="nav-item">
                <i class="fas fa-clipboard-check"></i> Attendance
            </a></li>
            <li><a href="<?php echo SITE_URL; ?>/exams/index.php" class="nav-item">
                <i class="fas fa-file-alt"></i> Exams
            </a></li>
            <li><a href="<?php echo SITE_URL; ?>/auth/logout.php" class="nav-item nav-item-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a></li>
        </ul>
    </nav>
    
    <main class="container">
        <h1>Edit Student</h1>
        
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger fade-in">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="student-form">
            <form method="POST" action="edit.php?id=<?php echo $student_id; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">ID (Admission Number) <span class="text-danger">*</span></label>
                        <input type="text" name="admission_no" class="form-control" value="<?php echo htmlspecialchars($student['admission_no']); ?>" required>
                        <small class="text-muted">Unique identifier for the student</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo ($student['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($student['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($student['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($student['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($student['dob']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="tel" name="contact" class="form-control" value="<?php echo htmlspecialchars($student['contact']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Created Date</label>
                        <input type="text" class="form-control" value="<?php echo date('F d, Y', strtotime($student['created_at'])); ?>" readonly disabled>
                        <small class="text-muted">Date when student was added to the system</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Updated</label>
                        <input type="text" class="form-control" value="<?php echo $student['updated_at'] ? date('F d, Y', strtotime($student['updated_at'])) : 'Never'; ?>" readonly disabled>
                        <small class="text-muted">Date when student information was last updated</small>
                    </div>
                </div>
                
                <div class="form-buttons">
                    <a href="index.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">Update Student</button>
                </div>
            </form>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p class="text-center">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> | All Rights Reserved</p>
        </div>
    </footer>
    
    <script>
        // Toggle mobile navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navToggle = document.getElementById('navToggle');
            const navList = document.getElementById('navList');
            
            if (navToggle) {
                navToggle.addEventListener('click', function() {
                    navList.classList.toggle('active');
                });
            }
            
            // Close navigation when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.matches('#navToggle') && !e.target.matches('.fa-bars') && !navList.contains(e.target)) {
                    if (navList.classList.contains('active')) {
                        navList.classList.remove('active');
                    }
                }
            });
        });
    </script>
</body>
</html>