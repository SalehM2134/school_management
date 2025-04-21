<?php
/**
 * Attendance Reports Page
 * Displays attendance reports with various filtering options
 */

// Include configuration and database connection
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = 'Attendance Reports';

// Initialize variables
$report_generated = false;
$report_data = [];
$summary = [
    'total_days' => 0,
    'present' => 0,
    'absent' => 0,
    'late' => 0
];

// Get all classes for the dropdown
try {
    $classes_query = "SELECT * FROM classes ORDER BY name";
    $classes_stmt = $conn->query($classes_query);
    $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
    $classes = [];
}

// Check if form is submitted
if (isset($_POST['generate_report'])) {
    $report_generated = true;
    
    // Calculate last month's date range
    $last_month = date('m') - 1;
    $year = date('Y');
    
    if ($last_month == 0) {
        $last_month = 12;
        $year = $year - 1;
    }
    
    $start_date = date('Y-m-d', strtotime("$year-$last_month-01"));
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // Generate monthly report
    try {
        $query = "
            SELECT 
                DATE_FORMAT(a.date, '%Y-%m') AS month,
                COUNT(DISTINCT a.date) AS total_days,
                COUNT(a.id) AS total_records,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) AS late_count,
                c.name AS class_name
            FROM attendance a
            JOIN classes c ON a.class_id = c.id
            WHERE a.date BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(a.date, '%Y-%m'), c.id, c.name
            ORDER BY month, c.name
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process results
        foreach ($results as $row) {
            $month = $row['month'];
            $total_days = $row['total_days'];
            $total_records = $row['total_records'];
            $present_count = $row['present_count'];
            $absent_count = $row['absent_count'];
            $late_count = $row['late_count'];
            $class_name = $row['class_name'];
            
            // Calculate attendance percentage
            $attendance_percentage = ($total_records > 0) ? round(($present_count / $total_records) * 100) : 0;
            
            $report_data[] = [
                'month' => $month,
                'month_name' => date('F Y', strtotime($month . '-01')),
                'class_name' => $class_name,
                'total_days' => $total_days,
                'present_count' => $present_count,
                'absent_count' => $absent_count,
                'late_count' => $late_count,
                'attendance_percentage' => $attendance_percentage
            ];
            
            // Update summary counts
            $summary['total_days'] = max($summary['total_days'], $total_days);
            $summary['present'] += $present_count;
            $summary['absent'] += $absent_count;
            $summary['late'] += $late_count;
        }
        
        // If no data found, create a message
        if (empty($report_data)) {
            $month_name = date('F Y', strtotime($start_date));
            $error_message = "No attendance records found for $month_name.";
        }
        
    } catch (PDOException $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

// Helper function to get attendance percentage badge class
function getAttendancePercentageBadgeClass($percentage) {
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

// Include header
include '../includes/header.php';

// Include navbar explicitly
include '../includes/navbar.php';
?>
<style>
    /* Container and Row Layout */
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
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding: 0 15px;
}

.col-md-9 {
    flex: 0 0 75%;
    max-width: 75%;
    padding: 0 15px;
}

/* Sidebar Styles */
.sidebar {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 20px;
    margin-bottom: 20px;
}

.sidebar h2 {
    font-size: 1.2rem;
    margin-bottom: 20px;
    color: #4361ee;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    margin-bottom: 8px;
}

.sidebar-menu li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border-radius: 6px;
    color: #343a40;
    text-decoration: none;
    transition: all 0.2s ease;
    font-weight: 500;
}

.sidebar-menu li a:hover {
    background-color: #f8f9fa;
    color: #4361ee;
}

.sidebar-menu li.active a {
    background-color: #4361ee;
    color: #ffffff;
}

/* Card Styles */
.card {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    overflow: hidden;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;
}

.card-header h2 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
    color: #343a40;
}

.card-body {
    padding: 20px;
}

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 15px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    text-decoration: none;
    gap: 8px;
}

.btn-primary {
    background-color: #4361ee;
    color: #ffffff;
}

.btn-primary:hover {
    background-color: #3a56d4;
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: #6c757d;
    color: #ffffff;
}

.btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
}

/* Table Styles */
.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th, .table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.table th {
    font-weight: 600;
    color: #343a40;
    background-color: #f8f9fa;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-state i {
    font-size: 3rem;
    color: #adb5bd;
    margin-bottom: 15px;
}

.empty-state h3 {
    margin-bottom: 10px;
    font-weight: 600;
    color: #343a40;
}

.empty-state p {
    color: #6c757d;
    max-width: 400px;
    margin: 0 auto;
}

/* Attendance Badge Styles */
.attendance-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.attendance-badge.excellent {
    background-color: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

.attendance-badge.good {
    background-color: rgba(52, 152, 219, 0.1);
    color: #3498db;
}

.attendance-badge.average {
    background-color: rgba(243, 156, 18, 0.1);
    color: #f39c12;
}

.attendance-badge.poor {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

/* Report Summary Styles */
.report-summary {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.report-summary h4 {
    margin-bottom: 15px;
    font-weight: 600;
    color: #343a40;
}

.summary-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.stat-item {
    flex: 1;
    min-width: 120px;
    padding: 15px;
    border-radius: 8px;
    background-color: #f8f9fa;
    text-align: center;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 5px;
    color: #343a40;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.stat-item.present .stat-value {
    color: #2ecc71;
}

.stat-item.absent .stat-value {
    color: #e74c3c;
}

.stat-item.late .stat-value {
    color: #f39c12;
}

.stat-item.percentage .stat-value {
    color: #3498db;
}

/* Alert Styles */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 6px;
}

.alert-danger {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.2);
}

/* Print Styles */
@media print {
    body {
        background-color: white;
        font-size: 12pt;
    }
    
    .container {
        width: 100%;
        max-width: 100%;
    }
    
    .sidebar, .navbar, .filter-form, .btn, footer {
        display: none !important;
    }
    
    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    .print-header {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .print-header h1 {
        font-size: 18pt;
    }
    
    .col-md-9 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>

<div class="container">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="sidebar">
                <h2><i class="fas fa-clipboard-check"></i> Attendance</h2>
                <ul class="sidebar-menu">
                    <li>
                        <a href="<?php echo SITE_URL; ?>/attendance/index.php">
                            <i class="fas fa-calendar-check"></i> Mark Attendance
                        </a>
                    </li>
                    <li class="active">
                        <a href="<?php echo SITE_URL; ?>/attendance/report.php">
                            <i class="fas fa-chart-bar"></i> Attendance Reports
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main content area -->
        <div class="col-md-9">
            <div class="content-header">
                <h1>Attendance Reports</h1>
            </div>
            
            <?php if (isset($error_message) && !empty($error_message)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>Monthly Attendance Report</h2>
                </div>
                <div class="card-body">
                    <p>Click the button below to generate the attendance report for last month.</p>
                    
                    <form method="POST" action="">
                        <button type="submit" name="generate_report" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Generate Monthly Report
                        </button>
                        
                        <?php if ($report_generated && !empty($report_data)): ?>
                        <button type="button" class="btn btn-secondary" onclick="printReport()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <?php if ($report_generated): ?>
                <?php if (!empty($report_data)): ?>
                <div class="card" id="reportContent">
                    <div class="card-header">
                        <h2>Monthly Attendance Summary</h2>
                        <div class="report-meta">
                            <div><strong>Period:</strong> <?php echo date('F Y', strtotime($report_data[0]['month'] . '-01')); ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>School Days</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Late</th>
                                        <th>Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                            <td><?php echo $row['total_days']; ?></td>
                                            <td><?php echo $row['present_count']; ?></td>
                                            <td><?php echo $row['absent_count']; ?></td>
                                            <td><?php echo $row['late_count']; ?></td>
                                            <td>
                                                <span class="attendance-badge <?php echo getAttendancePercentageBadgeClass($row['attendance_percentage']); ?>">
                                                    <?php echo $row['attendance_percentage']; ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="report-summary">
                            <h4>Summary</h4>
                            <div class="summary-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $summary['total_days']; ?></div>
                                    <div class="stat-label">Total Days</div>
                                </div>
                                <div class="stat-item present">
                                    <div class="stat-value"><?php echo $summary['present']; ?></div>
                                    <div class="stat-label">Present</div>
                                </div>
                                <div class="stat-item absent">
                                    <div class="stat-value"><?php echo $summary['absent']; ?></div>
                                    <div class="stat-label">Absent</div>
                                </div>
                                <div class="stat-item late">
                                    <div class="stat-value"><?php echo $summary['late']; ?></div>
                                    <div class="stat-label">Late</div>
                                </div>
                                <div class="stat-item percentage">
                                    <?php 
                                    $total_attendance = $summary['present'] + $summary['absent'] + $summary['late'];
                                    $attendance_percentage = ($total_attendance > 0) ? round(($summary['present'] / $total_attendance) * 100) : 0;
                                    ?>
                                    <div class="stat-value"><?php echo $attendance_percentage; ?>%</div>
                                    <div class="stat-label">Attendance Rate</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Records Found</h3>
                            <p>No attendance records found for last month. Please make sure attendance has been marked.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Generate a Report</h3>
                        <p>Click "Generate Monthly Report" to view attendance data for last month.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Print report function
    function printReport() {
        const printContents = document.getElementById('reportContent').innerHTML;
        const originalContents = document.body.innerHTML;
        
        document.body.innerHTML = `
            <div class="print-header">
                <h1>Monthly Attendance Report</h1>
                <p>${new Date().toLocaleDateString()}</p>
            </div>
            ${printContents}
        `;
        
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }
</script>

<?php include '../includes/footer.php'; ?>