<?php
/**
 * Complaint Handler - Processes complaint form submissions, feedbacks, and deletions
 * Separated from complaints.php for better code organization
 */
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';
require_once 'request_functions.php';

// Debug log file
$debug_log = 'complaint_debug.txt';

// Initialize variables
$studentId = 0;
$errors = [];
$success = "";

// Get student ID from session
$username = $_SESSION["user"];

// Get student ID from database
$stmt = $conn->prepare("SELECT id FROM students WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $studentId = $row['id'];
} else {
    $errors[] = "Student information not found.";
}

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Debug log
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - POST request received in complaint_handler.php\n", FILE_APPEND);
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - FILES data: " . print_r($_FILES, true) . "\n", FILE_APPEND);
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'submit_complaint':
                handleComplaintSubmission($conn, $studentId);
                break;
                
            case 'add_feedback':
                handleFeedbackSubmission($conn, $studentId);
                break;
                
            case 'delete_complaint':
                handleComplaintDeletion($conn, $studentId);
                break;
                
            default:
                $errors[] = "Invalid action.";
        }
    } else {
        $errors[] = "Missing form action.";
    }
}

// Store errors/success in session to persist across redirects
$_SESSION['complaint_errors'] = $errors;
$_SESSION['complaint_success'] = $success;

// Redirect back to the complaints page if this file is accessed directly
if (!isset($included_from_main)) {
    header("Location: complaints.php");
    exit();
}

/**
 * Handle complaint submission
 */
function handleComplaintSubmission($conn, $studentId) {
    global $success, $errors, $debug_log;
    
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Processing submit_complaint action\n", FILE_APPEND);
    
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $complaint_type = $_POST['complaint_type'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    
    // Debug log
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Student ID: $studentId\n", FILE_APPEND);
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Subject: $subject\n", FILE_APPEND);
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Type: $complaint_type\n", FILE_APPEND);
    
    // Server-side validation
    $form_errors = [];
    if (empty($subject)) $form_errors[] = "Subject is required";
    if (empty($description)) $form_errors[] = "Description is required";
    if (empty($complaint_type)) $form_errors[] = "Issue type is required";
    
    if (!empty($form_errors)) {
        $errors = array_merge($errors, $form_errors);
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Validation errors: " . implode(", ", $form_errors) . "\n", FILE_APPEND);
    } else {
        // Use the submitComplaint function
        $result = submitComplaint(
            $conn, 
            $studentId, 
            $subject, 
            $description, 
            $complaint_type, 
            $priority, 
            isset($_FILES['attachment']) ? $_FILES['attachment'] : null
        );
        
        // Debug log
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Submission result: " . print_r($result, true) . "\n", FILE_APPEND);
        
        if ($result['success']) {
            $success = $result['message'];
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Success message set: $success\n", FILE_APPEND);
            
            // Redirect to avoid form resubmission
            $_SESSION['complaint_success'] = $success;
            header("Location: complaints.php?submitted=1");
            exit();
        } else {
            $errors[] = $result['message'];
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Error message added: {$result['message']}\n", FILE_APPEND);
        }
    }
}

/**
 * Handle feedback submission for resolved complaints
 */
function handleFeedbackSubmission($conn, $studentId) {
    global $success, $errors;
    
    $complaint_id = $_POST['complaint_id'] ?? 0;
    $rating = $_POST['rating'] ?? 0;
    $feedback = trim($_POST['feedback'] ?? '');
    
    // Use the addComplaintFeedback function
    $result = addComplaintFeedback($conn, $complaint_id, $studentId, $rating, $feedback);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $errors[] = $result['message'];
    }
}

/**
 * Handle complaint deletion
 */
function handleComplaintDeletion($conn, $studentId) {
    global $success, $errors;
    
    $complaint_id = $_POST['complaint_id'] ?? 0;
    
    // Use the deleteComplaint function
    $result = deleteComplaint($conn, $complaint_id, $studentId);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $errors[] = $result['message'];
    }
}
?>
