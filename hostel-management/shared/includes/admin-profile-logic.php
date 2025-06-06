<?php
// Handle form submission first
$message = "";
$messageType = "";

// Always fetch the latest data from the database first
$username = $_SESSION["user"];
$sql = "SELECT * FROM admins WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

$name = "";
$email = "";
$phone = "";
$office_number = "";
$profile_picture = "";
$admin = null;

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();    
    $name = $admin["name"] ?? "";
    $email = $admin["email"] ?? "";
    $phone = $admin["contact_no"] ?? "";
    $office_number = isset($admin["office_number"]) ? $admin["office_number"] : "";
    $profile_picture = isset($admin["profile_pic"]) ? $admin["profile_pic"] : "";
    
    // Update session with the name from database
    $_SESSION["fullname"] = $name;
}

// Close the statement
if ($stmt) {
    $stmt->close();
    $stmt = null;
}

// Handle profile picture upload
require_once "../shared/includes/profile-picture-handler.php";

// Handle password change
require_once "../shared/includes/password-handler.php";

// Handle profile update
require_once "../shared/includes/profile-update-handler.php";

// Check if there's a message in session and display it, then clear it
$message = "";
$messageType = "";
if (isset($_SESSION["profile_message"])) {
    $message = $_SESSION["profile_message"];
    $messageType = $_SESSION["profile_message_type"];
    
    // Clear the message from session after displaying it once
    unset($_SESSION["profile_message"]);
    unset($_SESSION["profile_message_type"]);
}
?>
