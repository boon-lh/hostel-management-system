<?php
// Header include file for all dashboards
// Pass $pageTitle variable before including this file
if (!isset($pageTitle)) {
    $pageTitle = "MMU Hostel Management System";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Common CSS -->
    <link rel="stylesheet" href="<?php 
    // Determine correct path based on the current directory
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    echo $current_dir === 'includes' ? '../css/style.css' : '../shared/css/style.css'; 
    ?>">
      <!-- Role-specific CSS, should be defined in the including file -->
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <?php
            // Handle relative paths for CSS
            $cssPath = $css;
            if (strpos($css, 'http') !== 0) { // If not an absolute URL
                $base = dirname($_SERVER['SCRIPT_NAME']);
                if (substr($base, -1) !== '/') {
                    $base .= '/';
                }
                $cssPath = $base . $css;
            }
            ?>
            <link rel="stylesheet" href="<?php echo $cssPath; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
      <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">    <!-- Additional JavaScript, should be defined in the including file -->
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <?php 
            // If it's an absolute URL or starts with http, use as is
            if (strpos($js, 'http') === 0) {
                $jsPath = $js;
            } else {
                // For relative paths, we need to determine the absolute path from web root
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
</head>
<body>
    <div class="dashboard-container">