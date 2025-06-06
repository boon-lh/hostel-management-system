<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Student Profile";
$additionalCSS = ["css/profile.css"];

// Fetch student information from database
$username = $_SESSION["user"];
$student = null;
$emergency_contact = null; // Variable to store emergency contact details
$errors = [];
$success = false;

// Get student data
$stmt = $conn->prepare("SELECT * FROM students WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();

    // Fetch emergency contact information
    $stmt_emergency = $conn->prepare("SELECT * FROM emergency_contacts WHERE student_id = ?");
    $stmt_emergency->bind_param("i", $student['id']);
    $stmt_emergency->execute();
    $result_emergency = $stmt_emergency->get_result();
    if ($result_emergency->num_rows > 0) {
        $emergency_contact = $result_emergency->fetch_assoc();
    }
} else {
    $errors[] = "Student information not found.";
}

// Handle form submission for updating profile
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form data
    $name = $_POST['name'] ?? '';
    $contact_no = $_POST['contact_no'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($contact_no)) $errors[] = "Contact Number is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($address)) $errors[] = "Address is required";
    
    // Check if email already exists (but not for current user)
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND username != ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
    }
      // Profile image functionality removed as the column doesn't exist in database
    
    // If password change is requested
    if (!empty($current_password)) {
        // Verify current password
        if (!password_verify($current_password, $student["password"])) {
            $errors[] = "Current password is incorrect";
        }
        
        // Validate new password
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
      // Update student information
    if (empty($errors)) {
        if (!empty($current_password) && !empty($new_password)) {
            // Update with password change
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE students SET name = ?, contact_no = ?, email = ?, address = ?, password = ? WHERE username = ?");
            $stmt->bind_param("ssssss", $name, $contact_no, $email, $address, $hashed_password, $username);
        } else {
            // Update without password change
            $stmt = $conn->prepare("UPDATE students SET name = ?, contact_no = ?, email = ?, address = ? WHERE username = ?");
            $stmt->bind_param("sssss", $name, $contact_no, $email, $address, $username);
        }
        
        if ($stmt->execute()) {
            $success = true;
            // Update session variables
            $_SESSION["fullname"] = $name;
            
            // Refetch student data to reflect changes
            $stmt = $conn->prepare("SELECT * FROM students WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $student = $result->fetch_assoc();
            }
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}

// Include header
require_once '../shared/includes/header.php';

// Include student sidebar
require_once '../shared/includes/sidebar-student.php';
?>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1><i class="fas fa-user-circle"></i> My Profile</h1>
    </div>
    
    <!-- Profile Form -->
    <div class="profile-container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle fa-lg mr-2"></i>
                Profile updated successfully!
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                
                    <div class="profile-section">
                        <div class="section-title">
                            <i class="fas fa-id-card"></i>
                            <h4>Personal Information</h4>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($student['name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="ic_number" class="form-label">IC/Passport Number</label>
                                <div class="readonly-field">
                                    <input type="text" class="form-control" id="ic_number" value="<?php echo htmlspecialchars($student['ic_number'] ?? ''); ?>" readonly>
                                </div>
                                <small class="form-text text-muted">IC/Passport number cannot be changed</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <div class="readonly-field">
                                    <input type="text" class="form-control" id="gender" value="<?php echo htmlspecialchars($student['gender'] ?? ''); ?>" readonly>
                                </div>
                                <small class="form-text text-muted">Gender cannot be changed</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <div class="readonly-field">
                                    <input type="date" class="form-control" id="dob" value="<?php echo htmlspecialchars($student['dob'] ?? ''); ?>" readonly>
                                </div>
                                <small class="form-text text-muted">Date of birth cannot be changed</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="course" class="form-label">Course</label>
                                <div class="readonly-field">
                                    <input type="text" class="form-control" id="course" value="<?php echo htmlspecialchars($student['course'] ?? ''); ?>" readonly>
                                </div>
                                <small class="form-text text-muted">Course cannot be changed</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="citizenship" class="form-label">Citizenship</label>
                                <div class="readonly-field">
                                    <input type="text" class="form-control" id="citizenship" value="<?php echo htmlspecialchars($student['citizenship'] ?? ''); ?>" readonly>
                                </div>
                                <small class="form-text text-muted">Citizenship cannot be changed</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <div class="section-title">
                            <i class="fas fa-address-book"></i>
                            <h4>Contact Information</h4>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="contact_no" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="contact_no" name="contact_no" value="<?php echo htmlspecialchars($student['contact_no'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address" class="form-label">Home Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <div class="section-title">
                            <i class="fas fa-user-shield"></i>
                            <h4>Account Information</h4>
                        </div>
                        
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <div class="readonly-field">
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($student['username'] ?? ''); ?>" readonly>
                            </div>
                            <small class="form-text text-muted">Username cannot be changed</small>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <div class="section-title">
                            <i class="fas fa-ambulance"></i>
                            <h4>Emergency Contact Information</h4>
                        </div>
                        
                        <?php if ($emergency_contact): ?>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="emergency_name" class="form-label">Full Name</label>
                                    <div class="readonly-field">
                                        <input type="text" class="form-control" id="emergency_name" value="<?php echo htmlspecialchars($emergency_contact['name'] ?? ''); ?>" readonly>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="emergency_ic_number" class="form-label">IC/Passport Number</label>
                                    <div class="readonly-field">
                                        <input type="text" class="form-control" id="emergency_ic_number" value="<?php echo htmlspecialchars($emergency_contact['ic_number'] ?? ''); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="emergency_relationship" class="form-label">Relationship</label>
                                    <div class="readonly-field">
                                        <input type="text" class="form-control" id="emergency_relationship" value="<?php echo htmlspecialchars($emergency_contact['relationship'] ?? ''); ?>" readonly>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="emergency_contact_no" class="form-label">Contact Number</label>
                                    <div class="readonly-field">
                                        <input type="text" class="form-control" id="emergency_contact_no" value="<?php echo htmlspecialchars($emergency_contact['contact_no'] ?? ''); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="emergency_email" class="form-label">Email Address</label>
                                <div class="readonly-field">
                                    <input type="email" class="form-control" id="emergency_email" value="<?php echo htmlspecialchars($emergency_contact['email'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                No emergency contact information available.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-section">
                        <div class="section-title">
                            <i class="fas fa-lock"></i>
                            <h4>Change Password</h4>
                        </div>
                        <p class="text-muted mb-4">Leave blank if you don't want to change your password</p>
                        
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../shared/includes/footer.php';
?>
