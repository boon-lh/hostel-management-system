<?php
if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
    $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png"];
    $filename = $_FILES["profile_picture"]["name"];
    $filetype = $_FILES["profile_picture"]["type"];
    $filesize = $_FILES["profile_picture"]["size"];
    $username = $_SESSION["user"];
    
    // Verify file extension
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if (!array_key_exists($ext, $allowed)) {
        $_SESSION["profile_message"] = "Error: Please select a valid file format (JPG, JPEG, PNG).";
        $_SESSION["profile_message_type"] = "error";
    } else {
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) {
            $_SESSION["profile_message"] = "Error: File size is larger than the allowed limit (5MB).";
            $_SESSION["profile_message_type"] = "error";
        } else {
            // Verify MIME type
            if (in_array($filetype, $allowed)) {
                // Generate unique filename
                $new_filename = "admin_" . $username . "_" . time() . "." . $ext;
                $upload_dir = "../uploads/profile_pictures/";
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $absolute_upload_dir = realpath($upload_dir) . "/";
                
                // Move the file
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $absolute_upload_dir . $new_filename)) {
                    // Update database with new profile picture path
                    $profile_pic_sql = "UPDATE admins SET profile_pic = ? WHERE username = ?";
                    $profile_pic_stmt = $conn->prepare($profile_pic_sql);
                    $profile_pic_path = "uploads/profile_pictures/" . $new_filename; // Store the path relative to root
                    $profile_pic_stmt->bind_param("ss", $profile_pic_path, $username);
                    
                    if ($profile_pic_stmt->execute()) {
                        $_SESSION["profile_message"] = "Profile picture updated successfully!";
                        $_SESSION["profile_message_type"] = "success";
                        // Set the profile image in session with the full path
                        $_SESSION["profile_image"] = $profile_pic_path;
                    } else {
                        $_SESSION["profile_message"] = "Error updating profile picture in database: " . $conn->error;
                        $_SESSION["profile_message_type"] = "error";
                    }
                } else {
                    $_SESSION["profile_message"] = "Error uploading file.";
                    $_SESSION["profile_message_type"] = "error";
                }
            } else {
                $_SESSION["profile_message"] = "Error: There was a problem with the uploaded file.";
                $_SESSION["profile_message_type"] = "error";
            }
        }
    }
    
    // Redirect after profile picture upload to avoid resubmission
    header("Location: profile.php?reset=true");
    exit();
}
