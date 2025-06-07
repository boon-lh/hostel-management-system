<?php
/**
 * Admin Content Header
 * This file contains the header that appears at the top of each admin page content
 */

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

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/db_connection.php';
}
?>
<div class="header">
    <h1><?php echo isset($pageHeading) ? $pageHeading : 'Admin Dashboard'; ?></h1>
    <div class="user-info">
        <img src="<?php echo $profile_image; ?>" alt="Admin Profile">
        <span class="user-name"><?php echo $_SESSION["fullname"] ?? $_SESSION["user"]; ?></span>
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>