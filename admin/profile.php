<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

require_once "../shared/includes/db_connection.php";
require_once "../shared/includes/profile-forms.php";

// Reset form data if returning to the page or after form submission
if (isset($_GET['reset']) && $_GET['reset'] == 'true') {
    // Clear any form data in session
    unset($_SESSION['form_data']);
    unset($_SESSION['form_submitted']);
    // Clear any potential leftover form fields
    $_SESSION['form_reset'] = true;
    
    // Remove the reset parameter from URL after processing
    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $redirect_url");
    exit();
}

// Handle profile update redirection
if (isset($_SESSION['form_submitted']) && $_SESSION['form_submitted'] === true) {
    // Clear the flag
    unset($_SESSION['form_submitted']);
    
    // Add a success message
    $_SESSION["profile_message"] = "Profile updated successfully!";
    $_SESSION["profile_message_type"] = "success";
    
    // Redirect to reset the page and prevent form resubmission
    header("Location: profile.php?reset=true");
    exit();
}

// Include the profile logic file that handles database operations and form processing
require_once "admin-profile-logic.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add a log to ensure we're getting POST data
    error_log("POST data received: " . json_encode($_POST));
    
    // Update profile information
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
                    
                    // Add detailed message for debugging
                    $_SESSION["profile_message"] = "Profile updated successfully! Name: $name, Email: $email, Phone: $phone";
                    $_SESSION["profile_message_type"] = "success";
                    
                    // Log successful update
                    error_log("Admin profile updated successfully for user: $username");
                } else {
                    $_SESSION["profile_message"] = "Error updating profile: " . $conn->error;
                    $_SESSION["profile_message_type"] = "error";
                    error_log("Error updating admin profile for user $username: " . $conn->error);
                }
                
                // Clear statement
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
                
                // Clear statement
                $insert_stmt->close();
            }
            
            $check_stmt->close();
              // Redirect after form submission to prevent resubmission and apply the reset
            header("Location: profile.php?reset=true");
            exit();
        }
        
        $email_check_stmt->close();
    }
    
    // Handle profile picture upload
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
      // Password change functionality has been removed
}

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
<?php
// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Admin Profile";
$additionalCSS = ["css/dashboard.css", "css/profile.css"];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once 'sidebar-admin.php';
?>

<!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Admin Profile</h1>
                <div class="user-info">                    <?php
                    // Get the profile image path from the database or use a default
                    if (isset($admin['profile_pic']) && !empty($admin['profile_pic'])) {
                        $profile_image = $admin['profile_pic'];
                        // Make sure the path is relative to admin directory
                        if (!file_exists($profile_image)) {
                            $profile_image = "../" . $profile_image;
                        }
                    } else {
                        // Use default profile image from admin/uploads/profile_pictures
                        $profile_image = "../admin/uploads/profile_pictures/default_admin.png";
                        // Fallback to other locations if not found
                        if (!file_exists($profile_image)) {
                            if (file_exists("../uploads/profile_pictures/default_admin.png")) {
                                $profile_image = "../uploads/profile_pictures/default_admin.png";
                            } else {
                                $profile_image = "https://via.placeholder.com/150";
                            }
                        }
                    }
                    
                    // Update the session variable to ensure consistency across pages
                    $_SESSION["profile_image"] = $profile_image;
                    ?>
                    <img src="<?php echo $profile_image; ?>" alt="Admin Profile">
                    <span class="user-name"><?php echo $_SESSION["fullname"] ?? $_SESSION["user"]; ?></span>                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>" id="alert-message">
                <?php echo $message; ?>
                <button type="button" class="close-btn" onclick="closeAlert()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php endif; ?>
            
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-picture-container">
                        <img src="<?php echo $profile_image; ?>" alt="Profile Picture" class="profile-picture">
                        <div class="profile-picture-edit" id="changeProfilePicture">
                            <i class="fas fa-camera"></i>
                        </div>
                        <form id="profilePictureForm" action="" method="post" enctype="multipart/form-data">
                            <input type="file" name="profile_picture" id="profilePictureInput" accept="image/*">
                            <input type="submit" value="Upload">
                        </form>
                    </div>
                    <div class="profile-info">
                    <h2><?php echo isset($admin['name']) ? $admin['name'] : $_SESSION["user"]; ?></h2>
                    <p><i class="fas fa-user-shield"></i> <?php echo ucfirst($_SESSION["role"]); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo isset($admin['email']) && !empty($admin['email']) ? $admin['email'] : 'No email set'; ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo isset($admin['contact_no']) && !empty($admin['contact_no']) ? $admin['contact_no'] : 'No phone set'; ?></p>
                    <p><i class="fas fa-building"></i> Office: <?php echo isset($admin['office_number']) && !empty($admin['office_number']) ? $admin['office_number'] : 'No office set'; ?></p>
                </div>
                </div>                <div class="profile-tabs">
                    <div class="profile-tab active" data-tab="edit-profile">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </div>
                </div>                <?php
                display_profile_edit_form($name, $email, $phone, $office_number, $_SESSION["user"]);
                ?>
            </div>
        </div>
    </div>    <script>
        document.addEventListener('DOMContentLoaded', function() {            // Tab functionality simplified - only one tab exists now
            
            // Add form validation for profile update
            const profileForm = document.querySelector('#edit-profile form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    const nameField = document.getElementById('name');
                    const emailField = document.getElementById('email');
                    const phoneField = document.getElementById('phone');
                    
                    let isValid = true;
                    
                    // Validate name
                    if (!nameField.value.trim()) {
                        alert('Please enter your full name');
                        nameField.focus();
                        isValid = false;
                        e.preventDefault();
                        return;
                    }
                    
                    // Validate email
                    if (!emailField.value.trim() || !emailField.value.includes('@')) {
                        alert('Please enter a valid email address');
                        emailField.focus();
                        isValid = false;
                        e.preventDefault();
                        return;
                    }
                    
                    // Validate phone
                    if (!phoneField.value.trim()) {
                        alert('Please enter your phone number');
                        phoneField.focus();
                        isValid = false;
                        e.preventDefault();
                        return;
                    }
                    
                    // Disable the submit button to prevent double submission
                    if (isValid) {
                        const submitButton = this.querySelector('button[type="submit"]');
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                    }
                });
            }
              // Password change validation has been removed
            
            // Profile picture upload
            const changeProfilePictureBtn = document.getElementById('changeProfilePicture');
            const profilePictureInput = document.getElementById('profilePictureInput');
            
            changeProfilePictureBtn.addEventListener('click', function() {
                profilePictureInput.click();
            });
            
            profilePictureInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    // Auto-submit the form when a file is selected
                    document.getElementById('profilePictureForm').submit();
                }
            });
            
            // Auto-hide alert messages after 5 seconds
            const alertMessage = document.getElementById('alert-message');
            if (alertMessage) {
                setTimeout(function() {
                    alertMessage.style.opacity = '0';
                    setTimeout(function() {
                        alertMessage.style.display = 'none';
                    }, 500);
                }, 5000);
            }
            
            // Function to close alert messages
            window.closeAlert = function() {
                const alert = document.getElementById('alert-message');
                if (alert) {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }
            }
            
            // Clear form fields on page refresh or reload
            <?php if (isset($_SESSION['form_reset']) && $_SESSION['form_reset']): ?>
            // Reset all form fields
            document.querySelectorAll('form').forEach(form => {
                form.reset();
            });
            <?php 
                // Clear the reset flag
                $_SESSION['form_reset'] = false;
            endif; 
            ?>
            
            // Add form submit event listeners to prevent double submission
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    // Disable submit buttons after click to prevent double submission
                    const submitButtons = this.querySelectorAll('button[type="submit"]');
                    submitButtons.forEach(button => {
                        button.disabled = true;
                    });
                });
            });
        });
    </script>
</body>
</html>