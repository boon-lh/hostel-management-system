<?php
session_start();
require_once '../shared/includes/db_connection.php';

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form data
    $name = $_POST['name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $ic_number = $_POST['ic_number'] ?? '';
    $course = $_POST['course'] ?? '';
    $contact_no = $_POST['contact_no'] ?? '';
    $email = $_POST['email'] ?? '';
    $citizenship = $_POST['citizenship'] ?? '';
    $address = $_POST['address'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Get emergency contact form data
    $emergency_name = $_POST['emergency_name'] ?? '';
    $emergency_ic_number = $_POST['emergency_ic_number'] ?? '';
    $emergency_relationship = $_POST['emergency_relationship'] ?? '';
    $emergency_contact_no = $_POST['emergency_contact_no'] ?? '';
    $emergency_email = $_POST['emergency_email'] ?? '';
    
    // Validate form data
    if (empty($name)) $errors[] = "Name is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($dob)) $errors[] = "Date of Birth is required";
    if (empty($ic_number)) $errors[] = "IC Number is required";
    if (empty($course)) $errors[] = "Course is required";
    if (empty($contact_no)) $errors[] = "Contact Number is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($citizenship)) $errors[] = "Citizenship is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";

    // Validate emergency contact form data
    if (empty($emergency_name)) $errors[] = "Emergency Contact Name is required";
    if (empty($emergency_ic_number)) $errors[] = "Emergency Contact IC Number is required";
    if (empty($emergency_relationship)) $errors[] = "Emergency Contact Relationship is required";
    if (empty($emergency_contact_no)) $errors[] = "Emergency Contact Number is required";
    if (empty($emergency_email)) $errors[] = "Emergency Contact Email is required";
    if (!filter_var($emergency_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid Emergency Contact email format";
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM students WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Username already exists";
        }
    }
    
    // Check if IC number already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM students WHERE ic_number = ?");
        $stmt->bind_param("s", $ic_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "IC Number already exists";
        }
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    // If no errors, register student
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO students (name, gender, dob, ic_number, course, contact_no, email, citizenship, address, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssss", $name, $gender, $dob, $ic_number, $course, $contact_no, $email, $citizenship, $address, $username, $hashed_password);
        
        if ($stmt->execute()) {
            $student_id = $stmt->insert_id; // Get the ID of the newly inserted student

            // Insert into emergency_contacts table
            $stmt_emergency = $conn->prepare("INSERT INTO emergency_contacts (student_id, name, ic_number, relationship, contact_no, email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_emergency->bind_param("isssss", $student_id, $emergency_name, $emergency_ic_number, $emergency_relationship, $emergency_contact_no, $emergency_email);

            if ($stmt_emergency->execute()) {
                $success = true;
            } else {
                $errors[] = "Database error (emergency_contacts): " . $conn->error;
            }
        } else {
            $errors[] = "Database error (students): " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sign Up - MMU Hostel Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../shared/css/style.css">
    <link rel="stylesheet" href="css/signup.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-11">
                <div class="card">
                    <div class="card-header">
                        <h3>Create Your Student Account</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <h4><i class="fas fa-check-circle mr-2"></i>Registration Successful!</h4>
                                <p>Your account has been created successfully.</p>
                                <div class="text-center mt-4">
                                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                                    <a href="../index.php" class="btn btn-outline-secondary ml-2">Back to Home</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form action="signup.php" method="POST" novalidate>
                                <h5 class="form-section-title">Personal Information</h5>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="name">Full Name</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            </div>
                                            <input type="text" name="name" id="name" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="gender">Gender</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                            </div>
                                            <select name="gender" id="gender" class="form-control" required>
                                                <option value="" selected disabled>Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="dob">Date of Birth</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            </div>
                                            <input type="date" name="dob" id="dob" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="ic_number">IC Number / Passport</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            </div>
                                            <input type="text" name="ic_number" id="ic_number" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="course">Course</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                                            </div>
                                            <input type="text" name="course" id="course" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="contact_no">Contact Number</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            </div>
                                            <input type="text" name="contact_no" id="contact_no" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="email">Email</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            </div>
                                            <input type="email" name="email" id="email" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="citizenship">Citizenship</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-flag"></i></span>
                                            </div>
                                            <select name="citizenship" id="citizenship" class="form-control" required>
                                                <option value="" selected disabled>Select Citizenship</option>
                                                <option value="Malaysian">Malaysian</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-home"></i></span>
                                        </div>
                                        <textarea name="address" id="address" class="form-control" rows="3" required></textarea>
                                    </div>
                                </div>
                                
                                <h5 class="form-section-title">Emergency Contact Information</h5>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="emergency_name">Full Name</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            </div>
                                            <input type="text" name="emergency_name" id="emergency_name" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="emergency_ic_number">IC Number / Passport</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            </div>
                                            <input type="text" name="emergency_ic_number" id="emergency_ic_number" class="form-control" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="emergency_relationship">Relationship</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-users"></i></span>
                                            </div>
                                            <input type="text" name="emergency_relationship" id="emergency_relationship" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="emergency_contact_no">Contact Number</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            </div>
                                            <input type="text" name="emergency_contact_no" id="emergency_contact_no" class="form-control" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-12">
                                        <label for="emergency_email">Email</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            </div>
                                            <input type="email" name="emergency_email" id="emergency_email" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <h5 class="form-section-title">Account Credentials</h5>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="username">Username</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                            </div>
                                            <input type="text" name="username" id="username" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="password">Password</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            </div>
                                            <input type="password" name="password" id="password" class="form-control" required>
                                        </div>
                                        <div class="password-strength mt-2">
                                            <div class="password-strength-bar"></div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="confirm_password">Confirm Password</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            </div>
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="terms" name="terms" required>
                                        <label class="custom-control-label" for="terms">I agree to the <a href="#">Terms and Conditions</a></label>
                                    </div>
                                </div>
                                
                                <div class="form-group text-center">
                                    <button type="submit" class="btn btn-success btn-lg">Sign Up</button>
                                </div>
                                
                                <div class="text-center">
                                    <p>Already have an account? <a href="login.php">Login</a></p>
                                    <p><a href="../index.php">Back to Home</a></p>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../shared/js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBarContainer = document.querySelector('.password-strength');
            const strengthBar = document.querySelector('.password-strength-bar');
            
            // Remove all classes
            strengthBar.className = 'password-strength-bar'; // Reset to base class
            
            if (password.length > 0) {
                let strength = 0;
                
                // Check password length
                if (password.length >= 8) strength += 1;
                
                // Check for mixed case
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
                
                // Check for numbers
                if (password.match(/\\d/)) strength += 1;
                
                // Check for special characters
                if (password.match(/[^a-zA-Z\\d]/)) strength += 1;
                
                // Update strength bar
                switch (strength) {
                    case 0:
                        // Optionally handle case 0 if you want a default state or hide the bar
                        break;
                    case 1:
                        strengthBar.classList.add('weak');
                        break;
                    case 2:
                        strengthBar.classList.add('medium');
                        break;
                    case 3:
                        strengthBar.classList.add('strong');
                        break;
                    case 4:
                        strengthBar.classList.add('very-strong');
                        break;
                }
            }
        });
    </script>
</body>
</html>