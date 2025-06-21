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
    
    // Log all POST parameters for debugging
    error_log("AJAX POST Request: " . json_encode($_POST));
    
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $complaintId = isset($_POST['complaint_id']) ? intval($_POST['complaint_id']) : 0;
        $newStatus = $_POST['new_status'] ?? '';
        $comments = $_POST['comments'] ?? '';
        
        error_log("Processing status update: ID=$complaintId, Status=$newStatus");
        
        if ($complaintId <= 0) {
            error_log("Invalid complaint ID: $complaintId");
            echo json_encode(['success' => false, 'message' => 'Invalid complaint ID']);
            exit();
        }
        
        if (empty($newStatus)) {
            error_log("Empty status provided");
            echo json_encode(['success' => false, 'message' => 'New status is required']);
            exit();
        }
          try {
            // Log DB connection status
            error_log("DB connection status: " . ($conn->ping() ? "Connected" : "Not connected"));
            
            // Validate the status format
            if (!in_array($newStatus, ['pending', 'in_progress', 'resolved', 'closed'])) {
                error_log("Invalid status format: '$newStatus'");
                echo json_encode(['success' => false, 'message' => "Invalid status format: '$newStatus'"]);
                exit();
            }
            
            // Test the database connection with a simple query
            $testResult = $conn->query("SELECT 1");
            if (!$testResult) {
                error_log("DB connection test failed: " . $conn->error);
                echo json_encode(['success' => false, 'message' => 'Database connection issue: ' . $conn->error]);
                exit();
            }
              // Print the database details before update
            $beforeQuery = "SELECT * FROM complaints WHERE id = $complaintId";
            $beforeResult = $conn->query($beforeQuery);
            if ($beforeResult && $beforeResult->num_rows > 0) {
                $beforeData = $beforeResult->fetch_assoc();
                error_log("BEFORE UPDATE - ID: {$beforeData['id']}, Status: {$beforeData['status']}");
                
                // Display before status in response
                echo "<!-- DEBUG: Before update - ID: {$beforeData['id']}, Status: {$beforeData['status']} -->\n";
            } else {
                error_log("Could not find complaint with ID: $complaintId");
                echo json_encode(['success' => false, 'message' => "Complaint ID $complaintId not found"]);
                exit();
            }
            
            // Try to update using mysqli_query directly to bypass any potential MySQLi object issues
            $rawConn = mysqli_connect("localhost", "root", "", "hostel_management");
            if (!$rawConn) {
                error_log("Direct connection failed: " . mysqli_connect_error());
                echo json_encode(['success' => false, 'message' => 'Could not connect to database']);
                exit();
            }
              // Use prepared statement with the raw connection for safer update
            $updateStmt = mysqli_prepare($rawConn, "UPDATE complaints SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($updateStmt, "si", $newStatus, $complaintId);
            error_log("Executing prepared statement update - ID: $complaintId, Status: $newStatus");
            
            $updateSuccess = mysqli_stmt_execute($updateStmt);
            if (!$updateSuccess) {
                error_log("Prepared statement update failed: " . mysqli_stmt_error($updateStmt));
                
                // Try direct query as fallback
                $updateQuery = "UPDATE complaints SET status = '$newStatus' WHERE id = $complaintId";
                error_log("Falling back to direct query: $updateQuery");
                
                $updateResult = mysqli_query($rawConn, $updateQuery);
                if (!$updateResult) {
                    error_log("Direct update failed: " . mysqli_error($rawConn));
                    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . mysqli_error($rawConn)]);
                    exit();
                }
            }
            
            error_log("Update affected rows: " . mysqli_stmt_affected_rows($updateStmt) . " (prepared statement) or " . mysqli_affected_rows($rawConn) . " (direct query)");
            
            // Verify the update happened with the direct connection
            $verifyQuery = "SELECT id, status FROM complaints WHERE id = $complaintId";
            $verifyResult = mysqli_query($rawConn, $verifyQuery);
            
            if ($verifyResult && mysqli_num_rows($verifyResult) > 0) {
                $verifiedData = mysqli_fetch_assoc($verifyResult);
                error_log("AFTER UPDATE - ID: {$verifiedData['id']}, Status: {$verifiedData['status']}");
                
                // Display after status in response
                echo "<!-- DEBUG: After update - ID: {$verifiedData['id']}, Status: {$verifiedData['status']} -->\n";
            }
            
            // Verify the update happened
            $verifyQuery = "SELECT status FROM complaints WHERE id = $complaintId";
            $verifyResult = $conn->query($verifyQuery);
            
            if ($verifyResult && $verifyResult->num_rows > 0) {
                $currentStatus = $verifyResult->fetch_assoc()['status'];
                error_log("Status after update: $currentStatus");
                
                if ($currentStatus === $newStatus) {
                    // Add history entry separately
                    try {
                        $historyQuery = "INSERT INTO complaint_status_history (complaint_id, status, comments, changed_by) 
                                        VALUES ($complaintId, '$newStatus', " . 
                                        ($comments ? "'" . $conn->real_escape_string($comments) . "'" : "NULL") . 
                                        ", $adminId)";
                        error_log("History query: $historyQuery");
                        $conn->query($historyQuery);
                    } catch (Exception $historyErr) {
                        error_log("Could not add history entry: " . $historyErr->getMessage());
                        // But continue anyway
                    }
                    
                    // Get the updated complaint details
                    $updatedComplaint = getAdminComplaintDetails($conn, $complaintId);
                    error_log("Status update successful for complaint #$complaintId");
                    echo json_encode([
                        'success' => true,
                        'message' => "Complaint status updated successfully to $newStatus",
                        'complaint' => $updatedComplaint
                    ]);
                } else {
                    error_log("Status didn't update as expected. Current: $currentStatus, Expected: $newStatus");
                    echo json_encode([
                        'success' => false, 
                        'message' => "Status didn't update as expected. Database shows: $currentStatus"
                    ]);
                }
            } else {
                error_log("Could not verify status update: " . $conn->error);
                echo json_encode(['success' => false, 'message' => 'Could not verify status update']);
            }
        } catch (Exception $e) {
            error_log("Exception during status update: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error processing request: ' . $e->getMessage()]);
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
