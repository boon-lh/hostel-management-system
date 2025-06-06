<?php
// Database connection
$host = "localhost";
$username = "root"; // Change as needed
$password = ""; // Change as needed
$database = "hostel_management";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>