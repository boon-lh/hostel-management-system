<?php
if (isset($_POST["change_password"])) {
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];
    $username = $_SESSION["user"];
    
    // Basic validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION["profile_message"] = "All password fields are required!";
        $_SESSION["profile_message_type"] = "error";
        header("Location: profile.php?reset=true");
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION["profile_message"] = "New password and confirmation do not match!";
        $_SESSION["profile_message_type"] = "error";
        header("Location: profile.php?reset=true");
        exit();
    }
    
    // Verify the current password
    $password_sql = "SELECT password FROM admins WHERE username = ?";
    $password_stmt = $conn->prepare($password_sql);
    $password_stmt->bind_param("s", $username);
    $password_stmt->execute();
    $password_result = $password_stmt->get_result();
    
    if ($password_result->num_rows > 0) {
        $user_data = $password_result->fetch_assoc();
        $stored_password = $user_data["password"];
        
        // Verify current password
        if (password_verify($current_password, $stored_password)) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the password
            $update_password_sql = "UPDATE admins SET password = ? WHERE username = ?";
            $update_password_stmt = $conn->prepare($update_password_sql);
            $update_password_stmt->bind_param("ss", $hashed_password, $username);
            
            if ($update_password_stmt->execute()) {
                $_SESSION["profile_message"] = "Password changed successfully!";
                $_SESSION["profile_message_type"] = "success";
                error_log("Password changed successfully for admin: $username");
            } else {
                $_SESSION["profile_message"] = "Error updating password: " . $conn->error;
                $_SESSION["profile_message_type"] = "error";
                error_log("Error updating password for admin $username: " . $conn->error);
            }
            
            $update_password_stmt->close();
        } else {
            $_SESSION["profile_message"] = "Current password is incorrect!";
            $_SESSION["profile_message_type"] = "error";
        }
    } else {
        $_SESSION["profile_message"] = "User not found!";
        $_SESSION["profile_message_type"] = "error";
    }
    
    $password_stmt->close();
    
    // Redirect after password change to avoid resubmission
    header("Location: profile.php?reset=true");
    exit();
}
