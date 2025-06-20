<?php
/**
 * Admin Complaint AJAX Handler
 * Processes AJAX requests for viewing and updating complaints
 */
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

require_once '../shared/includes/db_connection.php';
require_once 'admin_request_functions.php';

// Get admin ID from session
$username = $_SESSION["user"];
$adminId = 0;

// Get admin ID from database
$stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $adminId = $row['id'];
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Admin information not found']);
    exit();
}

// Process GET requests (for viewing complaint details)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Log all GET parameters for debugging
    error_log("AJAX GET Request: " . json_encode($_GET));
    
    if (isset($_GET['action']) && $_GET['action'] === 'get_complaint' && isset($_GET['id'])) {
        $complaintId = intval($_GET['id']);
        error_log("Fetching complaint ID: $complaintId");
        
        try {
            $complaint = getAdminComplaintDetails($conn, $complaintId);
            
            if (!$complaint) {
                error_log("Complaint not found: ID $complaintId");
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Complaint not found']);
                exit();
            }
            
            // Return the complaint details as JSON
            header('Content-Type: application/json');
            echo json_encode($complaint);
            exit();
        } catch (Exception $e) {
            // Log and return any exceptions
            error_log("Exception in complaint_ajax_handler.php: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Error processing request: ' . $e->getMessage()]);
            exit();
        }
    }
}

// Process POST requests (for updating complaint status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $complaintId = isset($_POST['complaint_id']) ? intval($_POST['complaint_id']) : 0;
        $newStatus = $_POST['new_status'] ?? '';
        $comments = $_POST['comments'] ?? '';
        
        if ($complaintId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid complaint ID']);
            exit();
        }
        
        if (empty($newStatus)) {
            echo json_encode(['success' => false, 'message' => 'New status is required']);
            exit();
        }
        
        $result = updateComplaintStatus($conn, $complaintId, $newStatus, $adminId, $comments);
        
        if ($result['success']) {
            // Get the updated complaint details to return
            $updatedComplaint = getAdminComplaintDetails($conn, $complaintId);
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'complaint' => $updatedComplaint
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
        
        exit();
    }
    
    echo json_encode(['error' => 'Invalid action']);
    exit();
}

// Test endpoint to verify AJAX handler functionality
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'test') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'AJAX handler is working properly',
        'time' => date('Y-m-d H:i:s'),
        'session_status' => session_status(),
        'php_version' => phpversion()
    ]);
    exit();
}

// If no valid action is found
header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid request']);
exit();
?>
