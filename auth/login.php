<?php
/**
 * Login Page
 * 
 * This file handles user authentication
 */

// Include configuration
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard.php');
}

// Initialize variables
$username = '';
$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // No need to sanitize password
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Prepare SQL statement
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            
            // Check if user exists
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Password is correct, create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login time
                    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Redirect to dashboard or requested page
                    $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : SITE_URL . '/dashboard.php';
                    unset($_SESSION['redirect_url']);
                    redirect($redirect);
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
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
    <nav class="nav">
        <a href="<?php echo SITE_URL; ?>" class="logo">
            <i class="fas fa-school"></i> <?php echo SITE_NAME; ?>
        </a>
        <ul class="nav-list">
            <li><a href="<?php echo SITE_URL; ?>" class="nav-item">
                <i class="fas fa-home"></i> Home
            </a></li>
        </ul>
    </nav>
    
    <main>
        <h1 class="fade-in">Welcome Back</h1>
        
        <form class="authorization-form fade-in" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php echo flashMessage('login_message'); ?>
            
            <div class="field">
                <label class="field-label">
                    <i class="fas fa-user"></i> Username
                </label>
                <input type="text" class="input-field" name="username" placeholder="Enter your username" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
            </div>
            
            <div class="field">
                <label class="field-label">
                    <i class="fas fa-lock"></i> Password
                </label>
                <div class="input-field-icon">
                    <input type="password" class="input-field" name="password" id="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye" id="togglePassword"></i>
                </div>
            </div>
            
            <button type="submit" class="form-submit">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
            
            <div class="form-footer">
                <p>School Management System &copy; <?php echo date('Y'); ?></p>
            </div>
        </form>
    </main>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle eye icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Remove alert after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>