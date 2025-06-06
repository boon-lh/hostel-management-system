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
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
      <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Additional JavaScript, should be defined in the including file -->
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="dashboard-container">