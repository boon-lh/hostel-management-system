<?php
// Simple, direct room deletion script for fallback
session_start();

// Ensure user is logged in as admin
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    echo "Error: Admin access required";
    exit;
}

// Include database connection
require_once "../shared/includes/db_connection.php";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get room_id from URL
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($room_id <= 0) {
    echo "Error: Invalid room ID";
    exit;
}

// Simple direct deletion
$sql = "DELETE FROM rooms WHERE id = $room_id";
$result = $conn->query($sql);

if ($result) {
    $affected_rows = $conn->affected_rows;
    echo "Success! Room deleted. Affected rows: $affected_rows";
} else {
    echo "Error: " . $conn->error;
}

echo "<br><br><a href='block_rooms.php'>Return to Rooms List</a>";
?>
