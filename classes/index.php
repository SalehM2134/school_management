<?php
/**
 * Classes List Page
 * Shows all classes with options to add, edit, or view details
 */

// Include configuration and database connection
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = 'Manage Classes';

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($current_page - 1) * $records_per_page;

// Build query based on search
$params = [];
$where_clause = '';

if (!empty($search)) {
    $where_clause = " WHERE name LIKE ? OR description LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total number of classes
$count_query = "SELECT COUNT(*) as total FROM classes" . $where_clause;
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->execute($params);
} else {
    $count_stmt->execute();
}

$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get classes for current page
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM students WHERE class_id = c.id AND status = 'active') as student_count 
          FROM classes c" . $where_clause . " 
          ORDER BY c.name 
          LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for success or error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
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
    <style>

    .btn-primary {
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: var(--radius-md);
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition-fast);
        cursor: pointer;
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
    }

    /* Card styles */
    .card {
        background-color: var(--white);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .card-header {
        padding: 1rem 1.25rem;
        background-color: var(--light);
        border-bottom: 1px solid var(--white-3);
    }

    .card-body {
        padding: 1.25rem;
    }

    /* Search box */
    .search-box {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        position: relative;
        flex-wrap: wrap;
    }

    .search-box i {
        position: absolute;
        left: 0.75rem;
        color: var(--gray);
        pointer-events: none;
    }

    .search-box input {
        flex: 1;
        min-width: 200px;
        padding: 0.625rem 0.75rem 0.625rem 2.25rem;
        border: 1px solid var(--white-3);
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        transition: var(--transition-fast);
    }

    .search-box input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
    }

    .btn-search, .btn-clear {
        padding: 0.625rem 1rem;
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition-fast);
        cursor: pointer;
        border: none;
    }

    .btn-search {
        background-color: var(--primary);
        color: var(--white);
    }

    .btn-search:hover {
        background-color: var(--primary-dark);
    }

    .btn-clear {
        background-color: var(--secondary);
        color: var(--white);
    }

    .btn-clear:hover {
        background-color: var(--secondary-dark);
    }

    /* Table styles */
    .table-responsive {
        overflow-x: auto;
        margin-bottom: 1rem;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .data-table th, 
    .data-table td {
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid var(--white-3);
        vertical-align: middle;
    }

    .data-table th {
        background-color: var(--primary);
        color: var(--white);
        font-weight: 500;
        white-space: nowrap;
    }

    .data-table tr:last-child td {
        border-bottom: none;
    }

    .data-table tr:hover {
        background-color: rgba(67, 97, 238, 0.05);
    }

    .data-table em {
        color: var(--gray);
        font-style: italic;
    }

    /* Student count link */
    .student-count {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition-fast);
    }

    .student-count:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }

    .student-count i {
        font-size: 0.75rem;
    }

    /* Table actions */
    .table-actions {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
    }

    .action-icon {
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-md);
        color: var(--white);
        text-decoration: none;
        transition: var(--transition-fast);
    }

    .action-edit {
        background-color: var(--warning);
    }

    .action-edit:hover {
        background-color: #d35400;
    }

    .action-delete {
        background-color: var(--danger);
    }

    .action-delete:hover {
        background-color: var(--danger-dark);
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.375rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    .pagination a, 
    .pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        height: 2rem;
        padding: 0 0.5rem;
        border-radius: var(--radius-md);
        background-color: var(--white);
        border: 1px solid var(--white-3);
        color: var(--dark);
        text-decoration: none;
        font-size: 0.875rem;
        transition: var(--transition-fast);
    }

    .pagination a:hover {
        background-color: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }

    .pagination .current {
        background-color: var(--primary);
        color: var(--white);
        border-color: var(--primary);
        font-weight: 500;
    }

    /* No data state */
    .no-data {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--gray);
    }

    .no-data i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--gray-light);
    }

    .no-data p {
        margin-bottom: 0;
    }

    .no-data a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .no-data a:hover {
        text-decoration: underline;
    }

    /* Alert messages */
    .alert {
        padding: 1rem;
        border-radius: var(--radius-md);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .alert i {
        font-size: 1.25rem;
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

    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow: auto;
    }

    .modal-content {
        background-color: var(--white);
        margin: 10% auto;
        width: 90%;
        max-width: 500px;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-md);
        animation: modalFadeIn 0.3s;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .modal-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--white-3);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        color: var(--dark);
    }

    .close {
        color: var(--gray);
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: var(--dark);
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-body p {
        margin-top: 0;
        margin-bottom: 1rem;
    }

    .warning {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem;
        background-color: rgba(231, 76, 60, 0.1);
        border-radius: var(--radius-md);
        color: var(--danger);
    }

    .warning i {
        margin-top: 0.125rem;
    }

    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--white-3);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .btn-secondary {
        background-color: var(--secondary);
        color: var(--white);
        border: none;
        border-radius: var(--radius-md);
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: var(--transition-fast);
    }

    .btn-secondary:hover {
        background-color: var(--secondary-dark);
    }

    .btn-danger {
        background-color: var(--danger);
        color: var(--white);
        border: none;
        border-radius: var(--radius-md);
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: var(--transition-fast);
    }

    .btn-danger:hover {
        background-color: var(--danger-dark);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .search-box {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-box input {
            width: 100%;
        }
        
        .btn-search, .btn-clear {
            width: 100%;
        }
        
        .table-responsive {
            margin-left: -1.25rem;
            margin-right: -1.25rem;
            padding: 0 1.25rem;
            width: calc(100% + 2.5rem);
        }
        
        .data-table {
            min-width: 650px;
        }
        
        .modal-content {
            margin: 20% auto;
            width: 95%;
        }
    }
</style>

</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1><i class="fas fa-chalkboard"></i> Manage Classes</h1>
            <div class="action-buttons">
                <a href="add.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Add New Class
                </a>
            </div>
        </div>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success fade-in">
            <i class="fas fa-check-circle"></i>
            <div><?php echo $success_message; ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger fade-in">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo $error_message; ?></div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <form method="GET" action="" class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search by class name or description..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search">Search</button>
                    <?php if (!empty($search)): ?>
                    <a href="index.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="card-body">
                <?php if (count($classes) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Description</th>
                                <th>Capacity</th>
                                <th>Students</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['name']); ?></td>
                                <td><?php echo !empty($class['description']) ? htmlspecialchars($class['description']) : '<em>No description</em>'; ?></td>
                                <td><?php echo $class['capacity']; ?></td>
                                <td>
                                    <a href="../students/index.php?class_id=<?php echo $class['id']; ?>" class="student-count">
                                        <?php echo $class['student_count']; ?> / <?php echo $class['capacity']; ?>
                                        <i class="fas fa-user-graduate"></i>
                                    </a>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($class['created_at'])); ?></td>
                                <td class="table-actions">
                                    <a href="edit.php?id=<?php echo $class['id']; ?>" class="action-icon action-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="action-icon action-delete" 
                                       onclick="confirmDelete(<?php echo $class['id']; ?>, '<?php echo htmlspecialchars($class['name']); ?>')" 
                                       title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                    <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <?php if ($i == $current_page): ?>
                    <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chalkboard"></i>
                    <p>No classes found. <?php echo !empty($search) ? 'Try a different search term or ' : ''; ?><a href="add.php">add a new class</a>.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include_once '../includes/footer.php'; ?>
    
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the class "<span id="className"></span>"?</p>
                <p class="warning"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone and may affect student records.</p>
            </div>
            <div class="modal-footer">
                <button id="cancelDelete" class="btn-secondary">Cancel</button>
                <a id="confirmDelete" href="#" class="btn-danger">Delete</a>
            </div>
        </div>
    </div>
    <script>
        // Delete confirmation modal
        const modal = document.getElementById('deleteModal');
        const closeBtn = document.getElementsByClassName('close')[0];
        const cancelBtn = document.getElementById('cancelDelete');
        const className = document.getElementById('className');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        
        function confirmDelete(id, name) {
            modal.style.display = 'block';
            className.textContent = name;
            confirmDeleteBtn.href = 'delete.php?id=' + id;
        }
        
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        cancelBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
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