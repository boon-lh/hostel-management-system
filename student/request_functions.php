<?php
/**
 * Functions for handling student complaints, feedback
 * in the Hostel Management System
 */

/**
 * Submit a new complaint
 * 
 * @param mysqli $conn Database connection
 * @param int $studentId Student ID
 * @param string $subject Complaint subject
 * @param string $description Complaint description
 * @param string $complaintType Type of complaint
 * @param string $priority Priority level (low, medium, high, urgent)
 * @param array $attachment File upload data from $_FILES
 * @return array Associative array with 'success' (bool) and 'message' (string) keys
 */
function submitComplaint($conn, $studentId, $subject, $description, $priority = 'medium', $attachment = null) {
    $errors = [];
    
    // Basic validation
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($description)) $errors[] = "Description is required";
    
    // File upload handling
    $attachment_path = "";
    if ($attachment && isset($attachment['error']) && $attachment['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $file_type = $attachment['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, GIF, and PDF files are allowed";
        } else {
            $upload_dir = "../uploads/complaints/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($attachment['name']);
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($attachment['tmp_name'], $upload_path)) {
                $attachment_path = "uploads/complaints/" . $file_name;
            } else {
                $errors[] = "Failed to upload file";
            }
        }
    }
    
    // Insert complaint into database if no errors
    if (empty($errors)) {
        try {            // Begin transaction
            $conn->begin_transaction();
            
            $stmt = $conn->prepare("INSERT INTO complaints (student_id, subject, description, priority, attachment_path) 
                                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $studentId, $subject, $description, $priority, $attachment_path);
            
            if ($stmt->execute()) {
                $complaint_id = $conn->insert_id;
                
                // Insert into history
                $status = 'pending';
                $stmt = $conn->prepare("INSERT INTO complaint_status_history (complaint_id, status) VALUES (?, ?)");
                $stmt->bind_param("is", $complaint_id, $status);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                return [
                    'success' => true,
                    'message' => "Complaint submitted successfully! Your complaint ID is #" . $complaint_id,
                    'complaint_id' => $complaint_id
                ];
            } else {
                // Something went wrong, rollback
                $conn->rollback();
                return [
                    'success' => false,
                    'message' => "Failed to submit complaint: " . $conn->error
                ];
            }
        } catch (Exception $e) {
            // An exception occurred, rollback
            $conn->rollback();
            return [
                'success' => false,
                'message' => "Error processing complaint: " . $e->getMessage()
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => implode("<br>", $errors)
        ];
    }
}

/**
 * Add feedback and rating to a resolved complaint
 * 
 * @param mysqli $conn Database connection
 * @param int $complaintId Complaint ID
 * @param int $studentId Student ID (for validation)
 * @param int $rating Rating (1-5)
 * @param string $feedback Feedback text
 * @return array Associative array with 'success' (bool) and 'message' (string) keys
 */
function addComplaintFeedback($conn, $complaintId, $studentId, $rating, $feedback) {
    $errors = [];
    
    // Basic validation
    if (empty($complaintId) || !is_numeric($complaintId)) $errors[] = "Invalid complaint ID";
    if ($rating < 1 || $rating > 5) $errors[] = "Rating must be between 1 and 5";
    if (empty($feedback)) $errors[] = "Feedback is required";
    
    // Update complaint with feedback if no errors
    if (empty($errors)) {
        // Check if complaint belongs to student and is in 'resolved' status
        $stmt = $conn->prepare("SELECT id FROM complaints WHERE id = ? AND student_id = ? AND status = 'resolved'");
        $stmt->bind_param("ii", $complaintId, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => "Feedback can only be provided for resolved complaints that belong to you"
            ];
        }
        
        $stmt = $conn->prepare("UPDATE complaints SET rating = ?, feedback = ? WHERE id = ? AND student_id = ?");
        $stmt->bind_param("isii", $rating, $feedback, $complaintId, $studentId);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => "Thank you for your feedback!"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Failed to submit feedback: Complaint not found or not yours"
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => "Failed to submit feedback: " . $conn->error
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => implode("<br>", $errors)
        ];
    }
}

/**
 * Get all complaints for a student
 * 
 * @param mysqli $conn Database connection
 * @param int $studentId Student ID
 * @return array Array of complaints data
 */
function getStudentComplaints($conn, $studentId) {
    $complaints = [];
    
    $stmt = $conn->prepare("SELECT c.*, 
                           (SELECT COUNT(*) FROM complaint_status_history WHERE complaint_id = c.id) as updates_count
                           FROM complaints c 
                           WHERE c.student_id = ? 
                           ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
    
    return $complaints;
}

/**
 * Get complaint details including status history
 * 
 * @param mysqli $conn Database connection
 * @param int $complaintId Complaint ID
 * @param int $studentId Student ID for validation
 * @return array|false Complaint data or false if not found/not authorized
 */
function getComplaintDetails($conn, $complaintId, $studentId) {
    // Get complaint details
    $stmt = $conn->prepare("SELECT c.*, s.name as student_name, 
                           (SELECT name FROM admins WHERE id = c.resolved_by) as resolved_by_name
                           FROM complaints c
                           JOIN students s ON c.student_id = s.id
                           WHERE c.id = ? AND c.student_id = ?");
    $stmt->bind_param("ii", $complaintId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $complaint = $result->fetch_assoc();
    
    // Get complaint status history
    $stmt = $conn->prepare("SELECT csh.*, 
                          (SELECT name FROM admins WHERE id = csh.changed_by) as changed_by_name
                          FROM complaint_status_history csh
                          WHERE csh.complaint_id = ?
                          ORDER BY csh.created_at ASC");
    $stmt->bind_param("i", $complaintId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $status_history = [];
    while ($row = $result->fetch_assoc()) {
        $status_history[] = $row;
    }
    
    $complaint['status_history'] = $status_history;
    
    return $complaint;
}

/**
 * Submit a new service request (maintenance, checkout, room exchange)
 * 
 * @param mysqli $conn Database connection
 * @param int $studentId Student ID
 * @param string $requestType Type of request
 * @param string $subject Request subject
 * @param string $description Request description
 * @param int $roomId Current room ID
 * @param string|null $preferredDate Preferred date for service (optional)
 * @param string|null $preferredTimeSlot Preferred time slot (optional)
 * @param int|null $newRoomId New room ID (for room exchange requests)
 * @param array|null $attachment File upload data from $_FILES
 * @return array Associative array with 'success' (bool) and 'message' (string) keys
 */
function submitServiceRequest($conn, $studentId, $requestType, $subject, $description, $roomId, 
                             $preferredDate = null, $preferredTimeSlot = null, $newRoomId = null, $attachment = null) {
    $errors = [];
    
    // Basic validation
    if (empty($requestType)) $errors[] = "Request type is required";
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($description)) $errors[] = "Description is required";
    
    // Additional validation based on request type
    if ($requestType === 'room_exchange' && empty($newRoomId)) {
        $errors[] = "New room must be selected for room exchange requests";
    }
    
    if (($requestType === 'maintenance' || $requestType === 'room_cleaning') 
        && (empty($preferredDate) || empty($preferredTimeSlot))) {
        $errors[] = "Preferred date and time slot are required for maintenance and room cleaning requests";
    }
    
    // File upload handling
    $attachment_path = "";
    if ($attachment && isset($attachment['error']) && $attachment['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $file_type = $attachment['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, GIF, and PDF files are allowed";
        } else {
            $upload_dir = "../uploads/requests/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($attachment['name']);
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($attachment['tmp_name'], $upload_path)) {
                $attachment_path = "uploads/requests/" . $file_name;
            } else {
                $errors[] = "Failed to upload file";
            }
        }
    }
    
    // Insert request into database if no errors
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->begin_transaction();
            
            $stmt = $conn->prepare("INSERT INTO service_requests (
                student_id, request_type, subject, description, preferred_date, 
                preferred_time_slot, attachment_path, room_id, new_room_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param(
                "issssssii", 
                $studentId, $requestType, $subject, $description, $preferredDate,
                $preferredTimeSlot, $attachment_path, $roomId, $newRoomId
            );
            
            if ($stmt->execute()) {
                $request_id = $conn->insert_id;
                
                // Insert into history
                $status = 'pending';
                $stmt = $conn->prepare("INSERT INTO request_status_history (request_id, status) VALUES (?, ?)");
                $stmt->bind_param("is", $request_id, $status);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                return [
                    'success' => true,
                    'message' => "Service request submitted successfully! Your request ID is #" . $request_id,
                    'request_id' => $request_id
                ];
            } else {
                // Something went wrong, rollback
                $conn->rollback();
                return [
                    'success' => false,
                    'message' => "Failed to submit request: " . $conn->error
                ];
            }
        } catch (Exception $e) {
            // An exception occurred, rollback
            $conn->rollback();
            return [
                'success' => false,
                'message' => "Error processing request: " . $e->getMessage()
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => implode("<br>", $errors)
        ];
    }
}

/**
 * Cancel a service request
 * 
 * @param mysqli $conn Database connection
 * @param int $requestId Request ID
 * @param int $studentId Student ID for validation
 * @return array Associative array with 'success' (bool) and 'message' (string) keys
 */
function cancelServiceRequest($conn, $requestId, $studentId) {
    // Validate request ID
    if (empty($requestId) || !is_numeric($requestId)) {
        return [
            'success' => false,
            'message' => "Invalid request ID"
        ];
    }
    
    // Check if request belongs to student and is cancellable
    $stmt = $conn->prepare("SELECT id FROM service_requests 
                           WHERE id = ? AND student_id = ? AND status IN ('pending', 'approved')");
    $stmt->bind_param("ii", $requestId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => "Request not found or cannot be cancelled"
        ];
    }
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Update request status
        $status = 'cancelled';
        $stmt = $conn->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $requestId);
        $stmt->execute();
        
        // Add to history
        $comments = "Cancelled by student";
        $stmt = $conn->prepare("INSERT INTO request_status_history (request_id, status, comments) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $requestId, $status, $comments);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Request #" . $requestId . " has been cancelled successfully"
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => "Error cancelling request: " . $e->getMessage()
        ];
    }
}

/**
 * Get all service requests for a student
 * 
 * @param mysqli $conn Database connection
 * @param int $studentId Student ID
 * @return array Array of service requests data
 */
function getStudentRequests($conn, $studentId) {
    $requests = [];
    
    $stmt = $conn->prepare("SELECT sr.*, 
                           (SELECT COUNT(*) FROM request_status_history WHERE request_id = sr.id) as updates_count,
                           r.room_number, hb.block_name as block
                           FROM service_requests sr
                           LEFT JOIN rooms r ON sr.room_id = r.id
                           LEFT JOIN hostel_blocks hb ON r.block_id = hb.id
                           WHERE sr.student_id = ? 
                           ORDER BY sr.created_at DESC");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    return $requests;
}

/**
 * Get service request details including status history
 * 
 * @param mysqli $conn Database connection
 * @param int $requestId Request ID
 * @param int $studentId Student ID for validation
 * @return array|false Request data or false if not found/not authorized
 */
function getRequestDetails($conn, $requestId, $studentId) {
    // Get request details
    $stmt = $conn->prepare("SELECT sr.*, 
                          r.room_number, hb_current.block_name as block,
                          nr.room_number as new_room_number, hb_new.block_name as new_room_block,
                          s.name as student_name,
                          (SELECT name FROM admins WHERE id = sr.handled_by) as handled_by_name
                          FROM service_requests sr
                          JOIN students s ON sr.student_id = s.id
                          LEFT JOIN rooms r ON sr.room_id = r.id
                          LEFT JOIN hostel_blocks hb_current ON r.block_id = hb_current.id
                          LEFT JOIN rooms nr ON sr.new_room_id = nr.id
                          LEFT JOIN hostel_blocks hb_new ON nr.block_id = hb_new.id
                          WHERE sr.id = ? AND sr.student_id = ?");
    $stmt->bind_param("ii", $requestId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $request = $result->fetch_assoc();
    
    // Get request status history
    $stmt = $conn->prepare("SELECT rsh.*, 
                          (SELECT name FROM admins WHERE id = rsh.changed_by) as changed_by_name
                          FROM request_status_history rsh
                          WHERE rsh.request_id = ?
                          ORDER BY rsh.created_at ASC");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $status_history = [];
    while ($row = $result->fetch_assoc()) {
        $status_history[] = $row;
    }
    
    $request['status_history'] = $status_history;
    
    // Get maintenance assignments if applicable
    if ($request['request_type'] === 'maintenance' || $request['request_type'] === 'room_cleaning') {
        $stmt = $conn->prepare("SELECT ma.*, ms.name as staff_name, ms.position, ms.department
                              FROM maintenance_assignments ma
                              JOIN maintenance_staff ms ON ma.staff_id = ms.id
                              WHERE ma.request_id = ?
                              ORDER BY ma.assigned_date DESC");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $maintenance_assignments = [];
        while ($row = $result->fetch_assoc()) {
            $maintenance_assignments[] = $row;
        }
        
        $request['maintenance_assignments'] = $maintenance_assignments;
    }
    
    return $request;
}

/**
 * Get available rooms for room exchange
 * 
 * @param mysqli $conn Database connection
 * @param int $currentRoomId Current room ID
 * @return array Array of available rooms
 */
function getAvailableRoomsForExchange($conn, $currentRoomId) {
    $available_rooms = [];
    
    if ($currentRoomId > 0) {
        $stmt = $conn->prepare("SELECT r.id, r.room_number, r.block, r.capacity, 
                               (SELECT COUNT(*) FROM student_room_assignments 
                                WHERE room_id = r.id AND status = 'active') as occupied
                               FROM rooms r
                               WHERE r.id != ? AND r.status = 'available'
                               AND (SELECT COUNT(*) FROM student_room_assignments
                                   WHERE room_id = r.id AND status = 'active') < r.capacity
                               ORDER BY r.block, r.room_number");
        $stmt->bind_param("i", $currentRoomId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $available_rooms[] = $row;
        }
    }
    
    return $available_rooms;
}

/**
 * Delete a complaint submitted by a student
 * 
 * @param mysqli $conn Database connection
 * @param int $complaintId Complaint ID
 * @param int $studentId Student ID for validation
 * @return array Associative array with 'success' (bool) and 'message' (string) keys
 */
function deleteComplaint($conn, $complaintId, $studentId) {
    // Validate inputs
    if (empty($complaintId) || !is_numeric($complaintId)) {
        return [
            'success' => false,
            'message' => "Invalid complaint ID"
        ];
    }

    // Check if complaint belongs to student and is in a deletable state
    $stmt = $conn->prepare("SELECT status FROM complaints WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $complaintId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => "Complaint not found or you don't have permission to delete it"
        ];
    }
    
    $complaint = $result->fetch_assoc();
    
    // Only allow deletion if complaint is in pending status
    if ($complaint['status'] !== 'pending') {
        return [
            'success' => false,
            'message' => "Only pending complaints can be deleted"
        ];
    }
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Delete from complaint_status_history first (foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM complaint_status_history WHERE complaint_id = ?");
        $stmt->bind_param("i", $complaintId);
        $stmt->execute();
        
        // Then delete the complaint
        $stmt = $conn->prepare("DELETE FROM complaints WHERE id = ? AND student_id = ?");
        $stmt->bind_param("ii", $complaintId, $studentId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            // Rollback if no rows affected
            $conn->rollback();
            return [
                'success' => false,
                'message' => "Failed to delete complaint"
            ];
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Complaint has been deleted successfully"
        ];
    } catch (Exception $e) {
        // An exception occurred, rollback
        $conn->rollback();
        return [
            'success' => false,
            'message' => "Error deleting complaint: " . $e->getMessage()
        ];
    }
}
