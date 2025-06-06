<?php
session_start();
require_once '../shared/includes/db_connection.php';

$error = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? '';
    $password = $_POST["password"] ?? '';
    
    // Validate login credentials
    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, name, username, password, profile_pic FROM students WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $student['password'])) {
                // Set session variables
                $_SESSION["user_id"] = $student['id'];
                $_SESSION["user"] = $student['username'];
                $_SESSION["fullname"] = $student['name'];
                $_SESSION["role"] = "student";
                $_SESSION["profile_image"] = $student['profile_pic'] ?? null;
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = true;
            }
        } else {
            $error = true;
        }
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - MMU Hostel Management System</title>
    <link rel="stylesheet" href="../shared/css/style.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3 class="text-center">Student Login</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                Invalid username or password. Please try again.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['registered'])): ?>
                            <div class="alert alert-success">
                                Registration successful! Please log in with your credentials.
                            </div>
                        <?php endif; ?>
                        
                        <form action="login.php" method="POST">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            
                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-success btn-lg">Login</button>
                            </div>
                            
                            <div class="text-center">
                                <p><a href="reset_password.php">Forgot Password?</a></p>
                                <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
                                <p><a href="../index.php">Back to Home</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../shared/js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>