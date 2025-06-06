<?php
// Admin Header template for consistent header across all admin pages
// Pass $pageTitle variable before including this file
if (!isset($pageTitle)) {
    $pageTitle = "MMU Hostel Management System - Admin";
}

// Function to validate and format profile image path
function getValidProfileImagePath($imagePath) {
    // Default image path
    $defaultImage = "../uploads/profile_pictures/default_admin.png";
    
    if (empty($imagePath)) {
        return $defaultImage;
    }

    // If path starts with uploads/, prepend ../
    if (strpos($imagePath, 'uploads/') === 0) {
        $imagePath = "../" . $imagePath;
    }
    
    // Check if file exists
    if (file_exists($imagePath)) {
        return $imagePath;
    }
    
    // Try with added ../
    if (file_exists("../" . $imagePath)) {
        return "../" . $imagePath;
    }
    
    return $defaultImage;
}

// Initialize profile image path
$profile_image = "../uploads/profile_pictures/default_admin.png";

// First check session
if (isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])) {
    $profile_image = getValidProfileImagePath($_SESSION['profile_image']);
} else {
    // Get profile image from database if available
    if (isset($_SESSION["user"])) {
        $username = $_SESSION["user"];
        if (!isset($conn)) {
            require_once "../shared/includes/db_connection.php";
        }
        
        $img_query = "SELECT profile_pic FROM admins WHERE username = ?";
        $stmt = $conn->prepare($img_query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if (isset($admin['profile_pic']) && !empty($admin['profile_pic'])) {
                $profile_image = getValidProfileImagePath($admin['profile_pic']);
                // Update session for future use
                $_SESSION['profile_image'] = $admin['profile_pic'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Common CSS -->
    <link rel="stylesheet" href="../shared/css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/header.css">
    
    <!-- Bootstrap and Font Awesome -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <!-- Role-specific CSS -->
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header class="header">
        <h1><?php echo isset($pageHeading) ? $pageHeading : 'Admin Dashboard'; ?></h1>
        <div class="user-info">
            <img src="<?php echo $profile_image; ?>" alt="Admin Profile">
            <span class="user-name"><?php echo $_SESSION["fullname"] ?? $_SESSION["user"] ?? 'Admin User'; ?></span>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>
      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" id="alert-message">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="close-btn" onclick="closeAlert()">&times;</button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
      <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error" id="alert-message">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="close-btn" onclick="closeAlert()">&times;</button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <!-- JavaScript for header functionality -->
    <script src="js/header.js"></script>