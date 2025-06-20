<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Complaints, Feedback & Service Requests";
$additionalCSS = ["css/complaints.css"];
$additionalJS = [
    "https://code.jquery.com/jquery-3.6.0.min.js",
    "https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js",
    "https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js",
    "js/complaints.js"  // Add our new complaints JS file
];

// Initialize variables
$errors = [];
$success = "";
$studentId = 0;
$complaints = [];

// Include database connection and functions
require_once '../shared/includes/db_connection.php';
require_once 'request_functions.php';

// Flag to indicate this is the main file
$included_from_main = true;

// Process POST request using the handler
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once 'complaint_handler.php';
} else {
    // Get complaint success/error messages from session (after redirect)
    if (isset($_SESSION['complaint_success'])) {
        $success = $_SESSION['complaint_success'];
        unset($_SESSION['complaint_success']);
    }
    
    if (isset($_SESSION['complaint_errors']) && is_array($_SESSION['complaint_errors'])) {
        $errors = $_SESSION['complaint_errors'];
        unset($_SESSION['complaint_errors']);
    }
}

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

// Fetch complaints for the student using the function
if ($studentId > 0) {
    $complaints = getStudentComplaints($conn, $studentId);
}

// Include header
require_once '../shared/includes/header.php';

// Include student sidebar
require_once '../shared/includes/sidebar-student.php';
?>

<!-- Main Content -->
<div class="main-content">    <div class="header">
        <h1><i class="fas fa-comment-alt"></i> Complaints & Service Requests</h1>
        <p>Submit any issues, complaints, maintenance requests, or other hostel-related concerns here.</p>
    </div>
    
    <div class="complaints-container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Submit New Complaint or Request</h3>
                    </div>
                    <div class="card-body">
                        <form id="complaintForm" action="complaints.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="submit_complaint">
                              <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" placeholder="Brief subject of your complaint" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="priority">Priority</label>
                                <select class="form-control" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" placeholder="Please provide detailed information about your complaint" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="attachment">Attachment (optional)</label>
                                <input type="file" class="form-control-file" id="attachment" name="attachment">
                                <small class="form-text text-muted">You can attach a photo or document (JPG, PNG, GIF, PDF) up to 5MB.</small>
                            </div>                              <button type="submit" class="btn btn-primary btn-block" id="submitComplaintBtn">
                                <i class="fas fa-paper-plane"></i> Submit
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> My Complaints & Requests</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($complaints)): ?>                            <div class="no-complaints-message">
                                <i class="fas fa-info-circle"></i>
                                <p>You haven't submitted any complaints or service requests yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%">ID</th>
                                            <th style="width: 30%">Subject</th>
                                            <th style="width: 15%">Status</th>
                                            <th style="width: 15%">Created</th>
                                            <th style="width: 35%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($complaints as $complaint): ?>
                                            <tr>                                                <td>#<?php echo $complaint['id']; ?></td>
                                                <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = 'badge-info';
                                                    $status_icon = '<i class="fas fa-clock"></i> ';
                                                    
                                                    switch ($complaint['status']) {
                                                        case 'pending':
                                                            $status_class = 'badge-warning';
                                                            break;
                                                        case 'in_progress':
                                                            $status_class = 'badge-info';
                                                            $status_icon = '<i class="fas fa-spinner fa-spin"></i> ';
                                                            break;
                                                        case 'resolved':
                                                            $status_class = 'badge-success';
                                                            $status_icon = '<i class="fas fa-check-circle"></i> ';
                                                            break;
                                                        case 'closed':
                                                            $status_class = 'badge-secondary';
                                                            $status_icon = '<i class="fas fa-lock"></i> ';
                                                            break;
                                                    }
                                                    
                                                    echo '<span class="badge ' . $status_class . '">' . $status_icon . ucfirst(str_replace('_', ' ', $complaint['status'])) . '</span>';
                                                    ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($complaint['created_at'])); ?></td>                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    
                                                    <?php if ($complaint['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-danger" onclick="confirmDeleteComplaint(<?php echo $complaint['id']; ?>)">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($complaint['status'] === 'resolved' && empty($complaint['rating'])): ?>
                                                        <button class="btn btn-sm btn-success" onclick="showFeedbackModal(<?php echo $complaint['id']; ?>)">
                                                            <i class="fas fa-star"></i> Rate
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complaint View Modal -->
<div class="modal" id="complaintModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-comment-alt"></i> Complaint Details</h4>
                <button type="button" class="close" onclick="closeComplaintModal()">&times;</button>
            </div>
            <div class="modal-body" id="complaintContent">
                <!-- Complaint content will be dynamically inserted here -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeComplaintModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div class="modal" id="feedbackModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-star"></i> Rate Your Experience</h4>
                <button type="button" class="close" onclick="closeFeedbackModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="feedbackForm" method="POST" action="complaints.php">
                    <input type="hidden" name="action" value="add_feedback">
                    <input type="hidden" name="complaint_id" id="feedback_complaint_id" value="">
                    
                    <div class="feedback-info">
                        <div class="feedback-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="feedback-text">
                            <p>Your feedback helps us improve our services. Please rate your satisfaction with how your complaint was handled.</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>How satisfied are you with the resolution?</label>
                        <div class="rating-stars">
                            <i class="fas fa-star" data-rating="1"></i>
                            <i class="fas fa-star" data-rating="2"></i>
                            <i class="fas fa-star" data-rating="3"></i>
                            <i class="fas fa-star" data-rating="4"></i>
                            <i class="fas fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" id="rating" name="rating" value="0" required>
                        <div class="rating-text">Select a rating</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="feedback">Your Feedback</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="4" placeholder="Please share your thoughts on how your complaint was handled" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeFeedbackModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitFeedback()"><i class="fas fa-paper-plane"></i> Submit Feedback</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="deleteModalLabel"><i class="fas fa-trash"></i> Delete Complaint</h4>
                <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="closeDeleteModal()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this complaint? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form action="complaints.php" method="POST">
                    <input type="hidden" name="action" value="delete_complaint">
                    <input type="hidden" name="complaint_id" id="delete_complaint_id">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- All JavaScript functionality has been moved to js/complaints.js -->

<?php
// Include footer
require_once '../shared/includes/footer.php';
?>
