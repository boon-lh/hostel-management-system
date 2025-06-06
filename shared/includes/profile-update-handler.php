<?php
if (isset($_POST["update_profile"])) {
    // Sanitize input data
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["contact_no"]);
    $office_number = trim($_POST["office_number"]);
    $username = $_SESSION["user"];
    
    // Basic validation
    $is_valid = true;
    
    if(empty($name)) {
        $_SESSION["profile_message"] = "Name cannot be empty";
        $_SESSION["profile_message_type"] = "error";
        $is_valid = false;
    }
    
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["profile_message"] = "Please provide a valid email address";
        $_SESSION["profile_message_type"] = "error";
        $is_valid = false;
    }
    
    if(empty($phone)) {
        $_SESSION["profile_message"] = "Phone number cannot be empty";
        $_SESSION["profile_message_type"] = "error";
        $is_valid = false;
    }
    
    if(!$is_valid) {
        header("Location: profile.php");
        exit();
    }
    
    // First check if the email already exists for another user
    $email_check_sql = "SELECT * FROM admins WHERE email = ? AND username != ?";
    $email_check_stmt = $conn->prepare($email_check_sql);
    $email_check_stmt->bind_param("ss", $email, $username);
    $email_check_stmt->execute();
    $email_check_result = $email_check_stmt->get_result();
    
    if ($email_check_result->num_rows > 0) {
        $_SESSION["profile_message"] = "Email address already in use by another admin. Please use a different email.";
        $_SESSION["profile_message_type"] = "error";
    } else {
        // Now check if admin record exists
        $check_sql = "SELECT * FROM admins WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing record
            $update_sql = "UPDATE admins SET name = ?, email = ?, contact_no = ?, office_number = ? WHERE username = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssss", $name, $email, $phone, $office_number, $username);
            
            if ($update_stmt->execute()) {
                // Update session with the new name
                $_SESSION["fullname"] = $name;
                $_SESSION["profile_image"] = $admin["profile_pic"]; // Ensure profile image is consistent
                
                // Set flag to indicate successful submission
                $_SESSION["form_submitted"] = true;
                
                $_SESSION["profile_message"] = "Profile updated successfully! Name: $name, Email: $email, Phone: $phone";
                $_SESSION["profile_message_type"] = "success";
                
                error_log("Admin profile updated successfully for user: $username");
            } else {
                $_SESSION["profile_message"] = "Error updating profile: " . $conn->error;
                $_SESSION["profile_message_type"] = "error";
                error_log("Error updating admin profile for user $username: " . $conn->error);
            }
            
            $update_stmt->close();
        } else {
            // Insert new record since it doesn't exist
            $insert_sql = "INSERT INTO admins (name, email, contact_no, office_number, username) 
                          VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $name, $email, $phone, $office_number, $username);
            
            if ($insert_stmt->execute()) {
                // Update session with the new name
                $_SESSION["fullname"] = $name;
                
                // Set flag to indicate successful submission
                $_SESSION["form_submitted"] = true;
                
                $_SESSION["profile_message"] = "Profile created successfully! Name: $name, Email: $email";
                $_SESSION["profile_message_type"] = "success";
            } else {
                $_SESSION["profile_message"] = "Error creating profile: " . $conn->error;
                $_SESSION["profile_message_type"] = "error";
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
        
        // Redirect after form submission to prevent resubmission and apply the reset
        header("Location: profile.php?reset=true");
        exit();
    }
    
    $email_check_stmt->close();
}
