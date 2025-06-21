<?php
// filepath: c:\xampp\htdocs\hostel-management-system\admin\complaints.php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';
require_once 'admin_request_functions.php';

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Com                                        <th>ID</th>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>& Feedback";
$additionalCSS = ["css/dashboard.css", "css/complaints.css"];
$additionalJS = ["js/complaints.js"];

// Initialize variables
$errors = [];
$success = "";
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$filters = [];

// Check if a specific complaint ID is requested for viewing
$viewingComplaint = false;
$complaintDetails = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $complaintId = intval($_GET['id']);
    try {
        $complaintDetails = getAdminComplaintDetails($conn, $complaintId);
        if ($complaintDetails) {
            $viewingComplaint = true;
        } else {
            $errors[] = "Complaint #$complaintId not found.";
        }
    } catch (Exception $e) {
        $errors[] = "Error retrieving complaint details: " . $e->getMessage();
    }
}

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
    $errors[] = "Admin information not found.";
}

// Process filters from GET parameters
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

if (isset($_GET['priority']) && !empty($_GET['priority'])) {
    $filters['priority'] = $_GET['priority'];
}

// Removed complaint_type filter as it no longer exists in the database

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get complaints list
$result = getAdminComplaints($conn, $filters, $page, $limit);
$complaints = $result['complaints'];
$pagination = $result['pagination'];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once 'sidebar-admin.php';
?>

<!-- Main Content -->
<div class="main-content">
    <?php 
    $pageHeading = "Complaints & Feedback Management";
    require_once 'admin-content-header.php'; 
    ?>      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <p>If you're experiencing database table errors, please <a href="fix_complaints_view.php" class="alert-link">run the database fix script</a>.</p>
        </div>
    <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
      <!-- Admin Tools Section -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong>Complaint Management</strong>
        </div>
        <div class="card-body">
            <p>Use this tool to update complaint status directly:</p>
            <div>
                <a href="direct_status_update.php" class="btn btn-primary" target="_blank">
                    <i class="fas fa-tools"></i> Update Complaint Status
                </a>
            </div>
        </div>
    </div>
    
    <?php if ($viewingComplaint && $complaintDetails): ?>
        <!-- Display complaint details -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Complaint #<?= $complaintDetails['id'] ?> Details</h3>
                <a href="complaints.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to All Complaints
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Basic Information</h4>
                        <table class="table table-striped">
                            <tr>
                                <th>Subject:</th>
                                <td><?= htmlspecialchars($complaintDetails['subject']) ?></td>
                            </tr>
                            <tr>
                                <th>Student:</th>
                                <td><?= htmlspecialchars($complaintDetails['student_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Contact:</th>
                                <td><?= htmlspecialchars($complaintDetails['contact_no']) ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?= htmlspecialchars($complaintDetails['email']) ?></td>
                            </tr>
                            <tr>
                                <th>Room:</th>
                                <td><?= $complaintDetails['room_number'] ? htmlspecialchars($complaintDetails['room_number']) : 'N/A' ?></td>
                            </tr>
                            <tr>
                                <th>Block:</th>
                                <td><?= $complaintDetails['block'] ? htmlspecialchars($complaintDetails['block']) : 'N/A' ?></td>
                            </tr>                            <!-- Type row removed as it's no longer in the database -->
                            <tr>
                                <th>Priority:</th>
                                <td>
                                    <?php
                                    $priorityClass = '';
                                    switch($complaintDetails['priority']) {
                                        case 'low': $priorityClass = 'status-resolved'; break;
                                        case 'medium': $priorityClass = 'status-in-progress'; break;
                                        case 'high': $priorityClass = 'status-pending'; break;
                                        case 'urgent': $priorityClass = 'status-urgent'; break;
                                    }
                                    ?>
                                    <span class="status <?= $priorityClass ?>">
                                        <?= htmlspecialchars(ucfirst($complaintDetails['priority'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Current Status:</th>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    switch($complaintDetails['status']) {
                                        case 'pending': $statusClass = 'status-pending'; break;
                                        case 'in_progress': $statusClass = 'status-in-progress'; break;
                                        case 'resolved': $statusClass = 'status-resolved'; break;
                                        case 'closed': $statusClass = 'status-closed'; break;
                                    }
                                    ?>
                                    <span class="status <?= $statusClass ?>">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $complaintDetails['status']))) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Date Submitted:</th>
                                <td><?= date('M d, Y h:i A', strtotime($complaintDetails['created_at'])) ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h4>Description</h4>
                        <div class="complaint-description">
                            <?= nl2br(htmlspecialchars($complaintDetails['description'])) ?>
                        </div>
                        
                        <?php if (!empty($complaintDetails['attachment'])): ?>
                        <h4 class="mt-4">Attachment</h4>
                        <div class="complaint-attachment mb-4">
                            <?php
                            $fileExtension = pathinfo($complaintDetails['attachment'], PATHINFO_EXTENSION);
                            $isImage = in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif']);
                            
                            if ($isImage):
                            ?>
                                <img src="../<?= htmlspecialchars($complaintDetails['attachment']) ?>" 
                                     class="img-fluid complaint-image" 
                                     alt="Complaint Attachment">
                            <?php else: ?>
                                <a href="../<?= htmlspecialchars($complaintDetails['attachment']) ?>" 
                                   class="btn btn-primary" target="_blank">
                                    <i class="fas fa-file"></i> Download Attachment
                                </a>
                            <?php endif; ?>
                        </div>                        <?php endif; ?>
                        
                        <!-- Status Update Link -->
                        <div class="alert alert-info mt-4">
                            <h5>Need to update this complaint status?</h5>
                            <p>Use our direct status update tool for reliable status updates:</p>
                            <a href="direct_status_update.php?complaint_id=<?= $complaintDetails['id'] ?>" class="btn btn-primary" target="_blank">
                                <i class="fas fa-edit"></i> Update Status for Complaint #<?= $complaintDetails['id'] ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Status History -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h4>Status History</h4>
                        <?php if (!empty($complaintDetails['status_history'])): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Changed By</th>
                                        <th>Status</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complaintDetails['status_history'] as $history): ?>
                                    <tr>
                                        <td><?= date('M d, Y h:i A', strtotime($history['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($history['changed_by_name'] ?? 'System') ?></td>
                                        <td>
                                            <span class="status status-<?= strtolower($history['status']) ?>">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $history['status']))) ?>
                                            </span>
                                        </td>
                                        <td><?= nl2br(htmlspecialchars($history['comments'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No status history available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Stats Overview -->
        <div class="stat-cards">
            <?php
            // Get counts for different statuses
            $pending_count = 0;
            $in_progress_count = 0;
            $resolved_count = 0;
            $urgent_count = 0;
            
            if (!empty($complaints)) {
                foreach ($complaints as $c) {
                    if ($c['status'] === 'pending') $pending_count++;
                    if ($c['status'] === 'in_progress') $in_progress_count++;
                    if ($c['status'] === 'resolved') $resolved_count++;
                    if ($c['priority'] === 'urgent') $urgent_count++;
                }
            }
            ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $pending_count ?></h3>
                    <p>Pending Complaints</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $in_progress_count ?></h3>
                    <p>In Progress</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $resolved_count ?></h3>
                    <p>Resolved</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $urgent_count ?></h3>
                    <p>Urgent Complaints</p>
                </div>
            </div>
        </div>
        
        <!-- Complaints List View -->
        <div class="complaints-list-container">
            <div class="card">
                <div class="card-header">                        
                    <div class="card-title-area">
                        <div class="card-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h2 class="card-title">All Student Complaints</h2>
                    </div>
                    <div class="card-actions">
                        <a href="complaints.php" <?= empty($_GET) || (count($_GET) === 1 && isset($_GET['page'])) ? 'style="display:none"' : '' ?>>
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </div>
                <div class="card-body">                    
                    <div class="filter-form">
                        <div class="filter-header">
                            <h4 class="filter-title">Filter Complaints</h4>
                        </div>
                        <form method="get" action="">
                            <div class="filter-controls">
                                <div class="filter-control">
                                    <label for="status" class="filter-label">Status:</label>
                                    <select class="filter-select" id="status" name="status">
                                        <option value="">All</option>
                                        <option value="pending" <?= isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="in_progress" <?= isset($_GET['status']) && $_GET['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="resolved" <?= isset($_GET['status']) && $_GET['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                        <option value="closed" <?= isset($_GET['status']) && $_GET['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                    </select>
                                </div>
                                <div class="filter-control">
                                    <label for="priority" class="filter-label">Priority:</label>
                                    <select class="filter-select" id="priority" name="priority">
                                        <option value="">All</option>
                                        <option value="urgent" <?= isset($_GET['priority']) && $_GET['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                        <option value="high" <?= isset($_GET['priority']) && $_GET['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                                        <option value="medium" <?= isset($_GET['priority']) && $_GET['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                                        <option value="low" <?= isset($_GET['priority']) && $_GET['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                                    </select>
                                </div><div class="search-form">
                                    <input type="text" name="search" class="search-input" placeholder="Search by ID, subject or description..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                </div>
                                <button type="submit" class="filter-btn apply-filters-btn">
                                    <i class="fas fa-search"></i> Search & Filter
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <?php if (empty($complaints)): ?>
                        <div class="no-data-container">
                            <div class="no-data-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h3>No Complaints Found</h3>
                            <p>There are no complaints matching your current filters</p>
                        </div>
                    <?php else: ?>
                        <div class="complaints-table-container">
                            <table class="complaints-table">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td><?= $c['id'] ?></td>
                                        <td><?= htmlspecialchars($c['student_name']) ?></td>                                        <td><?= htmlspecialchars($c['subject']) ?></td>
                                        <td>
                                            <?php
                                            $priorityClass = '';
                                            switch($c['priority']) {
                                                case 'low': $priorityClass = 'status-resolved'; break;
                                                case 'medium': $priorityClass = 'status-in-progress'; break;
                                                case 'high': $priorityClass = 'status-pending'; break;
                                                case 'urgent': $priorityClass = 'status-urgent'; break;
                                            }
                                            ?>
                                            <span class="status <?= $priorityClass ?>">
                                                <?= htmlspecialchars(ucfirst($c['priority'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch($c['status']) {
                                                case 'pending': $statusClass = 'status-pending'; break;
                                                case 'in_progress': $statusClass = 'status-in-progress'; break;
                                                case 'resolved': $statusClass = 'status-resolved'; break;
                                                case 'closed': $statusClass = 'status-closed'; break;
                                            }
                                            ?>
                                            <span class="status <?= $statusClass ?>">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $c['status']))) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>                                    <td class="action-buttons">                                        <!-- View button - direct link instead of modal -->
                                        <a href="complaints.php?id=<?= $c['id'] ?>" 
                                           class="action-btn view-btn"
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                      <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($pagination['total_pages'], $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $pagination['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Complaint Detail Modal - Compatible with both Bootstrap 4 and 5 -->
<div class="modal fade" id="complaintDetailModal" tabindex="-1" aria-labelledby="complaintModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="complaintModalTitle">Complaint Details</h5>
                <!-- Support both Bootstrap 4 and 5 close button styles -->
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="complaintModalContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center p-5">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p class="mt-3">Loading complaint details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <!-- Support both Bootstrap 4 and 5 dismiss attributes -->
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for quick status updates -->
<form id="quick-update-form" style="display: none;">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" id="quick_complaint_id" name="complaint_id" value="">
    <input type="hidden" id="quick_new_status" name="new_status" value="">
    <textarea id="quick_comments" name="comments" style="display: none;"></textarea>
</form>

<!-- Notification Container -->
<div id="notificationContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

<!-- Simple script for basic page functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Complaints page loaded - Status update now using direct tool only');
});
</script>
</script>

<?php 
endif; // End of else statement for $viewingComplaint check
require_once '../shared/includes/footer.php'; 
?>