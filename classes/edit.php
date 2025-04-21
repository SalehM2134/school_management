<?php
/**
 * Edit Class Page
 */

// Include configuration and database connection
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = 'Edit Class';

// Check if class ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid class ID';
    header('Location: index.php');
    exit;
}

$class_id = intval($_GET['id']);

// Get class data
try {
    $class_stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
    $class_stmt->execute([$class_id]);
    
    if ($class_stmt->rowCount() === 0) {
        $_SESSION['error_message'] = 'Class not found';
        header('Location: index.php');
        exit;
    }
    
    $class = $class_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Get current student count
try {
    $student_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ? AND status = 'active'");
    $student_count_stmt->execute([$class_id]);
    $student_count = $student_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    $student_count = 0;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $errors = [];
    
    // Required fields
    $required_fields = ['name', 'capacity'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Validate capacity is a positive number
    if (!empty($_POST['capacity']) && (!is_numeric($_POST['capacity']) || $_POST['capacity'] <= 0)) {
        $errors[] = 'Capacity must be a positive number';
    }
    
    // Check if capacity is less than current student count
    if (!empty($_POST['capacity']) && $_POST['capacity'] < $student_count) {
        $errors[] = 'Capacity cannot be less than the current number of students (' . $student_count . ')';
    }
    
    // Check if class name already exists (excluding current class)
    if (!empty($_POST['name'])) {
        $check_stmt = $conn->prepare("SELECT id FROM classes WHERE name = ? AND id != ?");
        $check_stmt->execute([$_POST['name'], $class_id]);
        if ($check_stmt->rowCount() > 0) {
            $errors[] = 'Class name already exists';
        }
    }
	    // If no errors, update class data
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE classes SET 
                                    name = ?, 
                                    description = ?, 
                                    capacity = ?, 
                                    updated_at = NOW() 
                                    WHERE id = ?");
            
            $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? null,
                $_POST['capacity'],
                $class_id
            ]);
            
            $_SESSION['success_message'] = 'Class updated successfully';
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
</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Edit Class</h1>
            <div class="action-buttons">
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Classes
                </a>
            </div>
        </div>
        
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
        
        <div class="card">
            <div class="card-body">
                <form method="POST" action="edit.php?id=<?php echo $class_id; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Class Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($class['name']); ?>" required>
                            <small class="text-muted">Example: Class 1, Grade 10, Science Section, etc.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" class="form-control" value="<?php echo htmlspecialchars($class['capacity']); ?>" min="<?php echo $student_count; ?>" required>
                            <small class="text-muted">
                                Maximum number of students allowed in this class. 
                                Current students: <?php echo $student_count; ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($class['description'] ?? ''); ?></textarea>
                        <small class="text-muted">Optional: Add details about this class</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Created Date</label>
                            <input type="text" class="form-control" value="<?php echo date('F d, Y', strtotime($class['created_at'])); ?>" readonly disabled>
                            <small class="text-muted">Date when class was created</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Updated</label>
                            <input type="text" class="form-control" value="<?php echo $class['updated_at'] ? date('F d, Y', strtotime($class['updated_at'])) : 'Never'; ?>" readonly disabled>
                            <small class="text-muted">Date when class information was last updated</small>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <a href="index.php" class="btn-cancel">Cancel</a>
                        <button type="submit" class="btn-submit">Update Class</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include_once '../includes/footer.php'; ?>
    
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