<?php
/**
 * Edit Student - Admin Panel
 * Hostel Management System
 * 
 * This file allows admin to edit student information
 */
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';

$errors = [];
$success = false;
$student = null;

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $errors[] = "Student ID is required.";
} else {
    // Validate student ID is numeric
    $studentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($studentId === false || $studentId === null) {
        $errors[] = "Invalid student ID format.";
    } else {
        // Fetch student details
        $student_query = "SELECT * FROM students WHERE id = ?";
        $stmt = $conn->prepare($student_query);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = "Student not found.";
        } else {
            $student = $result->fetch_assoc();
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_student'])) {    // Get and sanitize form data
    $name = trim($_POST['name'] ?? '');
    $contact_no = trim($_POST['contact_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Enhanced validation
    if (empty($name)) {
        $errors[] = "Name is required";
    } elseif (strlen($name) > 100) {
        $errors[] = "Name must be less than 100 characters";
    }
    
    if (empty($contact_no)) {
        $errors[] = "Contact Number is required";
    } elseif (!preg_match('/^\d{10,15}$/', str_replace(['-', ' '], '', $contact_no))) {
        $errors[] = "Contact Number must be 10-15 digits";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email must be less than 100 characters";
    }
    
    if (empty($address)) {
        $errors[] = "Address is required";
    } elseif (strlen($address) > 255) {
        $errors[] = "Address must be less than 255 characters";
    }
    
    // Check if email already exists (but not for current student)
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists for another student";
        }
    }      // Update student information in the database
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();            // Update student information
            $stmt = $conn->prepare("UPDATE students SET name = ?, contact_no = ?, email = ?, address = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare update statement: " . $conn->error);
            }
            
            $stmt->bind_param("ssssi", $name, $contact_no, $email, $address, $studentId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update student: " . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                // No rows were updated - could mean no changes or student doesn't exist
                $checkStmt = $conn->prepare("SELECT id FROM students WHERE id = ?");
                $checkStmt->bind_param("i", $studentId);
                $checkStmt->execute();
                if ($checkStmt->get_result()->num_rows === 0) {
                    throw new Exception("Student not found");
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Set success flag
            $success = true;
            
            // Refresh student data
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            
            // Add success message with details
            $_SESSION['success_message'] = "Student information updated successfully!";
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = "Error: " . $e->getMessage();
            error_log("Failed to update student ID $studentId: " . $e->getMessage());
        }
    }
}

// Set page title and additional CSS/JS files
$pageTitle = "Edit Student - MMU Hostel Management";
$additionalCSS = ["css/dashboard.css", "css/student-management.css"];
$additionalJS = [
    "https://code.jquery.com/jquery-3.6.0.min.js",
    "https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js",
    "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
    "../shared/js/script.js",
    "js/students.js"
];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once 'sidebar-admin.php';
?>

<div class="main-content">
    <?php 
    $pageHeading = "Edit Student";
    require_once 'admin-content-header.php'; 
    ?>

    <div class="content-wrapper">
        <nav class="page-navigation">
            <a href="students.php" class="nav-tab">
                <i class="fas fa-users"></i>
                Student List
            </a>
            <a href="edit_student.php?id=<?php echo $studentId; ?>" class="nav-tab active">
                <i class="fas fa-user-edit"></i>
                Edit Student
            </a>
        </nav>

        <div class="edit-student-form">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-edit"></i> Edit Student Information</h3>
                </div>
                <div class="card-body">                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> Student information updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($student): ?>
                        <form method="POST" class="student-form">
                            <div class="form-section">
                                <h4>Personal Information</h4>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="contact_no" class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" id="contact_no" name="contact_no" value="<?php echo htmlspecialchars($student['contact_no']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="id" class="form-label">Student ID</label>
                                        <input type="text" class="form-control" id="id" value="<?php echo htmlspecialchars($student['id']); ?>" readonly>
                                        <small class="text-muted">Student ID cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <input type="text" class="form-control" id="gender" value="<?php echo htmlspecialchars($student['gender']); ?>" readonly>
                                        <small class="text-muted">Gender cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="course" class="form-label">Course</label>
                                        <input type="text" class="form-control" id="course" value="<?php echo htmlspecialchars($student['course']); ?>" readonly>
                                        <small class="text-muted">Course cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="dob" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="dob" value="<?php echo htmlspecialchars($student['dob']); ?>" readonly>
                                        <small class="text-muted">Date of birth cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="citizenship" class="form-label">Citizenship</label>
                                        <input type="text" class="form-control" id="citizenship" value="<?php echo htmlspecialchars($student['citizenship']); ?>" readonly>
                                        <small class="text-muted">Citizenship cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="ic_number" class="form-label">IC Number</label>
                                        <input type="text" class="form-control" id="ic_number" value="<?php echo htmlspecialchars($student['ic_number']); ?>" readonly>
                                        <small class="text-muted">IC Number cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($student['username']); ?>" readonly>
                                        <small class="text-muted">Username cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($student['address']); ?></textarea>
                                    </div>
                                </div>                            </div>
                            
                            <div class="form-actions mt-4">
                                <button type="submit" name="update_student" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Student
                                </button>
                                <a href="students.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Student List
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No student information found.
                            <a href="students.php" class="btn btn-sm btn-secondary mt-3">
                                <i class="fas fa-arrow-left"></i> Back to Student List
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>    </div>
</div>

<?php
// Include footer
require_once '../shared/includes/footer.php';
?>

<script>
// Add validation function to check if the page was loaded correctly
document.addEventListener('DOMContentLoaded', function() {
    console.log('Edit student page loaded for ID: <?php echo isset($studentId) ? $studentId : "none"; ?>');
    
    // Check if there was an error loading student data
    <?php if (!empty($errors)): ?>
    console.error('Errors loading student data:', <?php echo json_encode($errors); ?>);
    <?php endif; ?>
    
    // Check if student data was loaded successfully
    <?php if ($student): ?>
    console.log('Student data loaded successfully');
    <?php endif; ?>
});
</script>
