<?php
// filepath: c:\xampp\htdocs\hostel-management-system\admin\complaints.php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';
require_once '../shared/includes/admin_request_functions.php';

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Complaints & Feedback";
$additionalCSS = ["css/dashboard.css", "css/complaints.css"];

// Initialize variables
$errors = [];
$success = "";
$complaint = null;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$filters = [];

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

// Process status update if form submitted
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $complaintId = isset($_POST['complaint_id']) ? intval($_POST['complaint_id']) : 0;
    $newStatus = $_POST['new_status'] ?? '';
    $comments = $_POST['comments'] ?? '';
    
    if ($complaintId > 0 && !empty($newStatus)) {
        $result = updateComplaintStatus($conn, $complaintId, $newStatus, $adminId, $comments);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    } else {
        $errors[] = "Missing required information to update status.";
    }
}

// Get complaint details if ID provided in URL
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $complaintId = intval($_GET['id']);
    $complaint = getAdminComplaintDetails($conn, $complaintId);
    
    if (!$complaint) {
        $errors[] = "Complaint not found.";
    }
}

// Apply filters from GET parameters
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

if (isset($_GET['priority']) && !empty($_GET['priority'])) {
    $filters['priority'] = $_GET['priority'];
}

if (isset($_GET['complaint_type']) && !empty($_GET['complaint_type'])) {
    $filters['complaint_type'] = $_GET['complaint_type'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get complaints list if not viewing a specific complaint
if (!$complaint) {
    $result = getAdminComplaints($conn, $filters, $page, $limit);
    $complaints = $result['complaints'];
    $pagination = $result['pagination'];
}

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once '../shared/includes/sidebar-admin.php';
?>

<!-- Main Content -->
<div class="main-content">
    <?php 
    $pageHeading = "Complaints & Feedback Management";
    require_once '../shared/includes/admin-content-header.php'; 
    ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$complaint): // Only show stat cards on list view ?>
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
    <?php endif; ?>
    
    <?php if ($complaint): ?>        <!-- Complaint Detail View -->
        <div class="complaint-details-container">
            <div class="card">
                <div class="card-header">
                    <div class="card-title-area">
                        <div class="card-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h2 class="card-title">Complaint #<?= $complaint['id'] ?></h2>
                    </div>
                    <div class="card-actions">
                        <a href="complaints.php">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">                    <div class="row">
                        <div class="col-md-6">
                            <h4>Complaint Information</h4>
                            <table class="data-table">
                                <tr>
                                    <th>Subject</th>
                                    <td><?= htmlspecialchars($complaint['subject']) ?></td>
                                </tr>
                                <tr>
                                    <th>Type</th>
                                    <td>
                                        <span class="status status-in-progress">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $complaint['complaint_type']))) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Priority</th>
                                    <td>
                                        <?php
                                        $priorityClass = '';
                                        switch($complaint['priority']) {
                                            case 'low': $priorityClass = 'status-resolved'; break;
                                            case 'medium': $priorityClass = 'status-in-progress'; break;
                                            case 'high': $priorityClass = 'status-pending'; break;
                                            case 'urgent': $priorityClass = 'status-urgent'; break;
                                        }
                                        ?>
                                        <span class="status <?= $priorityClass ?>">
                                            <?= htmlspecialchars(ucfirst($complaint['priority'])) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch($complaint['status']) {
                                            case 'pending': $statusClass = 'status-pending'; break;
                                            case 'in_progress': $statusClass = 'status-in-progress'; break;
                                            case 'resolved': $statusClass = 'status-resolved'; break;
                                            case 'closed': $statusClass = 'status-closed'; break;
                                        }
                                        ?>
                                        <span class="status <?= $statusClass ?>">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $complaint['status']))) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date Submitted</th>
                                    <td><?= date('M d, Y h:i A', strtotime($complaint['created_at'])) ?></td>
                                </tr>
                            </table>
                        </div>                        <div class="col-md-6">
                            <h4>Student Information</h4>
                            <table class="data-table">
                                <tr>
                                    <th>Name</th>
                                    <td><?= htmlspecialchars($complaint['student_name']) ?></td>
                                </tr>                                <tr>
                                    <th>Contact</th>
                                    <td><?= htmlspecialchars($complaint['contact_no']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($complaint['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Room</th>
                                    <td>
                                        <?php if (!empty($complaint['room_number'])): ?>
                                            <span class="status status-in-progress">
                                                Block <?= htmlspecialchars($complaint['block']) ?>, 
                                                Room <?= htmlspecialchars($complaint['room_number']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status status-pending">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                      <div class="row mt-4">
                        <div class="col-12">
                            <h4>Description</h4>
                            <div class="complaint-description">
                                <?= nl2br(htmlspecialchars($complaint['description'])) ?>
                            </div>
                        </div>
                    </div>
                      <?php if (!empty($complaint['attachment_path'])): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title-area">
                                        <div class="card-icon">
                                            <i class="fas fa-paperclip"></i>
                                        </div>
                                        <h2 class="card-title">Attachment</h2>
                                    </div>
                                    <div class="card-actions">
                                        <a href="../<?= htmlspecialchars($complaint['attachment_path']) ?>" target="_blank">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="attachment-container">
                                        <?php
                                        $ext = pathinfo($complaint['attachment_path'], PATHINFO_EXTENSION);
                                        $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif']);
                                        
                                        if ($isImage):
                                        ?>
                                            <a href="../<?= htmlspecialchars($complaint['attachment_path']) ?>" target="_blank">
                                                <img src="../<?= htmlspecialchars($complaint['attachment_path']) ?>" class="img-thumbnail" style="max-height: 200px;" alt="Attachment">
                                            </a>
                                        <?php else: ?>
                                            <div class="document-preview">
                                                <i class="fas fa-file-<?= strtolower($ext) === 'pdf' ? 'pdf' : (strtolower($ext) === 'doc' || strtolower($ext) === 'docx' ? 'word' : 'alt') ?> fa-3x"></i>
                                                <p>Click to view document</p>
                                            </div>
                                            <a href="../<?= htmlspecialchars($complaint['attachment_path']) ?>" class="filter-btn mt-3" target="_blank">
                                                <i class="fas fa-eye"></i> View Document
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                      <?php if (!empty($complaint['feedback'])): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title-area">
                                        <div class="card-icon">
                                            <i class="fas fa-comment-dots"></i>
                                        </div>
                                        <h2 class="card-title">Student Feedback</h2>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="rating mb-3">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $complaint['rating'] ? 'text-warning' : 'text-muted' ?>" style="font-size: 20px;"></i>
                                        <?php endfor; ?>
                                        <span class="ms-2" style="font-weight: 500;"><?= $complaint['rating'] ?>/5</span>
                                    </div>
                                    <div class="complaint-description">
                                        <?= nl2br(htmlspecialchars($complaint['feedback'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                      <!-- Status History -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title-area">
                                        <div class="card-icon">
                                            <i class="fas fa-history"></i>
                                        </div>
                                        <h2 class="card-title">Status History</h2>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="status-timeline">
                                        <?php foreach ($complaint['status_history'] as $history): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-icon">
                                                <?php
                                                $iconClass = '';
                                                switch($history['status']) {
                                                    case 'pending': $iconClass = 'fa-clock text-warning'; break;
                                                    case 'in_progress': $iconClass = 'fa-tools text-info'; break;
                                                    case 'resolved': $iconClass = 'fa-check-circle text-success'; break;
                                                    case 'closed': $iconClass = 'fa-times-circle text-secondary'; break;
                                                }
                                                ?>
                                                <i class="fas <?= $iconClass ?>"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <h5>
                                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $history['status']))) ?>
                                                    <span class="small text-muted">
                                                        - <?= date('M d, Y h:i A', strtotime($history['created_at'])) ?>
                                                    </span>
                                                </h5>
                                                <?php if (!empty($history['changed_by_name'])): ?>
                                                    <p class="small">By: <?= htmlspecialchars($history['changed_by_name']) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($history['comments'])): ?>
                                                    <p><?= nl2br(htmlspecialchars($history['comments'])) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                      <!-- Update Status Form -->
                    <?php if ($complaint['status'] !== 'closed'): ?>
                    <div class="row mt-4" id="update-status">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title-area">
                                        <div class="card-icon">
                                            <i class="fas fa-tasks"></i>
                                        </div>
                                        <h2 class="card-title">Update Status</h2>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label for="new_status" class="form-label">New Status</label>
                                            <select class="form-select" id="new_status" name="new_status" required>
                                                <option value="">-- Select Status --</option>
                                                <?php if ($complaint['status'] === 'pending'): ?>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="resolved">Resolved</option>
                                                    <option value="closed">Closed</option>
                                                <?php elseif ($complaint['status'] === 'in_progress'): ?>
                                                    <option value="resolved">Resolved</option>
                                                    <option value="closed">Closed</option>
                                                <?php elseif ($complaint['status'] === 'resolved'): ?>
                                                    <option value="closed">Closed</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="comments" class="form-label">Comments</label>
                                            <textarea class="form-control" id="comments" name="comments" rows="3" required placeholder="Please provide details about this status update"></textarea>
                                        </div>
                                        
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="filter-btn">
                                                <i class="fas fa-save"></i> Update Status
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>        <!-- Complaints List View -->
        <div class="complaints-list-container">
            <div class="card">
                <div class="card-header">                        <div class="card-title-area">
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
                <div class="card-body">                    <div class="filter-form">
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
                                </div>
                                <div class="filter-control">
                                    <label for="complaint_type" class="filter-label">Type:</label>
                                    <select class="filter-select" id="complaint_type" name="complaint_type">
                                        <option value="">All</option>
                                        <option value="maintenance" <?= isset($_GET['complaint_type']) && $_GET['complaint_type'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                        <option value="cleanliness" <?= isset($_GET['complaint_type']) && $_GET['complaint_type'] === 'cleanliness' ? 'selected' : '' ?>>Cleanliness</option>
                                        <option value="roommate" <?= isset($_GET['complaint_type']) && $_GET['complaint_type'] === 'roommate' ? 'selected' : '' ?>>Roommate</option>
                                        <option value="security" <?= isset($_GET['complaint_type']) && $_GET['complaint_type'] === 'security' ? 'selected' : '' ?>>Security</option>
                                        <option value="noise" <?= isset($_GET['complaint_type']) && $_GET['complaint_type'] === 'noise' ? 'selected' : '' ?>>Noise</option>
                                        <option value="other" <?= isset($_GET['complaint_type']) && $_GET['complaint_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>                                <div class="search-form">
                                    <input type="text" name="search" class="search-input" placeholder="Search by ID, subject or type..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                </div>                                <button type="submit" class="filter-btn apply-filters-btn">
                                    <i class="fas fa-search"></i> Search & Filter
                                </button>
                            </div>
                        </form>
                    </div><?php if (empty($complaints)): ?>
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
                                        <th>Type</th>
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
                                        <td><?= htmlspecialchars($c['student_name']) ?></td>
                                        <td><?= htmlspecialchars($c['subject']) ?></td>                                        <td>
                                            <span class="status status-in-progress">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $c['complaint_type']))) ?>
                                            </span>
                                        </td><td>
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
                                        </td>                                        <td>
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
                                        <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                        <td class="action-buttons">
                                            <a href="complaints.php?id=<?= $c['id'] ?>" title="View Details" class="action-btn view-btn">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($c['status'] === 'pending'): ?>
                                            <a href="complaints.php?id=<?= $c['id'] ?>#update-status" title="Update Status" class="action-btn edit-btn">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
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
    <?php endif; ?>
</div>


<?php require_once '../shared/includes/footer.php'; ?>