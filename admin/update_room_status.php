<?php
// filepath: c:\xampp\htdocs\hostel-management-system\admin\update_room_status.php

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Create a debug log file
$debug_log = fopen("update_room_debug.log", "a");
fwrite($debug_log, "\n\n----- " . date('Y-m-d H:i:s') . " -----\n");
fwrite($debug_log, "POST data: " . print_r($_POST, true) . "\n");
fwrite($debug_log, "Session data: " . print_r($_SESSION, true) . "\n");

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    fwrite($debug_log, "Access denied - not logged in as admin\n");
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

require_once "../shared/includes/db_connection.php";
fwrite($debug_log, "Database connection included\n");

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fwrite($debug_log, "Method not allowed: " . $_SERVER['REQUEST_METHOD'] . "\n");
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get the room_id and new status from the POST data
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$new_status = isset($_POST['status']) ? $_POST['status'] : '';

fwrite($debug_log, "Raw POST data: " . print_r($_POST, true) . "\n");
fwrite($debug_log, "Parsed room_id: $room_id, new_status: $new_status\n");

// Validate the inputs
if ($room_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
    exit();
}

// Check if the provided status is valid - simplified to 3 options
$valid_statuses = ['Available', 'Occupied', 'Under Maintenance'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status. Only Available, Occupied, or Under Maintenance are allowed.']);
    exit();
}

fwrite($debug_log, "Validated room_id: $room_id and new_status: $new_status\n");

// Debug - check if the room exists
$check_query = "SELECT id, availability_status FROM rooms WHERE id = ?";
fwrite($debug_log, "Check query: $check_query with room_id: $room_id\n");
$check_stmt = $conn->prepare($check_query);
if ($check_stmt) {
    $check_stmt->bind_param("i", $room_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    fwrite($debug_log, "Room exists check - rows: " . $check_result->num_rows . "\n");
    if ($check_result->num_rows > 0) {
        $room_data = $check_result->fetch_assoc();
        fwrite($debug_log, "Current room data: " . print_r($room_data, true) . "\n");
    } else {
        fwrite($debug_log, "Room not found with id: $room_id\n");
    }
    $check_stmt->close();
} else {
    fwrite($debug_log, "Error preparing check statement: " . $conn->error . "\n");
}

// Update the room status in the database
$query = "UPDATE rooms SET availability_status = ? WHERE id = ?";
fwrite($debug_log, "SQL Query: $query\n");
fwrite($debug_log, "Parameters: status=$new_status, id=$room_id\n");

$stmt = $conn->prepare($query);
if (!$stmt) {
    fwrite($debug_log, "Error preparing statement: " . $conn->error . "\n");
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    fclose($debug_log);
    exit();
}

$stmt->bind_param("si", $new_status, $room_id);
$result = $stmt->execute();

// Check if any rows were affected
$rows_affected = $stmt->affected_rows;
fwrite($debug_log, "Execute result: " . ($result ? 'true' : 'false') . ", Rows affected: $rows_affected\n");

// Debug - check if the status was updated
if ($result) {
    $verify_query = "SELECT id, availability_status FROM rooms WHERE id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    if ($verify_stmt) {
        $verify_stmt->bind_param("i", $room_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        if ($verify_result->num_rows > 0) {
            $updated_room = $verify_result->fetch_assoc();
            fwrite($debug_log, "Updated room data: " . print_r($updated_room, true) . "\n");
        }
        $verify_stmt->close();
    } else {
        fwrite($debug_log, "Error preparing verify statement: " . $conn->error . "\n");
    }
}

if ($result && $rows_affected > 0) {
    // Get the block_id of this room to retrieve updated stats
    $roomQuery = "SELECT block_id FROM rooms WHERE id = ?";
    $roomStmt = $conn->prepare($roomQuery);
    $roomStmt->bind_param("i", $room_id);
    $roomStmt->execute();
    $roomResult = $roomStmt->get_result();
    
    if ($roomResult && $roomResult->num_rows > 0) {
        $roomData = $roomResult->fetch_assoc();
        $block_id = $roomData['block_id'];
        
        // Get updated stats for this block
        $statsQuery = "SELECT 
            COUNT(*) AS total_rooms,
            SUM(CASE WHEN availability_status = 'Available' THEN 1 ELSE 0 END) AS available_rooms,
            SUM(CASE WHEN availability_status = 'Occupied' THEN 1 ELSE 0 END) AS occupied_rooms,
            SUM(CASE WHEN availability_status = 'Under Maintenance' THEN 1 ELSE 0 END) AS maintenance_rooms
        FROM rooms 
        WHERE block_id = ?";
        
        $statsStmt = $conn->prepare($statsQuery);
        $statsStmt->bind_param("i", $block_id);
        $statsStmt->execute();
        $statsResult = $statsStmt->get_result();
        $stats = $statsResult->fetch_assoc();
        
        // Success response with updated stats
        echo json_encode([
            'success' => true, 
            'message' => 'Room status updated successfully',
            'room_id' => $room_id,
            'new_status' => $new_status,
            'stats' => $stats
        ]);
        
        $statsStmt->close();
    } else {
        // Success but couldn't get stats
        echo json_encode([
            'success' => true, 
            'message' => 'Room status updated successfully',
            'room_id' => $room_id,
            'new_status' => $new_status
        ]);
    }
    
    if (isset($roomStmt)) {
        $roomStmt->close();
    }
} else {
    // Error
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update room status: ' . $conn->error . ' (Affected rows: ' . $rows_affected . ', Room ID: ' . $room_id . ', New status: ' . $new_status . ')'
    ]);
}

// Close the database connection
$stmt->close();
$conn->close();

// Close debug log
fwrite($debug_log, "Request completed\n");
fclose($debug_log);
?>
