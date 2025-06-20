<?php
session_start();
// Set up debug log file
$log_file = 'debug_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Result page loaded\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - GET data: " . print_r($_GET, true) . "\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Test Result</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Form Submission Test Results</h1>
        
        <?php if (isset($_GET['submitted']) && $_GET['submitted'] == 1): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['test_success'] ?? 'Form was submitted!'; ?>
            </div>
            <?php unset($_SESSION['test_success']); ?>
        <?php else: ?>
            <div class="alert alert-warning">
                No submission detected.
            </div>
        <?php endif; ?>
        
        <div class="card mt-3">
            <div class="card-header">Debug Information</div>
            <div class="card-body">
                <h5>Session Data:</h5>
                <pre><?php print_r($_SESSION); ?></pre>
                
                <h5>GET Data:</h5>
                <pre><?php print_r($_GET); ?></pre>
                
                <h5>Server Information:</h5>
                <p>PHP Version: <?php echo phpversion(); ?></p>
                <p>Server Software: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="redirect_test.php" class="btn btn-secondary">Try Again</a>
            <a href="complaints.php" class="btn btn-primary">Back to Complaints</a>
        </div>
    </div>
</body>
</html>
