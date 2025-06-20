<?php
session_start();
require_once '../shared/includes/db_connection.php';

// Set up debug log file
$log_file = 'debug_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Redirect test started\n", FILE_APPEND);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - POST request received\n", FILE_APPEND);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
    
    // Set success message in session
    $_SESSION['test_success'] = "Form submission successful!";
    
    // Redirect back to the test page with a flag
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Redirecting to test_result.php\n", FILE_APPEND);
    header("Location: test_result.php?submitted=1");
    exit();
}

file_put_contents($log_file, date('Y-m-d H:i:s') . " - No POST data, showing form\n", FILE_APPEND);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Test</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Form Submission Test</h1>
        
        <div class="card">
            <div class="card-header">Test Form</div>
            <div class="card-body">
                <form action="redirect_test.php" method="POST">
                    <div class="form-group">
                        <label>Test Input</label>
                        <input type="text" name="test_input" class="form-control" value="Test Value">
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Test Form</button>
                </form>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="complaints.php" class="btn btn-secondary">Back to Complaints</a>
        </div>
    </div>
</body>
</html>
