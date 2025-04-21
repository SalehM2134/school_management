<?php
/**
 * Footer include file
 * Contains the footer section and closing body/html tags
 */

// Prevent direct access
if (!defined('SITE_URL')) {
    exit('Direct access not permitted');
}
?>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="fas fa-school"></i> <?php echo SITE_NAME; ?>
                </div>
                <div class="footer-info">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> | All Rights Reserved</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
    <?php if (isset($additional_js) && is_array($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?php echo SITE_URL; ?>/assets/js/<?php echo $js_file; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>