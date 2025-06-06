<?php
// Database setup script
// Run this file once to set up the database and tables

$host = "localhost";
$username = "root"; // Change as needed
$password = ""; // Change as needed

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to MySQL server successfully.<br>";

// Create database if it doesn't exist
$dbname = "hostel_management";
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname`";
if ($conn->query($sql)) {
    echo "Database created/selected successfully<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Read SQL file
$sql = file_get_contents('database_setup.sql');

// Split the SQL file into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

// Execute each statement
$success = true;
foreach($statements as $statement) {
    if(!empty($statement)) {
        if($conn->query($statement) === FALSE) {
            echo "Error executing statement: " . $conn->error . "<br>";
            echo "Statement: " . $statement . "<br><br>";
            $success = false;
            break;
        }
    }
}

if ($success) {
    echo "Database and tables created successfully!<br>";
    
    // Create initial admin user
    require_once 'shared/includes/insert_admin.php';
    echo "Initial admin user setup completed.<br>";
    
    // Create upload directory for profile pictures if it doesn't exist
    if (!file_exists('uploads/profile_pics/')) {
        if (mkdir('uploads/profile_pics/', 0777, true)) {
            echo "Uploads directory created successfully.<br>";
        } else {
            echo "Failed to create uploads directory.<br>";
        }
    }
    
    echo "<p>Your database is now set up! You can now <a href='index.html'>login to the system</a>.</p>";
} else {
    echo "Error creating database: " . $conn->error;
}

$conn->close();
?>