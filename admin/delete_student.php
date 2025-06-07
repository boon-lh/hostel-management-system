<?php
session_start();

// Enable error logging for debugging
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1); // Log errors
error_log("Delete student request received: " . print_r($_POST, true)); // Log the request

if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../shared/includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['id']) ? $_POST['id'] : null;
    
    if ($student_id) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {            // Delete related records first (assuming foreign key constraints)
            $sql_bills = "DELETE FROM bills WHERE student_id = ?";
            $stmt_bills = $conn->prepare($sql_bills);
            $stmt_bills->bind_param("s", $student_id); // Changed to string in case student_id is alphanumeric
            $stmt_bills->execute();
            
            // Delete student record
            $sql_student = "DELETE FROM students WHERE id = ?";
            $stmt_student = $conn->prepare($sql_student);
            $stmt_student->bind_param("s", $student_id); // Changed to string in case student_id is alphanumeric
            $stmt_student->execute();
            
            if ($stmt_student->affected_rows > 0) {
                $conn->commit();
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Student not found');
            }        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error deleting student: " . $e->getMessage());
            error_log("Error details: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
