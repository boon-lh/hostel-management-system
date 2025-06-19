<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

require_once "../shared/includes/db_connection.php";

// Get room details for debugging
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

// Check if a specific room ID was provided
if ($room_id > 0) {
    $query = "SELECT id, room_number, availability_status FROM rooms WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $room = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'room' => $room
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Room not found'
        ]);
    }
    
    $stmt->close();
} else {
    // Get all rooms for the current block
    $block_id = isset($_GET['block_id']) ? intval($_GET['block_id']) : 0;
    
    if ($block_id > 0) {
        $query = "SELECT id, room_number, availability_status FROM rooms WHERE block_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $block_id);
    } else {
        $query = "SELECT id, room_number, availability_status FROM rooms";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'rooms' => $rooms
    ]);
    
    $stmt->close();
}

$conn->close();
?>
