<?php
// filepath: c:\xampp\htdocs\hostel-management-system\admin\delete_room.php

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start logging
$log_file = fopen("delete_room_debug.log", "a");
fwrite($log_file, "\n\n----- " . date('Y-m-d H:i:s') . " -----\n");
fwrite($log_file, "Request started\n");

session_start();
fwrite($log_file, "Session started\n");
fwrite($log_file, "Session data: " . print_r($_SESSION, true) . "\n");

// Check if the user is logged in and is an admin
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    fwrite($log_file, "Access denied: Not logged in as admin\n");
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    fclose($log_file);
    exit();
}

// Include database connection
fwrite($log_file, "Including database connection\n");
require_once "../shared/includes/db_connection.php";
fwrite($log_file, "Database connection included\n");

// Log connection status
if (!isset($conn)) {
    fwrite($log_file, "ERROR: Database connection variable not available\n");
} else {
    fwrite($log_file, "Database connection established\n");
}

// Check if this is a POST request
fwrite($log_file, "Request method: " . $_SERVER['REQUEST_METHOD'] . "\n");
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fwrite($log_file, "Method not allowed: Expected POST, got " . $_SERVER['REQUEST_METHOD'] . "\n");
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    fclose($log_file);
    exit();
}

// Log POST data
fwrite($log_file, "POST data: " . print_r($_POST, true) . "\n");

// Get the room ID from POST data
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
fwrite($log_file, "Extracted room_id: " . $room_id . "\n");

// Validate input
if ($room_id <= 0) {
    fwrite($log_file, "Invalid room ID: " . $room_id . "\n");
    echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
    fclose($log_file);
    exit();
}

// Get block_id before deletion for stats update
$block_query = "SELECT block_id FROM rooms WHERE id = ?";
fwrite($log_file, "Executing query to get block_id: " . $block_query . " with room_id = " . $room_id . "\n");

// Check if prepare succeeded
$block_stmt = $conn->prepare($block_query);
if (!$block_stmt) {
    fwrite($log_file, "Error preparing block query: " . $conn->error . "\n");
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    fclose($log_file);
    exit();
}

// Execute statement
$block_stmt->bind_param("i", $room_id);
$block_result = $block_stmt->execute();

if (!$block_result) {
    fwrite($log_file, "Error executing block query: " . $block_stmt->error . "\n");
}

$block_result = $block_stmt->get_result();
$block_id = 0;

if ($block_result && $block_result->num_rows > 0) {
    $block_data = $block_result->fetch_assoc();
    $block_id = $block_data['block_id'];
    fwrite($log_file, "Found block_id: " . $block_id . "\n");
} else {
    fwrite($log_file, "Room not found or no block_id associated\n");
    echo json_encode(['success' => false, 'message' => 'Room not found in database']);
    fclose($log_file);
    exit();
}

if ($block_stmt) {
    $block_stmt->close();
}

// Check if the room has active registrations
fwrite($log_file, "Checking for active registrations\n");

// First check if the room_registrations table exists
$table_check = null;
try {
    $table_check = $conn->query("SHOW TABLES LIKE 'room_registrations'");
    $table_exists = ($table_check !== false && $table_check->num_rows > 0);
    fwrite($log_file, "room_registrations table exists: " . ($table_exists ? "Yes" : "No") . "\n");
} catch (Exception $e) {
    fwrite($log_file, "Error checking for room_registrations table: " . $e->getMessage() . "\n");
    $table_exists = false;
}

if ($table_exists) {
    $registration_check = "SELECT COUNT(*) AS count FROM room_registrations WHERE room_id = ? AND status IN ('Approved', 'Pending')";
    fwrite($log_file, "Registration check query: " . $registration_check . "\n");
    
    $reg_stmt = $conn->prepare($registration_check);
    if (!$reg_stmt) {
        fwrite($log_file, "Error preparing registration check: " . $conn->error . "\n");
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        fclose($log_file);
        exit();
    }
    
    $reg_stmt->bind_param("i", $room_id);
    $reg_exec = $reg_stmt->execute();
    
    if (!$reg_exec) {
        fwrite($log_file, "Error executing registration check: " . $reg_stmt->error . "\n");
    }
    
    $reg_result = $reg_stmt->get_result();
    $reg_data = $reg_result->fetch_assoc();
    
    fwrite($log_file, "Registration check result: " . print_r($reg_data, true) . "\n");
} else {
    // If table doesn't exist, assume no registrations
    fwrite($log_file, "room_registrations table doesn't exist, assuming no registrations\n");
    $reg_data = ['count' => 0];
}

if (isset($reg_data['count']) && $reg_data['count'] > 0) {
    fwrite($log_file, "Delete prevented: Room has active registrations\n");
    echo json_encode([
        'success' => false, 
        'message' => 'Cannot delete room. There are active registrations for this room.'
    ]);
    if (isset($reg_stmt) && $reg_stmt) {
        $reg_stmt->close();
    }
    fclose($log_file);
    exit();
}

if (isset($reg_stmt) && $reg_stmt) {
    $reg_stmt->close();
}

// Delete the room
$delete_query = "DELETE FROM rooms WHERE id = ?";
fwrite($log_file, "Delete query: " . $delete_query . " with room_id = " . $room_id . "\n");

$delete_stmt = $conn->prepare($delete_query);
if (!$delete_stmt) {
    fwrite($log_file, "Error preparing delete statement: " . $conn->error . "\n");
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    fclose($log_file);
    exit();
}

$delete_stmt->bind_param("i", $room_id);
$result = $delete_stmt->execute();
$affected_rows = $delete_stmt->affected_rows;

fwrite($log_file, "Delete result: " . ($result ? "Success" : "Failed") . "\n");
fwrite($log_file, "Affected rows: " . $affected_rows . "\n");

if ($result && $affected_rows > 0) {
    fwrite($log_file, "Room deleted successfully\n");
    
    // Get updated block stats if we know the block_id
    $stats = null;
    
    if ($block_id > 0) {
        fwrite($log_file, "Getting updated stats for block_id: " . $block_id . "\n");
        $stats_query = "SELECT 
            COUNT(*) AS total_rooms,
            SUM(CASE WHEN availability_status = 'Available' THEN 1 ELSE 0 END) AS available_rooms,
            SUM(CASE WHEN availability_status = 'Occupied' THEN 1 ELSE 0 END) AS occupied_rooms,
            SUM(CASE WHEN availability_status = 'Under Maintenance' THEN 1 ELSE 0 END) AS maintenance_rooms
        FROM rooms 
        WHERE block_id = ?";
        
        $stats_stmt = $conn->prepare($stats_query);
        $stats_stmt->bind_param("i", $block_id);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result();
        $stats = $stats_result->fetch_assoc();
        $stats_stmt->close();
    }
      // Return success response
    $response = [
        'success' => true, 
        'message' => 'Room deleted successfully',
        'room_id' => $room_id
    ];
    
    if ($stats !== null) {
        $response['stats'] = $stats;
    }
    
    fwrite($log_file, "Sending success response: " . print_r($response, true) . "\n");
    echo json_encode($response);
} else {
    // Return error response
    $error_message = 'Failed to delete room';
    if ($conn->error) {
        $error_message .= ': ' . $conn->error;
    }
    
    fwrite($log_file, "Error deleting room: " . $error_message . "\n");
    echo json_encode([
        'success' => false, 
        'message' => $error_message
    ]);
}

// Close the statement and connection
if ($delete_stmt) {
    $delete_stmt->close();
}
$conn->close();

// Close log file
fwrite($log_file, "Request completed\n");
fclose($log_file);
?>
