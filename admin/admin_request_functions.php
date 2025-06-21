<?php
/**
 * Admin functions for handling student complaints, feedback, and service requests
 * in the Hostel Management System
 */

/**
 * Get all complaints with optional filters
 * 
 * @param mysqli $conn Database connection
 * @param array $filters Optional filters (status, priority, type, student_id, etc.)
 * @param int $page Page number for pagination
 * @param int $limit Items per page
 * @return array Array of complaints data and pagination info
 */
function getAdminComplaints($conn, $filters = [], $page = 1, $limit = 10) {
    $complaints = [];
    $total = 0;
    
    // Build WHERE clause from filters
    $whereClause = [];
    $params = [];
    $types = "";
    
    if (!empty($filters['status'])) {
        $whereClause[] = "c.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (!empty($filters['priority'])) {
        $whereClause[] = "c.priority = ?";
        $params[] = $filters['priority'];
        $types .= "s";
    }
      // Removed complaint_type filter as it no longer exists in the database
    
    if (!empty($filters['student_id'])) {
        $whereClause[] = "c.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= "i";
    }
      if (!empty($filters['search'])) {
        $search = "%" . $filters['search'] . "%";
        $whereClause[] = "(c.id LIKE ? OR c.subject LIKE ? OR c.description LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "sss";
    }
      // Calculate offset for pagination
    $offset = ($page - 1) * $limit;
    
    // Prepare base query
    $query = "SELECT c.*, s.name as student_name, s.contact_no, 
              (SELECT COUNT(*) FROM complaint_status_history WHERE complaint_id = c.id) as updates_count
              FROM complaints c
              JOIN students s ON c.student_id = s.id";
    
    // Add WHERE clause if filters exist
    if (!empty($whereClause)) {
        $query .= " WHERE " . implode(" AND ", $whereClause);
    }
      // Get total count for pagination
    $countQuery = str_replace("c.*, s.name as student_name, s.contact_no, 
              (SELECT COUNT(*) FROM complaint_status_history WHERE complaint_id = c.id) as updates_count", 
              "COUNT(*) as total", $query);
              
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total = $row['total'];
    }
    
    // Add ORDER BY and LIMIT for main query
    $query .= " ORDER BY 
                CASE 
                    WHEN c.status = 'pending' AND c.priority = 'urgent' THEN 1
                    WHEN c.status = 'pending' AND c.priority = 'high' THEN 2
                    WHEN c.status = 'pending' AND c.priority = 'medium' THEN 3
                    WHEN c.status = 'pending' AND c.priority = 'low' THEN 4
                    WHEN c.status = 'in_progress' THEN 5
                    WHEN c.status = 'resolved' THEN 6
                    ELSE 7
                END,
                c.created_at DESC
                LIMIT ? OFFSET ?";
    
    // Add limit and offset parameters
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
    
    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    
    return [
        'complaints' => $complaints,
        'pagination' => [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => $totalPages
        ]
    ];
}

/**
 * Get detailed complaint information for admin
 * 
 * @param mysqli $conn Database connection
 * @param int $complaintId Complaint ID
 * @return array|false Complaint data or false if not found
 */
function getAdminComplaintDetails($conn, $complaintId) {    
    // Simplest possible query that doesn't depend on optional tables
    $stmt = $conn->prepare("SELECT c.*, s.name as student_name, s.contact_no, s.email,
                          NULL as room_number, NULL as block,
                          (SELECT name FROM admins WHERE id = c.resolved_by) as resolved_by_name
                          FROM complaints c
                          JOIN students s ON c.student_id = s.id
                          WHERE c.id = ?");$stmt->bind_param("i", $complaintId);
    
    try {
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $complaint = $result->fetch_assoc();
    } catch (Exception $e) {
        // Log the error
        error_log("Error in getAdminComplaintDetails: " . $e->getMessage());
        
        // Fallback query with minimal fields if the first query failed
        $fallbackStmt = $conn->prepare("SELECT c.*, s.name as student_name, s.contact_no, s.email
                                        FROM complaints c
                                        JOIN students s ON c.student_id = s.id
                                        WHERE c.id = ?");
        $fallbackStmt->bind_param("i", $complaintId);
        $fallbackStmt->execute();
        $fallbackResult = $fallbackStmt->get_result();
        
        if ($fallbackResult->num_rows === 0) {
            return false;
        }
        
        $complaint = $fallbackResult->fetch_assoc();
        
        // Add empty fields that might be expected
        $complaint['room_number'] = null;
        $complaint['block'] = null;
        $complaint['resolved_by_name'] = null;
    }// Get complaint status history
    $status_history = [];
    
    try {
        // Check if the table exists
        $historyTableExists = false;
        $tablesResult = $conn->query("SHOW TABLES LIKE 'complaint_status_history'");
        if ($tablesResult) {
            $historyTableExists = $tablesResult->num_rows > 0;
        }
        
        if ($historyTableExists) {
            $stmt = $conn->prepare("SELECT csh.*, 
                                  (SELECT name FROM admins WHERE id = csh.changed_by) as changed_by_name
                                  FROM complaint_status_history csh
                                  WHERE csh.complaint_id = ?
                                  ORDER BY csh.created_at ASC");
            $stmt->bind_param("i", $complaintId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $status_history[] = $row;
            }
        } else {
            // If table doesn't exist, create a placeholder history entry
            $status_history[] = [
                'id' => 0,
                'complaint_id' => $complaintId,
                'status' => $complaint['status'],
                'changed_by' => null,
                'changed_by_name' => 'System',
                'comments' => 'Status history not available',
                'created_at' => $complaint['created_at']
            ];
        }
    } catch (Exception $e) {
        // Log error and provide an empty history array
        error_log("Error getting complaint status history: " . $e->getMessage());
        
        // Create a placeholder history entry
        $status_history[] = [
            'id' => 0,
            'complaint_id' => $complaintId,
            'status' => $complaint['status'],
            'changed_by' => null,
            'changed_by_name' => 'System',
            'comments' => 'Status history not available due to an error',
            'created_at' => $complaint['created_at']
        ];
    }
    
    $complaint['status_history'] = $status_history;
    
    return $complaint;
}

/**
 * Update complaint status
 * 
 * @param mysqli $conn Database connection
 * @param int $complaintId Complaint ID
 * @param string $newStatus New status
 * @param int $adminId Admin ID
 * @param string $comments Comments about the status update
 * @return array Associative array with 'success' (bool) and 'message' (string) keys
 */
function updateComplaintStatus($conn, $complaintId, $newStatus, $adminId, $comments = '') {
    // Validate status
    $validStatuses = ['pending', 'in_progress', 'resolved', 'closed'];
    if (!in_array($newStatus, $validStatuses)) {
        return [
            'success' => false,
            'message' => "Invalid status. Status must be one of: " . implode(', ', $validStatuses)
        ];
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update complaint status
        $stmt = $conn->prepare("UPDATE complaints SET status = ?, resolved_by = ? WHERE id = ?");
        $stmt->bind_param("sii", $newStatus, $adminId, $complaintId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Complaint not found or no changes made");
        }
        
        // Add status history entry
        $stmt = $conn->prepare("INSERT INTO complaint_status_history (complaint_id, status, comments, changed_by) 
                               VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $complaintId, $newStatus, $comments, $adminId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Complaint status updated successfully to " . $newStatus
        ];
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        return [
            'success' => false,
            'message' => "Error updating complaint: " . $e->getMessage()
        ];
    }
}

/**
 * Get all service requests with optional filters
 * 
 * @param mysqli $conn Database connection
 * @param array $filters Optional filters (status, type, student_id, etc.)
 * @param int $page Page number for pagination
 * @param int $limit Items per page
 * @return array Array of service requests data and pagination info
 */
function getAdminServiceRequests($conn, $filters = [], $page = 1, $limit = 10) {
    $requests = [];
    $total = 0;
    
    // Build WHERE clause from filters
    $whereClause = [];
    $params = [];
    $types = "";
    
    if (!empty($filters['status'])) {
        $whereClause[] = "sr.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (!empty($filters['request_type'])) {
        $whereClause[] = "sr.request_type = ?";
        $params[] = $filters['request_type'];
        $types .= "s";
    }
    
    if (!empty($filters['student_id'])) {
        $whereClause[] = "sr.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= "i";
    }
    
    if (!empty($filters['search'])) {
        $search = "%" . $filters['search'] . "%";
        $whereClause[] = "(sr.subject LIKE ? OR sr.description LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    // Date range filters
    if (!empty($filters['date_from'])) {
        $whereClause[] = "sr.created_at >= ?";
        $params[] = $filters['date_from'] . ' 00:00:00';
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $whereClause[] = "sr.created_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59';
        $types .= "s";
    }
    
    // Calculate offset for pagination
    $offset = ($page - 1) * $limit;
      // Prepare base query
    $query = "SELECT sr.*, s.name as student_name, s.contact_no, 
              r.room_number, r.block,
              (SELECT COUNT(*) FROM request_status_history WHERE request_id = sr.id) as updates_count
              FROM service_requests sr
              JOIN students s ON sr.student_id = s.id
              LEFT JOIN rooms r ON sr.room_id = r.id";
    
    // Add WHERE clause if filters exist
    if (!empty($whereClause)) {
        $query .= " WHERE " . implode(" AND ", $whereClause);
    }
      // Get total count for pagination
    $countQuery = str_replace("sr.*, s.name as student_name, s.contact_no, 
              r.room_number, r.block,
              (SELECT COUNT(*) FROM request_status_history WHERE request_id = sr.id) as updates_count", 
              "COUNT(*) as total", $query);
              
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total = $row['total'];
    }
    
    // Add ORDER BY and LIMIT for main query
    $query .= " ORDER BY 
                CASE 
                    WHEN sr.status = 'pending' THEN 1
                    WHEN sr.status = 'approved' THEN 2
                    WHEN sr.status = 'in_progress' THEN 3
                    WHEN sr.status = 'completed' THEN 4
                    WHEN sr.status = 'rejected' THEN 5
                    ELSE 6
                END,
                sr.created_at DESC
                LIMIT ? OFFSET ?";
    
    // Add limit and offset parameters
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    
    return [
        'requests' => $requests,
        'pagination' => [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => $totalPages
        ]
    ];
}

/**
 * Get detailed service request information for admin
 * 
 * @param mysqli $conn Database connection
 * @param int $requestId Request ID
 * @return array|false Request data or false if not found
 */
function getAdminRequestDetails($conn, $requestId) {
    // Get request details
    $stmt = $conn->prepare("SELECT sr.*, 
                          s.name as student_name, s.contact_no, s.email,
                          r.room_number, r.block,
                          nr.room_number as new_room_number, nr.block as new_room_block,
                          (SELECT name FROM admins WHERE id = sr.handled_by) as handled_by_name
                          FROM service_requests sr
                          JOIN students s ON sr.student_id = s.id
                          LEFT JOIN rooms r ON sr.room_id = r.id
                          LEFT JOIN rooms nr ON sr.new_room_id = nr.id
                          WHERE sr.id = ?");
    $stmt->bind_param("i", $requestId);
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
 * Update service request status
 * 
 * @param mysqli $conn Database connection
 * @param int $requestId Request ID
 * @param string $newStatus New status
 * @param int $adminId Admin ID
 * @param string $comments Comments about the status update
 * @return array Associative array with 'success' (bool) and 'message' (string) keys
 */
function updateRequestStatus($conn, $requestId, $newStatus, $adminId, $comments = '') {
    // Validate status
    $validStatuses = ['pending', 'approved', 'in_progress', 'completed', 'rejected', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        return [
            'success' => false,
            'message' => "Invalid status. Status must be one of: " . implode(', ', $validStatuses)
        ];
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update request status
        $stmt = $conn->prepare("UPDATE service_requests SET status = ?, handled_by = ? WHERE id = ?");
        $stmt->bind_param("sii", $newStatus, $adminId, $requestId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Request not found or no changes made");
        }
        
        // Add status history entry
        $stmt = $conn->prepare("INSERT INTO request_status_history (request_id, status, comments, changed_by) 
                               VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $requestId, $newStatus, $comments, $adminId);
        $stmt->execute();
        
        // If completed, update completion date
        if ($newStatus === 'completed') {
            $stmt = $conn->prepare("UPDATE service_requests SET completion_date = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
        }
        
        // If rejected, add rejection reason
        if ($newStatus === 'rejected' && !empty($comments)) {
            $stmt = $conn->prepare("UPDATE service_requests SET rejection_reason = ? WHERE id = ?");
            $stmt->bind_param("si", $comments, $requestId);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Request status updated successfully to " . $newStatus
        ];
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        return [
            'success' => false,
            'message' => "Error updating request: " . $e->getMessage()
        ];
    }
}

/**
 * Assign maintenance staff to a request
 * 
 * @param mysqli $conn Database connection
 * @param int $requestId Request ID
 * @param int $staffId Maintenance staff ID
 * @param int $adminId Admin ID who made the assignment
 * @return array Associative array with 'success' (bool) and 'message' (string) keys
 */
function assignMaintenanceStaff($conn, $requestId, $staffId, $adminId) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Verify the request is for maintenance or cleaning
        $stmt = $conn->prepare("SELECT request_type FROM service_requests WHERE id = ?");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Request not found");
        }
        
        $request = $result->fetch_assoc();
        if ($request['request_type'] !== 'maintenance' && $request['request_type'] !== 'room_cleaning') {
            throw new Exception("This request type does not need a maintenance staff assignment");
        }
        
        // Verify the staff exists
        $stmt = $conn->prepare("SELECT id FROM maintenance_staff WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Maintenance staff not found or inactive");
        }
        
        // Create assignment
        $stmt = $conn->prepare("INSERT INTO maintenance_assignments (request_id, staff_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $requestId, $staffId);
        $stmt->execute();
        
        // Update request status to in_progress
        $status = 'in_progress';
        $stmt = $conn->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $requestId);
        $stmt->execute();
        
        // Add status history entry
        $comments = "Maintenance staff assigned";
        $stmt = $conn->prepare("INSERT INTO request_status_history (request_id, status, comments, changed_by) 
                               VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $requestId, $status, $comments, $adminId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Maintenance staff assigned successfully"
        ];
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        return [
            'success' => false,
            'message' => "Error assigning maintenance staff: " . $e->getMessage()
        ];
    }
}

/**
 * Update maintenance assignment status
 * 
 * @param mysqli $conn Database connection
 * @param int $assignmentId Maintenance assignment ID
 * @param string $status New status
 * @param string $notes Completion notes
 * @return array Associative array with 'success' (bool) and 'message' (string) keys
 */
function updateMaintenanceAssignment($conn, $assignmentId, $status, $notes = '') {
    // Validate status
    $validStatuses = ['assigned', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        return [
            'success' => false,
            'message' => "Invalid status. Status must be one of: " . implode(', ', $validStatuses)
        ];
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update assignment status
        $stmt = $conn->prepare("UPDATE maintenance_assignments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $assignmentId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Assignment not found");
        }
        
        // If completed, add completion notes and date
        if ($status === 'completed') {
            $stmt = $conn->prepare("UPDATE maintenance_assignments 
                                   SET completion_notes = ?, completed_at = CURRENT_TIMESTAMP
                                   WHERE id = ?");
            $stmt->bind_param("si", $notes, $assignmentId);
            $stmt->execute();
            
            // Get request ID
            $stmt = $conn->prepare("SELECT request_id FROM maintenance_assignments WHERE id = ?");
            $stmt->bind_param("i", $assignmentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $requestId = $row['request_id'];
            
            // Update service request to completed if all maintenance assignments are completed
            $stmt = $conn->prepare("SELECT COUNT(*) as total, 
                                          SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                                   FROM maintenance_assignments
                                   WHERE request_id = ?");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] === $row['completed']) {
                // All assignments completed, update request status
                $request_status = 'completed';
                $stmt = $conn->prepare("UPDATE service_requests 
                                       SET status = ?, completion_date = CURRENT_TIMESTAMP
                                       WHERE id = ?");
                $stmt->bind_param("si", $request_status, $requestId);
                $stmt->execute();
                
                // Add status history entry
                $comments = "All maintenance tasks completed";
                $stmt = $conn->prepare("INSERT INTO request_status_history (request_id, status, comments) 
                                       VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $requestId, $request_status, $comments);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Maintenance assignment updated successfully to " . $status
        ];
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        return [
            'success' => false,
            'message' => "Error updating maintenance assignment: " . $e->getMessage()
        ];
    }
}
