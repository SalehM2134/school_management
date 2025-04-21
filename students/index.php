<?php
/**
 * Student Management Page
 * 
 * Lists all students with search and filter functionality
 */

// Include configuration and database connection
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = 'Student Management';

// Initialize variables
$students = [];
$total_records = 0;
$total_pages = 1;
$stats = ['total' => 0, 'male_count' => 0, 'female_count' => 0];
$error_message = '';

try {
    // Handle search and filters
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    $class_filter = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
    $gender_filter = isset($_GET['gender']) ? sanitize($_GET['gender']) : '';

    // Pagination settings
    $records_per_page = 10;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $records_per_page;

    // Prepare query
    $query = "SELECT s.*, c.name as class_name FROM students s 
              LEFT JOIN classes c ON s.class_id = c.id 
              WHERE s.status = 'active'";

    $count_query = "SELECT COUNT(*) as total FROM students s 
                    WHERE s.status = 'active'";

    $params = [];

    if (!empty($search)) {
        $search_condition = " AND (s.name LIKE :search 
                            OR s.admission_no LIKE :search 
                            OR s.email LIKE :search
                            OR s.contact LIKE :search)";
        $query .= $search_condition;
        $count_query .= $search_condition;
        $params[':search'] = "%{$search}%";
    }

    if ($class_filter > 0) {
        $class_condition = " AND s.class_id = :class_id";
        $query .= $class_condition;
        $count_query .= $class_condition;
        $params[':class_id'] = $class_filter;
    }

    if (!empty($gender_filter)) {
        $gender_condition = " AND s.gender = :gender";
        $query .= $gender_condition;
        $count_query .= $gender_condition;
        $params[':gender'] = $gender_filter;
    }

    $query .= " ORDER BY s.name LIMIT :offset, :limit";

    // Prepare and execute the count query
    $count_stmt = $conn->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Prepare and execute the main query
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all classes for filter dropdown
    $classes_query = "SELECT * FROM classes ORDER BY name";
    $classes_stmt = $conn->query($classes_query);
    $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get gender stats
    $stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male_count,
                    SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female_count
                    FROM students 
                    WHERE status = 'active'";
    $stats_stmt = $conn->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
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

/* Add Button */
.add-button {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background-color: var(--primary);
    color: var(--white);
    border-radius: var(--radius-md);
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: var(--transition-fast);
}

.add-button i {
    margin-right: 0.5rem;
}

.add-button:hover {
    background-color: var(--primary-dark);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

/* Stats Bar */
.stats-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    flex: 1;
    min-width: 200px;
    background-color: var(--white);
    border-radius: var(--radius-md);
    padding: 1rem;
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition-fast);
}

.stat-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-3px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--dark);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--gray);
}

.male-icon {
    background-color: rgba(52, 152, 219, 0.1);
    color: #3498db;
}

.female-icon {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.total-icon {
    background-color: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

/* Search and Filter */
.search-filter-container {
    background-color: var(--white);
    border-radius: var(--radius-md);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
}

.search-filter-form {
    width: 100%;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
}

.search-box {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
}

.search-box input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--white-3);
    border-radius: var(--radius-md);
    font-size: 1rem;
    transition: var(--transition-fast);
}

.search-box input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
}

.filter-box {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-box select {
    padding: 0.75rem 1rem;
    border: 1px solid var(--white-3);
    border-radius: var(--radius-md);
    font-size: 1rem;
    min-width: 150px;
    background-color: var(--white);
    transition: var(--transition-fast);
}

.filter-box select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
}

.filter-box button, .btn {
    padding: 0.75rem 1.5rem;
    background-color: var(--primary);
    color: var(--white);
    border: none;
    border-radius: var(--radius-md);
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition-fast);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-box button:hover, .btn:hover {
    background-color: var(--primary-dark);
}

.filter-box .reset-btn {
    background-color: var(--gray);
}

.filter-box .reset-btn:hover {
    background-color: var(--gray-dark);
}

/* Table Styles */
.table-container {
    background-color: var(--white);
    border-radius: var(--radius-md);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    margin-bottom: 1.5rem;
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

table th, table td {
    padding: 1rem;
    border-bottom: 1px solid var(--white-3);
}

table th {
    background-color: var(--primary);
    color: var(--white);
    font-weight: 500;
    text-transform: none;
    letter-spacing: normal;
    font-size: 1rem;
}

table tr:last-child td {
    border-bottom: none;
}

table tr:hover {
    background-color: rgba(67, 97, 238, 0.05);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.action-buttons a {
    width: 35px;
    height: 35px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-md);
    color: var(--white);
    text-decoration: none;
    transition: var(--transition-fast);
}

.view-btn {
    background-color: var(--info);
}

.view-btn:hover {
    background-color: #2980b9;
}

.edit-btn {
    background-color: var(--warning);
}

.edit-btn:hover {
    background-color: #d35400;
}

.delete-btn {
    background-color: var(--danger);
}

.delete-btn:hover {
    background-color: var(--danger-dark);
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.pagination a, .pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-md);
    background-color: var(--white);
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    min-width: 40px;
    box-shadow: var(--shadow-sm);
    transition: var(--transition-fast);
}

.pagination a:hover {
    background-color: var(--primary);
    color: var(--white);
}

.pagination .active {
    background-color: var(--primary);
    color: var(--white);
}

.pagination .disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: var(--white-3);
    color: var(--gray);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    background-color: var(--white);
    border-radius: var(--radius-md);
    margin: 2rem 0;
    box-shadow: var(--shadow-sm);
}

.empty-state i {
    font-size: 4rem;
    color: var(--gray-light);
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--gray-dark);
}

.empty-state p {
    color: var(--gray);
    margin-bottom: 1.5rem;
}

/* Alert Messages */
.alert {
    padding: 1rem;
    border-radius: var(--radius-md);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: opacity 0.5s ease;
}

.alert i {
    font-size: 1.25rem;
    margin-top: 0.125rem;
}

.alert-success {
    background-color: rgba(46, 204, 113, 0.1);
    border-left: 4px solid var(--success);
    color: #27ae60;
}

.alert-danger {
    background-color: rgba(231, 76, 60, 0.1);
    border-left: 4px solid var(--danger);
    color: var(--danger);
}

.fade-in {
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Footer */
.footer {
    background-color: var(--white);
    padding: 1.5rem;
    text-align: center;
    margin-top: 2rem;
    box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.1);
}

.footer p {
    color: var(--gray);
    margin: 0;
}

/* Button Styles */
.btn-submit {
    background-color: var(--primary);
    color: var(--white);
    border: none;
    border-radius: var(--radius-md);
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition-fast);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-submit:hover {
    background-color: var(--primary-dark);
}

/* Responsive Styles */
@media (max-width: 992px) {
    .nav-toggle {
        display: block;
        margin-left: auto;
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
        margin-right: 0;
    }
    
    .nav-item-logout {
        margin-top: auto;
        margin-left: 0;
    }
    
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box, .filter-box {
        width: 100%;
    }
    
    .filter-box {
        flex-direction: column;
    }
    
    .filter-box select, .filter-box button, .filter-box .btn {
        width: 100%;
    }
    
    .stats-bar {
        flex-direction: column;
    }
    
    .stat-card {
        width: 100%;
    }
    
    .action-buttons {
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }
    
    h1 {
        font-size: 1.5rem;
    }
    
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    
    .pagination {
        gap: 0.25rem;
    }
    
    .pagination a, .pagination span {
        padding: 0.375rem 0.75rem;
        min-width: 35px;
    }
}

@media (max-width: 576px) {
    .logo {
        font-size: 1.25rem;
    }
    
    .add-button {
        width: 100%;
        justify-content: center;
    }
    
    .empty-state {
        padding: 2rem 1rem;
    }
    
    .empty-state i {
        font-size: 3rem;
    }
    
    .empty-state h3 {
        font-size: 1.25rem;
    }
}
    </style>
</head>
<body>
    <nav class="nav">
        <a href="<?php echo SITE_URL; ?>/dashboard.php" class="logo">
            <i class="fas fa-school"></i> <?php echo SITE_NAME; ?>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
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
        <h1>Student Management</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success fade-in" role="alert">
            <i class="fas fa-check-circle"></i>
            <div>
                <p><?php echo $_SESSION['success_message']; ?></p>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger fade-in" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <p><?php echo $_SESSION['error_message']; ?></p>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger fade-in" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <p><?php echo $error_message; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon total-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon male-icon">
                    <i class="fas fa-male"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['male_count']; ?></div>
                    <div class="stat-label">Male Students</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon female-icon">
                    <i class="fas fa-female"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['female_count']; ?></div>
                    <div class="stat-label">Female Students</div>
                </div>
            </div>
        </div>
        
        <a href="<?php echo SITE_URL; ?>/students/add.php" class="add-button">
            <i class="fas fa-plus"></i> Add New Student
        </a>
        
        <div class="search-filter-container">
            <form method="GET" action="index.php" class="search-filter-form">
                <div class="filter-row">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search by name, ID or contact" value="<?php echo htmlspecialchars($search); ?>" aria-label="Search students">
                    </div>
                    <div class="filter-box">
                        <select name="class_id" aria-label="Filter by class">
                            <option value="0">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo ($class_filter == $class['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="gender" aria-label="Filter by gender">
                            <option value="">All Genders</option>
                            <option value="male" <?php echo ($gender_filter == 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($gender_filter == 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($gender_filter == 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        
                        <button type="submit">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        
                        <?php if (!empty($search) || $class_filter > 0 || !empty($gender_filter)): ?>
                        <a href="index.php" class="btn reset-btn">
                            <i class="fas fa-times"></i> Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (count($students) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Class</th>
                            <th scope="col">Gender</th>
                            <th scope="col">Date of Birth</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['class_name'] ?? 'N/A'); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($student['gender'])); ?></td>
                            <td><?php echo !empty($student['dob']) ? date('d M Y', strtotime($student['dob'])) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($student['contact'] ?? 'N/A'); ?></td>
                            <td class="action-buttons">
                                <a href="view.php?id=<?php echo $student['id']; ?>" class="view-btn" title="View Student Details" aria-label="View student details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $student['id']; ?>" class="edit-btn" title="Edit Student" aria-label="Edit student">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $student['id']; ?>" class="delete-btn" title="Delete Student" aria-label="Delete student" 
                                   onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
                        <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo (!empty($search) ? '&search='.urlencode($search) : '').($class_filter > 0 ? '&class_id='.$class_filter : '').(!empty($gender_filter) ? '&gender='.urlencode($gender_filter) : ''); ?>" aria-label="First page">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?page=<?php echo $page-1; ?><?php echo (!empty($search) ? '&search='.urlencode($search) : '').($class_filter > 0 ? '&class_id='.$class_filter : '').(!empty($gender_filter) ? '&gender='.urlencode($gender_filter) : ''); ?>" aria-label="Previous page">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled" aria-hidden="true"><i class="fas fa-angle-double-left"></i></span>
                        <span class="disabled" aria-hidden="true"><i class="fas fa-angle-left"></i></span>
                    <?php endif; ?>
                    
                    <?php
                    // Calculate range of page numbers to display
                    $range = 2; // Display 2 pages before and after current page
                    $start_page = max(1, $page - $range);
                    $end_page = min($total_pages, $page + $range);
                    
                    // Always show first page
                    if ($start_page > 1) {
                        echo '<a href="?page=1'.(!empty($search) ? '&search='.urlencode($search) : '').($class_filter > 0 ? '&class_id='.$class_filter : '').(!empty($gender_filter) ? '&gender='.urlencode($gender_filter) : '').'">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="disabled" aria-hidden="true">...</span>';
                        }
                    }
                    
                    // Display page numbers
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo '<span class="active" aria-current="page">'.$i.'</span>';
                        } else {
                            echo '<a href="?page='.$i.(!empty($search) ? '&search='.urlencode($search) : '').($class_filter > 0 ? '&class_id='.$class_filter : '').(!empty($gender_filter) ? '&gender='.urlencode($gender_filter) : '').'">'.$i.'</a>';
                        }
                    }
                    
                    // Always show last page
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="disabled" aria-hidden="true">...</span>';
                        }
                        echo '<a href="?page='.$total_pages.(!empty($search) ? '&search='.urlencode($search) : '').($class_filter > 0 ? '&class_id='.$class_filter : '').(!empty($gender_filter) ? '&gender='.urlencode($gender_filter) : '').'">'.$total_pages.'</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?><?php echo (!empty($search) ? '&search='.urlencode($search) : '').($class_filter > 0 ? '&class_id='.$class_filter : '').(!empty($gender_filter) ? '&gender='.urlencode($gender_filter) : ''); ?>" aria-label="Next page">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?page=<?php echo $total_pages; ?><?php echo (!empty($search) ? '&search='.urlencode($search) : '').($class_filter > 0 ? '&class_id='.$class_filter : '').(!empty($gender_filter) ? '&gender='.urlencode($gender_filter) : ''); ?>" aria-label="Last page">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled" aria-hidden="true"><i class="fas fa-angle-right"></i></span>
                        <span class="disabled" aria-hidden="true"><i class="fas fa-angle-double-right"></i></span>
                    <?php endif; ?>
                </div>
            </nav>
            <?php endif; ?>
            
            <div class="text-center" style="margin-top: 1rem; color: var(--gray);">
                Showing <?php echo count($students); ?> of <?php echo $total_records; ?> students
            </div>
            
        <?php else: ?>
            <div class="empty-state" role="status">
                <i class="fas fa-user-graduate" aria-hidden="true"></i>
                <h3>No Students Found</h3>
                <?php if (!empty($search) || $class_filter > 0 || !empty($gender_filter)): ?>
                    <p>No students match your search criteria. Try adjusting your filters.</p>
                    <a href="index.php" class="btn-submit">Clear Filters</a>
                <?php else: ?>
                    <p>There are no students in the system yet. Add your first student to get started.</p>
                    <a href="add.php" class="btn-submit">Add New Student</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p class="text-center">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> | All Rights Reserved</p>
        </div>
    </footer>
    
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