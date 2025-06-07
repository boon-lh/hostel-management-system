<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "student") {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

require_once '../shared/includes/db_connection.php';
require_once 'request_functions.php';

// Get student ID from session
$username = $_SESSION["user"];
$studentId = 0;

// Get request ID from request
$requestId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($requestId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request ID']);
    exit();
}

// Get student ID from database
$stmt = $conn->prepare("SELECT id FROM students WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $studentId = $row['id'];
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Student information not found']);
    exit();
}

// Use the getRequestDetails function
$request = getRequestDetails($conn, $requestId, $studentId);

if (!$request) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Request not found or access denied']);
    exit();
}

// Return the request details as JSON
header('Content-Type: application/json');
echo json_encode($request);
?>
