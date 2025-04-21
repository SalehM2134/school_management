<?php
/**
 * View Student Page
 * Displays detailed information about a specific student
 */

// Include configuration and database connection
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = 'Student Details';

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid student ID';
    header('Location: index.php');
    exit;
}

$student_id = intval($_GET['id']);

// Get student data with class information
try {
    $student_stmt = $conn->prepare("
        SELECT s.*, c.name as class_name 
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.id = ? AND s.status = 'active'
    ");
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

// Get attendance statistics
try {
    $attendance_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
        FROM attendance
        WHERE student_id = ?
    ");
    $attendance_stmt->execute([$student_id]);
    $attendance = $attendance_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate attendance percentage
    $attendance_percentage = 0;
    if ($attendance['total_days'] > 0) {
        $attendance_percentage = round(($attendance['present_days'] / $attendance['total_days']) * 100);
    }
} catch (PDOException $e) {
    // If there's an error, just set empty attendance data
    $attendance = [
        'total_days' => 0,
        'present_days' => 0,
        'absent_days' => 0,
        'late_days' => 0
    ];
    $attendance_percentage = 0;
}

// Get recent attendance records
try {
    $recent_attendance_stmt = $conn->prepare("
        SELECT date, status, remarks
        FROM attendance
        WHERE student_id = ?
        ORDER BY date DESC
        LIMIT 5
    ");
    $recent_attendance_stmt->execute([$student_id]);
    $recent_attendance = $recent_attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_attendance = [];
}

// Get exam results
try {
    $exams_stmt = $conn->prepare("
        SELECT e.name as exam_name, er.marks, er.grade, er.remarks, e.date
        FROM exam_results er
        JOIN exams e ON er.exam_id = e.id
        WHERE er.student_id = ?
        ORDER BY e.date DESC
        LIMIT 5
    ");
    $exams_stmt->execute([$student_id]);
    $exam_results = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $exam_results = [];
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
    <style>
        /* Additional styles for print functionality */
        @media print {
            .nav, .page-header, .action-buttons, .btn-link, .footer, .no-print {
                display: none !important;
            }
            
            body {
                background-color: white;
                font-size: 12pt;
                color: black;
            }
            
            .container {
                width: 100%;
                max-width: 100%;
                padding: 0;
                margin: 0;
            }
            
            .student-profile {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .print-header {
                text-align: center;
                margin-bottom: 20px;
                padding-top: 20px;
            }
            
            .print-header h1 {
                font-size: 24pt;
                margin-bottom: 5px;
            }
            
            .print-header p {
                font-size: 12pt;
                color: #666;
            }
            
            .profile-section {
                page-break-inside: avoid;
            }
        }
        
        /* Fix for the top right buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn-secondary, .btn-primary, .btn-info {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-primary {
            background-color: #4361ee;
            color: white;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-secondary:hover, .btn-primary:hover, .btn-info:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Fix for the View Full Attendance Record link */
        .btn-link {
            display: inline-block;
            margin-top: 15px;
            color: #4361ee;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-link:hover {
            color: #2541b2;
            text-decoration: underline;
        }
    </style>
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
        <div class="page-header">
            <h1>Student Details</h1>
            <div class="action-buttons">
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="edit.php?id=<?php echo $student_id; ?>" class="btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button onclick="printStudentProfile()" class="btn-info">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        
        <div class="student-profile" id="printableArea">
            <div class="print-header" style="display: none;">
                <h1><?php echo SITE_NAME; ?> - Student Profile</h1>
                <p>Printed on <?php echo date('F d, Y'); ?></p>
            </div>
            
            <div class="profile-header">
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($student['name']); ?></h2>
                    <p class="student-id">ID: <?php echo htmlspecialchars($student['admission_no']); ?></p>
                    <p class="student-class">
                        <i class="fas fa-chalkboard"></i> 
                        <?php echo htmlspecialchars($student['class_name'] ?? 'No Class Assigned'); ?>
                    </p>
                    <div class="attendance-badge <?php echo getAttendanceBadgeClass($attendance_percentage); ?>">
                        <i class="fas fa-calendar-check"></i> 
                        <?php echo $attendance_percentage; ?>% Attendance
                    </div>
                </div>
            </div>
            
            <div class="profile-sections">
                <div class="profile-section">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Admission Number</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['admission_no']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Class</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['class_name'] ?? 'Not Assigned'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Gender</span>
                            <span class="info-value"><?php echo htmlspecialchars(ucfirst($student['gender'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date of Birth</span>
                            <span class="info-value">
                                <?php echo !empty($student['dob']) ? date('F d, Y', strtotime($student['dob'])) : 'Not provided'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Age</span>
                            <span class="info-value">
                                <?php 
                                if (!empty($student['dob'])) {
                                    $dob = new DateTime($student['dob']);
                                    $now = new DateTime();
                                    $age = $now->diff($dob)->y;
                                    echo $age . ' years';
                                } else {
                                    echo 'Not available';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Contact Number</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['contact'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Registration Date</span>
                            <span class="info-value"><?php echo date('F d, Y', strtotime($student['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="profile-section">
                    <h3><i class="fas fa-clipboard-check"></i> Attendance Summary</h3>
                    <div class="attendance-summary">
                        <div class="attendance-stat">
                            <div class="stat-value"><?php echo $attendance['total_days']; ?></div>
                            <div class="stat-label">Total Days</div>
                        </div>
                        <div class="attendance-stat present">
                            <div class="stat-value"><?php echo $attendance['present_days']; ?></div>
                            <div class="stat-label">Present</div>
                        </div>
                        <div class="attendance-stat absent">
                            <div class="stat-value"><?php echo $attendance['absent_days']; ?></div>
                            <div class="stat-label">Absent</div>
                        </div>
                        <div class="attendance-stat late">
                            <div class="stat-value"><?php echo $attendance['late_days']; ?></div>
                            <div class="stat-label">Late</div>
                        </div>
                    </div>
                    
                    <?php if (!empty($recent_attendance)): ?>
                    <h4>Recent Attendance</h4>
                    <div class="recent-attendance">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attendance as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['remarks'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="../attendance/student.php?id=<?php echo $student_id; ?>" class="btn-link no-print">
                            View Full Attendance Record <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <?php else: ?>
                    <p class="no-data">No attendance records available.</p>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($exam_results)): ?>
                <div class="profile-section">
                    <h3><i class="fas fa-file-alt"></i> Recent Exam Results</h3>
                    <div class="exam-results">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Date</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exam_results as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($result['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($result['marks']); ?></td>
                                    <td>
                                        <span class="grade-badge grade-<?php echo strtolower($result['grade']); ?>">
                                            <?php echo htmlspecialchars($result['grade']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($result['remarks'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="../exams/student.php?id=<?php echo $student_id; ?>" class="btn-link no-print">
                            View All Exam Results <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
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
        
        // Print student profile function
        function printStudentProfile() {
            // Show the print header that's normally hidden
            const printHeader = document.querySelector('.print-header');
            printHeader.style.display = 'block';
            
            // Print the page
            window.print();
            
            // Hide the print header again after printing
            setTimeout(function() {
                printHeader.style.display = 'none';
            }, 100);
        }
    </script>
</body>
</html>

<?php
// Helper function to determine attendance badge class
function getAttendanceBadgeClass($percentage) {
    if ($percentage >= 90) {
        return 'excellent';
    } elseif ($percentage >= 75) {
        return 'good';
    } elseif ($percentage >= 60) {
        return 'average';
    } else {
        return 'poor';
    }
}
?>