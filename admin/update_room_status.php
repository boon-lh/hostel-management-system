<?php
// filepath: c:\xampp\htdocs\hostel-management-system\admin\update_room_status.php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

require_once "../shared/includes/db_connection.php";

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get the room_id and new status from the POST data
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$new_status = isset($_POST['status']) ? $_POST['status'] : '';

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

// Update the room status in the database
$query = "UPDATE rooms SET availability_status = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $new_status, $room_id);
$result = $stmt->execute();

if ($result) {
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
        'message' => 'Failed to update room status: ' . $conn->error
    ]);
}

// Close the database connection
$stmt->close();
$conn->close();
?>
