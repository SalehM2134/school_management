<?php
/**
 * Dashboard Page
 * 
 * This is the main dashboard after login
 */

// Include configuration and database connection
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

// Set page title
$page_title = 'Dashboard';

// Get counts from database
try {
    // Count students
    $stmt = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $studentCount = $stmt->fetch()['count'];
    
    // Count classes
    $stmt = $conn->query("SELECT COUNT(*) as count FROM classes");
    $classCount = $stmt->fetch()['count'];
    
    // Count today's attendance
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                           SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                           SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                           FROM attendance 
                           WHERE date = ?");
    $stmt->execute([$today]);
    $attendanceStats = $stmt->fetch();
    $presentCount = $attendanceStats['present'] ?? 0;
    $absentCount = $attendanceStats['absent'] ?? 0;
    
    // Get recent students
    $stmt = $conn->query("SELECT s.*, c.name as class_name 
                         FROM students s 
                         LEFT JOIN classes c ON s.class_id = c.id 
                         WHERE s.status = 'active' 
                         ORDER BY s.created_at DESC 
                         LIMIT 5");
    $recentStudents = $stmt->fetchAll();
    
} catch (PDOException $e) {
    // Handle error
    $error = $e->getMessage();
}

// Include header
include 'includes/header.php';
?>

<style>
    /* Additional styles for dashboard layout */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }
    
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -15px;
    }
    
    .col-md-3 {
        flex: 0 0 25%;
        max-width: 25%;
        padding: 0 15px;
        margin-bottom: 20px;
    }
    
    .col-md-4 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
        padding: 0 15px;
        margin-bottom: 20px;
    }
    
    .col-md-8 {
        flex: 0 0 66.666667%;
        max-width: 66.666667%;
        padding: 0 15px;
        margin-bottom: 20px;
    }
    
    .mt-4 {
        margin-top: 2rem;
    }
    
    .mb-3 {
        margin-bottom: 1rem;
    }
    
    .w-100 {
        width: 100%;
    }
    
    .d-flex {
        display: flex;
    }
    
    .justify-content-between {
        justify-content: space-between;
    }
    
    .align-items-center {
        align-items: center;
    }
    
    .text-center {
        text-align: center;
    }
    
    .text-muted {
        color: var(--gray);
    }
    
    /* Dashboard card styles */
    .card {
        height: auto;
        margin-bottom: 0;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .card-footer {
        padding: 1rem 1.5rem;
    }
    
    .card-footer a {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 500;
        transition: var(--transition-fast);
    }
    
    .card-footer a:hover {
        opacity: 0.8;
    }
    
    .icon {
        background-color: rgba(67, 97, 238, 0.1);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .text-primary .icon {
        background-color: rgba(67, 97, 238, 0.1);
    }
    
    .text-success .icon {
        background-color: rgba(46, 204, 113, 0.1);
    }
    
    .text-warning .icon {
        background-color: rgba(243, 156, 18, 0.1);
    }
    
    .text-danger .icon {
        background-color: rgba(231, 76, 60, 0.1);
    }
    
    /* Button styles */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1rem;
        border-radius: var(--radius-md);
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition-fast);
        border: none;
        text-decoration: none;
        gap: 0.5rem;
    }
    
    .btn-primary {
        background-color: var(--primary);
        color: var(--white);
    }
    
    .btn-success {
        background-color: var(--success);
        color: var(--white);
    }
    
    .btn-warning {
        background-color: var(--warning);
        color: var(--white);
    }
    
    .btn-info {
        background-color: var(--info);
        color: var(--white);
    }
    
    .btn-danger {
        background-color: var(--danger);
        color: var(--white);
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
    
    /* Footer styles */
    .footer {
        background-color: var(--white);
        padding: 1.5rem 0;
        margin-top: 3rem;
        border-top: 1px solid var(--white-3);
    }
    
    /* Responsive navbar */
    .nav-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--primary);
        cursor: pointer;
    }
    
    /* Logout button style */
    .nav-item-logout {
        background-color: var(--danger);
        margin-left: auto;
    }
    
    .nav-item-logout:hover {
        background-color: #c0392b;
    }
    
    @media (max-width: 992px) {
        .nav-toggle {
            display: block;
        }
        
        .nav-list {
            position: fixed;
            top: 70px;
            left: -100%;
            width: 250px;
            height: calc(100vh - 70px);
            background-color: var(--white);
            flex-direction: column;
            padding: 1rem;
            box-shadow: var(--shadow-md);
            transition: left 0.3s ease;
            z-index: 1000;
            display: flex;
        }
        
        .nav-list.active {
            left: 0;
        }
        
        .nav-item {
            width: 100%;
            margin-bottom: 0.5rem;
        }
        
        .nav-item-logout {
            margin-top: auto;
            margin-left: 0;
        }
        
        .col-md-3, .col-md-4, .col-md-8 {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }
</style>

<?php include 'includes/navbar.php'; ?>

<main class="container">
    <h1 class="fade-in">Dashboard</h1>
    
    <?php echo flashMessage('dashboard_message'); ?>
    
    <div class="row fade-in">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Students</h6>
                            <h2><?php echo $studentCount; ?></h2>
                        </div>
                        <div class="icon text-primary">
                            <i class="fas fa-user-graduate fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo SITE_URL; ?>/students/index.php" class="text-primary">
                        View Students <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Classes</h6>
                            <h2><?php echo $classCount; ?></h2>
                        </div>
                        <div class="icon text-success">
                            <i class="fas fa-chalkboard fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo SITE_URL; ?>/classes/index.php" class="text-success">
                        View Classes <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Present Today</h6>
                            <h2><?php echo $presentCount; ?></h2>
                        </div>
                        <div class="icon text-warning">
                            <i class="fas fa-clipboard-check fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo SITE_URL; ?>/attendance/index.php" class="text-warning">
                        Mark Attendance <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Absent Today</h6>
                            <h2><?php echo $absentCount; ?></h2>
                        </div>
                        <div class="icon text-danger">
                            <i class="fas fa-user-times fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo SITE_URL; ?>/attendance/report.php" class="text-danger">
                        View Report <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4 fade-in">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Recently Added Students</h5>
                    <a href="<?php echo SITE_URL; ?>/students/index.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentStudents)): ?>
                                    <?php foreach ($recentStudents as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['class_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/students/view.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No students found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo SITE_URL; ?>/students/add.php" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-plus"></i> Add New Student
                    </a>
                    <a href="<?php echo SITE_URL; ?>/classes/add.php" class="btn btn-success w-100 mb-3">
                        <i class="fas fa-plus"></i> Add New Class
                    </a>
                    <a href="<?php echo SITE_URL; ?>/attendance/index.php" class="btn btn-warning w-100 mb-3">
                        <i class="fas fa-clipboard-check"></i> Take Attendance
                    </a>
                    <a href="<?php echo SITE_URL; ?>/attendance/report.php" class="btn btn-info w-100">
                        <i class="fas fa-chart-bar"></i> Attendance Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
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
    
    // Toggle mobile navigation
    document.addEventListener('DOMContentLoaded', function() {
        const navToggle = document.getElementById('navToggle');
        const navList = document.getElementById('navList');
        
        if (navToggle) {
            navToggle.addEventListener('click', function() {
                navList.classList.toggle('active');
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.matches('.dropdown-toggle')) {
                const dropdowns = document.querySelectorAll('.dropdown-menu');
                dropdowns.forEach(function(dropdown) {
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                });
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>