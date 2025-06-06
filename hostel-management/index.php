<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Hostel Management System</title>
    <link rel="stylesheet" href="shared/css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #7579ff, #b224ef);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .role-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .role-card {
            flex: 1;
            margin: 0 10px;
            padding: 30px 20px;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
            background: white;
        }
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        .role-card h3 {
            margin-bottom: 15px;
            font-weight: 600;
        }
        .role-card p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .role-card .icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
        .admin-icon {
            color: #007bff;
        }
        .student-icon {
            color: #28a745;
        }
        .visitor-icon {
            color: #ffc107;
        }
        .main-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .alert {
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Make the cards stack on mobile */
        @media (max-width: 768px) {
            .role-container {
                flex-direction: column;
            }
            .role-card {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container">
            <div class="text-center mb-4">
                <h1>MMU Hostel Management System</h1>
                <p class="lead">Please select your role to continue</p>
            </div>
            
            <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-circle mr-2"></i>Login failed. Invalid credentials.
            </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['registered'])): ?>
            <div class="alert alert-success text-center">
                <i class="fas fa-check-circle mr-2"></i>Registration successful! You can now log in.
            </div>
            <?php endif; ?>
            
            <div class="role-container">                <div class="role-card">
                    <i class="fas fa-user-shield icon admin-icon"></i>
                    <h3>Admin</h3>
                    <p>Hostel administration and management</p>
                    <a href="admin/login.php" class="btn btn-primary btn-block">Login</a>
                </div>
                
                <div class="role-card">
                    <i class="fas fa-user-graduate icon student-icon"></i>
                    <h3>Student</h3>
                    <p>Hostel resident access</p>
                    <a href="student/login.php" class="btn btn-success btn-block">Login</a>
                    <a href="student/signup.php" class="btn btn-outline-success btn-block">Sign Up</a>
                </div>
                
                <div class="role-card">
                    <i class="fas fa-user-friends icon visitor-icon"></i>
                    <h3>Visitor</h3>
                    <p>Register for a visit</p>
                    <a href="visitor/registration.php" class="btn btn-warning btn-block">Register Visit</a>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p class="text-muted">© <?php echo date('Y'); ?> MMU Hostel Management System</p>
            </div>
        </div>
    </div>

    <script src="shared/js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>