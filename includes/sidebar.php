<?php
/**
 * Sidebar include file
 * Contains the sidebar navigation for dashboard
 */

// Prevent direct access
if (!defined('SITE_URL')) {
    exit('Direct access not permitted');
}

// Determine current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_directory = basename(dirname($_SERVER['PHP_SELF']));

// Function to check if a menu item should be active
function isSidebarActive($page_name, $directory_name = '') {
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

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <h3><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></h3>
                <p><?php echo ucfirst($_SESSION['user_role'] ?? 'admin'); ?></p>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="<?php echo isSidebarActive('dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Academic</span>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/students/index.php" class="<?php echo isSidebarActive(['index.php', 'add.php', 'edit.php', 'view.php'], 'students') ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i> Students
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/classes/index.php" class="<?php echo isSidebarActive(['index.php', 'add.php', 'edit.php'], 'classes') ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard"></i> Classes
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/attendance/index.php" class="<?php echo isSidebarActive(['index.php', 'report.php'], 'attendance') ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i> Attendance
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/exams/index.php" class="<?php echo isSidebarActive(['index.php', 'add.php', 'edit.php', 'results.php'], 'exams') ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i> Exams
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Administration</span>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/users/index.php" class="<?php echo isSidebarActive(['index.php', 'add.php', 'edit.php'], 'users') ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i> Users
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/settings.php" class="<?php echo isSidebarActive('settings.php') ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Account</span>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/profile.php" class="<?php echo isSidebarActive('profile.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> My Profile
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>