<?php
/**
 * Fix script to create missing tables for complaints functionality
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once '../shared/includes/db_connection.php';

// Check if student_room_assignments table exists
$tableExists = false;
$result = $conn->query("SHOW TABLES LIKE 'student_room_assignments'");
if ($result) {
    $tableExists = $result->num_rows > 0;
}

if (!$tableExists) {
    echo "<h2>Creating student_room_assignments table...</h2>";
    
    // Create the student_room_assignments table
    $sql = "CREATE TABLE `student_room_assignments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `student_id` int(11) NOT NULL,
      `room_id` int(11) NOT NULL,
      `status` enum('active','cancelled','completed') NOT NULL DEFAULT 'active',
      `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `student_id` (`student_id`),
      KEY `room_id` (`room_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table student_room_assignments created successfully</p>";
    } else {
        echo "<p>Error creating table: " . $conn->error . "</p>";
    }
}

// Check if complaint_status_history table exists
$tableExists = false;
$result = $conn->query("SHOW TABLES LIKE 'complaint_status_history'");
if ($result) {
    $tableExists = $result->num_rows > 0;
}

if (!$tableExists) {
    echo "<h2>Creating complaint_status_history table...</h2>";
    
    // Create the complaint_status_history table
    $sql = "CREATE TABLE `complaint_status_history` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `complaint_id` int(11) NOT NULL,
      `status` enum('pending','in_progress','resolved','closed') NOT NULL,
      `changed_by` int(11) DEFAULT NULL,
      `comments` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `complaint_id` (`complaint_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table complaint_status_history created successfully</p>";
    } else {
        echo "<p>Error creating table: " . $conn->error . "</p>";
    }
}

echo "<h2>Fix complete!</h2>";
echo "<p>You can now <a href='complaints.php'>return to the complaints page</a>.</p>";
?>
