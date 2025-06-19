<?php
// filepath: c:\xampp\htdocs\hostel-management-system\admin\add_room.php
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

// Get the form data
$block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
$room_number = isset($_POST['room_number']) ? trim($_POST['room_number']) : '';
$room_type = isset($_POST['room_type']) ? trim($_POST['room_type']) : '';
$capacity = isset($_POST['capacity']) ? intval($_POST['capacity']) : 1;
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$features = isset($_POST['features']) ? trim($_POST['features']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'Available';

// Validate the inputs
if ($block_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid block ID']);
    exit();
}

if (empty($room_number)) {
    echo json_encode(['success' => false, 'message' => 'Room number is required']);
    exit();
}

// Check room type is valid
$valid_types = ['Single', 'Double', 'Triple', 'Quad'];
if (!in_array($room_type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid room type']);
    exit();
}

// Check status is valid
$valid_statuses = ['Available', 'Occupied', 'Under Maintenance'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Check if the room number already exists in this block
$check_query = "SELECT id FROM rooms WHERE block_id = ? AND room_number = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("is", $block_id, $room_number);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => "Room number '$room_number' already exists in this block"]);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Insert the new room into the database
$insert_query = "INSERT INTO rooms (block_id, room_number, type, capacity, price, features, availability_status) VALUES (?, ?, ?, ?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("issiiss", $block_id, $room_number, $room_type, $capacity, $price, $features, $status);
$result = $insert_stmt->execute();

if ($result) {
    // Get the ID of the newly inserted room
    $new_room_id = $conn->insert_id;
    
    // Get the new room details to return
    $room_query = "SELECT * FROM rooms WHERE id = ?";
    $room_stmt = $conn->prepare($room_query);
    $room_stmt->bind_param("i", $new_room_id);
    $room_stmt->execute();
    $room_result = $room_stmt->get_result();
    $room_data = $room_result->fetch_assoc();
    
    // Get updated stats for this block
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
    
    // Success response with the new room data and updated stats
    echo json_encode([
        'success' => true, 
        'message' => 'Room added successfully',
        'room' => $room_data,
        'stats' => $stats
    ]);
    
    $room_stmt->close();
    $stats_stmt->close();
} else {
    // Error
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to add room: ' . $conn->error
    ]);
}

// Close the database connection
$insert_stmt->close();
$conn->close();
?>
