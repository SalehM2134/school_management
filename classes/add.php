<?php
/**
 * Add New Class Page
 */

// Include configuration and database connection
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = 'Add New Class';

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
    
    // Check if class name already exists
    if (!empty($_POST['name'])) {
        $check_stmt = $conn->prepare("SELECT id FROM classes WHERE name = ?");
        $check_stmt->execute([$_POST['name']]);
        if ($check_stmt->rowCount() > 0) {
            $errors[] = 'Class name already exists';
        }
    }
    
    // If no errors, insert class data
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO classes (name, description, capacity, created_at) 
                                   VALUES (?, ?, ?, NOW())");
            
            $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? null,
                $_POST['capacity']
            ]);
            
            $_SESSION['success_message'] = 'Class added successfully';
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
            <h1><i class="fas fa-plus-circle"></i> Add New Class</h1>
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
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Class Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            <small class="text-muted">Example: Class 1, Grade 10, Science Section, etc.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" class="form-control" value="<?php echo isset($_POST['capacity']) ? htmlspecialchars($_POST['capacity']) : '30'; ?>" min="1" required>
                            <small class="text-muted">Maximum number of students allowed in this class</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <small class="text-muted">Optional: Add details about this class</small>
                    </div>
                    
                    <div class="form-buttons">
                        <a href="index.php" class="btn-cancel">Cancel</a>
                        <button type="submit" class="btn-submit">Add Class</button>
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