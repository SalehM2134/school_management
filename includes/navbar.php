<?php
/**
 * Navigation bar include file
 * Contains the main navigation menu
 */

// Prevent direct access
if (!defined('SITE_URL')) {
    exit('Direct access not permitted');
}

// Determine current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_directory = basename(dirname($_SERVER['PHP_SELF']));

// Function to check if a menu item should be active
function isActive($page_name, $directory_name = '') {
    global $current_page, $current_directory;
    
    if (!empty($directory_name) && $current_directory == $directory_name) {
        return true;
    }
    
    if (is_array($page_name)) {
        return in_array($current_page, $page_name);
    }
    
    return $current_page == $page_name;
}
?>

<nav class="nav">
    <a href="<?php echo SITE_URL; ?>/dashboard.php" class="logo">
        <i class="fas fa-school"></i> <?php echo SITE_NAME; ?>
    </a>
    <button class="nav-toggle" id="navToggle">
        <i class="fas fa-bars"></i>
    </button>
    <ul class="nav-list" id="navList">
        <li><a href="<?php echo SITE_URL; ?>/dashboard.php" class="nav-item <?php echo isActive('dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a></li>
        <li><a href="<?php echo SITE_URL; ?>/students/index.php" class="nav-item <?php echo isActive(['index.php', 'add.php', 'edit.php', 'view.php'], 'students') ? 'active' : ''; ?>">
            <i class="fas fa-user-graduate"></i> Students
        </a></li>
        <li><a href="<?php echo SITE_URL; ?>/classes/index.php" class="nav-item <?php echo isActive(['index.php', 'add.php', 'edit.php'], 'classes') ? 'active' : ''; ?>">
            <i class="fas fa-chalkboard"></i> Classes
        </a></li>
        <li><a href="<?php echo SITE_URL; ?>/attendance/index.php" class="nav-item <?php echo isActive(['index.php', 'report.php'], 'attendance') ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i> Attendance
        </a></li>
        <li><a href="<?php echo SITE_URL; ?>/exams/index.php" class="nav-item <?php echo isActive(['index.php', 'add.php', 'edit.php', 'results.php'], 'exams') ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Exams
        </a></li>
        <li><a href="<?php echo SITE_URL; ?>/auth/logout.php" class="nav-item nav-item-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a></li>
    </ul>
</nav>