<?php
require_once 'config/config.php';

// Set a page title to test the active state
$page_title = 'Test Navigation';

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Navigation Test Page</h1>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Testing Navigation Links</h5>
                </div>
                <div class="card-body">
                    <h6>Current Configuration:</h6>
                    <ul>
                        <li><strong>SITE_NAME:</strong> <?php echo SITE_NAME; ?></li>
                        <li><strong>SITE_URL:</strong> <?php echo SITE_URL; ?></li>
                        <li><strong>Current Page Title:</strong> <?php echo $page_title; ?></li>
                    </ul>
                    
                    <h6 class="mt-4">Test Links:</h6>
                    <div class="list-group">
                        <a href="<?php echo SITE_URL; ?>/dashboard.php" class="list-group-item list-group-item-action">
                            Dashboard Link
                        </a>
                        <a href="<?php echo SITE_URL; ?>/students/" class="list-group-item list-group-item-action">
                            Students Link
                        </a>
                        <a href="<?php echo SITE_URL; ?>/classes/" class="list-group-item list-group-item-action">
                            Classes Link
                        </a>
                        <a href="<?php echo SITE_URL; ?>/attendance/" class="list-group-item list-group-item-action">
                            Attendance Link
                        </a>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <p><strong>How to test:</strong></p>
                        <ol>
                            <li>Check that the sidebar appears correctly with all menu items</li>
                            <li>Verify that no menu items are highlighted as active (since we're on a test page)</li>
                            <li>Click each link in the sidebar to ensure they navigate to the correct URL</li>
                            <li>Check that the URL in the browser address bar includes your SITE_URL value</li>
                        </ol>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>