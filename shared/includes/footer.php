    </div> <!-- End of dashboard-container -->
<?php if (!isset($additionalJS)) { $additionalJS = []; } ?>
<?php $additionalJS = array_merge(["../shared/js/script.js"], $additionalJS); ?>

<!-- Additional JavaScript -->
<?php if (isset($additionalJS) && is_array($additionalJS)): ?>
    <?php foreach ($additionalJS as $js): ?>
        <?php 
        // If it's an absolute URL or starts with http, use as is
        if (strpos($js, 'http') === 0) {
            $jsPath = $js;
        } else {
            // For relative paths, determine the absolute path from web root
            $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $base_dir = dirname($request_path);
            if ($base_dir === '\\' || $base_dir === '/') {
                $base_dir = '';
            }
            if (substr($js, 0, 3) === '../') {
                // If path starts with ../, remove the current directory from base_dir
                $base_dir = dirname($base_dir);
                $js = substr($js, 3);
            }
            $jsPath = $base_dir . '/' . $js;
            // Ensure there's only one forward slash between parts
            $jsPath = preg_replace('#/+#', '/', $jsPath);
        }
        ?>
        <script src="<?php echo $jsPath; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Common functionality for menu items -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menu item active state
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            menuItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>
</body>
</html>