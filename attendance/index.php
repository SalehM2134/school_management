<?php
/**
 * Attendance Management Page
 * Allows marking attendance for a class on a specific date
 */

// Include configuration and database connection
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = 'Attendance Management';

// Initialize variables
$selected_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$current_user_id = $_SESSION['user_id'];

// Validate date format
if (!validateDate($selected_date)) {
    $selected_date = date('Y-m-d');
}

// Get all classes
try {
    $classes_query = "SELECT * FROM classes ORDER BY name";
    $classes_stmt = $conn->query($classes_query);
    $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
    $classes = [];
}

// Process form submission for marking attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $class_id = intval($_POST['class_id']);
    $attendance_date = $_POST['date'];
    $student_status = $_POST['status'] ?? [];
    $student_remarks = $_POST['remarks'] ?? [];
    
    // Validate inputs
    $errors = [];
    
    if (empty($class_id)) {
        $errors[] = 'Please select a class';
    }
    
    if (!validateDate($attendance_date)) {
        $errors[] = 'Invalid date format';
    }
    
    // Check if date is in the future
    if (strtotime($attendance_date) > strtotime(date('Y-m-d'))) {
        $errors[] = 'Cannot mark attendance for future dates';
    }
    
    // If no errors, process attendance
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // First, delete any existing attendance records for this class and date
            $delete_stmt = $conn->prepare("DELETE FROM attendance WHERE class_id = ? AND date = ?");
            $delete_stmt->execute([$class_id, $attendance_date]);
            
            // Insert new attendance records
            $insert_stmt = $conn->prepare("
                INSERT INTO attendance (student_id, class_id, date, status, remarks, marked_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            foreach ($student_status as $student_id => $status) {
                $remark = isset($student_remarks[$student_id]) ? $student_remarks[$student_id] : null;
                $insert_stmt->execute([
                    $student_id,
                    $class_id,
                    $attendance_date,
                    $status,
                    $remark,
                    $current_user_id
                ]);
            }
            
            // Commit transaction
            $conn->commit();
            
            setFlashMessage('attendance_message', 'Attendance marked successfully for ' . date('F d, Y', strtotime($attendance_date)), 'success');
            
            // Redirect to same page with same parameters to prevent form resubmission
            redirect(SITE_URL . "/attendance/index.php?class_id=$class_id&date=$attendance_date");
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get students for selected class
$students = [];
if ($selected_class > 0) {
    try {
        $students_stmt = $conn->prepare("
            SELECT id, admission_no, name, gender
            FROM students
            WHERE class_id = ? AND status = 'active'
            ORDER BY name
        ");
        $students_stmt->execute([$selected_class]);
        $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
    
    // Get existing attendance records for this class and date
    try {
        $attendance_stmt = $conn->prepare("
            SELECT student_id, status, remarks
            FROM attendance
            WHERE class_id = ? AND date = ?
        ");
        $attendance_stmt->execute([$selected_class, $selected_date]);
        $attendance_records = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to associative array for easier access
        $attendance_data = [];
        foreach ($attendance_records as $record) {
            $attendance_data[$record['student_id']] = [
                'status' => $record['status'],
                'remarks' => $record['remarks']
            ];
        }
    } catch (PDOException $e) {
        $error_message = 'Database error: ' . $e->getMessage();
        $attendance_data = [];
    }
}

// Helper function to validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
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

/* Form Styles */
.filter-form {
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #343a40;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 1rem;
    color: #343a40;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #4361ee;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
}

select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23343a40' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 16px 12px;
    padding-right: 40px;
}

input[type="date"].form-control {
    padding-right: 15px;
}

/* Responsive Styles */
@media (max-width: 992px) {
    .col-md-3, .col-md-6, .col-md-9 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .sidebar {
        margin-bottom: 20px;
    }
}

/* Content Header */
.content-header {
    margin-bottom: 20px;
}

.content-header h1 {
    font-size: 1.8rem;
    font-weight: 600;
    color: #343a40;
    margin: 0;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

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

.btn-sm {
    padding: 6px 10px;
    font-size: 0.875rem;
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

/* Form Control Small */
.form-control-sm {
    padding: 6px 10px;
    font-size: 0.875rem;
}

/* Margin and Padding Utilities */
.mt-3 {
    margin-top: 1rem;
}

.mb-3 {
    margin-bottom: 1rem;
}

/* Status Labels */
.status-label {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-label.present {
    background-color: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

.status-label.absent {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.status-label.late {
    background-color: rgba(243, 156, 18, 0.1);
    color: #f39c12;
}

/* Attendance Status */
.attendance-status {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.status-option {
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
}

/* Gender Indicator */
.gender-indicator {
    margin-left: 5px;
    font-size: 0.8rem;
}

.gender-indicator.male {
    color: #3498db;
}

.gender-indicator.female {
    color: #e74c3c;
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

</style>

<!-- Main content -->
<div class="container">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="sidebar">
                <h2><i class="fas fa-clipboard-check"></i> Attendance</h2>
                <ul class="sidebar-menu">
                    <li class="active">
                        <a href="<?php echo SITE_URL; ?>/attendance/index.php">
                            <i class="fas fa-calendar-check"></i> Mark Attendance
                        </a>
                    </li>
                    <li>
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
                <h1>Attendance Management</h1>
            </div>
            
            <?php echo flashMessage('attendance_message'); ?>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>Mark Attendance</h2>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="filter-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Select Class</label>
                                    <select name="class_id" class="form-control" required onchange="this.form.submit()">
                                        <option value="">-- Select Class --</option>
                                        <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo ($selected_class == $class['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Select Date</label>
                                    <input type="date" name="date" class="form-control" value="<?php echo $selected_date; ?>" max="<?php echo date('Y-m-d'); ?>" required onchange="this.form.submit()">
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <?php if ($selected_class > 0 && !empty($students)): ?>
                    <form method="POST" action="" id="attendanceForm">
                        <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                        <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                        
                        <div class="action-buttons mb-3">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="markAll('present')">
                                <i class="fas fa-check"></i> Mark All Present
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="markAll('absent')">
                                <i class="fas fa-times"></i> Mark All Absent
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="clearAll()">
                                <i class="fas fa-eraser"></i> Clear All
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>ID</th>
                                        <th>Student Name</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($student['name']); ?>
                                            <span class="gender-indicator <?php echo strtolower($student['gender']); ?>">
                                                <i class="fas fa-<?php echo ($student['gender'] == 'male') ? 'mars' : (($student['gender'] == 'female') ? 'venus' : 'genderless'); ?>"></i>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="attendance-status">
                                                <label class="status-option">
                                                    <input type="radio" name="status[<?php echo $student['id']; ?>]" value="present" 
                                                        <?php echo (isset($attendance_data[$student['id']]) && $attendance_data[$student['id']]['status'] == 'present') ? 'checked' : ''; ?>>
                                                    <span class="status-label present">Present</span>
                                                </label>
                                                <label class="status-option">
                                                    <input type="radio" name="status[<?php echo $student['id']; ?>]" value="absent" 
                                                        <?php echo (isset($attendance_data[$student['id']]) && $attendance_data[$student['id']]['status'] == 'absent') ? 'checked' : ''; ?>>
                                                    <span class="status-label absent">Absent</span>
                                                </label>
                                                <label class="status-option">
                                                    <input type="radio" name="status[<?php echo $student['id']; ?>]" value="late" 
                                                        <?php echo (isset($attendance_data[$student['id']]) && $attendance_data[$student['id']]['status'] == 'late') ? 'checked' : ''; ?>>
                                                    <span class="status-label late">Late</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="remarks[<?php echo $student['id']; ?>]" class="form-control form-control-sm" 
                                                placeholder="Optional remarks" 
                                                value="<?php echo isset($attendance_data[$student['id']]) ? htmlspecialchars($attendance_data[$student['id']]['remarks'] ?? '') : ''; ?>">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="form-buttons mt-3">
                            <button type="submit" name="mark_attendance" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Attendance
                            </button>
                        </div>
                    </form>
                    <?php elseif ($selected_class > 0 && empty($students)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-graduate"></i>
                        <h3>No students found</h3>
                        <p>No students found in this class. <a href="<?php echo SITE_URL; ?>/students/add.php">Add students</a> to the class first.</p>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>Select class and date</h3>
                        <p>Please select a class and date to mark attendance.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Mark all students with the same status
    function markAll(status) {
        const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
        radios.forEach(radio => {
            radio.checked = true;
        });
    }
    
    // Clear all selections
    function clearAll() {
        const radios = document.querySelectorAll('input[type="radio"]');
        radios.forEach(radio => {
            radio.checked = false;
        });
        
        const remarks = document.querySelectorAll('input[name^="remarks"]');
        remarks.forEach(input => {
            input.value = '';
        });
    }
    
    // Form validation before submit
    const attendanceForm = document.getElementById('attendanceForm');
    if (attendanceForm) {
        attendanceForm.addEventListener('submit', function(e) {
            const students = <?php echo json_encode(array_column($students ?? [], 'id')); ?>;
            let allMarked = true;
            
            students.forEach(studentId => {
                const statusRadios = document.querySelectorAll(`input[name="status[${studentId}]"]:checked`);
                if (statusRadios.length === 0) {
                    allMarked = false;
                }
            });
            
            if (!allMarked) {
                if (!confirm('Some students do not have attendance marked. Continue anyway?')) {
                    e.preventDefault();
                }
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>